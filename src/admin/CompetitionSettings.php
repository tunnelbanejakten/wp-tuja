<?php

namespace tuja\admin;

use tuja\admin\AdminUtils;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\model\MessageTemplate;
use tuja\util\DateUtils;
use Exception;

class CompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function __constructor() {
		add_action('init', array($this, 'handle_post'));
	}


	public function handle_post()
	{
		$competition_dao = new CompetitionDao($wpdb);
		$competition = $competition_dao->get($_GET['tuja_competition']);

		if(!$competition) throw new Exception('Could not find competition');

		if($_POST['tuja_competition_settings_action'] === 'save') {
			$this->competition_settings_save_other($thiscompetition);
			$this->competition_settings_save_group_categories($competition);
			$this->competition_settings_save_message_templates($competition);
		}
	}


	public function output()
	{
		$competition_dao = new CompetitionDao($wpdb);
		$competition = $competition_dao->get($_GET['tuja_competition']);
		$message_template_dao = new MessageTemplateDao($wpdb);
		
		$message_templates = $message_template_dao->get_all_in_competition($competition->id);

		$competition_url = add_query_arg(array(
			'tuja_competition' => $competition->id,
			'tuja_view' => 'competition'
		));

		include('views/competition-settings.php');
	}


	public function list_item_field_name($list_name, $id, $field)
	{
		return join(self::FIELD_SEPARATOR, array($list_name, $id, $field));
	}


	public function submitted_list_item_ids($list_name): array
	{
		$prefix = $list_name . self::FIELD_SEPARATOR;
		// $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
		$person_prop_field_names = array_filter(array_keys($_POST), function ($key) use ($prefix) {
			return substr($key, 0, strlen($prefix)) === $prefix;
		});

		// $all_ids will include duplicates (one for each of the name, email and phone fields).
		// $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
		$all_ids = array_map(function ($key) {
			list(, $id) = explode(self::FIELD_SEPARATOR, $key);
			return $id;
		}, $person_prop_field_names);
		return array_filter(array_unique($all_ids) /* No callback to outer array_filter means that empty strings will be skipped.*/);
	}

	public function print_message_template_form(MessageTemplate $message_template)
	{
		$pattern = '
			<div class="tuja-messagetemplate-form">
				<input type="text" placeholder="Mallens namn" size="50" name="%s" value="%s"><br>
				<input type="text" placeholder="Ämnesrad för e-post" size="50" name="%s" value="%s"><br>
				<textarea id="" cols="80" rows="10" placeholder="Meddelande för e-post/SMS" name="%s">%s</textarea><br>
				<button class="button tuja-delete-messagetemplate" type="button">
					Ta bort
				</button>
			</div>
		';

		return sprintf($pattern,
			$this->list_item_field_name('messagetemplate', $message_template->id, 'name'),
			$message_template->name,
			$this->list_item_field_name('messagetemplate', $message_template->id, 'subject'),
			$message_template->subject,
			$this->list_item_field_name('messagetemplate', $message_template->id, 'body'),
			$message_template->body);
	}

	public function print_group_category_form(GroupCategory $category)
	{
		$id1 = uniqid();
		$id2 = uniqid();
		$pattern = '
			<div class="tuja-groupcategory-form">
				<input type="text" placeholder="Mallens namn" size="50" name="%s" value="%s">
				<input type="radio" name="%s" id="%s" value="true" %s><label for="%s">Funktionär</label>
				<input type="radio" name="%s" id="%s" value="false" %s><label for="%s">Tävlande</label>
				<button class="button tuja-delete-groupcategory" type="button">
					Ta bort
				</button>
			</div>
		';

		return sprintf($pattern,
			$this->list_item_field_name('groupcategory', $category->id, 'name'),
			$category->name,
			$this->list_item_field_name('groupcategory', $category->id, 'iscrew'),
			$id1,
			$category->is_crew == true ? 'checked="checked"' : '',
			$id1,
			$this->list_item_field_name('groupcategory', $category->id, 'iscrew'),
			$id2,
			$category->is_crew != true ? 'checked="checked"' : '',
			$id2);
	}


	public function competition_settings_save_message_templates(Competition $competition)
	{
		global $wpdb;
		$message_template_dao = new MessageTemplateDao($wpdb);

		$message_templates = $message_template_dao->get_all_in_competition($competition->id);

		$preexisting_ids = array_map(function ($template) {
			return $template->id;
		}, $message_templates);

		$submitted_ids = $this->submitted_list_item_ids('messagetemplate');

		$updated_ids = array_intersect($preexisting_ids, $submitted_ids);
		$deleted_ids = array_diff($preexisting_ids, $submitted_ids);
		$created_ids = array_diff($submitted_ids, $preexisting_ids);

		$message_template_map = array_combine(array_map(function ($message_template) {
			return $message_template->id;
		}, $message_templates), $message_templates);

		foreach ($created_ids as $id) {
			try {
				$new_template = new MessageTemplate();
				$new_template->competition_id = $competition->id;
				$new_template->name = $_POST[$this->list_item_field_name('messagetemplate', $id, 'name')];
				$new_template->subject = $_POST[$this->list_item_field_name('messagetemplate', $id, 'subject')];
				$new_template->body = $_POST[$this->list_item_field_name('messagetemplate', $id, 'body')];

				$new_template_id = $message_template_dao->create($new_template);
			} catch (ValidationException $e) {
			} catch (Exception $e) {
			}
		}

		foreach ($updated_ids as $id) {
			if (isset($message_template_map[$id])) {
				try {
					$message_template_map[$id]->name = $_POST[$this->list_item_field_name('messagetemplate', $id, 'name')];
					$message_template_map[$id]->subject = $_POST[$this->list_item_field_name('messagetemplate', $id, 'subject')];
					$message_template_map[$id]->body = $_POST[$this->list_item_field_name('messagetemplate', $id, 'body')];

					$affected_rows = $message_template_dao->update($message_template_map[$id]);
				} catch (ValidationException $e) {
				} catch (Exception $e) {
				}
			}
		}

		foreach ($deleted_ids as $id) {
			if (isset($message_template_map[$id])) {
				$delete_successful = $message_template_dao->delete($id);
			}
		}
	}

	public function competition_settings_save_group_categories(Competition $competition)
	{
		global $wpdb;
		$category_dao = new GroupCategoryDao($wpdb);

		$categories = $category_dao->get_all_in_competition($competition->id);

		$preexisting_ids = array_map(function ($category) {
			return $category->id;
		}, $categories);

		$submitted_ids = $this->submitted_list_item_ids('groupcategory');

		$updated_ids = array_intersect($preexisting_ids, $submitted_ids);
		$deleted_ids = array_diff($preexisting_ids, $submitted_ids);
		$created_ids = array_diff($submitted_ids, $preexisting_ids);

		$category_map = array_combine(array_map(function ($category) {
			return $category->id;
		}, $categories), $categories);

		foreach ($created_ids as $id) {
			try {
				$new_template = new GroupCategory();
				$new_template->competition_id = $competition->id;
				$new_template->name = $_POST[$this->list_item_field_name('groupcategory', $id, 'name')];
				$new_template->is_crew = $_POST[$this->list_item_field_name('groupcategory', $id, 'iscrew')] === 'true';

				$new_template_id = $category_dao->create($new_template);
			} catch (ValidationException $e) {
			} catch (Exception $e) {
			}
		}

		foreach ($updated_ids as $id) {
			if (isset($category_map[$id])) {
				try {
					$category_map[$id]->name = $_POST[$this->list_item_field_name('groupcategory', $id, 'name')];
					$category_map[$id]->is_crew = $_POST[$this->list_item_field_name('groupcategory', $id, 'iscrew')] === 'true';

					$affected_rows = $category_dao->update($category_map[$id]);
				} catch (ValidationException $e) {
				} catch (Exception $e) {
				}
			}
		}

		foreach ($deleted_ids as $id) {
			if (isset($category_map[$id])) {
				$delete_successful = $category_dao->delete($id);
			}
		}
	}

	public function competition_settings_save_other(Competition $competition)
	{
		global $wpdb;

		try {
			// TODO: Settle on one naming convention for form field names.
			$competition->create_group_start = DateUtils::from_date_local_value($_POST['tuja_create_group_start']);
			$competition->create_group_end = DateUtils::from_date_local_value($_POST['tuja_create_group_end']);
			$competition->edit_group_start = DateUtils::from_date_local_value($_POST['tuja_edit_group_start']);
			$competition->edit_group_end = DateUtils::from_date_local_value($_POST['tuja_edit_group_end']);

			$competition->message_template_id_new_group_admin = !empty($_POST['tuja_competition_settings_message_template_id_new_group_admin']) ? intval($_POST['tuja_competition_settings_message_template_id_new_group_admin']) : null;
			$competition->message_template_id_new_group_reporter = !empty($_POST['tuja_competition_settings_message_template_id_new_group_reporter']) ? intval($_POST['tuja_competition_settings_message_template_id_new_group_reporter']) : null;
			$competition->message_template_id_new_crew_member = !empty($_POST['tuja_competition_settings_message_template_id_new_crew_member']) ? intval($_POST['tuja_competition_settings_message_template_id_new_crew_member']) : null;
			$competition->message_template_id_new_noncrew_member = !empty($_POST['tuja_competition_settings_message_template_id_new_noncrew_member']) ? intval($_POST['tuja_competition_settings_message_template_id_new_noncrew_member']) : null;

			$dao = new CompetitionDao($wpdb);
			$dao->update($competition);
		} catch (Exception $e) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException($e);
		}
	}
}