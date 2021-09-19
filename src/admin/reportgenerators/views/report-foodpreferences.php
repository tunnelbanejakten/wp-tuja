<h1>Allergier</h1>

<h2>Summering av svar</h2>

<?php foreach ( $summary as $entry ) { ?>
    <p><?= $entry['label'] ?>: <?= $entry['count'] ?></p>
<?php } ?>

<h2>Alla svar</h2>

<?php foreach ( $rows as $row ) { ?>
    <p><?= $row['value'] ?></p>
<?php } ?>
