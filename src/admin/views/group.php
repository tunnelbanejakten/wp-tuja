<?php
namespace tuja\admin;

use tuja\data\model\Person;
use tuja\data\store\ResponseDao;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\util\rules\RuleResult;

AdminUtils::printTopMenu( $competition );
?>

<h3>Grupp <?= htmlspecialchars( $group->name ) ?> (id: <code><?= htmlspecialchars( $group->random_id ) ?></code>)</h3>

<?php printf( '<p><a id="tuja_group_back" href="%s">« Tillbaka till grupplistan</a></p>', $back_url ); ?>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
	<?php
	if ( ! empty( $group->note ) ) {
		printf( '<p>Meddelande från laget: <em>%s</em></p>', $group->note );
	}
	?>
	<?php
	if ( ! empty( $group->city ) ) {
		printf( '<p>Ort: <em>%s</em></p>', $group->city );
	}
	?>

	<h3>Länkar</h3>
	<table class="tuja-table">
		<tbody>
			<tr>
				<td>Länk till lagportal:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $group_home_link, $group_home_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $group_home_link ) ?></td>
			</tr>
			<tr>
				<td>Länk för att ändra lagets namn och tävlingsklass:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $group_editor_link, $group_editor_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $group_editor_link ) ?></td>
			</tr>
			<tr>
				<td>Länk för att ändra deltagare:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $group_people_editor_link, $group_people_editor_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $group_people_editor_link ) ?></td>
			</tr>
			<tr>
				<td>Länk för att checka in:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $group_checkin_link, $group_checkin_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $group_checkin_link ) ?></td>
			</tr>
			<tr>
				<td>Länk för att anmäla nya till laget:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $group_signup_link, $group_signup_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $group_signup_link ) ?></td>
			</tr>
			<?= join( $group_form_links ) ?>
			<tr>
				<td>Länk för att logga in i appen:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $app_link, $app_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $app_link ) ?></td>
			</tr>
		</tbody>
	</table>

	<h3>Redigera grupp</h3>

	<h4>Anmälningsavgift</h4>

	<div id="tuja-group-payment">
		<?= $this->print_fee_configuration_form(); ?>
	</div>

	<?php
	$group_categories_settings_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsGroupCategories'
	) );
	$competition_settings_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettings'
	) );
	printf( '<p><em>Anmälningsavgift kan konfigureras per enskilt lag, per <a href="%s">gruppkategori</a> eller för <a href="%s">tävlingen generellt</a>. Den mest specifika inställningen används.</em></p>', 
		$group_categories_settings_url,
		$competition_settings_url );
	?>

	<button class="button button-primary" type="submit" name="tuja_points_action" value="save_group">
        Spara
    </button>

	<p>
		<?php printf('Faktisk: %s', $group->effective_fee_calculator->description()); ?>
	</p>

    <h3>Tidsbegränsade frågor som visats</h3>

	<?php
	if ( count($view_question_events) > 0 ) {
		foreach($view_question_events as $event) {
			printf(
				'
				<p>
				Visade fråga %s kl. %s
				<button class="button" type="submit" name="tuja_points_action" value="delete_event__%s">%s</button>
				</p>
				', 
				$event->object_id, 
				$event->created_at->format('H:i P'),
				$event->id, 
				'Ta bort'
			);
		}
	} else {
		print('<p>Inga.</p>');
	}
	?>
    <h3>Status</h3>

    <p>
        Aktuell status:
		<?php
		printf( '<td><span class="tuja-admin-groupstatus tuja-admin-groupstatus-%s">%s</span></td>',
			$group->get_status(),
			$group->get_status()
		);
		?>
    </p>
    <div class="tuja-buttons">
        Ändra status:
		<?= join( array_map( function ( $allowed_next_state ) {
			return sprintf(
				'<button class="button" type="submit" name="tuja_points_action" value="transition__%s">%s</button>',
				$allowed_next_state,
				$allowed_next_state );
		}, \tuja\data\model\Group::STATUS_TRANSITIONS[ $group->get_status() ] ) ) ?>
    </div>

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
		ResponseDao::QUESTION_FILTER_UNREVIEWED_IMAGES         => 'alla bilder som inte kontrollerats',
		ResponseDao::QUESTION_FILTER_UNREVIEWED_CHECKPOINT     => 'alla kontroller som inte kontrollerats'
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
		@$_GET[ Group::QUESTION_FILTER_URL_PARAM ] ?: Group::DEFAULT_QUESTION_FILTER,
		[ $group ],
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
            <th>Mat</th>
            <th>Meddelande</th>
            <th>Medföljare</th>
            <th>Lagledare</th>
            <th>Telefon</th>
            <th>E-post</th>
            <th>Länk för att redigera</th>
        </tr>
        </thead>
		<?php if ( ! empty( $people ) ) { ?>
            <tfoot>
            <tr>
                <td colspan="8">
                    Flytta markerade deltagare till detta lag: <br>
                    <select name="tuja_group_move_people_to">
                        <option value="0">Välj lag</option>
						<?= join( array_map( function ( $g ) use ( $group ) {
							return sprintf( '<option value="%s" %s>%s</option>',
								$g->id,
								$group->id == $g->id ? 'disabled="disabled"' : '',
								$g->name );
						}, $groups ) ) ?>
                    </select>
                    <button class="button" type="submit" name="tuja_points_action" value="move_people">Flytta</button>
                </td>
            </tr>
            </tfoot>
		<?php } ?>
        <tbody>
		<?php
		print join( '', array_map( function ( Person $person ) use ( $group ) {
			$person_edit_link = PersonEditorInitiator::link( $group, $person );

			return sprintf( '<tr class="tuja-person-status-%s">' .
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
				$person_edit_link );
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
		$messages = $db_message->get_by_group( $group->id );
		print $messages_manager->get_html( $messages )
		?>
        </tbody>
    </table>
</form>