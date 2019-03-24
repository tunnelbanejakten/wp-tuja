<?php

namespace tuja\data\store;

use DateTime;
use DateTimeZone;
use tuja\data\model\Competition;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Message;
use tuja\data\model\Person;
use tuja\data\model\Points;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\util\Id;
use tuja\util\Phone;

class AbstractDao {
	protected $id;
	protected $wpdb;
	protected $table;

	function __construct() {
		global $wpdb;
		$this->id   = new Id();
		$this->wpdb = $wpdb;
	}

	protected function get_object( $mapper, $query, ...$arguments ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $arguments ), OBJECT );
		if ( $db_results !== false && count( $db_results ) > 0 ) {
			return $mapper( $db_results[0] );
		}

		return false;
	}

	protected function get_objects( $mapper, $query, ...$arguments ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $arguments ), OBJECT );
		$results    = [];
		foreach ( $db_results as $result ) {
			$results[] = $mapper( $result );
		}

		return $results;
	}

	// TODO: Move all to_* methods to the corresponding model classes. Already done for FormDao and CompetitionDao.
	protected static function to_group( $result ): Group {
		$g                 = new Group();
		$g->id             = $result->id;
		$g->random_id      = $result->random_id;
		$g->name           = $result->name;
		$g->category_id    = $result->category_id;
		$g->competition_id = $result->competition_id;

		return $g;
	}

	protected static function to_group_category( $result ): GroupCategory {
		$gc                 = new GroupCategory();
		$gc->id             = $result->id;
		$gc->competition_id = $result->competition_id;
		$gc->is_crew        = $result->is_crew != 0;
		$gc->name           = $result->name;

		return $gc;
	}

	protected static function to_person( $result ): Person {
		$p                   = new Person();
		$p->id               = $result->id;
		$p->random_id        = $result->random_id;
		$p->name             = $result->name;
		$p->group_id         = $result->team_id;
		// TODO: Should normalizing the phone number be something we do when we read it from the database? Why not when stored?
		$p->phone            = Phone::fix_phone_number( $result->phone );
		$p->phone_verified   = $result->phone_verified;
		$p->email            = $result->email;
		$p->email_verified   = $result->email_verified;
		$p->is_competing     = $result->is_competing != 0;
		$p->is_group_contact = $result->is_team_contact != 0;
		$p->food             = $result->food;
		$p->pno              = $result->pno;

		return $p;
	}

	protected static function to_form_question( $result ): Question {
		$q                   = new Question();
		$q->id               = $result->id;
		$q->form_id          = $result->form_id;
		$q->type             = $result->type;
		$q->possible_answers = json_decode( $result->answer, true )['options'];
		$q->correct_answers  = json_decode( $result->answer, true )['values'];
		$q->score_type       = json_decode( $result->answer, true )['score_type'];
		$q->score_max        = json_decode( $result->answer, true )['score_max'];
		$q->text             = $result->text;
		$q->sort_order       = $result->sort_order;
		$q->text_hint        = $result->text_hint;

		return $q;
	}

	protected static function to_message( $result ): Message {
		$m                    = new Message();
		$m->id                = $result->id;
		$m->form_question_id  = $result->form_question_id;
		$m->group_id          = $result->team_id;
		$m->text              = $result->text;
		$m->image_ids         = explode( ',', $result->image );
		$m->source            = $result->source;
		$m->source_message_id = $result->source_message_id;
		$m->date_received     = new DateTime( $result->date_received );
		$m->date_imported     = new DateTime( $result->date_imported );

		return $m;
	}

	protected static function to_db_date( DateTime $dateTime = null ) {
		if ( $dateTime != null ) {
			return $dateTime->getTimestamp(); // Unix timestamps are always UTC
		} else {
			return null;
		}
	}

	protected static function from_db_date( $dbDate ) {
		if ( ! empty( $dbDate ) ) {
			return new DateTime( '@' . $dbDate, new DateTimeZone( 'UTC' ) );
		} else {
			return null;
		}
	}

}