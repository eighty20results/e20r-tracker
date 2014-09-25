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

if ( ! class_exists( 'E20R_Checkin' ) ):

    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.E20R_Checkin.php" );
    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.exercise.php" );

endif;

if ( ! class_exists( 'S3F_clientData' )):

    require_once( E20R_PLUGIN_DIR . "classes" . DIRECTORY_SEPARATOR . "class.S3F_clientData.php" );

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

if ( ! function_exists( 'activateE20R_Plugin') ):
    function activateE20R_Plugin() {

        global $wpdb;
        global $e20r_db_version;

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        dbg("Loading table SQL");

        $intakeTableSql =
            "CREATE TABLE If NOT EXISTS {$this->tables->client_info} (
                    id int not null auto_increment,
                    user_id int not null,
                    user_dob date not null,
                    height decimal(18, 3) null,
                    heritage int null,
                    waist_circumference decimal(18,3),
                    weight decimal(18,3) null,
                    is_metric tinyint default 0,
                    is_imperial tinyint default 0,
                    is_gb_imperial tinyint default 0,
                    use_pictures tinyint default 0,
                    for_research tinyint default 0,
                    chronic_pain tinyint default 0,
                    injuries tinyint default 0,
                    primary_key (id),
                    key user_id (user_id asc) )
                  {$charset_collate}
                ";

        $measurementTableSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->measurements} (
                    id int not null auto_increment,
                    user_id int not null,
                    recorded_date datetime null,
                    weight decimal(18,3) null,
                    neck decimal(18,3) null,
                    shoulder decimal(18,3) null,
                    chest decimal(18,3) null,
                    arm decimal(18,3) null,
                    waist decimal(18,3) null,
                    hip decimal(18,3) null,
                    thigh decimal(18,3) null,
                    calf decimal(18,3) null,
                    girth decimal(18,3) null,
                    primary key  (id),
                    key user_id ( user_id asc) )
                  {$charset_collate}
              ";

        $itemsTableSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->checkin_items} (
                    id int not null auto_increment,
                    short_name varchar(20) null,
                    program_id int null,
                    item_name varchar(50) null,
                    startdate datetime null,
                    enddate datetime null,
                    item_order int not null default 1,
                    maxcount int null,
                    membership_level_id int not null default 0,
                primary key  (id) ,
                unique key shortname_UNIQUE (short_name asc) )
                {$charset_collate}";

        $businessRulesSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->checkin_rules} (
                    id int not null auto_increment,
                    checkin_id int null,
                    success_rule mediumtext null,
                    primary key  (id),
                    key checkin_id (checkin_id asc) )
                {$charset_collate}";

        $checkinSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->checkin} (
                    id int not null auto_increment,
                    user_id int null,
                    checkin_date datetime null,
                    checkin_id int null,
                    program_id int null, -- Uses the membership_level->ID value (unless it's nourish)
                    primary key  (id) )
                {$charset_collate}";

        require_once( ABSPATH . "wp-admin/includes/upgrade.php" );

        dbg('dbInstall() - Creating tables in database');
        dbDelta( $itemsTableSql );
        dbDelta( $businessRulesSql );
        dbDelta( $checkinSql );
        dbDelta( $measurementTableSql );
        dbDelta( $intakeTableSql );

        add_option( 'e20rTracker_db_version', $e20r_db_version );

        flush_rewrite_rules();
    }
endif;

if ( ! function_exists( 'deactivateE20R_Plugin' ) ):

function deactivateE20R_Plugin() {

    global $wpdb;
    global $e20r_db_version;

    $deleteTables = get_option( 'e20r_clean_tables', false );

    dbg("Loading table SQL");

    if ( $deleteTables !== false) {

        foreach ( $this->tables as $tblName ) {

            dbg( "dbUninstall() - {$tblName} being dropped" );

            $sql = "DROP TABLE IF EXISTS {$tblName}";
            $wpdb->query( $sql );
        }
    }
}
endif;

$e20rTracker = new e20rTracker();
$e20rTracker->init();


