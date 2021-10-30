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
				$props->name                            = @$_POST['tuja_competition_name'];
				$props->initial_group_status            = @$_POST['tuja_competition_initial_group_status'];
				$props->create_default_group_categories = @$_POST['tuja_create_default_group_categories'] === 'true';
				$props->create_default_crew_groups      = @$_POST['tuja_create_default_crew_groups'] === 'true';
				$props->create_common_group_state_transition_sendout_templates = @$_POST['tuja_create_common_group_state_transition_sendout_templates'] === 'true';
				$props->create_sample_form                                     = @$_POST['tuja_create_sample_form'] === 'true';
				$props->create_sample_maps                                     = @$_POST['tuja_create_sample_maps'] === 'true';
				$props->create_sample_stations                                 = @$_POST['tuja_create_sample_stations'] === 'true';

				$bootstrap_result = $controller->bootstrap_competition( $props );
				$competition      = $bootstrap_result['competition'];

				$url = add_query_arg(
					array(
						'tuja_view'        => 'Competition',
						'tuja_competition' => $competition->id,
					)
				);

				AdminUtils::printSuccess(
					sprintf(
						'<a href="%s"
							id="tuja_bootstrapped_competition_link"
							data-crew-group-key="%s"
							data-form-key="%s"
							data-form-id="%s"
							data-map-id="%s"
							data-competition-id="%s"
							data-competition-key="%s">Tävling %s</a> har skapats.',
						$url,
						$bootstrap_result['crew_group_key'],
						$bootstrap_result['sample_form_key'],
						$bootstrap_result['sample_form_id'],
						$bootstrap_result['sample_map_id'],
						$competition->id,
						$competition->random_id,
						$props->name
					)
				);

				return true;
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
		return false;
	}

	public function get_scripts(): array {
		return array();
	}

	public function output() {
		$is_competition_created = $this->handle_post();

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

		if ( ! $is_competition_created ) {
			include 'views/competition-bootstrap.php';
		}
	}
}
