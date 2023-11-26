<?php

namespace tuja\admin;

use tuja\util\ImageManager;

class UploadsList extends Uploads {
	public function __construct() {
		parent::__construct();
	}

	public function get_scripts(): array {
		return array(
			'admin-uploads.js',
		);
	}

	public function output() {

		$offset = intval( $_GET['tuja_uploads_offset'] ?? '0' );
		$count  = intval( $_GET['tuja_uploads_count'] ?? '50' );

		$uploads = $this->upload_dao->get_all_in_competition(
			$this->competition->id,
			$offset,
			$count + 1
		);

		$show_next_page = count( $uploads ) > $count;

		$uploads = array_slice( $uploads, 0, $count );

		$image_manager = new ImageManager();

		$prev_page_link = add_query_arg(
			array(
				'tuja_uploads_offset' => max( 0, $offset - $count ),
			)
		);
		$next_page_link = add_query_arg(
			array(
				'tuja_uploads_offset' => $offset + $count,
			)
		);

		$pagination_html = '' .
		( $offset > 0 ? sprintf( '<a href="%s">Föregående sida</a>', $prev_page_link ) : '' ) .
		sprintf( ' Visar bild %d till %d ', $offset + 1, $offset + count( $uploads ) ) .
		( $show_next_page ? sprintf( '<a href="%s">Nästa sida</a>', $next_page_link ) : '' ) .
		'. Bilder per sida: ' . join(
			' | ',
			array_map(
				function ( int $num ) {
					return sprintf(
						'<a href="%s">%s</a>',
						add_query_arg(
							array(
								'tuja_uploads_count' => $num,
							)
						),
						$num
					);
				},
				array( 10, 50, 100 )
			)
		) . '.';

		include( 'views/uploads-list.php' );
	}
}
