<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Person;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\util\GroupCategoryCalculator;
use tuja\util\rules\RegistrationEvaluator;
use tuja\util\rules\RuleResult;
use tuja\util\Template;
use tuja\util\messaging\MessageSender;
use tuja\util\messaging\OutgoingEmailMessage;
use tuja\util\messaging\OutgoingSMSMessage;

class MessagesSend {
	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post( $group_selectors, $people_selectors, $delivery_methods, $groups ) {
		if ( ! isset( $_POST['tuja_messages_action'] ) ) {
			return [];
		}

		$is_preview = $_POST['tuja_messages_action'] === 'preview';
		$is_send    = $_POST['tuja_messages_action'] === 'send';

		if ( $is_preview || $is_send ) {
			$group_selector  = $group_selectors[ intval( $_POST['tuja_messages_group_selector'] ) ];
			$people_selector = $people_selectors[ $_POST['tuja_messages_people_selector'] ];
			$delivery_method = $delivery_methods[ $_POST['tuja_messages_delivery_method'] ];
			if ( isset( $group_selector ) && isset( $people_selector ) && isset( $delivery_method ) ) {
				$selected_groups = array_filter( $groups, $group_selector['selector'] );

				$person_dao = new PersonDao();
				$people     = [];
				foreach ( $selected_groups as $selected_group ) {
					$group_members = array_filter( $person_dao->get_all_in_group( $selected_group->id ), $people_selector['selector'] );
					$people        = array_merge( $people, $group_members );
				}

				$body_template    = Template::string( $_POST['tuja_messages_body'] );
				$subject_template = Template::string( $_POST['tuja_messages_subject'] );

				$variables = array_merge( $body_template->get_variables(), $subject_template->get_variables() );

				return [
					'body_template'    => $body_template,
					'subject_template' => $subject_template,
					'variables'        => $variables,
					'recipients'       => array_map( function ( $person ) use ( $delivery_method, $variables, $groups, $subject_template, $body_template, $is_send ) {
						$group               = reset( array_filter( $groups, function ( $grp ) use ( $person ) {
							return $grp->id == $person->group_id;
						} ) );
						$template_parameters = $this->get_parameters( $person, $group );
						$message_generator   = $delivery_method['message_generator'];
						$outgoing_message    = $message_generator( $person, $subject_template, $body_template, $template_parameters );
						$message             = 'OK';
						$message_css_class   = 'tuja-admin-review-autoscore-good';
						try {
							if ( $is_send ) {
								$outgoing_message->send();
								$message = 'Meddelande har skickats';
							} else {
								$outgoing_message->validate();
							}
						} catch ( Exception $e ) {
							$message           = $e->getMessage();
							$message_css_class = 'tuja-admin-review-autoscore-poor';
						}

						return [
							'template_parameters' => $template_parameters,
							'message'             => $message,
							'message_css_class'   => $message_css_class,
							'person_name'         => $person->name,
							'is_plain_text_body'  => $delivery_method['is_plain_text_body']
						];
					}, $people )

				];
			}
		}
	}

	public function output() {
		$competition = $this->competition;

		$group_category_dao     = new GroupCategoryDao();
		$category_calculator    = new GroupCategoryCalculator( $competition->id );
		$registration_evaluator = new RegistrationEvaluator( $competition->id );
		$group_categories       = $group_category_dao->get_all_in_competition( $competition->id );
		$crew_category_ids      = array_map( function ( $category ) {
			return $category->id;
		}, array_filter( $group_categories, function ( $category ) {
			return $category->is_crew;
		} ) );

		$group_dao       = new GroupDao();
		$groups          = $group_dao->get_all_in_competition( $competition->id );
		$group_selectors = array_merge(
			array(
				array(
					'label'    => 'Alla grupper, inkl. funk',
					'selector' => function ( $group ) {
						return true;
					}
				),
				array(
					'label'    => 'Alla tävlande grupper',
					'selector' => function ( $group ) use ( $crew_category_ids, $category_calculator ) {
						$category = $category_calculator->get_category( $group );

						return ! in_array( $category->id, $crew_category_ids );
					}
				),
				array(
					'label'    => 'Alla tävlande grupper med ofullständiga anmälningar',
					'selector' => function ( $group ) use ( $crew_category_ids, $category_calculator, $registration_evaluator ) {
						$category = $category_calculator->get_category( $group );

						$is_competing_group = ! in_array( $category->id, $crew_category_ids );

						if ( $is_competing_group ) {
							$result = $registration_evaluator->evaluate( $group );

							$count_registration_issues = count( array_filter( $result, function ( $eval_res ) {
								return $eval_res->status !== RuleResult::OK;
							} ) );

							return $count_registration_issues > 0;
						} else {
							return false;
						}
					}
				),
				array(
					'label'    => 'Alla funktionärsgrupper',
					'selector' => function ( $group ) use ( $crew_category_ids, $category_calculator ) {
						$category = $category_calculator->get_category( $group );

						return in_array( $category->id, $crew_category_ids );
					}
				),
			),
			array_map(
				function ( $category ) use ( $category_calculator ) {
					return array(
						'label'    => 'Alla grupper i kategorin ' . $category->name,
						'selector' => function ( $group ) use ( $category, $category_calculator ) {
							$group_category = $category_calculator->get_category( $group );

							return $group_category->id === $category->id;
						}
					);
				},
				$group_categories ),
			array_map(
				function ( $selected_group ) {
					return array(
						'label'    => 'Gruppen ' . $selected_group->name,
						'selector' => function ( $group ) use ( $selected_group ) {
							return $group->id === $selected_group->id;
						}
					);
				},
				$groups ) );

		$people_selectors = array(
			'all'              => array(
				'label'    => 'Alla personer i valda grupper',
				'selector' => function ( $person ) {
					return true;
				}
			),
			'primary_contacts' => array(
				'label'    => 'Enbart valda gruppers primära kontaktpersoner',
				'selector' => function ( $person ) {
					return $person->is_group_contact;
				}
			)
		);

		$delivery_methods = array(
			'sms'   => array(
				'label'              => 'SMS',
				'message_generator'  => function ( Person $person, $subject_template, $body_template, $template_parameters ) {
					return new OutgoingSMSMessage(
						new MessageSender(),
						$person,
						$body_template->render( $template_parameters ) );
				},
				'is_plain_text_body' => true
			),
			'email' => array(
				'label'              => 'E-post',
				'message_generator'  => function ( Person $person, $subject_template, $body_template, $template_parameters ) {
					return new OutgoingEmailMessage(
						new MessageSender(),
						$person,
						$body_template->render( $template_parameters, true ),
						$subject_template->render( $template_parameters ) );
				},
				'is_plain_text_body' => false
			)
		);

		$groups = $group_dao->get_all_in_competition( $this->competition->id );

		$action_result = $this->handle_post( $group_selectors, $people_selectors, $delivery_methods, $groups );

		$message_template_dao = new MessageTemplateDao();
		$templates            = $message_template_dao->get_all_in_competition( $competition->id );

		$settings_url = add_query_arg( array(
			'tuja_competition' => $competition->id,
			'tuja_view'        => 'CompetitionSettings'
		) );

		$is_preview = $_POST['tuja_messages_action'] === 'preview';
		$is_send    = $_POST['tuja_messages_action'] === 'send';

		include( 'views/messages-send.php' );
	}


	public function get_parameters( $person, $group ) {
		return array_merge(
			Template::group_parameters( $group ),
			Template::person_parameters( $person ),
			Template::site_parameters()
		);
	}
}
