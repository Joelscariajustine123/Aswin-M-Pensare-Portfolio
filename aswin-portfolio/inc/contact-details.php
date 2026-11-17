<?php
/**
 * Contact Details — one admin page for email, phone, WhatsApp, socials, CV.
 * Used by the contact block, footer, Contact Details widget and [aswin_contact] shortcode.
 */

defined( 'ABSPATH' ) || exit;

/** Field definitions: key => [label, type, group, placeholder, help]. */
function aswin_contact_fields() {
	return array(
		'email'        => array( 'Email', 'email', 'contact', 'you@example.com', '' ),
		'phone'        => array( 'Phone', 'text', 'contact', '+91 98765 43210', 'Shown as a “Call” button and in the footer.' ),
		'whatsapp'     => array( 'WhatsApp number', 'text', 'contact', '+91 98765 43210', 'Include the country code. Leave empty to hide the WhatsApp button.' ),
		'whatsapp_msg' => array( 'WhatsApp opening message', 'text', 'contact', 'Hi Aswin, I saw your portfolio…', 'Pre-filled when someone taps the WhatsApp button (optional).' ),
		'location'     => array( 'Location', 'text', 'contact', 'Kochi, Kerala, India', '' ),
		'availability' => array( 'Availability note', 'text', 'contact', 'Open to freelance & full-time roles', 'A short line shown with your details (optional).' ),
		'linkedin'     => array( 'LinkedIn', 'url', 'social', 'https://www.linkedin.com/in/…', '' ),
		'instagram'    => array( 'Instagram', 'url', 'social', 'https://www.instagram.com/…', '' ),
		'behance'      => array( 'Behance', 'url', 'social', 'https://www.behance.net/…', '' ),
		'x'            => array( 'X (Twitter)', 'url', 'social', 'https://x.com/…', '' ),
		'facebook'     => array( 'Facebook', 'url', 'social', 'https://www.facebook.com/…', '' ),
		'youtube'      => array( 'YouTube', 'url', 'social', 'https://www.youtube.com/@…', '' ),
		'medium'       => array( 'Medium / Blog', 'url', 'social', 'https://medium.com/@…', '' ),
		'website'      => array( 'Other website', 'url', 'social', 'https://…', '' ),
		'cv'           => array( 'CV / Résumé (PDF)', 'media', 'cv', '', 'Adds a “Download CV” button to the contact block.' ),
	);
}

function aswin_contact( $key ) {
	$o = get_option( 'aswin_contact', array() );
	return is_array( $o ) && isset( $o[ $key ] ) ? $o[ $key ] : '';
}

/* ---------- Admin page ---------- */
add_action( 'admin_menu', function () {
	add_menu_page( 'Contact Details', 'Contact Details', 'manage_options', 'aswin-contact', 'aswin_contact_page', 'dashicons-id', 4 );
} );

add_action( 'admin_init', function () {
	register_setting( 'aswin_contact', 'aswin_contact', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = array();
			foreach ( aswin_contact_fields() as $k => $f ) {
				$v = isset( $in[ $k ] ) ? wp_unslash( $in[ $k ] ) : '';
				switch ( $f[1] ) {
					case 'email':
						$v = sanitize_email( $v );
						break;
					case 'url':
						$v = esc_url_raw( trim( $v ) );
						break;
					case 'media':
						$v = absint( $v ) ? (string) absint( $v ) : '';
						break;
					default:
						$v = sanitize_text_field( $v );
				}
				if ( '' !== $v ) {
					$out[ $k ] = $v;
				}
			}
			if ( function_exists( 'aswin_request_cdn_purge' ) ) {
				aswin_request_cdn_purge();
			}
			return $out;
		},
	) );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_aswin-contact' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'aswin-admin', get_template_directory_uri() . '/assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), ASWIN_VER, true );
	wp_enqueue_style( 'aswin-admin', get_template_directory_uri() . '/assets/admin.css', array(), ASWIN_VER );
} );

function aswin_contact_page() {
	$groups = array(
		'contact' => 'Contact',
		'social'  => 'Social profiles',
		'cv'      => 'CV / Résumé',
	);
	?>
	<div class="wrap aswin-settings">
		<h1>Contact Details</h1>
		<p class="aswin-tip">Fill these in once — they appear automatically in the <strong>contact section</strong> (homepage &amp; every section page), the <strong>footer</strong>, and anywhere you add the <strong>Contact Details</strong> widget in Elementor (or the <code>[aswin_contact]</code> shortcode). Leave a field empty to hide it.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'aswin_contact' ); ?>
			<?php foreach ( $groups as $g => $title ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					foreach ( aswin_contact_fields() as $k => $f ) :
						if ( $f[2] !== $g ) {
							continue;
						}
						$name = 'aswin_contact[' . $k . ']';
						$val  = aswin_contact( $k );
						?>
						<tr>
							<th scope="row"><label for="ac-<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $f[0] ); ?></label></th>
							<td>
								<?php if ( 'media' === $f[1] ) : ?>
									<?php aswin_media_field( $name, '', $val, 'application/pdf', false, $f[4] ); ?>
								<?php else : ?>
									<input class="regular-text" type="<?php echo 'url' === $f[1] ? 'url' : ( 'email' === $f[1] ? 'email' : 'text' ); ?>" id="ac-<?php echo esc_attr( $k ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $val ); ?>" placeholder="<?php echo esc_attr( $f[3] ); ?>">
									<?php if ( $f[4] ) : ?><p class="description"><?php echo esc_html( $f[4] ); ?></p><?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button( 'Save contact details' ); ?>
		</form>
	</div>
	<?php
}

