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

if ( ! class_exists( 'E20R_Checkin' ) ):
    require_once(plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "class.checkin.php");
    require_once(plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "class.exercise.php");
endif;

global $e20r_db_version;

$e20r_db_version = "1.0";

if ( ! function_exists( 'dbgOut' ) ):

    /**
     * Debug function (if executes if DEBUG is defined)
     *
     * @param $msg -- Debug message to print to debug log.
     */
    function dbgOut($msg)
    {
        $dbgPath = plugin_dir_path( __FILE__ ) . DIRECTORY_SEPARATOR . 'debug';

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

if( ! function_exists( 'e20r_tracker_dbInstall' ) ):

    register_activation_hook( __FILE__, 'e20r_tracker_dbInstall');

    function e20r_tracker_dbInstall() {

        global $wpdb;
        global $e20r_db_version;

        $cItems = $wpdb->prefix . 'e20r_checkinItems';
        $cRules = $wpdb->prefix . 'e20r_checkinRules';
        $cTable = $wpdb->prefix . 'e20r_checkin';

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $itemsTableSql =
            "CREATE TABLE IF NOT EXISTS {$cItems} (
                id int not null auto_increment,
                shortname varchar(20) null,
                program_id int null,
                itemname varchar(50) null,
                startdate datetime null,
                enddate datetime null,
                item_order int not null default 1,
                maxcount int null,
                membership_level_id int not null default 1,
            primary key  (id) ,
            unique key shortname_UNIQUE (shortname asc) )
            {$charset_collate}";

        $businessRulesSql =
            "CREATE TABLE IF NOT EXISTS {$cRules} (
                id int not null auto_increment,
                checkin_id int null,
                success_rule mediumtext null,
                primary key  (id),
                key checkin_id (checkin_id asc) )
            {$charset_collate}";

        $checkinSql =
            "CREATE TABLE IF NOT EXISTS {$cTable} (
                id int not null auto_increment,
                user_id int null,
                checkin_date datetime null,
                checkin_id int null,
                program_id int null,
                primary key  (id) )
            {$charset_collate}";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbgOut('dbInstall() - Creating tables in database');
        dbDelta( $itemsTableSql );
        dbDelta( $businessRulesSql );
        dbDelta( $checkinSql );

        // add_option( 'e20rTracker_db_version', $e20r_db_version );

        flush_rewrite_rules();
    };

endif;

if ( ! function_exists( 'e20r_tracker_dbUninstall' ) ):

    register_deactivation_hook( __FILE__, 'e20r_tracker_dbUninstall');

    function e20r_tracker_dbUninstall() {

        global $wpdb;
        global $e20r_db_version;

        $tableList = array(
            $wpdb->prefix . 'e20r_checkinItems',
            $wpdb->prefix . 'e20r_checkinRules',
            $wpdb->prefix . 'e20r_checkin',
        );

        foreach ( $tableList as $tblName ) {

            dbgOut("dbUninstall() - {$tblName} being dropped");

            $sql = "DROP TABLE IF EXISTS {$tblName}";
            $wpdb->query( $sql );
        }
    }
endif;


