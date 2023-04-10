<?php namespace tuja\admin;

use tuja\controller\payments\MatchPaymentResult;
use tuja\data\model\payment\PaymentTransaction;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table id="tuja_transctions_list" class="tuja-table tuja-admin-table-align-top">
		<thead>
		<tr>
			<th>Datum</th>
			<th>Meddelande</th>
			<th>Avsändare</th>
			<th>Belopp</th>
			<th>Åtgärd</th>
		</tr>
		</thead>
		<tbody>
		<?php
		array_walk(
			$match_result->transactions,
			function ( MatchPaymentResult $result ) use ( $actions_by_transaction_key ) {
				$amount_major_unit = $result->transaction->amount / 100;
				printf(
					'
				<tr>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
				</tr>
				',
					$result->transaction->transaction_time->format( 'Y-m-d' ),
					$result->transaction->message,
					$result->transaction->sender,
					number_format( $amount_major_unit, 2, ',', '' ),
					$actions_by_transaction_key[ $result->transaction->key ]
				);
			}
		);
		?>
		</tbody>
	</table>
	<button type="submit" class="button button-primary" name="tuja_action" value="<?php echo self::ACTION_NAME_START; ?>" id="tuja_save_button">
		Länka
	</button>
</form>
