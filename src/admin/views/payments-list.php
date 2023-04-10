<?php namespace tuja\admin;

use tuja\data\model\payment\PaymentTransaction;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table id="tuja_transctions_list" class="tuja-table">
		<thead>
		<tr>
			<th>Tidpunkt</th>
			<th>Meddelande</th>
			<th>Avsändare</th>
			<th>Belopp</th>
			<!-- <th>Id</th> -->
		</tr>
		</thead>
		<tbody>
		<?php
		array_walk(
			$transaction,
			function ( PaymentTransaction $transaction ) {
				printf(
					'
				<tr>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s (varav %s allokerat till lag)</td>
					<!-- <td>%s</td> -->
				</tr>
				',
					$transaction->transaction_time->format( 'c' ),
					$transaction->message,
					$transaction->sender,
					number_format( $transaction->amount / 100, 2, ',', '' ),
					number_format( $transaction->groups_attribution_sum / 100, 2, ',', '' ),
					$transaction->key,
				);
			}
		);
		?>
		</tbody>
	</table>
</form>