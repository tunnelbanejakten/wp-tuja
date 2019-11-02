<?php get_header(); ?>
	<main id="content" class="content">

		<?php do_action( 'basic_before_page_article' ); ?>
		<article class="post page" id="pageid-<?php the_ID(); ?>">

			<?php do_action( 'basic_before_page_content_box' );  ?>
			<div class="entry-box clearfix">
				<?php do_action( 'basic_before_page_content' );  ?>
				<?php echo apply_filters('the_content', $content) ?>
				<?php do_action( 'basic_after_page_content' );  ?>
			</div>
			<?php do_action( 'basic_after_page_content_box' );  ?>

		</article>
		<?php do_action( 'basic_after_page_article' ); ?>
		
	</main> <!-- #content -->
	<?php get_sidebar(); ?>
<?php get_footer(); ?>