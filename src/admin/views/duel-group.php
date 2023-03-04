<?php
namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<label for="duel_group_name">Namn</label><br>
		<input type="text" name="tuja_duel_group_name" id="tuja_duel_group_name" value="<?php echo $duel_group->name; ?>"/>
	</div>
	<div class="tuja-buttons">
		<button class="button button-primary" type="submit" name="tuja_duel_group_action" value="<?php echo self::ACTION_SAVE; ?>">
			Spara
		</button>
		<button class="button" type="submit" name="tuja_duel_group_action" onclick="return confirm('Är du säker?');" value="<?php echo self::ACTION_DELETE; ?>">
			Ta bort
		</button>
	</div>
</form>