/* ---------- Data helpers ---------- */

/** Direct contact actions: [key, label, display value, url, icon]. */
function aswin_contact_actions() {
	$out = array();
	if ( $e = aswin_contact( 'email' ) ) {
		$out[] = array( 'email', 'Email me', antispambot( $e ), 'mailto:' . antispambot( $e ), 'mail' );
	}
	if ( $w = aswin_contact( 'whatsapp' ) ) {
		$num   = preg_replace( '/\D+/', '', $w );
		$msg   = aswin_contact( 'whatsapp_msg' );
		$out[] = array( 'whatsapp', 'WhatsApp', $w, 'https://wa.me/' . $num . ( $msg ? '?text=' . rawurlencode( $msg ) : '' ), 'whatsapp' );
	}
	if ( $p = aswin_contact( 'phone' ) ) {
		$out[] = array( 'phone', 'Call', $p, 'tel:' . preg_replace( '/[^\d+]/', '', $p ), 'phone' );
	}
	return $out;
}

/** Social profiles: [key, label, url]. */
function aswin_contact_socials() {
	$out = array();
	foreach ( aswin_contact_fields() as $k => $f ) {
		if ( 'social' === $f[2] && ( $u = aswin_contact( $k ) ) ) {
			$out[] = array( $k, $f[0], $u );
		}
	}
	return $out;
}

function aswin_cv_url() {
	$id = (int) aswin_contact( 'cv' );
	return $id ? (string) wp_get_attachment_url( $id ) : '';
}

function aswin_icon( $name ) {
	$i = array(
		'mail'      => '<path d="M3.5 6.5h17v11h-17z"/><path d="m3.5 7 8.5 6.5L20.5 7"/>',
		'phone'     => '<path d="M6.6 3.5h3l1.5 4.2-2 1.3a12 12 0 0 0 5.9 5.9l1.3-2 4.2 1.5v3a1.8 1.8 0 0 1-2 1.8A16.6 16.6 0 0 1 4.8 5.5a1.8 1.8 0 0 1 1.8-2z"/>',
		'whatsapp'  => '<path d="M4 20l1.2-4.3A8.3 8.3 0 1 1 8.6 19z"/><path d="M9 8.6c.2 2.9 3.2 5.9 6.1 6.1l1-1.3-1.9-1.2-.8.7a5 5 0 0 1-2.2-2.2l.7-.8-1.2-1.9z"/>',
		'pin'       => '<path d="M12 21s-6.5-6.2-6.5-11a6.5 6.5 0 0 1 13 0c0 4.8-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
		'spark'     => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>',
		'download'  => '<path d="M12 4v11M7 10.5l5 5 5-5M5 20h14"/>',
		'linkedin'  => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10.5V16M8 7.8v.1M11.5 16v-3.2a2.2 2.2 0 0 1 4.4 0V16M11.5 10.5V16"/>',
		'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="3.8"/><circle cx="17" cy="7" r=".6"/>',
		'behance'   => '<path d="M3.5 7h5a2.5 2.5 0 0 1 0 5h-5zM3.5 12h5.5a2.5 2.5 0 0 1 0 5H3.5zM14.5 14h6a3 3 0 1 0-.9 2.2M15 8h5"/>',
		'x'         => '<path d="M4 4l16 16M20 4 4 20"/>',
		'facebook'  => '<path d="M14.5 21v-7.5h2.5l.5-3h-3V8.8c0-.9.4-1.5 1.6-1.5h1.5V4.6a19 19 0 0 0-2.3-.1c-2.4 0-3.8 1.4-3.8 4v2h-2.5v3h2.5V21"/>',
		'youtube'   => '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="m10.5 9.5 4 2.5-4 2.5z"/>',
		'medium'    => '<circle cx="7.5" cy="12" r="4.5"/><ellipse cx="16" cy="12" rx="2" ry="4.3"/><path d="M20.5 8v8"/>',
		'website'   => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.5 2.6 2.5 14.4 0 17M12 3.5c-2.5 2.6-2.5 14.4 0 17"/>',
	);
	return '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . ( $i[ $name ] ?? $i['website'] ) . '</svg>';
}

