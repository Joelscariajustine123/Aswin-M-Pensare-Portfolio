<?php
/**
 * Adds the Supreme Bakes brand-guidelines deck to "Branding" and switches Branding to the laptop design.
 * Run: wp eval-file import-supreme-branding.php <dir-with-slides> --user=admin
 */

$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$cat = get_term_by( 'slug', 'branding', 'aswin_work_cat' );
if ( ! $cat ) {
	WP_CLI::error( 'Branding category not found.' );
}
update_term_meta( $cat->term_id, 'aswin_style', 'laptop' );

$slug = 'supreme-brand-guidelines';
if ( get_page_by_path( $slug, OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Supreme work already exists.' );
}

// Slides in deck order (cover first).
$slides = array(
	'supreme-01-happiness-is-supreme.jpg',
	'supreme-02-foundation.jpg',
	'supreme-03-refining-identity.jpg',
	'supreme-04-positioning.jpg',
	'supreme-05-personality.jpg',
	'supreme-06-values.jpg',
	'supreme-07-voice.jpg',
	'supreme-08-tone.jpg',
);

$sections = array(
	'The brief'   => 'Supreme Bakes has been part of everyday life and cherished celebrations across Kerala for over four decades. The brand needed a refined, consistent identity for the future — without losing the trust and familiarity generations of customers already feel.',
	'The idea'    => '“Supreme Bake is not rebranding, we are actually de-branding.” Instead of reinventing the brand, the guidelines strip it back to what has always made it special and express it more clearly — summed up in one line: “Happiness is Supreme.”',
	'What I wrote' => array(
		'The Foundation of Supreme — the brand story, built on one simple philosophy: if a product is not good enough for our own family, it is not good enough for anyone else.',
		'Brand positioning pillars — Heritage & Legacy, Trust & Care, Quality Without Compromise and Shared Moments.',
		'Brand personality — Nurturing, Trustworthy, Authentic and Approachable — and seven brand values, from Uncompromising Quality to Sustainable Growth.',
		'Brand voice (Classic, Descriptive, Warm & Inviting) and tone, with clear do’s and don’ts so every team writes in the same voice.',
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
	'post_title'   => 'Supreme Bakes — Brand Identity Guidelines',
	'post_name'    => $slug,
	'post_excerpt' => 'Brand story, positioning, personality, values and voice for a Kerala bakery loved for 46 years — refined, not reinvented.',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Brand strategy, voice & tone' );
update_post_meta( $id, '_aswin_year', '2026' );
$brand = get_page_by_path( 'supreme-the-food-co', OBJECT, 'aswin_brand' );
if ( $brand ) {
	update_post_meta( $id, '_aswin_brand', $brand->ID );
}

$ids = array();
foreach ( $slides as $f ) {
	$tmp = wp_tempnam( $f );
	copy( $dir . '/' . $f, $tmp );
	$a = media_handle_sideload( array( 'name' => $f, 'tmp_name' => $tmp ), $id, 'Supreme Bakes brand guidelines' );
	if ( is_wp_error( $a ) ) {
		WP_CLI::warning( $a->get_error_message() );
		continue;
	}
	update_post_meta( $a, '_wp_attachment_image_alt', 'Supreme Bakes brand guidelines slide' );
	$ids[] = $a;
}
set_post_thumbnail( $id, $ids[0] );
update_post_meta( $id, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );

if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Added ' . get_permalink( $id ) . ' with ' . count( $ids ) . ' slides.' );
