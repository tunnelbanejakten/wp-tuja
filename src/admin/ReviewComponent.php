<?php

namespace tuja\admin;


use DateTime;
use Exception;
use ReflectionClass;
use tuja\data\model\Points;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\data\store\AbstractDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\model\Competition;
use tuja\util\score\ScoreCalculator;


class ReviewComponent {

	const FORM_FIELD_TIMESTAMP = 'tuja_review_timestamp';
	const FORM_FIELD_OVERRIDE_PREFIX = 'tuja_review_points';
	const FORM_FIELD_CHANGE_CORRECT_ANSWER_PREFIX = 'tuja_review_change_correct_answer';
	const ACTION_SET_CORRECT = 'set_correct';
	const ACTION_UNSET_CORRECT = 'unset_correct'; // TODO: Start using this feature
	const ACTION_SET_INCORRECT = 'set_incorrect';
	const ACTION_UNSET_INCORRECT = 'unset_incorrect'; // TODO: Start using this feature

	private $response_dao;
	private $points_dao;
	private $question_dao;
	private $question_group_dao;
	private $group_dao;

	private $competition;

	public function __construct( Competition $competition ) {
		$this->response_dao       = new ResponseDao();
		$this->points_dao         = new PointsDao();
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();

		$this->competition = $competition;
	}

	private static function get_auto_score_html( $max_score, $auto_score, $auto_confidence ): string {
		return sprintf( '<span class="tuja-admin-review-autoscore %s" title="Auto-rättaren är %d &percnt; säker på att den gjort rätt bedömning.">%s p</span>',
			$max_score > 0 ? AdminUtils::getScoreCssClass( $auto_score ?: 0.0 / $max_score ) : '',
			$auto_confidence * 100,
			$auto_score ?: 0.0 );
	}

