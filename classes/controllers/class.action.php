<?php

namespace E20R\Tracker\Controllers;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Models\Program_Model;
use E20R\Tracker\Models\Tables;
use E20R\Tracker\Models\Action_Model;
use E20R\Tracker\Views\Action_View;
use E20R\Utilities\Utilities;

class Action extends Settings {
	
	private static $instance = null;
	protected $model;
	protected $view;
	
	protected $types = array(
		'none'       => 0,
		'action'     => CHECKIN_ACTION, // 1
		'assignment' => CHECKIN_ASSIGNMENT, // 2
		'survey'     => CHECKIN_SURVEY, // 3
		'activity'   => CHECKIN_ACTIVITY, // 4
		'note'       => CHECKIN_NOTE // 5
	);
	
	// checkin_type: 0 - action (habit), 1 - lesson, 2 - activity (workout), 3 - survey
	// "Enum" for the types of check-ins
	private $checkin = array();
	
	// checkedin values: 0 - false, 1 - true, 2 - partial, 3 - not applicable
	// "Enum" for the valid statuses.
	private $status = array(
		'no'      => 0,
		'yes'     => 1,
		'partial' => 2,
		'na'      => 3,
	);
	
	public function __construct() {
		
		Utilities::get_instance()->log( "Initializing Action class" );
		
		$this->model = new Action_Model();
		$this->view  = new Action_View();
		
		parent::__construct( 'action', Action_Model::post_type, $this->model, $this->view );
	}
	
	/**
	 * @return Action
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Add Custom columns to Action list
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function set_custom_edit_columns( $columns ) {
		
		unset( $columns['post_type'] );
		
		$columns['e20r_action_type'] = __( "Action Type", "e20r-tracker" );
		$columns['e20r_program']     = __( "Program", "e20r-tracker" );
		
		return $columns;
	}
	
	/**
	 * Display column content for the action type and for the program name
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function custom_column( $column, $post_id ) {
		
		if ( $column == 'e20r_action_type' ) {
			
			$typeId = get_post_meta( $post_id, '_e20r-action-checkin_type', true );
			
			Utilities::get_instance()->log( "Type ID: {$typeId} for {$post_id}" );
			$type = $this->getTypeDescr( $typeId );
			
			Utilities::get_instance()->log( "Type Name: {$type}" );
			echo ucfirst( $type );
		}
		
		if ( 'e20r_program' === $column ) {
			
			$program_ids = get_post_meta( $post_id, '_e20r-action-program_ids' );
			
			if ( ! empty( $program_ids ) ) {
				foreach ( $program_ids as $program_id ) {
					printf( '%1$s %2$s', get_the_title( $program_id ), '<br/>' );
				}
			} else {
				_e( 'Not selected', 'e20r-tracker' );
			}
		}
	}
	
	/**
	 * Return the description label for the action type
	 *
	 * @param int $typeId
	 *
	 * @return null|string
	 */
	public function getTypeDescr( $typeId ) {
		
		$descr = array_search( $typeId, $this->types );
		
		if ( is_string( $descr ) ) {
			return $descr;
		}
		
		return null;
	}
	
	/**
	 * Make Action Type and Program Name sortable columns
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function sortable_column( $columns ) {
		$columns['e20r_action_type'] = array( 'e20r_action_type', true );
		$columns['e20r_program']     = array( 'e20r_program', true );
		
		return $columns;
	}
	
	/**
	 * Set the sort order for the query when processing a checkin
	 *
	 * @param \WP_Query $query
	 */
	public function sort_column( $query ) {
		
		if ( $query->is_main_query() && is_admin() && ( $orderby = $query->get( 'orderby' ) ) ) {
			
			switch ( $orderby ) {
				case 'e20r_action_type':
					$query->set( 'meta_key', '_e20r-action-checkin_type' );
					$query->set( 'orderby', 'meta_value_num' );
					break;
			}
		}
	}
	
