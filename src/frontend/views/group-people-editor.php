<form method="post">
    <h1>Laguppställningen</h1>

    <?= $errors_overall ?>

    <h2>Lagledare</h2>
    <p>
        <small>Lagledaren får information via e-post <em>innan</em> tävlingen och via SMS <em>under</em> tävlingen. I övrigt är lagledaren som vem som helst i laget.</small>
    </p>

    <?= $form_group_contact ?>

    <h2>Resten av laget</h2>
    <p>
        <small>Ni kan ha 3-7 lagmedlemmar, utöver lagledaren.</small>
    </p>

    <?= $form_group_members ?>

    <?php if ( ! empty( $form_adult_supervisor ) ) { ?>
        <h2>Vuxen ledare</h2>
        <p>
            <small>I denna tävlingsklass kräver vi att en vuxen ledare går med under dagen. Denna ledare är inte med och tävlar och betalar därför ingen deltagaravgift, men får förstås gratis fika efter tävlingen ändå.</small>
        </p>

        <?= $form_adult_supervisor ?>

    <?php } ?>

    <h2>Extra kontaktperson</h2>
    <p>
        <small>Här kan du ange om vi ska skicka ut information inför tävlingen till någon mer än bara lagledaren. Detta kan vara användbart om lagets anmälan administreras av någon som inte kommer vara med i tävlingen.</small>
    </p>

	<?= $form_extra_contact ?>

    <?= $form_save_button ?>

    <p>
        <?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
    </p>
</form>