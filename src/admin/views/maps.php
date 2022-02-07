<?php namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<h3>Importera</h3>
	<?php
	printf( '<p><a href="%s">Importera kartmarkörer</a></p>', $import_url );
	?>

	<h3>Alla markörer</h3>
	<table id="tuja_all_markers" class="tuja-table">
		<thead>
		<tr>
		<th>Karta:</th>
		<?php
		foreach ( $maps as $map ) {
			printf(
				'
			<td>
				<input type="text" class="text tuja-map-name-field" value="%s" name="%s" id="%s"><br>
				<button type="submit" class="button" name="tuja_action" onclick="return confirm(\'Är du säker?\');" value="%s" id="%s">Ta bort</button>
			</td>',
				$map->name,
				'tuja_map_name__' . $map->id,
				'tuja_map_name__' . $map->id,
				'tuja_map_delete__' . $map->id,
				'tuja_map_delete__' . $map->id
			);
		}
		?>
		<td>
			<input type="text" name="tuja_map_name" id="tuja_map_name" placeholder="Namn på ny karta"/><br>
			<button type="submit" class="button" name="tuja_action" value="map_create" id="tuja_map_create_button">
				Lägg till
			</button>
		</td>
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
								return sprintf( 
									'<td><input type="text" class="tuja-marker-raw-field" name="%s" id="%s" value="%s" /></td>', 
									'tuja_marker_raw__' . $key, 
									'tuja_marker_raw__' . $key, 
									$value );
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
		<button type="submit" class="button button-primary" name="tuja_action" value="save" id="tuja_save_button">
			Spara ändringar
		</button>
	</div>
</form>
