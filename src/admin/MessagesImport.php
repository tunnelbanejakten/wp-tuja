<?php

namespace tuja\admin;

use tuja\data\model\Message;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\util\ImageManager;
use tuja\util\SmsBackupRestoreXmlFileProcessor;
use tuja\data\store\PersonDao;
use tuja\data\store\MessageDao;
use Exception;
use DateTime;

class MessagesImport extends Messages {

	private $group_dao;
	private $message_dao;
	private $person_dao;

	const SOURCE = 'mms';

	public function __construct() {
		parent::__construct();
		$this->group_dao   = new GroupDao();
		$this->message_dao = new MessageDao();
		$this->person_dao  = new PersonDao();
	}

	public function handle_post() {
		if(!isset($_POST['tuja_points_action'])) return;

		if ( $_POST['tuja_points_action'] === 'import' ) {
			$mms_messages = $this->get_mms_messages_to_import();

			if (isset($mms_messages)) {
				$im = new ImageManager();

				foreach ($mms_messages as $mms) {
					$text_value = join('. ', $mms->texts);

					$person    = $this->person_dao->get_by_contact_data( $this->competition->id, $mms->from );
					$group_key = null;
					$group_id  = null;
					if ( $person ) {
						$group = $this->group_dao->get( $person->group_id );
						if ( $group ) {
							$group_key = $group->random_id;
							$group_id  = $group->id;
						}
					}

					$image_value = array_reduce( $mms->images, function ( $carry, $image_path ) use ( $im, $group_key ) {
						try {
							$image_file_hash = $im->import_jpeg( $image_path, $group_key );
							$carry[]         = $image_file_hash;

							return $carry;
						} catch (Exception $e) {
							printf('<p>Kunde inte importera: %s</p>', $e->getMessage());
						}

						return $carry;
					}, []);

					try {
						$message = $this->import( $text_value, $image_value, $mms->from, $mms->date, $group_id );
						AdminUtils::printSuccess(
							sprintf( 'Importerade bilderna %s med id=%s för lag %s</p>',
								join( ', ', $message->image_ids ),
								$message->source_message_id,
								$group_id ) );
					} catch (Exception $e) {
						AdminUtils::printError( sprintf( '<p>Kunde inte importera: %s</p>', $e->getMessage() ) );
					}
				}
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		include('views/messages-import.php');
	}


	public function get_mms_messages_to_import() {
		$only_recent = $_POST['tuja_import_onlyrecent'] === 'yes';

		update_option('tuja_message_import_only_recent', $only_recent ? 'yes' : null);

		$date_limit = $only_recent ? new DateTime('yesterday') : null;

		$importer = new SmsBackupRestoreXmlFileProcessor('.', $date_limit);

		if (file_exists($_FILES['tuja_import_file']['tmp_name'])) {
			return $importer->process($_FILES['tuja_import_file']['tmp_name']);
		} else {
			$import_url = $_POST['tuja_import_url'];
			if (!empty($import_url)) {
				update_option('tuja_message_import_url', $import_url);

				return $importer->process($import_url);
			}
		}

		return null;
	}

	public function import( $text, $image_ids, $sender, $timestamp, $group_id ) {
		$message_id = sprintf( '%s,%s', $sender, $timestamp->format( DateTime::ISO8601 ) );

		if ( $this->message_dao->exists( self::SOURCE, $message_id ) ) {
			throw new Exception( sprintf( 'Meddelande %s har redan importerats.', $message_id ) );
		}

		$message                    = new Message();
		$message->form_question_id  = null;
		$message->group_id          = $group_id;
		$message->text              = $text;
		$message->image_ids         = $image_ids;
		$message->source            = self::SOURCE;
		$message->source_message_id = $message_id;
		$message->date_received     = $timestamp;

		$res = $this->message_dao->create( $message );

		if ( $res === false ) {
			throw new Exception( sprintf( 'Sparade inte meddelande %s i databasen pga. databasfel.', $message->source_message_id ) );
		}

		return $message;
	}

}
