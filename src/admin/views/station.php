<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<h3>Station <?php echo htmlspecialchars( $station->name ); ?> (id: <code><?php echo htmlspecialchars( $station->random_id ); ?></code>)</h3>

<?php printf( '<p><a id="tuja_station_back" href="%s">« Tillbaka till stationslistan</a></p>', $back_url ); ?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<label for="station_name">Namn</label><br>
		<input type="text" name="tuja_station_name" id="tuja_station_name" value="<?php echo $station->name; ?>"/>
	</div>
	<div class="tuja-buttons">
		<button class="button button-primary" type="submit" name="tuja_station_action" value="save">
			Spara
		</button>
	</div>
</form>
