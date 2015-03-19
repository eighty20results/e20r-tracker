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

    public $actionsLoaded = false;
    public $scriptsLoaded = false;

    private $model = null; // Client Model.
    private $view = null; // Client Views.

    private $weightunits;
    private $lengthunits;

    public function __construct( $user_id = null) {

        if ( $user_id !== null ) {

            $this->id = $user_id;
        }

        $this->model = new e20rClientModel();
        $this->view = new e20rClientViews( $this->id );
    }

    public function init() {

        global $e20rTracker;

        dbg('e20rClient::init() - Running INIT for e20rClient Controller');
        dbg('e20rClient::init() - ' . $e20rTracker->whoCalledMe() );

        if ( $this->client_loaded !== TRUE ) {

            $this->model->setUser( $this->id );
            $this->model->load();
            $this->client_loaded = TRUE;
        }

    }

    public function isNourishClient( $user_id = 0 ) {

        dbg("e20rClient::isNourishClient() - is user with id {$user_id} a nourish client?");

        if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {

            dbg("e20rClient::isNourishClient() - Checking against Paid Memberships Pro");
            // TODO: Fetch this from an option (multi-select on plugin settings page)
	        $nourish_levels = array( 16, 21, 22, 23, 18 );

            if ( pmpro_hasMembershipLevel( $nourish_levels, $user_id ) ) {

                dbg("e20rClient::isNourishClient() - User with id {$user_id} has a Nourish Coaching membership");
                return true;
            }
        }

        return false;
    }

    public function clientId() {

        return $this->id;
    }

    public function getLengthUnit() {

        return $this->model->getData( $this->id, 'lengthunits');
    }

    public function getWeightUnit() {

        return $this->model->getData( $this->id, 'weightunits' );
    }

    public function getBirthdate( $user_id ) {

        return $this->model->getData( $user_id, 'birthdate' );
    }

    public function getUploadPath( $user_id ) {

        return $this->model->getData( $user_id, 'program_photo_dir' );
    }

    public function getUserImgUrl( $who, $when, $imageSide ) {

        global $e20rTables;

        if ( $this->isNourishClient( $who ) &&  $e20rTables->isBetaClient() ) {

            return $this->model->getBetaUserUrl( $who, $when, $imageSide );
        }

        return false;
    }

    public function setClient( $userId ) {

        $this->id = $userId;
        $this->model->setUser( $this->id );
    }

    public function getData( $clientId ) {

        if ( ! $this->client_loaded ) {

            $this->setClient( $clientId );
            $this->init();
        }

        $data = $this->model->getData( $this->id );
        dbg("e20rClient::getData() - Returned data for {$this->id} from client_info table:");
        dbg($data);

        return $data;
    }

	public function getGender() {

		return $this->model->getData( $this->id, 'gender');
	}

    public function completedInterview( $userId ) {

        $data = $this->model->getData( $userId, 'weight_loss');

        return ( ! empty( $data ) ? true : false );
    }

    /*
    public function saveNewUnit( $type, $unit ) {

        switch ($type) {

            case 'length':

                dbg("e20rClient::saveNewUnit() - Saving new length unit: {$unit}");
                $this->model->info->saveUnitInfo( $unit, $this->getWeightUnit() );
                break;

            case 'weight':

                dbg("e20rClient::saveNewUnit() - Saving new weight unit: {$unit}");
                $this->model->info->saveUnitInfo( $this->getLengthUnit(), $unit );
                break;
        }

        return true;
    }
*/
    public function loadClientInfo( $user_id ) {

        try {

            dbg("e20rClient::loadClientInfo() - Loading data for client model");
            $this->model->setUser($user_id);
            $this->model->load();

        }
        catch ( Exception $e ) {

            dbg("Error loading user data for ({$user_id}): " . $e->getMessage() );
        }

    }

    /*
public function loadClient( $id = null ) {

    if ( $id ) {
        $this->id = $id;
    }

    $this->model->setUser( $this->id );
    $this->model->load();

}
*/
    /*
    public function afterGFSubmission( $entry, $form ) {

        dbg("gf_after_submission - entry: ". print_r( $entry, true));
        dbg("gf_after_submission - form: ". print_r( $form, true));
    }
    */
