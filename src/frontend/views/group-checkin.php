<?php

use tuja\util\Strings;

?>
<p><strong><?= Strings::get( 'checkin.title' ) ?></strong></p>
<div class="tuja-message tuja-message-info">

    <p><?= Strings::get( 'checkin.data.group_name', $group->name ) ?></p>
    <p><?= Strings::get( 'checkin.data.group_category', $category->name ) ?></p>

	<?php

	use tuja\data\model\Person;

	if ( ! empty( $competing ) ) {
		printf( '<p>%s</p>', Strings::get( 'checkin.data.participants.title' ) );
		print '<ul>';
		print join( array_map( function ( Person $person ) {
			if ( $person->is_contact() ) {
				return sprintf( '<li>%s, %s</li>', $person->name, $person->phone );
			} else {
				return sprintf( '<li>%s</li>', $person->name );
			}
		}, $competing ) );
		print '</ul>';
	}
	if ( ! empty( $adult_supervisors ) ) {
		printf( '<p>%s</p>', Strings::get( 'checkin.data.adult_supervisors.title' ) );
		print '<ul>';
		print join( array_map( function ( Person $person ) {
			if ( $person->is_contact() ) {
				return sprintf( '<li>%s, %s</li>', $person->name, $person->phone );
			} else {
				return sprintf( '<li>%s</li>', $person->name );
			}
		}, $adult_supervisors ) );
		print '</ul>';
	}
	?>
    <p><?= Strings::get( 'checkin.data.contacts_note' ) ?></p>
</div>
<form method="post">
	<?= $form ?>

	<?= $submit_button ?>
</form>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
</p>
