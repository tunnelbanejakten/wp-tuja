<?php
namespace tuja\admin;

use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );
?>

<h3>Texter</h3>

<?php printf( '<p><a id="tuja_competition_settings_strings_back" href="%s">« Tillbaka till övriga inställningar</a></p>', $back_url ); ?>

<form method="post" class="tuja">
    <table style="width: 100%" class="tuja-table">
        <tbody>
        <?php
        $final_list  = Strings::get_list();
        $last_header = null;
        foreach ( $final_list as $key => $value ) {
            list ( $header ) = explode( '.', $key );
            if ( $last_header != $header ) {
                printf(
                    '<tr><td colspan="2"><h3>%s</h3></td></tr>',
                    $header
                );
            }
            if ( Strings::is_markdown( $key ) ) {
                printf(
                    '<tr><td style="width: auto; vertical-align: top">%s</td><td style="width: 100%%">%s</td></tr>',
                    $key,
                    TemplateEditor::render( CompetitionSettingsStrings::string_field_name( $key ), $value, Strings::get_sample_template_parameters( $key ) )
                );

            } else {
                printf(
                    '<tr><td style="width: auto">%s</td><td style="width: 100%%"><input type="text" name="%s" style="width: 100%%" value="%s"></td></tr>',
                    $key,
                    CompetitionSettingsStrings::string_field_name( $key ),
                    $value
                );
            }
            $last_header = $header;
        }
        ?>
        </tbody>
    </table>

	<button class="button button-primary"
			type="submit"
			name="tuja_competition_settings_action"
			id="tuja_save_competition_settings_button"
			value="save">
		Spara
	</button>
</form>
