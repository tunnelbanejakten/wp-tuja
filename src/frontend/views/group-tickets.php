<?php

use tuja\data\model\Ticket;
use tuja\util\Strings;

?>
<form method="post">
	<?php echo $error_message; ?>
	<?php echo $success_message; ?>

	<div class="tuja-tickets">
		<?php
		if ( ! empty( $tickets ) ) {
			print( join(
				array_map(
					function ( Ticket $ticket ) {
						$wrapper_class   = $ticket->is_used
							? 'tuja-ticket-wrapper tuja-ticket-used'
							: 'tuja-ticket-wrapper tuja-ticket-unused';
						$is_used_element = $ticket->is_used
							? sprintf(
								'<div class="tuja-used-ticket-wrapper">
									<div class="tuja-used-ticket-explanation">%s</div>
								</div>',
								Strings::get( 'group_tickets.ticket.used_ticket_explanation' )
							)
							: '';
						return sprintf(
							'<div class="%s">
								<div class="tuja-ticket" style="background-color: %s">
									<div class="tuja-ticket-station">%s</div>
									<div class="tuja-ticket-word-description">%s</div>
									<div class="tuja-ticket-word">%s</div>
								</div>
								%s
							</div>',
							$wrapper_class,
							$ticket->colour,
							Strings::get( 'group_tickets.ticket.station', sprintf( '<strong>%s</strong>', $ticket->station->name ) ),
							Strings::get( 'group_tickets.ticket.word_description' ),
							$ticket->word,
							$is_used_element
						);
					},
					$tickets
				)
			) );
		} else {
			printf( '<p>%s</p>', Strings::get( 'group_tickets.ticket.no_tickets_yet' ) );
		}
		?>
	</div>

	<?php echo $form; ?>

	<?php echo $button; ?>

</form>

<p class="tuja-buttons">
	<?php printf( '<a href="%s">Tillbaka</a>', $home_link ); ?>
</p>
