<?php

namespace E20R\Tracker\Models;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Controllers\Tracker;
use E20R\Utilities\Utilities;

class Program_Model extends Settings_Model {
	
	const post_type = 'e20r_programs';
	private static $instance = null;
	
	protected $settings;
	
	/**
	 * Settings for the Program class
	 */
	
	/**
	 * Numeric program ID
	 *
	 * @var int $id
	 */
	protected $id = -1;
	
	/**
	 * Slug for program URL
	 *
	 * @var null|string
	 */
	protected $program_shortname = null;
	
	/**
	 * MySQL DateTime formatted value indicating when the program starts
	 *
	 * @var null|string
	 */
	protected $startdate = null;
	
	/**
	 * MySQL DateTime formatted value indicating when the program ends
	 *
	 * @var null|string
	 */
	protected $enddate = null;
	
	/**
	 * ID of the post/page containing the intake form
	 *
	 * @var null|int
	 */
	protected $intake_form = null;
	
	/**
	 * Whether the member/user has completed their intake interview
	 *
	 * @var bool
	 */
	protected $incomplete_intake_form_page = false;
	
	/**
	 * Numeric value indicating the day of the week when the user can submit measurements
	 *
	 * @var int
	 */
	protected $measurement_day = 6;
	
	/**
	 * Page/Post ID for the program activity
	 *
	 * @var null|int
	 */
	protected $activity_page_id = null;
	
	/**
	 * Post/Page ID for the member dashboard
	 *
	 * @var null|int
	 */
	protected $dashboard_page_id = null;
	
	/**
	 * Post/Page ID where the measurements shortcode is located
	 *
	 * @var null|int
	 */
	protected $measurements_page_id = null;
	
	/**
	 * Post/Page ID where the member's progress data is displayed (contains shortcode)
	 *
	 * @var null|null
	 */
	protected $progress_page_id = null;
	
	/**
	 * Post/Page IDs where the program sales copy is located
	 *
	 * @var int[]
	 */
	protected $sales_page_ids = array();
	
	/**
	 * The Post/Page ID for the welcome interview form
	 *
	 * @var null|int
	 */
	protected $welcome_page_id = null;
	
	/**
	 * The Post/Page ID for the 'contact your coach' post/page
	 *
	 * @var null|int
	 */
	protected $contact_page_id = null;
	
	/**
	 * Post/Page ID containing the member's account information (membership billing, etc)
	 * @var null
	 */
	protected $account_page_id = null;
	
	/**
	 * Group ID (membership level ID) the program is grants access to
	 *
	 * @var int
	 */
	protected $group = - 1;
	
	/**
	 * List of user IDs who have been assigned this program
	 *
	 * @var int[]
	 */
	protected $users = array(); // TODO: Figure out how to add current_user->ID to  this array.
	
	/**
	 * List of User IDs for the female coaches assigned to the program (pool of coaches to select from)
	 *
	 * @var int[]
	 */
	protected $female_coaches = array();
	
	/**
	 * List of User IDs for the male coaches assigned to the program (pool of coaches to select from)
	 *
	 * @var int[]
	 */
	protected $male_coaches = array();
	
	/**
	 * Drip feed sequence(s) to use in the program (for daily reminders, etc).
	 *
	 * @var int[]
	 */
	protected $sequences = array();
	
	/**
	 * Program title
	 *
	 * @var null|string
	 */
	protected $title = null;
	
	/**
	 * Program summary (excerpt)
	 *
	 * @var null|string
	 */
	protected $excerpt = null;
	
	/**
	 * The delay value (for sequence/location in program flow) for the current day (member specific value)
	 *
	 * @var null|int
	 */
	protected $active_delay = null;
	
	/**
	 * The previous day's delay value (for sequence/location in program flow)
	 *
	 * @var null|int
	 */
	protected $previous_delay = null;
	
	/**
	 * Program_Model constructor.
	 */
	public function __construct() {
		
		try {
			parent::__construct( 'program', self::post_type );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error instantiating Program Model class: " . $exception->getMessage() );
			
			return false;
		}
		
		$this->settings = new \stdClass();
		
		return $this;
	}
	
	/**
	 * @return Program_Model|null
	 *
	 * @throws \Exception
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Register the Programs Taxonomy for the E20R Tracker plugin
	 */
	public static function registerTaxonomy() {
		
		register_taxonomy(
			'programs',
			'e20r_program',
			array(
				'label'              => __( 'Programs', 'e20r-tracker' ),
				'rewrite'            => array( 'slug' => 'programs' ),
				'public'             => false,
				'show_tagcloud'      => false,
				'show_in_quick_edit' => false,
				'hierarchical'       => true,
				'capabilities'       => array(
					'assign_terms' => 'edit_posts',
					'edit_terms'   => 'manage_categories',
				),
			)
		);
	}
	
