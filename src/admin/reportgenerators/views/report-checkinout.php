<h1>In- och utcheckning</h1>
<table>
	<thead>
	<tr>
		<th style="width: 20%"></th>
		<th style="width: 20%"></th>
		<th style="width: 10%">Incheckad</th>
		<th style="width: 10%">Utcheckad</th>
		<th style="width: 40%">Notering</th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $groups as $group ) {
		?>

		<tr class="print-form">
			<td style="white-space: nowrap;">
				<?php
				echo $group['name'];
				if ( $group['referral_count'] > 0 ) {
					printf(
						' <span id="group-referral-count-%s">(%s funk)</span>',
						$group['key'],
						$group['referral_count']
					);
				}
				?>
			</td>
			<td><?php echo $group['category']; ?></td>
			<td class="write-line"></td>
			<td class="write-line"></td>
			<td class="write-line"></td>
		</tr>

		<?php
	}
	?>

	</tbody>
</table>
