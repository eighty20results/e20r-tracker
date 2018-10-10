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

class Permalinks {
	
	/**
	 * @var null|Permalinks
	 */
	static private $instance = null;
	
	/**
	 * Permalinks constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Permalinks|null;
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
		
		add_action( 'init', array( $this, 'addRewriteRule' ), 10 );
		add_action( 'init', array( $this, 'addRewriteTags' ), 10);
		
		add_filter( 'post_link', array( $this, 'processPostLink' ), 10, 3 );
		// add_filter( 'query_vars', array( $this, 'addQueryVars' ), 10, 1 );
	}
	
	/**
	 * Query variables that can/may be present
	 *
	 * @param string[] $vars
	 *
	 * @return string[]
	 */
	public function addQueryVars( $vars ) {
		
		$vars[] = "article_date";
		
		return $vars;
	}
	
	/**
	 * Add the end-point for the article_date parameter
	 */
	public function addRewriteRule() {
		
		add_rewrite_rule(
			'^([^/]*)/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$',
			'index.php?pagename=$matches[1]&article_date=$matches[2]',
			'top'
		);
		
		// add_rewrite_endpoint( 'article_date',EP_PERMALINK );
	}
	
	/**
	 * Support the article_date rewrite tag
	 */
	public function addRewriteTags() {
		
		add_rewrite_tag('%article_date%', '([0-9]{4}-[0-9]{2}-[0-9]{2})/$');
	}
	
	/**
	 * Process the permalink type containing the %article_date% (eg YYYY-MM-DD formatted dates)
	 *
	 * @param $permalink
	 * @param $post
	 * @param $leavename
	 *
	 * @return string
	 */
	public function processPostLink( $permalink, $post, $leavename ) {
		
		global $currentArticle;
		global $current_user;
		
		$Tracker = Tracker::getInstance();
		
		// global $post;
		
		if ( !is_user_logged_in() ) {
			
			return str_replace( '%article_date%', '', $permalink );
		}
		
		if ( false === strpos( $permalink, '%article_date%' ) ) {
			
			Utilities::get_instance()->log("No permalink containing the %article_date% tag");
			return $permalink;
		}
		
		if ( isset( $currentArticle->post_id ) && ( $currentArticle->post_id == $post->ID ) ) {
			
			$article_date = $Tracker->getDateFromDelay( ($currentArticle->release_day -1), $current_user->ID );
			
			$article_date = ( !empty( $article_date ) ? $article_date : date('Y-m-d', current_time('timestamp') ) );
			$article_date = urlencode( $article_date );
			
			$permalink = str_replace( '%article_date%', $article_date, $permalink );
			
		} else {
			
			$permalink = str_replace( '%article_date%/', '', $permalink );
		}
		
		// Utilities::get_instance()->log("Using permalink: {$permalink}");
		return esc_url($permalink);
	}
}