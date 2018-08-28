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


use E20R\Tracker\Controllers\Shortcodes\Dashboard_Button;

class Shortcodes {
	
	private static $instance = null;
	
	private function __construct() {
	}
	
	/**
	 * Load Shortcode hooks
	 */
	public function loadHooks() {
		
		add_shortcode( 'e20r_tracker_dashboard_button', array( Dashboard_Button::getInstance(), 'loadShortcode' ) );
		add_shortcode( 'weekly_progress', array( Measurements::getInstance(), 'shortcodeWeeklyProgress' ) );
		add_shortcode( 'progress_overview', array( Measurements::getInstance(), 'shortcodeProgressOverview') );
		add_shortcode( 'daily_progress', array( Action::getInstance(), 'shortcode_dailyProgress' ) );
		add_shortcode( 'e20r_activity', array( Workout::getInstance(), 'shortcode_activity' ) );
		add_shortcode( 'e20r_activity_archive', array( Workout::getInstance(), 'shortcode_act_archive' ) );
		add_shortcode( 'e20r_exercise', array( Exercise::getInstance(), 'shortcode_exercise' ) );
		add_shortcode( 'e20r_profile', array( Client::getInstance(), 'shortcode_clientProfile' ) );
		add_shortcode( 'e20r_client_overview', array( Client::getInstance(), 'shortcode_clientList') );
		add_shortcode( 'e20r_article_summary', array( Article::getInstance(), 'shortcode_article_summary') );
		add_shortcode( 'e20r_article_archive', array( Article::getInstance(), 'article_archive_shortcode') );
	}
	
	/**
	 * @return Shortcodes|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
}