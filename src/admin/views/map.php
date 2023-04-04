<?php namespace tuja\admin;

$this->print_root_menu();
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
		<table id="tuja_all_markers" class="tuja-table tuja-admin-table-align-top">
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
				<tr><td colspan="2">
					<p>Så här använder du kartan:</p>
					<ol>
						<li>Klicka på "kartnålen" bredvid frågan/stationen du vill placera ut.</li>
						<li>Klicka på karta där frågan/stationen ska ligga.</li>
						<li>Ge platsen ett namn som underlättar för lagen att hitta dit.</li>
					</ol>
					<p>
						Varje plats får en färg för att den ska vara enklare att<br>
						se nu när du redigerar kartan men denna färg är slumpmässig<br>
						och kommer inte att synas i appen.
					</p>
				</td></tr>
				<?php
				$last_header = null;
				foreach ( $marker_config as $question_fields ) {
					list ($lat_fieldname, $lat_value)   = $question_fields['fields']['lat'];
					list ($long_fieldname, $long_value) = $question_fields['fields']['long'];
					list ($name_fieldname, $name_value) = $question_fields['fields']['name'];

					if ( $question_fields['question_group'] !== $last_header ) {
						printf( '<tr><th>%s</th></tr>', $question_fields['question_group'] );
						$last_header = $question_fields['question_group'];
					}
					printf( '<tr><td><div class="tuja-admin-richtext-preview tuja-admin-richtext-preview-narrow">%s</div></td><td>', $question_fields['label'] );

					$controls_id = 'tuja-map-markers-controls-' . uniqid();
					printf(
						'<div
							id="%s"
							class="tuja-map-marker-controls"
							data-short-label="%s"
							data-name-field-id="%s"
							data-lat-field-id="%s"
							data-long-field-id="%s"
							/>',
						$controls_id,
						$question_fields['short_label'],
						$name_fieldname,
						$lat_fieldname,
						$long_fieldname,
					);

					printf( '<span class="dashicons dashicons-location tuja-map-marker-pin-button"></span>' );
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
							/>',
						$name_fieldname,
						$name_fieldname,
						$name_value,
					);
					printf( '<span class="dashicons dashicons-no tuja-map-marker-delete-button"></span>' );
					printf( '</div></td></tr>' );
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
