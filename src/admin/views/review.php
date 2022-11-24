<?php

namespace tuja\admin;

use tuja\data\store\ResponseDao;

$this->print_root_menu();
// $this->print_menu();

$question_filters = [
	ResponseDao::QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE => 'alla svar där auto-rättningen är osäker',
	ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL            => 'alla svar som inte kontrollerats',
	ResponseDao::QUESTION_FILTER_UNREVIEWED_IMAGES         => 'alla bilder som inte kontrollerats',
	ResponseDao::QUESTION_FILTER_UNREVIEWED_CHECKPOINT     => 'alla kontroller som inte kontrollerats'
];

?>

<p>
	Du ser vilken poäng som kommer delas ut om du inte gör något. Om du fyller i poäng för ett svar är det din
	poäng som räknas.
</p>

<form method="get" action="<?= add_query_arg( [] ) ?>" class="tuja">
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
					function ( $key, $label ) use ( $selected_filter ) {
						return sprintf( '<option value="%s" %s>%s</option>',
							$key,
							$key == $selected_filter ? ' selected="selected"' : '',
							$label );
					},
					array_keys( $question_filters ),
					array_values( $question_filters ) ) ) );
		print ' för ';
        $field_group_selector->render( Review::GROUP_FILTER_URL_PARAM, @$_GET[Review::GROUP_FILTER_URL_PARAM] );

		?>
        <button type="submit" class="button">Visa</button>
    </div>
</form>
<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">

	<?php
	$review_component->render(
		$selected_filter,
		$selected_groups,
		true,
		'tuja_review_action',
		'save'
	);
	?>
</form>
