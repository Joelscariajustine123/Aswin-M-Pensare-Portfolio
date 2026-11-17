<?php
/**
 * One-time setup: builds the Elementor homepage for the Aswin portfolio.
 * Run on the server with:  wp eval-file build-home.php
 */

if ( ! did_action( 'elementor/loaded' ) ) {
	WP_CLI::error( 'Elementor is not active.' );
}

function a_id() {
	return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
}
function a_w( $type, $settings = array(), $css = '' ) {
	if ( $css ) {
		$settings['_css_classes'] = $css;
	}
	return array( 'id' => a_id(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => array() );
}
function a_c( $settings, $children, $inner = false ) {
	return array( 'id' => a_id(), 'elType' => 'container', 'isInner' => $inner, 'settings' => $settings, 'elements' => $children );
}
function a_items( $rows ) {
	return array_map( function ( $r ) {
		return ( is_string( $r ) ? array( 'text' => $r ) : $r ) + array( '_id' => a_id() );
	}, $rows );
}
function a_text( $html, $css = '' ) {
	return a_w( 'text-editor', array( 'editor' => $html ), $css );
}
function a_section( $id, $classes, $children, $bg = '' ) {
	$s = array( 'content_width' => 'boxed', 'css_classes' => $classes, '_element_id' => $id, 'flex_direction' => 'column' );
	if ( $bg ) {
		$s['background_background'] = 'classic';
		$s['background_color']      = $bg;
	}
	return a_c( $s, $children );
}

$tech     = array( 'Copywriting', 'Content Writing', 'Brand Storytelling', 'Social Media Content', 'Scriptwriting', 'Research & Fact-checking', 'Content Editing' );
$creative = array( 'Creative Ideation', 'Campaign Conceptualisation', 'Brand Positioning', 'Brand Strategy', 'Communication Strategy', 'Audience Understanding', 'Brand Storytelling', 'Brand Voice & Tone Development' );
$off      = array( 'Observing the little things', 'Exploring new ideas', 'Reading between the lines', 'Finding stories in everyday life', 'And, of course, BREATHING!' );

$data = array();

/* 01 Hero */
$data[] = a_c( array( 'content_width' => 'full', 'css_classes' => 'hero-sec', '_element_id' => 'welcome', 'flex_direction' => 'column' ), array(
	a_w( 'aswin-hero', array(
		'chapter_num'   => '01',
		'chapter_label' => 'Welcome to my portfolio',
		'quote'         => 'Wonder is the beginning of *wisdom.*',
		'author'        => 'Socrates',
		'cursor'        => 'yes',
		'intro'         => 'Hi, I’m Aswin M. I get paid to think, play with words and occasionally stare at a blank screen until inspiration strikes.',
		'roles'         => a_items( array( 'Copywriter', 'Content Writer', 'Creative Strategist' ) ),
	) ),
	a_w( 'aswin-marquee', array( 'items' => a_items( array_values( array_unique( array_merge( $tech, $creative ) ) ) ), 'speed' => 38, 'tilt' => -1.5 ) ),
) );

/* About */
$data[] = a_c( array( 'content_width' => 'boxed', 'css_classes' => 'section about-row', '_element_id' => 'about', 'flex_direction' => 'row', 'flex_direction_mobile' => 'column' ), array(
	a_c( array( 'content_width' => 'full', 'css_classes' => 'about-left', 'width' => array( 'unit' => '%', 'size' => 38 ), 'width_mobile' => array( 'unit' => '%', 'size' => 100 ) ), array(
		a_w( 'aswin-big-heading', array( 'title' => 'Hi, I’m Aswin', 'emoji' => '👋', 'subtitle' => 'Copywriter. Content Writer. Professional blank-screen starer.', 'sticky' => 'yes' ) ),
	), true ),
	a_c( array( 'content_width' => 'full', 'css_classes' => 'about-right', 'width' => array( 'unit' => '%', 'size' => 58 ), 'width_mobile' => array( 'unit' => '%', 'size' => 100 ) ), array(
		a_text( '<p>I’m a Copywriter, Content Writer and Creative Strategist. In simple terms, I get paid to think, play with words and occasionally stare at a blank screen until inspiration strikes.</p>', 'intro-lead' ),
		a_text( '<p>I believe every idea has a story worth telling. The trick is finding the right way to tell it.</p><p>In a world where attention spans are shorter than YouTube ad tolerance, words alone aren’t enough. You need curiosity to grab attention, creativity and humour to hold it, and strategy to make it count.</p>' ),
		a_w( 'aswin-quote-card', array( 'text' => 'Think of a mother trying to give her child bitter medicine. The medicine matters, but getting it swallowed is an entirely different campaign! A little sweetness and a clever approach can change everything.', 'pill' => 'yes' ) ),
		a_text( '<p>That’s how I approach content: turning everyday observations into fresh ideas, simple messages into compelling stories and brand communication into meaningful connections.</p>' ),
		a_w( 'aswin-statement', array( 'text' => 'Because good content gets attention. *Great content earns a place in people’s minds.*' ) ),
	), true ),
) );

/* 02 Toolkit */
$data[] = a_section( 'toolkit', 'section toolkit', array(
	a_w( 'aswin-section-heading', array( 'chapter_num' => '02', 'chapter_label' => 'The toolkit', 'title' => 'What do I bring *to the table?*', 'kicker' => '' ) ),
	a_c( array( 'content_width' => 'full', 'css_classes' => 'tool-row' ), array(
		a_w( 'aswin-skill-card', array( 'title' => 'Technical skills', 'items' => a_items( $tech ), 'variant' => 'outline', 'count' => 'yes' ) ),
		a_w( 'aswin-skill-card', array( 'title' => 'Creative skills', 'items' => a_items( $creative ), 'variant' => 'outline', 'count' => 'yes' ) ),
		a_w( 'aswin-skill-card', array( 'title' => 'When I’m not working', 'items' => a_items( $off ), 'variant' => 'accent', 'big_last' => 'yes', 'count' => 'yes' ) ),
	), true ),
), '#151412' );

/* 03–08 Work sections */
$sample_ig = array(
	array(
		'title'      => 'Meatball Day — alt Pizza',
		'caption'    => 'Social post & caption',
		'ig_handle'  => 'altpizza',
		'ig_caption' => "Balls so good, they take you on a ride! 🚀\n\nIt’s Meatball Day, have you ordered our Wagyu Meatballs yet? Get ’em from @grabfoodsg, @foodpandasg or @deliveroo_sg.\n\n#MeatballDay #bakedwagyumeatballs #fiery #ordernow #sgfood",
		'ig_likes'   => 'ansheeeesh',
		'ig_date'    => '9 March 2022',
	),
	array( 'title' => 'Your next post', 'caption' => 'Upload the creative & paste the caption', 'ig_caption' => 'Write the caption here. #hashtags and @mentions turn blue automatically.', 'ig_likes' => 'someone', 'ig_date' => 'Date' ),
	array( 'title' => 'Another post', 'caption' => 'Replace me in Elementor', 'ig_caption' => 'Caption goes here ✨ #yourbrand', 'ig_likes' => 'someone', 'ig_date' => 'Date' ),
);

$work = array(
	array( 'brands', '03', 'Trusted by', 'Brands', 'Names I’ve had the pleasure of putting words to.', 'logos', array() ),
	array( 'technical-writing', '04', 'Selected work', 'Technical Copywriting / *Content Writing*', 'Making the complicated sound simple — and readable.', 'cards', array() ),
	array( 'social-media', '05', 'Selected work', 'Creative Copywriting — *Social Media*', 'Thumb-stoppers, one scroll at a time.', 'phone', $sample_ig ),
	array( 'campaigns-pr', '06', 'Selected work', 'Campaigns / *PR Events*', 'Ideas that stepped off the screen and into the room.', 'wide', array() ),
	array( 'newspaper-ad', '07', 'Selected work', 'Newspaper *Ad*', 'Print that people actually stop to read.', 'wide', array() ),
	array( 'branding', '08', 'Selected work', 'Branding', 'Voices, names and stories that brands grow into.', 'wide', array() ),
);
foreach ( $work as $i => $w ) {
	$data[] = a_section( $w[0], 'section work-section', array(
		a_w( 'aswin-section-heading', array( 'chapter_num' => $w[1], 'chapter_label' => $w[2], 'title' => $w[3], 'kicker' => $w[4] ) ),
		a_w( 'aswin-gallery', array(
			'layout'    => $w[5],
			'columns'   => 'phone' === $w[5] ? '3' : 'auto',
			'ig_ui'     => 'yes',
			'ig_handle' => 'brandname',
			'lightbox'  => 'yes',
			'items'     => a_items( $w[6] ),
		) ),
	), $i % 2 ? '#F3EEE5' : '#E9E2D5' );
}

/* 09 Contact */
$data[] = a_section( 'contact', 'section contact', array(
	a_w( 'aswin-contact', array(
		'chapter_num'   => '09',
		'chapter_label' => 'The last word',
		'title'         => 'Got a story? *Let’s tell it.*',
		'text'          => 'Every idea has a story worth telling. If you’ve got one that needs the right words, I’d love to hear it.',
		'buttons'       => array(),
	) ),
), '#FF4F1F' );

/* Save */
$page_id = (int) get_option( 'page_on_front' );
if ( ! $page_id ) {
	WP_CLI::error( 'No static front page set.' );
}
wp_update_post( array( 'ID' => $page_id, 'post_content' => '' ) );
update_post_meta( $page_id, '_wp_page_template', 'elementor_header_footer' );

$doc = \Elementor\Plugin::$instance->documents->get( $page_id, false );
$doc->set_is_built_with_elementor( true );
$doc->save( array( 'elements' => $data ) );
update_post_meta( $page_id, '_wp_page_template', 'elementor_header_footer' );

/* Kit: wider boxed container, no Elementor default fonts/colours overriding the theme */
$kit_id = (int) get_option( 'elementor_active_kit' );
if ( $kit_id ) {
	$ks = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$ks = is_array( $ks ) ? $ks : array();
	$ks['container_width'] = array( 'unit' => 'px', 'size' => 1320, 'sizes' => array() );
	update_post_meta( $kit_id, '_elementor_page_settings', $ks );
}
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );
\Elementor\Plugin::$instance->files_manager->clear_cache();

WP_CLI::success( 'Homepage built with Elementor (page ' . $page_id . ', ' . count( $data ) . ' sections).' );
