<?php

namespace view;

use data\store\FormDao;
use data\store\GroupDao;
use data\store\QuestionDao;
use data\store\ResponseDao;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\view\Field;

class FormShortcode
{
    private $question_dao;
    private $group_dao;
    private $response_dao;

    const TEAMS_DROPDOWN_NAME = 'tuja_formshortcode_response_team';

    public function __construct($wpdb, $form_id, $team_key)
    {
        $this->form_id = $form_id;
        $this->team_key = $team_key;
        $this->question_dao = new QuestionDao($wpdb);
        $this->group_dao = new GroupDao($wpdb);
        $this->response_dao = new ResponseDao($wpdb);
        $this->form_dao = new FormDao($wpdb);
    }

    public function update_answers($team_id): String
    {
        $overall_success = true;
        $questions = $this->question_dao->get_all_in_form($this->form_id);
        foreach ($questions as $question) {
            if (isset($_POST['tuja_formshortcode_response_' . $question->id])) {
                $new_response = new Response();
                $new_response->team_id = $team_id;
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
        $html_sections = [];
        $team_key = $this->team_key;
        $group = $this->group_dao->get_by_key($team_key);
        if ($group === false) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
        }

        $html_sections[] = sprintf('<p>Hej, %s.</p>', $group->name);

        if ($group->type === 'crew') {
            $html_sections[] = sprintf('<p>%s</p>', $this->get_teams_dropdown());

            $team_id = $_POST[self::TEAMS_DROPDOWN_NAME];
        } else {
            $team_id = $group->id;
        }

        $message_success = null;
        $message_error = null;
        switch ($_POST['tuja_formshortcode_action']) {
            case 'update':
                $updated = $this->update_answers($team_id);
                if ($updated) {
                    $message_success = 'Era svar har sparats.';
                    $html_sections[] = sprintf('<p class="tuja-message tuja-message-success">%s</p>', $message_success);
                } else {
                    $message_error = 'Oj, det gick inte att spara era svar.';
                    $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $message_error);
                }
                break;
        }

        $responses = $this->response_dao->get_by_team($team_id);
        $questions = $this->question_dao->get_all_in_form($this->form_id);

        $html_sections[] = join(array_map(function ($question) use ($responses) {
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
        $html_sections[] = sprintf('<button type="submit" name="tuja_formshortcode_action" value="update">Uppdatera svar</button>');
        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function get_teams_dropdown(): string
    {
        $choose_team_question = new Question();
        $choose_team_question->type = 'dropdown';
        $choose_team_question->text = 'Vilket lag vill du rapportera för?';

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
        $choose_team_question->set_answer_one_of($options);
        $html_field = Field::create($choose_team_question)->render(self::TEAMS_DROPDOWN_NAME);
        return $html_field;
    }
}