<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>">
    <?php
    $settings_url = add_query_arg(array(
        'tuja_competition' => $competition->id,
        'tuja_view' => 'CompetitionSettings'
    ));
    printf('<p><a href="%s">Inställningar</a></p>', $settings_url);
    $messages_url = add_query_arg(array(
        'tuja_competition' => $competition->id,
        'tuja_view' => 'Messages'
    ));
    printf('<p><a href="%s">Meddelanden, ex. MMS</a></p>', $messages_url);
    ?>

    <h3>Formulär</h3>
    <table>
        <tbody>
        <?php
        foreach ($forms as $form) {
            $url = add_query_arg(array(
                'tuja_view' => 'Form',
                'tuja_form' => $form->id
            ));
            printf('' .
                '<tr>' .
                '<td><a href="%s">%s</a></td>' .
                '<td><code>[tuja_form form="%d"]</code></td>' .
                '<td><code>[tuja_create_group competition="%d"]</code></td>' .
                '<td><code>[tuja_points competition="%d"]</code></td>' .
                '</tr>', $url, $form->name, $form->id, $form->id, $form->id);
        }
        ?>
        </tbody>
    </table>
    <input type="text" name="tuja_form_name"/>
    <button type="submit" name="tuja_action" value="form_create">Skapa</button>

    <h3>Ställning</h3>
    <?php
    $review_url = add_query_arg(array(
        'tuja_competition' => $competition->id,
        'tuja_view' => 'Review'
    ));
    printf('<p><a href="%s">Gå igenom okontrollerade svar</a></p>', $review_url);
    ?>
    <table>
        <tbody>
        <?php
        $calculator  = new ScoreCalculator( $competition->id, new QuestionDao(), new ResponseDao(), new GroupDao(), new PointsDao() );
        $score_board = $calculator->score_board();
        usort($score_board, function ($a, $b) {
            return $b['score'] - $a['score'];
        });
        foreach ($score_board as $team_score) {
            $group_url = add_query_arg(array(
                'tuja_group' => $team_score['group_id'],
                'tuja_view' => 'Group'
            ));
            printf('<tr><td><a href="%s">%s</a></td><td>%d p</td></tr>', $group_url, htmlspecialchars($team_score['group_name']), $team_score['score']);
        }
        ?>
        </tbody>
    </table>
    <input type="text" name="tuja_group_name"/>
    <select name="tuja_group_type">
        <?php
        $category_dao = new GroupCategoryDao();
        print join('', array_map(function ($category) {
            return sprintf('<option value="%d">%s (%s)</option>',
                $category->id,
                $category->name,
                $category->is_crew ? 'Funktionär' : 'Tävlande');
        }, $category_dao->get_all_in_competition($competition->id)));
        ?>
    </select>
    <button type="submit" name="tuja_action" value="group_create">Skapa</button>
</form>
