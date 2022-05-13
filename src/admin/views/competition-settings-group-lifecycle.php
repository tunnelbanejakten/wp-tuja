<?php
namespace tuja\admin;

use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<form method="post" class="tuja">
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
