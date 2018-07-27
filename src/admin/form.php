<?php

use tuja\data\model\Question;
use tuja\view\Field;

const FORM_FIELD_NAME_PREFIX = 'tuja-question';
const ACTION_NAME_DELETE_PREFIX = 'question_delete__';

$form = $db_form->get($_GET['tuja_form']);
if (!$form) {
    print 'Could not find form';
    return;
}
if ($_POST['tuja_action'] == 'questions_update') {
    $wpdb->show_errors();

    $form_values = array_filter($_POST, function ($key) {
        return substr($key, 0, strlen(FORM_FIELD_NAME_PREFIX)) === FORM_FIELD_NAME_PREFIX;
    }, ARRAY_FILTER_USE_KEY);

    $questions = $db_question->get_all_in_form($form->id);

    $updated_questions = array_combine(array_map(function ($q) {
        return $q->id;
    }, $questions), $questions);
    foreach ($form_values as $field_name => $field_value) {
        list(, $id, $attr) = explode('__', $field_name);
        switch ($attr) {
            case 'type':
                $updated_questions[$id]->type = $field_value;
                break;
            case 'text':
                $updated_questions[$id]->text = $field_value;
                break;
            case 'text_hint':
                $updated_questions[$id]->text_hint = $field_value;
                break;
            case 'scoretype':
                $updated_questions[$id]->score_type = !empty($field_value) ? $field_value : null;
                break;
            case 'scoremax':
                $updated_questions[$id]->score_max = $field_value;
                break;
            case 'correct_answers':
                $updated_questions[$id]->correct_answers = array_map('trim', explode("\n", trim($field_value)));
                break;
            case 'possible_answers':
                $updated_questions[$id]->possible_answers = array_map('trim', explode("\n", trim($field_value)));
                break;
            case 'sort_order':
                $updated_questions[$id]->sort_order = $field_value;
                break;
        }
    }

    $overall_success = true;
    foreach ($updated_questions as $updated_question) {
        try {
            $affected_rows = $db_question->update($updated_question);
            $this_success = $affected_rows !== false && $affected_rows === 1;
            $overall_success = ($overall_success and $this_success);
        } catch (Exception $e) {
            $overall_success = false;
        }
    }
    var_dump($overall_success); // TODO: Show nicer status message
} elseif ($_POST['tuja_action'] == 'question_create') {
    $props = new Question();
    $props->correct_answers = array('Alice');
    $props->possible_answers = array('Alice', 'Bob');
    $props->form_id = $form->id;
    try {
        $affected_rows = $db_question->create($props);
        $success = $affected_rows !== false && $affected_rows === 1;
    } catch (Exception $e) {
        $success = false;
    }
    var_dump($success); // TODO: Show nicer status message
} elseif (substr($_POST['tuja_action'], 0, strlen(ACTION_NAME_DELETE_PREFIX)) == ACTION_NAME_DELETE_PREFIX) {
    $wpdb->show_errors(); // TODO: Show nicer error message if question cannot be deleted (e.g. in case someone has answered the question already)

    $question_to_delete = substr($_POST['tuja_action'], strlen(ACTION_NAME_DELETE_PREFIX));

    $affected_rows = $db_question->delete($question_to_delete);
    $success = $affected_rows !== false && $affected_rows === 1;
    var_dump($success); // TODO: Show nicer status message
}
$competition = $db_competition->get($form->competition_id);

