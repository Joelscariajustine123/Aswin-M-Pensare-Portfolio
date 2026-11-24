<?php
/**
 * Works + Brands post types, Work Categories, admin fields.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Registration ---------- */
add_action( 'init', function () {
	register_post_type( 'aswin_work', array(
		'labels'             => array(
			'name'                  => 'Works',
			'singular_name'         => 'Work',
			'add_new'               => 'Add Work',
			'add_new_item'          => 'Add New Work',
			'edit_item'             => 'Edit Work',
			'all_items'             => 'All Works',
			'featured_image'        => 'Cover image',
			'set_featured_image'    => 'Set cover image',
			'remove_featured_image' => 'Remove cover image',
			'use_featured_image'    => 'Use as cover image',
		),
		'public'             => true,
		'show_in_rest'       => true,
		'has_archive'        => 'work',
		'rewrite'            => array( 'slug' => 'work', 'with_front' => false ),
		'menu_icon'          => 'dashicons-portfolio',
		'menu_position'      => 5,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
	) );

	register_taxonomy( 'aswin_work_cat', 'aswin_work', array(
		'labels'            => array(
			'name'          => 'Work Categories',
			'singular_name' => 'Work Category',
			'menu_name'     => 'Categories',
			'add_new_item'  => 'Add New Category',
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'work-category', 'with_front' => false ),
	) );

	register_post_type( 'aswin_brand', array(
		'labels'        => array(
			'name'                  => 'Brands',
			'singular_name'         => 'Brand',
			'add_new'               => 'Add Brand',
			'add_new_item'          => 'Add New Brand',
			'edit_item'             => 'Edit Brand',
			'all_items'             => 'All Brands',
			'featured_image'        => 'Logo',
			'set_featured_image'    => 'Set logo',
			'remove_featured_image' => 'Remove logo',
			'use_featured_image'    => 'Use as logo',
		),
		'public'        => true,
		'show_in_rest'  => true,
		// When an editable "Brands" page exists it owns /brands/, so the automatic archive is switched off.
		'has_archive'   => get_option( 'aswin_brands_page' ) ? false : 'brands',
		'rewrite'       => array( 'slug' => 'brands', 'with_front' => false ),
		'menu_icon'     => 'dashicons-awards',
		'menu_position' => 6,
		// Order is set with the "Priority" field in the Brand details box (stored in menu_order).
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	) );
} );

/** Show every work/brand on archive pages, in admin order. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( array( 'aswin_work', 'aswin_brand' ) ) || $q->is_tax( 'aswin_work_cat' ) ) {
		$q->set( 'posts_per_page', -1 );
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
} );

/** Flush permalinks once after the theme's URL structure changes. */
add_action( 'init', function () {
	if ( get_option( 'aswin_rewrite_ver' ) !== '4' ) {
		flush_rewrite_rules( false );
		update_option( 'aswin_rewrite_ver', '4' );
	}
}, 99 );
add_action( 'update_option_aswin_brands_page', function () {
	delete_option( 'aswin_rewrite_ver' );
} );

/** Old category URLs → the editable section page, when one is linked. */
add_action( 'template_redirect', function () {
	if ( is_tax( 'aswin_work_cat' ) ) {
		$page = (int) get_term_meta( get_queried_object_id(), 'aswin_page', true );
		if ( $page && 'publish' === get_post_status( $page ) ) {
			wp_safe_redirect( get_permalink( $page ), 301 );
			exit;
		}
	}
} );

/** Default work categories: slug => [name, style]. */
function aswin_default_work_cats() {
	return array(
		'technical-writing' => array( 'Technical Copywriting / Content Writing', 'card' ),
		'social-media'      => array( 'Creative Copywriting — Social Media', 'phone' ),
		'campaigns-pr'      => array( 'Campaigns / PR Events', 'card' ),
		'newspaper-ad'      => array( 'Newspaper Ad', 'card' ),
		'branding'          => array( 'Branding', 'card' ),
	);
}

