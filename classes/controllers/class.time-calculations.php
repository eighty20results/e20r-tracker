<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits, educational reminders, etc.
 * Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers;

use E20R\Utilities\Utilities;

class Time_Calculations {
	
	/**
	 * @var null|Time_Calculations
	 */
	static private $instance = null;
	
	/**
	 * Time_Calculations constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Time_Calculations|null;
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load hooks for this class
	 */
	public function loadHooks() {
	
	}
	
	/**
	 * Calculates the # of days between two dates (specified in UTC seconds)
	 *
	 * @param $startTS (timestamp) - timestamp value for start date
	 * @param $endTS (timestamp) - timestamp value for end date
	 *
	 * @return int ( the # of days )
	 */
	public static function daysBetween( $startTS, $endTS = null, $tz = 'UTC' ) {
		
		$days = 0;
		
		// use current day as $endTS if nothing is specified
		if ( ( is_null( $endTS ) ) && ( $tz == 'UTC') ) {
			
			$endTS = current_time( 'timestamp', true );
		}
		elseif ( is_null( $endTS ) ) {
			
			$endTS = current_time( 'timestamp' );
		}
		
		// Create two DateTime objects
		$dStart = new \DateTime( date( 'Y-m-d', $startTS ), new \DateTimeZone( $tz ) );
		$dEnd   = new \DateTime( date( 'Y-m-d', $endTS ), new \DateTimeZone( $tz ) );
		
		Utilities::get_instance()->log("StartTS: {$startTS}, endTS: {$endTS} " );
		
		if ( version_compare( PHP_VERSION, "5.3", '>=' ) ) {
			
			/* Calculate the difference using 5.3 supported logic */
			$dDiff  = $dStart->diff( $dEnd );
			$dDiff->format( '%d' );
			//$dDiff->format('%R%a');
			
			$days = $dDiff->days;
			
			// Invert the value
			if ( $dDiff->invert == 1 )
			{$days = 0 - $days;}
		}
		else {
			
			// V5.2.x workaround
			$dStartStr = $dStart->format('U');
			$dEndStr = $dEnd->format('U');
			
			// Difference (in seconds)
			$diff = abs($dStartStr - $dEndStr);
			
			// Convert to days.
			$days = $diff * 86400; // Won't handle DST correctly, that's probably not a problem here..?
			
			// Sign flip if needed.
			if ( gmp_sign($dStartStr - $dEndStr) == -1)
			{$days = 0 - $days;}
		}
		
		// Correct the $days value because include the "to" day.
		$days = $days + 1;
		Utilities::get_instance()->log("Returning: {$days}");
		return $days;
	}
	
	/**
	 * Returns the post date (when it was posted/made available)
	 *
	 * @param int     $days
	 * @param null|int $userId
	 *
	 * @return false|string
	 */
	public static function getDateForPost( $days, $userId = null ) {
		
		Utilities::get_instance()->log("Loading function...");
		
		global $current_user;
		
		$Program = Program::getInstance();
		
		if ( $userId  === null ) {
			
			$userId = $current_user->ID;
		}
		
		$startDateTS = $Program->startdate( $userId );
		
		if (! $startDateTS ) {
			
			Utilities::get_instance()->log("{$days} -> No startdate found for user with ID of {$userId}");
			return ( date( 'Y-m-d', current_time( 'timestamp' ) ) );
		}
		
		if ( empty( $days ) || ( $days == 'now' ) ) {
			Utilities::get_instance()->log("Calculating 'now' based on current time and startdate for the user");
			$days = self::daysBetween( $startDateTS, current_time('timestamp') );
		}
		
		$startDate = date( 'Y-m-d', $startDateTS );
		Utilities::get_instance()->log("{$days} -> Startdate found for user with ID of {$userId}: {$startDate} and days: {$days}");
		
		$releaseDate = date( 'Y-m-d', strtotime( "{$startDate} +" . ($days -1 ) ." days") );
		
		Utilities::get_instance()->log("{$days} -> Calculated date for delay of {$days}: {$releaseDate}");
		return $releaseDate;
	}
}