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

class Activity_Archive {
	
	/**
	 * Instance of the Activity_Archive class
	 *
	 * @var null|Activity_Archive
	 */
	private static $instance = null;
	
	/**
	 * Activity_Archive constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Activity_Archive|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Process the Activity Archive shortcode
	 *
	 * @param null|array $attributes
	 * @param string     $content
	 *
	 * @return string
	 * @since 0.8.0
	 */
	public function loadShortcode( $attributes = null, $content = '' ) {
		
		Utilities::get_instance()->log( "Loading shortcode data for the activity archive." );
		
		global $current_user;
		global $currentProgram;
		
		$Workout = Workout::getInstance();
		$View    = $Workout->getViewClass();
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		$config                    = new \stdClass();
		$config->userId            = $current_user->ID;
		$config->programId         = $currentProgram->id;
		$config->expanded          = false;
		$config->show_tracking     = 0;
		$config->phase             = 0;
		$config->print_only        = null;
		$config->activity_override = false;
		
		$tmp = shortcode_atts( array(
			'period'     => 'current',
			'print_only' => null,
		), $attributes );
		
		foreach ( $tmp as $key => $val ) {
			
			if ( ! empty( $val ) ) {
				$config->{$key} = $val;
			}
		}
		
		// Valid "false" responses for print_only atribute can include: array( 'no', 'false', 'null', '0', 0, false, null );
		$true_responses = array( 'yes', 'true', '1', 1, true );
		
		if ( in_array( $config->print_only, $true_responses ) ) {
			Utilities::get_instance()->log( "User requested the archive be printed (i.e. include all unique exercises for the week)" );
			$config->print_only = true;
		} else {
			Utilities::get_instance()->log( "User did NOT request the archive be printed" );
			$config->print_only = false;
		}
		
		if ( 'current' == $config->period ) {
			$period = E20R_CURRENT_WEEK;
		}
		
		if ( 'previous' == $config->period ) {
			$period = E20R_PREVIOUS_WEEK;
		}
		
		if ( 'next' == $config->period ) {
			$period = E20R_UPCOMING_WEEK;
		}
		
		Utilities::get_instance()->log( "Period set to {$config->period}." );
		
		$activities = $Workout->getActivityArchive( $current_user->ID, $currentProgram->id, $period );
		
		Utilities::get_instance()->log( "Check whether we're generating the list of exercises for print only: " . ( $config->print_only ? 'Yes' : 'No' ) );
		
		if ( true === $config->print_only ) {
			
			Utilities::get_instance()->log( "User requested this activity archive be printed. Listing unique exercises." );
			
			$printable         = array();
			$already_processed = array();
			
			foreach ( $activities as $key => $workout ) {
				
				if ( 'header' !== $key && ( ! in_array( $workout->id, $already_processed ) ) ) {
					
					$routine = new \stdClass();
					
					if ( ( 0 == $config->phase ) || ( $config->phase < $workout->phase ) ) {
						
						$routine->phase = $workout->phase;
						Utilities::get_instance()->log( "Setting phase number for the archive: {$config->phase}." );
					}
					
					$routine->id          = $workout->id;
					$routine->name        = $workout->title;
					$routine->description = $workout->excerpt;
					$routine->started     = $workout->startdate;
					$routine->ends        = $workout->enddate;
					$routine->days        = $workout->days;
					
					$list = array();
					
					foreach ( $workout->groups as $grp ) {
						
						Utilities::get_instance()->log( "Adding " . count( $grp->exercises ) . " to list of exercises for routine # {$routine->id}" );
						$list = array_merge( $list, $grp->exercises );
					}
					
					$routine->exercises = array_unique( $list, SORT_NUMERIC );
					
					Utilities::get_instance()->log( "Total number of exercises for  routine #{$routine->id}: " . count( $routine->exercises ) );
					$already_processed[] = $routine->id;
					$printable[]         = $routine;
				}
			}
			
			Utilities::get_instance()->log( "Will display " . count( $printable ) . " workouts and their respective exercises for print" );
			
			return $View->display_printable_list( $printable, $config );
		}
		
		Utilities::get_instance()->log( "Grabbed activity count: " . count( $activities ) );
		
		return $View->displayArchive( $activities, $config );
	}
}