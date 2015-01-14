<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/25/14
 * Time: 1:50 PM
 */

class e20rMeasurements {

    private $id = null;
    private $model = null;
    private $view = null;

    protected $dates = null;
    protected $measurementDate = null;

    private $girths = null;

    public function e20rMeasurements( $user_id = null ) {

        global $e20rMeasurements;

        $this->id = $user_id;

        $this->view = new e20rMeasurementViews();
        $this->model = new e20rMeasurementModel( $this->id );

        if ( ! isset( $e20rMeasurements ) ) {
            $e20rMeasurements = $this;
        }
    }

    public function init( $forDate = null, $user_id = 0 ) {

        global $e20rMeasurementDate;
        global $e20rTracker;

        if ( $user_id != 0 ) {

            $this->id = $user_id;
        }

        if ( ! isset( $this->id ) ) {

            global $current_user;

            if ( $current_user->ID != 0 ) {

                $this->id = $current_user->ID;
                dbg("e20rMeasurements::init() - Loading measurements for user {$this->id}");
            }
        }

        if ( ( $forDate !== null ) && ( $this->measurementDate == null ) ) {

            dbg("e20rMeasurements::init() - Loading measurement dates based on article date of {$forDate}");
            $this->dates = $e20rTracker->datesForMeasurements( $forDate );
        }
        elseif ( $forDate !== null ) {

            $this->dates = $e20rTracker->datesForMeasurements( $forDate );
        }
        else {
            $this->dates = $e20rTracker->datesForMeasurements();
        }

        dbg("e20rMeasurements::init() - Setting date to the 'current' value of: " . $this->dates['current']);
        dbg( $this->dates );

        $this->measurementDate = $this->dates['current'];

        dbg("e20rMeasurements::init() - Loading data for: {$this->measurementDate}.");

        $e20rMeasurementDate = $this->measurementDate;

        $this->loadData( $this->measurementDate );
    }

    public function setMeasurementDate( $date = null ) {

        global $e20rTracker, $e20rMeasurementDate;

        $this->dates = $e20rTracker->datesForMeasurements( $date );

        if ( $date != $this->measurementDate ) {
            // Remove transient so it can be reloaded
            dbg("e20rMeasurements::setMeasurementDate() - Given a new date. Clearing transient(s) for {$this->id}");
            delete_transient( "e20r_all_client_measurements_{$this->id}");
        }

        dbg("e20rMeasurements::setMeasurementDate() - Date: {$date}, When: " . print_r( $this->dates, true ));

        if ( ( ! in_array( $date, array( 'current', 'last_week', 'next' ) ) ) &&
             ( strtotime( $date ) !== false ) ) {
            dbg("e20rMeasurements::setMeasurementDate() - Specified an actual date value ({$date})");
            $this->measurementDate = $date;
        }
        else {
            dbg("e20rMeasurements::setMeasurementDate() - Specified a relative date value ({$date})");
            $this->measurementDate = $this->dates[$date];
        }

        $e20rMeasurementDate = $this->measurementDate;
    }

    public function getMeasurementDate() {

        if ( isset( $this->measurementDate ) ) {
            dbg("e20rMeasurements::getMeasurementDate() - returning the configured date");
            return $this->measurementDate;
        }
        else {
            dbg("e20rMeasurements::getMeasurementDate() - returning the current date");
            return $this->dates['current'];
        }
    }

    public function areCaptured( $articleId, $userId, $mDate ) {

        dbg("e20rMeasurements::areCaptured() - Checking if the user has recorded data already");
        return $this->model->checkCompletion( $articleId, $userId, $mDate );
    }

    public function setClient( $client_id ) {

        global $current_user;

        if ( $current_user->ID !== 0 ) {
            dbg( "e20rMeasurements::setClient() - Changing client ID from {$this->id} to {$client_id}" );
            $this->id = $client_id;
        }

    }

    public function hasData() {

        if ( empty( $this->model->all ) ) {
            return false;
        }

        return true;
    }

    public function set_progress_upload_dir( $upload ) {

        global $e20rClient, $e20rMeasurementDate;

        if ( ! $e20rClient->client_loaded ) {
            dbg("e20rMeasurements::progress_upload_dir() - Need to init the Client class");
            $e20rClient->init();
        }

        $upload['path'] = $upload['basedir'] . "/{$e20rClient->data->info->progress_photo_dir}/{$e20rMeasurementDate}";
        $upload['url'] = $upload['baseurl'] . "/{$e20rClient->data->info->progress_photo_dir}/{$e20rMeasurementDate}";

        dbg("e20rMeasurements::progress_upload_dir() - Directory: {$upload['path']}");
        return $upload;
    }

