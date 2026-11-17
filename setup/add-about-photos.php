<?php
/**
 * Adds a Photo Collage widget under the "Hi, I'm Aswin" heading in the About section.
 * Usage: wp eval-file add-about-photos.php --user=admin [-- <img-url-1> <img-url-2> <img-url-3>]
 * Optional URLs are for preview testing only.
 */

$page_id = (int) get_option( 'page_on_front' );
$data    = json_decode( get_post_meta( $page_id, '_elementor_data', true ), true );
$urls    = isset( $args ) ? (array) $args : array();
$caps    = array( 'That’s me.', 'Mid-idea.', 'Off the clock.' );
$photos  = array();
foreach ( $caps as $i => $cap ) {
	$photos[] = array(
		'_id'     => substr( md5( uniqid( '', true ) ), 0, 7 ),
		'image'   => isset( $urls[ $i ] ) ? array( 'id' => '', 'url' => $urls[ $i ] ) : array( 'id' => '', 'url' => '' ),
		'caption' => $cap,
	);
}
$widget = array(
	'id'         => substr( md5( uniqid( '', true ) . 'c' ), 0, 7 ),
	'elType'     => 'widget',
	'widgetType' => 'aswin-photo-collage',
	'settings'   => array( 'layout' => 'stack', 'photos' => $photos, 'polaroid' => 'yes', 'tilt' => 'yes', 'lightbox' => 'yes', 'sticker' => 'Hello!' ),
	'elements'   => array(),
);

$done = false;
foreach ( $data as &$section ) {
	if ( ( $section['settings']['_element_id'] ?? '' ) !== 'about' ) {
		continue;
	}
	foreach ( $section['elements'] as &$col ) {
		if ( false === strpos( $col['settings']['css_classes'] ?? '', 'about-left' ) ) {
			continue;
		}
		// Replace an existing collage (re-runs) or append after the heading.
		$col['elements'] = array_values( array_filter( $col['elements'], function ( $e ) {
			return ( $e['widgetType'] ?? '' ) !== 'aswin-photo-collage';
		} ) );
		$col['elements'][] = $widget;
		$done = true;
	}
	unset( $col );
}
unset( $section );

if ( ! $done ) {
	WP_CLI::error( 'About left column not found — nothing changed.' );
}
\Elementor\Plugin::$instance->documents->get( $page_id, false )->save( array( 'elements' => $data ) );
\Elementor\Plugin::$instance->files_manager->clear_cache();
WP_CLI::success( 'Photo Collage added to the About section' . ( $urls ? ' (with preview images)' : '' ) . '.' );
