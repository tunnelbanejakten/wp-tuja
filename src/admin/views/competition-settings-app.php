<?php
namespace tuja\admin;

use tuja\util\Strings;
use tuja\util\TemplateEditor;

$this->print_root_menu();
$this->print_leaves_menu();
?>

<form method="post" class="tuja">

    <?= $this->print_app_config_form( $competition ); ?>
	
    <button class="button button-primary"
			type="submit"
			name="tuja_competition_settings_action"
			id="tuja_save_competition_settings_button"
			value="save">
		Spara
	</button>
</form>
