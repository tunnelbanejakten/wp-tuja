<?php namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\Group;
use tuja\data\model\StationWeight;

$this->print_root_menu();
$this->print_menu();
?>

<h3>Poäng</h3>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
    <table>
        <thead>
        <tr>
            <th>Lag</th>
			<?= join( array_map( function ( Station $station ) {
				return sprintf( '<th>Station %s</th>', $station->name );
			}, $stations ) ); ?>
        </tr>
        </thead>
        <tbody>

		<?= join( array_map( function ( Group $group ) use ( $points_by_key, $stations ) {
			$inputs = join( array_map( function ( Station $station ) use ( $points_by_key, $group ) {
                $field_key = self::get_field_key($station->id, $group->id);
                return sprintf( '<td>
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
			}, $stations ) );

			return sprintf( '
            <tr>
                <td>%s</td>
                %s
            </tr>',
				$group->name,
				$inputs );
		}, $groups ) ); ?>
        </tbody>
    </table>

	<?= $save_button ?>
</form>
