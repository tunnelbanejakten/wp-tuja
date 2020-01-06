<?php

use tuja\data\model\Ticket;
use tuja\util\Strings;

?>
<form method="post">
	<?= $error_message ?>
	<?= $success_message ?>

    <div class="tuja-tickets">
		<?php
		if ( ! empty( $tickets ) ) {
			print( join( array_map( function ( Ticket $ticket ) {
				return sprintf( '
                    <div class="tuja-ticket" style="background-color: %s">
                        <div class="tuja-ticket-station">%s</div>
                        <div class="tuja-ticket-word-description">%s</div>
                        <div class="tuja-ticket-word">%s</div>
                    </div>',
					$ticket->colour,
					Strings::get( 'group_tickets.ticket.station', sprintf( '<strong>%s</strong>', $ticket->station->name ) ),
					Strings::get( 'group_tickets.ticket.word_description' ),
					$ticket->word
				);
			}, $tickets ) ) );
		} else {
			printf( '<p>%s</p>', Strings::get( 'group_tickets.ticket.no_tickets_yet' ) );
		}
		?>
    </div>

	<?= $form ?>

	<?= $button ?>

    <p>
		<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
    </p>
</form>