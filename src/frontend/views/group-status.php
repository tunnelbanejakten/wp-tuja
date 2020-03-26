<?php if ( ! empty( $blockers ) ) { ?>
    <p>Problem som ni behöver fixa för att göra er anmälan komplett:</p>
    <ul>
		<?php
		foreach ( $blockers as $rule_result ) {
			printf( '<li class="tuja-group-status-blocker-message"><strong>%s</strong>: %s</li>', $rule_result->rule_name, $rule_result->details );
		}
		?>
    </ul>
<?php } ?>
<?php if ( ! empty( $warnings ) ) { ?>
    <p>Inte nödvändigtvis problem men ändå saker att dubbelkolla:</p>
    <ul>
		<?php
		foreach ( $warnings as $rule_result ) {
			printf( '<li class="tuja-group-status-warning-message"><strong>%s</strong>: %s</li>', $rule_result->rule_name, $rule_result->details );
		}
		?>
    </ul>
<?php } ?>
<p>
    <small>
		<?php printf( 'Uppdatera er anmälan på sidan för <a href="%s" id="tuja_edit_group_link">lagets namn och tävlingsklass</a> och på sidan för <a href="%s" id="tuja_edit_people_link">deltagare och kontaktpersoner</a>.', $edit_group_link, $edit_people_link ) ?>
    </small>
</p>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
</p>
