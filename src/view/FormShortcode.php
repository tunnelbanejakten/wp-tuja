<?php

namespace view;

use data\store\FormDao;
use data\store\GroupDao;
use data\store\QuestionDao;
use data\store\ResponseDao;
use Exception;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\view\Field;

class FormShortcode
{
    private $question_dao;
    private $group_dao;
    private $response_dao;

    const TEAMS_DROPDOWN_NAME = 'tuja_formshortcode_response_group';

    public function __construct($wpdb, $form_id, $group_key)
    {
        $this->form_id = $form_id;
        $this->group_key = $group_key;
        $this->question_dao = new QuestionDao($wpdb);
        $this->group_dao = new GroupDao($wpdb);
        $this->response_dao = new ResponseDao($wpdb);
        $this->form_dao = new FormDao($wpdb);
    }

    public function update_answers($group_id): array
    {
        $errors = array();
        $overall_success = true;
        $questions = $this->question_dao->get_all_in_form($this->form_id);
        foreach ($questions as $question) {
            $user_answer = $_POST['tuja_formshortcode_response_' . $question->id];
            if (isset($user_answer)) {
                try {
                    $new_response = new Response();
                    $new_response->group_id = $group_id;
                    $new_response->form_question_id = $question->id;
                    $new_response->answer = $user_answer;
                    $new_response->points = null;

                    $affected_rows = $this->response_dao->create($new_response);

                    $this_success = $affected_rows !== false && $affected_rows === 1;
                    $overall_success = ($overall_success and $this_success);
                } catch (Exception $e) {
                    $overall_success = false;
                    $errors['tuja_formshortcode_response_' . $question->id] = $e->getMessage();
                }
            }
        }
        return $errors;
    }

    public function render(): String
    {
        $html_sections = [];
        $group_key = $this->group_key;
        $group = $this->group_dao->get_by_key($group_key);
        if ($group === false) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
        }

        $html_sections[] = sprintf('<p>Hej, %s.</p>', $group->name);

        if ($group->type === 'crew') {
            $html_sections[] = sprintf('<p>%s</p>', $this->get_groups_dropdown());

            $group_id = $_POST[self::TEAMS_DROPDOWN_NAME];
        } else {
            $group_id = $group->id;
        }

        $message_success = null;
        $message_error = null;
        $errors = array();
        switch ($_POST['tuja_formshortcode_action']) {
            case 'update':
                $errors = $this->update_answers($group_id);
                if (empty($errors)) {
                    $message_success = 'Era svar har sparats.';
                    $html_sections[] = sprintf('<p class="tuja-message tuja-message-success">%s</p>', $message_success);
                } else {
                    $message_error = 'Oj, det gick inte att spara era svar.';
                    $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $message_error);
                }
                break;
        }

        $responses = $this->response_dao->get_by_group($group_id);
        $questions = $this->question_dao->get_all_in_form($this->form_id);

        $html_sections[] = join(array_map(function ($question) use ($responses, $errors) {
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
            return sprintf('<div class="tuja-question %s">%s%s</div>',
                isset($errors[$field_name]) ? 'tuja-field-error' : '',
                $html_field,
                isset($errors[$field_name]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[$field_name]) : '');
        }, $questions));
        $html_sections[] = sprintf('<button type="submit" name="tuja_formshortcode_action" value="update">Uppdatera svar</button>');
        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function get_groups_dropdown(): string
    {
        $choose_group_question = new Question();
        $choose_group_question->type = 'dropdown';
        $choose_group_question->text = 'Vilket lag vill du rapportera för?';

        $form = $this->form_dao->get($this->form_id);
        $competition_id = $form->competition_id;

        $competition_groups = $this->group_dao->get_all_in_competition($competition_id);
        $participant_groups = array_filter($competition_groups, function ($option) {
            return $option->type === 'participant';
        });
        $options = array_combine(
            array_map(function ($option) {
                return $option->id;
            }, $participant_groups),
            array_map(function ($option) {
                return $option->name;
            }, $participant_groups));
        $choose_group_question->set_answer_one_of($options);
        $html_field = Field::create($choose_group_question)->render(self::TEAMS_DROPDOWN_NAME);
        return $html_field;
    }
}