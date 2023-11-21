<?php
namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<?php if(isset($preview_url)): ?>
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
<?php endif; ?>

<?php if($question->id): ?>
	<h2>Redigera fråga <?php echo esc_html($question->name); ?></h2>
<?php else: ?>
	<h2>Ny fråga</h2>
<?php endif; ?>

<form method="post" class="tuja-admin-form tuja-admin-question">
	<div class="row">
		<div class="form-control">
			<?php wp_editor($this->question->text, 'text'); ?>
		</div>
	</div>

	<h3>Inställningar</h3>

	<div class="row">
		<div class="form-control">
			<label for="text_hint">Hjälptext</label>
			<input type="text" name="text_hint" id="text_hint" value="<?php echo esc_html($this->question->text_hint); ?>">
		</div>
	</div>

	<div class="row">
		<div class="form-control short">
			<label for="score_max">Maximal poäng</label>
			<input type="number" name="score_max" id="score_max" min="0" value="<?php echo (int)$this->question->score_max; ?>">
		</div>

		<div class="form-control short">
			<label for="sort_order">Position</label>
			<input type="number" name="sort_order" id="sort_order" value="<?php echo (int)$this->question->sort_order; ?>">
		</div>
	</div>

	<?php $question->question_type_props_html(); ?>

	<h3>Tidsbegränsad uppgift</h3>

	<div class="row">
		<div class="form-control short">
			<label for="limit_time">Maximal tid (sekunder, 0 = ingen begränsning)</label>
			<input type="number" name="limit_time" id="limit_time" min="0" value="<?php echo (int)$this->question->limit_time; ?>">
		</div>

		<div class="form-control short">
			<label for="text_preparation">Text innan uppgiften visas</label>
			<input type="text" name="text_preparation" id="text_preparation" value="<?php echo esc_html($this->question->text_preparation); ?>">
		</div>
	</div>

	<div class="row">
		<?php
		if($question->id) {
			printf('<button type="submit" class="button button-primary" name="tuja_action" value="%s">Spara</button>', self::ACTION_NAME_UPDATE);
			printf('<button type="submit" class="button" name="tuja_action" value="%s" onclick="return confirm(\'Är du säker?\');">Ta bort</button>', self::ACTION_NAME_DELETE);
		} elseif (is_string($_GET['tuja_question'])) {
			printf('<button type="submit" class="button button-primary" name="tuja_action" value="%s">Skapa</button>', $_GET['tuja_question']);
		}
		?>
	</div>
</form>
