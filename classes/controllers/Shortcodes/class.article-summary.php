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
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Time_Calculations;
use E20R\Tracker\Controllers\Tracker;

use E20R\Utilities\Utilities;

class Article_Summary {
	
	/**
	 * Instance of the Article_Summary class
	 *
	 * @var null|Article_Summary
	 */
	private static $instance = null;
	
	/**
	 * Article_Summary constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Article_Summary|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Process the Article Summary short-code
	 *
	 * @param null|array $attributes
	 * @param string     $content
	 *
	 * @return null|string
	 */
	public function loadShortcode( $attributes = null, $content = '' ) {
		
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		$Article = Article::getInstance();
		
		$Model = $Article->getModelClass();
		$View  = $Article->getViewClass();
		
		global $currentProgram;
		global $currentArticle;
		
		global $current_user;
		global $post;
		
		$html       = null;
		$article    = null;
		$article_id = null;
		
		$for_date = $Tracker->sanitize( get_query_var( 'article_date' ) );
		Utilities::get_instance()->log( "Loading article summary based on shortcode: {$for_date}" );
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
			wp_die();
		}
		
		if ( ! empty( $_REQUEST ) && ( isset( $_REQUEST['e20r-checkin-nonce'] ) ) ) {
			
			Utilities::get_instance()->log( "Checking for valid check-in Nonce" );
			check_ajax_referer( 'e20r-checkin-data', 'e20r-checkin-nonce' );
			
			$article_id = isset( $_REQUEST['article-id'] ) ? $Tracker->sanitize( $_REQUEST['article-id'] ) : null;
			$for_date   = isset( $_REQUEST['for-date'] ) ? $Tracker->sanitize( $_REQUEST['for-date'] ) : null;
			$program_id = isset( $_REQUEST['program-id'] ) ? $Tracker->sanitize( $_REQUEST['program-id'] ) : null;
		}
		
		if ( ! empty( $_REQUEST ) && ( isset( $_REQUEST['e20r-action-nonce'] ) ) ) {
			
			Utilities::get_instance()->log( "Checking for valid action Nonce (from dashboard)" );
			check_ajax_referer( 'e20r-action-data', 'e20r-action-nonce' );
			
			$article_id = isset( $_REQUEST['article-id'] ) ? $Tracker->sanitize( $_REQUEST['article-id'] ) : null;
			$for_date   = isset( $_REQUEST['for-date'] ) ? $Tracker->sanitize( $_REQUEST['for-date'] ) : null;
			$program_id = isset( $_REQUEST['program-id'] ) ? $Tracker->sanitize( $_REQUEST['program-id'] ) : null;
			Utilities::get_instance()->log( "Checking for valid action Nonce (from dashboard)" );
		}
		
		if ( empty( $program_id ) ) {
			
			Utilities::get_instance()->log( "Loading program info for user {$current_user->ID}" );
			$Program->getProgramIdForUser( $current_user->ID );
		} else {
			Utilities::get_instance()->log( "Loading program config for {$program_id}" );
			$Program->init( $program_id );
		}
		
		if ( ! empty( $for_date ) ) {
			
			Utilities::get_instance()->log( "Received date: {$for_date} and will calculate # of days from that" );
			$days_since_start = Time_Calculations::daysBetween( strtotime( $currentProgram->startdate ), strtotime( $for_date ) );
		} else {
			$days_since_start = $Tracker->getDelay( 'now', $current_user->ID );
		}
		
		Utilities::get_instance()->log( "using delay value of: {$days_since_start}" );
		
		if ( is_null( $article_id ) ) {
			
			global $post;
			
			$articles = $Model->find( 'post_id', $post->ID, $currentProgram->id );
			
			Utilities::get_instance()->log( "Found " . count( $articles ) . " for this post ID ({$post->ID})" );
			
			foreach ( $articles as $a ) {
				
				if ( $a->release_day == $days_since_start ) {
					
					Utilities::get_instance()->log( "Found article {$a->id} and release day {$a->release_day}" );
					$currentArticle = $a;
					break;
				}
			}
			
			if ( ! isset( $currentArticle->id ) || ( $currentArticle->id == 0 ) ) {
				Utilities::get_instance()->log( "No article ID specified by calling post/page. Not displaying anything" );
				
				return false;
			}
		}
		
		Utilities::get_instance()->log( "Loading article summary shortcode for: {$currentArticle->id}" );
		
		// $program_id = $Program->getProgramIdForUser($current_user->ID);
		// $days_since_start = $Tracker->getDelay('now', $current_user->ID);
		
		if ( ! isset( $currentArticle->id ) ) { // || !empty( $article_id ) && ( $article_id != $currentArticle->id )
			
			$articles = $Model->find( 'id', $article_id, $currentProgram->id );
			Utilities::get_instance()->log( "Found " . count( $articles ) . " article(s) with post ID {$post->ID}" );
			$article        = array_pop( $articles );
			$article_id     = $article->id;
			$currentArticle = $article;
		}
		
