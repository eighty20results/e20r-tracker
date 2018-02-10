<?php

namespace E20R\Tracker\Controllers;
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

define( "E20R_ASSIGNMENT_META_VER", 1 );

use E20R\Tracker\Models\Assignment_Model;
use E20R\Tracker\Views\Assignment_View;

use E20R\Tracker\Models\Tables;

class Assignment extends Settings {
	
	/**
	 * @var null|Assignment
	 */
	private static $instance = null;
	/**
	 * @var Assignment_Model
	 */
	protected $model;
	/**
	 * @var Assignment_View
	 */
	protected $view;
	
	/**
	 * Assignment constructor.
	 */
	public function __construct() {
		
		E20R_Tracker::dbg( "Assignment::__construct() - Initializing Assignment class" );
		
		$this->model = new Assignment_Model();
		$this->view  = new Assignment_View();
		
		parent::__construct( 'assignments', Assignment_Model::post_type, $this->model, $this->view );
	}
	
	/**
	 * @return Assignment
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate a default assignment for the Article
	 *
	 * @param \stdClass $article
	 *
	 * @return bool|int|\WP_Error
	 */
	public function createDefaultAssignment( $article ) {
		
		E20R_Tracker::dbg( "Assignment::createDefaultAssignment() - Loading a dummy title based on the article ID " );
		
		$title = get_the_title( $article->id );
		
		if ( false !== ( $assignmentId = $this->model->createDefaultAssignment( $article, $title ) ) ) {
			
			E20R_Tracker::dbg( "Assignment::createDefaultAssignment() - Created new default assignment with ID: {$assignmentId} " );
		}
		
		return $assignmentId;
	}
	
	public function getAssignmentsByArticleId( $articleId ) {
		
		return $this->model->getArticleAssignments( $articleId );
	}
	
	public function get_assignment_question( $assignment_id ) {
		
		return $this->model->get_assignment_question( $assignment_id );
	}
	
	public function getInputType( $id ) {
		
		return $this->model->getInputType( $id );
	}
	
	public function loadAssignment( $assignmentId = null ) {
		
		global $currentAssignment;
		
		if ( ! isset( $currentAssignment->id ) || ( $currentAssignment->id != $assignmentId ) ) {
			
			$currentAssignment     = $this->model->loadSettings( $assignmentId );
			$currentAssignment->id = $assignmentId;
			
			E20R_Tracker::dbg( "Assignment::init() - Loaded settings for {$currentAssignment->id}" );
		}
		
		return $currentAssignment;
	}
	
	public function saveAssignment( $aArray ) {
		
		E20R_Tracker::dbg( 'Assignment::saveAssignment() - Saving assignment data for user... ' );
		
		return $this->model->saveUserAssignment( $aArray );
	}
	
	public function load_userAssignment( $articleID, $assignmentId, $userId = null ) {
		
		$Article = Article::getInstance();
		global $currentArticle;
		
		if ( is_null( $currentArticle ) || ( $currentArticle->id != $articleID ) ) {
			
			E20R_Tracker::dbg( "Assignment::load_userAssignment() - Loading article settings for {$articleID}" );
			$Article->init( $articleID );
			
		}
		
		$delay = $Article->releaseDay( $articleID );
		
		// return $this->model->loadUserAssignment( $articleID, $userId, $delay, $assignmentId );
		return $this->model->load_user_assignment_info( $userId, $assignmentId, $articleID );
	}
	
