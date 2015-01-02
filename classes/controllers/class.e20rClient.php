<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/22/14
 * Time: 1:16 PM
 */

class e20rClient {

    private $id;
    public $client_loaded = false;

    public $show = null; // Views
    public $actionsLoaded = false;
    public $scriptsLoaded = false;

    public $data = null; // Client Model

    private $program = null; // The Program class for the user

    private $current_programs = array();
    private $current_article = null;

    private $assignments = null;
    public $checkin;

    private $lengthunit = "in";
    private $weightunit = "lbs";

    public function e20rClient( $user_id = null ) {

        if ( ! isset( $user_id ) ) {
            dbg('e20rClient::constructor() - No user ID specified');

            global $current_user;

            if ( isset( $current_user->ID ) ) {
                dbg("e20rClient::constructor() - Using ID {$current_user->id}");
                $user_id = $current_user->ID;
            }
        }

        $this->id = $user_id;
    }

    public function init() {

        global $e20rTracker, $e20rMeasurements, $e20rArticle, $e20rProgram;

        dbg('e20rClient::init() - Running INIT for Client Controller');
        dbg('e20rClient::init() - ' . $e20rTracker->whoCalledMe() );

        if ( $this->id != null ) {

            $this->loadClient( $this->id );

            if ( ! isset( $e20rMeasurements ) ) {
                $e20rMeasurements = new e20rMeasurements( $this->id );
            }

            if ( ! isset( $e20rArticle ) ) {
                $e20rArticle = new e20rArticle();
            }

            if ( ! isset( $this->checkin ) ) {
                $this->checkin = new e20rCheckin( $this->id );
            }
        }

        $this->loadClient();
        $this->client_loaded = true;
    }

    public function isNourishClient( $user_id = 0 ) {

        if ( ( ! $this->id ) && ( $user_id != 0 ) ) {
            $this->id = $user_id;
        }

        dbg("e20rClient::isNourishClient() - is user with id {$this->id} a nourish client?");

        $nourish_levels = array( 16, 21, 22, 23, 18 );

        if ( pmpro_hasMembershipLevel( $nourish_levels, $this->id ) ) {
            dbg("e20rClient::isNourishClient() - user with id {$this->id} is a nourish client");
            return true;
        }

        return false;
    }

    public function clientId() {
        return $this->id;
    }

    public function getLengthUnit() {
        dbg("e20rClient::getLengthUnit() - Returning the current setting for the length unit: {$this->data->info->lengthunits}");
        return $this->data->info->lengthunits;
    }

    public function getWeightUnit() {
        dbg("e20rClient::getWeightUnit() - Returning the current setting for the weight unit: {$this->data->info->weightunits}");
        return $this->data->info->weightunits;
    }

    public function loadClient( $id = null ) {

        if ( $id ) {
            $this->id = $id;
        }

        if ( ! class_exists( 'e20rClientModel' ) ) {
            include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rClientModel.php" );
        }

        if ( ! isset( $this->data->info->id ) ) {
            dbg("e20rClient::loadClient() - Instantiate the client model");
            $this->data = new e20rClientModel( $this->id );
        }

        $this->loadClientInfo( $this->id );

    }

    public function loadClientInfo( $user_id ) {

        $this->id = $user_id;
        $this->program = get_user_meta( $this->id, 'e20r_tracker_programId', true );

        try {

            dbg("Loading data for client model");
            $this->data->load();
        }
        catch ( Exception $e ) {
            dbg("Error loading user data: " . $e->getMessage() );
        }

    }

    public function updateUnitTypes() {


        dbg( "e20rClient::updateUnitTypes() - Attempting to update the Length or weight Units via AJAX");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("e20rClient::updateUnitTypes() - POST content: " . print_r($_POST, true));

        global $current_user, $e20rMeasurements;

        $user_id = isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID;
        $dimension = isset( $_POST['dimension'] ) ? sanitize_text_field( $_POST['dimension'] ) : null;
        $value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : null;

        $userObj = new WP_User( $user_id );

        $e20rMeasurements->setClient( $user_id );

        $this->id = $user_id;

        try {
            if ( ! isset( $this->data ) ) {
                $this->loadClientInfo( $userObj->get( 'user_login' ), $user_id );
            };
        }
        catch ( Exception $e ) {

            dbg("e20rClient::updateUnitTypes() - Error loading client info");
            wp_send_json_error( "Error loading data: " . $e->getMessage() );
            wp_die();
        }

        // Update the data for this user in the measurements table.
        try {

            $e20rMeasurements->updateMeasurementsForType( $dimension, $value );
        }
        catch ( Exception $e ) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurements for new measurement type(s)");
            wp_send_json_error( "Error updating existing data: " . $e->getMessage() );
            wp_die();
        }

