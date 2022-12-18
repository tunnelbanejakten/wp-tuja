<?php
namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
$this->print_leaves_menu();
?>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
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
			<?= join( $crew_signup_links ) ?>
			<tr>
				<td>Länk för att logga in i appen:</td>
				<td><?= sprintf( '<a href="%s">%s</a>', $app_link, $app_link ) ?></td>
				<td><?= AdminUtils::qr_code_button( $app_link ) ?></td>
			</tr>
		</tbody>
	</table>
</form>