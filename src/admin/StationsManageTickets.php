<?php

namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\Group;
use tuja\data\model\Ticket;
use tuja\data\model\TicketDesign;
use tuja\data\store\StationDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\store\TicketDao;

class StationsManageTickets extends Stations {
	private $ticket_dao;

	const ACTION_GRANT  = 'grant_ticket';
	const ACTION_REVOKE = 'revoke_ticket';

	const INPUT_FIELD_PASSWORD = 'tuja_ticket_password';
	const INPUT_FIELD_ACTION   = 'tuja_ticket_action';

	public function __construct() {
		parent::__construct();

		$this->ticket_dao = new TicketDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST[ self::INPUT_FIELD_ACTION ] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST[ self::INPUT_FIELD_ACTION ], 2 );
		if ( self::ACTION_REVOKE === $action ) {
			@list ($station_id, $group_id) = explode( '__', $parameter );

			$success = $this->ticket_dao->revoke_ticket( intval( $group_id ), intval( $station_id ) );

			if ( $success ) {
				AdminUtils::printSuccess( 'Biljett borttagen.' );
			} else {
				AdminUtils::printError( 'Kunde inte ta bort biljetten.' );
			}
		} elseif ( self::ACTION_GRANT === $action ) {
			@list ($station_id, $group_id) = explode( '__', $parameter );

			$password = @$_POST[ self::INPUT_FIELD_PASSWORD ];
			if ( ! empty( $password ) ) {

				$success = $this->ticket_dao->grant_ticket( intval( $group_id ), intval( $station_id ), $password );

				if ( $success ) {
					AdminUtils::printSuccess( 'Biljett utdelad.' );
				} else {
					AdminUtils::printError( 'Kunde inte dela ut biljett.' );
				}
			} else {
				AdminUtils::printError( 'Lösenord måste väljas.' );
			}
		}
	}

	private static function get_field_key( int $station_id, int $group_id ) {
		return join( '__', array( $station_id, $group_id ) );
	}

	public function output() {
		$this->handle_post();

		$station_dao = new StationDao();
		$group_dao   = new GroupDao();

		$competition = $this->competition;

		$stations = $station_dao->get_all_in_competition( $this->competition->id );
		$groups   = $group_dao->get_all_in_competition( $this->competition->id );

		$tickets = $this->ticket_dao->get_competition_tickets( $competition );

		$revoke_actions = array_reduce(
			$tickets,
			function ( array $res, Ticket $ticket ) {
				$field_key         = self::get_field_key( $ticket->station->id, $ticket->group_id );
				$res[ $field_key ] = sprintf(
					'
                    <div class="tuja-ticketmanagement-ticket">
                        <div class="tuja-ticket-password-used">Lösenord som användes: <code>%s</code></div>
                        <div class="tuja-buttons">
                            <button class="button" type="submit" name="tuja_ticket_action" value="%s__%s">%s</button>
                        </div>
                    </div>
                    ',
					$ticket->on_complete_password_used,
					self::ACTION_REVOKE,
					$field_key,
					'Ta bort'
				);
				return $res;
			},
			array()
		);

		$form_actions = array();

		array_walk(
			$groups,
			function ( Group $group ) use ( &$form_actions, &$revoke_actions, $stations ) {
					array_walk(
						$stations,
						function ( Station $station ) use ( &$form_actions, &$revoke_actions, $group ) {
							$field_key = self::get_field_key( $station->id, $group->id );
							if ( isset( $revoke_actions[ $field_key ] ) ) {
								$form_actions[ $field_key ] = $revoke_actions[ $field_key ];
							} else {
								$form_actions[ $field_key ] = sprintf(
									'
                                    <div class="tuja-ticketmanagement-ticket">
                                        <div class="tuja-buttons">
                                            <button class="button" type="submit" name="tuja_ticket_action" value="%s__%s">%s</button>
                                        </div>
                                    </div>
                                    ',
									self::ACTION_GRANT,
									$field_key,
									'Dela ut biljett'
								);
							}
						}
					);
			}
		);

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Stations',
			)
		);

		$station_passwords        = array_map(
			function ( TicketDesign $design ) {
				return $design->on_complete_password;
			},
			$this->ticket_dao->get_ticket_designs( $competition )
		);
		$station_password_options = join(
			'<br>',
			array_map(
				function ( string $password ) {
					$id = uniqid();
					return sprintf(
						'<input type="radio" name="%s" value="%s" id="%s" %s/><label for="%s">%s</label>',
						self::INPUT_FIELD_PASSWORD,
						$password,
						$id,
						@$_POST[ self::INPUT_FIELD_PASSWORD ] === $password ? 'checked="checked"' : '',
						$id,
						$password
					);
				},
				$station_passwords
			)
		);

		include 'views/stations-manage-tickets.php';
	}
}
