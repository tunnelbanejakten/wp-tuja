<?php namespace tuja\admin; ?>

<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $this->competition->name) ?></h1>

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
