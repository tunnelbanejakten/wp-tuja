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

		$rows = array_map(
			function ( $values ) {
				list ($food, $name, $phone, $email) = $values;
				return array(
					'value' => $food,
					'name'  => $name,
					'phone' => $phone,
					'email' => $email,
				);
			},
			array_filter(
				array_map(
					function ( Person $person ) {
						return array( $person->food, $person->name, $person->phone, $person->email );
					},
					$people
				),
				function ( $values ) {
					return ! empty( $values[0] );
				}
			)
		);

		sort( $rows );

		return $rows;
	}

	private function summary_by_keywords( array $rows ) {
		$summary_config = array(
			'Gluten'         => array( 'gluten' ),
			'Laktos'         => array( 'laktos' ),
			'Inga allergier' => array( 'nepp', 'nej' ),
			'Vego'           => array( 'vego', 'vegetarian' ),
		);
		return array_map(
			function ( $label, $keywords ) use ( $rows ) {
				return array(
					'label' => $label,
					'count' => count(
						array_filter(
							$rows,
							function ( $row ) use ( $keywords ) {
								foreach ( $keywords as $keyword ) {
									if ( strpos( strtolower( $row['value'] ), strtolower( $keyword ) ) !== false ) {
										return true;
									}
								}

								return false;
							}
						)
					),
				);
			},
			array_keys( $summary_config ),
			array_values( $summary_config )
		);
	}

	private function summary_by_comma_separation( array $rows ) {
		$summary = array_reduce(
			$rows,
			function ( $summary, $row ) {
				$raw_value        = $row['value'];
				$values           = array_filter( array_map( 'trim', explode( ',', $raw_value ) ) );
				$countable_values = array_map(
					function ( $str ) {
						return 'allergenen ' . $str;
					},
					$values
				);
				if ( count( $values ) > 1 ) {
					sort( $values );
					$countable_values[] = 'kombinationen ' . join( ', ', $values );
				} else {
					$countable_values[] = 'enbart ' . $values[0];
				}
				foreach ( $countable_values as $value ) {
					if ( ! isset( $summary[ $value ] ) ) {
						$summary[ $value ] = array(
							'label' => $value,
							'count' => 0,
						);
					}
					$summary[ $value ]['count']++;
				}
				return $summary;
			},
			array()
		);
		usort(
			$summary,
			function ( $a, $b ) {
				return $b['count'] - $a['count'];
			}
		);
		return $summary;
	}

	function output_html( array $rows ) {
		$list_all = 'true' === $_GET['tuja_reports_list_all'];

		$comma_separated = 'comma_separated' === $_GET['tuja_reports_grouping'];
		$summary         = $comma_separated
			? $this->summary_by_comma_separation( $rows )
			: $this->summary_by_keywords( $rows );

		include( 'views/report-foodpreferences.php' );
	}
}
