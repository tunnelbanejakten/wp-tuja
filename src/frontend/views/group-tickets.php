<?php

use tuja\data\model\Ticket;

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
                        <div class="tuja-ticket-station">Biljett för kontroll <strong>%s</strong></div>
                        <div class="tuja-ticket-word-description">
                            När ni kommer till kontrollen ska ni visa den här biljetten på 
                            er mobil eller säga detta lösen:
                        </div>
                        <div class="tuja-ticket-word">%s</div>
                    </div>',
					$ticket->colour,
					$ticket->station->name,
					$ticket->word
				);
			}, $tickets ) ) );
		} else {
			printf( '<p>Ni har ännu inte några biljetter.</p>' );
		}
		?>
    </div>

	<?= $form ?>

	<?= $button ?>

    <p>
		<?php printf( '<a href="%s">Tillbaka</a>', $home_link ) ?>
    </p>
</form>