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
    require_once( plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "class.E20R_Checkin.php" );
    require_once( plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "class.exercise.php" );
endif;

if ( ! class_exists( 'S3F_clientData' )):

    require_once( plugin_dir_path( __FILE__) . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "class.S3F_clientData.php" );

endif;


global $e20r_db_version;

$e20r_db_version = "1.0";

if ( ! function_exists( 'e20r_loadAdmin') ):

    add_action( 'admin_menu', 'e20r_loadAdmin' );

    function e20r_loadAdmin() {
        dbg("loadAdmin() - Starting");
        $adminPgs = new S3F_clientData();
        $adminPgs->registerAdminPages();

    }

endif;

if ( ! function_exists( 'e20r_loadscripts_graphs' ) ):

    /**
     * Add scripts to the front-end UI (wp-admin)
     */
    add_action( 'wp_enqueue_scripts', 'e20r_loadscripts_graphs' );

    /* Load graphing scripts */
    function e20r_loadscripts_graphs() {

        wp_deregister_script('jqplot' );
        wp_enqueue_script( 'jqplot', plugins_url( '/js/jQPlot/core/jqplot.min.js', __FILE__ ), false, '0.1' );

        wp_deregister_style( 'jqplot' );
        wp_enqueue_style( 'jqplot', plugins_url( '/js/jQPlot/core/jquery.jqplot.min.css', __FILE__ ), false, '0.1' );

    }
endif;

if (! function_exists( 'e20r_admin_scripts' ) ):

    /**
     * Add scripts to the back-end UI (wp-admin)
     */
    add_action( 'admin_enqueue_scripts', 'e20r_loadscripts_graphs' );
    add_action( 'admin_enqueue_scripts', 'e20r_admin_scripts' );

    /**
     * Load all JS for Admin page
     */
    function e20r_admin_scripts()
    {

        wp_register_script('e20r-tracker-admin', plugins_url('/js/e20r-tracker-admin.js', __FILE__), array('jquery'), '0.1', true);

        /* Localize ajax script */
        wp_localize_script('e20r-tracker-admin', 'e20rtracker',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'lang' => array(
                    'save' => __('Update', 'e20rtracker'),
                    'saving' => __('Saving', 'e20rtracker'),
                    'saveSettings' => __('Update Settings', 'e20rtracker'),
                    'delay_change_confirmation' => __('Changing the delay type will erase all existing posts or pages in the Sequence list. (Cancel if your are unsure)', 'e20rtracker'),
                    'saving_error_1' => __('Error saving sequence post [1]', 'e20rtracker'),
                    'saving_error_2' => __('Error saving sequence post [2]', 'e20rtracker'),
                    'remove_error_1' => __('Error deleting sequence post [1]', 'e20rtracker'),
                    'remove_error_2' => __('Error deleting sequence post [2]', 'e20rtracker'),
                    'undefined' => __('Not Defined', 'e20rtracker'),
                    'unknownerrorrm' => __('Unknown error removing post from sequence', 'e20rtracker'),
                    'unknownerroradd' => __('Unknown error adding post to sequence', 'e20rtracker'),
                    'daysLabel' => __('Delay', 'e20rtracker'),
                    'daysText' => __('Days to delay', 'e20rtracker'),
                    'dateLabel' => __('Avail. on', 'e20rtracker'),
                    'dateText' => __('Release on (YYYY-MM-DD)', 'e20rtracker'),
                )
            )
        );

        wp_enqueue_style("e20rtracker_css", plugins_url('/css/e20r-tracker.css', __FILE__ ));
        wp_enqueue_script('e20r-tracker-admin');

    }
endif;

if ( ! function_exists( 'dbgOut' ) ):

    /**
     * Debug function (if executes if DEBUG is defined)
     *
     * @param $msg -- Debug message to print to debug log.
     */
    function dbg($msg)
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

        dbg('dbInstall() - Creating tables in database');
        dbDelta( $itemsTableSql );
        dbDelta( $businessRulesSql );
        dbDelta( $checkinSql );

        add_option( 'e20rTracker_db_version', $e20r_db_version );

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

            dbg("dbUninstall() - {$tblName} being dropped");

            $sql = "DROP TABLE IF EXISTS {$tblName}";
            $wpdb->query( $sql );
        }
    }
endif;


