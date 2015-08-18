<?php

/*
Plugin Name: E20R Tracker
Plugin URI: http://eighty20results.com/e20r-tracker
Description: Track Coaching Activities
Version: 1.1.18
Author: Wicked Strong Chicks, LLC <info@eighty20results.com>
Author URI: http://eighty20results.com/thomas-sjolshagen
Text Domain: e20rtracker
Domain Path: /languages
License: GPLv2
*/
/*
    Copyright 2015 Thomas Sjolshagen / Wicked Strong Chicks, LLC (info@eighty20results.com)

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

define( 'E20R_VERSION', '1.1.18' );
define( 'E20R_RUN_UNSERIALIZE', 0 ); // 0 == Do NOT Run, 1 == Run
define( 'E20R_DB_VERSION', '10');
define( 'E20R_NEW_DB_VERSION', '9');
define( 'E20R_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'E20R_PLUGINS_URL', plugins_url( '', __FILE__ ) );
define( 'E20R_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'E20R_MAX_LOG_SIZE', 3*1024*1024 );
define( 'E20R_UPCOMING_WEEK', 1002 );
define( 'E20R_CURRENT_WEEK', 1001 );
define( 'E20R_PREVIOUS_WEEK', 1000 );
define( 'E20R_QUESTIONS', 1 );
define( 'E20R_ANSWERS', 1 );
define( 'CONST_SATURDAY', 6 );
define( 'CONST_SUNDAY', 0 );
define( 'CONST_MONDAY', 1 );
define( 'GF_PHOTOFORM_ID', 45 );
define( 'E20R_SURVEY_TYPE_WELCOME', 1);
define( 'E20R_SURVEY_TYPE_OTHER', 9999);

// define( 'URL_TO_PROGRESS_FORM', site_url('/coaching/progress-update/'));
define( 'E20R_COACHING_URL' , site_url( '/coaching/' ) );
define( 'URL_TO_PROGRESS_FORM', E20R_COACHING_URL. 'weekly-progress/');
define( 'URL_TO_CHECKIN_FORM', site_url('/coaching/home') );

define ('CONST_MEASUREMENTDAY', 6 );
define( 'TOTAL_GIRTH_MEASUREMENTS', 8 ); // Total number of girth measurements expected

define( 'CHECKIN_ACTION', 1 );
define( 'CHECKIN_ASSIGNMENT', 2 );
define( 'CHECKIN_SURVEY', 3 );
define( 'CHECKIN_ACTIVITY', 4 );
define( 'CHECKIN_NOTE', 5 );
define( 'CHECKIN_ACTION_AND_ACTIVITY', 14 );

define( 'CONST_NULL_ARTICLE', -9999 );
define( 'CONST_DEFAULT_ASSIGNMENT', -9999 );
define( 'CONST_MAXDAYS_FUTURE', 1 );
define( 'CONST_MAXDAYS_PAST', 2 );

global $e20r_db_version;

$e20r_db_version = "1.0";

// TODO: Remove this before going live.
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
// set_error_handler("exception_error_handler");

if ( ! function_exists( 'dbg' ) ):

    /**
     * Debug function (if executes if DEBUG is defined)
     *
     * @param $msg -- Debug message to print to debug log.
     */
    function dbg($msg)
    {
	    $uplDir = wp_upload_dir();
	    $plugin = plugin_basename( __DIR__ );

        $dbgRoot = $uplDir['basedir'] . "/${plugin}";
	    // $dbgRoot = "${plugin}/";
	    $dbgPath = "${dbgRoot}/debug";

        if (WP_DEBUG === true) {

            if (!file_exists($dbgPath)) {

                error_log("E20R Track: Creating root directory for debug logging: ${dbgPath}");

                // Create the debug logging directory
                wp_mkdir_p($dbgPath, 0750);

                if (!is_writable($dbgRoot)) {

                    error_log('E20R Track: Debug log directory is not writable. exiting.');
                    return;
                }
            }

            // $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log-' . date('Y-m-d', current_time('timestamp')) . '.txt';
            $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log.txt';

            $tid = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));

            $dbgMsg = '(' . date('d-m-y H:i:s', current_time('timestamp')) . "-{$tid}) -- " .
                ((is_array($msg) || (is_object($msg))) ? print_r($msg, true) : $msg) . "\n";

            add_log_text($dbgMsg, $dbgFile);
        }
    }
endif;

function add_log_text($text, $filename) {

    if ( !file_exists($filename) ) {

        touch( $filename );
        chmod( $filename, 0640 );
    }

    if ( filesize( $filename ) > E20R_MAX_LOG_SIZE ) {

        $filename2 = "$filename.old";

        if ( file_exists( $filename2 ) ) {

            unlink($filename2);
        }

        rename($filename, $filename2);
        touch($filename);
        chmod($filename,0640);
    }

    if ( !is_writable( $filename ) ) {

        error_log( "Unable to open debug log file ($filename)" );
    }

    if ( !$handle = fopen( $filename, 'a' ) ) {

        error_log("Unable to open debug log file ($filename)");
    }

    if ( fwrite( $handle, $text ) === FALSE ) {

        error_log("Unable to write to debug log file ($filename)");
    }

    fclose($handle);
}

