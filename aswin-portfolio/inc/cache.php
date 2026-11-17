<?php
/**
 * Keep Hostinger's CDN (hcdn) from serving stale homepage HTML.
 *
 * 1. HTML pages tell the CDN/browsers to revalidate instead of keeping a copy for days.
 * 2. Whenever content changes (Elementor save, Works/Brands, menus, theme), the CDN cache
 *    is purged once at the end of the request, using the Hostinger plugin's own purger.
 */

defined( 'ABSPATH' ) || exit;

/* 1. No long-lived caching of front-end HTML. Static files (CSS/JS/images) are unaffected. */
add_action( 'send_headers', function () {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || headers_sent() ) {
		return;
	}
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0, s-maxage=0' );
	header( 'CDN-Cache-Control: no-store' );
} );

/* 2. Purge the Hostinger CDN after content changes. */
function aswin_request_cdn_purge() {
	static $queued = false;
	if ( $queued ) {
		return;
	}
	$queued = true;
	add_action( 'shutdown', 'aswin_purge_cdn_now' );
}

function aswin_purge_cdn_now() {
	$needed = array( 'Hostinger\Cdn\CachePurger', 'Hostinger\Cdn\CacheClient', 'Hostinger\Cdn\SoftwareIdResolver', 'Hostinger\WpHelper\Requests\Client', 'Hostinger\WpHelper\Utils', 'Hostinger\WpHelper\Config' );
	foreach ( $needed as $class ) {
		if ( ! class_exists( $class ) ) {
			return; // Hostinger plugin not available — nothing to do.
		}
	}
	if ( ! defined( 'HOSTINGER_PROXY_API_HOST' ) ) {
		return;
	}
	if ( empty( $_SERVER['H_PLATFORM'] ) ) {
		$_SERVER['H_PLATFORM'] = 'Hostinger';
	}
	try {
		$utils  = new \Hostinger\WpHelper\Utils();
		$client = new \Hostinger\WpHelper\Requests\Client(
			'https://' . HOSTINGER_PROXY_API_HOST,
			array(
				\Hostinger\WpHelper\Config::TOKEN_HEADER  => $utils->getApiToken(),
				\Hostinger\WpHelper\Config::DOMAIN_HEADER => $utils->getHostInfo(),
			)
		);
		$purger = new \Hostinger\Cdn\CachePurger( new \Hostinger\Cdn\CacheClient( $client ), new \Hostinger\Cdn\SoftwareIdResolver( $client ), $utils );
		$purger->purge_all();
	} catch ( \Throwable $e ) {
		error_log( 'Aswin theme: CDN purge failed — ' . $e->getMessage() ); // phpcs:ignore
	}
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) {
		return;
	}
	if ( in_array( $post->post_type, array( 'page', 'post', 'aswin_work', 'aswin_brand', 'elementor_library', 'nav_menu_item' ), true ) ) {
		aswin_request_cdn_purge();
	}
}, 99, 2 );
add_action( 'deleted_post', 'aswin_request_cdn_purge' );
add_action( 'elementor/editor/after_save', 'aswin_request_cdn_purge' );
add_action( 'elementor/core/files/clear_cache', 'aswin_request_cdn_purge' );
add_action( 'wp_update_nav_menu', 'aswin_request_cdn_purge' );
add_action( 'switch_theme', 'aswin_request_cdn_purge' );
add_action( 'customize_save_after', 'aswin_request_cdn_purge' );
add_action( 'created_aswin_work_cat', 'aswin_request_cdn_purge' );
add_action( 'edited_aswin_work_cat', 'aswin_request_cdn_purge' );
add_action( 'delete_aswin_work_cat', 'aswin_request_cdn_purge' );
add_action( 'update_option_show_on_front', 'aswin_request_cdn_purge' );
add_action( 'update_option_page_on_front', 'aswin_request_cdn_purge' );
add_action( 'upgrader_process_complete', 'aswin_request_cdn_purge' );
