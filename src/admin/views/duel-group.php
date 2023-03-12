<?php
namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<label for="<?php echo self::FIELD_DUEL_GROUP_NAME; ?>">Namn</label><br>
		<input type="text" name="<?php echo self::FIELD_DUEL_GROUP_NAME; ?>" id="<?php echo self::FIELD_DUEL_GROUP_NAME; ?>" value="<?php echo $duel_group->name; ?>"/>
	</div>
	<div>
		<label>Kopplad fråga</label><br>
		<?php echo $questions_dropdown; ?>
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
