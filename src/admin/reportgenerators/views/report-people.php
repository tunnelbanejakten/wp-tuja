<h1>Personer</h1>
<?php
if ( count( $people ) > 0 ) {
	?>
	<table>
		<thead>
		<tr>
			<?php
			echo join(
				array_map(
					function ( $column ) {
						return sprintf( '<th>%s</th>', $column );
					},
					array_keys( current( $people ) )
				)
			);
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $people as $person ) {
			echo '<tr>';
			echo join(
				array_map(
					function ( $column ) {
						return sprintf( '<td>%s</td>', $column );
					},
					array_values( $person )
				)
			);
				echo '</tr>';
		}
		?>

		</tbody>
	</table>
	<p>Totalt: <?php echo count( $people ); ?> personer.</p>
	<?php
}
?>
