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

        $tmp = new e20rTables();
        $this->table = $tmp->getTable( 'measurements' );
        $this->fields = $tmp->getFields( 'measurements' );

        unset($tmp);
    }

    /**
     * Loads any already recorded measurements from the database
     */
    private function loadAll() {

        if ( false === ( $this->all = get_transient( "e20r_all_client_measurements_{$this->client_id}" ) ) ) {

            dbg("Loading ALL client measurements for user_id {$this->client_id} from the database");

            // Not stored yet, so grab the data from the DB and store it.
            global $wpdb;

            $sql = $wpdb->prepare(
                "
                  SELECT
                    {$this->fields['id']}, {$this->fields['recorded_date']},
                    {$this->fields['weight']}, {$this->fields['girth_neck']},
                    {$this->fields['girth_shoulder']}, {$this->fields['girth_chest']},
                    {$this->fields['girth_arm']}, {$this->fields['girth_waist']},
                    {$this->fields['girth_hip']}, {$this->fields['girth_thigh']},
                    {$this->fields['girth_calf']}, {$this->fields['girth']},
                    {$this->fields['essay1']}, {$this->fields['behaviorprogress']}
                    FROM {$this->table}
                    WHERE {$this->fields['user_id']} = %d
                    ORDER BY {$this->fields['recorded_date']} ASC
                ", $this->client_id );


            $results = $wpdb->get_results( $sql );

            dbg("MeasurementModel_loadAll() - loaded " . count($results) . " records");

            if ( ! empty( $results ) ) {

                $this->all = $this->remap_fields( $results );
            }
            else {
                throw new Exception("Warning: No data found for user_id {$this->client_id}");
            }

            set_transient( "e20r_all_client_measurements_{$this->client_id}", $this->all, 900 );
        }

        if ( false === ( $this->byDate = get_transient( "e20r_byDate_client_measurements_{$this->client_id}" ) ) ) {

            dbg("MeasurementModel_loadAll() - Loading fresh copy if byDate version of measurements");

            foreach( $this->all as $rec ) {
                $ts = strtotime($rec->{$this->fields['recorded_date']});
                $this->byDate[date('Y-m-d', $ts)] = $rec;
            }

            set_transient( "e20r_byDate_client_measurements_{$this->client_id}", $this->byDate, 900);
        }
    }

    public function checkCompletion( $article, $user_id, $date ) {

        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT id,
                ( COUNT({$this->fields['girth_neck']}) +
                  COUNT({$this->fields['girth_shoulder']}) +
                  COUNT({$this->fields['girth_chest']}) +
                  COUNT({$this->fields['girth_arm']}) +
                  COUNT({$this->fields['girth_waist']}) +
                  COUNT({$this->fields['girth_hip']}) +
                  COUNT({$this->fields['girth_thigh']}) +
                  COUNT({$this->fields['girth_calf']}) ) AS completed,
                      {$this->fields['girth']} AS girth,
                      {$this->fields['weight']} AS weight,
                      {$this->fields['behaviorprogress']} AS behaviorprogress,
                      {$this->fields['essay1']} AS essay1
                FROM {$this->table}
                WHERE {$this->fields['article_id']} = %d AND
                      {$this->fields['user_id']} = %d AND
                      {$this->fields['recorded_date']} = %s
                ",
                $article,
                $user_id,
                $date
        );

        $results = $wpdb->get_results( $sql );
        $completionStatus = false;
        $girth_compl = true;
        $weight_compl = true;
        $completionPct = 0;

        if ( ! empty( $results ) ) {

            foreach( $results as $res ) {

                if ( $res->completed < 8 ) {

                    $completionPct = ( $res->completed / 8 );
                    $girth_compl = false;
                }

                if ( empty ($res->weight ) ) {
                    $weight_compl = false;
                }

                if ( empty( $res->girth ) && ( $completionPct == 1 ) ) {
                    $this->setTotalGirth( $res->id );
                }
            }

            $completionStatus = ( $girth_compl && $weight_compl );

            dbg("Completion Status: {$completionStatus} and percentage: {$completionPct}");
        }

        return $completionStatus;
    }

    private function setTotalGirth( $recordId ) {

        global $wpdb;

        $SQL = "SELECT
                  {$this->fields['girth_neck']} AS neck, {$this->fields['girth_shoulder']} AS shoulder,
                  {$this->fields['girth_chest']} AS chest, {$this->fields['girth_arm']} AS arm,
                  {$this->fields['girth_waist']} AS waist, {$this->fields['girth_hip']} AS hip,
                  {$this->fields['girth_thigh']} AS thigh, {$this->fields['girth_calf']} AS calf
                FROM {$this->table}
                WHERE {$this->fields['id']} = {$recordId}";

        $row = $wpdb->get_row( $SQL );

        if ( ! empty($row) ) {
            dbg("Updating the total GIRTH value in the database");
            $wpdb->update(
                $this->table,
                array( $this->fields['girth'] => ( $row->neck + $row->shoulder + $row->chest + $row->arm + $row->waist + $row->hip + $row->thigh + $row->calf ) ),
                array( $this->fields['id'] => $recordId ), array( '%f', ));
        }
    }

    public function getFields() {

        return $this->fields;
    }

    private function loadNullMeasurement( $when ) {

        dbg("MeasurementsModel_loadNullMeasurement() - Loading empty measurement info");

        global $e20rTracker;

        $fields = $e20rTracker->tables->getFields('measurements');

        dbg("MeasurementsModel_loadNullMeasurement() - Fields: " . print_r($fields, true));

        $nullMeasurement = new stdClass();

        foreach( $fields as $field => $val ) {
            $nullMeasurement->{$field} = null;
        }

        dbg("MeasurementsModel_loadNullMeasurement() - Returning empty record for {$when}" );

        if ( $when != 'all' ) {

            return array( $when => $nullMeasurement );
        }
        else {
            return array( $nullMeasurement );
        }
    }
    /**
     * Load and return all measurement records for the specific user ID
     *
     * @return array -- Array of measurement record for the $this->client_id
     */
    public function getMeasurements() {

        $test = (array)$this->all;

        if ( empty( $test ) ) {

            dbg("MeasurementsModel_getMeasurements() - No data present in class. Loading data. ");
            $this->loadAll();

        }

        return $this->all;
    }

    /**
     * @param string $date -- Date of the record to return.
     *
     * @return array|bool -- The measurement record for the specified date.
     */
    public function getByDate( $date = 'all' ) {

        $test = (array) $this->byDate;

        if ( empty( $test ) ) {
            dbg("MeasurementModel_getByDate() - No data in byDate sort");
            $this->loadAll( $date );
        }

        dbg("MeasurementModel_getByDate() - Loaded data: " . print_r( $this->byDate, true ) );
        dbg("MeasurementModel_getByDate() - Getting data for {$date}");

        if ( $date != 'all' ) {

            dbg("MeasurementModel_getByDate() - Returning data for {$date} only");
            return ( array_key_exists( $date, $this->byDate ) ? $this->byDate[ $date ] : $this->loadNullMeasurement( $date ) );
        }
        else {
            return (empty( $this->byDate ) ? $this->loadNullMeasurement( $date ) : $this->byDate );
        }

    }

    public function setByDate( $record, $date ) {

        $this->byDate[$date] = $record;

    }

    /**
     * Returns the client id
     *
     * @return int|null -- Return the client's ID (WP user ID)
     */
    public function getClientId() {

        return $this->client_id;
    }

    public function save( $form_key, $value, $articleID, $when ) {

        if ( $this->client_id == 0 ) {

            throw new Exception( "User is not logged in" );
        }

        dbg("Received variables: {$form_key}, {$value}, {$articleID}, {$when}");
        delete_transient("e20r_byDate_client_measurements_{$this->client_id}");
        delete_transient("e20r_all_client_measurements_{$this->client_id}");

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

            dbg("Updating existing data to the database." );

            foreach ( $existing as $key => $val ) {

                dbg("Key: {$key} => Val: {$val}");

                if ( $key != $form_key ) {

                    if ( ( $key != 'essay1' ) && ( $val === null ) ) {
                        dbg("Skipping {$key}");
                        continue;
                    }

                    dbg("Existing data from the table");
                    $data = array_merge( $data, array( $key => ( empty( $val ) ? '' : $val ) ) );
                }
                else {

                    dbg("Updating {$form_key} data in Database: " . $value );
                    $data = array_merge( $data, array( $form_key => ( empty($value) ? '' : $value ) ) );

                }

            }
            dbg("Data to update: " . print_r( $data, true));

        }
        else {
            $data = array(
                $this->fields['user_id']       => $this->client_id,
                $this->fields['article_id']    => $articleID,
                $this->fields['recorded_date'] => "{$when} 00:00:00",
                $this->fields[ $form_key ]     => esc_sql($value)
            );
        }

        dbg("Creating format array for the new/updated row of data.");
        $format = array();

        foreach( $data as $key => $val ) {
            $varFormat = $this->setFormat( $val );

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
        delte_transient("e20r_all_client_measurements_{$this->client_id}");
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

        if ( ! is_numeric( $value ) ) {
            dbg( "setFormat() - {$value} is NOT numeric" );

            if ( ctype_alpha( $value ) ) {
                dbg( "setFormat() - {$value} is a string" );
                return '%s';
            }

            if ( strtotime( $value ) ) {
                dbg( "setFormat() - {$value} is a date (treating it as a string)" );
                return '%s';
            }

            if ( is_string( $value ) ) {
                dbg( "setFormat() - {$value} is a string" );
                return '%s';
            }

            if (is_null( $value )) {
                dbg( "setFormat() - it's a NULL value");
                return '%s';
            }
        }
        else {
            dbg( "setFormat() - .{$value}. IS numeric" );

            if ( is_float( $value + 1 ) ) {
                dbg( "setFormat() - {$value} is a float" );

                return '%f';
            }

            if ( is_int( $value + 1 ) ) {
                dbg( "setFormat() - {$value} is an integer" );
                return '%d';
            }
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
            $tmp->essay1 = $record->{$this->fields['essay1']};
            $tmp->behaviorprogress = $record->{$this->fields['behaviorprogress']};

            $retArr[] = $tmp;

            $when = date( 'Y-m-d', strtotime( $tmp->recorded_date ) );
            $this->byDate[$when] = $tmp;

            unset($tmp);

        }

        return $retArr;
    }
} 