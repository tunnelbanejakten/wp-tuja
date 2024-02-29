<?php

namespace tuja\frontend;


use Exception;
use DateTime;
use tuja\data\model\Competition;
use tuja\data\model\ValidationException;
use tuja\data\model\ExpenseReport;
use tuja\data\store\CompetitionDao;
use tuja\controller\ExpenseReportController;
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
	private $controller;
	private $expense_report_key;

	public function __construct( $url, $competition_key, $expense_report_key ) {
		parent::__construct( $url );
		$this->competition_dao = new CompetitionDao();
		$this->competition_key = $competition_key;
		$this->controller = new ExpenseReportController();
		$this->expense_report_key = $expense_report_key;
	}

	function get_title() {
		return sprintf( 'Utlägg %s', strtoupper($this->expense_report_key) ); // TODO: Extract to strings.ini
	}

	function get_competition(): Competition {
		return $this->competition_dao->get_by_key( $this->competition_key ); //TODO: Cache return value.
	}

	function get_content() {
		try {
			Strings::init( $this->get_competition()->id );

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}

	private function create_field(string $field_key, array $extra) {
		return new FieldText(
			Strings::get( 'expense_report_editor.form.'.$field_key.'.label' ),
			Strings::get( 'expense_report_editor.form.'.$field_key.'.hint' ),
			$this->is_read_only(),
			$extra );
	}

	private static function to_amount(string $str) : float {
		$value = floatval( str_replace( ',', '.', trim( $str ) ) );
		if ( $value === 0 ) {
			throw new ValidationException( self::FIELD_AMOUNT, Strings::get( 'expense_report_editor.error.invalid_amount' ) );
		}
		return $value;
	}

	private static function to_cents(float $value) : int {
		return round($value * 100);
	}
	
	private static function to_date(string $str) : DateTime {
		$value = DateTime::createFromFormat( 'Y-m-d', $str );
		if ( $value === false ) {
			throw new ValidationException( self::FIELD_DATE, Strings::get( 'expense_report_editor.error.invalid_date' ) );
		}
		return $value;
	}

	function output() {
		$competition = $this->get_competition();
		$errors      = [];

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			try {

				$expense_report                 = new ExpenseReport();
				$expense_report->competition_id = $this->get_competition()->id;
				$expense_report->random_id      = strtolower($this->expense_report_key);
				$expense_report->description    = $_POST[self::FIELD_DESCRIPTION];
				$expense_report->amount         = self::to_cents(self::to_amount($_POST[self::FIELD_AMOUNT]));
				$expense_report->date           = self::to_date($_POST[self::FIELD_DATE]);
				$expense_report->name           = $_POST[self::FIELD_NAME];
				$expense_report->email          = $_POST[self::FIELD_EMAIL];
				$expense_report->bank_account   = $_POST[self::FIELD_BANK_ACCOUNT];
		
				$this->controller->create( $expense_report );

				printf( '<p class="tuja-message tuja-message-success">%s</p>', Strings::get( 'expense_report_editor.created.success_message' ) );
				printf( '<p>%s</p>', Strings::get( 'expense_report_editor.created.success_message_reminder' ) );

				return;
			} catch ( ValidationException $e ) {
				$errors = [ $e->getField() => $e->getMessage() ];
				error_log($e->getMessage());
			} catch ( Exception $e ) {
				print $this->get_exception_message_html( $e );
				
				error_log($e->getMessage());
				return;
			}
		}

		if ( $this->is_read_only() ) {
			$email_link = sprintf(
				'<a href="mailto:%s">%s</a>', 
				get_bloginfo( 'admin_email' ),
				get_bloginfo( 'admin_email' ));
			printf(
				'<p class="tuja-message tuja-message-error">%s</p>',
				Strings::get( 'expense_report_editor.read_only.body', $email_link));
			return;
		}

		$error_message = '';
		$success_message = '';

		$expense_report_key = $this->expense_report_key;

		$field_description_html  = $this->get_description_field_html( $errors );
		$field_amount_html       = $this->get_amount_field_html( $errors );
		$field_date_html         = $this->get_date_field_html( $errors );
		$field_name_html         = $this->get_name_field_html( $errors );
		$field_email_html        = $this->get_email_field_html( $errors );
		$field_bank_account_html = $this->get_bank_account_field_html( $errors );
		
		$button = $this->get_form_save_button_html();

		include( 'views/expense-report-editor.php' );
	}

	private function get_description_field_html( array $errors ) : string {
		$field = $this->create_field('description', array(
			'maxlength' => '1000',
			));
		return $this->render_field($field, self::FIELD_DESCRIPTION, @$errors[ self::FIELD_DESCRIPTION ]);
	}

	private function get_amount_field_html( array $errors ) : string {
		$field = $this->create_field('amount', array(
			'type' => 'number',
			'min' => '0',
			'max' => '10000',
			'step' => '0.01',
			));
		return $this->render_field($field, self::FIELD_AMOUNT, @$errors[ self::FIELD_AMOUNT ]);
	}

	private function get_date_field_html( array $errors ) : string {
		$field = $this->create_field('date', array(
			'type' => 'date',
			));
		return $this->render_field($field, self::FIELD_DATE, @$errors[ self::FIELD_DATE ]);
	}

	private function get_name_field_html( array $errors ) : string {
		$field = $this->create_field('name', array(
			'maxlength' => '100',
			));
		return $this->render_field($field, self::FIELD_NAME, @$errors[ self::FIELD_NAME ]);
	}

	private function get_email_field_html( array $errors ) : string {
		$field = $this->create_field('email', array(
			'type' => 'email',
			'maxlength' => '100',
			));
		return $this->render_field($field, self::FIELD_EMAIL, @$errors[ self::FIELD_EMAIL ]);
	}

	private function get_bank_account_field_html( array $errors ) : string {
		$field = $this->create_field('bank_account', array(
			'maxlength' => '100',
			));
		return $this->render_field($field, self::FIELD_BANK_ACCOUNT, @$errors[ self::FIELD_BANK_ACCOUNT ]);
	}

	private function is_read_only() {
		$competition = $this->get_competition();
		return $this->controller->exists( $competition, $this->expense_report_key );
	}

	private function get_form_save_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s" id="tuja_save_button">%s</button></div>',
				self::ACTION_BUTTON_NAME,
				self::ACTION_NAME_SAVE,
				Strings::get( 'expense_report_editor.save_button.label' ) );
	}
}