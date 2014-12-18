<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/22/14
 * Time: 1:16 PM
 */

class e20rClient {

    private $id = null;

    public $show = null; // Views
    public $data = null; // Client Model
    private $measurements = null; // Measurements class
    private $current_programs = array();

    private $lw_measurement;

    private $assignments = null;

    public function e20rClient( $user_id = null ) {

        if (! $user_id ) {
            global $current_user;

            if ( isset( $current_user->ID) ) {
                $this->id = $current_user->ID;
            }
        }
        else {
            $this->id = $user_id;
        }
        dbg("e20rClient::constructor() - Loading shortcode hook for measurements");
        add_shortcode( 'track_measurements', array( &$this, 'shortcode_editProgress' ) );
    }

    public function init() {

        dbg('Running INIT for Client Controller');

        if ( $this->id  == null ) {
            global $current_user;

            if ( $current_user->ID != 0 ) {
                dbg("User ID: " . $current_user->ID );
                $this->id = $current_user->ID;
            }
        }

        if ( ! class_exists( 'e20rClientModel' ) ) {
            include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rClientModel.php" );
        }

        try {

            $this->data = new e20rClientModel( $this->id );

            dbg("Loading data for client model");
            $this->data->load();
        }
        catch ( Exception $e ) {
            dbg("Error loading user data: " . $e->getMessage() );
        }

        if ( $this->id !== null ) {

            add_action( 'wp_print_scripts', array( &$this, 'load_scripts' ) );
            // add_action( 'wp_ajax_e20r_userinfo', array( &$this, 'ajax_userInfo_callback' ) );
//            add_action( 'wp_ajax_e20r_measurementDataForUser', array( &$this, 'ajax_getMeasurementDataForUser' ) );
            // add_action( 'wp_ajax_checkCompletion', array(  &$this, 'ajax_checkMeasurementCompletion' ) );

        }
    }

    public function load_hooks() {

        dbg("e20rClient() - Loading hooks for Client data");
        add_action( 'wp_ajax_updateUnitTypes', array( &$this, 'updateUnitTypes') );

        add_action( 'wp_ajax_e20r_clientDetail', array( &$this, 'ajax_clientDetail' ) );
        add_action( 'wp_ajax_e20r_complianceData', array( &$this, 'ajax_complianceData' ) );
        add_action( 'wp_ajax_e20r_assignmentData', array( &$this, 'ajax_assignmentData' ) );

        // Used by the wp-admin backend for the Coaches
        add_action( 'wp_ajax_get_memberlistForLevel', array( &$this, 'ajax_getMemberlistForLevel' ) );

        add_action( 'wp_ajax_nopriv_updateUnitTypes', 'e20r_ajaxUnprivError' );

    }

    public function updateUnitTypes() {

        dbg( "updateUnitTypes() - Attempting to update the Length or weight Units via AJAX");
        dbg("POST content: " . print_r($_POST, true));

        // Save the actual setting for the current user

        // Update the data for this user in the measurements table.
    }

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

    public function shortcode_editProgress( $attributes ) {

        global $e20r_plot_jscript, $current_user, $post;
        $e20r_plot_jscript = true;

        $day = 0;
        $from_programstart = 1;

        extract( shortcode_atts( array(
            'day' => 0,
            'from_programstart' => 1,
        ), $attributes ) );

        if ( $current_user->ID == 0 ) {
            dbg("User Isn't logged in! Redirect immediately");
            auth_redirect();
        }

        // TODO: Does user have permission...?
        try {

            global $current_user, $e20rArticle;

            if ( $e20rArticle === null ) {
                $e20rArticle = new e20rArticle( $post->ID );
            }

            $when = $this->getWeeklyUpdateSettings( $e20rArticle->getID(), $from_programstart, $day );

            dbg("shortcode: Loading the e20rClient class()");
            $this->init();

            if ( ! class_exists( ' e20rMeasurementModel' ) ) {

                dbg("shortcode: Loading model class for measurements: " . E20R_PLUGIN_DIR );

                if ( ! include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rMeasurementModel.php" ) ) {
                    wp_die( "Unable to load e20rMeasurementModel class" );
                }
                dbg("shortcode: Model Class loaded");
            }

            if ( empty( $this->measurements ) ) {
                dbg("shortcode: Loading measurement class");
                $this->measurements = new e20rMeasurements( $this->id, $when );
                $this->measurements->init();
            }

//            dbg("shortcode: Attempting to load data for {$when}");
//            $this->measurements->getMeasurement( $when );


            dbg("shortcode: Loading progress form for {$when} by {$this->id}");
            return $this->measurements->view_EditProgress( $when, $this->data->getInfo() );

            dbg('Shortcode: Form load completed...');
        }
        catch ( Exception $e ) {
            dbg("Error displaying measurement form (shortcode): " . $e->getMessage() );
        }
    }

