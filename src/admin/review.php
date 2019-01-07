<?php

namespace tuja\admin;

use tuja\view\FieldImages;

$competition = $db_competition->get($_GET['tuja_competition']);
if (!$competition) {
    print 'Could not find competition';
    return;
}

$competition_url = add_query_arg(array(
    'tuja_competition' => $competition->id,
    'tuja_view' => 'competition'
));

if ($_POST['tuja_review_action'] === 'save') {
    $form_values = array_filter($_POST, function ($key) {
        return substr($key, 0, strlen('tuja_review_points')) === 'tuja_review_points';
    }, ARRAY_FILTER_USE_KEY);

    foreach ($form_values as $field_name => $field_value) {
        list(, $question_id, $group_id) = explode('__', $field_name);
        $db_points->set($group_id, $question_id, is_numeric($field_value) ? intval($field_value) : null);
    }
    $db_response->mark_as_reviewed(explode(',', $_POST['tuja_review_response_ids']));
}

$groups = $db_groups->get_all_in_competition($competition->id);
$groups_map = array_combine(array_map(function ($group) {
    return $group->id;
}, $groups), array_values($groups));
$forms = $db_form->get_all_in_competition($competition->id);

//$score_calculator = new ScoreCalculator($competition->id, $db_question, $db_response, $db_groups, $db_points);
//$calculated_scores_final = $score_calculator->score_per_question($group->id);
//$calculated_scores_without_overrides = $score_calculator->score_per_question($group->id, false);
$responses = $db_response->get_not_reviewed($competition->id);
//$response_per_question = array_combine(array_map(function ($response) {
//    return $response->form_question_id;
//}, $responses), array_values($responses));
//$points_overrides = $db_points->get_by_group($group->id);
//$points_overrides_per_question = array_combine(array_map(function ($points) {
//    return $points->form_question_id;
//}, $points_overrides), array_values($points_overrides));

//printf('<pre>%s</pre>', print_r($responses, true));

$current_points = $db_points->get_by_competition($competition->id);
$current_points = array_combine(
    array_map(function ($points) {
        return $points->form_question_id . '__' . $points->group_id;
    }, $current_points),
    array_values($current_points));

?>
<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $competition->name) ?></h1>

    <?php
    if (empty($responses)) {
        printf('<p>Allt är redan kontrollerat. Bra jobbat!</p>');
    } else {
        ?>

        <table>
            <thead>
            <tr>
                <th rowspan="2">Lag</th>
                <th rowspan="2">Rätt svar</th>
                <th rowspan="2">Lagets svar</th>
                <th colspan="3">Poäng</th>
            </tr>
            <tr>
                <td>Autom.</td>
                <td>Manuell</td>
                <td>Slutlig</td>
            </tr>
            </thead>
            <tbody>

            <?php

            $response_ids = [];
            foreach ($forms as $form) {
                $is_form_name_printed = false;
                $questions = $db_question->get_all_in_form($form->id);
                foreach ($questions as $question) {
                    $is_question_printed = false;
//                $calculated_score_without_override = $calculated_scores_without_overrides[$question->id] ?: 0;
//                $calculated_score_final = $calculated_scores_final[$question->id] ?: 0;
//                $points_override = $points_overrides_per_question[$question->id] ? $points_overrides_per_question[$question->id]->points : '';
                    foreach ($responses as $response) {
                        if ($response->form_question_id === $question->id) {
                            if (!$is_form_name_printed) {
                                printf('<tr><td colspan="6"><strong>%s</strong></td></tr>', $form->name);
                                $is_form_name_printed = true;
                            }
                            if (!$is_question_printed) {
                                printf('<tr><td colspan="6"><strong>%s</strong></td></tr>', $question->text);
                                $is_question_printed = true;
                            }
                            $response_ids[] = $response->id;
                            $points = $current_points[$response->form_question_id . '__' . $response->group_id];
                            // Only set $points if the override points were set AFTER the most recent answer was created/submitted.
                            $field_value = isset($points) && $points->created > $response->created ? $points->points : '';

                            if (is_array($response->answers) && $question->type == 'images') {
                                $field = new FieldImages($question->possible_answers ?: $question->correct_answers);
                                // For each user-provided answer, render the photo description and a photo thumbnail:
                                $response->answers = array_map(function ($answer) use ($field) {
                                    return $field->render_admin_preview($answer);
                                }, $response->answers);
                            }

                            printf('' .
                                '<tr>' .
                                '  <td valign="top">%s</td>' .
                                '  <td valign="top">%s</td>' .
                                '  <td valign="top">%s</td>' .
                                '  <td valign="top">%s p</td>' .
                                '  <td valign="top"><input type="text" name="%s" value="%s" size="5"></td>' .
                                '  <td valign="top">%d p</td>' .
                                '</tr>',
                                $groups_map[$response->group_id]->name,
                                join(', ', $question->correct_answers),
                                is_array($response->answers) ? join('<br>', $response->answers) : '<em>Ogiltigt svar</em>',
                                '',
                                sprintf('tuja_review_points__%s__%s', $response->form_question_id, $response->group_id),
                                $field_value,
                                '');
                        }
                    }
                }
            }
            ?>
            </tbody>
        </table>
        <input type="hidden" name="tuja_review_response_ids" value="<?= join(',', $response_ids) ?>">
        <button class="button button-primary" type="submit" name="tuja_review_action" value="save">
            Spara manuella poäng och markera svar som kontrollerade
        </button>
        <?php
    }
    ?>
</form>
