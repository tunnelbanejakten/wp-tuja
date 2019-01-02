<?php

use admin\AdminUtils;
use data\model\ValidationException;
use data\store\GroupCategoryDao;
use data\store\MessageTemplateDao;
use tuja\data\model\MessageTemplate;
use tuja\data\model\GroupCategory;
use util\DateUtils;

const GROUP_CATEGORY_FIELD_PREFIX = 'tuja_competition_setting_groupcategory';
const GROUP_CATEGORY_FIELD_SEPARATOR = '__';
const GROUP_CATEGORY_FIELD_NEW_ID = 'new';

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}

function tuja_group_category_field_name($id, $field)
{
    return join(GROUP_CATEGORY_FIELD_SEPARATOR, array(GROUP_CATEGORY_FIELD_PREFIX, $id, $field));
}

function tuja_print_message_template_form(MessageTemplate $message_template)
{
    $pattern = <<<PATTERN
        <div class="tuja-messagetemplate-form">
            <input type="text" placeholder="Mallens namn" size="50" name="tuja_messagetemplate__name__%d" value="%s"><br>
            <input type="text" placeholder="Ämnesrad för e-post" size="50" name="tuja_messagetemplate__subject__%d" value="%s"><br>
            <textarea id="" cols="80" rows="10" placeholder="Meddelande för e-post/SMS" name="tuja_messagetemplate__body__%d">%s</textarea><br>
            <button class="button tuja-delete-messagetemplate" type="button">
                Ta bort
            </button>
        </div>
PATTERN;
    return sprintf($pattern,
        $message_template->id,
        $message_template->name,
        $message_template->id,
        $message_template->subject,
        $message_template->id,
        $message_template->body);
}

$message_template_dao = new MessageTemplateDao($wpdb);

function tuja_submitted_messagetemplate_ids(): array
{
    // $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
    $person_prop_field_names = array_filter(array_keys($_POST), function ($key) {
        return substr($key, 0, strlen('tuja_messagetemplate__')) === 'tuja_messagetemplate__';
    });

    // $all_ids will include duplicates (one for each of the name, email and phone fields).
    // $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
    $all_ids = array_map(function ($key) {
        list(, , $id) = explode('__', $key);
        return $id;
    }, $person_prop_field_names);
    return array_filter(array_unique($all_ids) /* No callback to outer array_filter means that empty strings will be skipped.*/);
}

$competition_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'competition'
));

$category_dao = new GroupCategoryDao($wpdb);

if ($_POST['tuja_competition_settings_action'] === 'save') {

    try {

        // TODO: Settle on one naming convention for form field names.
        $competition->create_group_start = DateUtils::from_date_local_value($_POST['tuja_create_group_start']);
        $competition->create_group_end = DateUtils::from_date_local_value($_POST['tuja_create_group_end']);
        $competition->edit_group_start = DateUtils::from_date_local_value($_POST['tuja_edit_group_start']);
        $competition->edit_group_end = DateUtils::from_date_local_value($_POST['tuja_edit_group_end']);

        $competition->message_template_id_new_group_admin = !empty($_POST['tuja_competition_settings_message_template_id_new_group_admin']) ? intval($_POST['tuja_competition_settings_message_template_id_new_group_admin']): null;
        $competition->message_template_id_new_group_reporter = !empty($_POST['tuja_competition_settings_message_template_id_new_group_reporter']) ? intval($_POST['tuja_competition_settings_message_template_id_new_group_reporter']): null;
        $competition->message_template_id_new_crew_member = !empty($_POST['tuja_competition_settings_message_template_id_new_crew_member']) ? intval($_POST['tuja_competition_settings_message_template_id_new_crew_member']): null;
        $competition->message_template_id_new_noncrew_member = !empty($_POST['tuja_competition_settings_message_template_id_new_noncrew_member']) ? intval($_POST['tuja_competition_settings_message_template_id_new_noncrew_member']): null;

        $db_competition->update($competition);
    } catch (Exception $e) {
        // TODO: Reuse this exception handling elsewhere?
        AdminUtils::printException($e);
    }

    $group_categories_props = array_reduce(array_keys($_POST), function ($carry, $key) {
        if (substr($key, 0, strlen(GROUP_CATEGORY_FIELD_PREFIX)) == GROUP_CATEGORY_FIELD_PREFIX) {
            list (, $id, $field) = explode(GROUP_CATEGORY_FIELD_SEPARATOR, $key);
            $carry[$id][$field] = trim($_POST[$key]);
        }
        return $carry;
    }, array());

    if (count($group_categories_props) > 0) {
        // TODO: Delete (unused) group categories
        foreach ($group_categories_props as $key => $props) {
            if (!empty($props['name'])) {
                $group_category = new GroupCategory();
                $group_category->competition_id = $competition->id;
                $group_category->name = $props['name'];
                $group_category->is_crew = $props['iscrew'] == 'yes';
                try {
                    if ($key == GROUP_CATEGORY_FIELD_NEW_ID) {
                        $category_dao->create($group_category);
                    } elseif (is_int($key)) {
                        $group_category->id = $key;
                        $category_dao->update($group_category);
                    }
                } catch (ValidationException $e) {
                    printf($e->getMessage());
                }
            }
        }
    }

    $message_templates = $message_template_dao->get_all_in_competition($competition->id);

    $preexisting_ids = array_map(function ($person) {
        return $person->id;
    }, $message_templates);

    $submitted_ids = tuja_submitted_messagetemplate_ids();

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
            $new_template->name = $_POST['tuja_messagetemplate__name__' . $id];
            $new_template->subject = $_POST['tuja_messagetemplate__subject__' . $id];
            $new_template->body = $_POST['tuja_messagetemplate__body__' . $id];

            $new_template_id = $message_template_dao->create($new_template);
//                $this_success = $new_person_id !== false;
//                $overall_success = ($overall_success and $this_success);
        } catch (ValidationException $e) {
//                $validation_errors['__' . $e->getField()] = $e->getMessage();
//                $overall_success = false;
        } catch (Exception $e) {
//                $overall_success = false;
        }
    }

    foreach ($updated_ids as $id) {
        if (isset($message_template_map[$id])) {
            try {
                $message_template_map[$id]->name = $_POST['tuja_messagetemplate__name__' . $id];
                $message_template_map[$id]->subject = $_POST['tuja_messagetemplate__subject__' . $id];
                $message_template_map[$id]->body = $_POST['tuja_messagetemplate__body__' . $id];

                $affected_rows = $message_template_dao->update($message_template_map[$id]);
//                    $this_success = $affected_rows !== false;
//                    $overall_success = ($overall_success and $this_success);
            } catch (ValidationException $e) {
//                    $validation_errors[$id . '__' . $e->getField()] = $e->getMessage();
//                    $overall_success = false;
            } catch (Exception $e) {
//                    $overall_success = false;
            }
        }
    }

    foreach ($deleted_ids as $id) {
        if (isset($message_template_map[$id])) {
            $delete_successful = $message_template_dao->delete($id);
//                if (!$delete_successful) {
//                    $overall_success = false;
//                }
        }
    }

