<?php

use admin\AdminUtils;
use data\model\ValidationException;
use data\store\CompetitionDao;
use data\store\GroupCategoryDao;
use data\store\MessageTemplateDao;
use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\model\MessageTemplate;
use util\DateUtils;

const FIELD_SEPARATOR = '__';

$competition_dao = new CompetitionDao($wpdb);
$competition = $competition_dao->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}

function tuja_list_item_field_name($list_name, $id, $field)
{
    return join(FIELD_SEPARATOR, array($list_name, $id, $field));
}

function tuja_submitted_list_item_ids($list_name): array
{
    $prefix = $list_name . FIELD_SEPARATOR;
    // $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
    $person_prop_field_names = array_filter(array_keys($_POST), function ($key) use ($prefix) {
        return substr($key, 0, strlen($prefix)) === $prefix;
    });

    // $all_ids will include duplicates (one for each of the name, email and phone fields).
    // $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
    $all_ids = array_map(function ($key) {
        list(, $id) = explode(FIELD_SEPARATOR, $key);
        return $id;
    }, $person_prop_field_names);
    return array_filter(array_unique($all_ids) /* No callback to outer array_filter means that empty strings will be skipped.*/);
}

function tuja_print_message_template_form(MessageTemplate $message_template)
{
    $pattern = <<<PATTERN
        <div class="tuja-messagetemplate-form">
            <input type="text" placeholder="Mallens namn" size="50" name="%s" value="%s"><br>
            <input type="text" placeholder="Ämnesrad för e-post" size="50" name="%s" value="%s"><br>
            <textarea id="" cols="80" rows="10" placeholder="Meddelande för e-post/SMS" name="%s">%s</textarea><br>
            <button class="button tuja-delete-messagetemplate" type="button">
                Ta bort
            </button>
        </div>
PATTERN;
    return sprintf($pattern,
        tuja_list_item_field_name('messagetemplate', $message_template->id, 'name'),
        $message_template->name,
        tuja_list_item_field_name('messagetemplate', $message_template->id, 'subject'),
        $message_template->subject,
        tuja_list_item_field_name('messagetemplate', $message_template->id, 'body'),
        $message_template->body);
}

function tuja_print_group_category_form(GroupCategory $category)
{
    $id1 = uniqid();
    $id2 = uniqid();
    $pattern = <<<PATTERN
        <div class="tuja-groupcategory-form">
            <input type="text" placeholder="Mallens namn" size="50" name="%s" value="%s">
            <input type="radio" name="%s" id="%s" value="true" %s><label for="%s">Funktionär</label>
            <input type="radio" name="%s" id="%s" value="false" %s><label for="%s">Tävlande</label>
            <button class="button tuja-delete-groupcategory" type="button">
                Ta bort
            </button>
        </div>
PATTERN;
    return sprintf($pattern,
        tuja_list_item_field_name('groupcategory', $category->id, 'name'),
        $category->name,
        tuja_list_item_field_name('groupcategory', $category->id, 'iscrew'),
        $id1,
        $category->is_crew == true ? 'checked="checked"' : '',
        $id1,
        tuja_list_item_field_name('groupcategory', $category->id, 'iscrew'),
        $id2,
        $category->is_crew != true ? 'checked="checked"' : '',
        $id2);
}

$message_template_dao = new MessageTemplateDao($wpdb);

$competition_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'competition'
));

$category_dao = new GroupCategoryDao($wpdb);

function tuja_competition_settings_save_message_templates(Competition $competition)
{
    global $wpdb;
    $message_template_dao = new MessageTemplateDao($wpdb);

    $message_templates = $message_template_dao->get_all_in_competition($competition->id);

    $preexisting_ids = array_map(function ($template) {
        return $template->id;
    }, $message_templates);

    $submitted_ids = tuja_submitted_list_item_ids('messagetemplate');

    $updated_ids = array_intersect($preexisting_ids, $submitted_ids);
    $deleted_ids = array_diff($preexisting_ids, $submitted_ids);
    $created_ids = array_diff($submitted_ids, $preexisting_ids);

    $message_template_map = array_combine(array_map(function ($message_template) {
        return $message_template->id;
    }, $message_templates), $message_templates);

    foreach ($created_ids as $id) {
        try {
            $new_template = new MessageTemplate();
            $new_template->competition_id = $competition->id;
            $new_template->name = $_POST[tuja_list_item_field_name('messagetemplate', $id, 'name')];
            $new_template->subject = $_POST[tuja_list_item_field_name('messagetemplate', $id, 'subject')];
            $new_template->body = $_POST[tuja_list_item_field_name('messagetemplate', $id, 'body')];

            $new_template_id = $message_template_dao->create($new_template);
        } catch (ValidationException $e) {
        } catch (Exception $e) {
        }
    }

    foreach ($updated_ids as $id) {
        if (isset($message_template_map[$id])) {
            try {
                $message_template_map[$id]->name = $_POST[tuja_list_item_field_name('messagetemplate', $id, 'name')];
                $message_template_map[$id]->subject = $_POST[tuja_list_item_field_name('messagetemplate', $id, 'subject')];
                $message_template_map[$id]->body = $_POST[tuja_list_item_field_name('messagetemplate', $id, 'body')];

                $affected_rows = $message_template_dao->update($message_template_map[$id]);
            } catch (ValidationException $e) {
            } catch (Exception $e) {
            }
        }
    }

    foreach ($deleted_ids as $id) {
        if (isset($message_template_map[$id])) {
            $delete_successful = $message_template_dao->delete($id);
        }
    }
}

