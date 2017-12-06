<?php
/*
Plugin Name: E20R Tracker
Plugin URI: http://eighty20results.com/e20r-tracker
Description: Track Coaching Activities
Version: 1.7.4
Author: Wicked Strong Chicks, LLC <info@eighty20results.com>
Author URI: http://eighty20results.com/thomas-sjolshagen
Text Domain: e20r-tracker
Domain Path: /languages
License: GPLv2
*/
/*
    Copyright 2015-2017 Thomas Sjolshagen / Wicked Strong Chicks, LLC (info@eighty20results.com)

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

if ( !defined( 'ABSPATH' ) ) {
	die( "Sorry, you are not allowed to access this page directly." );
}

define('E20R_VERSION', '1.7.4');
define('E20R_RUN_UNSERIALIZE', 0); // 0 == Do NOT Run, 1 == Run
define('E20R_DB_VERSION', '11');
define('E20R_NEW_DB_VERSION','9');
define('E20R_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('E20R_PLUGINS_URL', plugins_url('', __FILE__));
define('E20R_PLUGIN_NAME', plugin_basename(__FILE__));
define('E20R_MAX_LOG_SIZE', 10 * 1024 * 1024);
define('E20R_UPCOMING_WEEK', 1002);
define('E20R_CURRENT_WEEK', 1001);
define('E20R_PREVIOUS_WEEK', 1000);
define('E20R_QUESTIONS', 1);
define('E20R_ANSWERS', 1);
define('CONST_SATURDAY', 6);
define('CONST_SUNDAY', 0);
define('CONST_MONDAY', 1);
define('GF_PHOTOFORM_ID', 45);
define('E20R_SURVEY_TYPE_WELCOME', 1);
define('E20R_SURVEY_TYPE_OTHER', 9999);

// define( 'URL_TO_PROGRESS_FORM', site_url('/coaching/progress-update/'));
define('E20R_COACHING_URL', site_url('/coaching/'));
define('URL_TO_PROGRESS_FORM', E20R_COACHING_URL . 'weekly-progress/');
define('URL_TO_CHECKIN_FORM', site_url('/coaching/home'));

define('CONST_MEASUREMENTDAY', 6);
define('TOTAL_GIRTH_MEASUREMENTS', 8); // Total number of girth measurements expected

define('CHECKIN_ACTION', 1);
define('CHECKIN_ASSIGNMENT', 2);
define('CHECKIN_SURVEY', 3);
define('CHECKIN_ACTIVITY', 4);
define('CHECKIN_NOTE', 5);
define('CHECKIN_ACTION_AND_ACTIVITY', 14);

define( 'CONST_ARTICLE_FEEDBACK_LESSONS', 10000 );
define( 'CONST_ARTICLE_FEEDBACK_LESSON_SUMMARY', 10001);
define( 'CONST_ARTICLE_FEEDBACK_ACTIVITIES', 10010 );
define( 'CONST_ARTICLE_FEEDBACK_ACTIVITIES_SUMMARY', 10010 );

define('CONST_NULL_ARTICLE', -9999);
define('CONST_DEFAULT_ASSIGNMENT', -9999);
define('CONST_MAXDAYS_FUTURE', 1);
define('CONST_MAXDAYS_PAST', 2);

define( 'E20R_SELECT2_VER', '4.0.5' );

global $e20r_db_version;

$e20r_db_version = "1.0";

if ( !function_exists('e20r_autoloader')) {
    function e20r_autoloader($class_name)
    {

        if (false === strpos($class_name, 'e20r')) {
            return;
        }

        $base_path = E20R_PLUGIN_DIR . "classes";
        $types = array('models', 'controllers', 'views');

        foreach ($types as $type) {

            if (file_exists("{$base_path}/{$type}/class.{$class_name}.php")) {

                dbg("e20rTracker::autoload() - {$class_name} loading");
                require_once("{$base_path}/{$type}/class.{$class_name}.php");

            }
        }
    }
}

// TODO: Remove this before going live.
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

// set_error_handler("exception_error_handler");

if (!function_exists('dbg')):

    /**
     * Debug function (if executes if DEBUG is defined)
     *
     * @param $msg -- Debug message to print to debug log.
     */
    function dbg($msg)
    {
        $uplDir = wp_upload_dir();
        $plugin = plugin_basename(__DIR__);

        $dbgRoot = $uplDir['basedir'] . "/${plugin}";
        // $dbgRoot = "${plugin}/";
        $dbgPath = "${dbgRoot}";

        if (WP_DEBUG === true) {

            if (!file_exists($dbgPath)) {

                error_log("E20R Track: Creating root directory for debug logging: ${dbgPath}");

                // Create the debug logging directory
                wp_mkdir_p( $dbgPath );

                if (!is_writable($dbgRoot)) {

                    error_log('E20R Track: Debug log directory is not writable. exiting.');
                    return;
                }
            }

            // $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log-' . date('Y-m-d', current_time('timestamp')) . '.txt';
            $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log.txt';

            $tid = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . ( isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : 80 ))));

            $dbgMsg = '(' . date('d-m-y H:i:s', current_time('timestamp')) . "-{$tid}) -- " .
                ((is_array($msg) || (is_object($msg))) ? print_r($msg, true) : $msg) . "\n";

            add_log_text($dbgMsg, $dbgFile);
        }
    }
