<?php
namespace tuja\admin;

use tuja\data\model\payment\GroupPayment;

$this->print_root_menu();
$this->print_menu();
$this->print_leaves_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">

<?php
	printf(
		'
		<table class="tuja-admin-table">
		<thead>
			<tr>
				<th>Belopp</th>
				<th>Notering</th>
				<th>Kopplad?</th>
				<th></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td><input type="number" name="tuja_create_payment_amount" id="tuja_create_payment_amount"></td>
				<td><input type="text" name="tuja_create_payment_note" id="tuja_create_payment_note"></td>
				<td></td>
				<td>
					<button class="button" type="submit" name="tuja_payment_action" value="create_payment">Lägg till</button>
				</td>
			</tr>
		</tfoot>
		<tbody>
		'
	);
	if ( count( $payments ) > 0 ) {
		array_walk(
			$payments,
			function ( GroupPayment $payment ) {
				printf(
					'
			<tr>
				<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td>
					<button class="button" type="submit" name="tuja_payment_action" value="delete_payment__%s">Ta bort</button>
				</td>
			</tr>
                ',
					number_format( $payment->amount / 100, 2, ',', '' ),
					$payment->note,
					$payment->paymenttransaction_id > 0 ? 'Ja, automatiskt registrerad från '. $payment->get_paymenttransaction_description() : 'Nej, manuellt registrerad',
					$payment->id
				);
			}
		);
	} else {
		print( '<tr><td colspan="4">Inga inbetalningar har registrerats.</td></tr>' );
	}
	printf(
		'
		</tbody>
		</table>
	'
	);
	?>

</form>
