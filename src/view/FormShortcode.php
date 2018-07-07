<?php

namespace view;

use data\store\GroupDao;
use data\store\QuestionDao;
use data\store\ResponseDao;
use tuja\data\model\Response;
use tuja\view\Field;

class FormShortcode
{
    private $question_dao;
    private $group_dao;
    private $response_dao;

    public function __construct($wpdb, $form_id, $team_id)
    {
        $this->form_id = $form_id;
        $this->team_id = $team_id;
        $this->question_dao = new QuestionDao($wpdb);
        $this->group_dao = new GroupDao($wpdb);
        $this->response_dao = new ResponseDao($wpdb);
    }

    public function update_answers(): String
    {
        $overall_success = true;
        $questions = $this->question_dao->get_all_in_form($this->form_id);
        foreach ($questions as $question) {
            if (isset($_POST['tuja_formshortcode_response_' . $question->id])) {
                $new_response = new Response();
                $new_response->team_id = $this->team_id;
                $new_response->form_question_id = $question->id;
                $new_response->answer = $_POST['tuja_formshortcode_response_' . $question->id];
                $new_response->points = null;

                $affected_rows = $this->response_dao->create($new_response);

                $this_success = $affected_rows !== false && $affected_rows === 1;
                $overall_success = ($overall_success and $this_success);
            }
        }
        return $overall_success;

    }

    public function render(): String
    {
        $group = $this->group_dao->get_by_key($this->team_id);
        if ($group === false) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
        }

        $message_success = null;
        $message_error = null;
        switch ($_POST['tuja_formshortcode_action']) {
            case 'update':
                $updated = $this->update_answers();
                if ($updated) {
                    $message_success = 'Era svar har sparats.';
                } else {
                    $message_error = 'Oj, det gick inte att spara era svar.';
                }
                break;
        }

        $responses = $this->response_dao->get_by_team($this->team_id);
        $questions = $this->question_dao->get_all_in_form($this->form_id);

        $html_form = join(array_map(function ($question) use ($responses) {
            $questions_responses = array_filter($responses, function ($response) use ($question) {
                return $response->form_question_id == $question->id;
            });
            if (count($questions_responses) > 0) {
                usort($questions_responses, function ($a, $b) {
                    return $a->id < $b->id ? 1 : -1;
                });
                $question->latest_response = $questions_responses[0];
            }
            $field_name = 'tuja_formshortcode_response_' . $question->id;
            $html_field = Field::create($question)->render($field_name);
            return sprintf('<p>%s</p>', $html_field);
        }, $questions));
        $html_greeting = sprintf('<p>Hej, %s.</p>', $group->name);
        $submit_button = sprintf('<button type="submit" name="tuja_formshortcode_action" value="update">Uppdatera svar</button>');
        return sprintf(
            '%s %s %s<form method="post">%s %s</form>',
            $html_greeting,
            isset($message_success) ? sprintf('<p class="tuja-message tuja-message-success">%s</p>', $message_success) : '',
            isset($message_error) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $message_error) : '',
            $html_form,
            $submit_button);
    }
}