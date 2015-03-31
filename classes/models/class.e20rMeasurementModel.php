<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 11/1/14
 * Time: 5:38 PM
 */

class e20rMeasurementModel {

    private $client_id = null;
    private $programId = null;

    private $measured_items = array();

    public $all = array();
    public $byDate = array();

    private $table = array();
    private $fields = array();

    public function e20rMeasurementModel( $user_id = null ) {

        global $wpdb;
        global $e20rTables;
        global $e20rProgram;

        global $current_user;

	    if ( is_user_logged_in() ) {

		    if ( ( $user_id == null ) && ( $current_user->ID != 0 ) ) {
			    $user_id = $current_user->ID;
		    }

		    $this->client_id = $user_id;
		    $this->programId = $e20rProgram->getProgramIdForUser( $this->client_id );

		    dbg( "e20rMeasurementModel::construct() - For user_id: {$user_id}" );
	    }

        $this->table = $e20rTables->getTable( 'measurements' );
        $this->fields = $e20rTables->getFields( 'measurements' );
    }

    public function checkCompletion( $articleId, $programId, $user_id, $date ) {

        global $wpdb;

        $nextDay = date( 'Y-m-d', strtotime( $date . '+ 1 day') );

        dbg( "e20rMeasurementModel::checkCompletion() - Day: {$date} -> next day: {$nextDay}");

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

	    /*
        dbg("e20rMeasurementModel::checkCompletion() - SQL: ");
        dbg($sql);
		*/
        $results = $wpdb->get_results( $sql );

        dbg("e20rMeasurementModel::checkCompletion() - Returned " . count( $results ) . " records");
        $completionStatus = false;
        $girth_compl = false;
        $weight_compl = false;
        $completionPct = 0;

        if ( is_wp_error( $results ) ) {

            dbg("e20rMeasurementModel::checkCompletion() - Error searching database: " . $wpdb->print_error() );
            throw new Exception( "Error searching database: " . $wpdb->print_error() );
            return false;
        }

        if ( ! empty( $results ) ) {

            foreach( $results as $res ) {

	            /*
                dbg("e20rMeasurementModel::checkCompletion() - Returned Data: ");
                dbg($res);
				*/
                if ( $res->completed >= TOTAL_GIRTH_MEASUREMENTS ) {

                    $girth_compl = true;
                    dbg("e20rMeasurementModel::checkCompletion() - Have all girth measurements.");
                }

                $completionPct = ( $res->completed / TOTAL_GIRTH_MEASUREMENTS );

                if ( ! empty( $res->weight ) ) {

                    dbg("e20rMeasurementModel::checkCompletion() - Recorded weight.");
                    $weight_compl = true;
                }

                if ( !empty( $res->girth ) && ( $girth_compl ) ) {

                    dbg("e20rMeasurementModel::checkCompletion() - Updating total Girth for user {$user_id} on {$date}");
                    $this->setTotalGirth( $res->id );
                }
            }

            $completionStatus = ( $girth_compl && $weight_compl );
        }

        dbg("e20rMeasurementModel::checkCompletion() - Completion Status: {$completionStatus} and percentage: {$completionPct}");
        return array( 'status' => $completionStatus, 'percent' => ( $completionPct * 100));
    }

    /**
     * Load and return all measurement records for the specific user ID
     *
     * @return array -- Array of measurement record for the $this->client_id
     */
    public function getMeasurements() {

	    if ( WP_DEBUG == true ) {

		    dbg("e20rMeasurementModel::getMeasurements() - DEBUG is enabled. Clear transient data");
		    $this->setFreshClientData();
	    }

        try {
            $this->loadAll();

        }
        catch( Exception $e ) {
            dbg("e20rMeasurementModel::getMeasurements() - Error loading all data: " . $e->getMessage() );
            return false;
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

		    dbg("e20rMeasurementModel::getByDate() - DEBUG is enabled. Clear transient data");
		    $this->setFreshClientData();
	    }

        dbg("e20rMeasurementModel::getByDate() - Fetching data for {$date}");
        $this->loadByDate( $date );

        if ( $date != 'all' ) {

            dbg("e20rMeasurementModel::getByDate() - Returning data for {$date} only");
            return ( array_key_exists( $date, $this->byDate ) ? $this->byDate[ $date ] : $this->loadNullMeasurement( $date ) );
        }
        else {
            return (empty( $this->byDate ) ? $this->loadNullMeasurement( $date ) : $this->byDate );
        }

    }

