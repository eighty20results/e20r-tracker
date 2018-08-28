<?php
namespace E20R\Tracker\Controllers;

use E20R\Tracker\Views\Measurement_View;
use E20R\Tracker\Models\Measurement_Model;
use E20R\Utilities\Utilities;
use E20R\Tracker\Models\Tables;

class Measurements {

    private $id = null;
    private $girths = null;
    
    private $model = null;
    private $view = null;

    protected $dates = null;
    protected $measurementDate = null;

    private static $instance = null;

    public function __construct( $user_id = null ) {
    	
        global $current_user;

        $this->view = new Measurement_View();
        $this->model = new Measurement_Model( $user_id );

        if ( $user_id === null ) {

            $this->id = $current_user->ID;
        }
    }

	/**
	 * @return Measurements|null
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/**
	 * Return the ID of the user for whom we're processing measurements
	 *
	 * @return int|null
	 */
	public function getUserId() {
		return $this->id;
	}
	
    public function init( $forDate = null, $user_id = 0 ) {

        global $e20rMeasurementDate;
        $Tracker = Tracker::getInstance();

        if ( $user_id != 0 ) {

            $this->id = $user_id;
        }

        if ( ! isset( $this->id ) ) {

            global $current_user;

            if ( $current_user->ID != 0 ) {

                $this->id = $current_user->ID;
                Utilities::get_instance()->log("Loading measurements for user {$this->id}");
            }
        }

        if ( ( $forDate !== null ) && ( $this->measurementDate == null ) ) {

            Utilities::get_instance()->log("Loading measurement dates based on article date of {$forDate}");
            $this->dates = $Tracker->datesForMeasurements( $forDate );
        }
        elseif ( $forDate !== null ) {

            $this->dates = $Tracker->datesForMeasurements( $forDate );
        }
        else {
            $this->dates = $Tracker->datesForMeasurements();
        }

        Utilities::get_instance()->log("Setting date to the 'current' value of: " . $this->dates['current']);

        $this->measurementDate = $this->dates['current'];

        Utilities::get_instance()->log("Loading data for: {$this->measurementDate}.");

        $e20rMeasurementDate = $this->measurementDate;

        $this->loadData( $this->measurementDate );
    }

    public function setMeasurementDate( $date = null ) {

        global $e20rMeasurementDate;
	    
        $Tracker = Tracker::getInstance();
        
        $this->dates = $Tracker->datesForMeasurements( $date );

        if ( $date != $this->measurementDate ) {
            // Remove transient so it can be reloaded
            Utilities::get_instance()->log("Given a new date. Clearing transient(s) for {$this->id}");
            delete_transient( "e20r_all_client_measurements_{$this->id}");
        }

        Utilities::get_instance()->log("Date: {$date}, When: " . print_r( $this->dates, true ));

        if ( ( ! in_array( $date, array( 'current', 'last_week', 'next' ) ) ) &&
             ( strtotime( $date ) !== false ) ) {
            Utilities::get_instance()->log("Specified an actual date value ({$date})");
            $this->measurementDate = $date;
        }
        else {
            Utilities::get_instance()->log("Specified a relative date value ({$date})");
            $this->measurementDate = $this->dates[$date];
        }

        $e20rMeasurementDate = $this->measurementDate;
    }

    public function getMeasurementDate() {

        Utilities::get_instance()->log("Is POST configured..?");
        Utilities::get_instance()->log($_REQUEST);

        if ( isset( $this->measurementDate ) ) {
            Utilities::get_instance()->log("returning the configured date");
            return $this->measurementDate;
        }
        else {
            Utilities::get_instance()->log("returning the current date");
            return $this->dates['current'];
        }
    }

    public function areCaptured( $articleId, $programId, $userId, $mDate ) {

        Utilities::get_instance()->log("Checking if the user has recorded data already");
        Utilities::get_instance()->log("Article({$articleId}), Program({$programId}), User({$userId}), Date({$mDate})");
        try {
	        return $this->model->checkCompletion( $articleId, $programId, $userId, $mDate );
        } catch( \Exception $exception ) {
        	Utilities::get_instance()->log("Measurements:areCaptured() - Erro checking if measurements were found. Error: " . $exception->getMessage() );
        	return null;
        }
    }

    public function setClient( $client_id ) {

        global $current_user;

        if ( $current_user->ID !== 0 ) {
            Utilities::get_instance()->log( "Changing client ID from {$this->id} to {$client_id}" );
            $this->id = $client_id;
        }

    }

