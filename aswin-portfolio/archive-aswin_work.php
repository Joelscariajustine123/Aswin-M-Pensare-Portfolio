<?php
/**
 * All Work page (/work/) — every work, with category filter tabs.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$posts = $GLOBALS['wp_query']->posts;
?>
<section class="wrap single-hero archive-hero">
	<nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></nav>
	<div class="chapter reveal"><b>✦</b> <?php echo esc_html( count( $posts ) . ( 1 === count( $posts ) ? ' piece' : ' pieces' ) ); ?></div>
	<h1 class="section-title reveal">All <em>work</em></h1>
	<p class="lede reveal">Copy, campaigns, captions and everything in between. Pick a shelf or browse the lot.</p>
</section>
<section class="wrap archive-body">
	<?php if ( $posts ) : ?>
		<?php echo Aswin_Render::i()->works_grid( $posts, array( 'columns' => 3, 'filter' => true, 'chips' => true ) ); // phpcs:ignore ?>
	<?php else : ?>
		<div class="work-empty"><strong>Fresh work loading…</strong>Samples available on request.</div>
	<?php endif; ?>
</section>
<?php
get_footer();
