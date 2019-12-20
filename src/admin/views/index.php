<?php namespace tuja\admin; ?>

<div class="tuja tuja-view-index">
	<form method="post" action="<?= add_query_arg() ?>" class="tuja">

		<h1>Tävlingar</h1>
		<?php
		$competitions = $db_competition->get_all();
		foreach ($competitions as $competition) {
			$url = add_query_arg(array(
				'tuja_view' => 'Competition',
				'tuja_competition' => $competition->id
			));
			printf('<p><a href="%s">%s</a></p>', $url, $competition->name);
		}
		?>

		<input type="text" name="tuja_competition_name" id="tuja_competition_name"/>
		<button type="submit" name="tuja_action" value="competition_create" class="button" id="tuja_create_competition_button">Skapa</button>
	</form>
	<?php
	$url = add_query_arg(array(
		'tuja_view' => 'Settings'
	));
	printf('<p><a href="%s">Inställningar</a></p>', $url);
	?>
</div>