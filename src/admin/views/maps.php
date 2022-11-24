<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Kartor</h3>
<p>Kartor visar var lagen hittar positionsbaserade uppgifter från formulär och var lagen hittar bemannade stationer.</p>
<p>En tävling kan pågå på flera orter samtidigt och varje ort har då en egen karta. Tänk i så fall på att placera ut samma kontroller på alla kartorna.</p>
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
<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<input type="text" name="tuja_map_name" id="tuja_map_name" placeholder="Namn på ny karta"/><br>
	<button type="submit" class="button" name="tuja_action" value="map_create" id="tuja_map_create_button">
		Skapa ny karta
	</button>
</form>

<h3>Importera</h3>
<?php printf( '<p><a href="%s">Importera kartmarkörer</a></p>', $import_url ); ?>

