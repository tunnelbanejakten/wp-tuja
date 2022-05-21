<?php
namespace tuja\admin;

use tuja\util\DateUtils;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<form method="post" class="tuja">
    <div>
        <div class="tuja-admin-question">
			<label for="tuja_event_name">Vad heter tävlingen?</label>
			<input type="text" name="tuja_event_name" id="tuja_event_name" value="<?= $competition->name ?>"/>
        </div>
	</div>
	<div>
        <div class="tuja-admin-question">
            <div>När är tävlingen?</div>
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="tuja_event_start">Start</label>
                    <input type="datetime-local" name="tuja_event_start" id="tuja_event_start"
                           placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="tuja_event_end">Slut</label>
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
