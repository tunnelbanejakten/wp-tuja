<?php namespace tuja\admin;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

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

<?php
printf( '<p><a href="%s">Dela ut poäng</a></p>', $points_url );
printf( '<p><a href="%s">Hantera biljetter</a></p>', $manage_tickets_url );
printf( '<p><a href="%s">Konfigurera biljettsystem</a></p>', $ticketing_url );
