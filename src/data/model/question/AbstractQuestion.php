<?php

namespace tuja\data\model\question;


use ReflectionClass;
use ReflectionProperty;
use tuja\data\model\ValidationException;

abstract class AbstractQuestion {

	// TODO: Do these properties need to be public?
	public $id;
	public $question_group_id;
	public $text_hint;
	public $text;
	public $sort_order;
	public $score_max;
//	protected $type;

	/**
	 * AbstractQuestion constructor.
	 *
	 * @param $type
	 * @param $question_group_id
	 * @param $text
	 * @param $id
	 * @param $text_hint
	 * @param $sort_order
	 */
	// TODO: Remove $type parameter
	public function __construct( $type, $question_group_id, $text, $id, $text_hint, $sort_order ) {
		$this->id                = $id;
		$this->question_group_id = $question_group_id;
		$this->text_hint         = $text_hint;
		$this->text              = $text;
		$this->sort_order        = $sort_order;
//		$this->type              = $type;
	}


	/**
	 * Grades an answer and returns the score for the answer.
	 */
	abstract function score( $answer_object );

	/**
	 * Returns the HTML used to render this question.
	 */
	abstract function get_html( $field_name, $is_read_only, $answer_object );

	/**
	 * Gathers data from $_POST about the current question. The response from this function
	 * can be sent to score(...) and can be stored in the database.
	 */
	abstract function get_answer_object( $field_name );

	/**
	 * Returns a JSON schema used to validate the question configuration. Also used to generate a form for editing the question.
	 */
	abstract function get_config_schema();

	/**
	 * Returns the configuration data to store in the database for this question.
	 */
	abstract function get_config_object();

	/**
	 * Initializes the different properties of the question object based on a string, presumable one returned from get_config_string().
	 */
	abstract function set_config( $config_object );

	abstract function get_correct_answer_html();

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågan är för lång.' );
		}
		if ( strlen( $this->text_hint ) > 65000 ) {
			throw new ValidationException( 'text_hint', 'Hjälptexten är för lång.' );
		}
		if ( ! empty( $this->score_type ) && ! in_array( $this->score_type, self::SCORING_METHODS ) ) {
			throw new ValidationException( 'score_type', 'Ogiltig poängberäkningsmetod.' );
		}
//		if(!in_array($this->type, self::VALID_TYPES)) {
//			throw new ValidationException('type', 'Ogiltig frågetyp.');
//		}
	}

	// TODO: Use this as a starting point for an auto-generated question editor
//	public function get_fields() {
//		$cls = new ReflectionClass( $this );
//
//		return array_map( function ( ReflectionProperty $prop ) {
//			$prop->setAccessible( true );
//			return [
//				'name' => $prop->getName(),
//				'datatype' => gettype($prop->getValue( $this )),
//			];
//		}, $cls->getProperties() );
//	}
}