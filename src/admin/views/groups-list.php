<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<p>Filter: <?= $filters ?>.</p>
<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
    <table id="tuja_groups_list" class="tuja-table">
        <thead>
        <tr>
            <th rowspan="2" valign="top"><input type="checkbox" id="tuja_group_toggle_all"></th>
            <th rowspan="2" valign="top">Namn</th>
            <th rowspan="2" valign="top">Ålder</th>
            <th rowspan="2" valign="top">Tävlingsklass</th>
            <th rowspan="2" valign="top">Ort <?php AdminUtils::printTooltip( 'Fritextfält som anges av lagen själva vid anmälan.' ); ?></th>
            <th rowspan="2" valign="top">Karta <?php AdminUtils::printTooltip( 'Den karta som laget fått tilldelad.' ); ?></th>
            <th colspan="3" valign="top">Antal</th>
            <th colspan="3" valign="top">Anmälningsstatus</th>
        </tr>
        <tr>
            <td>Tävlande</td>
            <td>Övriga</td>
            <td>Kontakter</td>
            <td>Status</td>
            <td>Sena ändr. <?php AdminUtils::printTooltip( 'Kan laget ändra sin anmälan även efter att anmälan stängt?' ); ?></td>
            <td>Meddelanden</td>
        </tr>
        </thead>
		<?php if ( ! empty( $groups_data ) ) { ?>
            <tfoot>
            <tr>
                <td colspan="3" valign="top">&nbsp;&nbsp;&rdsh; För valda grupper:</td>
                <td>
					<?php
					// Print group category selector
					$category_options = [];
					foreach ( $group_category_map as $id => $label ) {
						$category_options[] = sprintf( '<option value="%d">%s</option>',
							$id,
							$label );
					}
					printf( '<select name="tuja_group_batch__category">%s</select>',
						join( '', $category_options )
					);
					?>
                    <div class="tuja-buttons">
                        <button type="submit" class="button" name="tuja_action" value="tuja_group_batch__category">
                            Ändra
                        </button>
                    </div>
                </td>
				<td></td>
                <td>
					<?php
					// Print map selector
					$map_options = [];
					foreach ( $map_map as $id => $label ) {
						$map_options[] = sprintf( '<option value="%d">%s</option>',
							$id,
							$label );
					}
					printf( '<select name="tuja_group_batch__map">%s</select>',
						join( '', $map_options )
					);
					?>
                    <div class="tuja-buttons">
                        <button type="submit" class="button" name="tuja_action" value="tuja_group_batch__map">
                            Ändra
                        </button>
                    </div>
                </td>
                <td colspan="4"></td>
                <td>
					<?php
					$status_options = [];
					foreach ( array_keys( \tuja\data\model\Group::STATUS_TRANSITIONS ) as $key ) {
						$status_options[] = sprintf( '<option value="%s">%s</option>',
							$key,
							$key );
					}
					printf( '<select name="tuja_group_batch__status">%s</select>',
						join( '', $status_options )
					);
					?>
                    <div class="tuja-buttons">
                        <button type="submit" class="button" name="tuja_action" value="tuja_group_batch__status">Ändra
                        </button>
                    </div>
                </td>
                <td>
                    <select name="tuja_group_batch__alwayseditable">
                        <option value="yes">Ja</option>
                        <option value="no">Nej</option>
                    </select>
                    <div class="tuja-buttons">
                        <button type="submit" class="button" name="tuja_action"
                                value="tuja_group_batch__alwayseditable">Ändra
                        </button>
                    </div>
                </td>
                <td colspan="4"></td>
            </tr>
            </tfoot>
		<?php } ?>
        <tbody>
		<?php
		foreach ( $groups_data as $group_data ) {
			$group = $group_data['model'];

			print '<tr>';

			printf( '<td data-group-key="%s"><input type="checkbox" name="tuja_group__selection[]" value="%d" class="tuja-group-checkbox" %s></td>',
				$group->random_id,
				$group->id,
				@in_array( $group->id, $_POST['tuja_group__selection'] ?? array() ) ? 'checked="checked"' : ''
			);

			// Print name and age range
			printf( '<td><a href="%s">%s</a></td>' .
			        '<td><span title="%.1f år (%.1f-%.1f)">&approx;%.0f år</span></td>',
				$group_data['details_link'],
				htmlspecialchars( $group->name ),
				$group->age_competing_avg,
				$group->age_competing_min,
				$group->age_competing_max,
				$group->age_competing_avg
			);

			// Print group category selector
			$category_options = [];
			foreach ( $group_category_map as $id => $label ) {
				$category_options[] = sprintf( '<option value="%d" %s>%s</option>',
					$id,
					$id === @$_POST[ 'tuja_group_type__' . $id ] || $id == $group->category_id ? 'selected="selected"' : '',
					$label );
			}
			printf( '<td>%s</td>', $group_data['category'] ? $group_data['category']->name : '' );

			printf( '<td><span class="tuja-group-city" title="%s">%s</span></td>', $group->city, $group->city);

			printf( '<td>%s</td>', isset($group->map_id) ? $map_map[ $group->map_id ] : '' );

			// Print summary of group members
			printf( '<td>%d st</td>' .
			        '<td>%d st</td>' .
			        '<td>%d st</td>',
				$group->count_competing,
				$group->count_follower,
				$group->count_team_contact
			);

			// Print group status
			printf( '<td><span class="tuja-admin-groupstatus tuja-admin-groupstatus-%s">%s</span></td>',
				$group->get_status(),
				$group->get_status()
			);

			printf( '<td>%s</td>', $group->is_always_editable ? 'Ja, tillåtet' : 'Nej' );

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
</form>
