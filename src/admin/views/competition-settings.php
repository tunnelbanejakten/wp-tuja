<?php
namespace tuja\admin;

use tuja\data\model\MessageTemplate;
use tuja\util\DateUtils;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

AdminUtils::printTopMenu( $competition );
?>

<?php
	$group_categories_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsGroupCategories'
	) );
	printf( '<p><a href="%s">Gruppkategorier</a></p>', $group_categories_url );
?>

<form method="post" class="tuja">
    <div class="nav-tab-wrapper">
        <a class="nav-tab nav-tab-active" data-tab-id="tuja-tab-dates" id="tuja_tab_dates">Datum och tider</a>
        <a class="nav-tab" data-tab-id="tuja-tab-messagetemplates" id="tuja_tab_messagetemplates">Meddelandemallar</a>
        <a class="nav-tab" data-tab-id="tuja-tab-groups" id="tuja_tab_groups">Grupper</a>
        <a class="nav-tab" data-tab-id="tuja-tab-payment" id="tuja_tab_payment">Avgifter</a>
        <a class="nav-tab" data-tab-id="tuja-tab-appconfig" id="tuja_tab_appconfig">Appen</a>
        <a class="nav-tab" data-tab-id="tuja-tab-strings" id="tuja_tab_strings">Texter</a>
    </div>

    <div class="tuja-tab" id="tuja-tab-dates">
        <div class="tuja-admin-question">
            <div>När är tävlingen?</div>
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Start</label>
                    <input type="datetime-local" name="tuja_event_start" id="tuja_event_start"
                           placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Slut</label>
                    <input type="datetime-local" name="tuja_event_end" id="tuja_event_end"
                           placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_end ) ?>"/>
                </div>
            </div>
        </div>
    </div>
    <div class="tuja-tab" id="tuja-tab-messagetemplates">
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
    <div class="tuja-tab" id="tuja-tab-groups">

        <h4>Livscykel för grupp</h4>

        <div 
            class="tuja-stategraph" 
            data-definition="<?= htmlentities( $group_status_transitions_definitions ) ?>"
            data-width-factor="0.60"></div>

        <div>
            <label for="tuja_competition_settings_initial_group_status">
                Status för nya grupper:
            </label><br>
			<?= AdminUtils::get_initial_group_status_selector($competition->initial_group_status, 'tuja_competition_settings_initial_group_status') ?>
        </div>
    </div>
    <div class="tuja-tab" id="tuja-tab-payment">

        <h4>Anmälningsavgift</h4>

        <?= $this->print_group_fee_configuration_form($competition); ?>

        <?php
        $group_categories_settings_url = add_query_arg( array(
            'tuja_competition' => $competition->id,
            'tuja_view'        => 'CompetitionSettingsGroupCategories'
        ) );
        printf( '<p><em>Anmälningsavgift kan konfigureras per enskilt lag, per <a href="%s">gruppkategori</a> eller för tävlingen generellt. Den mest specifika inställningen används.</em></p>', 
            $group_categories_settings_url );
        ?>

        <h4>Betalningsmetoder</h4>

        <?= $this->print_payment_options_configuration_form($competition); ?>
        <input type="hidden" name="tuja_competition_settings_payment_options" id="tuja_competition_settings_payment_options"/>

    </div>
    <div class="tuja-tab" id="tuja-tab-appconfig">
        <?= $this->print_app_config_form( $competition ); ?>
    </div>
    <div class="tuja-tab" id="tuja-tab-strings">

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
						TemplateEditor::render( CompetitionSettings::string_field_name( $key ), $value, Strings::get_sample_template_parameters( $key ) )
					);

				} else {
					printf(
						'<tr><td style="width: auto">%s</td><td style="width: 100%%"><input type="text" name="%s" style="width: 100%%" value="%s"></td></tr>',
						$key,
						CompetitionSettings::string_field_name( $key ),
						$value
					);
				}
				$last_header = $header;
			}
			?>
            </tbody>
        </table>

    </div>

    <button class="button button-primary"
            type="submit"
            name="tuja_competition_settings_action"
            id="tuja_save_competition_settings_button"
            value="save">
        Spara
    </button>
</form>