	public function hasCheckedIn( $userId, $articleId, $type = CHECKIN_ASSIGNMENT ) {
		
		global $wpdb;
		global $currentArticle;
		global $currentProgram;
		
		global $current_user;
		$Tables  = Tables::getInstance();
		$Program = Program::getInstance();
		
		if ( is_null( $userId ) ) {
			
			$userId = $current_user->ID;
		}
		
		if ( empty( $currentProgram->id ) ) {
			
			$Program->getProgramIdForUser( $userId );
			
		}
		if ( ( empty( $currentArticle ) ) || ( $currentArticle->id != $articleId ) ) {
			
			Utilities::get_instance()->log( " loading settings for article: {$articleId} (ID)" );
			$currentArticle = $this->model->loadSettings( $articleId );
		}
		
		try {
			$table_name = $Tables->getTable( 'action' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error fetching Action table. Error: " . $exception->getMessage() );
			
			return false;
		}
		
		$sql = $wpdb->prepare( "
	    		    SELECT checkedin
	    		    FROM {$table_name}
	    		    WHERE article_id = %d AND user_id = %d AND
	    		    	program_id = %d AND checkin_type = %d",
			$articleId, $userId, $currentProgram->id
		);
		
		if ( false !== ( $result = $wpdb->query( $sql ) ) ) {
			return $result;
		}
	}
	
	public function get_shortname( $checkin_id ) {
		
		global $currentAction;
		
		$this->init( $checkin_id );
		
		return $currentAction->short_name;
	}
	
	public function findCheckinItemId( $articleId ) {
		
		$Article = Article::getInstance();
	}
	
	/**
	 * Flag the specified article as 'read' (complete)
	 *
	 * @param int $userId
	 * @param int $articleId
	 *
	 * @return bool
	 */
	public function setArticleAsComplete( $userId, $articleId ) {
		
		$Article = Article::getInstance();
		$Program = Program::getInstance();
		
		$programId = $Program->getProgramIdForUser( $userId );
		
		$defaults = array(
			'user_id'            => $userId,
			'checkedin'          => $this->status['yes'],
			'article_id'         => $articleId,
			'program_id'         => $programId,
			'checkin_date'       => $Article->releaseDate( $articleId ),
			'checkin_short_name' => null,
			'checkin_type'       => $this->types['action'],
			'checkin_note'       => null,
		);
		
		if ( $this->model->setCheckin( $defaults ) ) {
			Utilities::get_instance()->log( "Check-in for user {$userId}, article {$articleId} in program {$programId} has been saved" );
			
			return true;
		}
		
		Utilities::get_instance()->log( "Unable to save check-in value!" );
		
		return false;
	}
	
	/**
	 * Load all user checkin's, actions, activities (aka accomplishments)
	 *
	 * @param null|int $userId
	 *
	 * @return string
	 */
	public function listUserAchievements( $userId = null ) {
		
		if ( empty( $userId ) ) {
			return null;
		}
		
		global $currentProgram;
		global $current_user;
		
		$Article = Article::getInstance();
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		$Access  = Tracker_Access::getInstance();
		$Client  = Client::getInstance();
		
		// $config = new \stdClass();
		
		if ( $userId != $current_user->ID ) {
			
			Utilities::get_instance()->log( "Validate that the current user has rights to access this data!" );
			if ( ! $Access->is_a_coach( $current_user->ID ) ) {
				
				return null;
			}
			
		}
		
		if ( empty( $currentProgram->id ) ) {
			
			$Program->getProgramIdForUser( $userId );
		}
		
		$programId = $currentProgram->id;
		$dates     = array();
		
		$program_actions   = $this->model->getProgramActions( $programId, $this->types['action'] );
		$user_action_types = array( $this->types['action'], $this->types['activity'] );
		Utilities::get_instance()->log( "Found " . count( $program_actions ) . " actions for program {$programId}" );
		
		
		$results                  = array();
		$results['program_days']  = 1; //$user_delay;
		$results['program_score'] = 0;
		
		$bronze_max = apply_filters( 'e20r-tracker-achievement-score-bronze-max', 0.6 );
		$silver_min = apply_filters( 'e20r-tracker-achievement-score-silver-min', 0.6 );
		$gold_min   = apply_filters( 'e20r-tracker-achievement-score-gold-min', 0.80 );
		
		foreach ( $program_actions as $program_action ) {
			
			$program_action = $Client->updateForClientInfo( $program_action );
			
			$dates['min'] = $program_action->startdate;
			$dates['max'] = $program_action->enddate;
			
			$start = $Tracker->getDelay( $program_action->startdate, $userId );
			$end   = $Tracker->getDelay( $program_action->enddate, $userId );
			
			$find_range = array( $start, $end );
			
			$article_list = $Article->findArticles( 'release_day', $find_range, $programId, 'BETWEEN' );
			$article_ids  = wp_list_pluck( $article_list, 'id' );
			
			$recent_article = $Article->findArticlesNear( 'release_day', $end, $programId );
			
			if ( isset( $recent_article[0] ) && $end > $recent_article[0]->release_day ) {
				
				$end = $recent_article[0]->release_day;
			}
			
			$action_days = ( $end - $start );
			
			Utilities::get_instance()->log( "Found " . count( $article_ids ) . " article(s) for program {$programId} with a release_day between {$find_range[0]} and {$find_range[1]} " );
			
			$user_checkins = $this->model->loadCheckinsForUser( $userId, $article_ids, $user_action_types, $dates );
			$user_lessons  = $this->model->loadCheckinsForUser( $userId, $article_ids, array( $this->types['assignment'] ), $dates );
			
			$action_count     = $this->count_actions( $user_checkins, $this->types['action'], $program_action->startdate, $program_action->enddate );
			$activity_count   = $this->count_actions( $user_checkins, $this->types['activity'], $program_action->startdate, $program_action->enddate );
			$assignment_count = $this->count_actions( $user_lessons, $this->types['assignment'], $program_action->startdate, $program_action->enddate );
			
			Utilities::get_instance()->log( "Processing " . count( $user_checkins ) . " lessons and found {$action_count} actions" );
			Utilities::get_instance()->log( "Processing " . count( $user_checkins ) . " lessons and found {$activity_count} activities" );
			Utilities::get_instance()->log( "Processing " . count( $user_lessons ) . " lessons and found {$assignment_count} assignments" );
			
			$avg_score = 0;
			
			foreach ( array( 'action', 'activity', 'assignment' ) as $key ) {
				
				$var_name = "{$key}_count";
				
				// Init the array (if needed)
				if ( ! isset( $results[ $program_action->startdate ] ) ) {
					
					$results[ $program_action->startdate ]                 = array();
					$results[ $program_action->startdate ]['action_title'] = sprintf(
						'%1$s %6$s%4$s(%2$s to %3$s)%5$s',
						wp_unslash( $program_action->item_text ),
						date( 'M jS, Y', strtotime( $program_action->startdate ) ),
						date( 'M jS, Y', strtotime( $program_action->enddate ) ),
						'<span class="e20r-date-header">',
						'</span>',
						'<br />'
					);
					
				}
				
				/**
				 * Add the action (if needed)
				 */
				if ( ! isset( $results[ $program_action->startdate ][ $key ] ) ) {
					$results[ $program_action->startdate ][ $key ]       = new \stdClass();
					$results[ $program_action->startdate ][ $key ]->days = $action_days;
					
				}
				
				// Calculate score for this action
				$score = round( ( ${$var_name} / $results[ $program_action->startdate ][ $key ]->days ), 2 );
				$badge = null;
				
				// Set the appropriate badge to use
				if ( ( $score >= $bronze_max ) && ( $score < $silver_min ) ) {
					$badge = 'bronze';
				} else if ( ( $score >= $silver_min ) && ( $score < $gold_min ) ) {
					
					$badge = 'silver';
				} else if ( ( $score >= $gold_min ) ) {
					
					$badge = 'gold';
				}
				
				// Save the score and badge
				$results[ $program_action->startdate ][ $key ]->badge = $badge;
				$results[ $program_action->startdate ][ $key ]->score = $score;
				$avg_score                                            += $score;
			}
			
			$results['program_days'] += $results[ $program_action->startdate ][ $key ]->days;
			$avg_score               = ( $avg_score / 3 );
			
			// All $action->shortname entries minus the two program_* entries in the array.
			$result_count = count( $results ) - 2;
			
			// Set the overall program score for this user.
			$results['program_score'] = ( $results['program_score'] + $avg_score ) / ( $result_count + 1 );
		}
		
		return $this->view->view_user_achievements( $results );
	}
	
	/**
	 * Count the number of completed actions of the specified type between the start & end date
	 *
	 * @param array  $action_list
	 * @param int    $type
	 * @param string $start_date
	 * @param string $end_date
	 *
	 * @return int
	 */
	private function count_actions( $action_list, $type, $start_date, $end_date ) {
		
		$action_count = 0;
		
		foreach ( $action_list as $action ) {
			
			$comp_date = date( 'Y-m-d', strtotime( $action->checkin_date ) );
			
			if ( ( $action->checkin_type == $type ) && ( $action->checkedin == 1 ) &&
			     ( $comp_date >= $start_date ) && ( $comp_date <= $end_date )
			) {
				
				$action_count += 1;
			}
		}
		
		Utilities::get_instance()->log( "Counted {$action_count} completed actions of type {$type} between {$start_date} and {$end_date}" );
		
		return $action_count;
	}
	
	/**
	 * AJAX: Save the check-in action by the user
	 */
	public function saveCheckin_callback() {
		
		Utilities::get_instance()->log( "Attempting to save checkin for user." );
		Utilities::get_instance()->log( "Checking ajax referrer privileges" );
		
		check_ajax_referer( 'e20r-action-data', 'e20r-action-nonce' );
		
		Utilities::get_instance()->log( "Checking ajax referrer has the right privileges" );
		
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		
		// Save the $_POST data for the Action callback
		global $current_user;
		
		$Tracker = Tracker::getInstance();
		$Program = Program::getInstance();
		
		Utilities::get_instance()->log( "Content of POST variable:" . print_r( $_POST, true ) );
		
		$data = array(
			'user_id'            => $current_user->ID,
			'id'                 => ( isset( $_POST['id'] ) ? ( $Tracker->sanitize( $_POST['id'] ) != 0 ? $Tracker->sanitize( $_POST['id'] ) : null ) : null ),
			'action_id'          => ( isset( $_POST['action-id'] ) ? ( $Tracker->sanitize( $_POST['action-id'] ) != 0 ? $Tracker->sanitize( $_POST['action-id'] ) : null ) : null ),
			'checkin_type'       => ( isset( $_POST['checkin-type'] ) ? $Tracker->sanitize( $_POST['checkin-type'] ) : null ),
			'article_id'         => ( isset( $_POST['article-id'] ) ? $Tracker->sanitize( $_POST['article-id'] ) : CONST_NULL_ARTICLE ),
			'program_id'         => ( isset( $_POST['program-id'] ) ? $Tracker->sanitize( $_POST['program-id'] ) : - 1 ),
			'checkin_date'       => ( isset( $_POST['checkin-date'] ) ? $Tracker->sanitize( $_POST['checkin-date'] ) : null ),
			'checkedin_date'     => ( isset( $_POST['checkedin-date'] ) ? $Tracker->sanitize( $_POST['checkedin-date'] ) : null ),
			'descr_id'           => ( isset( $_POST['descr-id'] ) ? $Tracker->sanitize( $_POST['descr-id'] ) : null ),
			'checkin_note'       => ( isset( $_POST['checkin-note'] ) ? $Tracker->sanitize( $_POST['checkin-note'] ) : null ),
			'checkin_short_name' => ( isset( $_POST['checkin-short-name'] ) ? $Tracker->sanitize( $_POST['checkin-short-name'] ) : null ),
			'checkedin'          => ( isset( $_POST['checkedin'] ) ? $Tracker->sanitize( $_POST['checkedin'] ) : 0 ),
		);
		
		if ( $data['program_id'] !== - 1 ) {
			
			$Program->init( $data['program_id'] );
		} else {
			
			$Program->getProgramIdForUser( $current_user->ID, $data['article_id'] );
		}
		
		if ( $data['article_id'] == CONST_NULL_ARTICLE ) {
			Utilities::get_instance()->log( "No checkin needed/scheduled" );
			wp_send_json_error();
			exit();
		}
		
		if ( ! $this->model->setCheckin( $data ) ) {
			
			Utilities::get_instance()->log( "Error saving checkin information..." );
			wp_send_json_error();
			exit();
		}
		
		wp_send_json_success();
		exit();
	}
	
	/**
	 * Pass-through of the the check-in save operation to the model
	 *
	 * @param      $checkin_data
	 * @param null $type
	 *
	 * @return bool
	 */
	public function save_check_in( $checkin_data, $type = null ) {
		
		if ( ! isset( $checkin_data['checkin_type'] ) && ( ! is_null( $type ) ) ) {
			
			$checkin_data['checkin_type'] = $this->types[ $type ];
		}
		
		if ( $this->model->isValid( $checkin_data ) ) {
			
			return $this->model->saveCheckin( $checkin_data );
		}
		
		return false;
	}
	
	/**
	 * AJAX: Save the assignment answer
	 */
	public function dailyProgress_callback() {
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-assignment-answer' );
		Utilities::get_instance()->log( "Ajax calleee has the right privileges: " . print_r( $_POST, true ) );
		
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		
		$Tracker    = Tracker::getInstance();
		$Program    = Program::getInstance();
		$Article    = Article::getInstance();
		$Assignment = Assignment::getInstance();
		
		$descrId            = null;
		$success            = false;
		$answerIsDefaultBtn = false;
		
		$articleId   = ( isset( $_POST['e20r-article-id'] ) ? $Tracker->sanitize( $_POST['e20r-article-id'] ) : null );
		$userId      = ( isset( $_POST['e20r-article-user_id'] ) ? $Tracker->sanitize( $_POST['e20r-article-user_id'] ) : null );
		$delay       = ( isset( $_POST['e20r-article-release_day'] ) ? $Tracker->sanitize( $_POST['e20r-article-release_day'] ) : null );
		$answerDate  = ( isset( $_POST['e20r-assignment-answer_date'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-answer_date'] ) : null );
		$recordIds   = ( isset( $_POST['e20r-assignment-record_id'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-record_id'] ) : array() );
		$answerIds   = ( isset( $_POST['e20r-assignment-id'] ) && is_array( $_POST['e20r-assignment-id'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-id'] ) : array() );
		$questionIds = ( isset( $_POST['e20r-assignment-question_id'] ) && is_array( $_POST['e20r-assignment-question_id'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-question_id'] ) : array() );
		$fieldTypes  = ( isset( $_POST['e20r-assignment-field_type'] ) && is_array( $_POST['e20r-assignment-field_type'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-field_type'] ) : array() );
		$answers     = ( isset( $_POST['e20r-assignment-answer'] ) && is_array( $_POST['e20r-assignment-answer'] ) ? $Tracker->sanitize( $_POST['e20r-assignment-answer'] ) : array() );
		
		$programId = $Program->getProgramIdForUser( $userId, $articleId );
		
		if ( ( CONST_NULL_ARTICLE === $articleId ) && ( is_null( $userId ) || is_null( $answerDate ) || is_null( $delay ) ) ) {
			Utilities::get_instance()->log( "Can't save assignment info!" );
			wp_send_json_error( __( "Unable to save your answer. Please contact technical support!", "e20r-tracker" ) );
			exit();
		}
		
		if ( count( $questionIds ) != count( $answers ) ) {
			Utilities::get_instance()->log( "Mismatch for # of questions and # of answers provided/supplied. " );
			Utilities::get_instance()->log( "Questions: ", print_r( $questionIds, true ) );
			Utilities::get_instance()->log( "Answers: " . print_r( $answers, true ) );
			
			// Is this a default "read this lesson" button?
			if ( empty( $answers ) && ( 1 == count( $fieldTypes ) ) && ( 0 == $fieldTypes[0] ) ) {
				
				// It is, so flag the fact.
				$answerIsDefaultBtn = true;
			}
			// wp_send_json_error( __( "You didn't answer all of the questions we had for you. We're saving what we received.", "e20r-tracker" ) );
		}
		
		Utilities::get_instance()->log( "Have an array of answers to process.." );
		
		// Build answer objects to save to database
		foreach ( $answerIds as $key => $id ) {
			
			if ( ! $descrId ) {
				$descrId = $id;
			}
			
			$checkin = array(
				'descr_id'           => $descrId,
				'article_id'         => $articleId,
				'user_id'            => $userId,
				'checkin_type'       => CHECKIN_ASSIGNMENT,
				'program_id'         => $programId,
				'checkin_date'       => $Article->releaseDate( $articleId ),
				'checkedin_date'     => $answerDate,
				'checkin_short_name' => 'daily_lesson',
				'checkedin'          => ( ! empty( $answers[ $key ] ) || ( ( $answerIsDefaultBtn ) && ( 0 == $fieldTypes[0] ) ) ),
			);
			
			Utilities::get_instance()->log( "Saving answer(s) for assignment # {$id} " );
			
			$answer = array(
				/* 'id' => $id, */
				'article_id'  => $articleId,
				'program_id'  => $programId,
				'delay'       => $delay,
				'question_id' => $questionIds[ $key ],
				'user_id'     => $userId,
				'answer_date' => $answerDate,
				'answer'      => ( isset( $answers[ $key ] ) ? $answers[ $key ] : null ),
				'field_type'  => $Assignment->getInputType( $fieldTypes[ $key ] ),
			);
			
			if ( - 1 != $recordIds[ $key ] ) {
				
				$answer['id'] = $recordIds[ $key ];
			}
			
			Utilities::get_instance()->log( 'Answer Provided: ' . print_r( $answer, true ) );
			
			Utilities::get_instance()->log( "Saving answer to question # {$answer['question_id']}" );
			$new     = $Assignment->saveAssignment( $answer );
			$success = ( $success && $new );
		}
		
		Utilities::get_instance()->log( "Make sure check-in isn't empty" );
		
		if ( ( ! empty( $checkin ) ) ) {
			
			Utilities::get_instance()->log( "Saving checkin for date {$checkin['checkin_date']}" );
			$ok = $this->model->setCheckin( $checkin );
			
			if ( ! $ok ) {
				
				global $wpdb;
				Utilities::get_instance()->log( "DB error: " . $wpdb->last_error );
			} else {
				
				$success = true;
			}
		}
		
		if ( $success == true ) {
			wp_send_json_success();
			exit();
		} else {
			wp_send_json_error( __( "Unable to save your update", "e20rtracke" ) );
			exit();
		}
	}
	
	/**
	 * AJAX: Load the page for the next check-in/article by day
	 */
	public function nextCheckin_callback() {
		
		Utilities::get_instance()->log( "Checking ajax referrer privileges" );
		check_ajax_referer( 'e20r-action-data', 'e20r-action-nonce' );
		
		Utilities::get_instance()->log( "Checking ajax referrer has the right privileges" );
		
		if ( ! is_user_logged_in() ) {
			
			Utilities::get_instance()->log( "Return login error and force redirect to login page." );
			wp_send_json_error( array( 'ecode' => 3 ) );
			exit();
		}
		
		$Article = Article::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		global $currentArticle;
		
		$config = $this->configure_dailyProgress( true );
		
		if ( $config->delay != $currentArticle->release_day ) {
			
			Utilities::get_instance()->log( "Need to load a new article (by delay)" );
			
			$articles = $Article->findArticles( 'release_day', $config->delay, $config->programId );
			Utilities::get_instance()->log( "Found " . count( $articles ) . " articles for program {$config->programId} and with a release day of {$config->delay}" . print_r( $articles, true ) );
			
			if ( is_array( $articles ) && ( 1 == count( $articles ) ) ) {
				
				$articles = $articles[0];
			}
			
			// Single article returned.
			if ( ! empty( $articles ) ) {
				
				Utilities::get_instance()->log( "Loading the article info for the requested day" );
				$Article->init( $articles->id );
				$config->articleId = $articles->id;
				
				Utilities::get_instance()->log( "Checking access to post # {$articles->post_id} for user ID {$config->userId}" );
				$access = $Access->hasAccess( $config->userId, $articles->post_id );
				Utilities::get_instance()->log( "Access to post # {$articles->post_id} for user ID {$config->userId}: {$access}" );
			} else {
				
				$access = false;
			}
			
			if ( ( false === $access ) && ( empty( $articles ) ) ) {
				Utilities::get_instance()->log( "Error: No article for user {$config->userId} in this program." );
				wp_send_json_error( array( 'ecode' => 2 ) );
				exit();
			} else if ( ( false === $access ) && ( ! empty( $articles ) ) ) {
				
				Utilities::get_instance()->log( "Error: User {$config->userId} DOES NOT have access to post " );
				wp_send_json_error( array( 'ecode' => 1 ) );
				exit();
			}
			
			$config->is_survey = isset( $currentArticle->is_survey ) && ( $currentArticle->is_survey == 0 ) ? false : true;
			$config->articleId = isset( $currentArticle->id ) ? $currentArticle->id : CONST_NULL_ARTICLE;
		}
		
		Utilities::get_instance()->log( "Article: {$config->articleId}, Program: {$config->programId}, delay: {$config->delay}, start: {$config->startTS}, delay_byDate: {$config->delay_byDate}, post: {$config->post_id}" );
		
		$access = $Access->hasAccess( $config->userId, $config->post_id );
		Utilities::get_instance()->log( "Access: " . ( $access ? 'true' : 'false' ) . ". Using closest article and not current_day: " . ( $config->using_closest ? 'true' : 'false' ) . ". delay_byDate: {$config->delay_byDate} vs delay: {$config->delay}" );
		
		if ( $access && $config->using_closest && ( $config->delay > $config->delay_byDate ) ) {
			
			Utilities::get_instance()->log( "Article & post isn't available to this user yet due to delay vs today" );
			wp_send_json_error( array( 'ecode' => 1 ) );
			exit();
		}
		
		if ( ! $access ) {
			Utilities::get_instance()->log( "User doesn't have access to article." );
			wp_send_json_error( array( 'ecode' => 1 ) );
			exit();
		}
		
		if ( ( $html = $this->dailyProgress( $config ) ) !== false ) {
			
			Utilities::get_instance()->log( "Sending new dailyProgress data (html)" );
			wp_send_json_success( $html );
			exit();
		}
		
		wp_send_json_error();
		exit();
	}
	
	/**
	 * Defines the settings for the Daily Progress page
	 *
	 * @param bool $is_callback
	 *
	 * @return \stdClass
	 */
	public function configure_dailyProgress( $is_callback = false ) {
		
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		$Article = Article::getInstance();
		
		global $currentProgram;
		global $currentArticle;
		
		global $current_user;
		global $post;
		
		$articles           = array();
		$article_configured = false;
		
		$config = new \stdClass();
		
		$config->type          = 'action';
		$config->survey_id     = null;
		$config->post_date     = null;
		$config->maxDelayFlag  = null;
		$config->assignment_id = null;
		
		$config->complete      = false;
		$config->userId        = $current_user->ID;
		$config->update_period = 'Today';
		$config->using_closest = false;
		$config->today         = null;
		$config->post_id       = null;
		
		if ( isset( $currentArticle->id ) && ( $post->ID == $currentArticle->post_id ) ) {
			Utilities::get_instance()->log( "Article data already loaded: {$currentArticle->post_id} vs {$post->ID} -> " . print_r( $currentArticle, true ) );
			$article_configured = true;
		}
		
		$config->programId = ( ! isset( $_POST['program-id'] ) ? $Program->getProgramIdForUser( $config->userId ) : intval( $_POST['program-id'] ) );
		
		if ( ! isset( $currentProgram->id ) || ( $currentProgram->id !== $config->programId ) ) {
			
			$Program->init( $config->programId );
		}
		
		$dashboard   = ( isset( $currentProgram->dashboard_page_id ) && ! empty( $currentProgram->dashboard_page_id ) || isset( $currentProgram->dashboard_page_id ) && $currentProgram->dashboard_page_id != - 1 ) ? get_permalink( $currentProgram->dashboard_page_id ) : null;
		$config->url = $dashboard;
		
		$config->startTS = isset( $currentProgram->startdate ) && ! empty( $currentProgram->startdate ) ? strtotime( $currentProgram->startdate ) : null;
		
		Utilities::get_instance()->log( "POST vaues: " . print_r( $_POST, true ) );
		
		if ( isset( $_POST['e20r-use-card-based-display'] ) ) {
			Utilities::get_instance()->log( "Card status from the calling page/post: {$_POST['e20r-use-card-based-display']}" );
			$card_setting      = $Tracker->sanitize( $_POST['e20r-use-card-based-display'] );
			$config->use_cards = ( $card_setting ? true : false );
			
			Utilities::get_instance()->log( "Using card-based display setting from calling page: " . ( false === $config->use_cards ? 'false' : 'true' ) );
		}
		
		if ( isset( $_POST['e20r-action-day'] ) ) {
			
			$config->delay = $Tracker->getDelay( $Tracker->sanitize( $_POST['e20r-action-day'] ), $config->userId );
			$config->today = isset( $_POST['e20r-today'] ) ? $Tracker->sanitize( $_POST['e20r-today'] ) : $Tracker->getDelay();
			
			Utilities::get_instance()->log( "Was given a specific release_day to load the article for: {$config->delay}" );
		}
		
		if ( isset( $_POST['article-id'] ) ) {
			Utilities::get_instance()->log( "Article ID is specified: {$_POST['article-id']}" );
		}
		
		if ( isset( $_POST['article-id'] ) && ! isset( $config->delay ) ) {
			
			$config->articleId = $Tracker->sanitize( $_POST['article-id'] );
			Utilities::get_instance()->log( "Loading article based on specified article ID ({$config->articleId}) from POST variable" );
			
			// Article ID given in POST variable so load the requested article
			$Article->init( $config->articleId );
		} else {
			
			if ( ! isset( $config->delay ) && ( false === $article_configured ) && isset( $post->ID ) ) {
				
				Utilities::get_instance()->log( "Trying to load article based on post_id" );
				
				$articles = $Article->findArticles( 'post_id', $post->ID, $config->programId );
				
				if ( empty( $articles ) ) {
					
					Utilities::get_instance()->log( "post_id got us nowhere so using the present ('now') as a delay value to try to find a valid article" );
					$config->delay = $Tracker->getDelay( 'now', $config->userId );
				}
			}
			
			if ( isset( $config->delay ) && empty( $articles ) && ( false === $article_configured ) ) {
				
				Utilities::get_instance()->log( "Loading article (list?) based on the release day (delay value)" );
				$articles = $Article->findArticles( 'release_day', $config->delay, $config->programId );
			}
			
			if ( empty( $articles ) && ( false === $article_configured ) ) {
				
				$articles = $Article->findArticlesNear( 'release_day', $config->delay, $config->programId, '<=' );
				
				Utilities::get_instance()->log( "Empty article for the actual day, so we're looking for the one the closest to requested day" );
				$article               = ! empty( $articles[0] ) ? $articles[0] : null;
				$config->using_closest = true;
			}
			
			if ( is_array( $articles ) && ( 1 == count( $articles ) ) && ( false === $article_configured ) ) {
				
				$article = array_pop( $articles );
				
			} else if ( 1 < count( $articles ) && ( false === $article_configured ) ) {
				Utilities::get_instance()->log( "WARNING: Multiple articles have been returned. Select the one with a release data == the delay." );
				
				if ( empty( $config->delay ) ) {
					
					$use = $Tracker->getDelay();
				} else {
					
					$use = $config->delay;
				}
				
				$article = $Article->getClosest( $use, $articles );
				Utilities::get_instance()->log( "Found an article w/what we think is the correct release_day and program ID. Using it: {$article->id}." );
				
			} else if ( is_object( $articles ) && ( false === $article_configured ) ) {
				Utilities::get_instance()->log( "Articles object: " . gettype( $articles ) . " -> " . print_r( $articles, true ) );
				$article = $articles;
			}
			
			if ( ( false === $article_configured ) && isset( $article->release_day ) ) {
				$currentArticle = $article;
			}
		}
		
		Utilities::get_instance()->log( "Loaded article info for: " . ( ! empty( $currentArticle->id ) ? $currentArticle->id : 'Not found!' ) );
		
		if ( ! empty( $currentArticle->assignment_ids ) ) {
			
			switch ( count( $currentArticle->assignment_ids ) ) {
				case 0:
					$config->assignment_id = 0;
					break;
				case 1:
					
					if ( ! is_array( $currentArticle->assignment_ids ) ) {
						$config->assignment_id = $currentArticle->assignment_ids;
					} else {
						$assignment            = $currentArticle->assignment_ids;
						$config->assignment_id = array_pop( $assignment );
					}
					break;
				
				default:
					$assignment            = $currentArticle->assignment_ids;
					$config->assignment_id = array_pop( $assignment );
			}
		}
		
		$config->post_id      = isset( $currentArticle->post_id ) ? $currentArticle->post_id : null;
		$config->delay        = isset( $currentArticle->release_day ) ? $currentArticle->release_day : 0;
		$config->delay_byDate = $Tracker->getDelay();
		$config->is_survey    = isset( $currentArticle->is_survey ) && ( $currentArticle->is_survey == 0 ) ? false : true;
		$config->articleId    = isset( $currentArticle->id ) ? $currentArticle->id : CONST_NULL_ARTICLE;
		$config->use_cards    = ( isset( $config->use_cards ) ? $config->use_cards : false );
		
		return $config;
		
	}
	
	/**
	 * Generate the Daily Progress page for the Tracker
	 *
	 * @param \stdClass $config
	 *
	 * @return null|string
	 */
	public function dailyProgress( $config ) {
		
		$Tracker    = Tracker::getInstance();
		$Article    = Article::getInstance();
		$Assignment = Assignment::getInstance();
		global $currentArticle;
		
		Utilities::get_instance()->log( "Start of dailyProgress(): " . $Tracker->whoCalledMe() );
		
		if ( ! isset( $config->delay ) || $config->delay <= 0 ) {
			
			Utilities::get_instance()->log( "Negative delay value. No article to be found." );
			$config->articleId = CONST_NULL_ARTICLE;
		}
		
		if ( ! isset( $currentArticle->post_id ) || ( $config->articleId != $currentArticle->id ) ) {
			
			Utilities::get_instance()->log( "No or wrong article is active. Updating..." );
			$Article->init( $config->articleId );
		}
		
		if ( $config->delay > ( $config->delay_byDate + 2 ) ) {
			// The user is attempting to view a day >2 days after today.
			$config->maxDelayFlag = CONST_MAXDAYS_FUTURE;
		}
		
		$config->prev = $config->delay - 1;
		$config->next = $config->delay + 1;
		
		Utilities::get_instance()->log( "Delay info: Now = {$config->delay}, 'tomorrow' = {$config->next}, 'yesterday' = {$config->prev}" );
		
		$t                = $Tracker->getDateFromDelay( ( $config->next - 1 ) );
		$config->tomorrow = date_i18n( 'D M. jS', strtotime( $t ) );
		
		$y                 = $Tracker->getDateFromDelay( ( $config->prev - 1 ) );
		$config->yesterday = date_i18n( 'D M. jS', strtotime( $y ) );
		
		if ( ! isset( $config->userId ) ) {
			
			global $current_user;
			$config->userId = $current_user->ID;
		}
		
		$this->checkin = $this->load_default_checkins();
		
		Utilities::get_instance()->log( "currentArticle is {$currentArticle->id} " );
		$config->complete = $this->hasCompletedLesson( $config->articleId, $currentArticle->post_id, $config->userId );
		
		if ( ( strtolower( $config->type ) == 'action' ) || ( strtolower( $config->type ) == 'activity' ) ) {
			
			Utilities::get_instance()->log( "Processing action or activity" );
			
			if ( ! isset( $config->articleId ) || empty( $config->articleId ) ) {
				
				Utilities::get_instance()->log( " No articleId specified. Searching..." );
				
				$articles = $Article->findArticles( 'release_day', $config->delay, $config->programId );
				
				foreach ( $articles as $article ) {
					
					if ( $config->delay == $article->release_day ) {
						
						$config->articleId = $article->id;
						Utilities::get_instance()->log( " Found article # {$config->articleId}" );
						break;
					}
				}
				
				if ( empty( $articles ) ) {
					Utilities::get_instance()->log( "No article found. Using default of: " . CONST_NULL_ARTICLE );
					$config->articleId = CONST_NULL_ARTICLE;
				}
			}
			
			Utilities::get_instance()->log( "Configured daily action check-ins for article ID(s):" );
			
			// if ( !is_array( $config->articleId ) && ( $config->articleId !== CONST_NULL_ARTICLE ) ) {
			// if ( $config->articleId !== CONST_NULL_ARTICLE ) {
			Utilities::get_instance()->log( "Generating excerpt for daily action lesson/reminder" );
			$config->actionExcerpt = $Article->getExcerpt( $config->articleId, $config->userId, 'action', $config->use_cards );
			
			Utilities::get_instance()->log( "Generating excerpt for daily activity" );
			$config->activityExcerpt = $Article->getExcerpt( $config->articleId, $config->userId, 'activity', $config->use_cards );
			//}
			
			// Get the check-in id list for the specified article ID
			$checkinIds = $Article->getCheckins( $config );
			
			if ( empty( $checkinIds ) ) {
				
				Utilities::get_instance()->log( "No check-in ids stored for this user/article Id..." );
				
				// Set default checkin data (to ensure rendering of form).
				$this->checkin[ CHECKIN_ACTION ]               = $this->model->get_user_checkin( $config, $config->userId, CHECKIN_ACTION );
				$this->checkin[ CHECKIN_ACTION ]->actionList   = array();
				$this->checkin[ CHECKIN_ACTION ]->actionList[] = $this->model->defaultAction();
				
				$this->checkin[ CHECKIN_ACTIVITY ] = $this->model->get_user_checkin( $config, $config->userId, CHECKIN_ACTIVITY );
				
				$config->post_date = Time_Calculations::getDateForPost( $config->delay );
				$checkinIds        = $this->model->findActionByDate( $config->post_date, $config->programId );
			}
			
			Utilities::get_instance()->log( "Checkin info loaded (count): " . count( $checkinIds ) );
			
			// $activity = $Article->getActivity( $config->articleId, $config->userId );
			// Utilities::get_instance()->log( "Activity info loaded (count): " . count( $activity ) );
			
			$note = null;
			
			foreach ( $checkinIds as $id ) {
				
				Utilities::get_instance()->log( "Processing checkin ID {$id}" );
				$settings = $this->model->loadSettings( $id );
				
				switch ( $settings->checkin_type ) {
					
					case $this->types['assignment']:
						
						Utilities::get_instance()->log( "Loading data for assignment check-in" );
						$checkin = null;
						break;
					
					case $this->types['survey']:
						
						Utilities::get_instance()->log( "Loading data for survey check-in" );
						// TODO: Load view for survey data (pick up survey(s) from Gravity Forms entry.
						$checkin = null;
						break;
					
					case $this->types['action']:
						
						Utilities::get_instance()->log( "Loading data for daily action check-in & action list" );
						
						$checkin             = $this->model->get_user_checkin( $config, $config->userId, $settings->checkin_type, $settings->short_name );
						$note                = $this->model->get_user_checkin( $config, $config->userId, CHECKIN_NOTE, $settings->short_name );
						$checkin->actionList = $this->model->getActions( $id, $settings->checkin_type, - 3 );
						
						break;
					
					case $this->types['activity']:
						
						Utilities::get_instance()->log( "Loading data for daily activity check-in" );
						$checkin = $this->model->get_user_checkin( $config, $config->userId, $settings->checkin_type, $settings->short_name );
						break;
					
					case $this->types['note']:
						// We handle this in the action check-in.
						Utilities::get_instance()->log( "Explicitly loading data for daily activity note(s)" );
						$note = $this->model->get_user_checkin( $config, $config->userId, CHECKIN_NOTE, $settings->short_name );
						break;
					
					default:
						
						// Load action and acitvity view.
						Utilities::get_instance()->log( "No default action to load!" );
						$checkin = null;
				}
				
				if ( ! empty( $checkin ) ) {
					
					// Reset the value to true Y-m-d format
					$checkin->checkin_date                    = date( 'Y-m-d', strtotime( $checkin->checkin_date ) );
					$this->checkin[ $settings->checkin_type ] = $checkin;
					
					if ( ! $Tracker->isEmpty( $note ) ) {
						
						Utilities::get_instance()->log( "Including data for daily progress note" );
						$this->checkin[ CHECKIN_NOTE ] = $note;
					}
				}
				
			} // End of foreach()
			
			Utilities::get_instance()->log( "Loading checkin for user {$config->userId} and delay {$config->delay}.." . print_r( $this->checkin, true ) );
			
			return $this->load_UserCheckin( $config, $this->checkin );
		}
		
		if ( strtolower( $config->type ) == 'assignment' ) {
			
			Utilities::get_instance()->log( "Processing assignment" );
			
			if ( $config->articleId === false ) {
				
				Utilities::get_instance()->log( "No article defined. Quitting." );
				
				return null;
			}
			
			Utilities::get_instance()->log( "Loading pre-existing data for the lesson/assignment " );
			
			// TODO: Decide whether or not the daily assignment is supposed to be a survey or not.
			$assignments = $Article->getAssignments( $config->articleId, $config->userId );
			
			if ( true === $config->is_survey ) {
				
				Utilities::get_instance()->log( "We're being asked to render a survey with the article" );
				
			}
			
			return $Assignment->showAssignment( $assignments, $config );
		}
		
		if ( strtolower( $config->type == 'show_assignment' ) ) {
			
			Utilities::get_instance()->log( "Processing display of assignments status" );
			
			Utilities::get_instance()->log( "Loading Assignment list" );
			
			return $Assignment->listUserAssignments( $config );
		}
		
		if ( strtolower( $config->type == 'survey' ) ) {
			
			Utilities::get_instance()->log( "Process a survey assignment/page" );
			
			if ( $config->articleId === false ) {
				
				Utilities::get_instance()->log( "No article defined. Quitting." );
				
				return null;
			}
			
			Utilities::get_instance()->log( "Loading pre-existing data for the lesson/assignment " );
			
			$assignments = $Article->getAssignments( $config->articleId, $config->userId );
			Utilities::get_instance()->log( "Found assignments: " . print_r( $assignments, true ) );
			
			return $Assignment->showAssignment( $assignments, $config );
			
		}
	}
	
	/**
	 * Create a default (empty) check-in array
	 *
	 * @return array
	 */
	public function load_default_checkins() {
		
		$defaults = array();
		
		$defaults[ CHECKIN_ACTION ]     = $this->model->defaultCheckin( CHECKIN_ACTION );
		$defaults[ CHECKIN_ASSIGNMENT ] = $this->model->defaultCheckin( CHECKIN_ASSIGNMENT );
		$defaults[ CHECKIN_NOTE ]       = $this->model->defaultCheckin( CHECKIN_NOTE );
		
		return $defaults;
	}
	
	/**
	 * Test whether the user has completed the assignment for the article on the specific delay (day)
	 *
	 * @param int      $articleId
	 * @param null|int $postId
	 * @param null|int $userId
	 * @param null|int $delay
	 *
	 * @return bool
	 */
	public function hasCompletedLesson( $articleId, $postId = null, $userId = null, $delay = null ) {
		
		Utilities::get_instance()->log( "Verify whether the current UserId has checked in for this lesson." );
		
		global $currentArticle;
		global $currentProgram;
		
		$Article = Article::getInstance();
		$Program = Program::getInstance();
		
		$config = new \stdClass();
		
		if ( is_null( $postId ) ) {
			
			global $post;
			$postId = ( isset( $post->ID ) ? $post->ID : null );
		}
		
		if ( empty( $currentProgram->id ) ) {
			
			$Program->getProgramIdForUser( $userId );
		}
		
		Utilities::get_instance()->log( "Requested post ID #{$postId} v.s. currentArticle post_id: {$currentArticle->post_id}" );
		
		if ( isset( $currentArticle->post_id ) && ( $currentArticle->post_id != $postId ) ) {
			
			Utilities::get_instance()->log( "loading settings for article post #{$postId} (ID)" );
			$Article->init( $postId );
		}
		
		$config->articleId = $currentArticle->id;
		
		if ( ! isset( $config->articleId ) ) {
			
			Utilities::get_instance()->log( "No article ID defined..? Exiting." );
			
			return false;
		}
		
		if ( $delay !== null ) {
			
			$config->delay = $delay;
		} else {
			$config->delay = $Article->releaseDay( $config->articleId );
		}
		
		Utilities::get_instance()->log( "Check if the database indicates a completed lesson..." );
		$checkin = $this->model->get_user_checkin( $config, $userId, CHECKIN_ASSIGNMENT );
		
		if ( isset( $checkin->checkedin ) && ( $checkin->checkedin == 1 ) ) {
			
			Utilities::get_instance()->log( "User has completed this check-in." );
			
			return true;
		} else {
			
			Utilities::get_instance()->log( "No user check-in found." );
			
			return false;
		}
		
		// return $this->model->lessonComplete( $this->articleId );
		
	}
	
	/**
	 * Load the check-in card for a user
	 *
	 * @param array $config
	 * @param array $checkinArr
	 *
	 * @return null|string
	 */
	public function load_UserCheckin( $config, $checkinArr ) {
		
		$Tracker = Tracker::getInstance();
		
		$action     = null;
		$activity   = null;
		$assignment = null;
		$note       = null;
		$survey     = null;
		$view       = null;
		
		Utilities::get_instance()->log( "For type: {$config->type}" );
		
		if ( ! empty( $checkinArr ) ) {
			
			Utilities::get_instance()->log( "Array of checkin values isn't empty..." );
			
			foreach ( $checkinArr as $type => $c ) {
				
				Utilities::get_instance()->log( "Loading view type {$type} for checkin" );
				
				if ( $type == CHECKIN_ACTION ) {
					
					Utilities::get_instance()->log( "Loading Action checkin data" );
					$action = $c;
				}
				
				if ( $type == CHECKIN_ACTIVITY ) {
					
					Utilities::get_instance()->log( "Loading Activity checkin data" );
					$activity = $c;
				}
				
				if ( $type == CHECKIN_NOTE ) {
					
					Utilities::get_instance()->log( "Loading check-in note(s)" );
					$note = $c;
				}
				
				if ( $type == CHECKIN_ASSIGNMENT ) {
					$assignment = $c;
				}
				
				if ( $type == CHECKIN_SURVEY ) {
					$survey = $c;
				}
				
			} // End of foreach()
			
			if ( ( ! $Tracker->isEmpty( $action ) ) && ( ! $Tracker->isEmpty( $activity ) ) ) {
				
				Utilities::get_instance()->log( "Loading the view for the Dashboard" );
				
				if ( ! isset( $config->use_cards ) || ( false === $config->use_cards ) ) {
					
					Utilities::get_instance()->log( "Using old view layout" );
					$view = $this->view->view_actionAndActivityCheckin( $config, $action, $activity, $action->actionList, $note );
				} else if ( true === $config->use_cards ) {
					
					Utilities::get_instance()->log( "Using new view layout" );
					$view = $this->view->view_action_and_activity( $config, $action, $activity, $action->actionList, $note );
				}
			}
			
		} // else if ( ( $config->type == 'action' ) || ( $config->type == 'activity' ) ) {
		else if ( ( $config->type == $this->getTypeDescr( CHECKIN_ACTION ) ) ||
		          ( $config->type == $this->getTypeDescr( CHECKIN_ACTIVITY ) )
		) {
			
			Utilities::get_instance()->log( "An activity or action check-in requested..." );
			if ( ! isset( $config->use_cards ) || ( false === $config->use_cards ) ) {
				
				Utilities::get_instance()->log( "Using old view layout" );
				$view = $this->view->view_actionAndActivityCheckin( $config, $action, $activity, $action->actionList, $note );
			} else if ( true === $config->use_cards ) {
				
				Utilities::get_instance()->log( "Using new view layout" );
				$view = $this->view->view_action_and_activity( $config, $action, $activity, $action->actionList, $note );
			}
		}
		
		return $view;
	}
	
	/**
	 * Load all peers for the check-in
	 *
	 * @param null|int $checkinId
	 *
	 * @return array
	 */
	public function getPeers( $checkinId = null ) {
		
		if ( is_null( $checkinId ) ) {
			
			global $post;
			// Use the parent value for the current post to get all of its peers.
			$checkinId = $post->post_parent;
		}
		
		$checkins = new \WP_Query( array(
			'post_type'      => 'page',
			'post_parent'    => $checkinId,
			'posts_per_page' => - 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		$checkinList = array(
			'pages' => $checkins->posts,
		);
		
		foreach ( $checkinList['pages'] as $k => $v ) {
			
			if ( $v == get_the_ID() ) {
				
				if ( isset( $checkins->posts[ $k - 1 ] ) ) {
					
					$checkinList['prev'] = $checkins->posts[ $k - 1 ];
				}
				
				if ( isset( $checkins->posts[ $k + 1 ] ) ) {
					
					$checkinList['next'] = $checkins->posts[ $k + 1 ];
				}
			}
		}
		
		wp_reset_postdata();
		
		return $checkinList;
	}
	
	/**
	 * Add metabox for the Action edit page
	 *
	 * @param \WP_Post $post
	 */
	public function editor_metabox_setup( $post ) {
		
		if ( isset( $post->ID ) ) {
			$this->init( $post->ID );
		}
		
		add_meta_box( 'e20r-tracker-checkin-settings', __( 'Action Settings', 'e20r-tracker' ), array(
			&$this,
			"addMeta_Settings",
		), Action_Model::post_type, 'normal', 'high' );
		
	}
	
	/**
	 * Load the settings for the program(s)
	 *
	 * @since 3.0 - BUG FIX: Didn't return and empty array of Program posts
	 */
	public function addMeta_Settings() {
		
		global $post;
		
		$def_status = array(
			'publish',
			'pending',
			'draft',
			'future',
			'private',
		);
		
		// Query to load all available programs (used with check-in definition)
		$query = array(
			'post_type'      => Program_Model::post_type,
			'post_status'    => apply_filters( 'e20r_tracker_checkin_status', $def_status ),
			'posts_per_page' => - 1,
		);
		
		wp_reset_query();
		
		//  Fetch Programs
		$checkins = get_posts( $query );
		
		if ( empty( $checkins ) ) {
			
			Utilities::get_instance()->log( "No programs found!" );
			$checkins = array();
		}
		
		wp_reset_postdata();
		
		Utilities::get_instance()->log( "Loading settings metabox for checkin page {$post->ID}" );
		$settings = $this->model->loadSettings( $post->ID );
		
		echo $this->view->viewSettingsBox( $settings, $checkins );
	}
	
	/**
	 * Save the Action settings
	 *
	 * @param int $post_id
	 *
	 * @return bool|int|void
	 */
	public function saveSettings( $post_id ) {
		
		$post = get_post( $post_id );
		
		if ( isset( $post->post_type ) && ( $post->post_type == $this->model->get_slug() ) ) {
			
			setup_postdata( $post );
			
			$this->model->set( 'short_name', get_the_title( $post_id ) );
			$this->model->set( 'item_text', get_the_excerpt() );
		}
		
		parent::saveSettings( $post_id );
	}
	
	/**
	 * Calculates the # of days the check-in was supposed to be active for
	 *
	 * @param mixed $checkin
	 *
	 * @return int
	 */
	private function days_of_action( $checkin ) {
		
		if ( $checkin->enddate <= date( 'Y-m-d', current_time( 'timestamp' ) ) ) {
			
			$days_to_add = $checkin->maxcount;
		} else {
			// Calculate the # of days passed since start of current action
			$days_to_add = Time_Calculations::daysBetween( strtotime( $checkin->startdate . " 00:00:00" ), current_time( 'timestamp' ), get_option( 'timezone_string' ) );
		}
		
		return $days_to_add;
	}
}