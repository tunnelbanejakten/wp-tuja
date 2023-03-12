<?php namespace tuja\admin;

use DateTimeZone;
use tuja\data\store\DuelDao;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Dueller</h3>
<?php
foreach ( $duel_groups as $duel_group ) {
	$url = add_query_arg(
		array(
			'tuja_view'       => 'DuelGroup',
			'tuja_duel_group' => $duel_group->id,
		)
	);
	printf( '<p><a href="%s" data-id="%d">%s</a></p>', $url, $duel_group->id, $duel_group->name );
}
?>
<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<input type="text" name="tuja_duel_group_name" id="tuja_duel_group_name" placeholder="Namn"/>
		<button type="submit" class="button" name="tuja_action" value="create_duel_group" id="tuja_create_duel_group_button">
			Skapa ny duellgrupp
		</button>
	</div>
</form>
