<?php
/**
 * Individual brand page — logo, description, and works for this brand.
 */

defined( 'ABSPATH' ) || exit;
get_header();

while ( have_posts() ) :
	the_post();
	$id    = get_the_ID();
	$site  = get_post_meta( $id, '_aswin_link', true );
	$works = get_posts( array(
		'post_type'      => 'aswin_work',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'meta_key'       => '_aswin_brand', // phpcs:ignore
		'meta_value'     => $id, // phpcs:ignore
	) );
	?>
	<article class="brand-single">
		<header class="wrap single-hero brand-hero">
			<nav class="crumbs" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span>/</span>
				<a href="<?php echo esc_url( aswin_brands_url() ); ?>">Brands</a>
			</nav>
			<div class="brand-head">
				<div class="brand-logo reveal">
					<?php echo has_post_thumbnail() ? get_the_post_thumbnail( $id, 'large' ) : '<span>' . esc_html( get_the_title() ) . '</span>'; ?>
				</div>
				<div>
					<h1 class="reveal"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?><p class="lede reveal"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
					<?php if ( $site ) : ?><p class="reveal"><a class="btn ghost" href="<?php echo esc_url( $site ); ?>" target="_blank" rel="noopener">Visit website <span aria-hidden="true">↗</span></a></p><?php endif; ?>
				</div>
			</div>
		</header>

		<?php if ( trim( get_the_content() ) ) : ?>
			<div class="wrap"><div class="entry reveal"><?php the_content(); ?></div></div>
		<?php endif; ?>

		<?php if ( $works ) : ?>
			<section class="wrap related">
				<div class="section-head">
					<div class="chapter reveal"><b><?php echo count( $works ); ?></b> <?php echo 1 === count( $works ) ? 'Piece' : 'Pieces'; ?></div>
					<h2 class="section-title reveal">Work for <em><?php the_title(); ?></em></h2>
				</div>
				<?php echo Aswin_Render::i()->works_grid( $works, array( 'columns' => 3, 'chips' => true, 'filter' => count( $works ) > 3 ) ); // phpcs:ignore ?>
			</section>
		<?php endif; ?>

		<div class="wrap related"><?php echo Aswin_Render::i()->view_more( aswin_brands_url(), 'All brands' ); // phpcs:ignore ?></div>
	</article>
	<?php
endwhile;

get_footer();
