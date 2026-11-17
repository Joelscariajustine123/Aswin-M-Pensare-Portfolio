<?php
/**
 * Creates one editable Elementor page per portfolio section and links them up.
 * Run: wp eval-file create-section-pages.php --user=admin   (then: wp rewrite flush)
 * Safe to re-run: existing pages (matched by slug) are reused, not duplicated; their content is NOT overwritten.
 */

if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'aswin_work_cats' ) ) {
	WP_CLI::error( 'Elementor and the Aswin Portfolio theme must be active.' );
}
function p_id() {
	return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
}
function p_w( $type, $settings ) {
	return array( 'id' => p_id(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}
function p_c( $classes, $children, $extra = array() ) {
	return array( 'id' => p_id(), 'elType' => 'container', 'isInner' => false, 'settings' => array_merge( array( 'content_width' => 'boxed', 'css_classes' => $classes, 'flex_direction' => 'column' ), $extra ), 'elements' => $children );
}

/* 1. A distinct design per category. */
$designs = array( 'technical-writing' => 'list', 'social-media' => 'phone', 'campaigns-pr' => 'feature', 'newspaper-ad' => 'newsprint', 'branding' => 'case' );
foreach ( $designs as $slug => $d ) {
	$t = get_term_by( 'slug', $slug, 'aswin_work_cat' );
	if ( $t ) {
		update_term_meta( $t->term_id, 'aswin_style', $d );
	}
}

/* Contact block copied from the homepage so it reads the same everywhere. */
$home_data = json_decode( get_post_meta( (int) get_option( 'page_on_front' ), '_elementor_data', true ), true );
$contact   = array( 'chapter_num' => '', 'chapter_label' => 'The last word', 'title' => 'Got a story? *Let’s tell it.*', 'text' => 'Every idea has a story worth telling. If you’ve got one that needs the right words, I’d love to hear it.', 'buttons' => array() );
foreach ( (array) $home_data as $c ) {
	foreach ( (array) ( $c['elements'] ?? array() ) as $el ) {
		if ( ( $el['widgetType'] ?? '' ) === 'aswin-contact' ) {
			$contact                = $el['settings'];
			$contact['chapter_num'] = '';
		}
	}
}

$titles = array(
	'technical-writing' => 'Technical Copywriting / *Content Writing*',
	'social-media'      => 'Creative Copywriting — *Social Media*',
	'campaigns-pr'      => 'Campaigns / *PR Events*',
	'newspaper-ad'      => 'Newspaper *Ads*',
	'branding'          => 'Branding',
);
$page_titles = array(
	'technical-writing' => 'Technical Writing',
	'social-media'      => 'Social Media',
	'campaigns-pr'      => 'Campaigns & PR',
	'newspaper-ad'      => 'Newspaper Ads',
	'branding'          => 'Branding',
);

function aswin_make_page( $slug, $title, $data ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $existing ) {
		WP_CLI::log( "Page /$slug/ already exists (ID {$existing->ID}) — kept as is." );
		return $existing->ID;
	}
	$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ) );
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	$doc = \Elementor\Plugin::$instance->documents->get( $id, false );
	$doc->set_is_built_with_elementor( true );
	$doc->save( array( 'elements' => $data ) );
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	WP_CLI::log( "Created /$slug/ (ID $id)." );
	return $id;
}

$links   = p_c( 'section section-links-sec', array( p_w( 'aswin-section-links', array( 'label' => 'Explore other sections', 'home' => 'yes', 'all' => 'yes' ) ) ), array( 'background_background' => 'classic', 'background_color' => '#E9E2D5' ) );
$contact_c = function () use ( $contact ) {
	return p_c( 'section contact', array( p_w( 'aswin-contact', $contact ) ), array( 'background_background' => 'classic', 'background_color' => '#FF4F1F' ) );
};

/* 2. Brands page. */
$brands_id = aswin_make_page( 'brands', 'Brands', array(
	p_c( 'section page-head', array( p_w( 'aswin-section-heading', array( 'chapter_num' => '03', 'chapter_label' => 'Trusted by', 'title' => 'Brands', 'kicker' => 'Names I’ve had the pleasure of putting words to.', 'tag' => 'h1' ) ) ) ),
	p_c( 'section page-body', array( p_w( 'aswin-brands', array( 'limit' => -1, 'columns' => '4', 'link' => 'page', 'names' => 'yes', 'grayscale' => 'yes', 'view_more' => 'hide' ) ) ) ),
	$links,
	$contact_c(),
) );
update_option( 'aswin_brands_page', $brands_id );

/* 3. One page per work category. */
foreach ( aswin_work_cats() as $i => $t ) {
	$num = str_pad( (string) ( 4 + $i ), 2, '0', STR_PAD_LEFT );
	$id  = aswin_make_page( $t->slug, $page_titles[ $t->slug ] ?? $t->name, array(
		p_c( 'section page-head', array( p_w( 'aswin-section-heading', array( 'chapter_num' => $num, 'chapter_label' => 'Selected work', 'title' => $titles[ $t->slug ] ?? $t->name, 'kicker' => $t->description, 'tag' => 'h1' ) ) ) ),
		p_c( 'section page-body', array( p_w( 'aswin-work-showcase', array( 'cats' => array( (string) $t->term_id ), 'limit' => -1, 'columns' => '3', 'design' => 'auto', 'mode' => 'grid', 'view_more' => 'hide', 'ig_ui' => 'yes', 'ig_handle' => 'brandname' ) ) ) ),
		$links,
		$contact_c(),
	) );
	update_term_meta( $t->term_id, 'aswin_page', $id );
}

/* 4. Header menu: Brands → Brands page. */
foreach ( (array) wp_get_nav_menu_items( 'Header' ) as $mi ) {
	if ( 'Brands' === $mi->title ) {
		update_post_meta( $mi->ID, '_menu_item_url', get_permalink( $brands_id ) );
	}
}

delete_option( 'aswin_rewrite_ver' );
\Elementor\Plugin::$instance->files_manager->clear_cache();
WP_CLI::success( 'Section pages ready. Now run: wp rewrite flush' );
