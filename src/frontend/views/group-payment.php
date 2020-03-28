<?php

use tuja\util\Strings;

?>
<p id="tuja-payment-body">
	<?php printf( Strings::get( 'groups_payment.to_pay', number_format( $amount, 2, ',', ' ' ) ) ); ?>
</p>
<p>
    <small><?php printf( Strings::get( 'groups_payment.fee_model', $description, $email_link ) ); ?></small>
</p>

<h2><?= Strings::get( 'groups_payment.swish.header' ) ?></h2>
<p><?= Strings::get( 'groups_payment.swish.body_text' ) ?></p>
<?php
if ( ! empty( $swish_qr_code_image_url ) ) {
	printf( '<div class="tuja-swish-qr-code-wrapper"><img class="tuja-swish-qr-code" src="%s" alt="QR-kod som kan scannas av Swish-appen"></div>', $swish_qr_code_image_url );
}
?>
<div class="tuja-swish-details">
    <div>
        <div><?= Strings::get( 'groups_payment.swish.payee' ) ?></div>
        <div><?= $swish_payee ?></div>
    </div>
    <div>
        <div><?= Strings::get( 'groups_payment.swish.message' ) ?></div>
        <div><?= $swish_message ?></div>
    </div>
    <div>
        <div><?= Strings::get( 'groups_payment.swish.amount' ) ?></div>
        <div><?= $amount ?></div>
    </div>
</div>

<h2><?= Strings::get( 'groups_payment.giro.header' ) ?></h2>
<p><?= Strings::get( 'groups_payment.giro.body_text', $email_link ) ?></p>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
</p>
