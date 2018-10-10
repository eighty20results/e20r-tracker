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


use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Action;

class Daily_Progress {
	
	/**
	 * Instance of the Daily_Progress class
	 *
	 * @var null|Daily_Progress
	 */
	private static $instance = null;
	
	/**
	 * Daily_Progress constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Daily_Progress|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Process the Daily Progress short-code
	 *
	 * @param null|array $attrs
	 * @param string     $content
	 *
	 * @return string
	 */
	public function loadShortcode( $attrs = null, $content = '' ) {
		
		/**
		 * Send 'em to the login page...
		 */
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		Utilities::get_instance()->log( "Processing the daily_progress short code" );
		
		$Action = Action::getInstance();
		
		// Configure the daily progress page for today's actions/assignments/activities
		$config = $Action->configure_dailyProgress();
		
		// Process the short code
		$code_atts = shortcode_atts( array(
			'type'      => 'action',
			'use_cards' => false,
		), $attrs );
		
		// Add shortcode settings to the $config object
		foreach ( $code_atts as $key => $val ) {
			
			Utilities::get_instance()->log( "daily_progress shortcode --> Key: {$key} -> {$val}" );
			$config->{$key} = $val;
		}
		
		// Should we use the grid of cards, or the old table?
		if ( in_array( strtolower( $config->use_cards ), array( 'yes', 'true', '1' ) ) ) {
			
			Utilities::get_instance()->log( "User requested card based dashboard: {$config->use_cards}" );
			$config->use_cards = true;
		} else if ( in_array( strtolower( $config->use_cards ), array( 'no', 'false', '0' ) ) ) {
			
			Utilities::get_instance()->log( "User requested old-style dashboard: {$config->use_cards}" );
			$config->use_cards = false;
		}
		
		// Nothing explicit stated in the shortcode so going for the default
		if ( ! isset( $config->use_cards ) ) {
			$config->use_cards = false;
		}
		
		Utilities::get_instance()->log( "Config is currently: " . print_r( $config, true ) );
		/*
				if ($config->type == 'assignment') {
		
					Utilities::get_instance()->log("Finding article info by post_id: {$post->ID}");
					$articles = $Article->findArticles('post_id', $post->ID, $config->programId);
				}
		*/
		Utilities::get_instance()->log( "Article ID is currently set to: {$config->articleId}" );
		
		// Load the daily progress HTML and return it (shortcode)
		ob_start(); ?>
        <div id="e20r-daily-progress">
			<?php echo $Action->dailyProgress( $config ); ?>
        </div>
		<?php
		return ob_get_clean();
	}
}