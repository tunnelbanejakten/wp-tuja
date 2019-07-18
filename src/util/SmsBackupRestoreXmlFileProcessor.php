<?php

namespace tuja\util;

use DateTime;

class Message
{
    public $from = null;
    public $date = null;
    public $texts = array();
    public $images = array();
}

class SmsBackupRestoreXmlFileProcessor
{
    private $image_folder;
    private $date_limit;

    public function __construct($image_folder, $date_limit)
    {
        $this->image_folder = $image_folder;
        $this->date_limit = $date_limit;
    }

	public function process( $xml_file ) {
		$res = array();

		$handle = fopen( $xml_file, "r" );
		if ( $handle ) {

			$message = null;
			while ( ( $line = fgets( $handle ) ) !== false ) {
				$lines_matches = [];
				if ( preg_match( '/<mms date="([^"]*)" .* address="([^"]*)"/', $line, $lines_matches ) == 1 ) {
					if ( ! empty( $lines_matches ) ) {
						list ( , $timestamp, $phone_number ) = $lines_matches;

						// TODO: Group individual messages sent, say, less than 30 seconds apart in case groups send the image as one message and the description as another.
						$message_date = new DateTime( "@" . substr( $timestamp, 0, 10 ) );

						if ( ! isset( $this->date_limit ) || $message_date >= $this->date_limit ) {
							$message       = new Message();
							$message->from = Phone::fix_phone_number( $phone_number );
							$message->date = $message_date;
							$res[]         = $message;
						} else {
							$message = null;
						}
					}
				} elseif ( preg_match( '/<part .* ct="image\/[^"]*" .* data="([^"]*)"/', $line, $lines_matches ) == 1 ) {
					if ( ! empty( $lines_matches ) && isset( $message ) ) {
						list ( , $data ) = $lines_matches;
						$path = SmsBackupRestoreXmlFileProcessor::create_temp_file();
						file_put_contents( $path, base64_decode( $data ) );
						$message->images[] = $path;
					}
				} elseif ( preg_match( '/<part .* ct="text\/plain" .* text="([^"]*)"/', $line, $lines_matches ) == 1 ) {
					if ( ! empty( $lines_matches ) && isset( $message ) ) {
						list ( , $text ) = $lines_matches;
						$message->texts[] = $text;
					}
				}
			}

			fclose( $handle );
		} else {
			// error opening the file.
		}

		return array_filter( $res, function ( Message $message ) {
			return count( $message->images ) > 0;
		} );
	}

    /**
     * Create a temporary file which will be delete by PHP itself once the script completes.
     *
     * @return absolute path to the newly created file, e.g: /tmp/phpFx0513a.
     */
    private static function create_temp_file()
    {
        return stream_get_meta_data(tmpfile())['uri'];
    }
}
