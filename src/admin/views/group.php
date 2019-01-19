<?php namespace tuja\admin; ?>

<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $this->competition->name) ?></h1>
    <h2>Grupp <?= htmlspecialchars($group->name) ?></h2>

    <p><strong>Totalt <?= array_sum($calculated_scores_final) ?> poäng.</strong></p>

    <table>
        <thead>
        <tr>
            <th rowspan="2">Fråga</th>
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
        <tfoot>
        <tr>
            <td colspan="5"></td>
            <td><?= array_sum($calculated_scores_final) ?> p</td>
        </tr>
        </tfoot>
        <tbody>

        <?php
        foreach ($forms as $form) {
            printf('<tr><td colspan="6"><strong>%s</strong></td></tr>', $form->name);
            $questions = $db_question->get_all_in_form($form->id);
            foreach ($questions as $question) {
                $calculated_score_without_override = $calculated_scores_without_overrides[$question->id] ?: 0;
                $calculated_score_final = $calculated_scores_final[$question->id] ?: 0;

                $field_value = isset($points) && $points->created > $response->created ? $points->points : '';
                $response = $response_per_question[$question->id]; // TODO: One line to late?
                // Only set $points_override if the override points were set AFTER the most recent answer was created/submitted.
                $points_override = $points_overrides_per_question[$question->id] && $points_overrides_per_question[$question->id]->created > $response->created
                    ? $points_overrides_per_question[$question->id]->points
                    : '';

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
                    $question->text,
                    join(', ', $question->correct_answers),
                    is_array($response->answers) ? join('<br>', $response->answers) : '<em>Ogiltigt svar</em>',
                    $calculated_score_without_override,
                    'tuja_group_points__' . $question->id,
                    $points_override,
                    $calculated_score_final);
            }
        }
        ?>
        </tbody>
    </table>
    <button class="button button-primary" type="submit" name="tuja_points_action" value="save">Spara</button>

    <h3>Meddelanden</h3>
    <table>
        <tbody>

        <?php
        // TODO: Show messages nicer (also in messages.php)
        $messages = $db_message->get_by_group($group->id);
        foreach ($messages as $message) {
            if (is_array($message->image_ids)) {
                $field = new FieldImages([]);
                // For each user-provided answer, render the photo description and a photo thumbnail:
                $images = array_map(function ($image_id) use ($field) {
                    return $field->render_admin_preview("$image_id,,");
                }, $message->image_ids);
            }

            printf('<tr>' .
                '<td valign="top">%s</td>' .
                '<td valign="top">%s</td>' .
                '<td valign="top">%s</td>' .
                '</tr>',
                $message->date_received->format(DateTime::ISO8601),
                join('', $images),
                $message->text);
        }
        ?>
        </tbody>
    </table>
</form>