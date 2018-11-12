<?php

namespace E20R\Tracker\Models;

use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Action;
use E20R\Tracker\Controllers\Article;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Exercise;

use E20R\Utilities\Utilities;

/**
 * Class Workout_Model
 *
 * @package E20R\Tracker\Models
 */
class Workout_Model extends Settings_Model {
	
	const post_type = 'e20r_workout';
	private static $instance = null;
	protected $settings;
	protected $types = array();
	protected $table;
	
	/**
	 * Workout_Model constructor.
	 */
	public function __construct() {
		
		$Tables = Tables::getInstance();
		
		try {
			parent::__construct( 'workout', self::post_type );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error instantiating the Workout Model class: " . $exception->getMessage() );
			
			return false;
		}
		
		try {
			$this->table = $Tables->getTable( 'workout' );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Unable to fetch the Workout table: " . $exception->getMessage() );
			
			return false;
		}
		
		$this->fields = $Tables->getFields( 'workout' );
		
		Utilities::get_instance()->log( "Workout_Model() - Constructor..." );
		
		$this->types = array(
			0 => '',
			1 => __( "Slow", "e20r-tracker" ),
			2 => __( "Normal", "e20r-tracker" ),
			3 => __( "Fast", "e20r-tracker" ),
			4 => __( "Varying", "e20r-tracker" ),
		);
		
		$this->settings = new \stdClass();
		
		return $this;
	}
	
	public function find( $key, $value, $programId = - 1, $comp = 'LIKE', $order = 'DESC', $dataType = 'numeric', $settings_page = false ) {
		
		if ( empty( $aIds ) && true === $settings_page ) {
			Utilities::get_instance()->log( 'Loading all activities from DB (without settings)' );
			
			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => $this->cpt_slug,
				'post_status'    => apply_filters( 'e20r-tracker-model-data-status', array(
					'publish',
					'draft',
					'future',
				) ),
				'order'          => $order,
			);
			
			$activity_query = new \WP_Query( $args );
			$activities = $activity_query->get_posts();
			
		} else {
			Utilities::get_instance()->log( 'Loading activities/activity from DB' );
			$activities = parent::find( $key, $value, $programId, $comp, $order, $dataType );
		}
		
