<?php namespace tuja\admin;

use DateTimeZone;
use tuja\data\store\DuelDao;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Bjud in</h3>
<p>Härifrån bjuder du in till dueller.</p>
<p>Så fungerar det när du bjuder in:</p>
<div class="tuja">
	<ul>
		<li>Alla nuvarande inbjudningar kommer tas bort och ersättas med nya.</li>
		<li>Lag duellerar enbart med lag från samma ort (baserat på deras tilldelade karta)</li>
		<li>Alla lag får en inbjudan till varje "duellgrupp".</li>
		<li>Dueller schemaläggas på "hela timmar utanför lunchtid".</li>
		<li>Du kan ange hur många lag som ska bjudas in till varje duell.</li>
	</ul>
</div>
<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		<label for="tuja_min_duel_participant_count">Antal lag per duell</label> <?php echo AdminUtils::tooltip( 'Två är det klassiska men att bjuda in tre kan vara bra eftersom att om ett lag inte dyker upp så blir det ändå två som kan duellera.' ); ?><br>
		<input type="number" min="2" max="5" step="1" value="<?php echo $_POST['tuja_min_duel_participant_count'] ?? '2'; ?>" name="tuja_min_duel_participant_count" id="tuja_min_duel_participant_count" style="width: 10em"/>
	</div>
	
	<div>
		<button type="submit" class="button" name="tuja_action" onclick="return confirm('Existerande inbjudningar kommer ersättas med nya. Är du säker på att du vill generera nya inbjudningar?');" value="create_duels" id="tuja_create_duels_button">
			Bjud in till dueller
		</button>
	</div>
</form>
<div class="tuja">
<?php
foreach ( $duel_groups as $duel_group ) {
	printf( '<p>Duellgrupp <strong>%s</strong></p>', $duel_group->name );
	printf( '<ul class="tuja-no-bullets-list">' );
	foreach ( $duel_group->duels as $duel ) {
		if ( count( $duel->invites ) > 0 ) {
			printf(
				'<li>Duell %d kl. %s<ul class="tuja-no-bullets-list">',
				$duel->id,
				$duel->duel_at->setTimezone( new DateTimeZone( wp_timezone_string() ) )->format( 'H:i' )
			);
			foreach ( $duel->invites as $invite ) {
				$id = uniqid();
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
	printf( '</ul></li>' );
}
?>
</div>
