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
            <td style="white-space: nowrap;"><?= $group['name'] ?></td>
            <td><?= $group['category'] ?></td>
            <td class="write-line"></td>
            <td class="write-line"></td>
            <td class="write-line"></td>
        </tr>

		<?php
	}
	?>

    </tbody>
</table>