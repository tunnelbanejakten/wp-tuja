<?php
namespace tuja\admin;

$this->print_root_menu();
$this->print_leaves_menu( true );
?>
<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
	<h3>Meddelanden utan tydlig avsändare</h3>
	<p>De här meddelandena har inte kunnat kopplas till någon av de tävlande lagen:</p>
	<?php echo $messages_manager->get_html( $messages ); ?>
</form>
