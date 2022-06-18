<?php namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
$this->print_menu();
?>
<div id="tuja-map-page">
<div id="tuja-map-markers">
	<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
		<button type="submit" class="button button-primary" name="tuja_action" value="map_save" id="tuja_save_button">
			Spara ändringar
		</button>
		<button type="submit" class="button" name="tuja_action" value="map_delete" id="tuja_delete_button" onclick="return confirm('Är du säker?');">
			Ta bort
		</button>
		<table id="tuja_all_markers" class="tuja-table">
			<tbody>
				<tr>
					<td>Kartans namn</td>
					<?php
					printf(
						'<td><input type="text" name="%s" id="%s" value="%s" /></td>',
						'tuja_map_name',
						'tuja_map_name',
						$map->name,
					);
					?>
				</tr>
				<?php
				$last_header = null;
				foreach ( $marker_config as $question_fields ) {
					if ( $question_fields['question_group'] !== $last_header ) {
						printf( '<tr><th>%s</th></tr>', $question_fields['question_group'] );
						$last_header = $question_fields['question_group'];
					}
					printf( '<tr><td><span class="tuja-maps-question">%s</span></td><td>', $question_fields['label'] );

					list ($lat_fieldname, $lat_value)   = $question_fields['fields']['lat'];
					list ($long_fieldname, $long_value) = $question_fields['fields']['long'];
					list ($name_fieldname, $name_value) = $question_fields['fields']['name'];

					printf(
						'<input type="text" readonly name="%s" id="%s" value="%s"/>',
						$lat_fieldname,
						$lat_fieldname,
						$lat_value,
					);
					printf(
						'<input type="text" readonly name="%s" id="%s" value="%s" />',
						$long_fieldname,
						$long_fieldname,
						$long_value,
					);
					printf(
						'<input
							type="text"
							class="tuja-marker-raw-field"
							name="%s"
							id="%s"
							value="%s"
							data-short-label="%s"
							data-lat-field-id="%s"
							data-long-field-id="%s"
							/>',
						$name_fieldname,
						$name_fieldname,
						$name_value,
						$question_fields['short_label'],
						$lat_fieldname,
						$long_fieldname,
					);
					printf( '</td></tr>' );
				}
				?>
			</tbody>
		</table>
	</form>
</div>
<div id="tuja-map-component-container">
	<div id="tuja-map-component-overlay-container">
		<div id="tuja-map-component-overlay"></div>
	</div>
	<div id="tuja-map-component" style="height: 300px"></div>
</div>
</div>
