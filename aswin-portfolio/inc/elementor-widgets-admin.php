<?php
/**
 * Elementor widgets fed from WP Admin: Works (per category or filterable) and Brands.
 * Rendering lives in inc/render.php (shared with the theme templates).
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

class Aswin_Work_Showcase_Widget extends Aswin_Widget {

	public function get_name() { return 'aswin-work-showcase'; }
	public function get_title() { return 'Works (from admin)'; }
	public function get_icon() { return 'eicon-filter'; }
	public function get_keywords() { return array( 'works', 'portfolio', 'filter', 'category', 'instagram', 'view more' ); }

	protected function register_controls() {
		$cats = array();
		foreach ( aswin_work_cats() as $t ) {
			$cats[ (string) $t->term_id ] = $t->name;
		}

		$this->start_controls_section( 'content', array( 'label' => 'Works' ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => 'Works are managed in <b>WP Admin → Works</b>. Categories (and whether each shows as a phone mockup) in <b>Works → Categories</b>.<br><a href="' . esc_url( admin_url( 'edit.php?post_type=aswin_work' ) ) . '" target="_blank">Open Works ↗</a>',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );
		$this->add_control( 'cats', array(
			'label'       => 'Categories to include',
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $cats,
			'label_block' => true,
			'description' => 'Pick one category for a category section. Leave empty for all.',
		) );
		$this->add_control( 'limit', array( 'label' => 'Number of works to show', 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => -1, 'description' => '-1 shows all.' ) );
		$this->add_control( 'columns', array( 'label' => 'Columns (desktop)', 'type' => Controls_Manager::SELECT, 'default' => '3', 'options' => array( '2' => '2', '3' => '3', '4' => '4' ) ) );
		$this->add_control( 'design', array(
			'label'       => 'Section design',
			'type'        => Controls_Manager::SELECT,
			'default'     => 'auto',
			'options'     => array(
				'auto'      => 'Automatic (category’s Display style)',
				'cards'     => 'Cards / phone mockups grid',
				'scene'     => 'Phone scene (phone + side posts mockup)',
				'showcase'  => 'Showcase cards (full image + caption)',
				'list'      => 'Article list (numbered rows)',
				'feature'   => 'Feature mosaic (big hero tile)',
				'newsprint' => 'Newspaper clippings',
				'case'      => 'Case-study rows (alternating)',
			),
			'description' => 'Automatic uses the design set in Works → Categories when one category is picked.',
		) );
		$this->add_control( 'mode', array( 'label' => 'Card layout', 'type' => Controls_Manager::SELECT, 'default' => 'grid', 'options' => array( 'grid' => 'Even grid (cropped thumbnails)', 'masonry' => 'Masonry (natural image heights)' ), 'condition' => array( 'design' => array( 'auto', 'cards' ) ) ) );

		$this->add_control( 'vm_heading', array( 'label' => 'View more button', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'view_more', array(
			'label'   => 'Show button',
			'type'    => Controls_Manager::SELECT,
			'default' => 'always',
			'options' => array( 'always' => 'Always', 'auto' => 'Only when there are more works', 'hide' => 'Hide' ),
		) );
		$this->add_control( 'view_more_label', array( 'label' => 'Button text', 'type' => Controls_Manager::TEXT, 'default' => 'View more', 'condition' => array( 'view_more!' => 'hide' ) ) );
		$this->add_control( 'view_more_url', array(
			'label'       => 'Custom link (optional)',
			'type'        => Controls_Manager::URL,
			'description' => 'Leave empty to link to the category page (or the All Work page when several categories are picked).',
			'condition'   => array( 'view_more!' => 'hide' ),
		) );

		$this->add_control( 'f_heading', array( 'label' => 'Filter tabs', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'show_filter', array( 'label' => 'Show filter tabs', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'show_all', array( 'label' => 'Show "All" tab', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'condition' => array( 'show_filter' => 'yes' ) ) );
		$this->add_control( 'all_label', array( 'label' => '"All" tab text', 'type' => Controls_Manager::TEXT, 'default' => 'All work', 'condition' => array( 'show_filter' => 'yes', 'show_all' => 'yes' ) ) );
		$this->add_control( 'default_tab', array( 'label' => 'Tab selected on load', 'type' => Controls_Manager::SELECT, 'default' => '*', 'options' => array( '*' => 'All' ) + $cats, 'condition' => array( 'show_filter' => 'yes' ) ) );
		$this->add_control( 'counts', array( 'label' => 'Show counts on tabs', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'condition' => array( 'show_filter' => 'yes' ) ) );
		$this->add_control( 'hide_empty', array( 'label' => 'Hide tabs with no works', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'condition' => array( 'show_filter' => 'yes' ) ) );

		$this->add_control( 'o_heading', array( 'label' => 'Display', 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'ig_ui', array( 'label' => 'Instagram UI in phone mockups', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'description' => 'Turn off if your uploads are already full phone screenshots.' ) );
		$this->add_control( 'ig_handle', array( 'label' => 'Default account handle', 'type' => Controls_Manager::TEXT, 'default' => 'brandname', 'condition' => array( 'ig_ui' => 'yes' ) ) );
		$this->add_control( 'chips', array( 'label' => 'Show category label on cards', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'empty_text', array( 'label' => 'Text when no works', 'type' => Controls_Manager::TEXT, 'default' => 'Samples available on request — just say hello.', 'label_block' => true ) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_control( 'tab_bg', array( 'label' => 'Active tab background', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .work-filter button.is-active' => 'background: {{VALUE}}; border-color: {{VALUE}};' ) ) );
		$this->add_control( 'tab_color', array( 'label' => 'Active tab text', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .work-filter button.is-active' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'phone_color', array( 'label' => 'Phone frame colour', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .phone' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'btn_color', array( 'label' => 'View more button colour', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .view-more .btn' => 'color: {{VALUE}}; box-shadow: inset 0 0 0 2px {{VALUE}};' ) ) );
		$this->style_text( 'title', 'Work title', '.work-meta h3, .phone-cap h3' );
		$this->style_text( 'desc', 'Work description', '.work-meta p, .phone-cap p' );
		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$picked = ! empty( $s['cats'] ) ? array_map( 'intval', (array) $s['cats'] ) : array();
		$terms  = aswin_work_cats( $picked );
		$limit  = (int) $s['limit'];
		$args   = array(
			'post_type'      => 'aswin_work',
			'post_status'    => 'publish',
			'posts_per_page' => $limit ? $limit : -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		);
		if ( $picked ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'aswin_work_cat', 'field' => 'term_id', 'terms' => $picked ) );
		}
		$q       = new WP_Query( $args );
		$posts   = $q->posts;
		$total   = (int) $q->found_posts;
		$is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();

		if ( ! $posts ) {
			$add = admin_url( 'post-new.php?post_type=aswin_work' );
			echo '<div class="work-empty"><strong>Fresh work loading…</strong>' . ( $is_edit ? 'Add works in <a href="' . esc_url( $add ) . '" target="_blank">WP Admin → Works → Add Work</a>' . ( 1 === count( $terms ) ? ' and tick “' . esc_html( $terms[0]->name ) . '”.' : '.' ) : esc_html( $s['empty_text'] ) ) . '</div>';
			return;
		}

		$filter = 'yes' === $s['show_filter'];
		$def    = '*';
		if ( $filter ) {
			foreach ( $terms as $t ) {
				if ( (string) $t->term_id === (string) $s['default_tab'] ) {
					$def = $t->slug;
				}
			}
		}

		$design = 'auto' === $s['design'] ? aswin_design_for( $terms ) : $s['design'];
		if ( $filter ) {
			$design = 'cards'; // tabs only make sense on the grid
		}
		echo '<div class="design-' . esc_attr( $design ) . '">';
		echo Aswin_Render::i()->works_grid( $posts, array( // phpcs:ignore
			'design'       => $design,
			'columns'      => (int) $s['columns'],
			'mode'         => $s['mode'],
			'ig_ui'        => 'yes' === $s['ig_ui'],
			'ig_handle'    => $s['ig_handle'],
			'chips'        => 'yes' === $s['chips'],
			'filter'       => $filter,
			'show_all'     => 'yes' === $s['show_all'],
			'all_label'    => $s['all_label'],
			'counts'       => 'yes' === $s['counts'],
			'hide_empty'   => 'yes' === $s['hide_empty'],
			'default_slug' => $def,
			'terms'        => $terms,
		) );
		echo '</div>';

		$vm = $s['view_more'];
		if ( 'hide' !== $vm && ( 'always' === $vm || $total > count( $posts ) ) ) {
			$url = ! empty( $s['view_more_url']['url'] ) ? $s['view_more_url']['url'] : ( 1 === count( $picked ) && $terms ? aswin_section_url( $terms[0] ) : get_post_type_archive_link( 'aswin_work' ) );
			if ( $url ) {
				$label = $s['view_more_label'] ? $s['view_more_label'] : 'View more';
				echo Aswin_Render::i()->view_more( $url, $label . ( $total > count( $posts ) ? ' (' . $total . ')' : '' ) ); // phpcs:ignore
			}
		}
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Brands_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-brands'; }
	public function get_title() { return 'Brands (from admin)'; }
	public function get_icon() { return 'eicon-logo'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Brands' ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => 'Brands are managed in <b>WP Admin → Brands</b> (name, logo, description, link, order).<br><a href="' . esc_url( admin_url( 'edit.php?post_type=aswin_brand' ) ) . '" target="_blank">Open Brands ↗</a>',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );
		$this->add_control( 'limit', array( 'label' => 'Number of brands to show', 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => -1, 'description' => '-1 shows all.' ) );
		$this->add_control( 'columns', array( 'label' => 'Columns (desktop)', 'type' => Controls_Manager::SELECT, 'default' => '3', 'options' => array( 'auto' => 'Auto', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ) ) );
		$this->add_control( 'link', array( 'label' => 'Clicking a logo opens', 'type' => Controls_Manager::SELECT, 'default' => 'page', 'options' => array( 'page' => 'Brand page', 'website' => 'Brand website', 'none' => 'Nothing' ) ) );
		$this->add_control( 'names', array( 'label' => 'Show brand name under logo', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'grayscale', array( 'label' => 'Greyscale until hover', 'type' => Controls_Manager::SWITCHER, 'default' => '' ) );
		$this->add_control( 'view_more', array( 'label' => 'View more button', 'type' => Controls_Manager::SELECT, 'default' => 'always', 'separator' => 'before', 'options' => array( 'always' => 'Always', 'auto' => 'Only when there are more brands', 'hide' => 'Hide' ) ) );
		$this->add_control( 'view_more_label', array( 'label' => 'Button text', 'type' => Controls_Manager::TEXT, 'default' => 'View all brands', 'condition' => array( 'view_more!' => 'hide' ) ) );
		$this->add_control( 'empty_text', array( 'label' => 'Text when no brands', 'type' => Controls_Manager::TEXT, 'default' => 'Brand list coming soon.', 'label_block' => true ) );
		$this->end_controls_section();

		$this->style_start();
		$this->add_control( 'hover_bg', array( 'label' => 'Hover background', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .logo-cell:hover' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'logo_h', array(
			'label'      => 'Logo max height',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 20, 'max' => 200 ) ),
			'selectors'  => array( '{{WRAPPER}} .logo-cell img' => 'max-height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->style_text( 'name', 'Brand name', '.logo-cell span, .logo-cell em' );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$limit = (int) $s['limit'];
		$q     = new WP_Query( array(
			'post_type'      => 'aswin_brand',
			'post_status'    => 'publish',
			'posts_per_page' => $limit ? $limit : -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		) );
		if ( ! $q->posts ) {
			$is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
			echo '<div class="work-empty"><strong>Brands coming soon…</strong>' . ( $is_edit ? 'Add brands in <a href="' . esc_url( admin_url( 'post-new.php?post_type=aswin_brand' ) ) . '" target="_blank">WP Admin → Brands → Add Brand</a>.' : esc_html( $s['empty_text'] ) ) . '</div>';
			return;
		}
		echo Aswin_Render::i()->brands_wall( $q->posts, array( // phpcs:ignore
			'columns'   => $s['columns'],
			'grayscale' => 'yes' === $s['grayscale'],
			'link'      => $s['link'],
			'names'     => 'yes' === $s['names'],
		) );
		$total = (int) $q->found_posts;
		if ( 'hide' !== $s['view_more'] && ( 'always' === $s['view_more'] || $total > count( $q->posts ) ) ) {
			echo Aswin_Render::i()->view_more( aswin_brands_url(), ( $s['view_more_label'] ? $s['view_more_label'] : 'View all brands' ) . ( $total > count( $q->posts ) ? ' (' . $total . ')' : '' ) ); // phpcs:ignore
		}
	}
}

/* ------------------------------------------------------------------ */

