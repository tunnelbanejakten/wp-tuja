<?php namespace tuja\admin;

use tuja\data\model\payment\PaymentTransaction;

$this->print_root_menu();
$this->print_menu();
?>

<table class="tuja-table">
	<thead>
		<td>key</td>
		<td>transaction_time</td>
		<td>message</td>
		<td>sender</td>
		<td>amount</td>
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
							<td>%s</td>
							<td>%s</td>
						</tr>',
						$transaction->key,
						$transaction->transaction_time->format( 'c' ),
						$transaction->message,
						$transaction->sender,
						$transaction->amount
					);
				}
			);
		}
		?>
</tbody>
</table>
