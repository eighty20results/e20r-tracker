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
        global $e20rTracker;
        $this->plugin = $e20rTracker;

    } // end setUp()

    function testPluginInitialization() {
        $this->assertFalse( null == $this->plugin );
    } // End testPluginInit

    function configureDB() {

        global $e20rTracker;
        $e20rTracker->e20r_tracker_activate();
    }

    function teardownDB() {

        global $e20rTracker;

        if ( $e20rTracker->updateSetting( 'delete_tables', true ) && $e20rTracker->updateSetting( 'purge_tables', false )) {
            $e20rTracker->e20r_tracker_deactivate();
        }
        else throwException("Unable to set the delete_tables setting to true");
    }

    function testCreateTables() {

        global $wpdb, $e20rTracker;

        // Create database tables
        $e20rTracker->e20r_tracker_activate();


        // Iterate through and check that DB was correctly created
        foreach ( $e20rTracker->tables as $tblName ) {

            $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $tblName);
            $db_res = $wpdb->get_var( $sql );

            // Thest that the table is present in the DB.
            $this->assertEquals( $tblName, $db_res );

        } // End foreach

        // Do whatever uninstall magic we need to do
        $this->teardownDB();

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
        $this->configureDB();

        $ID = $this->plugin->addItem('nourish_test', 'Test Habit for Nourish', '08/22/2014', null, 1, 1, 14);
        $ID2 = $this->plugin->addItem('nourish_test2', 'Test Habit for Nourish', '07/22/2014', '07/24/2014', 1, 1, 12);

        dbgOut("Updated data from DB:" . print_r( $wpdb->get_row( "SELECT * FROM {$this->plugin->_tables['items']}" ), true));

        $readID = $wpdb->get_var( "SELECT id FROM {$this->plugin->_tables['items']} WHERE id = {$ID}");

        $this->assertEquals( $ID, $readID );

        $this->plugin->delItem( $ID );

        // Do whatever uninstall magic we need to do
        $this->teardownDB();

    }

    function testUpdateCheckinItem() {

        global $wpdb;

        // Create database tables
        $this->configureDB();

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

        // Do whatever uninstall magic we php -need to do
        $this->teardownDB();

    }

    function testInitMeasurements() {

        global $wpdb;

        $this->configureDB();

        $this->teardownDB();
    }
}// The end (end class)
 
