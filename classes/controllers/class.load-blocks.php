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


class Load_Blocks {
	
	/**
	 * List of supported/implemented Gutenberg blocks
	 * @var array $blocks
	 */
	private $blocks = array();
	
	/**
	 * Path to the Gutenberg blocks
	 *
	 * @var string $block_path
	 */
	private $block_path;
	
	/**
	 * Instance of the Load_Blocks class (singleton)
	 *
	 * @var null|Load_Blocks $instance
	 */
	private static $instance = null;
	
	/**
	 * Load_Blocks constructor.
	 *
	 * Deactivates 'new Load_Blocks()' from external sources
	 */
	private function __construct() {
		
		$this->blocks = array(
			'activity',
			'activity-archive',
			'article-archive',
			'article-summary',
			'client-summary',
			'daily-progress',
			'dashboard-button',
			'exercise',
			'profile',
			'progress-overview',
			'weekly-progress'
		);
		
		$this->block_path =  E20R_PLUGIN_DIR . "/blocks";
	}
	
	/**
	 * Deactivated clone operation for class (singleton)
	 */
	private function __clone() {}
	
	/**
	 * Get or instantiate and get the Load_Blocks class
	 *
	 * @return Load_Blocks|null
	 */
	public function getInstance() {
		
		if ( is_null( self::$instance )) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Load action handlers for the supported blocks
	 */
	public function loadBlockHooks() {
		
		// Load blocks dynamically
		foreach( $this->blocks as $block_name ) {
			
			if ( file_exists( "{$this->block_path}/{$block_name}.php" ) ) {
				require_once( "{$this->block_path}/{$block_name}.php" );
			}
		}
	}
}