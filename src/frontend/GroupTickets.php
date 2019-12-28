<?php

namespace tuja\frontend;


use Exception;
use tuja\data\store\TicketDao;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\ticket\CouponToTicketTsl2020;
use tuja\view\FieldText;

class GroupTickets extends AbstractGroupView {
	private $group_key;

	const ACTION_NAME_VALIDATE_TICKET = 'validate';
	const FIELD_PASSWORD = 'tuja_ticket_password';

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Biljetter för %s' );
		$this->group_key = $group_key;
	}

	function output() {
		try {
			$ticket_dao      = new TicketDao();
			$group           = $this->get_group();

			$this->check_group_status( $group );

			$error_message   = '';
			$success_message = '';

			$password = trim( @$_POST[ self::FIELD_PASSWORD ] );
			if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_VALIDATE_TICKET && ! empty( $password ) ) {
				try {
					$ticket_validator = new CouponToTicketTsl2020();
					$new_stations     = $ticket_validator->get_tickets_from_coupon_code( $group, $password );

					$success_message = sprintf( '<p class="tuja-message tuja-message-success" data-granted-tickets-count="%d">Ni har fått %s.</p>', count( $new_stations ), count( $new_stations ) == 1 ? 'en ny biljett' : sprintf( '%d nya biljetter', count( $new_stations ) ) );
					unset( $_POST[ self::FIELD_PASSWORD ] );
				} catch ( Exception $e ) {
					$error_message = sprintf( '<p class="tuja-message tuja-message-error">Tyvärr gick något snett. Försök en gång till och om det fortfarande inte fungerar så bör ni kontakta kundtjänst. Felmeddelande: %s.</p>', $e->getMessage() );
				}
			}

			$tickets = $ticket_dao->get_group_tickets( $group );

			$form = $this->get_password_form_html( ! empty( $tickets ) );

			$button = $this->get_validate_password_button_html();

			$home_link = GroupHomeInitiator::link( $group );

			include( 'views/group-tickets.php' );
		} catch ( Exception $e ) {
			print $this->get_exception_message_html( $e );
		}
	}

	private function get_password_form_html( $has_tickets_already ) {
		$html_sections = [];

		$field           = new FieldText(
			$has_tickets_already ?
				'Skriv lösenord för att få fler biljetter' :
				'Skriv lösenord för att få biljetter',
			'Ni får lösenord från funktionär när ni slutfört en kontroll.' );
		$html_sections[] = $this->render_field( $field, self::FIELD_PASSWORD, null, @$_POST[ self::FIELD_PASSWORD ] );

		return join( $html_sections );
	}

	private function get_validate_password_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s" id="tuja_validate_ticket_button">%s</button></div>',
			self::ACTION_BUTTON_NAME,
			self::ACTION_NAME_VALIDATE_TICKET,
			'Byt lösenordet mot biljetter' );
	}
}