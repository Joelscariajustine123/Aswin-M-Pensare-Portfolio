<?php
/**
 * Replace the "Newspaper Ad" section with "Concept Development, Scripting & Direction".
 * Same term (renamed), same section page (renamed), homepage heading updated.
 * Newspaper works are set to draft (not deleted).
 * Run: wp eval-file replace-newspaper-section.php --user=admin
 */
$t = get_term_by( 'slug', 'newspaper-ad', 'aswin_work_cat' );
if ( ! $t ) {
	WP_CLI::error( 'newspaper-ad category not found (already replaced?).' );
}
$name  = 'Concept Development, Scripting & Direction';
$slug  = 'concept-scripting-direction';
$title = 'Concept Development, *Scripting & Direction*';
$desc  = 'From the first spark of an idea to the script and the shoot.';

// 1. Newspaper works → draft.
$works = get_posts( array( 'post_type' => 'aswin_work', 'post_status' => 'publish', 'posts_per_page' => -1, 'tax_query' => array( array( 'taxonomy' => 'aswin_work_cat', 'field' => 'term_id', 'terms' => $t->term_id ) ) ) );
foreach ( $works as $w ) {
	wp_update_post( array( 'ID' => $w->ID, 'post_status' => 'draft' ) );
	WP_CLI::log( 'Drafted: ' . $w->post_title );
}

// 2. Rename the category.
wp_update_term( $t->term_id, 'aswin_work_cat', array( 'name' => $name, 'slug' => $slug, 'description' => $desc ) );
update_term_meta( $t->term_id, 'aswin_style', 'showcase' );

// 3. Rename the section page.
$page = (int) get_term_meta( $t->term_id, 'aswin_page', true );
if ( $page ) {
	wp_update_post( array( 'ID' => $page, 'post_title' => 'Concept, Scripting & Direction', 'post_name' => $slug ) );
	$d = json_decode( get_post_meta( $page, '_elementor_data', true ), true );
	$walk = function ( &$els ) use ( &$walk, $title, $desc ) {
		foreach ( $els as &$e ) {
			if ( ( $e['widgetType'] ?? '' ) === 'aswin-section-heading' && isset( $e['settings']['title'] ) && false !== stripos( $e['settings']['title'], 'newspaper' ) ) {
				$e['settings']['title']  = $title;
				$e['settings']['kicker'] = $desc;
			}
			if ( ! empty( $e['elements'] ) ) {
				$walk( $e['elements'] );
			}
		}
	};
	$walk( $d );
	\Elementor\Plugin::$instance->documents->get( $page, false )->save( array( 'elements' => $d ) );
}

// 4. Homepage section heading + anchor.
$home = (int) get_option( 'page_on_front' );
$d    = json_decode( get_post_meta( $home, '_elementor_data', true ), true );
foreach ( $d as &$c ) {
	if ( ( $c['settings']['_element_id'] ?? '' ) !== 'newspaper-ad' ) {
		continue;
	}
	$c['settings']['_element_id'] = $slug;
	foreach ( $c['elements'] as &$e ) {
		if ( ( $e['widgetType'] ?? '' ) === 'aswin-section-heading' ) {
			$e['settings']['title']  = $title;
			$e['settings']['kicker'] = $desc;
		}
	}
	unset( $e );
}
unset( $c );
\Elementor\Plugin::$instance->documents->get( $home, false )->save( array( 'elements' => $d ) );

flush_rewrite_rules( false );
\Elementor\Plugin::$instance->files_manager->clear_cache();
if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( 'Section replaced. Page: ' . ( $page ? get_permalink( $page ) : '-' ) );
