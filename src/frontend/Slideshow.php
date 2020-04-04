<?php


namespace tuja\frontend;


use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\util\ImageManager;
use tuja\util\Strings;
use tuja\view\FieldChoices;


class Slideshow extends FrontendView {

	const FIELD_DURATION = 'duration';
	const FIELD_QUESTION_FILTER = 'questions';
	const FIELD_SHUFFLE = 'shuffle';

	private $competition_key;
	private $question_dao;
	private $response_dao;
	private $competition_dao;
	private $group_dao;
	private $image_manager;
	private $form_dao;
	private $question_group_dao;

	public function __construct( string $url, string $competition_key ) {
		parent::__construct( $url );
		$this->competition_key    = $competition_key;
		$this->question_dao       = new QuestionDao();
		$this->response_dao       = new ResponseDao();
		$this->competition_dao    = new CompetitionDao();
		$this->group_dao          = new GroupDao();
		$this->image_manager      = new ImageManager();
		$this->form_dao           = new FormDao();
		$this->question_group_dao = new QuestionGroupDao();
	}

	function get_content() {
		try {
			Strings::init( $this->get_competition()->id );

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}

	function get_competition(): Competition {
		return $this->competition_dao->get_by_key( $this->competition_key );
	}

	private function get_question_ids(): array {
		list( $filter_type, $filter_param ) = @explode( '_', $_GET[ self::FIELD_QUESTION_FILTER ] );

		switch ( $filter_type ) {
			case 'all':
				return array_map( function ( AbstractQuestion $question ) {
					return $question->id;
				}, $this->question_dao->get_all_in_competition( $this->get_competition()->id ) );
			case 'form':
				return array_map( function ( AbstractQuestion $question ) {
					return $question->id;
				}, $this->question_dao->get_all_in_form( intval( $filter_param ) ) );
			case 'questiongroup':
				return array_map( function ( AbstractQuestion $question ) {
					return $question->id;
				}, $this->question_dao->get_all_in_group( intval( $filter_param ) ) );
			default:
				return [];
		}
	}

	function output() {

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tuja-slideshow-script' );

		$competition = $this->get_competition();

		$image_responses = $this->response_dao->get_by_questions( $competition->id, ResponseDao::QUESTION_FILTER_IMAGES, [] );
		$groups          = $this->group_dao->get_all_in_competition( $competition->id );
		$group_map       = array_combine(
			array_map( function ( Group $group ) {
				return $group->id;
			}, $groups ),
			array_map( function ( Group $group ) {
				return $group->random_id;
			}, $groups )
		);

		$question_ids = $this->get_question_ids();

		$image_urls = [];
		foreach ( $image_responses as $image_response ) {
			foreach ( $image_response['questions'] as $question_data ) {
				if ( in_array( $question_data['question']->id, $question_ids ) ) {
					foreach ( $question_data['responses'] as $response ) {
						$group_id   = $response['response']->group_id;
						$group_name = $this->group_dao->get( $group_id )->name;
						foreach ( $response['response']->submitted_answer['images'] as $image_id ) {
							$image_urls[] = [
								'caption' => $question_data['question']->text . ' av ' . $group_name,
								'url'     => $this->image_manager->get_original_image_url(
									$image_id,
									$group_map[ $group_id ] )
							];
						}
					}
				}
			}
		}

		if ( $_GET[ self::FIELD_SHUFFLE ] == 'yes' ) {
			shuffle( $image_urls );
		}

		$question_filters = array_merge(
			[ 'all' => 'Alla' ],
			array_reduce( $this->form_dao->get_all_in_competition( $competition->id ), function ( array $acc, \tuja\data\model\Form $form ) {
				$question_groups = $this->question_group_dao->get_all_in_form( $form->id );

				return array_merge( $acc,
					[ 'form_' . $form->id => str_repeat( '&nbsp;', 4 ) . $form->name ],
					array_combine(
						array_map( function ( QuestionGroup $question_group ) {
							return 'questiongroup_' . $question_group->id;
						}, $question_groups ),
						array_map( function ( QuestionGroup $question_group ) {
							return str_repeat( '&nbsp;', 8 ) . ( $question_group->text ?: 'Frågegrupp ' . $question_group->id );
						}, $question_groups ) ) );
			}, [] )
		);

		$option_question_filter = sprintf( '<div class="tuja-field"><label>%s</label>%s</div>', 'Visa bilder från dessa frågor:', join(
			array_map( function ( string $key, string $label ) {
				$id = uniqid();

				return sprintf( '<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s/><label for="%s">%s</label></div>',
					FieldChoices::FIELD_TYPE,
					'radiobutton',
					'radio',
					self::FIELD_QUESTION_FILTER,
					htmlspecialchars( $key ),
					FieldChoices::FIELD_TYPE,
					FieldChoices::FIELD_TYPE,
					$id,
					$_GET[ self::FIELD_QUESTION_FILTER ] == $key ? ' checked="checked"' : '',
					$id,
					$label );
			}, array_keys( $question_filters ), array_values( $question_filters ) ) ) );

		$option_duration = sprintf( '<div class="tuja-field"><label>%s</label>%s</div>', 'Visa varje bild så här länge:', join( array_map( function ( int $seconds ) {
			$id = uniqid();

			return sprintf( '<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s/><label for="%s">%s</label></div>',
				FieldChoices::FIELD_TYPE,
				'radiobutton',
				'radio',
				self::FIELD_DURATION,
				htmlspecialchars( $seconds ),
				FieldChoices::FIELD_TYPE,
				FieldChoices::FIELD_TYPE,
				$id,
				$_GET[ self::FIELD_DURATION ] == $seconds ? ' checked="checked"' : '',
				$id,
				htmlspecialchars( sprintf( '%d sekunder', $seconds ) ) );
		}, [ 3, 5, 10 ] ) ) );

		$id             = uniqid();
		$option_shuffle = sprintf( '<div class="tuja-field"><label>%s</label>%s</div>',
			'Slumpad ordning:',
			sprintf( '<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s/><label for="%s">%s</label></div>',
				FieldChoices::FIELD_TYPE,
				'checkbox',
				'checkbox',
				self::FIELD_SHUFFLE,
				'yes',
				FieldChoices::FIELD_TYPE,
				FieldChoices::FIELD_TYPE,
				$id,
				$_GET[ self::FIELD_SHUFFLE ] == 'yes' ? ' checked="checked"' : '',
				$id,
				htmlspecialchars( "Ja, slumpa" ) ) );


		include( 'views/slideshow.php' );
	}

	function get_title() {
		return sprintf( 'Bilder från %s', $this->get_competition()->name );
	}
}