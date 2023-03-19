<h1>Allergier</h1>

<h2>Summering av svar</h2>

<table>
	<?php foreach ( $summary as $entry ) { ?>
		<tr>
			<td><?php echo $entry['count']; ?> pers</td>
			<td>är allergiska mot <?php echo $entry['label']; ?></td>
		</tr>
	<?php } ?>
</table>
<p>
	Notera att personer som har angett flera allergier inkluderas både i antalen för 
	respektive allergen <em>och</em> i antalet för kombinationen.
</p>

<?php if ( $list_all ) { ?>
	<h2>Alla svar</h2>
	
	<?php foreach ( $rows as $row ) { ?>
		<p><?php echo $row['value']; ?></p>
	<?php } ?>
<?php } ?>
