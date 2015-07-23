<?php

class e20rClientModel {

    // protected $id = null;
    // private $program_id = null;

    protected $table;
    protected $fields;

    protected $data;

    public function __construct() {

        global $e20rTables;
        global $e20rProgram;
        global $currentClient;

        try {
            $this->table = $e20rTables->getTable( 'client_info' );
            $this->fields = $e20rTables->getFields( 'client_info' );
        }
        catch ( Exception $e ) {
            dbg("e20rClientModel::construct() - Error loading client_info table: " . $e->getMessage() );
        }

        if ( empty( $currentClient) ) {

            $currentClient = new stdClass();

            $currentClient->user_id = null;
            $currentClient->program_id = null;
        }
    }

    public function defaultSettings() {

        global $currentClient;
        global $currentProgram;
        global $post;

        $defaults                       = new stdClass();
        $defaults->user_id              = $currentClient->user_id;
        $defaults->program_id           = $currentProgram->id;
        $defaults->page_id              = isset( $post->ID ) ? $post->ID : CONST_NULL_ARTICLE;
        $defaults->program_start        = $currentProgram->startdate;
        $defaults->progress_photo_dir    = "e20r_pics/client_{$currentClient->program_id}_{$currentClient->user_id}";
        $defaults->gender               = 'm';
        $defaults->incomplete_interview = true; // Will redirect the user to the interview page.
        $defaults->first_name           = null;
        $defaults->birthdate            = null;
        $defaults->lengthunits          = 'in';
        $defaults->weightunits          = 'lbs';
        $defaults->loadedDefaults       = true;

        return $defaults;

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

		$sql = $wpdb->prepare("
			SELECT id
			FROM {$this->table}
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

        global $currentClient;

        if ( $currentClient->user_id != $userId ) {

            $this->setUser( $userId );
        }

        // No item specified, returning everything we have.
        if ( is_null( $item ) ) {

            dbg("e20rClientModel::getData() - Loading client information from database");
            $this->loadData( $currentClient->user_id );

            // Return all of the data for this user.
            return $currentClient;

        } else {

	        if ( ! isset( $currentClient->{$item} ) ) {

		        dbg( "e20rClientModel::getData() - Requested Item ({$item}) not found. Reloading.." );
		        $this->loadData( $userId );
	        }

	        // Only return the specified item value.
	        return ( empty( $currentClient->{$item} ) ? false : $currentClient->{$item} );
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

/*    public function load() {

        global $currentClient;
        global $current_user;

	    if ( WP_DEBUG === true ) {

		    $this->clearTransients();
	    }

	    if ( empty( $currentClient->user_id ) ) {

            $currentClient->user_id = $current_user->ID;
	    }

	    // $this->setUser( $currentClient->user_id );

        try {
            dbg("e20rClientModel::load() - Attempting to load clientInfo for user {$currentClient->user_id} from cache");

            if ( false === ( $currentClient = get_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}" ) ) ) {

                dbg("e20rClientModel::load() - Have to load client information for {$this->id} from the database");

                // Not stored yet, so grab the data from the DB and store it.
                $currentClient = $this->loadData( $currentClient->user_id, $currentClient->program_id );
                $this->data = $currentClient;

                // set_transient( "e20r_client_info_{$this->id}", $this->info, 1 * HOUR_IN_SECONDS );
                set_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}", $currentClient, 1 * 60 ); // TODO: Set to one hour!
            }

            dbg("e20rClientModel::load() - No Client information in the database for {$currentClient->user_id} and program {$currentClient->program_id}");
            $this->info = $currentClient;

            dbg("e20rClientModel::load() - Client info loaded" );

        } catch ( Exception $e ) {

            dbg( "e20rClientModel::load() - Error loading user information for {$currentClient->user_id} and program {$currentClient->program_id}: " . $e->getMessage() );
        }

    } */

    public function setUser( $id ) {

        global $currentClient;
        global $e20rProgram;

        if ( $id != $currentClient->user_id ) {
            $currentClient->user_id = $id;
            $currentClient->program_id = $e20rProgram->getProgramIdForUser( $currentClient->user_id );
        }
    }

    public function saveUnitInfo( $lengthunit, $weightunit ) {

        global $wpdb;
        global $e20rProgram;
        global $currentClient;

        if ( $wpdb->update( $this->table,
            array( 'lengthunits' => $lengthunit, 'weightunits' => $weightunit ),
            array( 'user_id' => $currentClient->user_id, 'program_id' => $currentClient->programId ),
                array( '%d' ) ) === false ) {

            dbg("e20rClientModel::saveUnitInfo() - Error updating unit info: " . $wpdb->print_error() );
            throw new Exception("Error updating weight/length units: " . $wpdb->print_error() );
        }

        $this->clearTransients();

        return true;
    }

	private function clearTransients() {

        global $currentClient;

		dbg("e20rClientModel::clearTransients() - Resetting cache & transients");
		delete_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}" );
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

    /*
    public function getAppointments() {

        if ( empty( $this->appointments ) ) {

            $this->load_appointments();
        }

        return $this->appointments;
    }
    */
    private function loadData( $clientId, $program_id = null ) {

	    global $wpdb;
	    global $post;
        global $e20rProgram;
	    global $currentProgram;
        global $currentClient;

	    global $e20rTracker;

	    // $oldId = $clientId;

        if ( empty( $currentClient->user_id ) || ( $clientId != $currentClient->user_id ) ) {

            dbg( "e20rClientModel::loadData() - WARNING: Loading data for a different client/user. Was: {$currentClient->user_id}, now: {$clientId}" );
            $this->setUser( $clientId );
        }

        if ( $currentProgram->id != $program_id ) {

            dbg( "e20rClientModel::loadData() - WARNING: Loading data for a different program: {$program_id} vs {$currentProgram->id}" );
            $currentClient->program_id = $e20rProgram->getProgramIdForUser( $currentClient->user_id );
            dbg( "e20rClientModel::loadData() - WARNING: Program data is now for {$currentProgram->id}" );
        }


        dbg( "e20rClientModel::loadData() - Loading default currentClient structure");

        // Init the unencrypted structure and load defaults.
        $currentClient = $this->defaultSettings();

	    // $this->setUser( $currentClient->user_id );

	    $key = $e20rTracker->getUserKey( $currentClient->user_id );

	    if ( WP_DEBUG === true ) {

		    $this->clearTransients();
	    }

	    if ( false === ( $tmpData = get_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}" ) ) ) {

            dbg("e20rClientModel::loadData() - Client data wasn't cached. Loading from DB.");

		    $excluded = array_keys( (array) $currentClient );

		    $sql = $wpdb->prepare( "
	                    SELECT *
	                    FROM {$this->table}
	                    WHERE program_id = %d AND
	                    user_id = %d
	                    ORDER BY program_start DESC
	                    LIMIT 1",
			    $currentClient->program_id,
			    $currentClient->user_id
		    );

		    $result = $wpdb->get_row( $sql, ARRAY_A );

		    if ( ! empty( $result ) ) {

                dbg("e20rClientModel::loadData() - Found client data in DB for user {$currentClient->user_id} and program {$currentClient->program_id}.");
                $currentClient->loadedDefaults = false;

			    foreach ( $result as $key => $val ) {

				    // Encrypted data gets decoded
				    if ( ! in_array( $key, $excluded ) ) {

                        $currentClient->{$key} = $e20rTracker->decryptData( $val, $key );
				    } else {
					    // Unencrypted data is simply passed back.
                        $currentClient->{$key} = $val;
				    }
			    }

			    // Clear the record from memory.
			    unset( $result );
		    }

		    if ( ! isset( $currentClient->user_enc_key ) && ( ! empty( $currentClient->user_enc_key ) ) ) {
			    unset( $currentClient->user_enc_key );
		    }

		    if ( isset( $currentClient->weight_loss ) && ( ! empty( $currentClient->weight_loss ) ) ) {

			    dbg( "e20rClientModel::loadClientData() - Client interview has been completed." );
                $currentClient->incomplete_interview = false;
		    }

		    set_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}", $currentClient, 3600 );

            // Restore the original User ID.
            // $this->setUser( $oldId );
        }
        else {
            $currentClient = $tmpData;
        }

        return $currentClient;
    }

    /*
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
    */
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
