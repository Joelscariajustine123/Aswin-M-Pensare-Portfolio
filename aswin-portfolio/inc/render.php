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
	if ( in_array( 'video', $styles, true ) ) {
		return 'video';
	}
	if ( in_array( 'laptop', $styles, true ) ) {
		return 'laptop';
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
		'video'     => 'Video films (poster + player)',
		'laptop'    => 'Laptop screen (decks / brand guidelines)',
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
	return in_array( $s, array( 'video', 'laptop', 'scene', 'showcase', 'list', 'feature', 'newsprint', 'case' ), true ) ? $s : 'cards';
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

	/**
	 * Laptop mockup. Card: cover slide. $big (work page): every slide as a slideshow.
	 * Slides are shown whole (contain), never cropped.
	 */
	public function laptop_html( $p, $big = false ) {
		$id     = $p->ID;
		$slides = aswin_work_images( $id );
		$multi  = count( $slides ) > 1;
		$tag    = $big ? 'button type="button"' : 'span role="button" tabindex="0"'; // cards are links: no nested <button>
		$etag   = $big ? 'button' : 'span';
		$h      = '<div class="lap-scene' . ( $big ? ' is-big' : '' ) . '"><div class="lap"' . ( $multi ? ' data-lap-slides' : '' ) . '>';
		$h     .= '<div class="lap-lid"><span class="lap-cam"></span><div class="lap-screen">';
		if ( ! $slides ) {
			$h .= '<div class="lap-slide is-on"><div class="ph-ph">' . esc_html( get_the_title( $id ) ) . '</div></div>';
		}
		foreach ( $slides as $i => $img ) {
			$tag_img = wp_get_attachment_image( $img, $big ? 'large' : 'medium_large', false, array( 'loading' => 0 === $i ? 'eager' : 'lazy' ) );
			if ( $i > 1 ) {
				// Deferred: main.js swaps data-src/srcset in just before the slide is shown.
				$tag_img = str_replace( array( ' src=', ' srcset=' ), array( ' data-src=', ' data-srcset=' ), $tag_img );
			}
			$h .= '<div class="lap-slide' . ( 0 === $i ? ' is-on' : '' ) . '">' . $tag_img . '</div>';
		}
		if ( $multi ) {
			$h .= '<' . $tag . ' class="lap-nav lap-prev" aria-label="Previous slide">‹</' . $etag . '><' . $tag . ' class="lap-nav lap-next" aria-label="Next slide">›</' . $etag . '>';
		}
		$h .= '</div></div><div class="lap-base"><span></span></div></div>';
		if ( $multi ) {
			$h .= '<div class="lap-dots">';
			foreach ( $slides as $i => $img ) {
				$h .= '<' . $tag . ' class="lap-dot' . ( 0 === $i ? ' is-on' : '' ) . '" aria-label="Slide ' . ( $i + 1 ) . '"></' . $etag . '>';
			}
			$h .= '</div>';
		}
		return $h . '</div>';
	}

	/** First film of a work: [attachment id, url, poster url, width, height, seconds]. */
	public function film_info( $id, $size = 'large' ) {
		$films = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_aswin_videos', true ) ) ) );
		$v     = $films ? (int) reset( $films ) : (int) get_post_meta( $id, '_aswin_video', true ); // fall back to the single "Video" field
		if ( ! $v || ! ( $url = wp_get_attachment_url( $v ) ) ) {
			return null;
		}
		$meta   = wp_get_attachment_metadata( $v );
		$poster = (int) get_post_meta( $v, '_aswin_poster', true );
		$poster = $poster ? $poster : (int) get_post_thumbnail_id( $id );
		return array(
			'id'     => $v,
			'url'    => $url,
			'poster' => $poster ? wp_get_attachment_image_url( $poster, $size ) : '',
			'w'      => (int) ( $meta['width'] ?? 16 ),
			'h'      => (int) ( $meta['height'] ?? 9 ),
			'secs'   => (int) ( $meta['length'] ?? 0 ),
		);
	}

	/** Large responsive player for the work page (keeps the film's own shape, fits the screen). */
	public function film_player( $id ) {
		$f = $this->film_info( $id, 'full' );
		if ( ! $f ) {
			return '';
		}
		$ratio = $f['w'] && $f['h'] ? $f['w'] . ' / ' . $f['h'] : '16 / 9';
		return '<div class="vd-player' . ( $f['h'] > $f['w'] ? ' is-vertical' : '' ) . '" style="--vd-ratio:' . esc_attr( $ratio ) . '">'
			. '<video src="' . esc_url( $f['url'] ) . '"' . ( $f['poster'] ? ' poster="' . esc_url( $f['poster'] ) . '"' : '' ) . ' controls playsinline preload="metadata"></video></div>';
	}

	/** Concept / scripting / direction section: film cards. */
	public function design_video( $posts, $columns = 3 ) {
		$h = '<div class="d-video cols-' . (int) $columns . '">';
		foreach ( array_values( $posts ) as $p ) {
			$id    = $p->ID;
			$f     = $this->film_info( $id );
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$label = implode( ' · ', array_filter( array( get_post_meta( $id, '_aswin_role', true ), $this->meta_line( $id ) ) ) );
			$poster = $f && $f['poster'] ? $f['poster'] : ( has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'large' ) : '' );
			$h    .= '<a class="psc-card vd-card reveal" href="' . esc_url( get_permalink( $id ) ) . '"><div class="vd-thumb">';
			if ( $poster ) {
				$h .= '<span class="vd-bg" style="background-image:url(' . esc_url( $poster ) . ')"></span><img src="' . esc_url( $poster ) . '" alt="" loading="lazy">';
			}
			if ( $f ) {
				$h .= '<span class="cb-reel vd-play" role="button" tabindex="0" data-video="' . esc_url( $f['url'] ) . '" aria-label="Play: ' . esc_attr( get_the_title( $id ) ) . '"><i></i></span>';
				if ( $f['secs'] ) {
					$h .= '<span class="vd-time">' . esc_html( floor( $f['secs'] / 60 ) . ':' . str_pad( (string) ( $f['secs'] % 60 ), 2, '0', STR_PAD_LEFT ) ) . '</span>';
				}
			}
			$h .= '</div><div class="ds-cap">' . ( $label ? '<span class="ds-meta">' . esc_html( $label ) . '</span>' : '' ) . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '<span class="ds-link">Watch &amp; read <span aria-hidden="true">→</span></span></div></a>';
		}
		return $h . '</div>';
	}

	/** Branding section: laptop cards with caption. */
	public function design_laptop( $posts, $columns = 3 ) {
		$h = '<div class="d-laptop cols-' . (int) $columns . '">';
		foreach ( array_values( $posts ) as $p ) {
			$id    = $p->ID;
			$desc  = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
			$n     = count( aswin_work_images( $id ) );
			$label = implode( ' · ', array_filter( array( get_post_meta( $id, '_aswin_role', true ), $this->meta_line( $id ) ) ) );
			$h    .= '<a class="psc-card lap-card reveal" href="' . esc_url( get_permalink( $id ) ) . '">' . $this->laptop_html( $p );
			$h    .= '<div class="ds-cap">' . ( $label ? '<span class="ds-meta">' . esc_html( $label ) . '</span>' : '' ) . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '<span class="ds-link">' . ( $n > 1 ? 'View ' . $n . ' slides' : 'View work' ) . ' <span aria-hidden="true">→</span></span></div></a>';
		}
		return $h . '</div>';
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
			$imgs  = aswin_work_images( $id );
			$films = array_values( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_aswin_videos', true ) ) ) ) );
			$multi = count( $imgs ) + count( $films ) > 1;
			$board = (bool) get_post_meta( $id, '_aswin_tagline', true );
			$h    .= '<a class="df-item reveal' . ( $media ? '' : ' no-img' ) . ( $board ? ' is-board' : ( $multi ? ' is-campaign' : '' ) ) . '" href="' . esc_url( get_permalink( $id ) ) . '">';
			if ( $board ) {
				// Card = scaled-down preview of the showcase page.
				$h .= '<div class="df-media df-board">' . $this->campaign_board( $p, true ) . '</div>';
				if ( $films ) {
					$h .= '<span class="df-watch" role="link" tabindex="0" data-go="' . esc_url( get_permalink( $id ) . '#films' ) . '"><i aria-hidden="true">▶</i> Watch the films</span>';
				}
			} elseif ( $multi ) {
				// The whole campaign inside the card: creatives + films, each shown whole on a blurred backdrop of itself.
				$size = 0 === $i ? 'large' : 'medium_large';
				$h   .= '<div class="df-media df-show" data-lap-slides>';
				$n    = 0;
				foreach ( $imgs as $img ) {
					$url = wp_get_attachment_image_url( $img, $size );
					$tag = wp_get_attachment_image( $img, $size, false, array( 'loading' => 'lazy', 'class' => 'df-fg' ) );
					if ( $n > 1 ) {
						$tag = str_replace( array( ' src=', ' srcset=' ), array( ' data-src=', ' data-srcset=' ), $tag );
					}
					$h .= '<div class="lap-slide' . ( 0 === $n ? ' is-on' : '' ) . '"><span class="df-bg"' . ( $n > 1 ? ' data-bg' : '' ) . ' style="background-image:url(' . esc_url( $url ) . ')"></span>' . $tag . '</div>';
					$n++;
				}
				foreach ( $films as $v ) {
					$src    = wp_get_attachment_url( $v );
					$poster = (int) get_post_meta( $v, '_aswin_poster', true );
					$purl   = $poster ? wp_get_attachment_image_url( $poster, 'medium_large' ) : '';
					if ( ! $src ) {
						continue;
					}
					$h .= '<div class="lap-slide is-film' . ( 0 === $n ? ' is-on' : '' ) . '"><span class="df-bg" style="' . ( $purl ? 'background-image:url(' . esc_url( $purl ) . ')' : '' ) . '"></span>'
						. '<video class="df-fg" data-film-src="' . esc_url( $src ) . '"' . ( $purl ? ' poster="' . esc_url( $purl ) . '"' : '' ) . ' muted loop playsinline preload="none"></video><span class="df-play" aria-hidden="true">▶ Film</span></div>';
					$n++;
				}
				$h .= '</div>';
				$bits = array();
				if ( $imgs ) {
					$bits[] = count( $imgs ) . ( 1 === count( $imgs ) ? ' creative' : ' creatives' );
				}
				if ( $films ) {
					$bits[] = count( $films ) . ( 1 === count( $films ) ? ' film' : ' films' );
				}
				$h .= '<span class="df-count">' . esc_html( implode( ' · ', $bits ) ) . '</span>';
				// Story-style progress bars (one per slide; film slides run longer).
				$h .= '<div class="lap-dots df-bars">';
				for ( $k = 0; $k < $n; $k++ ) {
					$h .= '<span role="button" tabindex="0" class="lap-dot' . ( $k >= count( $imgs ) ? ' is-filmbar' : '' ) . ( 0 === $k ? ' is-on' : '' ) . '" aria-label="Slide ' . ( $k + 1 ) . '"></span>';
				}
				$h .= '</div>';
				$tagline = get_post_meta( $id, '_aswin_tagline', true );
				if ( $tagline ) {
					$h .= '<span class="df-tagline" aria-hidden="true">' . esc_html( $tagline ) . '</span>';
				}
				if ( $films ) {
					// Not a nested <a>: main.js follows data-go.
					$h .= '<span class="df-watch" role="link" tabindex="0" data-go="' . esc_url( get_permalink( $id ) . '#films' ) . '"><i aria-hidden="true">▶</i> Watch the films</span>';
				}
			} else {
				$h .= '<div class="df-media">' . $media . '</div>';
			}
			$h    .= '<div class="df-cap">' . ( $meta ? '<span class="df-meta">' . esc_html( $meta ) . '</span>' : '' ) . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>' . ( $desc ? '<p>' . esc_html( $desc ) . '</p>' : '' ) . '</div>';
			$h    .= '<span class="df-arrow" aria-hidden="true">↗</span></a>';
		}
		return $h . '</div>';
	}

	/**
	 * Campaign showcase board (matches the showcase mockup): headline + leaf, laptop on a table,
	 * social series (3 tall tiles), two wide feature videos, podcast row (3 tiles).
	 * $mini = true renders a static preview for cards (no links/buttons inside).
	 */
	public function campaign_board( $p, $mini = false ) {
		$id       = $p->ID;
		$cover    = (int) get_post_thumbnail_id( $id );
		$series   = array_values( array_diff( aswin_work_images( $id ), array( $cover ) ) );
		$films    = array_values( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_aswin_videos', true ) ) ) ) );
		$feature  = array_values( array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_aswin_feature_videos', true ) ) ) ) );
		if ( ! $feature ) {
			$feature = array_slice( $films, 0, 2 ); // until dedicated feature videos are added
		}
		$headline = get_post_meta( $id, '_aswin_headline', true );
		$headline = $headline ? $headline : get_post_meta( $id, '_aswin_tagline', true );
		$s_label  = get_post_meta( $id, '_aswin_series_label', true );
		$f_label  = get_post_meta( $id, '_aswin_films_label', true );
		$desc     = has_excerpt( $id ) ? get_the_excerpt( $id ) : '';
		$size     = $mini ? 'medium_large' : 'large';

		$video_tile = function ( $v, $cls, $inner ) use ( $mini ) {
			$src = wp_get_attachment_url( $v );
			return $mini
				? '<span class="' . $cls . '">' . $inner . '</span>'
				: '<button type="button" class="' . $cls . '" data-video="' . esc_url( $src ) . '" aria-label="Play: ' . esc_attr( get_the_title( $v ) ) . '">' . $inner . '</button>';
		};
		$poster = function ( $v ) use ( $size ) {
			$pid = (int) get_post_meta( $v, '_aswin_poster', true );
			return $pid ? wp_get_attachment_image( $pid, $size, false, array( 'loading' => 'lazy', 'alt' => '' ) ) : '';
		};

		$h  = '<div class="cmp-board' . ( $mini ? ' is-mini' : ' is-page' ) . '">';
		// Scenery: bokeh, wooden table, leaves.
		$h .= '<span class="cb-bokeh" aria-hidden="true"></span><span class="cb-table" aria-hidden="true"></span>';
		foreach ( array( 'l1', 'l2', 'l3', 'l4', 'l5' ) as $l ) {
			$h .= '<svg class="cb-dleaf ' . $l . '" viewBox="0 0 100 60" aria-hidden="true"><path d="M4 56C14 22 46 4 96 4 90 40 58 60 4 56z"/></svg>';
		}

		// Intro.
		$h .= '<div class="cb-intro"><p class="cb-kicker">Campaign showcase</p>';
		$tag = $mini ? 'p' : 'h1';
		$h .= '<' . $tag . ' class="cb-title">' . esc_html( $headline ) . '</' . $tag . '>';
		$h .= '<svg class="cb-leaf" viewBox="0 0 120 110" aria-hidden="true"><path d="M60 104C40 70 44 30 86 6c14 34 6 76-26 98z" fill="#4aa52a"/><path d="M60 104C66 70 74 40 84 14" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round"/><path d="M56 104C34 96 10 74 8 44c30 4 46 30 48 60z" fill="#7cc843"/><path d="M56 104C44 84 30 66 14 50" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round"/></svg>';
		$h .= '<span class="cb-rule"></span>' . ( $desc ? '<p class="cb-desc">' . esc_html( $desc ) . '</p>' : '' ) . '</div>';

		// Laptop.
		if ( $cover ) {
			$h .= '<div class="cb-lid"><div class="cb-screen">' . wp_get_attachment_image( $cover, $size, false, array( 'loading' => $mini ? 'lazy' : 'eager', 'alt' => '' ) ) . '</div></div>';
			$h .= '<div class="cb-deck" aria-hidden="true"><span class="cb-keys"></span><span class="cb-pad"></span></div>';
		}

		// Social series.
		if ( $series ) {
			$h .= '<span class="cb-pill cb-pill-s">' . esc_html( $s_label ? $s_label : 'Social media series' ) . '</span><div class="cb-posts"' . ( $mini ? '' : ' id="series"' ) . '>';
			foreach ( array_slice( $series, 0, 3 ) as $img ) {
				$im = wp_get_attachment_image( $img, $size, false, array( 'loading' => 'lazy' ) );
				$h .= $mini ? '<span class="cb-post">' . $im . '</span>' : '<a class="cb-post" href="' . esc_url( wp_get_attachment_image_url( $img, 'full' ) ) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="board-' . (int) $id . '">' . $im . '</a>';
			}
			$h .= '</div>';
		}

		// Feature videos (two wide tiles).
		if ( $feature ) {
			$h .= '<div class="cb-feature"' . ( $mini ? '' : ' id="films"' ) . '>';
			foreach ( array_slice( $feature, 0, 2 ) as $k => $v ) {
				$inner = $poster( $v ) . '<span class="cb-ftxt"><b>' . esc_html( get_the_title( $v ) ) . '</b>' . ( 0 === $k ? '<i>Campaign video</i>' : '' ) . '</span><span class="cb-fplay"></span>';
				$h    .= $video_tile( $v, 'cb-ftile', $inner );
			}
			$h .= '</div>';
		}

		// Podcast row.
		if ( $films ) {
			$h .= '<span class="cb-pill cb-pill-f">' . esc_html( $f_label ? $f_label : 'Campaign films' ) . '</span><div class="cb-pods">';
			foreach ( array_slice( $films, 0, 3 ) as $v ) {
				$h .= $video_tile( $v, 'cb-pod', $poster( $v ) . '<span class="cb-play" aria-hidden="true"></span>' );
			}
			$h .= '</div>';
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
			case 'video':
				return $this->design_video( $posts, $o['columns'] );
			case 'laptop':
				return $this->design_laptop( $posts, $o['columns'] );
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
