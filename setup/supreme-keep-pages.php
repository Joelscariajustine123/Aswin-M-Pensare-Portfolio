<?php
/**
 * Keep only the chosen PDF pages (in this order) on the Supreme work; delete the other page images.
 * Run: wp eval-file supreme-keep-pages.php --user=admin
 */
$keep = array( 3, 5, 2, 6, 7, 8, 12, 13, 14 );

$work = get_page_by_path( 'supreme-brand-guidelines', OBJECT, 'aswin_work' );
$atts = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_parent' => $work->ID, 'posts_per_page' => -1 ) );

$by_page = array();
$other   = array();
foreach ( $atts as $a ) {
	$file = basename( (string) get_attached_file( $a->ID ) );
	if ( preg_match( '/^supreme-guidelines-(\d+)/', $file, $m ) ) {
		$by_page[ (int) $m[1] ][] = $a->ID;
	} else {
		$other[] = $a->ID; // old screenshot slides
	}
}
WP_CLI::log( count( $by_page ) . ' PDF pages found, ' . count( $other ) . ' older slides.' );
foreach ( $keep as $n ) {
	if ( empty( $by_page[ $n ] ) ) {
		WP_CLI::error( "Page $n missing — nothing changed." );
	}
}

$ids = array();
foreach ( $keep as $n ) {
	$ids[] = array_shift( $by_page[ $n ] );
}
set_post_thumbnail( $work->ID, $ids[0] );
update_post_meta( $work->ID, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );

$deleted = 0;
foreach ( $by_page as $list ) {
	foreach ( $list as $id ) {
		wp_delete_attachment( $id, true );
		$deleted++;
	}
}
foreach ( $other as $id ) {
	wp_delete_attachment( $id, true );
	$deleted++;
}
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Kept ' . count( $ids ) . ' pages (' . implode( ', ', $keep ) . "), deleted $deleted other images." );
