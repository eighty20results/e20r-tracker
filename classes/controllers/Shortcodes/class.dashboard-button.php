<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits,
 * educational reminders, etc. Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation, either version 2 of the License or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers\Shortcodes;


use E20R\Tracker\Controllers\Program;

class Dashboard_Button {
	
	private static $instance = null;
	
	private function __construct() {
	}
	
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate the Dashboard Button shortcode
	 *
	 * @param array  $atts
	 * @param string $content
	 */
	public function loadShortcode( $atts, $content = "" ) {
		
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		global $current_user;
		global $currentProgram;
		
		$Program = Program::getInstance();
		
		$button_text = null;
		$css_class   = array();
		
		$atts = shortcode_atts( array(
			'css_class'   => 'e20r-tracker-dashboard_button',
			'button_text' => __( 'To: Your Coaching Dashboard', 'e20r-tracker' ),
		), $atts );
		
		$classes         = array();
		$user_program_id = $Program->getProgramIdForUser( $current_user->ID );
		$dashboard_url   = get_permalink( $Program->getValue( $user_program_id, 'dashboard_page_id' ) );
		
		if ( ! empty( $css_class ) ) {
			$classes = array_map( 'trim', explode( ',', $atts['css_class'] ) );
		}
		
		echo '<div style="width: 100%;">';
		echo '<div style="min-width: 200px; max-width: 500px; width: 90%; margin-left: auto; margin-right: auto; text-align: center;">';
		
		printf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			esc_url_raw( $dashboard_url ),
			$atts['button_text'],
			sprintf(
				'<button type="button" class="%1$s">%2$s</button>',
				( ! empty( $classes ) ? implode( ' ', $classes ) : null ),
				$atts['button_text']
			)
		);
		echo '</div>';
		echo '</div>';
	}
}