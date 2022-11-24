<?php

namespace tuja\admin;


use DateTime;
use Exception;
use ReflectionClass;
use tuja\data\model\Points;
use tuja\data\model\Event;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\data\store\AbstractDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\model\Competition;
use tuja\data\store\EventDao;
use tuja\util\score\ScoreCalculator;


class ReviewComponent {

	const FORM_FIELD_TIMESTAMP = 'tuja_review_timestamp';
	const FORM_FIELD_OVERRIDE_PREFIX = 'tuja_review_points';
	const RESPONSE_CONTAINER_PREFIX = 'tuja_review_response_container';
	const AUTO_SCORE_CONTAINER_PREFIX = 'tuja_review_auto_score';
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
	private $event_dao;

	private $competition;

	public function __construct( Competition $competition ) {
		$this->response_dao       = new ResponseDao();
		$this->points_dao         = new QuestionPointsOverrideDao();
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();
		$this->event_dao          = new EventDao();

		$this->competition = $competition;
	}

	private static function get_auto_score_html( $max_score, $auto_score, $auto_confidence ): string {
		return sprintf( '<span class="tuja-admin-review-autoscore %s" title="Auto-rättaren är %d &percnt; säker på att den gjort rätt bedömning.">%s</span>',
			$max_score > 0 ? AdminUtils::getScoreCssClass( ( $auto_score ?: 0.0 ) / $max_score ) : '',
			$auto_confidence * 100,
			$auto_score ?: 0.0 );
	}