    public function setFilenameForClientUpload( $file ) {

        if ( ( $this->id == 0 ) || ( $this->id === null ) ) {

            dbg("e20rMeasurements::setFilenameForClientUpload() - No Client ID available...");
            $this->id == get_current_user_id();
        }

        dbg("e20rMeasurements::setFilenameForClientUpload() - Filename was: {$file['name']}");
        $timestamp = date( "Ymd", current_time( 'timestamp' ) );
        $side = '';

        // $fileName[0] = name, $fileName[(count($fileName)] = Extension
        $fileName = explode( '.', $file['name'] );
        $ext = $fileName[ (count($fileName) - 1)];

        dbg("e20rMeasurements::setFilenameForClientUpload() - " . print_r( $_FILES, true ) );

        $file['name'] = "{$this->id}-{$timestamp}-{$side}.{$ext}";
        dbg("e20rMeasurements::setFilenameForClientUpload() - New filename: {$file['name']}");

        return $file;
    }

    public function clientMediaUploader( $strings ) {

        global $current_user;

        if ( in_array( 'admin', $current_user->roles ) ) {

            dbg("e20rMeasurements::clientMediaUploader() -- User is an administrator so don't remove anything.");
            return $strings;
        }

        dbg("e20rMeasurements::clientMediaUploader() -- Regular user.");
        unset( $strings['mediaLibraryTitle'] ); //Media Library
        unset( $strings['createGalleryTitle'] ); //Create Gallery
        unset( $strings['setFeaturedImageTitle'] ); //Set Featured Image

        unset( $strings['insertFromUrlTitle'] ); //Insert from URL
        return $strings;
    }

    private function load_girthTypes() {

        dbg("loadGirthInfo() - Running function to grab all girth posts");
        $this->girths = array();

        $girthQuery = new WP_Query(
            array(
                'post_type' => 'e20r_girth_types',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'caller_get_posts' => 1
            )
        );

        if ( $girthQuery->have_posts()) {
            dbg("loadGirthInfo() - There are Girth posts listed..");

            while ( $girthQuery->have_posts()) {

                $girthQuery->the_post();

                $obj = new stdClass();
                $obj->id = get_the_ID();
                $obj->type = strtolower(get_the_title());
                $obj->descr = get_the_content(); // TODO: Grab the get_the_excerpt() for the description (descr), use get_the_content() for the help?
                $obj->sortOrder = get_post_meta( $obj->id, 'e20r_girth_type_sortorder', true );

                if ( ! empty( $obj->sortOrder ) ) {
                    // dbg("Sort order is specified: {$obj->sortOrder} for {$obj->type}");
                    $this->girths[$obj->sortOrder] = $obj;
                }
                else {
                    $this->girths[] = $obj;
                }

                ksort($this->girths);
            }
        }
        wp_reset_query();
        // dbg("loadGirthInfo() - Girth Info: " . print_r($this->girths, true));
    }

