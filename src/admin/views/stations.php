<?php namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
	<?php
	foreach ( $stations as $station ) {
		$url = add_query_arg( array(
			'tuja_view' => 'Station',
			'tuja_form' => $station->id
		) );
		printf( '<p><a href="%s" data-id="%d" data-key="%s">%s</a></p>', $url, $station->id, $station->random_id, $station->name );
	}
	?>
    <input type="text" name="tuja_station_name" id="tuja_station_name"/>
    <button type="submit" class="button" name="tuja_action" value="station_create" id="tuja_station_create_button">
        Skapa
    </button>
	<?php
	printf( '<p><a href="%s">Biljettsystem</a></p>', $ticketing_url );
	printf( '<p><a href="%s">Poäng</a></p>', $points_url );
	?>
</form>
