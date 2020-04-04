<form method="get">
	<?= $option_question_filter ?>
	<?= $option_duration ?>
	<?= $option_shuffle ?>

    <button type="submit" id="tuja-slideshow-start">Visa</button>
</form>
<div id="tuja-slideshow">
    <div id="tuja-slideshow-close">Stäng</div>
	<?= join( array_map( function ( $url ) {
		return sprintf( '
            <figure>
                <img src="%s">
                <figcaption>%s</figcaption>
            </figure>
            ', $url['url'], $url['caption'] );
	}, $image_urls ) )
	?>
</div>
