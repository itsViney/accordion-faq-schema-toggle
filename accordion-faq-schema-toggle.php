<?php
/**
 * Plugin Name: Accordion FAQ Schema Toggle
 * Description: Adds a toggle to the core/accordion block to output FAQ Schema JSON-LD markup, providing potential SEO benefits.
 * Version: 1.0.2
 * Author: Andrew Viney
 * Author URI: https://www.itsviney.com
 * Plugin URI: https://github.com/itsViney/accordion-faq-schema-toggle
 * Text Domain: accordion-faq-schema-toggle
 */

namespace viney\AccordionFaqSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue block editor JavaScript.
 */
function enqueue_block_editor_assets() {
    wp_enqueue_script(
        'accordion-faq-schema-editor',
        plugins_url( 'build/index.js', __FILE__ ),
        [ 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-hooks' ],
        filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' ),
        true
    );
}

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_block_editor_assets' );

/**
 * Append FAQ schema when attribute is enabled.
 */
function get_block_faq_schema( $block_content, $block ) {

	if ( 'core/accordion' !== $block['blockName'] ) {
		return $block_content;
	}

	if ( empty( $block['attrs']['addFaqSchema'] ) ) {
		return $block_content;
	}

	$faq_data = [];

	foreach ( $block['innerBlocks'] as $accordion_content ) {
		if ( 'core/accordion-content' !== $accordion_content['blockName'] ) {
			continue;
		}

		$question = '';
		$answer_html = '';

		foreach ( $accordion_content['innerBlocks'] as $inner ) {
			if ( 'core/accordion-header' === $inner['blockName'] && ! empty( $inner['innerHTML'] ) ) {
				if ( preg_match( '/<span[^>]*>(.*?)<\/span>/s', $inner['innerHTML'], $matches ) ) {
					$question = wp_strip_all_tags( $matches[1] );
				}
			}
			if ( 'core/accordion-panel' === $inner['blockName'] ) {
				$answer_html = apply_filters( 'the_content', render_block( $inner ) );
			}
		}

		if ( $question && $answer_html ) {
			$faq_data[] = [
				'@type' => 'Question',
				'name'  => $question,
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $answer_html ),
				],
			];
		}
	}

	if ( $faq_data ) {
		$schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $faq_data,
		];

		// Store the schema for output later
		global $viney_accordion_faq_schema;
		if ( ! is_array( $viney_accordion_faq_schema ) ) {
			$viney_accordion_faq_schema = [];
		}
		$viney_accordion_faq_schema[] = $schema;
	}

	return $block_content;
}

add_filter( 'render_block', __NAMESPACE__ . '\\get_block_faq_schema', 10, 2 );

function print_collected_faq_schema() {
	global $viney_accordion_faq_schema;

	if ( empty( $viney_accordion_faq_schema ) ) {
		return;
	}

	// Merge multiple accordions on the same page into a single FAQPage if desired.
	// Here we just output one combined script for simplicity:
	$all_entities = [];
	foreach ( $viney_accordion_faq_schema as $schema ) {
		if ( ! empty( $schema['mainEntity'] ) ) {
			$all_entities = array_merge( $all_entities, $schema['mainEntity'] );
		}
	}

	if ( $all_entities ) {
		$final_schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $all_entities,
		];

		echo '<script type="application/ld+json">' . wp_json_encode( $final_schema ) . '</script>';
	}
}
add_action( 'wp_footer', __NAMESPACE__ . '\\print_collected_faq_schema', 20 );