	private static function get_set_score_html( $manual_score_field_name, $max_score, $score ): string {
		return sprintf( '<span class="tuja-admin-review-autoscore %s"><a href="#" class="tuja-admin-review-set-score" data-target-field="%s" data-score="%d">%d</a></span>',
			$max_score > 0 ? AdminUtils::getScoreCssClass( ( $score ?: 0.0 ) / $max_score ) : '',
			$manual_score_field_name,
			$score ?: 0.0,
			$score ?: 0.0 );
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

	public function render( $selected_filter, $selected_groups, $hide_groups_without_responses, $button_name, $button_value ) {
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

		$events_for_all_groups = array_reduce(
			$selected_groups,
			function ( $res, Group $selected_group ) {
				$events_of_interest = array_filter(
					$this->event_dao->get_by_group( $selected_group->id ),
					function ( Event $event ) {
						return $event->event_name === Event::EVENT_VIEW &&
							$event->object_type === Event::OBJECT_TYPE_QUESTION;
					}
				);
				return array_merge( $res, $events_of_interest );
			},
			array()
		);

		$view_question_events = array_reduce(
			$events_for_all_groups,
			function ( $res, Event $event ) {
				$key = $event->object_id . '__' . $event->group_id;
				if ( ! isset( $res[ $key ] ) ) {
					$res[ $key ] = $event;
				}
				return $res;
			},
			array()
		);

		$data = $this->get_data( $selected_filter, $selected_groups );

		if ( empty( $data ) ) {
			printf( '<p class="tuja-admin-review-form-empty">Det finns inget att visa.</p>' );

			return;
		}
		print ( '
	        <table class="tuja-admin-review tuja-table"><tbody>
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

				$question                  = $question_entry['question'];
				$question_group_text       = $question_groups[ $question->question_group_id ]->text;
				$question_text             = join( ': ', array_filter( array( $question->name, $question_group_text, $question->text ) ) );
				$answer_html               = $question->get_correct_answer_html();
				$is_correct_answer_defined = ! empty( $answer_html );
				$rowspan                   = $is_correct_answer_defined ? 2 : 1;
				$valign                    = $is_correct_answer_defined ? 'bottom' : 'bottom';
				$auto_score_header         = $is_correct_answer_defined ? 'Autorättning ' . AdminUtils::tooltip( 'Systemets egen rättning. Denna poäng används om du inte anger manuell poäng. Klicka på Felrättat om du vill ändra "rättningsmallen" för en fråga så att systemet rättar frågan bättre nästa gång.' ) : '';
				printf( '
					<tr class="tuja-admin-review-question-row">
						<td></td>
						<td colspan="3"><strong>%s</strong></td>
						<td valign="%s" rowspan="%s">%s</td>
						<td valign="%s" rowspan="%s">Manuell %s</td>
						<td valign="%s" rowspan="%s">Slutlig</td>
					</tr>
					',
					$question_text,
					$valign,
					$rowspan,
					$auto_score_header,
					$valign,
					$rowspan,
					AdminUtils::tooltip( 'Klicka på siffrorna, eller skriv in poäng själv, för att poängsätta svaret. Manuell poäng ersätter eventuell poäng från autorättningen.' ),
					$valign,
					$rowspan
				);

				if ( $is_correct_answer_defined ) {
					printf( '
					        <tr class="tuja-admin-review-correctanswer-row">
					          <td colspan="2"></td>
					          <td valign="top">Rätt svar</td>
					          <td valign="top">%s</td>
					        </tr>',
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
					$first_view_event      = @$view_question_events[ $question_id . '__' . $group_id ];
					$score_question_result = ScoreCalculator::score_combined( $response, $question, $points, $group, $first_view_event );
					if ( isset( $response ) ) {
						$response_ids[] = $response->id;
						$response_html  = $question->get_submitted_answer_html( $response->submitted_answer, $groups_map[ $response->group_id ] );

						$is_valid_answer_submitted = $response_html != AbstractQuestion::RESPONSE_MISSING_HTML;
						$is_auto_score_unreliable  = $score_question_result->auto_confidence < 1.0 || $score_question_result->auto < $question->score_max;
						if ( $is_valid_answer_submitted && $is_auto_score_unreliable ) {
							$is_add_incorrect = $score_question_result->auto > 0;
							$action_field_id  = self::FORM_FIELD_CHANGE_CORRECT_ANSWER_PREFIX . '__' . $response->id;
							$popup_html       = $this->get_popup_html( $action_field_id, $response->id, $response_html, $is_add_incorrect );

							$auto_score_html = $is_correct_answer_defined ? sprintf( '
									<span class="tuja-admin-review-corrected-autoscore">%s</span>
									<span class="tuja-admin-review-original-autoscore">%s</span>
									<a class="thickbox" title="Edit" href="#TB_inline?width=300&height=200&inlineId=tuja-admin-change-autoscore-%d">%s</a>
									%s',
								$is_add_incorrect
									? self::get_auto_score_html( $question->score_max, 0, 1.0 )
									: self::get_auto_score_html( $question->score_max, $question->score_max, 1.0 ),
								self::get_auto_score_html( $question->score_max, $score_question_result->auto, $score_question_result->auto_confidence ),
								$response->id,
								'Felrättat',
								$popup_html ) : '';
						} else {
							$auto_score_html = sprintf( '<span class="tuja-admin-review-original-autoscore">%s</span>',
								self::get_auto_score_html( $question->score_max, $score_question_result->auto, $score_question_result->auto_confidence ) );
						}
						$response_id = $response->id;
					} else {
						$auto_score_html = '';
						$response_html   = AbstractQuestion::RESPONSE_MISSING_HTML;
						$response_id     = Review::RESPONSE_MISSING_ID;
					}
					$score_field_value = isset( $score_question_result->override ) ? $score_question_result->override : '';

					$response_html_container_id  = join( '__', [
						self::RESPONSE_CONTAINER_PREFIX,
						$question_id,
						$group_id
					] );
					$auto_score_container_id  = join( '__', [
						self::AUTO_SCORE_CONTAINER_PREFIX,
						$question_id,
						$group_id
					] );
					$manual_score_field_name  = join( '__', [
						self::FORM_FIELD_OVERRIDE_PREFIX,
						$response_id,
						$question_id,
						$group_id
					] );
					$manual_score_field_id  = join( '__', [
						self::FORM_FIELD_OVERRIDE_PREFIX,
						$question_id,
						$group_id
					] );
					$manual_score_preset_full = $question->score_max;
					$manual_score_preset_half = round( $question->score_max / 2 );
					$manual_score_presets     = [];
					$manual_score_presets[]   = self::get_set_score_html( $manual_score_field_id, $question->score_max, $manual_score_preset_full );
					if ( $manual_score_preset_full != $manual_score_preset_half ) {
						$manual_score_presets[] = self::get_set_score_html( $manual_score_field_id, $question->score_max, $manual_score_preset_half );
					}
					$manual_score_presets[] = self::get_set_score_html( $manual_score_field_id, $question->score_max, 0 );

					printf( '' .
					        '<tr class="tuja-admin-review-response-row">
					          <td colspan="2"></td>
					          <td valign="center"><a href="%s" class="tuja-admin-review-group-link">%s</a></td>
					          <td valign="center"><span id="%s">%s</span></td>
					          <td valign="center"><div id="%s" class="tuja-admin-review-change-autoscore-container">%s</div></td>
					          <td valign="center">%s<input type="number" id="%s" name="%s" value="%s" size="5" min="0" max="%d"></td>
					          <td valign="center"><span class="tuja-admin-review-final-score">n p</span></td>
					        </tr>',
						$group_url,
						$groups_map[ $group_id ]->name,
						$response_html_container_id,
						$response_html,
						$auto_score_container_id,
						$auto_score_html,
						join( ' ', $manual_score_presets ),
						$manual_score_field_id,
						$manual_score_field_name,
						$score_field_value,
						$question->score_max ?: 1000
					);
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

		printf(
			'
			<button class="button button-primary" type="submit" name="%s" value="%s">
				Spara manuella poäng och markera svar som kontrollerade
			</button>
			',
			$button_name,
			$button_value,
		);
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

	private function get_popup_html( string $action_field_id, $response_id, string $response_html, bool $is_add_incorrect ): string {
		return sprintf( '
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
			$response_id,
			$response_html,
			$is_add_incorrect ? 'felaktigt' : 'korrekt',
			$is_add_incorrect ? self::ACTION_SET_INCORRECT : self::ACTION_SET_CORRECT,
			$action_field_id,
			$action_field_id );
	}
}