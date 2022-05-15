<?php
namespace tuja\admin;

use tuja\data\model\GroupCategory;
use tuja\data\model\Group;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<form method="post" class="tuja" id="tuja-tab-payment">

	<h3>Anmälningsavgift</h3>

	<p>Anmälningsavgift kan konfigureras per enskilt lag, per gruppkategori eller för tävlingen generellt. Den mest specifika inställningen används.</p>

	<table class="tuja-table">
		<tbody>
			<tr>
				<th colspan="2">Tävlingen</th>
			</tr>
			<tr>
				<td>Standardavgift</td>
				<td>
					<?php echo $this->print_group_fee_configuration_form( $competition ); ?>
				</td>
			</tr>
			<tr>
				<th colspan="2">Gruppkategorier</th>
			</tr>

			<?php
				print join(
					array_map(
						function ( GroupCategory $category ) {
							return sprintf(
								'<tr><td data-category-name="%s" data-category-id="%d">%s</td><td>%s</td></tr>',
								$category->name,
								$category->id,
								$category->name,
								$this->print_group_category_fee_override_configuration_form( $category )
							);
						},
						$category_dao->get_all_in_competition( $competition->id )
					)
				);
				?>

			<tr>
				<th colspan="2">Grupper</th>
			</tr>

			<?php
				print join(
					array_map(
						function ( Group $group ) {
							return sprintf( '<tr><td>%s</td><td>%s</td></tr>', $group->name, $this->print_group_fee_override_configuration_form( $group ) );
						},
						$group_dao->get_all_in_competition( $competition->id )
					)
				);
				?>

		</tbody>
	</table>

	

	<?php
	$group_categories_settings_url = add_query_arg(
		array(
			'tuja_competition' => $competition->id,
			'tuja_view'        => 'CompetitionSettingsGroupCategories',
		)
	);
	?>

	<h3>Betalningsmetoder</h3>

	<?php echo $this->print_payment_options_configuration_form( $competition ); ?>
	<input type="hidden" name="tuja_payment_options" id="tuja_payment_options"/>

	<button class="button button-primary"
			type="submit"
			name="tuja_competition_settings_action"
			id="tuja_save_competition_settings_button"
			value="save">
		Spara
	</button>
</form>
