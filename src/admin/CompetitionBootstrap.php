<?php

namespace tuja\admin;

use Exception;
use tuja\controller\BootstrapCompetitionController;
use tuja\controller\BootstrapCompetitionParams;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;

class CompetitionBootstrap {

	private $competition;

	public function __construct() {
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'competition_bootstrap' ) {
			$controller = new BootstrapCompetitionController();
			try {
				$props                                  = new BootstrapCompetitionParams();
				$props->name                            = @$_POST['competition_name'];
				$props->create_default_group_categories = @$_POST['tuja_create_default_group_categories'] === 'true';
				$props->create_default_crew_groups      = @$_POST['tuja_create_default_crew_groups'] === 'true';
				$props->create_common_group_state_transition_sendout_templates = @$_POST['tuja_create_common_group_state_transition_sendout_templates'] === 'true';
				$props->create_sample_form                                     = @$_POST['tuja_create_sample_form'] === 'true';
				$props->create_sample_maps                                     = @$_POST['tuja_create_sample_maps'] === 'true';
				$props->create_sample_stations                                 = @$_POST['tuja_create_sample_stations'] === 'true';
				$controller->bootstrap_competition( $props );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	public function get_scripts(): array {
		return array();
	}

	public function output() {
		$this->handle_post();

		$back_link = add_query_arg(
			array(
				'tuja_view' => 'Competitions',
			)
		);

		$checkbox_definitions = array(
			'tuja_create_default_group_categories' => 'Skapa grunduppsättning gruppkategorier',
			'tuja_create_default_crew_groups'      => 'Skapa funktionärsgrupp',
			'tuja_create_common_group_state_transition_sendout_templates' => 'Definiera e-postutskick för vanliga händelser',
			'tuja_create_sample_form'              => 'Skapa ett formulär med exempelfrågor',
			'tuja_create_sample_maps'              => 'Skapa kartor för ett par svenska städer',
			'tuja_create_sample_stations'          => 'Skapa ett par stationer',
		);

		$checkboxes = array_map(
			function( string $key, string $label ) {
				$is_form_submitted = isset( $_POST['tuja_action'] );
				return sprintf(
					'
				<div>
					<input type="checkbox" name="%s" id="%s" value="true" %s/>
					<label for="%s">%s</label>
				</div>
			',
					$key,
					$key,
					! $is_form_submitted || @$_POST[ $key ] === 'true' ? 'checked="checked"' : '',
					$key,
					$label
				);
			},
			array_keys( $checkbox_definitions ),
			array_values( $checkbox_definitions )
		);
		include 'views/competition-bootstrap.php';
	}
}
