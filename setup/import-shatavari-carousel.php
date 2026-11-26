<?php
/**
 * Shatavari "3 Causes of Disease" carousel → Creative Copywriting — Social Media.
 * Run: wp eval-file import-shatavari-carousel.php <dir> --user=admin
 */
$dir = rtrim( $args[0], '/' );
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$slug = 'shatavari-3-causes-of-disease';
if ( get_page_by_path( $slug, OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Already exists.' );
}
$cat = get_term_by( 'slug', 'social-media', 'aswin_work_cat' );

$body = array(
	'The brief' => 'Turn a core Ayurvedic idea — the three causes of disease — into a social carousel that people actually swipe through, for Shatavari Ayurveda, Wayanad.',
	'The idea'  => 'One cover, three slides, one simple structure on every slide: what it is, a one-line truth, the everyday examples, the impact, and a short action to take.',
	'The copy'  => array(
		'Atiyoga — Over use: “When it’s too much, the system suffers.” Action: Reduce. Slow down. Respect your limits.',
		'Ayoga — Under use: “Lack of use leads to stagnation.” Action: Move daily. Stay active. Stay connected.',
		'Mithyayoga — Wrong use: “Improper use can cause illness.” Action: Correct your habits. Align with nature.',
	),
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
	'post_title'   => 'Shatavari Ayurveda — 3 Causes of Disease',
	'post_name'    => $slug,
	'post_excerpt' => 'A four-slide carousel that explains the Ayurvedic causes of disease — over use, under use and wrong use — and what to do about each.',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Social copy, carousel content' );
update_post_meta( $id, '_aswin_ig_handle', 'shatavariayurveda' );
update_post_meta( $id, '_aswin_ig_caption', "Disease doesn’t just happen. Ayurveda traces it to three causes — over use, under use and wrong use. Swipe to see which one is affecting you. 🌿\n#ShatavariAyurveda #Ayurveda #Wellness #Wayanad" );
$b = get_posts( array( 'post_type' => 'aswin_brand', 's' => 'Shatavari', 'posts_per_page' => 1 ) );
if ( $b ) {
	update_post_meta( $id, '_aswin_brand', $b[0]->ID );
}

$ids = array();
foreach ( array( 'cover', 'atiyoga', 'ayoga', 'mithyayoga' ) as $k ) {
	$f   = "shatavari-carousel-$k.jpeg";
	$tmp = wp_tempnam( $f );
	copy( "$dir/$f", $tmp );
	$a = media_handle_sideload( array( 'name' => $f, 'tmp_name' => $tmp ), $id, 'Shatavari — 3 Causes of Disease' );
	if ( ! is_wp_error( $a ) ) {
		$ids[] = (int) $a;
	}
}
set_post_thumbnail( $id, $ids[0] );
update_post_meta( $id, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( get_permalink( $id ) . ' — ' . count( $ids ) . ' images' );
