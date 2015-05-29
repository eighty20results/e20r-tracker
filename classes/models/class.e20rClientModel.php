<?php

class e20rClientModel {

    protected $id = null;
    private $program_id = null;

    protected $table;
    protected $fields;

    protected $data;

    public function __construct() {

        global $e20rTables;
        global $e20rProgram;

        try {
            $this->table = $e20rTables->getTable( 'client_info' );
            $this->fields = $e20rTables->getFields( 'client_info' );
        }
        catch ( Exception $e ) {
            dbg("e20rClientModel::load() - Error loading client_info table: " . $e->getMessage() );
        }
    }

	public function save_client_interview( $data ) {

		global $wpdb;
		// global $e20rTables;
		global $e20rTracker;

		$data['completed_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
		// $id = false;

		dbg("e20rClientModel::save_client_interview() - Saving data to {$this->table}");

		if ( ( $id = $this->recordExists( $data['user_id'], $data['program_id'], $data['page_id'] ) ) !== false ) {

			dbg('e20rTrackerModel::save_client_interview() - User/Program exists in the client info table. Editing existing record.' );
			$data['edited_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
			$data['id'] = $id;
		}

		if ( isset( $data['started_date'] ) ) {

			unset($data['started_date'] ); // $data['started_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
		}

		// Generate format array.
		$format = $e20rTracker->setFormatForRecord( $data );

		dbg("e20rClientModel::save_client_interview() - Format for the record: ");
		// dbg($format);
		dbg("e20rClientModel::save_client_interview() - The record to insert: ");
		// dbg($data);

		// $wpdb->show_errors();

		// if ( $id === false ) {

			if ( false === $wpdb->replace( $this->table, $data, $format ) ) {

				global $EZSQL_ERROR;
				dbg($EZSQL_ERROR);

				dbg( "e20rTrackerModel::save_client_interview() - Error inserting form data: " . $wpdb->print_error() );
				dbg( "e20rTrackerModel::save_client_interview() - Query: " . print_r( $wpdb->last_query, true ) );

				return false;
			}
/* 		}
		else {

			$data['id'] = $id;

			if ( false === $wpdb->update( $this->table, $data, array( 'id' => $id ), $format, array( '%d' ) ) ) {

				dbg( "e20rTrackerModel::save_client_interview() - Error updating data: " . $wpdb->print_error() );
				dbg( "e20rTrackerModel::save_client_interview() - Query: " . print_r( $wpdb->last_query, true ) );

				return false;
			}
		}
*/
		// $wpdb->hide_errors();

		dbg("e20rTrackerModel::save_client_interview() - Data saved...");
		$this->clearTransients();

		return true;
	}

	private function recordExists( $userId, $programId, $pageId ) {

		global $wpdb;
		global $e20rTables;

		$cTable = $e20rTables->getTable('client_info');

		$sql = $wpdb->prepare("
			SELECT id
			FROM $cTable
			WHERE user_id = %d AND program_id = %d AND page_id = %d
		",
			$userId,
			$programId,
			$pageId
		);

		$exists = $wpdb->get_var( $sql );

		if ( ! is_null( $exists ) ) {

			dbg("e20rTrackerModel::recordExists() - Found record with id: {$exists}");
			return (int)$exists;
		}

		return false;
	}

    public function getData( $userId, $item = null ) {

        // No item specified, returning everything we have.
        if ( is_null( $item ) ) {

            if ( !isset( $this->data->weightunits) || empty( $this->data->weightunits ) ) {

                dbg("e20rClientModel::getData() - Completed date for record not found. Reloading..");
                $this->setUser( $userId );
                $this->load();
            }

            // Return all of the data for this user.
            return $this->data;
        } else {

	        if ( ! isset( $this->data->{$item} ) ) {

		        dbg( "e20rClientModel::getData() - Requested Item ({$item}) not found. Reloading.." );
		        $this->load();
	        }

	        // Only return the specified item value.
	        return ( empty( $this->data->{$item} ) ? false : $this->data->{$item} );
        }

	    return false;
    }

    public function saveData( $clientData ) {

        global $wpdb;
        global $e20rTables;
        global $e20rTracker;

        $table = $e20rTables->getTable( 'client_info' );

        // These are the fields that won't get encrypted.
        $encData = array();
        $encData['user_id'] = $clientData['user_id'];
        $encData['program_id'] = $clientData['program_id'];
	    $encData['page_id'] = $clientData['page_id'];
        $encData['program_start'] = $clientData['program_start'];
        $encData['progress_photo_dir'] = $clientData['progress_photo_dir'];
        $encData['first_name'] = $clientData['first_name'];
        $encData['birthdate'] = $clientData['birthdate'];
        $encData['gender'] = $clientData['gender'];
        $encData['lengthunits'] = $clientData['lengthunits'];
        $encData['weightunits'] = $clientData['weightunits'];

        $exclude = array_keys( $encData );

        dbg("e20rTrackerModel::saveData() - Encrypting client data.");

        foreach( $clientData as $key => $value ) {

            if ( !in_array( $key, $exclude ) ) {

                $encData[$key] = $value;
            }
        }

        dbg("e20rClientModel::saveData() - Set the format for the fields ");
        $format = $e20rTracker->setFormatForRecord( $encData );

        dbg("e20ClientModel::saveData() - Data: ");
/*
        dbg($encData);
        dbg($format);
*/
        if ( $wpdb->replace( $table, $encData, $format ) === false ) {

	        dbg("e20rClientModel::saveData() - Unable to save the client data: " . print_r( $wpdb->last_query, true ) );
	        return false;
        }

        $this->data = $clientData;

	    $this->clearTransients();
    }

    public function load() {

	    if ( WP_DEBUG === true ) {

		    $this->clearTransients();
	    }

	    global $current_user;

	    if ( empty( $this->id ) ) {

		    $this->id = $current_user->ID;
	    }

	    $this->setUser( $this->id );

        try {
            dbg("e20rClientModel::load() - Loading clientInfo for user {$this->id}");

            if ( false === ( $this->data = get_transient( "e20r_client_info_{$this->id}" ) ) ) {

                dbg("e20rClientModel::load() - Loading client information for {$this->id} from the database");

                // Not stored yet, so grab the data from the DB and store it.
                $this->info = $this->loadData( $this->id );
                // set_transient( "e20r_client_info_{$this->id}", $this->info, 1 * HOUR_IN_SECONDS );
                set_transient( "e20r_client_info_{$this->id}", $this->info, 1 * 60 ); // TODO: Set to one hour!
            }

            if ( empty( $this->data ) ) {
                dbg("e20rClientModel::load() - No Client information in the database for {$this->id}");
	            $this->data = $this->info;
            }

            dbg("e20rClientModel::load() - Client info loaded" );

        } catch ( Exception $e ) {

            dbg( "e20rClientModel::load() - Error loading user information for {$this->id}: " . $e->getMessage() );
        }

    }

    public function setUser( $id ) {

        global $e20rProgram;

        $this->id = $id;
        $this->program_id = $e20rProgram->getProgramIdForUser( $this->id );
    }

    public function saveUnitInfo( $lengthunit, $weightunit ) {

        global $wpdb;
        global $e20rProgram;

        if ( $wpdb->update( $this->table,
            array( 'lengthunits' => $lengthunit, 'weightunits' => $weightunit ),
            array( 'user_id' => $this->id, 'program_id' => $e20rProgram->getProgramIdForUser( $this->id ) ),
                array( '%d' ) ) === false ) {

            dbg("e20rClientModel::saveUnitInfo() - Error updating unit info: " . $wpdb->print_error() );
            throw new Exception("Error updating weight/length units: " . $wpdb->print_error() );
        }

        $this->clearTransients();

        return true;
    }

	private function clearTransients() {

		dbg("e20rClientModel::clearTransients() - Resetting cache & transiets");
		delete_transient( "e20r_client_info_{$this->id}" );
	}

    /**
     * @param $who - User ID
     * @param $when - Date of captured measurement
     * @param $imageSide -- Front/Side/Back
     * @param $imageSize -- 'thumbnail', 'large', 'medium', etc.
     *
     * @return mixed - URL to image
     */
    public function getBetaUserUrl( $who, $when, $imageSide ) {

        global $e20rTables, $wpdb;

        if ( $e20rTables->isBetaClient() ) {

            dbg("e20rClientModel::getBetaUserUrl() - User with ID {$who} IS a member of the Nourish BETA group");

            switch ( $imageSide ) {

                case 'front':
                    $fid = 2;
                    break;

                case 'side':
                    $fid = 3;
                    break;

                case 'back':
                    $fid = 4;
                    break;
            }

            $sql = $wpdb->prepare("SELECT gf_detail.value as URL
                                    FROM {$wpdb->prefix}rg_lead AS gf_lead
                                      INNER JOIN {$wpdb->prefix}rg_lead_detail AS gf_detail
                                      ON ( gf_lead.id = gf_detail.lead_id
                                          AND ( gf_lead.created_by = %d )
                                          AND ( gf_lead.form_id = %d )
                                          AND ( gf_lead.date_created < %s )
                                          AND ( gf_detail.field_number = %d )
                                  )
                                  ORDER BY gf_lead.date_created DESC
                                  LIMIT 1",
                    $who,
                    GF_PHOTOFORM_ID,
                    date('Y-m-d', strtotime($when) + (24 * 60 * 60) ),
                    $fid
            );

            // dbg("e20rClientModel::getBetaUserUrl() - SQL: {$sql}");

            $imageLink = $wpdb->get_row( $sql );

            if ( empty( $imageLink ) ) {
                $imageUrl = E20R_PLUGINS_URL . '/images/no-image-uploaded.jpg';
            }
            else {
                $imageUrl = $imageLink->URL;
            }
        }
        else {

            dbg("e20rClientModel::getBetaUserUrl() - User with ID {$who} is NOT a member of the Nourish BETA group");
            $imageUrl = false;

        }

        return $imageUrl;
    }

    public function getAppointments() {

        if ( empty( $this->appointments ) ) {

            $this->load_appointments();
        }

        return $this->appointments;
    }

    private function loadData( $clientId ) {

	    global $wpdb;
	    global $post;
	    global $e20rProgram;
	    global $e20rTracker;

	    $oldId = $this->id;

	    if ( $clientId != $this->id ) {

		    dbg( "e20rClientModel::loadClientData() - WARNING: Loading data for a different client/user. Was: {$this->id}, now: {$clientId}" );
		    $this->id = $clientId;
	    }

	    $this->setUser( $this->id );

	    $key = $e20rTracker->getUserKey( $clientId );

	    if ( WP_DEBUG === true ) {

		    $this->clearTransients();
	    }

	    if ( false === ( $clientData = get_transient( "e20r_client_info_{$this->id}" ) ) ) {

		    // Init the unencrypted structure and load defaults.
		    $clientData                       = new stdClass();
		    $clientData->user_id              = $this->id;
		    $clientData->program_id           = $this->program_id;
		    $clientData->page_id              = isset( $post->ID ) ? $post->ID : CONST_NULL_ARTICLE;
		    $clientData->program_start        = date_i18n( 'Y-m-d', $e20rProgram->startdate( $clientId, $this->program_id ) );
		    $clientData->progress_photo_dir    = "e20r_pics/client_{$this->program_id}_{$this->id}";
		    $clientData->gender               = 'm';
		    $clientData->incomplete_interview = true; // Will redirect the user to the interview page.
		    $clientData->first_name           = null;
		    $clientData->birthdate            = null;
		    $clientData->lengthunits          = 'in';
		    $clientData->weightunits          = 'lbs';

		    $excluded = array_keys( (array) $clientData );

		    $sql = $wpdb->prepare( "
	                    SELECT *
	                    FROM {$this->table}
	                    WHERE program_id = %d AND
	                    user_id = %d
	                    ORDER BY program_start DESC
	                    LIMIT 1",
			    $this->program_id,
			    $this->id
		    );

		    $result = $wpdb->get_row( $sql, ARRAY_A );

		    if ( ! empty( $result ) ) {

			    foreach ( $result as $key => $val ) {

				    // Encrypted data gets decoded
				    if ( ! in_array( $key, $excluded ) ) {

					    $clientData->{$key} = $e20rTracker->decryptData( $val, $key );
				    } else {
					    // Unencrypted data is simply passed back.
					    $clientData->{$key} = $val;
				    }
			    }

			    // Clear the record from memory.
			    unset( $result );
		    }

		    if ( ! isset( $clientData->user_enc_key ) && ( ! empty( $clientData->user_enc_key ) ) ) {
			    unset( $clientData->user_enc_key );
		    }

		    if ( isset( $clientData->weight_loss ) && ( ! empty( $clientData->weight_loss ) ) ) {

			    dbg( "e20rClientModel::loadClientData() - Client interview has been completed." );
			    $clientData->incomplete_interview = false;
		    }

		    // Restore the original User ID.
		    $this->id = $oldId;
		    set_transient( "e20r_client_info_{$this->id}", $clientData, 3600 );
	    }

        return $clientData;
    }

    private function load_appointments() {

        global $current_user, $wpdb, $appointments, $e20rTracker, $e20rTables;

        $appTable = $e20rTables->getTable('appointments');

        if ( empty( $appointments ) ) {

            throw new Exception("Appointments+ Plugin is not installed.");
            return false;
        }

        $statuses = array( "completed", "removed" );

        if ( $this->id == 0 ) {

            $this->id = $current_user->ID;
        }

        $sql = $wpdb->prepare(
            "
                SELECT ID, user, start, status, created
                FROM {$appTable} AS app
                INNER JOIN {$wpdb->users} AS usr
                  ON ( app.user = usr.ID )
                WHERE user = %d AND status NOT IN ( [IN] )
                ORDER BY start ASC
            ",
            $this->id
        );

        $sql = $e20rTracker->prepare_in( $sql, $statuses, '%s' );
        // dbg("SQL for appointment list: " . print_r( $sql, true ) );
        $this->appointments = $wpdb->get_results( $sql, OBJECT);
    }

    /*
    public function getUserList( $level = '' ) {

        global $wpdb, $e20rTracker;

        if ( ! empty($level) ) {
            $this->load_levels( $level );
        }

        $levels = $this->get_level_ids();

        dbg("Levels being loaded: " . print_r( $levels, true ) );

        if ( ! empty($levels) ) {
            $sql = "
                    SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                    FROM {$wpdb->users} AS u
                      INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                        ON ( u.ID = m.user_id )
                        INNER JOIN {$wpdb->usermeta} AS um
                        ON ( u.ID = um.user_id )
                    WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
                    ORDER BY last_name ASC
            ";
        }
        else {
            $sql = "
                    SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                    FROM {$wpdb->users} AS u
                      INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                        ON ( u.ID = m.user_id )
                        INNER JOIN {$wpdb->usermeta} AS um
                        ON ( u.ID = um.user_id )
                    WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' )
                    ORDER BY last_name ASC
            ";
        }
        $sql = $e20rTracker->prepare_in( $sql, $levels );

        dbg("SQL for user list: " . print_r( $sql, true));

        $user_list = $wpdb->get_results( $sql, OBJECT );

        if (! empty( $user_list ) ) {
            return $user_list;
        }
        else {
            $data = new stdClass();
            $data->id = 0;
            $data->name = 'No users found';

            return array( $data );
        }
    }

    public function getLevelList( $onlyVisible = false ) {

        $allLevels = pmpro_getAllLevels();
        $levels = array();

        foreach ($allLevels as $level ) {

            $visible = ( $level->allow_signups == 1 ? true : false );

            if ( ( ! $onlyVisible) || ( $visible && $onlyVisible )) {
                $levels[ $level->id ] = $level->name;
            }
        }

        asort( $levels );

        // dbg("Levels fetched: " . print_r( $levels, true ) );

        return $levels;
    }
    */

    /*
private function load_levels( $name = null ) {

    global $wpdb;

    if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
        $this->raise_error( 'pmpro' );
    } else {

        dbg("Loading levels from PMPro");

        $allLevels = pmpro_getAllLevels( true );

        if ( ! empty( $name ) ) {
            dbg("e20rClientModel::load_levels() -- Name for pattern: {$name}");
            $name = str_replace( '+', '\+', $name);
            $pattern = "/{$name}/i";
            dbg("e20rClientModel::load_levels() -- Membership level pattern: {$pattern}");
        }

        foreach( $allLevels as $level ) {

            if ( preg_match($pattern, $level->name ) == 1 ) {
                $this->levels[] = $level->id;
            }
            elseif ( empty( $name ) ) {
                $this->levels[] = $level->id;
            }
        }
    }
}
*/

    /*
private function loadInfo() {

    global $wpdb;

    dbg("e20rClientModel::loadInfo() - Client ID is: {$this->id}");

    $sql = $wpdb->prepare( "
                SELECT *
                FROM {$this->table}
                WHERE user_id = %d
                ORDER BY program_start ASC
                LIMIT 1
              ", $this->id
    );

    $data = $wpdb->get_row( $sql );

    if ( empty ($data) ) {

        dbg("e20rClientmodel::loadInfo() - No client data found in DB");

        $data = new stdClass();
        $data->lengthunits = 'in';
        $data->weightunits = 'lbs';
        $data->gender = 'M';
        $data->incomplete_interview = true; // Will redirect the user to the interview page.
        $data->progress_photo_dir = "e20r_pics/client_{$this->id}";
        $data->user_id = $this->id;
    }

    if (isset($data->user_enc_key) ) {
        $this->data_enc_key = $data->user_enc_key;
        unset( $data->user_enc_key );
    }

    return $data;
}
*/

    /**
     * Configure the weight & length unit settings for the current user
     *
     * Generates an array:
     *
     * array( 'weightunits' => array(
     *                          'key' => 'lbs',
     *                          'desc' => 'pounds (lbs)',
     *                       ),
     *        'lengthunits' => array(
     *                          'key' => 'in',
     *                          'desc' => 'inches (in)',
     *                       ),
     *          'default' => 'lbs',
     *          '
     *
     *
     * @param stdClass $client_data -- The client data from the database (object)
     */
    /*
    private function setUnitType( $client_data = null ) {

        if ( empty ( $this->unit_type ) ) {
            $this->unit_type = array();
        }

        if ( $client_data != null ) {

            $this->unit_type['weightunits'] = $client_data->weightunits;
            $this->unit_type['lengthunits'] = $client_data->lengthunits;

        }
        else {

            global $wpdb;

            if ( $this->id != 0 ){
                $sql = $wpdb->prepare( "
                    SELECT weightunits, lengthunits
                    FROM {$wpdb->prefix}e20r_client_info
                    WHERE user_id = %d
                    LIMIT 1
                    ",
                    $this->id
                );

                $res = $wpdb->get_row( $sql );

                if ( ! empty( $res ) ) {
                    $this->unit_type['weightunits'] = $res->weightunits;
                    $this->unit_type['lengthunits'] = $res->lengthunits;
                }
                else {
                    $this->unit_type['weightunits'] = null;
                    $this->unit_type['lengthunits'] = null;
                }
            }

        }
    }
*/

    /*
    public function getMeasurements() {

        if ( $this->measurements == null ) {

            try {
                dbg( "load() - Loading measurements for user {$this->id}" );

                $tmp                = new e20rMeasurements( $this->id );
                $this->measurements = $tmp->getMeasurement( 'all' );

                if ( empty( $this->measurements ) ) {
                    dbg( "No measurements in the database for {$this->id}" );
                }
            } catch ( Exception $e ) {

                dbg( "Error loading measurements for {$this->id}: " . $e->getMessage() );
            }
        }


        return $this->measurements;

    }
*/
}