$competition_url = add_query_arg(array(
    'tuja_view' => 'competition',
    'tuja_competition' => $competition->id
));
?>
<h1>Tunnelbanejakten</h1>
<h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>
<h3>Formulär <?= $form->name ?></h3>
<form method="post" action="<?= add_query_arg() ?>">
    <?php
    $questions = $db_question->get_all_in_form($form->id);

    foreach ($questions as $question) {
        printf('<div class="tuja-admin-question">');
        printf('<div class="tuja-admin-question-properties">');

        //TODO: There is a lot of quite similar code for generating HTML here. Can a helper method be implemented in order to DRY?

        $render_id = uniqid();
        $field_name = FORM_FIELD_NAME_PREFIX . '__' . $question->id . '__type';
        $selected_value = $_POST[$field_name] ?: $question->type;
        $options = array(
            'text' => 'Fritext',
            'dropdown' => 'Välj ett alternativ',
            'multi' => 'Välj flera alternativ'
        );
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-type"><label for="%s">%s</label><select id="%s" name="%s">%s</select></div>',
            $render_id,
            'Typ av fråga',
            $render_id,
            $field_name,
            join(array_map(function ($key, $value) use ($selected_value) {
                return sprintf('<option value="%s" %s>%s</option>', $key, $key == $selected_value ? ' selected="selected"' : '', $value);
            }, array_keys($options), array_values($options))));

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__text';
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-text"><label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" /></div>',
            $render_id,
            'Text',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->text);

        $render_id = uniqid();
        $field_name = FORM_FIELD_NAME_PREFIX . '__' . $question->id . '__scoretype';
        $selected_value = $_POST[$field_name] ?: $question->score_type;
        $options = array(
            '' => 'Rätta inte',
            Question::QUESTION_GRADING_TYPE_ONE_OF => 'Ge poäng om minst ett korrekt svar angivits',
            Question::QUESTION_GRADING_TYPE_ALL_OF => 'Ge poäng om alla korrekta svar angivits',
            Question::QUESTION_GRADING_TYPE_SUBMITTED_ANSWER_IS_POINTS => 'Svaret är poängen'
        );
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-type"><label for="%s">%s</label><select id="%s" name="%s">%s</select></div>',
            $render_id,
            'Rättningsmall',
            $render_id,
            $field_name,
            join(array_map(function ($key, $value) use ($selected_value) {
                return sprintf('<option value="%s" %s>%s</option>', $key, $key == $selected_value ? ' selected="selected"' : '', $value);
            }, array_keys($options), array_values($options))));

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__scoremax';
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-text"><label for="%s">%s</label><input type="number" id="%s" name="%s" value="%s" /></div>',
            $render_id,
            'Maxpoäng',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->score_max);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__text_hint';
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-texthint"><label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" /></div>',
            $render_id,
            'Tips eller vägledning',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->text_hint);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__sort_order';
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-sortorder"><label for="%s">%s</label><input type="number" id="%s" name="%s" value="%s" /></div>',
            $render_id,
            'Ordning',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->sort_order);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__correct_answers';
        $answer = stripslashes(isset($_POST[$field_name]) ? $_POST[$field_name] : implode("\n", $question->correct_answers ?: array()));
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-correctanswers"><label for="%s">%s</label><textarea cols="50" rows="5" id="%s" name="%s">%s</textarea></div>',
            $render_id,
            'Korrekta svar (ett per rad)',
            $render_id,
            $field_name,
            $answer);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__possible_answers';
        $answer = stripslashes(isset($_POST[$field_name]) ? $_POST[$field_name] : implode("\n", $question->possible_answers ?: array()));
        printf('<div class="tuja-admin-question-property tuja-admin-question-property-possibleanswers"><label for="%s">%s</label><textarea cols="50" rows="5" id="%s" name="%s">%s</textarea></div>',
            $render_id,
            'Alternativ att visa (ett per rad)',
            $render_id,
            $field_name,
            $answer);

        printf('</div>');

        printf('<button type="submit" name="tuja_action" value="%s%d">Ta bort</button>', ACTION_NAME_DELETE_PREFIX, $question->id);

        $html_field = Field::create($question)->render('tuja_' . uniqid());
        printf('<div class="tuja-admin-question-preview"><p>Förhandsgranskning av fråga <br>(uppdateras när du sparar ändringarna): </p>%s</div>', $html_field);

        printf('</div>');
    }
    ?>
    <button type="submit" name="tuja_action" value="questions_update">Spara</button>
    <button type="submit" name="tuja_action" value="question_create">Ny fråga</button>
</form>
