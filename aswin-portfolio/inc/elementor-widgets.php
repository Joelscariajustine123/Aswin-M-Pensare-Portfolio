<?php
/**
 * Aswin Portfolio — custom Elementor widgets.
 * Loaded only when Elementor is active (see functions.php).
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

/** Shared helpers for all Aswin widgets. */
abstract class Aswin_Widget extends Widget_Base {

	public function get_categories() {
		return array( 'aswin' );
	}

	/** Escape text, *word* → <em>, newlines → <br>. */
	protected function em( $text ) {
		return nl2br( preg_replace( '/\*(.+?)\*/s', '<em>$1</em>', esc_html( (string) $text ) ) );
	}

	/** Colour + typography controls for one element, in the Style tab. */
	protected function style_text( $key, $label, $selector ) {
		$this->add_control( $key . '_color', array(
			'label'     => $label . ' colour',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => $key . '_typo',
			'label'    => $label . ' typography',
			'selector' => '{{WRAPPER}} ' . $selector,
		) );
	}

	protected function style_start( $label = 'Style' ) {
		$this->start_controls_section( 'section_style', array( 'label' => $label, 'tab' => Controls_Manager::TAB_STYLE ) );
	}

	protected function chapter( $num, $label ) {
		if ( '' === trim( $num . $label ) ) {
			return '';
		}
		return '<div class="chapter reveal">' . ( '' !== $num ? '<b>' . esc_html( $num ) . '</b> ' : '' ) . esc_html( $label ) . '</div>';
	}

