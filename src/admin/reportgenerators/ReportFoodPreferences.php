<?php

namespace tuja\admin\reportgenerators;


use tuja\data\model\Person;
use tuja\data\store\PersonDao;

class ReportFoodPreferences extends AbstractReport {
	private $person_dao;

	public function __construct() {
		parent::__construct();
		$this->person_dao = new PersonDao();
	}

	function get_rows(): array {
		$people = $this->person_dao->get_all_in_competition( $this->competition->id );

		$rows = array_map( function ( $value ) {
			return [ 'value' => $value ];
		}, array_unique( array_filter( array_map( function ( Person $person ) {
			return $person->food;
		}, $people ) ) ) );

		sort( $rows );

		return $rows;
	}

	function output_html( array $rows ) {
		$summary_config = [
			'Gluten'         => [ 'gluten' ],
			'Laktos'         => [ 'laktos' ],
			'Inga allergier' => [ 'nepp', 'nej' ],
			'Vego'           => [ 'vego', 'vegetarian' ]
		];
		$summary        = array_map(
			function ( $label, $keywords ) use ( $rows ) {
				return [
					'label' => $label,
					'count' => count( array_filter( $rows, function ( $row ) use ( $keywords ) {
						foreach ( $keywords as $keyword ) {
							if ( strpos( strtolower( $row['value'] ), strtolower( $keyword ) ) !== false ) {
								return true;
							}
						}

						return false;
					} ) )
				];
			},
			array_keys( $summary_config ),
			array_values( $summary_config ) );
		include( 'views/report-foodpreferences.php' );
	}
}