<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<p>Formulär innehåller uppgifter som lagen ska lösa under tävlingens gång.</p>
<p>För att skapa lite struktur så läggs frågor i frågegrupper, som i sin tur läggs i formulär.</p>
<p>På appens Svara-sidan visas alla frågor som inte placerats ut på kartan. På Karta-sidan i appen visas, naturligtvis, de frågor som har placerats ut på kartan.</p>
<p>De här formulären finns i denna tävling:</p>
<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
	<?php
	foreach ( $forms as $form ) {
		$url = add_query_arg( array(
			'tuja_view' => 'Form',
			'tuja_form' => $form->id
		) );
		printf(
			'<div><a href="%s" data-id="%d" data-random-id="%s">%s</a></div>',
			$url,
			$form->id,
			$form->random_id,
			$form->name );
	}
	?>
	<input type="text" name="tuja_form_name" id="tuja_form_name"/>
	<button type="submit" class="button" name="tuja_action" value="form_create" id="tuja_form_create_button">Skapa</button>
</form>
