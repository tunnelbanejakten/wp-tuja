<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<h3>Importera</h3>
	<?php
	printf( '<p><a href="%s">Importera kartmarkörer</a></p>', $import_url );
	?>

	<h3>Alla markörer</h3>
	<table>
		<thead>
		<tr>
		<td></td>
		<?php
		foreach ( $maps as $map ) {
			printf( '<td>%s</a></td>', $map->name );
		}
		?>
		</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $questions as $question ) {
				printf(
					'<tr><td><span class="tuja-maps-question">%s</span></td>%s</tr>',
					$question->text,
					join(
						array_map(
							function ( $map ) use ( $question, $markers ) {
								$key   = sprintf( '%s__%s', $map->id, $question->id );
								$value = '';
								if ( isset( $markers[ $key ] ) ) {
									$value = sprintf( '%s %s %s', $markers[ $key ]->gps_coord_lat, $markers[ $key ]->gps_coord_long, $markers[ $key ]->name );
								}
								return sprintf( '<td><input type="text" name="%s" value="%s" /></td>', 'tuja_marker_raw__' . $key, $value );
							},
							$maps
						)
					)
				);
			}
			?>
		</tbody>
	</table>
	<div>
		<button type="submit" class="button" name="tuja_action" value="save" id="tuja_save_button">
			Spara ändringar
		</button>
	</div>
	<input type="text" name="tuja_map_name" id="tuja_map_name"/>
	<button type="submit" class="button" name="tuja_action" value="map_create" id="tuja_map_create_button">
		Skapa karta
	</button>
</form>
