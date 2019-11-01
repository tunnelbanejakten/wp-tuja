<?php
namespace tuja\admin;

use tuja\util\DateUtils;
use tuja\util\ReflectionUtils;

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

    <p><strong>Frågegrupper</strong></p>
    <?php if(empty($question_groups)): ?>
		<p><i>Inga frågor än. Klicka på "Ny fråga" för att lägga till en.<i></p>
		<div class="clear"></div>
	<?php else: ?>
		<?php ob_start() ?>

		<button type="submit" name="tuja_action" class="button button-primary" value="question_groups_update">Spara frågegrupper</button>
		<button type="submit" name="tuja_action" class="button" value="question_group_create">Ny frågegrupp</button>
		
		<?php foreach ($question_groups as $question_group): ?>
			<div class="tuja-admin-question">
				<div class="tuja-admin-question-properties">
					<?php
					$json       = $question_group->get_editable_properties_json();
					$field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $question_group->id;

					$options_schema = $question_group->json_schema();

					printf( '<div class="tuja-admin-questiongroup-form" data-schema="%s" data-values="%s" data-field-id="%s"></div>', htmlentities( $options_schema ), htmlentities( $json ), htmlentities( $field_name ) );
					printf( '<input type="hidden" name="%s" id="%s" value="" />', $field_name, $field_name );
					?>
				</div>
				
				<button type="submit" class="button" name="tuja_action" onclick="return confirm('Är du säker?');" value="<?php echo self::ACTION_NAME_DELETE_PREFIX . $question_group->id; ?>">Ta bort</button>
				<a href="<?php echo admin_url('admin.php?page=tuja&tuja_view=FormQuestions&tuja_question_group=' . $question_group->id); ?>" class="button button-primary">Visa frågor</a>
			</div>
		<?php endforeach; ?>
		
		<?php ob_end_flush(); ?>
	<?php endif; ?>

    <button type="submit" name="tuja_action" class="button button-primary" value="question_groups_update">Spara frågegrupper</button>
    <button type="submit" name="tuja_action" class="button" value="question_group_create">Ny frågegrupp</button>
</form>
