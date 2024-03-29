<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Station;
use tuja\data\model\StationWeight;
use tuja\data\model\TicketDesign;
use tuja\data\store\StationDao;
use tuja\data\store\TicketDao;

class StationsTicketing extends Stations {
	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'save' ) {
			try {
				//
				// Collect and validate input
				//

				$station_dao = new StationDao();
				$ticket_dao  = new TicketDao();

				$stations = $station_dao->get_all_in_competition( $this->competition->id );

				$ticket_designs = array();
				$weights        = array();
				array_walk(
					$stations,
					function ( Station $station ) use ( &$ticket_designs, &$weights, $stations ) {

						$ticket_design                       = new TicketDesign();
						$ticket_design->station_id           = $station->id;
						$ticket_design->colour               = $_POST[ 'tuja_ticketdesign__' . $station->id . '__colour' ];
						$ticket_design->word                 = $_POST[ 'tuja_ticketdesign__' . $station->id . '__word' ];
						$ticket_design->symbol               = $_POST[ 'tuja_ticketdesign__' . $station->id . '__symbol' ];
						$ticket_design->on_complete_password = $_POST[ 'tuja_ticketdesign__' . $station->id . '__password' ];
						if ( empty( $ticket_design->on_complete_password ) ) {
							throw new Exception( 'On-complete password must be specified.' );
						}
						$ticket_designs[] = $ticket_design;

						foreach ( $stations as $station_to ) {
							if ( $station->id !== $station_to->id ) {
								$field_name = join(
									'__',
									array(
										'tuja_ticketcouponweight',
										$station->id,
										$station_to->id,
									)
								);
								$weight     = trim( $_POST[ $field_name ] );
								if ( empty( $weight ) ) {
									throw new Exception( $field_name . ' must be specified.' );
								}
								$weights[] = new StationWeight( $station->id, $station_to->id, floatval( $weight ) );
							}
						}

					}
				);

				//
				// Save data
				//

				$ticket_dao->set_station_weights( $this->competition, $weights );
				$ticket_dao->set_ticket_designs( $ticket_designs );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-stations-ticketing.js',
		);
	}

	public function output() {
		$this->handle_post();

		$ticket_dao  = new TicketDao();
		$station_dao = new StationDao();

		$competition = $this->competition;

		$stations = $station_dao->get_all_in_competition( $competition->id );

		$ticket_designs = $ticket_dao->get_ticket_designs( $competition );

		$station_weights = $ticket_dao->get_station_weights( $competition->id );

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Stations',
			)
		);

		$save_button = sprintf(
			'
			<div class="tuja-buttons">
        		<button type="submit" class="button" name="tuja_action" value="%s">Spara</button>
    		</div>',
			'save'
		);

		include( 'views/stations-ticketing.php' );
	}
}
