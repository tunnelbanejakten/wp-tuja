<?php


namespace tuja\util;


use Exception;

class SwishQrCode {
	private $directory;
	private $public_url_directory;

	public function __construct() {
		$dir = wp_upload_dir( 'tuja', true, false );
		if ( ! isset( $dir['path'] ) ) {
			throw new Exception( 'Could not find folder to put image in.' );
		}
		$this->directory            = trailingslashit( $dir['path'] );
		$this->public_url_directory = trailingslashit( $dir['url'] );
	}

	public function swish_qr_code_image_url( string $payee, int $amount, string $message ): string {
		$http_request_payload = json_encode( array(
			'format'      => 'png',
			'payee'       => array(
				'editable' => false,
				'value'    => $payee
			),
			'amount'      => array(
				'editable' => false,
				'value'    => $amount
			),
			'message'     => array(
				'editable' => false,
				'value'    => $message
			),
			'size'        => 300,
			'border'      => 0,
			'transparent' => false
		) );

		$ext           = 'png';
		$hash          = md5( $http_request_payload );
		$filename      = $hash . '.' . $ext;
		$sub_directory = 'swish-qr/';
		if ( ! is_dir( $this->directory . $sub_directory ) ) {
			mkdir( $this->directory . $sub_directory, 0755, true );
		}

		$new_path = $this->directory . $sub_directory . $filename;

		if ( file_exists( $new_path ) ) {
			// This exact file has been generated before.
			return $this->public_url_directory . $sub_directory . $filename;
		}

		$http_config = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-Type: application/json',
				'content' => $http_request_payload
			)
		);

		$context = stream_context_create( $http_config );

		$image_data = file_get_contents( 'https://mpc.getswish.net/qrg-swish/api/v1/prefilled', false, $context );
		if ( $image_data === false ) {
			throw new Exception( 'Could not generate QR code' );
		}
		$write_result = file_put_contents( $new_path, $image_data );
		chmod( $new_path, 0644 );
		if ( $write_result === false ) {
			throw new Exception( 'Could not save QR code' );
		}

		return $this->public_url_directory . $sub_directory . $filename;
	}
}