//        if (!$overall_success) {
//            $validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
//        }
//        return $validation_errors;
}

$message_templates = $message_template_dao->get_all_in_competition($competition->id);

?>
<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tunnelbanejakten</h1>
    <h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>

    <!-- TODO: Show settings sections in tabs -->

    <h3>Datum och tider</h3>

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

    <h3>Meddelandemallar</h3>

    <div class="tuja-messagetemplates-existing">
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

    <h3>Automatiska utskick</h3>

    <div>
        <label for="tuja_competition_settings_message_template_id_new_group_admin">Ny grupp anmäld (e-post till tävlingsledningen):</label><br>
        <select name="tuja_competition_settings_message_template_id_new_group_admin" id="tuja_competition_settings_message_template_id_new_group_admin">
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
        <label for="tuja_competition_settings_message_template_id_new_group_reporter">Ny grupp anmäld (e-post till den som anmäler):</label><br>
        <select name="tuja_competition_settings_message_template_id_new_group_reporter" id="tuja_competition_settings_message_template_id_new_group_reporter">
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
        <label for="tuja_competition_settings_message_template_id_new_crew_member">Ny person anmäler sig själv till funktionärslag (e-post):</label><br>
        <select name="tuja_competition_settings_message_template_id_new_crew_member" id="tuja_competition_settings_message_template_id_new_crew_member">
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
        <label for="tuja_competition_settings_message_template_id_new_noncrew_member">Ny person anmäler sig själv till deltagarlag (e-post):</label><br>
        <select name="tuja_competition_settings_message_template_id_new_noncrew_member" id="tuja_competition_settings_message_template_id_new_noncrew_member">
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

    <h3>Typer av grupper</h3>

    <table>
        <thead>
        <tr>
            <th>Id</th>
            <th>Namn</th>
            <th>Funktionär</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td></td>
            <td><input type="text"
                       class="text"
                       name="<?= tuja_group_category_field_name(GROUP_CATEGORY_FIELD_NEW_ID, 'name') ?>"
                       placeholder="Namn på ny kategori"></td>
            <td><input type="checkbox"
                       class="checkbox"
                       name="<?= tuja_group_category_field_name(GROUP_CATEGORY_FIELD_NEW_ID, 'iscrew') ?>"
                       value="yes"></td>
        </tr>
        </tfoot>
        <tbody>

        <?php
        // TODO: Use javascript-based add-and-remove feature, like how message templates are added and removed.
        foreach ($category_dao->get_all_in_competition($competition->id) as $category) {
            printf('' .
                '<tr>' .
                '  <td>%d</td>' .
                '  <td><input type="text" value="%s" name="%s"></td>' .
                '  <td><input type="checkbox" value="yes" class="checkbox" %s name="%s"></td>' .
                '</tr>',
                $category->id,
                $category->name,
                tuja_group_category_field_name($category->id, 'name'),
                $category->is_crew == true ? 'checked="checked"' : '',
                tuja_group_category_field_name($category->id, 'iscrew'));
        }
        ?>
        </tbody>
    </table>
    <button class="button button-primary"
            type="submit"
            name="tuja_competition_settings_action"
            value="save">
        Spara
    </button>
</form>
