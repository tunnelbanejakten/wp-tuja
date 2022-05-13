<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<label for="station_name">Namn</label><br>
		<input type="text" name="tuja_station_name" id="tuja_station_name" value="<?php echo $station->name; ?>"/>
	</div>
	<div class="tuja-buttons">
		<button class="button button-primary" type="submit" name="tuja_station_action" value="<?php echo self::ACTION_SAVE; ?>">
			Spara
		</button>
		<button class="button" type="submit" name="tuja_station_action" onclick="return confirm('Är du säker?');" value="<?php echo self::ACTION_DELETE; ?>">
			Ta bort
		</button>
	</div>
</form>
