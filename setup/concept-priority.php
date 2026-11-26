<?php
/**
 * Concept section: add "Every Scent Tells a Story", fix film 12, draft the corrupted 123 short film,
 * and put the priority films first.
 * Run: wp eval-file concept-priority.php <dir> --user=admin
 */
$dir = rtrim( $args[0], '/' );
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$cat = get_term_by( 'slug', 'concept-scripting-direction', 'aswin_work_cat' );

function cp_up( $p, $parent, $t ) {
	$tmp = wp_tempnam( basename( $p ) );
	copy( $p, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $p ), 'tmp_name' => $tmp ), $parent, $t );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

// 1. New film.
$slug = 'every-scent-tells-a-story';
$new  = get_page_by_path( $slug, OBJECT, 'aswin_work' );
if ( ! $new ) {
	$content = '<!-- wp:heading --><h2 class="wp-block-heading">The idea</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html( 'A busy, stressful workday. One spray of fragrance — and a memory of childhood, festive clothes and family comes rushing back. The film tells the brand line instead of saying it: “Every scent tells a story.”' ) . '</p><!-- /wp:paragraph -->';
	$id = wp_insert_post( array(
		'post_type'    => 'aswin_work',
		'post_status'  => 'publish',
		'post_title'   => 'Every Scent Tells a Story — Fragrance Film',
		'post_name'    => $slug,
		'post_excerpt' => 'A short fragrance film where one spray at a stressful desk opens up a childhood memory.',
		'post_content' => $content,
	) );
	wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
	update_post_meta( $id, '_aswin_role', 'Concept, script & direction' );
	$v = cp_up( "$dir/film-scent.mp4", $id, 'Every Scent Tells a Story' );
	$p = cp_up( "$dir/poster-scent.jpg", $id, 'Every Scent Tells a Story (poster)' );
	update_post_meta( $v, '_aswin_poster', $p );
	set_post_thumbnail( $id, $p );
	update_post_meta( $id, '_aswin_videos', (string) $v );
	$new = get_post( $id );
	WP_CLI::log( 'Added ' . get_permalink( $id ) );
}

// 2. Replace film 12's file in place (same attachment, fixed colour range).
$w12 = get_page_by_path( 'exclusive-membership-film', OBJECT, 'aswin_work' );
if ( $w12 ) {
	$a = (int) get_post_meta( $w12->ID, '_aswin_videos', true );
	$path = get_attached_file( $a );
	if ( $path && copy( "$dir/film-12-fixed.mp4", $path ) ) {
		wp_update_attachment_metadata( $a, wp_generate_attachment_metadata( $a, $path ) );
		WP_CLI::log( 'Fixed film 12 file' );
	}
}

// 3. Corrupted 123 short film → draft.
$w3 = get_page_by_path( '123-cargo-short-film', OBJECT, 'aswin_work' );
if ( $w3 ) {
	wp_update_post( array( 'ID' => $w3->ID, 'post_status' => 'draft' ) );
	WP_CLI::log( 'Drafted 123 Cargo — Short Film (video file damaged)' );
}

// 4. Priority films first: within works, then work order.
$reorder = function ( $slug, $want_suffixes ) {
	$w = get_page_by_path( $slug, OBJECT, 'aswin_work' );
	if ( ! $w ) {
		return;
	}
	$ids  = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $w->ID, '_aswin_videos', true ) ) ) );
	$keyd = array();
	foreach ( $ids as $a ) {
		$keyd[ basename( (string) get_attached_file( $a ) ) ] = $a;
	}
	$out = array();
	foreach ( $want_suffixes as $s ) {
		foreach ( $keyd as $name => $a ) {
			if ( false !== strpos( $name, $s ) ) {
				$out[] = $a;
				unset( $keyd[ $name ] );
			}
		}
	}
	$out = array_merge( $out, array_values( $keyd ) );
	update_post_meta( $w->ID, '_aswin_videos', implode( ',', $out ) );
	$first = (int) get_post_meta( $out[0], '_aswin_poster', true );
	if ( $first ) {
		set_post_thumbnail( $w->ID, $first );
	}
};
$reorder( 'parpie-brand-films', array( '-07.', '-11.', '-08.' ) );         // For all the little moments, Some things…, Simple is comfort
$reorder( 'shatavari-ayurveda-films', array( '-01.', '-10.', '-02.' ) );   // Ancient Ayurveda first

$order = array(
	'every-scent-tells-a-story'     => 1,
	'parpie-brand-films'            => 2,
	'shatavari-ayurveda-films'      => 3,
	'123-cargo'                     => 4,
	'carecast-pregnancy-podcast'    => 5,
	'habari-real-estate-explainer'  => 6,
	'amend-dental-film'             => 7,
	'first-driving-centre-film'     => 8,
	'we-find-you-a-home-film'       => 9,
	'exclusive-membership-film'     => 10,
);
foreach ( $order as $s => $o ) {
	$w = get_page_by_path( $s, OBJECT, 'aswin_work' );
	if ( $w ) {
		wp_update_post( array( 'ID' => $w->ID, 'menu_order' => $o ) );
	}
}
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Concept section updated.' );
