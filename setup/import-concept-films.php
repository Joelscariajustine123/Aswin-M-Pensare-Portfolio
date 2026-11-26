<?php
/**
 * Concept Development, Scripting & Direction — 14 films grouped into 9 works.
 * Run: wp eval-file import-concept-films.php <dir with film-NN.mp4 + poster-NN.jpg> --user=admin
 */
$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$cat = get_term_by( 'slug', 'concept-scripting-direction', 'aswin_work_cat' );
if ( ! $cat ) {
	WP_CLI::error( 'Category not found.' );
}
update_term_meta( $cat->term_id, 'aswin_style', 'video' );

// [slug, title, excerpt, brand search, films => [file no => film title], role]
$works = array(
	array( 'shatavari-ayurveda-films', 'Shatavari Ayurveda — Podcast Reels & Brand Film', 'Expert podcast reels and a brand film that make Ayurveda approachable — from its ancient roots to Panchakarma.', 'Shatavari', array( 10 => 'Shatavari brand film', 1 => 'Ancient Ayurveda', 2 => 'What makes Panchakarma so powerful?' ), 1 ),
	array( 'carecast-pregnancy-podcast', 'CareCast — Pregnancy Health Podcast', 'Podcast episodes that answer real questions about early pregnancy and planning, in a warm, conversational format.', 'Malabar', array( 4 => 'Early pregnancy', 5 => 'Planning a pregnancy' ), 2 ),
	array( 'parpie-brand-films', 'Parpie — Brand Films', '“Simple is comfort.” Short films that capture the small, real moments of parenting.', 'Parpie', array( 8 => 'Simple is Comfort', 11 => 'Some things don’t need to be complicated', 7 => 'Made to keep little ones happy' ), 3 ),
	array( '123-cargo-short-film', '123 Cargo — Short Film', 'A story-led short film set on the streets and work sites of the city, built around the people who keep things moving.', '123 Cargo', array( 3 => '123 Cargo — short film' ), 4 ),
	array( 'habari-real-estate-explainer', 'Habari Real Estate — Animated Explainer', '“Your requirement, our responsibility.” An animated service film for a real estate firm that finds the right space for every need.', 'Habari', array( 9 => 'Habari Real Estate — service video' ), 5 ),
	array( 'amend-dental-film', 'Amend Dental Centre — Promo Film', 'A Malayalam promo film announcing aligners and smile correction at Amend Dental Centre, Kottakkal.', 'Amend', array( 14 => 'Amend Dental Centre' ), 6 ),
	array( 'first-driving-centre-film', 'First Driving Centre — “Patience”', '“Let’s wait together, because patience is…” A short film built on one everyday moment at a traffic light.', 'First Driving', array( 13 => 'Patience' ), 7 ),
	array( 'we-find-you-a-home-film', 'We Find You a Home — Presentation Film', 'A presenter-led film that walks viewers through how the right home is found.', '', array( 6 => 'We find you a home' ), 8 ),
	array( 'exclusive-membership-film', 'Join the Exclusive Membership — Promo Film', 'A short promo film inviting viewers to join an exclusive membership.', '', array( 12 => 'Join the exclusive membership' ), 9 ),
);

function cf_up( $path, $name, $title, $parent ) {
	$tmp = wp_tempnam( $name );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $parent, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $name . ': ' . $id->get_error_message() );
		return 0;
	}
	return (int) $id;
}

foreach ( $works as $w ) {
	list( $slug, $title, $excerpt, $brand, $films, $order ) = $w;
	if ( get_page_by_path( $slug, OBJECT, 'aswin_work' ) ) {
		WP_CLI::log( "Exists: $title" );
		continue;
	}
	$id = wp_insert_post( array( 'post_type' => 'aswin_work', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_excerpt' => $excerpt, 'menu_order' => $order ) );
	wp_set_object_terms( $id, (int) $cat->term_id, 'aswin_work_cat' );
	update_post_meta( $id, '_aswin_role', 'Concept, script & direction' );
	if ( $brand ) {
		$b = get_posts( array( 'post_type' => 'aswin_brand', 's' => $brand, 'posts_per_page' => 1 ) );
		if ( $b ) {
			update_post_meta( $id, '_aswin_brand', $b[0]->ID );
		}
	}
	$vids = array();
	foreach ( $films as $n => $ftitle ) {
		$nn  = str_pad( (string) $n, 2, '0', STR_PAD_LEFT );
		$vid = cf_up( "$dir/film-$nn.mp4", "$slug-$nn.mp4", $ftitle, $id );
		if ( ! $vid ) {
			continue;
		}
		$poster = cf_up( "$dir/poster-$nn.jpg", "$slug-$nn-poster.jpg", $ftitle . ' (poster)', $id );
		if ( $poster ) {
			update_post_meta( $vid, '_aswin_poster', $poster );
			if ( ! $vids ) {
				set_post_thumbnail( $id, $poster );
			}
		}
		$vids[] = $vid;
	}
	update_post_meta( $id, '_aswin_videos', implode( ',', $vids ) );
	WP_CLI::log( "Added: $title (" . count( $vids ) . ' films)' );
}
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Concept films imported.' );
