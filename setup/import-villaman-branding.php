<?php
/**
 * Villaman brand guidelines → Branding (laptop slider).
 * Run: wp eval-file import-villaman-branding.php <dir> --user=admin
 */
$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( get_page_by_path( 'villaman-brand-guidelines', OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Already exists.' );
}
$cat = get_term_by( 'slug', 'branding', 'aswin_work_cat' );

// Deck order (story order, not file-number order).
$order = array( 1, 197, 198, 230, 229, 269, 273, 252, 227, 228, 199, 200, 201 );

$sections = array(
	'The brief'    => 'Villaman grew out of Alshwifat, a family business founded in Dubai in 2006 as a villa maintenance company. For twenty years its work was built entirely on trust — no advertising, just word of mouth across villa communities. Villa owners still found renovation, maintenance and day-to-day issues a burden, and the market needed one partner for all of it.',
	'The idea'     => '“Villaman, Your Villa Partner.” Not the start of something new, but the recognition of what has always existed — the same values and commitment to doing the work right, now focused on being an all-in-one partner that gives villa owners peace of mind.',
	'What I wrote' => array(
		'The brand story, mission and vision — making every villa feel truly yours by taking complete responsibility for its maintenance and renovation.',
		'Brand personality (Leadership, Approachable, Assured, Reliable) and brand values — Reliable, Honest, Time Assurance, Quality Driven, Expertise, Collaborative.',
		'The verbal identity — “How we speak. How we make people feel.” A voice like a responsible caretaker, four core voice principles (approachable, warm, sincere, detail-focused), tone for clients, business, internal and marketing, and clear do’s and don’ts.',
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
	'post_title'   => 'Villaman — Brand Guidelines',
	'post_name'    => 'villaman-brand-guidelines',
	'post_excerpt' => '“Villaman, Your Villa Partner.” Brand story, values and verbal identity for an all-in-one villa care brand built on twenty years of trust.',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Brand strategy & verbal identity' );
$b = get_posts( array( 'post_type' => 'aswin_brand', 's' => 'Villaman', 'posts_per_page' => 1 ) );
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
	$name = 'villaman-guidelines-' . $n . '.' . pathinfo( $by[ $n ], PATHINFO_EXTENSION );
	$tmp  = wp_tempnam( $name );
	copy( $by[ $n ], $tmp );
	$a = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $id, 'Villaman brand guidelines' );
	if ( is_wp_error( $a ) ) {
		WP_CLI::warning( $a->get_error_message() );
		continue;
	}
	update_post_meta( $a, '_wp_attachment_image_alt', 'Villaman brand guidelines slide' );
	$ids[] = $a;
}
set_post_thumbnail( $id, $ids[0] );
update_post_meta( $id, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( get_permalink( $id ) . ' — ' . count( $ids ) . ' slides' . ( $b ? ', brand: ' . $b[0]->post_title : ', no brand linked' ) );
