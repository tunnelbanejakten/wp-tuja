<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

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
	$ticketing_url = add_query_arg( array(
		'tuja_view' => 'StationsTicketing'
	) );
	printf( '<p><a href="%s">Biljettsystem</a></p>', $ticketing_url );
	?>
</form>
