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
function progress_overview_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'progress-overview/index.js';
	wp_register_script(
		'progress-overview-block-editor',
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
		filemtime( "$dir/$index_js" )
	);

	$editor_css = 'progress-overview/editor.css';
	wp_register_style(
		'progress-overview-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'progress-overview/style.css';
	wp_register_style(
		'progress-overview-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'e20r-tracker/progress-overview', array(
		'editor_script' => 'progress-overview-block-editor',
		'editor_style'  => 'progress-overview-block-editor',
		'style'         => 'progress-overview-block',
	) );
}
add_action( 'init', 'progress_overview_block_init' );
