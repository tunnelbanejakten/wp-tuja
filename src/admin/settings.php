<?php

namespace tuja\admin;

class Settings {
	const TUJA_OPTION_FIELD_NAME_PREFIX = 'tuja_option__';


	public function output() {
		$this->handle_post();
			
		include('views/settings.php');
	}


	public function handle_post() {
		$form_values = array_filter($_POST, function ($key) {
			return substr($key, 0, strlen(self::TUJA_OPTION_FIELD_NAME_PREFIX)) === self::TUJA_OPTION_FIELD_NAME_PREFIX;
		}, ARRAY_FILTER_USE_KEY);
		
		foreach($form_values as $field_name => $field_value) {
			$option_name = substr($field_name, strlen(self::TUJA_OPTION_FIELD_NAME_PREFIX));
			update_option($option_name, $field_value);
		}
	}

	
	public function print_option_row($label, $option_name)
	{
		$field_name = self::TUJA_OPTION_FIELD_NAME_PREFIX . $option_name;
		$field_value = $_POST[$field_name] ?: get_option($option_name);
		printf('
			<tr>
				<th scope="row"><label for="%s">%s</label></th>
				<td>
					<input name="%s" id="%s" value="%s" class="regular-text">
				</td>
			</tr>', $field_name, $label, $field_name, $field_name, $field_value);
	}
}
