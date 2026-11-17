<?php defined( 'ABSPATH' ) || exit; ?>
</main>
<footer class="site-footer">
	<?php if ( function_exists( 'aswin_has_contact_details' ) && aswin_has_contact_details() ) : ?>
		<div class="wrap footer-contact">
			<div class="fc-main">
				<p class="fc-kicker">Let’s talk</p>
				<?php if ( $e = aswin_contact( 'email' ) ) : ?><a class="fc-email" href="mailto:<?php echo esc_attr( antispambot( $e ) ); ?>"><?php echo esc_html( antispambot( $e ) ); ?></a><?php endif; ?>
				<p class="fc-line">
					<?php if ( $p = aswin_contact( 'phone' ) ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $p ) ); ?>"><?php echo aswin_icon( 'phone' ) . esc_html( $p ); // phpcs:ignore ?></a><?php endif; ?>
					<?php if ( $w = aswin_contact( 'whatsapp' ) ) : ?><a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D+/', '', $w ) ); ?>" target="_blank" rel="noopener"><?php echo aswin_icon( 'whatsapp' ); // phpcs:ignore ?>WhatsApp</a><?php endif; ?>
					<?php if ( $l = aswin_contact( 'location' ) ) : ?><span><?php echo aswin_icon( 'pin' ) . esc_html( $l ); // phpcs:ignore ?></span><?php endif; ?>
				</p>
			</div>
			<?php echo aswin_socials_html( 'socials on-dark' ); // phpcs:ignore ?>
		</div>
	<?php endif; ?>
	<?php $sections = function_exists( 'aswin_section_links' ) ? aswin_section_links() : array(); ?>
	<?php if ( $sections ) : ?>
		<nav class="wrap footer-sections" aria-label="Portfolio sections">
			<?php foreach ( $sections as $url => $label ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'aswin_work' ) ); ?>">All work</a>
		</nav>
	<?php endif; ?>
	<div class="wrap">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. Written with care, not with a template.</span>
		<span>Built by <a href="https://veinbit.com" target="_blank" rel="noopener">Veinbit Software Solutions LLP</a></span>
		<a href="#top">Back to top ↑</a>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
