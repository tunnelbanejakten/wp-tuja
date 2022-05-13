<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;

class AbstractGroup {
	protected $group;
	protected $competition;
	protected $group_dao;

	public function __construct() {
		$this->group_dao = new GroupDao();
		$this->group     = $this->group_dao->get( $_GET['tuja_group'] );
		if ( ! $this->group ) {
			print 'Could not find group';

			return;
		}

		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $this->group->competition_id );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}

	public function print_menu() {
		$items   = array(
			Group::class        => 'Allmänt',
			GroupLinks::class   => 'Länkar',
			GroupEvents::class  => 'Tidsbegränsade frågor som visats',
			GroupScore::class   => 'Svar och poäng',
			GroupMembers::class => 'Deltagare',
		);
		$current = $_GET['tuja_view'];
		$links   = array_map(
			function ( $full_view_name, $title ) use ( $current ) {
				$short_view_name = substr( $full_view_name, strrpos( $full_view_name, '\\' ) + 1 );
				if ( $short_view_name === $current ) {
					return sprintf( '<strong>%s</strong>', $title );
				} else {
					$link = add_query_arg(
						array(
							'tuja_competition' => $this->competition->id,
							'tuja_view'        => $short_view_name,
						)
					);
					return sprintf( '<a href="%s">%s</a>', $link, $title );
				}
			},
			array_keys( $items ),
			array_values( $items )
		);
		printf( '<div>%s</div>', join( ' | ', $links ) );
	}
}
