<?php
namespace tuja\admin;

use tuja\data\model\GroupCategory;
use tuja\data\model\MessageTemplate;
use tuja\util\DateUtils;

AdminUtils::printTopMenu( $competition );
?>

<form method="post">
    <div class="nav-tab-wrapper">
        <a class="nav-tab nav-tab-active" data-tab-id="tuja-tab-dates">Datum och tider</a>
        <a class="nav-tab" data-tab-id="tuja-tab-messagetemplates">Meddelandemallar</a>
        <a class="nav-tab" data-tab-id="tuja-tab-sendouts">Automatiska utskick</a>
        <a class="nav-tab" data-tab-id="tuja-tab-groupcategories">Typer av grupper</a>
    </div>

    <div class="tuja-tab" id="tuja-tab-dates">
        <div class="tuja-admin-question">
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Tävlingen startar</label>
                    <input type="datetime-local" name="tuja_event_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Tävlingen slutar</label>
                    <input type="datetime-local" name="tuja_event_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->event_end ) ?>"/>
                </div>
            </div>
        </div>
        <div class="tuja-admin-question">
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Nya anmälningar kan göras fr.o.m.</label>
                    <input type="datetime-local" name="tuja_create_group_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->create_group_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Nya anmälningar kan göras t.o.m.</label>
                    <input type="datetime-local" name="tuja_create_group_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->create_group_end ) ?>"/>
                </div>
            </div>
        </div>
        <div class="tuja-admin-question">
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Anmälningar kan ändras fr.o.m.</label>
                    <input type="datetime-local" name="tuja_edit_group_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->edit_group_start ) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Anmälningar kan ändras t.o.m.</label>
                    <input type="datetime-local" name="tuja_edit_group_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value( $competition->edit_group_end ) ?>"/>
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
            Ny
        </button>
    </div>
    <div class="tuja-tab" id="tuja-tab-sendouts">
        <div>
            <label for="tuja_competition_settings_message_template_id_new_group_admin">
                Ny grupp anmäld (e-post till tävlingsledningen):
            </label><br>
            <select name="tuja_competition_settings_message_template_id_new_group_admin"
                    id="tuja_competition_settings_message_template_id_new_group_admin">
                <option value="">Ej valt - utskick inaktiverat</option>
	            <?= join( '', array_map( function ( $template ) use ( $competition ) {
		            return sprintf( '<option value="%s" %s>%s</option>',
			            $template->id,
			            $template->id == $competition->message_template_id_new_group_admin ? 'selected="selected"' : '',
			            $template->name
		            );
	            }, $message_templates ) ) ?>
            </select>
        </div>
        <div>
            <label for="tuja_competition_settings_message_template_id_new_group_reporter">
                Ny grupp anmäld (e-post till den som anmäler):
            </label><br>
            <select name="tuja_competition_settings_message_template_id_new_group_reporter"
                    id="tuja_competition_settings_message_template_id_new_group_reporter">
                <option value="">Ej valt - utskick inaktiverat</option>
	            <?= join( '', array_map( function ( $template ) use ( $competition ) {
		            return sprintf( '<option value="%s" %s>%s</option>',
			            $template->id,
			            $template->id == $competition->message_template_id_new_group_reporter ? 'selected="selected"' : '',
			            $template->name
		            );
	            }, $message_templates ) ) ?>
            </select>
        </div>
        <div>
            <label for="tuja_competition_settings_message_template_id_new_crew_member">
                Ny person anmäler sig själv till funktionärslag (e-post):
            </label><br>
            <select name="tuja_competition_settings_message_template_id_new_crew_member"
                    id="tuja_competition_settings_message_template_id_new_crew_member">
                <option value="">Ej valt - utskick inaktiverat</option>
	            <?= join( '', array_map( function ( $template ) use ( $competition ) {
		            return sprintf( '<option value="%s" %s>%s</option>',
			            $template->id,
			            $template->id == $competition->message_template_id_new_crew_member ? 'selected="selected"' : '',
			            $template->name
		            );
	            }, $message_templates ) ) ?>
            </select>
        </div>
        <div>
            <label for="tuja_competition_settings_message_template_id_new_noncrew_member">
                Ny person anmäler sig själv till deltagarlag (e-post):
            </label><br>
            <select name="tuja_competition_settings_message_template_id_new_noncrew_member"
                    id="tuja_competition_settings_message_template_id_new_noncrew_member">
                <option value="">Ej valt - utskick inaktiverat</option>
	            <?= join( '', array_map( function ( $template ) use ( $competition ) {
		            return sprintf( '<option value="%s" %s>%s</option>',
			            $template->id,
			            $template->id == $competition->message_template_id_new_noncrew_member ? 'selected="selected"' : '',
			            $template->name
		            );
	            }, $message_templates ) ) ?>
            </select>
        </div>
    </div>
    <div class="tuja-tab" id="tuja-tab-groupcategories">
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
        <p>Grypptyper ska inte förväxlas med grupper. En tävling kan ha flera grupper och varje person är med i en
            grupp. Grupptyper är ett sätt att klassificera grupperna utifrån deras roll i tävlingen.</p>
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
    </div>

    <button class="button button-primary"
            type="submit"
            name="tuja_competition_settings_action"
            value="save">
        Spara
    </button>
</form>
