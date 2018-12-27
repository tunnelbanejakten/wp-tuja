<?php

use data\model\ValidationException;
use data\store\GroupCategoryDao;
use tuja\data\model\GroupCategory;

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

$competition_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'competition'
));

$category_dao = new GroupCategoryDao($wpdb);

if ($_POST['tuja_competition_settings_action'] === 'save') {

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
}

?>
<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tunnelbanejakten</h1>
    <h2>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h2>

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