	/**
	 * Programs are managed as Custom Posts for the E20R Tracker plugin
	 */
	public static function registerCPT() {
		
		$labels = array(
			'name'               => __( 'Products', 'e20r-tracker' ),
			'singular_name'      => __( 'Product', 'e20r-tracker' ),
			'slug'               => self::post_type,
			'add_new'            => __( 'New Product', 'e20r-tracker' ),
			'add_new_item'       => __( 'New Product', 'e20r-tracker' ),
			'edit'               => __( 'Edit product', 'e20r-tracker' ),
			'edit_item'          => __( 'Edit Product', 'e20r-tracker' ),
			'new_item'           => __( 'Add New', 'e20r-tracker' ),
			'view'               => __( 'View Products', 'e20r-tracker' ),
			'view_item'          => __( 'View This Product', 'e20r-tracker' ),
			'search_items'       => __( 'Search Products', 'e20r-tracker' ),
			'not_found'          => __( 'No Products Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Products Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type( self::post_type,
			array(
				'labels'             => apply_filters( 'e20r-tracker-program-cpt-labels', $labels ),
				'public'             => true,
				'show_ui'            => true,
				// 'show_in_menu' => true,
				'menu_icon'          => '',
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'supports'           => array( 'title', 'excerpt', 'custom-fields', 'page-attributes' ),
				'can_export'         => true,
				'show_in_nav_menus'  => false,
				'show_in_menu'       => 'e20r-tracker-info',
				'rewrite'            => array(
					'slug'       => apply_filters( 'e20r-tracker-program-cpt-slug', 'tracker-programs' ),
					'with_front' => false,
				),
				'has_archive'        => apply_filters( 'e20r-tracker-program-cpt-archive-slug', 'tracker-program-archive' ),
			)
		);
		
		if ( is_wp_error( $error ) ) {
			Utilities::get_instance()->log( 'ERROR: Failed to register e20r_program CPT: ' . $error->get_error_message );
		}
	}
	
	/**
	 * Locate the program ID that a specific member (WP_User->ID) belongs to
	 *
	 * @param int $mID
	 *
	 * @return bool|int
	 */
	public function findByMembershipId( $mID ) {
		
		$pId = null;
		
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => $this->cpt_slug,
			'post_status'    => apply_filters( 'e20r-tracker-model-data-status', array( 'publish' ) ),
			'order_by'       => 'meta_value',
			'meta_query'     => array(
				array(
					'key'     => "_e20r-program-group",
					'value'   => $mID,
					'compare' => '=',
				),
			),
		);
		
		$query = new \WP_Query( $args );
		
		Utilities::get_instance()->log( "Program_Model::findByMembershipId() - Returned: {$query->post_count} programs for group w/ID: {$mID}" );
		// Utilities::get_instance()->log($query);
		
		if ( $query->post_count == 0 ) {
			
			Utilities::get_instance()->log( "Program_Model::findByMembershipId() - Error: No program IDs returned?!?" );
			
			return false;
		}
		
		if ( $query->post_count > 1 ) {
			Utilities::get_instance()->log( "Program_Model::findByMembershipId() - Error: Incorrect program/membership definition! More than one entry was returned" );
			
			return false;
		}
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			$pId = get_the_ID();
			
			// $new = $this->loadSettings( get_the_ID() );
			// $new->id = get_the_ID();
			
			// $pList[] = $new;
		}
		
		wp_reset_postdata();
		
		Utilities::get_instance()->log( "Program_Model::findByMembershipId() - Located program # {$pId}" );
		
