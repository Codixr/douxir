<?php
/**
 * Douxir Gutenberg blocks.
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register editor script and dynamic blocks.
 *
 * @return void
 */
function douxir_register_blocks() {
	$asset_file = DOUXIR_PATH . '/assets/dist/blocks.asset.php';
	$js_file    = DOUXIR_PATH . '/assets/dist/blocks.js';
	$asset      = file_exists( $asset_file ) ? include $asset_file : array(
		'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
		'version'      => DOUXIR_VERSION,
	);
	$deps       = isset( $asset['dependencies'] ) ? $asset['dependencies'] : array( 'wp-blocks', 'wp-element', 'wp-i18n' );
	$version    = isset( $asset['version'] ) ? $asset['version'] : DOUXIR_VERSION;

	if ( file_exists( $js_file ) ) {
		wp_register_script(
			'douxir-blocks',
			DOUXIR_URL . '/assets/dist/blocks.js',
			$deps,
			$version,
			true
		);
	}

	register_block_type(
		'douxir/docs-list',
		array(
			'api_version'     => 3,
			'editor_script'   => 'douxir-blocks',
			'render_callback' => 'douxir_render_docs_list_block',
			'attributes'      => array(
				'category'    => array( 'type' => 'string', 'default' => '' ),
				'layout'      => array( 'type' => 'string', 'default' => 'list' ),
				'columns'     => array( 'type' => 'number', 'default' => 3 ),
				'limit'       => array( 'type' => 'number', 'default' => 10 ),
				'showSearch'  => array( 'type' => 'boolean', 'default' => false ),
			),
		)
	);

	register_block_type(
		'douxir/search',
		array(
			'api_version'     => 3,
			'editor_script'   => 'douxir-blocks',
			'render_callback' => 'douxir_render_search_block',
			'attributes'      => array(
				'placeholder' => array( 'type' => 'string', 'default' => '' ),
			),
		)
	);

	register_block_type(
		'douxir/categories',
		array(
			'api_version'     => 3,
			'editor_script'   => 'douxir-blocks',
			'render_callback' => 'douxir_render_categories_block',
			'attributes'      => array(
				'layout'  => array( 'type' => 'string', 'default' => 'grid' ),
				'columns' => array( 'type' => 'number', 'default' => 3 ),
			),
		)
	);
}
add_action( 'init', 'douxir_register_blocks' );

/**
 * Render the Docs List block via the list shortcode.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function douxir_render_docs_list_block( $attributes ) {
	$atts = array(
		'category'     => isset( $attributes['category'] ) ? $attributes['category'] : '',
		'layout'       => isset( $attributes['layout'] ) ? $attributes['layout'] : 'list',
		'columns'      => isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3,
		'limit'        => isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 10,
		'show_search'  => ! empty( $attributes['showSearch'] ) ? 'yes' : 'no',
	);
	return Douxir_Shortcodes::render_doc_list( $atts );
}

/**
 * Render the Search block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function douxir_render_search_block( $attributes ) {
	$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
	return Douxir_Shortcodes::render_search( array( 'placeholder' => $placeholder ) );
}

/**
 * Render the Categories block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function douxir_render_categories_block( $attributes ) {
	$atts = array(
		'layout'  => isset( $attributes['layout'] ) ? $attributes['layout'] : 'grid',
		'columns' => isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3,
	);
	return Douxir_Shortcodes::render_categories( $atts );
}