function aswin_seed_work_cats() {
	$i = 1;
	foreach ( aswin_default_work_cats() as $slug => $c ) {
		$t = term_exists( $slug, 'aswin_work_cat' );
		if ( ! $t ) {
			$t = wp_insert_term( $c[0], 'aswin_work_cat', array( 'slug' => $slug ) );
		}
		if ( ! is_wp_error( $t ) ) {
			$id = (int) ( is_array( $t ) ? $t['term_id'] : $t );
			if ( ! get_term_meta( $id, 'aswin_style', true ) ) {
				update_term_meta( $id, 'aswin_style', $c[1] );
			}
			if ( '' === get_term_meta( $id, 'aswin_order', true ) ) {
				update_term_meta( $id, 'aswin_order', $i );
			}
		}
		$i++;
	}
}
add_action( 'after_switch_theme', 'aswin_seed_work_cats' );

/** Work categories sorted by their Order field. */
function aswin_work_cats( $only_ids = array() ) {
	$args = array( 'taxonomy' => 'aswin_work_cat', 'hide_empty' => false );
	if ( $only_ids ) {
		$args['include'] = array_map( 'intval', $only_ids );
	}
	$terms = get_terms( $args );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort( $terms, function ( $a, $b ) {
		return (int) get_term_meta( $a->term_id, 'aswin_order', true ) <=> (int) get_term_meta( $b->term_id, 'aswin_order', true );
	} );
	return $terms;
}

/* ---------- Category fields: display style + order ---------- */
function aswin_cat_fields( $term = null ) {
	$style = $term ? get_term_meta( $term->term_id, 'aswin_style', true ) : 'card';
	$order = $term ? get_term_meta( $term->term_id, 'aswin_order', true ) : '';
	$page  = $term ? (int) get_term_meta( $term->term_id, 'aswin_page', true ) : 0;
	$sel   = '<select name="aswin_style" id="aswin_style">';
	foreach ( aswin_designs() as $k => $v ) {
		$sel .= '<option value="' . esc_attr( $k ) . '"' . selected( $style, $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	$sel .= '</select>';
	$ord  = '<input type="number" name="aswin_order" id="aswin_order" value="' . esc_attr( $order ) . '" style="width:90px">';
	$pg   = wp_dropdown_pages( array( 'name' => 'aswin_page', 'id' => 'aswin_page', 'selected' => $page, 'show_option_none' => '— None (use automatic category page) —', 'option_none_value' => '0', 'echo' => 0 ) );
	$rows = array(
		array( 'aswin_style', 'Display style', $sel, 'The design used for this section on the homepage and its page.' ),
		array( 'aswin_order', 'Order', $ord, 'Lower numbers show first (homepage sections, tabs, links).' ),
		array( 'aswin_page', 'Section page', $pg, '“View more” opens this page. Build it in Elementor with the “Works (from admin)” widget.' ),
	);
	foreach ( $rows as $r ) {
		if ( $term ) {
			echo '<tr class="form-field"><th><label for="' . esc_attr( $r[0] ) . '">' . esc_html( $r[1] ) . '</label></th><td>' . $r[2] . '<p class="description">' . esc_html( $r[3] ) . '</p></td></tr>'; // phpcs:ignore
		} else {
			echo '<div class="form-field"><label for="' . esc_attr( $r[0] ) . '">' . esc_html( $r[1] ) . '</label>' . $r[2] . '<p>' . esc_html( $r[3] ) . '</p></div>'; // phpcs:ignore
		}
	}
}
add_action( 'aswin_work_cat_add_form_fields', function () { aswin_cat_fields(); } );
add_action( 'aswin_work_cat_edit_form_fields', function ( $term ) { aswin_cat_fields( $term ); } );
$aswin_save_cat = function ( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['aswin_style'] ) ) {
		$style = sanitize_key( wp_unslash( $_POST['aswin_style'] ) );
		update_term_meta( $term_id, 'aswin_style', array_key_exists( $style, aswin_designs() ) ? $style : 'card' );
	}
	if ( isset( $_POST['aswin_order'] ) ) {
		update_term_meta( $term_id, 'aswin_order', (int) $_POST['aswin_order'] );
	}
	if ( isset( $_POST['aswin_page'] ) ) {
		update_term_meta( $term_id, 'aswin_page', absint( $_POST['aswin_page'] ) );
	}
};
add_action( 'created_aswin_work_cat', $aswin_save_cat );
add_action( 'edited_aswin_work_cat', $aswin_save_cat );

