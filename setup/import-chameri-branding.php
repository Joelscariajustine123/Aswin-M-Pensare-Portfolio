<?php
/**
 * Adds the Chameri brand book (brands/ folder) to "Branding" (laptop slider).
 * Run: wp eval-file import-chameri-branding.php <dir-with-slides> --user=admin
 */
$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$cat = get_term_by( 'slug', 'branding', 'aswin_work_cat' );
if ( get_page_by_path( 'chameri-brand-identity', OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Chameri work already exists.' );
}

$slides = array(
	'chameri-01-life-settles-here.jpeg',
	'chameri-02-what-chameri-stands-for.jpeg',
	'chameri-03-foundations.jpeg',
	'chameri-04-building-trust-since-1985.jpeg',
	'chameri-05-brand-values.jpeg',
	'chameri-06-verbal-identity.jpeg',
	'chameri-07-less-said-more-believed.jpeg',
	'chameri-08-brand-voice.jpeg',
	'chameri-09-brand-tone.jpeg',
	'chameri-10-measured-words.jpeg',
);

$sections = array(
	'The brief'   => 'Chameri has been in real estate since 1985. Over four decades it has seen the gap between what premium property buyers hope for and what they are actually offered — and it needed a brand that speaks to that gap without the loud promises the category is known for.',
	'The idea'    => '“Life Settles Here.” Chameri is about inward living — a chamber of inner space, a cocoon where the outside world fades at the threshold. In a market crowded with claims, the brand chooses restraint: “Less said. More believed.”',
	'What I wrote' => array(
		'The brand foundations — what the name means and why home today is as much about quieting the mind as sheltering the body.',
		'The brand story (“Building trust since 1985”) and purpose (“What Chameri really stands for”).',
		'Brand values — Guided by Purpose, Responsibility, Thoughtfulness, Balance and Connection.',
		'The verbal identity: a voice that speaks softly and stays composed, a tone guided by honesty and restraint, and the principle that ties them together — “Measured words. Steady values.”',
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
	'post_title'   => 'Chameri — Brand Identity',
	'post_name'    => 'chameri-brand-identity',
	'post_excerpt' => '“Life Settles Here.” A quiet, confident verbal identity for a real estate brand building trust since 1985.',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Brand strategy & verbal identity' );
update_post_meta( $id, '_aswin_year', '2026' );

$ids = array();
foreach ( $slides as $f ) {
	$tmp = wp_tempnam( $f );
	copy( $dir . '/' . $f, $tmp );
	$a = media_handle_sideload( array( 'name' => $f, 'tmp_name' => $tmp ), $id, 'Chameri brand identity' );
	if ( is_wp_error( $a ) ) {
		WP_CLI::warning( $a->get_error_message() );
		continue;
	}
	update_post_meta( $a, '_wp_attachment_image_alt', 'Chameri brand identity slide' );
	$ids[] = $a;
}
set_post_thumbnail( $id, $ids[0] );
update_post_meta( $id, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Added ' . get_permalink( $id ) . ' with ' . count( $ids ) . ' slides.' );
