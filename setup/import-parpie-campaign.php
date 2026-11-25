<?php
/**
 * Malabar Hospitals (World Diabetes Day) + Wonderla (Summer) → Campaigns / PR Events, image-only.
 * Run: wp eval-file import-campaign-boards.php <dir> --user=admin
 */
$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$cat   = get_term_by( 'slug', 'campaigns-pr', 'aswin_work_cat' );
$items = array(	array(		'slug'    => 'parpie-brand-launch',		'title'   => 'Parpie — Brand Launch Campaign',		'excerpt' => '“You Know Your Baby. We Know Our Diapers.” A multi-platform launch that introduced Parpie as a trusted, parent-first baby care brand.',		'file'    => 'parpie-brand-launch-campaign.png',		'brand'   => 'Parpie',		'year'    => '',		'order'   => 3,	),);

foreach ( $items as $it ) {
	if ( get_page_by_path( $it['slug'], OBJECT, 'aswin_work' ) ) {
		WP_CLI::log( "Exists: {$it['title']}" );
		continue;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'aswin_work',
		'post_status'  => 'publish',
		'post_title'   => $it['title'],
		'post_name'    => $it['slug'],
		'post_excerpt' => $it['excerpt'],
		'menu_order'   => $it['order'],
	) );
	wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
	update_post_meta( $id, '_aswin_image_only', '1' );
	update_post_meta( $id, '_aswin_role', 'Campaign concept & copy' );
	if ( $it['year'] ) {
		update_post_meta( $id, '_aswin_year', $it['year'] );
	}
	$b = get_posts( array( 'post_type' => 'aswin_brand', 's' => $it['brand'], 'posts_per_page' => 1 ) );
	if ( $b ) {
		update_post_meta( $id, '_aswin_brand', $b[0]->ID );
	}
	$tmp = wp_tempnam( $it['file'] );
	copy( $dir . '/' . $it['file'], $tmp );
	$a = media_handle_sideload( array( 'name' => $it['file'], 'tmp_name' => $tmp ), $id, $it['title'] );
	if ( is_wp_error( $a ) ) {
		WP_CLI::warning( $a->get_error_message() );
		continue;
	}
	update_post_meta( $a, '_wp_attachment_image_alt', $it['title'] );
	set_post_thumbnail( $id, $a );
	WP_CLI::log( 'Added ' . get_permalink( $id ) . ( $b ? ' (brand: ' . $b[0]->post_title . ')' : '' ) );
}
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Done.' );
