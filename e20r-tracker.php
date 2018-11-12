<?php
/*
Plugin Name: E20R Tracker
Plugin URI: http://eighty20results.com/e20r-tracker
Description: Track Coaching Activities
Version: 3.4
Author: Wicked Strong Chicks, LLC <info@eighty20results.com>
Author URI: http://eighty20results.com/thomas-sjolshagen
Text Domain: e20r-tracker
Domain Path: /languages
License: GPLv2
*/

namespace E20R\Tracker\Controllers;
/*
    Copyright 2015-2018 Thomas Sjolshagen / Wicked Strong Chicks, LLC (info@eighty20results.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
// TODO: run unserialize functionality from the Tools section?
/* Prevent direct access to the plugin */

use E20R\Tracker\Models\Tables;
use E20R\Utilities\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	die( "Sorry, you are not allowed to access this page directly." );
}

define( 'E20R_VERSION', '3.4' );
define( 'E20R_RUN_UNSERIALIZE', 0 ); // 0 == Do NOT Run, 1 == Run
define( 'E20R_DB_VERSION', '12' );
define( 'E20R_NEW_DB_VERSION', '9' );
define( 'E20R_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'E20R_PLUGINS_URL', plugins_url( '', __FILE__ ) );
define( 'E20R_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'E20R_MAX_LOG_SIZE', 10 * 1024 * 1024 );
define( 'E20R_UPCOMING_WEEK', 1002 );
define( 'E20R_CURRENT_WEEK', 1001 );
define( 'E20R_PREVIOUS_WEEK', 1000 );
define( 'E20R_QUESTIONS', 1 );
define( 'E20R_ANSWERS', 1 );
define( 'CONST_SATURDAY', 6 );
define( 'CONST_SUNDAY', 0 );
define( 'CONST_MONDAY', 1 );
define( 'GF_PHOTOFORM_ID', 45 );
define( 'E20R_SURVEY_TYPE_WELCOME', 1 );
define( 'E20R_SURVEY_TYPE_OTHER', 9999 );

// define( 'URL_TO_PROGRESS_FORM', site_url('/coaching/progress-update/'));
define( 'E20R_COACHING_URL', site_url( '/coaching/' ) );
define( 'URL_TO_PROGRESS_FORM', E20R_COACHING_URL . 'weekly-progress/' );
define( 'URL_TO_CHECKIN_FORM', site_url( '/coaching/home' ) );

define( 'CONST_MEASUREMENTDAY', 6 );
define( 'TOTAL_GIRTH_MEASUREMENTS', 8 ); // Total number of girth measurements expected

define( 'CHECKIN_ACTION', 1 );
define( 'CHECKIN_ASSIGNMENT', 2 );
define( 'CHECKIN_SURVEY', 3 );
define( 'CHECKIN_ACTIVITY', 4 );
define( 'CHECKIN_NOTE', 5 );
define( 'CHECKIN_ACTION_AND_ACTIVITY', 14 );

define( 'CONST_ARTICLE_FEEDBACK_LESSONS', 10000 );
define( 'CONST_ARTICLE_FEEDBACK_LESSON_SUMMARY', 10001 );
define( 'CONST_ARTICLE_FEEDBACK_ACTIVITIES', 10010 );
define( 'CONST_ARTICLE_FEEDBACK_ACTIVITIES_SUMMARY', 10010 );

define( 'CONST_NULL_ARTICLE', - 9999 );
define( 'CONST_DEFAULT_ASSIGNMENT', - 9999 );
define( 'CONST_MAXDAYS_FUTURE', 1 );
define( 'CONST_MAXDAYS_PAST', 2 );

define( 'E20R_SELECT2_VER', '4.0.5' );

global $e20r_db_version;

$e20r_db_version = E20R_DB_VERSION;

class E20R_Tracker {
	private static $instance = null;
	
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Class auto-loader for the E20R Tracker plugin
	 *
	 * @param string $class_name Name of the class to auto-load
	 *
	 * @since  2.3.3
	 * @access public static
	 */
	public static function autoLoader( $class_name ) {
		
		// Do nothing unless it's for an E20R Plugin class (or a crypto class).
		if ( false === stripos( $class_name, 'e20r' ) && false === stripos( $class_name, 'defuse\\crypto' ) ) {
			return;
		}
		
		/**
		 * Load E20R Plugin classes
		 */
		if ( false !== stripos( $class_name, 'e20r' ) ) {
			
			$parts    = explode( '\\', $class_name );
			$c_name   = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
			$filename = "class.{$c_name}.php";
			
			if ( file_exists( plugin_dir_path( __FILE__ ) . 'classes/' ) ) {
				$base_path = plugin_dir_path( __FILE__ ) . 'classes/';
			}
			
			if ( file_exists( plugin_dir_path( __FILE__ ) . 'class/' ) ) {
				$base_path = plugin_dir_path( __FILE__ ) . 'class/';
			}
		}
		
		/**
		 * Load classes for the cryptography library
		 */
		if ( false !== stripos( $class_name, 'defuse\\crypto' ) ) {
			
			$parts     = explode( '\\', $class_name );
			$c_name    = $parts[ ( count( $parts ) - 1 ) ];
			$base_path = plugin_dir_path( __FILE__ ) . 'classes/cryptography/';
			$filename  = "{$c_name}.php";
		}
		
		$iterator = new \RecursiveDirectoryIterator( $base_path, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveIteratorIterator::SELF_FIRST | \RecursiveIteratorIterator::CATCH_GET_CHILD | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS );
		
		$filter = new \RecursiveCallbackFilterIterator( $iterator, function ( $current, $key, $iterator ) use ( $filename ) {
			
			// Skip hidden files and directories.
			if ( $current->getFilename()[0] == '.' || $current->getFilename() == '..' ) {
				return false;
			}
			
			if ( $current->isDir() ) {
				// Only recurse into intended subdirectories.
				return $current->getFilename() === $filename;
			} else {
				// Only consume files of interest.
				return strpos( $current->getFilename(), $filename ) === 0;
			}
		} );
		
		foreach ( new \ RecursiveIteratorIterator( $iterator ) as $f_filename => $f_file ) {
			
			$class_path = $f_file->getPath() . "/" . $f_file->getFilename();
			
			if ( $f_file->isFile() && false !== strpos( $class_path, $filename ) ) {
				
				require_once( $class_path );
			}
		}
	}
	
	/**
	 * Load the plugin
	 */
	public function load() {
		
		Utilities::get_instance()->log( "Loading the e20rTracker classes and running init of the e20rTracker() class" );
		
		try {
			
			$e20rUpdateChecker = \PucFactory::buildUpdateChecker(
				'https://eighty20results.com/protected-content/e20r-tracker/metadata.json',
				__FILE__,
				'e20r-tracker'
			);
			
			Tables::getInstance()->init();
			
			Utilities::get_instance()->log( "Loading hooks." );
			Tracker::getInstance()->loadAllHooks();
			
		} catch ( \Exception $e ) {
			Utilities::get_instance()->log( "Error initializing the Tracker plugin: " . $e->getMessage() );
		}
	}
	
	/**
	 * Debug function (if executes if DEBUG is defined)
	 *
	 * @param $msg -- Debug message to print to debug log.
	 */
	public static function dbg( $msg ) {
		
		$utils = Utilities::get_instance();
		$utils->log( $msg );
	}
	
	/**
	 * Functions returns error message. Used by nopriv Ajax traps.
	 */
	public function ajaxUnprivError() {
		
		Utilities::get_instance()->log( 'Unprivileged ajax call attempted' );
		
		wp_send_json_error( array(
			'ecode'    => 3,
			'errno'    => 3,
			'err_text' => __( 'You must be logged in to access/view tracker data', 'e20r_tracker' ),
		) );
		exit();
	}
}

spl_autoload_register( 'E20R\\Tracker\\Controllers\\E20R_Tracker::autoLoader' );

Utilities::get_instance()->log( "Loading update checker & autoloader" );

try {
	
	if ( ! class_exists( '\\PucFactory' ) ) {
		require_once( E20R_PLUGIN_DIR . "classes/controllers/plugin-updates/plugin-update-checker.php" );
	}
	
	
	global $e20rMeasurementDate;
	global $e20rExampleProgress;
	
	Utilities::get_instance()->log( "\n\n------------------------------------- New Iteration ---------------------------\n\n" );
	
	// Set to false unless the user is accessing the example/demo progress form.
	$e20rExampleProgress = false;
	
	$e20rMeasurementDate = '2014-01-01';
	
	register_activation_hook( __FILE__, array( Tracker::getInstance(), 'activate' ) );
	register_deactivation_hook( __FILE__, array( Tracker::getInstance(), 'deactivate' ) );
	
	add_action( 'plugins_loaded', array( E20R_Tracker::getInstance(), 'load' ), 9 );
	
} catch ( \Exception $e ) {
	Utilities::get_instance()->log( "Error initializing the E20R Tracker plugin: " . $e->getMessage() );
}