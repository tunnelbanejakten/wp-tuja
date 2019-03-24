<?php

namespace tuja\view;

use Exception;
use tuja\data\model\Question;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;

class PointsShortcode
{
    private $competition_id;
    private $question_dao;
    private $group_dao;
    private $points_dao;
    private $group_key;
    private $participant_groups;
    private $category_dao;

    const FIELD_NAME_PART_SEP = '__';
    const FORM_PREFIX = 'tuja_pointsshortcode';
    const OPTIMISTIC_LOCK_FIELD_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'optimistic_lock';
    const ACTION_FIELD_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'action';
    const FILTER_DROPDOWN_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter';
    const QUESTION_FIELD_PREFIX = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'question';

    public function __construct($wpdb, $competition_id, $group_key)
    {
        $this->competition_id = $competition_id;
	    $this->group_key      = $group_key;
	    $this->question_dao   = new QuestionDao();
	    $this->group_dao      = new GroupDao();
	    $this->points_dao     = new PointsDao();
	    $this->category_dao   = new GroupCategoryDao();
    }

    public function update_points(): array
    {
        $errors = array();

        $form_values = array_filter($_POST, function ($key) {
            return substr($key, 0, strlen(self::QUESTION_FIELD_PREFIX)) === self::QUESTION_FIELD_PREFIX;
        }, ARRAY_FILTER_USE_KEY);

        try {
            $this->check_optimistic_lock($form_values);
        } catch (Exception $e) {
            // We do not want to present the previously inputted values in case we notice that another user has assigned score to the same questions.
            // The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
            foreach ($form_values as $field_name => $field_value) {
                unset($_POST[$field_name]);
            }
            return array($e->getMessage());
        }

        foreach ($form_values as $field_name => $field_value) {
            list(, , $question_id, $group_id) = explode(self::FIELD_NAME_PART_SEP, $field_name);
            try {
                // TODO: Make sure user cannot input a higher score than allowed by the question
                $this->points_dao->set($group_id, $question_id, is_numeric($field_value) ? intval($field_value) : null);
            } catch (Exception $e) {
                // TODO: Use the key to display the error message next to the problematic text field.
                $errors[$field_name] = $e->getMessage();
            }
        }

        return $errors;
    }

    public function render(): String
    {
        $group_key = $this->group_key;
        $crew_group = $this->group_dao->get_by_key($group_key);
        if ($crew_group === false) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Vi vet inte vilken grupp du tillhör.');
        }

        $group_category = $this->category_dao->get($crew_group->category_id);
        if (!$group_category->is_crew) {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Bara funktionärer får använda detta formulär.');
        }

        $html_sections = [];

        $message_success = null;
        $message_error = null;
	    if ( isset( $_POST[ self::ACTION_FIELD_NAME ] ) && $_POST[ self::ACTION_FIELD_NAME ] == 'update' ) {
            $errors = $this->update_points();
            if (empty($errors)) {
                $message_success = 'Poängen har sparats.';
                $html_sections[] = sprintf('<p class="tuja-message tuja-message-success">%s</p>', $message_success);
            } else {
                $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', join('. ', $errors));
            }
        }

        $html_sections[] = sprintf('<p>%s</p>', $this->get_filter_field());

        $groups = array_filter($this->get_participant_groups(), function ($group) {
	        return isset( $_POST[ self::FILTER_DROPDOWN_NAME ] ) && ( substr( $_POST[ self::FILTER_DROPDOWN_NAME ], 0, strlen( 'group' ) ) !== 'group' || $_POST[ self::FILTER_DROPDOWN_NAME ] == 'group' . $group->id );
        });
        $questions = array_filter($this->question_dao->get_all_in_competition($this->competition_id), function ($question) {
	        return isset( $_POST[ self::FILTER_DROPDOWN_NAME ] ) && ( substr( $_POST[ self::FILTER_DROPDOWN_NAME ], 0, strlen( 'question' ) ) !== 'question' || $_POST[ self::FILTER_DROPDOWN_NAME ] == 'question' . $question->id );
        });

        $current_points = $this->points_dao->get_by_competition($this->competition_id);
        $current_points = array_combine(
            array_map(function ($points) {
                return $points->form_question_id . self::FIELD_NAME_PART_SEP . $points->group_id;
            }, $current_points),
            array_values($current_points));

