<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Map;
use tuja\data\model\Marker;
use tuja\data\model\Station;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\MapDao;
use tuja\data\model\ValidationException;
use tuja\data\store\MarkerDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\StationDao;

class Maps extends Competition {
	const FIELD_VALUE_SEP = ' ';

	protected $map_dao;

	public function __construct() {
		parent::__construct();
		$this->map_dao = new MapDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'map_create' ) {
			$props                 = new Map();
			$props->name           = $_POST['tuja_map_name'];
			$props->competition_id = $this->competition->id;
			try {
				$new_map_id = $this->map_dao->create( $props );
				if ( $new_map_id !== false ) {
					AdminUtils::printSuccess(
						sprintf(
							'<span id="tuja_map_create_map_result" data-map-id="%s">Karta %s har lagts till.</span>',
							$new_map_id,
							$props->name
						)
					);
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$import_url = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'MapsImport',
			)
		);

		$maps = $this->map_dao->get_all_in_competition( $competition->id );

		include 'views/maps.php';
	}
}
