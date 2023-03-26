<?php

namespace tuja\frontend;


use Exception;
use tuja\controller\ReportPointsController;
use tuja\data\model\Group;
use tuja\data\model\Station;
use tuja\data\store\StationDao;
use tuja\frontend\AbstractCrewMemberView;
use tuja\frontend\router\ReportPointsInitiator;
use tuja\util\concurrency\LockValuesList;
use tuja\view\FieldNumber;
use tuja\Frontend;

class ReportPoints extends AbstractCrewMemberView {
	private $station_dao;

	public function __construct( string $url, string $person_key, string $station_key ) {
		parent::__construct( $url, $person_key, 'Rapportera poäng' );
		$this->station_key              = $station_key;
		$this->station_dao              = new StationDao();
		$this->render_points_controller = new ReportPointsController();
	}

	function output() {
		$station = $this->get_station();
		if ( false !== $station ) {
			Frontend::use_script( 'jquery' );
			Frontend::use_script( 'tuja-report-points.js' );
		}
		Frontend::use_stylesheet( 'tuja-wp-report-points.css' );

		$form = $this->get_form_html();
		include( 'views/report-points.php' );
	}

	private function get_station() {
		if ( isset( $this->station_key ) ) {
			return $this->station_dao->get_by_key( $this->station_key );
		}
		return false;
	}

	public function get_form_html(): string {
		$html_sections = array();

		$html_sections[] = $this->handle_post();

		$station = $this->get_station();

		// If a station and question station has been selected, display the questions with current points and a save button
		if ( $station ) {
			$all_data = $this->render_points_controller->get_all_points( $station );

			array_walk(
				$all_data,
				function ( $data ) use ( &$html_sections, $station ) {
					$group_id   = $data['group_id'];
					$group_name = $data['group_name'];
					$points     = $data['points'];
					$lock       = $data['lock'];

					$html_sections[] = sprintf(
						'
						<section
							class="%s"
							data-group-id="%s"
							data-lock-value="%s"
						>
							<div class="%s">
								<div>Uppdaterar...</div>
							</div>
							<div class="%s">
								%s
							</div>
						</section>
						',
						'tuja-team-score-container',
						$group_id,
						htmlspecialchars( $lock ),
						'tuja-team-score-loading-container',
						'tuja-team-score-field-container',
						$this->render_points_field( $group_name, $station->id, $group_id, $points ),
					);
				}
			);

			$html_sections[] = sprintf(
				'
				<div 
					id="tuja-report-points-data" 
					data-api-url="%s"
					data-user-key="%s"
					data-station-id="%s" 
				></div>',
				admin_url( 'admin-ajax.php' ),
				$this->get_person()->random_id,
				$station->id
			);

			$html_sections[] = sprintf( '<a href="%s">Tillbaka</a>', ReportPointsInitiator::link_all( $this->person ) );
		} else {
			$stations = $this->station_dao->get_all_in_competition( $this->competition_id );
			array_walk(
				$stations,
				function ( Station $station ) use ( &$html_sections ) {
					$html_sections[] = sprintf(
						'<p><a href="%s">%s</a></p>',
						ReportPointsInitiator::link_one( $this->person, $station ),
						$station->name
					);
				}
			);
		}

		return join( $html_sections );
	}

	private function render_points_field( $text, $station_id, $group_id, $points ): string {
		$field      = new FieldNumber( $text );
		$field_name = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'group_points' . self::FIELD_NAME_PART_SEP . $group_id;

		return $field->render( $field_name, $points, new Group() );
	}

	public function get_optimistic_lock(): LockValuesList {
		die( 'Not implemented' );
	}

	public function update_points(): array {
		die( 'Not implemented' );
	}

}
