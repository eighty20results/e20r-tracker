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
        $defaults->coach_id             = null;
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

    public function save_message_to_history( $userId, $programId, $senderId, $message, $topic ) {

        global $wpdb;

        global $e20rTables;
        global $e20rTracker;

        $table = $e20rTables->getTable('message_history');
        $fields = $e20rTables->getFields('message_history');
        $sent = date_i18n( 'Y-m-d H:i:s', current_time('timestamp') );

        dbg("e20rClientModel::save_message_to_history() - Saving message '{$topic}' to user ID {$userId} from user ID {$senderId} sent at {$sent}");

        $sql = "
            INSERT INTO {$table}
              ( {$fields['user_id']}, {$fields['program_id']}, {$fields['sender_id']}, {$fields['topic']}, {$fields['message']}, {$fields['sent']} )
            VALUES ( {$userId}, {$programId}, {$senderId}, '" . esc_sql( $topic ) . "', '" . esc_sql( $message ) ."', '". esc_sql( $sent ) ."' )";


        // dbg("e20rClientModel::save_message_to_history: {$sql}");
        $status = $wpdb->query( $sql );

        if ( false === $status ) {

            $user = get_user_by('id', $userId);
            $error = '<div class="error">';
            $error .= '    <p>' . sprintf( __("Error while saving the message history for %s %s: %s ", "e20rtracker"), $user->user_firstname, $user->user_lastname, $wpdb->print_error() ) . '</p>';
            $error .= '</div><!-- /.error -->';

            $e20rTracker->updateSetting( 'unserialize_notice', $error );

            dbg("e20rClientModel::save_message_to_history() - ERROR: Could not save message to {$user->user_firstname}  {$user->user_lastname}: " . $wpdb->print_error() );
            return false;
        }

        dbg("e20rClientModel::save_message_to_history() - Saved message to message history for {$userId}");
        return true;
    }

    public function load_message_history( $userId ) {

        dbg("e20rClientModel::load_message_history() - Looking for a message history for {$userId}");
        global $wpdb;

        global $e20rTables;

        global $currentProgram;

        $table = $e20rTables->getTable('message_history');
        $fields = $e20rTables->getFields('message_history');

        $sql = $wpdb->prepare("
            SELECT  {$fields['id']},
                    {$fields['sender_id']},
                    {$fields['topic']},
                    {$fields['message']},
                    {$fields['sent']}
            FROM {$table}
            WHERE {$fields['user_id']} = %d AND {$fields['program_id']} = %d
            ",
            $userId,
            $currentProgram->id );

        // dbg("e20rClientModel::load_message_history() - SQL for message history: {$sql}");

        $messages = $wpdb->get_results( $sql );

        dbg("e20rClientModel::load_message_history() - Found " . count( $messages) . " messages for user with ID {$userId}");

        $history = array();

        if ( !empty( $messages ) ) {

            foreach ( $messages as $message ) {

                $sent_date = date_i18n('Y-m-d \a\t H:i', strtotime($message->sent));
                unset($message->sent); // Make the date for the sent message the format we want.
                $history[$sent_date] = $message;
            }
        }

        return $history; // Empty array if there is no message history.
    }

    public function interview_complete( $userId ) {

        global $currentProgram;
        global $e20rProgram;

        global $wpdb;

        if ( empty( $currentProgram->id ) ) {
            $e20rProgram->getProgramIdForUser( $userId );
        }

        $sql = $wpdb->prepare( "
                SELECT COUNT(NULLIF(user_id, '')) + COUNT(NULLIF(program_id, '')) + COUNT(NULLIF(page_id, '')) +
                  COUNT(NULLIF(program_start, '')) + COUNT(NULLIF(progress_photo_dir, '')) + COUNT(NULLIF(gender, '')) +
                  COUNT(NULLIF(first_name, '')) + COUNT(NULLIF(last_name, '')) + COUNT(NULLIF(birthdate, '')) +
                  COUNT(NULLIF(lengthunits, '')) + COUNT(NULLIF(weightunits, '')) AS completed_fields
                FROM {$this->table}
                WHERE program_id = %d AND
                user_id = %d
                ORDER BY program_start DESC
                LIMIT 1",
            $currentProgram->id,
            $userId
        );

        dbg("e20rClientModel::interview_complete() - SQL to check whether the interview was completed: {$sql}");
        $count = $wpdb->get_var( $sql );

        if ( empty( $count ) || $count == 0 || $count < 11 ) {
            dbg("e20rClientModel::interview_complete() - Not enough answers given: {$count}");
            return false;
        }

        dbg("e20rClientModel::interview_complete() - Interview has been completed");
        return true;
    }

    public function load_client_settings( $clientId ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        global $e20rProgram;
        global $currentClient;
        global $currentProgram;

        global $wpdb;
        global $e20rTracker;

        if ( !isset( $currentProgram->id ) ) {

            $e20rProgram->getProgramIdForUser( $clientId );
        }

        dbg("e20rClientModel::load_client_settings() - Loading client information from database for {$clientId}");

        $sql = $wpdb->prepare( "
                SELECT user_id, program_id, page_id, program_start, progress_photo_dir, gender,
                       first_name, last_name, birthdate, lengthunits, weightunits
                FROM {$this->table}
                WHERE program_id = %d AND
                user_id = %d
                ORDER BY program_start DESC
                LIMIT 1",
            $currentProgram->id,
            $clientId
        );

        $records = $wpdb->get_row( $sql, ARRAY_A );

        if ( ! empty( $records ) ) {

            dbg("e20rClientModel::load_client_settings() - Populating the currentClient object...");
            foreach ( $records as $key => $val ) {

                $currentClient->{$key} = $val;
            }
        }
        dbg("e20rClientModel::load_client_settings() - currentClient object:");
        dbg( $currentClient );

        // Return all of the data for this user.
        return $currentClient;

    }

    public function get_data( $userId, $item = null, $article_id = null ) {

        if ( ! is_user_logged_in() ) {

            return false;
        }

        global $currentClient;
        global $e20rTracker;
        global $e20rProgram;

        global $current_user;
        global $currentProgram;

        dbg("e20rClientModel::get_data(): " . $e20rTracker->whoCalledMe() );
//        $e20rProgram->getProgramIdForUser( $userId );

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

	        if ( empty( $currentClient->{$item} ) ) {

		        dbg( "e20rClientModel::get_data() - Requested Item ({$item}) not found. Reloading.." );
                // $programId = $e20rProgram->getProgramIdForUser( $currentClient->user_id );
		        $this->load_data( $currentClient->user_id, $currentProgram->id );
	        }

            // dbg( "e20rClientModel::get_data() - Requested Item ({$item}) found for user {$currentClient->user_id}" );
	        // Only return the specified item value.
	        return ( empty( $currentClient->{$item} ) ? false : $currentClient->{$item} );
        }

	    return false;
    }

    public function load_basic_clientdata( $clientId, $program_id = null, $table = null )
    {

        global $wpdb;
        global $post;

        global $currentProgram;
        global $currentClient;
        global $currentArticle;

        global $e20rProgram;
        global $e20rTracker;
        global $e20rArticle;

        dbg("e20rClientModel::load_basic_clientdata() - Loading default currentClient structure");

        // Init the unencrypted structure and load defaults.
        $currentClient = $this->defaultSettings();

        // $this->setUser( $currentClient->user_id );

        if (is_null($table)) {

            $table = $this->table;
        }

        // $key = $e20rTracker->getUserKey( $currentClient->user_id );

        if (WP_DEBUG === true) {

            $this->clearTransients();
        }

        dbg("e20rClientModel::load_basic_clientdata() - Client data wasn't cached. Loading from DB.");

        $sql = $wpdb->prepare("
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

        $records = $wpdb->get_row($sql, ARRAY_A);
        return $records;
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

        dbg("e20rClientModel::load_data(): " . $e20rTracker->whoCalledMe() );

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
            $records = $this->load_basic_clientdata( $clientId, $program_id, $table );

            if ( ! empty( $records ) ) {

                if ( !empty( $records['page_id'] ) ) {

                   $postId = $records['page_id'];
                }
                else {
                    $postId = $post->ID;
                }

                /*
                if ( CONST_NULL_ARTICLE == $postId ) {

                    return $currentClient;
                }
                */
                dbg("e20rClientModel::load_data() - Have a page Id to search for the article on behalf of");
                $articles = $e20rArticle->findArticles( 'post_id', $postId, 'numeric', $currentClient->program_id );

                foreach ($articles as $article ) {

                    if ( in_array( $currentClient->program_id, $article->program_ids ) && ( 1 == $article->is_survey ) ) {

                        dbg("e20rClientModel::load_data() - Found article for the survey.");
                        $currentArticle = $article;
                        break;
                    }
                }

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

        $table = $e20rTables->getTable( 'surveys' );
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

        if ( ( !empty( $program_id ) ) && ( $program_id != $currentProgram->id ) ) {

            global $e20rProgram;
            dbg("e20rClientModel::load_from_survey_table() - Program ID in data vs currentProgram mismatch. Loading new program info");

            $e20rProgram->init( $program_id );
        }
        */
        if ( ( is_admin() && $e20rTracker->is_a_coach( $current_user->ID ) )||
            ( $post->ID == $currentProgram->intake_form ) ||
            ( has_shortcode( $post->post_content, 'e20r_profile' ) ) ) {

            dbg("e20rClientModel::load_from_survey_table() - Program config indicates we're loading for a welcome survey.");
            $survey_type = E20R_SURVEY_TYPE_WELCOME;
        }
        else {
            $survey_type = E20R_SURVEY_TYPE_OTHER;
        }

        $current_date_ts = strtotime( $e20rTracker->getDateFromDelay( $currentArticle->release_day, $clientId ) );
        $record['for_date'] = date_i18n( 'Y-m-d H:i:s', $current_date_ts );

        if ( !has_shortcode( $post->post_content, 'e20r_profile' ) ) {

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
                ( !empty( $article_id ) ? $article_id : '%' )
            );
        }
        else {
            $sql = $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE {$fields['user_id']} = %d AND
                {$fields['survey_type']} = %d AND
                {$fields['program_id']} = %d
          ",
                $clientId,
                $survey_type,
                $program_id
            );
        }


        dbg($sql);
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

                    dbg("e20rClientModel::load_from_survey_table() - Retrieved and decrypted " . count($survey) . " survey fields");
                    return $survey;
                }
            }
        }

        dbg("e20rClientModel::load_from_survey_table() - No survey records found for user ID {$clientId} in program {$program_id} for article {$article_id} ");
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

            dbg("e20rTrackerModel::save_client_interview() - User/Program exists in the client info table. Editing an existing record: {$id}" );
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

        if ( isset( $data['id'] ) ) {

            $to_save['id'] = $data['id'];
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
            dbg("e20rClientModel::recordExists() - Including post ID in search");
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
            dbg("e20rClientModel::recordExists() - NOT including post ID in search");
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
        global $current_user;

        global $currentClient;

        if ( !isset( $currentClient->user_id ) || ( !isset( $currentClient->programId ) ) ) {

            $this->setUser( $current_user->ID );
        }

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
                $imageUrl = E20R_PLUGINS_URL . '/img/no-image-uploaded.jpg';
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

    public function get_coach( $client_id = null, $program_id = null ) {

        global $e20rProgram;
        global $currentProgram;

        global $wpdb;

        $saved = $currentProgram;

        $coaches = array();

        if ( is_null( $program_id ) && ( is_null( $client_id ) ) ) {
            dbg("e20rClientModel::get_coach() - Neither program nor user ID is defined. Get everybody with a capability like 'e20r_coach'");
            $coach_query = array(
                'field' => array( 'ID', 'display_name' ),
                'meta_query' => array(
                    array(
                        'key'       => $wpdb->prefix . 'capabilities',
                        'value'     => 'e20r_coach',
                        'compare'   => 'LIKE'
                    ),
                )
            );
        }
        elseif ( is_null( $client_id ) && !is_null( $program_id ) ) {

            dbg("e20rClientModel::get_coach() - Program ID is defined. User ID isn't. Get all coaches who coach for program # {$program_id}");
            $coach_query = array(
                'field' => array( 'ID', 'display_name' ),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'       => $wpdb->prefix . 'capabilities',
                        'value'     => 'e20r_coach',
                        'compare'   => 'LIKE'
                    ),
                    array(
                        'key'       => 'e20r-tracker-coaching-program_ids',
                        'value'     => $program_id,
                        'compare'   => 'IN'
                    ),
                )
            );
        }
        elseif ( !is_null( $client_id ) && is_null( $program_id ) ) {

            dbg("e20rClientModel::get_coach() - Program ID is NOT defined. User ID IS. Get all coaches who coach user with ID {$user_id}");
            $coach_query = array(
                'field' => array( 'ID', 'display_name' ),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'       => $wpdb->prefix . 'capabilities',
                        'value'     => 'e20r_coach',
                        'compare'   => 'LIKE'
                    ),
                    array(
                        'key'       => 'e20r-tracker-coaching-client_ids',
                        'value'     => $client_id,
                        'compare'   => 'IN'
                    ),
                )
            );

        }
        elseif ( !is_null( $program_id ) && ( !is_null( $client_id )) ) {
            dbg("e20rClientModel::get_coach() - Program ID is defined. User ID is defined. Get all coaches who coach for program # {$program_id} and user {$client_id}");
            $coach_query = array(
                'field' => array( 'ID', 'display_name' ),
                'meta_query'   => array(
                    'relation' => 'AND',
                    array(
                        'key'       => $wpdb->prefix . 'capabilities',
                        'value'     => 'e20r_coach',
                        'compare'   => 'LIKE'
                    ),
                    array(
                        'key'       => 'e20r-tracker-coaching-program-ids',
                        'value'     => $program_id,
                        'compare'   => 'IN'
                    ),
                    array(
                        'key'       => 'e20r-tracker-coaching-client_ids',
                        'value'     => $client_id,
                        'compare'   => 'IN'
                    ),
                ),
            );
        }

        $results = get_users( $coach_query );

        dbg("e20rClientModel::get_coach() - Found " . count($results) . " coaches...");

        if (!empty( $results ) ) {

            $coaches = array();

            foreach( $results as $coach ) {
                $coaches[ $coach->ID ] = $coach->display_name;
            }
        }
        $currentProgram = $saved;
        return $coaches;
    }

    public function get_clients( $coach_id = null, $program_id = null ) {

        global $wpdb;

        dbg("e20rClientModel::get_clients_for_program() - Loading all of coach #{$coach_id} clients for program {$program_id} ");

        if ( is_null( $program_id ) ) {

            $user_query = array(
                'meta_query'   => array(
                    array(
                        'key'       => 'e20r-tracker-coach-for-program',
                        'value'     => $coach_id,
                        'compare'   => '='
                    )
                ),
            );
        }
        elseif ( is_null( $coach_id ) && !is_null( $program_id ) ) {

            $user_query = array(
                'meta_query'   => array(
                    array(
                        'key'       => 'e20r-tracker-program-id',
                        'value'     => $program_id,
                        'compare'   => '='
                    )
                )
            );
        }
        else {
            $user_query = array(
                'meta_query'   => array(
                    'relation' => 'AND',
                    array(
                        'key'       => 'e20r-tracker-program-id',
                        'value'     => $program_id,
                        'compare'   => '='
                    ),
                    array(
                        'key'       => 'e20r-tracker-coach-for-program',
                        'value'     => $coach_id,
                        'compare'   => '='
                    )
                ),
            );
        }

        $users = get_users( $user_query );
        dbg("e20rClientModel::get_clients_for_program() - Found " . count( $users ) . " clients for coach/program: {$coach_id}/{$program_id}");

        return $users;
    }

}
