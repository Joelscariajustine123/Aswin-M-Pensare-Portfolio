<?php
/**
 * Shared rendering for Works & Brands (used by Elementor widgets and theme templates).
 */

defined( 'ABSPATH' ) || exit;

/** Single-page layout for a work: 'scene', 'phone' or 'card' (from its categories' Display style). */
function aswin_work_style( $post_id ) {
	$styles = array();
	foreach ( (array) wp_get_post_terms( $post_id, 'aswin_work_cat' ) as $t ) {
		$styles[] = get_term_meta( $t->term_id, 'aswin_style', true );
	}
	if ( in_array( 'scene', $styles, true ) ) {
		return 'scene';
	}
	return in_array( 'phone', $styles, true ) ? 'phone' : 'card';
}

/** Available section designs (term meta aswin_style). */
function aswin_designs() {
	return array(
		'card'      => 'Cards (image grid)',
		'phone'     => 'Phone mockups (Instagram / social)',
		'scene'     => 'Phone scene (phone + side posts mockup)',
		'showcase'  => 'Showcase cards (full image + caption)',
		'list'      => 'Article list (numbered rows)',
		'feature'   => 'Feature mosaic (big hero tile)',
		'newsprint' => 'Newspaper clippings',
		'case'      => 'Case-study rows (alternating)',
	);
}

/** Design for a set of categories: the category's own design when exactly one, else cards/phones grid. */
function aswin_design_for( $terms ) {
	if ( 1 !== count( $terms ) ) {
		return 'cards';
	}
	$s = get_term_meta( $terms[0]->term_id, 'aswin_style', true );
	return in_array( $s, array( 'scene', 'showcase', 'list', 'feature', 'newsprint', 'case' ), true ) ? $s : 'cards';
}

/** URL of a category's section page (Elementor page if linked, else category archive). */
function aswin_section_url( $term ) {
	$page = (int) get_term_meta( $term->term_id, 'aswin_page', true );
	if ( $page && 'publish' === get_post_status( $page ) ) {
		return get_permalink( $page );
	}
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? '' : $link;
}

/** URL of the Brands page. */
function aswin_brands_url() {
	$page = (int) get_option( 'aswin_brands_page' );
	if ( $page && 'publish' === get_post_status( $page ) ) {
		return get_permalink( $page );
	}
	return (string) get_post_type_archive_link( 'aswin_brand' );
}

/** [url => label] for every section page, in order (Brands first). */
function aswin_section_links() {
	$links = array();
	if ( $b = aswin_brands_url() ) {
		$links[ $b ] = 'Brands';
	}
	foreach ( aswin_work_cats() as $t ) {
		if ( $u = aswin_section_url( $t ) ) {
			$links[ $u ] = $t->name;
		}
	}
	return $links;
}

/** First category of a work (respecting tab order). */
function aswin_work_primary_cat( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'aswin_work_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	usort( $terms, function ( $a, $b ) {
		return (int) get_term_meta( $a->term_id, 'aswin_order', true ) <=> (int) get_term_meta( $b->term_id, 'aswin_order', true );
	} );
	return $terms[0];
}

/** Image IDs for a work: cover first, then "More images". */
function aswin_work_images( $post_id ) {
	$gallery = array_map( 'intval', explode( ',', (string) get_post_meta( $post_id, '_aswin_gallery', true ) ) );
	return array_values( array_unique( array_filter( array_merge( array( (int) get_post_thumbnail_id( $post_id ) ), $gallery ) ) ) );
}

class Aswin_Render {

	private static $instance;

	public static function i() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function svg_icon( $name ) {
		$p = array(
			'heart'   => '<path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 8 3.3 4.5 6.8 4.5c2 0 3.4 1.1 4.2 2.5.9-1.4 2.3-2.5 4.2-2.5 3.5 0 5.4 3.5 4.1 6.8-1.8 4.6-7.3 9.2-7.3 9.2z"/>',
			'comment' => '<path d="M20.5 11.6a8.4 8.4 0 0 1-12.2 7.5L3.5 20.5l1.4-4.6A8.4 8.4 0 1 1 20.5 11.6z"/>',
			'send'    => '<path d="M21 3 10 13.5M21 3l-6.5 18-4-7.5L3 9.5z"/>',
			'save'    => '<path d="M19 21l-7-5-7 5V4.5A1.5 1.5 0 0 1 6.5 3h11A1.5 1.5 0 0 1 19 4.5z"/>',
			'home'    => '<path d="M3.5 10.5 12 3.5l8.5 7V20a.5.5 0 0 1-.5.5h-5v-6h-6v6H4a.5.5 0 0 1-.5-.5z"/>',
			'search'  => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20.5 20.5-5.2-5.2"/>',
			'plus'    => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M12 8v8M8 12h8"/>',
			'reels'   => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M3.5 8.5h17M9 3.5l2.5 5M14.5 3.5l2.5 5M10.5 11.5v5l4-2.5z"/>',
			'back'    => '<path d="M15 4.5 7.5 12l7.5 7.5"/>',
			'more'    => '<circle cx="5.5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="18.5" cy="12" r="1.2"/>',
		);
		return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $p[ $name ] . '</svg>';
	}

