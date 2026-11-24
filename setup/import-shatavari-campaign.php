<?php
/**
 * Shatavari Ayurveda — "The Way of Ayurveda" (National Ayurveda Day 2026) → Campaigns / PR Events.
 * Run: wp eval-file import-shatavari-campaign.php <dir> --user=admin
 * <dir> holds banner.jpeg, post-*.jpeg, v1..v3-web.mp4 and v1..v3-poster.jpg.
 */
$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$cat = get_term_by( 'slug', 'campaigns-pr', 'aswin_work_cat' );
if ( ! $cat ) {
	WP_CLI::error( 'Campaigns category not found.' );
}
if ( get_page_by_path( 'shatavari-way-of-ayurveda', OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Shatavari campaign already exists.' );
}

function sh_up( $dir, $file, $title, $parent ) {
	$tmp = wp_tempnam( $file );
	copy( $dir . '/' . $file, $tmp );
	$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), $parent, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $file . ': ' . $id->get_error_message() );
		return 0;
	}
	return (int) $id;
}

$body = array(
	'The brief'  => 'Celebrate National Ayurveda Day 2026 for Shatavari Ayurveda, Wayanad — and use the moment to remind people that Ayurveda is a way of living, not just a treatment.',
	'The idea'   => '“The Way of Ayurveda.” One campaign line, carried through every touchpoint under the national theme “Ayurveda for a healthier tomorrow”. A hero banner honours “a timeless tradition that believes true wellness begins with balance”, and the series breaks that balance down into the everyday pillars Ayurveda is built on.',
	'The series' => array(
		'Nidrā | Sleep — restful sleep as an essential part of balance, letting body and mind unwind, recover and reconnect with their natural rhythm.',
		'Āhāra | Diet — mindful eating and wholesome nutrition that support agni, metabolism and dosha balance.',
		'Vyāyāma | Exercise — physical activity practised according to one’s Prakriti, age and strength, for strength, stamina and body-mind balance.',
	),
	'The films'  => 'Three short podcast-style reels with Shatavari’s experts take the conversation further — why diseases keep coming back, what makes Panchakarma so powerful, and why Ayurveda has endured for so long.',
);
$content = '';
foreach ( $body as $h => $p ) {
	$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $h ) . '</h2><!-- /wp:heading -->';
	foreach ( (array) $p as $para ) {
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $para ) . '</p><!-- /wp:paragraph -->';
	}
}

$id = wp_insert_post( array(
	'post_type'    => 'aswin_work',
	'post_status'  => 'publish',
	'post_title'   => 'The Way of Ayurveda — Shatavari Ayurveda',
	'post_name'    => 'shatavari-way-of-ayurveda',
	'post_excerpt' => 'A National Ayurveda Day 2026 campaign — banner, social series and expert video reels — built around one line: “The Way of Ayurveda.”',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Campaign concept & copy' );
update_post_meta( $id, '_aswin_year', '2026' );
$brand = get_page_by_path( 'shatavari-ayurveda', OBJECT, 'aswin_brand' );
if ( ! $brand ) {
	$found = get_posts( array( 'post_type' => 'aswin_brand', 's' => 'Shatavari', 'posts_per_page' => 1 ) );
	$brand = $found ? $found[0] : null;
}
if ( $brand ) {
	update_post_meta( $id, '_aswin_brand', $brand->ID );
}

// Images: banner = cover; the three posts in series order.
$banner = sh_up( $dir, 'banner.jpeg', 'The Way of Ayurveda — Ayurveda Day 2026 banner', $id );
set_post_thumbnail( $id, $banner );
$posts = array();
foreach ( array( 'post-1-nidra-sleep.jpeg' => 'Nidrā | Sleep', 'post-2-ahara-diet.jpeg' => 'Āhāra | Diet', 'post-3-vyayama-exercise.jpeg' => 'Vyāyāma | Exercise' ) as $f => $t ) {
	$a = sh_up( $dir, $f, 'The Way of Ayurveda — ' . $t, $id );
	if ( $a ) {
		update_post_meta( $a, '_wp_attachment_image_alt', 'The Way of Ayurveda — ' . $t );
		$posts[] = $a;
	}
}
update_post_meta( $id, '_aswin_gallery', implode( ',', $posts ) );

// Films: compressed reels + poster frames.
$films = array();
foreach ( array( 'v1' => 'Why do diseases keep coming back?', 'v2' => 'What makes Panchakarma so powerful?', 'v3' => 'Why Ayurveda has endured' ) as $v => $t ) {
	$vid = sh_up( $dir, $v . '-web.mp4', $t, $id );
	if ( ! $vid ) {
		continue;
	}
	$poster = sh_up( $dir, $v . '-poster.jpg', $t . ' (poster)', $id );
	if ( $poster ) {
		update_post_meta( $vid, '_aswin_poster', $poster );
	}
	$films[] = $vid;
}
update_post_meta( $id, '_aswin_videos', implode( ',', $films ) );

if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( get_permalink( $id ) . ' — banner, ' . count( $posts ) . ' posts, ' . count( $films ) . ' films.' );
