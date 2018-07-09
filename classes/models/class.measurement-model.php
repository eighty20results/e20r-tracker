<?php
namespace E20R\Tracker\Models;

use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Client;
use E20R\Utilities\Utilities;

class Measurement_Model {

    private $client_id = null;
    private $programId = null;
    
    public $all = array();
    public $byDate = array();
	
	/**
	 * @var null|string $table
	 */
    private $table = null;
    private $fields = array();

    private static $instance = null;

    public function __construct( $user_id = null ) {
    	
        $Tables = Tables::getInstance();

        global $current_user;

	    if ( is_user_logged_in() ) {

		    if ( ( $user_id == null ) && ( $current_user->ID != 0 ) ) {
			    $user_id = $current_user->ID;
		    }

		    $this->client_id = $user_id;
            $this->programId = 0;
		    // $this->programId = $Program->getProgramIdForUser( $this->client_id );

		    Utilities::get_instance()->log( "e20rMeasurementModel::construct() - For user_id: {$user_id}" );
	    }
	    
	    try {
		    $this->table  = $Tables->getTable( 'measurements' );
	    } catch ( \Exception $e ) {
	    	Utilities::get_instance()->log("Error configuring measurement table: " . $e->getMessage() );
	    	return false;
	    }
	
	    $this->fields = $Tables->getFields( 'measurements' );
	    
	    return $this;
    }

