<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

 <h3>Formulär <?= $this->form->name ?> - Grupp "<?= $this->question_group->text ?: $this->question_group->id; ?>"</h3>

<a href="<?php echo admin_url('admin.php?page=tuja&tuja_view=Form&tuja_competition=' . $competition->id . '&tuja_form=' . $this->question_group->form_id); ?>">« Tillbaka till grupp</a>

<form method="post">
    <p><strong>Frågor</strong></p>
    <?php
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
			printf('<button type="submit" class="button" name="tuja_action" value="%s%d" onclick="return confirm(\'Är du säker?\');">Ta bort</button>', self::ACTION_NAME_DELETE_PREFIX, $question->question_group_id);
			echo '</div>';
		}
		
		ob_end_flush();
	}
    ?>
    <button type="submit" name="tuja_action" class="button button-primary" value="questions_update">Spara frågor</button>
    <button type="submit" name="tuja_action" class="button" value="question_create">Ny fråga</button>
</form>
