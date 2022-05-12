<?php namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
	<?php
	foreach ( $forms as $form ) {
		$url = add_query_arg( array(
			'tuja_view' => 'Form',
			'tuja_form' => $form->id
		) );
		printf(
			'<p><a href="%s" data-id="%d" data-random-id="%s">%s</a></p>',
			$url,
			$form->id,
			$form->random_id,
			$form->name );
	}
	?>
	<input type="text" name="tuja_form_name" id="tuja_form_name"/>
	<button type="submit" class="button" name="tuja_action" value="form_create" id="tuja_form_create_button">Skapa</button>
</form>
