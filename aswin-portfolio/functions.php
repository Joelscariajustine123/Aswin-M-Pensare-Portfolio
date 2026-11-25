<?php
/**
 * Aswin Portfolio theme functions.
 * Page content is built with Elementor using the custom widgets in inc/elementor-widgets.php.
 */

defined( 'ABSPATH' ) || exit;

define( 'ASWIN_VER', '3.6.5' );

require_once __DIR__ . '/inc/content-types.php';
require_once __DIR__ . '/inc/render.php';
require_once __DIR__ . '/inc/contact-details.php';
require_once __DIR__ . '/inc/demo.php';
require_once __DIR__ . '/inc/cache.php';

/** Elementor's lightbox on work/brand pages (they're theme templates, not Elementor pages). */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}
	if ( is_singular( array( 'aswin_work', 'aswin_brand' ) ) || is_post_type_archive( array( 'aswin_work', 'aswin_brand' ) ) || is_tax( 'aswin_work_cat' ) ) {
		\Elementor\Plugin::$instance->frontend->enqueue_styles();
		\Elementor\Plugin::$instance->frontend->enqueue_scripts();
	}
}, 30 );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_image_size( 'aswin-card', 900, 675, true );
	add_image_size( 'aswin-wide', 1400, 875, true );
	add_image_size( 'aswin-free', 900, 0, false );
	register_nav_menus( array( 'primary' => 'Header menu' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'aswin-fonts', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Serif:ital@0;1&display=swap', array(), null );
	wp_enqueue_style( 'aswin-style', get_stylesheet_uri(), array( 'aswin-fonts' ), ASWIN_VER );
	wp_enqueue_script( 'aswin-main', get_template_directory_uri() . '/assets/main.js', array(), ASWIN_VER, array( 'strategy' => 'defer', 'in_footer' => true ) );
}, 20 );

add_filter( 'wp_resource_hints', function ( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/* ---------- Elementor ---------- */
add_action( 'elementor/elements/categories_registered', function ( $manager ) {
	$manager->add_category( 'aswin', array( 'title' => 'Aswin Portfolio', 'icon' => 'eicon-star' ) );
} );

add_action( 'elementor/widgets/register', function ( $widgets ) {
	require_once get_template_directory() . '/inc/elementor-widgets.php';
	require_once get_template_directory() . '/inc/elementor-widgets-admin.php';
	require_once get_template_directory() . '/inc/elementor-widgets-media.php';
	foreach ( array( 'Hero', 'Marquee', 'Big_Heading', 'Photo_Collage', 'Quote_Card', 'Statement', 'Section_Heading', 'Skill_Card', 'Work_Showcase', 'Brands', 'Section_Links', 'Gallery', 'Contact', 'Contact_Details' ) as $w ) {
		$class = 'Aswin_' . $w . '_Widget';
		$widgets->register( new $class() );
	}
} );

/** Let Elementor's lightbox and editor preview use our fonts. */
add_action( 'elementor/editor/after_enqueue_styles', function () {
	wp_enqueue_style( 'aswin-fonts', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,800&family=Instrument+Serif:ital@0;1&display=swap', array(), null );
} );

/** Admin notice if Elementor is missing. */
add_action( 'admin_notices', function () {
	if ( ! did_action( 'elementor/loaded' ) && current_user_can( 'install_plugins' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Aswin Portfolio:</strong> install and activate <em>Elementor</em> to edit the homepage.</p></div>';
	}
} );

/* ---------- Header menu fallback ---------- */
function aswin_menu_fallback() {
	$home  = home_url( '/' );
	$links = array( 'about' => 'About', 'toolkit' => 'Skills', 'brands' => 'Brands', 'work' => 'Work' );
	foreach ( $links as $id => $label ) {
		printf( '<a href="%s#%s">%s</a>', esc_url( $home ), esc_attr( $id ), esc_html( $label ) );
	}
}

/** Give menu links the nav styling; the last item becomes the pill button. */
add_filter( 'nav_menu_link_attributes', function ( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location && in_array( 'btn-pill', (array) $item->classes, true ) ) {
		$atts['class'] = 'btn-pill';
	}
	return $atts;
}, 10, 3 );
