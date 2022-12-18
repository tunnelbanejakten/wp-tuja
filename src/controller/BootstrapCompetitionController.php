<?php
namespace tuja\controller;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Map;
use tuja\data\model\Marker;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\Station;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MapDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\StationDao;
use tuja\util\rules\CrewMembersRuleSet;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\OlderParticipantsRuleSet;
use tuja\util\rules\YoungParticipantsRuleSet;

class BootstrapCompetitionController {
	private $competition_dao;

	function __construct() {
		$this->competition_dao      = new CompetitionDao();
		$this->category_dao         = new GroupCategoryDao();
		$this->group_dao            = new GroupDao();
		$this->person_dao           = new PersonDao();
		$this->form_dao             = new FormDao();
		$this->question_group_dao   = new QuestionGroupDao();
		$this->question_dao         = new QuestionDao();
		$this->station_dao          = new StationDao();
		$this->map_dao              = new MapDao();
		$this->marker_dao           = new MarkerDao();
		$this->message_template_dao = new MessageTemplateDao();
	}

	function bootstrap_competition( BootstrapCompetitionParams $params ) {
		$competition = $this->create_competition( $params->name, $params->initial_group_status );
		if ( $params->create_default_group_categories ) {
			list ($crew_group_key, $crew_person_key) = $this->create_group_categories( $competition, $params->create_default_crew_groups );
		}
		if ( $params->create_sample_form ) {
			$form           = $this->create_form( $competition );
			$question_group = $this->create_question_group( $form );

			$this->create_text_question( $question_group );
			$this->create_number_question( $question_group );
			$this->create_images_question( $question_group, 'Ta en bild på något som får dig att le' );
			$this->create_options_question( $question_group );

			$sample_form_key = $form->random_id;
			$sample_form_id  = $form->id;
		}
		if ( $params->create_sample_stations ) {
			$sample_station_ids = $this->create_stations( $competition );
		}
		if ( $params->create_sample_maps ) {
			$sample_map_id = $this->create_maps( $competition );
		}
		if ( $params->create_common_group_state_transition_sendout_templates ) {
			$this->create_common_sendout_templates( $competition );
		}
		return array(
			'competition'        => $competition,
			'crew_group_key'     => $crew_group_key ?: null,
			'crew_person_key'    => $crew_person_key ?: null,
			'sample_form_key'    => $sample_form_key ?: null,
			'sample_form_id'     => $sample_form_id ?: null,
			'sample_map_id'      => $sample_map_id ?: null,
			'sample_station_ids' => $sample_station_ids ?: array(),
		);
	}

	private function create_competition( string $name, string $initial_group_status ) {
		$competition                       = new Competition();
		$competition->name                 = $name;
		$competition->initial_group_status = $initial_group_status;

		$competition_id = $this->competition_dao->create( $competition );
		if ( $competition_id === false ) {
			throw new Exception( 'Could not create competition.' );
		}
		$competition = $this->competition_dao->get( $competition_id );
		return $competition;
	}

	private function create_group_categories( Competition $competition, bool $create_default_crew_groups ) {
		$crew_group_key  = null;
		$crew_person_key = null;
		$rule_sets       = array(
			'The Crew'           => new CrewMembersRuleSet(),
			'Young Participants' => new YoungParticipantsRuleSet(),
			'Old Participants'   => new OlderParticipantsRuleSet(),
		);
		foreach ( $rule_sets as $name => $rule_set ) {
			$is_crew_group_category = $rule_set->is_crew();
			$props                  = new GroupCategory();
			$props->competition_id  = $competition->id;
			$props->name            = $name;
			$props->set_rules( GroupCategoryRules::from_rule_set( $rule_set, $competition ) );

			$category_id = $this->category_dao->create( $props );
			if ( $category_id !== false && $is_crew_group_category ) {
				if ( $create_default_crew_groups ) {
					$group_props                     = new Group();
					$group_props->competition_id     = $competition->id;
					$group_props->map_id             = null;
					$group_props->name               = $name;
					$group_props->note               = null;
					$group_props->city               = null;
					$group_props->is_always_editable = false;
					$group_props->category_id        = $category_id;
					$group_props->set_status( Group::DEFAULT_STATUS );

					$crew_group_id = $this->group_dao->create( $group_props );
					if ( $crew_group_id !== false ) {
						$crew_group     = $this->group_dao->get( $crew_group_id );
						$crew_group_key = $crew_group->random_id;

						$new_person           = new Person();
						$new_person->group_id = $crew_group_id;
						$new_person->email    = 'c.rew@example.com';
						$new_person->name     = 'C. Rew';
						$new_person->phone    = '070-123 45 67';
						$new_person->set_status( Person::DEFAULT_STATUS );
						$new_person->set_type( Person::PERSON_TYPE_REGULAR );

						$crew_person_id = $this->person_dao->create( $new_person );
						if ( false !== $crew_person_id ) {
							$crew_person     = $this->person_dao->get( $crew_person_id );
							$crew_person_key = $crew_person->random_id;
						}
					}
				}
			}
		}
		return array( $crew_group_key, $crew_person_key );
	}

	private function create_form( Competition $competition, string $name = 'Ett formulär' ) : Form {
		$props                                     = new Form();
		$props->name                               = $name;
		$props->competition_id                     = $competition->id;
		$props->allow_multiple_responses_per_group = false;
		$props->submit_response_start              = null;
		$props->submit_response_end                = null;

		$form_id = $this->form_dao->create( $props );
		if ( $form_id === false ) {
			throw new Exception( 'Could not create form.' );
		}
		$form = $this->form_dao->get( $form_id );
		return $form;
	}

