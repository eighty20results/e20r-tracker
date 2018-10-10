<?php
/*
    Copyright 2015-2018 Thomas Sjolshagen / Wicked Strong Chicks, LLC (info@eighty20results.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace E20R\Tracker\Models;

use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Assignment;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\Tracker_Crypto;
use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Tracker_Access;
use E20R\Utilities\Cache;
use MongoDB\BSON\UTCDateTime;

/**
 * Class Client_Model
 * @package E20R\Tracker\Models
 */
class Client_Model {
	
	protected $table;
	protected $fields;
	
	protected $data;
	
	protected $clientinfo_fields;
	
	/**
	 * Client_Model constructor.
	 */
	public function __construct() {
		
		$Tables = Tables::getInstance();
		
		global $currentClient;
		
		try {
			$this->table = $Tables->getTable( 'client_info' );
		} catch ( \Exception $e ) {
			Utilities::get_instance()->log( "Error loading client_info table: " . $e->getMessage() );
			
			return false;
		}
		
		$this->fields = $Tables->getFields( 'client_info' );
		
		/**
		 * Required data to load the User Measurement(s) page(s)
		 */
		$this->clientinfo_fields = array(
			'user_id',
			'program_id',
			'page_id',
			'program_start',
			'progress_photo_dir',
			'gender',
			'first_name',
			'last_name',
			'birthdate',
			'lengthunits',
			'weightunits',
		);
		
		if ( empty( $currentClient ) ) {
			
			$currentClient = new \stdClass();
			
			$currentClient->user_id    = null;
			$currentClient->program_id = null;
		}
	}
	
