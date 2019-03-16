<?php
namespace tuja\admin;


use tuja\data\model\Question;
use tuja\view\Field;
use tuja\util\DateUtils;

?>

<h1>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h1>
<h3>Formulär <?= $this->form->name ?></h3>
<form method="post">

    <p><strong>Inställningar</strong></p>

    <div class="tuja-admin-question">
        <div class="tuja-admin-question-properties">
            <div class="tuja-admin-question-property tuja-admin-question-short">
                <label for="">Svar accepteras fr.o.m.</label>
                <input type="datetime-local" name="tuja-submit-response-start" placeholder="yyyy-mm-dd hh:mm"
                       value="<?= DateUtils::to_date_local_value($this->form->submit_response_start) ?>"/>
            </div>
            <div class="tuja-admin-question-property tuja-admin-question-short">
                <label for="">Svar accepteras t.o.m.</label>
                <input type="datetime-local" name="tuja-submit-response-end" placeholder="yyyy-mm-dd hh:mm"
                       value="<?= DateUtils::to_date_local_value($this->form->submit_response_end) ?>"/>
            </div>
        </div>
    </div>

    <button class="button button-primary" type="submit" name="tuja_action" value="form_update">
        Spara inställningar
    </button>

    <p><strong>Frågor</strong></p>

    <?php
    $questions = $db_question->get_all_in_form($this->form->id);

    foreach ($questions as $question) {
        printf('<div class="tuja-admin-question">');
        printf('<div class="tuja-admin-question-properties">');

        //TODO: There is a lot of quite similar code for generating HTML here. Can a helper method be implemented in order to DRY?

        $render_id = uniqid();
        $field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $question->id . '__type';
		$selected_value = !empty($_POST[$field_name]) ? $_POST[$field_name] : $question->type;
		
        // TODO: Extract question types to constants. DRY. Sync with \tuja\data\model\Question::VALID_TYPES.
        $options = array(
            'text' => 'Fritext',
            'images' => 'Bilder',
            'pick_one' => 'Välj ett alternativ',
            'pick_multi' => 'Välj flera alternativ',
            'text_multi' => 'Skriv flera alternativ'
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
        $field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $question->id . '__scoretype';
        $selected_value = !empty($_POST[$field_name]) ? $_POST[$field_name] : $question->score_type;
        $options = array(
            '' => 'Rätta inte',
            Question::GRADING_TYPE_ONE_OF => 'Ge poäng om minst ett korrekt svar angivits',
            Question::GRADING_TYPE_ORDERED_PERCENT_OF=> 'Ge poäng utifrån andel korrekta svar (i ordning)',
            Question::GRADING_TYPE_UNORDERED_PERCENT_OF => 'Ge poäng utifrån andel korrekta svar (oavsett ordning)',
            Question::GRADING_TYPE_ALL_OF => 'Ge poäng om alla korrekta svar angivits'
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

        printf('<button type="submit" class="button" name="tuja_action" value="%s%d">Ta bort</button>', self::ACTION_NAME_DELETE_PREFIX, $question->id);

        $html_field = Field::create($question)->render('tuja_' . uniqid());
        printf('<div class="tuja-admin-question-preview"><p>Förhandsgranskning av fråga <br>(uppdateras när du sparar ändringarna): </p>%s</div>', $html_field);

        printf('</div>');
    }
    ?>
    <button type="submit" name="tuja_action" class="button button-primary" value="questions_update">Spara frågor
    </button>
    <button type="submit" name="tuja_action" class="button" value="question_create">Ny fråga</button>
</form>
