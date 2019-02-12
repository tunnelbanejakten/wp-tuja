<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\util\ImageManager;
use tuja\util\MessageImporter;
use tuja\util\SmsBackupRestoreXmlFileProcessor;
use tuja\data\store\PersonDao;
use tuja\data\store\MessageDao;
use Exception;
use DateTime;

class MessagesImport {

	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if (!$this->competition) {
			print 'Could not find competition';
			return;
		}
		
	}


	public function handle_post() {
		if(!isset($_POST['tuja_points_action'])) return;
		
		if($_POST['tuja_points_action'] === 'import') {
			$mms_messages = $this->get_mms_messages_to_import();
		
			if (isset($mms_messages)) {
				$im = new ImageManager();

				$person_dao  = new PersonDao();
				$message_dao = new MessageDao();
		
				$importer = new MessageImporter(
					$message_dao,
					$person_dao->get_all_in_competition($this->competition->id));
		
				foreach ($mms_messages as $mms) {
					$text_value = join('. ', $mms->texts);
		
					$image_value = array_reduce($mms->images, function ($carry, $image_path) use ($im) {
						try {
							$image_file_hash = $im->import_jpeg($image_path);
							$carry[] = $image_file_hash;
							return $carry;
						} catch (Exception $e) {
							printf('<p>Kunde inte importera: %s</p>', $e->getMessage());
						}
						return $carry;
					}, []);
		
					try {
						$message = $importer->import($text_value, $image_value, $mms->from, $mms->date);
						printf('<p>Importerade bilderna %s med id=%s</p>',
							join(', ', $message->image_ids),
							$message->source_message_id);
					} catch (Exception $e) {
						printf('<p>Kunde inte importera: %s</p>', $e->getMessage());
					}
				}
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		// TODO: Make helper function for generating URLs
		$competition_url = add_query_arg(array(
			'tuja_competition' => $competition->id,
			'tuja_view' => 'Competition'
		));

		include('views/messages-import.php');
	}


	public function get_mms_messages_to_import()
	{
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
}
