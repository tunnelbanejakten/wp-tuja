<?php namespace tuja\admin;

use DateTimeZone;
use tuja\data\store\DuelDao;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Dueller</h3>
<?php
// print '<pre>';
// print_r( $duel_groups );
// print '</pre>';
// print '<pre>';
// foreach ( $events as $event ) {
// 	printf( '%s<br>', $event->format( 'c' ) );
// }
// print_r( $events );
// print '</pre>';
foreach ( $duel_groups as $duel_group ) {
	$url = add_query_arg(
		array(
			'tuja_view'       => 'DuelGroup',
			'tuja_duel_group' => $duel_group->id,
		)
	);
	printf( '<p><a href="%s" data-id="%d">%s</a></p>', $url, $duel_group->id, $duel_group->name );
	printf( '<ul>' );
	foreach ( $duel_group->duels as $duel ) {
		if ( count( $duel->invites ) > 0 ) {
			printf(
				'<li>Duell %d kl. %s<ul>',
				$duel->id,
				$duel->duel_at->setTimezone( new DateTimeZone( wp_timezone_string() ) )->format( 'H:i' )
			);
			foreach ( $duel->invites as $invite ) {
				$id              = uniqid();
				printf(
					'<li><input type="checkbox" name="tuja_duel_invite[]" value="%s" id="%s"><label for="%s">%s</label></input></li>',
					$invite->random_id,
					$id,
					$id,
					$invite->group->name,
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
		<input type="text" name="tuja_duel_group_name" id="tuja_duel_group_name" placeholder="Namn på ny duellgrupp"/>
		<button type="submit" class="button" name="tuja_action" value="create_duel_group" id="tuja_create_duel_group_button">
			Skapa ny duellgrupp
		</button>
	</div>

	<div>
		<input type="number" min="2" max="5" step="1" value="<?php echo $_POST['tuja_min_duel_participant_count'] ?? '2'; ?>" name="tuja_min_duel_participant_count" id="tuja_min_duel_participant_count" placeholder="Antal lag per duell" style="width: 10em"/>

		<button type="submit" class="button" name="tuja_action" value="create_duels" id="tuja_create_duels_button">
			Bjud in till dueller
		</button>
	</div>
</form>
