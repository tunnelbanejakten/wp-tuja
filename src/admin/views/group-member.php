<?php
namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
<table class="tuja-table">
	<tbody>
	<tr>
		<td><label for="">Namn</label></td>
		<td><input type="text" name="tuja_person_property__name" id="tuja_person_property__name" value="<?php echo $_POST['tuja_person_property__name'] ?? $person->name; ?>"></td>
	</tr>
	<tr>
		<td><label for="">Roll</label></td>
		<td>
			<?php echo $person_type_dropdown; ?>
		</td>
	</tr>
	<tr>
		<td><label for="">Telefonnummer</label></td>
		<td><input type="text" name="tuja_person_property__phone" id="tuja_person_property__phone" value="<?php echo $_POST['tuja_person_property__phone'] ?? $person->phone; ?>"></td>
	</tr>
	<tr>
		<td><label for="">Epostadress</label></td>
		<td><input type="text" name="tuja_person_property__email" id="tuja_person_property__email" value="<?php echo $_POST['tuja_person_property__email'] ?? $person->email; ?>"></td>
	</tr>
	<tr>
		<td><label for="">Matpreferenser</label></td>
		<td><input type="text" name="tuja_person_property__food" id="tuja_person_property__food" value="<?php echo $_POST['tuja_person_property__food'] ?? $person->food; ?>"></td>
	</tr>
	<tr>
		<td><label for="">Personnummer</label></td>
		<td><input type="text" name="tuja_person_property__pno" id="tuja_person_property__pno" value="<?php echo $_POST['tuja_person_property__pno'] ?? $person->pno; ?>"></td>
	</tr>
	<tr>
		<td><label for="">Meddelande till tävlingsledningen</label></td>
		<td><input type="text" name="tuja_person_property__note" id="tuja_person_property__note" value="<?php echo $_POST['tuja_person_property__note'] ?? $person->note; ?>"></td>
	</tr>
	</tbody>
</table>

<button type="submit" class="button" name="tuja_action" value="save" id="tuja_group_member_save_button">Spara</button>

<?php if ( ! $is_create_mode ) { ?>
	<h3>Status</h3>

	<p>
		Aktuell status:
		<?php
		printf(
			'<td><span class="tuja-admin-groupstatus tuja-admin-groupstatus-%s">%s</span></td>',
			$person->get_status(),
			$person->get_status()
		);
		?>
	</p>
	<div class="tuja-buttons">
		Ändra status:
		<?php
		echo join(
			array_map(
				function ( $allowed_next_state ) {
					return sprintf(
						'<button class="button" type="submit" name="tuja_action" value="transition__%s">%s</button>',
						$allowed_next_state,
						$allowed_next_state
					);
				},
				\tuja\data\model\Person::STATUS_TRANSITIONS[ $person->get_status() ]
			)
		)
		?>
	</div>
<?php } ?>

</form>

<?php if ( ! empty( $links ) ) { ?>
	<h3>Länkar</h3>
	
	<?php
	foreach ( $links as $label => $url ) {
		printf( '<p><a href="%s">%s</a></p>', $url, $label );
	}
	?>
<?php } ?>