    public function hasData() {

        if ( empty( $this->model->all ) ) {
            return false;
        }

        return true;
    }

    private function loadGirthTypes() {

        Utilities::get_instance()->log("loadGirthInfo() - Running function to grab all girth posts");
        $this->girths = array();

        $girthQuery = new \WP_Query(
            array(
                'post_type' => 'e20r_girth_types',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'ignore_sticky_posts' => true
            )
        );

        if ( $girthQuery->have_posts()) {
            Utilities::get_instance()->log("loadGirthInfo() - There are Girth posts listed..");

            while ( $girthQuery->have_posts()) {

                $girthQuery->the_post();

                $obj =  new \stdClass();
                $obj->id = get_the_ID();
                $obj->type = strtolower(get_the_title());
                $obj->descr = get_the_content(); // TODO: Grab the get_the_excerpt() for the description (descr), use get_the_content() for the help?
                $obj->sortOrder = get_post_meta( $obj->id, 'e20r_girth_type_sortorder', true );

                if ( ! empty( $obj->sortOrder ) ) {
                    // Utilities::get_instance()->log("Sort order is specified: {$obj->sortOrder} for {$obj->type}");
                    $this->girths[$obj->sortOrder] = $obj;
                }
                else {
                    $this->girths[] = $obj;
                }

                ksort($this->girths);
            }
        }
        wp_reset_query();
        // Utilities::get_instance()->log("loadGirthInfo() - Girth Info: " . print_r($this->girths, true));
    }

    private function conversionFactor( $oldUnit, $newUnit ) {

        $factors = array(
            "lbs->lbs" => 1,
            "kg-kg" => 1,
            "st->st" => 1,
            "st_uk->st_uk" => 1,
            "cm->cm" => 1,
            "in->in" => 1,
            "cm->in" => 0.3937007874,
            "in->cm" => 2.54,
            "lbs->kg" => 0.45359237,
            "lbs->st" => 0.08,
            "lbs->st_uk" => 0.071428571429,
            "kg->lbs" => 2.2046226218,
            "kg->st" => 0.17636980975,
            "kg->st_uk" => 0.15747304442,
            "st->kg" => 5.669904625,
            "st->lbs" => 12.5,
            "st->st_uk" => 0.89285714286,
            "st_uk->kg" => 6.35029318,
            "st_uk->lbs" => 14,
            "st_uk->st" => 1.12,
        );

        return $factors["{$oldUnit}->{$newUnit}"];
    }

