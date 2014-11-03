<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 11/1/14
 * Time: 5:38 PM
 */

class e20rMeasurementModel {

    private $client_id = null;
    private $lDate = null;

    private $measured_items = array();

    public $all = array();
    public $byDate = array();

    private $tables = array();

    public function e20rMeasurementModel( $user_id = null, $forDate = null ) {

        global $wpdb;

        $this->client_id = $user_id;

        dbg("Model of Measurements for {$user_id}");

        if ( $forDate !== null ) {

            $this->lDate = new DateTime( $forDate, new DateTimeZone( get_option( 'timezone_string' ) ) );
        }
        else {

            $this->lDate = new DateTime( 'NOW', new DateTimeZone( get_option( 'timezone_string' ) ) );
        }

        // $this->unit_type = $this->userInfo( 'unit-type' );

        // dbg("Last weeks date: " . $this->getMeasurements( 'last_week' ) );

        if ( ! function_exists( 'in_betagroup' ) ) {
            dbg("in_betagroup function is missing???");
        }

        if ( ! in_betagroup( $this->client_id ) ) {

            dbg("User {$this->client_id} is NOT in the beta group");

            $this->tables['name'] = $wpdb->prefix . 'e20r_measurements';

            $this->tables['fields'] = array(
                'id' => 'id',
                'user_id' => 'user_id',
                'recorded_date' => 'recorded_date',
                'weight' => 'weight',
                'neck' => 'neck',
                'shoulder' => 'shoulder',
                'chest' => 'chest',
                'arm' => 'arm',
                'waist' => 'waist',
                'hip' => 'hip',
                'thigh' => 'thigh',
                'calf' => 'calf',
                'girth' => 'girth'
            );

        }
        else {

            $this->tables['name'] = $wpdb->prefix . 'nourish_measurements';

            $this->tables['fields'] = array(
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

        $this->measured_items = array(
            'Body Weight',
            'Girth' => array( 'neck', 'shoulder', 'arm', 'chest', 'waist', 'hip', 'thigh', 'calf' ),
            'Photos',
            'Other Progress Indicators',
            'Progress Questionnaire'
        );

        //add_action( 'wp_enqueue_scripts', array( &$this, 'load_progress_scripts') );

        return true;
    }

    /**
     * Loads any already recorded measurements from the database
     */
    public function loadAll() {

        global $wpdb;

        $sql = $wpdb->prepare(
            "
              SELECT
                {$this->tables['fields']['id']}, {$this->tables['fields']['recorded_date']},
                {$this->tables['fields']['weight']}, {$this->tables['fields']['neck']},
                {$this->tables['fields']['shoulder']}, {$this->tables['fields']['chest']},
                {$this->tables['fields']['arm']}, {$this->tables['fields']['waist']},
                {$this->tables['fields']['hip']}, {$this->tables['fields']['thigh']},
                {$this->tables['fields']['calf']}, {$this->tables['fields']['girth']}
                FROM {$this->tables['name']}
                WHERE {$this->tables['fields']['user_id']} = %d
                ORDER BY {$this->tables['fields']['recorded_date']} ASC
            ", $this->client_id );

        dbg("SQL for measurements: " . $sql );

        $this->all = $this->remap_fields( $wpdb->get_results( $sql ) );
    }

    public function loadForDate( $date ) {

        global $wpdb;

        $sql = $wpdb->prepare(
            "
              SELECT
                {$this->tables['fields']['id']}, {$this->tables['fields']['recorded_date']},
                {$this->tables['fields']['weight']}, {$this->tables['fields']['neck']},
                {$this->tables['fields']['shoulder']}, {$this->tables['fields']['chest']},
                {$this->tables['fields']['arm']}, {$this->tables['fields']['waist']},
                {$this->tables['fields']['hip']}, {$this->tables['fields']['thigh']},
                {$this->tables['fields']['calf']}, {$this->tables['fields']['girth']}
                FROM {$this->tables['name']}
                WHERE {$this->tables['fields']['user_id']} = %d
                AND {$this->tables['fields']['recorded_date']} = %s
                ORDER BY {$this->tables['fields']['recorded_date']} ASC
            ",
            $this->client_id,
            $date
        );

        dbg("SQL for measurements loaded by Date: " . $sql );

        $this->all = $this->remap_fields( $wpdb->get_results( $sql ) );


    }

    public function getFields() {

        return $this->tables['fields'];
    }

    /**
     * Load and return all measurement records for the specific user ID
     *
     * @return array -- Array of measurement record for the $this->client_id
     */
    public function getMeasurements() {

        if ( empty( $this->all ) ) {

            $this->loadAll();
        }

        return $this->all;
    }

    /**
     * Returns the client id
     *
     * @return int|null -- Return the client's ID (WP user ID)
     */
    public function getClientId() {

        return $this->client_id;
    }

    /**
     * @param string $date -- Date of the record to return.
     *
     * @return array|bool -- The measurement record for the specified date.
     */
    public function getByDate( $date = 'all' ) {

        if ( empty( $this->byDate[ $date ] ) && (! empty( $this->byDate ) ) ) {

            $this->loadAll();
        }

        if ( $date == 'all' ) {

            return $this->byDate;
        }
        else {
            return ( ! empty( $this->byDate[ $date ] ) ? $this->byDate[ $date ] : false );
        }
    }

    public function setByDate( $record, $date ) {

        $this->byDate[$date] = $record;

    }

    public function save( $form_key, $value) {

        if ( $this->client_id == 0 ) {

            throw new Exception( "User is not logged in" );
        }


    }

    /**
     * Re-map the actual database fields to the correct variables (workaround for beta group & Gravity Forms/List plugin.
     *
     * @param (array) $data - List of $wpdb objects (from the database)
     *
     * @return array - Re-mapped DB fields
     */
    private function remap_fields( $data ) {

        $retArr = array();

        foreach ( $data as $record ) {

            $tmp = new stdClass();
            $tmp->id = $record->{$this->tables['fields']['id']};
            $tmp->recorded_date = $record->{$this->tables['fields']['recorded_date']};
            $tmp->weight = $record->{$this->tables['fields']['weight']};
            $tmp->neck = $record->{$this->tables['fields']['neck']};
            $tmp->shoulder = $record->{$this->tables['fields']['shoulder']};
            $tmp->chest = $record->{$this->tables['fields']['chest']};
            $tmp->arm = $record->{$this->tables['fields']['arm']};
            $tmp->waist = $record->{$this->tables['fields']['waist']};
            $tmp->hip = $record->{$this->tables['fields']['hip']};
            $tmp->thigh = $record->{$this->tables['fields']['thigh']};
            $tmp->calf = $record->{$this->tables['fields']['calf']};
            $tmp->girth = $record->{$this->tables['fields']['girth']};

            $retArr[] = $tmp;
            $this->byDate[$tmp->recorded_date] = $tmp;

            unset($tmp);

        }

        return $retArr;
    }
} 