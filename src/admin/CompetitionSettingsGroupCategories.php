<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\util\rules\CrewMembersRuleSet;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\OlderParticipantsRuleSet;
use tuja\util\rules\PassthroughRuleSet;
use tuja\util\rules\YoungParticipantsRuleSet;
use tuja\util\fee\GroupFeeCalculator;

class CompetitionSettingsGroupCategories extends AbstractCompetitionSettings {
	const FIELD_SEPARATOR = '__';

	const RULE_SETS = array(
		PassthroughRuleSet::class       => 'Inga regler',
		YoungParticipantsRuleSet::class => 'Deltagare under 15 år',
		OlderParticipantsRuleSet::class => 'Deltagare över 15 år',
		CrewMembersRuleSet::class       => 'Funktionärer',
	);

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		$competition = $this->competition_dao->get( $_GET['tuja_competition'] );

		if ( ! $competition ) {
			throw new Exception( 'Could not find competition' );
		}

		@list( $action, $parameter ) = explode( '__', $_POST['tuja_action'] );

		if ( $action === 'tuja_groupcategory_save' ) {
			$this->save_changes( $competition );
		} elseif ( $action === 'tuja_groupcategory_create' ) {
			$this->create_category( $competition );
		} elseif ( $action === 'tuja_groupcategory_delete' ) {
			$this->delete_category( intval( $parameter ) );
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'jsoneditor.min.js',
			'admin-competition-settings-group-categories.js',
		);
	}

	public function output() {
		$this->handle_post();

		$competition  = $this->competition_dao->get( $_GET['tuja_competition'] );
		$category_dao = new GroupCategoryDao();

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'CompetitionSettings',
			)
		);

		include 'views/competition-settings-group-categories.php';
	}


	public function list_item_field_name( $list_name, $id, $field ) {
		return join( self::FIELD_SEPARATOR, array( $list_name, $field, $id ) );
	}

	public function print_group_category_form( GroupCategory $category, Competition $competition ) {
		$rules             = $category->get_rules();
		$jsoneditor_config = GroupCategoryRules::get_jsoneditor_config();
		$jsoneditor_values = $rules->get_json_values();

		return sprintf(
			'
		<td>
			<div class="tuja-groupcategory-form tuja-ruleset-column">
				<input type="hidden" name="%s" id="%s" value="%s">
				<div class="tuja-group-category-rules">
					<div class="tuja-admin-formgenerator-form" 
						data-schema="%s" 
						data-values="%s" 
						data-field-id="%s"
						data-root-name="%s"></div>
				</div>
			</div>
		</td>',
			$this->list_item_field_name( 'groupcategory', $category->id, 'rules' ),
			$this->list_item_field_name( 'groupcategory', $category->id, 'rules' ),
			htmlentities( $jsoneditor_values ),
			htmlentities( $jsoneditor_config ),
			htmlentities( $jsoneditor_values ),
			htmlentities( $this->list_item_field_name( 'groupcategory', $category->id, 'rules' ) ),
			'tuja-admin-formgenerator-form-' . $category->id
		);
	}

	public function print_group_fee_configuration_form( GroupCategory $category ) {
		return sprintf(
			'
		<td class="tuja-group-fee-configuration-form">
			%s
		</td>',
			AdminUtils::print_fee_configuration_form(
				$category->fee_calculator,
				$this->list_item_field_name( 'groupcategory', $category->id, 'fee' ),
				true
			)
		);
	}

	public function delete_category( int $id ) {
		$category_dao      = new GroupCategoryDao();
		$delete_successful = $category_dao->delete( $id );
		if ( ! $delete_successful ) {
			global $wpdb;
			AdminUtils::printError( 'Could not delete category' . $wpdb->last_error );
		}
	}

	public function create_category( Competition $competition ) {
		$class_name   = stripslashes( $_POST['tuja_groupcategory_ruleset'] );
		$category_dao = new GroupCategoryDao();
		try {
			$category                 = new GroupCategory();
			$category->competition_id = $competition->id;
			$category->name           = $_POST['tuja_groupcategory_name'];
			$category->set_rules( GroupCategoryRules::from_rule_set( new $class_name(), $competition ) );

			$new_category_id = $category_dao->create( $category );
			AdminUtils::printSuccess( sprintf( 'Created group category %d', $new_category_id ) );
		} catch ( ValidationException $e ) {
			AdminUtils::printException( $e );
		} catch ( Exception $e ) {
			AdminUtils::printException( $e );
		}
	}

	public function save_changes( Competition $competition ) {
		$category_dao = new GroupCategoryDao();

		$categories = $category_dao->get_all_in_competition( $competition->id );
		array_walk(
			$categories,
			function ( GroupCategory $category ) use ( $category_dao ) {
				try {
					$id                       = $category->id;
					$category->name           = $_POST[ $this->list_item_field_name( 'groupcategory', $id, 'name' ) ];
					$category->fee_calculator = AdminUtils::get_fee_configuration_object( $this->list_item_field_name( 'groupcategory', $id, 'fee' ) );
					$category->set_rules( new GroupCategoryRules( json_decode( stripslashes( $_POST[ $this->list_item_field_name( 'groupcategory', $id, 'rules' ) ] ), true ) ) );

					$affected_rows = $category_dao->update( $category );
				} catch ( ValidationException $e ) {
					AdminUtils::printException( $e );
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
			}
		);
	}
}
