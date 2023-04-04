<?php namespace tuja\admin;

$this->print_root_menu();
// $this->print_menu();
$this->print_leaves_menu();

// printf( '<pre>%s</pre>', var_export( $score_board, true ) );
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
<table id="tuja_all_markers" class="tuja-table tuja-admin-table-align-top">
<thead>
	<tr>
		<td></td>
		<?php
		print join(
			array_map(
				function( $column_label ) {
					return sprintf( '<td class="tuja-rotated-header"><span>%s</span></td>', $column_label );
				},
				$column_labels
			)
		);
		?>
	</tr>
</thead>
<tbody>
	<?php
	$render_fields = function ( $fields ) {
		foreach ( $fields as $value ) {
			$extra_class = 0 == $value ? 'zero-value' : '';
			printf(
				'<td class="numeric-value %s">%s</td>',
				$extra_class,
				$value
			);
		}
	};
	printf( '<tr><th>Övergripande:</th></tr>' );
	foreach ( $overall_fields as $overall_field ) {
		printf( '<tr><td><span class="tuja-scoreboard-details-question">%s</span></td>', $overall_field['label'] );
		$render_fields( $overall_field['fields'] );
		printf( '</tr>' );
	}
	printf( '<tr><th>Frågor:</th></tr>' );
	$last_header = null;
	foreach ( $questions_fields as $question_fields ) {
		if ( $question_fields['question_group'] !== $last_header ) {
			printf( '<tr><td>%s</td></tr>', $question_fields['question_group'] );
			$last_header = $question_fields['question_group'];
		}
		printf( '<tr><td><div class="tuja-admin-richtext-preview">%s</div></td>', $question_fields['label'] );
		$render_fields( $question_fields['fields'] );
		printf( '</tr>' );
	}
	printf( '<tr><th>Stationer:</th><td colspan="%d"><a href="%s">Ändra poäng för stationer...</a></td></tr>', count( $column_labels ), $stations_points_url );
	foreach ( $stations_fields as $station_fields ) {
		printf( '<tr><td><span class="tuja-scoreboard-details-question">%s</span></td>', $station_fields['label'] );
		$render_fields( $station_fields['fields'] );
		printf( '</tr>' );
	}
	printf( '<tr><th>Bonuspoäng:</th><td colspan="%d"><a href="%s">Ändra bonuspoäng...</a></td></tr>', count( $column_labels ), $extra_points_url );
	foreach ( $extras_fields as $extra_fields ) {
		printf( '<tr><td><span class="tuja-scoreboard-details-question">%s</span></td>', $extra_fields['label'] );
		$render_fields( $extra_fields['fields'] );
		printf( '</tr>' );
	}
	?>
</tbody>
</table>
</form>
