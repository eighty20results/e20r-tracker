<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 11:23 AM
 */

$path = plugin_dir_path( __FILE__ );
echo $path;
require_once( $path );

class e20rArticleTest extends PHPUnit_Framework_TestCase {

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
}