/*
    public function ajax_userInfo_callback() {

        dbg("ajax_userInfo_Callback() - Checking access");
        dbg("Received data: " . print_r($_POST, true ) );

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("ajax_userInfo_Callback() - Access approved");

        global $wpdb;

        $var = ( isset( $_POST['measurement-type']) ? sanitize_text_field( $_POST['measurement-type']): null );

        try {

            if ( empty( $this->model ) ) {
                $this->init();
            }

            $userData = $this->model->info->getInfo();
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
    /**
     * Render page on back-end for client data (admin selectable).
     */
    public function render_client_page( $lvlName = '', $client_id = 0 ) {

        global $current_user;

        if ( $client_id != 0 ) {

            $this->setClient( $client_id );
        }

        $this->init();

        echo $this->view->viewClientAdminPage( $lvlName );
    }

    /*
    public function getInfo() {

        if ( empty( $this->model->info ) ) {

            try {
                $this->loadInfo();
            }
            catch ( Exception $e ) {
                dbg('Error loading user info from the database: ' . $e->getMessage() );
            }
        }

        return $this->info;
    }
*/
    public function updateUnitTypes() {

        dbg( "e20rClient::updateUnitTypes() - Attempting to update the Length or weight Units via AJAX");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("e20rClient::updateUnitTypes() - POST content: " . print_r($_POST, true));

        global $current_user;
        global $e20rMeasurements;

        $this->id = isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID;
        $dimension = isset( $_POST['dimension'] ) ? sanitize_text_field( $_POST['dimension'] ) : null;
        $value = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : null;

        // Configure the client data object(s).
        $this->init();
        $e20rMeasurements->setClient( $this->id );

        // Update the data for this user in the measurements table.
        try {

            $e20rMeasurements->updateMeasurementsForType( $dimension, $value );
        }
        catch ( Exception $e ) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurements for new measurement type(s): " . $e->getMessage() );
            wp_send_json_error( "Error updating existing data: " . $e->getMessage() );
        }

        // Save the actual setting for the current user

        $this->weightunits = $this->getWeightUnit();
        $this->lengthunits = $this->getLengthUnit();

        if ( $dimension == 'weight' ) {
            $this->weightunits = $value;
        }

        if ( $dimension == 'length' ) {
            $this->lengthunits = $value;
        }

        // Update the settings for the user
        try {
            $this->model->saveUnitInfo( $this->lengthunits, $this->weightunits );
        }
        catch ( Exception $e ) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurement unit for {$dimension}");
            wp_send_json_error( "Unable to save new {$dimension} type " );
        }

        dbg("e20rClient::updateUnitTypes() - Unit type updated");
        wp_send_json_success( "All data updated ");
        wp_die();
    }

    function ajax_getMemberlistForLevel() {
        // dbg($_POST);
        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        global $e20rTracker;

        $levelId = ( isset($_POST['hidden_e20r_level']) ? intval( $_POST['hidden_e20r_level']) : 0 );

        $this->init();

        dbg("e20rClient::getMemberListForLevel() - Level requested: {$levelId}");

        if ( $levelId != 0 ) {

            $levels = $e20rTracker->getMembershipLevels( $levelId );
            // $this->load_levels( $levelObj->name );
            dbg("e20rClient::getMemberListForLevel() - Loading members for {$levels->name}");
            $data = $this->view->viewMemberSelect(  $levelId );
        }
        else {

            $this->view->load_levels();
            $data = $this->view->viewMemberSelect();
        }

        wp_send_json_success( $data );

    }

    function ajax_clientDetail() {
        dbg('Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("Nonce is OK");

        dbg("Request: " . print_r($_REQUEST, true));

        $this->init();
        // $this->initClientViews();

        /*
        global $wpdb, $current_user, $e20rClient, $e20rMeasurements;

        // TODO: Don't init the client here. No need until it's actually used by something (i.e. in the has_weeklyProgress_shortcode, etc)
        if ( ! $e20rClient->client_loaded ) {
            dbg("e20rTracker::init() - Loading Client info");
            $e20rClient->init();
        }
        */


    }

    function ajax_complianceData() {

        dbg('Requesting Check-In details');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("Nonce is OK");

        $this->init();
//        $this->initClientViews();

        $checkins = new E20Rcheckin();

        // TODO: 10/02/2014 - Multiple steps: For different habits, get & generate different graphs.
        // NOTE: Special care for existing Nourish group... :(
        // Get the list of check-ins so far - SQL.
        // Calculate the max # of check-ins per check-in type (day/calendar based)
        //


    }

    function ajax_assignmentData() {
        dbg('Requesting Assignment details');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("Nonce is OK");

        $this->init();
//        $this->initClientViews();

    }

    public function validateAccess( $clientId ) {

        global $current_user;

        dbg("e20rClient::validateAccess() - Client being validated: " . $clientId );

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