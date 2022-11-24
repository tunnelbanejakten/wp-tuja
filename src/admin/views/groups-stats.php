<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>
<h3>Statistik</h3>

<table class="tuja-table">
    <tbody>
    <tr>
        <td>Antal tävlande personer:</td>
        <td><?= $people_competing + $people_following ?> st</td>
    </tr>
    <tr>
        <td style="padding-left: 2em">varav tävlande:</td>
        <td><?= $people_competing ?> st</td>
    </tr>
    <tr>
        <td style="padding-left: 2em">varav vuxna som följer med:</td>
        <td><?= $people_following ?> st</td>
    </tr>
    <tr>
        <td>Antal tävlande lag:</td>
        <td><?= $groups_competing ?> st</td>
    </tr>
	<?php foreach ( $groups_per_category as $name => $count ) { ?>
        <tr>
            <td style="padding-left: 2em">varav i kategori <?= $name ?>:</td>
            <td><?= $count ?> st</td>
        </tr>
	<?php } ?>
    </tbody>
</table>