	public function ig_text( $text ) {
		$html = preg_replace( '/(^|\s)([#@][\p{L}\p{N}_.]+)/u', '$1<span class="tag">$2</span>', esc_html( (string) $text ) );
		return nl2br( $html );
	}

	/** Cover media: video (cover image as poster) or image. */
	public function work_media( $post_id, $size ) {
		$video = (int) get_post_meta( $post_id, '_aswin_video', true );
		$thumb = get_post_thumbnail_id( $post_id );
		$src   = $video ? wp_get_attachment_url( $video ) : '';
		if ( $src ) {
			$poster = $thumb ? wp_get_attachment_image_url( $thumb, $size ) : '';
			return '<video src="' . esc_url( $src ) . '"' . ( $poster ? ' poster="' . esc_url( $poster ) . '"' : '' ) . ' autoplay muted loop playsinline preload="metadata"></video>';
		}
		return $thumb ? wp_get_attachment_image( $thumb, $size, false, array( 'loading' => 'lazy' ) ) : '';
	}

	/**
	 * Phone mockup for a work.
	 * $link: URL to wrap the phone in ('' = not linked). $caption: show title/description under it.
	 */
	public function phone_html( $p, $ui = true, $default_handle = 'brandname', $link = '', $caption = true ) {
		$id      = $p->ID;
		$handle  = get_post_meta( $id, '_aswin_ig_handle', true );
		$handle  = $handle ? $handle : $default_handle;
		$cap     = get_post_meta( $id, '_aswin_ig_caption', true );
		$likes   = get_post_meta( $id, '_aswin_ig_likes', true );
		$date    = get_post_meta( $id, '_aswin_ig_date', true );
		$media   = $this->work_media( $id, 'large' );

		$h = '<figure class="phone-item">' . ( $link ? '<a class="phone-link" href="' . esc_url( $link ) . '">' : '' );
		$h .= '<div class="phone"><div class="phone-screen' . ( $ui ? ' has-ui' : '' ) . '"><span class="phone-island"></span>';
		if ( $ui ) {
			$h .= '<div class="ph-status"><b>9:41</b><span class="ph-sys"><i></i><i></i><i></i></span></div>';
			$h .= '<div class="ph-bar">' . $this->svg_icon( 'back' ) . '<b>Posts</b><span></span></div>';
			$h .= '<div class="ph-head"><span class="ph-avatar"></span><b>' . esc_html( $handle ) . '</b>' . $this->svg_icon( 'more' ) . '</div>';
		}
		$h .= '<div class="ph-media">' . ( $media ? $media : '<div class="ph-ph">' . esc_html( get_the_title( $id ) ) . '</div>' ) . '</div>';
		if ( $ui ) {
			$h .= '<div class="ph-actions">' . $this->svg_icon( 'heart' ) . $this->svg_icon( 'comment' ) . $this->svg_icon( 'send' ) . '<span></span>' . $this->svg_icon( 'save' ) . '</div><div class="ph-text">';
			if ( $likes ) {
				$h .= '<p class="ph-likes">Liked by <b>' . esc_html( $likes ) . '</b> and <b>others</b></p>';
			}
			if ( $cap ) {
				$h .= '<p><b>' . esc_html( $handle ) . '</b> ' . $this->ig_text( $cap ) . '</p>';
			}
			if ( $date ) {
				$h .= '<p class="ph-date">' . esc_html( $date ) . '</p>';
			}
			$h .= '</div><div class="ph-nav">' . $this->svg_icon( 'home' ) . $this->svg_icon( 'search' ) . $this->svg_icon( 'plus' ) . $this->svg_icon( 'reels' ) . '<span class="ph-avatar"></span></div>';
		}
		$h .= '</div></div>' . ( $link ? '</a>' : '' );
		if ( $caption ) {
			$desc = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$h   .= '<figcaption class="phone-cap"><h3>' . ( $link ? '<a href="' . esc_url( $link ) . '">' : '' ) . esc_html( get_the_title( $id ) ) . ( $link ? '</a>' : '' ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '</figcaption>';
		}
		return $h . '</figure>';
	}

	public function card_html( $p, $cat_label = '', $mode = 'grid' ) {
		$id    = $p->ID;
		$media = $this->work_media( $id, 'masonry' === $mode ? 'aswin-free' : 'aswin-card' );
		$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
		$h     = '<a class="work-card" href="' . esc_url( get_permalink( $id ) ) . '">';
		$h    .= $media ? '<div class="work-thumb">' . $media . '</div>' : '<div class="work-thumb no-img">' . esc_html( get_the_title( $id ) ) . '</div>';
		$h    .= '<div class="work-meta">' . ( $cat_label ? '<span class="chip">' . esc_html( $cat_label ) . '</span>' : '' ) . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' );
		if ( get_post_meta( $id, '_aswin_pdf', true ) ) {
			$h .= '<span class="badge">PDF</span>';
		}
		$imgs = count( aswin_work_images( $id ) );
		if ( $imgs > 1 ) {
			$h .= '<span class="badge light">' . $imgs . ' images</span>';
		}
		return $h . '<span class="arrow" aria-hidden="true">↗</span></div></a>';
	}

	/** "Client · Year" line for a work. */
	public function meta_line( $id ) {
		$bits  = array();
		$brand = (int) get_post_meta( $id, '_aswin_brand', true );
		if ( $brand && get_post_status( $brand ) ) {
			$bits[] = get_the_title( $brand );
		}
		$year = get_post_meta( $id, '_aswin_year', true );
		if ( $year ) {
			$bits[] = $year;
		}
		return implode( ' · ', $bits );
	}

	/* ---- Section designs ---- */

	/** Technical writing: numbered editorial list. */
	public function design_list( $posts ) {
		$h = '<ol class="d-list">';
		foreach ( array_values( $posts ) as $i => $p ) {
			$id    = $p->ID;
			$media = $this->work_media( $id, 'aswin-card' );
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$meta  = $this->meta_line( $id );
			$h    .= '<li class="reveal"><a class="dl-row" href="' . esc_url( get_permalink( $id ) ) . '">';
			$h    .= '<span class="dl-num">' . str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) . '</span>';
			$h    .= '<span class="dl-main"><h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '</span>';
			$h    .= '<span class="dl-meta">' . esc_html( $meta ) . ( get_post_meta( $id, '_aswin_pdf', true ) ? ' <b>PDF</b>' : '' ) . '</span>';
			$h    .= '<span class="dl-thumb">' . ( $media ? $media : '<i></i>' ) . '</span>';
			$h    .= '<span class="dl-arrow" aria-hidden="true">→</span></a></li>';
		}
		return $h . '</ol>';
	}