function loadTracker() {

    dbg("Loading the e20rTracker classes and running init of the e20rTracker() class");

    try {

        global $e20rTables;
        global $e20rTracker;
        global $e20rClient;
        global $e20rMeasurements;
        global $e20rArticle;
        global $e20rProgram;
        global $e20rTables;
        global $e20rExercise;
        global $e20rWorkout;
        global $e20rCheckin;
        global $e20rAssignment;
	    global $e20r_isClient;

        $e20rUpdateChecker = PucFactory::buildUpdateChecker(
            'https://eighty20results.com/protected-content/e20r-tracker/metadata.json',
            __FILE__,
            'e20r-tracker'
        );

        $e20rTables->init();
        // $e20rTracker->init();

        if ( ! isset( $e20rProgram ) ) {
            dbg("E20R Tracker Init: Loading e20rProgram class");
            $e20rProgram = new e20rProgram();
        }

        if ( ! isset( $e20rMeasurements ) ) {
            dbg("E20R Tracker Init: Loading e20rMeasurements class");
            $e20rMeasurements = new e20rMeasurements();
        }

        if ( ! isset( $e20rCheckin ) ) {
            dbg("E20R Tracker Init: Loading e20rCheckin class");
            $e20rCheckin = new e20rCheckin();
        }


        if ( ! isset( $e20rArticle ) ) {
            dbg("E20R Tracker Init: Loading e20rArticle class");
            $e20rArticle = new e20rArticle();
        }


        if ( ! isset( $e20rExercise ) ) {
            dbg("E20R Tracker Init: Loading e20rExercise class");
            $e20rExercise = new e20rExercise();
        }

        if ( ! isset( $e20rWorkout ) ) {
            dbg("E20R Tracker Init: Loading e20rWorkout class");
            $e20rWorkout = new e20rWorkout();
        }

        if ( ! isset( $e20rClient ) ) {
            dbg("E20R Tracker Init: Loading e20rClient class");
            $e20rClient = new e20rClient();
        }

        if ( ! isset( $e20rAssignment ) ) {
            dbg("E20R Tracker Init: Loading e20rAssignment class");
            $e20rAssignment = new e20rAssignment();
        }

        $e20rTracker->loadAllHooks();


    }
    catch ( Exception $e ) {
        dbg("Error initializing the Tracker plugin: " . $e->getMessage() );
    }
}

if ( ! function_exists( 'in_betagroup' ) ) {

    function in_betagroup( $user_id ) {

        $user = get_user_by( 'id', $user_id );

        if ( ( ! empty( $user->roles ) ) && is_array( $user->roles ) ) {
            if ( in_array( 'nourish-beta', $user->roles ) ) {

                dbg("User {$user->display_name} is in the Nourish Beta group");
                return true;
            }

        }
        elseif ( ! empty( $user->roles ) ) {
            if ( $user->roles == 'nourish-beta' ) {

                dbg("User {$user->display_name} is in the Nourish Beta group");
                return true;
            }
        }

        // dbg("User {$user->display_name} is NOT in the Nourish Beta group");
        return false;
    }
}

if ( ! function_exists( 'e20r_ajaxUnprivError' ) ):

    /**
     * Functions returns error message. Used by nopriv Ajax traps.
     */
    function e20r_ajaxUnprivError() {

        dbg('Unprivileged ajax call attempted');

        wp_send_json_error( array(
            'ecode' => 3,
            'errno' => 3,
            'err_text' => __('You must be logged in to access/view tracker data', 'e20r_tracker')
        ) );
    }


endif;

if ( ! class_exists( 'e20rTracker' ) ):

    try {

        require_once( E20R_PLUGIN_DIR . "classes/controllers/plugin-updates/plugin-update-checker.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rTables.php" );

        require_once( E20R_PLUGIN_DIR . "classes/controllers/autoload.php" );
        // require_once( E20R_PLUGIN_DIR . "classes/controllers/class.Crypto.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rTrackerModel.php");
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rTracker.php");

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rSettingsModel.php");
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rSettings.php");
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rSettingsView.php");

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rMeasurementModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rMeasurements.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rMeasurementViews.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rClientModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rClient.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rClientViews.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rProgramModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rProgram.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rProgramView.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rExerciseModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rExercise.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rExerciseView.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rWorkoutModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rWorkout.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rWorkoutView.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rArticleModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rArticle.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rArticleView.php" );

        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rCheckin.php" );
        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rCheckinModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rCheckinView.php" );

        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rAssignment.php" );
        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rAssignmentModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rAssignmentView.php" );

        global $e20rTracker;
        global $e20rClient;
        global $e20rMeasurements;
        global $e20rArticle;
        global $e20rProgram;
        global $e20rTables;
        global $e20rExercise;
        global $e20rWorkout;
        global $e20rCheckin;
        global $e20rAssignment;
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

        if ( ! isset( $e20rTables ) ) {

            dbg("E20R Tracker Init: Loading e20rTables class");
            $e20rTables = new e20rTables();
        }

        if ( ! isset( $e20rTracker ) ) {

            dbg("E20R Tracker Init: Loading e20rTracker class");
            $e20rTracker = new e20rTracker();
        }

        $e20rMeasurementDate = '2014-01-01';

        add_action( 'init' , 'loadTracker', 9 );

        register_activation_hook( __FILE__, array( &$e20rTracker, 'activate' ) );

        register_deactivation_hook( __FILE__, array( &$e20rTracker, 'deactivate' ) );

    }
    catch ( Exception $e ) {
        dbg("Error initializing the Tracker plugin: " . $e->getMessage() );
    }

endif;
