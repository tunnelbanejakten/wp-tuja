<?php
namespace tuja\admin;

use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );
?>

<h3>Appen</h3>

<?php printf( '<p><a id="tuja_competition_settings_app_back" href="%s">« Tillbaka till övriga inställningar</a></p>', $back_url ); ?>

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
