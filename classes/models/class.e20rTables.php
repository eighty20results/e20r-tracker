<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/8/14
 * Time: 2:52 PM
 */

if ( ! class_exists( 'e20rTables' ) ):

class e20rTables {

    protected $tables = null;
    protected $oldTables = null;
    protected $fields = array();

    protected $inBeta = false;

    public function e20rTables() {

        if ( ! function_exists( 'in_betagroup' ) ) {
            dbg( "Error: in_betagroup function is missing!" );
            wp_die( "Critical plugin functionality is missing: in_betagroup()" );
        }

    }


    public function init( $user_id = null ) {

        if ( ! function_exists( 'get_user_by' ) ) {
            dbg("e20rTables::init() - Wordpress not fully loaded yet...");
            return;
        }

        dbg("e20rTables::constructor() - Initializing the e20rTables() class");
        global $wpdb, $current_user;

        $this->inBeta = in_betagroup( $user_id );

        $this->tables = new stdClass();

        /* The database tables used by this plugin */
        $this->tables->checkin       = $wpdb->prefix . 'e20r_checkin';
        $this->tables->assignments   = $wpdb->prefix . 'e20r_assignments';
        $this->tables->responses     = $wpdb->prefix . 'e20r_answers';
        $this->tables->measurements  = $wpdb->prefix . 'e20r_measurements';
        $this->tables->client_info   = $wpdb->prefix . 'e20r_client_info';
        $this->tables->program       = $wpdb->prefix . 'e20r_programs';
        $this->tables->sets          = $wpdb->prefix . 'e20r_sets';
        $this->tables->exercise      = $wpdb->prefix . 'e20r_exercises';
        $this->tables->appointments  = $wpdb->prefix . 'app_appointments';
        $this->tables->assignments   = $wpdb->prefix . 'e20r_assignment';
        $this->tables->questions     = $wpdb->prefix . 'e20r_question';


        if ( ( $this->inBeta ) ) {

            dbg("User {$current_user->ID} IS in the beta group");
            $this->tables->assignments  = "{$wpdb->prefix}s3f_nourishAssignments";
            $this->tables->compliance   = "{$wpdb->prefix}s3f_nourishHabits";
            $this->tables->surveys      = "{$wpdb->prefix}e20r_Surveys";
            $this->tables->measurements = "{$wpdb->prefix}nourish_measurements";
            $this->tables->meals        = "{$wpdb->prefix}wp_s3f_nourishMeals";

        }
    }

    // TODO: Implement this as a custome post type.
    private function loadAssignmentFields() {

    }

    // TODO: Implement this as a custom post type.
    private function loadQuestionFields() {

    }

    /**
     * Returns status for membership in the Nourish Beta group.
     *
     * @return bool -- True if the user is a member of the Nourish Beta group
     */
    public function isBetaClient() {

        return $this->inBeta;
    }

    private function loadCheckinFields() {

        $this->fields['checkin'] = array(
            'id'                => 'id',
            'user_id'           => 'user_id',
            'checkin_date'      => 'checkin_date',
            'checkin_item_id'   => 'checkin_item_id',
            'checkedin'         => 'checkedin',
            'checkin_type'      => 'checkin_type',
        );
    }

    private function loadProgramFields() {

        $this->fields['program'] = array(
            'id'                => 'id',
            'program_name'      => 'program_name',
            'program_shortname' => 'program_shortname',
            'description'       => 'description',
            'starttime'         => 'starttime',
            'endtime'           => 'endtime',
            'member_id'         => 'member_id',
            'belongs_to'        => 'belongs_to'
        );
    }

    private function loadMeasurementFields() {

        if ( ! $this->inBeta ) {

            $this->fields[ 'measurements' ] = array(
                'id'                    => 'id',
                'user_id'               => 'user_id',
                'article_id'            => 'article_id',
                'recorded_date'         => 'recorded_date',
                'weight'                => 'weight',
                'girth_neck'            => 'neck',
                'girth_shoulder'        => 'shoulder',
                'girth_chest'           => 'chest',
                'girth_arm'             => 'arm',
                'girth_waist'           => 'waist',
                'girth_hip'             => 'hip',
                'girth_thigh'           => 'thigh',
                'girth_calf'            => 'calf',
                'girth'                 => 'girth',
                'behaviorprogress'      => 'behaviorprogress',
                'essay1'                => 'essay1',
                'front_image'           => 'front_image',
                'side_image'            => 'side_image',
                'back_image'            => 'back_image',
            );
        }
        else {

            $this->fields[ 'measurements' ] = array(
                'id' => 'lead_id',
                'user_id' => 'created_by',
                'article_id' => 'article_id',
                'recorded_date' => 'recordedDate',
                'weight' => 'weight',
                'girth_neck' => 'neckCM',
                'girth_shoulder' => 'shoulderCM',
                'girth_chest' => 'chestCM',
                'girth_arm' => 'armCM',
                'girth_waist' => 'waistCM',
                'girth_hip' => 'hipCM',
                'girth_thigh' => 'thighCM',
                'girth_calf' => 'calfCM',
                'girth' => 'totalGrithCM',
                'behaviorprogress' => 'behaviorprogress',
                'essay1' => 'essay1',
                'front_image'           => 'front_image',
                'side_image'            => 'side_image',
                'back_image'            => 'back_image',
            );
        }
    }

    private function loadFields( $name ) {

        switch ( $name ) {

            case 'measurements':
                $this->loadMeasurementFields();
                break;

            case 'program':
                $this->loadProgramFields();
                break;

            case 'checkin':
                $this->loadCheckinFields();
                break;
        }
    }

    public function getTable( $name = null ) {

        if ( ! $name ) {
            return $this->tables;
        }

        if ( empty ( $this->tables->{$name} ) ) {
            throw new Exception( "No {$name} table exists" );
            return false;
        }

        return $this->tables->{$name};
    }

    public function getFields( $name = null ) {

        if ( empty( $this->fields[ $name ] ) ) {

            $this->loadFields( $name );
        }

        return $this->fields[$name];
    }

    public function createTables() {

    }
}
endif;