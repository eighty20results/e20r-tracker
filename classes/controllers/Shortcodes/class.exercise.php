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

use E20R\Utilities\Utilities;

class Exercise {
	
	/**
	 * Instance of the Exercise class
	 *
	 * @var null|Exercise
	 */
	private static $instance = null;
	
	/**
	 * Exercise constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Exercise|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Process the Exercise shortcode
	 *
	 * @param null|array $attributes
	 *
	 * @return string|null
	 */
	public function loadShortcode( $attributes = null, $content = '' ) {
		
		Utilities::get_instance()->log( "Loading shortcode data for the exercise." );
		
		$Exercise = \E20R\Tracker\Controllers\Exercise::getInstance();
		$Model    = $Exercise->getModelClass();
		$View     = $Exercise->getViewClass();
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		$config = new \stdClass();
		
		$tmp = shortcode_atts( array(
			'id'        => null,
			'display'   => 'row',
			'shortcode' => null,
		), $attributes );
		
		foreach ( $tmp as $key => $val ) {
			
			$config->{$key} = $val;
		}
		
		if ( isset( $config->id ) && ( ! is_null( $config->id ) ) ) {
			Utilities::get_instance()->log( "Using ID to locate exercise: {$config->id}" );
			$exInfo = $Model->findExercise( 'id', $config->id );
		}
		
		if ( isset( $config->shortcode ) && ( ! is_null( $config->shortcode ) ) ) {
			Utilities::get_instance()->log( "Using shortcode to locate exercise: {$config->shortcode}" );
			$exInfo = $Model->findExercise( 'shortcode', $config->shortcode );
		}
		
		if ( empty( $exInfo ) ) {
			return __( 'The administrator did not indicate which exercise to show', 'e20r-tracker' );
		}
		
		foreach ( $exInfo as $ex ) {
			
			if ( isset( $ex->id ) && ( ! is_null( $ex->id ) ) ) {
				
				Utilities::get_instance()->log( "Loading exercise info: {$ex->id}" );
				$Exercise->init( $ex->id );
				
				if ( $config->display != 'new' ) {
					
					Utilities::get_instance()->log( "Printing with old layout" );
					
					return $View->view_exercise_as_row();
				} else {
					Utilities::get_instance()->log( "Printing with NEW layout" );
					
					return $View->view_exercise_as_columns();
					
				}
				
			} else {
				Utilities::get_instance()->log( "No exercise found to display!" );
				
				return null;
			}
		}
		
		return null;
	}
}