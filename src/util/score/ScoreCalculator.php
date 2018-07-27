<?php

namespace util\score;

class ScoreCalculator
{
    private $competition_id;
    private $question_dao;
    private $response_dao;
    private $group_dao;

    /**
     * ScoreCalculator constructor.
     * @param $competition_id
     * @param $question_dao
     * @param $response_dao
     * @param $group_dao
     */
    public function __construct($competition_id, $question_dao, $response_dao, $group_dao)
    {
        $this->competition_id = $competition_id;
        $this->question_dao = $question_dao;
        $this->response_dao = $response_dao;
        $this->group_dao = $group_dao;
    }

    public function score($group_id)
    {
        return array_sum($this->score_per_question($group_id));
    }

    /**
     * Calculates total score for a single team.
     */
    public function score_per_question($group_id)
    {
        $scores = [];
        $responses = $this->response_dao->get_by_group($group_id);
        // TODO: This sorting is done in multiple methods. Move to get_by_group method?
        usort($responses, function ($a, $b) {
            return $a->id < $b->id ? 1 : -1;
        });

        $last_response = [];
        foreach (array_reverse($responses) as $response) {
            $last_response[$response->form_question_id] = $response->answers;
        }

        // TODO: Cache response of get_all_in_competition? (Unnecessary to call it once per team.)
        $questions = $this->question_dao->get_all_in_competition($this->competition_id);
        foreach ($questions as $question) {
            $answers = $last_response[$question->id];
            if (isset($answers)) {
                $scores[$question->id] = $question->score($answers);
            }
        }

        return $scores;
    }

    public function score_board()
    {
        $result = [];
        $groups = $this->group_dao->get_all_in_competition($this->competition_id);
        foreach ($groups as $group) {
            $group_result = [];
            // TODO: Return proper objects instead of associative arrays.
            $group_result['group_id'] = $group->id;
            $group_result['group_name'] = $group->name;
            $group_result['score'] = $this->score($group->id);
            $result[] = $group_result;
        }
        return $result;
    }
}