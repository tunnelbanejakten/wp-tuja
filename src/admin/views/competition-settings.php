<?php
namespace tuja\admin;

use tuja\data\model\GroupCategory;
use tuja\data\model\MessageTemplate;
use tuja\util\DateUtils;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" class="tuja">
    <div class="nav-tab-wrapper">
        <a class="nav-tab nav-tab-active" data-tab-id="tuja-tab-dates">Datum och tider</a>
        <a class="nav-tab" data-tab-id="tuja-tab-messagetemplates">Meddelandemallar</a>
        <a class="nav-tab" data-tab-id="tuja-tab-groups">Grupper</a>
    </div>

    <div class="tuja-tab" id="tuja-tab-dates">
        <div class="tuja-admin-question">
            <div>När är tävlingen?</div>
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Start</label>
                    <input type="datetime-local" name="tuja_event_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Slut</label>
                    <input type="datetime-local" name="tuja_event_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_end ) ?>"/>
                </div>
            </div>
        </div>
        <div class="tuja-admin-question">
            <div>När kan lag anmälas?</div>
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Tidigast</label>
                    <input type="datetime-local" name="tuja_create_group_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->create_group_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Senast</label>
                    <input type="datetime-local" name="tuja_create_group_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->create_group_end ) ?>"/>
                </div>
            </div>
            <div>
                <small>Reglerna för olika grupptyper kan minska detta tidsintervall.</small>
            </div>
        </div>
        <div class="tuja-admin-question">
            <div>När kan anmälningar ändras?</div>
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Tidigast</label>
                    <input type="datetime-local" name="tuja_edit_group_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->edit_group_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Senast</label>
                    <input type="datetime-local" name="tuja_edit_group_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->edit_group_end ) ?>"/>
                </div>
            </div>
            <div>
                <small>Reglerna för olika grupptyper kan minska detta tidsintervall.</small>
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

        <h4>Nya grupper</h4>

        <div>
            <label for="tuja_competition_settings_initial_group_status">
                Status för nya grupper:
            </label><br>
            <select name="tuja_competition_settings_initial_group_status"
                    id="tuja_competition_settings_initial_group_status">
			    <?= join( '', array_map( function ( $status ) use ( $competition ) {
				    return sprintf( '<option value="%s" %s>%s</option>',
					    $status,
					    $status == $competition->initial_group_status ? 'selected="selected"' : '',
					    $status
				    );
			    }, \tuja\data\model\Competition::allowed_initial_statuses() ) ) ?>
            </select>
        </div>

        <h4>Grupptyper</h4>

        <p>
            Grupptyper gör det möjligt att hantera flera tävlingsklasser och att skilja på tävlande och funktionärer.
        </p>
        <div class="tuja-groupcategory-existing">
	        <?= join( array_map( function ( GroupCategory $category ) {
		        return $this->print_group_category_form( $category );
	        }, $category_dao->get_all_in_competition( $competition->id ) ) ) ?>
        </div>
        <div class="tuja-groupcategory-template">
	        <?= $this->print_group_category_form( new GroupCategory() ) ?>
        </div>
        <button class="button tuja-add-groupcategory" type="button">
            Ny
        </button>

        <p><strong>Grupper och grupptyper:</strong></p>

        <p>Grypptyper ska inte förväxlas med grupper. En tävling kan ha flera grupper och varje person är med i en
            grupp. Grupptyper är ett sätt att klassificera grupperna utifrån deras roll i tävlingen.</p>

        <p><strong>Regler för Tävlande eller Funktionär:</strong></p>

        <p>Detta gäller för grupper som har en grupptyp som är Funktionär:</p>
        <ul>
            <li>Personer i dessa grupper får rapportera in poäng för vilken grupp som helst.</li>
            <li>Personer i dessa grupper får besvara formulär åt vilken grupp som helst.</li>
            <li>Exempel på funktionärsgrupptyper: Kontrollanter, Tävlingsledning.</li>
        </ul>
        <p>Detta gäller för grupper som har en grupptyp som är Tävlande:</p>
        <ul>
            <li>Personer i dessa grupper får inte rapportera in poäng.</li>
            <li>Personer i dessa grupper får enbart besvara formulär för egen räkning.</li>
            <li>Exempel på tävlande grupptyper: Nybörjare, Veteraner, Super-experter.</li>
        </ul>

        <p><strong>Ytterligare regler som kan appliceras:</strong></p>

        <table>
            <thead>
            <tr>
                <th rowspan="2" valign="top">Uppsättning</th>
                <th rowspan="2" valign="top">Vuxen medföljare</th>
                <th colspan="3" valign="top">Sista dag för att</th>
            </tr>
            <tr>
                <td>Anmäla</td>
                <td>Ändra</td>
                <td>Avanmäla</td>
            </tr>
            </thead>
            <tbody>
		    <?php
		    foreach ( self::RULE_SETS as $class_name => $label ) {
			    if ( ! empty( $class_name ) ) {
				    $rules = new $class_name;
				    printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					    $label,
					    $rules->is_adult_supervisor_required(  )? 'Ja, krav' : '-',
					    $rules->get_create_registration_period( $competition )->end->format( 'd M' ),
					    $rules->get_update_registration_period( $competition )->end->format( 'd M' ),
					    $rules->get_delete_registration_period( $competition )->end->format( 'd M' ) );
			    }
		    }
		    ?>
            </tbody>
        </table>
    </div>

    <button class="button button-primary"
            type="submit"
            name="tuja_competition_settings_action"
            value="save">
        Spara
    </button>
</form>