		if ( ! isset( $currentArticle->id ) && ( ! is_null( $article_id ) ) ) {
			
			Utilities::get_instance()->log( "Configure article settings (not needed?) " );
			$Article->init( $article_id );
		}
		
		$defaults          = $Model->defaultSettings();
		$days_of_summaries = ( ! isset( $currentArticle->max_summaries ) || is_null( $currentArticle->max_summaries ) ? $defaults->max_summaries : $currentArticle->max_summaries );
		$title             = null;
		
		$tmp = shortcode_atts( array(
			'days'  => $days_of_summaries,
			'title' => null,
		), $attributes );
		
		Utilities::get_instance()->log( "Article # {$currentArticle->id} needs to locate {$tmp['days']} or {$days_of_summaries} days worth of articles to pull summaries from, ending on day # {$currentArticle->release_day}" );
		
		if ( isset( $tmp['title'] ) && ! empty( $tmp['title'] ) ) {
			$title = $tmp['title'];
		}
		
		if ( $days_of_summaries != $tmp['days'] ) {
			
			$days_of_summaries = $tmp['days'];
		}
		
		$start_day = ( $currentArticle->release_day - $days_of_summaries );
		$gt_days   = ( $currentArticle->release_day - $days_of_summaries );
		
		$start_TS = strtotime( "{$currentProgram->startdate} +{$start_day} days" );
		
		Utilities::get_instance()->log( "Searching for articles with release_day between start: {$start_day} and end: {$currentArticle->release_day}" );
		
		$between = array( ( $start_day - 1 ), $currentArticle->release_day );
		$history = $Article->findArticles( 'release_day', $between, $currentProgram->id, 'BETWEEN' );
		
		Utilities::get_instance()->log( "Fetched " . count( $history ) . " articles to pull summaries from" );
		
		$summary = array();
		
		foreach ( $history as $k => $a ) {
			
			$new = array(
				'title'   => null,
				'summary' => null,
				'day'     => null,
			);
			
			if ( ! empty( $a->post_id ) ) {
				
				$p   = get_post( $a->post_id );
				$art = get_post( $a->id );
				
				Utilities::get_instance()->log( "Loading data for post {$a->post_id} vs {$p->ID}" );
				
				$new['day']   = $a->release_day;
				$new['title'] = $p->post_title;
				
				if ( ! empty( $art->post_content ) ) {
					
					Utilities::get_instance()->log( "Using the article description." );
					$new['summary'] = wp_kses_allowed_html( $art->post_content );
					
				} else if ( ! empty( $art->post_excerpt ) ) {
					
					Utilities::get_instance()->log( "Using the article summary." );
					$new['summary'] = $art->post_excerpt;
					
				} else if ( ! empty( $p->post_excerpt ) ) {
					
					Utilities::get_instance()->log( "Using the post excerpt." );
					$new['summary'] = $p->post_excerpt;
				} else {
					
					Utilities::get_instance()->log( "Using the post summary." );
					$new['summary'] = $p->post_content;
					$new['summary'] = wp_trim_words( $new['summary'], 30, " [...]" );
				}
				
				Utilities::get_instance()->log( "Current day: {$currentArticle->release_day} + Last release day to include: {$gt_days}." );
				
				if ( ( $new['day'] > $gt_days ) ) {
					
					if ( ( $a->measurement_day != true ) && ( $a->summary_day != true ) ) {
						
						Utilities::get_instance()->log( "Adding {$new['title']} (for day# {$new['day']}) to list of posts to summarize" );
						$summary[ $a->release_day ] = $new;
					}
				}
				
				$a = null;
				wp_reset_postdata();
			}
		}
		
		ksort( $summary );
		
		Utilities::get_instance()->log( "Original prefix of {$currentArticle->prefix}" );
		$prefix = lcfirst( preg_replace( '/\[|\]/', '', $currentArticle->prefix ) );
		Utilities::get_instance()->log( "Scrubbed prefix: {$prefix}" );
		// Utilities::get_instance()->log($summary);
		
		$summary_post = get_post( $currentArticle->id );
		$info         = null;
		
		wp_reset_postdata();
		
		if ( ! empty( $summary_post->post_content ) ) {
			
			$info = wpautop( $summary_post->post_content_filtered );
		}
		
		// Since we're saving the array using the delay day as the key we'll have to do some jumping through hoops
		// to get the right key for the last day in the list.
		$k_array          = array_keys( $summary );
		$last_day_key     = count( $summary ) > 0 ? $k_array[ ( count( $summary ) - 1 ) ] : 0;
		$last_day_summary = isset( $summary[ $last_day_key ] ) ? $summary[ $last_day_key ] : null;
		$end_day          = ! empty( $last_day_summary['day'] ) ? $last_day_summary['day'] : 7;
		
		Utilities::get_instance()->log( "Using end day for summary period: {$end_day}" );
		
		$end_TS = strtotime( "{$currentProgram->startdate} +{$end_day} days", current_time( 'timestamp' ) );
		
		$html = $View->view_article_history( $prefix, $title, $summary, $start_TS, $end_TS, $info );
		
		return $html;
	}
}