<form method="post">
    <h1>Namn och tävlingsklass</h1>

    <?= $errors_overall ?>

    <?= $form_group ?>

    <?= $form_save_button ?>
    <p>
        <?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
    </p>
</form>