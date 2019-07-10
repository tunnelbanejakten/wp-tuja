<?php
namespace tuja\admin;

use tuja\data\store\ResponseDao;
use tuja\util\rules\RuleResult;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>" class="tuja">
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
        <strong>Totalt <?= $score_result->total_final ?> poäng.</strong>
		<?php
		if ( $score_result->total_without_question_group_max_limits != $score_result->total_final ) {
			printf( '%d poäng har dragits av pga. att maximal poäng uppnåtts på vissa frågegrupper.',
				$score_result->total_without_question_group_max_limits - $score_result->total_final );
		}
		?>
    </p>

	<?php
	$review_url = add_query_arg( array(
		'tuja_view'                       => 'Review',
		'tuja_competition'                => $this->competition->id,
		Review::GROUP_FILTER_URL_PARAM      => FieldGroupSelector::to_key( $group ),
		Review::QUESTION_FILTER_URL_PARAM => ResponseDao::QUESTION_FILTER_ALL
	) );
	printf( '<p><a href="%s">Visa frågor och svar</a></p>', $review_url );
	?>


    <table class="tuja-admin-review">
        <thead>
        <tr>
            <th colspan="2">Fråga</th>
            <th>Rätt svar</th>
            <th>Lagets svar</th>
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
			$current_group = '';

            foreach ($questions as $question) {

	            $calculated_score_without_override = isset( $score_result->questions[ $question->id ] )
		            ? $score_result->questions[ $question->id ]->auto
		            : 0;

	            $response = isset( $response_per_question[ $question->id ] ) ? $response_per_question[ $question->id ] : null;
                // TODO: Don't do "override" calculators both here and in ScoreCalculator. Only use the latter for all things related to score.
                // Only set $points_override if the override points were set AFTER the most recent answer was created/submitted.
	            $points_override = isset( $points_overrides_per_question[ $question->id ] ) && $points_overrides_per_question[ $question->id ] && ( ! $response || $points_overrides_per_question[ $question->id ]->created > $response->created )
                    ? $points_overrides_per_question[$question->id]->points
                    : '';

				$score_class = $question->score_max > 0 ? AdminUtils::getScoreCssClass( $calculated_score_without_override / $question->score_max ) : '';
				$q_group = $question_groups[ $question->question_group_id ]->text;

				if($q_group !== $current_group) {
					printf( '' .
						'<tr class="tuja-admin-review-response-row question-group">' .
						'  <td></td>' .
						'  <td valign="top">%s</td>' .
						'  <td valign="top" colspan="4"></td>' .
						'</tr>',
						$q_group
					);
					$current_group = $q_group;
				}
					
	            printf( '' .
					'<tr class="tuja-admin-review-response-row">' .
					'  <td></td>' .
                    '  <td valign="top">%s</td>' .
                    '  <td valign="top">%s</td>' .
                    '  <td valign="top">%s</td>' .
                    '  <td valign="top"><span class="tuja-admin-review-autoscore %s">%s p</span></td>' .
                    '  <td valign="top"><input type="number" name="%s" value="%s" size="5" min="0" max="%d"> p</td>' .
                    '</tr>',
		            $question->text,
		            $question->get_correct_answer_html(),
		            $response ? $question->get_submitted_answer_html($response->submitted_answer, $group) : '',
		            $score_class,
                    $calculated_score_without_override,
                    'tuja_group_points__' . $question->id,
		            $points_override,
		            $question->score_max ?: 1000 );
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
            <th></th>
            <th>Namn</th>
            <th>Personnummer</th>
            <th>Ålder</th>
            <th>Medföljare</th>
            <th>Lagledare</th>
            <th>Telefon</th>
            <th>E-post</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td colspan="8">
                Flytta markerade deltagare till detta lag: <br>
                <select name="tuja_group_move_people_to">
                    <option value="0">Välj lag</option>
                    <?= join(array_map(function($g) use ($group) {
	                    return sprintf( '<option value="%s" %s>%s</option>',
                            $g->id,
		                    $group->id == $g->id ? 'disabled="disabled"' : '',
		                    $g->name);
                    }, $groups)) ?>
                </select>
                <button class="button" type="submit" name="tuja_points_action" value="move_people">Flytta</button>
            </td>
        </tr>
        </tfoot>
        <tbody>
		<?php
		print join( '', array_map( function ( $person ) {
			return sprintf( '<tr>' .
			                '<td><input type="checkbox" name="tuja_group_people[]" value="%d" id="tuja_group_people__person_%d"></td>' .
			                '<td><label for="tuja_group_people__person_%d">%s</label></td>' .
			                '<td>%s</td>' .
			                '<td>%.1f</td>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td><a href="mailto:%s">%s</a></td>' .
			                '</tr>',
				$person->id,
				$person->id,
				$person->id,
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
        $messages = $db_message->get_by_group($group->id);
        print $messages_manager->get_html( $messages )
        ?>
        </tbody>
    </table>
</form>