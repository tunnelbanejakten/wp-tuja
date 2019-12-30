<?php

namespace tuja\view;

use tuja\data\model\Group;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\QuestionGroupDao;
use DateTime;
use Exception;
use tuja\data\model\Response;

class FormShortcode extends AbstractShortcode
{
	public function __construct( $wpdb, $form_id, $group_key, $is_crew_override ) {
		parent::__construct();
		$this->form_id            = $form_id;
		$this->group_key          = $group_key;
		$this->is_crew_override   = $is_crew_override;
	}


}