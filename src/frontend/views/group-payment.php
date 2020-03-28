<?php

use tuja\util\Strings;

?>
<p id="tuja-payment-body">
	<?php printf( Strings::get( 'groups_payment.to_pay', number_format( $amount, 2, ',', ' ' ) ) ); ?>
</p>
<p>
	<?php printf( Strings::get( 'groups_payment.fee_model', $description, $email_link ) ); ?>
</p>

<h2><?= Strings::get( 'groups_payment.swish.header' ) ?></h2>
<p><?= Strings::get( 'groups_payment.swish.body_text' ) ?></p>
<?php
if ( ! empty( $swish_qr_code_image_url ) ) {
	printf( '<div class="tuja-swish-qr-code-wrapper"><img class="tuja-swish-qr-code" src="%s" alt="QR-kod som kan scannas av Swish-appen"></div>', $swish_qr_code_image_url );
}
?>
<table class="tuja-swish-details">
    <tbody>
    <tr>
        <td><?= Strings::get('groups_payment.swish.payee') ?></td>
        <td><?= $swish_payee ?></td>
    </tr>
    <tr>
        <td><?= Strings::get('groups_payment.swish.message') ?></td>
        <td><?= $swish_message ?></td>
    </tr>
    <tr>
        <td><?= Strings::get('groups_payment.swish.amount') ?></td>
        <td><?= number_format( $amount, 2, ',', ' ' ) ?></td>
    </tr>
    </tbody>
</table>

<h2><?= Strings::get( 'groups_payment.giro.header' ) ?></h2>
<p><?= Strings::get( 'groups_payment.giro.body_text', $email_link ) ?></p>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
</p>
