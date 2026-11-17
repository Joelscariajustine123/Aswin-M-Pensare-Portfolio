<?php
/**
 * All Brands page (/brands/).
 */

defined( 'ABSPATH' ) || exit;
get_header();

$posts = $GLOBALS['wp_query']->posts;
?>
<section class="wrap single-hero archive-hero">
	<nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></nav>
	<div class="chapter reveal"><b>03</b> Trusted by</div>
	<h1 class="section-title reveal">Brands</h1>
	<p class="lede reveal">Names I’ve had the pleasure of putting words to.</p>
</section>
<section class="wrap archive-body">
	<?php if ( $posts ) : ?>
		<?php echo Aswin_Render::i()->brands_wall( $posts, array( 'columns' => 4, 'names' => true ) ); // phpcs:ignore ?>
	<?php else : ?>
		<div class="work-empty"><strong>Brands coming soon…</strong></div>
	<?php endif; ?>
</section>
<?php
get_footer();