    private function conversionFactor( $oldUnit, $newUnit ) {

        $factors = array(
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

        global $e20rTables, $e20rClient;

        $fields = $e20rTables->getFields( 'measurements' );

        if ( $unitType == 'weight' ) {
            $oldUnit = $e20rClient->getWeightUnit();
        }

        if ( $unitType == 'length' ) {
            $oldUnit = $e20rClient->getLengthUnit();
        }

        $allData = $this->getMeasurement( 'all', false );

        $convFactor = $this->conversionFactor( $oldUnit, $newUnit );
        dbg("e20rMeasurements::updateMeasurementsForType() - Conversion factor for {$oldUnit}->{$newUnit}: {$convFactor}");

        dbg("e20rMeasurements::updateMeasurementsForType() - All of the data for this user: " . print_r( $allData, true ) );

        foreach( $allData as $key => $record ) {

            if ( $unitType == 'weight' ) {

                dbg("e20rMeasurements::updateMeasurementsForType() - Converting weight for record # {$key}" );

                $allData[$key]->{$fields['weight']} = round( ( $record->{$fields['weight']} * $convFactor ), 3 );
            }

            if ( $unitType == 'length' ) {
                dbg("e20rMeasurements::updateMeasurementsForType() - Converting lengths for record # {$key}" );

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
            $this->model->saveRecord( $allData[$key], $allData[$key]->{$fields['user_id']}, $allData[$key]->{$fields['recorded_date']});

        }

        dbg("e20rMeasurements::updateMeasurementsForType() - Converted {$unitType}: " . print_r( $allData, true ) );

        try {
            // Save the updated unit type.
            $e20rClient->saveNewUnit( $unitType, $newUnit );
        }
        catch ( Exception $e ) {
            dbg("e20rMeasurements::updateMeasurementsForType() - Unable to save {$unitType} unit designation: " . $e->getMessage() );
            return false;
        }

        dbg("e20rMeasurements::updateMeasurementsForType() - Done with conversion for {$unitType} units" );
        return true;
    }

    public function ajax_deletePhoto_callback() {

        dbg('e20rMeasurements::ajax_deletePhoto_callback() - Deleting uploaded photo');

        check_ajax_referer('e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("e20rMeasurements::ajax_deletePhoto_callback() - Nonce is OK");

        dbg("e20rMeasurements::ajax_deletePhoto_callback() - Request: " . print_r( $_REQUEST, true ) );
        $imgId = isset( $_POST['image-id'] ) ? intval( $_POST['image-id'] ) : null;

        if ( ! $imgId ) {

            dbg("e20rMeasurements::ajax_deletePhoto_callback() - No attachment ID provided!");

            wp_send_json_error( "Error: Image not found!");
            wp_die();
        }

        // TODO: Remove image from e20r_measurements for this user, on this date, by setting the field to null.
        if ( wp_delete_attachment( $imgId , true ) ) {

            dbg("e20rMeasurements::ajax_deletePhoto_callback() - Attachment with ID {$imgId} successfully deleted");

            wp_send_json_success( array( 'imageLink' => E20R_PLUGINS_URL . "/images/no-image-uploaded.jpg" ) );
            wp_die();
        }

        wp_send_json_error( "Error: Unable to delete image.");
        wp_die();

    }

    public function ajax_getPlotDataForUser() {

        global $e20rTables, $e20rClient, $e20rMeasurements;

        dbg('e20rMeasurements::ajax_getPlotDataForUser() - Requesting measurement data');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("e20rMeasurements::ajax_getPlotDataForUser() - Nonce is OK");

        $clientId = isset( $_POST['client_id'] ) ? intval( $_POST['client_id'] ) : null;

        if ( $e20rClient->validateAccess( $clientId ) ) {
            $this->id = $clientId;
        }
        else {
            dbg( "e20rMeasurements::ajax_getPlotDataForUser() - Logged in user ID does not have access to the data for user ${clientId}" );
            wp_send_json_error( 'You do not have permission to access the data you requested.' );
            wp_die();
        }

        dbg("e20rMeasurements::ajax_getPlotDataForUser() - Loading client data");
        $e20rTables->init( $this->id );

        dbg("e20rMeasurements::ajax_getPlotDataForUser() - Using measurement data & configure dimensions");

        $measurements = $this->getMeasurement('all');

        if ( $this->view == null ) {
            dbg("e20rMeasurements::ajax_getPlotDataForUser() - Loading Views() class");
            $this->view = new e20rMeasurementViews();
        }

        $dimensions = array( 'width' => '650', 'height' => '500', 'type' => 'px' );

        $data = $this->view->viewTableOfMeasurements( $this->id, $measurements, $dimensions );

        $weight = $this->generatePlotData( $measurements, 'weight' );
        $girth = $this->generatePlotData( $measurements, 'girth' );

        $data = json_encode( array( 'success' => true, 'data' => $data, 'weight' => $weight, 'girth' => $girth ), JSON_NUMERIC_CHECK );
        echo $data;
        exit;
    }

    public function shortcode_progressOverview( $attributes ) {

        global $e20r_plot_jscript;
        global $current_user;
        global $post;
        global $e20rArticle;
        global $e20rTracker;
        global $e20rClient;
        global $e20rTables;

        dbg("e20rMeasurements::shortcode_progressOverview() - Loading shortcode processor: " . $e20rTracker->whoCalledMe() );

        if ( $current_user->ID == 0 ) {
            dbg("e20rMeasurements::shortcode_progressOverview() - User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        $e20r_plot_jscript = true;

        // Load javascript for the progress overview.
        extract( shortcode_atts( array(
            'something' => 0,
        ), $attributes ) );

        if ( $e20rClient->validateAccess( $current_user->ID ) ) {
            $this->id = $current_user->ID;
        }
        else {
            dbg( "e20rMeasurements::shortcode_progressOverview() - Logged in user ID does not have access to progress data" );
            return;
        }

        dbg("e20rMeasurements::shortcode_progressOverview() - Loading client data");
        $e20rTables->init( $this->id );

        dbg("e20rMeasurements::shortcode_progressOverview() - Using measurement data & configure dimensions");

        $measurements = $this->getMeasurement('all');

        if ( $this->view == null ) {
            dbg("e20rMeasurements::shortcode_progressOverview() - Loading e20rMeasurementViews() class");
            $this->view = new e20rMeasurementViews();
        }

        $dimensions = array( 'width' => '650', 'height' => '350', 'type' => 'px' );

        $data = $this->view->viewTableOfMeasurements( $this->id, $measurements, $dimensions );
        //
        // TODO: Load $weight and $girth data for graph.
        //
        $weight = $this->generatePlotData( $measurements, 'weight' );
        $girth = $this->generatePlotData( $measurements, 'girth' );

        $data = json_encode( array( 'success' => true, 'data' => $data, 'weight' => $weight, 'girth' => $girth ), JSON_NUMERIC_CHECK );
        echo $data;
        exit;
    }

    public function shortcode_weeklyProgress( $attributes ) {

        global $e20r_plot_jscript;
        global $current_user;
        global $e20rArticle;
        global $e20rTracker;
        global $e20rClient;
        global $e20rMeasurementDate;

        dbg("e20rMeasurements::shortcode_weeklyProgress() - Loading shortcode processor: " . $e20rTracker->whoCalledMe() );

        $e20r_plot_jscript = true;

        dbg("e20rMeasurements::shortcode_weeklyProgress() - Request: " . print_r( $_POST, true ) );

        $mDate = isset( $_POST['e20r-progress-form-date'] ) ? sanitize_text_field( $_POST['e20r-progress-form-date'] ) : null;
        $articleId = isset( $_POST['e20r-progress-form-article']) ? intval( $_POST['e20r-progress-form-article']) : null;

        $day = 0;
        $from_programstart = 1;
        $use_article_id = 1;
        $demo_form = 0;

        extract( shortcode_atts( array(
            'day' => 0,
            'from_programstart' => 1,
            'use_article_id' => 1,
            'demo_form' => 0,
        ), $attributes ) );

        if ( $demo_form == 1 ) {

            global $e20rExampleProgress;
            $e20rExampleProgress = true;
        }

        if ( strtotime( $mDate ) ) {

            dbg("e20rMeasurements::shortcode_weeklyProgress() - Date to measure for requested: {$mDate}");
            $this->setMeasurementDate( $mDate );
        }

        if ( $current_user->ID == 0 ) {
            dbg("e20rMeasurements::shortcode_weeklyProgress() - User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        // TODO: Does user have permission...?
        try {

            dbg("e20rMeasurements::shortcode_weeklyProgress() - Loading the measurement data for {$this->measurementDate}");
            $this->init( $this->measurementDate, $this->id );

            if ( ! isset( $e20rClient->data ) ) {

                dbg("e20rMeasurements::shortcode_weeklyProgress() - Loading the e20rClient class()");
                $e20rClient->init();
            }

            if ( $this->loadData( $this->measurementDate ) == false ) {
                dbg("e20rMeasurements::shortcode_weeklyProgress() - Error loading data for (user: {$current_user->ID}) for {$this->measurementDate}");
            }

            dbg("e20rMeasurements::shortcode_weeklyProgress() - Loading progress form for {$this->measurementDate} by {$this->id}");
            return $this->load_EditProgress( $articleId );

            dbg('e20rMeasurements::shortcode_weeklyProgress() - Form load completed...');
        }
        catch ( Exception $e ) {
            dbg("Error displaying weekly progress form: " . $e->getMessage() );
        }
    }

    /*
    public function alreadyRecorded( $articleId, $userId, $date ) {

        //
        // if ( $userId != $this->id ) {
        //    dbg("e20rMeasurements::alreadyRecorded() - User ID supplied ({$userId}) doesn't match ID ({$this->id})");
        //    return false;
        // }

        $completion = $this->model->checkCompletion( $articleId, $userId, $date );

        dbg("e20rMeasurements::alreadyRecorded() - Completion: {$completion}");

        return $completion;
    }
*/
    public function loadData( $when = 'all') {

        dbg("Loading measurement data for {$when}");

        try {
            /*
            if ( ! class_exists( ' e20rMeasurementModel' ) ) {
                dbg("Loading model class for measurements: " . E20R_PLUGIN_DIR );
                if ( ! include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rMeasurementModel.php" ) ) {
                    wp_die( "Unable to load e20rMeasurementModel class" );
                }
                dbg("Model Class loaded");
            }
            */
            if ( !isset( $this->model ) ) {

                dbg("e20rMeasurements::loadData() - Init of measurement model");
                $this->model = new e20rMeasurementModel( $this->id, $when );
            }

            $this->model->getByDate( $when );

        }
        catch ( Exception $e ) {

            dbg("e20rMeasurements::loadData() - Error loading measurement data: " . $e->getMessage() );
            return false;
        }

        return true;
    }

    private function transformForJS( $data ) {

        global $e20rClient;
        global $e20rTables;

        $retVal = array();
        $fields = $e20rTables->getFields('measurements');

        $exclude = array(
            'id',
            'user_id',
            'article_id',
            'recorded_date',
            'essay1',
            'behaviorprogress',
            'front_image',
            'side_image',
            'back_image',
        );

        foreach ($data as $key => $value ) {

            $mKey = array_search( $key, $fields);

            dbg("e20rMeasurements::transformForJS() - Key ({$key}) is really {$mKey}");

            if ( ! in_array( $key, $exclude ) ) {

                $retVal[ $mKey ] = array(
                    'value' => $value,
                    'units' => ( $key != 'weight' ? $e20rClient->getLengthUnit() : $e20rClient->getWeightUnit() ),
                );
            }
        }

        return ( empty( $retVal ) ? $data : $retVal );
    }

    public function getGirthTypes() {

        if (empty( $this->girths ) ) {

            $this->load_girthTypes();
        }

        return $this->girths;
    }

    public function getMeasurement( $when = 'all', $forJS = false ) {

        global $e20rTracker, $current_user;

        if ( ! isset( $this->id ) ) {
            dbg("e20rMeasurements::getMeasurement() - User ID hasn't been set yet.");

            if ( $current_user->ID == 0 ) {
                dbg("e20rMeasurements::getMeasurement() - No user ID defined.");
                return;
            }

            $this->id = $current_user->ID;
        }

        if ( !isset( $this->model ) ) {

            dbg("e20rMeasurements::getMeasurement() - For some reason, the model isn't loaded yet!");
            $this->model = new e20rMeasurementModel( $this->id );
            // $this->model->getFields( $when );
        }

        $byDateArr = (array)$this->model->byDate;

        if ( empty($byDateArr) ) {
            dbg("e20rMeasurements::getMeasurement() - No data loaded yet...");
        }

        if ( $when !== 'all' ) {

            dbg( "e20rMeasurements::getMeasurement({$when}, {$forJS}) was called by: " . $e20rTracker->whoCalledMe() );
            $date = $this->dates[$when];

            dbg( "e20rMeasurements::getMeasurement() - {$when} week: " . $date );

            $data = $this->model->getByDate( $date );

        }
        else {
                dbg("e20rMeasurements::getMeasurement() - Load all");
                $data = $this->model->getMeasurements();
        }

        return ( $forJS === true ? $this->transformForJS( $data ) : $data );

    }

    private function load_EditProgress( $articleId = null ) {

        global $e20rTracker;
        global $current_user;
        global $e20rProgram;
        global $e20rArticle;
        global $e20rMeasurements;
        global $e20rTables;

        dbg("e20rMeasurements::load_EditProgress() - Date supplied is: " . print_r($this->measurementDate, true) . " " . $e20rTracker->whoCalledMe() );
        $count = 1;

        $programId = $e20rProgram->getProgramIdForUser( $current_user->ID,  $articleId );

        dbg("e20rMeasurements::load_EditProgress() - Program ID for user ({$current_user->ID}): {$programId}");
        dbg("e20rMeasurements::load_EditProgress() - Date for use with progress tracking form: {$this->measurementDate}");

        if ( ! isset( $this->model ) ) {
            $this->model = new e20rMeasurementModel($this->id, $this->measurementDate);
        }

        dbg("e20rMeasurements::load_EditProgress() - Measurement model is present. Loading data for {$this->measurementDate}...");
        $measurements   = $this->model->getByDate( $this->measurementDate );

        dbg("e20rMeasurements::load_EditProgress() - Views for measurements are loaded");
        $this->view->init( $this->measurementDate, $measurements );

        ob_start();

        echo $this->view->startProgressForm( $articleId, $programId );
        dbg("e20rMeasurements::load_EditProgress() - Start of measurement form generated.");

        echo $this->view->showChangeBirthDate();

        dbg("e20rMeasurements::load_EditProgress() - Birth date portion of measurement form generated.");

        echo $this->view->showWeightRow( );

        dbg("e20rMeasurements::load_EditProgress() - Weight info for form generated.");
        $this->load_girthTypes();
        dbg("e20rMeasurements::load_EditProgress() - Girth Count: " . count($this->girths));

        echo $this->view->showGirthRow( $this->girths );
        dbg("e20rMeasurements::load_EditProgress() - Girth Row generated");

        echo $this->view->showPhotoRow( $this->requestPhotos() );
        dbg("e20rMeasurements::load_EditProgress() - Photo Row generated");

        echo $this->view->showOtherIndicatorsRow( $this->requestPhotos() );
        dbg("e20rMeasurements::load_EditProgress() - Other Indicators row generated");

        echo $this->view->showProgressQuestionRow( $this->requestPhotos() );
        dbg("e20rMeasurements::load_EditProgress() - Progress Questionnaire row generated");

        echo $this->view->endProgressForm();

        $html = ob_get_clean();

        return $html;
    }

    private function requestPhotos() {

        global $post, $e20rArticle;

        if ( $e20rArticle->isPhotoDay( $post->ID ) === true ) {
            return true;
        }
        // Using the startdate for the current user + whether the current delay falls on a Saturday (and it's a "Photo" day - every 4 weeks starting the 2nd week of the program )x
        // Return true.
        return false;
    }

    public function generatePlotData( $data, $variable ) {

        global $e20rTables;

        $fields = $e20rTables->getFields( 'measurements' );
        $data_matrix = array();

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
        return $data_matrix;
    }

    /***********************************************************
     * Any and all ajax callback functions
     **********************************************************/

    /**
     * Weekly progress form submission/save
     */
    public function saveMeasurement_callback() {

        global $e20rCheckin;

        dbg("e20rMeasurements::saveMeasurement() - Checking access");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("e20rMeasurements::saveMeasurement() - Access approved");

        global $current_user, $e20rTables;

        if ( $current_user->ID == 0 ) {
            dbg("e20rMeasurements::saveMeasurement() - User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        $measurementType = (isset( $_POST['measurement-type'] ) ? sanitize_text_field( trim($_POST['measurement-type']) ) : null );
        $measurementValue = (isset( $_POST['measurement-value'] ) ? sanitize_text_field( trim($_POST['measurement-value'])) : null );
        $user_id = ( isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID );
        $articleId = ( isset( $_POST['article-id'] ) ? intval( $_POST['article-id'] ) : null );
        $post_date = ( isset( $_POST['date'] ) ? sanitize_text_field($_POST['date']) : null );

        dbg("e20rMeasurements::saveMeasurement() - Received from user {$user_id}- Type: {$measurementType}, Value: {$measurementValue}, Date: {$post_date}");

        if ( ! $post_date ) {

            dbg("e20rMeasurements::saveMeasurement() - No date specified for the measurement");
            wp_send_json_error( "No date specified for the measurement" );
        }

        if ( ! $articleId ) {

            dbg("e20rMeasurements::saveMeasurement() - No article ID specified for the measurement ");
            wp_send_json_error( "No article ID specified for the measurement ");
        }

/*        if ( ( ! $measurementType ) || ( ( ! $measurementValue ) && ( $measurementType != 'essay1' ) ) ) {
            dbg("Incomplete measurement data provided.");
            wp_send_json_error("Incomplete measurement data provided.");
        }
*/
        if ( ( $measurementType == 'completed') && ( $measurementValue == 1) ){

            dbg("e20rMeasurements::saveMeasurement() - Measurement form is being saved by the user. TODO: Display with correct header to show completion");
            // TODO: Add functionality to set the check-in for this article_id as "complete"
            if ( $e20rCheckin->setArticleAsComplete( $current_user->ID, $articleId ) ) {
                wp_send_json_success( "Progress saved for {$post_date}" );
            }
            else {
                wp_send_json_success( "Unable to save your progress measurements. Please try again" );
            }
        }

        try {
            dbg( "e20rMeasurements::saveMeasurement() - Saving measurement: {$measurementType} -> {$measurementValue}");

            /*
            if ( ! class_exists( ' e20rMeasurementModel' ) ) {

                dbg("Loading model class for measurements: " . E20R_PLUGIN_DIR );

                if ( ! include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rMeasurementModel.php" ) ) {
                    wp_send_json_error( "Unknown error while trying to save measurement. Please contact the Webmaster!");
                    exit;
                }
                dbg("Measurement class loaded");
            }
            */
            $fields = $e20rTables->getFields( 'measurements' );

            if ( ! isset( $this->model) ) {
                $this->model = new e20rMeasurementModel( $user_id, $post_date );
            }

            if ( ! $this->model->saveField( $fields[$measurementType], $measurementValue, $articleId, $post_date, $user_id ) ) {

                wp_send_json_error( "Unknown error saving measurement for {$measurementType}" );
                exit;
            }

        }
        catch ( Exception $e ) {
            dbg("saveProgressForm() - Exception while saving the {$measurementType} measurement: " . $e->getMessage() );
            wp_send_json_error( "Error saving {$measurementType} measurement" );
            exit;
        }

        dbg("saveProgressForm() - {$measurementType} measurement saved for user ID {$user_id}");
        wp_send_json_success( "Saved {$measurementType} for user ID {$user_id}" );
        exit;
    }

    public function checkProgressFormCompletion_callback() {

        global $wpdb, $current_user;

        if ( $current_user->ID == 0 ) {
            dbg("checkProgressFormCompletion_callback() - User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        dbg("checkProgressFormCompletion_callback() - Checking access");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("checkProgressFormCompletion_callback() - Access approved");

        $articleId = ( isset( $_POST['article-id'] ) ? intval( $_POST['article-id'] ) : null );
        $post_date = ( isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : null );

        if ( $articleId === null ) {
            wp_send_json_success( array( 'progress_form_completed' => false ) );
        }

        if ($post_date === null) {
            wp_send_json_success( array( 'progress_form_completed' => false ) );
        }

        if ( empty( $this->model ) ) {

            dbg("Init of measurement model");
            if ( ! class_exists( ' e20rMeasurementModel' ) ) {
                dbg("Loading model class for measurements: " . E20R_PLUGIN_DIR );
                if ( ! include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rMeasurementModel.php" ) ) {
                    wp_die( "Unable to load e20rMeasurementModel class" );
                }
                dbg("Class loaded");
            }

            $this->model = new e20rMeasurementModel( $this->id );
        }

        dbg("checkProgressFormCompletion_callback() - Ajax sent to calling page");
        wp_send_json_success( array( 'progress_form_completed' => $this->model->checkCompletion( $articleId, $current_user->ID, $post_date ) ) );
        exit;

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

        switch ($setting) {

            case 'items':
                return $options['measuring'];
                break;

        }
    }

    /**
     *
     */

    private function getWeeklyUpdateSettings( $articleId = null, $from_programstart = 1, $day = 0 ) {

        global $post, $e20rArticle, $e20rTracker, $e20rMeasurementDate;

        if ($articleId === null ) {

            global $post;
            $articleId = $post->ID;
        }

        dbg("e20Measurements::getWeeklyUpdateSettings() - Article ID: {$articleId}");

        // $article = new e20rArticle( $articleId );
        $meta = $e20rArticle->getMeta();

        dbg("e20rMeasurements::getWeeklyUpdateSettings() - Article Meta: " . print_r( $meta, true ) );
        if ( $meta->is_measurement_day == 1 ) {
            dbg("e20rMeasurements::getWeeklyUpdateSettings() -- Article has listed this post as a measurement post!");
            $dates = $e20rTracker->datesForMeasurements( $meta['release_date'] );
            dbg("e20rMeasurements::getWeeklyUpdateSettings() - Day is: {$dates['current']}");
            $when = $dates['current'];
            dbg("e20rMeasurements::getWeeklyUpdateSettings() - Measurement date is {$when}");
            $e20rMeasurementDate = $when;
        }

        /*
        if ( ( $from_programstart === 0 ) && ( $day !== 0 ) ) {

        }
        elseif ( ( $from_programstart === 0 ) ) {

        }
        else {
            $when = null;
        }
    */
        return $when;
    }
}