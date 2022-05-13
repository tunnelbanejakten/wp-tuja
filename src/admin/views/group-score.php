<?php
namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\store\ResponseDao;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">

	<p id="tuja-group-score" data-total-final="<?php echo $score_result->total_final; ?>">
		<strong>Totalt <?php echo $score_result->total_final; ?> poäng.</strong>
		<?php
		if ( $score_result->total_without_question_group_max_limits != $score_result->total_final ) {
			printf(
				'%d poäng har dragits av pga. att maximal poäng uppnåtts på vissa frågegrupper.',
				$score_result->total_without_question_group_max_limits - $score_result->total_final
			);
		}
		?>
	</p>

	<?php
	$question_filters = array(
		ResponseDao::QUESTION_FILTER_ALL                   => 'alla frågor (även obesvarade och okontrollerade)',
		ResponseDao::QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE => 'alla svar där auto-rättningen är osäker',
		ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL        => 'alla svar som inte kontrollerats',
		ResponseDao::QUESTION_FILTER_UNREVIEWED_IMAGES     => 'alla bilder som inte kontrollerats',
		ResponseDao::QUESTION_FILTER_UNREVIEWED_CHECKPOINT => 'alla kontroller som inte kontrollerats',
	);

	printf(
		'<p>Filter: %s</p>',
		join(
			', ',
			array_map(
				function ( $key, $label ) {
					return ( ( @$_GET[ GroupScore::QUESTION_FILTER_URL_PARAM ] ?: GroupScore::DEFAULT_QUESTION_FILTER ) == $key )
					? sprintf( ' <strong>%s</strong>', $label )
					: sprintf(
						' <a href="%s">%s</a>',
						add_query_arg(
							array(
								GroupScore::QUESTION_FILTER_URL_PARAM => $key,
							)
						),
						$label
					);
				},
				array_keys( $question_filters ),
				array_values( $question_filters )
			)
		)
	);

	$review_component->render(
		@$_GET[ GroupScore::QUESTION_FILTER_URL_PARAM ] ?: GroupScore::DEFAULT_QUESTION_FILTER,
		array( $group ),
		false
	);
	?>

	<button class="button button-primary" type="submit" name="tuja_points_action" value="save">
		Spara manuella poäng och markera svar som kontrollerade
	</button>

	<p><strong>Stationer</strong></p>
	<table class="tuja-table">
		<tbody>	
		<?php
		array_walk(
			$stations,
			function( Station $station ) use ( $score_result ) {
				$points = @$score_result->stations[ $station->id ]->final ?? 0;
				printf( '<tr><td>%s</td><td>%s p</td></tr>', $station->name, $points );
			}
		);
		?>
		</tbody>
	</table>

</form>
