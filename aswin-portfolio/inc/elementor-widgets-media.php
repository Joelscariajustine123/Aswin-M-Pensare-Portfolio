<?php
/**
 * Photo Collage widget — personal photos for the About section.
 * Requires inc/elementor-widgets.php (Aswin_Widget base) to be loaded first.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

class Aswin_Photo_Collage_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-photo-collage'; }
	public function get_title() { return 'Photo Collage'; }
	public function get_icon() { return 'eicon-photo-library'; }
	public function get_keywords() { return array( 'photo', 'image', 'about', 'portrait', 'polaroid', 'collage' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Photos' ) );

		$this->add_control( 'layout', array(
			'label'   => 'Layout',
			'type'    => Controls_Manager::SELECT,
			'default' => 'stack',
			'options' => array(
				'stack'  => 'Polaroid stack (up to 4, tilted)',
				'grid'   => 'Grid',
				'single' => 'Single portrait',
			),
		) );

		$r = new Repeater();
		$r->add_control( 'image', array( 'label' => 'Photo', 'type' => Controls_Manager::MEDIA ) );
		$r->add_control( 'caption', array( 'label' => 'Caption (optional)', 'type' => Controls_Manager::TEXT, 'default' => '', 'placeholder' => 'e.g. Somewhere, thinking.' ) );
		$this->add_control( 'photos', array(
			'label'       => 'Photos',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ caption || "Photo" }}}',
			'default'     => array(
				array( 'caption' => 'That’s me.' ),
				array( 'caption' => 'Mid-idea.' ),
				array( 'caption' => 'Off the clock.' ),
			),
		) );

		$this->add_control( 'polaroid', array( 'label' => 'White polaroid frame', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'tilt', array( 'label' => 'Tilt photos', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'lightbox', array( 'label' => 'Open full size on click', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'sticker', array( 'label' => 'Sticker text (optional)', 'type' => Controls_Manager::TEXT, 'default' => 'Hello!', 'description' => 'A round badge on the collage. Leave empty to hide.' ) );
		$this->add_control( 'grid_cols', array(
			'label'     => 'Grid columns',
			'type'      => Controls_Manager::SELECT,
			'default'   => '2',
			'options'   => array( '1' => '1', '2' => '2', '3' => '3' ),
			'condition' => array( 'layout' => 'grid' ),
		) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_responsive_control( 'max_w', array(
			'label'      => 'Max width',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 200, 'max' => 900 ), '%' => array( 'min' => 30, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .collage' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'frame_color', array( 'label' => 'Frame colour', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .collage.is-polaroid .ph-print' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'sticker_bg', array( 'label' => 'Sticker colour', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .collage-sticker' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'radius', array(
			'label'      => 'Photo corner radius',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .ph-print img, {{WRAPPER}} .ph-print .ph-empty' => 'border-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->style_text( 'cap', 'Caption', '.ph-print figcaption' );
		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$layout  = in_array( $s['layout'], array( 'stack', 'grid', 'single' ), true ) ? $s['layout'] : 'stack';
		$photos  = (array) $s['photos'];
		$preview = \Elementor\Plugin::$instance->editor->is_edit_mode() || current_user_can( 'edit_posts' );

		// Visitors never see empty placeholders.
		$has_img = array_filter( $photos, function ( $p ) {
			return ! empty( $p['image']['id'] ) || ( ! empty( $p['image']['url'] ) && false === strpos( $p['image']['url'], 'placeholder' ) );
		} );
		if ( ! $has_img && ! $preview ) {
			return;
		}
		if ( 'single' === $layout ) {
			$photos = array_slice( $photos, 0, 1 );
		} elseif ( 'stack' === $layout ) {
			$photos = array_slice( $photos, 0, 4 );
		}

		$cls = 'collage layout-' . $layout . ' n-' . count( $photos )
			. ( 'yes' === $s['polaroid'] ? ' is-polaroid' : '' )
			. ( 'yes' === $s['tilt'] ? ' is-tilted' : '' )
			. ( 'grid' === $layout ? ' cols-' . (int) $s['grid_cols'] : '' );

		echo '<div class="' . esc_attr( $cls ) . ' reveal">';
		foreach ( $photos as $p ) {
			$id   = (int) ( $p['image']['id'] ?? 0 );
			$url  = $p['image']['url'] ?? '';
			$real = $id || ( $url && false === strpos( $url, 'placeholder' ) );
			if ( ! $real && ! $preview ) {
				continue;
			}
			$img = '';
			if ( $id ) {
				$img = wp_get_attachment_image( $id, 'large', false, array( 'loading' => 'lazy', 'alt' => $p['caption'] ? $p['caption'] : 'Photo of Aswin' ) );
			} elseif ( $real ) {
				$img = '<img src="' . esc_url( $url ) . '" alt="" loading="lazy">';
			}
			$full = $id ? wp_get_attachment_image_url( $id, 'full' ) : $url;

			echo '<figure class="ph-print">';
			if ( $img && 'yes' === $s['lightbox'] ) {
				echo '<a href="' . esc_url( $full ) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="c-' . esc_attr( $this->get_id() ) . '"' . ( $p['caption'] ? ' data-elementor-lightbox-title="' . esc_attr( $p['caption'] ) . '"' : '' ) . '>' . $img . '</a>'; // phpcs:ignore
			} elseif ( $img ) {
				echo $img; // phpcs:ignore
			} else {
				echo '<div class="ph-empty"><span>Upload a photo<br><small>Click this widget → Photos</small></span></div>';
			}
			if ( $p['caption'] ) {
				echo '<figcaption>' . esc_html( $p['caption'] ) . '</figcaption>';
			}
			echo '</figure>';
		}
		if ( $s['sticker'] ) {
			echo '<span class="collage-sticker" aria-hidden="true">' . esc_html( $s['sticker'] ) . '</span>';
		}
		echo '</div>';
	}
}
