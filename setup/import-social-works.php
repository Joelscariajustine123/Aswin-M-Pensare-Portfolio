<?php
/**
 * Adds the works/ folder posts to "Creative Copywriting — Social Media".
 * Run: wp eval-file import-social-works.php <dir-with-images> --user=admin
 * Safe to re-run: new works are skipped if their slug exists; IMAX images are only appended once.
 */

$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$cat = get_term_by( 'slug', 'social-media', 'aswin_work_cat' );
if ( ! $cat ) {
	WP_CLI::error( 'Category social-media not found.' );
}

function sw_upload( $dir, $file, $title, $parent ) {
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_aswin_src', 'meta_value' => $file ) ); // phpcs:ignore
	if ( $existing ) {
		return (int) $existing[0];
	}
	$src = $dir . '/' . $file;
	if ( ! file_exists( $src ) ) {
		WP_CLI::warning( "Missing $src" );
		return 0;
	}
	$tmp = wp_tempnam( $file );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), $parent, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_aswin_src', $file );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return (int) $id;
}

function sw_body( $sections ) {
	$c = '';
	foreach ( $sections as $h => $p ) {
		$c .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $h ) . '</h2><!-- /wp:heading -->';
		foreach ( (array) $p as $para ) {
			$c .= '<!-- wp:paragraph --><p>' . esc_html( $para ) . '</p><!-- /wp:paragraph -->';
		}
	}
	return $c;
}

/* ---------- New works ---------- */
$new = array(
	array(
		'slug'    => 'go-and-go-tours-travels',
		'title'   => 'Go & Go Tours & Travels',
		'excerpt' => 'Malayalam travel posts that sell Kazakhstan and Thailand packages as feelings first, itineraries second.',
		'role'    => 'Social copy, Malayalam headlines',
		'handle'  => 'goandgo_tours',
		'brand'   => 0,
		'images'  => array( 'gogo-kazakhstan.jpeg', 'gogo-thailand.jpeg' ),
		'caption' => "Borderless experiences, one boarding pass away. ✈️\nKazakhstan & Thailand group departures — book your seats now.\n#GoAndGo #Kazakhstan #Thailand #TravelKerala",
		'body'    => array(
			'The brief' => 'Promote Go & Go’s international group tour packages to Malayali travellers on social media — and make two very different destinations feel like the next trip to take.',
			'The idea'  => array(
				'Kazakhstan: “അതിരുകളില്ലാത്ത അനുഭവങ്ങളിലേക്ക്!” — “Towards experiences without borders!” The headline sells the feeling, while the destination list (Almaty, Shymbulak, Koktobe, Kolsai Lake, Kaindy Lake, Charyn Canyon) does the convincing.',
				'Thailand: “തായ്‌ലൻഡിലേക്ക് വിട്ടാലോ?” — “Shall we head off to Thailand?” A casual, conversational nudge, backed by the facts that matter: Bangkok & Pattaya, 3 nights / 4 days, a fixed departure date, inclusions and the package price.',
			),
			'The copy' => 'Each post pairs one warm Malayalam headline with crisp, scannable details and a single call to action — “Book your seats now” with the booking numbers — so interest turns straight into an enquiry.',
		),
	),
	array(
		'slug'    => 'lulookkas-gold-campaign',
		'title'   => 'Lulookkas Gold & Diamonds — “The Right Time”',
		'excerpt' => 'A four-post Malayalam story that answers the buyer’s biggest hesitation: “Should I wait for the right time to buy gold?”',
		'role'    => 'Campaign concept, Malayalam copy',
		'handle'  => 'lulookkas',
		'brand'   => 161,
		'images'  => array( 'lulookkas-1.jpeg', 'lulookkas-2.jpeg', 'lulookkas-3.jpeg', 'lulookkas-4.jpeg' ),
		'caption' => "Waiting for the right time to buy gold? 🤔\nThat time might be today.\nVisit Lulookkas Gold & Diamonds — Mukkam | Edavannappara | Kuttikkattoor\n#Lulookkas #GoldJewellery #BuyGold #Kerala",
		'body'    => array(
			'The brief' => 'Many customers keep postponing their gold purchase, waiting for prices to drop. Lulookkas needed social content that gently turns that hesitation into action.',
			'The idea'  => 'Tell it as a four-part conversation, borrowing the tone of much-loved Malayalam cinema moments so the message feels familiar and fun rather than salesy.',
			'The copy'  => array(
				'1 — The question: “സ്വർണ്ണം വാങ്ങാൻ ശരിയായ സമയം കാത്തിരിക്കുകയാണോ നിങ്ങൾ?” — “Are you waiting for the right time to buy gold?”',
				'2 — The twist: “എന്നാൽ ആ സമയം ഏതാണെന്ന് അറിയാമോ?” — “But do you know when that time is?”',
				'3 — The reason: wars, political uncertainty and global economic conditions all influence the price of gold — hesitate today and it may cost more tomorrow.',
				'4 — The call to action: “വില ഇനിയും ഉയരുന്നതിന് മുമ്പ് നിങ്ങളുടെ സ്വർണ്ണം സ്വന്തമാക്കൂ!” — “Own your gold before prices rise further! Today’s decision may be tomorrow’s gain.”',
			),
		),
	),
);

