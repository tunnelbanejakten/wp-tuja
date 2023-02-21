<?php namespace tuja\admin;

use tuja\data\store\DuelDao;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Dueller</h3>
<?php
// print '<pre>';
// print_r( $duel_groups );
// print '</pre>';
foreach ( $duel_groups as $duel_group ) {
	printf(
		'<p><strong>%s</strong></p>',
		$duel_group->name,
	);
	printf( '<ul>' );
	foreach ( $duel_group->duels as $duel ) {
		if ( count( $duel->invites ) > 0 ) {
			printf( '<li>Duell %d<ul>', $duel->id);
			foreach ( $duel->invites as $invite ) {
				$id              = uniqid();
				$group_duel_data = ( new DuelDao() )->get_duels_by_group( $invite->group );
				$tooltip         = AdminUtils::tooltip( sprintf( '<pre style="font-size: 10px; line-height: 12px;">%s</pre>', print_r( $group_duel_data, true ) ) );
				printf(
					'<li><input type="checkbox" name="tuja_duel_invite[]" value="%s" id="%s"><label for="%s">%s (%s)</label>%s</input></li>',
					$invite->random_id,
					$id,
					$id,
					$invite->group->name,
					$invite->status,
					$tooltip,
				);
			}
			printf( '</ul></li>' );
		}
	}
	printf( '</ul>' );
}
?>
<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<input type="text" name="tuja_duel_group_name" id="tuja_duel_group_name" placeholder="Namn på ny duellgrupp"/><br>
		<button type="submit" class="button" name="tuja_action" value="create_duel_group" id="tuja_create_duel_group_button">
			Skapa ny duellgrupp
		</button>
	</div>

	<div>
		<?php echo $duel_group_selector; ?>
		<?php echo $group_set_selector; ?>

		<input type="number" min="2" max="5" step="1" value="<?php echo $_POST['tuja_min_duel_participant_count'] ?? '2'; ?>" name="tuja_min_duel_participant_count" id="tuja_min_duel_participant_count" placeholder="Antal lag per duell" style="width: 10em"/>

		<button type="submit" class="button" name="tuja_action" value="create_duels" id="tuja_create_duels_button">
			Bjud in till dueller
		</button>
	</div>
</form>
