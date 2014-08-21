<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 8/20/14
 * Time: 10:40 PM
 */

require_once('./e20r-tracker.php');

class E20R_Checkin_DBTests extends WP_UnitTestCase {

    private $plugin;

    function setUp() {

        parent::setUp();
        $this->plugin = $GLOBALS['E20R_Checkin'];

    } // end setUp()

    function testPluginInitialization() {
        $this->assertFalse( null == $this->plugin );
    } // End testPluginInit

    function testCreateTables() {

        global $wpdb;

        $tableList = array(
            $wpdb->prefix . 'e20r_checkinItems',
            $wpdb->prefix . 'e20r_checkinRules',
            $wpdb->prefix . 'e20r_checkin',
        );

        // Create database tables
        e20r_tracker_dbInstall();

        // Iterate through and check that DB was correctly created
        foreach ( $tableList as $tblName ) {

            $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $tblName);
            $db_res = $wpdb->get_var( $sql );

            // Thest that the table is present in the DB.
            $this->assertEquals( $tblName, $db_res );

        } // End foreach
    } // End testCreateTable

    function testRemoveTables() {

        global $wpdb;

        $tableList = array(
            $wpdb->prefix . 'e20r_checkinItems',
            $wpdb->prefix . 'e20r_checkinRules',
            $wpdb->prefix . 'e20r_checkin',
        );

        // Validate that tables are present in the DB
        foreach ( $tableList as $tblName ) {

            $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $tblName);
            $db_res = $wpdb->get_var( $sql );

            $this->assertEquals( $tblName, $db_res );
        }

        // Do whatever uninstall magic we need to do
        e20r_tracker_dbUninstall();

        // Validate that the tables are now gone.
        foreach ( $tableList as $tblName ) {

            $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $tblName);
            $db_res = $wpdb->get_var( $sql );

            $this->assertNull( $db_res );
        }
    }

}// The end (end class)
 