add_filter( 'manage_edit-aswin_work_cat_columns', function ( $c ) {
	$c['aswin_style'] = 'Display style';
	$c['aswin_order'] = 'Order';
	$c['aswin_page']  = 'Section page';
	return $c;
} );
add_filter( 'manage_aswin_work_cat_custom_column', function ( $out, $col, $term_id ) {
	if ( 'aswin_style' === $col ) {
		$d = aswin_designs();
		$s = get_term_meta( $term_id, 'aswin_style', true );
		return esc_html( $d[ $s ] ?? $d['card'] );
	}
	if ( 'aswin_page' === $col ) {
		$p = (int) get_term_meta( $term_id, 'aswin_page', true );
		return $p ? '<a href="' . esc_url( get_edit_post_link( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a>' : '—';
	}
	if ( 'aswin_order' === $col ) {
		return (int) get_term_meta( $term_id, 'aswin_order', true );
	}
	return $out;
}, 10, 3 );

/* ---------- Meta boxes ---------- */
function aswin_work_fields() {
	return array(
		'_aswin_video'      => 'int',
		'_aswin_gallery'    => 'ids',
		'_aswin_pdf'        => 'int',
		'_aswin_link'       => 'url',
		'_aswin_link_new'   => 'bool',
		'_aswin_ig_handle'  => 'text',
		'_aswin_ig_caption' => 'textarea',
		'_aswin_ig_likes'   => 'text',
		'_aswin_ig_date'    => 'text',
		'_aswin_brand'      => 'int',
		'_aswin_videos'     => 'ids',
		'_aswin_feature_videos' => 'ids',
		'_aswin_tagline'    => 'text',
		'_aswin_headline'   => 'text',
		'_aswin_series_label' => 'text',
		'_aswin_films_label'  => 'text',
		'_aswin_year'       => 'text',
		'_aswin_role'       => 'text',
	);
}

/** Media picker field (hidden input + preview + buttons). */
function aswin_media_field( $name, $label, $value, $type = '', $multiple = false, $help = '' ) {
	$ids = array_filter( array_map( 'intval', explode( ',', (string) $value ) ) );
	echo '<div class="aswin-field aswin-media' . ( $multiple ? ' is-multiple' : '' ) . '"><label>' . esc_html( $label ) . '</label>';
	echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '">';
	echo '<div class="aswin-preview">';
	foreach ( $ids as $id ) {
		echo aswin_admin_preview( $id ); // phpcs:ignore
	}
	echo '</div>';
	printf(
		'<button type="button" class="button aswin-pick" data-type="%s" data-multiple="%d" data-title="%s">%s</button> ',
		esc_attr( $type ),
		$multiple ? 1 : 0,
		esc_attr( $label ),
		$multiple ? 'Add files' : 'Upload / choose'
	);
	echo '<button type="button" class="button-link aswin-clear"' . ( $ids ? '' : ' hidden' ) . '>Remove' . ( $multiple ? ' all' : '' ) . '</button>';
	if ( $help ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
	echo '</div>';
}

function aswin_admin_preview( $id ) {
	$mime = (string) get_post_mime_type( $id );
	if ( 0 === strpos( $mime, 'image' ) ) {
		$inner = wp_get_attachment_image( $id, 'thumbnail' );
	} else {
		$inner = '<span class="aswin-file">' . esc_html( ( 0 === strpos( $mime, 'video' ) ? '🎬 ' : '📄 ' ) . basename( (string) get_attached_file( $id ) ) ) . '</span>';
	}
	return '<span class="aswin-thumb" data-id="' . (int) $id . '">' . $inner . '<button type="button" class="aswin-x" aria-label="Remove">×</button></span>';
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'aswin_work_media', 'Work files & link', 'aswin_work_media_box', 'aswin_work', 'normal', 'high' );
	add_meta_box( 'aswin_work_ig', 'Instagram post details (for phone-mockup categories)', 'aswin_work_ig_box', 'aswin_work', 'normal', 'default' );
	add_meta_box( 'aswin_work_details', 'Project details', 'aswin_work_details_box', 'aswin_work', 'side', 'default' );
	add_meta_box( 'aswin_brand_link', 'Brand link', 'aswin_brand_box', 'aswin_brand', 'normal', 'high' );
} );

function aswin_work_media_box( $post ) {
	wp_nonce_field( 'aswin_save', 'aswin_nonce' );
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, $k, true );
	};
	echo '<p class="aswin-tip">The <strong>Cover image</strong> (sidebar) shows on the homepage and category pages. <strong>Short description</strong> appears under the title. The main editor above is the full write-up on this work’s own page (brief, idea, result…). Pick categories in the sidebar.</p>';
	aswin_media_field( '_aswin_video', 'Video (optional)', $m( '_aswin_video' ), 'video', false, 'MP4/WebM. Plays silently on loop in place of the cover image — great for reels.' );
	aswin_media_field( '_aswin_gallery', 'More images (optional)', $m( '_aswin_gallery' ), 'image', true, 'Shown as a gallery on the work page (click to enlarge). Drag to reorder.' );
	aswin_media_field( '_aswin_feature_videos', 'Feature videos (optional, 2)', $m( '_aswin_feature_videos' ), 'video', true, 'The two wide video tiles on a campaign showcase (e.g. campaign film, treatment video). Empty = the first two campaign videos are used.' );
	aswin_media_field( '_aswin_videos', 'Campaign videos (optional)', $m( '_aswin_videos' ), 'video', true, 'Shown as “The films” on the work page, each with its own player. Drag to reorder.' );
	aswin_media_field( '_aswin_pdf', 'PDF file (optional)', $m( '_aswin_pdf' ), 'application/pdf', false, 'e.g. an article or press release. Shown inside the work page with a download button.' );
	echo '<div class="aswin-field"><label for="_aswin_link">Link (optional)</label><input type="url" class="widefat" id="_aswin_link" name="_aswin_link" placeholder="https://" value="' . esc_attr( $m( '_aswin_link' ) ) . '">';
	echo '<label class="aswin-inline"><input type="checkbox" name="_aswin_link_new" value="1"' . checked( $m( '_aswin_link_new' ), '1', false ) . '> Open in new tab</label>';
	echo '<p class="description">Shown as a “View live” button on the work page (e.g. the published article or post).</p></div>';
}

