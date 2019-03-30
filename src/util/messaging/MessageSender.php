<?php

namespace tuja\util\messaging;

use Exception;
use tuja\util\Template;

class MessageSender
{
	public function send_mail( $to, $subject, $body ) {
		$attachments  = [];
		$headers      = [
			'Content-Type: text/html; charset=UTF-8'
		];
		$wrapped_body = Template::file( 'util/messaging/email_template.html' )->render( [
			'subject' => $subject,
			'body'    => $body
		] );

		return wp_mail( $to, "[Tunnelbanejakten] $subject", $wrapped_body, $headers, $attachments );
	}

	public function send_sms( $to, $body ) {
		if ( ! preg_match( '/^\+46[^0][0-9]+$/', $to ) ) {
			throw new Exception( 'Telefonnummer m&aring:ste b&ouml;rja med +46 och bara inneh&aring;lla siffror.' );
		}
		$username = get_option( 'tuja_46elks_username' );
		$password = get_option( 'tuja_46elks_password' );
		if ( empty( $username ) || empty( $password ) ) {
			throw new Exception( 'Inloggningsuppgifter för 46elks har inte angetts.' );
		}
		$sms     = array(
			"from"    => "Tbanejakten" /* Can be up to 11 alphanumeric characters */,
			"to"      => $to,
			"message" => $body
		);
		$context = stream_context_create( array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Authorization: Basic ' .
				             base64_encode( $username . ':' . $password ) . "\r\n" .
				             "Content-type: application/x-www-form-urlencoded\r\n",
				'content' => http_build_query( $sms ),
				'timeout' => 10
			)
		) );

		file_get_contents( "https://api.46elks.com/a1/SMS", false, $context );

		if ( ! strstr( $http_response_header[0], "200 OK" ) ) {
			throw new Exception( $http_response_header[0] );
		}
	}
}