<?php namespace tuja\admin;

use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\SlideshowInitiator;

$this->print_root_menu();
// $this->print_menu();
// $this->print_leaves_menu();
?>

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
