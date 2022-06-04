<?php
namespace tuja\admin;

use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\SlideshowInitiator;

AdminUtils::printTopMenu( $competition );
$this->print_menu();
?>

<form method="post" action="<?php echo add_query_arg( array() ); ?>" class="tuja">
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