class Aswin_Section_Links_Widget extends Aswin_Widget {
	public function get_name() { return 'aswin-section-links'; }
	public function get_title() { return 'Section Links'; }
	public function get_icon() { return 'eicon-navigation-horizontal'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'Links' ) );
		$this->add_control( 'note', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => 'Links to every section page (Brands + each work category, in category order). The current page is highlighted.',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );
		$this->add_control( 'label', array( 'label' => 'Heading', 'type' => Controls_Manager::TEXT, 'default' => 'Explore other sections' ) );
		$this->add_control( 'home', array( 'label' => 'Include “Home” link', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'all', array( 'label' => 'Include “All work” link', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ) );
		$this->add_control( 'align', array( 'label' => 'Alignment', 'type' => Controls_Manager::CHOOSE, 'default' => 'left',
			'options'   => array( 'left' => array( 'title' => 'Left', 'icon' => 'eicon-text-align-left' ), 'center' => array( 'title' => 'Center', 'icon' => 'eicon-text-align-center' ) ),
			'selectors' => array( '{{WRAPPER}} .section-links' => 'text-align: {{VALUE}};', '{{WRAPPER}} .cat-pills' => 'justify-content: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->style_start();
		$this->style_text( 'label', 'Heading', '.section-links h2' );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$here  = untrailingslashit( (string) get_permalink() );
		$links = array();
		if ( 'yes' === $s['home'] ) {
			$links[ home_url( '/' ) ] = 'Home';
		}
		$links += aswin_section_links();
		if ( 'yes' === $s['all'] && ( $all = get_post_type_archive_link( 'aswin_work' ) ) ) {
			$links[ $all ] = 'All work';
		}
		echo '<nav class="section-links" aria-label="Sections">';
		if ( $s['label'] ) {
			echo '<h2>' . esc_html( $s['label'] ) . '</h2>';
		}
		echo '<div class="cat-pills">';
		foreach ( $links as $url => $label ) {
			$on = untrailingslashit( $url ) === $here;
			echo '<a href="' . esc_url( $url ) . '"' . ( $on ? ' class="is-active" aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		echo '</div></nav>';
	}
}
