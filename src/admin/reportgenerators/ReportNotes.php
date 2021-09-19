<?php

namespace tuja\admin\reportgenerators;


use tuja\data\model\Person;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;

class ReportNotes extends AbstractReport {
	private $person_dao;
	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->person_dao = new PersonDao();
		$this->group_dao  = new GroupDao();
	}

	function get_rows(): array {
		$rows = [];
		foreach ( $this->group_dao->get_all_in_competition( $this->competition->id ) as $group ) {
			if ( ! empty( $group->note ) ) {
				$rows[] = [
					'group'  => $group->name,
					'person' => '',
					'note'   => $group->note,
				];
			}
			foreach ( $this->person_dao->get_all_in_group( $group->id ) as $person ) {
				if ( ! empty( $person->note ) ) {
					$rows[] = [
						'group'  => $group->name,
						'person' => $person->name,
						'note'   => $person->note,
					];
				}
			}
		}

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
							$value = @$row['value'] ?? '';
							if ( strpos( strtolower( $value ), strtolower( $keyword ) ) !== false ) {
								return true;
							}
						}

						return false;
					} ) )
				];
			},
			array_keys( $summary_config ),
			array_values( $summary_config ) );
		include( 'views/report-notes.php' );
	}
}