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

        if ( $user_id === null ) {

            global $current_user;

            if ( $current_user->id != 0 ) {
                dbg("User ID: " . $current_user->id );
                $user_id = $current_user->id;
            } /* else {
                throw new Exception( "Error: Unauthorized user" );
            }*/
        }

        $this->id = $user_id;

    }

    function init() {

        dbg('Running INIT for Client Controller');

        if ( ! class_exists( 'e20rClientModel' ) ) {
            require_once( E20R_PLUGIN_DIR . "classes/models/class.e20rClientModel.php" );
        }

        $this->data = new e20rClientModel( $this->id );

        try {
            dbg("Loading data for client model");
            $this->data->load();
        }
        catch ( Exception $e ) {
            dbg("Error loading user data: " . $e->getMessage() );
        }
    }

    public function initClientViews() {

        if ( ! class_exists( 'e20rClientViews' ) ) {
            require_once( E20R_PLUGIN_DIR . "classes/views/class.e20rClientViews.php" );
        }

        $this->show = new e20rClientViews();
    }

    public function shortcode_editProgress( $attributes ) {

        $day = 0;

        extract( shortcode_atts( array(
            'day' => 0,
        ), $attributes ) );

        // TODO: Does user have permission...?
        try {

            global $current_user;

            $when = '2014-11-01'; // DEBUG only
            $this->init();

            $mCtrl = new e20rMeasurements( $this->id, $when );

            dbg("Attempting to load data for {$when}");

            if ( $mCtrl->loadData( $when ) == false ) {

                dbg("No data found for user {$this->id} on {$when}");
            }
            else {
                dbg("Loading progress form for {$when} by {$this->id}");
                return $mCtrl->view_EditProgress( $when );
            }

            dbg('Shortcode completed...');
        }
        catch ( Exception $e ) {
            dbg("Error displaying measurement form (shortcode): " . $e->getMessage() );
        }
    }

    public function load_scripts() {

        dbg("Loading javascript & localizing for e20rClient controller");

        wp_register_script('e20r_progress_js', E20R_PLUGINS_URL . '/js/e20r-progress.js', array('jquery'), '0.1', true);

        /* Load user specific settings */
        wp_localize_script('e20r_progress_js', 'e20r_tracker',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'user_info' => array (
                    "program_startdate" => $this->data->info->program_start,
//                    'article_id' => $this->getArticleId(),
                    "birthdate" => $this->data->info->birthdate,
                    "lengthunits" => $this->data->info->lengthunits,
                    "weightunits" => $this->data->info->weightunits,
                    "gender" => $this->data->info->gender,
                    'progress_photo_dir' => $this->data->info->progress_photo_dir,
                ),
//                'last_week_measurements' => $this->getMeasurement( 'last_week', true ), // Returns array of measurements
//                'measurements' => $this->getMeasurement( 'current', true ), // The current weeks measurements
            )
        );

        wp_enqueue_script('e20r_progress_js');
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