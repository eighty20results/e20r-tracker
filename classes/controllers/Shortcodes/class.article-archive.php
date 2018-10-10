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
use E20R\Tracker\Controllers\Program;

use E20R\Utilities\Utilities;

class Article_Archive {
	
	/**
	 * Instance of the Article_Archive class
	 *
	 * @var null|Article_Archive
	 */
	private static $instance = null;
	
	/**
	 * Article_Archive constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Article_Archive|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate an archive of Articles
	 *
	 * @param null|array $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function loadShortcode( $attr = null, $content = '' ) {
		
		$Client = Client::getInstance();
		$Article = Article::getInstance();
		
		global $currentClient;
		global $current_user;
		
		$Client->setClient( $current_user->ID );
		
		$articles = $this->get_article_archive( $currentClient->user_id );
		$daily_cards    = array();
		
		foreach ( $articles as $article ) {
			
			$daily_cards[$article->release_day] = $Article->get_card_info( $article, $currentClient->user_id );
			Utilities::get_instance()->log( "Received " . count( $daily_cards ) . " cards for day # {$article->release_day}" );
		}
		
		Utilities::get_instance()->log( print_r( $daily_cards, true ) );
		
		// TODO: Process the daily cards an place them in a grid...
		return $content;
	}
	
	/**
	 * Load and return the article archive available for the specified user ID (or the current user)
	 *
	 * @param int|null $user_id
	 *
	 * @return array|bool
	 */
	public function get_article_archive( $user_id = null ) {
		
		$Program = Program::getInstance();
		$Article = Article::getInstance();
		$Model = $Article->getModelClass();
		
		global $currentProgram;
		
		if ( is_null( $user_id ) ) {
			
			global $currentClient;
			global $current_user;
			
			if ( ! isset( $currentClient->user_id ) ) {
				
				$Client = Client::getInstance();
				$Client->setClient( $current_user->ID );
				$Client->init();
			}
			
			$user_id = $currentClient->user_id;
		}
		
		Utilities::get_instance()->log( "Loading article archive for user {$user_id}" );
		
		$Program->getProgramIdForUser( $user_id );
		
		// The archive goes up to (but doesn't include) the current day.
		$up_to = ( $currentProgram->active_delay - 1 );
		
		$archive = $Model->load_for_archive( $up_to );
		
		Utilities::get_instance()->log( "Returned " . count( $archive ) . " articles for archive for user {$user_id} with a last-day value of {$currentProgram->active_delay}" );
		
		return $archive;
		
		// $Tracker->get_closest_release_day( $array, $day );
	}
}