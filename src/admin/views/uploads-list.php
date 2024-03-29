<?php namespace tuja\admin;

use tuja\data\model\payment\PaymentTransaction;
use tuja\data\model\Upload;
use tuja\util\ImageManager;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<div>
		
	<table id="tuja_uploads_list" class="tuja-table">
		<thead>
		<tr>
			<td colspan="5" style="text-align: right"><?php echo $pagination_html; ?></td>
		</tr>
		<tr>
			<th>Id</th>
			<th>Favorit?</th>
			<th>Datum</th>
			<th>Förhandsgranskning</th>
			<th>Versioner</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td colspan="5" style="text-align: right"><?php echo $pagination_html; ?></td>
		</tr>
		</tfoot>
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
				$update_favourite_endpoint = add_query_arg(
					array(
						'action'         => 'tuja_favourite_upload',
						'tuja_upload_id' => rawurlencode( strval( $upload->id ) ),
					),
					admin_url( 'admin.php' )
				);
				printf(
					'
				<tr>
					<td>%s</td>
					<td><input type="checkbox" class="tuja-toggle-favourite-upload" data-endpoint="%s" %s></td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
				</tr>
				',
					$upload->id,
					$update_favourite_endpoint,
					$upload->is_favourite ? 'checked="checked"' : '',
					$upload->created_at->format( 'Y-m-d H:i' ),
					$thumbnail_html,
					join( ', ', array_keys( $upload->versions ) )
				);
			}
		);
		?>
		</tbody>
	</table>
</form>
