<?php

use tuja\util\Strings;

?>
<p id="tuja-payment-body">
	<?php printf( Strings::get( 'groups_payment.to_pay', number_format( $fee_amount, 2, ',', ' ' ) ) ); ?>
</p>
<p>
	<small><?php printf( Strings::get( 'groups_payment.fee_model', $fee_description, $email_link ) ); ?></small>
</p>
<?php
if ( $is_fee_fully_paid ) {
	printf(
		'<p class="tuja-message tuja-message-success">%s</p>',
		Strings::get( 'groups_payment.fee_fully_paid' )
	);
}
?>

<?php echo $payment_options; ?>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ); ?>
</p>
