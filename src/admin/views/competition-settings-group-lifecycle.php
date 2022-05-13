<?php
namespace tuja\admin;

use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );
?>

<h3>Livscykel för grupp</h3>

<?php printf( '<p><a id="tuja_competition_settings_group_lifecycle_back" href="%s">« Tillbaka till övriga inställningar</a></p>', $back_url ); ?>

<form method="post" class="tuja">

	<h4>Livscykel för grupp</h4>

	<div 
		class="tuja-stategraph" 
		data-definition="<?php echo htmlentities( $group_status_transitions_definitions ); ?>"
		data-width-factor="0.60"></div>

	<div>
		<label for="tuja_competition_settings_initial_group_status">
			Status för nya grupper:
		</label><br>
		<?php echo AdminUtils::get_initial_group_status_selector( $competition->initial_group_status, 'tuja_competition_settings_initial_group_status' ); ?>
	</div>

	<button class="button button-primary"
			type="submit"
			name="tuja_competition_settings_action"
			id="tuja_save_competition_settings_button"
			value="save">
		Spara
	</button>
</form>
