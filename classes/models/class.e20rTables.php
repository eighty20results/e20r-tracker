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

        global $wpdb, $current_user;

        $inBeta = false;

        if ( ! function_exists( 'in_betagroup' ) ) {
            dbg("Error: in_betagroup function is missing!");
            wp_die("Critical plugin functionality is missing: in_betagroup()");
        }

        $this->inBeta = in_betagroup( $current_user->ID );

        $this->tables = new stdClass();

        /* The database tables used by this plugin */
        $this->tables->checkin_items = $wpdb->prefix . 'e20r_checkin_items';
        $this->tables->checkin_rules = $wpdb->prefix . 'e20r_checkin_rules';
        $this->tables->checkin       = $wpdb->prefix . 'e20r_checkin';
        $this->tables->assignments   = $wpdb->prefix . 'e20r_assignments';
        $this->tables->responses     = $wpdb->prefix . 'e20r_answers';
        $this->tables->measurements  = $wpdb->prefix . 'e20r_measurements';
        $this->tables->client_info   = $wpdb->prefix . 'e20r_client_info';
        $this->tables->programs      = $wpdb->prefix . 'e20r_programs';
        $this->tables->sets          = $wpdb->prefix . 'e20r_sets';
        $this->tables->exercise      = $wpdb->prefix . 'e20r_exercises';

        if ( ( $this->inBeta ) ) {

            dbg("User {$current_user->ID} IS in the beta group");
            $this->tables->assignments  = "{$wpdb->prefix}s3f_nourishAssignments";
            $this->tables->compliance   = "{$wpdb->prefix}s3f_nourishHabits";
            $this->tables->surveys      = "{$wpdb->prefix}e20r_Surveys";
            $this->tables->measurements = "{$wpdb->prefix}nourish_measurements";
            $this->tables->meals        = "{$wpdb->prefix}wp_s3f_nourishMeals";

        }
    }

    private function loadMeasurementFields() {

        if ( ! $this->inBeta ) {

            $this->fields[ 'measurements' ] = array(
                'id'            => 'id',
                'user_id'       => 'user_id',
                'recorded_date' => 'recorded_date',
                'weight'        => 'weight',
                'neck'          => 'neck',
                'shoulder'      => 'shoulder',
                'chest'         => 'chest',
                'arm'           => 'arm',
                'waist'         => 'waist',
                'hip'           => 'hip',
                'thigh'         => 'thigh',
                'calf'          => 'calf',
                'girth'         => 'girth'
            );
        }
        else {

            $this->fields[ 'measurements' ] = array(
                'id' => 'lead_id',
                'user_id' => 'created_by',
                'recorded_date' => 'recordedDate',
                'weight' => 'weight',
                'neck' => 'neckCM',
                'shoulder' => 'shoulderCM',
                'chest' => 'chestCM',
                'arm' => 'armCM',
                'waist' => 'waistCM',
                'hip' => 'hipCM',
                'thigh' => 'thighCM',
                'calf' => 'calfCM',
                'girth' => 'totalGrithCM'
            );
        }
    }

    private function loadCheckinFields() {

    }
    private function loadFields( $name ) {

        switch ( $name ) {
            case 'measurements':
                $this->loadMeasurementFields();
                break;
        }
    }

    public function getTable( $name = null ) {

        if ( empty ( $this->tables->{$name} ) ) {
            throw new Exception( "No {$name} table exists" );
            return false;
        }

        if ( ! $name ) {
            return $this->tables;
        }

        return $this->tables->{$name};
    }

    public function getFields( $name = null ) {

        if ( empty( $this->fields[ $name ] ) ) {

            $this->loadFields( $name );
        }

        return $this->fields[$name];
    }
}
endif;