<?php

namespace tuja\admin;

use DateTime;
use tuja\controller\PaymentsController;
use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\data\store\PaymentDao;

class FieldGroupSelector {

	const GROUP_KEY_ALL = 'all';
	private $selectors = [];
	private $groups;
	protected $payment_dao;

	public function __construct( Competition $competition ) {
		$group_category_dao = new GroupCategoryDao();
		$group_categories   = $group_category_dao->get_all_in_competition( $competition->id );

		$group_dao         = new GroupDao();
		$this->payment_dao = new PaymentDao();
		$this->groups      = $group_dao->get_all_in_competition( $competition->id );
		$this->selectors   = array_merge(
			array(
				array(
					'key'      => self::GROUP_KEY_ALL,
					'label'    => 'alla grupper, inkl. funk',
					'selector' => function ( Group $group ) {
						return true;
					},
				),
				array(
					'key'      => 'competinggroups',
					'label'    => 'alla tävlande grupper',
					'selector' => function ( Group $group ) {
						return ! $group->is_crew;
					},
				),
				array(
					'key'      => 'crewgroups',
					'label'    => 'alla funktionärsgrupper',
					'selector' => function ( Group $group ) {
						return $group->is_crew;
					},
				),
				array(
					'key'      => 'feeunpaid',
					'label'    => 'grupper som inte betalat hela avgiften',
					'selector' => function ( Group $group ) use ( $competition ) {
						static $group_payments = null;
						static $payments_controller = null;
						if ( null === $group_payments ) {
							$group_payments = $this->payment_dao->get_group_payments( $competition->id );
							$payments_controller = new PaymentsController( $competition->id );
						}
						list ($fee, $fee_paid, $status_message) = $payments_controller->group_fee_status(
							$group,
							$group_payments[ $group->id ] ?? array(),
							new DateTime()
						);

						$fee_diff         = $fee - $fee_paid;
						return $fee_diff > 0;
					},
				),
			),
			array_map(
				function ( GroupCategory $category ) {
					return array(
						'key'      => 'category' . $category->id,
						'label'    => 'alla grupper i kategorin ' . $category->name,
						'selector' => function ( Group $group ) use ( $category ) {
							$group_category = $group->get_category();

							return isset( $group_category ) && $group_category->id === $category->id;
						},
					);
				},
				$group_categories
			),
			array_map(
				function ( string $status ) {
					return array(
						'key'      => 'status' . $status,
						'label'    => 'alla grupper med status ' . strtoupper( $status ),
						'selector' => function ( Group $group ) use ( $status ) {
							$group_status = $group->get_status();

							return isset( $group_status ) && $group_status === $status;
						},
					);
				},
				array_keys( Group::STATUS_TRANSITIONS )
			),
			array_map(
				function ( Group $selected_group ) {
					return array(
						'key'      => self::to_key( $selected_group ),
						'label'    => 'grupp ' . $selected_group->name,
						'selector' => function ( Group $group ) use ( $selected_group ) {
							return $group->id === $selected_group->id;
						},
					);
				},
				$this->groups
			)
		);
	}

	public function render( $field_name, $field_value ) {
		printf( '<select name="%s">%s</select>',
			$field_name,
			join(
				array_map(
					function ( $group_selector ) use ( $field_value ) {
						return sprintf( '<option value="%s" %s>%s</option>',
							$group_selector['key'],
							$field_value == $group_selector['key'] ? ' selected="selected"' : '',
							$group_selector['label'] );
					},
					$this->selectors ) ) );
	}

	public function get_selected_groups( $field_value ) {
		$selector = current( array_map(
			function ( $selector ) {
				return $selector['selector'];
			},
			array_filter(
				$this->selectors,
				function ( $selector ) use ( $field_value ) {
					return $selector['key'] == $field_value;
				} ) ) );

		return $selector ? array_filter( $this->groups, $selector ) : [];
	}

	public static function to_key( Group $group ) {
		return 'group' . $group->id;
	}
}