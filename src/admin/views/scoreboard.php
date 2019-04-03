<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>">
    <table>
        <tbody>
		<?php
		$calculator  = new ScoreCalculator(
			$competition->id,
			new QuestionDao(),
			new ResponseDao(),
			new GroupDao(),
			new PointsDao() );
		$score_board = $calculator->score_board();
		usort( $score_board, function ( $a, $b ) {
			return $b['score'] - $a['score'];
		} );

		$score_board = array_map( function ( $obj ) use ( $groups, $category_calculator ) {
			$group_found = array_filter( $groups, function ( $group ) use ( $obj, $category_calculator ) {
				return $group->id == $obj['group_id'];
			} );
			$group = reset( $group_found );
//			var_dump( $group );
			$obj['category'] = $category_calculator->get_category( $group );

			return $obj;
		}, $score_board );

		$score_board_by_category = [];
		foreach ( $score_board as $team_score ) {
			$key                               = $team_score['category'] ? $team_score['category']->name : 'Övriga';
			$score_board_by_category[ $key ][] = $team_score;
		}

//		var_dump( $score_board_by_category );

		foreach ( $score_board_by_category as $key => $score_board ) {
			printf( '<tr><td><strong>%s</strong></td></tr>', $key );
			foreach ( $score_board as $team_score ) {
				$group_url = add_query_arg( array(
					'tuja_group' => $team_score['group_id'],
					'tuja_view'  => 'Group'
				) );
				printf( '<tr><td><a href="%s">%s</a></td><td>%d p</td></tr>',
					$group_url,
					htmlspecialchars( $team_score['group_name'] ),
					$team_score['score'] );
			}
		}
		?>
        </tbody>
    </table>
</form>