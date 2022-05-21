<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\util\DateUtils;
use tuja\util\Strings;

class CompetitionSettings extends AbstractCompetitionSettings {
	public function output() {
		$competition     = $this->competition;

		include( 'views/competition-settings.php' );
	}
}
