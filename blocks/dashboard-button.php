<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package e20r-tracker
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type/#enqueuing-block-scripts
 */
function dashboard_button_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'dashboard-button/index.js';
	wp_register_script(
		'dashboard-button-block-editor',
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
		filemtime( "$dir/$index_js" )
	);

	$editor_css = 'dashboard-button/editor.css';
	wp_register_style(
		'dashboard-button-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'dashboard-button/style.css';
	wp_register_style(
		'dashboard-button-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'e20r-tracker/dashboard-button', array(
		'editor_script' => 'dashboard-button-block-editor',
		'editor_style'  => 'dashboard-button-block-editor',
		'style'         => 'dashboard-button-block',
	) );
}
add_action( 'init', 'dashboard_button_block_init' );