		return $pId;
	}
	
	/**
	 * Load all users who have been assigned the specified program (ID)
	 *
	 * @param int $programId
	 *
	 * @return \WP_User[]
	 */
	public function loadProgramMembers( $programId ) {
		
		$Tracker = Tracker::getInstance();
		
		Utilities::get_instance()->log( "Loading users with Program ID: {$programId}" );
		
		if ( 0 == $programId ) {
			$args = array(
				'order_by' => 'display_name',
			);
			
		} else if ( - 1 == $programId ) {
			
			return array();
		} else {
			$args = array(
				'meta_key'   => 'e20r-tracker-program-id',
				'meta_value' => $programId,
				'order_by'   => 'user_nicename',
			);
			
		}
		
		$user_list = get_users( $args );
		
		foreach ( $user_list as $k => $u ) {
			
			if ( false === $Tracker->isActiveClient( $u->ID ) ) {
				unset( $user_list[ $k ] );
			}
		}
		
		Utilities::get_instance()->log( "User Objects returned: " . count( $user_list ) );
		
		return $user_list;
	}
	
	/**
	 * Save the Program Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific program.
	 *
	 * @return bool - True if successful at updating program settings
	 */
	public function saveSettings( $settings ) {
		
		$programId = $settings->id;
		
		$defaults = $this->defaultSettings();
		
		Utilities::get_instance()->log( "Saving program Metadata: " . print_r( $settings, true ) );
		
		$error = false;
		
		foreach ( $defaults as $key => $value ) {
			
			if ( in_array( $key, array(
				'id',
				'program_shortname',
				'title',
				'excerpt',
				'active_delay',
				'previous_delay',
			) ) ) {
				continue;
			}
			
			if ( false === $this->settings( $programId, 'update', $key, $settings->{$key} ) ) {
				
				Utilities::get_instance()->log( "ERROR saving {$key} setting ({$settings->{$key}}) for program definition with ID: {$programId}" );
				
				$error = true;
			}
		}
		
		return ( ! $error );
	}
	
	/**
	 * Default settings for the program
	 *
	 * @return \stdClass
	 */
	public function defaultSettings() {
		
		global $post;
		
		$settings                              = parent::defaultSettings();
		$settings->id                          = - 1;
		$settings->program_shortname           = ( isset( $post->post_name ) ? $post->post_name : null );
		$settings->startdate                   = date_i18n( 'Y-m-d h:i:s', current_time( 'timestamp' ) );
		$settings->enddate                     = null;
		$settings->intake_form                 = null;
		$settings->incomplete_intake_form_page = null;
		$settings->measurement_day             = 6;
		$settings->activity_page_id            = null;
		$settings->dashboard_page_id           = null;
		$settings->measurements_page_id        = null;
		$settings->progress_page_id            = null;
		$settings->sales_page_ids              = array();
		$settings->welcome_page_id             = null;
		$settings->contact_page_id             = null;
		$settings->account_page_id             = null;
		$settings->group                       = - 1;
		$settings->users                       = array(); // TODO: Figure out how to add current_user->ID to  this array.
		$settings->female_coaches              = array();
		$settings->male_coaches                = array();
		$settings->sequences                   = array();
		$settings->title                       = null;
		$settings->excerpt                     = null;
		$settings->active_delay                = null;
		$settings->previous_delay              = null;
		
		return $settings;
	}
	
	/**
	 * Return a list of program IDs and titles
	 *
	 * @param null|array $status
	 *
	 * @return string[]
	 */
	public function getAllProgramNamesIds( $status = null ) {
		
		$return = array();
		$args   = array(
			'posts_per_page' => - 1,
			'fields'         => array(),
			'post_type'      => Program_Model::post_type,
			'post_status'    => ( empty( $status ) ? array(
				'publish',
				'pending',
				'draft',
				'future',
				'private',
			) : $status ),
			'order_by'       => 'title',
			'order'          => 'ASC',
		);
		
		$programs = get_posts( $args );
		
		if ( ! empty( $programs ) ) {
			
			/**
			 * @var \WP_Post $program
			 */
			foreach ( $programs as $program ) {
				$return[ $program->ID ] = $program->post_title;
			}
		}
		
		return $return;
	}
	
	public function getUsersInPrograms() {
		
		$args = array(
			'posts_per_page' => - 1,
			'meta_query'     => array(
				array(
					'key'     => "e20r-tracker-program-id",
					'compare' => 'EXISTS',
				),
			),
		);
		
		$users_in_programs = new \WP_User_Query( $args );
		
		if ( empty( $users_in_programs ) ) {
			return array();
		}
		
		$users = $users_in_programs->get_results();
		Utilities::get_instance()->log( "Found " . count( $users ) . " users who are in a program" );
		
		return $users;
	}
	
	/**
	 * Load program settings from database
	 *
	 * @param       integer $id Program (post) ID (CPT)
	 *
	 * @return      \stdClass    Program->settings       Settings for the specified program
	 */
	public function loadSettings( $id ) {
		
		global $post;
		
		$Tracker = Tracker::getInstance();
		
		global $currentProgram;
		
		if ( isset( $currentProgram->id ) && ( $currentProgram->id == $id ) ) {
			
			return $currentProgram;
		}
		
		if ( $id == 0 ) {
			
			$this->settings     = $this->defaultSettings();
			$this->settings->id = $id;
			
		} else {
			
			$savePost = $post;
			
			$this->settings = parent::loadSettings( $id );
			
			if ( ! is_array( $this->settings->sequences ) ) {
				$this->settings->sequences = array(
					! empty( $this->settings->sequences ) ? array( $this->settings->sequences ) : array(),
				);
			}
			
			if ( ! is_array( $this->settings->male_coaches ) ) {
				$this->settings->male_coaches = array(
					! empty( $this->settings->male_coaches ) ? array( $this->settings->male_coaches ) : array(),
				);
			}
			
			if ( ! is_array( $this->settings->female_coaches ) ) {
				$this->settings->female_coaches = array(
					! empty( $this->settings->female_coaches ) ? array( $this->settings->female_coaches ) : array(),
				);
			}
			
			$post = get_post( $id );
			setup_postdata( $post );
			
			if ( ! empty( $post->post_title ) ) {
				
				$this->settings->excerpt           = $post->post_excerpt;
				$this->settings->program_shortname = $post->post_name;
				$this->settings->title             = $post->post_title;
				$this->settings->id                = $id;
			}
			
			if ( ! isset( $this->settings->users ) || empty( $this->settings->users ) ) {
				
				$this->settings->users = array();
			}
			
			wp_reset_postdata();
			$post = $savePost;
		}
		
		$this->settings->previous_delay = null;
		$currentProgram                 = $this->settings;
		
		/** BUGFIX: Couldn't figure out the correct startdate for the user/program because the current startdate was wrong */
		$this->settings->active_delay = $Tracker->getDelay( 'now' );
		$currentProgram               = $this->settings;
		
		return $currentProgram;
	}
}