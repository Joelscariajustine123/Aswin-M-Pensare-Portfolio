<?php
/**
 * Imports demo brands, works, images and contact details.
 * Run on the server:  wp eval-file import-demo.php <dir-with-demo.json-and-img> --user=admin
 * Everything created is tagged _aswin_demo = 1 (removable from WP Admin in one click).
 */

$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
$def = json_decode( file_get_contents( $dir . '/demo.json' ), true );
if ( ! $def ) {
	WP_CLI::error( 'Could not read demo.json in ' . $dir );
}
if ( get_option( 'aswin_demo_installed' ) ) {
	WP_CLI::error( 'Demo content is already installed. Remove it first (WP Admin notice) if you want to re-import.' );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function d_image( $dir, $name, $title, $parent = 0 ) {
	$src = $dir . '/img/' . $name . '.png';
	if ( ! file_exists( $src ) ) {
		WP_CLI::warning( "Missing image $name" );
		return 0;
	}
	$tmp = wp_tempnam( $name . '.png' );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => $name . '.png', 'tmp_name' => $tmp ), $parent, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_aswin_demo', '1' );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return (int) $id;
}

/* Brands */
$brand_ids  = array();
$brand_logo = array();
foreach ( $def['brands'] as $i => $b ) {
	$id = wp_insert_post( array(
		'post_type'    => 'aswin_brand',
		'post_status'  => 'publish',
		'post_title'   => $b['name'],
		'post_name'    => $b['slug'],
		'post_excerpt' => $b['excerpt'],
		'post_content' => $b['content'],
		'menu_order'   => $i + 1,
	) );
	update_post_meta( $id, '_aswin_demo', '1' );
	update_post_meta( $id, '_aswin_link', $b['url'] );
	$logo = d_image( $dir, 'logo-' . $b['slug'], $b['name'] . ' logo', $id );
	if ( $logo ) {
		set_post_thumbnail( $id, $logo );
	}
	$brand_ids[ $b['slug'] ]  = $id;
	$brand_logo[ $b['slug'] ] = $logo;
	WP_CLI::log( "Brand: {$b['name']}" );
}

/* Works */
foreach ( $def['works'] as $w ) {
	$content = '<!-- wp:heading --><h2 class="wp-block-heading">The brief</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>' . esc_html( $w['brief'] ) . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">The idea</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>' . esc_html( $w['idea'] ) . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">The result</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>' . esc_html( $w['result'] ) . '</p><!-- /wp:paragraph -->';
	$id = wp_insert_post( array(
		'post_type'    => 'aswin_work',
		'post_status'  => 'publish',
		'post_title'   => $w['title'],
		'post_name'    => $w['slug'],
		'post_excerpt' => $w['excerpt'],
		'post_content' => $content,
		'menu_order'   => (int) $w['order'],
	) );
	wp_set_object_terms( $id, $w['cat'], 'aswin_work_cat' );
	update_post_meta( $id, '_aswin_demo', '1' );
	update_post_meta( $id, '_aswin_brand', $brand_ids[ $w['brand'] ] ?? '' );
	update_post_meta( $id, '_aswin_year', $w['year'] );
	update_post_meta( $id, '_aswin_role', $w['role'] );
	$img = d_image( $dir, 'work-' . $w['slug'], $w['title'], $id );
	if ( $img ) {
		set_post_thumbnail( $id, $img );
	}
	if ( ! empty( $w['ig'] ) ) {
		update_post_meta( $id, '_aswin_ig_handle', $w['ig']['handle'] );
		update_post_meta( $id, '_aswin_ig_caption', $w['ig']['caption'] );
		update_post_meta( $id, '_aswin_ig_likes', $w['ig']['likes'] );
		update_post_meta( $id, '_aswin_ig_date', $w['ig']['date'] );
	}
	if ( 'branding' === $w['cat'] && ! empty( $brand_logo[ $w['brand'] ] ) ) {
		update_post_meta( $id, '_aswin_gallery', (string) $brand_logo[ $w['brand'] ] );
	}
	WP_CLI::log( "Work: {$w['title']}" );
}

/* Old placeholder social posts → remove; keep the real alt Pizza example after the demos. */
foreach ( array( 'Your next post', 'Another post' ) as $t ) {
	$p = get_posts( array( 'post_type' => 'aswin_work', 'title' => $t, 'posts_per_page' => 1, 'post_status' => 'any' ) );
	if ( $p ) {
		wp_delete_post( $p[0]->ID, true );
		WP_CLI::log( "Removed placeholder: $t" );
	}
}
$meat = get_posts( array( 'post_type' => 'aswin_work', 'title' => 'Meatball Day — alt Pizza', 'posts_per_page' => 1 ) );
if ( $meat ) {
	wp_update_post( array( 'ID' => $meat[0]->ID, 'menu_order' => 10 ) );
}

/* Contact details — only if none are set yet. */
if ( ! get_option( 'aswin_contact' ) ) {
	update_option( 'aswin_contact', $def['contact'] );
	update_option( 'aswin_contact_is_demo', 1 );
	WP_CLI::log( 'Contact details: demo placeholders set.' );
}

update_option( 'aswin_demo_installed', 1 );
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( count( $def['brands'] ) . ' brands and ' . count( $def['works'] ) . ' works imported.' );
