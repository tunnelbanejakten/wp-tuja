<?php

namespace tuja\util\score;

class ScoreCalculator
{
    private $competition_id;
    private $question_dao;
    private $response_dao;
    private $group_dao;
    private $points_dao;

    public function __construct($competition_id, $question_dao, $response_dao, $group_dao, $points_dao)
    {
        $this->competition_id = $competition_id;
        $this->question_dao = $question_dao;
        $this->response_dao = $response_dao;
        $this->group_dao = $group_dao;
        $this->points_dao = $points_dao;
    }

    public function score($group_id)
    {
        return array_sum($this->score_per_question($group_id));
    }

    /**
     * Calculates total score for a single team.
     */
    public function score_per_question($group_id, $consider_overrides = true)
    {
	    $points_overrides = array();
	    if ( $consider_overrides ) {
		    $points           = $this->points_dao->get_by_group($group_id);
		    $points_overrides = array_combine(array_map(function ($points) {
			    return $points->form_question_id;
		    }, $points), $points);
	    }

        $scores = [];
        $responses = $this->response_dao->get_latest_by_group($group_id);

        // TODO: Cache response of get_all_in_competition? (Unnecessary to call it once per team.)
        $questions = $this->question_dao->get_all_in_competition($this->competition_id);
        foreach ($questions as $question) {
	        if ( isset( $responses[ $question->id ] ) ) {
		        $answers = $responses[ $question->id ]->answers;
		        // TODO: How should the is_reviewed flag be used? Only count points for answers where is_reviewed = true?
		        if ( isset( $answers ) ) {
			        $scores[ $question->id ] = $question->score( $answers );
		        }
	        }
	        if ( $consider_overrides
	             && isset( $points_overrides[ $question->id ] )
	             && isset( $responses[ $question->id ] )
	             && $points_overrides[ $question->id ]->created > $responses[ $question->id ]->created ) {
                $scores[$question->id] = $points_overrides[$question->id]->points;
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