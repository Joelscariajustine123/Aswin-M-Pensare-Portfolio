<?php
/**
 * Merge the Thavidari work (214) into IMAX Gold (202): image → IMAX side images,
 * write-up → new section on IMAX page, Thavidari work → trash.
 * Run: wp eval-file merge-thavidari.php --user=admin
 */
$from = 214;
$to   = 202;
if ( 'aswin_work' !== get_post_type( $from ) || 'trash' === get_post_status( $from ) ) {
	WP_CLI::error( 'Thavidari work not found (already merged?).' );
}

// Images: Thavidari cover (+ any gallery) appended to IMAX side images.
$add = array_filter( array_merge( array( (int) get_post_thumbnail_id( $from ) ), array_map( 'intval', explode( ',', (string) get_post_meta( $from, '_aswin_gallery', true ) ) ) ) );
$gal = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $to, '_aswin_gallery', true ) ) ) );
foreach ( $add as $a ) {
	if ( ! in_array( $a, $gal, true ) && $a !== (int) get_post_thumbnail_id( $to ) ) {
		$gal[] = $a;
	}
	wp_update_post( array( 'ID' => $a, 'post_parent' => $to ) );
}
update_post_meta( $to, '_aswin_gallery', implode( ',', $gal ) );

// Write-up: Thavidari's own text becomes a section on the IMAX Gold page.
$imax = get_post( $to );
if ( false === strpos( $imax->post_content, 'Thavidari Rice' ) ) {
	$section = '<!-- wp:heading --><h2 class="wp-block-heading">Thavidari Rice</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>' . esc_html( get_post_field( 'post_excerpt', $from ) ) . '</p><!-- /wp:paragraph -->'
		. get_post_field( 'post_content', $from );
	wp_update_post( array(
		'ID'           => $to,
		'post_content' => $imax->post_content . $section,
		'post_excerpt' => 'A creative advertising campaign celebrating the taste of tradition, the warmth of family, and the joy of sharing meals — across IMAX Gold Jeerakasala, Biriyani Chef and Thavidari Rice.',
	) );
}

wp_trash_post( $from );
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Merged. IMAX Gold side images: ' . implode( ',', $gal ) . '. Thavidari work moved to trash.' );
