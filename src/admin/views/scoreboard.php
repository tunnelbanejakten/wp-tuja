<?php namespace tuja\admin;

use tuja\data\store\ResponseDao;

AdminUtils::printTopMenu( $competition );
$this->print_menu();
$this->print_leaves_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table class="tuja-table">
		<tbody>
		<tr>
			<td></td>
			<td><span title="Andel av uppgifterna som poängsatts" class="progress-bar-wrapper">Avklarat</span></td>
			<td>Orättade svar</td><td>Poäng</td>
		</tr>
		<?php
		foreach ( $team_scores_by_category as $key => $team_scores ) {
			printf( '<tr><td><strong>%s</strong></td></tr>', $key );
			foreach ( $team_scores as $team_score ) {
				$group_url = add_query_arg(
					array(
						'tuja_group' => $team_score['group_id'],
						'tuja_view'  => 'Group',
					)
				);
				printf(
					'
					<tr>
						<td><a href="%s">%s</a></td>
						<td><div title="Andel av uppgifterna som poängsatts" class="progress-bar-wrapper"><div class="progress-bar" style="width: %d%%"></div></div></td>
						<td>%s</td>
						<td><span id="tuja-scoreboard-group-%s-points" data-score="%d"></span>%d p</td>
					</tr>',
					$group_url,
					htmlspecialchars( $team_score['group_name'] ),
					$team_score['progress'] * 100,
					$team_score['unreviewed_count'] > 0
						? sprintf(
							'<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-decent"><a href="%s">%s svar</a> behöver rättas</span>',
							$team_score['unreviewed_link'],
							$team_score['unreviewed_count']
						)
						: 'Nej',
					$team_score['group_id'],
					$team_score['score'],
					$team_score['score'],
				);
			}
		}
		?>
		</tbody>
	</table>
</form>