foreach ( $new as $w ) {
	if ( get_page_by_path( $w['slug'], OBJECT, 'aswin_work' ) ) {
		WP_CLI::log( "Exists, skipped: {$w['title']}" );
		continue;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'aswin_work',
		'post_status'  => 'publish',
		'post_title'   => $w['title'],
		'post_name'    => $w['slug'],
		'post_excerpt' => $w['excerpt'],
		'post_content' => sw_body( $w['body'] ),
		'menu_order'   => 0,
	) );
	wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
	update_post_meta( $id, '_aswin_role', $w['role'] );
	update_post_meta( $id, '_aswin_ig_handle', $w['handle'] );
	update_post_meta( $id, '_aswin_ig_caption', $w['caption'] );
	if ( $w['brand'] ) {
		update_post_meta( $id, '_aswin_brand', $w['brand'] );
	}
	$ids = array();
	foreach ( $w['images'] as $f ) {
		$a = sw_upload( $dir, $f, $w['title'], $id );
		if ( $a ) {
			$ids[] = $a;
		}
	}
	if ( $ids ) {
		set_post_thumbnail( $id, $ids[0] );
		if ( count( $ids ) > 1 ) {
			update_post_meta( $id, '_aswin_gallery', implode( ',', array_slice( $ids, 1 ) ) );
		}
	}
	WP_CLI::log( "Added: {$w['title']} — " . count( $ids ) . ' images — ' . get_permalink( $id ) );
}

/* ---------- IMAX Gold: append the two new posts to the existing work ---------- */
$imax = get_page_by_path( 'imax-gold', OBJECT, 'aswin_work' );
if ( $imax ) {
	$gal = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $imax->ID, '_aswin_gallery', true ) ) ) );
	foreach ( array( 'imax-biriyani-chef.jpeg', 'imax-jeerakashala.jpeg' ) as $f ) {
		$a = sw_upload( $dir, $f, 'IMAX Gold', $imax->ID );
		if ( $a && ! in_array( $a, $gal, true ) ) {
			$gal[] = $a;
		}
	}
	update_post_meta( $imax->ID, '_aswin_gallery', implode( ',', $gal ) );
	if ( ! get_post_meta( $imax->ID, '_aswin_brand', true ) ) {
		update_post_meta( $imax->ID, '_aswin_brand', 179 );
	}
	if ( ! get_post_meta( $imax->ID, '_aswin_role', true ) ) {
		update_post_meta( $imax->ID, '_aswin_role', 'Social copy, Malayalam headlines' );
	}
	// Add the new posts to the write-up without touching what is already there.
	if ( false === strpos( $imax->post_content, 'More from the series' ) ) {
		$extra = sw_body( array(
			'More from the series' => array(
				'Biriyani Chef: “കല്യാണ ബിരിയാണിയുടെ രുചി ഇനി വീട്ടിലും!” — “The taste of wedding biryani, now at home too!” A mother-and-child moment that brings the celebration-table favourite into everyday cooking.',
				'Jeerakashala: “ഓരോ അരിയിലും തനത് പാരമ്പര്യം” — “Authentic tradition in every grain.” Supporting copy explains why it matters: rice aged for close to two years that cooks up non-sticky, grain by grain.',
			),
		) );
		wp_update_post( array( 'ID' => $imax->ID, 'post_content' => $imax->post_content . $extra ) );
	}
	WP_CLI::log( 'IMAX Gold: gallery now ' . implode( ',', $gal ) );
}

if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Social media works imported.' );
