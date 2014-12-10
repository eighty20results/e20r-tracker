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

    private $table = array();
    private $fields = array();

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

        $tmp = new e20rTables();
        $this->table = $tmp->getTable( 'measurements' );
        $this->fields = $tmp->getFields( 'measurements' );

        /*
        $this->measured_items = array(
            'Weight',
            'Girth' => array( 'neck', 'shoulder', 'arm', 'chest', 'waist', 'hip', 'thigh', 'calf' ),
            'Photos',
            'Other Progress Indicators',
            'Progress Questionnaire'
        );
        */
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
                {$this->fields['id']}, {$this->fields['recorded_date']},
                {$this->fields['weight']}, {$this->fields['girth_neck']},
                {$this->fields['girth_shoulder']}, {$this->fields['girth_chest']},
                {$this->fields['girth_arm']}, {$this->fields['girth_waist']},
                {$this->fields['girth_hip']}, {$this->fields['girth_thigh']},
                {$this->fields['girth_calf']}, {$this->fields['girth']}
                FROM {$this->table}
                WHERE {$this->fields['user_id']} = %d
                ORDER BY {$this->fields['recorded_date']} ASC
            ", $this->client_id );

        dbg("SQL for measurements: " . $sql );

        $this->all = $this->remap_fields( $wpdb->get_results( $sql ) );
    }

    public function loadForDate( $date ) {

        global $wpdb;

        $when = date( 'Y-m-d H:i:s', strtotime( $date ) );

        $sql = $wpdb->prepare(
            "
              SELECT
                {$this->fields['id']}, {$this->fields['recorded_date']},
                {$this->fields['weight']}, {$this->fields['girth_neck']},
                {$this->fields['girth_shoulder']}, {$this->fields['girth_chest']},
                {$this->fields['girth_arm']}, {$this->fields['girth_waist']},
                {$this->fields['girth_hip']}, {$this->fields['girth_thigh']},
                {$this->fields['girth_calf']}, {$this->fields['girth']}
                FROM {$this->table}
                WHERE {$this->fields['user_id']} = %d
                AND {$this->fields['recorded_date']} = %s
                ORDER BY {$this->fields['recorded_date']} ASC
            ",
            $this->client_id,
            $when
        );

        $this->all = $this->remap_fields( $wpdb->get_results( $sql ) );
    }

    public function getFields() {

        return $this->fields;
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

    public function save( $form_key, $value, $articleID, $when ) {

        if ( $this->client_id == 0 ) {

            throw new Exception( "User is not logged in" );
        }

        dbg("Received variables: {$form_key}, {$value}, {$articleID}, {$when}");
        global $wpdb;

        $varFormat = false;

        $existing = $wpdb->get_row( $sql = $wpdb->prepare(
                    "SELECT *
                        FROM {$this->table}
                        WHERE ( {$this->fields['user_id']} = %d ) AND
                              ( {$this->fields['recorded_date']} = %s )",
                        $this->client_id,
                        "{$when} 00:00:00"
                    )
        );

        dbg("SQL for save: " . $sql );

        if ( is_wp_error( $existing ) ) {
            dbg("Error updating database: " . $wpdb->print_error() );
            throw new Exception( "Error updating database: " . $wpdb->print_error() );
        }

        dbg("We found " . count($existing) . " records in the database for {$this->client_id} on {$when} 00:00:00");

        if ( ! empty( $existing ) ) {

            dbg("Assuming we've gotta include existing data when updating the database");

            $data = array(
                $this->fields['id']            => $existing->{$this->fields['id']},
                $this->fields['user_id']       => $this->client_id,
                $this->fields['article_id']    => $articleID,
                $this->fields['recorded_date'] => "{$when} 00:00:00",
            );

            $format = array( '%d', '%d', '%d', '%s' );

            dbg("Adding existing data to the database: " . print_r($existing, true) );

            foreach ( $existing as $key => $val ) {

                dbg("Key: {$key} => Val: {$val}");

                if ( $key != $form_key ) {

                    if ( $val === null ) {
                        dbg("Skipping {$key}");
                        continue;
                    }

                    dbg("Existing data from the table");
                    $data = array_merge( $data, array( $key => $val ) );
                    $varFormat = $this->setFormat( $val );
                }
                else {

                    dbg("Updating {$form_key} data in Database: {$value}");
                    $data = array_merge( $data, array( $form_key => $value ) );
                    $varFormat = $this->setFormat( $value );

                }

                if (  $varFormat !== false ) {
                    dbg("Adding new format: {$varFormat}");
                    $format = array_merge( $format, array( $varFormat ) );
                }
                else {
                    dbg("Invalid format for value: {$varFormat}");
                    throw new Exception( "Submitted value is of an invalid type" );
                    return false;
                }

                $data = array_merge($data, $newData);
            }
            dbg("Data to update: " . print_r( $data, true));

        }
        else {
            $data = array(
                $this->fields['user_id']       => $this->client_id,
                $this->fields['article_id']    => $articleID,
                $this->fields['recorded_date'] => "{$when} 00:00:00",
                $this->fields[ $form_key ]     => $value
            );

            $varFormat = $this->setFormat( $value );
            $format    = array( '%d', '%d', '%s' );

            if ( $varFormat !== false ) {

                $format = array_merge( $format, array( $varFormat ) );
            }
            else {
                dbg("Invalid format for {$value}");
                throw new Exception( "Submitted value ($value) is of an invalid type" );

                return false;
            }
        }

        dbg("Data to 'replace': " . print_r( $data, true ) );
        dbg("formats for Data to 'replace': " . print_r( $format, true ) );

        $wpdb->replace( $this->table, $data, $format );
        return true;


    }

    /**
     * Identify the format of the variable value.
     *
     * @param $value -- The variable to set the format for
     *
     * @return bool|string -- Either %d, %s or %f (integer, string or float). Can return false if unsupported format.
     */
    private function setFormat( $value ) {

        switch ( gettype( $value ) ) {

            case 'integer':
                return '%d';
                break;

            case 'double':
                return '%f';
                break;

            case 'string':
                return '%s';
                break;
        }

        return false;
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
            $tmp->id = $record->{$this->fields['id']};
            $tmp->recorded_date = $record->{$this->fields['recorded_date']};
            $tmp->weight = $record->{$this->fields['weight']};
            $tmp->neck = $record->{$this->fields['girth_neck']};
            $tmp->shoulder = $record->{$this->fields['girth_shoulder']};
            $tmp->chest = $record->{$this->fields['girth_chest']};
            $tmp->arm = $record->{$this->fields['girth_arm']};
            $tmp->waist = $record->{$this->fields['girth_waist']};
            $tmp->hip = $record->{$this->fields['girth_hip']};
            $tmp->thigh = $record->{$this->fields['girth_thigh']};
            $tmp->calf = $record->{$this->fields['girth_calf']};
            $tmp->girth = $record->{$this->fields['girth']};

            $retArr[] = $tmp;

            $when = date( 'Y-m-d', strtotime( $tmp->recorded_date ) );
            $this->byDate[$when] = $tmp;

            unset($tmp);

        }

        return $retArr;
    }
} 