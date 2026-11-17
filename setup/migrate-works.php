<?php
/**
 * Migration: per-section Elementor galleries → admin-managed Works / Brands.
 * Run on the server with:  wp eval-file migrate-works.php --user=admin
 * Keeps every other section of the homepage exactly as it is.
 */

if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'aswin_seed_work_cats' ) ) {
	WP_CLI::error( 'Elementor and the Aswin Portfolio theme must be active.' );
}

function m_id() {
	return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
}

aswin_seed_work_cats();

$page_id = (int) get_option( 'page_on_front' );
$data    = json_decode( get_post_meta( $page_id, '_elementor_data', true ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'Could not read homepage Elementor data.' );
}

$work_sections = array( 'technical-writing', 'social-media', 'campaigns-pr', 'newspaper-ad', 'branding' );
$created_w     = 0;
$created_b     = 0;
$new           = array();

foreach ( $data as $c ) {
	$sid = $c['settings']['_element_id'] ?? '';

	// Pull items out of old gallery widgets.
	foreach ( $c['elements'] as $el ) {
		if ( ( $el['widgetType'] ?? '' ) !== 'aswin-gallery' ) {
			continue;
		}
		foreach ( (array) ( $el['settings']['items'] ?? array() ) as $i => $it ) {
			$media_id = (int) ( $it['media']['id'] ?? 0 );
			if ( 'brands' === $sid ) {
				$pid = wp_insert_post( array( 'post_type' => 'aswin_brand', 'post_status' => 'publish', 'post_title' => $it['title'] ?? 'Brand', 'menu_order' => $i + 1 ) );
				if ( $media_id ) {
					set_post_thumbnail( $pid, $media_id );
				}
				if ( ! empty( $it['link']['url'] ) ) {
					update_post_meta( $pid, '_aswin_link', esc_url_raw( $it['link']['url'] ) );
				}
				$created_b++;
			} elseif ( in_array( $sid, $work_sections, true ) ) {
				$pid = wp_insert_post( array(
					'post_type'    => 'aswin_work',
					'post_status'  => 'publish',
					'post_title'   => $it['title'] ?? 'Work',
					'post_excerpt' => $it['caption'] ?? '',
					'menu_order'   => $i + 1,
				) );
				wp_set_object_terms( $pid, $sid, 'aswin_work_cat' );
				if ( $media_id ) {
					$mime = (string) get_post_mime_type( $media_id );
					if ( 0 === strpos( $mime, 'video' ) ) {
						update_post_meta( $pid, '_aswin_video', $media_id );
					} else {
						set_post_thumbnail( $pid, $media_id );
					}
				}
				$map = array( 'ig_handle' => '_aswin_ig_handle', 'ig_caption' => '_aswin_ig_caption', 'ig_likes' => '_aswin_ig_likes', 'ig_date' => '_aswin_ig_date' );
				foreach ( $map as $k => $meta ) {
					if ( ! empty( $it[ $k ] ) ) {
						update_post_meta( $pid, $meta, $it[ $k ] );
					}
				}
				if ( ! empty( $it['link']['url'] ) ) {
					update_post_meta( $pid, '_aswin_link', esc_url_raw( $it['link']['url'] ) );
					if ( ! empty( $it['link']['is_external'] ) ) {
						update_post_meta( $pid, '_aswin_link_new', '1' );
					}
				}
				if ( ! empty( $it['file']['id'] ) ) {
					update_post_meta( $pid, '_aswin_pdf', (int) $it['file']['id'] );
				}
				$created_w++;
			}
		}
	}

	if ( 'brands' === $sid ) {
		foreach ( $c['elements'] as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'aswin-gallery' ) {
				$el = array( 'id' => m_id(), 'elType' => 'widget', 'widgetType' => 'aswin-brands', 'settings' => array( 'columns' => 'auto', 'grayscale' => 'yes' ), 'elements' => array() );
			}
		}
		unset( $el );
		$new[] = $c;

		// One filterable Works section right after Brands.
		$new[] = array(
			'id'       => m_id(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => array(
				'content_width'         => 'boxed',
				'css_classes'           => 'section work-section',
				'_element_id'           => 'work',
				'flex_direction'        => 'column',
				'background_background' => 'classic',
				'background_color'      => '#F3EEE5',
			),
			'elements' => array(
				array( 'id' => m_id(), 'elType' => 'widget', 'widgetType' => 'aswin-section-heading', 'elements' => array(), 'settings' => array(
					'chapter_num'   => '04',
					'chapter_label' => 'Selected work',
					'title'         => 'Words that *worked.*',
					'kicker'        => 'Pick a shelf — or browse the whole lot.',
				) ),
				array( 'id' => m_id(), 'elType' => 'widget', 'widgetType' => 'aswin-work-showcase', 'elements' => array(), 'settings' => array(
					'show_filter' => 'yes',
					'show_all'    => 'yes',
					'all_label'   => 'All work',
					'default_tab' => '*',
					'counts'      => 'yes',
					'hide_empty'  => 'yes',
					'columns'     => '3',
					'ig_ui'       => 'yes',
					'ig_handle'   => 'brandname',
					'chips'       => 'yes',
				) ),
			),
		);
		continue;
	}

	if ( in_array( $sid, $work_sections, true ) ) {
		continue; // replaced by the single Works section
	}

	if ( 'contact' === $sid ) {
		foreach ( $c['elements'] as &$el ) {
			if ( ( $el['widgetType'] ?? '' ) === 'aswin-contact' && ( $el['settings']['chapter_num'] ?? '' ) === '09' ) {
				$el['settings']['chapter_num'] = '05';
			}
		}
		unset( $el );
	}
	$new[] = $c;
}

$doc = \Elementor\Plugin::$instance->documents->get( $page_id, false );
$doc->save( array( 'elements' => $new ) );

// Header menu: "Work" → #work
foreach ( (array) wp_get_nav_menu_items( 'Header' ) as $mi ) {
	if ( false !== strpos( $mi->url, '#technical-writing' ) ) {
		update_post_meta( $mi->ID, '_menu_item_url', home_url( '/#work' ) );
	}
}

\Elementor\Plugin::$instance->files_manager->clear_cache();
WP_CLI::success( sprintf( 'Created %d works and %d brands. Homepage now has %d sections.', $created_w, $created_b, count( $new ) ) );
