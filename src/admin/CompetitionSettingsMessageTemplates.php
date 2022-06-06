<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\MessageTemplateDao;
use tuja\util\DateUtils;
use tuja\util\messaging\EventMessageSender;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

class CompetitionSettingsMessageTemplates extends CompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		$competition = $this->competition_dao->get( $_GET['tuja_competition'] );

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save_message_templates( $competition );
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-competition-message-templates.js',
			'admin-templateeditor.js',
		);
	}

	public function output() {
		$this->handle_post();

		$competition_dao      = new CompetitionDao();
		$competition          = $competition_dao->get( $_GET['tuja_competition'] );
		$message_template_dao = new MessageTemplateDao();

		$message_templates = $message_template_dao->get_all_in_competition( $competition->id );

		$event_options = $this->get_event_options();

		$template_configs = MessageTemplate::default_templates();

		$default_message_templates = join(
			'<br>',
			array_map(
				function ( $key, MessageTemplate $mt ) {
					$config = array(
						'name'                => $mt->name,
						'auto_send_trigger'   => $mt->auto_send_trigger,
						'auto_send_recipient' => $mt->auto_send_recipient,
						'delivery_method'     => $mt->delivery_method,
						'subject'             => $mt->subject,
						'body'                => $mt->body,
					);

					return sprintf(
						'<button class="button tuja-add-messagetemplate" type="button" %s>Ny mall %s</button>',
						join(
							' ',
							array_map(
								function ( $key, $value ) {
									return 'data-' . $key . '="' . htmlentities( $value ) . '"';
								},
								array_keys( $config ),
								array_values( $config )
							)
						),
						$config['name'] ?: $key
					);
				},
				array_keys( $template_configs ),
				array_values( $template_configs )
			)
		);

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'CompetitionSettings',
			)
		);

		include( 'views/competition-settings-messagetemplates.php' );
	}


	public function list_item_field_name( $list_name, $id, $field ) {
		return join( self::FIELD_SEPARATOR, array( $list_name, $field, $id ) );
	}


	public function submitted_list_item_ids( $list_name ): array {
		$prefix = $list_name . self::FIELD_SEPARATOR;
		// $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
		$person_prop_field_names = array_filter(
			array_keys( $_POST ),
			function ( $key ) use ( $prefix ) {
				return substr( $key, 0, strlen( $prefix ) ) === $prefix;
			}
		);

		// $all_ids will include duplicates (one for each of the name, email and phone fields).
		// $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
		$all_ids = array_map(
			function ( $key ) {
				list( , , $id ) = explode( self::FIELD_SEPARATOR, $key );

				return $id;
			},
			$person_prop_field_names
		);

		return array_filter( array_unique( $all_ids ) /* No callback to outer array_filter means that empty strings will be skipped.*/ );
	}

	public function print_message_template_form( MessageTemplate $message_template ) {
		$auto_send_trigger_options = $this->get_auto_send_trigger_options( $message_template );

		$auto_send_recipient_options = $this->get_auto_send_recipient_options( $message_template );

		$delivery_method_options = $this->get_delivery_method_options( $message_template );

		return sprintf(
			'
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
			$auto_send_trigger_options
		);
	}


	public function competition_settings_save_message_templates( Competition $competition ) {
		$message_template_dao = new MessageTemplateDao();

		$message_templates = $message_template_dao->get_all_in_competition( $competition->id );

		$preexisting_ids = array_map(
			function ( $template ) {
				return $template->id;
			},
			$message_templates
		);

		$submitted_ids = $this->submitted_list_item_ids( 'messagetemplate' );

		$updated_ids = array_intersect( $preexisting_ids, $submitted_ids );
		$deleted_ids = array_diff( $preexisting_ids, $submitted_ids );
		$created_ids = array_diff( $submitted_ids, $preexisting_ids );

		$message_template_map = array_combine(
			array_map(
				function ( $message_template ) {
					return $message_template->id;
				},
				$message_templates
			),
			$message_templates
		);

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

	private function get_auto_send_recipient_options( MessageTemplate $message_template ): string {
		$to                          = array(
			EventMessageSender::RECIPIENT_ADMIN         => 'Tuko',
			EventMessageSender::RECIPIENT_GROUP_CONTACT => 'Gruppledaren',
			EventMessageSender::RECIPIENT_SELF          => 'Personen det gäller',
		);
		$auto_send_recipient_options = join(
			'',
			array_map(
				function ( $key, $value ) use ( $message_template ) {
					return sprintf(
						'<option value="%s" %s>Skicka till %s</option>',
						htmlspecialchars( $key ),
						$message_template->auto_send_recipient == $key ? 'selected="selected"' : '',
						$value
					);
				},
				array_keys( $to ),
				array_values( $to )
			)
		);

		return $auto_send_recipient_options;
	}

	private function get_delivery_method_options( MessageTemplate $message_template ): string {
		$delivery_methods        = array(
			MessageTemplate::EMAIL => 'Skicka som e-post',
			MessageTemplate::SMS   => 'Skicka som SMS',
		);
		$delivery_method_options = join(
			'',
			array_map(
				function ( $key, $value ) use ( $message_template ) {
					return sprintf(
						'<option value="%s" %s>%s</option>',
						htmlspecialchars( $key ),
						$message_template->delivery_method == $key ? 'selected="selected"' : '',
						$value
					);
				},
				array_keys( $delivery_methods ),
				array_values( $delivery_methods )
			)
		);

		return $delivery_method_options;
	}

	private function get_auto_send_trigger_options( MessageTemplate $message_template ): string {
		$event_options = $this->get_event_options();

		$auto_send_trigger_options = join(
			'',
			array_map(
				function ( $key, $value ) use ( $message_template ) {
					return sprintf(
						'<option value="%s" %s>%s</option>',
						htmlspecialchars( $key ),
						$message_template->auto_send_trigger == $key ? 'selected="selected"' : '',
						$value
					);
				},
				array_keys( $event_options ),
				array_values( $event_options )
			)
		);

		return $auto_send_trigger_options;
	}

	private function get_event_options() {
		return EventMessageSender::event_names();
	}
}
