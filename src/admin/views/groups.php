<?php namespace tuja\admin;

use tuja\data\store\GroupCategoryDao;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>">
    <table>
        <thead>
        <tr>
            <th rowspan="2">Namn</th>
            <th rowspan="2">Ålder</th>
            <th colspan="2">Tävlingsklass</th>
            <th colspan="3">Antal</th>
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
		foreach ( $groups as $group ) {
			$group_url = add_query_arg( array(
				'tuja_group' => $group->id,
				'tuja_view'  => 'Group'
			) );
			$category  = $category_calculator->get_category( $group );
			printf( '<tr>' .
			        '<td><a href="%s">%s</a></td>' .
			        '<td>%.1f (%.1f-%.1f) år</td>' .
			        '<td>%s</td>' .
			        '<td>%s</td>' .
			        '<td>%d st</td>' .
			        '<td>%d st</td>' .
			        '<td>%d st</td>' .
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
				$group->count_team_contact
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
