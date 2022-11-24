<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_leaves_menu();
?>

<p>Stationer, även kallat "kontroller", är tänkt att represtera platser där det finns funktionärer som berättar vad det är för uppgift som lagen ska utföra.</p>
<p>En station har ingen beskrivning (bara ett namn), eftersom funktionären på plats förklarar uppgiften för laget.</p>

<h3>Stationer</h3>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<?php
	foreach ( $stations as $station ) {
		$url = add_query_arg(
			array(
				'tuja_view'    => 'Station',
				'tuja_station' => $station->id,
			)
		);
		printf( '<p><a href="%s" data-id="%d" data-key="%s">%s</a></p>', $url, $station->id, $station->random_id, $station->name );
	}
	?>
	<input type="text" name="tuja_station_name" id="tuja_station_name"/>
	<button type="submit" class="button" name="tuja_action" value="station_create" id="tuja_station_create_button">
		Skapa
	</button>
</form>

<h3>Biljetter och poäng</h3>

<p>Lagen erhåller poäng för avklarade stationer. Poängen skrivs vanligtvis in av funktionärerna.</p>
<p>För att minska risken för köbildning vid stationer nära starten så får lagen biljetter. Varje biljett gäller för en viss station. Mer information om biljettsystemet hittar du på sidan <em>Konfigurera biljettsystem</em>.</p>

<?php
printf( '<p><a href="%s">Dela ut poäng</a></p>', $points_url );
printf( '<p><a href="%s">Hantera biljetter</a> %s</p>', $manage_tickets_url, AdminUtils::tooltip( 'Här kan du dela ut specifika biljetter till specifika lag om den automatiska biljettutdelningen inte fungerar som den ska.' ) );
printf( '<p><a href="%s">Konfigurera biljettsystem</a> %s</p>', $ticketing_url, AdminUtils::tooltip( 'Styr hur biljetterna ska se ut, vilka lösenord som ska anges efter avklarad station, och hur sannolikt det är att biljetter för en annan station delas ut efter att ett visst lösenord angetts av lagen.' ) );
