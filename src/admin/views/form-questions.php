<?php
namespace tuja\admin;


use tuja\data\model\Question;
use tuja\view\Field;
use tuja\util\DateUtils;

AdminUtils::printTopMenu( $competition );
?>

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

    <button class="button button-primary" type="submit" name="tuja_action" value="form_update">Spara inställningar</button>

    <p><strong>Frågor</strong></p>
    <?php
    $questions = $db_question->get_all_in_form($this->form->id);

	if(empty($questions)) {
		echo '<p><i>Inga frågor än. Klicka på "Ny fråga" för att lägga till en.';
		echo '<div class="clear"></div>';
	} else {
		ob_start();
		?>
		<button type="submit" name="tuja_action" class="button button-primary" value="questions_update">Spara frågor</button>
		<button type="submit" name="tuja_action" class="button" value="question_create">Ny fråga</button>
		<?php
		
		foreach ($questions as $question) {
			echo '<div class="tuja-admin-question">';
			echo '<div class="tuja-admin-question-properties">';
	
			$json = json_encode($question, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			$rows = substr_count($json, "\n") + 1;
			$field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $question->id;
			printf('<textarea name="%s" rows="%d">%s</textarea>', $field_name, $rows, $json);

			echo '</div>';
			printf('<button type="submit" class="button" name="tuja_action" value="%s%d">Ta bort</button>', self::ACTION_NAME_DELETE_PREFIX, $question->question_group_id);
			echo '</div>';
		}
		
		ob_end_flush();
	}
    ?>
    <button type="submit" name="tuja_action" class="button button-primary" value="questions_update">Spara frågor</button>
    <button type="submit" name="tuja_action" class="button" value="question_create">Ny fråga</button>
</form>
