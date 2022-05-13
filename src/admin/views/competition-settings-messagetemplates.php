<?php
namespace tuja\admin;

use tuja\data\model\MessageTemplate;
use tuja\util\DateUtils;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );
?>

<h3>Meddelandemallar</h3>

<?php printf( '<p><a id="tuja_competition_settings_message_templates_back" href="%s">« Tillbaka till övriga inställningar</a></p>', $back_url ); ?>

<form method="post" class="tuja">
   <div>
        <div class="tuja-messagetemplate-existing">
			<?= join( array_map( function ( $message_template ) {
				return $this->print_message_template_form( $message_template );
			}, $message_template_dao->get_all_in_competition( $competition->id ) ) ) ?>
        </div>
        <div class="tuja-messagetemplate-template">
			<?= $this->print_message_template_form( new MessageTemplate() ) ?>
        </div>
        <button class="button tuja-add-messagetemplate" type="button">
            Ny tom mall
        </button>
        <br>
		<?= $default_message_templates ?>
    </div>
    <button class="button button-primary"
            type="submit"
            name="tuja_competition_settings_action"
            id="tuja_save_competition_settings_button"
            value="save">
        Spara
    </button>
</form>
