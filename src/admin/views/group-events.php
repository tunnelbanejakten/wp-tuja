<?php
namespace tuja\admin;

AdminUtils::printTopMenu( $competition );

$this->print_menu();
?>

<form method="post" action="<?= add_query_arg( [] ) ?>" class="tuja">

<?php
if ( count($view_question_events) > 0 ) {
	foreach($view_question_events as $event) {
		printf(
			'
			<p>
			Visade fråga %s kl. %s
			<button class="button" type="submit" name="tuja_points_action" value="delete_event__%s">%s</button>
			</p>
			', 
			$event->object_id, // TODO: Also show name of question
			$event->created_at->format('H:i P'),
			$event->id, 
			'Ta bort'
		);
	}
} else {
	print('<p>Inga.</p>');
}
?>

</form>