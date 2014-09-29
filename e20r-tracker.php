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
define('E20R_T_DEBUG', true);

define('E20R_PLUGIN_DIR', plugin_dir_path(__FILE__) );
define('E20R_PLUGINS_URL', plugins_url( '', __FILE__ ) );

if ( ! class_exists( 'e20rTracker' ) ):

    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR ."class.e20rTracker.php");

endif;

if (! class_exists( 'e20rPrograms' ) ):

    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.e20rPrograms.php" );

endif;

if ( ! class_exists( 'S3F_clientData' )):

    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.S3F_clientData.php" );

endif;

if ( ! class_exists( 'E20Rcheckin' ) ):

    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.E20Rcheckin.php" );
//     require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.ExercisePrograms.php" );

endif;


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

            $dbgFile = $dbgPath . DIRECTORY_SEPARATOR . 'e20r_debug_log-' . date('Y-m-d') . '.txt';

            if ( ($fh = fopen($dbgFile, 'a')) !== false ) {

                // Format the debug log message
                $dbgMsg = '(' . date('d-m-y H:i:s') . ') -- '. $msg;

                // Write it to the debug log file
                fwrite( $fh, $dbgMsg . "\r\n" );
                fclose( $fh );
            }
            else
                error_log('E20R Track: Unable to open debug log');
        }
    }


endif;


$e20rTracker = new e20rTracker();
$e20rTracker->init();

dbg("Register activation/deactiviation hooks");
register_activation_hook( __FILE__, array( &$e20rTracker, 'e20r_tracker_activate' ) );
register_deactivation_hook( __FILE__, array( &$e20rTracker, 'e20r_tracker_deactivate' ) );