	/**
	 * Save the sent message to the user's message history
	 *
	 * @param int    $user_id
	 * @param int    $program_id
	 * @param int    $senderId
	 * @param string $message
	 * @param string $topic
	 *
	 * @return bool
	 */
	public function save_message_to_history( $user_id, $program_id, $senderId, $message, $topic ) {
		
		global $wpdb;
		
		$Tables  = Tables::getInstance();
		$Tracker = Tracker::getInstance();
		
		try {
			$table = $Tables->getTable( 'message_history' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error getting table for the message history: " . $exception->getMessage() );
			
			return false;
		}
		
		$fields = $Tables->getFields( 'message_history' );
		$sent   = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		
		Utilities::get_instance()->log( "Saving message '{$topic}' to user ID {$user_id} from user ID {$senderId} sent at {$sent}" );
		
		$record = array(
			$fields['user_id']    => $user_id,
			$fields['program_id'] => $program_id,
			$fields['sender_id']  => $senderId,
			$fields['topic']      => $topic,
			$fields['message']    => $message,
			$fields['sent']       => $sent,
		);
		
		try {
			$format = $Tracker->setFormatForRecord( $record );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Problem generating record format! Error: " . $exception->getMessage() );
			
			return false;
		}
		
		if ( false === $wpdb->insert( $table, $record, $format ) ) {
			
			$user  = get_user_by( 'id', $user_id );
			$error = '<div class="error">';
			$error .= '    <p>' . sprintf( __( "Error while saving the message history for %s %s: %s ", "e20r-tracker" ), $user->user_firstname, $user->user_lastname, $wpdb->print_error() ) . '</p>';
			$error .= '</div><!-- /.error -->';
			
			$Tracker->updateSetting( 'unserialize_notice', $error );
			
			Utilities::get_instance()->log( "ERROR: Could not save message to {$user->user_firstname}  {$user->user_lastname}: " . $wpdb->print_error() );
			
			return false;
		}
		
		Utilities::get_instance()->log( "Saved message to message history for {$user_id}" );
		
		return true;
	}
	
	/**
	 * Load the message history for a specific user ID
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function load_message_history( $user_id ) {
		
		Utilities::get_instance()->log( "Looking for a message history for {$user_id}" );
		global $wpdb;
		
		$Tables     = Tables::getInstance();
		$Tracker    = Tracker::getInstance();
		$Assignment = Assignment::getInstance();
		
		global $currentProgram;
		
		try {
			$table   = $Tables->getTable( 'message_history' );
			$r_table = $Tables->getTable( 'response' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error fetching table definition: " . $exception->getMessage() );
			
			return array();
		}
		
		$fields   = $Tables->getFields( 'message_history' );
		$r_fields = $Tables->getFields( 'response' );
		
		$sql = $wpdb->prepare( "
            SELECT  {$fields['id']},
                    {$fields['sender_id']},
                    {$fields['topic']},
                    {$fields['message']},
                    {$fields['sent']}
            FROM {$table}
            WHERE {$fields['user_id']} = %d AND {$fields['program_id']} = %d
            ORDER BY {$fields['sent']}
            ",
			$user_id,
			$currentProgram->id );
		
		// Utilities::get_instance()->log("SQL for message history: {$sql}");
		
		$messages = $wpdb->get_results( $sql );
		
		Utilities::get_instance()->log( "Found " . count( $messages ) . " messages for user with ID {$user_id}" );
		
		$resp_sql = $wpdb->prepare( "
            SELECT  {$r_fields['id']} AS id,
                    {$r_fields['sent_by_id']} AS sender_id,
                    {$r_fields['assignment_id']} AS assignment_id,
                    {$r_fields['message']} AS message,
                    {$r_fields['message_time']} AS sent
            FROM {$r_table}
            WHERE {$r_fields['client_id']} = %d AND {$fields['program_id']} = %d
            ORDER BY {$r_fields['message_time']}
            ",
			$user_id,
			$currentProgram->id
		);
		
		$responses = $wpdb->get_results( $resp_sql );
		
		$merged = array_merge( $responses, $messages );
		
		Utilities::get_instance()->log( "Found " . count( $responses ) . " messages in response table for user with ID {$user_id}" );
		Utilities::get_instance()->log( "Total messages for {$user_id}: " . count( $merged ) );
		
		usort( $merged, array( &$Tracker, 'sort_descending' ) );
		
		$history = array();
		
		if ( ! empty( $merged ) ) {
			
			foreach ( $merged as $message ) {
				
				if ( isset( $message->assignment_id ) ) {
					$message->topic = $Assignment->get_assignment_question( $message->assignment_id );
				}
				
				$message->message = stripslashes( $message->message );
				$sent_date        = strtotime( $message->sent );
				// $sent_date = date_i18n('Y-m-d \a\t H:i', strtotime($message->sent));
				unset( $message->sent ); // Make the date for the sent message the format we want.
				$history[ $sent_date ] = $message;
			}
		}
		
		return $history; // Empty array if there is no message history.
	}
	
	/**
	 * Check if the user has completed the intake interview
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function interview_complete( $user_id ) {
		
		global $currentProgram;
		$Program = Program::getInstance();
		
		global $wpdb;
		
		if ( empty( $currentProgram->id ) ) {
			$Program->getProgramIdForUser( $user_id );
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
			$user_id
		);
		
		Utilities::get_instance()->log( "SQL to check whether the interview was completed: {$sql}" );
		$count = $wpdb->get_var( $sql );
		
		if ( empty( $count ) || $count == 0 || $count < 11 ) {
			Utilities::get_instance()->log( "Not enough answers given: {$count}" );
			
			return false;
		}
		
		Utilities::get_instance()->log( "Interview has been completed" );
		
		return true;
	}
	
	/**
	 * Load client specific settings
	 *
	 * @param int $client_id
	 *
	 * @return bool
	 */
	public function load_client_settings( $client_id ) {
		
		if ( ! is_user_logged_in() || empty( $client_id ) ) {
			
			return false;
		}
		
		$Program = Program::getInstance();
		global $currentClient;
		global $currentProgram;
		
		global $wpdb;
		
		if ( empty( $currentProgram->id ) ) {
			
			$Program->getProgramIdForUser( $client_id );
		}
		
		if ( true === WP_DEBUG ) {
			Cache::delete( "e20r_client_info_{$client_id}_{$currentProgram->id}", 'e20rtracker' );
		}
		
		Utilities::get_instance()->log( "Loading client information from database for {$client_id} in {$currentProgram->id}" );
		
		if ( null === ( $currentClient = Cache::get( "e20r_client_info_{$client_id}_{$currentProgram->id}", 'e20rtracker' ) ) ) {
			
			Utilities::get_instance()->log( "Have to load client data for {$client_id} from the database" );
			$currentClient = new \stdClass();
			
			$sql = $wpdb->prepare( "
                SELECT id, user_id, program_id, page_id, program_start, progress_photo_dir, gender,
                       first_name, last_name, birthdate, lengthunits, weightunits
                FROM {$this->table}
                WHERE program_id = %d AND
                user_id = %d
                ORDER BY program_start DESC
                LIMIT 1",
				$currentProgram->id,
				$client_id
			);
			
			$records = $wpdb->get_row( $sql, ARRAY_A );
			
			if ( ! empty( $records ) ) {
				
				Utilities::get_instance()->log( "Populating the currentClient object..." );
				foreach ( $records as $key => $val ) {
					
					$currentClient->{$key} = $val;
				}
			}
			
			if ( ! empty( $currentClient->user_id ) ) {
				Cache::set( "e20r_client_info_{$client_id}_{$currentProgram->id}", $currentClient, HOUR_IN_SECONDS, 'e20rtracker' );
			}
			
		}
		
		if ( empty( $currentClient->user_id ) ) {
			$currentClient = $this->defaultSettings( $client_id );
		}
		
		Utilities::get_instance()->log( "currentClient object:" . print_r( $currentClient, true ) );
		
		// Return all of the data for this user.
		return $currentClient;
		
	}
	
	/**
	 * Generate the default settings for a new client/member/user
	 *
	 * @param null|int $user_id
	 *
	 * @return \stdClass
	 */
	public function defaultSettings( $user_id = null ) {
		
		global $currentClient;
		global $currentProgram;
		global $currentArticle;
		global $post;
		
		if ( empty( $currentClient->user_id ) && ! empty( $user_id ) ) {
			Program::getInstance()->getProgramIdForUser( $user_id );
		}
		
		$u_id       = ! empty( $currentClient->user_id ) ? $currentClient->user_id : ( empty( $currentClient->user_id ) && ! empty( $user_id ) ? $user_id : null );
		$program_id = ! empty( $currentClient->program_id ) ? $currentClient->program_id : ( ! empty( $currentProgram->id ) ? $currentProgram->id : - 1 );
		
		if ( ! empty( $u_id ) ) {
			$client = get_user_by( 'ID', $u_id );
		}
		
		$defaults                       = new \stdClass();
		$defaults->user_id              = $u_id;
		$defaults->coach_id             = $this->get_coach( $u_id, ! empty( $currentProgram->id ) ? $currentProgram->id : null );
		$defaults->program_id           = $program_id;
		$defaults->page_id              = ! empty( $post->ID ) ? $post->ID : CONST_NULL_ARTICLE;
		$defaults->article_id           = ! empty( $currentArticle->id ) ? $currentArticle->id : CONST_NULL_ARTICLE;
		$defaults->program_start        = $currentProgram->startdate;
		$defaults->progress_photo_dir   = "e20r_pics/client_{$program_id}_{$u_id}";
		$defaults->gender               = 'm';
		$defaults->incomplete_interview = true; // Will redirect the user to the interview page.
		$defaults->first_name           = isset( $client->first_name ) ? $client->first_name : null;
		$defaults->last_name            = isset( $client->last_name ) ? $client->last_name : null;
		$defaults->birthdate            = date( 'Y-m-d', current_time( 'timestamp' ) );
		$defaults->lengthunits          = 'in';
		$defaults->weightunits          = 'lbs';
		$defaults->loadedDefaults       = true;
		
		return $defaults;
		
	}
	
	/**
	 * Return the coach's User ID for the specified user/program combination
	 *
	 * @param null|int $client_id
	 * @param null|int $program_id
	 *
	 * @return string[]
	 */
	public function get_coach( $client_id = null, $program_id = null ) {
		
		global $currentProgram;
		
		global $wpdb;
		
		$saved       = $currentProgram;
		$user_roles  = apply_filters( 'e20r-tracker-configured-roles', array() );
		$coaches     = array();
		$coach_query = null;
		
		if ( is_null( $program_id ) && ( is_null( $client_id ) ) ) {
			Utilities::get_instance()->log( "Neither program nor user ID is defined. Get any user with a capability like {$user_roles['coach']['role']}" );
			$coach_query = array(
				'field'      => array( 'ID', 'display_name' ),
				'meta_query' => array(
					array(
						'key'     => $wpdb->prefix . 'capabilities',
						'value'   => $user_roles['coach']['role'],
						'compare' => 'LIKE',
					),
				),
			);
		} else if ( is_null( $client_id ) && ! is_null( $program_id ) ) {
			
			Utilities::get_instance()->log( "Program ID is defined. User ID isn't. Get all coaches who coach for program # {$program_id}" );
			$coach_query = array(
				'field'      => array( 'ID', 'display_name' ),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => $wpdb->prefix . 'capabilities',
						'value'   => $user_roles['coach']['role'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'e20r-tracker-coaching-program_ids',
						'value'   => $program_id,
						'compare' => 'IN',
					),
				),
			);
		} else if ( ! is_null( $client_id ) && is_null( $program_id ) ) {
			
			Utilities::get_instance()->log( "Program ID is NOT defined. User ID IS. Get all coaches who coach user with ID {$client_id}" );
			$coach_query = array(
				'field'      => array( 'ID', 'display_name' ),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => $wpdb->prefix . 'capabilities',
						'value'   => $user_roles['coach']['role'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'e20r-tracker-coaching-client_ids',
						'value'   => $client_id,
						'compare' => 'IN',
					),
				),
			);
			
		} else if ( ! is_null( $program_id ) && ( ! is_null( $client_id ) ) ) {
			Utilities::get_instance()->log( "Program ID is defined. User ID is defined. Get all coaches who coach for program # {$program_id} and user {$client_id}" );
			$coach_query = array(
				'field'      => array( 'ID', 'display_name' ),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => $wpdb->prefix . 'capabilities',
						'value'   => $user_roles['coach']['role'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'e20r-tracker-coaching-program_ids',
						'value'   => $program_id,
						'compare' => '=',
					),
					array(
						'key'     => 'e20r-tracker-coaching-client_ids',
						'value'   => $client_id,
						'compare' => '=',
					),
				),
			);
		}
		
		if ( ! is_null( $coach_query ) ) {
			
			Utilities::get_instance()->log( print_r( $coach_query, true ) );
			$results = new \WP_User_Query( $coach_query );
			
			Utilities::get_instance()->log( "Found " . $results->get_total() . " coaches..." );
			$found_coaches = $results->get_results();
			
			if ( ! empty( $found_coaches ) ) {
				
				$coaches = array();
				
				foreach ( $found_coaches as $coach ) {
					$coaches[ $coach->ID ] = $coach->display_name;
				}
			}
		}
		
		$currentProgram = $saved;
		
		return $coaches;
	}
	
	/**
	 * Save the client data
	 *
	 * @param array $clientData
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function saveData( $clientData ) {
		
		if ( ! is_user_logged_in() ) {
			
			return false;
		}
		
		global $wpdb;
		$Tables  = Tables::getInstance();
		$Tracker = Tracker::getInstance();
		
		try {
			$table = $Tables->getTable( 'client_info' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error fetching table: " . $exception->getMessage() );
			
			return false;
		}
		
		// These are the fields that won't get encrypted.
		$encData                       = array();
		$encData['user_id']            = isset( $clientData['user_id'] ) ? $clientData['user_id'] : null;
		$encData['program_id']         = isset( $clientData['program_id'] ) ? $clientData['program_id'] : - 1;
		$encData['article_id']         = isset( $clientData['article_id'] ) ? $clientData['article_id'] : CONST_NULL_ARTICLE;
		$encData['page_id']            = isset( $clientData['page_id'] ) ? $clientData['page_id'] : null;
		$encData['program_start']      = isset( $clientData['program_start'] ) ? $clientData['program_start'] : date( 'Y-m_d', current_time( 'timestamp' ) );
		$encData['progress_photo_dir'] = isset( $clientData['progress_photo_dir'] ) ? $clientData['progress_photo_dir'] : null;
		$encData['first_name']         = isset( $clientData['first_name'] ) ? $clientData['first_name'] : null;
		$encData['last_name']          = isset( $clientData['last_name'] ) ? $clientData['last_name'] : null;
		$encData['birthdate']          = isset( $clientData['birthdate'] ) ? $clientData['birthdate'] : date( 'Y-m-d', current_time( 'timestamp' ) );
		$encData['gender']             = isset( $clientData['gender'] ) ? $clientData['gender'] : 'm';
		$encData['lengthunits']        = isset( $clientData['lengthunits'] ) ? $clientData['lengthunits'] : 'in';
		$encData['weightunits']        = isset( $clientData['weightunits'] ) ? $clientData['weightunits'] : 'lbs';
		
		$exclude = array_keys( $encData );
		
		Utilities::get_instance()->log( "Encrypting client data." );
		
		foreach ( $clientData as $key => $value ) {
			
			if ( ! in_array( $key, $exclude ) ) {
				
				$encData[ $key ] = $value;
			}
		}
		
		if ( isset( $encData['coach_id'] ) ) {
			unset( $encData['coach_id'] );
		}
		
		if ( isset( $encData['incomplete_interview'] ) ) {
			unset( $encData['incomplete_interview'] );
		}
		
		if ( isset( $encData['loadedDefaults'] ) ) {
			unset( $encData['loadedDefaults'] );
		}
		
		Utilities::get_instance()->log( "Set the format for the fields " );
		try {
			$format = $Tracker->setFormatForRecord( $encData );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Problem with formatting for record. Error: " . $exception->getMessage() );
			
			return false;
		}
		
		Utilities::get_instance()->log( "Data to replace/save: " . print_r( $encData, true ) );
		
		if ( $wpdb->replace( $table, $encData, $format ) === false ) {
			
			Utilities::get_instance()->log( "Unable to save the client data: " . print_r( $wpdb->last_query, true ) );
			
			return false;
		}
		
		$this->data = $clientData;
		
		// Clear the cache for this user (to reload when needed)
		Cache::delete( "e20r_client_info_{$clientData['user_id']}_{$clientData['program_id']}", 'e20rtracker' );
		
		return true;
	}
	
	/**
	 * Save data from the Interview Form to the Database (and encrypt it)
	 *
	 * @param array $data
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function saveClientInterview( $data ) {
		
		if ( ! is_user_logged_in() ) {
			
			return false;
		}
		
		global $wpdb;
		$Tracker = Tracker::getInstance();
		
		if ( ! empty( $data['completion_date'] ) ) {
			$data['completed_date'] = date( 'Y-m-d H:i:s', strtotime( $data['completion_date'] . " " . date( 'H:i:s', current_time( 'timestamp' ) ) ) );
		} else {
			$data['completed_date'] = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		}
		// $id = false;
		
		Utilities::get_instance()->log( "Saving data to {$this->table}" );
		
		if ( ( $id = $this->recordExists( $data['user_id'], $data['program_id'], $data['page_id'], $data['article_id'] ) ) !== false ) {
			
			Utilities::get_instance()->log( "User/Program exists in the client info table. Editing an existing record: {$id}" );
			$data['edited_date'] = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
			$data['id']          = $id;
		}
		
		if ( isset( $data['started_date'] ) ) {
			
			unset( $data['started_date'] ); // $data['started_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
		}
		
		if ( false === $this->save_in_survey_table( $data ) ) {
			Utilities::get_instance()->log( "ERROR: Couldn't save in survey table!" );
			
			return false;
		}
		
		$to_save = array();
		foreach ( $this->clientinfo_fields as $field ) {
			
			// Clean (empty) the client_info for everything except what we need in order to load various forms, etc.
			$to_save[ $field ] = $data[ $field ];
		}
		
		if ( isset( $data['id'] ) ) {
			
			$to_save['id'] = $data['id'];
		}
		
		Utilities::get_instance()->log( "We will save the following data to the client_info table: " . print_r( $to_save, true ) );
		
		// Generate format array.
		$format = $Tracker->setFormatForRecord( $to_save );
		
		if ( false === $wpdb->replace( $this->table, $to_save, $format ) ) {
			
			global $EZSQL_ERROR;
			Utilities::get_instance()->log( print_r( $EZSQL_ERROR, true ) );
			
			Utilities::get_instance()->log( "Error inserting form data: " . $wpdb->print_error() );
			Utilities::get_instance()->log( "Query: " . print_r( $wpdb->last_query, true ) );
			
			return false;
		}
		
		Utilities::get_instance()->log( "Data saved..." );
		Cache::delete( "e20r_client_info_{$to_save['user_id']}_{$to_save['program_id']}", 'e20rtracker' );
		
		return true;
	}
	
	/**
	 * Check if the record for the user/program/article/post exists in the specified table
	 *
	 * @param int         $user_id
	 * @param int         $program_id
	 * @param null|int    $post_id
	 * @param null|int    $article_id
	 * @param null|string $table_name
	 *
	 * @return bool|int
	 */
	private function recordExists( $user_id, $program_id, $post_id = null, $article_id = null, $table_name = null ) {
		
		global $wpdb;
		
		if ( is_null( $table_name ) ) {
			
			$table_name = $this->table;
		}
		
		Utilities::get_instance()->log( "Checking whether {$table_name} record exists for {$user_id} in {$program_id}" );
		
		if ( ! is_null( $post_id ) ) {
			Utilities::get_instance()->log( "Including post ID in search" );
			$sql = $wpdb->prepare( "
                SELECT id
                FROM {$table_name}
                WHERE user_id = %d AND program_id = %d AND page_id = %d AND article_id = %d
            ",
				$user_id,
				$program_id,
				$post_id,
				$article_id
			);
		} else {
			Utilities::get_instance()->log( "NOT including post ID in search" );
			$sql = $wpdb->prepare( "
                SELECT id
                FROM {$table_name}
                WHERE user_id = %d AND program_id = %d AND article_id = %d
            ",
				$user_id,
				$program_id,
				$article_id
			);
		}
		
		$exists = $wpdb->get_var( $sql );
		
		if ( ! empty( $exists ) ) {
			
			Utilities::get_instance()->log( "Found record with id: {$exists}" );
			
			return (int) $exists;
		}
		
		return false;
	}
	
	/**
	 * Save Survey data to the e20r tracker survey data table
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function save_in_survey_table( $data ) {
		
		if ( ! is_user_logged_in() ) {
			
			return false;
		}
		
		global $currentProgram;
		global $currentArticle;
		global $current_user;
		$Tracker = Tracker::getInstance();
		$Tables  = Tables::getInstance();
		
		global $post;
		global $wpdb;
		
		$record = array();
		
		try {
			$table = $Tables->getTable( 'surveys' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error locating the surveys table: " . $exception->getMessage() );
			
			return false;
		}
		
		$fields  = $Tables->getFields( 'surveys' );
		$encrypt = (bool) $Tracker->loadOption( 'encrypt_surveys' );
		
		if ( ( ! empty( $data['program_id'] ) ) && ( $data['program_id'] != $currentProgram->id ) ) {
			
			$Program = Program::getInstance();
			Utilities::get_instance()->log( "Program ID in data vs currentProgram mismatch. Loading new program info" );
			
			$Program->init( $data['program_id'] );
		}
		
		$record["{$fields['program_id']}"] = ! empty( $data['program_id'] ) ? $data['program_id'] : $currentProgram->id;
		
		if ( ( ! empty( $data['article_id'] ) ) && ( $data['article_id'] != $currentArticle->id ) ) {
			
			$Article = Article::getInstance();
			Utilities::get_instance()->log( "Article ID in data vs currentArticle mismatch. Loading new article" );
			$Article->getSettings( $data['article_id'] );
		}
		
		$record["{$fields['article_id']}"] = ! empty( $data['article_id'] ) ? $data['article_id'] : $currentArticle->id;
		
		if ( $post->ID == $currentProgram->intake_form || ( $post->ID == $currentProgram->dashboard_page_id ) ) {
			Utilities::get_instance()->log( "Program config indicates we're saving a welcome survey." );
			$record["{$fields['survey_type']}"] = E20R_SURVEY_TYPE_WELCOME;
		} else {
			$record["{$fields['survey_type']}"] = E20R_SURVEY_TYPE_OTHER;
		}
		
		$for_date_ts                     = strtotime( $Tracker->getDateFromDelay( $currentArticle->release_day, $data['user_id'] ) );
		$record["{$fields['for_date']}"] = date_i18n( 'Y-m-d H:i:s', $for_date_ts );
		
		if ( ! empty( $data['user_id'] ) ) {
			Utilities::get_instance()->log( "User ID: {$data['user_id']}" );
			
			$record["{$fields['user_id']}"] = $data['user_id'];
		} else {
			$record["{$fields['user_id']}"] = $current_user->ID;
		}
		
		if ( ! empty( $data['completion_date'] ) ) {
			$record["{$fields['completed']}"] = date_i18n( 'Y-m-d H:i:s', strtotime( $data['completion_date'] ) );
		}
		
		$survey_data = array();
		
		foreach ( $data as $key => $value ) {
			
			if ( ! in_array( $key, $this->clientinfo_fields ) ) {
				
				$survey_data[ $key ] = $value;
			}
		}
		
		if ( true === $encrypt ) {
			Utilities::get_instance()->log( "Enable data encryption for user {$record['user_id']}" );
			$record["{$fields['is_encrypted']}"] = true;
		} else {
			Utilities::get_instance()->log( "WARNING: Won't enable data encryption for user {$record['user_id']}" );
			$record["{$fields['is_encrypted']}"] = false;
		}
		
		Utilities::get_instance()->log( "Loading the encryption key for the end user" );
		
		$record["{$fields['survey_data']}"] = Tracker_Crypto::encryptData( $data['user_id'], $survey_data );
		
		// Check whether the surveys table already contains the record we're trying to save.
		if ( ( $id = $this->recordExists( $record['user_id'], $record['program_id'], null, $record['article_id'], $table ) ) !== false ) {
			
			Utilities::get_instance()->log( 'TrackerModel::save_in_survey_table() - User/Program exists in the client info table. Editing existing record.' );
			$record["{$fields['id']}"] = $id;
		}
		
		try {
			$format = $Tracker->setFormatForRecord( $record );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to generate a column format array for the record: " . $exception->getMessage() );
			
			return false;
		}
		
		if ( false === $wpdb->replace( $table, $record, $format ) ) {
			
			global $EZSQL_ERROR;
			Utilities::get_instance()->log( print_r( $EZSQL_ERROR, true ) );
			
			Utilities::get_instance()->log( "TrackerModel::save_in_survey_table() - Error inserting form data: " . $wpdb->print_error() );
			Utilities::get_instance()->log( "TrackerModel::save_in_survey_table() - Query: " . print_r( $wpdb->last_query, true ) );
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Save the length/weight unit info for the user (to their survey table entry)
	 *
	 * @param string $length_unit
	 * @param string $weight_unit
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function saveUnitInfo( $length_unit, $weight_unit ) {
		
		global $wpdb;
		global $current_user;
		
		global $currentClient;
		
		if ( ! isset( $currentClient->user_id ) || ( ! isset( $currentClient->programId ) ) ) {
			
			$this->setUser( $current_user->ID );
		}
		
		if ( $wpdb->update( $this->table,
				array( 'lengthunits' => $length_unit, 'weightunits' => $weight_unit ),
				array( 'user_id' => $currentClient->user_id, 'program_id' => $currentClient->programId ),
				array( '%d' ) ) === false ) {
			
			Utilities::get_instance()->log( "Error updating unit info: " . $wpdb->print_error() );
			throw new \Exception( "Error updating weight/length units: " . $wpdb->print_error() );
		}
		
		// Clear the cache for this user
		Cache::delete( "e20r_client_info_{$currentClient->user_id}_{$currentClient->programId}", 'e20rtracker' );
		
		return true;
	}
	
	/**
	 * Set/Configure the user's Client Info
	 *
	 * @param int $user_id the user's ID number
	 */
	public function setUser( $user_id ) {
		
		global $currentClient;
		$Program = Program::getInstance();
		
		if ( $user_id != $currentClient->user_id || empty( $currentClient->program_id ) ) {
			Utilities::get_instance()->log( "Resetting currentUser in preparation to load info for {$user_id}" );
			
			$currentClient = $this->defaultSettings();
			
			$currentClient->user_id    = $user_id;
			$currentClient->program_id = $Program->getProgramIdForUser( $currentClient->user_id );
		}
	}
	
	/**
	 * Return the URL for a user who belongs to the Nourish Beta tester group
	 *
	 * @param int    $who        - User ID
	 * @param string $when       - Date of captured measurement
	 * @param string $image_side -- Front/Side/Back
	 *
	 * @return mixed - URL to image
	 */
	public function getBetaUserUrl( $who, $when, $image_side ) {
		
		global $wpdb;
		
		$Tables = Tables::getInstance();
		
		if ( $Tables->isBetaClient() ) {
			
			Utilities::get_instance()->log( "User with ID {$who} IS a member of the Nourish BETA group" );
			
			switch ( $image_side ) {
				
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
			
			$sql = $wpdb->prepare( "SELECT gf_detail.value as URL
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
				date( 'Y-m-d', strtotime( $when ) + ( 24 * 60 * 60 ) ),
				$fid
			);
			
			// Utilities::get_instance()->log("SQL: {$sql}");
			
			$imageLink = $wpdb->get_row( $sql );
			
			if ( empty( $imageLink ) ) {
				$imageUrl = E20R_PLUGINS_URL . '/img/no-image-uploaded.jpg';
			} else {
				$imageUrl = $imageLink->URL;
			}
		} else {
			
			Utilities::get_instance()->log( "User with ID {$who} is NOT a member of the Nourish BETA group" );
			$imageUrl = false;
			
		}
		
		return $imageUrl;
	}
	
	/**
	 * Return the list of clients that have been assigned to a coach for a program
	 *
	 * @param null|int $coach_id
	 * @param null|int $program_id
	 *
	 * @return \WP_User[]
	 */
	public function get_clients( $coach_id = null, $program_id = null ) {
		
		Utilities::get_instance()->log( "Loading all of coach #{$coach_id} clients for program (ID: {$program_id})" );
		$uList = array();
		
		if ( ! empty( $coach_id ) ) {
			$client_list = get_user_meta( $coach_id, 'e20r-tracker-client-program-list', true );
			
			if ( ! is_null( $program_id ) && ( isset( $client_list[ $program_id ] ) ) ) {
				
				Utilities::get_instance()->log( "Found client list for program with ID {$program_id}" );
				$uList[ $program_id ] = $client_list[ $program_id ];
			} else if ( is_null( $program_id ) ) {
				
				Utilities::get_instance()->log( "No program ID specified. Returning all clients " );
				if ( empty( $client_list ) ) {
					$uList[] = array();
				} else {
					$uList = $client_list;
				}
				
			} else {
				$uList = array();
			}
			
			$programs = array();
			
			if ( empty( $uList ) ) {
				
				$programs[] = array();
			}
			
			foreach ( $uList as $pgmId => $userList ) {
				
				Utilities::get_instance()->log( "Program {$pgmId} has the following users associated: " . print_r( $userList, true ) );
				
				$args = array(
					'include' => $userList,
					'fields'  => array( 'ID', 'display_name' ),
				);
				
				$programs[ $pgmId ] = get_users( $args );
			}
			
			Utilities::get_instance()->log( "fetched user info: " . print_r( $programs, true ) );
			
		} else {
			
			$programs   = array();
			$programs[] = array();
		}
		Utilities::get_instance()->log( "Found " . count( $programs ) . " programs for coach: {$coach_id}" );
		
		return $programs;
	}
	
	/**
	 * Return survey data (or survey item by name) for the user
	 *
	 * @param int         $user_id
	 * @param null|string $item_name
	 * @param null|int    $article_id
	 *
	 * @return bool
	 */
	public function get_data( $user_id, $item_name = null, $article_id = null ) {
		
		if ( ! is_user_logged_in() ) {
			
			return false;
		}
		
		$Tracker = Tracker::getInstance();
		$Program = Program::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		global $current_user;
		global $currentProgram;
		global $currentClient;
		
		Utilities::get_instance()->log( $Tracker->whoCalledMe() );
		
		if ( empty( $currentProgram->id ) && empty( $user_id ) ) {
			Utilities::get_instance()->log( "No client or program info configured" );
			
			return false;
		}
		
		$Program->getProgramIdForUser( $user_id );
		
		if ( ( $currentClient->user_id != $user_id ) && ( $Access->is_a_coach( $current_user->ID ) ) ) {
			
			Utilities::get_instance()->log( "Loading client information from database for client ID {$user_id}" );
			$this->setUser( $user_id );
		}
		
		// No item specified, returning everything we have.
		if ( empty( $item_name ) ) {
			
			Utilities::get_instance()->log( "Loading client information from database for {$currentClient->user_id} and program {$currentProgram->id}" );
			$this->load_data( $currentClient->user_id, $currentProgram->id );
			
			// Return all of the data for this user.
			return $currentClient;
			
		} else {
			
			if ( empty( $currentClient->{$item_name} ) ) {
				
				Utilities::get_instance()->log( "Requested Item ({$item_name}) for {$currentClient->user_id} not found. Reloading.." );
				// $program_id = $Program->getProgramIdForUser( $currentClient->user_id );
				$this->load_data( $currentClient->user_id, $currentProgram->id );
			}
			
			// Utilities::get_instance()->log( "Requested Item ({$item}) found for user {$currentClient->user_id}" );
			// Only return the specified item value.
			return ( empty( $currentClient->{$item_name} ) ? false : $currentClient->{$item_name} );
		}
	}
	
	/**
	 * Load the client's survey data and other related information from the database
	 *
	 * @param int         $client_id
	 * @param null|int    $program_id
	 * @param null|string $table
	 *
	 * @return bool|\stdClass
	 */
	private function load_data( $client_id, $program_id = null, $table = null ) {
		
		if ( ! is_user_logged_in() ) {
			
			return false;
		}
		
		global $post;
		
		global $currentProgram;
		global $currentClient;
		global $currentArticle;
		
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		$Article = Article::getInstance();
		
		Utilities::get_instance()->log( "For {$client_id}: " . $Tracker->whoCalledMe() );
		
		if ( empty( $currentClient->user_id ) || ( $client_id != $currentClient->user_id ) ) {
			
			Utilities::get_instance()->log( "WARNING: Loading data for a different client/user. Was: {$currentClient->user_id}, will be: {$client_id}" );
			$this->setUser( $client_id );
		}
		
		if ( empty( $currentProgram->id ) || ( $currentProgram->id != $program_id ) ) {
			
			Utilities::get_instance()->log( "WARNING: Loading data for a different program: {$program_id} vs {$currentProgram->id}" );
			$currentClient->program_id = $Program->getProgramIdForUser( $currentClient->user_id );
			Utilities::get_instance()->log( "WARNING: Program data is now for {$currentProgram->id}" );
		}
		
		Utilities::get_instance()->log( "Loading currentClient structure" );
		
		if ( is_null( $table ) ) {
			
			$table = $this->table;
		}
		
		if ( WP_DEBUG ) {
			Utilities::get_instance()->log( "Clear cache while in debug mode" );
			Cache::delete( "e20r_client_info_{$client_id}_{$currentClient->program_id}", 'e20rtracker' );
		}
		
		if ( null === ( $tmpData = Cache::get( "e20r_client_info_{$client_id}_{$currentProgram->id}", 'e20rtracker' ) ) ) {
			
			Utilities::get_instance()->log( "Client data wasn't cached. Loading from DB." );
			$records = $this->load_basic_clientdata( $client_id, $program_id, $table );
			
			if ( ! empty( $records ) ) {
				
				if ( ! empty( $records['page_id'] ) ) {
					
					$post_id = $records['page_id'];
				} else {
					$post_id = $post->ID;
				}
				
				/*
				if ( CONST_NULL_ARTICLE == $post_id ) {

					return $currentClient;
				}
				*/
				Utilities::get_instance()->log( "Have a post/page id ({$post_id}) to search for the article on behalf of" );
				$articles = $Article->findArticles( 'post_id', $post_id, $currentClient->program_id );
				
				foreach ( $articles as $article ) {
					
					if ( in_array( $currentClient->program_id, $article->program_ids ) && ( 1 == $article->is_survey ) ) {
						
						Utilities::get_instance()->log( "Found article for the survey." );
						$currentArticle = $article;
						break;
					}
				}
				
				if ( empty( $currentArticle->id ) ) {
					$article_id = CONST_NULL_ARTICLE;
				} else {
					$article_id = $currentArticle->id;
				}
				
				Utilities::get_instance()->log( "Found client data in DB for user {$currentClient->user_id} and program {$currentClient->program_id}." );
				
				$currentClient->loadedDefaults = false;
				
				// Load the relevant survey record (for this article/assignment/page)
				Utilities::get_instance()->log( "Load data from survey table for user {$currentClient->user_id} and program {$currentClient->program_id}" );
				
				if ( false ===
				     ( $survey = $this->load_from_survey_table( $client_id, $currentProgram->id, $article_id ) ) ) {
					return false;
				}
				
				Utilities::get_instance()->log( "Fetched Survey record: " );
				// Utilities::get_instance()->log($survey);
				
				$result = array();
				
				// Merge the survey data with the client_info data.
				foreach ( $records as $key => $value ) {
					
					$result[ $key ] = $value;
				}
				
				foreach ( $survey as $key => $value ) {
					
					$result[ $key ] = $value;
				}

//                 $result = array_merge( $survey, $records );
				
				Utilities::get_instance()->log( "Merged Survey record with client_info record: " );
				// Utilities::get_instance()->log($result);
				
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
				
				Utilities::get_instance()->log( "Client interview has been completed." );
				$currentClient->incomplete_interview = false;
			}
			
			if ( ! empty( $currentClient ) ) {
				Cache::set( "e20r_client_info_{$client_id}_{$currentClient->program_id}", $currentClient, 3600, 'e20rtracker' );
			}
			
			// Restore the original User ID.
			// $this->setUser( $oldId );
		} else if ( ! empty( $tmpData ) ) {
			$currentClient = $tmpData;
		}
		
		return $currentClient;
	}
	
	/**
	 * Return the basic settings/data for the specified client/program combination
	 *
	 * @param int         $client_id
	 * @param null|int    $program_id
	 * @param null|string $table
	 *
	 * @return array
	 */
	public function load_basic_clientdata( $client_id, $program_id = null, $table = null ) {
		
		global $wpdb;
		global $currentClient;
		
		Utilities::get_instance()->log( "Loading default currentClient structure" );
		
		// Init the unencrypted structure and load defaults.
		$this->setUser( $client_id );
		
		if ( is_null( $table ) ) {
			
			$table = $this->table;
		}
		
		// $key = E20R_Crypto::getUserKey( $currentClient->user_id );
		Utilities::get_instance()->log( "Client data wasn't cached. Loading from DB." );
		
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
		
		return $records;
	}
	
	/**
	 * Load the user's current survey data for the program/user id combination from the database (decrypt if needed)
	 *
	 * @param int $client_id
	 * @param int $program_id
	 * @param int $article_id
	 *
	 * @return bool|mixed
	 */
	private function load_from_survey_table( $client_id, $program_id, $article_id ) {
		
		if ( ! is_user_logged_in() ) {
			
			return false;
		}
		
		Utilities::get_instance()->log( "Start..." );
		global $currentProgram;
		global $currentArticle;
		global $current_user;
		
		$Tracker = Tracker::getInstance();
		$Tables  = Tables::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		global $wpdb;
		global $post;
		
		try {
			$table = $Tables->getTable( 'surveys' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to locate surveys table: " . $exception->getMessage() );
			
			return false;
		}
		
		$fields = $Tables->getFields( 'surveys' );
		
		if ( ( is_admin() && $Access->is_a_coach( $current_user->ID ) ) ||
		     ( $post->ID == $currentProgram->intake_form ) || ( $post->ID == $currentProgram->measurements_page_id ) ||
		     ( has_shortcode( $post->post_content, 'e20r_profile' ) ) ) {
			
			Utilities::get_instance()->log( "Program config indicates we're loading for a welcome survey." );
			$survey_type = E20R_SURVEY_TYPE_WELCOME;
		} else {
			$survey_type = E20R_SURVEY_TYPE_OTHER;
		}
		
		Utilities::get_instance()->log( "Survey type is {$survey_type}" );
		
		$current_date_ts    = strtotime( $Tracker->getDateFromDelay( $currentArticle->release_day, $client_id ) );
		$record['for_date'] = date_i18n( 'Y-m-d H:i:s', $current_date_ts );
		
		$sql = $wpdb->prepare(
			"SELECT *
                FROM {$table}
                WHERE {$fields['user_id']} = %d AND
                {$fields['survey_type']} = %d AND
                {$fields['program_id']} = %d
          ",
			$client_id,
			$survey_type,
			$program_id
		);
		
		Utilities::get_instance()->log( $sql );
		$records = $wpdb->get_results( $sql, ARRAY_A );
		
		if ( ! empty( $records ) ) {
			
			if ( count( $records ) > 1 ) {
				Utilities::get_instance()->log( "WARNING: More than one record returned for this program/article/user combination (not supposed to happen!)" );
				
				return false;
			} else {
				
				foreach ( $records as $record ) {
					
					$maybe_encrypted_survey = $record["{$fields['survey_data']}"];
					
					if ( true === (bool) $record["{$fields['is_encrypted']}"] ) {
						
						Utilities::get_instance()->log( "WARNING: Survey data is encrypted. Decrypting it for user {$client_id}" );
						
						if ( false ===
						     Tracker_Crypto::maybeUpdateEncryption(
							     $client_id,
							     $maybe_encrypted_survey,
							     $record['id'] )
						) {
							Utilities::get_instance()->log( "Error: Unable to update encryption for {$client_id}!!!" );
							
							return false;
						}
						
						Utilities::get_instance()->log( "Decrypting data for user {$client_id} " );
						$survey = Tracker_Crypto::decryptData( $client_id, $maybe_encrypted_survey, $maybe_encrypted_survey );
						
					} else {
						Utilities::get_instance()->log( "Survey data is NOT encrypted. Nothing to decrypt" );
						$survey = unserialize( $maybe_encrypted_survey );
					}
					
					Utilities::get_instance()->log( "Retrieved and decrypted " . count( $survey ) . " survey fields" );
					
					return $survey;
				}
			}
		}
		
		Utilities::get_instance()->log( "No survey records found for user ID {$client_id} in program {$program_id} for article {$article_id} " );
		
		return false;
	}
	
	/**
	 * Empty cached value(s) for the current user/program combination
	 */
	private function clearTransients() {
		
		global $currentClient;
		
		Utilities::get_instance()->log( "Resetting cache & transients" );
		delete_transient( "e20r_client_info_{$currentClient->user_id}_{$currentClient->program_id}" );
	}
	
}
