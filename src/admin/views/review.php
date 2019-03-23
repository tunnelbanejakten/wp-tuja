<?php

namespace tuja\admin;
use tuja\view\FieldImages;

?>

<form method="post" action="<?= add_query_arg() ?>">
    <h1>Tävling <?= sprintf('<a href="%s">%s</a>', $competition_url, $this->competition->name) ?></h1>

    <?php
    if (empty($responses)) {
        printf('<p>Allt är redan kontrollerat. Bra jobbat!</p>');
    } else {
        ?>

        <p>Här ser du vilka svar som ännu inte kontrollerats manuellt.</p>
        <p>
            Du ser vilken poäng som kommer delas ut om du inte gör något. Om du fyller i poäng för ett svar är det din
            poäng som räknas.
        </p>
        <table class="tuja-admin-review">
            <tbody>
            <tr>
                <td>
                    <div class="spacer"></div>
                </td>
                <td>
                    <div class="spacer"></div>
                </td>
                <td colspan="4"></td>
            </tr>
            <?php

            $response_ids = [];
            foreach ($forms as $form) {
                $is_form_name_printed = false;
                $questions = $db_question->get_all_in_form($form->id);
                foreach ($questions as $question) {
                    $is_question_printed = false;
                    foreach ($responses as $response) {
                        if ($response->form_question_id === $question->id) {
                            if (!$is_form_name_printed) {
	                            printf( '<tr class="tuja-admin-review-form-row"><td colspan="6"><strong>%s</strong></td></tr>', $form->name );
                                $is_form_name_printed = true;
                            }
                            if (!$is_question_printed) {
	                            printf( '<tr class="tuja-admin-review-question-row"><td></td><td colspan="5"><strong>%s</strong></td></tr>', $question->text );
	                            printf( '' .
	                                    '<tr class="tuja-admin-review-correctanswer-row">' .
	                                    '  <td colspan="2"></td>' .
	                                    '  <td valign="top">Rätt svar:</td>' .
	                                    '  <td valign="top" colspan="3">%s</td>' .
	                                    '</tr>',
		                            join( '<br>', $question->correct_answers ) );
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

	                        $group_url = add_query_arg( array(
		                        'tuja_group' => $response->group_id,
		                        'tuja_view'  => 'Group'
	                        ) );

	                        $score = $question->score( $response->answers );

	                        $score_class = $question->score_max > 0 ? AdminUtils::getScoreCssClass( $score / $question->score_max ) : '';

	                        printf( '' .
	                                '<tr class="tuja-admin-review-response-row">' .
	                                '  <td colspan="2"></td>' .
	                                '  <td valign="top">Svar från <a href="%s">%s</a>:</td>' .
	                                '  <td valign="top">%s</td>' .
	                                '  <td valign="top"><span class="tuja-admin-review-autoscore %s">%s p</span></td>' .
	                                '  <td valign="top"><input type="number" name="%s" value="%s" size="5" min="0" max="%d"> p</td>' .
	                                '</tr>',
		                        $group_url,
                                $groups_map[$response->group_id]->name,
                                is_array($response->answers) ? join('<br>', $response->answers) : '<em>Ogiltigt svar</em>',
		                        $score_class,
		                        $score,
                                sprintf('tuja_review_points__%s__%s', $response->form_question_id, $response->group_id),
		                        $field_value,
		                        $question->score_max ?: 1000 );
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
