<?php
/**
 * Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

namespace E20R\Tracker\Controllers\Shortcodes;

use E20R\Tracker\Controllers\Workout;
use E20R\Utilities\Utilities;

class Activity {
	
	/**
	 * Instance of the Activity class
	 *
	 * @var null|Activity
	 */
	private static $instance = null;
	
	/**
	 * Activity constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Activity|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Process the Activity (Workout) short-code
	 *
	 * @param null|array $attributes
	 * @param string     $content
	 *
	 * @return string
	 */
	public function loadShortcode( $attributes = null, $content = '' ) {
		
		$Workout = Workout::getInstance();
		
		Utilities::get_instance()->log( "Loading shortcode data for the activity." );
		Utilities::get_instance()->log( print_r( $_REQUEST, true ) );
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		$config                = new \stdClass();
		$config->show_tracking = true;
		$config->display_type  = 'row';
		$config->print_only    = null;
		
		$tmp = shortcode_atts( array(
			'activity_id'   => null,
			'show_tracking' => true,
			'display_type'  => 'row', // Valid types: 'row', 'column', 'print'
		), $attributes );
		
		foreach ( $tmp as $key => $val ) {
			
			if ( ( 'activity_id' == $key ) && ( ! is_null( $val ) ) ) {
				$val = array( $val );
			}
			
			if ( ! is_null( $val ) ) {
				// Utilities::get_instance()->log("Setting {$key} to {$val}");
				$config->{$key} = $val;
			}
		}
		
		if ( false === in_array( strtolower( $config->show_tracking ), array( 'yes', 'no', 'true', 'false', 1, 0 ) ) ) {
			
			Utilities::get_instance()->log( "User didn't specify a valid show_tracking value in the shortcode!" );
			
			return sprintf( '<div class="error">%s</div>', __( 'Incorrect show_tracking value in the e20r_activity shortcode! (Valid values are: "yes", "no", "true", "false", "1", "0")', 'e20r-tracker' ) );
		}
		
		
		if ( ! in_array( $config->display_type, array( 'row', 'column', 'print' ) ) ) {
			
			Utilities::get_instance()->log( "User didn't specify a valid display_type in the shortcode!" );
			
			return sprintf( '<div class="error">%s</div>', __( 'Incorrect display_type value in the e20r_activity shortcode! (Valid values are "row", "column", "print")', 'e20r-tracker' ) );
		}
		
		$config->show_tracking = in_array( strtolower( $config->show_tracking ), array( 'yes', 'true', 1 ) );
		
		if ( 'print' === $config->display_type ) {
			
			$config->print_only = true;
		}
		
		Utilities::get_instance()->log( "Value of show_tracking is: {$config->show_tracking} -> " . ( $config->show_tracking ? 'true' : 'false' ) );
		
		return $Workout->prepare_activity( $config );
	}
}