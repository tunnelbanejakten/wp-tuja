<h1>Meddelanden till tävlingsledningen</h1>

<?php
$last_group_name = null;
foreach ( $rows as $entry ) {
	if ( $entry['group'] !== $last_group_name ) {
		printf( '<h3>%s</h3>', $entry['group'] );
		$last_group_name = $entry['group'];
	}
	if ( empty( $entry['person'] ) ) {
		printf( '<p><em>%s</em></p>', $entry['note'] );
	} else {
		printf( '<p>%s: <em>%s</em></p>', $entry['person'], $entry['note'] );
	}
}
?>
