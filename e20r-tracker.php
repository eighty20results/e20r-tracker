<?php

/*
Plugin Name: E20R Tracker
Plugin URI: http://eighty20results.com/e20r-tracker
Description: Track Nutrition & Fitness Activities
Version: 0.1
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: http://eighty20results.com/thomas-sjolshagen
License: GPL2
*/

/* Enable / Disable DEBUG logging to separate file */
define( 'E20R_T_DEBUG', true );

define( 'E20R_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'E20R_PLUGINS_URL', plugins_url( '', __FILE__ ) );
define( 'E20R_QUESTIONS', 1 );
define( 'E20R_ANSWERS', 1 );
define( 'CONST_SATURDAY', 6 );
define( 'CONST_SUNDAY', 0 );
define( 'CONST_MONDAY', 1 );
define( 'GF_PHOTOFORM_ID', 45 );

define ('CONST_MEASUREMENTDAY', 6 );

global $e20r_db_version;

$e20r_db_version = "1.0";

if ( ! function_exists( 'dbg' ) ):

    /**
     * Debug function (if executes if DEBUG is defined)
     *
     * @param $msg -- Debug message to print to debug log.
     */
    function dbg($msg)
    {
        $dbgPath = E20R_PLUGIN_DIR . 'debug';

        if (E20R_T_DEBUG)
        {

            if (!  file_exists( $dbgPath )) {
                // Create the debug logging directory
                mkdir( $dbgPath, 0750 );

                if (! is_writable( $dbgPath )) {
                    error_log('E20R Track: Debug log directory is not writable. exiting.');
                    return;
                }
            }

            $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log-' . date('Y-m-d', current_time('timestamp') ) . '.txt';

            if ( ($fh = fopen($dbgFile, 'a')) !== false ) {

                // Format the debug log message
                $tid = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));
                $dbgMsg = '(' . date('d-m-y H:i:s', current_time('timestamp' ) ) . "-{$tid}) -- ". $msg;

                // Write it to the debug log file
                fwrite( $fh, $dbgMsg . "\r\n" );
                fclose( $fh );
            }
            else
                error_log('E20R Track: Unable to open debug log');
        }
    }
endif;

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

        $e20rTables->init();
        // $e20rTracker->init();

        if ( ! isset( $e20rClient ) ) {
            dbg("E20R Tracker Init: Loading e20rClient class");
            $e20rClient = new e20rClient();
        }

        if ( ! isset( $e20rMeasurements ) ) {
            dbg("E20R Tracker Init: Loading e20rMeasurements class");
            $e20rMeasurements = new e20rMeasurements();
        }

        if ( ! isset( $e20rProgram ) ) {
            $e20rProgram = new e20rProgram();
        }

        if ( ! isset( $e20rCheckin ) ) {
            dbg("E20R Tracker Init: Loading e20rCheckin class");
            $e20rCheckin = new e20rCheckin();
        }


        if ( ! isset( $e20rArticle ) ) {
            $e20rArticle = new e20rArticle();
        }


        if ( ! isset( $e20rExercise ) ) {
            $e20rExercise = new e20rExercise();
        }

        if ( ! isset( $e20rWorkout ) ) {
            $e20rWorkout = new e20rWorkout();
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

        dbg("User {$user->display_name} is NOT in the Nourish Beta group");
        return false;
    }
}

if ( ! function_exists( 'e20r_ajaxUnprivError' ) ):

    /**
     * Functions returns error message. Used by nopriv Ajax traps.
     */
    function e20r_ajaxUnprivError() {

        dbg('Unprivileged ajax call attempted');

        wp_send_json_error( __('You must be logged in to access/view tracker data', 'e20r_tracker') );
    }


endif;

if ( ! class_exists( 'e20rTracker' ) ):

    try {

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rTables.php" );

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rTrackerModel.php");
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rTracker.php");

        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rSettingsModel.php");
        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rSettings.php");
//        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rSettingsView.php");

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

        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rArticle.php" );

        require_once( E20R_PLUGIN_DIR . "classes/controllers/class.e20rCheckin.php" );
        require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rCheckinModel.php" );
        require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rCheckinView.php" );

        // require_once( E20R_PLUGIN_DIR . "classes/class.e20rWorkouts.php" );

        require_once( E20R_PLUGIN_DIR . "classes/class.e20rAssignment.php" );

        global $e20rTracker;
        global $e20rClient;
        global $e20rMeasurements;
        global $e20rArticle;
        global $e20rProgram;
        global $e20rTables;
        global $e20rExercise;
        global $e20rWorkout;
        global $e20rCheckin;
        global $e20rMeasurementDate;
        global $e20rExampleProgress;

        /*
         * $GLOBALS['e20rTracker']
         * $GLOBALS['e20rClient']
         * $GLOBALS['e20rMeasurements']
         *
         */
//        dbg("Request: " . print_r($_REQUEST, true));

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

        register_activation_hook( __FILE__, array( &$e20rTracker, 'e20r_tracker_activate' ) );
        register_deactivation_hook( __FILE__, array( &$e20rTracker, 'e20r_tracker_deactivate' ) );

    }
    catch ( Exception $e ) {
        dbg("Error initializing the Tracker plugin: " . $e->getMessage() );
    }

endif;
