<?php
namespace tuja\admin;

use tuja\util\DateUtils;

$this->print_root_menu();
$this->print_menu();
?>

<div class="tuja-buttons">
	<a href="<?php echo $preview_url; ?>"
		title="Förhandsgranskning"
		class="thickbox button"
		target="_blank">
		Förhandsgranskning
	</a>
	<em>Glöm inte att spara innan du förhandsgranskar.</em>
</div>
<hr class="tuja-hr">

<form method="post">
    <h2>Frågegrupper</h2>
    <?php if(empty($question_groups)): ?>
		<p><i>Inga frågor än. Klicka på "Ny fråga" för att lägga till en.<i></p>
		<div class="clear"></div>
	<?php else: ?>
		<table class="tuja-admin-table widefat">
			<thead>
				<tr>
					<th scope="col">Nr</th>
					<th scope="col">Namn</th>
					<th scope="col">Max poäng</th>
					<th scope="col">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($question_groups as $question_group): ?>
					<tr>
						<td><?php echo $question_group->id; ?></td>
						<td><?php echo $question_group->text; ?></td>
						<td><?php echo $question_group->score_max ?: 0; ?></td>
						<td>
							<?php
								$link = add_query_arg( array(
									'tuja_competition'    => $competition->id,
									'tuja_view'           => 'FormQuestionGroup',
									'tuja_question_group' => $question_group->id
								) );

								printf('<a href="%s" class="button">Redigera</a>', $link);
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

    <button type="submit" name="tuja_action" class="button button-primary" value="question_group_create">Ny frågegrupp</button>

	<hr class="tuja-hr">

	<h2>Inställningar</h2>
    <div class="tuja-admin-question">
        <div class="tuja-admin-question-properties">
            <div class="tuja-admin-question-property tuja-admin-question-short">
                <label for="">Svar accepteras fr.o.m. <?php echo AdminUtils::tooltip( 'Om inget anges så används tävlingens starttid istället.'); ?></label>
                <input type="datetime-local" name="tuja-submit-response-start" id="tuja-submit-response-start" placeholder="yyyy-mm-dd hh:mm"
                       value="<?= DateUtils::to_date_local_value($this->form->submit_response_start) ?>"/>
            </div>
            <div class="tuja-admin-question-property tuja-admin-question-short">
                <label for="">Svar accepteras t.o.m. <?php echo AdminUtils::tooltip( 'Om inget anges så används tävlingens sluttid istället.'); ?></label>
                <input type="datetime-local" name="tuja-submit-response-end" id="tuja-submit-response-end" placeholder="yyyy-mm-dd hh:mm"
                       value="<?= DateUtils::to_date_local_value($this->form->submit_response_end) ?>"/>
            </div>
        </div>
    </div>

    <button class="button button-primary" type="submit" name="tuja_action" value="form_update">Spara inställningar</button>
</form>