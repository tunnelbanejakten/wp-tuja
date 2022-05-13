<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<h3>Avgifter</h3>

<?php printf( '<p><a id="tuja_competition_settings_fees_back" href="%s">« Tillbaka till övriga inställningar</a></p>', $back_url ); ?>

<form method="post" class="tuja" id="tuja-tab-payment">

	<h3>Anmälningsavgift</h3>

	<?php echo $this->print_group_fee_configuration_form( $competition ); ?>

	<?php
	$group_categories_settings_url = add_query_arg(
		array(
			'tuja_competition' => $competition->id,
			'tuja_view'        => 'CompetitionSettingsGroupCategories',
		)
	);
	printf(
		'<p><em>Anmälningsavgift kan konfigureras per enskilt lag, per <a href="%s">gruppkategori</a> eller för tävlingen generellt. Den mest specifika inställningen används.</em></p>',
		$group_categories_settings_url
	);
	?>

	<h3>Betalningsmetoder</h3>

	<?php echo $this->print_payment_options_configuration_form( $competition ); ?>
	<input type="hidden" name="tuja_competition_settings_payment_options" id="tuja_competition_settings_payment_options"/>

	<button class="button button-primary"
			type="submit"
			name="tuja_competition_settings_action"
			id="tuja_save_competition_settings_button"
			value="save">
		Spara
	</button>
</form>
