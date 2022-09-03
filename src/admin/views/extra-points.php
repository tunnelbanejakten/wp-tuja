<?php namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\Group;
use tuja\data\model\StationWeight;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<h3>Bonuspoäng</h3>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table>
		<thead>
		<tr>
			<th>Etikett</th>
			<?php
			echo join(
				array_map(
					function ( string $name ) {
						$name_field_key = self::get_field_key( $name, self::MAGIC_NUMBER_NAME_FIELD_ID );
						$read_only      = ! empty( $name );
						return sprintf( '<td><input type="text" name="%s" id="%s" value="%s" %s></td>', $name_field_key, $name_field_key, $name, $read_only ? 'readonly' : '' );
					},
					$all_names
				)
			);
			?>
		</tr>
		</thead>
		<tbody>

		<?php
		echo join(
			array_map(
				function ( Group $group ) use ( $points_by_key, $all_names ) {
					$inputs = join(
						array_map(
							function ( string $name ) use ( $points_by_key, $group ) {
								$field_key = self::get_field_key( $name, $group->id );
								return sprintf(
									'<td>
                                        <input
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            value="%s"
                                            id="%s" 
                                            name="%s">
                                    </td>',
									@$points_by_key[ $field_key ] ?? '',
									$field_key,
									$field_key
								);
							},
							$all_names
						)
					);

					return sprintf(
						'<tr>
                            <td>%s</td>
                            %s
                        </tr>',
						$group->name,
						$inputs
					);
				},
				$groups
			)
		);
		?>
		</tbody>
	</table>

	<?php echo $save_button; ?>
</form>