	/**
	 * Phone scene: gradient backdrop + plinth, phone (cover image) in the middle,
	 * up to two extra posts (from "More images") fanned out either side, brand logo on top.
	 * $big = true for the work's own page.
	 */
	public function scene_html( $p, $big = false ) {
		$id      = $p->ID;
		$cover   = (int) get_post_thumbnail_id( $id );
		$video   = (int) get_post_meta( $id, '_aswin_video', true );
		$extras  = array_values( array_diff( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_aswin_gallery', true ) ) ) ), array( $cover ) ) );
		$brand   = (int) get_post_meta( $id, '_aswin_brand', true );
		$brand   = $brand && 'publish' === get_post_status( $brand ) ? $brand : 0;
		$handle  = get_post_meta( $id, '_aswin_ig_handle', true );
		$name    = $brand ? get_the_title( $brand ) : ( $handle ? $handle : get_the_title( $id ) );
		$size    = $big ? 'large' : 'medium_large';

		$screen = $this->work_media( $id, $size );
		if ( ! $screen ) {
			$screen = '<div class="ph-ph">' . esc_html( get_the_title( $id ) ) . '</div>';
		}

		$has_logo = $brand && has_post_thumbnail( $brand );
		$h        = '<div class="psc-scene' . ( $big ? ' is-big' : '' ) . ( $extras ? ' has-sides' : '' ) . '"><div class="psc-stage">';
		if ( $has_logo ) {
			$h .= '<div class="psc-brand">' . get_the_post_thumbnail( $brand, 'medium', array( 'alt' => $name, 'loading' => 'lazy' ) ) . '</div>';
		}
		$h .= '<div class="psc-plinth" aria-hidden="true"></div>';
		// Other images alternate left / right; a 3rd and 4th sit one layer behind, further out.
		foreach ( array_slice( $extras, 0, 4 ) as $i => $img ) {
			$side = 0 === $i % 2 ? 'left' : 'right';
			$h   .= '<div class="psc-side psc-' . $side . ' d' . (int) floor( $i / 2 ) . '">' . wp_get_attachment_image( $img, $size, false, array( 'loading' => 'lazy' ) ) . '</div>';
		}
		// Phone: status bar + profile header, the cover post (lined up with the side posts), a thumbnail strip, nav.
		$h .= '<div class="psc-phone"><div class="psc-screen">';
		$h .= '<div class="psc-status"><b>12:08</b><span class="ph-sys"><i></i><i></i><i></i></span></div>';
		$h .= '<div class="psc-profile"><span class="psc-avatar">' . ( $has_logo ? get_the_post_thumbnail( $brand, 'thumbnail', array( 'alt' => '' ) ) : '' ) . '</span><b>' . esc_html( $name ) . '</b></div>';
		$h .= '<div class="psc-post">' . $screen . '</div>';
		$h .= '<div class="psc-nav">' . $this->svg_icon( 'home' ) . $this->svg_icon( 'search' ) . $this->svg_icon( 'plus' ) . $this->svg_icon( 'reels' ) . '<span class="ph-avatar"></span></div>';
		$h .= '</div><span class="psc-island"></span></div>';
		return $h . '</div></div>';
	}

	/** Social media section: phone-scene cards with caption. */
	public function design_scene( $posts, $columns = 3 ) {
		$h = '<div class="d-scene cols-' . (int) $columns . '">';
		foreach ( array_values( $posts ) as $p ) {
			$id    = $p->ID;
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$label = implode( ' · ', array_filter( array( get_post_meta( $id, '_aswin_role', true ), $this->meta_line( $id ) ) ) );
			$h    .= '<a class="psc-card reveal" href="' . esc_url( get_permalink( $id ) ) . '">' . $this->scene_html( $p );
			$h    .= '<div class="ds-cap">' . ( $label ? '<span class="ds-meta">' . esc_html( $label ) . '</span>' : '' ) . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '<span class="ds-link">View work <span aria-hidden="true">→</span></span></div></a>';
		}
		return $h . '</div>';
	}

	/** Showcase cards: the whole image framed (never cropped), caption underneath. */
	public function design_showcase( $posts, $columns = 3 ) {
		$h = '<div class="d-showcase cols-' . (int) $columns . '">';
		foreach ( array_values( $posts ) as $i => $p ) {
			$id    = $p->ID;
			$media = $this->work_media( $id, 'large' );
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$role  = get_post_meta( $id, '_aswin_role', true );
			$meta  = $this->meta_line( $id );
			$label = implode( ' · ', array_filter( array( $role, $meta ) ) );
			$h    .= '<a class="ds-card reveal" href="' . esc_url( get_permalink( $id ) ) . '">';
			$h    .= '<div class="ds-frame">' . ( $media ? $media : '<span class="ds-ph">' . esc_html( get_the_title( $id ) ) . '</span>' ) . '</div>';
			$h    .= '<div class="ds-cap">';
			$h    .= $label ? '<span class="ds-meta">' . esc_html( $label ) . '</span>' : '';
			$h    .= '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>';
			$h    .= $desc ? '<p>' . esc_html( $desc ) . '</p>' : '';
			$h    .= '<span class="ds-link">View work <span aria-hidden="true">→</span></span>';
			$h    .= '</div></a>';
		}
		return $h . '</div>';
	}

	/** Campaigns / PR: feature mosaic with captions over images. */
	public function design_feature( $posts ) {
		$h = '<div class="d-feature">';
		foreach ( array_values( $posts ) as $i => $p ) {
			$id    = $p->ID;
			$media = $this->work_media( $id, 0 === $i ? 'aswin-wide' : 'aswin-card' );
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$meta  = $this->meta_line( $id );
			$h    .= '<a class="df-item reveal' . ( $media ? '' : ' no-img' ) . '" href="' . esc_url( get_permalink( $id ) ) . '">';
			$h    .= '<div class="df-media">' . $media . '</div>';
			$h    .= '<div class="df-cap">' . ( $meta ? '<span class="df-meta">' . esc_html( $meta ) . '</span>' : '' ) . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '</div>';
			$h    .= '<span class="df-arrow" aria-hidden="true">↗</span></a>';
		}
		return $h . '</div>';
	}

	/** Newspaper ads: paper clippings with a masthead. */
	public function design_newsprint( $posts ) {
		$h = '<div class="d-news">';
		foreach ( array_values( $posts ) as $i => $p ) {
			$id     = $p->ID;
			$media  = $this->work_media( $id, 'aswin-free' );
			$desc   = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$brand  = (int) get_post_meta( $id, '_aswin_brand', true );
			$paper  = $brand && get_post_status( $brand ) ? get_the_title( $brand ) : 'The Daily Copy';
			$year   = get_post_meta( $id, '_aswin_year', true );
			$h     .= '<a class="dn-clip reveal" href="' . esc_url( get_permalink( $id ) ) . '">';
			$h     .= '<div class="dn-mast"><span>Vol. ' . ( $i + 1 ) . '</span><b>' . esc_html( $paper ) . '</b><span>' . esc_html( $year ? $year : 'Print' ) . '</span></div>';
			$h     .= $media ? '<div class="dn-img">' . $media . '</div>' : '';
			$h     .= '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>';
			$h     .= $desc ? '<p>' . esc_html( $desc ) . '</p>' : '';
			$h     .= '<span class="dn-more">Read the ad →</span></a>';
		}
		return $h . '</div>';
	}

	/** Branding: alternating case-study rows. */
	public function design_case( $posts ) {
		$h = '<div class="d-case">';
		foreach ( array_values( $posts ) as $i => $p ) {
			$id    = $p->ID;
			$media = $this->work_media( $id, 'aswin-wide' );
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$brand = (int) get_post_meta( $id, '_aswin_brand', true );
			$role  = get_post_meta( $id, '_aswin_role', true );
			$year  = get_post_meta( $id, '_aswin_year', true );
			$h    .= '<a class="dc-row reveal" href="' . esc_url( get_permalink( $id ) ) . '">';
			$h    .= '<div class="dc-media">' . ( $media ? $media : '<div class="dc-ph">' . esc_html( get_the_title( $id ) ) . '</div>' ) . '</div>';
			$h    .= '<div class="dc-body"><span class="dc-num">Case ' . str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) . '</span><h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' );
			$facts = array();
			if ( $brand && get_post_status( $brand ) ) {
				$facts['Client'] = get_the_title( $brand );
			}
			if ( $role ) {
				$facts['Role'] = $role;
			}
			if ( $year ) {
				$facts['Year'] = $year;
			}
			if ( $facts ) {
				$h .= '<dl>';
				foreach ( $facts as $k => $v ) {
					$h .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
				}
				$h .= '</dl>';
			}
			$h .= '<span class="btn ghost">View case study <span aria-hidden="true">→</span></span></div></a>';
		}
		return $h . '</div>';
	}

