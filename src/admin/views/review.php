<?php

namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<form method="get" action="<?= add_query_arg() ?>" class="tuja">
    <?= join(array_map(function($key, $value) {
	    return sprintf( '<input type="hidden" name="%s" value="%s">', $key, $value );
    }, array_keys($_GET), array_values($_GET))) ?>
    <div>
        Visa
		<?php

		printf( '<select name="%s">%s</select>',
			Review::QUESTION_FILTER_URL_PARAM,
			join(
				array_map(
					function ( $question_filter ) {
						return sprintf( '<option value="%s" %s>%s</option>',
							$question_filter['key'],
							$question_filter['selected'] == true ? ' selected="selected"' : '',
							$question_filter['label'] );
					},
					$question_filters ) ) );
		print ' för ';
        $field_group_selector->render( Review::GROUP_FILTER_URL_PARAM, $_GET[Review::GROUP_FILTER_URL_PARAM] );

		?>
        <button type="submit" class="button">Visa</button>
    </div>
</form>
<form method="post" action="<?= add_query_arg() ?>" class="tuja">

	<?php


	if ( empty( $data ) ) {
        printf('<p>Allt är redan kontrollerat. Bra jobbat!</p>');
    } else {
        ?>

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
            $limit = 200;
            foreach ( $data as $form_id => $form_entry ) {
	            printf( '<tr class="tuja-admin-review-form-row"><td colspan="6"><strong>%s</strong></td></tr>', $form_entry['form']->name );
	            foreach ( $form_entry['questions'] as $question_id => $question_entry ) {

		            $question            = $question_entry['question'];
		            $question_group_text = $question_groups[ $question->question_group_id ]->text;
		            $question_text       = $question_group_text
			            ? $question_group_text . " : " . $question->text
			            : $question->text;
		            printf( '<tr class="tuja-admin-review-question-row"><td></td><td colspan="5"><strong>%s</strong></td></tr>',
			            $question_text );
		            printf( '' .
		                    '<tr class="tuja-admin-review-correctanswer-row">' .
		                    '  <td colspan="2"></td>' .
		                    '  <td valign="top">Rätt svar</td>' .
		                    '  <td valign="top" colspan="3">%s</td>' .
		                    '</tr>',
			            $question->get_correct_answer_html() );

		            usort( $question_entry['responses'], function ( $entry_a, $entry_b ) use ($groups_map) {
			            return strnatcasecmp(
				            $groups_map[ $entry_a['response']->group_id ]->name,
				            $groups_map[ $entry_b['response']->group_id ]->name
			            );
		            } );

		            foreach ( $question_entry['responses'] as $response_entry ) {
			            $response       = $response_entry['response'];
			            $response_ids[] = $response->id;
			            $score          = $response_entry['score']->score;

			            $group_url = add_query_arg( array(
				            'tuja_group' => $response->group_id,
				            'tuja_view'  => 'Group'
			            ) );

			            $field_value = isset( $points ) && $points->created > $response->created ? $points->points : '';

			            $score_class = $question->score_max > 0 ? AdminUtils::getScoreCssClass( $score / $question->score_max ) : '';

			            // TODO: Show field for points even if no response is available.

			            printf( '' .
			                    '<tr class="tuja-admin-review-response-row">' .
			                    '  <td colspan="2"></td>' .
			                    '  <td valign="top"><a href="%s" class="tuja-admin-review-group-link">%s</a></td>' .
			                    '  <td valign="top">%s</td>' .
			                    '  <td valign="top"><span class="tuja-admin-review-autoscore %s">%s p</span></td>' .
			                    '  <td valign="top"><input type="number" name="%s" value="%s" size="5" min="0" max="%d"></td>' .
			                    '</tr>',
				            $group_url,
				            $groups_map[ $response->group_id ]->name,
				            $question->get_submitted_answer_html( $response->submitted_answer, $groups_map[ $response->group_id ] ),
				            $score_class,
				            $score,
				            sprintf( 'tuja_review_points__%s', $response->id ),
				            $field_value,
				            $question->score_max ?: 1000 );
			            $limit = $limit - 1;
		            }
		            if ( $limit < 0 ) {
			            break 2;
		            }
	            }
            }
            ?>
            </tbody>
        </table>
        <?php
		if ( $limit < 0 ) {
			printf( '<p><em>Alla frågor visas inte.</em></p>' );
		}
        ?>
        <input type="hidden" name="tuja_review_response_ids" value="<?= join(',', $response_ids) ?>">
        <button class="button button-primary" type="submit" name="tuja_review_action" value="save">
            Spara manuella poäng och markera svar som kontrollerade
        </button>
        <?php
    }
    ?>
</form>
