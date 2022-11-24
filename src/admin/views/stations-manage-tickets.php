<?php namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\Group;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Hantera biljetter</h3>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
    <div>
		Välj lösenord att registrera när biljetter delas ut från denna sida:<br>
		<?php echo $station_password_options; ?>
	</div>
	<table class="tuja-table">
		<thead>
		<tr>
			<th>Lag</th>
			<?php
			echo join(
				array_map(
					function ( Station $station ) {
						return sprintf( '<th>Station %s</th>', $station->name );
					},
					$stations
				)
			);
			?>
		</tr>
		</thead>
		<tbody>

		<?php
		echo join(
			array_map(
				function ( Group $group ) use ( &$form_actions, $stations ) {
					$inputs = join(
						array_map(
							function ( Station $station ) use ( &$form_actions, $group ) {
								$field_key = self::get_field_key( $station->id, $group->id );
								return sprintf( '<td>%s</td>', @$form_actions[ $field_key ] ?? '' );
							},
							$stations
						)
					);
					return sprintf(
						'<tr><td>%s</td>%s</tr>',
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
</form>
