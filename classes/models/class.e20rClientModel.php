<?php

class e20rClientModel {

    // protected $id = null;
    // private $program_id = null;

    protected $table;
    protected $fields;

    protected $data;

    protected $clientinfo_fields;

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

        $this->clientinfo_fields = array(
            'user_id', 'program_id', 'page_id', 'program_start', 'progress_photo_dir',
            'gender', 'first_name', 'last_name','birthdate', 'lengthunits',
            'weightunits'
        );

        if ( empty( $currentClient) ) {

            $currentClient = new stdClass();

            $currentClient->user_id = null;
            $currentClient->program_id = null;
        }
    }

    public function defaultSettings() {

        global $currentClient;
        global $currentProgram;
        global $currentArticle;
        global $post;

        $defaults                       = new stdClass();
        $defaults->user_id              = !empty( $currentClient->user_id ) ? $currentClient->user_id : 0;
        $defaults->program_id           = !empty( $currentProgram->id ) ? $currentProgram->id : null;
        $defaults->page_id              = !empty( $post->ID ) ? $post->ID : CONST_NULL_ARTICLE;
        $defaults->article_id           = !empty( $currentArticle->id ) ? $currentArticle->id : CONST_NULL_ARTICLE;
        $defaults->program_start        = !empty( $currentProgram->startdate ) ? $currentProgram->startdate : null;
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

    public function save_in_survey_table( $data ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        global $currentProgram;
        global $currentArticle;
        global $current_user;
        global $e20rTracker;
        global $e20rTables;

        global $post;
        global $wpdb;

        $record = array();

        $table = $e20rTables->getTable('surveys');
        $fields = $e20rTables->getFields('surveys');
        $encrypt = $e20rTracker->loadOption('encrypt_surveys');

        if ( ( !empty( $data['article_id'] )) && ( $data['article_id'] != $currentArticle->id ) ) {

            global $e20rArticle;
            dbg("e20rClientModel::save_in_survey_table() - Article ID in data vs currentArticle mismatch. Loading new article");
            $e20rArticle->getSettings( $data['article_id']);
        }

        $record["{$fields['article_id']}"] = !empty( $data['article_id'] ) ? $data['article_id'] : $currentArticle->id;

        if ( ( !empty( $data['program_id'] ) ) && ( $data['program_id'] != $currentProgram->id ) ) {

            global $e20rProgram;
            dbg("e20rClientModel::save_in_survey_table() - Program ID in data vs currentProgram mismatch. Loading new program info");

            $e20rProgram->init( $data['program_id']);
        }

        $record["{$fields['program_id']}"] = !empty( $data['program_id'] ) ? $data['program_id'] : $currentProgram->id;

        if ( $post->ID == $currentProgram->intake_form ) {
            dbg("e20rClientModel::save_in_survey_table() - Program config indicates we're on the same page as the welcome survey.");
            $record["{$fields['survey_type']}"] = E20R_SURVEY_TYPE_WELCOME;
        }
        else {
            $record["{$fields['survey_type']}"] = E20R_SURVEY_TYPE_OTHER;
        }

        $for_date_ts = strtotime( $e20rTracker->getDateFromDelay( $currentArticle->release_day, $data['user_id'] ) );
        $record["{$fields['for_date']}"] = date_i18n( 'Y-m-d H:i:s', $for_date_ts );

        if ( !empty( $data['user_id'] ) ) {
            dbg("e20rClientModel::save_in_survey_table() - User ID: {$data['user_id']}");

            $record["{$fields['user_id']}"] = $data['user_id'];
        }
        else {
            $record["{$fields['user_id']}"] = $current_user->ID;
        }

        if ( !empty( $data['completion_date'] ) ) {
            $record["{$fields['completed']}"] = date_i18n( 'Y-m-d H:i:s', strtotime( $data['completion_date'] ) );
        }

        $survey_data = array();

        foreach( $data as $key => $value ) {

            if ( !in_array( $key, $this->clientinfo_fields ) ) {

                $survey_data[$key] = $value;
            }
        }

        if ( 1 == $encrypt ) {
            dbg("e20rClientModel::save_in_survey_table() - Enable data encryption for user {$record['user_id']}");
            $record["{$fields['is_encrypted']}"] = true;
        }
        else {
            dbg("e20rClientModel::save_in_survey_table() - WARNING: Won't enable data encryption for user {$record['user_id']}");
            $record["{$fields['is_encrypted']}"] = false;
        }

        dbg("e20rClientModel::save_in_survey_table() - Loading the encryption key for the end user");
        $key = $e20rTracker->getUserKey( $data['user_id']);

        $record["{$fields['survey_data']}"] = $e20rTracker->encryptData( serialize( $survey_data ), $key );

        // Check whether the surveys table already contains the record we're trying to save.
        if ( ( $id = $this->recordExists( $record['user_id'], $record['program_id'], null, $record['article_id'], $table ) ) !== false ) {

            dbg('e20rTrackerModel::save_in_survey_table() - User/Program exists in the client info table. Editing existing record.' );
            $record["{$fields['id']}"] = $id;
        }

        $format = $e20rTracker->setFormatForRecord( $record );

        if ( false === $wpdb->replace( $table, $record, $format ) ) {

            global $EZSQL_ERROR;
            dbg($EZSQL_ERROR);

            dbg( "e20rTrackerModel::save_in_survey_table() - Error inserting form data: " . $wpdb->print_error() );
            dbg( "e20rTrackerModel::save_in_survey_table() - Query: " . print_r( $wpdb->last_query, true ) );

            return false;
        }

        return true;
    }

	public function save_client_interview( $data ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        global $wpdb;
		global $e20rTracker;

		if ( !empty( $data['completion_date'] ) ) {
            $data['completed_date'] = date('Y-m-d H:i:s', strtotime( $data['completion_date'] . " ". date( 'H:i:s', current_time('timestamp') ) ) );
        }
        else {
            $data['completed_date'] = date('Y-m-d H:i:s', current_time('timestamp'));
        }
		// $id = false;

		dbg("e20rClientModel::save_client_interview() - Saving data to {$this->table}");

		if ( ( $id = $this->recordExists( $data['user_id'], $data['program_id'], $data['page_id'] ) ) !== false ) {

			dbg('e20rTrackerModel::save_client_interview() - User/Program exists in the client info table. Editing an existing record.' );
			$data['edited_date'] = date_i18n('Y-m-d H:i:s', current_time('timestamp') );
			$data['id'] = $id;
		}

		if ( isset( $data['started_date'] ) ) {

			unset($data['started_date'] ); // $data['started_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
		}

        if ( false === $this->save_in_survey_table( $data ) ) {
            dbg("e20rClientModel::save_client_interview() - ERROR: Couldn't save in survey table!");
            return false;
        }

        $to_save = array();
        foreach( $this->clientinfo_fields as $field ) {

            // Clean (empty) the client_info for everything except what we need in order to load various forms, etc.
            $to_save[$field] = $data[$field];
        }

        dbg("e20rClientModel::save_client_interview() - We will save the following data to the client_info table:");
        dbg($to_save);

		// Generate format array.
		$format = $e20rTracker->setFormatForRecord( $to_save );

        if ( false === $wpdb->replace( $this->table, $to_save, $format ) ) {

            global $EZSQL_ERROR;
            dbg($EZSQL_ERROR);

            dbg( "e20rTrackerModel::save_client_interview() - Error inserting form data: " . $wpdb->print_error() );
            dbg( "e20rTrackerModel::save_client_interview() - Query: " . print_r( $wpdb->last_query, true ) );

            return false;
        }

		dbg("e20rTrackerModel::save_client_interview() - Data saved...");
		$this->clearTransients();

		return true;
	}

	private function recordExists( $userId, $programId, $postId = null, $article_id = null, $table_name = null ) {

		global $wpdb;
        global $e20rTables;

        if ( is_null( $table_name ) ) {

            $table_name = $this->table;
        }

        dbg("e20rClientModel::recordExists() - Checking whether {$table_name} record exists for {$userId} in {$programId}");

        if ( !is_null( $postId ) ) {
            $sql = $wpdb->prepare("
                SELECT id
                FROM {$table_name}
                WHERE user_id = %d AND program_id = %d AND page_id = %d AND article_id = %d
            ",
                $userId,
                $programId,
                $postId,
                $article_id
            );
        }
        else {
            $sql = $wpdb->prepare("
                SELECT id
                FROM {$table_name}
                WHERE user_id = %d AND program_id = %d AND article_id = %d
            ",
                $userId,
                $programId,
                $article_id
            );
        }

		$exists = $wpdb->get_var( $sql );

		if ( !empty( $exists ) ) {

			dbg("e20rClientModel::recordExists() - Found record with id: {$exists}");
			return (int)$exists;
		}

		return false;
	}

    public function get_data( $userId, $item = null ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        global $currentClient;
        global $e20rTracker;
        global $e20rProgram;

        global $current_user;
        global $currentProgram;

        dbg("e20rClientModel::get_data() - Loading program information for client ID {$userId}");
        $e20rProgram->getProgramIdForUser( $userId );

        if ( ( $currentClient->user_id != $userId ) && ( $e20rTracker->is_a_coach( $current_user->ID ) ) ) {

            dbg("e20rClientModel::get_data() - Loading client information from database for client ID {$userId}");
            $this->setUser( $userId );
        }


        // No item specified, returning everything we have.
        if ( is_null( $item ) ) {

            dbg("e20rClientModel::get_data() - Loading client information from database for {$currentClient->user_id}");
            $this->load_data( $currentClient->user_id, $currentProgram->id );

            // Return all of the data for this user.
            return $currentClient;

        } else {

	        if ( ! isset( $currentClient->{$item} ) ) {

		        dbg( "e20rClientModel::get_data() - Requested Item ({$item}) not found. Reloading.." );
                // $programId = $e20rProgram->getProgramIdForUser( $currentClient->user_id );
		        $this->load_data( $currentClient->user_id, $currentProgram->id );
	        }

            dbg( "e20rClientModel::get_data() - Requested Item ({$item}) found for user {$currentClient->user_id}" );
	        // Only return the specified item value.
	        return ( empty( $currentClient->{$item} ) ? false : $currentClient->{$item} );
        }

	    return false;
    }

    public function saveData( $clientData ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        global $wpdb;
        global $e20rTables;
        global $e20rTracker;

        $table = $e20rTables->getTable( 'client_info' );

        // These are the fields that won't get encrypted.
        $encData = array();
        $encData['user_id'] = $clientData['user_id'];
        $encData['program_id'] = $clientData['program_id'];
        $encData['article_id'] = $clientData['article_id'];
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

    private function load_from_survey_table( $clientId, $program_id, $article_id ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        dbg("e20rClientModel::load_from_survey_table() - Start...");
        global $currentProgram;
        global $currentArticle;
        global $current_user;

        global $e20rTracker;
        global $e20rTables;
        global $e20rArticle;

        global $wpdb;
        global $post;

        $table = $e20rTables->getTable('surveys');
        $fields = $e20rTables->getFields( 'surveys' );

        /*
        if ( ( !empty( $article_id )) && ( $article_id != $currentArticle->id ) ) {

            dbg("e20rClientModel::load_from_survey_table() - Article ID in data vs currentArticle mismatch. Loading new article");
            $e20rArticle->getSettings( $article_id );
        }
        else {
            dbg("e20rClientModel::load_from_survey_table() - No article ID specified. ");
            $e20rArticle->getSettings( CONST_NULL_ARTICLE );
        }
        */
        /*
        if ( ( !empty( $program_id ) ) && ( $program_id != $currentProgram->id ) ) {

            global $e20rProgram;
            dbg("e20rClientModel::load_from_survey_table() - Program ID in data vs currentProgram mismatch. Loading new program info");

            $e20rProgram->init( $program_id );
        }
        */
        if ( ( is_admin() && $e20rTracker->is_a_coach( $current_user->ID ) )|| ( $post->ID == $currentProgram->intake_form ) ) {

            dbg("e20rClientModel::load_from_survey_table() - Program config indicates we're on the same page as the welcome survey.");
            $survey_type = E20R_SURVEY_TYPE_WELCOME;
        }
        else {
            $survey_type = E20R_SURVEY_TYPE_OTHER;
        }

        $current_date_ts = strtotime( $e20rTracker->getDateFromDelay( $currentArticle->release_day, $clientId ) );
        $record['for_date'] = date_i18n( 'Y-m-d H:i:s', $current_date_ts );

        $sql = $wpdb->prepare(
            "SELECT *
                FROM {$table}
                WHERE {$fields['user_id']} = %d AND
                {$fields['survey_type']} = %d AND
                {$fields['program_id']} = %d AND
                {$fields['article_id']} = %s
          ",
            $clientId,
            $survey_type,
            $program_id,
            ( $article_id != 0 ? $article_id : '%' )
        );

        // dbg($sql);
        $records = $wpdb->get_results( $sql, ARRAY_A );

        if ( !empty( $records ) ) {

            if ( count( $records ) > 1 ) {
                dbg("e20rClientModel::load_from_survey_table() - WARNING: More than one record returned for this program/article/user combination (not supposed to happen!)");
                return false;
            }
            else {

                foreach( $records as $record ) {

                    $encrypted_survey = $record["{$fields['survey_data']}"];

                    if ( 1 == $record["{$fields['is_encrypted']}"] ) {

                        dbg("e20rClientModel::load_from_survey_table() - WARNING: Survey data is encrypted. Decrypting it for user {$clientId}");

                        $userKey = $e20rTracker->getUserKey($clientId);

                        if ( !empty( $userKey ) ) {

                            dbg("e20rClientModel::load_from_survey_table() - Loaded key for user {$clientId} ");
                            $decrypted_survey = $e20rTracker->decryptData($encrypted_survey, $userKey, $encrypted_survey );

                            $survey = unserialize($decrypted_survey);
                        }
                        else {

                            return false;
                        }
                    }
                    else {
                        dbg("e20rClientModel::load_from_survey_table() - Survey data is NOT encrypted. Nothing to decrypt");
                        $survey = unserialize( $encrypted_survey );
                    }

                    dbg("e20rClientModel::load_from_survey_table() - Retrieved and decrypted" . count($survey) . " encrypted survey fields");
                    return $survey;
                }
            }
        }

        dbg("e20rClientModel::load_from_survey_table() - No survey records found for user ID {$clientId} in program {$program_id} for article {$article_id} ");
        return false;
    }

    private function load_data( $clientId, $program_id = null, $table = null ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

	    global $wpdb;
	    global $post;

	    global $currentProgram;
        global $currentClient;
        global $currentArticle;

        global $e20rProgram;
	    global $e20rTracker;
        global $e20rArticle;

	    if ( empty( $currentClient->user_id ) || ( $clientId != $currentClient->user_id ) ) {

            dbg( "e20rClientModel::load_data() - WARNING: Loading data for a different client/user. Was: {$currentClient->user_id}, will be: {$clientId}" );
            $this->setUser( $clientId );
        }

        if ( empty( $currentProgram->id ) || ($currentProgram->id != $program_id ) ) {

            dbg( "e20rClientModel::load_data() - WARNING: Loading data for a different program: {$program_id} vs {$currentProgram->id}" );
            $currentClient->program_id = $e20rProgram->getProgramIdForUser( $currentClient->user_id );
            dbg( "e20rClientModel::load_data() - WARNING: Program data is now for {$currentProgram->id}" );
        }

        dbg( "e20rClientModel::load_data() - Loading default currentClient structure");

        // Init the unencrypted structure and load defaults.
        $currentClient = $this->defaultSettings();

	    // $this->setUser( $currentClient->user_id );

        if ( is_null( $table ) ) {

            $table = $this->table;
        }

	    // $key = $e20rTracker->getUserKey( $currentClient->user_id );

	    if ( WP_DEBUG === true ) {

		    $this->clearTransients();
	    }

	    if ( false === ( $tmpData = get_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}" ) ) ) {

            dbg("e20rClientModel::load_data() - Client data wasn't cached. Loading from DB.");

		    $excluded = array_keys( (array) $currentClient );

		    $sql = $wpdb->prepare( "
	                    SELECT user_id, program_id, page_id, program_start, progress_photo_dir, gender,
	                           first_name, last_name, birthdate, lengthunits, weightunits
	                    FROM {$table}
	                    WHERE program_id = %d AND
	                    user_id = %d
	                    ORDER BY program_start DESC
	                    LIMIT 1",
			    $currentClient->program_id,
			    $currentClient->user_id
		    );

		    $records = $wpdb->get_row( $sql, ARRAY_A );

		    if ( ! empty( $records ) ) {

/*                if ( !empty( $records['page_id'] ) ) {

                    dbg("e20rClientModel::load_data() - Have a page Id to search for the article on behalf of");
                    $aId = $e20rArticle->findArticle( 'post_id', $records['page_id'], 'numeric', $currentClient->program_id );
                    dbg($aId);

                    $e20rArticle->getSettings( $aId );
                }
*/
                dbg("e20rClientModel::load_data() - Found client data in DB for user {$currentClient->user_id} and program {$currentClient->program_id}.");
                $currentClient->loadedDefaults = false;

                // Load the relevant survey record (for this article/assignment/page)
                dbg("e20rClientModel::load_data() - Load data from survey table for user {$currentClient->user_id} and program {$currentClient->program_id} and article {$currentArticle->id}");

                if ( false ===
                    ( $survey = $this->load_from_survey_table( $clientId, $currentProgram->id, $currentArticle->id ) ) ) {
                    return false;
                }

                dbg("e20rClientModel::load_data() - Fetched Survey record: ");
                // dbg($survey);

                $result = array();

                // Merge the survey data with the client_info data.
                foreach ( $records as $key => $value ) {

                    $result[$key] = $value;
                }

                foreach( $survey as $key => $value ) {

                    $result[$key] = $value;
                }

//                 $result = array_merge( $survey, $records );

                dbg("e20rClientModel::load_data() - Merged Survey record with client_info record: ");
                // dbg($result);

                // Save merged records to $currentClient global
			    foreach ( $result as $key => $val ) {

                    $currentClient->{$key} = $val;
			    }

			    // Clear the record from memory.
			    unset( $survey );
                unset( $result );
		    }

		    if ( ! isset( $currentClient->user_enc_key ) && ( ! empty( $currentClient->user_enc_key ) ) ) {
			    unset( $currentClient->user_enc_key );
		    }

		    if ( isset( $currentClient->completed_date ) && ( ! empty( $currentClient->completed_date ) ) ) {

			    dbg( "e20rClientModel::load_data() - Client interview has been completed." );
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
