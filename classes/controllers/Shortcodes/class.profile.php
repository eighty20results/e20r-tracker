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
use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\Assignment;
use E20R\Tracker\Controllers\Client;
use E20R\Tracker\Controllers\Measurements;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Workout;
use E20R\Utilities\Utilities;

class Profile {
	
	/**
	 * Instance of the Profile class
	 *
	 * @var null|Profile
	 */
	private static $instance = null;
	
	/**
	 * Profile constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Profile|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Short Code Handler: Client Profile page(s)
	 *
	 * @param null|array $atts
	 * @param string     $content
	 *
	 * @return string
	 */
	public function loadShortcode( $atts = null, $content = '' ) {
		
		Utilities::get_instance()->log( "Loading shortcode data for the client profile page." );
		// Utilities::get_instance()->log($content);
		
		global $current_user;
		
		$Program      = Program::getInstance();
		$Action       = Action::getInstance();
		$Assignment   = Assignment::getInstance();
		$Client       = Client::getInstance();
		$Workout      = Workout::getInstance();
		$Article      = Article::getInstance();
		$Measurements = Measurements::getInstance();
		
		$Model = $Client->getModelClass();
		$View  = $Client->getViewClass();
		
		global $currentArticle;
		
		$html = null;
		
		if ( ! is_user_logged_in() || ( ! $Client->hasDataAccess( $current_user->ID ) ) ) {
			
			auth_redirect();
		} else {
			
			$user_id = $current_user->ID;
			$Program->getProgramIdForUser( $user_id );
		}
		
		/* Load views for the profile page tabs */
		$config = $Action->configure_dailyProgress();
		
		$code_atts = shortcode_atts( array(
			'use_cards' => false,
		), $atts );
		
		foreach ( $code_atts as $key => $val ) {
			
			Utilities::get_instance()->log( "e20r_profile shortcode --> Key: {$key} -> {$val}" );
			$config->{$key} = $val;
		}
		
		if ( in_array( strtolower( $config->use_cards ), array( 'yes', 'true', '1' ) ) ) {
			
			Utilities::get_instance()->log( "User requested card based dashboard: {$config->use_cards}" );
			$config->use_cards = true;
		}
		
		if ( in_array( strtolower( $config->use_cards ), array( 'no', 'false', '0' ) ) ) {
			
			Utilities::get_instance()->log( "User requested old-style dashboard: {$config->use_cards}" );
			$config->use_cards = false;
		}
		
		if ( ! isset( $config->use_cards ) ) {
			$config->use_cards = false;
		}
		
		if ( $Client->completeInterview( $config->userId ) ) {
			$interview_descr = 'Saved interview';
		} else {
			
			$interview_descr = '<div style="color: darkred; text-decoration: underline; font-weight: bolder;">' . __( "Please complete interview", "e20r-tracker" ) . '</div>';
		}
		
		$interview_html = '<div id="e20r-profile-interview">' . $Client->view_interview( $config->userId ) . '</div>';
		$interview      = array( $interview_descr, $interview_html );
		
		if ( ! $currentArticle->is_preview_day ) {
			
			Utilities::get_instance()->log( "Configure user specific data" );
			
			$Model->setUser( $config->userId );
			// $this->setClient($user_id);
			
			$dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
			// $pDimensions = array( 'width' => '90', 'height' => '1024', 'htype' => 'px', 'wtype' => '%' );
			
			Utilities::get_instance()->log( "Loading progress data..." );
			$measurements = $Measurements->getMeasurement( 'all', false );
			
			if ( true === $Client->completeInterview( $config->userId ) ) {
				$measurement_view = $Measurements->showTableOfMeasurements( $config->userId, $measurements, $dimensions, true, false );
			} else {
				$measurement_view = '<div class="e20r-progress-no-measurement">' . $Program->incompleteIntakeForm() . '</div>';
			}
			
			$assignments  = $Assignment->listUserAssignments( $config->userId );
			$activities   = $Workout->listUserActivities( $config->userId );
			$achievements = $Action->listUserAchievements( $config->userId );
			
			$progress = array(
				'Measurements' => '<div id="e20r-progress-measurements">' . $measurement_view . '</div>',
				'Assignments'  => '<div id="e20r-progress-assignments"><br/>' . $assignments . '</div>',
				'Activities'   => '<div id="e20r-progress-activities">' . $activities . '</div>',
				'Achievements' => '<div id="e20r-progress-achievements">' . $achievements . '</div>',
			);
			
			$dashboard = array(
				'Your dashboard',
				'<div id="e20r-daily-progress">' . $Action->dailyProgress( $config ) . '</div>',
			);
			/*
                       $activity = array(
                           'Your activity',
                           '<div id="e20r-profile-activity">' . $Workout->prepare_activity( $config ) . '</div>'
                       );
            */
			$progress_html = array(
				'Your progress',
				'<div id="e20r-profile-status">' . $Measurements->showProgress( $progress, null, false ) . '</div>',
			);
			
			$tabs = array(
				'Home'      => $dashboard,
//                'Activity'          => $activity,
				'Progress'  => $progress_html,
				'Interview' => $interview,
			);
		} else {
			
			$lesson_prefix = preg_replace( '/\[|\]/', '', $currentArticle->prefix );
			$lesson        = array(
				'Your ' . lcfirst( $lesson_prefix ),
				'<div id="e20r-profile-lesson">' . $Article->load_lesson( $config->articleId ) . '</div>',
			);
			
			$tabs = array(
				$lesson_prefix => $lesson,
				'Interview'    => $interview,
			);
		}
		
		$content .= $View->viewClientProfile( $tabs );
		Utilities::get_instance()->log( "Display the HTML for the e20r_profile short code: " . strlen( $content ) );
		
		return $content;
		
	}
}