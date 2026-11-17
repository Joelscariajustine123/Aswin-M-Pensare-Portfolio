<?php
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$is_elementor = did_action( 'elementor/loaded' ) && \Elementor\Plugin::$instance->documents->get( get_the_ID() )->is_built_with_elementor();
	if ( $is_elementor ) {
		the_content();
	} else {
		?>
		<div class="wrap">
			<header class="single-hero"><h1><?php the_title(); ?></h1></header>
			<article class="entry"><?php the_content(); ?></article>
		</div>
		<?php
	}
endwhile;
get_footer();
