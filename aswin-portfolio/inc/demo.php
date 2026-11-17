<?php
/**
 * Demo content: notice + one-click removal.
 * Demo posts and images carry the meta key _aswin_demo = 1.
 */

defined( 'ABSPATH' ) || exit;

function aswin_demo_ids( $post_type ) {
	return get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_aswin_demo', // phpcs:ignore
		'meta_value'     => '1', // phpcs:ignore
	) );
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	$show   = $screen && ( in_array( $screen->post_type, array( 'aswin_work', 'aswin_brand' ), true ) || in_array( $screen->id, array( 'dashboard', 'toplevel_page_aswin-contact', 'edit-page' ), true ) );
	if ( isset( $_GET['aswin_demo_removed'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p><strong>Demo content removed.</strong> Your own works, brands and pages were not touched.</p></div>';
		return;
	}
	if ( ! $show ) {
		return;
	}
	$works  = count( aswin_demo_ids( 'aswin_work' ) );
	$brands = count( aswin_demo_ids( 'aswin_brand' ) );
	if ( ! $works && ! $brands ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=aswin_remove_demo' ), 'aswin_remove_demo' );
	printf(
		'<div class="notice notice-info"><p><strong>Demo content is installed</strong> — %d works, %d brands%s, all marked “Demo”. Replace them with your own whenever you’re ready, then remove the rest in one click. <a class="button" href="%s" onclick="return confirm(\'Delete all demo works, brands, their images%s? Your own content stays.\')">Remove demo content</a></p></div>',
		(int) $works,
		(int) $brands,
		get_option( 'aswin_contact_is_demo' ) ? ' and sample contact details' : '',
		esc_url( $url ),
		get_option( 'aswin_contact_is_demo' ) ? ' and the sample contact details' : ''
	);
} );

add_action( 'admin_post_aswin_remove_demo', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'aswin_remove_demo' ) ) {
		wp_die( 'Not allowed.' );
	}
	foreach ( array( 'aswin_work', 'aswin_brand', 'attachment' ) as $pt ) {
		foreach ( aswin_demo_ids( $pt ) as $id ) {
			'attachment' === $pt ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
		}
	}
	if ( get_option( 'aswin_contact_is_demo' ) ) {
		delete_option( 'aswin_contact' );
		delete_option( 'aswin_contact_is_demo' );
	}
	delete_option( 'aswin_demo_installed' );
	if ( function_exists( 'aswin_request_cdn_purge' ) ) {
		aswin_request_cdn_purge();
	}
	wp_safe_redirect( add_query_arg( 'aswin_demo_removed', 1, wp_get_referer() ? wp_get_referer() : admin_url() ) );
	exit;
} );

/** Once you edit the Contact Details yourself, they stop being "demo". */
add_action( 'update_option_aswin_contact', function () {
	if ( did_action( 'load-options.php' ) ) {
		delete_option( 'aswin_contact_is_demo' );
	}
} );

/** "Demo" badge in admin lists. */
add_filter( 'display_post_states', function ( $states, $post ) {
	if ( get_post_meta( $post->ID, '_aswin_demo', true ) ) {
		$states['aswin_demo'] = 'Demo';
	}
	return $states;
}, 10, 2 );
