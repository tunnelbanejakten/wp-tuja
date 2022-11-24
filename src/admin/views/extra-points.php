<?php namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\Group;
use tuja\data\model\StationWeight;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Bonuspoäng <?php AdminUtils::printTooltip( 'Bonuspoäng är, som namnet antyder, extra poäng som lagen får utöver de poäng som tilldelas av funktionärer på stationer och som erhålles genom att svara rätt på formulären. Det går att dela ut hur många bonuspoäng som helst. Det är även möjligt att ge negativa bonuspoäng, vilket innebär poängavdrag från lagets totalpoäng.' ); ?></h3>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<table>
		<thead>
		<tr>
			<th>Etikett <?php AdminUtils::printTooltip( 'Etiketter beskriver kortfattat varför ett lag har fått bonuspoäng, exempelvis "Hyllningssång" eller "Bonusuppgift 1". Om flera lag kan få bonuspoäng av samma orsak så är det smidigt att använda samma etikett för dessa bonuspoäng.' ); ?></th>
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