endif;

if (!function_exists('add_log_text')):
    function add_log_text($text, $filename)
    {

        if (!file_exists($filename)) {

            touch($filename);
            chmod($filename, 0640);
        }

        if (filesize($filename) > E20R_MAX_LOG_SIZE) {

            $filename2 = "$filename.old";

            if (file_exists($filename2)) {

                unlink($filename2);
            }

            rename($filename, $filename2);
            touch($filename);
            chmod($filename, 0640);
        }

        if (!is_writable($filename)) {

            error_log("Unable to open debug log file ($filename)");
        }

        if (!$handle = fopen($filename, 'a')) {

            error_log("Unable to open debug log file ($filename)");
        }

        if (fwrite($handle, $text) === FALSE) {

            error_log("Unable to write to debug log file ($filename)");
        }

        fclose($handle);
    }
endif;

if ( !function_exists( 'e20r_load' ) ) {
    function e20r_load()
    {

        dbg("Loading the e20rTracker classes and running init of the e20rTracker() class");
        // dbg($GLOBALS);
        try {

            $e20rTracker = e20rTracker::getInstance();
            $e20rTables = e20rTables::getInstance();

            $e20rUpdateChecker = PucFactory::buildUpdateChecker(
                'https://eighty20results.com/protected-content/e20r-tracker/metadata.json',
                __FILE__,
                'e20r-tracker'
            );

            $e20rTables->init();

            dbg("E20rTracker - Loading hooks.");
            $e20rTracker->loadAllHooks();

        } catch (Exception $e) {
            dbg("Error initializing the Tracker plugin: " . $e->getMessage());
        }
    }
}

if (!function_exists('in_betagroup')) {

    function in_betagroup($user_id)
    {

        $user = get_user_by('id', $user_id);

        if ((!empty($user->roles)) && is_array($user->roles)) {
            if (in_array('nourish-beta', $user->roles)) {

                dbg("User {$user->display_name} is in the Nourish Beta group");
                return true;
            }

        } elseif (!empty($user->roles)) {
            if ($user->roles == 'nourish-beta') {

                dbg("User {$user->display_name} is in the Nourish Beta group");
                return true;
            }
        }

        // dbg("User {$user->display_name} is NOT in the Nourish Beta group");
        return false;
    }
}

if (!function_exists('e20r_ajaxUnprivError')):

    /**
     * Functions returns error message. Used by nopriv Ajax traps.
     */
    function e20r_ajaxUnprivError()
    {

        dbg('Unprivileged ajax call attempted');

        wp_send_json_error(array(
            'ecode' => 3,
            'errno' => 3,
            'err_text' => __('You must be logged in to access/view tracker data', 'e20r_tracker')
        ));
	    exit();
    }


endif;

dbg("e20rTracker::- Loading update checker & autoloader for encryption");
try {

	if ( ! class_exists( '\\PucFactory')) {
		require_once( E20R_PLUGIN_DIR . "classes/controllers/plugin-updates/plugin-update-checker.php" );
	}

	if ( ! class_exists('\\Crypto' ) ) {
        require_once(E20R_PLUGIN_DIR . "classes/controllers/autoload.php");
	}

    global $e20rMeasurementDate;
    global $e20rExampleProgress;

    /*
     * $GLOBALS['e20rTracker']
     * $GLOBALS['e20rClient']
     * $GLOBALS['e20rMeasurements']
     *
     */
//        dbg("Request: " . print_r($_REQUEST, true));

    dbg("\n\n------------------------------------- New Iteration ---------------------------\n\n");
    // Set to false unless the user is accessing the example/demo progress form.
    $e20rExampleProgress = false;

    spl_autoload_register('e20r_autoloader');

    $e20rMeasurementDate = '2014-01-01';

    register_activation_hook( __FILE__, array( e20rTracker::getInstance(), 'activate' ) );

    register_deactivation_hook( __FILE__, array( e20rTracker::getInstance(), 'deactivate' ) );

	add_action( 'init', 'e20r_load', 9 );

} catch (Exception $e) {
    dbg("Error initializing the Tracker plugin: " . $e->getMessage());
}