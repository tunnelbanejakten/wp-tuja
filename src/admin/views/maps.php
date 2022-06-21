<?php namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
$this->print_menu();
?>

<?php
foreach ( $maps as $map ) {
	$link = add_query_arg(
		array(
			'tuja_competition' => $this->competition->id,
			'tuja_view'        => 'Map',
			'tuja_map'         => $map->id,
		)
	);
	printf(
		'<p><a href="%s" class="tuja-map-link">%s</a></p>',
		$link,
		$map->name,
	);
}
?>

<h3>Importera</h3>
<?php printf( '<p><a href="%s">Importera kartmarkörer</a></p>', $import_url ); ?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<h3>Ny karta</h3>
	<input type="text" name="tuja_map_name" id="tuja_map_name" placeholder="Namn på ny karta"/><br>
	<button type="submit" class="button" name="tuja_action" value="map_create" id="tuja_map_create_button">
		Lägg till
	</button>
</form>
