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
	<button type="submit" name="tuja_action" class="button button-primary" value="question_groups__add_question">Ny fråga</button>
</form>

<hr class="tuja-hr">

<h2>Redigera frågegrupp</h2>
<form method="post">
	<div class="tuja-admin-question">
		<div class="tuja-admin-question-properties">
			<?php
			$json       = $this->question_group->get_editable_properties_json();
			$field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $this->question_group->id;

			$options_schema = $this->question_group->json_schema();

			printf( '<div class="tuja-admin-formgenerator-form" data-schema="%s" data-values="%s" data-field-id="%s"></div>', htmlentities( $options_schema ), htmlentities( $json ), htmlentities( $field_name ) );
			printf( '<input type="hidden" name="%s" id="%s" value="" />', $field_name, $field_name );
			?>
		</div>
	</div>

	<button type="submit" name="tuja_action" class="button button-primary" value="question_groups_update">Spara frågegrupp</button>
	<button type="submit" class="button" name="tuja_action" onclick="return confirm('Är du säker?');" value="<?php echo self::ACTION_NAME_DELETE_PREFIX . $this->question_group->id; ?>">Ta bort</button>
</form>