<?php
namespace tuja\admin;

use tuja\data\model\Person;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
$this->print_leaves_menu();
?>

<p><a href="<?= $add_member_url ?>" id="tuja_group_member_add_link">Lägg till deltagare...</a></p>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">

	<table class="tuja-table">
		<thead>
		<tr>
			<th></th>
			<th>Namn</th>
			<th>Roll</th>
			<th>Ålder</th>
			<th>Mat</th>
			<th>Meddelande</th>
			<th>Telefon</th>
			<th>E-post</th>
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
				function ( Person $person ) {
					$admin_person_url = add_query_arg(
						array(
							'tuja_competition' => $this->competition->id,
							'tuja_person'      => $person->id,
							'tuja_view'        => 'GroupMember',
						)
					);

					return sprintf(
						'<tr class="tuja-person-status-%s">' .
							'<td><input type="checkbox" name="tuja_group_people[]" value="%d" id="tuja_group_people__person_%d"></td>' .
							'<td><a href="%s" id="tuja_group_member_link__%s">%s</a></td>' .
							'<td>%s</td>' .
							'<td>%s</td>' .
							'<td><em>%s</em></td>' .
							'<td><em>%s</em></td>' .
							'<td>%s</td>' .
							'<td><a href="mailto:%s">%s</a></td>' .
							'</tr>',
						$person->get_status(),
						$person->id,
						$person->id,
						$admin_person_url,
						$person->id,
						$person->get_short_description(),
						$person->get_type_label(),
						$person->get_formatted_age(),
						$person->food,
						$person->note,
						$person->phone,
						$person->email,
						$person->email,
					);
				},
				$people
			)
		);
		?>
		</tbody>
	</table>

</form>
