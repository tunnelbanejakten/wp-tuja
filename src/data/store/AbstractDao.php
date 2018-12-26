<?php

namespace data\store;

use DateTime;
use DateTimeZone;
use tuja\data\model\Competition;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Points;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\util\Id;

class AbstractDao
{
    protected $id;
    protected $wpdb;

    function __construct($wpdb)
    {
        $this->id = new Id();
        $this->wpdb = $wpdb;
    }

    protected function get_object($mapper, $query, ...$arguments)
    {
        $db_results = $this->wpdb->get_results($this->wpdb->prepare($query, $arguments), OBJECT);
        if ($db_results !== false && count($db_results) > 0) {
            return $mapper($db_results[0]);
        }
        return false;
    }

    protected function get_objects($mapper, $query, ...$arguments)
    {
        $db_results = $this->wpdb->get_results($this->wpdb->prepare($query, $arguments), OBJECT);
        $results = [];
        foreach ($db_results as $result) {
            $results[] = $mapper($result);
        }
        return $results;
    }

    // TODO: Move all to_* methods to the corresponding model classes. Already done for FormDao and CompetitionDao.
    protected static function to_group($result): Group
    {
        $g = new Group();
        $g->id = $result->id;
        $g->random_id = $result->random_id;
        $g->name = $result->name;
        $g->type = $result->type;
        $g->competition_id = $result->competition_id;
        return $g;
    }

    protected static function to_person($result): Person
    {
        $p = new Person();
        $p->id = $result->id;
        $p->random_id = $result->random_id;
        $p->name = $result->name;
        $p->group_id = $result->team_id;
        $p->phone = $result->phone;
        $p->phone_verified = $result->phone_verified;
        $p->email = $result->email;
        $p->email_verified = $result->email_verified;
        return $p;
    }

    protected static function to_form_question($result): Question
    {
        $q = new Question();
        $q->id = $result->id;
        $q->form_id = $result->form_id;
        $q->type = $result->type;
        $q->possible_answers = json_decode($result->answer, true)['options'];
        $q->correct_answers = json_decode($result->answer, true)['values'];
        $q->score_type = json_decode($result->answer, true)['score_type'];
        $q->score_max = json_decode($result->answer, true)['score_max'];
        $q->text = $result->text;
        $q->sort_order = $result->sort_order;
        $q->text_hint = $result->text_hint;
        return $q;
    }

    protected static function to_response($result): Response
    {
        $r = new Response();
        $r->id = $result->id;
        $r->form_question_id = $result->form_question_id;
        $r->group_id = $result->team_id;
        $r->answers = json_decode($result->answer);
        $r->created = self::from_db_date($result->created_at);
        $r->is_reviewed = $result->is_reviewed;
        return $r;
    }

    protected static function to_points($result): Points
    {
        $p = new Points();
        $p->form_question_id = $result->form_question_id;
        $p->group_id = $result->team_id;
        $p->points = $result->points;
        $p->created = self::from_db_date($result->created_at);
        return $p;
    }

    protected static function to_db_date(DateTime $dateTime = null)
    {
        if ($dateTime != null) {
            return $dateTime->getTimestamp(); // Unix timestamps are always UTC
        } else {
            return null;
        }
    }

    protected static function from_db_date($dbDate)
    {
        if (!empty($dbDate)) {
            return new DateTime('@' . $dbDate, new DateTimeZone('UTC'));
        } else {
            return null;
        }
    }

}