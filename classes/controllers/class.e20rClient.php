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
    public $data = null; // Models

    function e20rClient( $user_id = null ) {

        if (! $user_id ) {
            global $current_user;

            if ( isset( $current_user->ID) ) {
                $this->id = $current_user->ID;
            }
        }
        else {
            $this->id = $user_id;
        }

        if ( $this->id !== null ) {
            add_action( 'wp_ajax_e20r_userinfo', array( &$this, 'ajax_userInfo_callback' ) );
            add_action( 'wp_print_scripts', array( &$this, 'load_scripts' ) );
        }
    }

    function init() {

        dbg('Running INIT for Client Controller');

        if ( $this->id  == null ) {
            global $current_user;

            if ( $current_user->ID != 0 ) {
                dbg("User ID: " . $current_user->ID );
                $user_id = $current_user->ID;
            }
            elseif ( $current_user->ID == 0 ) {
                    auth_redirect();
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
    }

    function ajax_checkMeasurementCompletion() {

        dbg("ajax_checkMeasurementCompletion() - Checking access");
        dbg("Received data: " . print_r($_POST, true ) );

        // check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("ajax_checkMeasurementCompletion() - Access approved");

        //$retVal = '{"progress_form_completed":1}';
        $retVal = array( 'progress_form_completed' => false );
        // dbg( "Retval: {$retVal}");

        echo json_encode( $retVal );
        dbg("ajax_checkMeasurementCompletion() - Ajax sent to calling page");
        exit;

    }
    function ajax_userInfo_callback() {

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

            $userData = $this->data->getInfo();
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

        global $e20r_plot_jscript;
        $e20r_plot_jscript = true;

        $day = 0;

        extract( shortcode_atts( array(
            'day' => 0,
        ), $attributes ) );

        // TODO: Does user have permission...?
        try {

            global $current_user;

            $when = '2014-11-01'; // DEBUG only

            dbg("shortcode: Loading the e20rClient class()");
            $this->init();

            dbg("shortcode: Loading the Measurements class");
            $mCtrl = new e20rMeasurements( $this->id, $when, $this->data->getInfo() );

            dbg("shortcode: Attempting to load data for {$when}");

            if ( $mCtrl->loadData( $when ) == false ) {

                dbg("shortcode: No data found for user {$this->id} on {$when}");
            }
            else {
                dbg("shortcode: Loading progress form for {$when} by {$this->id}");
                return $mCtrl->view_EditProgress( $when );
            }

            dbg('Shortcode completed...');
        }
        catch ( Exception $e ) {
            dbg("Error displaying measurement form (shortcode): " . $e->getMessage() );
        }
    }

    private function getMeasurement( $when, $forJS ) {

        $mCtrl = new e20rMeasurements( $this->id );

        if ( $mCtrl->loadData() == false ) {

            dbg("No data found for user {$this->id} on {$when}");
        }

        dbg("Attempting to load data for {$when}");
        $data =  $mCtrl->getMeasurement( $when, $forJS );

        dbg("Data for client measurements - {$when}: " . print_r( $data, true ));
        return $data;

    }

    public function load_scripts() {

        if ( $this->id == null ) {
            return;
        }

        dbg("Loading javascript & localizing for e20rClient controller");

//        wp_register_script('e20r_progress_js', E20R_PLUGINS_URL . '/js/e20r-progress.js', array('jquery'), '0.1', true);


        dbg("load_scripts() - user id: {$this->id}");

        if ( empty( $this->data ) ) {
            $this->init();
        }

        $userData = $this->data->getInfo();

        /* Load user specific settings */
        wp_localize_script('e20r-progress-js', 'e20r_progress',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'settings' => array(
                    'article_id' => $this->getArticleID(),
                    'lengthunit' => $userData->lengthunittype,
                    'weightunit' => $userData->weightunittype,
                ),
                'measurements' => array(
                    'current' => json_encode( $this->getMeasurement( 'current', true ), JSON_NUMERIC_CHECK ),
                    'last_week' => json_encode( $this->getMeasurement( 'last_week', true ), JSON_NUMERIC_CHECK ),
                    // 'last_week' => json_encode( $this->getMeasurement( 'current', true ), JSON_NUMERIC_CHECK ),
                ),
                'user_info' => array(
                    'userdata' => json_encode( $userData, JSON_NUMERIC_CHECK ),
                    'display_birthdate' => ( ! empty( $userData->birthdate ) ? 1 : 0),

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

        if ( empty( $this->info ) ) {

            try {
                $this->loadInfo();
            }
            catch ( Exception $e ) {
                dbg('Error loading user info from the database: ' . $e->getMessage() );
            }
        }

        return $this->info;
    }

    public function addArticle( $obj ) {

        $key = $this->findArticle( $obj->id );

        if ($key !== false ) {

            $this->article_list[ $key ] = $obj;
        }
        else {

            $this->article_list[] = $obj;
        }
    }

    public function getArticles() {

        return $this->article_list;
    }

    public function getArticle( $id ) {

        if ( $key = $this->findArticle( $id ) !== false ) {
            return $this->article_list[$key];
        }

        return false;
    }

    public function setID( $id ) {

        $this->id = $id;
    }

    public function getID() {

        return $this->id;
    }

    private function findArticle( $id ) {

        foreach ( $this->article_list as $key => $article ) {

            if ( $article->id == $id ) {

                return $key;
            }
        }

        return false;
    }

}