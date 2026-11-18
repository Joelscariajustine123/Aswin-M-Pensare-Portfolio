<?php
/**
 * Imports the real Technical Writing samples (Technical_Writing/ folder) as Works
 * in "Technical Copywriting / Content Writing", ahead of the demo pieces.
 * Run: wp eval-file import-technical-writing.php <dir-with-images> --user=admin
 * Safe to re-run: skips works whose slug already exists.
 */

$dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$items = array(
	array(
		'slug'    => 'seafood-processing-workflow',
		'file'    => 'seafood-processing-workflow.jpeg',
		'title'   => 'Seafood Processing Workflow',
		'excerpt' => 'A six-step process infographic that walks buyers and auditors from raw-material receiving to cold-store dispatch.',
		'role'    => 'Process copy, infographic content',
		'body'    => array(
			'About this piece' => 'A standardised processing workflow written for a seafood exporter — one page that explains how product quality, food safety and export compliance are maintained from the moment raw material arrives to the moment it is dispatched.',
			'What I wrote'     => 'Six stages — Raw Material Receiving, Initial Inspection, Cleaning & Processing, Freezing & Packaging, Quality Control, and Storage & Dispatch — each reduced to a clear title and a single action-led line, so the whole process can be understood at a glance.',
			'Why it works'     => 'Technical processes earn trust when they look simple, not when they look complicated. Short, parallel sentences and a strict one-step-one-idea structure make the workflow easy for buyers, auditors and new staff alike.',
		),
	),
	array(
		'slug'    => 'malabar-urology-nephrology',
		'file'    => 'malabar-urology-nephrology.jpeg',
		'title'   => 'Urology & Nephrology — Malabar Hospital',
		'excerpt' => 'Department profile copy that introduces the team and translates ten clinical procedures into language patients understand.',
		'role'    => 'Healthcare content, brochure copy',
		'body'    => array(
			'About this piece' => 'A department page for Malabar Hospital’s Urology & Nephrology unit — introducing its experience, its senior consultants and its standing as one of the first laser centres in Kerala.',
			'What I wrote'     => 'The department overview, and a procedures list where every clinical term is paired with what it actually means — for example “Surgery performed inside the kidney – Retrograde Intrarenal Surgery (RIRS)”. A feature callout introduces the 160W Holmium Laser for enlarged prostate and kidney stone treatment.',
			'Why it works'     => 'Patients read medical brochures when they are anxious. Leading with the plain-English description and keeping the medical name alongside it reassures patients without losing the precision doctors need.',
		),
	),
	array(
		'slug'    => 'fabrication-modularization',
		'file'    => 'fabrication-modularization.jpeg',
		'title'   => 'Fabrication & Modularization',
		'excerpt' => 'Capability copy for an engineering company — structural, piping, mechanical, electrical and instrumentation works on one page.',
		'role'    => 'B2B technical copy, company profile',
		'body'    => array(
			'About this piece' => 'A services page from an engineering company profile, covering integrated engineering, fabrication, modularization and installation for infrastructure and industrial projects.',
			'What I wrote'     => 'A layered message — a one-line positioning statement, a bold promise about quality and efficient project execution, and a supporting line on safety and reliability — followed by six service tags from Infrastructure & Industrial Works to Instrumentation & Control.',
			'Why it works'     => 'Procurement teams skim. The page states what the company does, how well it does it and exactly which works it covers — in that order, in under a hundred words.',
		),
	),
	array(
		'slug'    => 'marine-conversion-asset-solutions',
		'file'    => 'marine-conversion-asset-solutions.jpeg',
		'title'   => 'Marine Conversion & Asset Solutions',
		'excerpt' => 'Service copy for marine asset conversion, refurbishment and life extension — from FPSO units to skid packages.',
		'role'    => 'B2B technical copy, company profile',
		'body'    => array(
			'About this piece' => 'A companion services page in the same engineering company profile, focused on marine asset conversion, refurbishment, upgrades and life extension.',
			'What I wrote'     => 'An introduction to integrated fabrication, assembly and installation for process modules and skid packages; a highlighted promise on engineering and modification solutions; and five service tags — FPSO Units, Process Modules, Module Assembly & Integration, Skid Packages and Structural Modules.',
			'Why it works'     => 'It mirrors the structure of the Fabrication & Modularization page, so readers learn the layout once and find what they need faster across the whole profile.',
		),
	),
	array(
		'slug'    => 'cold-storage-food-distribution',
		'file'    => 'cold-storage-food-distribution.jpeg',
		'title'   => 'Cold Storage, Logistics & Food Distribution',
		'excerpt' => 'Business-profile copy explaining how a seafood group’s cold chain and domestic distribution arms fit together.',
		'role'    => 'Corporate profile, business writing',
		'body'    => array(
			'About this piece' => 'A spread from a group business profile covering two divisions — cold storage & logistics, and domestic food distribution.',
			'What I wrote'     => 'Narrative copy that explains not just what each business does, but why it matters to the group: how owning cold-storage capacity lets it buy when supply is plentiful and ship when the market is right, and how the distribution arm balances export earnings with demand at home.',
			'Why it works'     => 'Corporate profiles often list businesses; this one connects them. Every paragraph answers the reader’s real question — “why does this make the group stronger?”',
		),
	),
	array(
		'slug'    => 'optimized-processing',
		'file'    => 'optimized-processing.jpeg',
		'title'   => 'Optimized Processing: Advancing Efficiency & Quality',
		'excerpt' => 'A technology spread on rice milling, AI commodity grading, automated poultry management and smart aquaculture.',
		'role'    => 'Technical copy, brochure',
		'body'    => array(
			'About this piece' => 'A brochure spread on technology in food and agricultural processing, organised into four focused capability blocks.',
			'What I wrote'     => 'Four short explainers — Precision Rice Milling, AI Commodity Grading, Automated Poultry Management and Smart Aquaculture Monitoring — each moving from the technology to the outcome it delivers: efficiency, quality, welfare or yield.',
			'Why it works'     => 'Technology copy is easy to overload. Keeping each block to one technology and its benefit makes complex systems feel approachable for investors and buyers alike.',
		),
	),
);

