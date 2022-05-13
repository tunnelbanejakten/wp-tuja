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
	$strings_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsStrings'
	) );
	printf( '<p><a href="%s">Texter</a></p>', $strings_url );
	$app_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsApp'
	) );
	printf( '<p><a href="%s">Appen</a></p>', $app_url );
	$payment_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsFees'
	) );
	printf( '<p><a href="%s">Avgifter</a></p>', $payment_url );
	$lifecycle_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsGroupLifecycle'
	) );
	printf( '<p><a href="%s">Livscykel för grupper</a></p>', $lifecycle_url );
	$message_templates_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsMessageTemplates'
	) );
	printf( '<p><a href="%s">Meddelandemallar</a></p>', $message_templates_url );
?>

<form method="post" class="tuja">
    <div>
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
    <button class="button button-primary"
            type="submit"
            name="tuja_competition_settings_action"
            id="tuja_save_competition_settings_button"
            value="save">
        Spara
    </button>
</form>
