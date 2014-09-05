<?php
/**
 * Unit tests for Database access & manipulation
 * By: Thomas Sjolshagen @ Eighty / 20 results
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

        // Create database tables
        e20r_tracker_dbInstall();

        // Iterate through and check that DB was correctly created
        foreach ( $this->plugin->_tables as $tblName ) {

            $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $tblName);
            $db_res = $wpdb->get_var( $sql );

            // Thest that the table is present in the DB.
            $this->assertEquals( $tblName, $db_res );

        } // End foreach

        // Do whatever uninstall magic we need to do
        e20r_tracker_dbUninstall();

        // Validate that the tables are now gone.
        foreach ( $this->plugin->_tables as $tblName ) {

            $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $tblName);
            $db_res = $wpdb->get_var( $sql );

            $this->assertNull( $db_res );
        }

    } // End testCreateTable

    function testAddCheckinItem() {

        global $wpdb;

        // Create database tables
        e20r_tracker_dbInstall();

        $ID = $this->plugin->addItem('nourish_test', 'Test Habit for Nourish', '08/22/2014', null, 1, 1, 14);
        $ID2 = $this->plugin->addItem('nourish_test2', 'Test Habit for Nourish', '07/22/2014', '07/24/2014', 1, 1, 12);

        dbgOut("Updated data from DB:" . print_r( $wpdb->get_row( "SELECT * FROM {$this->plugin->_tables['items']}" ), true));

        $readID = $wpdb->get_var( "SELECT id FROM {$this->plugin->_tables['items']} WHERE id = {$ID}");

        $this->assertEquals( $ID, $readID );

        $this->plugin->delItem( $ID );

        // Do whatever uninstall magic we need to do
        e20r_tracker_dbUninstall();

    }

    function testUpdateCheckinItem() {

        global $wpdb;

        // Create database tables
        e20r_tracker_dbInstall();

        $ID = $this->plugin->addItem('nourish_test', 'Test Habit for Nourish', '08/22/2014', null, 1, 1, 14);
        dbgOut("Updated data from DB:" . print_r( $wpdb->get_row( "SELECT * FROM {$this->plugin->_tables['items']} WHERE id = {$ID}" ), true));

        $readID = $wpdb->get_var( "SELECT id FROM {$this->plugin->_tables['items']}  WHERE id = {$ID}");

        if ($ID == $readID) {

            $data = array(
                'short_name' => 'nourish_test1',
                'item_name' => 'Updated test habit for Nourish',
                'startdate' => '08/23/2014',
            );

            $this->plugin->editItem( $ID, $data );

            // dbgOut("Updated data from DB:" . print_r( $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}e20r_checkinItems WHERE id = {$ID}" ), true));

        }

        // Do whatever uninstall magic we need to do
        e20r_tracker_dbUninstall();


    }
}// The end (end class)
 