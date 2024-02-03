<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Competition;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\Frontend;
use tuja\util\Strings;
use tuja\view\FieldChoices;
use tuja\view\FieldText;

class ExpenseReportEditor extends FrontendView {

	const FIELD_KEY = 'key';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_AMOUNT = 'amount';
	const FIELD_DATE = 'date';
	const FIELD_NAME = 'name';
	const FIELD_EMAIL = 'email';
	const FIELD_BANK_ACCOUNT = 'bank_account';

	private $competition_dao;
	private $competition_key;
	private $expense_report_key;

	public function __construct( $url, $competition_key, $expense_report_key ) {
		parent::__construct( $url );
		$this->competition_dao = new CompetitionDao();
		$this->competition_key = $competition_key;
		$this->expense_report_key = $expense_report_key;
	}

	function get_title() {
		return sprintf( 'Utlägg %s', strtoupper($this->expense_report_key) ); // TODO: Extract to strings.ini
	}

	function get_competition(): Competition {
		return $this->competition_dao->get_by_key( $this->competition_key );
	}

	function get_content() {
		try {
			Strings::init( $this->get_competition()->id );

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}

	private function create_field(string $field_key) {
		return new FieldText(
			Strings::get( 'expense_report_editor.form.'.$field_key.'.label' ),
			Strings::get( 'expense_report_editor.form.'.$field_key.'.hint' ),
			false, [] );
	}

	function output() {
		$competition = $this->get_competition();
		$errors      = [];

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			// TODO: Implement.
		}

		$error_message = '';
		$success_message = '';

		$expense_report_key = $this->expense_report_key;

		$field_description = $this->create_field('description');
		$field_description_html = $this->render_field($field_description, self::FIELD_DESCRIPTION, @$errors[ self::FIELD_DESCRIPTION]);

		$field_amount = $this->create_field('amount');
		$field_amount_html = $this->render_field($field_amount, self::FIELD_AMOUNT, @$errors[ self::FIELD_AMOUNT]);

		$field_date = $this->create_field('date');
		$field_date_html = $this->render_field($field_date, self::FIELD_DATE, @$errors[ self::FIELD_DATE]);

		$field_name = $this->create_field('name');
		$field_name_html = $this->render_field($field_name, self::FIELD_NAME, @$errors[ self::FIELD_NAME]);

		$field_email = $this->create_field('email');
		$field_email_html = $this->render_field($field_email, self::FIELD_EMAIL, @$errors[ self::FIELD_EMAIL]);

		$field_bank_account = $this->create_field('bank_account');
		$field_bank_account_html = $this->render_field($field_bank_account, self::FIELD_BANK_ACCOUNT, @$errors[ self::FIELD_BANK_ACCOUNT]);
		
		$button = $this->get_form_save_button_html();

		include( 'views/expense-report-editor.php' );
	}

	private function is_read_only() {
		return false; // TODO: Implement
	}

	private function get_form_save_button_html() {
		$html_sections = [];

		if ( ! $this->is_read_only() ) {
			$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s" id="tuja_save_button">%s</button></div>',
				self::ACTION_BUTTON_NAME,
				self::ACTION_NAME_SAVE,
				'Spara' );
		} else {
			$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>',
				sprintf( 'Den här utläggsrapporten är låst. Kontakta <a href="mailto:%s">%s</a> om du behöver ändra något.',
					get_bloginfo( 'admin_email' ),
					get_bloginfo( 'admin_email' ) ) );
		}

		return join( $html_sections );
	}
}