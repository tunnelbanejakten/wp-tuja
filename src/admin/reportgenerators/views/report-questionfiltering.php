<ul>
	<?php
	foreach ( $forms as $form ) {
		printf( '<li>%s<ul>', $form['form_name'] );
		foreach ( $form['question_groups'] as $question_group ) {
			printf( '<li>%s<ul>', $question_group['question_group'] );
			foreach ( $question_group['questions_by_team'] as $questions_by_team ) {
				printf( '<li>%s<ul>', $questions_by_team['team_name'] );
				foreach ( $questions_by_team['questions'] as $question ) {
					printf( '<li data-question-id="%d" data-team-id="%d">%s</li>', $question['id'], $questions_by_team['team_id'], $question['text'] );
				}
				printf( '</ul></li>' );
			}
			printf( '</ul></li>' );
		}
		printf( '</ul></li>' );
	}
	?>
</ul>
