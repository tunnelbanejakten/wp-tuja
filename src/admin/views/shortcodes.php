<?php
namespace tuja\admin;
AdminUtils::printTopMenu( $competition );

use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\SlideshowInitiator;

?>

<style>

.grid {
	display: grid;
	align-items: center;
	/* height: 100vh; */
}

.small {
	font-size: 10px;
}

.drop-container:hover .drop {
	display: block;
}
.drop-container .drop {
	display: none;
	position: absolute;
	/* top: 20px; */
	/* left: 8px; */
	background-color: white;
}
.drop-container .drop a {
	display: block;
	padding: 0px 0px;
	white-space: nowrap;
}

.breadcrumb {
	padding: 8px 15px;
	margin-bottom: 20px;
	list-style: none;
	border-radius: 4px;
}

ol, ul {
	margin-top: 0;
	margin-bottom: 10px;
}
.breadcrumb>li {
	display: inline-block;
}

.pl0 {
	padding-left: 0;
}
.list {
	list-style-type: none;
}
.relative {
	position: relative;
}
ol.breadcrumb {
	padding: 0;
	margin: 0;
}
ol.breadcrumb > li::after {
	content: " > ";
}
ol.breadcrumb > li:last-child:after {
	content: ":";
}
</style>

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
