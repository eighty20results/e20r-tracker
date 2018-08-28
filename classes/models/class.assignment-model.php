<?php

namespace E20R\Tracker\Models;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Tracker;
use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Tracker_Access;

class Assignment_Model extends Settings_Model {
	
	/**
	 * The registered Custom Post Type for Assignments
	 */
	const post_type = 'e20r_assignments';
	
	/**
	 * @var array
	 */
	private $settings;
	/**
	 * @var array
	 */
	private $answerTypes;
	
	/**
	 * @var array
	 */
	private $answerInputs;
	
	/**
	 * Assignment_Model constructor.
	 */
	public function __construct() {
		
		$Tables = Tables::getInstance();
		
		try {
			parent::__construct( 'assignments', Assignment_Model::post_type );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to instantiate the Assignemnt_Model class: " . $exception->getMessage() );
			
			return false;
		}
		
		$this->answerInputs = array(
			0 => 'button',
			1 => 'input',
			2 => 'textbox',
//		    3 => 'checkbox',
			4 => 'multichoice',
			5 => 'rank',
			6 => 'yesno',
			7 => 'html',
		);
		
		$this->answerTypes = array(
			0 => __( "'Lesson complete' button", "e20r-tracker" ),
			1 => __( "Line of text (input)", "e20r-tracker" ),
			2 => __( "Paragraph of text (textbox)", "e20r-tracker" ),
//		    3 => __("Checkbox", "e20r-tracker"),
			4 => __( "Multiple choice", "e20r-tracker" ),
			5 => __( "1-10 ranking", "e20r-tracker" ),
			6 => __( "Yes/No question", "e20r-tracker" ),
			7 => __( "HTML/Text Field", "e20r-tracker" ),
		);
		
		try {
			$this->table = $Tables->getTable( 'assignments' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to locate the assignments table: " . $exception->getMessage() );
			
			return false;
		}
		
		$this->fields = $Tables->getFields( 'assignments' );
		
		return $this;
	}
	
	/**
	 * Register the Assignment post type
	 */
	public static function registerCPT() {
		
		$labels = array(
			'name'               => __( 'Assignments', 'e20r-tracker' ),
			'singular_name'      => __( 'Assignment', 'e20r-tracker' ),
			'slug'               => self::post_type,
			'add_new'            => __( 'New Assignment', 'e20r-tracker' ),
			'add_new_item'       => __( 'New Assignment', 'e20r-tracker' ),
			'edit'               => __( 'Edit assignments', 'e20r-tracker' ),
			'edit_item'          => __( 'Edit Assignment', 'e20r-tracker' ),
			'new_item'           => __( 'Add New', 'e20r-tracker' ),
			'view'               => __( 'View Assignments', 'e20r-tracker' ),
			'view_item'          => __( 'View This Assignment', 'e20r-tracker' ),
			'search_items'       => __( 'Search Assignments', 'e20r-tracker' ),
			'not_found'          => __( 'No Assignments Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Assignment Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type( self::post_type,
			array(
				'labels'             => apply_filters( 'e20r-tracker-assignments-cpt-labels', $labels ),
				'public'             => false,
				'show_ui'            => true,
				// 'show_in_menu' => true,
				'menu_icon'          => '',
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'supports'           => array( 'title', 'excerpt' ),
				'can_export'         => true,
				'show_in_nav_menus'  => false,
				'show_in_menu'       => 'e20r-tracker-articles',
				'rewrite'            => array(
					'slug'       => apply_filters( 'e20r-tracker-assignments-cpt-slug', 'tracker-assignments' ),
					'with_front' => false,
				),
				'has_archive'        => apply_filters( 'e20r-tracker-assignments-cpt-archive-slug', 'tracker-assignments-archive' ),
			)
		);
		
		if ( is_wp_error( $error ) ) {
			Utilities::get_instance()->log( 'ERROR: Failed to register e20r_assignments CPT: ' . $error->get_error_message );
		}
	}
	
	/**
	 * Returns the Answer description (descriptive text to pair with the assignment question)
	 *
	 * @return array
	 */
	public function getAnswerDescriptions() {
		
		return $this->answerTypes;
	}
	
	/**
	 * Return the input type (for the specified input key)
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getInputType( $key ) {
		
		return $this->answerInputs[ $key ];
	}
	
	/**
	 * Save assignment (from user)
	 *
	 * @param array $aArray
	 *
	 * @return bool
	 */
	public function saveUserAssignment( $aArray ) {
		
		global $wpdb;
		
		if ( ( $result = $this->exists( $aArray ) ) !== false ) {
			Utilities::get_instance()->log( "Found existing record: {$result->id}" );
			
			$aArray['id'] = $result->id;
		}
		
		$state = $wpdb->replace( $this->table, $aArray );
		
		Utilities::get_instance()->log( "State: ({$state})" );
		
		if ( ! $state ) {
			Utilities::get_instance()->log( "Error: " . $wpdb->last_error );
		}
		
		return ( $state ? true : false );
	}
	
	/**
	 * Check if the received assignment already exists in the e20r_assignments DB table
	 *
	 * @param array $assignment
	 *
	 * @return \stdClass|bool
	 */
	public function exists( $assignment ) {
		
		global $wpdb;
		
		if ( ! is_array( $assignment ) ) {
			
			Utilities::get_instance()->log( " Incorrect data received!" );
			
			return false;
		}
		
		$sql = $wpdb->prepare(
			"SELECT id, answer
                FROM {$this->table}
                WHERE (
                ( {$this->fields['user_id']} = %d ) AND
                ( {$this->fields['delay']} = %d ) AND
                ( {$this->fields['program_id']} = %d ) AND
                ( {$this->fields['article_id']} = %d ) AND
                ( {$this->fields['question_id']} = %d )
                )
           ",
			$assignment['user_id'],
			$assignment['delay'],
			$assignment['program_id'],
			$assignment['article_id'],
			$assignment['question_id']
		);
		
		$result = $wpdb->get_row( $sql );
		
		if ( ! empty( $result ) ) {
			
			Utilities::get_instance()->log( "Got a result returned. " );
			
			return $result;
		} else if ( $result === false ) {
			
			Utilities::get_instance()->log( "Error: " . $wpdb->last_error );
		}
		
		return false;
	}
	
	/**
	 * Build the default assignment ("Lesson complete")
	 *
	 * @param \stdClass $article
	 * @param string    $title
	 *
	 * @return bool|int|\WP_Error
	 */
	public function createDefaultAssignment( $article, $title ) {
		
		$postDef = array(
			'post_title'     => "Lesson complete for {$title}",
			'post_excerpt'   => '',
			'post_type'      => self::post_type,
			'post_status'    => 'publish',
			'post_date'      => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		
		$exists = get_page_by_title( "Lesson complete for {$title}" );
		
		if ( ! empty( $exists ) ) {
			
			return false;
		}
		
		$assignment     = $this->defaultSettings();
		$assignment->id = wp_insert_post( $postDef );
		
		if ( 0 != $assignment->id ) {
			
			$assignment->question_id = $assignment->id;
			$assignment->order_num   = 1;
			$assignment->field_type  = 0; // Lesson complete button
			$assignment->article_ids = array( $article->id );
			$assignment->delay       = $article->release_day;
			
			if ( ! $this->saveSettings( $assignment ) ) {
				
				Utilities::get_instance()->log( "Error saving assignment settings ({$assignment->id}) for Article # {$article->id}" );
				
				return false;
			}
			
			Utilities::get_instance()->log( "Saved new assignment Article # {$article->id}: {$assignment->id}" );
			
			return $assignment->id; // Return the new assignment ID.
		}
		
		Utilities::get_instance()->log( "Error adding post (assignment) for article # {$article->id}" );
		
		return false;
	}
	
	/**
	 * Create the default settings for an assignment
	 *
	 * @return \stdClass
	 */
	public function defaultSettings() {
		
		$settings = parent::defaultSettings();
		
		$settings->id          = null;
		$settings->descr       = null;
		$settings->order_num   = 1;
		$settings->question    = null;
		$settings->question_id = null;
		$settings->delay       = null;
		$settings->field_type  = 0;
		// $settings->user_id = $current_user->ID;
		$settings->article_ids        = array();
		$settings->program_ids        = array();
		$settings->answer_date        = null;
		$settings->answer             = null;
		$settings->select_options     = array();
		$settings->messages           = array();
		$settings->new_messages       = false;
		$settings->thread_is_archived = false;
		
		return $settings;
	}
	
	/**
	 * Save the Assignment Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific assignment.
	 *
	 * @return bool - True if successful at updating assignment settings
	 */
	public function saveSettings( $settings ) {
		
		$assignmentId = $settings->id;
		
		$defaults = $this->defaultSettings();
		
		Utilities::get_instance()->log( "Saving assignment Metadata: " . print_r( $settings, true ) );
		
		$error = false;
		
		foreach ( $defaults as $key => $value ) {
			
			if ( in_array( $key, array( 'id', 'descr', 'question', 'program_id' ) ) ) {
				continue;
			}
			
			if ( false === $this->settings( $assignmentId, 'update', $key, $settings->{$key} ) ) {
				
				Utilities::get_instance()->log( "ERROR saving {$key} setting ({$settings->{$key}}) for check-in definition with ID: {$assignmentId}" );
				
				$error = true;
			}
		}
		
		return ( ! $error );
	}
	
	/**
	 * Return the assignment question for the specified assignment (ID)
	 *
	 * @param int $assignment_id
	 *
	 * @return string
	 */
	public function get_assignment_question( $assignment_id ) {
		
		$text = get_the_title( $assignment_id );
		wp_reset_postdata();
		
		return $text;
	}
	
	/**
	 * Paginated loading of all user assignments
	 *
	 * @param int $userId
	 * @param int $page_num
	 *
	 * @return array|bool
	 */
	public function loadAllUserAssignments( $userId, $page_num = - 1 ) {
		
		$Tracker = Tracker::getInstance();
		$Program = Program::getInstance();
		
		$delay     = $Tracker->getDelay( 'now', $userId );
		$programId = $Program->getProgramIdForUser( $userId );
		
		Utilities::get_instance()->log( "Loading assignments for user {$userId} until day {$delay} for program {$programId} for page num: {$page_num}" );
		
		$assignments = $this->loadAssignmentByMeta( $programId, 'delay', $delay, '<=', 'numeric', 'delay', 'DESC', $page_num );
		
		Utilities::get_instance()->log( "Returned " . count( $assignments ) . " to process up to the {$delay} program day: " . print_r( $assignments, true ) );
		
		if ( empty( $assignments ) ) {
			
			Utilities::get_instance()->log( "No records found." );
			
			return false;
		}
		
		$answers = array();
		
		// Transfer config settings...
		if ( isset( $assignments['max_num_pages'] ) ) {
			$answers['max_num_pages'] = $assignments['max_num_pages'];
			unset( $assignments['max_num_pages'] );
		}
		
		if ( isset( $assignments['current_page'] ) ) {
			$answers['current_page'] = $assignments['current_page'];
			unset( $assignments['current_page'] );
		}
		
		foreach ( $assignments as $assignment ) {
			
			if ( ! empty( $assignment->article_ids ) && ( $key = array_search( 0, $assignment->article_ids ) ) !== false ) {
				
				Utilities::get_instance()->log( "Assignment has a 0 for an article_ids value. Removing that value." );
				unset( $assignment->article_ids[ $key ] );
			}
			
			// Process this assignment if it's NOT a "I've read it" button.
			if ( isset( $assignment->field_type ) && 0 != $assignment->field_type ) {
				
				Utilities::get_instance()->log( "Assignment information being processed:" );
				// Utilities::get_instance()->log($assignment);
				
				if ( count( $assignment->article_ids ) < 1 ) {
					
					Utilities::get_instance()->log( "ERROR: No user assignments defined for {$assignment->question_id}!" );
					$assignment->article_ids = array();
					
				}
				// TODO: Unroll loop to simplify fetching assignment repsponses for the user
				foreach ( $assignment->article_ids as $userAId ) {
					
					// $userInfo = $this->loadUserAssignment( $userAId, $userId, $assignment->delay, $assignment->id);
					$userInfo = $this->load_user_assignment_info( $userId, $assignment->id, $userAId );
					Utilities::get_instance()->log( "Returned assignment info for user: {$userId} and assignment {$assignment->id}" );
					
					foreach ( $userInfo as $k => $data ) {
						
						$assignment->id                 = isset( $data->id ) ? $data->id : null;
						$assignment->answer             = isset( $data->answer ) ? $data->answer : null;
						$assignment->answer_date        = isset( $data->answer_date ) ? $data->answer_date : null;
						$assignment->article_id         = $userAId;
						$assignment->messages           = isset( $data->messages ) ? $data->messages : null;
						$assignment->new_messages       = isset( $data->new_messages ) ? $data->new_messages : false;
						$assignment->thread_is_archived = isset( $data->thread_is_archived ) ? $data->thread_is_archived : false;
						$answers[]                      = $assignment;
					}
				}
			}
		}
		
		// Sort the answers by the delay value, then the order_num value
		// $answers = $Tracker->sortByFields( $answers, array( 'delay', 'order_num' ) );
		
		Utilities::get_instance()->log( "Returning sorted array of answers: " );
		
		return $answers;
	}
	
	/**
	 * Query and paginate the Assignment data for a program / user
	 *
	 * @param int    $programId
	 * @param mixed  $key
	 * @param mixed  $value
	 * @param string $comp
	 * @param string $type
	 * @param string $orderbyKey
	 * @param string $order
	 *
	 * @return array    - Array of assignments + the max number of pages (for pagination) in the `max_num_pages` array
	 *                  key
	 *
	 * @since 3.0 - BUG FIX: Didn't consistently sort the assignments
	 */
	private function loadAssignmentByMeta( $programId, $key, $value, $comp = '=', $type = 'numeric', $orderbyKey = 'order_num', $order = 'ASC', $page_num = - 1 ) {
		
		$Article = Article::getInstance();
		$Tracker = Tracker::getInstance();
		
		$assignments = array();
		
		Utilities::get_instance()->log( "For program #: {$programId}" );
		
		$items = apply_filters( 'e20r-tracker-items-per-page', 20 );
		
		$page = 1;
		
		if ( - 1 === $page_num ) {
			$pg = get_query_var( 'paged' );
			
			if ( ! empty( $pg ) || 1 == $pg ) {
				$page = get_query_var( 'paged' );
			}
		} else {
			Utilities::get_instance()->log( "Received Page number request {$page_num}" );
			$page = $page_num;
		}
		
		$args = array(
			'posts_per_page' => ( empty( $items ) ? - 1 : $items ),
			'paged'          => $page,
			'post_type'      => self::post_type,
			'post_status'    => 'publish',
			'meta_key'       => "_e20r-{$this->type}-{$orderbyKey}",
			'order_by'       => 'meta_value_num',
			'order'          => $order,
			'meta_query'     => array(
				array(
					'key'     => "_e20r-{$this->type}-{$key}",
					'value'   => $value,
					'compare' => $comp,
					'type'    => $type,
				),
				array(
					'key'     => "_e20r-{$this->type}-program_ids",
					'value'   => $programId,
					'compare' => '=',
					'type'    => 'numeric',
				),
			),
		);
		
		if ( 'field_type' != $key ) {
			
			$args['meta_query'][] = array(
				'key'     => "_e20r-{$this->type}-field_type",
				'value'   => 0,
				'compare' => '!=',
				'type'    => 'numeric',
			);
		}
		Utilities::get_instance()->log( "Using:" . print_r( $args, true ) );
		
		$query     = new \WP_Query( $args );
		$num_pages = $query->max_num_pages;
		
		global $e20r_tracker_sort_params;
		
		$e20r_tracker_sort_params = array( 'key' => $orderbyKey, 'order' => $order );
		Utilities::get_instance()->log( "Returned {$query->post_count} {$this->cpt_slug} records. Page # {$page}" );
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			// $new = new stdClass();
			$assignment_id = get_the_ID();
			
			$new = $this->loadSettings( $assignment_id );
			
			if ( empty( $new->article_ids ) || ! isset( $new->article_ids ) || in_array( 0, $new->article_ids ) ) {
				
				Utilities::get_instance()->log( "Loading articles which have had {$assignment_id} assigned to it" );
				$tmp          = $Article->findArticles( 'assignment_ids', $assignment_id, $programId );
				$article_list = array();
				
				foreach ( $tmp as $art ) {
					
					if ( ! empty( $art->id ) && ( 0 != $art->id ) ) {
						Utilities::get_instance()->log( "Article definitions received: " );
						//Utilities::get_instance()->log($tmp);
						$article_list[] = $art->id;
					}
				}
				
				$new->article_ids = $article_list;
			}
			
			$new->id       = ! empty( $assignment_id ) ? $assignment_id : null;
			$new->descr    = $query->post->post_excerpt;
			$new->question = $query->post->post_title;
			
			$assignments[] = $new;
		}
		
		Utilities::get_instance()->log( "Sorting the records. Using " . print_r( $e20r_tracker_sort_params, true ) );
		usort( $assignments, array( $this, 'sortAssignments' ) );
		
		Utilities::get_instance()->log( "Returning " .
		                                count( $assignments ) . " records to: " . $Tracker->whoCalledMe() );
		
		wp_reset_postdata();
		
		$assignments['max_num_pages'] = $num_pages;
		$assignments['current_page']  = $page;
		
		return $assignments;
	}
	
	/**
	 * Load settings for the specified assignment
	 *
	 * @param null|int $id
	 *
	 * @return \stdClass
	 */
	public function loadSettings( $id = null ) {
		
		global $post;
		global $currentAssignment;
		
		if ( isset( $currentAssignment->id ) && ( $currentAssignment->id == $id ) ) {
			
			return $currentAssignment;
		}
		
		if ( 0 == $id ) {
			
			$this->settings           = $this->defaultSettings();
			$this->settings->id       = $id;
			$this->settings->question = "Lesson complete (default)";
			
		} else {
			
			$savePost = $post;
			
			Utilities::get_instance()->log( "Loading settings for {$id}" );
			
			$this->settings = parent::loadSettings( $id );
			
			$post = get_post( $id );
			setup_postdata( $post );
			
			if ( ! empty( $post->post_title ) ) {
				
				$this->settings->descr    = $post->post_excerpt;
				$this->settings->question = $post->post_title;
				$this->settings->id       = $post->ID;
			}
			
			if ( ! is_array( $this->settings->program_ids ) && ( ! empty( $this->settings->program_ids ) ) ) {
				$this->settings->program_ids = array( $this->settings->program_ids );
			}
			
			if ( isset( $this->settings->article_id ) ) {
				// Delete this (old setting and no longer used).
				unset( $this->settings->article_id );
			}
			
			if ( ! is_array( $this->settings->select_options ) && ( ! empty( $this->settings->select_options ) ) ) {
				$this->settings->select_options = array( $this->settings->select_options );
			}
			
			wp_reset_postdata();
			$post = $savePost;
		}
		
		if ( empty( $this->settings->field_type ) ) {
			$this->settings->field_type = 0;
		}
		
		$currentAssignment = $this->settings;
		
		return $currentAssignment;
	}
	
	/**
	 * Load the assignment - and response(s) - for the user/assignment combination
	 *
	 * @param int      $user_id
	 * @param int      $assignment_id
	 * @param null|int $article_id
	 *
	 * @return array
	 */
	public function load_user_assignment_info( $user_id, $assignment_id, $article_id = null ) {
		
		global $wpdb;
		global $post;
		
		global $currentProgram;
		
		$Program = Program::getInstance();
		
		Utilities::get_instance()->log( "Attempting to locate info for assignment {$assignment_id}, user {$user_id} and article {$article_id}" );
		
		$save_data = array(
			$this->fields['id'],
			$this->fields['answer_date'],
			$this->fields['answer'],
			$this->fields['user_id'],
			$this->fields['article_id'],
		);
		
		$records      = array();
		$record_count = 0;
		$save_post    = $post;
		$assignment   = null;
		
		if ( empty( $currentProgram->id ) || ( - 1 == $currentProgram->id ) ) {
			$program_id = $Program->getProgramIdForUser( $user_id );
		} else {
			$program_id = $currentProgram->id;
		}
		
		Utilities::get_instance()->log( "Loading data for article # {$article_id} in program {$program_id} for user {$user_id}" );
		
		$assignment_sql = "SELECT a.{$this->fields['id']} AS id,
                            a.{$this->fields['answer_date']} AS answer_date,
                            a.{$this->fields['answer']} AS answer,
                            a.{$this->fields['user_id']} AS recipient_id,
                            a.{$this->fields['question_id']} AS question_id,
                            a.{$this->fields['article_id']} AS article_id
                     FROM {$this->table} AS a
                     WHERE ( ( a.{$this->fields['user_id']} = %d ) AND
                      ( a.{$this->fields['question_id']} = %d ) AND
                      ( a.{$this->fields['program_id']} = %d ) )
                  ORDER BY a.{$this->fields['delay']}";
		
		$assignment_sql = $wpdb->prepare( $assignment_sql, $user_id, $assignment_id, $program_id );
		
		$assignments = $wpdb->get_results( $assignment_sql );
		
		if ( empty( $assignments ) ) {
			
			Utilities::get_instance()->log( "No records found for {$assignment_id} and user {$user_id}" );
			Utilities::get_instance()->log( "Error: " . ( empty( $wpdb->last_error ) ? 'N/A' : $wpdb->last_error ) );
			
			if ( CONST_DEFAULT_ASSIGNMENT != $assignment_id ) {
				
				Utilities::get_instance()->log( "Loading regular settings ({$assignment_id})" );
				$assignment = $this->loadSettings( $assignment_id );
			}
			
			if ( ! isset( $assignment->question_id ) ) {
				Utilities::get_instance()->log( "Loading default settings" );
				$assignment = $this->defaultSettings();
			}
			
			if ( isset( $assignment->id ) ) {
				unset( $assignment->id );
			}
			
			return array( 0 => $assignment );
		}
		
		Utilities::get_instance()->log( "Found " . count( $assignments ) . " records for {$assignment_id} and user {$user_id}" );
		foreach ( $assignments as $key => $r ) {
			
			// Utilities::get_instance()->log("Used SQL: {$assignment_sql}");
			
			$assignment = $this->loadSettings( $r->question_id );
			
			if ( ! empty( $assignment->article_ids ) && ! in_array( $article_id, $assignment->article_ids ) ) {
				Utilities::get_instance()->log( "WARNING: Assignment {$r->question_id} is NOT included in article {$article_id}. Skipping it..." . print_r( $assignment->article_ids, true ) );
				continue;
			}
			
			foreach ( $assignment as $k => $val ) {
				
				if ( ! in_array( $k, $save_data ) ) {
					
					$r->{$k} = $val;
					
					// Special handling of field_type == 4
					if ( ( 'field_type' == $k ) && ( 4 == $val ) ) {
						
						Utilities::get_instance()->log( "Found a multi-choice answer. Restoring it as an array." );
						$r->answer = json_decode( stripslashes( $r->answer ) );
					}
				}
			}
			
			Utilities::get_instance()->log( "Assignment_Model::load_user_assignment_info()- Loading record ID {$r->id} from database result: {$key}" );
			$records[ $record_count ] = $r;
			
			$post = get_post( $r->question_id );
			
			setup_postdata( $post );
			
			$records[ $record_count ]->id                 = $r->id;
			$records[ $record_count ]->descr              = $post->post_excerpt;
			$records[ $record_count ]->question           = $post->post_title;
			$records[ $record_count ]->question_id        = $assignment->question_id;
			$records[ $record_count ]->article_ids        = $assignment->article_ids;
			$records[ $record_count ]->messages           = $this->get_history( $assignment->question_id, $currentProgram->id, $article_id, $user_id );
			$records[ $record_count ]->new_messages       = $this->has_unread_messages( $records[ $record_count ]->messages );
			$records[ $record_count ]->thread_is_archived = $this->thread_is_archived( $records[ $record_count ]->messages );
			
			if ( ! empty( $records[ $record_count ]->article_ids ) && ! in_array( $article_id, $records[ $record_count ]->article_ids ) ) {
				Utilities::get_instance()->log( "Assignment is NOT included in article {$article_id}}. Skipping it" );
				unset( $records[ $record_count ] );
				continue;
			}
			
			$record_count ++;
			// unset($assignments[$key]);
			
			wp_reset_postdata();
		}
		
		$post = $save_post;
		
		return $records;
	}
	
	/**
	 * Returns the message history (emails) for the specified user
	 *
	 * @param int $assignment_id
	 * @param int $program_id
	 * @param int $article_id
	 * @param int $user_id
	 *
	 * @return array|bool
	 */
	public function get_history( $assignment_id, $program_id, $article_id, $user_id ) {
		
		global $wpdb;
		global $current_user;
		
		$Tables = Tables::getInstance();
		$Access = Tracker_Access::getInstance();
		
		try {
			$r_table = $Tables->getTable( 'response' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to find the client/coach response table! Error: " . $exception->getMessage() );
			
			return false;
		}
		
		$r_fields = $Tables->getFields( 'response' );
		
		$user_field = $r_fields['recipient_id'];
		
		if ( $Access->is_a_coach( $current_user->ID ) ) {
			
			Utilities::get_instance()->log( "Setting the user_field to the coach's value" );
			$user_field = $r_fields['client_id'];
		}
		
		Utilities::get_instance()->log( "Loading message history for {$user_id}, assignment: {$assignment_id} in program: {$program_id} and article: {$article_id} with field {$user_field}" );
		
		$sql = $wpdb->prepare( "
            SELECT
                {$r_fields['id']} AS response_id,
                {$r_fields['message_time']} AS message_time,
                {$r_fields['sent_by_id']} AS message_sender_id,
                {$r_fields['recipient_id']} AS recipient_id,
                {$r_fields['message_read']} AS read_status,
                {$r_fields['archived']} AS archived,
                {$r_fields['message']} AS message
            FROM {$r_table}
            WHERE ( {$r_fields['assignment_id']} = %d ) AND
             ( {$r_fields['article_id']} = %d ) AND
             ( {$r_fields['program_id']} = %d ) AND
             ( {$user_field} = %d )
            ORDER BY {$r_fields['message_time']}
        ",
			$assignment_id,
			$article_id,
			$program_id,
			$user_id
		);
		
		$history = $wpdb->get_results( $sql );
		// Utilities::get_instance()->log("Using SQL: {$sql}");
		Utilities::get_instance()->log( "Found " . count( $history ) . " message records for user {$user_id} about assignment {$assignment_id}" );
		
		if ( empty( $history ) ) {
			Utilities::get_instance()->log( "No records found for {$assignment_id} and user {$user_id} as part of program {$program_id}" );
			Utilities::get_instance()->log( "Error: " . ( empty( $wpdb->last_error ) ? 'N/A' : $wpdb->last_error ) );
		}
		
		Utilities::get_instance()->log( "Returning message history for {$user_id} and {$assignment_id} as part of program {$program_id}: " . count( $history ) . " messages" );
		
		return $history;
	}
	
	/**
	 * Check if the list of messages have any unread messages (used on front-end to notify customer)
	 *
	 * @param array $messages
	 *
	 * @return bool
	 *
	 * @access private
	 */
	private function has_unread_messages( $messages ) {
		
		global $current_user;
		
		$unread_messages = true;
		
		Utilities::get_instance()->log( "Counting unread messages..." );
		foreach ( $messages as $message ) {
			
			Utilities::get_instance()->log( "Checking if message # {$message->response_id} is read: {$message->read_status}" );
			
			$unread_messages = $unread_messages && (
					( 0 == $message->read_status ) && ( $current_user->ID != $message->message_sender_id ) &&
					( 0 == $message->archived ) && ( $current_user->ID == $message->recipient_id )
				);
		}
		
		Utilities::get_instance()->log( "Found unread message(s)? " . ( $unread_messages ? 'yes' : 'no' ) );
		
		return $unread_messages;
	}
	
	/**
	 * Is the messaging thread archived?
	 *
	 * @param $messages
	 *
	 * @return bool
	 *
	 * @access private
	 */
	private function thread_is_archived( $messages ) {
		
		global $current_user;
		
		$all_archived = true;
		
		// Make sure all messages are archived
		foreach ( $messages as $message ) {
			
			if ( ( 1 == $message->archived ) && ( $current_user->ID == $message->recipient_id ) ) {
				$all_archived = $all_archived && (bool) $message->archived;
			}
		}
		
		return $all_archived;
	}
	
	/**
	 * Return assignments linked to the specified Article ID
	 *
	 * @param int $articleId
	 *
	 * @return array
	 */
	public function getArticleAssignments( $articleId ) {
		
		$assignments = array();
		
		Utilities::get_instance()->log( "for article #: {$articleId}" );
		
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => self::post_type,
			'post_status'    => 'publish',
			'meta_key'       => '_e20r-assignments-order_num',
			'order_by'       => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_e20r-assignments-article_ids',
					'value'   => $articleId,
					'compare' => '=',
					'type'    => 'numeric',
				),
			),
		);
		
		$query = new \WP_Query( $args );
		
		Utilities::get_instance()->log( "Returned assignments: {$query->post_count}" );
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			$id = get_the_ID();
			
			$new = $this->loadSettings( $id );
			
			$new->descr    = $query->post->post_excerpt;
			$new->question = $query->post->post_title;
			
			$assignments[] = $new;
		}
		
