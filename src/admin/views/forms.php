<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>

<p>Formulär innehåller uppgifter som lagen ska lösa under tävlingens gång.</p>
<p>För att skapa lite struktur så läggs frågor i frågegrupper, som i sin tur läggs i formulär.</p>
<p>På appens Svara-sidan visas alla frågor som inte placerats ut på kartan. På Karta-sidan i appen visas, naturligtvis, de frågor som har placerats ut på kartan.</p>
<p>Klicka på ett av formulären nedan för att lägga till, ta bort eller redigera frågor och frågegrupper.</p>

<h2>Formulär i "<?php echo esc_html($competition->name); ?>"</h2>
<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">
	<table class="tuja-admin-table forms widefat">
		<thead>
			<tr>
				<th scope="col">Nr</th>
				<th scope="col">Namn</th>
				<th scope="col">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $forms as $form ): ?>
				<tr>
					<td><?php echo $form->id; ?></td>
					<td><?php echo $form->name; ?></td>
					<td>
						<?php
							$url = add_query_arg( array(
								'tuja_view' => 'Form',
								'tuja_form' => $form->id
							) );
							printf(
								'<a href="%s" class="button" data-id="%d" data-random-id="%s">Redigera</a>',
								$url,
								$form->id,
								$form->random_id
							);
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<br>
	<h2>Skapa nytt formulär</h2>
	<input type="text" name="tuja_form_name" placeholder="Namn..." id="tuja_form_name"/>
	<button type="submit" class="button" name="tuja_action" value="form_create" id="tuja_form_create_button">Skapa formulär</button>
</form>
