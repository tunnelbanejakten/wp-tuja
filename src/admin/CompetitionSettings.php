<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\StringsDao;
use tuja\util\DateUtils;
use tuja\util\messaging\EventMessageSender;
use tuja\util\rules\CrewMembersRuleSet;
use tuja\util\rules\OlderParticipantsRuleSet;
use tuja\util\rules\PassthroughRuleSet;
use tuja\util\rules\YoungParticipantsRuleSet;
use tuja\util\StateMachine;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

class CompetitionSettings {
	const FIELD_SEPARATOR = '__';

	const RULE_SETS = [
		PassthroughRuleSet::class       => 'Inga regler',
		YoungParticipantsRuleSet::class => 'Deltagare under 15 år',
		OlderParticipantsRuleSet::class => 'Deltagare över 15 år',
		CrewMembersRuleSet::class       => 'Funktionärer'
	];

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		$competition_dao = new CompetitionDao();
		$competition     = $competition_dao->get( $_GET['tuja_competition'] );

		if ( ! $competition ) {
			throw new Exception( 'Could not find competition' );
		}

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save_other( $competition );
			$this->competition_settings_save_groups( $competition );
			$this->competition_settings_save_message_templates( $competition );
			$this->competition_settings_save_strings( $competition );
		}
	}

	public function get_scripts(): array {
		return [
			'admin-competition-settings.js',
			'mermaid.min.js',
			'admin-templateeditor.js'
		];
	}

	public function output() {
		$this->handle_post();

		$competition_dao      = new CompetitionDao();
		$competition          = $competition_dao->get( $_GET['tuja_competition'] );
		$message_template_dao = new MessageTemplateDao();
		$category_dao         = new GroupCategoryDao();

		$message_templates = $message_template_dao->get_all_in_competition( $competition->id );

		$event_options = $this->get_event_options();

		$template_configs = [
			'awaiting_checkin.email'                         => [
				'name'                => 'Dags att checka in - E-post',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_ACCEPTED, \tuja\data\model\Group::STATUS_AWAITING_CHECKIN ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'awaiting_checkin.sms'                           => [
				'name'                => 'Dags att checka in - SMS',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_ACCEPTED, \tuja\data\model\Group::STATUS_AWAITING_CHECKIN ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::SMS
			],
			'group_accepted_by_default.groupcontact.email'   => [
				'name'                => 'Ny grupp - Direktanmäld',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_CREATED, \tuja\data\model\Group::STATUS_ACCEPTED ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'group_accepted_by_default.admin.email'          => [
				'name'                => 'Ny grupp - Direktanmäld (till Tuko)',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_CREATED, \tuja\data\model\Group::STATUS_ACCEPTED ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_ADMIN,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'group_accepted_from_waiting_list.email'         => [
				'name'                => 'Ny grupp - Inte längre på väntelistan',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_AWAITING_APPROVAL, \tuja\data\model\Group::STATUS_ACCEPTED ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'group_added_to_waiting_list.admin.email'        => [
				'name'                => 'Ny grupp - Tillagd på väntelistan (till Tuko)',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_CREATED, \tuja\data\model\Group::STATUS_AWAITING_APPROVAL ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_ADMIN,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'group_added_to_waiting_list.groupcontact.email' => [
				'name'                => 'Ny grupp - Tillagd på väntelistan',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_CREATED, \tuja\data\model\Group::STATUS_AWAITING_APPROVAL ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'signup_completed.email'                         => [
				'name'                => 'Anmälan komplett',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_INCOMPLETE_DATA, \tuja\data\model\Group::STATUS_ACCEPTED ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'signup_incomplete_data.email'                   => [
				'name'                => 'Anmälan behöver kompletteras',
				'auto_send_trigger'   => EventMessageSender::group_status_change_event_name( \tuja\data\model\Group::STATUS_ACCEPTED, \tuja\data\model\Group::STATUS_INCOMPLETE_DATA ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_GROUP_CONTACT,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'person_added.non_crew.email'                    => [
				'name'                => 'Ny person i tävlande lag',
				'auto_send_trigger'   => EventMessageSender::new_group_member_event_name( false ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_SELF,
				'delivery_method'     => MessageTemplate::EMAIL
			],
			'person_added.crew.email'                        => [
				'name'                => 'Ny person i funktionärslag',
				'auto_send_trigger'   => EventMessageSender::new_group_member_event_name( true ),
				'auto_send_recipient' => EventMessageSender::RECIPIENT_SELF,
				'delivery_method'     => MessageTemplate::EMAIL
			]
		];

		$default_message_templates = join( '<br>', array_map(
			function ( $key, $config ) {
				$strings           = parse_ini_file( __DIR__ . '/default_message_template/' . $key . '.ini' );
				$config['subject'] = $strings['subject'];
				$config['body']    = $strings['body'];

				return sprintf( '<button class="button tuja-add-messagetemplate" type="button" %s>Ny mall %s</button>', join( ' ', array_map( function ( $key, $value ) {
					return 'data-' . $key . '="' . htmlentities( $value ) . '"';
				}, array_keys( $config ), array_values( $config ) ) ), $config['name'] ?: basename( $filename, '.ini' ) );
			},
			array_keys( $template_configs ), array_values( $template_configs ) ) );

		$rules_html = [];
		$i          = 0;
		foreach ( self::RULE_SETS as $class_name => $label ) {
			if ( ! empty( $class_name ) ) {
				$rules = new $class_name;

				$indent = str_repeat( '&nbsp;', 4 );

				$rules_html[''][ $i ]                        = sprintf( '<strong>%s</strong>', $label );
				$rules_html['Antal i grupp'][ $i ]           = join( '-', $rules->get_group_size_range() );
				$rules_html['Vuxen medföljare'][ $i ]        = $rules->is_adult_supervisor_required() ? 'Ja, krav' : '-';
				$rules_html['Får rapportera poäng'][ $i ]    = $rules->is_crew() ? 'Ja' : '-';
				$rules_html['Sista dag för att'][ $i ]       = '';
				$rules_html[ $indent . '...anmäla' ][ $i ]   = $rules->get_create_registration_period( $competition )->end->format( 'd M' );
				$rules_html[ $indent . '...ändra' ][ $i ]    = $rules->get_update_registration_period( $competition )->end->format( 'd M' );
				$rules_html[ $indent . '...avanmäla' ][ $i ] = $rules->get_delete_registration_period( $competition )->end->format( 'd M' );

				$i = $i + 1;
			}
		}

		$group_status_transitions_definitions = StateMachine::as_mermaid_chart_definition( \tuja\data\model\Group::STATUS_TRANSITIONS );

		include( 'views/competition-settings.php' );
	}


	public function list_item_field_name( $list_name, $id, $field ) {
		return join( self::FIELD_SEPARATOR, array( $list_name, $field, $id ) );
	}


	public function submitted_list_item_ids( $list_name ): array {
		$prefix = $list_name . self::FIELD_SEPARATOR;
		// $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
		$person_prop_field_names = array_filter( array_keys( $_POST ), function ( $key ) use ( $prefix ) {
			return substr( $key, 0, strlen( $prefix ) ) === $prefix;
		} );

		// $all_ids will include duplicates (one for each of the name, email and phone fields).
		// $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
		$all_ids = array_map( function ( $key ) {
			list( , , $id ) = explode( self::FIELD_SEPARATOR, $key );

			return $id;
		}, $person_prop_field_names );

		return array_filter( array_unique( $all_ids ) /* No callback to outer array_filter means that empty strings will be skipped.*/ );
	}

	public function print_message_template_form( MessageTemplate $message_template ) {
		$auto_send_trigger_options = $this->get_auto_send_trigger_options( $message_template );

		$auto_send_recipient_options = $this->get_auto_send_recipient_options( $message_template );

		$delivery_method_options = $this->get_delivery_method_options( $message_template );

		return sprintf( '
			<div class="tuja-messagetemplate-form">
				<input type="text" placeholder="Mallens namn" size="50" name="%s" value="%s" class="tuja-messagetemplate-name" style="width: 100%%"><br>
				<div class="tuja-messagetemplate-collapsible tuja-messagetemplate-collapsed">
					<div class="tuja-messagetemplate-collapsecontrol">
						<a href="#">
							<span>Visa</span><span>Dölj</span>						
						</a>
					</div>
					<div class="tuja-messagetemplate-content">
						<select name="%s">%s</select>
						
						<br>
						
						<input type="text" placeholder="Ämnesrad" size="50" name="%s" value="%s" style="width: 100%%">
						
						<br>
						
						%s
						
						<br>
						
						<select name="%s">
							<option value="">Gör inget</option>
							%s
						</select>
						när
						<select name="%s">%s</select>
						
						<br/>
						
						<button class="button tuja-delete-messagetemplate" type="button">Ta bort</button>
					</div>				
				</div>
			</div>
		',
			$this->list_item_field_name( 'messagetemplate', $message_template->id, 'name' ),
			$message_template->name,
			$this->list_item_field_name( 'messagetemplate', $message_template->id, 'delivery_method' ),
			$delivery_method_options,
			$this->list_item_field_name( 'messagetemplate', $message_template->id, 'subject' ),
			$message_template->subject,
			TemplateEditor::render(
				$this->list_item_field_name( 'messagetemplate', $message_template->id, 'body' ),
				$message_template->body ?: '',
				EventMessageSender::template_parameters( Group::sample(), Person::sample() )
			),
			$this->list_item_field_name( 'messagetemplate', $message_template->id, 'auto_send_recipient' ),
			$auto_send_recipient_options,
			$this->list_item_field_name( 'messagetemplate', $message_template->id, 'auto_send_trigger' ),
			$auto_send_trigger_options );
	}

	public function print_group_category_form( GroupCategory $category ) {
		$id1                   = uniqid();
		$id2                   = uniqid();
		$rule_sets             = self::RULE_SETS;
		$rule_set_options_html = join( '', array_map(
			function ( $key, $value ) use ( $category ) {
				return sprintf( '<option value="%s" %s>%s</option>',
					htmlspecialchars( $key ),
					$category->rule_set_class_name == $key ? 'selected="selected"' : '',
					$value );
			},
			array_keys( $rule_sets ),
			array_values( $rule_sets ) ) );

		$pattern = '
			<div class="tuja-groupcategory-form">
				<input type="text" placeholder="Mallens namn" size="50" name="%s" value="%s">
				<select name="%s">%s</select>
				<button class="button tuja-delete-groupcategory" type="button">
					Ta bort
				</button>
			</div>
		';

		return sprintf( $pattern,
			$this->list_item_field_name( 'groupcategory', $category->id, 'name' ),
			$category->name,
			$this->list_item_field_name( 'groupcategory', $category->id, 'ruleset' ),
			$rule_set_options_html );
	}


	public function competition_settings_save_message_templates( Competition $competition ) {
		$message_template_dao = new MessageTemplateDao();

		$message_templates = $message_template_dao->get_all_in_competition( $competition->id );

		$preexisting_ids = array_map( function ( $template ) {
			return $template->id;
		}, $message_templates );

		$submitted_ids = $this->submitted_list_item_ids( 'messagetemplate' );

		$updated_ids = array_intersect( $preexisting_ids, $submitted_ids );
		$deleted_ids = array_diff( $preexisting_ids, $submitted_ids );
		$created_ids = array_diff( $submitted_ids, $preexisting_ids );

		$message_template_map = array_combine( array_map( function ( $message_template ) {
			return $message_template->id;
		}, $message_templates ), $message_templates );

		foreach ( $created_ids as $id ) {
			try {
				$enable_auto_send = ! empty( $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'auto_send_recipient' ) ] );

				$new_template                      = new MessageTemplate();
				$new_template->competition_id      = $competition->id;
				$new_template->name                = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'name' ) ];
				$new_template->subject             = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'subject' ) ];
				$new_template->body                = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'body' ) ];
				$new_template->delivery_method     = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'delivery_method' ) ];
				$new_template->auto_send_recipient = $enable_auto_send ? $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'auto_send_recipient' ) ] : null;
				$new_template->auto_send_trigger   = $enable_auto_send ? $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'auto_send_trigger' ) ] : null;

				$new_template_id = $message_template_dao->create( $new_template );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}

		foreach ( $updated_ids as $id ) {
			if ( isset( $message_template_map[ $id ] ) ) {
				try {
					$enable_auto_send = ! empty( $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'auto_send_recipient' ) ] );

					$message_template_map[ $id ]->name                = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'name' ) ];
					$message_template_map[ $id ]->subject             = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'subject' ) ];
					$message_template_map[ $id ]->body                = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'body' ) ];
					$message_template_map[ $id ]->delivery_method     = $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'delivery_method' ) ];
					$message_template_map[ $id ]->auto_send_recipient = $enable_auto_send ? $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'auto_send_recipient' ) ] : null;
					$message_template_map[ $id ]->auto_send_trigger   = $enable_auto_send ? $_POST[ $this->list_item_field_name( 'messagetemplate', $id, 'auto_send_trigger' ) ] : null;

					$affected_rows = $message_template_dao->update( $message_template_map[ $id ] );
				} catch ( ValidationException $e ) {
					AdminUtils::printException( $e );
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
			}
		}

		foreach ( $deleted_ids as $id ) {
			if ( isset( $message_template_map[ $id ] ) ) {
				$delete_successful = $message_template_dao->delete( $id );
				if ( ! $delete_successful ) {
					AdminUtils::printError( 'Could not delete message template' );
				}
			}
		}
	}

	public function competition_settings_save_groups( Competition $competition ) {
		$category_dao = new GroupCategoryDao();

		$categories = $category_dao->get_all_in_competition( $competition->id );

		$preexisting_ids = array_map( function ( $category ) {
			return $category->id;
		}, $categories );

		$submitted_ids = $this->submitted_list_item_ids( 'groupcategory' );

		$updated_ids = array_intersect( $preexisting_ids, $submitted_ids );
		$deleted_ids = array_diff( $preexisting_ids, $submitted_ids );
		$created_ids = array_diff( $submitted_ids, $preexisting_ids );

		$category_map = array_combine( array_map( function ( $category ) {
			return $category->id;
		}, $categories ), $categories );

		foreach ( $created_ids as $id ) {
			try {
				$new_template                      = new GroupCategory();
				$new_template->competition_id      = $competition->id;
				$new_template->name                = $_POST[ $this->list_item_field_name( 'groupcategory', $id, 'name' ) ];
				$rule_set_class_name               = stripslashes( $_POST[ $this->list_item_field_name( 'groupcategory', $id, 'ruleset' ) ] );
				$new_template->rule_set_class_name = ! empty( $rule_set_class_name ) && class_exists( $rule_set_class_name ) ? $rule_set_class_name : null;

				$new_template_id = $category_dao->create( $new_template );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}

		foreach ( $updated_ids as $id ) {
			if ( isset( $category_map[ $id ] ) ) {
				try {
					$category_map[ $id ]->name                = $_POST[ $this->list_item_field_name( 'groupcategory', $id, 'name' ) ];
					$rule_set_class_name                      = stripslashes( $_POST[ $this->list_item_field_name( 'groupcategory', $id, 'ruleset' ) ] );
					$category_map[ $id ]->rule_set_class_name = ! empty( $rule_set_class_name ) && class_exists( $rule_set_class_name ) ? $rule_set_class_name : null;

					$affected_rows = $category_dao->update( $category_map[ $id ] );
				} catch ( ValidationException $e ) {
					AdminUtils::printException( $e );
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
			}
		}

		foreach ( $deleted_ids as $id ) {
			if ( isset( $category_map[ $id ] ) ) {
				$delete_successful = $category_dao->delete( $id );
				if ( ! $delete_successful ) {
					AdminUtils::printError( 'Could not delete category' );
				}
			}
		}
	}

	public function competition_settings_save_other( Competition $competition ) {
		try {
			// TODO: Settle on one naming convention for form field names.
			$competition->create_group_start   = DateUtils::from_date_local_value( $_POST['tuja_create_group_start'] );
			$competition->create_group_end     = DateUtils::from_date_local_value( $_POST['tuja_create_group_end'] );
			$competition->edit_group_start     = DateUtils::from_date_local_value( $_POST['tuja_edit_group_start'] );
			$competition->edit_group_end       = DateUtils::from_date_local_value( $_POST['tuja_edit_group_end'] );
			$competition->event_start          = DateUtils::from_date_local_value( $_POST['tuja_event_start'] );
			$competition->event_end            = DateUtils::from_date_local_value( $_POST['tuja_event_end'] );
			$competition->initial_group_status = $_POST['tuja_competition_settings_initial_group_status'] ?: null;

			$dao = new CompetitionDao();
			$dao->update( $competition );
		} catch ( Exception $e ) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException( $e );
		}
	}

	private function get_auto_send_recipient_options( MessageTemplate $message_template ): string {
		$to                          = [
			EventMessageSender::RECIPIENT_ADMIN         => 'Tuko',
			EventMessageSender::RECIPIENT_GROUP_CONTACT => 'Gruppledaren',
			EventMessageSender::RECIPIENT_SELF          => 'Personen det gäller'
		];
		$auto_send_recipient_options = join( '', array_map(
			function ( $key, $value ) use ( $message_template ) {
				return sprintf( '<option value="%s" %s>Skicka till %s</option>',
					htmlspecialchars( $key ),
					$message_template->auto_send_recipient == $key ? 'selected="selected"' : '',
					$value );
			},
			array_keys( $to ),
			array_values( $to ) ) );

		return $auto_send_recipient_options;
	}

	private function get_delivery_method_options( MessageTemplate $message_template ): string {
		$delivery_methods        = [
			MessageTemplate::EMAIL => 'Skicka som e-post',
			MessageTemplate::SMS   => 'Skicka som SMS'
		];
		$delivery_method_options = join( '', array_map(
			function ( $key, $value ) use ( $message_template ) {
				return sprintf( '<option value="%s" %s>%s</option>',
					htmlspecialchars( $key ),
					$message_template->delivery_method == $key ? 'selected="selected"' : '',
					$value );
			},
			array_keys( $delivery_methods ),
			array_values( $delivery_methods ) ) );

		return $delivery_method_options;
	}

	private function get_auto_send_trigger_options( MessageTemplate $message_template ): string {
		$event_options = $this->get_event_options();

		$auto_send_trigger_options = join( '', array_map(
			function ( $key, $value ) use ( $message_template ) {
				return sprintf( '<option value="%s" %s>%s</option>',
					htmlspecialchars( $key ),
					$message_template->auto_send_trigger == $key ? 'selected="selected"' : '',
					$value );
			},
			array_keys( $event_options ),
			array_values( $event_options ) ) );

		return $auto_send_trigger_options;
	}

	private function get_event_options() {
		$event_names  = [];
		$event_labels = [];
		foreach ( \tuja\data\model\Group::STATUS_TRANSITIONS as $current => $allowed_next ) {
			$allowed_next = array_filter( $allowed_next, function ( $next ) {
				return $next !== \tuja\data\model\Group::STATUS_DELETED;
			} );
			$event_names  = array_merge( $event_names, array_map( function ( $next ) use ( $current ) {
				return EventMessageSender::group_status_change_event_name( $current, $next );
			}, $allowed_next ) );
			$event_labels = array_merge( $event_labels, array_map( function ( $next ) use ( $current ) {
				return sprintf( 'grupps status går från %s till %s', strtoupper( $current ), strtoupper( $next ) );
			}, $allowed_next ) );
		}
		$event_options = array_combine( $event_names, $event_labels );

		$event_options[ EventMessageSender::new_group_member_event_name( true ) ]  = 'person anmäler sig till funktionärslag';
		$event_options[ EventMessageSender::new_group_member_event_name( false ) ] = 'person anmäler sig till deltagarlag';

		return $event_options;
	}

	private function competition_settings_save_strings( Competition $competition ) {
		$final_list = Strings::get_list();

		$updated_list = [];
		foreach ( array_keys( $final_list ) as $key ) {
			$submitted_value = str_replace(
				"\r\n",
				"\n",
				@$_POST[ self::string_field_name( $key ) ] ?: '' );
			if ( ! Strings::is_default_value( $key, $submitted_value ) ) {
				$updated_list[ $key ] = $submitted_value;
			}
		}

		( new StringsDao() )->set_all( $competition->id, $updated_list );

		Strings::init( $competition->id, true );
	}

	public static function string_field_name( string $key ) {
		return 'tuja_strings__' . str_replace( '.', '_', $key );
	}
}