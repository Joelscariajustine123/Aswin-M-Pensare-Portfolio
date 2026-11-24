<?php
/**
 * Individual work page.
 */

defined( 'ABSPATH' ) || exit;
get_header();

$r = Aswin_Render::i();

while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$cat     = aswin_work_primary_cat( $id );
	$terms   = wp_get_post_terms( $id, 'aswin_work_cat' );
	$style   = aswin_work_style( $id );
	$brand   = (int) get_post_meta( $id, '_aswin_brand', true );
	$year    = get_post_meta( $id, '_aswin_year', true );
	$role    = get_post_meta( $id, '_aswin_role', true );
	$link    = get_post_meta( $id, '_aswin_link', true );
	$pdf     = (int) get_post_meta( $id, '_aswin_pdf', true );
	$pdf_url = $pdf ? wp_get_attachment_url( $pdf ) : '';
	$images  = aswin_work_images( $id );
	$back    = $cat ? aswin_section_url( $cat ) : get_post_type_archive_link( 'aswin_work' );
	$has_vid = (bool) get_post_meta( $id, '_aswin_video', true );
	?>
	<article class="work-single style-<?php echo esc_attr( $style ); ?>">
		<?php if ( get_post_meta( $id, '_aswin_tagline', true ) ) : ?>
			<?php include __DIR__ . '/parts/campaign.php'; ?>
		<?php else : ?>
		<header class="wrap single-hero">
			<nav class="crumbs" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span>/</span>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'aswin_work' ) ); ?>">Work</a>
				<?php if ( $cat ) : ?><span>/</span><a href="<?php echo esc_url( $back ); ?>"><?php echo esc_html( $cat->name ); ?></a><?php endif; ?>
			</nav>
			<h1 class="reveal"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p class="lede reveal"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>

			<dl class="work-facts reveal">
				<?php if ( $brand && get_post_status( $brand ) ) : ?>
					<div><dt>Client</dt><dd><a href="<?php echo esc_url( get_permalink( $brand ) ); ?>"><?php echo esc_html( get_the_title( $brand ) ); ?></a></dd></div>
				<?php endif; ?>
				<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
					<div><dt>Category</dt><dd><?php echo implode( ', ', array_map( function ( $t ) { return '<a href="' . esc_url( aswin_section_url( $t ) ) . '">' . esc_html( $t->name ) . '</a>'; }, $terms ) ); // phpcs:ignore ?></dd></div>
				<?php endif; ?>
				<?php if ( $role ) : ?><div><dt>Role</dt><dd><?php echo esc_html( $role ); ?></dd></div><?php endif; ?>
				<?php if ( $year ) : ?><div><dt>Year</dt><dd><?php echo esc_html( $year ); ?></dd></div><?php endif; ?>
			</dl>

			<?php if ( $link || $pdf_url ) : ?>
				<div class="work-actions reveal">
					<?php if ( $link ) : ?><a class="btn" href="<?php echo esc_url( $link ); ?>"<?php echo get_post_meta( $id, '_aswin_link_new', true ) ? ' target="_blank" rel="noopener"' : ''; ?>>View live <span aria-hidden="true">↗</span></a><?php endif; ?>
					<?php if ( $pdf_url ) : ?><a class="btn ghost" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener" download>Download PDF <span aria-hidden="true">↓</span></a><?php endif; ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ( 'laptop' === $style ) : ?>
			<div class="wrap work-scene reveal"><?php echo $r->laptop_html( get_post(), true ); // phpcs:ignore ?></div>
			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="wrap"><div class="entry reveal"><?php the_content(); ?></div></div>
			<?php endif; ?>
		<?php elseif ( 'scene' === $style ) : ?>
			<div class="wrap work-scene reveal"><?php echo $r->scene_html( get_post(), true ); // phpcs:ignore ?></div>
			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="wrap"><div class="entry reveal"><?php the_content(); ?></div></div>
			<?php endif; ?>
		<?php elseif ( 'phone' === $style ) : ?>
			<div class="wrap work-body has-phone">
				<div class="work-phone reveal"><?php echo $r->phone_html( get_post(), true, 'brandname', '', false ); // phpcs:ignore ?></div>
				<div class="entry reveal"><?php the_content(); ?></div>
			</div>
		<?php else : ?>
			<?php if ( has_post_thumbnail() || $has_vid ) : ?>
				<div class="wrap"><div class="single-cover reveal"><?php echo $r->work_media( $id, 'full' ); // phpcs:ignore ?></div></div>
			<?php endif; ?>
			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="wrap"><div class="entry reveal"><?php the_content(); ?></div></div>
			<?php endif; ?>
		<?php endif; ?>

		<?php
		// Gallery: for phone works show every image; otherwise skip the cover already shown above.
		$gallery = in_array( $style, array( 'phone', 'scene', 'laptop' ), true ) ? $images : array_slice( $images, ( has_post_thumbnail() && ! $has_vid ) ? 1 : 0 );
		if ( $gallery ) :
			?>
			<section class="wrap work-gallery n-<?php echo (int) min( count( $gallery ), 4 ); ?>" aria-label="Images">
				<?php foreach ( $gallery as $img ) : ?>
					<a class="reveal" href="<?php echo esc_url( wp_get_attachment_image_url( $img, 'full' ) ); ?>" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="work-<?php echo (int) $id; ?>">
						<?php echo wp_get_attachment_image( $img, 'large', false, array( 'loading' => 'lazy' ) ); ?>
					</a>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php
		// Campaign videos ("The films").
		$films = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_aswin_videos', true ) ) ) );
		if ( $films ) :
			?>
			<section class="wrap work-films" aria-label="Videos">
				<div class="section-head">
					<div class="chapter reveal"><b><?php echo count( $films ); ?></b> <?php echo 1 === count( $films ) ? 'Film' : 'Films'; ?></div>
					<h2 class="section-title reveal">The <em>films</em></h2>
				</div>
				<div class="films-grid n-<?php echo (int) min( count( $films ), 3 ); ?>">
					<?php
					foreach ( $films as $v ) :
						$src    = wp_get_attachment_url( $v );
						$poster = (int) get_post_meta( $v, '_aswin_poster', true );
						if ( ! $src ) {
							continue;
						}
						?>
						<figure class="film reveal">
							<video src="<?php echo esc_url( $src ); ?>"<?php echo $poster ? ' poster="' . esc_url( wp_get_attachment_image_url( $poster, 'large' ) ) . '"' : ''; ?> controls playsinline preload="none"></video>
							<?php if ( $t = get_the_title( $v ) ) : ?><figcaption><?php echo esc_html( $t ); ?></figcaption><?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $pdf_url ) : ?>
			<section class="wrap work-pdf reveal" aria-label="PDF">
				<object data="<?php echo esc_url( $pdf_url ); ?>#view=FitH" type="application/pdf">
					<p>Your browser can’t show the PDF here. <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener">Open it in a new tab ↗</a></p>
				</object>
			</section>
		<?php endif; ?>
		<?php endif; // campaign layout ?>

		<?php
		// Prev / next within the primary category.
		if ( $cat ) :
			$ids  = get_posts( array(
				'post_type'      => 'aswin_work',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'tax_query'      => array( array( 'taxonomy' => 'aswin_work_cat', 'field' => 'term_id', 'terms' => $cat->term_id ) ),
			) );
			$i    = array_search( $id, $ids, true );
			$prev = false !== $i && $i > 0 ? $ids[ $i - 1 ] : 0;
			$next = false !== $i && $i < count( $ids ) - 1 ? $ids[ $i + 1 ] : 0;
			if ( $prev || $next ) :
				?>
				<nav class="wrap post-nav" aria-label="More work">
					<?php if ( $prev ) : ?><a class="prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>"><small>← Previous</small><strong><?php echo esc_html( get_the_title( $prev ) ); ?></strong></a><?php endif; ?>
					<?php if ( $next ) : ?><a class="next" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><small>Next →</small><strong><?php echo esc_html( get_the_title( $next ) ); ?></strong></a><?php endif; ?>
				</nav>
				<?php
			endif;

			$more = get_posts( array(
				'post_type'      => 'aswin_work',
				'posts_per_page' => 3,
				'post__not_in'   => array( $id ),
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'tax_query'      => array( array( 'taxonomy' => 'aswin_work_cat', 'field' => 'term_id', 'terms' => $cat->term_id ) ),
			) );
			if ( $more ) :
				?>
				<section class="wrap related">
					<div class="section-head">
						<div class="chapter reveal"><b>+</b> Keep reading</div>
						<h2 class="section-title reveal">More <em><?php echo esc_html( $cat->name ); ?></em></h2>
					</div>
					<?php echo $r->works_grid( $more, array( 'columns' => 3, 'terms' => array( $cat ), 'design' => aswin_design_for( array( $cat ) ) ) ); // phpcs:ignore ?>
					<?php echo $r->view_more( $back, 'All ' . $cat->name ); // phpcs:ignore ?>
				</section>
				<?php
			endif;
		endif;
		?>
	</article>
	<?php
endwhile;

get_footer();