	public function handle_post( $selected_filter, $selected_groups ) {

		//
		// Get information about points we WANT to update:
		//
		$form_values = array_filter( $_POST, function ( $key ) {
			return substr( $key, 0, strlen( self::FORM_FIELD_OVERRIDE_PREFIX ) ) === self::FORM_FIELD_OVERRIDE_PREFIX;
		}, ARRAY_FILTER_USE_KEY );

		//
		// Get information about what we CAN update:
		//
		$data = $this->get_data( $selected_filter, $selected_groups );

		$reviewable_responses = [];
		foreach ( $data as $form_id => $form_entry ) {
			foreach ( $form_entry['questions'] as $question_id => $question_entry ) {
				foreach ( $question_entry['responses'] as $group_id => $response_entry ) {
					$response = isset( $response_entry ) ? $response_entry['response'] : null;
					if ( isset( $response ) ) {
						$reviewable_responses[] = $response;
					}
				}
			}
		}

		$overrides_timestamp = AbstractDao::from_db_date( $_POST[ self::FORM_FIELD_TIMESTAMP ] );

		$new_overrides = array_map(
			function ( Points $override ) {
				return sprintf( '%d__%d', $override->form_question_id, $override->group_id );
			},
			array_filter(
				$this->points_dao->get_by_competition( $this->competition->id ),
				function ( Points $override ) use ( $overrides_timestamp ) {
					return $override->created > $overrides_timestamp;
				} ) );


		foreach ( $reviewable_responses as $response ) {
			try {
				switch ( @$_POST[ self::FORM_FIELD_CHANGE_CORRECT_ANSWER_PREFIX . '__' . $response->id ] ) {
					case self::ACTION_SET_CORRECT:
						$this->set_correct_answer( $response );
						break;
					case self::ACTION_UNSET_CORRECT:
						$this->unset_correct_answer( $response );
						break;
					case self::ACTION_SET_INCORRECT:
						$this->set_incorrect_answer( $response );
						break;
					case self::ACTION_UNSET_INCORRECT:
						$this->unset_incorrect_answer( $response );
						break;
				}
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}

		//
		// Perform updates:
		//
		$skipped      = 0;
		$reviewed_ids = [];
		foreach ( $form_values as $field_name => $field_value ) {
			list( , $response_id, $question_id, $group_id ) = explode( '__', $field_name );
			$newer_override_exists = in_array( sprintf( '%d__%d', $question_id, $group_id ), $new_overrides );
			if ( $newer_override_exists ) {
				// Another user (reviewer) set points for this question while the user completed the form.
				// Use the other user's points instead, don't replace conflicting override points.
				$skipped ++;
				continue;
			}

			if ( $response_id != Review::RESPONSE_MISSING_ID ) {
				// Response exists, but is the response shown still the most recent one?

				$is_response_submitted = count( array_filter( $reviewable_responses, function ( Response $response ) use ( $response_id ) {
						return $response->id == $response_id;
					} ) ) > 0;
				if ( $is_response_submitted ) {
					// Yes, this response can still be reviewed (it is the most recent one).

					$this->points_dao->set(
						$group_id,
						$question_id,
						is_numeric( $field_value ) ? intval( $field_value ) : null );

					$reviewed_ids[] = $response_id;
				} else {
					$skipped ++;
				}
			} else {
				// Response did not exist when form was loaded but maybe one exists now?

				$is_response_submitted = count( array_filter( $reviewable_responses, function ( Response $response ) use ( $question_id, $group_id ) {
						return $response->group_id == $group_id && $response->form_question_id == $question_id;
					} ) ) > 0;

				if ( ! $is_response_submitted ) {
					// No, there is still no response from this team.

					$this->points_dao->set(
						$group_id,
						$question_id,
						is_numeric( $field_value ) ? intval( $field_value ) : null );
				} else {
					$skipped ++;
				}
			}
		}
		$this->response_dao->mark_as_reviewed( $reviewed_ids );

		return [
			'skipped'            => $skipped,
			'marked_as_reviewed' => $reviewed_ids
		];
	}

	private function set_correct_answer( Response $response ) {
		return $this->perform_autoscore_config_change( $response, 'array_merge', 'correct_answers' );
	}

	private function unset_correct_answer( Response $response ) {
		return $this->perform_autoscore_config_change( $response, 'array_diff', 'correct_answers' );
	}

	private function set_incorrect_answer( Response $response ) {
		return $this->perform_autoscore_config_change( $response, 'array_merge', 'incorrect_answers' );
	}

	private function unset_incorrect_answer( Response $response ) {
		return $this->perform_autoscore_config_change( $response, 'array_diff', 'incorrect_answers' );
	}

	private function perform_autoscore_config_change( Response $response, callable $array_func, $prop_name ) {
		$question = $this->question_dao->get( $response->form_question_id );

		$class = new ReflectionClass( $question );
		if ( $class->hasProperty( $prop_name ) ) {
			// Assume submitted answer is stored as array
			$question->{$prop_name} = $array_func(
				$question->{$prop_name},
				$response->submitted_answer );

		} else {
			throw new Exception( sprintf(
				'%s lacks property %s.',
				$class->getShortName(),
				$prop_name ) );
		}

		return $this->question_dao->update( $question );
	}

	public function render( $selected_filter, $selected_groups, $hide_groups_without_responses = false ) {
		$groups     = $this->group_dao->get_all_in_competition( $this->competition->id );
		$groups_map = array_combine( array_map( function ( $group ) {
			return $group->id;
		}, $groups ), array_values( $groups ) );

		$question_groups = $this->question_group_dao->get_all_in_competition( $this->competition->id );
		$question_groups = array_combine( array_map( function ( QuestionGroup $qg ) {
			return $qg->id;
		}, $question_groups ), $question_groups );

		$current_points = $this->points_dao->get_by_competition( $this->competition->id );
		$current_points = array_combine(
			array_map( function ( $points ) {
				return $points->form_question_id . '__' . $points->group_id;
			}, $current_points ),
			array_values( $current_points )
		);

		$data = $this->get_data( $selected_filter, $selected_groups );

		if ( empty( $data ) ) {
			printf( '<p>Det finns inget att visa.</p>' );

			return;
		}
		print ( '
	        <table class="tuja-admin-review"><tbody>
	            <tr>
	                <td><div class="spacer"></div></td>
	                <td><div class="spacer"></div></td>
	                <td colspan="4"></td>
	            </tr>' );
		$response_ids = [];
		$limit        = 2000;

		foreach ( $data as $form_id => $form_entry ) {
			printf( '<tr class="tuja-admin-review-form-row"><td colspan="6"><strong>%s</strong></td></tr>', $form_entry['form']->name );
			foreach ( $form_entry['questions'] as $question_id => $question_entry ) {

				$question            = $question_entry['question'];
				$question_group_text = $question_groups[ $question->question_group_id ]->text;
				$question_text       = $question_group_text
					? $question_group_text . " : " . $question->text
					: $question->text;
				printf( '<tr class="tuja-admin-review-question-row"><td></td><td colspan="5"><strong>%s</strong></td></tr>',
					$question_text );

				$answer_html = $question->get_correct_answer_html();
				if ( ! empty( $answer_html ) ) {
					printf( '' .
					        '<tr class="tuja-admin-review-correctanswer-row">' .
					        '  <td colspan="2"></td>' .
					        '  <td valign="top">Rätt svar</td>' .
					        '  <td valign="top" colspan="3">%s</td>' .
					        '</tr>',
						$answer_html );
				}

				foreach ( $selected_groups as $group ) {
					$group_id  = $group->id;
					$group_url = add_query_arg( array(
						'tuja_group' => $group_id,
						'tuja_view'  => 'Group'
					) );

					$response_entry = @$question_entry['responses'][ $group_id ];

					if ( $hide_groups_without_responses && ! isset( $response_entry ) ) {
						continue;
					}

					$points                = @$current_points[ $question_id . '__' . $group_id ] ?: null;
					$response              = isset( $response_entry ) ? $response_entry['response'] : null;
					$score_question_result = ScoreCalculator::score_combined( $response, $question, $points );
					if ( isset( $response ) ) {
						$response_ids[] = $response->id;
						$response_html  = $question->get_submitted_answer_html( $response->submitted_answer, $groups_map[ $response->group_id ] );

						$auto_score_html = self::get_auto_score_html( $question->score_max, $score_question_result->auto, $score_question_result->auto_confidence );

						$is_valid_answer_submitted = $response_html != AbstractQuestion::RESPONSE_MISSING_HTML;
						$is_auto_score_unreliable  = $score_question_result->auto_confidence < 1.0 || $score_question_result->auto < $question->score_max;
						if ( $is_valid_answer_submitted && $is_auto_score_unreliable ) {
							$is_add_incorrect = $score_question_result->auto > 0;
							$action_field_id  = self::FORM_FIELD_CHANGE_CORRECT_ANSWER_PREFIX . '__' . $response->id;
							$popup_html       = sprintf( '
								<input type="hidden" name="%s" id="%s" value="">
								<div id="tuja-admin-change-autoscore-%d" style="display:none;">
									<div>
									     <p>
									          Vill du ändra rättningsmallen så att <strong>%s</strong> räknas som ett <strong>%s</strong> svar? 
									     </p>
									     <div>
									        <button type="button" class="button button-primary tuja-admin-review-button-yes" data-value="%s" data-target-field="%s">Ja</button>
									        <button type="button" class="button tuja-admin-review-button-no" data-target-field="%s">Nej</button>
								        </div>
									     <p>
									        <em>Din ändring kommer påverka rättningen av alla svar på denna fråga, både existerande och framtida, under förutsättning att manuell poäng inte satts.</em>									     
									     </p>
									</div>
								</div>',
								$action_field_id,
								$action_field_id,
								$response->id,
								$response_html,
								$is_add_incorrect ? 'felaktigt' : 'korrekt',
								$is_add_incorrect ? self::ACTION_SET_INCORRECT : self::ACTION_SET_CORRECT,
								$action_field_id,
								$action_field_id );

							$change_auto_scoring_html = sprintf(
								                            '<a class="thickbox" title="Edit" href="#TB_inline?width=300&height=200&inlineId=tuja-admin-change-autoscore-%d">%s</a>',
								                            $response->id,
								                            $is_add_incorrect ? 'Skulle inte godkänts' : 'Skulle ha godkänts'
							                            ) . $popup_html;

							$auto_score_html = sprintf( '<div class="tuja-admin-review-change-autoscore-container">%s<span class="tuja-admin-review-corrected-autoscore">%s</span><span class="tuja-admin-review-original-autoscore">%s</span></div>',
								$change_auto_scoring_html,
								$is_add_incorrect ? self::get_auto_score_html( $question->score_max, 0, 1.0 ) : self::get_auto_score_html( $question->score_max, $question->score_max, 1.0 ),
								$auto_score_html );
						}
						$response_id = $response->id;
					} else {
						$auto_score_html = '';
						$response_html   = AbstractQuestion::RESPONSE_MISSING_HTML;
						$response_id     = Review::RESPONSE_MISSING_ID;
					}
					$score_field_value = isset( $score_question_result->override ) ? $score_question_result->override : '';
					printf( '' .
					        '<tr class="tuja-admin-review-response-row">' .
					        '  <td colspan="2"></td>' .
					        '  <td valign="top"><a href="%s" class="tuja-admin-review-group-link">%s</a></td>' .
					        '  <td valign="top">%s</td>' .
					        '  <td valign="top" align="right">%s</td>' .
					        '  <td valign="top"><input type="number" name="%s" value="%s" size="5" min="0" max="%d"></td>' .
					        '</tr>',
						$group_url,
						$groups_map[ $group_id ]->name,
						$response_html,
						$auto_score_html,
						join( '__', [ self::FORM_FIELD_OVERRIDE_PREFIX, $response_id, $question_id, $group_id ] ),
						$score_field_value,
						$question->score_max ?: 1000 );
					$limit = $limit - 1;
				}
				if ( $limit < 0 ) {
					break 2;
				}
			}
		}
		print ( '</tbody></table>' );
		if ( $limit < 0 ) {
			printf( '<p><em>Alla frågor visas inte.</em></p>' );
		}
		printf( '<input type="hidden" name="%s" value="%s">', self::FORM_FIELD_TIMESTAMP, AbstractDao::to_db_date( new DateTime() ) );
	}

	private function get_data( $selected_filter, $selected_groups ): array {
		return isset( $selected_filter )
			? $this->response_dao->get_by_questions(
				$this->competition->id,
				$selected_filter,
				array_map( function ( Group $group ) {
					return $group->id;
				}, $selected_groups ) )
			: [];
	}
}