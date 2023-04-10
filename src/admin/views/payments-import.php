<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>
<h2>Importera transaktioner</h2>
<p>Importera betalningsinformation/transaktioner via Swish-rapport i CSV-format från Swedbank.</p>
<h3>Skapa rapporten</h3>
<ol>
	<li>Logga in på Swedbanks internetbank</li>
	<li>Gå till <em>Betala och överföra</em> och <em>Swish</em>.</li>
	<li>Gå till <em>Swish-rapport</em>.</li>
	<li>Klicka på <em>Skapa ny rapport</em> och skapa rapporten.</li>
	<li>Vänta på att rapporten blir klar och klicka sedan på rapporten.</li>
	<li>Ladda ner som CSV-fil.</li>
	<li>Ladda upp filen på den här sidan.</li>
</ol>
<form method="post" action="<?php echo add_query_arg( array() ); ?>" enctype="multipart/form-data" class="tuja">
	<h3>Importera rapporten</h3>
	<p>Alternativ 1: Ladda upp filen</p>
	<div class="tuja-import-indent">
		<input type="file" name="tuja_import_file" id="tuja_import_file" accept=".csv">
	</div>
	<p>Alternativ 2: Klistra in innehållet i filen</p>
	<div class="tuja-import-indent">
		<textarea name="tuja_import_raw" id="tuja_import_raw" style="width: 100%; height: 20em;" placeholder="Klistra in innehållet i CSV-filen här..."></textarea>
	</div>
	<button type="submit" class="button button-primary" name="tuja_action" value="<?php echo self::ACTION_NAME_START; ?>" id="tuja_save_button">
		Importera
	</button>
</form>
