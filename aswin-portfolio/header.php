<?php defined( 'ABSPATH' ) || exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?php echo esc_attr( get_bloginfo( 'name' ) . ' — ' . get_bloginfo( 'description' ) ); ?>">
	<script>document.documentElement.classList.add('js');</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#main">Skip to content</a>
<header class="site-header" id="top">
	<div class="wrap">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?><span>.</span></a>
		<button class="menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="site-nav"><span></span><span></span><span></span></button>
		<nav class="nav" id="site-nav" aria-label="Primary">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'walker'         => new class() extends Walker_Nav_Menu {
						public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
							$cls     = in_array( 'btn-pill', (array) $item->classes, true ) ? ' class="btn-pill"' : '';
							$target  = $item->target ? ' target="' . esc_attr( $item->target ) . '"' : '';
							$output .= '<a href="' . esc_url( $item->url ) . '"' . $cls . $target . '>' . esc_html( $item->title ) . '</a>';
						}
						public function end_el( &$output, $item, $depth = 0, $args = null ) {}
					},
				) );
			} else {
				aswin_menu_fallback();
				echo '<a class="btn-pill" href="' . esc_url( home_url( '/' ) ) . '#contact">Let’s talk</a>';
			}
			?>
		</nav>
	</div>
</header>
<main id="main">
