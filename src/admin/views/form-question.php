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

<form method="post">
	<?php if($quetion->id): ?>
		<h2>Redigera fråga <?php echo esc_html($question->name); ?></h2>
	<?php else: ?>
		<h2>Ny fråga</h2>
	<?php endif; ?>

	<div class="tuja-admin-question tuja-admin-question-<?php echo esc_attr($question_class_short); ?>">
		<div class="tuja-admin-question-properties">
			<?php
				printf(
					'<div class="tuja-admin-formgenerator-form" data-schema="%s" data-values="%s" data-field-id="%s" id="%s"></div>',
					htmlentities( $options_schema ),
					htmlentities( $json ),
					htmlentities( $field_name ),
					sprintf('tuja-admin-questiongroup-form-%s', $question->id)
				);
				printf( '<input type="hidden" name="%s" id="%s" value="" />', $field_name, $field_name );
			?>
		</div>
	</div>

	<?php
	if($question->id) {
		printf('<button type="submit" class="button button-primary" name="tuja_action" value="%s%d">Spara</button>', self::ACTION_NAME_UPDATE_PREFIX, $question->id);
		printf('<button type="submit" class="button" name="tuja_action" value="%s%d" onclick="return confirm(\'Är du säker?\');">Ta bort</button>', self::ACTION_NAME_DELETE_PREFIX, $question->id);
	} elseif (is_string($_GET['tuja_question'])) {
		printf('<button type="submit" class="button button-primary" name="tuja_action" value="%s">Skapa</button>', $_GET['tuja_question']);
	}
	?>
</form>
