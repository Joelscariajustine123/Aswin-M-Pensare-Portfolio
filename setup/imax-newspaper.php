<?php
/**
 * Moves the Madhyamam newspaper page (attachment 203) from the IMAX Gold social work to a new Newspaper Ad work.
 * Run: wp eval-file imax-newspaper.php --user=admin
 */
$paper = 203;
$imax  = 202;
$cat   = get_term_by( 'slug', 'newspaper-ad', 'aswin_work_cat' );
if ( ! $cat || 'attachment' !== get_post_type( $paper ) ) {
	WP_CLI::error( 'Newspaper category or attachment missing.' );
}

// 1. IMAX Gold (social): standalone ad becomes the cover; newspaper page removed.
$gal = array_values( array_diff( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $imax, '_aswin_gallery', true ) ) ) ), array( $paper, 204 ) ) );
set_post_thumbnail( $imax, 204 );
update_post_meta( $imax, '_aswin_gallery', implode( ',', $gal ) );

// 2. New Newspaper Ad work using the same image.
if ( get_page_by_path( 'imax-gold-madhyamam-ad', OBJECT, 'aswin_work' ) ) {
	WP_CLI::error( 'Newspaper work already exists.' );
}
$body = array(
	'The brief' => 'Put IMAX Gold Jeerakasala biryani rice in front of Malappuram readers in a print placement that feels warm and homely — not just another product ad.',
	'The idea'  => '“നാവിലെത്തും മുന്നേ ഖൽബ് നിറയണ മാജിക്” — “The magic that fills your heart before it even reaches your tongue.” A grandmother lifting the lid on a pot of biryani while her grandson leans in captures the moment the aroma arrives, using the Malabar word “ഖൽബ്” (heart) to speak to readers in their own language.',
	'The copy'  => 'The headline leads, the image does the emotional work, and the support line — “രുചിക്കും ഗുണനിലവാരത്തിനും jeerakasala ബിരിയാണി അരി” (Jeerakasala biryani rice, for taste and quality) — closes on the product, followed by the company contact details.',
	'Placement' => 'Madhyamam, Malappuram edition — front page, 26 February 2026.',
);
$content = '';
foreach ( $body as $h => $p ) {
	$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $h ) . '</h2><!-- /wp:heading --><!-- wp:paragraph --><p>' . esc_html( $p ) . '</p><!-- /wp:paragraph -->';
}
$id = wp_insert_post( array(
	'post_type'    => 'aswin_work',
	'post_status'  => 'publish',
	'post_title'   => 'IMAX Gold — Madhyamam Front-Page Ad',
	'post_name'    => 'imax-gold-madhyamam-ad',
	'post_excerpt' => 'A front-page print ad for IMAX Gold Jeerakasala rice — “the magic that fills your heart before it reaches your tongue.”',
	'post_content' => $content,
	'menu_order'   => 0,
) );
wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
update_post_meta( $id, '_aswin_role', 'Headline & print copy' );
update_post_meta( $id, '_aswin_year', '2026' );
update_post_meta( $id, '_aswin_brand', 179 );
wp_update_post( array( 'ID' => $paper, 'post_parent' => $id ) );
update_post_meta( $paper, '_wp_attachment_image_alt', 'IMAX Gold ad on the front page of Madhyamam' );
set_post_thumbnail( $id, $paper );

if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'IMAX Gold cover is now 204 (gallery ' . implode( ',', $gal ) . '); newspaper ad added: ' . get_permalink( $id ) );
