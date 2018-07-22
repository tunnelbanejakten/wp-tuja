<?php

use tuja\data\model\Question;

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
            case 'answer':
                $updated_questions[$id]->answer = stripslashes($field_value);
                break;
            case 'sort_order':
                $updated_questions[$id]->sort_order = $field_value;
                break;
        }
    }

    $overall_success = true;
    foreach ($updated_questions as $updated_question) {
        $affected_rows = $db_question->update($updated_question);
        $this_success = $affected_rows !== false && $affected_rows === 1;
        $overall_success = ($overall_success and $this_success);
    }
    var_dump($overall_success); // TODO: Show nicer status message
} elseif ($_POST['tuja_action'] == 'question_create') {
    $props = new Question();
    $props->set_answer_one_of_these('alice', array('alice' => 'Alice', 'bob' => 'Bob'));
    $props->form_id = $form->id;
    $affected_rows = $db_question->create($props);
    $success = $affected_rows !== false && $affected_rows === 1;
    var_dump($success); // TODO: Show nicer status message
} elseif (substr($_POST['tuja_action'], 0, strlen(ACTION_NAME_DELETE_PREFIX)) == ACTION_NAME_DELETE_PREFIX) {
    $wpdb->show_errors(); // TODO: Show nicer error message if question cannot be deleted (e.g. in case someone has answered the question already)

    $question_to_delete = substr($_POST['tuja_action'], strlen(ACTION_NAME_DELETE_PREFIX));

    $affected_rows = $db_question->delete($question_to_delete);
    $success = $affected_rows !== false && $affected_rows === 1;
    var_dump($success); // TODO: Show nicer status message
}
$competition = $db_competition->get($form->competition_id);
?>
<h1>Tunnelbanejakten</h1>
<h2>Tävling <?= $competition->name ?></h2>
<h3>Formulär <?= $form->name ?></h3>
<form method="post" action="<?= add_query_arg() ?>">
    <?php
    $questions = $db_question->get_all_in_form($form->id);

    foreach ($questions as $question) {
        printf('<div class="tuja-admin-question">');

        //TODO: There is a lot of quite similar code for generating HTML here. Can a helper method be implemented in order to DRY?

        $render_id = uniqid();
        $field_name = FORM_FIELD_NAME_PREFIX . '__' . $question->id . '__type';
        $selected_value = $_POST[$field_name] ?: $question->type;
        $options = array(
            'text' => 'Fritext',
            'dropdown' => 'Välj ett alternativ'
        );
        printf('<label for="%s">%s</label><select id="%s" name="%s">%s</select>',
            $render_id,
            'Typ av fråga',
            $render_id,
            $field_name,
            join(array_map(function ($key, $value) use ($selected_value) {
                return sprintf('<option value="%s" %s>%s</option>', $key, $key == $selected_value ? ' selected="selected"' : '', $value);
            }, array_keys($options), array_values($options))));

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__text';
        printf('<label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" />',
            $render_id,
            'Text',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->text);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__text_hint';
        printf('<label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" />',
            $render_id,
            'Tips eller vägledning',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->text_hint);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__sort_order';
        printf('<label for="%s">%s</label><input type="number" id="%s" name="%s" value="%s" />',
            $render_id,
            'Ordning',
            $render_id,
            $field_name,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $question->sort_order);

        $render_id = uniqid();
        $field_name = 'tuja-question__' . $question->id . '__answer';
        $answer = stripslashes(isset($_POST[$field_name]) ? $_POST[$field_name] : json_encode(json_decode($question->answer), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        printf('<label for="%s">%s</label><textarea cols="50" rows="10" id="%s" name="%s">%s</textarea>',
            $render_id,
            'Svar',
            $render_id,
            $field_name,
            $answer);
        printf('<button type="submit" name="tuja_action" value="%s%d">Ta bort</button>', ACTION_NAME_DELETE_PREFIX, $question->id);
        printf('</div>');
    }
    ?>
    <button type="submit" name="tuja_action" value="questions_update">Spara</button>
    <button type="submit" name="tuja_action" value="question_create">Ny fråga</button>
</form>
