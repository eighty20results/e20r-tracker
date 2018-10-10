<?php

namespace E20R\Tracker\Models;

use E20R\Utilities\Utilities;
use E20R\Utilities\Cache;

class Exercise_Model extends Settings_Model {
	
	/**
	 * Constant for Exercise Post Type
	 */
	const post_type = 'e20r_exercises';
	private static $instance = null;
	protected $settings;
	/**
	 * @var array|null Type map for exercises (
	 */
	private $exercise_types = null;
	
	/**
	 * Exercise_Model constructor.
	 */
	public function __construct() {
		
		try {
			parent::__construct( 'exercise', self::post_type );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error constructing Exercise Model class instance:" . $exception->getMessage() );
			
			return false;
		}
		
		$this->exercise_types = apply_filters( 'e20r-tracker-exercise-type-map', array(
				1 => __( 'Reps', "e20r-tracker" ),
				2 => __( 'Time', 'e20r-tracker' ),
				3 => __( 'AMRAP', 'e20r-tracker' ),
			)
		);
		
		return $this;
	}
	
	/**
	 * @return Exercise_Model
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * @param null $exerciseId
	 */
	public function init( $exerciseId = null ) {
		
		if ( $exerciseId === null ) {
			
			global $post;
			
			if ( isset( $post->post_type ) && ( $post->post_type == self::post_type ) ) {
				
				$exerciseId = $post->ID;
			}
		}
		
		$this->settings = $this->loadSettings( $exerciseId );
	}
	
	public function loadSettings( $id = null ) {
		
		global $post;
		global $currentExercise;
		
		if ( ! empty( $currentExercise ) && ( $currentExercise->id == $id ) ) {
			
			return $currentExercise;
		}
		
		if ( $id == 0 ) {
			
			$this->settings     = $this->defaultSettings();
			$this->settings->id = $id;
			
		} else {
			
			$savePost = $post;
			
			$this->settings = parent::loadSettings( $id );
			
			$post = get_post( $id );
			setup_postdata( $post );
			
			if ( ! empty( $post->post_title ) ) {
				
				$this->settings->descr = $post->post_content;
				$this->settings->title = $post->post_title;
				$this->settings->id    = $id;
				$this->settings->image = get_post_thumbnail_id( $id ); // Returns the <img> element
			}
			
			wp_reset_postdata();
			$post = $savePost;
		}
		
		$currentExercise = $this->settings;
		
		return $this->settings;
		
	}
	
	public function defaultSettings() {
		
		$settings = parent::defaultSettings();
		
		$settings->title = null; // get_the_title();
		$settings->descr = null; // $post->post_content;
		$settings->image = null; // featured image
		
		$settings->type       = 0;
		$settings->reps       = null;
		$settings->rest       = null;
		$settings->shortcode  = null;
		$settings->video_link = null;
		
		return $settings;
	}
	
	public function get_activity_type( $typeId ) {
		
		Utilities::get_instance()->log( "Exercise_Model::get_activity_type() - Requested Type #: " . $typeId );
		
		return $this->exercise_types[ $typeId ];
	}
	
	public function get_activity_types() {
		
		return $this->exercise_types;
	}
	
	public function findExercise( $type = 'id', $value ) {
		
		global $currentProgram;
		
		// NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'. Default value is 'CHAR'.
		switch ( $type ) {
			
			case 'id':
			case 'reps':
			case 'rest':
				$dType = 'numeric';
				break;
			
			default:
				$dType = 'char';
		}
		
		return parent::find( $type, $value, $currentProgram->id, 'LIKE', 'DESC', $dType );
	}
	
	/**
	 * Returns an array of all programs merged with their associated settings.
	 *
	 * @param $statuses string|array - Statuses to return program data for.
	 *
	 * @return mixed - Array of program objects
	 */
	public function loadAllData( $statuses = 'any' ) {
		
		if ( null === ( $exercise_list = Cache::get( 'all_exercises', 'e20r_tracker' ) ) ) {
			
			if ( 1 === preg_match( "/,/", $statuses ) ) {
				$statuses = array_map( 'trim', explode( ',', $statuses ) );
			}
			
			if ( !is_array( $statuses ) && !empty( $statuses )) {
				$statuses = array( $statuses );
			}
			
			$query = array(
				'posts_per_page' => - 1,
				'post_type'      => self::post_type,
				'post_status'    => $statuses,
			);
			
			/* Fetch all Exercise posts */
			$list = new \WP_Query( $query );
			$exercise_list = $list->get_posts();
			
			if ( empty( $exercise_list ) ) {
				
				return array();
			}
			
			Utilities::get_instance()->log( "Loading exercise settings for {$list->post_count} exercises" );
			
			foreach ( $exercise_list as $key => $data ) {
				
				$settings = $this->loadSettings( $data->ID );
				
				$exercise_list[ $key ] = $settings;
			}
			
			if ( !empty( $exercise_list ) ) {
				Cache::set( 'all_exercises', $exercise_list, ( 10 * MINUTE_IN_SECONDS ), 'e20r_tracker' );
			}
		}
		
		return $exercise_list;
	}
	
