<?php
namespace tuja\admin;

use tuja\data\store\ResponseDao;
use tuja\util\rules\RuleResult;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg() ?>" class="tuja">
    <h3>Grupp <?= htmlspecialchars($group->name) ?> (id: <code><?= htmlspecialchars($group->random_id) ?></code>)</h3>

    <h3>Svar och poäng</h3>
    <p>
        <strong>Totalt <?= $score_result->total_final ?> poäng.</strong>
		<?php
		if ( $score_result->total_without_question_group_max_limits != $score_result->total_final ) {
			printf( '%d poäng har dragits av pga. att maximal poäng uppnåtts på vissa frågegrupper.',
				$score_result->total_without_question_group_max_limits - $score_result->total_final );
		}
		?>
    </p>

	<?php
	$question_filters = [
		ResponseDao::QUESTION_FILTER_ALL                       => 'alla frågor (även obesvarade och okontrollerade)',
		ResponseDao::QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE => 'alla svar där auto-rättningen är osäker',
		ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL            => 'alla svar som inte kontrollerats',
		ResponseDao::QUESTION_FILTER_UNREVIEWED_IMAGES         => 'alla bilder som inte kontrollerats'
	];

	printf( '<p>Filter: %s</p>', join( ', ', array_map( function ( $key, $label ) {
		return ( ( @$_GET[ Group::QUESTION_FILTER_URL_PARAM ] ?: Group::DEFAULT_QUESTION_FILTER ) == $key )
			? sprintf( ' <strong>%s</strong>', $label )
			: sprintf( ' <a href="%s">%s</a>',
				add_query_arg( array(
					Group::QUESTION_FILTER_URL_PARAM => $key,
				) ),
				$label );
	}, array_keys( $question_filters ), array_values( $question_filters ) ) ) );

	$review_component->render(
		$_GET[ Group::QUESTION_FILTER_URL_PARAM ] ?: Group::DEFAULT_QUESTION_FILTER,
		[$group],
		false );
	?>

    <button class="button button-primary" type="submit" name="tuja_points_action" value="save">
        Spara manuella poäng och markera svar som kontrollerade
    </button>

    <h3>Deltagare</h3>
    <table>
        <thead>
        <tr>
            <th></th>
            <th>Namn</th>
            <th>Personnummer</th>
            <th>Ålder</th>
            <th>Medföljare</th>
            <th>Lagledare</th>
            <th>Telefon</th>
            <th>E-post</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td colspan="8">
                Flytta markerade deltagare till detta lag: <br>
                <select name="tuja_group_move_people_to">
                    <option value="0">Välj lag</option>
                    <?= join(array_map(function($g) use ($group) {
	                    return sprintf( '<option value="%s" %s>%s</option>',
                            $g->id,
		                    $group->id == $g->id ? 'disabled="disabled"' : '',
		                    $g->name);
                    }, $groups)) ?>
                </select>
                <button class="button" type="submit" name="tuja_points_action" value="move_people">Flytta</button>
            </td>
        </tr>
        </tfoot>
        <tbody>
		<?php
		print join( '', array_map( function ( $person ) {
			return sprintf( '<tr>' .
			                '<td><input type="checkbox" name="tuja_group_people[]" value="%d" id="tuja_group_people__person_%d"></td>' .
			                '<td><label for="tuja_group_people__person_%d">%s</label></td>' .
			                '<td>%s</td>' .
			                '<td>%.1f</td>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td>%s</td>' .
			                '<td><a href="mailto:%s">%s</a></td>' .
			                '</tr>',
				$person->id,
				$person->id,
				$person->id,
				$person->name,
				$person->pno,
				$person->age,
				! $person->is_competing ? 'Ja' : '' ,
				$person->is_group_contact ? 'Ja' : '',
				$person->phone,
				$person->email,
				$person->email);
		}, $people ) );
		?>
        </tbody>
    </table>

    <p>Status för anmälan:</p>

	<?php
	$css_class_mapping = [
		RuleResult::OK      => 'notice-success',
		RuleResult::WARNING => 'notice-warning',
		RuleResult::BLOCKER => 'notice-error'
	];

	foreach ( $registration_evaluation as $result ) {
		printf( '<div class="notice %s" style="margin-left: 2px"><p><strong>%s: </strong>%s</p></div>',
			$css_class_mapping[ $result->status ],
			$result->rule_name,
			$result->details );
	}
	?>

    <h3>Meddelanden</h3>
    <table>
        <tbody>

        <?php
        $messages = $db_message->get_by_group($group->id);
        print $messages_manager->get_html( $messages )
        ?>
        </tbody>
    </table>
</form>