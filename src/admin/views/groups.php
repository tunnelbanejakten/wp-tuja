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
		foreach ( $groups_data as $group_data ) {
			$group = $group_data['model'];

			print '<tr>';

			// Print name and age range
			printf( '<td><a href="%s">%s</a></td>' .
			        '<td>%.1f (%.1f-%.1f) år</td>',
				$group_data['details_link'],
				htmlspecialchars( $group->name ),
				$group->age_competing_avg,
				$group->age_competing_min,
				$group->age_competing_max
			);

			// Print group category selector
			$category_options = [];
			foreach ( $group_category_map as $id => $label ) {
				$category_options[] = sprintf( '<option value="%d" %s>%s</option>',
					$id,
					$id === $_POST[ 'tuja_group_type__' . $id ] || $id == $group->category_id ? 'selected="selected"' : '',
					$label );
			}
			printf( '<td><select name="tuja_group__%d__category"><option value="0">Systemet väljer</option>%s</select></td>' .
			        '<td>%s</td>',
				$group->id,
				join( '', $category_options ),
				$group_data['category'] ? $group_data['category']->name : ''
			);

			// Print summary of group members
			printf( '<td>%d st</td>' .
			        '<td>%d st</td>' .
			        '<td>%d st</td>',
				$group->count_competing,
				$group->count_follower,
				$group->count_team_contact
			);

			// Print summary sign-up status
			printf( '<td>%s %s %s</td>',
				$group_data['registration_blocker_count'] > 0 ?
					sprintf(
						'<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-poor">%s problem</span>',
						$group_data['registration_blocker_count'] )
					: '',
				$group_data['registration_warning_count'] > 0 ?
					sprintf(
						'<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-decent">%s varningar</span>',
						$group_data['registration_warning_count'] ) :
					'',
				$group_data['registration_blocker_count'] == 0 && $group_data['registration_warning_count'] == 0 ?
					sprintf( '<span class="tuja-admin-review-autoscore tuja-admin-review-autoscore-good">Komplett och korrekt</span>' ) :
					''
			);

			print '</tr>';
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
		foreach ( $group_category_map as $id => $label ) {
			printf( '<option value="%d">%s</option>', $id, $label );
		}
		?>
    </select>
    <div class="tuja-buttons">
        <button type="submit" class="button" name="tuja_action" value="group_create">Skapa</button>
    </div>

    <h3>Dataskydd</h3>

    <p>Om du inte vill ta bort lagen men ändå vill skydda personuppgifterna så kan du använda verktyget för att anonymisera personuppgifter.</p>
    <p>
        <input type="radio" name="tuja_anonymizer_filter" value="all" id="tuja_anonymizer_filter_all"><label for="tuja_anonymizer_filter_all">Anonymisera personuppgifter för <em>alla tävlande och funktionärer</em></label><br/>
        <input type="radio" name="tuja_anonymizer_filter" value="participants" id="tuja_anonymizer_filter_participants"><label for="tuja_anonymizer_filter_participants">Anonymisera personuppgifter för <em>alla tävlande</em></label><br/>
        <input type="radio" name="tuja_anonymizer_filter" value="non_contacts" id="tuja_anonymizer_filter_non_contacts"><label for="tuja_anonymizer_filter_non_contacts">Anonymisera personuppgifter för <em>alla tävlande som inte är kontaktpersoner</em></label><br/>
    </p>
    <p><input type="checkbox" name="tuja_anonymizer_confirm" id="tuja_anonymizer_confirm" value="true"><label for="tuja_anonymizer_confirm">Ja, jag vill verkligen anonymisera personuppgifterna</label></p>

    <div class="tuja-buttons">
        <button type="submit" class="button" name="tuja_action" value="anonymize">Anonymisera valda personuppgifter</button>
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