    public function setByDate( $record, $date ) {

        $this->byDate[$date] = $record;
    }

    public function setUser( $userId ) {

        global $e20rProgram;
        global $e20rTables;

        $this->client_id = $userId;
        $this->programId = $e20rProgram->getProgramIdForUser( $this->client_id );

        // Update tables (account for possible beta group data).
        $e20rTables->init( $this->client_id);

        $this->table = $e20rTables->getTable( 'measurements', true );
        $this->fields = $e20rTables->getFields( 'measurements', true );
/*
        try {
            $this->loadAll();
        }
        catch ( Exception $e ) {

            dbg("e20rMeasurementModel::setUser() - Error loading all data for {$this->client_id}: " . $e->getMessage() );
            return;
        }
*/
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
        dbg("e20rMeasurementModel::record2Array() - Converting from stdClass: " . print_r( $record, true ) );
        return json_decode(json_encode($record), true);
    }

    public function saveRecord( $rec, $user_id, $date ) {

        global $wpdb;
        global $e20rProgram;
        global $e20rTracker;
        global $e20rClient;

        if (! is_array( $rec ) ) {

            dbg("e20rMeasurementModel::saveRecord() - Received data is in object format. Converting... ");
            $rec = $this->record2Array( $rec );
            dbg("e20rMeasurementModel::saveRecord() - Converted from stdClass: " . print_r( $rec, true ) );
        }

        $record = $rec;
        unset($rec); // Free memory

        $format = $e20rTracker->setFormatForRecord( $record );
        $programId = $e20rProgram->getProgramIdForUser( $user_id );

        // $insert_formats = implode( ', ', array_values( $format ) );
        // $insert_fields = implode( ', ', array_keys( $record ) );

        if ( $existing = $this->hasExistingData( $date, $programId, $user_id ) ) {

            dbg("e20rMeasurementModel::saveRecord() - Found existing record for same user/date/article");
            $record[$this->fields['id']] = $existing->{$this->fields['id']};

            // $insert_sql = "INSERT INTO {$this->table} ( {$insert_fields} ) VALUES ( {$insert_formats} );";
            // $update_sql = "UPDATE {$this->table} SET () WHERE ";

        }

        dbg("e20rMeasurementModel::saveRecord() - Updating record");
        if ( $wpdb->replace( $this->table, $record, $format ) === false ) {

            dbg("e20rMeasurementModel::saveRecord() - Unable to save database record: " . $wpdb->print_error() );
            throw new Exception("Unable to save record to database: " . $wpdb->print_error() );
            return false;
        }

        return true;
    }

    public function setFreshClientData() {

        global $e20rClient;

        // Make sure new data is accurately reflected to user(s).
        delete_transient("e20r_byDate_client_measurements_{$this->client_id}");
        delete_transient("e20r_all_client_measurements_{$this->client_id}");
	    delete_transient("e20r_datelist_client_measurements_{$this->client_id}");

        $e20rClient->scriptsLoaded = false;

    }

