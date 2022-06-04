<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );
$this->print_menu();
?>
<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">

	<?php
	$import_url = add_query_arg( array(
		'tuja_competition' => $this->competition->id,
		'tuja_view'        => 'MessagesImport'
	) );
	printf( '<h3>Importera</h3>' );
	printf( '<p><a href="%s">Importera meddelanden</a></p>', $import_url );
	?>

    <h3>Skicka</h3>
	<?php
	$import_url = add_query_arg( array(
		'tuja_competition' => $this->competition->id,
		'tuja_view'        => 'MessagesSend'
	) );
	printf( '<p><a href="%s">Skicka meddelanden</a></p>', $import_url );
	?>

    <h3>Meddelanden utan tydlig avsändare</h3>
    <p>De här meddelandena har inte kunnat kopplas till någon av de tävlande lagen:</p>
	<?= $messages_manager->get_html( $messages ) ?>

</form>