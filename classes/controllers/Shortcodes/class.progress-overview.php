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

use E20R\Tracker\Controllers\Action;
use E20R\Tracker\Controllers\Assignment;
use E20R\Tracker\Controllers\Client;
use E20R\Tracker\Controllers\Measurements;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Workout;

use E20R\Utilities\Utilities;

class Progress_Overview {
	
	/**
	 * Instance of the Progress_Overview class
	 *
	 * @var null|Progress_Overview
	 */
	private static $instance = null;
	
	/**
	 * Progress_Overview constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Progress_Overview|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Display content for the [progress_overview] shortcode
	 *
	 * @param array  $attributes
	 * @param string $content
	 *
	 * @return string
	 */
	public function loadShortcode( $attributes = array(), $content = '' ) {
		
		if (! is_user_logged_in()) {
			
			auth_redirect();
		}
		
		global $current_user;
		global $currentClient;
		
		$Tracker = Tracker::getInstance();
		$Client = Client::getInstance();
		$Action = Action::getInstance();
		$Assignment = Assignment::getInstance();
		$Workout = Workout::getInstance();
		$Program = Program::getInstance();
		$Measurements = Measurements::getInstance();
		
		$Model = $Measurements->getModelClass();
		$View = $Measurements->getViewClass();
		
		$user_id = $Measurements->getUserId();
		
		Utilities::get_instance()->log("Loading shortcode processor: " . $Tracker->whoCalledMe() );
		
		if ( $current_user->ID == 0 ) {
			Utilities::get_instance()->log("User Isn't logged in! Redirect immediately");
			auth_redirect();
		}
		
		$dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
		$pDimensions = array( 'width' => '90', 'height' => '1024', 'htype' => 'px', 'wtype' => '%' );
		
		// Load javascript for the progress overview.
		extract( shortcode_atts( array(
			'something' => 0,
		), $attributes ) );
		
		if ( $Client->hasDataAccess( $current_user->ID ) ) {
			
			$user_id = $current_user->ID;
			$Program->getProgramIdForUser( $user_id );
			Utilities::get_instance()->log( "User {$user_id} has access." );
		}
		else {
			Utilities::get_instance()->log( "Logged in user ID does not have access to progress data" );
			return null;
		}
		
		
		Utilities::get_instance()->log("Configure user specific data");
		$Model->setUser( $user_id );
		
		if ( empty($currentClient) || ( $user_id !== $currentClient->user_id || empty( $currentClient->program_id ) ) ) {
			Utilities::get_instance()->log("Have to update the currentClient info!");
			$Client->setClient( $user_id );
		}
		
		Utilities::get_instance()->log("Loading progress data... for {$user_id}");
		$measurements = $Measurements->getMeasurement( 'all', false );
		
		if ( true === $Client->completeInterview( $user_id ) ) {
			
			$measurement_view = $View->viewTableOfMeasurements( $user_id, $measurements, $dimensions, null, true );
		}
		else {
			$measurement_view = '<div class="e20r-progress-no-measurement">' . $Program->incompleteIntakeForm() . '</div>';
		}
		
		$tabs = array(
			'Measurements' => '<div id="e20r-progress-measurements">' . $measurement_view . '</div>',
			'Assignments' => '<div id="e20r-progress-assignments">' . $Assignment->listUserAssignments() . '</div>',
			'Activities' => '<div id="e20r-progress-activities">' . $Workout->listUserActivities() . '</div>',
			'Achievements' => '<div id="e20r-progress-achievements">' . $Action->listUserAchievements() . '</div>',
		);
		
		return $View->viewTabbedProgress( $tabs, $pDimensions, true );
	}
}