	public function manage_option_list() {
		
		$Tracker = Tracker::getInstance();
		
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Checking ajax referrer privileges" );
		check_ajax_referer( 'e20r-assignment-data', 'e20r-tracker-assignment-settings-nonce' );
		
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Checking ajax referrer has the right privileges" );
		
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		
		E20R_Tracker::dbg( $_POST );
		
		$post_id    = isset( $_POST['e20r-assignment-question_id'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-question_id'] ) : null;
		$field_type = isset( $_POST['e20r-assignment-field_type'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-field_type'] ) : null;;
		$order_num   = isset( $_POST['e20r-assignment-order_num'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-order_num'] ) : null;
		$delay       = isset( $_POST['e20r-assignment-delay'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-delay'] ) : null;
		$program_ids = isset( $_POST['e20r-assignment-program_ids'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-program_ids'] ) : array();
		$operation   = isset( $_POST['operation'] ) ? $Tracker->sanitize( $_POST['operation'] ) : null;
		
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Post ID for assignment is: {$post_id}" );
		
		if ( empty( $post_id ) ) {
			
			E20R_Tracker::dbg( "Assignment::manage_option_list() - This is a new post. Ask user to click 'Publish'" );
			wp_send_json_error( array( 'errno' => - 9999 ) );
			exit();
		}
		
		$settings = $this->model->loadSettings( $post_id );
		
		if ( ! is_null( $field_type ) && ( 4 != $settings->field_type ) ) {
			E20R_Tracker::dbg( "Assignment::manage_option_list() - Update field type from {$settings->field_type} to 4" );
			$settings->field_type = 4;
		}
		
		if ( ! is_null( $order_num ) && ( $order_num != $settings->order_num ) ) {
			E20R_Tracker::dbg( "Assignment::manage_option_list() - Update order_num to {$order_num}" );
			$settings->order_num = $order_num;
		}
		
		if ( $delay != $settings->delay ) {
			E20R_Tracker::dbg( "Assignment::manage_option_list() - Update delay to {$delay}" );
			$settings->delay = $delay;
		}
		
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Settings loaded for assignment {$settings->id}" );
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Requested operation: {$operation}" );
		
		if ( is_null( $operation ) ) {
			
			E20R_Tracker::dbg( "Assignment::manage_option_list() - Error: No operation requested!" );
			wp_send_json_error( array( 'errno' => - 1 ) );
			exit();
		}
		
		$existing_options = isset( $_POST['e20r-assignment-select_options'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-select_options'] ) : array();
		$new_option       = isset( $_POST['e20r-new-assignment-option'] ) ? $Tracker->sanitize( $_POST['e20r-new-assignment-option'] ) : null;
		
		switch ( $operation ) {
			case 'save':
				
				if ( empty( $new_option ) ) {
					E20R_Tracker::dbg( "Assignment::manage_option_list() - Error: Requested add operation, but no new option was supplied" );
					wp_send_json_error( array( 'errno' => - 2 ) );
					exit();
				}
				
				$existing_options[] = $new_option;
				break;
			
			case 'delete':
				
				$to_delete = isset( $_POST['e20r-delete-assignments'] ) ? $Tracker->sanitize( $_POST['e20r-delete-assignments'] ) : array();
				
				foreach ( $to_delete as $dVal ) {
					
					if ( ( $key = array_search( $dVal, $existing_options ) ) !== false ) {
						E20R_Tracker::dbg( "Assignment::manage_option_list() - Removing option #{$key}: {$existing_options[$key]}" );
						unset( $existing_options[ $key ] );
					}
				}
				break;
		}
		
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Current list of options: " );
		E20R_Tracker::dbg( $existing_options );
		
		if ( empty( $existing_options ) ) {
			
			E20R_Tracker::dbg( "Assignment::manage_option_list() - Empty list of existing options. Resetting." );
			$existing_options = array();
		}
		
		$settings->select_options = $existing_options;
		
		if ( $this->saveSettings( $post_id, $settings ) ) {
			
			$html = $this->view->viewOptionListTable( $settings );
			
			E20R_Tracker::dbg( "Assignment::manage_option_list() - Saved settings of assignment {$settings->id}" );
			wp_send_json_success( array( 'html' => $html ) );
			exit();
		}
		
		E20R_Tracker::dbg( "Assignment::manage_option_list() - Returning error because we didn't exist gracefully." );
		wp_send_json_error( array( 'errno' => - 3 ) );
		exit();
		
	}
	
	public function saveSettings( $assignmentId, $settings = null ) {
		
		$Tracker = Tracker::getInstance();
		global $post;
		
		if ( empty( $assignmentId ) ) {
			E20R_Tracker::dbg( "Assignment::saveSettings() - No Assignment ID supplied" );
			
			return false;
		}
		
		if ( is_null( $settings ) && ( ( ! isset( $post->post_type ) ) || ( $post->post_type !== Assignment_Model::post_type ) ) ) {
			
			E20R_Tracker::dbg( "Assignment::saveSettings() - Not an assignment. " );
			
			return $assignmentId;
		}
		
		if ( empty( $assignmentId ) ) {
			
			E20R_Tracker::dbg( "Assignment::saveSettings() - No post ID supplied" );
			
			return false;
		}
		
		if ( wp_is_post_revision( $assignmentId ) ) {
			
			return $assignmentId;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			
			return $assignmentId;
		}
		
		$savePost = $post;
		
		if ( empty( $settings ) ) {
			
			E20R_Tracker::dbg( "Assignment::saveSettings()  - Saving metadata from edit.php page, related to the Assignment post_type" );
			
			$this->model->init( $assignmentId );
			
			$settings = $this->model->loadSettings( $assignmentId );
			$defaults = $this->model->defaultSettings();
			
			if ( ! $settings ) {
				
				$settings = $defaults;
			}
			
			foreach ( $settings as $field => $setting ) {
				
				$tmp = isset( $_POST["e20r-assignment-{$field}"] ) ? $Tracker->sanitize( $_POST["e20r-assignment-{$field}"] ) : null;
				
				E20R_Tracker::dbg( "Assignment::saveSettings() - Page data : {$field} -> " );
				E20R_Tracker::dbg( $tmp );
				
				if ( is_null( $tmp ) ) {
					
					$tmp = $defaults->{$field};
					
				}
				
				$settings->{$field} = $tmp;
			}
			
			// Add post ID (checkin ID)
			$settings->id          = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;
			$settings->question_id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;
			
			if ( ! empty( $settings->select_options ) && ( 4 != $settings->field_type ) ) {
				
				E20R_Tracker::dbg( "Assignment::saveSettings() - Forcing select_options to null..." );
				$settings->select_options = null;
			}
			
			E20R_Tracker::dbg( "Assignment::saveSettings() - Saving: " . print_r( $settings, true ) );
			
			if ( ! $this->model->saveSettings( $settings ) ) {
				
				E20R_Tracker::dbg( "Assignment::saveSettings() - Error saving settings!" );
				
				return false;
			}
			
		} else if ( get_class( $settings ) != 'WP_Post' ) {
			
			E20R_Tracker::dbg( "Assignment::saveSettings() - Received settings from calling function." );
			
			if ( ! $this->model->saveSettings( $settings ) ) {
				
				E20R_Tracker::dbg( "Assignment::saveSettings() - Error saving settings!" );
			}
			
			$post = get_post( $assignmentId );
			
			setup_postdata( $post );
			
			$this->model->set( 'question', get_the_title() );
			$this->model->set( 'descr', get_the_excerpt() );
			
			wp_reset_postdata();
			
		}
		
		$post = $savePost;
		
		return true;
	}
	
	public function listUserSurveys( $userId ) {
		// TODO: Complete implementation of this function (Question: What is the survey we're looking for, an Assignment Survey?)
	}
	
	public function update_message_status() {
		
		$Tracker = Tracker::getInstance();
		$Tables  = Tables::getInstance();
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-assignment-nonce' );
		
		E20R_Tracker::dbg( "Assignment::update_message_status() - Showing the content of the REQUEST" );
		
		$message_ids    = isset( $_POST['message-id'] ) ? json_decode( stripslashes( $_POST['message-id'] ) ) : null;
		$message_status = isset( $_POST['message-status'] ) ? intval( $_POST['message-status'] ) : false;
		$status_type    = isset( $_POST['status-type'] ) ? $Tracker->sanitize( $_POST['status-type'] ) : null;
		
		E20R_Tracker::dbg( "Assignment::update_message_status() - Message ids: " . print_r( $message_ids, true ) );
		E20R_Tracker::dbg( "Assignment::update_message_status() - Status type: {$status_type}" );
		
		if ( ! is_null( $status_type ) ) {
			
			$fields = $Tables->getFields( 'response' );
			
			switch ( $status_type ) {
				case 'archive':
					$status_field = $fields['archived'];
					break;
				
				case 'read':
					$status_field = $fields['message_read'];
					break;
			}
		}
		
		if ( ! empty( $message_ids ) ) {
			
			E20R_Tracker::dbg( "Assignment::update_message_status() - Found message ids to update status for!" );
			
			foreach ( $message_ids as $message_id ) {
				
				if ( false === $this->model->update_reply_status( $message_id, $message_status, $status_field ) ) {
					
					E20R_Tracker::dbg( "Assignment::update_message_status() - Error updating status for record #{$message_id}" );
					
					wp_send_json_error(
						array(
							'message' =>
								sprintf( __( "Error: Unable to update the status for message: (#%s}).", "e20r-tracker" ), $message_id ),
						)
					);
					exit();
				}
			}
		}
		
		E20R_Tracker::dbg( "Assignment::update_message_status() - Completed status update..." );
		wp_send_json_success();
		exit();
	}
	
	public function add_assignment_reply() {
		
		$Tracker = Tracker::getInstance();
		global $current_user;
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-assignment-nonce' );
		
		E20R_Tracker::dbg( "Assignment::add_assignment_reply() - Showing the content of the REQUEST" );
		E20R_Tracker::dbg( $_POST );
		
		$data                  = array();
		$data['assignment_id'] = isset( $_POST['assignment-id'] ) ? $Tracker->sanitize( $_POST['assignment-id'] ) : null;
		$data['article_id']    = isset( $_POST['article-id'] ) ? $Tracker->sanitize( $_POST['article-id'] ) : null;
		$data['program_id']    = isset( $_POST['program-id'] ) ? $Tracker->sanitize( $_POST['program-id'] ) : null;
		$data['client_id']     = isset( $_POST['client-id'] ) ? $Tracker->sanitize( $_POST['client-id'] ) : null;
		$data['recipient_id']  = isset( $_POST['recipient-id'] ) ? $Tracker->sanitize( $_POST['recipient-id'] ) : null;
		$data['sent_by_id']    = isset( $_POST['sent-by-id'] ) ? $Tracker->sanitize( $_POST['sent-by-id'] ) : null;
		$data['replied_to']    = isset( $_POST['replied-to'] ) ? $Tracker->sanitize( $_POST['replied-to'] ) : null;
		$data['message_time']  = isset( $_POST['message-date'] ) ? $Tracker->sanitize( $_POST['message-date'] ) : current_time( 'mysql' );
		$data['message']       = isset( $_POST['reply-text'] ) ? wpautop( sanitize_text_field( $_POST['reply-text'] ) ) : null;
		$data['message_read']  = 0;
		$data['archived']      = 0;
		
		$delay = isset( $_POST['assignment-delay'] ) ? $Tracker->sanitize( $_POST['assignment-delay'] ) : null;
		
		if ( is_null( $data['assignment_id'] ) || is_null( $data['article_id'] ) || is_null( $data['program_id'] ) || is_null( $data['message_time'] ) || is_null( $data['message'] ) ) {
			
			E20R_Tracker::dbg( "Assignment::add_assignment_reply() - ERROR: Missing data from front-end!!" );
			wp_send_json_error( array( 'message' => sprintf( __( "Error: Unable to save assignment reply for assignment ID %d and user ID %d", "e20r-tracker" ), $data['assignment_id'], $data['user_id'] ) ) );
			exit();
		}
		
		// $am_coach = $Tracker->is_a_coach($current_user->ID);
		
		E20R_Tracker::dbg( "Assignment::add_assignment_reply() - We have data to process/update for {$data['client_id']}" );
		// $existing_assignment = $this->model->loadUserAssignment( $data['article_id'], $data['user_id'], $delay , $data['assignment_id'] );
		$existing_assignment = $this->model->load_user_assignment_info( $data['client_id'], $data['assignment_id'], $data['article_id'] );
		
		if ( empty( $existing_assignment ) ) {
			
			E20R_Tracker::dbg( "Assignment::add_assignment_reply() - ERROR: No previously existing assignment found, so shouldn't have been possible!" );
			wp_send_json_error( array( 'message' => __( "Error: No assignment found to reply to!", "e20r-tracker" ) ) );
			exit();
		}
		
		if ( count( $existing_assignment ) > 1 ) {
			E20R_Tracker::dbg( "Assignment::add_assignment_reply() - ERROR: More than a single assignment record found for user/assignment" );
			wp_send_json_error( array( 'message' => sprintf( __( "Error: Multiple (%d) assignments found for %d reply for assignment ID %d and user %d", "e20r-tracker" ), count( $existing_assignment ), $data['assignment_id'], $data['user_id'] ) ) );
			exit();
		}
		
		$assignment_info = array_pop( $existing_assignment );
		
		// if ( !empty( $assignment_info->id) ) {
		$data['record_id'] = isset( $assignment_info->id ) ? $assignment_info->id : null;
		// }
		
		E20R_Tracker::dbg( 'Assignment::add_assignment_reply() - Assignment reply data: ' );
		E20R_Tracker::dbg( $data );
		
		if ( false === $this->model->save_response( $data ) ) {
			
			wp_send_json_error( array( 'message' => __( "Error: Unable to save response, or update assignment information", "e20r-tracker" ) ) );
			exit();
		}
		
		$history         = $this->model->get_history( $data['assignment_id'], $data['program_id'], $data['article_id'], $data['client_id'] );
		$message_history = $this->view->message_history( $history, $data['recipient_id'], $data['assignment_id'] );
		
		if ( ( $data['client_id'] != $data['recipient_id'] ) && ( $Tracker->is_a_coach( $data['recipient_id'] ) ) ) {
			
			E20R_Tracker::dbg( 'Assignment::add_assignment_reply() - Sending notification email to coach' );
			
			$coach  = get_user_by( 'id', $data['recipient_id'] );
			$client = get_user_by( 'id', $data['client_id'] );
			
			$header                = array(
				sprintf( 'From: %s, <%s>\r\n', $client->display_name, $client->user_email ),
			);
			$client_assignment_lnk = admin_url( "admin.php?page=e20r-client-info&e20r-client-id={$data['client_id']}&e20r-level-id={$data['program_id']}" );
			$text                  = "%s has sent a new (instant) message via the %s website. Please <a href='%s' target='_blank'>log in<a/> and then click <a href='%s' target='_blank'>this link</a> to open the Assignment history for %s.";
			
			$subject = sprintf( __( "New message on %s from %s", "e20r-tracker" ), get_option( 'blogname' ), $client->display_name );
			$content = sprintf( __( $text, "e20r-tracker" ), $client->display_name, get_option( 'blogname' ), wp_login_url(), $client_assignment_lnk, $client->user_firstname );
			
			wp_mail( $coach->user_email, $subject, $content, $header );
		}
		
		wp_send_json_success( array( 'message_history' => $message_history ) );
		exit();
	}
	
	public function heartbeat_received() {
		
		E20R_Tracker::dbg( "Assignment::heartbeat_received() - Checking coach/client messaging status" );
		
		$nonce     = isset( $_REQUEST['e20r-message-nonce'] ) ? $_REQUEST['e20r-message-nonce'] : null;
		$client_id = isset( $_REQUEST['e20r-message-client-id'] ) ? intval( $_REQUEST['e20r-message-client-id'] ) : null;
		
		$retval = array(
			'e20r_new_messages'      => 0,
			'e20r_message_client_id' => $client_id,
		);
		
		E20R_Tracker::dbg( "Assignment::heartbeat_received() - Checking NONCE" );
		
		if ( wp_verify_nonce( $nonce, 'e20r-coach-message' ) && ! is_null( $retval['e20r_message_client_id'] ) ) {
			
			E20R_Tracker::dbg( "Assignment::heartbeat_received() - Received heartbeat. Checking for new messages" );
			
			$retval['e20r_new_messages'] = $this->client_has_unread_messages( $retval['e20r_message_client_id'] );
			
			E20R_Tracker::dbg( "Assignment::heartbeat_received() - New message for user {$retval['e20r_message_client_id']}: {$retval['e20r_new_messages']}" );
			
			wp_send_json_success( $retval );
			exit();
		}
		
		if ( empty( $client_id ) ) {
			E20R_Tracker::dbg( "Assignment::heartbeat_received() - Returning due to no client ID given" );
			wp_send_json_error( array( 'errormsg' => 'no-client-provided' ) );
			exit();
		}
		
		wp_send_json_error( array( 'errormsg' => __( "Unauthenticated polling request", 'e20r-tracker' ) ) );
		exit();
	}
	
	public function client_has_unread_messages( $client_id ) {
		
		$Program = Program::getInstance();
		
		$Program->getProgramIdForUser( $client_id );
		
		return $this->model->user_has_new_messages( $client_id );
	}
	
	public function update_metadata() {
		
		if ( 0 == E20R_RUN_UNSERIALIZE ) {
			return;
		}
		
		E20R_Tracker::dbg( "Assignment::update_metadata() - Test for required metadata update." );
		$Tracker = Tracker::getInstance();
		
		$old_meta_key = 'program_id';
		$new_meta_key = 'program_ids';
		
		$type = 'array';
		
		$version = $Tracker->loadOption( 'assignment_meta' );
		
		if ( ( false == $version ) || ( $version < E20R_ASSIGNMENT_META_VER ) ) {
			
			E20R_Tracker::dbg( "Assignment::update_metadata() - Need to update metadata for key: {$old_meta_key}." );
			$assignments = $this->getAllAssignments();
			
			foreach ( $assignments as $k => $a ) {
				
				E20R_Tracker::dbg( $a );
				
				if ( ! property_exists( $a, $old_meta_key ) ) {
					E20R_Tracker::dbg( "Assignment::update_metadata() - ERROR: Old key ({$old_meta_key}) is not present in the default configuration for Assignments. Must be included in the model->defaultSettings() function!" );
					
					return;
				}
				
				if ( ! property_exists( $a, $new_meta_key ) ) {
					E20R_Tracker::dbg( "Assignment::update_metadata() - ERROR: New key ({$new_meta_key}) is not present in the default configuration for Assignments. Must be included in the model->defaultSettings() function!" );
					
					return;
				}
				
				switch ( $type ) {
					case 'array':
						
						if ( ! empty( $a->{$old_meta_key} ) ) {
							$a->{$new_meta_key} = array( $a->{$old_meta_key} );
						} else {
							$a->{$new_meta_key} = null;
						}
						unset( $a->{$old_meta_key} );
						break;
				}
				
				// E20R_Tracker::dbg("Assignment::update_metadata() - Saving assignments # {$a->id} with new settings: ");
				// E20R_Tracker::dbg($a);
				if ( false !== $this->saveSettings( $a->id, $a ) ) {
					
					E20R_Tracker::dbg( "Assignment::update_metadata() - Removing assignment # {$a->id}'s {$old_meta_key} setting. " );
					delete_post_meta( $a->id, "_e20r-assignments-{$old_meta_key}" );
				}
				
			}
			
			E20R_Tracker::dbg( "Assignment::update_metadata() - Save the new meta version: " . ( E20R_ASSIGNMENT_META_VER + 1 ) );
			$Tracker->updateSetting( 'assignment_meta', ( E20R_ASSIGNMENT_META_VER + 1 ) );
		}
		
	}
	
	public function getAllAssignments() {
		
		$assignments = $this->model->loadAllAssignments();
		
		if ( count( $assignments ) >= 1 ) {
			E20R_Tracker::dbg( "Assignment::getAllAssignments() - Process the assignments?" );
		}
		
		return $assignments;
	}
	
	public function addMeta_answers() {
		
		global $post;
		
		E20R_Tracker::dbg( "Assignment::addMeta_answers() - Loading the article answers metabox for {$post->ID}, a {$post->post_type} CPT" );
		
		echo $this->configureArticleMetabox( $post->ID );
	}
	
	public function configureArticleMetabox( $articleId, $ajax = false ) {
		
		E20R_Tracker::dbg( "Assignment::configureArticleMetabox() - For article {$articleId}" );
		$Article = Article::getInstance();
		
		$assignments = array();
		
		$assignments = $Article->getAssignments( $articleId );
		$answerDefs  = $this->model->getAnswerDescriptions();
		
		
		if ( count( $assignments ) < 1 ) {
			
			E20R_Tracker::dbg( "Assignment::configureArticleMetabox() - No assignments defined. Using default" );
			
			$assignments = array();
			
			$assignments[0]             = $this->model->defaultSettings();
			$assignments[0]->order_num  = 1;
			$assignments[0]->question   = __( "Lesson complete (default)", 'e20r-tracker' );
			$assignments[0]->field_type = 0; // "Lesson complete" button.
		}
		
		ob_start();
		if ( false === $ajax ) {
			?>
            <div id="e20r-assignment-settings"> <?php
		} ?>
		<?php echo $this->view->viewArticle_Assignments( $articleId, $assignments, $answerDefs );
		if ( false === $ajax ) {
			?>
            </div>
			<?php
		}
		
		$html = ob_get_clean();
		
		return $html;
	}
	
	public function showAssignment( $assignments, $articleConf ) {
		
		return $this->view->viewAssignment( $assignments, $articleConf );
	}
	
	public function editor_metabox_setup( $post ) {
		
		add_meta_box( 'e20r-tracker-assignment-settings', __( 'Assignment Settings', 'e20r-tracker' ), array(
			&$this,
			"addMeta_Settings",
		), Assignment_Model::post_type, 'normal', 'core' );
		
	}
	
	public function getPeers( $assignmentId = null ) {
		
		if ( is_null( $assignmentId ) ) {
			
			global $post;
			// Use the parent value for the current post to get all of its peers.
			$assignmentId = $post->post_parent;
		}
		
		$assignments = new \WP_Query( array(
			'post_type'      => 'page',
			'post_parent'    => $assignmentId,
			'posts_per_page' => - 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		
		$assignmentList = array(
			'pages' => $assignments->posts,
		);
		
		foreach ( $assignments->posts as $k => $v ) {
			
			if ( $v == get_the_ID() ) {
				
				if ( isset( $assignments->posts[ $k - 1 ] ) ) {
					
					$assignmentList['prev'] = $assignments->posts[ $k - 1 ];
				}
				
				if ( isset( $assignments->posts[ $k + 1 ] ) ) {
					
					$assignmentList['next'] = $assignments->posts[ $k + 1 ];
				}
			}
		}
		wp_reset_postdata();
		
		return $assignmentList;
	}
	
	public function addMeta_Settings() {
		
		global $post;
		
		E20R_Tracker::dbg( "Assignment::addMeta_Settings() - Loading settings metabox for assignment page {$post->ID}" );
		$assignmentData = $this->model->loadSettings( $post->ID );
		
		echo $this->view->viewSettingsBox( $assignmentData, $this->model->getAnswerDescriptions() );
	}
	
	/**
	 * @param \WP_Query $query
	 */
	public function sort_column( $query ) {
		
		if ( $query->is_main_query() && is_admin() && ( $orderby = $query->get( 'orderby' ) ) ) {
			
			switch ( $orderby ) {
				case 'used_day':
					$query->set( 'meta_key', '_e20r-assignments-delay' );
					$query->set( 'orderby', 'meta_value_num' );
					
					E20R_Tracker::dbg( "Assignment::sort_column() - Order by is: " . $orderby );
					break;
			}
			
		}
	}
	
	public function order_by( $orderby ) {
		
		if ( 'used_day' !== $orderby ) {
			return $orderby;
		}
		
		if ( false !== stripos( $orderby, 'DESC' ) ) {
			$direction = "DESC";
		}
		
		if ( false !== stripos( $orderby, 'ASC' ) ) {
			$direction = "ASC";
		}
		
		$orderby = "CAST(meta_value AS SIGNED) {$direction}";
		
		return $orderby;
	}
	
	public function assignment_col_head( $columns ) {
		
		$columns['used_day'] = __( "Use on (Day #)", "e20r-tracker" );
		
		return $columns;
	}
	
	public function sortable_column( $columns ) {
		
		$columns['used_day'] = 'used_day';
		
		return $columns;
	}
	
	public function custom_column( $column, $post_ID ) {
		if ( $column != 'used_day' ) {
			return;
		}
		
		$post_releaseDay = $this->getDelay( $post_ID );
		echo intval( $post_releaseDay );
	}
	
	public function getDelay( $assignmentId ) {
		
		return $this->model->getSetting( $assignmentId, 'delay' );
	}
	
	public function set_custom_edit_columns( $columns ) {
		
		$columns['used_day'] = __( "Use on (Day #)", "e20r-tracker" );
		
		return $columns;
	}
	
	/** Load the assignments table via AJAX... */
	public function ajax_assignmentData() {
		
		$Tracker = Tracker::getInstance();
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-assignment-nonce' );
		E20R_Tracker::dbg( "Assignment::ajax_assignmentData() - Got a valid NONCE" );
		
		$client_id = isset( $_POST['client-id'] ) ? $Tracker->sanitize( $_POST['client-id'] ) : null;
		
		if ( ! is_null( $client_id ) ) {
			
			$html = $this->listUserAssignments( $client_id );
			wp_send_json_success( array( 'assignments' => $html ) );
			exit();
		}
		
		wp_send_json_error( array( 'message' => "No assignment data for client ({$client_id})" ) );
		exit();
	}
	
	public function listUserAssignments( $userId, $page_num = - 1 ) {
		
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		
		$config            = new \stdClass();
		$config->type      = 'action';
		$config->post_date = null;
		
		$config->userId    = $userId;
		$config->startTS   = $Program->startdate( $config->userId );
		$config->delay     = $Tracker->getDelay( 'now' );
		$config->programId = $Program->getProgramIdForUser( $config->userId );
		
		$answers = $this->model->loadAllUserAssignments( $userId, $page_num );
		
		return $this->view->viewAssignmentList( $config, $answers );
	}
	
	public function ajax_paginateAssignments() {
		$Tracker = Tracker::getInstance();
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-assignment-nonce' );
		E20R_Tracker::dbg( "Assignment::ajax_paginateAssignments() - Got a valid NONCE" );
		
		$client_id = isset( $_POST['client-id'] ) ? $Tracker->sanitize( $_POST['client-id'] ) : null;
		$page_num  = isset( $_REQUEST['page_num'] ) ? $Tracker->sanitize( $_REQUEST['page_num'] ) : 1;
		// $base_url = isset( $_REQUEST['page_url'] ) ? $Tracker->sanitize( $_REQUEST['page_url'] ) : null;
		
		if ( ! is_null( $client_id ) ) {
			
			/*            $org_uri = $_SERVER['REQUEST_URI'];
						$_SERVER['REQUEST_URI'] = $base_url; */
			$html = $this->listUserAssignments( $client_id, $page_num );
			/*            $_SERVER['REQUEST_URI'] = $org_uri; */
			
			wp_send_json_success( array( 'assignments' => $html ) );
			exit();
		}
		
		wp_send_json_error( array( 'message' => "No assignment data for client ({$client_id})" ) );
		exit();
	}
}
