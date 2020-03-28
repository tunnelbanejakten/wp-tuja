<h1>Förväntade inbetalningar för Tunnelbanejakten</h1>
<table>
    <thead>
    <tr>
        <th style="width: 50%">Lag</th>
        <th style="width: 40%">Referens</th>
        <th style="width: 10%">Belopp</th>
    </tr>
    </thead>
    <tbody>
	<?php foreach ( $groups as $group ) { ?>

        <tr>
            <td>
				<?= $group['name'] ?>
                <br>Tävlande: <?= $group['count_competing'] ?> pers (<?= join( ', ', $group['people'] ) ?>)
                <br>Övriga: <?= $group['count_follower'] ?> pers
            </td>
            <td><?= $group['reference'] ?></td>
            <td><?= number_format( $group['amount'], 2, ',', ' ' ) ?></td>
        </tr>

	<?php } ?>

    </tbody>
</table>