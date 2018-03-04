<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  GPL v2 license
 */

namespace E20R\Tracker\Models;

use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\E20R_Tracker;
use E20R\Tracker\Controllers\Time_Calculations;

/**
 * Class Action_Model
 * @package E20R\Tracker\Models
 */
class Action_Model extends Settings_Model {
	
	/**
	 * Post type definition (const)
	 */
	const post_type = 'e20r_actions';
	/**
	 * @var null|Action_Model
	 */
	private static $instance = null;
	/**
	 * @var array $settings;
	 */
	private $settings;
	
	/**
	 * Action_Model constructor.
	 */
	public function __construct() {
		
		try {
			parent::__construct( 'action', Action_Model::post_type );
		} catch ( \Exception $exception ) {
			E20R_Tracker::dbg( "Unable to instantiate the Action Model class: " . $exception->getMessage() );
			
			return false;
		}
		
		return $this;
	}
	
	/**
	 * Return or instantiate the Action_Model class
	 *
	 * @return Action_Model
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load/define the e20r_action custom post type
	 */
	public static function registerCPT() {
		
		self::rename_action_cpt();
		
		$labels = array(
			'name'               => __( 'Actions', 'e20r-tracker' ),
			'singular_name'      => __( 'Action', 'e20r-tracker' ),
			'slug'               => Action_Model::post_type,
			'add_new'            => __( 'New Action', 'e20r-tracker' ),
			'add_new_item'       => __( 'New Action', 'e20r-tracker' ),
			'edit'               => __( 'Edit Action', 'e20r-tracker' ),
			'edit_item'          => __( 'Edit Action', 'e20r-tracker' ),
			'new_item'           => __( 'Add New', 'e20r-tracker' ),
			'view'               => __( 'View Action', 'e20r-tracker' ),
			'view_item'          => __( 'View This Actions', 'e20r-tracker' ),
			'search_items'       => __( 'Search Actions', 'e20r-tracker' ),
			'not_found'          => __( 'No Actions Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Actions Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type( Action_Model::post_type,
			array(
				'labels'             => apply_filters( 'e20r-tracker-action-cpt-labels', $labels ),
				'public'             => true,
				'show_ui'            => true,
				// 'show_in_menu' => true,
				'menu_icon'          => '',
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'supports'           => array( 'title', 'excerpt', 'thumbnail' ),
				'can_export'         => true,
				'show_in_nav_menus'  => false,
				'show_in_menu'       => 'e20r-tracker-articles',
				'rewrite'            => array(
					'slug'       => apply_filters( 'e20r-tracker-action-cpt-slug', 'tracker-action' ),
					'with_front' => false,
				),
				'has_archive'        => apply_filters( 'e20r-tracker-action-cpt-archive-slug', 'tracker-action-archive' ),
			)
		);
		
		if ( is_wp_error( $error ) ) {
			E20R_Tracker::dbg( 'ERROR: Failed to register e20r_actions CPT: ' . $error->get_error_message );
		}
	}
	
	/**
	 * Rename the (old) Checkin post type to Action_Model::post_type
	 */
	private static function rename_action_cpt() {
		
		$args = array(
			'post_type'      => 'e20r_checkins',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
		);
		
		$old_posts = get_posts( $args );
		
		foreach ( $old_posts as $p ) {
			
			$update              = array();
			$update['ID']        = $p->ID;
			$update['post_type'] = Action_Model::post_type;
			
			wp_update_post( $update );
		}
		
		global $wpdb;
		
		$find_sql = "SELECT meta_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '_e20r-checkin-%'";
		
		// $update_sql = "UPDATE {$wpdb->prefix}postmeta SET meta_key = %s WHERE meta_id = %d";
		
		$results = $wpdb->get_results( $find_sql );
		
		if ( count( $results ) == 0 ) {
			return;
		}
		
		E20R_Tracker::dbg( "Tracker::rename_action_cpt() - Found " . count( $results ) . " old metadata keys to convert" );
		
		foreach ( $results as $record ) {
			
			if ( false === self::replace_metakey( $record, '_e20r-checkin', '_e20r-action' ) ) {
				return;
			}
		}
		
		$results   = null;
		$other_sql = "SELECT meta_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '%checkin_ids%'";
		$results   = $wpdb->get_results( $other_sql );
		
		foreach ( $results as $record ) {
			
			if ( false === self::replace_metakey( $record, '-checkin_ids', '-action_ids' ) ) {
				return;
			}
			
		}
		
	}
	
	/**
	 * Replace the key (post meta key) for the record/type
	 *
	 * @param \stdClass $record
	 * @param string $old
	 * @param string $new
	 *
	 * @return bool
	 */
	private static function replace_metakey( $record, $old, $new ) {
		
		global $wpdb;
		
		$old_key = $record->meta_key;
		$new_key = str_replace( $old, $new, $old_key );
		
		E20R_Tracker::dbg( "Action_Model::replace_metakey() - Changing key {$old_key} to {$new_key} for {$record->meta_id} in {$wpdb->postmeta}" );
		
		$upd = $wpdb->update( "{$wpdb->prefix}postmeta",
			array( 'meta_key' => $new_key ),
			array( 'meta_id' => $record->meta_id ),
			array( '%s' ),
			array( '%d' )
		);
		
		if ( false !== $upd ) {
			E20R_Tracker::dbg( "Action_Model::replace_metakey() - Changed key for {$record->meta_id} in {$wpdb->prefix}postmeta" );
			
			return true;
		} else {
			E20R_Tracker::dbg( "Action_Model::replace_metakey() - Error for record {$record->meta_id}!!!" );
			
			return false;
		}
	}
	
	/**
	 * Generate a default checking record (set to the type)
	 *
	 * @param int $type
	 *
	 * @return \stdClass
	 */
	public function defaultCheckin( $type ) {
		
		global $currentProgram;
		global $current_user;
		
		$default                     = new \stdClass();
		$default->id                 = null;
		$default->user_id            = $current_user->ID;
		$default->program_id         = isset( $currentProgram->id ) ? $currentProgram->id : null;
		$default->article_id         = CONST_NULL_ARTICLE;
		$default->checkin_date       = date( 'Y-m-d', current_time( 'timestamp' ) );
		$default->checkin_type       = $type;
		$default->checkin_note       = null;
		$default->checkedin          = null;
		$default->checkin_short_name = 'default_check_in';
		
		if ( CHECKIN_ACTION === $type ) {
			
			$default->actionList   = array();
			$default->actionList[] = $this->defaultAction();
		}
		
		return $default;
	}
	
	/**
	 * Generate a default action definition (settings)
	 *
	 * @return \stdClass
	 */
	public function defaultAction() {
		
		$action             = $this->defaultSettings();
		$action->id         = CONST_NULL_ARTICLE;
		$action->item_text  = 'No action scheduled';
		$action->short_name = 'null_action';
		
		return $action;
	}
	
	// TODO: This requires the presence of checkin IDs in the Article list, etc.
	// checkin definitions -> $obj->type, $obj->
	/**
	 * The default settings for the Action entry
	 *
	 * @return \stdClass
	 */
	public function defaultSettings() {
		
		global $post;
		
		$settings               = parent::defaultSettings();
		$settings->id           = ( isset( $post->id ) ? $post->id : null );
		$settings->checkin_type = 0; // 1 = Action, 2 = Assignment, 3 = Survey, 4 = Activity.
		$settings->item_text    = ( isset( $post->post_excerpt ) ? $post->post_excerpt : 'Not scheduled' );
		$settings->short_name   = ( isset( $post->post_title ) ? $post->post_title : null );
		$settings->startdate    = null;
		$settings->enddate      = null;
		$settings->maxcount     = 0;
		$settings->program_ids  = array();
		
		return $settings;
	}
	
	/**
	 * Load the actions of a specific type
	 *
	 * @param int $id
	 * @param int $type
	 * @param int $numBack
	 *
	 * @return array
	 */
	public function getActions( $id, $type = 1, $numBack = - 1 ) {
		
		global $currentProgram;
		
		E20R_Tracker::dbg( "Action_Model::getActions() - id: {$id}, type: {$type}, records: {$numBack}" );
		
		$start_date = $this->getSetting( $id, 'startdate' );
		$checkins   = array();
		
		E20R_Tracker::dbg( "Action_Model::getActions() - Loaded startdate: {$start_date} for id {$id}" );
		
		$args = array(
			'posts_per_page' => $numBack,
			'post_type'      => Action_Model::post_type,
			'post_status'    => 'publish',
			'order_by'       => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_e20r-action-startdate',
					'value'   => $start_date,
					'compare' => '<=',
					'type'    => 'DATE',
				),
				array(
					'key'     => '_e20r-action-checkin_type',
					'value'   => $type,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => '_e20r-action-program_ids',
					'value'   => $currentProgram->id,
					'compare' => '=',
					'type'    => 'numeric',
				),
			),
		);
		
