<h1>Förväntade inbetalningar för Tunnelbanejakten</h1>
<table>
    <thead>
    <tr>
    <?php
			echo join(
				array_map(
					function ( $column ) {
						return sprintf( '<th>%s</th>', $column );
					},
					array_keys( $groups[0] )
				)
			);
			?>
    </tr>
    </thead>
    <tbody>
    <?php
		foreach ( $groups as $group ) {
			echo '<tr>';
			echo join(
				array_map(
					function ( $column ) {
						return sprintf( '<td>%s</td>', $column );
					},
					array_values( $group )
				)
			);
				echo '</tr>';
		}
		?>
    </tbody>
</table>