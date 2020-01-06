<?php

use tuja\util\Strings;

?>
<form method="post">
	<?= $errors_overall ?>

	<?= Strings::get( 'group_people_editor.group_contact.header' ) ?>
    <p>
        <small><?= Strings::get( 'group_people_editor.group_contact.description' ) ?></small>
    </p>

	<?= $form_group_contact ?>

    <h2><?= Strings::get( 'group_people_editor.group_members.header' ) ?></h2>
    <p>
        <small><?= Strings::get( 'group_people_editor.group_members.description', $group_size_min - 1, $group_size_max - 1 ) ?></small>
    </p>

	<?= $form_group_members ?>

	<?php if ( ! empty( $form_adult_supervisor ) ) { ?>
        <h2><?= Strings::get( 'group_people_editor.adult_supervisors.header' ) ?></h2>
        <p>
            <small><?= Strings::get( 'group_people_editor.adult_supervisors.description', $category->name ) ?></small>
        </p>

		<?= $form_adult_supervisor ?>

	<?php } ?>

    <h2><?= Strings::get( 'group_people_editor.extra_contacts.header' ) ?></h2>

    <p>
        <small><?= Strings::get( 'group_people_editor.extra_contacts.description' ) ?></small>
    </p>

	<?= $form_extra_contact ?>

	<?= $form_save_button ?>

    <p>
		<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
    </p>
</form>