        // Save the actual setting for the current user

        if ( $dimension == 'weight' ) {
            $this->weightunit = $value;
        }

        if ( $dimension == 'length' ) {
            $this->lengthunit = $value;
        }

        // Update the settings for the user
        try {
            $this->data->saveUnitInfo( $this->lengthunit, $this->weightunit );
        }
        catch ( Exception $e ) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurement unit for {$dimension}");
            wp_send_json_error( "Unable to save new {$dimension} type " );
            wp_die();
        }

        dbg("e20rClient::updateUnitTypes() - Unit type updated");
        wp_send_json_success( "All data updated ");
        wp_die();
    }

    public function after_gf_submission( $entry, $form ) {

        dbg("gf_after_submission - entry: ". print_r( $entry, true));
        dbg("gf_after_submission - form: ". print_r( $form, true));
    }
/*
    public function ajax_userInfo_callback() {

        dbg("ajax_userInfo_Callback() - Checking access");
        dbg("Received data: " . print_r($_POST, true ) );

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("ajax_userInfo_Callback() - Access approved");

        global $wpdb;

        $var = ( isset( $_POST['measurement-type']) ? sanitize_text_field( $_POST['measurement-type']): null );

        try {

            if ( empty( $this->data ) ) {
                $this->init();
            }

            $userData = $this->data->info->getInfo();
            $retVal = $userData->{$var};

            dbg("Requested variable: {$var} = {$retVal}" );
            echo json_encode( $retVal, JSON_FORCE_OBJECT );
            exit;
        }
        catch ( Exception $e ) {
            dbg("Error loading and returning user data: " . $e->getMessage() );
        }
    }
*/
    public function initClientViews() {

        if ( ! class_exists( 'e20rClientViews' ) ) {
            include_once( E20R_PLUGIN_DIR . "classes/views/class.e20rClientViews.php" );
        }
        try {
            $this->show = new e20rClientViews();
        }
        catch ( Exception $e ) {
            dbg("Error loading views for client controller: " . $e->getMessage() );
        }

    }

    public function getArticleID() {

        global $post;

        // TODO: Simply returns the current POST ID - needs to get the correct article ID at some point.
        return $post->ID;
    }

    public function getInfo() {

        if ( empty( $this->data->info ) ) {

            try {
                $this->loadInfo();
            }
            catch ( Exception $e ) {
                dbg('Error loading user info from the database: ' . $e->getMessage() );
            }
        }

        return $this->info;
    }


    // TODO: Return data that can be viewed both by Shortcode and by back-end. I.e. only fetch data for user specified in request.

    function ajax_getMemberlistForLevel() {

        check_ajax_referer('e20r-tracker-data', 'e20r_tracker_levels_nonce');

        $level = ( isset($_POST['hidden_e20r_level']) ? intval( $_POST['hidden_e20r_level']) : 0 );

        dbg("Level returned: {$level}");

        if ( $level != 0 ) {

            $levelObj = pmpro_getLevel( $level );
            // $this->load_levels( $levelObj->name );
            dbg(" Loading members for {$levelObj->name}");
            $data = $this->viewMemberSelect( $levelObj->name );
        }
        else {

            $this->load_levels();
            $data = $this->viewMemberSelect();
        }

        wp_send_json_success( $data );

    }

    function ajax_clientDetail() {
        dbg('Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");

        dbg("Request: " . print_r($_REQUEST, true));

    }

    function ajax_complianceData() {

        dbg('Requesting Check-In details');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");

        $checkins = new E20Rcheckin();

        // TODO: 10/02/2014 - Multiple steps: For different habits, get & generate different graphs.
        // NOTE: Special care for existing Nourish group... :(
        // Get the list of check-ins so far - SQL.
        // Calculate the max # of check-ins per check-in type (day/calendar based)
        //


    }

    function ajax_assignmentData() {
        dbg('Requesting Assignment details');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");
    }

    private function validateClientAccess( $clientId ) {

        global $current_user;

        dbg("Client to validate: " . $clientId );

        if ( $clientId ) {

            dbg("Real user Id provided ");
            $client = get_user_by("id", $clientId );

            if ( ($current_user->ID != $clientId ) &&  ( $current_user->membership_level->id == 18 ) ) {
                return true;
            }
            elseif ( $current_user->ID == $clientId ) {
                return true;
            }
            // Make sure the $current_user has the right to view the data for $clientId

        }

        return false;
    }
}