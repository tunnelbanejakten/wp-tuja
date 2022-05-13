<?php
namespace tuja\admin;

use tuja\data\model\Person;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\frontend\router\ReportPointsInitiator;

AdminUtils::printTopMenu( $competition );
?>

<h3>Grupp <?php echo htmlspecialchars( $group->name ); ?> (id: <code><?php echo htmlspecialchars( $group->random_id ); ?></code>)</h3>

<?php printf( '<p><a id="tuja_group_back" href="%s">« Tillbaka till grupplistan</a></p>', $back_url ); ?>
<?php $this->print_menu(); ?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">

<h3>Deltagare</h3>
	<table>
		<thead>
		<tr>
			<th></th>
			<th>Namn</th>
			<th>Personnummer</th>
			<th>Ålder</th>
			<th>Mat</th>
			<th>Meddelande</th>
			<th>Medföljare</th>
			<th>Lagledare</th>
			<th>Telefon</th>
			<th>E-post</th>
			<th>Länk för att redigera</th>
			<th>
			<?php
			if ( $is_crew_group ) {
				?>
				Länk för att rapportera poäng<?php } ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $people ) ) { ?>
			<tfoot>
			<tr>
				<td colspan="8">
					Flytta markerade deltagare till detta lag: <br>
					<select name="tuja_group_move_people_to">
						<option value="0">Välj lag</option>
						<?php
						echo join(
							array_map(
								function ( $g ) use ( $group ) {
									return sprintf(
										'<option value="%s" %s>%s</option>',
										$g->id,
										$group->id == $g->id ? 'disabled="disabled"' : '',
										$g->name
									);
								},
								$groups
							)
						)
						?>
					</select>
					<button class="button" type="submit" name="tuja_points_action" value="move_people">Flytta</button>
				</td>
			</tr>
			</tfoot>
		<?php } ?>
		<tbody>
		<?php
		print join(
			'',
			array_map(
				function ( Person $person ) use ( $group, $is_crew_group ) {
					$person_edit_link   = PersonEditorInitiator::link( $group, $person );
					$report_points_link = $is_crew_group ? ReportPointsInitiator::link_all( $person ) : '';

					return sprintf(
						'<tr class="tuja-person-status-%s">' .
							'<td><input type="checkbox" name="tuja_group_people[]" value="%d" id="tuja_group_people__person_%d"></td>' .
							'<td><label for="tuja_group_people__person_%d">%s</label></td>' .
							'<td>%s</td>' .
							'<td>%.1f</td>' .
							'<td><em>%s</em></td>' .
							'<td><em>%s</em></td>' .
							'<td>%s</td>' .
							'<td>%s</td>' .
							'<td>%s</td>' .
							'<td><a href="mailto:%s">%s</a></td>' .
							'<td><a href="%s">%s</a></td>' .
							'<td><a href="%s">%s</a></td>' .
							'</tr>',
						$person->get_status(),
						$person->id,
						$person->id,
						$person->id,
						$person->name,
						$person->pno,
						$person->age,
						$person->food,
						$person->note,
						$person->is_adult_supervisor() ? 'Ja' : '',
						$person->is_group_leader() ? 'Ja' : '',
						$person->phone,
						$person->email,
						$person->email,
						$person_edit_link,
						$person_edit_link,
						$report_points_link,
						$report_points_link
					);
				},
				$people
			)
		);
		?>
		</tbody>
	</table>

</form>