function aswin_work_ig_box( $post ) {
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, $k, true );
	};
	echo '<div class="aswin-cols">';
	echo '<div class="aswin-field"><label>Account handle</label><input type="text" class="widefat" name="_aswin_ig_handle" placeholder="brandname" value="' . esc_attr( $m( '_aswin_ig_handle' ) ) . '"></div>';
	echo '<div class="aswin-field"><label>Liked by</label><input type="text" class="widefat" name="_aswin_ig_likes" placeholder="username" value="' . esc_attr( $m( '_aswin_ig_likes' ) ) . '"></div>';
	echo '<div class="aswin-field"><label>Date</label><input type="text" class="widefat" name="_aswin_ig_date" placeholder="9 March 2022" value="' . esc_attr( $m( '_aswin_ig_date' ) ) . '"></div>';
	echo '</div>';
	echo '<div class="aswin-field"><label>Post caption</label><textarea class="widefat" rows="5" name="_aswin_ig_caption" placeholder="Write the caption… #hashtags and @mentions turn blue">' . esc_textarea( $m( '_aswin_ig_caption' ) ) . '</textarea></div>';
}

function aswin_work_details_box( $post ) {
	$brand  = (int) get_post_meta( $post->ID, '_aswin_brand', true );
	$brands = get_posts( array( 'post_type' => 'aswin_brand', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => array( 'publish', 'draft' ) ) );
	echo '<div class="aswin-field"><label for="_aswin_brand">Client / brand</label><select class="widefat" name="_aswin_brand" id="_aswin_brand"><option value="">— None —</option>';
	foreach ( $brands as $b ) {
		echo '<option value="' . (int) $b->ID . '"' . selected( $brand, $b->ID, false ) . '>' . esc_html( $b->post_title ) . '</option>';
	}
	echo '</select><p class="description">Shown on the work page, and the work appears on that brand’s page. <a href="' . esc_url( admin_url( 'post-new.php?post_type=aswin_brand' ) ) . '" target="_blank">Add a brand</a></p></div>';
	echo '<div class="aswin-field"><label for="_aswin_year">Year</label><input type="text" class="widefat" name="_aswin_year" id="_aswin_year" placeholder="2024" value="' . esc_attr( get_post_meta( $post->ID, '_aswin_year', true ) ) . '"></div>';
	echo '<div class="aswin-field"><label for="_aswin_role">My role</label><input type="text" class="widefat" name="_aswin_role" id="_aswin_role" placeholder="Copy, concept" value="' . esc_attr( get_post_meta( $post->ID, '_aswin_role', true ) ) . '"></div>';
	echo '<div class="aswin-field"><label for="_aswin_tagline">Campaign line (optional)</label><input type="text" class="widefat" name="_aswin_tagline" id="_aswin_tagline" placeholder="The Way of Ayurveda" value="' . esc_attr( get_post_meta( $post->ID, '_aswin_tagline', true ) ) . '"><p class="description">When set, the work page becomes a campaign showcase board and the card shows its preview.</p></div>';
	foreach ( array(
		'_aswin_headline'     => array( 'Showcase headline', 'National Ayurveda Day 2026', 'Big green title on the board. Empty = campaign line.' ),
		'_aswin_series_label' => array( 'Posts label', 'Thematic social media series', '' ),
		'_aswin_films_label'  => array( 'Videos label', 'Podcast series', '' ),
	) as $k => $f ) {
		echo '<div class="aswin-field"><label for="' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label><input type="text" class="widefat" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" placeholder="' . esc_attr( $f[1] ) . '" value="' . esc_attr( get_post_meta( $post->ID, $k, true ) ) . '">' . ( $f[2] ? '<p class="description">' . esc_html( $f[2] ) . '</p>' : '' ) . '</div>';
	}
}

