<?php

namespace view;

use data\store\FormDao;
use data\store\GroupDao;
use data\store\QuestionDao;
use data\store\ResponseDao;

class FormReadonlyShortcode
{
    private $question_dao;
    private $group_dao;
    private $response_dao;

    public function __construct($wpdb, $form_id, $group_key)
    {
        $this->form_id = $form_id;
        $this->group_key = $group_key;
        $this->question_dao = new QuestionDao($wpdb);
        $this->group_dao = new GroupDao($wpdb);
        $this->response_dao = new ResponseDao($wpdb);
    }

    public function render(): String
    {
        $group_key = $this->group_key;
        $group = $this->group_dao->get_by_key($group_key);
        if ($group === false) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Oj då, vi vet inte vilket lag du tillhör.');
        }

        $responses = $this->response_dao->get_by_group($group->id);
        $questions = $this->question_dao->get_all_in_form($this->form_id);

        return join(array_map(function ($question) use ($responses) {
            $questions_responses = array_filter($responses, function ($response) use ($question) {
                return $response->form_question_id == $question->id;
            });
            if (count($questions_responses) > 0) {
                usort($questions_responses, function ($a, $b) {
                    return $a->id < $b->id ? 1 : -1;
                });
                $question->latest_response = $questions_responses[0];
            }
            return sprintf('<div class="tuja-question"><p><strong>%s</strong><br>%s</p></div>',
                $question->text,
                join('<br>', $question->latest_response->answers));
        }, $questions));
    }

}