function tuja_competition_settings_save_group_categories(Competition $competition)
{
    global $wpdb;
    $category_dao = new GroupCategoryDao($wpdb);

    $categories = $category_dao->get_all_in_competition($competition->id);

    $preexisting_ids = array_map(function ($category) {
        return $category->id;
    }, $categories);

    $submitted_ids = tuja_submitted_list_item_ids('groupcategory');

    $updated_ids = array_intersect($preexisting_ids, $submitted_ids);
    $deleted_ids = array_diff($preexisting_ids, $submitted_ids);
    $created_ids = array_diff($submitted_ids, $preexisting_ids);

    $category_map = array_combine(array_map(function ($category) {
        return $category->id;
    }, $categories), $categories);

    foreach ($created_ids as $id) {
        try {
            $new_template = new GroupCategory();
            $new_template->competition_id = $competition->id;
            $new_template->name = $_POST[tuja_list_item_field_name('groupcategory', $id, 'name')];
            $new_template->is_crew = $_POST[tuja_list_item_field_name('groupcategory', $id, 'iscrew')] === 'true';

            $new_template_id = $category_dao->create($new_template);
        } catch (ValidationException $e) {
        } catch (Exception $e) {
        }
    }

    foreach ($updated_ids as $id) {
        if (isset($category_map[$id])) {
            try {
                $category_map[$id]->name = $_POST[tuja_list_item_field_name('groupcategory', $id, 'name')];
                $category_map[$id]->is_crew = $_POST[tuja_list_item_field_name('groupcategory', $id, 'iscrew')] === 'true';

                $affected_rows = $category_dao->update($category_map[$id]);
            } catch (ValidationException $e) {
            } catch (Exception $e) {
            }
        }
    }

    foreach ($deleted_ids as $id) {
        if (isset($category_map[$id])) {
            $delete_successful = $category_dao->delete($id);
        }
    }
}

function tuja_competition_settings_save_other(Competition $competition)
{
    global $wpdb;

    try {
        // TODO: Settle on one naming convention for form field names.
        $competition->create_group_start = DateUtils::from_date_local_value($_POST['tuja_create_group_start']);
        $competition->create_group_end = DateUtils::from_date_local_value($_POST['tuja_create_group_end']);
        $competition->edit_group_start = DateUtils::from_date_local_value($_POST['tuja_edit_group_start']);
        $competition->edit_group_end = DateUtils::from_date_local_value($_POST['tuja_edit_group_end']);

        $competition->message_template_id_new_group_admin = !empty($_POST['tuja_competition_settings_message_template_id_new_group_admin']) ? intval($_POST['tuja_competition_settings_message_template_id_new_group_admin']) : null;
        $competition->message_template_id_new_group_reporter = !empty($_POST['tuja_competition_settings_message_template_id_new_group_reporter']) ? intval($_POST['tuja_competition_settings_message_template_id_new_group_reporter']) : null;
        $competition->message_template_id_new_crew_member = !empty($_POST['tuja_competition_settings_message_template_id_new_crew_member']) ? intval($_POST['tuja_competition_settings_message_template_id_new_crew_member']) : null;
        $competition->message_template_id_new_noncrew_member = !empty($_POST['tuja_competition_settings_message_template_id_new_noncrew_member']) ? intval($_POST['tuja_competition_settings_message_template_id_new_noncrew_member']) : null;

        $dao = new CompetitionDao($wpdb);
        $dao->update($competition);
    } catch (Exception $e) {
        // TODO: Reuse this exception handling elsewhere?
        AdminUtils::printException($e);
    }
}

if ($_POST['tuja_competition_settings_action'] === 'save') {
    tuja_competition_settings_save_other($competition);
    tuja_competition_settings_save_group_categories($competition);
    tuja_competition_settings_save_message_templates($competition);
}

$message_templates = $message_template_dao->get_all_in_competition($competition->id);