    private function getWeeklyUpdateSettings( $articleId = null, $from_programstart = 1, $day = 0 ) {

        global $post, $e20rTracker, $e20rArticle;

        if ($articleId === null ) {

            global $post;
            $articleId = $post->ID;
        }

        if ( $e20rArticle == null ) {
            dbg("e20rClient::getWeeklyUpdateSettings() - WARNING: Loading e20rArticle global here. Should have done that on plugin load!");
            $e20rArticle = new e20rArticle( null, $post->ID );
            $e20rArticle->init();
        }
        // $article = new e20rArticle( $articleId );
        $meta = $e20rArticle->getMeta();

        if ( $meta->is_measurement_day == 1 ) {
            dbg("e20rClient::getWeeklyUpdateSettings() -- Measurement date!");
            $dates = $e20rTracker->datesForMeasurements( $meta->release_date, '-1 week', CONST_MEASUREMENTDAY );

            if ( is_array($dates ) ) {
                $date = $dates[0];
            }

            $when = $date;
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

    private function getSaturday( $program_day ) {

    }

    /*
    private function getMeasurement( $when, $forJS ) {

        if ( empty( $this->data ) ) {
            dbg("getMeasurement() - Loading data on behalf of the e20rClient class...");
            $this->init();
        }

        if ( $this->{$when} == 'empty' ) {
            dbg("There are no measurements for {$when}");
            return null;
        }

        dbg("getMeasurement() - When value: {$when}");

        if ( empty( $this->{$when}->id ) ) {

            dbg("getMeasurement() - Loading {$when} measurements from DB");
            $this->{$when} = $this->data->measurements->getMeasurement( $when, $forJS );

            if ( empty( $this->{$when}->id ) ) {
                $this->{$when} = 'empty';
            }
        }

        dbg("getMeasurement() - Data for client measurements - {$when}: " . print_r( $this->{$when}, true ));
        return $this->{$when};
    }
*/
    public function load_scripts() {

        if ( $this->id == null ) {
            return;
        }

        dbg("e20rClient_load_scripts() - user id: {$this->id}");

        if ( empty( $this->data ) ) {
            $this->init();
        }

        if ( empty( $this->lw_measurement ) ) {
            $this->lw_measurement = $this->measurements->getMeasurement( 'last_week', true );
        }

        $userData = $this->data->info;

        if ( $userData->incomplete_interview ) {
            dbg("No USER DATA found in the database. Redirect to User interview page!");
        }

        dbg("User Data: " . print_r( $userData, true ));

        /* Load user specific settings */
        wp_localize_script('e20r-progress-js', 'e20r_progress',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'settings' => array(
                    'article_id' => $this->getArticleID(),
                    'lengthunit' => $userData->lengthunittype,
                    'weightunit' => $userData->weightunittype,
                    'imagepath' => E20R_PLUGINS_URL . '/images/',
                    'overrideDiff' => (isset( $this->lw_measurement->id ) ? false : true )
                ),
                'measurements' => array(
                    'last_week' => json_encode( $this->lw_measurement, JSON_NUMERIC_CHECK ),
                    // 'last_week' => json_encode( $this->measurements->getMeasurement( 'current', true ), JSON_NUMERIC_CHECK ),
                ),
                'user_info' => array(
                    'userdata' => json_encode( $userData, JSON_NUMERIC_CHECK ),
                    'progress_pictures' => '',
                    'display_birthdate' => ( empty( $userData->birthdate ) ? false : true),

                ),
            )
        );

//        wp_enqueue_script('e20r_progress_js');
//        wp_enqueue_script('e20r_progress_libs');
        dbg("load_scripts() - Javascript for Progress Report loaded");
    }

    private function getArticleID() {

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


}