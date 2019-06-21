<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
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

	private static function get_specific_recipient_option( array $specific_recipients ): array {
		return array(
			'label'    => sprintf( 'Specifika personer (%d st)', count( $specific_recipients ) ),
			'selector' => function ( Person $person ) use ( $specific_recipients ) {
				return in_array( $person->id, $specific_recipients );
			}
		);
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

				$warnings   = [];
				$person_dao = new PersonDao();
				$people     = [];
				foreach ( $selected_groups as $selected_group ) {
					$group_members = array_filter( $person_dao->get_all_in_group( $selected_group->id ), $people_selector['selector'] );
					if ( count( $group_members ) == 0 ) {
						$warnings[] = sprintf( 'Inga kontakter för %s', $selected_group->name );
					} else if ( count( $group_members ) > 2 ) {
						$warnings[] = sprintf( 'Fler än två kontakter för %s', $selected_group->name );
					}
					$people        = array_merge( $people, $group_members );
				}

				$body_template    = Template::string( $_POST['tuja_messages_body'] );
				$subject_template = Template::string( $_POST['tuja_messages_subject'] );

				$variables = array_merge( $body_template->get_variables(), $subject_template->get_variables() );

				$recipients_data = array_map( function ( $person ) use ( $delivery_method, $variables, $groups, $subject_template, $body_template, $is_send ) {
					$group               = reset( array_filter( $groups, function ( $grp ) use ( $person ) {
						return $grp->id == $person->group_id;
					} ) );
					$template_parameters = $this->get_parameters( $person, $group );
					$message_generator   = $delivery_method['message_generator'];
					$outgoing_message    = $message_generator( $person, $subject_template, $body_template, $template_parameters );
					$message             = 'OK';
					$message_css_class   = 'tuja-admin-review-autoscore-good';
					$success             = false;
					try {
						if ( $is_send ) {
							$outgoing_message->send();
							$message = 'Meddelande har skickats';
						} else {
							$outgoing_message->validate();
						}
						$success = true;
					} catch ( Exception $e ) {
						$message           = $e->getMessage();
						$message_css_class = 'tuja-admin-review-autoscore-poor';
					}

					return [
						'template_parameters' => $template_parameters,
						'success'             => $success,
						'message'             => $message,
						'message_css_class'   => $message_css_class,
						'person_id'           => $person->id,
						'person_name'         => $person->name,
						'group_name'          => $group->name,
						'is_plain_text_body'  => $delivery_method['is_plain_text_body']
					];
				}, $people );

				$retry_people_ids = [];
				foreach ( $recipients_data as $data ) {
					if ( ! $data['success'] ) {
						$retry_people_ids[] = $data['person_id'];
						$warnings[]         = sprintf( 'Problem för %s i %s. Välj mottagare "Specifika personer" för att skicka om.', $data['person_name'], $data['group_name'] );
					}
				}

				return [
					'body_template'    => $body_template,
					'subject_template' => $subject_template,
					'variables'        => $variables,
					'warnings'         => $warnings,
					'retry_people_ids' => $retry_people_ids,
					'recipients'       => $recipients_data
				];
			}
		}
	}

	public function output() {
		$competition = $this->competition;

		$group_category_dao     = new GroupCategoryDao();
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
					'selector' => function ( Group $group ) {
						return true;
					}
				),
				array(
					'label'    => 'Alla tävlande grupper',
					'selector' => function ( Group $group ) use ( $crew_category_ids ) {
						$category = $group->get_derived_group_category();

						return isset( $category ) && ! in_array( $category->id, $crew_category_ids );
					}
				),
				array(
					'label'    => 'Alla tävlande grupper med ofullständiga anmälningar',
					'selector' => function ( Group $group ) use ( $crew_category_ids ) {
						$category = $group->get_derived_group_category();

						$is_competing_group = isset( $category ) && ! in_array( $category->id, $crew_category_ids );

						if ( $is_competing_group ) {
							$result = $group->evaluate_registration();

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
					'selector' => function ( Group $group ) use ( $crew_category_ids ) {
						$category = $group->get_derived_group_category();

						return isset( $category ) && in_array( $category->id, $crew_category_ids );
					}
				),
			),
			array_map(
				function ( $category ) {
					return array(
						'label'    => 'Alla grupper i kategorin ' . $category->name,
						'selector' => function ( Group $group ) use ( $category ) {
							$group_category = $group->get_derived_group_category();

							return isset( $group_category ) && $group_category->id === $category->id;
						}
					);
				},
				$group_categories ),
			array_map(
				function ( $selected_group ) {
					return array(
						'label'    => 'Gruppen ' . $selected_group->name,
						'selector' => function ( Group $group ) use ( $selected_group ) {
							return $group->id === $selected_group->id;
						}
					);
				},
				$groups ) );

		$people_selectors    = array(
			'all'              => array(
				'label'    => 'Alla',
				'selector' => function ( Person $person ) {
					return true;
				}
			),
			'contacts' => array(
				'label'    => 'Kontaktpersoner',
				'selector' => function ( Person $person ) {
					return $person->is_group_contact;
				}
			),
			'contacts_and_non_competitors' => array(
				'label'    => 'Kontaktpersoner och medföljare',
				'selector' => function ( Person $person ) {
					return $person->is_group_contact || ! $person->is_competing;
				}
			)
		);
		$specific_recipients = [];
		if ( isset( $_POST['tuja_messages_specificrecipients'] ) ) {
			// Set of recipients already set. Use this data to enable the send-to-specific-people option during execution of handle_post()
			$specific_recipients          = explode( ',', $_POST['tuja_messages_specificrecipients'] );
			$people_selectors['specific'] = self::get_specific_recipient_option( $specific_recipients );
		}

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

		if ( ! empty( $action_result['retry_people_ids'] ) ) {
			// We failed to sent to some recipients. Enable the send-to-specific-people option when the page is rendered.
			$specific_recipients          = $action_result['retry_people_ids'];
			$people_selectors['specific'] = self::get_specific_recipient_option( $specific_recipients );
		}

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
