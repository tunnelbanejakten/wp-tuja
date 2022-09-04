<?php
namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\store\ResponseDao;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
$this->print_leaves_menu();
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
		false,
		'tuja_points_action',
		'save'
	);
	?>

	<p><strong>Stationer</strong></p>
	<table class="tuja-table">
		<tbody>	
		<?php
		array_walk(
			$stations,
			function( Station $station ) use ( &$station_points_by_key ) {
				$field_key = self::get_station_points_field_key( $station->id, $this->group->id );
				$input     = sprintf(
					'<input
						type="number"
						min="0"
						placeholder="0"
						value="%s"
						id="%s" 
						name="%s">',
					@$station_points_by_key[ $field_key ] ?? '',
					$field_key,
					$field_key
				);

				printf( '<tr><td>%s</td><td>%s</td></tr>', $station->name, $input );
			}
		);
		?>
		</tbody>
	</table>

	<p><strong>Bonuspoäng</strong></p>
	<table class="tuja-table">
		<tbody>	
		<?php
		array_walk(
			$all_extra_points_names,
			function( string $name ) use ( &$extra_points_by_key ) {
				$field_key    = self::get_extra_points_field_key( $name, $this->group->id );
				$points_input = sprintf(
					'<input
						type="number"
						min="0"
						placeholder="0"
						value="%s"
						id="%s" 
						name="%s">',
					@$extra_points_by_key[ $field_key ] ?? '',
					$field_key,
					$field_key
				);

				$name_field_key = self::get_extra_points_field_key( $name, self::MAGIC_NUMBER_NAME_FIELD_ID );
				$read_only      = ! empty( $name );
				$name_input     = sprintf( '<input type="text" name="%s" id="%s" value="%s" %s>', $name_field_key, $name_field_key, $name, $read_only ? 'readonly' : '' );

				printf( '<tr><td>%s</td><td>%s</td></tr>', $name_input, $points_input );
			}
		);
		?>
		</tbody>
	</table>

	<div class="buttons">
		<?php echo $save_station_and_extra_points_button; ?>
	</div>

</form>
