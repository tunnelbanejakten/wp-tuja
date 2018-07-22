<?php

use tuja\data\model\Form;
use tuja\data\model\Group;

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}
if ($_POST['tuja_action'] == 'group_create') {
    $props = new Group();
    $props->name = $_POST['tuja_group_name'];
    $props->type = $_POST['tuja_group_type'];
    $props->competition_id = $competition->id;
    $db_groups->create($props);
} elseif ($_POST['tuja_action'] == 'form_create') {
    $props = new Form();
    $props->name = $_POST['tuja_form_name'];
    $props->competition_id = $competition->id;
    $db_form->create($props);
}
$forms = $db_form->get_all_in_competition($competition->id);
$groups = $db_groups->get_all_in_competition($competition->id);
?>
<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tunnelbanejakten</h1>
    <h2>Tävling <?= $competition->name ?></h2>
    <h3>Formulär</h3>
    <?php
    foreach ($forms as $form) {
            $url = add_query_arg(array(
                'tuja_view' => 'form',
                'tuja_form' => $form->id
            ));
            printf('<p><a href="%s">%s</a></p>', $url, $form->name);
    }
    ?>
    <input type="text" name="tuja_form_name"/>
    <button type="submit" name="tuja_action" value="form_create">Skapa</button>
    <h3>Lag</h3>
    <?php
    foreach ($groups as $group) {
        printf('<p>%s</p>', $group->name);
    }
    ?>
    <input type="text" name="tuja_group_name"/>
    <select name="tuja_group_type">
        <option value="participant">Tävlande</option>
        <option value="crew">Funktionär</option>
    </select>
    <button type="submit" name="tuja_action" value="group_create">Skapa</button>
</form>
