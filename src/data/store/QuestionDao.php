<?php

namespace data\store;

class QuestionDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function get_all_in_form($form_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_form_question',
            'SELECT * FROM form_question WHERE form_id = %d',
            $form_id);
    }

}