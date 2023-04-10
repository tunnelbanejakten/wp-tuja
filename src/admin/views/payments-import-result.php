<?php namespace tuja\admin;

use tuja\data\model\payment\PaymentTransaction;

$this->print_root_menu();
$this->print_menu();
?>

<h2>Importerade transaktioner</h2>

<table class="tuja-table">
	<thead>
		<th>Datum</th>
		<th>Meddelande</th>
		<th>Avsändare</th>
		<th>Belopp</th>
	</thead>
	<tbody>
		<?php
		if ( false !== $transactions ) {
			array_walk(
				$transactions,
				function ( PaymentTransaction $transaction ) {
					printf(
						'<tr>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s kr</td>
						</tr>',
						$transaction->transaction_time->format( 'Y-m-d' ),
						$transaction->message,
						$transaction->sender,
						number_format_i18n( $transaction->amount / 100 ),
					);
				}
			);
		}
		?>
</tbody>
</table>