	/**
	 * Grid of works.
	 * Options: columns (2|3|4), mode (grid|masonry), ig_ui (bool), ig_handle, chips (bool),
	 *          filter (bool), all_label, counts (bool), hide_empty (bool), default_slug ('*'), terms (WP_Term[]).
	 */
	public function works_grid( $posts, $o = array() ) {
		$o = wp_parse_args( $o, array(
			'columns'      => 3,
			'mode'         => 'grid',
			'ig_ui'        => true,
			'ig_handle'    => 'brandname',
			'chips'        => true,
			'filter'       => false,
			'show_all'     => true,
			'all_label'    => 'All work',
			'counts'       => true,
			'hide_empty'   => true,
			'default_slug' => '*',
			'terms'        => null,
			'design'       => 'cards',
		) );
		switch ( $o['design'] ) {
			case 'scene':
				return $this->design_scene( $posts, $o['columns'] );
			case 'showcase':
				return $this->design_showcase( $posts, $o['columns'] );
			case 'list':
				return $this->design_list( $posts );
			case 'feature':
				return $this->design_feature( $posts );
			case 'newsprint':
				return $this->design_newsprint( $posts );
			case 'case':
				return $this->design_case( $posts );
		}
		$terms = null === $o['terms'] ? aswin_work_cats() : $o['terms'];
		$style = array();
		$count = array();
		foreach ( $terms as $t ) {
			$style[ $t->slug ] = 'phone' === get_term_meta( $t->term_id, 'aswin_style', true ) ? 'phone' : 'card';
			$count[ $t->slug ] = 0;
		}

		$items = array();
		foreach ( $posts as $p ) {
			$slugs = array();
			$is_ph = false;
			$label = '';
			foreach ( (array) wp_get_post_terms( $p->ID, 'aswin_work_cat' ) as $t ) {
				if ( ! isset( $style[ $t->slug ] ) ) {
					continue;
				}
				$slugs[] = $t->slug;
				$count[ $t->slug ]++;
				$is_ph = $is_ph || 'phone' === $style[ $t->slug ];
				$label = $label ? $label : $t->name;
			}
			$items[] = array( $p, $slugs, $is_ph, $label );
		}

		$def = $o['default_slug'];
		$h   = '<div class="work-showcase">';
		if ( $o['filter'] ) {
			$h  .= '<div class="work-filter reveal" role="tablist" aria-label="Filter work by category">';
			$tab = function ( $slug, $label, $n ) use ( $def, $o ) {
				$on = $slug === $def;
				return sprintf(
					'<button type="button" role="tab" data-filter="%s" class="%s" aria-selected="%s">%s%s</button>',
					esc_attr( $slug ),
					$on ? 'is-active' : '',
					$on ? 'true' : 'false',
					esc_html( $label ),
					$o['counts'] ? '<sup>' . (int) $n . '</sup>' : ''
				);
			};
			if ( $o['show_all'] ) {
				$h .= $tab( '*', $o['all_label'], count( $items ) );
			}
			foreach ( $terms as $t ) {
				if ( $o['hide_empty'] && ! $count[ $t->slug ] ) {
					continue;
				}
				$h .= $tab( $t->slug, $t->name, $count[ $t->slug ] );
			}
			$h .= '</div>';
		}

		$h .= '<div class="showcase-grid mode-' . esc_attr( $o['mode'] ) . ' cols-' . (int) $o['columns'] . '">';
		foreach ( $items as $it ) {
			list( $p, $slugs, $is_ph, $label ) = $it;
			$hidden = '*' !== $def && ! in_array( $def, $slugs, true );
			$h     .= '<div class="sc-item' . ( $is_ph ? ' is-phone' : '' ) . ( $hidden ? ' is-hidden' : '' ) . '" data-cats="' . esc_attr( implode( ' ', $slugs ) ) . '">';
			$h     .= $is_ph ? $this->phone_html( $p, $o['ig_ui'], $o['ig_handle'], get_permalink( $p ) ) : $this->card_html( $p, $o['chips'] ? $label : '', $o['mode'] );
			$h     .= '</div>';
		}
		return $h . '</div></div>';
	}