    public function updateMeasurementsForType( $unitType, $newUnit ) {

        $Tables = Tables::getInstance();
	    $Client = Client::getInstance();

        $fields = $Tables->getFields( 'measurements' );
	    $oldUnit = null;
	    
        if ( $unitType == 'weight' ) {
            $oldUnit = $Client->getWeightUnit();
        }

        if ( $unitType == 'length' ) {
            $oldUnit = $Client->getLengthUnit();
        }

        $allData = $this->getMeasurement( 'all', false );

        $convFactor = $this->conversionFactor( $oldUnit, $newUnit );
        Utilities::get_instance()->log("Conversion factor for {$oldUnit}->{$newUnit}: {$convFactor}");

        Utilities::get_instance()->log("All of the data for this user: " . print_r( $allData, true ) );

        if ( empty( $allData ) ) {

            Utilities::get_instance()->log("No data to convert. Returning success!" );
            return true;
        }

        foreach( $allData as $key => $record ) {

            if ( $unitType == 'weight' ) {

                Utilities::get_instance()->log("Converting weight for record # {$key}" );

                $allData[$key]->{$fields['weight']} = round( ( $record->{$fields['weight']} * $convFactor ), 3 );
            }

            if ( $unitType == 'length' ) {
                Utilities::get_instance()->log("Converting lengths for record # {$key}" );

                $allData[$key]->{$fields['girth_neck']} =  ( is_null( $record->{$fields['girth_neck']} ) ? null : round( ( $record->{$fields['girth_neck']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_shoulder']} =  ( is_null( $record->{$fields['girth_shoulder']} ) ? null : round( ( $record->{$fields['girth_shoulder']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_arm']} =  ( is_null( $record->{$fields['girth_arm']} ) ? null : round( ( $record->{$fields['girth_arm']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_chest']} =  ( is_null( $record->{$fields['girth_chest']} ) ? null : round( ( $record->{$fields['girth_chest']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_waist']} =  ( is_null( $record->{$fields['girth_waist']} ) ? null : round( ( $record->{$fields['girth_waist']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_hip']} =  ( is_null( $record->{$fields['girth_hip']} ) ? null : round( ( $record->{$fields['girth_hip']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_thigh']} =  ( is_null( $record->{$fields['girth_thigh']} ) ? null : round( ( $record->{$fields['girth_thigh']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth_calf']} =  ( is_null( $record->{$fields['girth_calf']} ) ? null : round( ( $record->{$fields['girth_calf']} * $convFactor ), 3 ) );
                $allData[$key]->{$fields['girth']} =  ( is_null( $record->{$fields['girth']} ) ? null : round( ( $record->{$fields['girth']} * $convFactor ), 3 ) );
            }

            // Save the updated record(s)
	        try {
            $this->model->saveRecord( $allData[$key], $allData[$key]->{$fields['user_id']}, $allData[$key]->{$fields['recorded_date']});
	        } catch( \Exception $exception ) {
            	Utilities::get_instance()->log("Error saving Measurement record! Error: " . $exception->getMessage() );
            	return false;
	        }

        }

        Utilities::get_instance()->log("Converted {$unitType}: " . print_r( $allData, true ) );

        try {
            // Save the updated unit type.
            $Client->saveNewUnit( $unitType, $newUnit );
        }
        catch ( \Exception $e ) {
            Utilities::get_instance()->log("Unable to save {$unitType} unit designation: " . $e->getMessage() );
            return false;
        }

        Utilities::get_instance()->log("Done with conversion for {$unitType} units" );
        return true;
    }

    public function ajaxDeletePhotoCallback() {

        global $current_user;
        $Program = Program::getInstance();

        Utilities::get_instance()->log('Deleting uploaded photo');

        check_ajax_referer('e20r-tracker-progress', 'e20r-progress-nonce');

        Utilities::get_instance()->log("Nonce is OK");

        Utilities::get_instance()->log("Request: " . print_r( $_REQUEST, true ) );
        $imgId = isset( $_POST['image-id'] ) ? intval( $_POST['image-id'] ) : null;
        $user_id = ( isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID );
        $articleId = ( isset( $_POST['article-id'] ) ? intval( $_POST['article-id'] ) : null );
        $programId = ( isset( $_POST['program-id'] ) ? intval( $_POST['program-id'] ) : null );
        $post_date = ( isset( $_POST['date'] ) ? sanitize_text_field($_POST['date']) : null );
        $imageSide = ( isset( $_POST['view'] ) ? sanitize_text_field($_POST['view']) : null );


        Utilities::get_instance()->log("Setting program definition");
        if ( !is_null( $programId ) ) {

            $Program->init( $programId );
        }
        else {

            $Program->getProgramIdForUser( $user_id );
        }

        if ( ! $imgId ) {

            Utilities::get_instance()->log("No attachment ID provided!");

            wp_send_json_error( __( "Error: Image not found!", "e20r-tracker" ));
	        exit();
        }

        if ( wp_delete_attachment( $imgId , true ) ) {

            Utilities::get_instance()->log("Attachment with ID {$imgId} successfully deleted");
        }

        try {
	        $retval = $this->model->saveField( "{$imageSide}_image", NULL, $articleId, $programId, $post_date, $user_id );
        } catch ( \Exception $e ) {
        	$retval = false;
        	Utilities::get_instance()->log("e20rMeasurments::ajaxDeletePhotoCallback() - Exception: " . $e->getMessage() );
        }
        
        if ( $retval === FALSE ) {

            wp_send_json_error( __( "Error removing the image from the database", "e20r-tracker" ));
	        exit();
        }

        $attLnk = wp_get_attachment_link( $imgId );

        Utilities::get_instance()->log("Link: {$attLnk} ");
        if ( 'Missing Attachment' === $attLnk ) {

            wp_send_json_success( array( 'imageLink' => E20R_PLUGINS_URL . "/img/no-image-uploaded.jpg" ) );
	        exit();
        }

        Utilities::get_instance()->log("Not deleted");
        wp_send_json_error( __( "Error: Unable to delete image.", "e20r-tracker" ));
	    exit();

    }

    // TODO: This is the current / active AJAX option
    public function ajaxGetPlotDataForUser() {

        $Tables = Tables::getInstance();
        $Client = Client::getInstance();
        $Program = Program::getInstance();

        Utilities::get_instance()->log('Requesting measurement data');

        check_ajax_referer('e20r-tracker-data', 'e20r_tracker_client_detail_nonce');

        Utilities::get_instance()->log("Nonce is OK");

        $this->id = isset( $_POST['client_id'] ) ? intval( $_POST['client_id'] ) : null;

        if ( $Client->hasDataAccess( $this->id ) ) {
            $Program->getProgramIdForuser( $this->id );
            $this->init();
        }
        else {
            Utilities::get_instance()->log( "Logged in user ID does not have access to the data for user {$this->id}" );
            wp_send_json_error( __( 'You do not have permission to access the data you requested.', 'e20r-tracker' ) );
            exit();
        }

        Utilities::get_instance()->log("Loading client data for {$this->id}");
        $Tables->init( $this->id );

	    $this->model->setUser( $this->id );

        Utilities::get_instance()->log("Using measurement data & configure dimensions");
        $this->model->setFreshClientData();
        $measurements = $this->getMeasurement( 'all', false );

        if ( isset( $_POST['h_dimension'] ) ) {

            Utilities::get_instance()->log("We're displaying the front-end user progress summary");
            $dimensions = array( 'width' => intval( $_POST['w_dimension'] ),
                                 'wtype' => sanitize_text_field($_POST['w_dimension_type']),
                                 'height' => intval( $_POST['h_dimension'] ),
                                 'htype' => sanitize_text_field( $_POST['h_dimension_type'] )
            );

            // $dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
        }
        else {

            Utilities::get_instance()->log("We're displaying on the admin page.");
            $dimensions = array( 'width' => '650', 'height' => '500', 'htype' => 'px', 'wtype' => 'px' );
        }

        Utilities::get_instance()->log("Dimensions: " . print_r( $dimensions, true ));

        $data = $this->view->viewTableOfMeasurements( $this->id, $measurements, $dimensions );

        $weight = $this->generatePlotData( $measurements, 'weight' );
        $girth = $this->generatePlotData( $measurements, 'girth' );

        Utilities::get_instance()->log("Generated plot data for measurements");
        $data = json_encode( array( 'success' => true, 'html' => $data, 'weight' => $weight, 'girth' => $girth ), JSON_NUMERIC_CHECK );
        echo $data;
        // wp_send_json_success( array( 'html' => $data, 'weight' => $weight, 'girth' => $girth ) );
        exit;
    }

    public function shortcodeProgressOverview( $attributes ) {

	    if (! is_user_logged_in()) {

		    auth_redirect();
	    }

        global $current_user;
	    global $currentClient;
	    
        $Tracker = Tracker::getInstance();
        $Client = Client::getInstance();
	    $Action = Action::getInstance();
	    $Assignment = Assignment::getInstance();
	    $Workout = Workout::getInstance();
        $Program = Program::getInstance();

        Utilities::get_instance()->log("Loading shortcode processor: " . $Tracker->whoCalledMe() );

        if ( $current_user->ID == 0 ) {
            Utilities::get_instance()->log("User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        $dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
        $pDimensions = array( 'width' => '90', 'height' => '1024', 'htype' => 'px', 'wtype' => '%' );

        // Load javascript for the progress overview.
        extract( shortcode_atts( array(
            'something' => 0,
        ), $attributes ) );

        if ( $Client->hasDataAccess( $current_user->ID ) ) {

            $this->id = $current_user->ID;
            $Program->getProgramIdForUser( $this->id );
            Utilities::get_instance()->log( "User {$this->id} has access." );
        }
        else {
            Utilities::get_instance()->log( "Logged in user ID does not have access to progress data" );
            return null;
        }

        
        Utilities::get_instance()->log("Configure user specific data");
	    $this->model->setUser( $this->id );
	    
	    if ( empty($currentClient) || ( $this->id !== $currentClient->user_id || empty( $currentClient->program_id ) ) ) {
		    Utilities::get_instance()->log("Have to update the currentClient info!");
		    $Client->setClient( $this->id );
	    }
	    
        Utilities::get_instance()->log("Loading progress data... for {$this->id}");
        $measurements = $this->getMeasurement( 'all', false );

        if ( true === $Client->completeInterview( $this->id ) ) {

            $measurement_view = $this->view->viewTableOfMeasurements( $this->id, $measurements, $dimensions, null, true );
        }
        else {
            $measurement_view = '<div class="e20r-progress-no-measurement">' . $Program->incompleteIntakeForm() . '</div>';
        }

        $tabs = array(
            'Measurements' => '<div id="e20r-progress-measurements">' . $measurement_view . '</div>',
            'Assignments' => '<div id="e20r-progress-assignments">' . $Assignment->listUserAssignments() . '</div>',
            'Activities' => '<div id="e20r-progress-activities">' . $Workout->listUserActivities() . '</div>',
            'Achievements' => '<div id="e20r-progress-achievements">' . $Action->listUserAchievements() . '</div>',
        );
	    
        return $this->view->viewTabbedProgress( $tabs, $pDimensions, true );
    }

    /**
     * Loads the view for the users progress overview (used by profile & progress_overview shortcodes)
     *
     * @param array $progress_data - Array of tabs & tab content for progress view.
     * @param array|null $dimensions - Array containing dimensions (not used)
     * @param bool $modal
     *
     * @returns string - HTML containing progress view w/tabs.
     *
     */
    public function showProgress( $progress_data, $dimensions = null, $modal = true ) {

        return $this->view->viewTabbedProgress( $progress_data, $dimensions, $modal );
    }

    public function showTableOfMeasurements( $clientId = null, $measurements, $dimensions = null, $tabbed = true, $admin = true ) {

        return $this->view->viewTableOfMeasurements( $clientId, $measurements, $dimensions, $tabbed, $admin );
    }
    
    public function shortcodeWeeklyProgress( $attributes ) {

	    if (! is_user_logged_in()) {

		    auth_redirect();
	    }

        global $e20r_plot_jscript;
        global $current_user;
        
        $Article = Article::getInstance();
        $Tracker = Tracker::getInstance();
        $Client = Client::getInstance();
        $Program = Program::getInstance();

        global $currentClient;
        global $currentArticle;

        global $e20rMeasurementDate;

        Utilities::get_instance()->log("Loading shortcode processor: " . $Tracker->whoCalledMe() );

        $e20r_plot_jscript = true;

        Utilities::get_instance()->log("Request: " . print_r( $_POST, true ) );

        $mDate = ( isset( $_POST['e20r-progress-form-date'] ) ? ( strtotime( $_POST['e20r-progress-form-date'] ) ? sanitize_text_field( $_POST['e20r-progress-form-date'] ) : null ) : null );
        $articleId = isset( $_POST['e20r-progress-form-article'] ) ? intval( $_POST['e20r-progress-form-article'] ) : null;

        // Get current article ID if it's not set as part of the $_POST variable.
	    if ( empty( $articleId ) ) {
	    	$delay = $Tracker->getDelay();
	    	$program  = $Program->getProgramIdForUser( $current_user->ID );
	    	$currentArticle = $Article->findArticles( 'release_day', $delay, $program )[0];

	    	Utilities::get_instance()->log("Current Article: " . print_r( $currentArticle, true ));
	    	$articleId = $currentArticle->id;

	    	Utilities::get_instance()->log("Article ID is now: {$articleId}");
	    }

        if ( $mDate ) {

            $e20rMeasurementDate = $mDate;
            Utilities::get_instance()->log( "Date to measure for requested: {$mDate}" );
            $this->setMeasurementDate( $mDate );
        }

        /*
        $day = 0;
        $from_programstart = 1;
        $use_article_id = 1;
        */
        $demo_form = 0;

        extract( shortcode_atts( array(
            'day' => 0,
            'from_programstart' => 1,
            'use_article_id' => 1,
            'demo_form' => 0,
        ), $attributes ) );

        if ( $demo_form == 1 ) {

            global $e20rExampleProgress;
            $e20rExampleProgress = true; // TODO: Do something if it's an example progress form.
        }
        

        // TODO: Does user have permission...?
        try {

            Utilities::get_instance()->log("Loading the measurement data for {$this->measurementDate}");
            $this->init( $this->measurementDate, $this->id );

            if ( !is_object( $currentClient ) || ( isset( $currentClient->loadedDefaults) && (false == $currentClient->loadedDefaults) ) ||
                ( true == $currentClient->loadedDefaults ) ) {

                Utilities::get_instance()->log("Loading the Client class()");
                $Client->loadClientInfo( $this->id );
            }

            if ( $this->loadData( $this->measurementDate ) == false ) {
                Utilities::get_instance()->log("Error loading data for (user: {$current_user->ID}) for {$this->measurementDate}");
            }

            Utilities::get_instance()->log("Loading progress form for {$this->measurementDate} by {$this->id}");
            return $this->loadEditProgress( $articleId );
	        
        }
        catch ( \Exception $e ) {
            Utilities::get_instance()->log("Error displaying weekly progress form: " . $e->getMessage() );
            return sprintf( __( "Error displaying weekly progress form. Error message: %s", "e20r-tracker" ), $e->getMessage() );
        }
    }
    
    public function loadData( $when = 'all') {

        Utilities::get_instance()->log("Loading measurement data for {$when}");

        try {
            
            if ( empty( $this->model ) ) {

                Utilities::get_instance()->log("Init of measurement model");
                $this->model = new Measurement_Model( $this->id );
            }

            $this->model->getByDate( $when );

        }
        catch ( \Exception $e ) {

            Utilities::get_instance()->log("Error loading measurement data: " . $e->getMessage() );
            return false;
        }

        return true;
    }

    public function getGirthTypes() {

        if (empty( $this->girths ) ) {

            $this->loadGirthTypes();
        }

        return $this->girths;
    }

    public function getMeasurement( $when = 'all', $forJS = false ) {
	
	    global $current_user;
	    global $currentClient;
	    
        $Tracker = Tracker::getInstance();
        
        if ( ! isset( $this->id ) ) {
            Utilities::get_instance()->log("User ID hasn't been set yet.");
	        
            if ( empty( $current_user->ID ) && empty( $currentClient->user_id ) ) {
                Utilities::get_instance()->log("No user ID defined.");
                return array();
            }
	        
            if ( !empty( $currentClient->user_id ) ) {
            	$this->id = $currentClient->user_id;
            }
            
            $this->id = $current_user->ID;
        }

        if ( !isset( $this->model ) ) {

            Utilities::get_instance()->log("For some reason, the model isn't loaded yet!");
            $this->model = new Measurement_Model( $this->id );
            // $this->model->getFields( $when );
        }

	    Utilities::get_instance()->log("Starting load for {$when} and parsing for Javascript: " . ( $forJS ? 'true' : 'false' ) );

        $this->model->setUser( $this->id );

        $byDateArr = (array)$this->model->byDate;

        if ( empty($byDateArr) ) {
            Utilities::get_instance()->log("No data loaded yet...");
        }

        if ( $when != 'all' ) {

            Utilities::get_instance()->log( "Measurements::getMeasurement({$when}, {$forJS}) was called by: " . $Tracker->whoCalledMe() );
            $date = $this->dates[$when];

            Utilities::get_instance()->log( "{$when}: " . $date );

            $data = $this->model->getByDate( $date );

        }
        else {
                Utilities::get_instance()->log("Load all measurements");
                $data = $this->model->getMeasurements();
            /**
             * @var array $data -> array( 0 => stdClass( obj->id, obj->user_id ), 1 => stdClass( obj->id, obj->user_id ), )
             */
        }

        return ( $forJS === true ? $this->transformForJS( $data ) : $data );
    }

    private function transformForJS( $records ) {

        $Tables = Tables::getInstance();

        global $currentClient;

        $retVal = array();
        $fields = $Tables->getFields('measurements');

	    if ( ! is_array( $records ) ) {
		    Utilities::get_instance()->log("Convert to array of results");
		    $records = array( $records );
	    }

        $exclude = array(
            'id',
            'user_id',
            'article_id',
            'program_id',
            'recorded_date',
            'essay1',
            'behaviorprogress',
            'front_image',
            'side_image',
            'back_image',
        );

        Utilities::get_instance()->log("DB  data:");
        
        foreach( $records as $data ) {

            foreach ( $data as $key => $value ) {

                $mKey = array_search( $key, $fields );


                if ( ! in_array( $key, $exclude ) ) {

                    $retVal[ $mKey ] = array(
                        'value' => $value,
                        'units' => ( $key != 'weight' ? $currentClient->lengthunits : $currentClient->weightunits ) // $Client->getLengthUnit() : $Client->getWeightUnit() ),
                    );
                }
            }
        }
        return ( empty( $retVal ) ? $data : $retVal );
    }

    private function loadEditProgress( $articleId = CONST_NULL_ARTICLE ) {
	
	    global $current_user;
	    
        $Tracker = Tracker::getInstance();
        $Program = Program::getInstance();
        $Article = Article::getInstance();

        Utilities::get_instance()->log("Date supplied is: {$this->measurementDate}. " . $Tracker->whoCalledMe() );

	    if ( is_null( $articleId ) ) {

		    Utilities::get_instance()->log("No article ID specified. Setting the ID to the NULL article");
		    $articleId = CONST_NULL_ARTICLE;
	    }
        $programId = $Program->getProgramIdForUser( $current_user->ID,  $articleId );
        $img = $Article->isPhotoDay( $articleId );

        Utilities::get_instance()->log("Program ID for user ({$current_user->ID}): {$programId}");
        Utilities::get_instance()->log("Today is a photo day ({$img}) for user ({$current_user->ID})? " . print_r( $img, true) );
        Utilities::get_instance()->log("Date for use with progress tracking form: {$this->measurementDate}");
        Utilities::get_instance()->log("Loading data for {$this->measurementDate}...");

        $measurements   = $this->model->getByDate( $this->measurementDate );

        Utilities::get_instance()->log("Views for measurements are loaded");
        $this->view->init( $this->measurementDate, $measurements );

        ob_start();

        echo $this->view->startProgressForm( $articleId, $programId );
        Utilities::get_instance()->log("Start of measurement form generated.");

        echo $this->view->showChangeBirthDate();

        Utilities::get_instance()->log("Birth date portion of measurement form generated.");

        echo $this->view->showWeightRow( );

        Utilities::get_instance()->log("Weight info for form generated.");
        $this->loadGirthTypes();

        Utilities::get_instance()->log("Girth Count: " . count($this->girths));
        echo $this->view->showGirthRow( $this->girths );
        Utilities::get_instance()->log("Girth Row generated");

        echo $this->view->showPhotoRow( $img );
        Utilities::get_instance()->log("Photo Row generated");

        echo $this->view->showOtherIndicatorsRow( $img );
        Utilities::get_instance()->log("Other Indicators row generated");

        echo $this->view->showProgressQuestionRow( $img );
        Utilities::get_instance()->log("Progress Questionnaire row generated");

        echo $this->view->endProgressForm();

        $html = ob_get_clean();

        Utilities::get_instance()->log("Weekly Measurements form has been loaded.");
        return $html;
    }

    public function generatePlotData( $data, $variable ) {

        $data_matrix = array();

	    if ( empty( $data ) ) {

		    return array();
	    }

        foreach ( $data as $measurement ) {

            if ( is_object( $measurement ) ) {

                switch ( $variable ) {
                    case 'weight':

                        $data_matrix[] = array( ( strtotime( $measurement->recorded_date ) * 1000 ), number_format( (float) $measurement->weight, 2) );
                        break;

                    case 'girth':

                        $data_matrix[] = array( ( strtotime( $measurement->recorded_date ) * 1000 ), number_format( (float) $measurement->girth, 2 ) );
                        break;
                }
            }
        }
        // Utilities::get_instance()->log( $data_matrix );
        return $data_matrix;
    }

    /**
     *********************************************************
     * Any and all ajax callback functions
     *********************************************************
     */

    /**
     * Weekly progress form submission/save
     */
    public function saveMeasurementCallback() {

        $Action = Action::getInstance();
        $Program = Program::getInstance();

        Utilities::get_instance()->log("Checking access");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        Utilities::get_instance()->log("Access approved");

        global $current_user;

        if ( !is_user_logged_in() ) {
            Utilities::get_instance()->log("User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        $measurementType = (isset( $_POST['measurement-type'] ) ? sanitize_text_field( trim($_POST['measurement-type']) ) : null );
        $measurementValue = (isset( $_POST['measurement-value'] ) ? sanitize_text_field( trim($_POST['measurement-value'])) : null );
        $user_id = ( isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID );
        $articleId = ( isset( $_POST['article-id'] ) ? intval( $_POST['article-id'] ) : -1 );
        $programId = ( isset( $_POST['program-id'] ) ? intval( $_POST['program-id'] ) : null );
        $post_date = ( isset( $_POST['date'] ) ? sanitize_text_field($_POST['date']) : null );
        $imageSide = ( isset( $_POST['view'] ) ? sanitize_text_field($_POST['view']) : null );

        if ( !is_null( $programId ) ) {

            $Program->init( $programId );
        }
        else {

            $Program->getProgramIdForUser( $user_id );
        }

        Utilities::get_instance()->log("Received from user {$user_id}- Type: {$measurementType}, Value: {$measurementValue}, Date: {$post_date}");

        if ( ! $post_date ) {

            Utilities::get_instance()->log("No date specified for the measurement");
            wp_send_json_error( __( "No date specified for the measurement", "e20r-tracker" ) );
	        exit();
        }

        if ( ! $articleId ) {

            Utilities::get_instance()->log("No article ID specified for the measurement ");
            wp_send_json_error( __( "No article ID specified for the measurement ", "e20r-tracker" ) );
	        exit();
        }
        
        if ( ( $measurementType == 'completed') && ( $measurementValue == 1) ){

            Utilities::get_instance()->log("Measurement form is being saved by the user. TODO: Display with correct header to show completion");
            if ( $Action->setArticleAsComplete( $current_user->ID, $articleId ) ) {
                wp_send_json_success( sprintf( __( "Progress saved for %s", "e20r-tracker" ), $post_date  ) );
	            exit();
            }
            else {
                wp_send_json_success( __( "Unable to save your progress measurements. Please try again", "e20r-tracker" ) );
	            exit();
            }
        }

        if ( $imageSide ) {

            $attachId = $measurementValue;
            
            $file = get_attached_file( $attachId );
            $path = pathinfo( $file );

            //dirname   = File Path
            //basename  = Filename.Extension
            //extension = Extension
            //filename  = Filename

            $newfilename = str_replace( 'REPLACEME', $imageSide, $path['filename'] );
            $newfile = $path['dirname']."/".$newfilename.".".$path['extension'];

            Utilities::get_instance()->log("Renaming {$file['filename']} to {$newfilename}");

            rename($file, $newfile);
            update_attached_file( $attachId, $newfile );

        }

        try {
            Utilities::get_instance()->log( "Saving measurement: {$measurementType} -> {$measurementValue}");
            
            if ( ! $this->model->saveField( $measurementType, $measurementValue, $articleId, $programId, $post_date, $user_id ) ) {

                wp_send_json_error( sprintf( __( "Unknown error saving measurement for %s", "e20r-tracker" ), $measurementType ) );
	            exit();
            }

        }
        catch ( \Exception $e ) {
            Utilities::get_instance()->log("saveProgressForm() - Exception while saving the {$measurementType} measurement: " . $e->getMessage() );
            wp_send_json_error( sprintf( __( "Error saving %s measurement", "e20r-tracker" ), $measurementType ) );
	        exit();
        }

        Utilities::get_instance()->log("saveProgressForm() - {$measurementType} measurement saved for user ID {$user_id}");
        wp_send_json_success( sprintf( __( "Saved %s for user ID %d", "e20r-tracker" ), $measurementType, $user_id ) );
	    exit();
    }

    public function saveMeasurement( $type, $value, $articleId, $programId, $post_date, $user_id ) {

        // $measurementType, $measurementValue, $articleId, $programId, $post_date, $user_id;
	    try {
		    $retval = $this->model->saveField( $type, $value, $articleId, $programId, $post_date, $user_id );
	    } catch ( \Exception $e ) {
		    
	    	Utilities::get_instance()->log("saveMeasurement() - Exception: " . $e->getMessage() );
	    	$this->model->setFreshClientData();
	    	$retval = false;
	    }
	    
	    return $retval;
    }

    public function checkProgressFormCompletionCallback() {

        global $current_user;

        $Program = Program::getInstance();

        if ( !is_user_logged_in() ) {
            Utilities::get_instance()->log("User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        Utilities::get_instance()->log("Checking access");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        Utilities::get_instance()->log("Access approved");
        // Utilities::get_instance()->log($_POST);

        $articleId = ( isset( $_POST['article-id'] ) ? intval( $_POST['article-id'] ) : null );
        $programId = ( isset( $_POST['program-id'] ) ? intval( $_POST['program-id'] ) : null );
        $post_date = ( isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : null );

        if ( !is_null( $programId ) ) {

            $Program->init( $programId );
        }
        else {
            $Program->getProgramIdForUser( $current_user->ID );
        }

        if ( $articleId === null ) {
            wp_send_json_success( array( 'progress_form_completed' => false ) );
	        exit();
        }

        if ($post_date === null) {
            wp_send_json_success( array( 'progress_form_completed' => false ) );
            exit();
        }
        
        $this->init( $post_date, $current_user->ID );
        
        try {
	        $status = $this->model->checkCompletion( $articleId, $programId, $current_user->ID, $post_date );
        } catch ( \Exception $exception ) {
        	Utilities::get_instance()->log("Can't figure out what the completion status is. Error: " . $exception->getMessage() );
        	wp_send_json_error();
        	exit();
        }

        Utilities::get_instance()->log("Status: ");
        Utilities::get_instance()->log($status);

        Utilities::get_instance()->log("Response being sent to calling page");
        $completed = array(
            'progress_form_completed' =>  $status['status'],
            'complete' => $status['percent']
        );

        wp_send_json_success( $completed );
	    exit();
    }

    /*********************************************************
     *      Private functions below here                     *
     *********************************************************/

    /**
     * Return the related (from DB) settings for this class.
     *
     * @param $setting -- The setting to retrieve.
     *
     * @return mixed -- The option values fetched from the DB.
     */
    private function loadSettings( $setting ) {

        $options = get_option( 'e20r-tracker' );
	    $retval = null;
	    
        switch ($setting) {

            case 'items':
                $retval = $options['measuring'];
                break;

        }
        
        return $retval;
    }
}