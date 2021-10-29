<?php

namespace tuja\controller;

class BootstrapCompetitionParams {
	public string $name;
	public string $initial_group_status;
	public bool $create_default_group_categories;
	public bool $create_default_crew_groups;
	public bool $create_common_group_state_transition_sendout_templates;
	public bool $create_sample_form;
	public bool $create_sample_maps;
	public bool $create_sample_stations;
}
