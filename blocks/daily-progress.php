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
function daily_progress_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'daily-progress/index.js';
	wp_register_script(
		'daily-progress-block-editor',
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
		filemtime( "$dir/$index_js" )
	);

	$editor_css = 'daily-progress/editor.css';
	wp_register_style(
		'daily-progress-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'daily-progress/style.css';
	wp_register_style(
		'daily-progress-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'e20r-tracker/daily-progress', array(
		'editor_script' => 'daily-progress-block-editor',
		'editor_style'  => 'daily-progress-block-editor',
		'style'         => 'daily-progress-block',
	) );
}
add_action( 'init', 'daily_progress_block_init' );
