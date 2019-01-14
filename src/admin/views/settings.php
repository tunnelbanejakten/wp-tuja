<?php namespace tuja\admin; ?>

<form method="post" action="<?= add_query_arg() ?>">
    <h1>Generella inställningar</h1>

    <h2 class="title">ReCAPTCHA</h2>

    <p>Om du har registrerat hemsidan för att använda reCAPTCHA så skyddar det mot att robotar registrera sig som lag,
        vilket i sin tur motverkar att hemsidan används för att skicka e-post till intet ont anade personer.</p>


    <table class="form-table">
        <tbody>
        <?php $this->print_option_row('Site key', 'tuja_recaptcha_sitekey') ?>
        <?php $this->print_option_row('Site secret', 'tuja_recaptcha_sitesecret') ?>
        </tbody>
    </table>

    <button type="submit" name="tuja_action" value="save">Spara</button>
</form>