		return $activities;
	}
	/**
	 * Return or instantiate the Workout_Model class
	 *
	 * @return Workout_Model
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Return the table name for the Workout/Activity table
	 *
	 * @return null|string
	 */
	public function getTable() {
		
		return $this->table;
	}
	
	/**
	 * Return the DB field name for the activity/workout table
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function getField( $name = 'all' ) {
		
		if ( 'all' == $name ) {
			
			return $this->fields;
		}
		
		return $this->fields[ $name ];
	}
	
	/**
	 * Return the workout/activity type
	 *
	 * @param null|int $tId
	 *
	 * @return mixed
	 */
	public function getType( $tId = null ) {
		
		$Tracker = Tracker::getInstance();
		
		if ( null == $tId ) {
			return $this->types[0];
		}
		
		Utilities::get_instance()->log( "Type ID: {$tId}: {$this->types[$tId]} ->" . $Tracker->whoCalledMe() );
		
		return $this->types[ $tId ];
	}
	
	/**
	 * Load the settings for the specified Workout ID
	 *
	 * @param null|int $workoutId
	 */
	public function init( $workoutId = null ) {
		
		global $currentWorkout;
		
		if ( $workoutId === null ) {
			
			global $post;
			
			if ( isset( $post->post_type ) && ( $post->post_type == self::post_type ) ) {
				
				$workoutId = $post->ID;
			}
		}
		Utilities::get_instance()->log( "Loading workoutData for {$workoutId}" );
		$tmp = $this->load_activity( $workoutId );
		
		$currentWorkout = $tmp[ $workoutId ];
	}
	
	/**
	 * Load the specified activity
	 *
	 * @param int    $id
	 * @param string $statuses
	 *
	 * @return array|mixed
	 */
	public function load_activity( $id, $statuses = 'any' ) {
		
		global $post;
		
		$workouts = array();
		
		Utilities::get_instance()->log( "Attempting to load workout settings for {$id}" );
		
		if ( $id === null ) {
			
			Utilities::get_instance()->log( "Warning: Unable to load workout data. No ID specified!" );
			
			return $this->defaultSettings();
		} else {
			
			$query = array(
				'post_type'   => $this->cpt_slug,
				'post_status' => $statuses,
				'p'           => $id,
			);
			
			/* Fetch Workouts */
			$query = new \WP_Query( $query );
			
			if ( $query->post_count <= 0 ) {
				Utilities::get_instance()->log( "No workout found!" );
				
				return array( $post->ID => $this->defaultSettings() );
			}
			
			while ( $query->have_posts() ) {
				
				$query->the_post();
				
				Utilities::get_instance()->log( "For Workout id: " . get_the_ID() );
				$new        = $this->loadSettings( get_the_ID() );
				$post_title = get_the_title();
				
				$new->id = $id;
				
				Utilities::get_instance()->log( "For {$post_title}: " . print_r( $new->assigned_usergroups, true ) );
				
				// Convert/update the userGroup to role based model
				$for_ex = preg_match( "/\([A-D|a-d][0-9][0-9][0-9][0-9]EX\)|\([A-D|a-d][0-9][0-9][0-9][0-9]EX-[1-9]\)/i", $post_title );
				
				Utilities::get_instance()->log( "For {$post_title}: " . print_r( $for_ex, true ) );
				
				if ( false != $for_ex && ( ! in_array( 'e20r_tracker_exp_3', $new->assigned_usergroups ) ) ) {
					Utilities::get_instance()->log( "Assigning/converting to use role for experienced" );
					$new->assigned_usergroups[] = 'e20r_tracker_exp_3';
				}
				
				$for_in = preg_match( "/\([A-D|a-d][0-9][0-9][0-9][0-9]IN\)|\([A-D|a-d][0-9][0-9][0-9][0-9]IN-[1-9]\)/i", $post_title );
				
				Utilities::get_instance()->log( "For {$post_title}: " . print_r( $for_in, true ) );
				
				if ( false != $for_in && ( ! in_array( 'e20r_tracker_exp_2', $new->assigned_usergroups ) ) ) {
					Utilities::get_instance()->log( "Assigning/converting to use role for intermediates" );
					$new->assigned_usergroups[] = 'e20r_tracker_exp_2';
				}
				
				$for_ne = preg_match( "/\([A-D|a-d][0-9][0-9][0-9][0-9]NE\)|\([A-D|a-d][0-9][0-9][0-9][0-9]NE-[1-9]\)/i", $post_title );
				Utilities::get_instance()->log( "For {$post_title}: " . print_r( $for_ne, true ) );
				
				if ( false != $for_ne && ( ! in_array( 'e20r_tracker_exp_1', $new->assigned_usergroups ) ) ) {
					Utilities::get_instance()->log( "Assigning/converting to use role for beginners" );
					$new->assigned_usergroups[] = 'e20r_tracker_exp_1';
				}
				
				update_post_meta( $new->id, 'e20r-workout-assigned_usergroups', $new->assigned_usergroups );
				
				$workouts[ $new->id ] = $new;
			}
			
			wp_reset_postdata();
		}
		
		return $workouts;
	}
	
	/**
	 * Defines the Settings for the workout
	 *
	 * @return mixed
	 */
	public function defaultSettings() {
		
		$workout                      = parent::defaultSettings();
		$workout->excerpt             = '';
		$workout->title               = '';
		$workout->days                = array();
		$workout->program_ids         = array();
		$workout->workout_ident       = 'A';
		$workout->phase               = null;
		$workout->assigned_user_id    = array( 0 ); // Not Applicable
		$workout->assigned_usergroups = array( 0 ); // Not Applicable
		// $workout->startdate = date( 'Y-m-d', current_time( 'timestamp' ) );
		$workout->startdate = null;
		$workout->enddate   = null;
		$workout->startday  = null;
		$workout->endday    = null;
		
		
		$workout->groups    = array();
		$workout->groups[0] = $this->defaultGroup();
		
		return $workout;
	}
	
	/**
	 * Defines the group structure for the workout/activity
	 *
	 * @return \stdClass
	 */
	public function defaultGroup() {
		
		$group                  = new \stdClass();
		$group->group_set_count = null;
		$group->group_tempo     = null;
		$group->group_rest      = null;
		
		// Key for the exercises array is the exercise id value (i.e. the post id)
		$group->exercises    = array();
		$group->exercises[0] = 0;
		
		return $group;
	}
	
	/**
	 * Load activity (workout) settings
	 *
	 * @param int $id
	 *
	 * @return mixed|\stdClass
	 */
	public function loadSettings( $id ) {
		
		global $post;
		global $current_user;
		
		global $currentWorkout;
		
		$Program = Program::getInstance();
		
		if ( isset( $currentWorkout->id ) && ( $currentWorkout->id == $id ) ) {
			
			return $currentWorkout;
		}
		
		if ( $id == 0 ) {
			
			$this->settings     = $this->defaultSettings();
			$this->settings->id = $id;
			
		} else {
			
			$savePost = $post;
			
			$this->settings = parent::loadSettings( $id );
			
			$post = get_post( $id );
			setup_postdata( $post );
			
			$this->settings->excerpt = ( empty ( $post->post_content ) ? $post->post_excerpt : $post->post_content );
			$this->settings->title   = $post->post_title;
			$this->settings->id      = $id;
			
			$this->settings->program_ids = get_post_meta( $post->ID, '_e20r-workout-program_ids' );
			
			Utilities::get_instance()->log( "Analyzing group content" );
			// Utilities::get_instance()->log( $this->settings );
			
			$g_def = $this->defaultGroup();
			
			if ( ! is_array( $this->settings->groups ) ) {
				
				$this->settings->groups   = array();
				$this->settings->groups[] = $g_def;
			}
			
			foreach ( $this->settings->groups as $i => $g ) {
				
				Utilities::get_instance()->log( "Analyzing group #{$i}" );
				
				if ( ! isset( $g->group_set_count ) || is_null( $g->group_set_count ) ) {
					
					Utilities::get_instance()->log( "Adding default set count info" );
					$g->group_set_count = $g_def->group_set_count;
				}
				
				if ( ! isset( $g->group_tempo ) || is_null( $g->group_tempo ) ) {
					
					Utilities::get_instance()->log( "Adding default set tempo" );
					$g->group_tempo = $g_def->group_tempo;
				}
				
				if ( ! isset( $g->group_rest ) || is_null( $g->group_rest ) ) {
					
					Utilities::get_instance()->log( "Adding default set rest" );
					$g->group_rest = $g_def->group_rest;
				}
				
				if ( ! isset( $g->exercises[1] ) || is_null( $g->exercises[1] ) ) {
					
					Utilities::get_instance()->log( "Adding default exercise array" );
					$g->exercises = $g_def->exercises;
				}
				
				$this->settings->groups[ $i ] = $g;
			}
			
			wp_reset_postdata();
			$post = $savePost;
		}
		
		// Test whether an exercise group is defined or not.
		if ( ! is_array( $this->settings->groups ) && ( ! isset( $this->settings->groups[0] ) ) ) {
			
			$this->settings->groups    = array();
			$this->settings->groups[0] = $this->defaultGroup();
		}
		
		$userStarted = date( 'Y-m-d', current_time( 'timestamp' ) );
		
		if ( ! is_admin() ) {
			
			if ( empty( $this->settings->startdate ) || empty( $this->settings->enddate ) ) {
				
				Utilities::get_instance()->log( "The startdate or enddate settings contain no data..." );
				// Grab membership start date for the current user.
				$startTS = $Program->startdate( $current_user->ID, null, true );
				
				$userStarted = date( 'Y-m-d', $startTS );
				Utilities::get_instance()->log( "The start Timestamp for use {$current_user->ID}: {$startTS} gives a start date of {$userStarted}" );
			}
			
			if ( empty( $this->settings->startdate ) && ! empty( $this->settings->startday ) && isset( $current_user->ID ) ) {
				Utilities::get_instance()->log( "Calculate the startdate based on the startday number" );
				
				$this->settings->startdate = date( 'Y-m-d', strtotime( "{$userStarted} +{$this->settings->startday} days" ) );
				
				Utilities::get_instance()->log( "Result: start day number {$this->settings->startday} gives a workout start date of {$this->settings->startdate}" );
			}
			
			if ( empty( $this->settings->enddate ) && ( ! empty( $this->settings->endday ) ) ) {
				Utilities::get_instance()->log( "Calculate the enddate based on the endday number" );
				
				$this->settings->enddate = date( 'Y-m-d', strtotime( "{$userStarted} +{$this->settings->endday} days" ) );
				
				Utilities::get_instance()->log( "Result: end day number {$this->settings->endday} gives a workout end date of {$this->settings->enddate}" );
			}
			
			if ( empty( $this->settings->enddate ) && empty( $this->settings->endday ) ) {
				Utilities::get_instance()->log( "No defined date/or day for the end of the workout" );
				$this->settings->enddate = '2038-01-01';
			}
			
			if ( empty( $this->settings->startdate ) && empty( $this->settings->startdate ) ) {
				Utilities::get_instance()->log( "No defined date/or day for the end of the workout" );
				$this->settings->startdate = '2014-01-01';
			}
			
		}
		
		$currentWorkout = $this->settings;
		
		return $currentWorkout;
	}
	
	/**
	 * Return all recorded activity for the specified activity ID
	 *
	 * @param \stdClass $config
	 * @param int       $id
	 *
	 * @return array
	 */
	public function getRecordedActivity( $config, $id ) {
		
		global $wpdb;
		$Tables = Tables::getInstance();
		
		$fields = $Tables->getFields( 'workout' );
		
		$sql = "SELECT
					{$fields['set_no']}, {$fields['exercise_key']},
					{$fields['recorded']}, {$fields['reps']},
					{$fields['weight']}, {$fields['id']},
					{$fields['exercise_id']}, {$fields['group_no']}
				FROM {$this->table} WHERE (
				 ( {$fields['for_date']} LIKE %s ) AND
				  ( {$fields['user_id']} = %d ) AND
				  ( {$fields['program_id']} = %d ) AND
				  ( {$fields['activity_id']} = %d )
				) ORDER BY {$fields['group_no']}, {$fields['set_no']}";
		
		// $sql = $Tracker->prepare_in( $sql, $group->exercises );
		
		$sql = $wpdb->prepare( $sql,
			$config->date . '%',
			$config->userId,
			$config->programId,
			$id
		);
		
		Utilities::get_instance()->log( "After prepare() processing: {$sql}" );
		
		$records = $wpdb->get_results( $sql );
		
		Utilities::get_instance()->log( "Fetched " . count( $records ) . " records from DB" );
		
		$saved = array();
		
		if ( ! empty( $records ) ) {
			
			foreach ( $records as $r ) {
				
				if ( ! isset( $saved[ $r->group_no ] ) ) {
					
					Utilities::get_instance()->log( "Adding new saved data.." );
					$saved[ $r->group_no ] = new \stdClass();
				}
				
				if ( ! isset( $saved[ $r->group_no ]->saved_exercises ) ) {
					
					Utilities::get_instance()->log( "Adding new exercises array." );
					$saved[ $r->group_no ]->saved_exercises = array();
				}
				
				if ( ! isset( $saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ] ) ) {
					
					Utilities::get_instance()->log( "Adding new class to store set information." );
					$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ] = new \stdClass();
				}
				
				if ( ! isset( $saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set ) ) {
					
					Utilities::get_instance()->log( "Adding new sets array." );
					$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set = array();
				}
				
				if ( ! isset( $saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ] ) ) {
					
					Utilities::get_instance()->log( "Adding new data object for set." );
					$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ] = new \stdClass();
				}
				
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->ex_id    = $r->exercise_id;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->id       = $r->id;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->recorded = $r->recorded;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->weight   = $r->weight;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->reps     = $r->reps;
			}
		}
		
		Utilities::get_instance()->log( "Returning: " . print_r( $saved, true ) );
		
		return $saved;
		
	}
	
	/**
	 * Save the user specific workout data
	 *
	 * @param array $data
	 * @param array $format
	 *
	 * @return bool|int
	 */
	public function save_userData( $data, $format ) {
		
		Utilities::get_instance()->log( "Saving data: " . print_r( $data, true ));
		
		global $wpdb;
		
		if ( $wpdb->replace( $this->table, $data, $format ) !== false ) {
			
			$id = $wpdb->insert_id;
			Utilities::get_instance()->log( "Replaced/Inserted ID: {$id}" );
			
			return $id;
		}
		
		return false;
	}
	
	/**
	 * Save the status for an activity (from the front-end status/checkin dashboard options)
	 *
	 * @param array $post_data
	 *
	 * @return bool
	 */
	public function save_activity_status( $post_data ) {
		
		$Tracker = Tracker::getInstance();
		$Action  = Action::getInstance();
		$Article = Article::getInstance();
		
		$completed         = isset( $_POST['completed'] ) ? $Tracker->sanitize( $_POST['completed'] ) : false;
		$userId            = isset( $_POST['user_id'] ) ? $Tracker->sanitize( $_POST['user_id'] ) : null;
		$activityId        = isset( $_POST['activity_id'] ) ? $Tracker->sanitize( $_POST['activity_id'] ) : null;
		$articleId         = isset( $_POST['article_id'] ) ? $Tracker->sanitize( $_POST['article_id'] ) : null;
		$programId         = isset( $_POST['program_id'] ) ? $Tracker->sanitize( $_POST['program_id'] ) : null;
		$checkedin_date    = isset( $_POST['recorded'] ) ? $Tracker->sanitize( $_POST['recorded'] ) : null;
		$checkin_date      = isset( $_POST['for_date'] ) ? $Tracker->sanitize( $_POST['for_date'] ) : null;
		$checkin_shortname = $Article->get_checkin_shortname( $articleId, CHECKIN_ACTIVITY );
		
		if ( false == $completed ) {
			
			$completed = 2;
		}
		
		$checkin = array(
			'user_id'            => $userId,
			'activity_id'        => $activityId,
			'article_id'         => $articleId,
			'program_id'         => $programId,
			'descr_id'           => $activityId,
			// descr_id is the assignment ID (no assignment for an activity so using $activityId )
			'checkedin_date'     => $checkedin_date,
			'checkin_type'       => CHECKIN_ACTIVITY,
			'checkin_date'       => $checkin_date,
			'checkedin'          => $completed,
			'checkin_short_name' => $checkin_shortname,
		);
		
		if ( false === $Action->save_check_in( $checkin, CHECKIN_ACTIVITY ) ) {
			
			Utilities::get_instance()->log( "Error saving activity check-in for user {$userId}" );
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Load the exericse specific history for the user (sets/reps/etc).
	 *
	 * @param int      $exercise_id
	 * @param int      $userId
	 * @param null|int $programId
	 * @param string   $start_date
	 *
	 * @return array|bool
	 */
	public function getExerciseHistory( $exercise_id, $userId, $programId = null, $start_date = 'all' ) {
		
		global $wpdb;
		global $currentProgram;
		
		if ( is_null( $programId ) ) {
			
			$programId = $currentProgram->id;
		}
		
		if ( 'all' == $start_date ) {
			
			$start_date = $currentProgram->startdate;
		}
		
		$sql = $wpdb->prepare( "
                        SELECT for_date, MAX(weight) AS weight, reps
                          FROM {$this->table}
                          WHERE {$this->fields['program_id']} = %d AND {$this->fields['user_id']} = %d AND {$this->fields['for_date']} >= %s
                          AND {$this->fields['exercise_id']} = %d
                        GROUP BY {$this->fields['for_date']}",
			$programId,
			$userId,
			$currentProgram->startdate,
			$exercise_id
		);
		Utilities::get_instance()->log( "SQL: {$sql}" );
		
		$results = $wpdb->get_results( $sql );
		
		$weights = array();
		$reps    = array();
		
		if ( empty( $results ) ) {
			
			Utilities::get_instance()->log( "Error loading from database: Zero records found & possible error:" . $wpdb->print_error() );
			
			return false;
		} else {
			
			Utilities::get_instance()->log( "Loaded " . count( $results ) . " records" );
			
			foreach ( $results as $rec ) {
				
				$rec->for_date = strtotime( $rec->for_date, current_time( 'timestamp' ) );
				// $ts = strtotime(  );
				
				$weights[] = array( $rec->for_date * 1000, number_format( (float) $rec->weight, 2 ) );
				$reps[]    = array( $rec->for_date * 1000, number_format( (float) $rec->reps, 2 ) );
			}
		}
		
		return array( $weights, $reps );
		// return $this->transformForJS( $records );
	}
	
	/**
	 * Return all tracked activity data for the specified user ID
	 *
	 * @param int $userId
	 *
	 * @return array|bool
	 */
	public function loadAllUserActivities( $userId ) {
		
		$Tracker  = Tracker::getInstance();
		$Exercise = Exercise::getInstance();
		$Program  = Program::getInstance();
		
		global $currentExercise;
		
		$today = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
		
		$programId = $Program->getProgramIdForUser( $userId );
		
		$delay = $Tracker->getDelay( 'now', $userId );
		
		Utilities::get_instance()->log( "Loading assignments for user {$userId} in program {$programId}" );
		
		$workout_byDate = $this->loadWorkoutByMeta( $programId, 'startdate', $today, '<=', 'date', 'startdate' );
		
		Utilities::get_instance()->log( "Not using  assignments for user {$userId} in program {$programId}" );
		$workout_byDay = $this->loadWorkoutByMeta( $programId, 'startday', $delay, '<=', 'numeric', 'startday' );
		
		if ( is_array( $workout_byDate ) && is_array( $workout_byDay ) ) {
			$workouts = array_merge( $workout_byDay, $workout_byDate );
		}
		
		if ( is_array( $workout_byDate ) && ( ! is_array( $workout_byDay ) ) ) {
			$workouts = $workout_byDate;
		}
		
		if ( is_array( $workout_byDay ) && ( ! is_array( $workout_byDate ) ) ) {
			$workouts = $workout_byDay;
		}
		
		Utilities::get_instance()->log( "Returned " . count( $workouts ) . " to process " );
		
		if ( empty( $workouts ) ) {
			
			Utilities::get_instance()->log( "No records found." );
			
			return false;
		}
		
		$userData = $this->loadUserActivityData( $userId, $programId );
		
		foreach ( $userData as $e => $info ) {
			
			Utilities::get_instance()->log( "Processing exercise {$e}" );
			
			if ( isset( $userData[ $e ] ) ) {
				
				$Exercise->set_currentExercise( $e );
				$userData[ $e ]->name = $currentExercise->title;
				$userData[ $e ]->type = $currentExercise->type;
				// $userData[$e]->descr = $currentExercise->descr;
			}
		}
		
		return $userData;
	}
	
	/**
	 * Find the defined workout for the program based on the supplied metadata field/value
	 *
	 * @param  int    $programId
	 * @param  string $key
	 * @param  mixed  $value
	 * @param string  $comp
	 * @param string  $type
	 * @param string  $orderbyKey
	 *
	 * @return array
	 */
	private function loadWorkoutByMeta( $programId, $key, $value, $comp = '=', $type = 'numeric', $orderbyKey = 'startday' ) {
		
		$Tracker = Tracker::getInstance();
		$records = array();
		
		Utilities::get_instance()->log( "for program #: {$programId}" );
		
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => $this->cpt_slug,
			'post_status'    => 'publish',
			'meta_key'       => "_e20r-{$this->type}-{$orderbyKey}",
			'order_by'       => 'meta_value',
			'order'          => 'ASC',
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
		
		$query = new \WP_Query( $args );
		
		Utilities::get_instance()->log( "Returned workouts: {$query->post_count}" );
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			$records[] = $this->loadSettings( get_the_ID() );
			
		}
		
		Utilities::get_instance()->log( "Returning " .
		                   count( $records ) . " records to: " . $Tracker->whoCalledMe() );
		
		wp_reset_query();
		
		return $records;
	}
	
	/**
	 * Load activity (workout) tracking data for the user
	 *
	 * @param int      $userId
	 * @param null|int $programId
	 *
	 * @return array
	 */
	public function loadUserActivityData( $userId, $programId = null ) {
		
		Utilities::get_instance()->log( "Loading activity data for {$userId} in program {$programId}" );
		
		global $wpdb;
		global $currentProgram;
		$Program = Program::getInstance();
		
		if ( ( is_null( $programId ) ) || ( $currentProgram->id != $programId ) ) {
			
			Utilities::get_instance()->log( "Change program id from {$currentProgram->id} to {$programId}" );
			$programId = $Program->getProgramIdForUser( $userId );
		}
		
		$activities = array();
		
		$sql = $wpdb->prepare(
			"SELECT exercise_id, for_date,
                    exercise_key, group_no, set_no, weight, reps
             FROM {$this->table}
             WHERE user_id = %d AND program_id = %d AND for_date >= %s
             ORDER BY exercise_id, for_date, group_no, set_no, exercise_key DESC",
			$userId,
			$programId,
			$currentProgram->startdate
		);
		
		Utilities::get_instance()->log( "SQL: {$sql}" );
		
		$records = $wpdb->get_results( $sql, ARRAY_A );
		
		if ( ! empty( $records ) ) {
			
			Utilities::get_instance()->log( "Processing " . count( $records ) . " workout records for user {$userId}" );
			
			foreach ( $records as $wr ) {
				
				$wr['for_date'] = strtotime( $wr['for_date'], current_time('timestamp' ) );
				
				if ( ! isset( $activities[ $wr['exercise_id'] ] ) ) {
					
					$activities[ $wr['exercise_id'] ] = new \stdClass();
				}
				
				if ( ! isset( $activities[ $wr['exercise_id'] ]->when ) ) {
					$activities[ $wr['exercise_id'] ]->when = array();
				}
				
				if ( ! isset( $activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ] ) ) {
					$activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ] = new \stdClass();
				}
				
				if ( ! isset( $activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group ) ) {
					
					$activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group = array();
				}
				
				if ( ! isset( $activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group[ $wr['group_no'] ] ) ) {
					
					$activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group[ $wr['group_no'] ] = new \stdClass();
				}
				
				$activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group[ $wr['group_no'] ]->set    = $wr['set_no'];
				$activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group[ $wr['group_no'] ]->weight = $wr['weight'];
				$activities[ $wr['exercise_id'] ]->when[ $wr['for_date'] ]->group[ $wr['group_no'] ]->reps   = $wr['reps'];
			}
			
			Utilities::get_instance()->log( "Completed processing: Returning data for " . count( $activities ) . " workouts" );
			// Utilities::get_instance()->log($activities);
		}
		
		return $activities;
	}
	
	/**
	 * Loads the user specific workout data
	 *
	 * @param int        $userId
	 * @param string     $start
	 * @param string     $end
	 * @param null|int   $programId
	 * @param null|array $fields
	 *
	 * @return array
	 */
	public function load_userData( $userId, $start = 'start', $end = 'end', $programId = null, $fields = null ) {
		
		global $wpdb;
		
		$Program = Program::getInstance();
		global $currentProgram;
		
		$result            = array();
		$result['workout'] = array();
		
		// Set/get the correct program ID (and configure the $currentProgram global).
		if ( is_null( $programId ) ) {
			
			//Set and load (if necessary) the program for this user
			$programId = $Program->getProgramIdForUser( $userId );
		} else {
			
			if ( $programId != $currentProgram->id ) {
				Utilities::get_instance()->log( "Loading new program config for program with ID: {$programId}" );
				$Program->init( $programId );
			}
		}
		
		// TODO: Have to handle the 'all' to/from date/time.
		
		// Make sure the $from_when time is a valid time/date value
		if ( ! is_null( $start ) && ( false === ( $fromTS = strtotime( $start ) ) ) ) {
			
			Utilities::get_instance()->log( "Error: Invalid date/time in 'from' value" );
			
			return false;
		}
		
		if ( ! is_null( $end ) && ( false === ( $toTS = strtotime( $end ) ) ) ) {
			
			Utilities::get_instance()->log( "Error: Invalid date/time in 'to' value. Setting to default ('now')" );
			$toTS = strtotime( 'now' );
		}
		
		$period = null;
		
		if ( 'start' == $start ) {
			
			// We're starting from the beginning of the specified programId
			$from = date( 'Y-m-d', strtotime( $currentProgram->startdate ) );
		} else {
			$from = date( 'Y-m-d', $fromTS );
		}
		
		if ( 'end' == $end ) {
			// We're starting from the beginning of the specified programId
			$to = date( 'Y-m-d', strtotime( $currentProgram->enddate ) );
		} else {
			$to = date( 'Y-m-d', strtotime( $toTS ) );
		}
		
		if ( ! ( ( 'all' == $start ) && ( 'all' == $end ) ) ) {
			// We're not being asked to return all records.
			$period = "( ( {$this->fields['for_date']} >= '{$from} 00:00:00' ) AND ( {$this->fields['for_date']} <= '{$to} 23:59:59' ) )";
		} else {
			$period = null;
		}
		
		
		if ( is_array( $fields ) ) {
			$selected = join( ',', $fields );
		} else if ( is_string( $fields ) ) {
			$selected = $fields;
		} else {
			$selected = "*";
		}
		
		$sql = $wpdb->prepare( "SELECT {$selected}
                FROM {$this->table}
                WHERE {$this->fields['user_id']} = %d AND
                ( {$this->fields['program_id']} = %d )" .
		                       ( is_null( $period ) ? null : " AND {$period}" ) .
		                       " ORDER BY {$this->fields['for_date']} DESC",
			$userId,
			$currentProgram->id
		);
		
		Utilities::get_instance()->log( "SQL: {$sql}" );
		
		$records = $wpdb->get_results( $sql );
		
		if ( ! empty( $records ) ) {
			
			Utilities::get_instance()->log( "Located " . count( $records ) . " records in DB for user {$userId} related to program {$currentProgram->id}" );
			
			/**
			 * Array to construct for the workout records for a user:
			 *
			 * $programId = array(
			 *          'date' => array(
			 *              $activity_id => array(
			 *                  $exercise_id' => array (
			 *                      $exercise_key => array(
			 *                          $group_no => array(
			 *                              $set_no => stdClass(
			 *                                  'reps' = $r['reps'],
			 *                                  'weight' = $r['weight'],
			 *                              ),
			 *                         ),
			 *                      ),
			 *                  ),
			 *              ),
			 *          );
			 */
			foreach ( $records as $r ) {
				
				if ( ! isset( $result[ $r['{$this->fields["for_date"]}'] ] ) ) {
					
					$result[ $r["{$this->fields['for_date']}"] ] = array();
				}
				
				if ( ! isset( $result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ] ) ) {
					
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ] = array();
				}
				
				if ( ! isset( $result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ] ) ) {
					
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ] = array();
				}
				
				if ( isset( $result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ] ) ) {
					
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ] = array();
				}
				
				if ( isset( $result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ] ) ) {
					
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ] = array();
				}
				
				if ( isset( $result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ][ $r["{$this->fields['set_no']}"] ] ) ) {
					
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ][ $r["{$this->fields['set_no']}"] ] = new \stdClass();
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ][ $r["{$this->fields['set_no']}"] ]->reps;
					$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ][ $r["{$this->fields['set_no']}"] ]->weight;
				}
				
				$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ][ $r["{$this->fields['set_no']}"] ]->reps   = $r["{$this->fields['reps']}"];
				$result[ $r["{$this->fields['for_date']}"] ][ $r["{$this->fields['activity_id']}"] ][ $r["{$this->fields['exercise_id']}"] ][ $r["{$this->fields['exercise_key']}"] ][ $r["{$this->fields['group_no']}"] ][ $r["{$this->fields['set_no']}"] ]->weight = $r["{$this->fields['weight']}"];
				
			}
			
			Utilities::get_instance()->log( "Loaded and formatted " . count( $result ) . " records for user {$userId} in program {$currentProgram->id} between {$start} and {$end}" );
		} else {
			Utilities::get_instance()->log( "No records found when specifying user: {$userId}, start: {$start}, end: {$end}, program: {$currentProgram->id}, SELECT {$selected}" );
			Utilities::get_instance()->log( "Error? {$wpdb->last_error}" );
		}
		
		return $result;
	}
	
	/**
	 * Returns an array of all workouts merged with their associated settings.
	 *
	 * @param $statuses string|array - Statuses to return program data for.
	 *
	 * @return mixed - Array of program objects
	 */
	public function loadAllData( $statuses = 'any' ) {
		
		$query = array(
			'posts_per_page' => - 1,
			'post_type'      => self::post_type,
			'post_status'    => $statuses,
		);
		
		wp_reset_query();
		
		/* Fetch all Sequence posts */
		$workout_list = get_posts( $query );
		
		if ( empty( $workout_list ) ) {
			
			return array();
		}
		
		Utilities::get_instance()->log( "Loading program settings for " . count( $workout_list ) . ' settings' );
		
		foreach ( $workout_list as $key => $data ) {
			
			$settings = $this->loadSettings( $data->ID );
			
			$loaded_settings = (object) array_replace( (array) $data, (array) $settings );
			
			$workout_list[ $key ] = $loaded_settings;
		}
		
		wp_reset_postdata();
		
		return $workout_list;
	}
	
	/**
	 * Save the Workout Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific program.
	 *
	 * @return bool - True if successful at updating program settings
	 */
	public function saveSettings( $settings ) {
		
		$workoutId = $settings->id;
		
		$defaults = $this->defaultSettings();
		
		Utilities::get_instance()->log( "Saving metadata for new activity: " . print_r( $settings, true ) );
		
		$error = false;
		
		foreach ( $defaults as $key => $value ) {
			
			if ( in_array( $key, array( 'id', 'excerpt' ) ) ) {
				
				continue;
			}
			
			if ( false === $this->settings( $workoutId, 'update', $key, $settings->{$key} ) ) {
				
				if ( is_array( $settings->{$key} ) || is_object( $settings->{$key} ) ) {
					
					Utilities::get_instance()->log( "ERROR saving {$key} setting for workout definition with ID: {$workoutId}: " );
					Utilities::get_instance()->log( print_r( $settings->{$key}, true ) );
				} else {
					
					Utilities::get_instance()->log( "ERROR saving {$key} setting ({$settings->{$key}}) for workout definition with ID: {$workoutId}" );
				}
				
				$error = true;
			}
		}
		
		return ( ! $error );
	}
	
	public static function registerCPT() {
		
		$labels =  array(
			'name' => __( 'Activities', 'e20r-tracker'  ),
			'singular_name' => __( 'Activity', 'e20r-tracker' ),
			'slug' => self::post_type,
			'add_new' => __( 'New Activity', 'e20r-tracker' ),
			'add_new_item' => __( 'New Activity', 'e20r-tracker' ),
			'edit' => __( 'Edit Activity', 'e20r-tracker' ),
			'edit_item' => __( 'Edit Activity', 'e20r-tracker'),
			'new_item' => __( 'Add New', 'e20r-tracker' ),
			'view' => __( 'View Activities', 'e20r-tracker' ),
			'view_item' => __( 'View This Activity', 'e20r-tracker' ),
			'search_items' => __( 'Search Activities', 'e20r-tracker' ),
			'not_found' => __( 'No Activities Found', 'e20r-tracker' ),
			'not_found_in_trash' => __( 'No Activities Found In Trash', 'e20r-tracker' ),
		);
		
		$error = register_post_type(self::post_type,
			array( 'labels' => apply_filters( 'e20r-tracker-workout-cpt-labels', $labels ),
			       'public' => true,
			       'show_ui' => true,
				// 'show_in_menu' => true,
				   'menu_icon' => '',
				   'publicly_queryable' => true,
				   'hierarchical' => true,
				   'supports' => array('title','editor','thumbnail'),
				   'can_export' => true,
				   'show_in_nav_menus' => false,
				   'show_in_menu' => 'e20r-tracker-articles',
				   'rewrite' => array(
					   'slug' => apply_filters('e20r-tracker-workout-cpt-slug', 'tracker-activity'),
					   'with_front' => false,
				   ),
				   'has_archive' => apply_filters('e20r-tracker-workout-cpt-archive-slug', 'tracker-activity-archive'),
			)
		);
		
		if ( is_wp_error($error) ) {
			Utilities::get_instance()->log('ERROR: Failed to register e20r_workout CPT: ' . $error->get_error_message);
		}
	}
}