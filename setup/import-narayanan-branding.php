<?php
/**
 * T. Narayanan Fruits & Vegetables brand guideline → Branding (laptop slider).
 * Run: wp eval-file import-narayanan-branding.php <dir> --user=admin
 */
$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( get_page_by_path( 't-narayanan-brand-guidelines', OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Already exists.' );
}
$cat = get_term_by( 'slug', 'branding', 'aswin_work_cat' );

// Deck order (story order, not file-number order).
$order = array( 130, 51, 57, 147, 149, 151, 153, 154, 158, 47, 53, 145, 55, 56, 142, 134 );

$sections = array(
	'The brief'    => 'T. Narayanan Fruits & Vegetables has served Kerala since 1953 — a family business that grew into one of the region’s most trusted fresh-produce names. The brand needed guidelines that keep that legacy intact while helping it speak to new audiences, from wholesale partners to retail and digital customers.',
	'The idea'     => '“Strong Roots. Fresh Future.” A masterbrand system that carries decades of trust forward, summed up in one promise: Consistency in Every Connection.',
	'What I wrote' => array(
		'The brand overview — mission, vision and brand promise, and the story of “Carrying the goodness forward”.',
		'Brand personality, values and philosophy — built on legacy, trust, consistency, freshness and quality, reliability, approachability and a customer-first mindset.',
		'The verbal identity — “Words that reflect our legacy”: a voice that is confident, trustworthy and approachable, a tone that adapts for wholesale, retail and digital audiences in simple English and Malayalam, and clear communication do’s and don’ts.',
	),
);
$content = '';
foreach ( $sections as $h => $p ) {
	$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $h ) . '</h2><!-- /wp:heading -->';
	foreach ( (array) $p as $para ) {
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $para ) . '</p><!-- /wp:paragraph -->';
	}
}

$id = wp_insert_post( array(
	'post_type'    => 'aswin_work',
	'post_status'  => 'publish',
	'post_title'   => 'T. Narayanan — Brand Guidelines',
	'post_name'    => 't-narayanan-brand-guidelines',
	'post_excerpt' => '“Strong Roots. Fresh Future.” Brand story, values and verbal identity for Kerala’s trusted fresh-produce partner since 1953.',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Brand strategy & verbal identity' );
update_post_meta( $id, '_aswin_year', '2026' );
$b = get_posts( array( 'post_type' => 'aswin_brand', 's' => 'Narayanan', 'posts_per_page' => 1 ) );
if ( $b ) {
	update_post_meta( $id, '_aswin_brand', $b[0]->ID );
}

$files = glob( $dir . '/*-slide-*' );
$by    = array();
foreach ( $files as $f ) {
	if ( preg_match( '/-slide-(\d+)\./', basename( $f ), $m ) ) {
		$by[ (int) $m[1] ] = $f;
	}
}
$ids = array();
foreach ( $order as $n ) {
	if ( empty( $by[ $n ] ) ) {
		WP_CLI::warning( "Slide $n missing" );
		continue;
	}
	$name = 't-narayanan-guidelines-' . $n . '.' . pathinfo( $by[ $n ], PATHINFO_EXTENSION );
	$tmp  = wp_tempnam( $name );
	copy( $by[ $n ], $tmp );
	$a = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $id, 'T. Narayanan brand guidelines' );
	if ( is_wp_error( $a ) ) {
		WP_CLI::warning( $a->get_error_message() );
		continue;
	}
	update_post_meta( $a, '_wp_attachment_image_alt', 'T. Narayanan brand guidelines slide' );
	$ids[] = $a;
}
set_post_thumbnail( $id, $ids[0] );
update_post_meta( $id, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( get_permalink( $id ) . ' — ' . count( $ids ) . ' slides' . ( $b ? ', brand: ' . $b[0]->post_title : ', no brand linked' ) );
