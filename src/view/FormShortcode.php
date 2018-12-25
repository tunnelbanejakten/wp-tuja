<?php

namespace view;

use data\store\FormDao;
use data\store\GroupDao;
use data\store\QuestionDao;
use data\store\ResponseDao;
use DateTime;
use Exception;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\view\Field;

class FormShortcode
{
    private $question_dao;
    private $group_dao;
    private $response_dao;

    const FORM_FIELD_NAME_PREFIX = 'tuja_formshortcode_response_';

    const TEAMS_DROPDOWN_NAME = FORM_FIELD_NAME_PREFIX . 'group';

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

        $responses = $this->response_dao->get_latest_by_group($group_id);

        if (!$this->is_submit_allowed()) {
            $errors[self::FORM_FIELD_NAME_PREFIX] = 'Svar får inte skickas in nu.';
        }

        foreach ($questions as $question) {
            $user_answer = Field::create($question)->get_posted_answer(self::FORM_FIELD_NAME_PREFIX . $question->id);
            if (isset($user_answer)) {
                $user_answer_array = is_array($user_answer) ? $user_answer : array($user_answer);
                if (!isset($responses[$question->id]) || $user_answer_array != $responses[$question->id]->answers) {
                    try {
                        $new_response = new Response();
                        $new_response->group_id = $group_id;
                        $new_response->form_question_id = $question->id;
                        $new_response->answers = $user_answer_array;

                        $affected_rows = $this->response_dao->create($new_response);

                        $this_success = $affected_rows !== false && $affected_rows === 1;
                        $overall_success = ($overall_success and $this_success);
                    } catch (Exception $e) {
                        $overall_success = false;
                        $errors[self::FORM_FIELD_NAME_PREFIX . $question->id] = $e->getMessage();
                    }
                }
            }
        }
        return $errors;
    }

    private function is_submit_allowed(): bool
    {
        $form = $this->form_dao->get($this->form_id);
        $now = new DateTime();
        if ($form->submit_response_start != null && $form->submit_response_start > $now) {
            return false;
        }
        if ($form->submit_response_end != null && $form->submit_response_end < $now) {
            return false;
        }
        return true;
    }

    public function render(): String
    {
        $html_sections = [];
        $group_key = $this->group_key;
        $group = $this->group_dao->get_by_key($group_key);
        if ($group === false) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
        }

        if ($group->type === 'crew') {
            $participant_groups = $this->get_participant_groups();

            $html_sections[] = sprintf('<p>%s</p>', $this->get_groups_dropdown($participant_groups));

            $selected_participant_group = $this->get_selected_group($participant_groups);

            $group_id = $selected_participant_group->id;
        } else {
            $group_id = $group->id;
        }

        $is_read_only = !$this->is_submit_allowed();

        if ($group_id) {
            $message_success = null;
            $message_error = null;
            $errors = array();
            if ($_POST['tuja_formshortcode_action'] == 'update') {
                $errors = $this->update_answers($group_id);
                if (empty($errors)) {
                    $message_success = 'Era svar har sparats.';
                    $html_sections[] = sprintf('<p class="tuja-message tuja-message-success">%s</p>', $message_success);
                } else {
                    $message_error = 'Oj, det gick inte att spara era svar. ';
                    if (isset($errors[self::FORM_FIELD_NAME_PREFIX])) {
                        $message_error .= $errors[self::FORM_FIELD_NAME_PREFIX];
                    }
                    $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', trim($message_error));
                }
            }
            // We do not want to present the previously inputted values in case the user changed from one group to another.
            // The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
            // Keep the previous form values if the user clicked "Update responses", not otherwise.
            $ignore_previous_form_values = $_POST['tuja_formshortcode_action'] != 'update';

            $responses = $this->response_dao->get_latest_by_group($group_id);
            $questions = $this->question_dao->get_all_in_form($this->form_id);

            $html_sections[] = join(array_map(function ($question) use ($responses, $errors, $ignore_previous_form_values, $is_read_only) {
                $question->latest_response = $responses[$question->id];
                $field_name = 'tuja_formshortcode_response_' . $question->id;
                if ($ignore_previous_form_values) {
                    // Clear input field value from previous submission:
                    unset($_POST[$field_name]);
                }
                $field = Field::create($question);
                $field->read_only = $is_read_only;
                $html_field = $field->render($field_name);
                return sprintf('<div class="tuja-question %s">%s%s</div>',
                    isset($errors[$field_name]) ? 'tuja-field-error' : '',
                    $html_field,
                    isset($errors[$field_name]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[$field_name]) : '');
            }, $questions));
            if (!$is_read_only) {
                $html_sections[] = sprintf('<button type="submit" name="tuja_formshortcode_action" value="update">Uppdatera svar</button>');
            } else {
                $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>',
                    'Svar får inte skickas in nu.');
            }

        }

        return sprintf('<form method="post" enctype="multipart/form-data" onsubmit="if (tujaUpload) { tujaUpload.removeRedundantFileFields() }">%s</form>', join($html_sections));
    }

    private function get_groups_dropdown($participant_groups): string
    {
        $choose_group_question = new Question();
        $choose_group_question->type = 'dropdown';
        $choose_group_question->text = 'Vilket lag vill du rapportera för?';
        $choose_group_question->text_hint = 'Byt inte lag om du har osparade ändringar.';

        $options = array_map(function ($option) {
            return $option->name;
        }, $participant_groups);
        $choose_group_question->possible_answers = array_merge(array('' => 'Välj lag'), $options);
        $field = Field::create($choose_group_question);
        $field->submit_on_change = true;
        $html_field = $field->render(self::TEAMS_DROPDOWN_NAME);
        return $html_field;
    }

    private function get_participant_groups(): array
    {
        $form = $this->form_dao->get($this->form_id);
        $competition_id = $form->competition_id;

        $competition_groups = $this->group_dao->get_all_in_competition($competition_id);
        $participant_groups = array_filter($competition_groups, function ($option) {
            return $option->type === 'participant';
        });
        return $participant_groups;
    }

    private function get_selected_group($participant_groups)
    {
        $selected_group_name = $_POST[self::TEAMS_DROPDOWN_NAME];
        $selected_group = array_values(array_filter($participant_groups, function ($group) use ($selected_group_name) {
            return strcmp($group->name, $selected_group_name) == 0;
        }))[0];
        return $selected_group;
    }
}