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

use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\Client;
use E20R\Tracker\Controllers\Measurements;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Tracker;

use E20R\Utilities\Utilities;

class Weekly_Progress {
	
	/**
	 * Instance of the Weekly_Progress class
	 *
	 * @var null|Weekly_Progress
	 */
	private static $instance = null;
	
	/**
	 * Weekly_Progress constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Weekly_Progress|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate the content for the [weekly_progress] shortcode
	 *
	 * @param array       $attributes
	 * @param string $content
	 *
	 * @return string
	 */
	public function loadShortcode( $attributes, $content = '' ) {
		
		if (! is_user_logged_in()) {
			
			auth_redirect();
		}
		
		global $e20r_plot_jscript;
		global $current_user;
		
		$Article = Article::getInstance();
		$Tracker = Tracker::getInstance();
		$Client = Client::getInstance();
		$Program = Program::getInstance();
		$Measurements = Measurements::getInstance();
		
		global $currentClient;
		global $currentArticle;
		
		global $e20rMeasurementDate;
		$user_id = $Measurements->getUserId();
		
		Utilities::get_instance()->log("Loading shortcode processor: " . $Tracker->whoCalledMe() );
		
		$e20r_plot_jscript = true;
		
		Utilities::get_instance()->log("Request: " . print_r( $_POST, true ) );
		
		$mDate = ( isset( $_POST['e20r-progress-form-date'] ) ? ( strtotime( $_POST['e20r-progress-form-date'] ) ? sanitize_text_field( $_POST['e20r-progress-form-date'] ) : null ) : null );
		$articleId = isset( $_POST['e20r-progress-form-article'] ) ? intval( $_POST['e20r-progress-form-article'] ) : null;
		
		// Get current article ID if it's not set as part of the $_POST variable.
		if ( empty( $articleId ) ) {
			$delay = $Tracker->getDelay();
			$program  = $Program->getProgramIdForUser( $current_user->ID );
			$currentArticle = $Article->findArticles( 'release_day', $delay, $program )[0];
			
			Utilities::get_instance()->log("Current Article: " . print_r( $currentArticle, true ));
			$articleId = $currentArticle->id;
			
			Utilities::get_instance()->log("Article ID is now: {$articleId}");
		}
		
		if ( $mDate ) {
			
			$e20rMeasurementDate = $mDate;
			Utilities::get_instance()->log( "Date to measure for requested: {$mDate}" );
			$Measurements->setMeasurementDate( $mDate );
		}
		
		/*
		$day = 0;
		$from_programstart = 1;
		$use_article_id = 1;
		*/
		$demo_form = 0;
		
		extract( shortcode_atts( array(
			'day' => 0,
			'from_programstart' => 1,
			'use_article_id' => 1,
			'demo_form' => 0,
		), $attributes ) );
		
		if ( $demo_form == 1 ) {
			
			global $e20rExampleProgress;
			$e20rExampleProgress = true; // TODO: Do something if it's an example progress form.
		}
		
		$measurementDate = $Measurements->getMeasurementDate();
		
		// TODO: Does user have permission...?
		try {
			
			Utilities::get_instance()->log("Loading the measurement data for {$measurementDate}");
			$Measurements->init( $measurementDate, $user_id );
			
			if ( !is_object( $currentClient ) || ( isset( $currentClient->loadedDefaults) && (false == $currentClient->loadedDefaults) ) ||
			     ( true == $currentClient->loadedDefaults ) ) {
				
				Utilities::get_instance()->log("Loading the Client class()");
				$Client->loadClientInfo( $user_id );
			}
			
			if ( $Measurements->loadData( $measurementDate ) == false ) {
				Utilities::get_instance()->log("Error loading data for (user: {$current_user->ID}) for {$measurementDate}");
			}
			
			Utilities::get_instance()->log("Loading progress form for {$measurementDate} by {$user_id}");
			return $Measurements->loadEditProgress( $articleId );
			
		}
		catch ( \Exception $e ) {
			Utilities::get_instance()->log("Error displaying weekly progress form: " . $e->getMessage() );
			return sprintf( __( "Error displaying weekly progress form. Error message: %s", "e20r-tracker" ), $e->getMessage() );
		}
	}
}