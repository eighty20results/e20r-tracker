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

namespace E20R\Tracker\Controllers;

use E20R\Tracker\Controllers\Shortcodes\Activity;
use E20R\Tracker\Controllers\Shortcodes\Activity_Archive;
use E20R\Tracker\Controllers\Shortcodes\Article_Archive;
use E20R\Tracker\Controllers\Shortcodes\Article_Summary;
use E20R\Tracker\Controllers\Shortcodes\Client_Overview;
use E20R\Tracker\Controllers\Shortcodes\Daily_Progress;
use E20R\Tracker\Controllers\Shortcodes\Dashboard_Button;
use E20R\Tracker\Controllers\Shortcodes\Profile;
use E20R\Tracker\Controllers\Shortcodes\Progress_Overview;
use E20R\Tracker\Controllers\Shortcodes\Weekly_Progress;

/**
 * Class Shortcodes
 * @package E20R\Tracker\Controllers
 *
 * @since v3.2 - ENHANCEMENT: Fixed PHPDoc blocks and supports the refactored short code handlers
 */
class Shortcodes {
	
	/**
	 * Instance of the ShortCodes controller class
	 *
	 * @var null|Shortcodes
	 */
	private static $instance = null;
	
	/**
	 * Shortcodes constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * Instantiates or returns the Shortcodes controller class
	 *
	 * @return Shortcodes|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load Short Code hooks
	 */
	public function loadHooks() {
		
		add_shortcode( 'e20r_tracker_dashboard_button', array( Dashboard_Button::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'weekly_progress', array( Weekly_Progress::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'progress_overview', array( Progress_Overview::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'daily_progress', array( Daily_Progress::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'e20r_activity', array( Activity::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'e20r_activity_archive', array( Activity_Archive::getInstance(), 'loadShortCode' ) );
		add_shortcode( 'e20r_exercise', array( \E20R\Tracker\Controllers\Shortcodes\Exercise::getInstance(), 'shortcode_exercise' ) );
		add_shortcode( 'e20r_profile', array( Profile::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'e20r_client_overview', array( Client_Overview::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'e20r_article_summary', array( Article_Summary::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'e20r_article_archive', array( Article_Archive::getInstance(), 'loadShortcode' ) );
	}
}