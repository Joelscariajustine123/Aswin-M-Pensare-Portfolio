<?php
/**
 * Replaces the Supreme work's slides with the rendered PDF pages (page order kept).
 * Run: wp eval-file supreme-pdf-slides.php <dir-with-page-NNN.jpg> --user=admin
 * Optional: --skip=3,7 (page numbers to leave out, e.g. blank pages) via env SKIP.
 */

$dir  = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
$skip = array_filter( array_map( 'intval', explode( ',', (string) getenv( 'SKIP' ) ) ) );
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$work = get_page_by_path( 'supreme-brand-guidelines', OBJECT, 'aswin_work' );
if ( ! $work ) {
	WP_CLI::error( 'Supreme work not found.' );
}
$old = aswin_work_images( $work->ID );

$files = glob( $dir . '/page-*.jpg' );
sort( $files, SORT_NATURAL );
$ids = array();
foreach ( $files as $f ) {
	$n = (int) preg_replace( '/\D/', '', basename( $f ) );
	if ( in_array( $n, $skip, true ) ) {
		continue;
	}
	$name = 'supreme-guidelines-' . str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) . '.jpg';
	$tmp  = wp_tempnam( $name );
	copy( $f, $tmp );
	$a = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $work->ID, 'Supreme Bakes brand guidelines — page ' . $n );
	if ( is_wp_error( $a ) ) {
		WP_CLI::warning( $a->get_error_message() );
		continue;
	}
	update_post_meta( $a, '_wp_attachment_image_alt', 'Supreme Bakes brand guidelines, page ' . $n );
	$ids[] = $a;
}
if ( ! $ids ) {
	WP_CLI::error( 'No pages imported.' );
}
set_post_thumbnail( $work->ID, $ids[0] );
update_post_meta( $work->ID, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );

// Remove the earlier screenshot slides (uploaded only for this work).
foreach ( $old as $a ) {
	if ( (int) get_post_field( 'post_parent', $a ) === (int) $work->ID ) {
		wp_delete_attachment( $a, true );
	}
}
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( count( $ids ) . ' PDF pages are now the Supreme slides.' );
