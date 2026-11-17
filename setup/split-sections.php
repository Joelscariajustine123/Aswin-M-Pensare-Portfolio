<?php
/**
 * Homepage: one section per category (3 items + View more), Brands limited to 3.
 * Run: wp eval-file split-sections.php --user=admin
 * Leaves hero, about, toolkit and contact untouched (contact chapter renumbered to 09).
 */

if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'aswin_work_cats' ) ) {
	WP_CLI::error( 'Elementor and the Aswin Portfolio theme must be active.' );
}
function s_id() {
	return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
}

// Category descriptions (used as kicker on homepage + category pages) — only if empty.
$kickers = array(
	'technical-writing' => 'Making the complicated sound simple — and readable.',
	'social-media'      => 'Thumb-stoppers, one scroll at a time.',
	'campaigns-pr'      => 'Ideas that stepped off the screen and into the room.',
	'newspaper-ad'      => 'Print that people actually stop to read.',
	'branding'          => 'Voices, names and stories that brands grow into.',
);
$titles = array(
	'technical-writing' => 'Technical Copywriting / *Content Writing*',
	'social-media'      => 'Creative Copywriting — *Social Media*',
	'campaigns-pr'      => 'Campaigns / *PR Events*',
	'newspaper-ad'      => 'Newspaper *Ad*',
	'branding'          => 'Branding',
);
foreach ( $kickers as $slug => $k ) {
	$t = get_term_by( 'slug', $slug, 'aswin_work_cat' );
	if ( $t && '' === trim( $t->description ) ) {
		wp_update_term( $t->term_id, 'aswin_work_cat', array( 'description' => $k ) );
	}
}

$page_id = (int) get_option( 'page_on_front' );
$data    = json_decode( get_post_meta( $page_id, '_elementor_data', true ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'Could not read homepage data.' );
}

$new = array();
$bg  = array( '#F3EEE5', '#E9E2D5' );
foreach ( $data as $c ) {
	$sid = $c['settings']['_element_id'] ?? '';

	if ( 'brands' === $sid ) {
		foreach ( $c['elements'] as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'aswin-brands' ) {
				$el['settings'] = array_merge( $el['settings'], array( 'limit' => 3, 'columns' => '3', 'link' => 'page', 'names' => 'yes', 'grayscale' => 'yes', 'view_more' => 'always', 'view_more_label' => 'View all brands' ) );
			}
		}
		unset( $el );
		$new[] = $c;
		continue;
	}

	if ( 'work' === $sid ) {
		$n = 4;
		foreach ( aswin_work_cats() as $i => $t ) {
			$title  = $titles[ $t->slug ] ?? $t->name;
			$kicker = $t->description ? $t->description : ( $kickers[ $t->slug ] ?? '' );
			$new[]  = array(
				'id'       => s_id(),
				'elType'   => 'container',
				'isInner'  => false,
				'settings' => array(
					'content_width'         => 'boxed',
					'css_classes'           => 'section work-section',
					'_element_id'           => $t->slug,
					'flex_direction'        => 'column',
					'background_background' => 'classic',
					'background_color'      => $bg[ $i % 2 ],
				),
				'elements' => array(
					array( 'id' => s_id(), 'elType' => 'widget', 'widgetType' => 'aswin-section-heading', 'elements' => array(), 'settings' => array(
						'chapter_num'   => str_pad( (string) $n, 2, '0', STR_PAD_LEFT ),
						'chapter_label' => 'Selected work',
						'title'         => $title,
						'kicker'        => $kicker,
					) ),
					array( 'id' => s_id(), 'elType' => 'widget', 'widgetType' => 'aswin-work-showcase', 'elements' => array(), 'settings' => array(
						'cats'            => array( (string) $t->term_id ),
						'limit'           => 3,
						'columns'         => '3',
						'mode'            => 'grid',
						'view_more'       => 'always',
						'view_more_label' => 'View more',
						'show_filter'     => '',
						'ig_ui'           => 'yes',
						'ig_handle'       => 'brandname',
						'chips'           => '',
					) ),
				),
			);
			$n++;
		}
		continue;
	}

	if ( 'contact' === $sid ) {
		foreach ( $c['elements'] as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'aswin-contact' ) {
				$el['settings']['chapter_num'] = '09';
			}
		}
		unset( $el );
	}
	$new[] = $c;
}

\Elementor\Plugin::$instance->documents->get( $page_id, false )->save( array( 'elements' => $new ) );

// Header menu: "Work" → All Work page.
foreach ( (array) wp_get_nav_menu_items( 'Header' ) as $mi ) {
	if ( 'Work' === $mi->title ) {
		update_post_meta( $mi->ID, '_menu_item_url', home_url( '/work/' ) );
	}
}

flush_rewrite_rules( false );
\Elementor\Plugin::$instance->files_manager->clear_cache();
WP_CLI::success( 'Homepage now has ' . count( $new ) . ' sections.' );
