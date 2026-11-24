<?php
/**
 * Campaign showcase page (single-aswin_work.php, when a Campaign line is set).
 * Expects: $id, $cat, $back, $brand, $year, $role, $images, $link, $pdf_url, $r.
 */
defined( 'ABSPATH' ) || exit;

$cover  = (int) get_post_thumbnail_id( $id );
$series = array_values( array_diff( $images, array( $cover ) ) );
$client = $brand && get_post_status( $brand ) ? get_the_title( $brand ) : '';
?>
<section class="cmp-showcase">
	<div class="wrap">
		<nav class="crumbs" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span>/</span>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'aswin_work' ) ); ?>">Work</a>
			<?php if ( $cat ) : ?><span>/</span><a href="<?php echo esc_url( $back ); ?>"><?php echo esc_html( $cat->name ); ?></a><?php endif; ?>
		</nav>
		<div class="reveal"><?php echo $r->campaign_board( get_post() ); // phpcs:ignore ?></div>

		<dl class="work-facts cmp-facts reveal">
			<?php if ( $client ) : ?><div><dt>Client</dt><dd><a href="<?php echo esc_url( get_permalink( $brand ) ); ?>"><?php echo esc_html( $client ); ?></a></dd></div><?php endif; ?>
			<div><dt>Campaign</dt><dd><?php echo esc_html( get_post_meta( $id, '_aswin_tagline', true ) ); ?></dd></div>
			<?php if ( $role ) : ?><div><dt>Role</dt><dd><?php echo esc_html( $role ); ?></dd></div><?php endif; ?>
			<?php if ( $year ) : ?><div><dt>Year</dt><dd><?php echo esc_html( $year ); ?></dd></div><?php endif; ?>
		</dl>
	</div>
</section>

<?php if ( trim( get_the_content() ) ) : ?>
	<section class="wrap cmp-story">
		<div class="chapter reveal"><b>01</b> The story</div>
		<div class="entry reveal"><?php the_content(); ?></div>
	</section>
<?php endif; ?>

<?php if ( count( $series ) > 3 ) : // board shows 3; the rest live here ?>
	<section class="wrap work-gallery n-<?php echo (int) min( count( $series ), 4 ); ?>" aria-label="All creatives">
		<?php foreach ( $series as $img ) : ?>
			<a class="reveal" href="<?php echo esc_url( wp_get_attachment_image_url( $img, 'full' ) ); ?>" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="all-<?php echo (int) $id; ?>"><?php echo wp_get_attachment_image( $img, 'large', false, array( 'loading' => 'lazy' ) ); ?></a>
		<?php endforeach; ?>
	</section>
<?php endif; ?>

<?php if ( $pdf_url ) : ?>
	<section class="wrap work-pdf reveal" aria-label="PDF">
		<object data="<?php echo esc_url( $pdf_url ); ?>#view=FitH" type="application/pdf"><p><a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener">Open the PDF ↗</a></p></object>
	</section>
<?php endif; ?>