		wp_reset_postdata();
		
		return $assignments;
	}
	
	/**
	 * Load all assignments from the DB
	 *
	 * @return \stdClass[]
	 */
	public function loadAllAssignments() {
		
		global $post;
		
		$assignments = parent::loadAllSettings( 'publish', 'asc' );
		
		if ( empty( $assignments ) ) {
			
			$assignments[0] = $this->defaultSettings();
		}
		
		$savePost = $post;
		
		foreach ( $assignments as $id => $obj ) {
			
			$post = get_post( $id );
			setup_postdata( $post );
			
			$obj->descr    = $post->post_excerpt;
			$obj->question = $post->post_title;
			$obj->id       = $id;
			
			wp_reset_postdata();
			
			$assignments[ $id ] = $obj;
		}
		
		$post = $savePost;
		
		return $assignments;
	}
	
	/**
	 * Load user specific info about the assignment
	 *
	 * @param int      $articleId
	 * @param int      $userId
	 * @param null|int $delay
	 * @param null|int $assignmentId
	 *
	 * @return array
	 */
	public function loadUserAssignment( $articleId, $userId, $delay = null, $assignmentId = null ) {
		
		// TODO: Load the recorded user assignment answers by assignment ID.
		global $wpdb;
		global $post;
		
		$Program    = Program::getInstance();
		$records    = array();
		$assignment = null;
		
		// Don't clobber the user responses
		$resp = array(
			$this->fields['id'],
			$this->fields['answer_date'],
			$this->fields['answer'],
			$this->fields['user_id'],
			$this->fields['article_id'],
		);
		
		// Preserve
		$save_post = $post;
		
		if ( is_null( $userId ) ) {
			
			$result = array();
		} else {
			
			$programId = $Program->getProgramIdForUser( $userId );
			
			Utilities::get_instance()->log( "date for article # {$articleId} in program {$programId} for user {$userId}: {$delay}" );
			
			$sql = "SELECT {$this->fields['id']},
                            {$this->fields['answer_date']},
                            {$this->fields['answer']},
                            {$this->fields['user_id']},
                            {$this->fields['question_id']},
                            {$this->fields['article_id']},
                            {$this->fields['response_id']}
                     FROM {$this->table} AS a
                     WHERE ( ( a.{$this->fields['user_id']} = %d ) AND
                      ( a.{$this->fields['question_id']} = %d ) AND
                      ( a.{$this->fields['program_id']} = %d ) AND
                    " . ( ! is_null( $delay ) ? "( a.{$this->fields['delay']} = " . intval( $delay ) . " ) ) " : null ) .
			       " ORDER BY a.{$this->fields['id']}";
			
			// $sql = $Tracker->prepare_in( $sql, $articleIds, '%d' );
			
			$sql = $wpdb->prepare( $sql,
				$userId,
				$assignmentId,
				$programId
//                $articleId
			);
			
			// Utilities::get_instance()->log("SQL: {$sql}");
			
			$result = $wpdb->get_results( $sql );
		}
		
		Utilities::get_instance()->log( "Loaded " . count( $result ) . " check-in records" );
		$record_count = 0;
		
		if ( ! empty( $result ) ) {
			
			// Index the result array by the ID of the assignment (key)
			foreach ( $result as $key => $data ) {
				
				Utilities::get_instance()->log( "Loading config first for assignment #{$data->question_id} on behalf of record ID {$data->id}" );
				$assignment = $this->loadSettings( $data->question_id );
				
				foreach ( $assignment as $k => $val ) {
					
					if ( ! in_array( $k, $resp ) ) {
						
						if ( 'article_ids' == $k ) {
							
							if ( ! empty( $val ) ) {
								
								$data->{$k}[] = $val;
								
								Utilities::get_instance()->log( "{$k} => " . print_r( $val, true ) );
								
								Utilities::get_instance()->log( "Assignment_Model::loadUserAssignment()- Loading article {$val} based message history for user {$userId} regarding {$data->question_id}" );
								$data->message_history[ $val ] = $this->get_history( $assignment->question_id, $data->program_id, $val, $userId );
								$data->new_messages[ $val ]    = $this->has_unread_messages( $data->message_history[ $val ] );
								
							} else {
								$data->{$k} = array();
								
							}
							
						} else {
							$data->{$k} = $val;
						}
						
						// Special handling of field_type == 4
						if ( ( 'field_type' == $k ) && ( 4 == $val ) ) {
							
							Utilities::get_instance()->log( "Found a multi-choice answer. Restoring it as an array." );
							$data->answer = json_decode( stripslashes( $data->answer ) );
							
						}
					}
				}
				
				Utilities::get_instance()->log( "Assignment_Model::loadUserAssignment()- Loading record ID {$data->id} from database result: {$key}" );
				$records[ $record_count ] = $data;
				
				$post = get_post( $data->question_id );
				
				setup_postdata( $post );
				
				$records[ $record_count ]->id              = $data->id;
				$records[ $record_count ]->descr           = $post->post_excerpt;
				$records[ $record_count ]->question        = $post->post_title;
				$records[ $record_count ]->question_id     = $assignment->question_id;
				$records[ $record_count ]->article_ids     = $assignment->article_ids;
				$records[ $record_count ]->message_history = $data->message_history;
				$records[ $record_count ]->new_messages    = $data->new_messages;
				// $records[$record_count]->message_history = array();
				
				unset( $result[ $key ] );
				
				$record_count ++;
				
				// Array is now indexed by record/post/assignment ID
				wp_reset_postdata();
			}
		} else {
			Utilities::get_instance()->log( "No user data returned. {$wpdb->last_error}" );
			
			if ( CONST_DEFAULT_ASSIGNMENT != $assignmentId ) {
				
				Utilities::get_instance()->log( "Loading settings ({$assignmentId})" );
				$assignment = $this->loadSettings( $assignmentId );
			}
			
			if ( ! isset( $assignment->question_id ) ) {
				Utilities::get_instance()->log( "Loading default settings" );
				$assignment = $this->defaultSettings();
			}
			
			if ( isset( $assignment->id ) ) {
				unset( $assignment->id );
			};
			
			$records = array( 0 => $assignment );
		}
		
		// Restore
		$post = $save_post;
		
		return $records;
	}
	
	/**
	 * Update message reply status
	 *
	 * @param int   $message_id
	 * @param int   $status
	 * @param mixed $status_field
	 *
	 * @return bool
	 */
	public function update_reply_status( $message_id, $status, $status_field ) {
		
		global $wpdb;
		$Tables = Tables::getInstance();
		
		try {
			$table = $Tables->getTable( 'response' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to find the client/coach response table! Error: " . $exception->getMessage() );
			
			return false;
		}
		
		$fields = $Tables->getFields( 'response' );
		
		$status = sprintf( '%d', $status );
		$data   = array( $status_field => $status );
		
		if ( $status_field == $fields['archived'] ) {
			
			$data = array(
				$status_field           => $status,
				$fields['message_read'] => 1,
			);
		}
		
		Utilities::get_instance()->log( "Setting {$status_field} to: {$status} for message ID {$message_id}" );
		
		$result = $wpdb->update(
			$table,
			$data,
			array( "{$fields['id']}" => $message_id ),
			array( '%d' ),
			array( '%d' )
		);
		
		if ( false === $result ) {
			Utilities::get_instance()->log( "ERROR: Unable to update status to {$status} for record # {$message_id} in {$table}: {$wpdb->last_error}" );
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check if there are new messages to/from the $client_id
	 *
	 * @param int $client_id
	 *
	 * @return bool|int
	 */
	public function user_has_new_messages( $client_id ) {
		
		$Tables = Tables::getInstance();
		
		global $currentProgram;
		
		global $current_user;
		global $wpdb;
		
		try {
			$table = $Tables->getTable( 'response' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to find the client/coach response table! Error: " . $exception->getMessage() );
			
			return false;
		}
		
		$fields = $Tables->getFields( 'response' );
		
		$sql = "SELECT COUNT({$fields['id']}) AS unread_messages FROM {$table} WHERE (
                  ({$fields['program_id']} = %d) AND
                  ({$fields['recipient_id']} = %d) AND
                  ({$fields['client_id']} = %d) AND
                  ({$fields['sent_by_id']} <> %d) AND
                  ({$fields['message_read']} = 0 AND {$fields['archived']} = 0)
                )";
		
		$sql = $wpdb->prepare( $sql, $currentProgram->id, $client_id, $client_id, $current_user->ID );
		
		$unread_messages = $wpdb->get_var( $sql );
		
		Utilities::get_instance()->log( "Has {$unread_messages} new/unread messages" );
		if ( is_null( $unread_messages ) ) {
			return false;
		}
		
		return $unread_messages;
	}
	
	/**
	 * Save the response from the user/coach to the database
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function save_response( $data ) {
		
		$Tracker = Tracker::getInstance();
		$Tables  = Tables::getInstance();
		
		global $wpdb;
		
		try {
			$reply_table      = $Tables->getTable( 'response' );
			$assignment_table = $Tables->getTable( 'assignments' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( 'Unable to locate a table: ' . $exception->getMessage() );
			
			return false;
		}
//        $reply_fields = $Tables->getFields('response');
		$assignment_fields = $Tables->getFields( 'assignments' );
		
		try {
			$format = $Tracker->setFormatForRecord( $data );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to define the data record format. Error: " . $exception->getMessage() );
			
			return false;
		}
		
		if ( array_key_exists( 'record_id', $data ) ) {
			
			$assignment_record_id = $data['record_id'];
			unset( $data['record_id'] );
		} else {
			Utilities::get_instance()->log( "ERROR: No record ID found for existing (saved) assignment record in {$assignment_table}" );
			
			return false;
		}
		
		Utilities::get_instance()->log( "Attempting to add response to {$reply_table}: " .print_r( $data, true ) );
		Utilities::get_instance()->log( print_r( $format, true ) );
		
		if ( false === $wpdb->insert( $reply_table, $data, $format ) ) {
			
			Utilities::get_instance()->log( "ERROR: Unable to save response data: {$wpdb->last_error} for query: " . $wpdb->last_query );
			
			return false;
		}
		
		$id = $wpdb->insert_id;
		Utilities::get_instance()->log( "Successfully inserted new reply in {$reply_table} with ID {$id}" );
		Utilities::get_instance()->log( "Attempting to update saved assignment {$assignment_record_id} in {$assignment_table} to reflect new response {$id}" );
		
		$updated = $wpdb->update( $assignment_table,
			array( "{$assignment_fields['response_id']}" => $id ),
			array( 'id' => $assignment_record_id ),
			array( '%d' ),
			array( '%d' )
		);
		
		if ( false === $updated ) {
			Utilities::get_instance()->log( "ERROR: Unable to update assignment record # {$data['assignment_id']} in {$assignment_table}: " . $wpdb->last_error . ' for query: ' . $wpdb->last_query );
			
			return false;
		}
		Utilities::get_instance()->log( "Returning success to calling function: " . $Tracker->whoCalledMe() );
		
		return true;
	}
	
	/**
	 * Sorter for the assignments
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 *
	 * @since 3.0 - ENHANCEMENT: Added sortAssignment handler for custom meta data
	 */
	private function sortAssignments( $a, $b ) {
		
		global $e20r_tracker_sort_params;
		
		$key   = $e20r_tracker_sort_params['key'];
		$order = $e20r_tracker_sort_params['order'];
		
		if ( $a->{$key} == $b->{$key} ) {
			return 0;
		}
		
		if ( $order === 'DESC' ) {
			return ( $a->{$key} > $b->{$key} ) ? - 1 : 1;
		} else {
			return ( $a->{$key} < $b->{$key} ) ? - 1 : 1;
		}
	}
}