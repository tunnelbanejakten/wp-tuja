<?php namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\StationWeight;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\util\score\ScoreCalculator;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
    <h3>Biljetternas utseende</h3>
    <p>Här fyller du i hur biljetterns ska se ut för respektive station.</p>
    <table>
        <thead>
        <tr>
            <th>Namn</th>
            <th>Färg</th>
            <th>Text</th>
            <th>Symbol</th>
        </tr>
        </thead>
        <tbody>
		<?= join( array_map( function ( Station $station ) use ( $ticket_designs ) {
			$design = @$ticket_designs[ $station->id ];

			return sprintf( '
            <tr data-station-id="%d" data-station-key="%s">
                <td>%s</td>
                <td><input type="color" name="tuja_ticketdesign__%d__colour" value="%s"></td>
                <td><input type="text"  name="tuja_ticketdesign__%d__word"   value="%s"></td>
                <td><input type="text"  name="tuja_ticketdesign__%d__symbol" value="%s"></td>
            </tr>
            ',
				$station->id,
				$station->random_id,
				$station->name,
				$station->id,
				@$design->colour ?: '#ffffff',
				$station->id,
				@$design->word,
				$station->id,
				@$design->symbol );
		}, $stations ) ); ?>
        </tbody>
    </table>

    <h3>Biljettutdelning</h3>
    <p>Mekanismen för att dela ut biljetter till tävlande lag bygger på två komponenter:</p>
    <ol>
        <li>Laget får, efter att en station slutförts, ett lösenord av stationens funktionär. Varje station har alltså bara ett lösenord och samma lösenord delas ut till alla lag som klarar av den station. Notera att detta lösenord <em>inte</em> är samma sak som den <em>text</em> som visas på biljetten (anges ovan). Lösenordet används enbart för att erhålla nya biljetter och ska inte uppges till funktionärer.</li>
        <li>Laget går sedan in på lagportalen och knappar in detta lösenord.</li>
        <li>Laget får (upp till) två biljetter i utbyte.</li>
    </ol>
    <p>Biljetter till nya stationer delas ut "lagom slumpmässigt". Detta innebär att varje lösenord ger laget två nya biljetter: en biljett till en station "i närheten" och en biljett till en station "lite längre bort".</p>
    <p>För att detta ska fungera behöver du konfigurera två saker:</p>
    <ul>
        <li>Lösenord: Det ord som ett lag ska uppge för att kunna få biljett till någon av stationerna på raden.</li>
        <li>Vikt: Avståndet från stationen på raden till stationen i kolumnen. Avståndet är bara ett tal och behöver inte vara exakt eftersom det bara används för att avgöra vad som är "i närheten" (låga tal) och vad som är "lite längre bort" (högre tal).</li>
    </ul>
    <table>
        <thead>
        <tr>
            <th rowspan="2" valign="top">Station</th>
            <th rowspan="2" valign="top">Lösenord efter avklarad station</th>
            <th colspan="<?= count( $stations ) ?>">Avstånd till andra stationer</th>
        </tr>
        <tr>
			<?= join( array_map( function ( Station $station ) {
				return sprintf( '<td>%s</td>', $station->name );
			}, $stations ) ); ?>
        </tr>
        </thead>
        <tbody>

		<?= join( array_map( function ( Station $station ) use ( $ticket_designs, $station_weights, $stations ) {
			$design = @$ticket_designs[ $station->id ];

			$inputs = join( array_map( function ( Station $temp_station ) use ( $station ) {
				return sprintf( '<td>%s</td>', $station->name );
			}, $stations ) );

			$weights_map = array_combine(
				array_map( function ( StationWeight $station_weight ) {
					return $station_weight->from_station_id . '__' . $station_weight->to_station_id;
				}, $station_weights ),
				array_map( function ( StationWeight $station_weight ) {
					return $station_weight->weight;
				}, $station_weights )
			);

			$inputs = join( array_map( function ( Station $to_station ) use ( $station, $weights_map ) {
				if ( $station->id === $to_station->id ) {
					return '<td></td>';
				} else {
					return sprintf( '<td>
                            <input type="number" min="0" value="%s"
                                placeholder="Avstånd"
                                class="tuja-ticket-couponweight" 
                                id="tuja_ticketcouponweight__%d__%d" 
                                name="tuja_ticketcouponweight__%d__%d" 
                                data-twin-field="tuja_ticketcouponweight__%d__%d">
                            </td>',
						$weights_map[ $station->id . '__' . $to_station->id ],
						$station->id, $to_station->id,
						$station->id, $to_station->id,
						$to_station->id, $station->id
					);
				}
			}, $stations ) );

			return sprintf( '
            <tr data-station-id="%d" data-station-key="%s">
                <td>%s</td>
                <td><input type="text" value="%s" name="tuja_ticketdesign__%d__password" placeholder="Lösenord"></td>
                %s
            </tr>',
				$station->id,
				$station->random_id,
				$station->name,
				$design->on_complete_password,
				$station->id,
				$inputs );
		}, $stations ) ); ?>
        </tbody>
    </table>

	<?= $save_button ?>
</form>