	public function loadData( $id = null, $statuses = 'any' ) {
		
		if ( $id == null ) {
			Utilities::get_instance()->log( "Error: Unable to load exercise data when no ID is specified!" );
			
			return false;
		}
		
		$query = array(
			'post_type'   => self::post_type,
			'post_status' => $statuses,
			'p'           => $id,
		);
		
		wp_reset_query();
		
		/* Fetch Exercises */
		$list = new \WP_Query( $query );
		
		$exercise_list = $list->get_posts();
		
		if ( empty( $exercise_list ) ) {
			Utilities::get_instance()->log( "Exercise_Model::loadExerciseData() - No exercises found!" );
			
			return false;
		}
		
		foreach ( $exercise_list as $key => $data ) {
			
			$settings = $this->loadSettings( $data->ID );
			
			$exercise_list[ $key ] = $settings;
		}
		
		wp_reset_postdata();
		
		return $settings;
	}
	
	public function getSettings() {
		
		return $this->settings;
	}
	
	/**
	 * Save the Exercise Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific program.
	 *
	 * @return bool - True if successful at updating program settings
	 */
	public function saveSettings( $settings ) {
		
		$defaults = self::defaultSettings();
		
		Utilities::get_instance()->log( "Exercise_Model::saveSettings() - Saving exercise Metadata: " . print_r( $settings, true ) );
		
		$error = false;
		
		$exerciseId = $settings->id;
		
		foreach ( $defaults as $key => $value ) {
			
			if ( in_array( $key, array( 'id', 'title', 'descr', 'image' ) ) ) {
				continue;
			}
			
			if ( false === parent::settings( $exerciseId, 'update', $key, $settings->{$key} ) ) {
				
				Utilities::get_instance()->log( "Exercise::saveSettings() - ERROR saving settings for exercise with ID: {$exerciseId}" );
				
				$error = true;
			}
		}
		
		return ( ! $error );
		
	}
	
	/**
	 * Register the e20r_Exercise custom post type
	 */
	public static function registerCPT() {
		
		$labels = array(
			'name'               => __( 'Exercises', 'e20r-tracker' ),
			'singular_name'      => __( 'Exercise', 'e20r-tracker' ),
			'slug'               => self::post_type,
			'add_new'            => __( 'New Exercise', 'e20r-tracker' ),
			'add_new_item'       => __( 'New Exercise', 'e20r-tracker' ),
			'edit'               => __( 'Edit Exercise', 'e20r-tracker' ),
			'edit_item'          => __( 'Edit Exercise', 'e20r-tracker' ),
			'new_item'           => __( 'Add New', 'e20r-tracker' ),
			'view'               => __( 'View Exercises', 'e20r-tracker' ),
			'view_item'          => __( 'View This Exercise', 'e20r-tracker' ),
			'search_items'       => __( 'Search Exercises', 'e20r-tracker' ),
			'not_found'          => __( 'No Exercises Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Exercises Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type( self::post_type,
			array(
				'labels'             => apply_filters( 'e20r-tracker-exercise-cpt-labels', $labels ),
				'public'             => true,
				'menu_icon'          => '',
				'show_ui'            => true,
				// 'show_in_menu' => true,
				'publicly_queryable' => true,
				'hierarchical'       => true,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'can_export'         => true,
				'show_in_nav_menus'  => false,
				'show_in_menu'       => 'e20r-tracker-articles',
				'rewrite'            => array(
					'slug'       => apply_filters( 'e20r-tracker-exercise-cpt-slug', 'tracker-exercise' ),
					'with_front' => false,
				),
				'has_archive'        => apply_filters( 'e20r-tracker-exercise-cpt-archive-slug', 'tracker-exercises-archive' ),
			)
		);
		
		if ( is_wp_error( $error ) ) {
			Utilities::get_instance()->log( 'ERROR: Failed to register e20r_exercise CPT: ' . $error->get_error_message );
		}
	}
}