        if (!empty($_POST[self::FILTER_DROPDOWN_NAME])) {
            if (count($groups) == 1) {
                $group = array_values($groups)[0];
                foreach ($questions as $question) {
                    $html_sections[] = sprintf('<p>%s</p>', $this->render_field($question->text, $question->score_max, $question->id, $group->id, $current_points));
                }
            } elseif (count($questions) == 1) {
                $question = array_values($questions)[0];
                foreach ($groups as $group) {
                    $html_sections[] = sprintf('<p>%s</p>', $this->render_field($group->name, $question->score_max, $question->id, $group->id, $current_points));
                }
            }

            $optimistic_lock_value = $this->get_optimistic_lock_value($this->get_keys($groups, $questions));

            $html_sections[] = sprintf('<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, $optimistic_lock_value);

            $html_sections[] = sprintf('<div class="tuja-buttons"><button type="submit" name="%s" value="update">Spara</button></div>', self::ACTION_FIELD_NAME);
        }

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function get_keys($groups, $questions): array
    {
        $keys = [];
        foreach ($groups as $group) {
            foreach ($questions as $question) {
                $keys[] = new PointsKey($group->id, $question->id);
            }
        }
        return $keys;
    }

    public function get_filter_field()
    {
        $render_id = self::FILTER_DROPDOWN_NAME;
        // TODO: Show user if the assigned points will actually be counted or if the use has provided a new answer to the question which in effect nullifies the points assigned here.
        $hint = sprintf('<br><span class="tuja-question-hint">%s</span>', 'Kom ihåg att spara innan du byter.');
        return sprintf('<div class="tuja-field"><label for="%s">%s%s</label>%s</div>',
            $render_id,
            'Vad vill du rapportera för?',
            $hint,
            $this->render_filter_dropdown()
        );
    }

    // TODO: Extend FieldChoices so that it supports <optgroup>?

    public function render_filter_dropdown()
    {
        $questions = $this->question_dao->get_all_in_competition($this->competition_id);
        $groups = $this->get_participant_groups();

        $question_option_values = array_map(function ($q) {
            return 'question' . $q->id;
        }, $questions);
        $question_option_labels = array_map(function ($q) {
            return $q->text;
        }, $questions);
        $question_options = join(array_map(function ($value, $label) {
            return sprintf('<option value="%s" %s>%s</option>',
                htmlspecialchars($value),
                $this->is_selected($value) ? ' selected="selected"' : '',
                htmlspecialchars($label));
        }, $question_option_values, $question_option_labels));

        $group_option_values = array_map(function ($group) {
            return 'group' . $group->id;
        }, $groups);
        $group_option_labels = array_map(function ($group) {
            return $group->name;
        }, $groups);
        $group_options = join(array_map(function ($value, $label) {
            return sprintf('<option value="%s" %s>%s</option>',
                htmlspecialchars($value),
                $this->is_selected($value) ? ' selected="selected"' : '',
                htmlspecialchars($label));
        }, $group_option_values, $group_option_labels));

        return sprintf('' .
            '<select id="%s" name="%s" class="tuja-fieldchoices tuja-fieldchoices-longlist" onchange="this.form.submit()" size="1">' .
            '  <option value="">Välj fråga eller lag</option>' .
            '  <optgroup label="%s">%s</optgroup>' .
            '  <optgroup label="%s">%s</optgroup>' .
            '</select>',
            self::FILTER_DROPDOWN_NAME,
            self::FILTER_DROPDOWN_NAME,
            'Frågor',
            $question_options,
            'Lag',
            $group_options);
    }

    private function get_participant_groups(): array
    {
        if (!isset($this->participant_groups)) {
            // TODO: DRY... Very similar code in FormShortcode.php
            $categories = $this->category_dao->get_all_in_competition($this->competition_id);
            $participant_categories = array_filter($categories, function ($category) {
                return !$category->is_crew;
            });
            $ids = array_map(function ($category) {
                return $category->id;
            }, $participant_categories);

            $competition_groups = $this->group_dao->get_all_in_competition($this->competition_id);
            $this->participant_groups = array_filter($competition_groups, function ($group) use ($ids) {
                return in_array($group->category_id, $ids);
            });
        }
        return $this->participant_groups;
    }

    private function is_selected($value)
    {
	    return isset( $_POST[ self::FILTER_DROPDOWN_NAME ] ) && $value == $_POST[ self::FILTER_DROPDOWN_NAME ];
    }

    private function render_field($text, $max_score, $question_id, $group_id, $current_points): string
    {
	    $key        = $question_id . self::FIELD_NAME_PART_SEP . $group_id;
	    $points     = isset( $current_points[ $key ] ) ? $current_points[ $key ]->points : null;
	    $field      = Field::create( Question::text( $text, sprintf( 'Max %d poäng.', $max_score ), $points ) );
        $field_name = self::QUESTION_FIELD_PREFIX . self::FIELD_NAME_PART_SEP . $question_id . self::FIELD_NAME_PART_SEP . $group_id;
        return $field->render($field_name);
    }

    private function get_optimistic_lock_value(array $keys)
    {
        $current_points = $this->points_dao->get_by_competition($this->competition_id);
	    $points_by_key  = array_combine(
            array_map(function ($points) {
                return $points->form_question_id . self::FIELD_NAME_PART_SEP . $points->group_id;
            }, $current_points),
            array_values($current_points));

	    $current_optimistic_lock_value = array_reduce( $keys, function ( $carry, PointsKey $key ) use ( $points_by_key ) {
		    $temp_key = $key->question_id . self::FIELD_NAME_PART_SEP . $key->group_id;

		    $response_timestamp = isset( $points_by_key[ $temp_key ] )
			    ? $points_by_key[ $temp_key ]->created != null
				    ? $points_by_key[ $temp_key ]->created->getTimestamp()
				    : 0
			    : 0;

		    return max( $carry, $response_timestamp );
	    }, 0 );

        return $current_optimistic_lock_value;
    }

    private function check_optimistic_lock($form_values)
    {
        $keys = array_map(function ($field_name) {
            list(, , $question_id, $group_id) = explode(self::FIELD_NAME_PART_SEP, $field_name);
            return new PointsKey($group_id, $question_id);
        }, array_keys($form_values));

        $current_optimistic_lock_value = $this->get_optimistic_lock_value($keys);

        if ($current_optimistic_lock_value != $_POST[self::OPTIMISTIC_LOCK_FIELD_NAME]) {
            throw new Exception('' .
                'Någon annan har hunnit rapportera in andra poäng för dessa frågor/lag sedan du ' .
                'laddade den här sidan. För att undvika att du av misstag skriver över andra ' .
                'funktionärers poäng så sparades inte poängen du angav. De senast inrapporterade ' .
                'poängen visas istället för de du rapporterade in.');
        }
    }

}

class PointsKey
{
    public $group_id;
    public $question_id;

    public function __construct($group_id, $question_id)
    {
        $this->group_id = $group_id;
        $this->question_id = $question_id;
    }
}