<?php

namespace tuja\admin;

const TUJA_OPTION_FIELD_NAME_PREFIX = 'tuja_option__';

$form_values = array_filter($_POST, function ($key) {
    return substr($key, 0, strlen(TUJA_OPTION_FIELD_NAME_PREFIX)) === TUJA_OPTION_FIELD_NAME_PREFIX;
}, ARRAY_FILTER_USE_KEY);

foreach ($form_values as $field_name => $field_value) {
    $option_name = substr($field_name, strlen(TUJA_OPTION_FIELD_NAME_PREFIX));
    update_option($option_name, $field_value);
}

function tuja_print_option_row($label, $option_name)
{
    $field_name = TUJA_OPTION_FIELD_NAME_PREFIX . $option_name;
    $field_value = $_POST[$field_name] ?: get_option($option_name);
    printf('
        <tr>
            <th scope="row"><label for="%s">%s</label></th>
            <td>
                <input name="%s" id="%s" value="%s" class="regular-text">
            </td>
        </tr>', $field_name, $label, $field_name, $field_name, $field_value);
}
?>
<form method="post" action="<?= add_query_arg() ?>">
    <h1>Generella inställningar</h1>

    <h2 class="title">ReCAPTCHA</h2>

    <p>Om du har registrerat hemsidan för att använda reCAPTCHA så skyddar det mot att robotar registrera sig som lag,
        vilket i sin tur motverkar att hemsidan används för att skicka e-post till intet ont anade personer.</p>


    <table class="form-table">
        <tbody>
        <?php tuja_print_option_row('Site key', 'tuja_recaptcha_sitekey') ?>
        <?php tuja_print_option_row('Site secret', 'tuja_recaptcha_sitesecret') ?>
        </tbody>
    </table>

    <button type="submit" name="tuja_action" value="save">Spara</button>
</form>
