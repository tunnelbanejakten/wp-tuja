<?php

namespace tuja\data\store;

use ReflectionClass;
use tuja\data\model\GroupCategory;
use tuja\data\model\ValidationException;
use tuja\util\Database;
use tuja\util\rules\CrewMembersRuleSet;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\PassthroughRuleSet;
use tuja\util\rules\RuleSet;

class GroupCategoryDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'team_category' );
	}

	function create( GroupCategory $category ) {
		$category->validate();

		if ( $this->exists( $category ) ) {
			throw new ValidationException( 'name', 'Det finns redan en kategori med detta namn.' );
		}

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'competition_id'      => $category->competition_id,
				'name'                => $category->name,
				'rules_configuration' => json_encode( $category->get_rules()->get_values() )
			),
			array(
				'%d',
				'%s',
				'%s'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( GroupCategory $category ) {
		$category->validate();

		if ( $this->exists( $category ) ) {
			throw new ValidationException( 'name', 'Det finns redan en kategori med detta namn.' );
		}

		return $this->wpdb->update( $this->table,
			array(
				'name'                => $category->name,
				'rules_configuration' => json_encode( $category->get_rules()->get_values() )
			),
			array(
				'id' => $category->id
			) );
	}

	function exists( GroupCategory $category ) {
		$db_results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT id FROM ' . $this->table . ' WHERE name = %s AND id != %d AND competition_id = %d',
				$category->name,
				$category->id,
				$category->competition_id ),
			OBJECT );

		return $db_results !== false && count( $db_results ) > 0;
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_group_category( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE id = %d',
			$id );
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_group_category( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d ORDER BY name',
			$competition_id );
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	private static function get_rule_set( $rule_set_class_name ): RuleSet {
		try {
			if ( isset( $rule_set_class_name ) && class_exists( $rule_set_class_name ) ) {
				return ( new ReflectionClass( $rule_set_class_name ) )->newInstance();
			}
		} catch ( ReflectionException $e ) {
		}

		return new PassthroughRuleSet();
	}


	private static function to_group_category( $result ): GroupCategory {
		$gc                 = new GroupCategory();
		$gc->id             = $result->id;
		$gc->competition_id = $result->competition_id;
		$gc->name           = $result->name;

		if ( isset( $result->rules_configuration ) ) {
			$gc->set_rules( new GroupCategoryRules( json_decode( $result->rules_configuration, true ) ) );
		} else {
			$competition_dao = new CompetitionDao(); // A bit of a hack but not super-ugly (DAOs should really be created in entity classes)
			$competition     = $competition_dao->get( $gc->competition_id );

			// Set rules based on (legacy) reference to RuleSet class in rule_set column.
			// TODO: Remove rule_set from database.
			$gc->set_rules( GroupCategoryRules::from_rule_set( self::get_rule_set( isset( $result->rule_set )
				? $result->rule_set
				: ( $result->is_crew != 0
					? CrewMembersRuleSet::class
					: PassthroughRuleSet::class ) ), $competition ) );
		}

		return $gc;
	}
}