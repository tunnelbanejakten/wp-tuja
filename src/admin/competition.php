<?php

include_once 'AdminUtils.php';

use data\store\GroupCategoryDao;
use tuja\data\model\Form;
use tuja\data\model\Group;
use util\score\ScoreCalculator;

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}
if ($_POST['tuja_action'] == 'group_create') {
    $props = new Group();
    $props->name = $_POST['tuja_group_name'];
    $props->category_id = $_POST['tuja_group_type'];
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
    <h1>Tävling <?= $competition->name ?></h1>

    <h3>Formulär</h3>
    <table>
        <tbody>
        <?php
        foreach ($forms as $form) {
            $url = add_query_arg(array(
                'tuja_view' => 'form',
                'tuja_form' => $form->id
            ));
            printf('' .
                '<tr>' .
                '<td><a href="%s">%s</a></td>' .
                '<td><code>[tuja_form form="%d"]</code></td>' .
                '<td><code>[tuja_create_group form="%d"]</code></td>' .
                '<td><code>[tuja_points form="%d"]</code></td>' .
                '</tr>', $url, $form->name, $form->id, $form->id, $form->id);
        }
        ?>
        </tbody>
    </table>
    <input type="text" name="tuja_form_name"/>
    <button type="submit" name="tuja_action" value="form_create">Skapa</button>
    <?php
    $settings_url = add_query_arg(array(
        'tuja_competition' => $competition->id,
        'tuja_view' => 'competition_settings'
    ));
    printf('<p><a href="%s">Inställningar för tävling</a></p>', $settings_url);
    ?>
    <h3>Ställning</h3>
    <?php
    $review_url = add_query_arg(array(
        'tuja_competition' => $competition->id,
        'tuja_view' => 'review'
    ));
    printf('<p><a href="%s">Gå igenom okontrollerade svar</a></p>', $review_url);
    ?>
    <table>
        <tbody>
        <?php
        $calculator = new ScoreCalculator($competition->id, $db_question, $db_response, $db_groups, $db_points);
        $score_board = $calculator->score_board();
        usort($score_board, function ($a, $b) {
            return $b['score'] - $a['score'];
        });
        foreach ($score_board as $team_score) {
            $group_url = add_query_arg(array(
                'tuja_group' => $team_score['group_id'],
                'tuja_view' => 'group'
            ));
            printf('<tr><td><a href="%s">%s</a></td><td>%d p</td></tr>', $group_url, htmlspecialchars($team_score['group_name']), $team_score['score']);
        }
        ?>
        </tbody>
    </table>
    <input type="text" name="tuja_group_name"/>
    <select name="tuja_group_type">
        <?php
        $category_dao = new GroupCategoryDao($wpdb);
        print join('', array_map(function ($category) {
            return sprintf('<option value="%d">%s (%s)</option>',
                $category->id,
                $category->name,
                $category->is_crew ? 'Funktionär' : 'Tävlande');
        }, $category_dao->get_all_in_competition($competition->id)));
        ?>
    </select>
    <button type="submit" name="tuja_action" value="group_create">Skapa</button>

    <h3>Specialfunktioner</h3>
    <?php
    $messages_url = add_query_arg(array(
        'tuja_competition' => $competition->id,
        'tuja_view' => 'messages'
    ));
    printf('<p><a href="%s">Meddelanden, ex. MMS</a></p>', $messages_url);
    ?>
</form>
