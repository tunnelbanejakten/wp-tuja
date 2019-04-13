<?php namespace tuja\admin;

use tuja\data\model\GroupCategory;
use tuja\util\rules\RuleResult;
use tuja\data\store\GroupCategoryDao;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>">
    <table>
        <thead>
        <tr>
            <th rowspan="2" valign="top">Namn</th>
            <th rowspan="2" valign="top">Ålder</th>
            <th colspan="2" valign="top">Tävlingsklass</th>
            <th colspan="3" valign="top">Antal</th>
            <th rowspan="2" valign="top">Anmälningsstatus</th>
        </tr>
        <tr>
            <td>Vald</td>
            <td>Faktisk</td>
            <td>Tävlande</td>
            <td>Övriga</td>
            <td>Kontakter</td>
        </tr>
        </thead>
        <tbody>
		<?php

		$groups_per_category = [];
		$groups_competing    = 0;
		$people_competing    = 0;

		$category_unknown          = new GroupCategory();
		$category_unknown->name    = 'okänd';
		$category_unknown->is_crew = false;

		foreach ( $groups as $group ) {
			$registration_evaluation = $registration_evaluator->evaluate( $group );

			$registration_warning_count = count( array_filter( $registration_evaluation, function ( RuleResult $res ) {
				return $res->status === RuleResult::WARNING;
			} ) );

			$registration_blocker_count = count( array_filter( $registration_evaluation, function ( RuleResult $res ) {
				return $res->status === RuleResult::BLOCKER;
			} ) );

			$group_url = add_query_arg( array(
				'tuja_group' => $group->id,
				'tuja_view'  => 'Group'
			) );
			$category  = $category_calculator->get_category( $group ) ?: $category_unknown;

			$groups_per_category[ $category->name ] += 1;
			if ( ! $category->is_crew ) {
				$groups_competing += 1;
				$people_competing += $group->count_competing;
			}
			printf( '<tr>' .
			        '<td><a href="%s">%s</a></td>' .
			        '<td>%.1f (%.1f-%.1f) år</td>' .
			        '<td>%s</td>' .
			        '<td>%s</td>' .
			        '<td>%d st</td>' .
			        '<td>%d st</td>' .
			        '<td>%d st</td>' .
			        '<td>%s %s %s</td>' .
			        '</tr>',
				$group_url,
				htmlspecialchars( $group->name ),
				$group->age_competing_avg,
				$group->age_competing_min,
				$group->age_competing_max,
				sprintf( '    <select name="tuja_group__%d__category">%s</select>',
					$group->id,
					join( '',
						array_merge(
							[ '<option value="0">Systemet väljer</option>' ],
							array_map( function ( $category ) use ( $group ) {
								return sprintf( '<option value="%d" %s>%s (%s)</option>',
									$category->id,
									$category->id === $_POST[ 'tuja_group_type__' . $group->id ] || $category->id === $group->category_id ? 'selected="selected"' : '',
									$category->name,
									$category->is_crew ? 'Funktionär' : 'Tävlande' );
							}, $group_categories ) ) ) ),
				$category ? $category->name : '',
				$group->count_competing,
				$group->count_follower,
				$group->count_team_contact,
				$registration_blocker_count > 0 ? sprintf( '<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-poor">%s problem</span>', $registration_blocker_count ) : '',
				$registration_warning_count > 0 ? sprintf( '<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-decent">%s varningar</span>', $registration_warning_count ) : '',
				$registration_warning_count == 0 && $registration_blocker_count == 0 ? sprintf( '<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-good">Komplett och korrekt</span>' ) : ''
			);
		}
		?>
        </tbody>
    </table>

    <div class="tuja-buttons">
        <button type="submit" class="button" name="tuja_action" value="group_update">Uppdatera</button>
    </div>

    <input type="text" name="tuja_new_group_name"/>
    <select name="tuja_new_group_type">
		<?php
		$category_dao = new GroupCategoryDao();
		print join( '', array_map( function ( $category ) {
			return sprintf( '<option value="%d">%s (%s)</option>',
				$category->id,
				$category->name,
				$category->is_crew ? 'Funktionär' : 'Tävlande' );
		}, $category_dao->get_all_in_competition( $competition->id ) ) );
		?>
    </select>
    <div class="tuja-buttons">
        <button type="submit" class="button" name="tuja_action" value="group_create">Skapa</button>
    </div>
</form>

<h3>Statistik</h3>

<table>
    <tbody>
    <tr>
        <td>Antal tävlande personer:</td>
        <td><?= $people_competing ?> st</td>
    </tr>
    <tr>
        <td>Antal tävlande lag:</td>
        <td><?= $groups_competing ?> st</td>
    </tr>
	<?php foreach ( $groups_per_category as $name => $count ) { ?>
        <tr>
            <td style="padding-left: 2em">varav i kategori <?= $name ?>:</td>
            <td><?= $count ?> st</td>
        </tr>
	<?php } ?>
    </tbody>
</table>