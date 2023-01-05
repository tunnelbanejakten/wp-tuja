<?php
namespace tuja\admin;

use tuja\util\rules\RuleResult;

$this->print_root_menu();
$this->print_menu();
$this->print_leaves_menu();
?>

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

	<h3>Redigera grupp</h3>

	<table class="tuja-table">
		<tbody>
		<tr>
			<td><label for="">Namn</label></td>
			<td><input type="text" name="tuja_group_name" id="tuja_group_name" value="<?php echo $_POST['tuja_group_name'] ?? $group->name; ?>"></td>
		</tr>
		<tr>
			<td><label for="">Meddelande från laget</label></td>
			<td><input type="text" name="tuja_group_note" id="tuja_group_note" value="<?php echo $_POST['tuja_group_note'] ?? $group->note; ?>"></td>
		</tr>
		<tr>
			<td><label for="">Ort</label></td>
			<td><input type="text" name="tuja_group_city" id="tuja_group_city" value="<?php echo $_POST['tuja_group_city'] ?? $group->city; ?>"></td>
		</tr>
		<tr>
			<td><label for="">Kategori</label></td>
			<td>
				<div id="tuja-group-category">
					<?= $this->print_group_category_selector(); ?>
				</div>
			</td>
		</tr>
		<tr>
			<td><label for="">Anmälningsavgift</label></td>
			<td>
				<div id="tuja-group-payment">
					<?= $this->print_fee_configuration_form(); ?>
				</div>
			</td>
		</tr>
		</tbody>
	</table>

	<?php
	$group_categories_settings_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettingsGroupCategories'
	) );
	$competition_settings_url = add_query_arg( array(
		'tuja_competition' => $competition->id,
		'tuja_view'        => 'CompetitionSettings'
	) );
	?>

	<button class="button button-primary" type="submit" name="tuja_points_action" value="save_group">
        Spara
    </button>

	<p>
		<?php printf('Faktisk: %s', $group->effective_fee_calculator->description()); ?>
	</p>

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