	/**
	 * @return Measurement_Model
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/**
	 * @param $articleId
	 * @param $programId
	 * @param $user_id
	 * @param $date
	 *
	 * @return array
	 * @throws \Exception
	 */
    public function checkCompletion( $articleId, $programId, $user_id, $date ) {

        global $wpdb;

        $nextDay = date( 'Y-m-d', strtotime( $date . '+ 1 day') );

        Utilities::get_instance()->log( "Measurement_Model::checkCompletion() - Day: {$date} -> next day: {$nextDay}");

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
                WHERE {$this->fields['user_id']} = %d AND " . ( $articleId != 0 ?
                      "{$this->fields['article_id']} = " . esc_sql($articleId) . " AND " : '' ) .
                      "{$this->fields['program_id']} = %d AND
                      ( ( {$this->fields['recorded_date']} >=  %s) AND ( {$this->fields['recorded_date']} <= %s ) )
                ",
	            $user_id,
                $programId,
                $date,
                $nextDay
        );

        $results = $wpdb->get_results( $sql );

        Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Returned " . count( $results ) . " records");
        
        $completionStatus = false;
        $girth_compl = false;
        $weight_compl = false;
        $completionPct = 0;

        if ( is_wp_error( $results ) ) {

            Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Error searching database: " . $wpdb->print_error() );
            throw new \Exception( "Error searching database: " . $wpdb->print_error() );
        }

        if ( ! empty( $results ) ) {

            foreach( $results as $res ) {

	            /*
                Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Returned Data: ");
                Utilities::get_instance()->log($res);
				*/
                if ( $res->completed >= TOTAL_GIRTH_MEASUREMENTS ) {

                    $girth_compl = true;
                    Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Have all girth measurements.");
                }

                $completionPct = ( $res->completed / TOTAL_GIRTH_MEASUREMENTS );

                if ( ! empty( $res->weight ) ) {

                    Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Recorded weight.");
                    $weight_compl = true;
                }

                if ( !empty( $res->girth ) && ( $girth_compl ) ) {

                    Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Updating total Girth for user {$user_id} on {$date}");
                    $this->setTotalGirth( $res->id );
                }
            }

            $completionStatus = ( $girth_compl && $weight_compl );
        }

        Utilities::get_instance()->log("Measurement_Model::checkCompletion() - Completion Status: {$completionStatus} and percentage: {$completionPct}");
        return array( 'status' => $completionStatus, 'percent' => ( $completionPct * 100));
    }

    /**
     * Load and return all measurement records for the specific user ID
     *
     * @return array -- Array of measurement record for the $this->client_id
     */
    public function getMeasurements() {

	    if ( WP_DEBUG == true ) {

		    Utilities::get_instance()->log("Measurement_Model::getMeasurements() - DEBUG is enabled. Clear transient data");
		    $this->setFreshClientData();
	    }

        try {
            $this->loadAll();

        }
        catch( \Exception $e ) {
            Utilities::get_instance()->log("Measurement_Model::getMeasurements() - Error loading all data: " . $e->getMessage() );
            return array();
        }

        return $this->all;
    }

    /**
     * @param string $date -- Date of the record to return.
     *
     * @return array|bool -- The measurement record for the specified date.
     */
    public function getByDate( $date = 'all' ) {

	    if ( WP_DEBUG == true ) {

		    Utilities::get_instance()->log("Measurement_Model::getByDate() - DEBUG is enabled. Clear transient data");
		    $this->setFreshClientData();
	    }

        Utilities::get_instance()->log("Measurement_Model::getByDate() - Fetching data for {$date}");
	    try {
		    $this->loadByDate( $date );
	    } catch( \Exception $exception ) {
	    	Utilities::get_instance()->log("Unable to load data based on a date ({$date}). Error: " . $exception->getMessage() );
		    $this->loadNullMeasurement( $date );
	    }

        if ( $date != 'all' ) {

            Utilities::get_instance()->log("Measurement_Model::getByDate() - Returning data for {$date} only");
            return ( array_key_exists( $date, $this->byDate ) ? $this->byDate[ $date ] : $this->loadNullMeasurement( $date ) );
        }
        else {
	        Utilities::get_instance()->log("Measurement_Model::getByDate() - Returning NULL data for {$date}");
            return (empty( $this->byDate ) ? $this->loadNullMeasurement( $date ) : $this->byDate );
        }

    }

    public function setByDate( $record, $date ) {

        $this->byDate[$date] = $record;
    }

    public function setUser( $userId ) {

        $Program = Program::getInstance();
        $Tables = Tables::getInstance();

        $this->client_id = $userId;
        $this->programId = $Program->getProgramIdForUser( $this->client_id );

        // Update tables (account for possible beta group data).
        $Tables->init( $this->client_id );
	    
        try {
	        $this->table = $Tables->getTable( 'measurements', true );
        } catch( \Exception $exception ) {
        	Utilities::get_instance()->log("Unable to locate the Measurements table in the database. Error: " . $exception->getMessage() );
        	$this->fields = null;
        	return false;
        }
        $this->fields = $Tables->getFields( 'measurements', true );
	    
        return $this->client_id;
    }

    /**
     * Returns the client id
     *
     * @return int|null -- Return the client's ID (WP user ID)
     */
    public function getClientId() {

        return $this->client_id;
    }

    private function record2Array( $record ) {
        Utilities::get_instance()->log("Measurement_Model::record2Array() - Converting from stdClass: " . print_r( $record, true ) );
        return json_decode(json_encode($record), true);
        
    }
	
	/**
	 * @param $rec
	 * @param $user_id
	 * @param $date
	 *
	 * @return bool
	 * @throws \Exception
	 */
    public function saveRecord( $rec, $user_id, $date ) {

        global $wpdb;
        $Program = Program::getInstance();
        $Tracker = Tracker::getInstance();

        if (! is_array( $rec ) ) {

            Utilities::get_instance()->log("Measurement_Model::saveRecord() - Received data is in object format. Converting... ");
            $rec = $this->record2Array( $rec );
            Utilities::get_instance()->log("Measurement_Model::saveRecord() - Converted from stdClass: " . print_r( $rec, true ) );
        }

        $record = $rec;
        unset($rec); // Free memory

        $format = $Tracker->setFormatForRecord( $record );
        $programId = $Program->getProgramIdForUser( $user_id );

        // $insert_formats = implode( ', ', array_values( $format ) );
        // $insert_fields = implode( ', ', array_keys( $record ) );
	    $existing = $this->hasExistingData( $date, $programId, $user_id );
	    
        if ( !empty( $existing ) ) {

            Utilities::get_instance()->log("Measurement_Model::saveRecord() - Found existing record for same user/date/article");
            $record[$this->fields['id']] = $existing->{$this->fields['id']};

            // $insert_sql = "INSERT INTO {$this->table} ( {$insert_fields} ) VALUES ( {$insert_formats} );";
            // $update_sql = "UPDATE {$this->table} SET () WHERE ";

        }

        Utilities::get_instance()->log("Measurement_Model::saveRecord() - Updating record");
        if ( $wpdb->replace( $this->table, $record, $format ) === false ) {

            Utilities::get_instance()->log("Measurement_Model::saveRecord() - Unable to save database record: " . $wpdb->print_error() );
            throw new \Exception("Unable to save record to database: " . $wpdb->print_error() );
        }

        return true;
    }

    public function setFreshClientData() {

        $Client = Client::getInstance();

        // Make sure new data is accurately reflected to user(s).
        delete_transient("e20r_byDate_client_measurements_{$this->client_id}");
        delete_transient("e20r_all_client_measurements_{$this->client_id}");
	    delete_transient("e20r_datelist_client_measurements_{$this->client_id}");

        $Client->scriptsLoaded = false;

    }
	
	/**
	 * @param      $when
	 * @param      $programId
	 * @param null $userId
	 *
	 * @return array|bool|null|object
	 * @throws \Exception
	 */
    private function hasExistingData( $when, $programId, $userId = null ) {

        Utilities::get_instance()->log("Measurement_Model::hasExistingData() - Checking data for {$userId}/{$this->client_id} on {$when}");

        global $wpdb;

        if ( is_null( $this->client_id ) ) {
            $this->client_id = $userId;
        }

        $nextDay = date( 'Y-m-d', strtotime( $when . '+ 1 day') );

        $sql = $wpdb->prepare(
                        "SELECT *
                                    FROM {$this->table}
                                    WHERE ( {$this->fields['user_id']} = %d ) AND
                                          ( ( {$this->fields['recorded_date']} >= %s ) AND
                                            ( {$this->fields['recorded_date']} < %s ) ) AND
                                          ( {$this->fields['program_id']} = %d )",
                        $this->client_id,
                        $when,
                        $nextDay,
                        $programId
                    );

//        Utilities::get_instance()->log("Measurement_Model::hasExistingData() - SQL: ");
//        Utilities::get_instance()->log($sql);

        $existing = $wpdb->get_row( $sql );

        if ( is_wp_error( $existing ) ) {
            Utilities::get_instance()->log("Measurement_Model::hasExistingData - Error searching database: " . $wpdb->print_error() );
            throw new \Exception( "Error searching database: " . $wpdb->print_error() );
        }

        Utilities::get_instance()->log("Measurement_Model::hasExistingData - We found " . count($existing) . " records in the database for {$this->client_id} on {$when} 00:00:00");

        if ( empty( $existing ) ) {
            return false;
        }

        return $existing;
    }

    /**
     * Saves the supplied measurement entity to the database
     *
     * @param $form_key
     * @param $value
     * @param $articleID
     * @param $when
     * @param $user_id
     *
     * @return bool
     * @throws \Exception
     */
    public function saveField( $form_key, $value, $articleID, $programId, $when, $user_id ) {
    	
        $Tracker = Tracker::getInstance();

        if ( $this->client_id == 0 ) {

            throw new \Exception( "User is not logged in" );
        }

        if ( $this->client_id !== $user_id ) {

            $this->client_id = $user_id;
        }

        Utilities::get_instance()->log("Measurement_Model::saveField - Received variables: {$form_key}, {$value}, {$articleID}, {$programId}, {$when}");

        global $wpdb;

        if ( ( $existing = $this->hasExistingData( $when, $programId, $user_id ) ) !== false ) {

            Utilities::get_instance()->log("Measurement_Model::saveField - Assuming we have to include existing data when updating the database");

            $data = array(
                $this->fields['id']            => $existing->{$this->fields['id']},
                $this->fields['user_id']       => $this->client_id,
                $this->fields['program_id']    => ( empty($existing->{$this->fields['program_id']}) ? esc_sql($programId) : $existing->{$this->fields['program_id']}),
                $this->fields['article_id']    => ( empty($existing->{$this->fields['article_id']}) ? esc_sql( is_null( $articleID ) ? -1 : $articleID ) : $existing->{$this->fields['article_id']}),
                $this->fields['recorded_date'] => "{$when} 00:00:00",
            );

            Utilities::get_instance()->log("Measurement_Model::saveField - Updating existing data to the database." );

            foreach ( $existing as $key => $val ) {

                Utilities::get_instance()->log("Measurement_Model::saveField() - Key: {$key} => Val: {$val}");

                if ( $key != $this->fields[ $form_key ]) {

                    if ( ( $key != 'essay1' ) && ( $val === null ) ) {
                        // Utilities::get_instance()->log("Skipping {$key}");
                        continue;
                    }

                    $data = array_merge( $data, array( $key => ( empty( $val ) ? '' : $val ) ) );
                }
                else {

                    Utilities::get_instance()->log("Measurement_Model::saveField - Updating {$this->fields[$form_key]} entry in DB: " . $value );
                    $data = array_merge( $data, array( $this->fields[ $form_key ] => ( !empty($value) ? $value : '' ) ) );

                }

            }
            // Utilities::get_instance()->log("Data to update: " . print_r( $data, true));

        }
        else {
            $data = array(
                $this->fields['user_id']       => $this->client_id,
                $this->fields['article_id']    => esc_sql( is_null( $articleID ) ? -1 : $articleID ),
                $this->fields['recorded_date'] => "{$when} 00:00:00",
                $this->fields['program_id']    => esc_sql($programId),
                $this->fields[ $form_key ]     => esc_sql($value)
            );
        }

        // Define/prepare the format for the supplied data
        $format = $Tracker->setFormatForRecord( $data );
        
        if ( $wpdb->replace( $this->table, $data, $format ) === false ) {

            Utilities::get_instance()->log("Measurement_Model::saveField - Error updating database: " . $wpdb->print_error() );
            throw new \Exception( "Error updating database: " . $wpdb->print_error() );
        }

        // Clear transients so the DB record gets loaded on next refresh
        $this->setFreshClientData();

        return true;
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

	    // Utilities::get_instance()->log("Measurement_Model::remap_fields() - Loading for fields:");
	    // Utilities::get_instance()->log($this->fields);

        foreach ( $data as $record ) {

            $tmp = new \stdClass();
            $tmp->id = $record->{$this->fields['id']};
            $tmp->user_id = $record->{$this->fields['user_id']};
            $tmp->article_id = $record->{$this->fields['article_id']};
            $tmp->program_id = $record->{$this->fields['program_id']};
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
            $tmp->front_image = $record->{$this->fields['front_image']};
            $tmp->side_image = $record->{$this->fields['side_image']};
            $tmp->back_image = $record->{$this->fields['back_image']};

            $retArr[] = $tmp;

            $when = date( 'Y-m-d', strtotime( $tmp->recorded_date ) );
            $this->byDate[$when] = $tmp;

            unset($tmp);
        }

        return $retArr;
    }
	
	/**
	 * @param $when
	 *
	 * @throws \Exception
	 */
    private function loadByDate( $when ) {

	    $Tracker = Tracker::getInstance();
	    global $e20rMeasurementDate;

	    if ( false === ( $this->all = get_transient( "e20r_datelist_client_measurements_{$this->client_id}" ) ) ) {

		    if ( ( ! in_array( $when, array( 'current', 'last_week', 'next' ) ) ) &&
		         ( strtotime( $when ) !== false ) ) {

			    Utilities::get_instance()->log( "Measurement_Model::loadByDate() - Specified an actual date value ({$when})" );
			    $date = $when;
		    }
		    elseif ( $when == 'all' ) {

			    Utilities::get_instance()->log( "Measurement_Model::loadByDate() - Specified all" );
			    $date = null;
		    }
		    else {

			    Utilities::get_instance()->log( "e20rMeasurements::loadByDate() - Specified an relative date value ({$when})" );

			    $mDates = $Tracker->datesForMeasurements( $e20rMeasurementDate );
			    $date   = $mDates[ $when ];
		    }

		    Utilities::get_instance()->log( "MeasurementModel::loadByDate() - Loading fresh copy if byDate version of measurements" );

		    global $wpdb;

		    $sql = $wpdb->prepare(
			    "
	              SELECT *
	                FROM {$this->table}
	                WHERE {$this->fields['user_id']} = %d AND
	                  {$this->fields['program_id']} = %d AND
	                  {$this->fields['recorded_date']} LIKE %s
	                ORDER BY {$this->fields['recorded_date']} ASC
	            ",
			    $this->client_id,
			    $this->programId,
			    $date . ' 00:00:00' );

		    // Utilities::get_instance()->log("MeasurementModel::loadByDate() - SQL: {$sql}" );

		    $results = $wpdb->get_results( $sql );

		    if ( is_wp_error( $results ) ) {

			    Utilities::get_instance()->log( "MeasurementModel::loadByDate() - Error loading from database: " . $wpdb->print_error() );
			    throw new \Exception( "Error loading by date: " . $wpdb->print_error() );
		    }

		    Utilities::get_instance()->log( "MeasurementModel::loadByDate() - loaded " . count( $results ) . " records" );

		    if (empty($results)) {
			    $ts = strtotime($when);
			    $this->byDate[ date( 'Y-m-d', $ts) ] = $this->loadNullMeasurement($when);
		    }

		    foreach ( $results as $rec ) {
			    $ts                                   = strtotime( $rec->{$this->fields['recorded_date']} );
			    $this->byDate[ date( 'Y-m-d', $ts ) ] = $rec;
		    }

		    set_transient( "e20r_datelist_client_measurements_{$this->client_id}", $this->byDate, 120 );
        }

    }

    /**
     * Loads all recorded measurements for the user from the database
     *
     * @throws \Exception
     */
    private function loadAll() {


        if ( false === ( $this->all = get_transient( "e20r_all_client_measurements_{$this->client_id}" ) ) ) {

            Utilities::get_instance()->log("Measurement_Model::loadAll() - Loading ALL client measurements for user_id {$this->client_id} from the database");

            // Not stored yet, so grab the data from the DB and store it.
            global $wpdb;

            if ( $this->programId === null  ) {

	            Utilities::get_instance()->log("Measurement_Model::loadAll() - Not using Program ID to locate data: {$this->table}");
                $sql = $wpdb->prepare(
                    "
                      SELECT *
                        FROM {$this->table}
                        WHERE {$this->fields['user_id']} = %d
                        ORDER BY {$this->fields['recorded_date']} ORDER BY {$this->fields['recorded_date']} ASC
                    ",
                    $this->client_id
                );
            }
            else {

	            Utilities::get_instance()->log("Measurement_Model::loadAll() - Using Program ID ({$this->programId}) to identify data: {$this->table}");
                $sql = $wpdb->prepare(
                    "
                      SELECT *
                        FROM {$this->table}
                        WHERE {$this->fields['user_id']} = %d AND
                              {$this->fields['program_id']} = %d
                        ORDER BY {$this->fields['recorded_date']} ASC
                    ",
                    $this->client_id,
                    $this->programId
                );
            }

            Utilities::get_instance()->log("Measurement_Model::loadAll() - SQL: " . $sql );

            $results = $wpdb->get_results( $sql );

            Utilities::get_instance()->log("Measurement_Model::loadAll() - loaded " . count($results) . " records");

	        if ( is_wp_error( $results ) ) {

		        Utilities::get_instance()->log("Measurement_Model::loadAll() - Error loading from database: " . $wpdb->print_error() );
		        throw new \Exception( "Error loading from database: " . $wpdb->print_error() );
	        }

            if ( ! empty( $results ) ) {

                $this->all = $this->remap_fields( $results );
            }
            else {
                throw new \Exception("Warning: No data found for user_id {$this->client_id}");
            }

            set_transient( "e20r_all_client_measurements_{$this->client_id}", $this->all, 600 );
        }
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

	    if ( ! empty( $row ) ) {
		    Utilities::get_instance()->log( "Measurement_Model::setTotalGirth() - Updating the total GIRTH value in the database" );
		    $wpdb->update(
			    $this->table,
			    array( $this->fields['girth'] => ( $row->neck + $row->shoulder + $row->chest + $row->arm + $row->waist + $row->hip + $row->thigh + $row->calf ) ),
			    array( $this->fields['id'] => $recordId ), array( '%f', ) );

	    } else {
		    Utilities::get_instance()->log( "Measurement_Model::setTotalGirth() - This doesn't really make sense.. We've just inserted this record and now it's not here?!?" );
	    }

    }

    private function loadNullMeasurement( $when ) {

        Utilities::get_instance()->log("MeasurementsModel_loadNullMeasurement() - Loading empty measurement info");

        $Tables = Tables::getInstance();

        $fields = $Tables->getFields('measurements');

        // Utilities::get_instance()->log("MeasurementsModel_loadNullMeasurement() - Fields: " . print_r($fields, true));

        $nullMeasurement = new \stdClass();

        foreach( $fields as $field => $val ) {
            $nullMeasurement->{$field} = 0;
        }

        Utilities::get_instance()->log("MeasurementsModel_loadNullMeasurement() - Returning empty record for {$when}" );

        if ( $when != 'all' ) {

            return array( $when => $nullMeasurement );
        }
        else {
            Utilities::get_instance()->log("MeasurementsModel_loadNullMeasurement() - Returning empty record for ALL!" );
            return array( $nullMeasurement );
        }
    }

} 