function aswin_brand_box( $post ) {
	wp_nonce_field( 'aswin_save', 'aswin_nonce' );
	echo '<p class="aswin-tip">Upload the brand <strong>Logo</strong> in the right sidebar (transparent PNG or SVG works best). Without a logo the brand name is shown as text. The main editor is the description on the brand’s own page; works linked to this brand (Works → Project details → Client) are listed there automatically.</p>';
	$prio = (int) $post->menu_order;
	echo '<div class="aswin-field"><label for="aswin_priority">Priority (optional)</label><input type="number" min="1" step="1" style="width:120px" id="aswin_priority" name="aswin_priority" placeholder="e.g. 1" value="' . ( $prio > 0 ? $prio : '' ) . '">';
	echo '<p class="description"><strong>1, 2, 3</strong> = the three brands shown on the homepage, in that order. Brands with a priority always come before brands without one. Leave empty if the order doesn’t matter.</p></div>';
	echo '<div class="aswin-field"><label for="_aswin_link">Website (optional)</label><input type="url" class="widefat" id="_aswin_link" name="_aswin_link" placeholder="https://" value="' . esc_attr( get_post_meta( $post->ID, '_aswin_link', true ) ) . '"></div>';
}

/** Save the brand Priority into menu_order (0 = no priority). */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'aswin_brand' !== $data['post_type'] || ! isset( $_POST['aswin_priority'], $_POST['aswin_nonce'] ) ) {
		return $data;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswin_nonce'] ) ), 'aswin_save' ) ) {
		return $data;
	}
	$data['menu_order'] = max( 0, (int) $_POST['aswin_priority'] );
	return $data;
}, 10, 2 );

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['aswin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswin_nonce'] ) ), 'aswin_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = 'aswin_brand' === $post->post_type ? array( '_aswin_link' => 'url' ) : ( 'aswin_work' === $post->post_type ? aswin_work_fields() : array() );
	foreach ( $fields as $key => $type ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		switch ( $type ) {
			case 'int':
				$val = (string) absint( $raw );
				$val = '0' === $val ? '' : $val;
				break;
			case 'ids':
				$val = implode( ',', array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) );
				break;
			case 'url':
				$val = esc_url_raw( $raw );
				break;
			case 'bool':
				$val = $raw ? '1' : '';
				break;
			case 'textarea':
				$val = sanitize_textarea_field( $raw );
				break;
			default:
				$val = sanitize_text_field( $raw );
		}
		if ( '' === $val ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $val );
		}
	}
}, 10, 2 );

