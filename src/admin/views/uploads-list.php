<?php namespace tuja\admin;

use tuja\data\model\payment\PaymentTransaction;
use tuja\data\model\Upload;
use tuja\util\ImageManager;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table id="tuja_uploads_list" class="tuja-table">
		<thead>
		<tr>
			<th>Id</th>
			<th>Favorit?</th>
			<th>Datum</th>
			<th>Förhandsgranskning</th>
			<th>Versioner</th>
			<th>Fråga</th>
			<th>Aktuell?</th>
		</tr>
		</thead>
		<tbody>
		<?php
		array_walk(
			$uploads,
			function ( Upload $upload ) use ( $image_manager ) {
				$thumbnail_html = '';
				$key            = 'resized_' . ImageManager::DEFAULT_THUMBNAIL_PIXEL_COUNT . '_path';
				if ( isset( $upload->versions[ $key ] ) ) {
					$thumbnail_html = sprintf( '<img src="%s">', $image_manager->get_url_from_absolute_path( $upload->versions[ $key ] ) );
				}
				printf(
					'
				<tr>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
				</tr>
				',
					$upload->id,
					$upload->is_favourite ? 'Ja' : 'Nej',
					$upload->created_at->format( 'Y-m-d H:i' ),
					$thumbnail_html,
					join( ', ', array_keys( $upload->versions ) ),
					'?',
					'?'
				);
			}
		);
		?>
		</tbody>
	</table>
</form>
