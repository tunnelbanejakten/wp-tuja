<?php

namespace tuja\admin;


use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\model\Group;

class FieldGroupSelector {

	private $selectors = [];
	private $groups;

	public function __construct( Competition $competition ) {
		$group_category_dao = new GroupCategoryDao();
		$group_categories   = $group_category_dao->get_all_in_competition( $competition->id );
		$crew_category_ids  = array_map( function ( $category ) {
			return $category->id;
		}, array_filter( $group_categories, function ( $category ) {
			return $category->get_rule_set()->is_crew();
		} ) );

		$group_dao       = new GroupDao();
		$this->groups    = $group_dao->get_all_in_competition( $competition->id );
		$this->selectors = array_merge(
			array(
				array(
					'key'      => 'all',
					'label'    => 'alla grupper, inkl. funk',
					'selector' => function ( Group $group ) {
						return true;
					}
				),
				array(
					'key'      => 'competinggroups',
					'label'    => 'alla tävlande grupper',
					'selector' => function ( Group $group ) use ( $crew_category_ids ) {
						$category = $group->get_category();

						return isset( $category ) && ! in_array( $category->id, $crew_category_ids );
					}
				),
				array(
					'key'      => 'crewgroups',
					'label'    => 'alla funktionärsgrupper',
					'selector' => function ( Group $group ) use ( $crew_category_ids ) {
						$category = $group->get_category();

						return isset( $category ) && in_array( $category->id, $crew_category_ids );
					}
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
						}
					);
				},
				$group_categories ),
			array_map(
				function ( Group $selected_group ) {
					return array(
						'key'      => self::to_key($selected_group),
						'label'    => 'grupp ' . $selected_group->name,
						'selector' => function ( Group $group ) use ( $selected_group ) {
							return $group->id === $selected_group->id;
						}
					);
				},
				$this->groups ) );
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
		$selector = reset( array_map(
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