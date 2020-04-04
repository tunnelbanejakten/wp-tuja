<?php
namespace tuja\admin;
AdminUtils::printTopMenu( $competition );

use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\SlideshowInitiator;

?>

<form method="post" action="<?= add_query_arg() ?>" class="tuja">
    <p>
        Anmäl lag:
		<?php
		$link = CompetitionSignupInitiator::link( $competition );
		printf( '<a href="%s" target="_blank" id="tuja_shortcodes_competitionsignup_link">%s</a>', $link, $link )
		?>
    </p>

    <p>
        Bildspel:
		<?php
		$link = SlideshowInitiator::link( $competition );
		printf( '<a href="%s" target="_blank" id="tuja_shortcodes_slideshow_link">%s</a>', $link, $link )
		?>
    </p>
</form>
