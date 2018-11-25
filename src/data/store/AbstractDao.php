<?php

namespace data\store;

use DateTime;
use tuja\data\model\Competition;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\Message;
use tuja\data\model\Person;
use tuja\data\model\Points;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\util\Id;
use util\Phone;

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

    // TODO: Move all to_* methods to the corresponding model classes?
    protected static function to_competition($result): Competition
    {
        $c = new Competition();
        $c->name = $result->name;
        $c->id = $result->id;
        $c->random_id = $result->random_id;
        return $c;
    }

    protected static function to_form($result): Form
    {
        $f = new Form();
        $f->id = $result->id;
        $f->competition_id = $result->competition_id;
        $f->name = $result->name;
        $f->allow_multiple_responses_per_group = $result->allow_multiple_responses_per_team;
        $f->accept_responses_from = $result->accept_responses_from;
        $f->accept_responses_until = $result->accept_responses_until;
        return $f;
    }

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
        $p->phone = Phone::fix_phone_number($result->phone);
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
        $r->created = new DateTime($result->created);
        $r->is_reviewed = $result->is_reviewed;
        return $r;
    }

    protected static function to_points($result): Points
    {
        $p = new Points();
        $p->form_question_id = $result->form_question_id;
        $p->group_id = $result->team_id;
        $p->points = $result->points;
        $p->created = new DateTime($result->created);
        return $p;
    }

    protected static function to_message($result): Message
    {
        $m = new Message();
        $m->id = $result->id;
        $m->form_question_id = $result->form_question_id;
        $m->group_id = $result->team_id;
        $m->text = $result->text;
        $m->image_ids = explode(',', $result->image);
        $m->source = $result->source;
        $m->source_message_id = $result->source_message_id;
        $m->date_received = new DateTime($result->date_received);
        $m->date_imported = new DateTime($result->date_imported);
        return $m;
    }

}