?>
<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tunnelbanejakten</h1>
    <h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>

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
                    <label for="">Nya anmälningar kan göras fr.o.m.</label>
                    <input type="datetime-local" name="tuja_create_group_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value($competition->create_group_start) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Nya anmälningar kan göras t.o.m.</label>
                    <input type="datetime-local" name="tuja_create_group_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value($competition->create_group_end) ?>"/>
                </div>
            </div>
        </div>
        <div class="tuja-admin-question">
            <div class="tuja-admin-question-properties">
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Anmälningar kan ändras fr.o.m.</label>
                    <input type="datetime-local" name="tuja_edit_group_start" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value($competition->edit_group_start) ?>"/>
                </div>
                <div class="tuja-admin-question-property tuja-admin-question-short">
                    <label for="">Anmälningar kan ändras t.o.m.</label>
                    <input type="datetime-local" name="tuja_edit_group_end" placeholder="yyyy-mm-dd hh:mm"
                           value="<?= DateUtils::to_date_local_value($competition->edit_group_end) ?>"/>
                </div>
            </div>
        </div>
    </div>
    <div class="tuja-tab" id="tuja-tab-messagetemplates">
        <div class="tuja-messagetemplate-existing">
            <?= join(array_map(function ($message_template) {
                return tuja_print_message_template_form($message_template);
            }, $message_template_dao->get_all_in_competition($competition->id))) ?>
        </div>
        <div class="tuja-messagetemplate-template">
            <?= tuja_print_message_template_form(new MessageTemplate()) ?>
        </div>
        <button class="button tuja-add-messagetemplate" type="button">
            Ny
        </button>
    </div>
    <div class="tuja-tab" id="tuja-tab-sendouts">
        <div>
            <label for="tuja_competition_settings_message_template_id_new_group_admin">Ny grupp anmäld (e-post till
                tävlingsledningen):</label><br>
            <select name="tuja_competition_settings_message_template_id_new_group_admin"
                    id="tuja_competition_settings_message_template_id_new_group_admin">
                <option value="">Ej valt - utskick inaktiverat</option>
                <?= join('', array_map(function ($template) use ($competition) {
                    return sprintf('<option value="%s" %s>%s</option>',
                        $template->id,
                        $template->id == $competition->message_template_id_new_group_admin ? 'selected="selected"' : '',
                        $template->name
                    );
                }, $message_templates)) ?>
            </select>
        </div>
        <div>
            <label for="tuja_competition_settings_message_template_id_new_group_reporter">Ny grupp anmäld (e-post till
                den
                som anmäler):</label><br>
            <select name="tuja_competition_settings_message_template_id_new_group_reporter"
                    id="tuja_competition_settings_message_template_id_new_group_reporter">
                <option value="">Ej valt - utskick inaktiverat</option>
                <?= join('', array_map(function ($template) use ($competition) {
                    return sprintf('<option value="%s" %s>%s</option>',
                        $template->id,
                        $template->id == $competition->message_template_id_new_group_reporter ? 'selected="selected"' : '',
                        $template->name
                    );
                }, $message_templates)) ?>
            </select>
        </div>
        <div>
            <label for="tuja_competition_settings_message_template_id_new_crew_member">Ny person anmäler sig själv till
                funktionärslag (e-post):</label><br>
            <select name="tuja_competition_settings_message_template_id_new_crew_member"
                    id="tuja_competition_settings_message_template_id_new_crew_member">
                <option value="">Ej valt - utskick inaktiverat</option>
                <?= join('', array_map(function ($template) use ($competition) {
                    return sprintf('<option value="%s" %s>%s</option>',
                        $template->id,
                        $template->id == $competition->message_template_id_new_crew_member ? 'selected="selected"' : '',
                        $template->name
                    );
                }, $message_templates)) ?>
            </select>
        </div>
        <div>
            <label for="tuja_competition_settings_message_template_id_new_noncrew_member">Ny person anmäler sig själv
                till
                deltagarlag (e-post):</label><br>
            <select name="tuja_competition_settings_message_template_id_new_noncrew_member"
                    id="tuja_competition_settings_message_template_id_new_noncrew_member">
                <option value="">Ej valt - utskick inaktiverat</option>
                <?= join('', array_map(function ($template) use ($competition) {
                    return sprintf('<option value="%s" %s>%s</option>',
                        $template->id,
                        $template->id == $competition->message_template_id_new_noncrew_member ? 'selected="selected"' : '',
                        $template->name
                    );
                }, $message_templates)) ?>
            </select>
        </div>
    </div>
    <div class="tuja-tab" id="tuja-tab-groupcategories">
        <p>
            Grupptyper gör det möjligt att hantera flera tävlingsklasser och att skilja på tävlande och funktionärer.
        </p>
        <div class="tuja-groupcategory-existing">
            <?= join(array_map(function ($category) {
                return tuja_print_group_category_form($category);
            }, $category_dao->get_all_in_competition($competition->id))) ?>
        </div>
        <div class="tuja-groupcategory-template">
            <?= tuja_print_group_category_form(new GroupCategory()) ?>
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
