<?php
/**
 * Category page — all works in one category (the "View more" destination).
 */

defined( 'ABSPATH' ) || exit;
get_header();

$term  = get_queried_object();
$posts = $GLOBALS['wp_query']->posts;
$all   = aswin_work_cats();
$num   = str_pad( (string) ( 3 + max( 1, (int) get_term_meta( $term->term_id, 'aswin_order', true ) ) ), 2, '0', STR_PAD_LEFT );
?>
<section class="wrap single-hero archive-hero">
	<nav class="crumbs" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span>/</span>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'aswin_work' ) ); ?>">Work</a>
	</nav>
	<div class="chapter reveal"><b><?php echo esc_html( $num ); ?></b> <?php echo esc_html( count( $posts ) . ( 1 === count( $posts ) ? ' piece' : ' pieces' ) ); ?></div>
	<h1 class="section-title reveal"><?php echo esc_html( $term->name ); ?></h1>
	<?php if ( $term->description ) : ?><p class="lede reveal"><?php echo esc_html( $term->description ); ?></p><?php endif; ?>
	<nav class="cat-pills reveal" aria-label="Other categories">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'aswin_work' ) ); ?>">All work</a>
		<?php foreach ( $all as $t ) : ?>
			<a href="<?php echo esc_url( aswin_section_url( $t ) ); ?>"<?php echo $t->term_id === $term->term_id ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo esc_html( $t->name ); ?></a>
		<?php endforeach; ?>
	</nav>
</section>

<section class="wrap archive-body">
	<?php if ( $posts ) : ?>
		<?php $design = aswin_design_for( array( $term ) ); ?>
		<div class="design-<?php echo esc_attr( $design ); ?>"><?php echo Aswin_Render::i()->works_grid( $posts, array( 'columns' => 3, 'terms' => array( $term ), 'design' => $design ) ); // phpcs:ignore ?></div>
	<?php else : ?>
		<div class="work-empty"><strong>Fresh work loading…</strong>Samples available on request — just <a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">say hello</a>.</div>
	<?php endif; ?>
</section>
<?php
get_footer();