$term = get_term_by( 'slug', 'technical-writing', 'aswin_work_cat' );
if ( ! $term ) {
	WP_CLI::error( 'Category technical-writing not found.' );
}

$created = 0;
foreach ( $items as $i => $it ) {
	if ( get_page_by_path( $it['slug'], OBJECT, 'aswin_work' ) ) {
		WP_CLI::log( "Exists, skipped: {$it['title']}" );
		continue;
	}
	$content = '';
	foreach ( $it['body'] as $h => $p ) {
		$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $h ) . '</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>' . esc_html( $p ) . '</p><!-- /wp:paragraph -->';
	}
	$id = wp_insert_post( array(
		'post_type'    => 'aswin_work',
		'post_status'  => 'publish',
		'post_title'   => $it['title'],
		'post_name'    => $it['slug'],
		'post_excerpt' => $it['excerpt'],
		'post_content' => $content,
		'menu_order'   => $i + 1,
	) );
	wp_set_object_terms( $id, (int) $term->term_id, 'aswin_work_cat' );
	update_post_meta( $id, '_aswin_role', $it['role'] );

	$src = $dir . '/' . $it['file'];
	if ( file_exists( $src ) ) {
		$tmp = wp_tempnam( $it['file'] );
		copy( $src, $tmp );
		$att = media_handle_sideload( array( 'name' => $it['file'], 'tmp_name' => $tmp ), $id, $it['title'] );
		if ( is_wp_error( $att ) ) {
			WP_CLI::warning( $att->get_error_message() );
		} else {
			update_post_meta( $att, '_wp_attachment_image_alt', $it['title'] );
			set_post_thumbnail( $id, $att );
		}
	} else {
		WP_CLI::warning( "Image missing: $src" );
	}
	$created++;
	WP_CLI::log( "Added: {$it['title']} (" . get_permalink( $id ) . ')' );
}

/* Push the demo technical-writing pieces after the real ones. */
$demo = get_posts( array(
	'post_type'      => 'aswin_work',
	'posts_per_page' => -1,
	'meta_key'       => '_aswin_demo', // phpcs:ignore
	'meta_value'     => '1', // phpcs:ignore
	'tax_query'      => array( array( 'taxonomy' => 'aswin_work_cat', 'field' => 'term_id', 'terms' => $term->term_id ) ),
) );
foreach ( $demo as $d ) {
	wp_update_post( array( 'ID' => $d->ID, 'menu_order' => 50 + (int) $d->menu_order ) );
}

if ( function_exists( 'aswin_purge_cdn_now' ) ) {
	aswin_purge_cdn_now();
}
WP_CLI::success( "$created technical-writing works added; " . count( $demo ) . ' demo pieces moved after them.' );
