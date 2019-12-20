<?php
namespace tuja\admin;
AdminUtils::printTopMenu( $competition );

use tuja\frontend\router\CompetitionSignupInitiator;

?>

<form method="post" action="<?= add_query_arg() ?>" class="tuja">
    <p>
        Anmäl lag:
		<?php
		$link = CompetitionSignupInitiator::link( $competition );
		printf( '<a href="%s" target="_blank" id="tuja_shortcodes_competitionsignup_link">%s</a>', $link, $link )
		?>
    </p>

    <p>Rapportera in poäng som funktionär</p>
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
