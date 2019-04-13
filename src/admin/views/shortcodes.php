<?php
namespace tuja\admin;
AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>">
    <p>Anmäl lag</p>
    <code>[tuja_create_group
        competition="<?= $competition->id ?>"
        edit_link_template="<?= get_site_url() ?>/edit-our-signup/%s"
        enable_group_category_selection="no"]</code>

    <p>Redigera anmälning</p>
    <code>[tuja_edit_group
        competition="<?= $competition->id ?>
        enable_group_category_selection="no"]</code>

    <p>Rapportera in poäng som functionär</p>
    <code>[tuja_points
        competition="<?= $competition->id ?>"]</code>

    <p>Rapportera in poäng som funktionär</p>
    <code>[tuja_create_person
        group_id="?"
        edit_link_template="<?= get_site_url() ?>/edit-my-signup/%s"]</code>

	<?php
	foreach ( $forms as $form ) { ?>
        <p>Visa formulär <?= $form->name ?></p>
        <code>[tuja_form form="<?= $form->id ?>"]</code>
	<?php } ?>
</form>