		$query = new \WP_Query( $args );
		E20R_Tracker::dbg( "Action_Model::getActions() - Returned checkins: {$query->post_count}" );
		// E20R_Tracker::dbg($args);
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			$new = $this->loadSettings( get_the_ID() );
			
			$new->id         = get_the_ID();
			$new->item_text  = $query->post->post_excerpt;
			$new->short_name = $query->post->post_title;
			
			$checkins[] = $new;
		}
		
		wp_reset_postdata();
		
		return $checkins;
	}
	
	/**
	 * Load the settings for the specified Action (ID)
	 *
	 * @param int $id
	 *
	 * @return \stdClass
	 */
	public function loadSettings( $id ) {
		
		$this->settings = parent::loadSettings( $id );
		
		$pst = get_post( $id );
		
		$this->settings->item_text  = $pst->post_excerpt;
		$this->settings->short_name = $pst->post_title;
		
		if ( empty( $this->settings->program_ids ) ) {
			
			$this->settings->program_ids = array();
		}
		
		return $this->settings;
	}
	
	/**
	 * Load the recorded checkin records for the specified user & article list
	 *
	 * @param int   $userId
	 * @param array $articleArr
	 * @param array $typeArr
	 * @param array $dateArr
	 *
	 * @return array|object
	 */
	public function loadCheckinsForUser( $userId, $articleArr, $typeArr, $dateArr ) {
		
		global $wpdb;
		global $current_user;
		$Tracker = Tracker::getInstance();
		$Program = Program::getInstance();
		
		$programId = $Program->getProgramIdForUser( $userId );
		
		$checkin_list = $Tracker->prepare_in( "c.checkin_type IN ([IN])", $typeArr );
		// Add the article ID array
		$article_in = $Tracker->prepare_in( " IN ([IN]) ) ", $articleArr );
		
		$sql = "SELECT *
                 FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %s ) AND
                  ( c.program_id = %d ) AND
                  ( {$checkin_list} ) AND
                  ( c.checkin_date BETWEEN %s AND %s ) AND
                  ( c.article_id {$article_in} )";
		
		$sql = $wpdb->prepare( $sql,
			$userId,
			$programId,
			$dateArr['min'] . " 00:00:00",
			$dateArr['max'] . " 23:59:59"
		);
		
		E20R_Tracker::dbg( "Action_Model::loadCheckinsForUser({$userId}) - Using SQL: {$sql}" );
		
		// E20R_Tracker::dbg("Action_Model::loadCheckinsForUser() - SQL: {$sql}");
		
		$results = $wpdb->get_results( $sql );
		
		if ( is_wp_error( $results ) ) {
			
			E20R_Tracker::dbg( "Action_Model::loadCheckinsForUser() - Error: {$wpdb->last_error}" );
			
			return array();
		}
		
		return $results;
	}
	
	/**
	 * Get the check-in for the user ID
	 *
	 * @param \stdClass $config
	 * @param int       $userId
	 * @param int       $type
	 * @param null      $short_name
	 *
	 * @return array|null|object|\stdClass|void
	 */
	public function get_user_checkin( $config, $userId, $type, $short_name = null ) {
		
		global $wpdb;
		global $current_user;
		
		$Program = Program::getInstance();
		$Article = Article::getInstance();
		
		E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Loading type {$type} check-ins for user {$userId}" );
		
		$programId = $Program->getProgramIdForUser( $userId );
		
		if ( empty( $config->articleId ) || ( $config->articleId == - 1 ) ) {
			
			$date = Time_Calculations::getDateForPost( $config->delay );
		} else {
			$date = $Article->releaseDate( $config->articleId );
		}
		// if ( $currentAction->articleId )
		
		E20R_Tracker::dbg( "Action_Model::get_user_checkin() - date for article # {$config->articleId} in program {$programId} for user {$userId}: {$date}" );
		
		if ( is_null( $short_name ) ) {
			
			E20R_Tracker::dbg( "Action_Model::get_user_checkin() - No short_name defined..." );
			$sql = $wpdb->prepare(
				"SELECT *
                 FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %d ) AND
                  ( c.program_id = %d ) AND
                  ( c.checkin_type = %d ) AND
                  ( c.checkin_date LIKE %s ) AND
                  ( c.article_id = %d ) )",
				$userId,
				$programId,
				$type,
				$date . "%",
				$config->articleId
			);
		} else {
			E20R_Tracker::dbg( "Action_Model::get_user_checkin() - short_name defined: {$short_name}" );
			$sql = $wpdb->prepare(
				"SELECT *
                 FROM {$this->table} AS c
                 WHERE ( ( c.user_id = %d ) AND
                  ( c.checkin_short_name = %s ) AND
                  ( c.program_id = %d ) AND
                  ( c.checkin_type = %d ) AND
                  ( c.checkin_date LIKE %s ) AND
                  ( c.article_id = %d ) )",
				$userId,
				$short_name,
				$programId,
				$type,
				$date . "%",
				$config->articleId
			);
		}
		
		// E20R_Tracker::dbg("Action_Model::get_user_checkin() - SQL: {$sql}");
		
		$result = $wpdb->get_row( $sql );
		
		if ( $result === false ) {
			
			E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Error loading check-in: " . $wpdb->last_error );
			
			return null;
		}
		
		E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Loaded {$wpdb->num_rows} check-in records" );
		
		if ( empty( $result ) ) {
			
			E20R_Tracker::dbg( "Action_Model::get_user_checkin() - No check-in records found for this user (ID: {$current_user->ID})" );
			
			/*
			if ( empty( $this->settings ) ) {
				$this->loadSettings( $articleId );
			}
			*/
			$a = $this->findActionByDate( $date, $programId );
			
			$result     = new \stdClass();
			$result->id = null;
			
			if ( is_array( $a ) && ( count( $a ) >= 1 ) ) {
				
				E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Default action: Found one or more ids" );
				
				foreach ( $a as $i ) {
					
					$n_type = $this->getSetting( $i, 'checkin_type' );
					
					E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Default action: Type settings for {$i}: {$n_type}" );
					
					if ( $n_type == $type ) {
						
						E20R_Tracker::dbg( 'Action_Model::get_user_checkin() - Default action: the type settings are correct. Using it...' );
						$result->id = $i;
						break;
					}
					
					E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Default action: the type mismatch {$n_type} != {$type}. Looping again." );
				}
				
			}
			
			// $result->descr_id = $short_name;
			$result->user_id            = $current_user->ID;
			$result->program_id         = $programId;
			$result->article_id         = $config->articleId;
			$result->checkin_date       = $date;
			$result->checkin_type       = $type;
			$result->checkin_note       = null;
			$result->checkedin          = null;
			$result->checkin_short_name = $short_name;
			
			E20R_Tracker::dbg( "Action_Model::get_user_checkin() - Default action: No user record found" );
			// E20R_Tracker::dbg($result);
		}
		
		return $result;
	}
	
	/**
	 * Locate Actions based on a date
	 *
	 * @param string $date
	 * @param int    $programId
	 *
	 * @return array
	 */
	public function findActionByDate( $date, $programId ) {
		
		
		E20R_Tracker::dbg( "Action_Model::findActionByDate() - Searching by date {$date} in {$programId}" );
		
		$actions = array();
		
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => self::post_type,
			'post_status'    => 'publish',
			'order_by'       => 'meta_value',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_e20r-action-program_ids',
					'value'   => $programId,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_e20r-action-startdate',
						'value'   => $date,
						'compare' => '>=',
						'type'    => 'DATE',
					),
					array(
						'key'     => '_e20r-action-enddate',
						'value'   => $date,
						'compare' => '<=',
						'type'    => 'DATE',
					),
				),
			),
		);
		
		$query = new \WP_Query( $args );
		E20R_Tracker::dbg( "Action_Model::findActionByDate() - Returned actions: {$query->post_count} for query... " );
		// E20R_Tracker::dbg($args);
		
		$curr_action_ids = $query->get_posts();
		wp_reset_postdata();
		
		E20R_Tracker::dbg( "Action_Model::findActionByDate() - Returning " . count( $curr_action_ids ) . " action ids" );
		
		// E20R_Tracker::dbg( $actions );
		
		return $curr_action_ids;
	}
	
	/**
	 * Get all defined program actions
	 *
	 * @param int $program_id
	 * @param int $action_type
	 *
	 * @return \WP_Post[]|array
	 */
	public function getProgramActions( $program_id, $action_type ) {
		
		E20R_Tracker::dbg( "Loading actions ({$action_type}) for program {$program_id}" );
		
		$action_args = array(
			'posts_per_page' => - 1,
			'post_type'      => self::post_type,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_e20r-action-program_ids',
					'value'   => $program_id,
					'compare' => '=',
					'type'    => 'numeric',
				),
				array(
					'key'     => '_e20r-action-checkin_type',
					'value'   => $action_type,
					'compare' => '=',
				),
			),
		);
		
		$actions     = new \WP_Query( $action_args );
		$new_actions = array();
		
		E20R_Tracker::dbg( "Action_Model::getProgramActions() - Returned actions: {$actions->post_count}" );
		
		// Return an empty array if there are no actions found
		if ( empty( $actions ) ) {
			return array();
		} else {
			foreach ( $actions->get_posts() as $action_id ) {
				
				$action        = $this->loadSettings( $action_id );
				$new_actions[] = $action;
			}
		}
		
		return $new_actions;
	}
	
	/**
	 * Validate the checkin array
	 *
	 * @param array $checkin_array
	 *
	 * @return bool
	 */
	public function isValid( $checkin_array ) {
		
		if ( array_key_exists( 'descr_id', $checkin_array ) &&
		     array_key_exists( 'checkedin', $checkin_array ) &&
		     array_key_exists( 'program_id', $checkin_array ) &&
		     array_key_exists( 'user_id', $checkin_array ) &&
		     array_key_exists( 'action_id', $checkin_array ) &&
		     array_key_exists( 'checkin_type', $checkin_array ) &&
		     array_key_exists( 'checkin_date', $checkin_array ) ) {
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Save the user's checkin to persistent storage
	 *
	 * @param $array checkin
	 *
	 * @return bool
	 */
	public function saveCheckin( $checkin ) {
		
		global $wpdb;
		
		E20R_Tracker::dbg( "Action_Model::setCheckin() - Check if the record exists already" );
		
		if ( ( $result = $this->exists( $checkin ) ) !== false ) {
			
			E20R_Tracker::dbg( "Action_Model::setCheckin() - found existing record: " );
			E20R_Tracker::dbg( $result->id );
			
			$checkin['id'] = $result->id;
		}
		
		E20R_Tracker::dbg( "Action_Model::setCheckin() - Checkin record:" );
		E20R_Tracker::dbg( $checkin );
		
		if ( false !== $wpdb->replace( $this->table, $checkin ) ) {
			
			$result = $this->exists( $checkin );
			
			return $result->id;
		}
		
		return false;
	}
	
	/**
	 * Check if the user has performed this checkin already
	 *
	 * @param array $checkin
	 *
	 * @return array|bool|null|object
	 */
	public function exists( $checkin ) {
		
		global $wpdb;
		
		E20R_Tracker::dbg( "Action_Model::exists() -  Data: " . print_r( $checkin, true ) );
		
		if ( ! is_array( $checkin ) ) {
			
			return false;
		}
		
		$sql = $wpdb->prepare(
			"SELECT id, checkedin
                FROM {$this->table}
                WHERE (
                ( {$this->fields['user_id']} = %d ) AND
                ( {$this->fields['checkin_date']} LIKE %s ) AND
                ( {$this->fields['program_id']} = %d ) AND
                ( {$this->fields['checkin_type']} = %d ) AND
                ( {$this->fields['checkin_short_name']} = %s )
                )
           ",
			$checkin['user_id'],
			$checkin['checkin_date'] . '%',
			$checkin['program_id'],
			$checkin['checkin_type'],
			$checkin['checkin_short_name']
		);
		
		$result = $wpdb->get_row( $sql );
		
		if ( ! empty( $result ) ) {
			
			E20R_Tracker::dbg( "Action_Model::exists() - Got a result returned: " );
			E20R_Tracker::dbg( $result );
			
			return $result;
		}
		
		return false;
	}
	
	/**
	 * Update the checkin record
	 *
	 * @param array $checkin
	 *
	 * @return bool
	 */
	public function setCheckin( $checkin ) {
		
		global $wpdb;
		
		E20R_Tracker::dbg( "Action_Model::setCheckin() - Check if the record exists already" );
		
		if ( ( $result = $this->exists( $checkin ) ) !== false ) {
			
			E20R_Tracker::dbg( "Action_Model::setCheckin() - found existing record: " );
			E20R_Tracker::dbg( $result->id );
			
			$checkin['id'] = $result->id;
		}
		
		E20R_Tracker::dbg( "Action_Model::setCheckin() - Checkin record:" );
		E20R_Tracker::dbg( $checkin );
		
		return ( $wpdb->replace( $this->table, $checkin ) ? true : false );
	}
	
	/**
	 * Save the Checkin Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific checkin.
	 *
	 * @return bool - True if successful at updating checkin settings
	 */
	public function saveSettings( $settings ) {
		
		$checkinId = $settings->id;
		
		$defaults = $this->defaultSettings();
		
		E20R_Tracker::dbg( "Action_Model::saveSettings() - Saving checkin Metadata: " . print_r( $settings, true ) );
		
		$error = false;
		
		foreach ( $defaults as $key => $value ) {
			
			if ( in_array( $key, array( 'id', 'short_name', 'item_text' ) ) ) {
				continue;
			}
			
			if ( false === $this->settings( $checkinId, 'update', $key, $settings->{$key} ) ) {
				
				E20R_Tracker::dbg( "Action_Model::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for check-in definition with ID: {$checkinId}" );
				
				$error = true;
			}
		}
		
		return ( ! $error );
	}
}