/* ---------- Admin assets ---------- */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( 'aswin_work', 'aswin_brand' ), true ) ) {
		return;
	}
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'aswin-admin', get_template_directory_uri() . '/assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), ASWIN_VER, true );
	}
	wp_enqueue_style( 'aswin-admin', get_template_directory_uri() . '/assets/admin.css', array(), ASWIN_VER );
} );

/** Excerpt box label → "Short description". */
add_filter( 'gettext', function ( $t, $text, $domain ) {
	if ( 'default' === $domain && is_admin() && 'Excerpt' === $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && in_array( $screen->post_type, array( 'aswin_work', 'aswin_brand' ), true ) ) {
			return 'Short description';
		}
	}
	return $t;
}, 10, 3 );

/* ---------- Admin list columns ---------- */
foreach ( array( 'aswin_work', 'aswin_brand' ) as $pt ) {
	add_filter( "manage_{$pt}_posts_columns", function ( $c ) use ( $pt ) {
		$new = array();
		foreach ( $c as $k => $v ) {
			if ( 'title' === $k ) {
				$new['aswin_thumb'] = '';
			}
			$new[ $k ] = $v;
		}
		$new['menu_order'] = 'aswin_brand' === $pt ? 'Priority' : 'Order';
		unset( $new['date'] );
		return $new;
	} );
	add_action( "manage_{$pt}_posts_custom_column", function ( $col, $id ) use ( $pt ) {
		if ( 'aswin_thumb' === $col ) {
			echo get_the_post_thumbnail( $id, array( 56, 56 ), array( 'style' => 'width:56px;height:56px;object-fit:contain;border-radius:6px;background:#f3eee5' ) ) ?: '<span style="display:inline-block;width:56px;height:56px;border-radius:6px;background:#f3eee5"></span>';
		} elseif ( 'menu_order' === $col ) {
			$o = (int) get_post_field( 'menu_order', $id );
			if ( 'aswin_brand' === $pt ) {
				static $home = null;
				if ( null === $home ) {
					$home = get_posts( array( 'post_type' => 'aswin_brand', 'post_status' => 'publish', 'posts_per_page' => 3, 'fields' => 'ids', 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'suppress_filters' => false ) );
				}
				echo ( $o > 0 ? '<strong>' . $o . '</strong>' : '—' ) . ( in_array( (int) $id, array_map( 'intval', $home ), true ) ? ' <span style="color:#ff4f1f">· on homepage</span>' : '' ); // phpcs:ignore
			} else {
				echo (int) $o;
			}
		}
	}, 10, 2 );
	add_filter( "manage_edit-{$pt}_sortable_columns", function ( $c ) {
		$c['menu_order'] = 'menu_order';
		return $c;
	} );
}

/** Admin lists sorted by Order by default. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && in_array( $q->get( 'post_type' ), array( 'aswin_work', 'aswin_brand' ), true ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
} );

/** Brands everywhere (front end and admin list): prioritised brands first (1, 2, 3…), then the rest A–Z. */
add_filter( 'posts_orderby', function ( $orderby, $q ) {
	global $wpdb;
	$pt = $q->get( 'post_type' );
	if ( 'aswin_brand' !== $pt || $q->get( 'aswin_raw_order' ) ) {
		return $orderby;
	}
	$requested = $q->get( 'orderby' );
	if ( is_admin() && $requested && ! in_array( $requested, array( 'menu_order', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ), true ) && ! is_array( $requested ) ) {
		return $orderby; // admin clicked another column (e.g. Title)
	}
	return "CASE WHEN {$wpdb->posts}.menu_order > 0 THEN 0 ELSE 1 END ASC, {$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_title ASC";
}, 10, 2 );

/** Category filter dropdown on the Works list. */
add_action( 'restrict_manage_posts', function ( $pt ) {
	if ( 'aswin_work' !== $pt ) {
		return;
	}
	wp_dropdown_categories( array(
		'taxonomy'        => 'aswin_work_cat',
		'name'            => 'aswin_work_cat',
		'value_field'     => 'slug',
		'show_option_all' => 'All categories',
		'selected'        => isset( $_GET['aswin_work_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['aswin_work_cat'] ) ) : '',
		'hide_empty'      => false,
	) );
} );
