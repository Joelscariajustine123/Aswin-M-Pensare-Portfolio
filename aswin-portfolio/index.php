<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="wrap">
	<header class="single-hero">
		<h1><?php echo is_404() ? 'Lost for words.' : esc_html( wp_strip_all_tags( get_the_archive_title() ?: get_the_title() ) ); ?></h1>
		<?php if ( is_404() ) : ?><p class="lede">Even copywriters get those days. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Head back home →</a></p><?php endif; ?>
	</header>
	<?php if ( ! is_404() ) : ?>
		<div class="entry">
			<?php
			while ( have_posts() ) :
				the_post();
				if ( is_singular() ) {
					the_content();
				} else {
					printf( '<h2><a href="%s">%s</a></h2>', esc_url( get_permalink() ), esc_html( get_the_title() ) );
					the_excerpt();
				}
			endwhile;
			?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
