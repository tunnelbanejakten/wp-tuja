<?php

use tuja\data\model\Group;

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}
if ($_POST['tuja_action'] == 'group_create') {
    $props = new Group();
    $props->name = $_POST['tuja_group_name'];
    $props->competition_id = $competition->id;
    $db_groups->create($props);
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
        printf('<ul><li><strong>%s:</strong>', $form->name);
        $questions = $db_question->get_all_in_form($form->id);

        printf('<ul>');
        foreach ($questions as $question) {
            printf('<li>%s</li>', $question->text);
        }
        printf('</ul>');

        printf('</li></ul>');
    }
    ?>
    <h3>Lag</h3>
    <?php
    foreach ($groups as $group) {
        printf('<p>%s</p>', $group->name);
    }
    ?>
    <input type="text" name="tuja_group_name"/>
    <button type="submit" name="tuja_action" value="group_create">Skapa</button>
</form>