    private function hasExistingData( $when, $programId, $userId = null ) {

        dbg("e20rMeasurementModel::hasExistingData() - Checking data for {$userId}/{$this->client_id} on {$when}");

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

//        dbg("e20rMeasurementModel::hasExistingData() - SQL: ");
//        dbg($sql);

        $existing = $wpdb->get_row( $sql );

        if ( is_wp_error( $existing ) ) {
            dbg("e20rMeasurementModel::hasExistingData - Error searching database: " . $wpdb->print_error() );
            throw new Exception( "Error searching database: " . $wpdb->print_error() );
            return false;
        }

        dbg("e20rMeasurementModel::hasExistingData - We found " . count($existing) . " records in the database for {$this->client_id} on {$when} 00:00:00");

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
     * @return bool|void
     * @throws Exception
     */
    public function saveField( $form_key, $value, $articleID, $programId, $when, $user_id ) {

        global $e20rClient;
        global $e20rTracker;

        if ( $this->client_id == 0 ) {

            throw new Exception( "User is not logged in" );
            return;
        }

        if ( $this->client_id !== $user_id ) {

            $this->client_id = $user_id;
        }

        dbg("e20rMeasurementModel::saveField - Received variables: {$form_key}, {$value}, {$articleID}, {$programId}, {$when}");

        global $wpdb;

        $varFormat = false;

        if ( ( $existing = $this->hasExistingData( $when, $programId, $user_id ) ) !== false ) {

            dbg("e20rMeasurementModel::saveField - Assuming we have to include existing data when updating the database");

            $data = array(
                $this->fields['id']            => $existing->{$this->fields['id']},
                $this->fields['user_id']       => $this->client_id,
                $this->fields['program_id']    => ( empty($existing->{$this->fields['program_id']}) ? esc_sql($programId) : $existing->{$this->fields['program_id']}),
                $this->fields['article_id']    => ( empty($existing->{$this->fields['article_id']}) ? esc_sql( is_null( $articleID ) ? -1 : $articleID ) : $existing->{$this->fields['article_id']}),
                $this->fields['recorded_date'] => "{$when} 00:00:00",
            );

            dbg("e20rMeasurementModel::saveField - Updating existing data to the database." );

            foreach ( $existing as $key => $val ) {

                dbg("e20rMeasurementModel::saveField() - Key: {$key} => Val: {$val}");

                if ( $key != $form_key ) {

                    if ( ( $key != 'essay1' ) && ( $val === null ) ) {
                        // dbg("Skipping {$key}");
                        continue;
                    }

                    $data = array_merge( $data, array( $key => ( empty( $val ) ? '' : $val ) ) );
                }
                else {

                    dbg("e20rMeasurementModel::saveField - Updating {$form_key} data in Database: " . $value );
                    $data = array_merge( $data, array( $form_key => ( empty($value) ? '' : $value ) ) );

                }

            }
            // dbg("Data to update: " . print_r( $data, true));

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
        $format = $e20rTracker->setFormatForRecord( $data );

//        dbg("e20rMeasurementModel::saveField - Data to 'replace': " . print_r( $data, true ) );
//        dbg("e20rMeasurementModel::saveField - formats for Data to 'replace': " . print_r( $format, true ) );

        if ( $wpdb->replace( $this->table, $data, $format ) === false ) {

            dbg("e20rMeasurementModel::saveField - Error updating database: " . $wpdb->print_error() );
            throw new Exception( "Error updating database: " . $wpdb->print_error() );
            return false;

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

	    // dbg("e20rMeasurementModel::remap_fields() - Loading for fields:");
	    // dbg($this->fields);

        foreach ( $data as $record ) {

            $tmp = new stdClass();
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

    private function loadByDate( $when ) {

	    global $e20rTracker;
	    global $e20rMeasurementDate;

	    if ( false === ( $this->all = get_transient( "e20r_datelist_client_measurements_{$this->client_id}" ) ) ) {

		    if ( ( ! in_array( $when, array( 'current', 'last_week', 'next' ) ) ) &&
		         ( strtotime( $when ) !== false ) ) {

			    dbg( "e20rMeasurementModel::loadByDate() - Specified an actual date value ({$when})" );
			    $date = $when;
		    }
		    elseif ( $when == 'all' ) {

			    dbg( "e20rMeasurementModel::loadByDate() - Specified all" );
			    $date = null;
		    }
		    else {

			    dbg( "e20rMeasurements::loadByDate() - Specified an relative date value ({$when})" );

			    $mDates = $e20rTracker->datesForMeasurements( $e20rMeasurementDate );
			    $date   = $mDates[ $when ];
		    }

		    dbg( "MeasurementModel::loadByDate() - Loading fresh copy if byDate version of measurements" );

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

		    // dbg("MeasurementModel::loadByDate() - SQL: {$sql}" );

		    $results = $wpdb->get_results( $sql );

		    if ( is_wp_error( $results ) ) {

			    dbg( "MeasurementModel::loadByDate() - Error loading from database: " . $wpdb->print_error() );
			    throw new Exception( "Error loading by date: " . $wpdb->print_error() );

			    return false;
		    }

		    dbg( "MeasurementModel::loadByDate() - loaded " . count( $results ) . " records" );

		    foreach ( $results as $rec ) {
			    $ts                                   = strtotime( $rec->{$this->fields['recorded_date']} );
			    $this->byDate[ date( 'Y-m-d', $ts ) ] = $rec;
		    }

		    set_transient( "e20r_datelist_client_measurements_{$this->client_id}", $this->byDate, 120 );
        }

    }

    /**
     * Loads all recorded measurements for the user from the database
     */
    private function loadAll() {


        if ( false === ( $this->all = get_transient( "e20r_all_client_measurements_{$this->client_id}" ) ) ) {

            dbg("e20rMeasurementModel::loadAll() - Loading ALL client measurements for user_id {$this->client_id} from the database");

            // Not stored yet, so grab the data from the DB and store it.
            global $wpdb;

            if ( $this->programId === null  ) {

	            dbg("e20rMeasurementModel::loadAll() - Not using Program ID to locate data: {$this->table}");
                $sql = $wpdb->prepare(
                    "
                      SELECT *
                        FROM {$this->table}
                        WHERE {$this->fields['user_id']} = %d
                        ORDER BY {$this->fields['recorded_date']} ASC
                    ",
                    $this->client_id
                );
            }
            else {

	            dbg("e20rMeasurementModel::loadAll() - Using Program ID to identify data: {$this->table}");
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

            dbg("e20rMeasurementModel::loadAll() - SQL: " . $sql );

            $results = $wpdb->get_results( $sql );

            dbg("e20rMeasurementModel::loadAll() - loaded " . count($results) . " records");

	        if ( is_wp_error( $results ) ) {

		        dbg("e20rMeasurementModel::loadAll() - Error loading from database: " . $wpdb->print_error() );
		        throw new Exception( "Error loading from database: " . $wpdb->print_error() );
		        return false;
	        }

            if ( ! empty( $results ) ) {

                $this->all = $this->remap_fields( $results );
            }
            else {
                throw new Exception("Warning: No data found for user_id {$this->client_id}");
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
		    dbg( "e20rMeasurementModel::setTotalGirth() - Updating the total GIRTH value in the database" );
		    $wpdb->update(
			    $this->table,
			    array( $this->fields['girth'] => ( $row->neck + $row->shoulder + $row->chest + $row->arm + $row->waist + $row->hip + $row->thigh + $row->calf ) ),
			    array( $this->fields['id'] => $recordId ), array( '%f', ) );

	    } else {
		    dbg( "e20rMeasurementModel::setTotalGirth() - This doesn't really make sense.. We've just inserted this record and now it's not here?!?" );
	    }

    }

    private function loadNullMeasurement( $when ) {

        dbg("MeasurementsModel_loadNullMeasurement() - Loading empty measurement info");

        global $e20rTables;

        $fields = $e20rTables->getFields('measurements');

        // dbg("MeasurementsModel_loadNullMeasurement() - Fields: " . print_r($fields, true));

        $nullMeasurement = new stdClass();

        foreach( $fields as $field => $val ) {
            $nullMeasurement->{$field} = null;
        }

        dbg("MeasurementsModel_loadNullMeasurement() - Returning empty record for {$when}" );

        if ( $when != 'all' ) {

            return array( $when => $nullMeasurement );
        }
        else {
            dbg("MeasurementsModel_loadNullMeasurement() - Returning empty record for ALL!" );
            return array( $nullMeasurement );
        }
    }

} 