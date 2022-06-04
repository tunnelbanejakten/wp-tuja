<?php namespace tuja\admin;

use tuja\data\model\question\AbstractQuestion;

AdminUtils::printTopMenu( $competition );
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<?php
	printf( '<p><a href="%s">« Tillbaka till kartöversikten</a></p>', $back_url );
	?>
	<textarea name="tuja_maps_import_raw" style="width: 100%; height: 10em;" placeholder="Klistra in innehållet i KML-filen här..."><?php echo stripslashes( @$_POST['tuja_maps_import_raw'] ); ?></textarea>

	<button type="submit" class="button" name="tuja_action" value="map_import_parse" id="map_import_parse_button">
		Läs in kartfil
	</button>

	<div>
		<h2>Kartor</h2>
		<ul>
			<?php
			foreach ( $map_labels as $name => $count ) {
				printf( '<li>%s (%d kartnålar, id %s)</li>', $name, $count, $existing_maps[ $name ]->id ?? '___' );
			}
			?>
		</ul>
	</div>

	<div>
		<h2>Kartnålar</h2>
		<table>
			<tr>
				<td>Namn</td>
				<td>Antal kartor</td>
				<td>Kopplad fråga</td>
			</tr>
			<?php
			foreach ( $markers_labels as $marker => $count ) {
				$question_selector = sprintf(
					'<select size="1" name="%s">%s</select>',
					'tuja_mapsimport_markerlabel__' . crc32($marker) . '__question',
					join(
						array_map(
							function ( AbstractQuestion $question ) {
								return sprintf( '<option value="%s">%s</option>', $question->id, $question->text );
							},
							$questions
						)
					)
				);
				printf( '<tr><td>%s</td><td>%d st</td><td>%s</td></tr>', var_export( $marker, true ), $count, $question_selector );
			}
			?>
		</table>
	</div>

	<button type="submit" class="button" name="tuja_action" value="map_import_save" id="map_import_save_button">
		Importera
	</button>

</form>