	protected function chapter_controls( $num, $label ) {
		$this->add_control( 'chapter_num', array( 'label' => 'Chapter number', 'type' => Controls_Manager::TEXT, 'default' => $num ) );
		$this->add_control( 'chapter_label', array( 'label' => 'Chapter label', 'type' => Controls_Manager::TEXT, 'default' => $label, 'label_block' => true ) );
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Hero_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-hero'; }
	public function get_title() { return 'Hero Quote'; }
	public function get_icon() { return 'eicon-header'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->chapter_controls( '01', 'Welcome to my portfolio' );
		$this->add_control( 'quote', array(
			'label'       => 'Quote',
			'type'        => Controls_Manager::TEXTAREA,
			'default'     => 'Wonder is the beginning of *wisdom.*',
			'description' => 'Wrap words in *asterisks* for the italic accent.',
		) );
		$this->add_control( 'author', array( 'label' => 'Quote author', 'type' => Controls_Manager::TEXT, 'default' => 'Socrates' ) );
		$this->add_control( 'cursor', array( 'label' => 'Blinking cursor', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'intro', array( 'label' => 'Intro line', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Hi, I’m Aswin M. I get paid to think, play with words and occasionally stare at a blank screen until inspiration strikes.' ) );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => 'Role', 'type' => Controls_Manager::TEXT, 'default' => 'Role' ) );
		$this->add_control( 'roles', array(
			'label'       => 'Role tags',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array( array( 'text' => 'Copywriter' ), array( 'text' => 'Content Writer' ), array( 'text' => 'Creative Strategist' ) ),
		) );
		$this->end_controls_section();

		$this->style_start();
		$this->style_text( 'quote', 'Quote', '.hero-quote' );
		$this->add_control( 'accent_color', array(
			'label'     => 'Accent (italic) colour',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hero-quote em' => 'color: {{VALUE}};' ),
		) );
		$this->style_text( 'intro', 'Intro', '.hero-foot p' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<div class="hero">
			<div class="wrap">
				<?php echo $this->chapter( $s['chapter_num'], $s['chapter_label'] ); ?>
				<h1 class="hero-quote reveal"><?php echo $this->em( $s['quote'] ); ?><?php if ( 'yes' === $s['cursor'] ) : ?><span class="cursor" aria-hidden="true"></span><?php endif; ?></h1>
				<?php if ( $s['author'] ) : ?><p class="quote-by reveal">— <?php echo esc_html( $s['author'] ); ?></p><?php endif; ?>
				<div class="hero-foot reveal">
					<p><?php echo esc_html( $s['intro'] ); ?></p>
					<div class="roles">
						<?php foreach ( (array) $s['roles'] as $r ) : ?><span class="elementor-repeater-item-<?php echo esc_attr( $r['_id'] ); ?>"><?php echo esc_html( $r['text'] ); ?></span><?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Marquee_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-marquee'; }
	public function get_title() { return 'Scrolling Ticker'; }
	public function get_icon() { return 'eicon-animation-text'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => 'Text', 'type' => Controls_Manager::TEXT, 'default' => 'Word' ) );
		$this->add_control( 'items', array(
			'label'       => 'Words',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array( array( 'text' => 'Copywriting' ), array( 'text' => 'Content Writing' ), array( 'text' => 'Brand Storytelling' ) ),
		) );
		$this->add_control( 'speed', array( 'label' => 'Seconds per loop', 'type' => Controls_Manager::NUMBER, 'default' => 38, 'min' => 5, 'max' => 200 ) );
		$this->add_control( 'tilt', array( 'label' => 'Tilt (deg)', 'type' => Controls_Manager::NUMBER, 'default' => -1.5, 'step' => 0.5,
			'selectors' => array( '{{WRAPPER}} .marquee' => 'transform: rotate({{VALUE}}deg);' ) ) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_control( 'bg', array( 'label' => 'Background', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .marquee' => 'background: {{VALUE}};' ) ) );
		$this->style_text( 'text', 'Text', '.marquee-track span' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$speed = max( 5, (float) $s['speed'] );
		?>
		<div class="marquee" aria-hidden="true">
			<div class="marquee-track" style="animation-duration:<?php echo esc_attr( $speed ); ?>s">
				<?php for ( $i = 0; $i < 2; $i++ ) : foreach ( (array) $s['items'] as $it ) : ?><span><?php echo esc_html( $it['text'] ); ?></span><?php endforeach; endfor; ?>
			</div>
		</div>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Big_Heading_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-big-heading'; }
	public function get_title() { return 'Big Hello Heading'; }
	public function get_icon() { return 'eicon-heading'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->add_control( 'title', array( 'label' => 'Heading', 'type' => Controls_Manager::TEXT, 'default' => 'Hi, I’m Aswin', 'label_block' => true ) );
		$this->add_control( 'emoji', array( 'label' => 'Waving emoji', 'type' => Controls_Manager::TEXT, 'default' => '👋' ) );
		$this->add_control( 'subtitle', array( 'label' => 'Subtitle', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Copywriter. Content Writer. Professional blank-screen starer.' ) );
		$this->add_control( 'sticky', array( 'label' => 'Stick while scrolling (desktop)', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->style_start();
		$this->style_text( 'title', 'Heading', '.about-hi' );
		$this->style_text( 'sub', 'Subtitle', '.about-hi small' );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$words = explode( ' ', trim( $s['title'] ) );
		$last  = array_pop( $words );
		?>
		<h2 class="about-hi reveal<?php echo 'yes' === $s['sticky'] ? ' is-sticky' : ''; ?>">
			<?php echo esc_html( implode( ' ', $words ) ); ?>
			<span style="white-space:nowrap"><?php echo esc_html( $last ); ?><?php if ( $s['emoji'] ) : ?> <span class="wave" aria-hidden="true"><?php echo esc_html( $s['emoji'] ); ?></span><?php endif; ?></span>
			<?php if ( $s['subtitle'] ) : ?><small><?php echo esc_html( $s['subtitle'] ); ?></small><?php endif; ?>
		</h2>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Quote_Card_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-quote-card'; }
	public function get_title() { return 'Quote Card'; }
	public function get_icon() { return 'eicon-blockquote'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->add_control( 'text', array( 'label' => 'Quote', 'type' => Controls_Manager::TEXTAREA, 'rows' => 6, 'default' => 'Your quote here.' ) );
		$this->add_control( 'cite', array( 'label' => 'Caption (optional)', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'pill', array( 'label' => 'Show pill decoration', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_control( 'bg', array( 'label' => 'Card background', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .quote-card' => 'background: {{VALUE}};' ) ) );
		$this->style_text( 'text', 'Quote', '.quote-card p' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<blockquote class="quote-card reveal<?php echo 'yes' === $s['pill'] ? ' has-pill' : ''; ?>">
			<p><?php echo $this->em( $s['text'] ); ?></p>
			<?php if ( $s['cite'] ) : ?><cite><?php echo esc_html( $s['cite'] ); ?></cite><?php endif; ?>
		</blockquote>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Statement_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-statement'; }
	public function get_title() { return 'Bold Statement'; }
	public function get_icon() { return 'eicon-t-letter'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->add_control( 'text', array(
			'label'       => 'Statement',
			'type'        => Controls_Manager::TEXTAREA,
			'default'     => 'Because good content gets attention. *Great content earns a place in people’s minds.*',
			'description' => 'Wrap words in *asterisks* to highlight them.',
		) );
		$this->end_controls_section();

		$this->style_start();
		$this->style_text( 'text', 'Statement', '.statement' );
		$this->add_control( 'hl', array( 'label' => 'Highlight colour', 'type' => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .statement mark' => 'background: linear-gradient(transparent 58%, {{VALUE}} 58%);' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$html = nl2br( preg_replace( '/\*(.+?)\*/s', '<mark>$1</mark>', esc_html( $s['text'] ) ) );
		echo '<p class="statement reveal">' . $html . '</p>';
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Section_Heading_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-section-heading'; }
	public function get_title() { return 'Section Heading'; }
	public function get_icon() { return 'eicon-heading'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->chapter_controls( '02', 'Selected work' );
		$this->add_control( 'title', array( 'label' => 'Title', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => 'Section *title*', 'description' => 'Wrap words in *asterisks* for italic serif.' ) );
		$this->add_control( 'kicker', array( 'label' => 'Kicker line', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => '' ) );
		$this->add_control( 'tag', array( 'label' => 'HTML tag', 'type' => Controls_Manager::SELECT, 'default' => 'h2',
			'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'div' ) ) );
		$this->end_controls_section();

		$this->style_start();
		$this->style_text( 'chapter', 'Chapter label', '.chapter' );
		$this->style_text( 'title', 'Title', '.section-title' );
		$this->style_text( 'kicker', 'Kicker', '.section-kicker' );
		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$tag = in_array( $s['tag'], array( 'h1', 'h2', 'h3', 'div' ), true ) ? $s['tag'] : 'h2';
		?>
		<div class="section-head">
			<?php echo $this->chapter( $s['chapter_num'], $s['chapter_label'] ); ?>
			<<?php echo $tag; ?> class="section-title reveal"><?php echo $this->em( $s['title'] ); ?></<?php echo $tag; ?>>
			<?php if ( $s['kicker'] ) : ?><p class="section-kicker reveal"><?php echo esc_html( $s['kicker'] ); ?></p><?php endif; ?>
		</div>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Skill_Card_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-skill-card'; }
	public function get_title() { return 'Skill Card'; }
	public function get_icon() { return 'eicon-bullet-list'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->add_control( 'title', array( 'label' => 'Card title', 'type' => Controls_Manager::TEXT, 'default' => 'Skills' ) );
		$r = new Repeater();
		$r->add_control( 'text', array( 'label' => 'Skill', 'type' => Controls_Manager::TEXT, 'default' => 'Skill' ) );
		$this->add_control( 'items', array(
			'label'       => 'List',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array( array( 'text' => 'Skill one' ), array( 'text' => 'Skill two' ) ),
		) );
		$this->add_control( 'variant', array( 'label' => 'Card style', 'type' => Controls_Manager::SELECT, 'default' => 'outline',
			'options' => array( 'outline' => 'Outline (dark)', 'accent' => 'Accent (orange)', 'light' => 'Light' ) ) );
		$this->add_control( 'big_last', array( 'label' => 'Make last item big', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'count', array( 'label' => 'Show count', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_control( 'bg', array( 'label' => 'Card background', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .tool-card' => 'background: {{VALUE}}; border-color: {{VALUE}};' ) ) );
		$this->style_text( 'title', 'Title', '.tool-card h3' );
		$this->style_text( 'item', 'List item', '.tool-card li' );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = (array) $s['items'];
		$cls   = 'tool-card reveal is-' . sanitize_html_class( $s['variant'] ) . ( 'yes' === $s['big_last'] ? ' big-last' : '' );
		?>
		<div class="<?php echo esc_attr( $cls ); ?>">
			<h3><?php echo esc_html( $s['title'] ); ?><?php if ( 'yes' === $s['count'] ) : ?> <span><?php echo esc_html( str_pad( (string) count( $items ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php endif; ?></h3>
			<ul><?php foreach ( $items as $it ) : ?><li><?php echo esc_html( $it['text'] ); ?></li><?php endforeach; ?></ul>
		</div>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Gallery_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-gallery'; }
	public function get_title() { return 'Portfolio Gallery'; }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_keywords() { return array( 'portfolio', 'work', 'gallery', 'instagram', 'phone', 'brands', 'logos' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Items' ) );

		$this->add_control( 'layout', array(
			'label'   => 'Display style',
			'type'    => Controls_Manager::SELECT,
			'default' => 'cards',
			'options' => array(
				'phone'   => 'Phone mockup (social media)',
				'cards'   => 'Cards',
				'wide'    => 'Wide (2 large)',
				'masonry' => 'Masonry (natural height)',
				'logos'   => 'Logo wall (brands)',
			),
		) );
		$this->add_control( 'columns', array(
			'label'   => 'Columns (desktop)',
			'type'    => Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => array( 'auto' => 'Auto', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
		) );
		$this->add_control( 'ig_ui', array(
			'label'       => 'Instagram-style UI in phone',
			'type'        => Controls_Manager::SWITCHER,
			'default'     => 'yes',
			'description' => 'Turn off if your uploads are already full phone screenshots.',
			'condition'   => array( 'layout' => 'phone' ),
		) );
		$this->add_control( 'ig_handle', array(
			'label'     => 'Default account handle',
			'type'      => Controls_Manager::TEXT,
			'default'   => 'brandname',
			'condition' => array( 'layout' => 'phone', 'ig_ui' => 'yes' ),
		) );
		$this->add_control( 'lightbox', array(
			'label'       => 'Open images in lightbox',
			'type'        => Controls_Manager::SWITCHER,
			'default'     => 'yes',
			'description' => 'Used when an item has no link or file.',
		) );

		$r = new Repeater();
		$r->add_control( 'media', array(
			'label'       => 'Image or video',
			'type'        => Controls_Manager::MEDIA,
			'media_types' => array( 'image', 'video' ),
		) );
		$r->add_control( 'title', array( 'label' => 'Title', 'type' => Controls_Manager::TEXT, 'default' => 'Project title', 'label_block' => true ) );
		$r->add_control( 'caption', array( 'label' => 'Short description', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => '' ) );
		$r->add_control( 'link', array( 'label' => 'Link (optional)', 'type' => Controls_Manager::URL, 'placeholder' => 'https://' ) );
		$r->add_control( 'file', array(
			'label'       => 'Attach file — PDF (optional)',
			'type'        => Controls_Manager::MEDIA,
			'media_types' => array( 'application/pdf' ),
			'description' => 'e.g. an article or press release. Card opens the file.',
		) );
		$r->add_control( 'ig_heading', array( 'label' => 'Phone mockup only', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'ig_handle', array( 'label' => 'Account handle', 'type' => Controls_Manager::TEXT, 'placeholder' => 'Uses default' ) );
		$r->add_control( 'ig_caption', array( 'label' => 'Post caption (#hashtags and @mentions turn blue)', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4 ) );
		$r->add_control( 'ig_likes', array( 'label' => 'Liked by', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$r->add_control( 'ig_date', array( 'label' => 'Date', 'type' => Controls_Manager::TEXT, 'default' => '' ) );

		$this->add_control( 'items', array(
			'label'       => 'Work items',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(),
		) );
		$this->add_control( 'empty_text', array(
			'label'     => 'Text when empty',
			'type'      => Controls_Manager::TEXT,
			'default'   => 'Samples available on request — just say hello.',
			'separator' => 'before',
			'label_block' => true,
		) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_responsive_control( 'gap', array(
			'label'      => 'Gap',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'  => array( '{{WRAPPER}} .work-grid' => 'gap: {{SIZE}}{{UNIT}}; column-gap: {{SIZE}}{{UNIT}};', '{{WRAPPER}} .layout-masonry .work-card' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'phone_color', array( 'label' => 'Phone frame colour', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .phone' => 'background: {{VALUE}};' ), 'condition' => array( 'layout' => 'phone' ) ) );
		$this->style_text( 'title', 'Item title', '.work-meta h3, .phone-cap h3' );
		$this->style_text( 'caption', 'Item description', '.work-meta p, .phone-cap p' );
		$this->end_controls_section();
	}

	/** Media HTML (image or video). */
	private function media_html( $media, $size ) {
		$id  = isset( $media['id'] ) ? (int) $media['id'] : 0;
		$url = isset( $media['url'] ) ? $media['url'] : '';
		if ( ! $url && ! $id ) {
			return '';
		}
		$mime = $id ? (string) get_post_mime_type( $id ) : '';
		if ( 0 === strpos( $mime, 'video' ) || preg_match( '/\.(mp4|webm|mov|m4v)$/i', $url ) ) {
			$src = $id ? wp_get_attachment_url( $id ) : $url;
			return '<video src="' . esc_url( $src ) . '" autoplay muted loop playsinline preload="metadata"></video>';
		}
		if ( $id && wp_attachment_is_image( $id ) ) {
			return wp_get_attachment_image( $id, $size, false, array( 'loading' => 'lazy' ) );
		}
		return '<img src="' . esc_url( $url ) . '" alt="" loading="lazy">';
	}

	private function is_video( $media ) {
		$id = isset( $media['id'] ) ? (int) $media['id'] : 0;
		return ( $id && 0 === strpos( (string) get_post_mime_type( $id ), 'video' ) ) || ( ! empty( $media['url'] ) && preg_match( '/\.(mp4|webm|mov|m4v)$/i', $media['url'] ) );
	}

	/** Opening <a>/<div> tag for an item. */
	private function open_tag( $it, $class, $lightbox ) {
		$attr = '';
		$tag  = 'div';
		if ( ! empty( $it['link']['url'] ) ) {
			$tag   = 'a';
			$attr .= ' href="' . esc_url( $it['link']['url'] ) . '"';
			if ( ! empty( $it['link']['is_external'] ) ) {
				$attr .= ' target="_blank"';
			}
			$attr .= ! empty( $it['link']['nofollow'] ) ? ' rel="nofollow noopener"' : ' rel="noopener"';
		} elseif ( ! empty( $it['file']['url'] ) ) {
			$tag   = 'a';
			$attr .= ' href="' . esc_url( $it['file']['url'] ) . '" target="_blank" rel="noopener"';
		} elseif ( $lightbox && ! empty( $it['media']['url'] ) && ! $this->is_video( $it['media'] ) ) {
			$tag   = 'a';
			$full  = ! empty( $it['media']['id'] ) ? wp_get_attachment_image_url( (int) $it['media']['id'], 'full' ) : $it['media']['url'];
			$attr .= ' href="' . esc_url( $full ) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="g-' . esc_attr( $this->get_id() ) . '" data-elementor-lightbox-title="' . esc_attr( $it['title'] ) . '"';
		}
		return array( '<' . $tag . ' class="' . esc_attr( $class ) . '"' . $attr . '>', '</' . $tag . '>' );
	}

	private function ig_caption( $text ) {
		$html = esc_html( (string) $text );
		$html = preg_replace( '/(^|\s)([#@][\p{L}\p{N}_.]+)/u', '$1<span class="tag">$2</span>', $html );
		return nl2br( $html );
	}

	private function icon( $name ) {
		$p = array(
			'heart'    => '<path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 8 3.3 4.5 6.8 4.5c2 0 3.4 1.1 4.2 2.5.9-1.4 2.3-2.5 4.2-2.5 3.5 0 5.4 3.5 4.1 6.8-1.8 4.6-7.3 9.2-7.3 9.2z"/>',
			'comment'  => '<path d="M20.5 11.6a8.4 8.4 0 0 1-12.2 7.5L3.5 20.5l1.4-4.6A8.4 8.4 0 1 1 20.5 11.6z"/>',
			'send'     => '<path d="M21 3 10 13.5M21 3l-6.5 18-4-7.5L3 9.5z"/>',
			'save'     => '<path d="M19 21l-7-5-7 5V4.5A1.5 1.5 0 0 1 6.5 3h11A1.5 1.5 0 0 1 19 4.5z"/>',
			'home'     => '<path d="M3.5 10.5 12 3.5l8.5 7V20a.5.5 0 0 1-.5.5h-5v-6h-6v6H4a.5.5 0 0 1-.5-.5z"/>',
			'search'   => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20.5 20.5-5.2-5.2"/>',
			'plus'     => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M12 8v8M8 12h8"/>',
			'reels'    => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M3.5 8.5h17M9 3.5l2.5 5M14.5 3.5l2.5 5M10.5 11.5v5l4-2.5z"/>',
			'back'     => '<path d="M15 4.5 7.5 12l7.5 7.5"/>',
			'more'     => '<circle cx="5.5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="18.5" cy="12" r="1.2"/>',
		);
		return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $p[ $name ] . '</svg>';
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$items    = (array) $s['items'];
		$layout   = in_array( $s['layout'], array( 'phone', 'cards', 'wide', 'masonry', 'logos' ), true ) ? $s['layout'] : 'cards';
		$lightbox = 'yes' === $s['lightbox'];
		$cols     = 'auto' === $s['columns'] ? '' : ' cols-' . (int) $s['columns'];
		$is_edit  = \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $items ) {
			echo '<div class="work-empty reveal"><strong>Fresh work loading…</strong>' . ( $is_edit ? 'Click this widget, then use <b>Add Item</b> in the left panel to upload images, videos or PDFs.' : esc_html( $s['empty_text'] ) ) . '</div>';
			return;
		}

		echo '<div class="layout-' . esc_attr( $layout ) . '">';

		if ( 'logos' === $layout ) {
			echo '<div class="logo-wall' . esc_attr( $cols ) . ' reveal">';
			foreach ( $items as $it ) {
				list( $open, $close ) = $this->open_tag( array( 'link' => $it['link'] ?? array(), 'file' => $it['file'] ?? array(), 'media' => array(), 'title' => $it['title'] ), 'logo-cell', false );
				echo $open;
				$m = $this->media_html( $it['media'] ?? array(), 'medium' );
				echo $m ? $m : '<span>' . esc_html( $it['title'] ) . '</span>';
				echo $close;
			}
			echo '</div></div>';
			return;
		}

		if ( 'phone' === $layout ) {
			$ui = 'yes' === $s['ig_ui'];
			echo '<div class="phone-grid' . esc_attr( $cols ) . '">';
			foreach ( $items as $it ) {
				$handle = $it['ig_handle'] ? $it['ig_handle'] : $s['ig_handle'];
				$media  = $this->media_html( $it['media'] ?? array(), 'large' );
				list( $open, $close ) = $this->open_tag( $it, 'phone-link', $lightbox );
				echo '<figure class="phone-item reveal">' . $open . '<div class="phone"><div class="phone-screen' . ( $ui ? ' has-ui' : '' ) . '"><span class="phone-island"></span>';
				if ( $ui ) {
					echo '<div class="ph-status"><b>9:41</b><span class="ph-sys"><i></i><i></i><i></i></span></div>';
					echo '<div class="ph-bar">' . $this->icon( 'back' ) . '<b>Posts</b><span></span></div>';
					echo '<div class="ph-head"><span class="ph-avatar"></span><b>' . esc_html( $handle ) . '</b>' . $this->icon( 'more' ) . '</div>';
				}
				echo '<div class="ph-media">' . ( $media ? $media : '<div class="ph-ph">' . esc_html( $it['title'] ) . '</div>' ) . '</div>';
				if ( $ui ) {
					echo '<div class="ph-actions">' . $this->icon( 'heart' ) . $this->icon( 'comment' ) . $this->icon( 'send' ) . '<span></span>' . $this->icon( 'save' ) . '</div>';
					echo '<div class="ph-text">';
					if ( $it['ig_likes'] ) {
						echo '<p class="ph-likes">Liked by <b>' . esc_html( $it['ig_likes'] ) . '</b> and <b>others</b></p>';
					}
					if ( $it['ig_caption'] ) {
						echo '<p><b>' . esc_html( $handle ) . '</b> ' . $this->ig_caption( $it['ig_caption'] ) . '</p>';
					}
					if ( $it['ig_date'] ) {
						echo '<p class="ph-date">' . esc_html( $it['ig_date'] ) . '</p>';
					}
					echo '</div><div class="ph-nav">' . $this->icon( 'home' ) . $this->icon( 'search' ) . $this->icon( 'plus' ) . $this->icon( 'reels' ) . '<span class="ph-avatar"></span></div>';
				}
				echo '</div></div>' . $close;
				if ( $it['title'] || $it['caption'] ) {
					echo '<figcaption class="phone-cap">' . ( $it['title'] ? '<h3>' . esc_html( $it['title'] ) . '</h3>' : '' ) . ( $it['caption'] ? '<p>' . esc_html( $it['caption'] ) . '</p>' : '' ) . '</figcaption>';
				}
				echo '</figure>';
			}
			echo '</div></div>';
			return;
		}

		$size = 'wide' === $layout ? 'aswin-wide' : ( 'masonry' === $layout ? 'aswin-free' : 'aswin-card' );
		echo '<div class="work-grid' . esc_attr( $cols ) . '">';
		foreach ( $items as $it ) {
			list( $open, $close ) = $this->open_tag( $it, 'work-card reveal', $lightbox );
			$media = $this->media_html( $it['media'] ?? array(), $size );
			echo $open;
			echo $media ? '<div class="work-thumb">' . $media . '</div>' : '<div class="work-thumb no-img">' . esc_html( $it['title'] ) . '</div>';
			echo '<div class="work-meta"><h3>' . esc_html( $it['title'] ) . '</h3>';
			if ( $it['caption'] ) {
				echo '<p>' . esc_html( $it['caption'] ) . '</p>';
			}
			if ( ! empty( $it['file']['url'] ) ) {
				echo '<span class="badge">PDF ↗</span>';
			}
			echo '<span class="arrow" aria-hidden="true">↗</span></div>';
			echo $close;
		}
		echo '</div></div>';
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Contact_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-contact'; }
	public function get_title() { return 'Contact Call-to-action'; }
	public function get_icon() { return 'eicon-mail'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Content' ) );
		$this->chapter_controls( '09', 'The last word' );
		$this->add_control( 'photo', array( 'label' => 'Small photo (optional)', 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'photo_caption', array( 'label' => 'Photo caption', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'title', array( 'label' => 'Heading', 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => 'Got a story? *Let’s tell it.*' ) );
		$this->add_control( 'text', array( 'label' => 'Text', 'type' => Controls_Manager::TEXTAREA, 'default' => 'Every idea has a story worth telling. If you’ve got one that needs the right words, I’d love to hear it.' ) );
		$r = new Repeater();
		$r->add_control( 'label', array( 'label' => 'Button text', 'type' => Controls_Manager::TEXT, 'default' => 'Email me' ) );
		$r->add_control( 'url', array( 'label' => 'Link', 'type' => Controls_Manager::URL, 'placeholder' => 'mailto:you@example.com or https://…' ) );
		$r->add_control( 'style', array( 'label' => 'Style', 'type' => Controls_Manager::SELECT, 'default' => 'solid', 'options' => array( 'solid' => 'Solid', 'ghost' => 'Outline' ) ) );
		$this->add_control( 'buttons', array(
			'label'       => 'Buttons',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ label }}}',
			'default'     => array(),
			'description' => 'Extra buttons (optional). Email / WhatsApp / Call / CV come from WP Admin → Contact Details.',
		) );
		$this->add_control( 'use_details', array( 'label' => 'Show buttons from Contact Details', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'show_socials', array( 'label' => 'Show social icons', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'show_meta', array( 'label' => 'Show location & availability', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'details_note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => '<a href="' . esc_url( admin_url( 'admin.php?page=aswin-contact' ) ) . '" target="_blank">Edit Contact Details ↗</a>',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );
		$this->end_controls_section();

		$this->style_start();
		$this->style_text( 'title', 'Heading', '.contact-title' );
		$this->style_text( 'text', 'Text', '.contact-text' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		?>
		<div class="contact-inner">
			<?php if ( ! empty( $s['photo']['url'] ) ) : ?>
				<figure class="contact-photo"><?php echo ! empty( $s['photo']['id'] ) ? wp_get_attachment_image( (int) $s['photo']['id'], 'medium', false, array( 'loading' => 'lazy', 'alt' => 'Aswin M' ) ) : '<img src="' . esc_url( $s['photo']['url'] ) . '" alt="Aswin M" loading="lazy">'; ?><?php if ( ! empty( $s['photo_caption'] ) ) : ?><figcaption><?php echo esc_html( $s['photo_caption'] ); ?></figcaption><?php endif; ?></figure>
			<?php endif; ?>
			<?php echo $this->chapter( $s['chapter_num'], $s['chapter_label'] ); ?>
			<h2 class="contact-title reveal"><?php echo $this->em( $s['title'] ); ?></h2>
			<?php if ( $s['text'] ) : ?><p class="contact-text reveal"><?php echo esc_html( $s['text'] ); ?></p><?php endif; ?>
			<?php
			$auto = ( 'yes' === $s['use_details'] && function_exists( 'aswin_contact_buttons_html' ) ) ? aswin_contact_buttons_html() : '';
			if ( ! empty( $s['buttons'] ) || $auto ) :
				?>
				<div class="contact-links reveal">
					<?php echo $auto; // phpcs:ignore ?>
					<?php foreach ( (array) $s['buttons'] as $b ) :
						if ( empty( $b['url']['url'] ) ) {
							continue;
						}
						$this->add_link_attributes( 'btn-' . $b['_id'], $b['url'] );
						?>
						<a class="btn<?php echo 'ghost' === $b['style'] ? ' ghost' : ''; ?>" <?php $this->print_render_attribute_string( 'btn-' . $b['_id'] ); ?>><?php echo esc_html( $b['label'] ); ?> <span aria-hidden="true">→</span></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php
			if ( function_exists( 'aswin_contact' ) ) {
				$meta = array();
				if ( 'yes' === $s['show_meta'] ) {
					if ( $l = aswin_contact( 'location' ) ) {
						$meta[] = aswin_icon( 'pin' ) . esc_html( $l );
					}
					if ( $v = aswin_contact( 'availability' ) ) {
						$meta[] = aswin_icon( 'spark' ) . esc_html( $v );
					}
				}
				if ( $meta ) {
					echo '<p class="contact-meta reveal"><span>' . implode( '</span><span>', $meta ) . '</span></p>'; // phpcs:ignore
				}
				if ( 'yes' === $s['show_socials'] ) {
					echo aswin_socials_html( 'socials reveal' ); // phpcs:ignore
				}
				if ( ! aswin_has_contact_details() && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					echo '<p class="contact-text">Add your email, phone and socials in <a href="' . esc_url( admin_url( 'admin.php?page=aswin-contact' ) ) . '" target="_blank">WP Admin → Contact Details</a> — they’ll appear here.</p>';
				}
			}
			?>
		</div>
		<?php
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Contact_Details_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-contact-details'; }
	public function get_title() { return 'Contact Details'; }
	public function get_icon() { return 'eicon-contact'; }
	public function get_keywords() { return array( 'contact', 'email', 'phone', 'whatsapp', 'social', 'address' ); }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Contact Details' ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => 'Details come from <b>WP Admin → Contact Details</b>.<br><a href="' . esc_url( admin_url( 'admin.php?page=aswin-contact' ) ) . '" target="_blank">Edit Contact Details ↗</a>',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );
		$this->add_control( 'heading', array( 'label' => 'Heading (optional)', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'show', array(
			'label'    => 'Show',
			'type'     => Controls_Manager::SELECT2,
			'multiple' => true,
			'default'  => array( 'email', 'phone', 'whatsapp', 'location', 'availability' ),
			'options'  => array( 'email' => 'Email', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'location' => 'Location', 'availability' => 'Availability' ),
		) );
		$this->add_control( 'layout', array( 'label' => 'Layout', 'type' => Controls_Manager::SELECT, 'default' => 'cards', 'options' => array( 'cards' => 'Cards grid', 'list' => 'Simple list' ) ) );
		$this->add_control( 'buttons', array( 'label' => 'Show action buttons', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'socials', array( 'label' => 'Show social icons', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_control( 'card_bg', array( 'label' => 'Card background', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .contact-list.is-cards li' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'icon_color', array( 'label' => 'Icon colour', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .contact-list .ic' => 'color: {{VALUE}};' ) ) );
		$this->style_text( 'value', 'Value text', '.contact-list span' );
		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		if ( ! function_exists( 'aswin_contact_list_html' ) ) {
			return;
		}
		$list = aswin_contact_list_html( (array) $s['show'] );
		if ( 'cards' === $s['layout'] ) {
			$list = str_replace( 'class="contact-list"', 'class="contact-list is-cards"', $list );
		}
		$html = $list;
		if ( 'yes' === $s['buttons'] && ( $b = aswin_contact_buttons_html() ) ) {
			$html .= '<div class="contact-links">' . $b . '</div>';
		}
		if ( 'yes' === $s['socials'] ) {
			$html .= aswin_socials_html();
		}
		if ( ! trim( wp_strip_all_tags( $html, true ) ) && false === strpos( $html, '<svg' ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="work-empty"><strong>No contact details yet</strong>Add them in <a href="' . esc_url( admin_url( 'admin.php?page=aswin-contact' ) ) . '" target="_blank">WP Admin → Contact Details</a>.</div>';
			}
			return;
		}
		echo '<div class="contact-block reveal">' . ( $s['heading'] ? '<h3 class="contact-block-title">' . esc_html( $s['heading'] ) . '</h3>' : '' ) . $html . '</div>'; // phpcs:ignore
	}
}

