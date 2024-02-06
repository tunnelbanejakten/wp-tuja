<?php namespace tuja\admin;

use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\SlideshowInitiator;
use tuja\frontend\router\ExpenseReportInitiator;
use tuja\controller\ExpenseReportController;

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

<p>
	Nytt utlägg:
	<?php
	$link = ExpenseReportInitiator::link( $competition, ExpenseReportController::get_new_id() );
	printf( '<a href="%s" target="_blank" id="tuja_shortcodes_expense_link">%s</a>', $link, $link )
	?>
</p>
