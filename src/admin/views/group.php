<?php
namespace tuja\admin;

use DateTime;
use tuja\util\rules\RuleResult;
use tuja\view\FieldImages;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>">
    <h3>Grupp <?= htmlspecialchars($group->name) ?> (id: <code><?= htmlspecialchars($group->random_id) ?></code>)</h3>

    <h3>Status för anmälan</h3>

	<?php

	$css_class_mapping = [
		RuleResult::OK      => 'notice-success',
		RuleResult::WARNING => 'notice-warning',
		RuleResult::BLOCKER => 'notice-error'
	];

	foreach ( $registration_evaluation as $result ) {
		printf( '<div class="notice %s" style="margin-left: 2px"><p><strong>%s: </strong>%s</p></div>',
			$css_class_mapping[ $result->status ],
			$result->rule_name,
			$result->details );
	}
	?>

    <h3>Svar och poäng</h3>
    <p>
        <strong>Totalt <?= $final_score ?> poäng.</strong>
		<?php
		if ( $questions_score != $final_score ) {
			printf( '%d poäng har dragits av pga. att maximal poäng uppnåtts på vissa frågegrupper.', $questions_score - $final_score );
		}
		?>
    </p>

    <table class="tuja-admin-review">
        <thead>
        <tr>
            <th colspan="2">Fråga</th>
            <th>Lagets svar</th>
            <th>Rätt svar</th>
            <th colspan="2">Poäng</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <div class="spacer"></div>
            </td>
            <td colspan="5"></td>
        </tr>

        <?php
        foreach ($forms as $form) {
	        printf( '<tr class="tuja-admin-review-form-row"><td colspan="6"><strong>%s</strong></td></tr>', $form->name );
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

                // TODO: Rewrite is a hack for getting HTML into $response->answers
                if (is_array($response->answers) && $question->type == 'images') {
                    $field = new FieldImages($question->possible_answers ?: $question->correct_answers);
                    // For each user-provided answer, render the photo description and a photo thumbnail:
	                $group_key         = $group->random_id;
	                $response->answers = array_map( function ( $answer ) use ( $field, $group_key ) {
		                return $field->render_admin_preview( $answer, $group_key );
                    }, $response->answers);
                }

	            $score_class = $question->score_max > 0 ? AdminUtils::getScoreCssClass( $calculated_score_without_override / $question->score_max ) : '';

	            printf( '' .
	                    '<tr class="tuja-admin-review-response-row"><td></td>' .
                    '  <td valign="top">%s</td>' .
                    '  <td valign="top">%s</td>' .
                    '  <td valign="top">%s</td>' .
                    '  <td valign="top"><span class="tuja-admin-review-autoscore %s">%s p</span></td>' .
                    '  <td valign="top"><input type="text" name="%s" value="%s" size="5"></td>' .
                    '</tr>',
                    $question->text,
		            join( '<br>', $question->correct_answers ),
                    is_array($response->answers) ? join('<br>', $response->answers) : '<em>Ogiltigt svar</em>',
		            $score_class,
                    $calculated_score_without_override,
                    'tuja_group_points__' . $question->id,
		            $points_override );
            }
        }
        ?>
        </tbody>
    </table>
    <button class="button button-primary" type="submit" name="tuja_points_action" value="save">Spara</button>

    <h3>Deltagare</h3>
    <table>
        <thead>
        <tr>
            <th>Namn</th>
            <th>Personnummer</th>
            <th>Ålder</th>
            <th>Medföljare</th>
            <th>Lagledare</th>
            <th>Telefon</th>
            <th>E-post</th>
        </tr>
        </thead>
        <tbody>
		<?php
		print join( '', array_map( function ( $person ) {
			return sprintf( '<tr>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td>%.1f</td>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td><a href="mailto:%s">%s</a></td>' .
			                '</tr>',
				$person->name,
				$person->pno,
				$person->age,
				! $person->is_competing ? 'Ja' : '' ,
				$person->is_group_contact ? 'Ja' : '',
				$person->phone,
				$person->email,
				$person->email);
		}, $people ) );
		?>
        </tbody>
    </table>
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