	/** Brand logo wall. $link: 'page' | 'website' | 'none'. */
	public function brands_wall( $posts, $o = array() ) {
		$o    = wp_parse_args( $o, array( 'columns' => 'auto', 'grayscale' => false, 'link' => 'page', 'names' => false ) );
		$cols = 'auto' === $o['columns'] ? '' : ' cols-' . (int) $o['columns'];
		$h    = '<div class="logo-wall' . esc_attr( $cols ) . ( $o['grayscale'] ? ' is-gray' : '' ) . ( $o['names'] ? ' with-names' : '' ) . ' reveal">';
		foreach ( $posts as $p ) {
			$name = get_the_title( $p );
			$url  = 'page' === $o['link'] ? get_permalink( $p ) : ( 'website' === $o['link'] ? get_post_meta( $p->ID, '_aswin_link', true ) : '' );
			$ext  = 'website' === $o['link'];
			$h   .= $url ? '<a class="logo-cell" href="' . esc_url( $url ) . '"' . ( $ext ? ' target="_blank" rel="noopener"' : '' ) . ' title="' . esc_attr( $name ) . '">' : '<div class="logo-cell" title="' . esc_attr( $name ) . '">';
			$h   .= has_post_thumbnail( $p ) ? get_the_post_thumbnail( $p, 'medium', array( 'alt' => $name, 'loading' => 'lazy' ) ) : '<span>' . esc_html( $name ) . '</span>';
			if ( $o['names'] && has_post_thumbnail( $p ) ) {
				$h .= '<em>' . esc_html( $name ) . '</em>';
			}
			$h .= $url ? '</a>' : '</div>';
		}
		return $h . '</div>';
	}

	public function view_more( $url, $label ) {
		if ( ! $url ) {
			return '';
		}
		return '<div class="view-more reveal"><a class="btn ghost" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span aria-hidden="true">→</span></a></div>';
	}
}
