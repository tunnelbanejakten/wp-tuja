<?php

namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
?>

<h3>Importera SMS och MMS</h3>

<form method="post" action="<?= add_query_arg() ?>" enctype="multipart/form-data">

	<h3>Krav för att kunna importera SMS och MMS:</h3>
	<ul>
		<li>Du har appen
			<a href="https://play.google.com/store/apps/details?id=com.riteshsahu.SMSBackupRestore&hl=en">
				SMS Backup &amp; Restore</a>
			på din telefon. Denna app finns enbart för Android.
		</li>
		<li>Inställningar i appen:
			<ul>
				<li>Meddelanden: Ja</li>
				<li>MMS: Ja</li>
				<li>Emojis och specialtecken: Nej</li>
			</ul>
		</li>
	</ul>

	<h3>Varje gång du vill importera SMS och MMS gör du så här:</h3>

	<p><strong>Skapa fil</strong></p>
	<ol>
		<li>Starta appen.</li>
		<li>Tryck på <em>Säkerhetskopiera nu</em> så att säkerhetkopian antingen sparas som en fil i din
			mobiltelefon eller sparas som en publik fil på webben (dvs. en fil som går att ladda ner utan
			att behöva logga in någonstans).
		</li>
	</ol>

	<p><strong>Importera fil</strong></p>

	<p>Alternativ 1: Fil som du sparat på din dator eller mobil</p>
	<div>
		<input type="hidden" name="MAX_FILE_SIZE" value="100000000"/>
		<input type="file" name="tuja_import_file" class="file">
	</div>

	<p>Alternativ 2: Fil som finns på nätet</p>
	<div>
		<input type="text"
				name="tuja_import_url"
				class="text"
				placeholder="http://"
				size="100"
				value="<?= get_option('tuja_message_import_url', '') ?>">
	</div>

	<p><strong>Inställningar</strong></p>

	<div>
		<input type="checkbox"
				value="yes"
				name="tuja_import_onlyrecent"
			<?= get_option('tuja_message_import_only_recent', '') == 'yes' ? 'checked="checked"' : '' ?>
				id="tuja-import-onlyrecent">
		<label for="tuja-import-onlyrecent">Importera bara meddelanden från idag och igår</label>
	</div>

	<br>

	<div>
		<button class="button button-primary" type="submit" name="tuja_points_action" value="import">
			Importera
		</button>
	</div>
</form>