/* ---------- Render helpers ---------- */

/** Big action buttons (Email / WhatsApp / Call / CV). */
function aswin_contact_buttons_html( $with_cv = true ) {
	$h = '';
	foreach ( aswin_contact_actions() as $i => $a ) {
		$ext = 'whatsapp' === $a[0] ? ' target="_blank" rel="noopener"' : '';
		$h  .= '<a class="btn' . ( $i ? ' ghost' : '' ) . '" href="' . esc_url( $a[3] ) . '"' . $ext . '>' . aswin_icon( $a[4] ) . esc_html( $a[1] ) . '</a>';
	}
	if ( $with_cv && ( $cv = aswin_cv_url() ) ) {
		$h .= '<a class="btn ghost" href="' . esc_url( $cv ) . '" target="_blank" rel="noopener" download>' . aswin_icon( 'download' ) . 'Download CV</a>';
	}
	return $h;
}

/** Round social icon links. */
function aswin_socials_html( $class = 'socials' ) {
	$s = aswin_contact_socials();
	if ( ! $s ) {
		return '';
	}
	$h = '<div class="' . esc_attr( $class ) . '">';
	foreach ( $s as $x ) {
		$h .= '<a href="' . esc_url( $x[2] ) . '" target="_blank" rel="noopener me" aria-label="' . esc_attr( $x[1] ) . '" title="' . esc_attr( $x[1] ) . '">' . aswin_icon( $x[0] ) . '</a>';
	}
	return $h . '</div>';
}

/** Detail list (email, phone, whatsapp, location, availability) — label + value rows. */
function aswin_contact_list_html( $show = array( 'email', 'phone', 'whatsapp', 'location', 'availability' ) ) {
	$rows = array();
	foreach ( aswin_contact_actions() as $a ) {
		if ( in_array( $a[0], $show, true ) ) {
			$rows[] = array( $a[4], 'phone' === $a[0] ? 'Phone' : ( 'email' === $a[0] ? 'Email' : 'WhatsApp' ), $a[2], $a[3], 'whatsapp' === $a[0] );
		}
	}
	if ( in_array( 'location', $show, true ) && ( $l = aswin_contact( 'location' ) ) ) {
		$rows[] = array( 'pin', 'Based in', $l, '', false );
	}
	if ( in_array( 'availability', $show, true ) && ( $v = aswin_contact( 'availability' ) ) ) {
		$rows[] = array( 'spark', 'Availability', $v, '', false );
	}
	if ( ! $rows ) {
		return '';
	}
	$h = '<ul class="contact-list">';
	foreach ( $rows as $r ) {
		$val = $r[3] ? '<a href="' . esc_url( $r[3] ) . '"' . ( $r[4] ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $r[2] ) . '</a>' : esc_html( $r[2] );
		$h  .= '<li>' . aswin_icon( $r[0] ) . '<span><small>' . esc_html( $r[1] ) . '</small>' . $val . '</span></li>';
	}
	return $h . '</ul>';
}

function aswin_has_contact_details() {
	return (bool) ( aswin_contact_actions() || aswin_contact_socials() || aswin_contact( 'location' ) || aswin_contact( 'availability' ) || aswin_cv_url() );
}

/* ---------- Shortcode: [aswin_contact show="list|buttons|socials|all"] ---------- */
add_shortcode( 'aswin_contact', function ( $atts ) {
	$a    = shortcode_atts( array( 'show' => 'all' ), $atts );
	$show = $a['show'];
	$h    = '<div class="contact-block">';
	if ( in_array( $show, array( 'all', 'list' ), true ) ) {
		$h .= aswin_contact_list_html();
	}
	if ( in_array( $show, array( 'all', 'buttons' ), true ) && ( $b = aswin_contact_buttons_html() ) ) {
		$h .= '<div class="contact-links">' . $b . '</div>';
	}
	if ( in_array( $show, array( 'all', 'socials' ), true ) ) {
		$h .= aswin_socials_html();
	}
	return $h . '</div>';
} );

/* ---------- Person schema for search engines ---------- */
add_action( 'wp_head', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Person',
		'name'     => get_bloginfo( 'name' ),
		'jobTitle' => 'Copywriter, Content Writer & Creative Strategist',
		'url'      => home_url( '/' ),
	);
	if ( $e = aswin_contact( 'email' ) ) {
		$data['email'] = 'mailto:' . $e;
	}
	if ( $p = aswin_contact( 'phone' ) ) {
		$data['telephone'] = $p;
	}
	if ( $l = aswin_contact( 'location' ) ) {
		$data['address'] = array( '@type' => 'PostalAddress', 'addressLocality' => $l );
	}
	$same = wp_list_pluck( aswin_contact_socials(), 2 );
	if ( $same ) {
		$data['sameAs'] = array_values( $same );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>' . "\n";
} );