	private function create_question_group( Form $form ) : QuestionGroup {
		$question_group_props                   = new QuestionGroup();
		$question_group_props->form_id          = $form->id;
		$question_group_props->text             = 'En grupp frågor';
		$question_group_props->text_description = null;
		$question_group_props->sort_order       = 0;
		$question_group_props->score_max        = null;
		$question_group_props->question_filter  = QuestionGroup::QUESTION_FILTER_ALL;

		$question_group_id = $this->question_group_dao->create( $question_group_props );
		if ( $question_group_id === false ) {
			throw new Exception( 'Could not create question group.' );
		}
		$question_group = $this->question_group_dao->get( $question_group_id );
		return $question_group;
	}

	private function create_text_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new TextQuestion(
			null,
			'Vad heter den utomjordiske superhjälten som jobbar på en tidningsredaktion?',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			null, // text_preparation
			10,
			TextQuestion::GRADING_TYPE_ONE_OF,
			true,
			array( 'stålmannen', 'superman', 'clark kent' ),
			array( 'spindelmannen', 'spider man', 'peter parker' )
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_number_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new NumberQuestion(
			null,
			'Vad är svaret på den yttersta frågan om livet, universum och allting?',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			null, // text_preparation
			10,
			42
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_images_question( QuestionGroup $question_group, $text ): AbstractQuestion {
		$question_props = new ImagesQuestion(
			null,
			$text,
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			null, // text_preparation
			10,
			ImagesQuestion::DEFAULT_FILE_COUNT_LIMIT
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_options_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new OptionsQuestion(
			null,
			'Vilken är en huvudstad?',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			null, // text_preparation
			10,
			OptionsQuestion::GRADING_TYPE_ALL_OF,
			true,
			array( 'paris' ),
			array( 'bergen', 'paris', 'milano', 'barcelona' ),
			false
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_question( QuestionGroup $question_group, AbstractQuestion $question_props ): AbstractQuestion {
		$question_id = $this->question_dao->create( $question_props );
		if ( $question_id === false ) {
			throw new Exception( 'Could not create question.' );
		}
		$question = $this->question_dao->get( $question_id );
		return $question;
	}

	private function create_stations( Competition $competition ) {
		return array_map(
			function ( $name ) use ( $competition ) {
				$props                          = new Station();
				$props->name                    = $name;
				$props->competition_id          = $competition->id;
				$props->location_gps_coord_lat  = null;
				$props->location_gps_coord_long = null;
				$props->location_description    = null;

				$station_id = $this->station_dao->create( $props );
				if ( false === $station_id ) {
					throw new Exception( 'Could not create station.' );
				}
				return $station_id;
			},
			array(
				'Hornstull',
				'Slussen',
				'Mariatorget',
				'Skanstull',
			)
		);
	}

	private function create_maps( Competition $competition ) {

		$form           = $this->create_form( $competition, 'Ett kartformulär' );
		$question_group = $this->create_question_group( $form );
		$question_a     = $this->create_images_question( $question_group, 'Ta en bild på din omgivning.' );
		$question_b     = $this->create_images_question( $question_group, 'Ta en till bild på din omgivning.' );
		$question_c     = $this->create_images_question( $question_group, 'Ta ytterligare en bild på din omgivning.' );

		$config = array(
			array(
				'Stockholm',
				array(
					array( 59.332280, 18.064106, 'Mitt på Plattan', $question_a->id ),
					array( 59.327525, 18.055132, 'Stadshusets innergård', $question_b->id ),
					array( 59.317896, 18.074455, 'Mosebacke torg', $question_c->id ),
				),
			),
			array(
				'Göteborg',
				array(
					array( 57.697341, 11.979380, 'Göteplatsen', $question_a->id ),
					array( 57.697025, 11.990975, 'Liseberg', $question_b->id ),
					array( 57.704861, 11.985694, 'Nya Ullevi', $question_c->id ),
				),
			),
		);

		foreach ( $config as $city_config ) {
			list($name, $markers)      = $city_config;
			$map_props                 = new Map();
			$map_props->competition_id = $competition->id;
			$map_props->name           = $name;

			$map_id = $this->map_dao->create( $map_props );
			if ( $map_id === false ) {
				throw new Exception( 'Could not create map.' );
			}
			foreach ( $markers as $marker_config ) {
				list($lat, $long, $label, $question_id) = $marker_config;
				$marker_props                           = new Marker();
				$marker_props->map_id                   = $map_id;
				$marker_props->gps_coord_lat            = $lat;
				$marker_props->gps_coord_long           = $long;
				$marker_props->type                     = Marker::MARKER_TYPE_TASK;
				$marker_props->name                     = $label;
				$marker_props->description              = null;
				$marker_props->link_form_id             = null;
				$marker_props->link_form_question_id    = $question_id;
				$marker_props->link_question_group_id   = null;
				$marker_props->link_station_id          = null;

				$marker_id = $this->marker_dao->create( $marker_props );
				if ( $marker_id === false ) {
					throw new Exception( 'Could not create map marker.' );
				}
			}
		}

		return $map_id; // Return id for last created map
	}

	private function create_common_sendout_templates( Competition $competition ) {
		$templates = MessageTemplate::default_templates();
		foreach ( $templates as $template ) {
			$template->competition_id = $competition->id;

			$template_id = $this->message_template_dao->create( $template );
			if ( $template_id === false ) {
				throw new Exception( 'Could not create sendout template.' );
			}
		}
	}
}
