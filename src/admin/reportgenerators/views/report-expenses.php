<h1>Utlägg</h1>
<table>
	<thead>
	<tr>
		<th>Id</th>
		<th>Beskrivning</th>
		<th>Belopp</th>
		<th>Datum</th>
		<th>Namn</th>
		<th>Epost</th>
		<th>Bankkonto</th>
	</tr>
	</thead>
	<tbody>
		<?php foreach ( $expense_reports as $expense_report ) { ?>
			<tr>
				<td><?php echo $expense_report['random_id']; ?></td>
				<td><?php echo $expense_report['description']; ?></td>
				<td><?php echo $expense_report['amount']; ?></td>
				<td><?php echo $expense_report['date']; ?></td>
				<td><?php echo $expense_report['name']; ?></td>
				<td><?php echo $expense_report['email']; ?></td>
				<td><?php echo $expense_report['bank_account']; ?></td>
			</tr>
		<?php } ?>
	</tbody>
</table>
