<?php
namespace tuja\admin;

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

<h2>Frågor i gruppen "<?php echo esc_html($this->question_group->text); ?>"</h2>
<?php if(empty($questions)): ?>
	<p>Inga frågor just nu.</p>
<?php else: ?>
	<table class="tuja-admin-table widefat">
		<thead>
			<tr>
				<th scope="col">Nr</th>
				<th scope="col">Fråga</th>
				<th scope="col">Typ</th>
				<th scope="col">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($questions as $i => $question): ?>
				<tr>
					<td><?php echo $question->name ?: $i; ?></td>
					<td><?php echo $question->text; ?></td>
					<td><?php echo array_pop(explode('\\', get_class($question))); ?></td>
					<td>
						<?php
							printf(
								'<a href="%s" class="button">Redigera</a>', 
								add_query_arg([
									'tuja_question' => $question->id,
									'tuja_view'     => 'FormQuestion',
								])
							);
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<form method="post">
	<button type="submit" name="tuja_action" class="button" value="<?php echo self::ACTION_NAME_CREATE_TEXT ?>">Ny textfråga</button>
	<button type="submit" name="tuja_action" class="button" value="<?php echo self::ACTION_NAME_CREATE_NUMBER ?>">Ny nummerfråga</button>
	<button type="submit" name="tuja_action" class="button" value="<?php echo self::ACTION_NAME_CREATE_IMAGES ?>">Ny bildfråga</button>
	<button type="submit" name="tuja_action" class="button" value="<?php echo self::ACTION_NAME_CREATE_CHOICES ?>">Ny flervalsfråga</button>
</form>

<hr class="tuja-hr">

<h2>Redigera frågegrupp</h2>
<form method="post" class="tuja-admin-form tuja-admin-question">
	<div class="row">
		<div class="form-control short">
			<label for="score_max">Maximal poäng</label>
			<input type="number" name="score_max" id="score_max" value="<?php echo (int)$this->question_group->score_max; ?>">
		</div>
	</div>

	<div class="row">
		<label>Metod för att välja frågor att visa</label>
	</div>

	<div class="row">
		<div class="form-control radio">
			<input type="radio" name="question_filter" id="question_filter__all" value="all"<?php checked($this->question_filter, 'all'); ?>>
			<label for="question_filter__all">Alla lag ser alla frågor i frågegruppen.</label>
		</div>
	</div>

	<div class="row">
		<div class="form-control radio">
			<input type="radio" name="question_filter" id="question_filter__one" value="one"<?php checked($this->question_filter, 'one'); ?>>
			<label for="question_filter__one">Varje lag ser bara en av frågorna i frågegruppen. Laget tilldelas sin fråga slumpmässigt.</label>
		</div>
	</div>

	<div class="row">
		<div class="form-control short">
			<label for="sort_order">Position</label>
			<input type="number" name="sort_order" id="sort_order" value="<?php echo (int)$this->question_group->sort_order; ?>">
		</div>
	</div>

	<div class="row">
		<div class="form-control">
			<label for="text">Rubrik</label>
			<input type="text" name="text" id="text" value="<?php echo esc_html($this->question_group->text); ?>">
		</div>
	</div>

	<div class="row">
		<div class="form-control">
			<label for="text_description">Beskrivning</label>
			<?php wp_editor($this->question_group->text_description, 'text_description'); ?>
		</div>
	</div>
	<?php

	printf( '<input type="hidden" name="%s" id="%s" value="" />', $field_name, $field_name );
	?>

	<div class="row">
		<button type="submit" name="tuja_action" class="button button-primary" value="<?php echo self::ACTION_UPDATE; ?>">Spara frågegrupp</button>
		<button type="submit" class="button" name="tuja_action" onclick="return confirm('Är du säker?');" value="<?php echo self::ACTION_NAME_DELETE_PREFIX . $this->question_group->id; ?>">Ta bort</button>
	</div>
</form>