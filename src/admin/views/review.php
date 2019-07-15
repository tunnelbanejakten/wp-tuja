<?php

namespace tuja\admin;

AdminUtils::printTopMenu( $competition );

use tuja\data\model\question\AbstractQuestion;
use tuja\data\store\ResponseDao;

$question_filters = [
	ResponseDao::QUESTION_FILTER_ALL                       => [
		'label'                         => 'alla frågor (även obesvarade och kontrollerade)',
		'hide_groups_without_responses' => false
	],
	ResponseDao::QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE => [
		'label'                         => 'alla svar där auto-rättningen är osäker',
		'hide_groups_without_responses' => true
	],
	ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL            => [
		'label'                         => 'alla svar som inte kontrollerats',
		'hide_groups_without_responses' => true
	],
	ResponseDao::QUESTION_FILTER_UNREVIEWED_IMAGES         => [
		'label'                         => 'alla bilder som inte kontrollerats',
		'hide_groups_without_responses' => true
	]
];

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
					function ( $key, $question_filter ) use ( $selected_filter ) {
						return sprintf( '<option value="%s" %s>%s</option>',
							$key,
							$key == $selected_filter ? ' selected="selected"' : '',
							$question_filter['label'] );
					},
					array_keys( $question_filters ), array_values( $question_filters ) ) ) );
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
            $response_ids                  = [];
            $limit                         = 2000;
            $hide_groups_without_responses = @$question_filters[ $selected_filter ]['hide_groups_without_responses'];

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

		            $answer_html = $question->get_correct_answer_html();
		            if ( ! empty( $answer_html ) ) {
			            printf( '' .
			                    '<tr class="tuja-admin-review-correctanswer-row">' .
			                    '  <td colspan="2"></td>' .
			                    '  <td valign="top">Rätt svar</td>' .
			                    '  <td valign="top" colspan="3">%s</td>' .
			                    '</tr>',
				            $answer_html );
		            }


		            foreach ( $selected_groups as $group_id ) {
			            $group_url = add_query_arg( array(
				            'tuja_group' => $group_id,
				            'tuja_view'  => 'Group'
			            ) );

			            $response_entry = @$question_entry['responses'][ $group_id ];

			            if ( $hide_groups_without_responses && ! isset( $response_entry ) ) {
				            continue;
			            }

			            if ( isset( $response_entry ) ) {
				            $response       = $response_entry['response'];
				            $response_ids[] = $response->id;
				            $score          = $response_entry['score']->score;

				            $score_field_value = isset( $points ) && $points->created > $response->created ? $points->points : '';
				            $auto_score_html   = sprintf( '<span class="tuja-admin-review-autoscore %s">%s p</span>',
					            $question->score_max > 0 ? AdminUtils::getScoreCssClass( $score / $question->score_max ) : '',
					            $score );
				            $response_html     = $question->get_submitted_answer_html( $response->submitted_answer, $groups_map[ $response->group_id ] );
				            $response_id       = $response->id;
			            } else {
				            $score_field_value = isset( $points ) ? $points->points : '';
				            $auto_score_html   = '';
				            $response_html     = AbstractQuestion::RESPONSE_MISSING_HTML;
				            $response_id       = Review::RESPONSE_MISSING_ID;
			            }
			            printf( '' .
			                    '<tr class="tuja-admin-review-response-row">' .
			                    '  <td colspan="2"></td>' .
			                    '  <td valign="top"><a href="%s" class="tuja-admin-review-group-link">%s</a></td>' .
			                    '  <td valign="top">%s</td>' .
			                    '  <td valign="top">%s</td>' .
			                    '  <td valign="top"><input type="number" name="%s" value="%s" size="5" min="0" max="%d"></td>' .
			                    '</tr>',
				            $group_url,
				            $groups_map[ $group_id ]->name,
				            $response_html,
				            $auto_score_html,
				            sprintf( 'tuja_review_points__%s__%s__%s', $response_id, $question_id, $group_id ),
				            $score_field_value,
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
        <button class="button button-primary" type="submit" name="tuja_review_action" value="save">
            Spara manuella poäng och markera svar som kontrollerade
        </button>
        <?php
    }
    ?>
</form>
