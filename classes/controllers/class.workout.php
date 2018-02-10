<?php

namespace E20R\Tracker\Controllers;

use E20R\Tracker\Models\Tables;
use E20R\Tracker\Models\Workout_Model;
use E20R\Tracker\Views\Workout_View;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */
class Workout extends Settings {
	
	private static $instance = null;
	/**
	 * @var Workout_Model|null
	 */
	public $model = null;
	/**
	 * @var Workout_View|null
	 */
	public $view = null;
	protected $table;
	protected $fields;
	
	public function __construct() {
		
		E20R_Tracker::dbg( "Workout::__construct() - Initializing Workout class" );
		
		$this->model = new Workout_Model();
		$this->view  = new Workout_View();
		
		parent::__construct( 'workout', Workout_Model::post_type, $this->model, $this->view );
	}
	
	/**
	 * @return Workout
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function getActivity( $identifier ) {
		
		E20R_Tracker::dbg( "Workout::getActivity() - Loading Activity data for {$identifier}" );
		
		if ( ! isset( $this->model ) ) {
			$this->init();
		}
		
		$workout = array();
		
		if ( is_numeric( $identifier ) ) {
			// Given an ID
			$workout = $this->model->load_activity( $identifier, 'any' );
		}
		
		E20R_Tracker::dbg( "Workout::getActivity() - Returning Activity data for {$identifier}" );
		
		return $workout;
		
	}
	
	public function init( $id = null ) {
		
		global $currentWorkout;
		$Tables = Tables::getInstance();
		
		try {
			$this->table = $Tables->getTable( 'workout' );
		} catch ( \Exception $exception ) {
			E20R_Tracker::dbg( "Error getting the workout table: " . $exception->getMessage() );
			
			return false;
		}
		
		$this->fields = $Tables->getFields( 'workout' );
		
		if ( empty( $currentWorkout ) || ( isset( $currentWorkout->id ) && ( $currentWorkout->id != $id ) ) ) {
			E20R_Tracker::dbg( "Workout::init() - received id value: {$id}" );
			
			// $currentWorkout = parent::init( $id );
			$this->model->init( $id );
			
			E20R_Tracker::dbg( "Workout::init() - Loaded settings for {$id}:" );
			// E20R_Tracker::dbg($currentWorkout);
		}
		
		return true;
	}
	
	public function getWorkout( $shortName ) {
		
		if ( ! isset( $this->model ) ) {
			$this->init();
		}
		
		$pgmList = $this->model->loadAllData( 'any' );
		
		foreach ( $pgmList as $pgm ) {
			if ( $pgm->shortname == $shortName ) {
				unset( $pgmList );
				
				return $pgm;
			}
		}
		
		unset( $pgmList );
		
		return false; // Returns false if the program isn't found.
	}
	
	public function editor_metabox_setup( $post ) {
		
		// global $currentWorkout;
		
		E20R_Tracker::dbg( "Workout::editor_metabox_setup() - Loading settings for workout page: " . $post->ID );
		$this->init( $post->ID );
		
		// $currentWorkout = $this->model->find( 'id', $post->ID );
		
		add_meta_box( 'e20r-tracker-workout-settings', __( 'Activity Settings', 'e20r-tracker' ), array(
			&$this,
			"addMeta_WorkoutSettings",
		), Workout_Model::post_type, 'normal', 'core' );
		
	}
	
	public function ajax_getPlotDataForUser() {
		
		E20R_Tracker::dbg( 'Workout::ajax_getPlotDataForUser() - Requesting workout data' );
		check_ajax_referer( 'e20r-tracker-data', 'e20r-weight-rep-chart' );
		
		$Program = Program::getInstance();
		$Client  = Client::getInstance();
		$Tracker = Tracker::getInstance();
		
		global $currentProgram;
		
		E20R_Tracker::dbg( "Workout::ajax_getPlotDataForUser() - Nonce is OK" );
		
		$user_id = isset( $_POST['client_id'] ) ? intval( $_POST['client_id'] ) : null;
		
		if ( $Client->validateAccess( $user_id ) ) {
			$Program->getProgramIdForuser( $user_id );
		} else {
			E20R_Tracker::dbg( "Workout::ajax_getPlotDataForUser() - Logged in user ID does not have access to the data for user {$user_id}" );
			wp_send_json_error( __( "Your membership level prevents you from accessing this data. Please upgrade.", "e20r-tracker" ) );
			exit();
		}
		
		$exercise_id = isset( $_POST['exercise_id'] ) ? $Tracker->sanitize( $_POST['exercise_id'] ) : 0;
		
		E20R_Tracker::dbg( "Workout::ajax_getPlotDataForUser() - Using measurement data & configure dimensions" );
		
		$stats = $this->model->getExerciseHistory( $exercise_id, $user_id, $currentProgram->id, $currentProgram->startdate );
		
		// $stats = $this->generate_stats( $history );
		
		if ( isset( $_POST['wh_h_dimension'] ) ) {
			
			E20R_Tracker::dbg( "Workout::ajax_getPlotDataForUser() - We're displaying the front-end user progress summary" );
			$dimensions = array(
				'width'  => intval( $_POST['wh_w_dimension'] ),
				'wtype'  => sanitize_text_field( $_POST['wh_w_dimension_type'] ),
				'height' => intval( $_POST['wh_h_dimension'] ),
				'htype'  => sanitize_text_field( $_POST['wh_h_dimension_type'] ),
			);
			
			// $dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
		} else {
			
			E20R_Tracker::dbg( "Workout::ajax_getPlotDataForUser() - We're displaying on the admin page." );
			$dimensions = array( 'width' => '650', 'height' => '500', 'htype' => 'px', 'wtype' => 'px' );
		}
		
		E20R_Tracker::dbg( "Workout::ajax_getPlotDataForuser() - Dimensions: " );
		// E20R_Tracker::dbg($dimensions);
		
		E20R_Tracker::dbg( "Workout::ajax_getPlotDataForuser() - Stats: " );
		// E20R_Tracker::dbg($stats);
		
		$html = $this->view->view_WorkoutStats( $user_id, $exercise_id, $dimensions );
		
		// $stats = $this->generate_stats( $activities );
		// $reps = $this->generatePlotData( $workout_data, 'reps' );
		
		E20R_Tracker::dbg( "Workout::ajax_get_PlotDataForUser() - Generated plot data for measurements" );
		$data = json_encode( array( 'success' => true, 'html' => $html, 'stats' => $stats ), JSON_NUMERIC_CHECK );
		echo $data;
		// wp_send_json_success( array( 'html' => $data, 'weight' => $weight, 'girth' => $girth ) );
		wp_die();
	}
	
	public function generate_stats( $data ) {
	 
		if ( empty( $data ) ) {
			
			return array();
		}
		
		foreach ( $data as $exercise ) {
			
			foreach ( $exercise as $workout ) {
				if ( is_object( $exercise->history ) ) {
					
					$workout_weight[] = array(
						( strtotime( $workout->for_date ) * 1000 ),
						number_format( (float) $workout->weight, 2 ),
					);
					$workout_reps[]   = array(
						( strtotime( $workout->for_date ) * 1000 ),
						number_format( (float) $workout->reps, 2 ),
					);
				}
			}
		}
		
		return array( $workout_weight, $workout_reps );
	}
	
	public function listUserActivities( $userId ) {
	 
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		
		$config            = new \stdClass();
		$config->type      = 'activity';
		$config->post_date = null;
		
		$config->userId  = $userId; // $userId;
		$config->startTS = $Program->startdate( $config->userId );
		$config->delay   = $Tracker->getDelay( 'now' );
		
		$activities = $this->model->loadAllUserActivities( $userId );
		
		E20R_Tracker::dbg( "Workout::listUserActivities() - Received " . count( $activities ) . " activity records..." );
		
		// Get and load the statistics for the user.
		if ( isset( $_POST['wh_h_dimension'] ) ) {
			
			E20R_Tracker::dbg( "Workout::listUserActivities() - We're displaying the front-end user progress summary" );
			$dimensions = array(
				'width'  => intval( $_POST['wh_w_dimension'] ),
				'wtype'  => sanitize_text_field( $_POST['wh_w_dimension_type'] ),
				'height' => intval( $_POST['wh_h_dimension'] ),
				'htype'  => sanitize_text_field( $_POST['wh_h_dimension_type'] ),
			);
			
			// $dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
		} else {
			
			E20R_Tracker::dbg( "Workout::listUserActivities() - We're displaying on the admin page." );
			$dimensions = array( 'width' => '650', 'height' => '300', 'htype' => 'px', 'wtype' => 'px' );
		}
		
		// $html = $this->view->view_WorkoutStats( $config->userId, $data, $dimensions );
		return $this->view->viewExerciseProgress( $activities, null, $userId, $dimensions );
	}
	
	public function saveExData_callback() {
	 
		$Tracker = Tracker::getInstance();
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'Please log in to access this service', 'e20r-tracker' ) );
			exit();
		}
		
		check_ajax_referer( 'e20r-tracker-activity', 'e20r-tracker-activity-input-nonce' );
		
		E20R_Tracker::dbg( "Workout::saveExData_callback() - Has the right privs to save data: " );
		// E20R_Tracker::dbg($_POST);
		
		if ( isset( $POST['completed'] ) && ( intval( $_POST['completed'] == 1 ) ) ) {
			
			E20R_Tracker::dbg( "Workout::saveExData_callback() - User indicated their workout is complete." );
			$id = $this->model->save_activity_status( $_POST );
			wp_send_json_success( array( 'id' => $id ) );
			exit();
		}
		
		$data = array();
		$skip = array( 'action', 'e20r-tracker-activity-input-nonce' );
		
		foreach ( $_POST as $k => $v ) {
			
			if ( $k == 'recorded' ) {
				
				E20R_Tracker::dbg( "Workout::saveExData_callback() - Saving date/time of record." );
				$v = date_i18n( 'Y-m-d H:i:s', $Tracker->sanitize( $v ) );
			}
			
			if ( $k == 'for_date' ) {
				
				E20R_Tracker::dbg( "Workout::saveExData_callback() - Saving date/time for when the record should have been recorded: {$v}." );
				$v = date_i18n( 'Y-m-d H:i:s', strtotime( $Tracker->sanitize( $v ) ) );
			}
			if ( ! in_array( $k, $skip ) ) {
				
				
				E20R_Tracker::dbg( "Workout::saveExData_callback() - Saving {$k} as {$v} for record." );
				$data[ $k ] = $Tracker->sanitize( $v );
			}
		}
		
		E20R_Tracker::dbg( "Workout::saveExData_callback() - Data array to use" );
		
		try {
			$format = $Tracker->setFormatForRecord( $data );
		} catch ( \Exception  $e ) {
			E20R_Tracker::dbg( "Workout::saveExData_callback() - Error setting format: " . $e->getMessage() );
			
			wp_send_json_error();
			exit();
		}
		
		if ( ( $id = $this->model->save_userData( $data, $format ) ) === false ) {
			E20R_Tracker::dbg( "Workout::saveExData_callback() - Error saving user data record!" );
			wp_send_json_error();
			exit();
		}
		
		E20R_Tracker::dbg( "Workout::saveExData_callback() - Saved record with ID: {$id}" );
		wp_send_json_success( array( 'id' => $id ) );
		exit();
	}
	
	public function load_user_activity( $activity_id, $user_id ) {
		
		$this->init( $activity_id );
		
		
	}
	
	public function loadUserData( $userId, $start = 'start', $end = 'end', $programId = null, $fields = null ) {
		
		return $this->model->load_userData( $userId, $start, $end, $programId, $fields );
	}
	
	/**
	 * Save the Workout Settings to the metadata table.
	 *
	 * @param $post_id (int) - ID of CPT settings for the specific article.
	 *
	 * @return bool - True if successful at updating article settings
	 */
	public function saveSettings( $post_id ) {
		
		global $post;
		$Tracker = Tracker::getInstance();
		
		if ( ( ! isset( $post->post_type ) ) || ( $post->post_type != Workout_Model::post_type ) ) {
			
			E20R_Tracker::dbg( "Workout::saveSettings() - Not a e20r_workout CPT: " );
			
			return $post_id;
		}
		
		if ( empty( $post_id ) ) {
			
			E20R_Tracker::dbg( "Workout::saveSettings() - No post ID supplied" );
			
			return false;
		}
		
		if ( wp_is_post_revision( $post_id ) ) {
			
			return $post_id;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			
			return $post_id;
		}
		
		E20R_Tracker::dbg( "Workout::saveSettings()  - Saving workout to database." );
		
		$groups  = array();
		$workout = $this->model->loadSettings( $post_id );
		
		$groupData     = isset( $_POST['e20r-workout-group'] ) ? $Tracker->sanitize( $_POST['e20r-workout-group'] ) : array( $post_id => $this->model->defaultGroup() );
		$exData        = isset( $_POST['e20r-workout-group_exercise_id'] ) ? $Tracker->sanitize( $_POST['e20r-workout-group_exercise_id'] ) : array();
		$orderData     = isset( $_POST['e20r-workout-group_exercise_order'] ) ? $Tracker->sanitize( $_POST['e20r-workout-group_exercise_order'] ) : array();
		$groupSetCount = isset( $_POST['e20r-workout-group_set_count'] ) ? $Tracker->sanitize( $_POST['e20r-workout-group_set_count'] ) : array();
		$groupSetTempo = isset( $_POST['e20r-workout-groups-group_tempo'] ) ? $Tracker->sanitize( $_POST['e20r-workout-groups-group_tempo'] ) : array();
		$groupSetRest  = isset( $_POST['e20r-workout-groups-group_rest'] ) ? $Tracker->sanitize( $_POST['e20r-workout-groups-group_rest'] ) : array();
		
		$workout->program_ids         = isset( $_POST['e20r-workout-program_ids'] ) ? $Tracker->sanitize( $_POST['e20r-workout-program_ids'] ) : array();
		$workout->days                = isset( $_POST['e20r-workout-days'] ) ? $Tracker->sanitize( $_POST['e20r-workout-days'] ) : array();
		$workout->workout_ident       = isset( $_POST['e20r-workout-workout_ident'] ) ? $Tracker->sanitize( $_POST['e20r-workout-workout_ident'] ) : 'A';
		$workout->phase               = isset( $_POST['e20r-workout-phase'] ) ? $Tracker->sanitize( $_POST['e20r-workout-phase'] ) : 1;
		$workout->assigned_user_id    = isset( $_POST['e20r-workout-assigned_user_id'] ) ? $Tracker->sanitize( $_POST['e20r-workout-assigned_user_id'] ) : array( 0 ); // Default is "everybody"
		$workout->assigned_usergroups = isset( $_POST['e20r-workout-assigned_usergroups'] ) ? $Tracker->sanitize( $_POST['e20r-workout-assigned_usergroups'] ) : array( 0 );
		$workout->startdate           = isset( $_POST['e20r-workout-startdate'] ) ? $Tracker->sanitize( $_POST['e20r-workout-startdate'] ) : null;
		$workout->enddate             = isset( $_POST['e20r-workout-enddate'] ) ? $Tracker->sanitize( $_POST['e20r-workout-enddate'] ) : null;
		$workout->startday            = isset( $_POST['e20r-workout-startday'] ) ? $Tracker->sanitize( $_POST['e20r-workout-startday'] ) : null;
		$workout->endday              = isset( $_POST['e20r-workout-endday'] ) ? $Tracker->sanitize( $_POST['e20r-workout-endday'] ) : null;
		
		$test = (array) $exData;
		
		if ( ! empty( $test ) ) {
			
			foreach ( $groupData as $key => $groupNo ) {
				
				$groups[ $groupNo ]->group_set_count = $groupSetCount[ $groupNo ];
				$groups[ $groupNo ]->group_tempo     = $groupSetTempo[ $groupNo ];
				$groups[ $groupNo ]->group_rest      = $groupSetRest[ $groupNo ];
				
				if ( isset( $exData[ $key ] ) ) {
					E20R_Tracker::dbg( "Workout::saveSettings() - Adding exercise data from new definition" );
					$groups[ $groupNo ]->exercises[ $orderData[ $key ] ] = $exData[ $key ];
				}
				
				if ( ( count( $workout->groups[ $groupNo ]->exercises ) > 1 ) &&
				     ( isset( $workout->groups[ $groupNo ]->exercises[0] ) )
				) {
					
					E20R_Tracker::dbg( "Workout::saveSettings() - Clearing data we don't need" );
					unset( $groups[ $groupNo ]->exercises[ $orderData[ $key ] ][0] );
				}
				
			}
			E20R_Tracker::dbg( "Workout::saveSettings() - Groups:" );
			// E20R_Tracker::dbg($groups);
		}
		
		// Add workout group data/settings
		$workout->groups = $groups;
		
		E20R_Tracker::dbg( 'Workout::saveSettings() - Workout data to save:' );
		// E20R_Tracker::dbg($workout);
		
		if ( $this->model->saveSettings( $workout ) ) {
			
			E20R_Tracker::dbg( 'Workout::saveSettings() - Saved settings/metadata for this e20r_workout CPT' );
			
			return $post_id;
		} else {
			E20R_Tracker::dbg( 'Workout::saveSettings() - Error saving settings/metadata for this e20r_workout CPT' );
			
			return false;
		}
		
		
	}
	
	public function getPeers( $workoutId = null ) {
		
		if ( is_null( $workoutId ) ) {
			
			global $post;
			// Use the parent value for the current post to get all of its peers.
			$workoutId = $post->post_parent;
		}
		
		$workouts = new \WP_Query( array(
			'post_type'      => 'page',
			'post_parent'    => $workoutId,
			'posts_per_page' => - 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		$workoutList = array(
			'pages' => $workouts->posts,
		);
		
		foreach ( $workoutList['posts'] as $k => $v ) {
			
			if ( $v == get_the_ID() ) {
				
				if ( isset( $workouts->posts[ $k - 1 ] ) ) {
					
					$workoutList['prev'] = $workouts->posts[ $k - 1 ];
				}
				
				if ( isset( $workouts->posts[ $k + 1 ] ) ) {
					
					$workoutList['next'] = $workouts->posts[ $k + 1 ];
				}
			}
		}
		
		wp_reset_postdata();
		
		return $workoutList;
	}
	
	public function addMeta_WorkoutSettings() {
		
		// global $post;
		global $currentWorkout;
		
		E20R_Tracker::dbg( "Workout::addMeta_WorkoutSettings() - Loading settings metabox for workout page: " . $currentWorkout->id );
		// $this->init( $post->ID );
		// $currentWorkout = $this->model->find( 'id', $post->ID );
		
		if ( ! empty( $currentWorkout ) ) {
			
			E20R_Tracker::dbg( "Workout::addMeta_WorkoutSettings() - Loaded a workout with settings..." );
			echo $this->view->viewSettingsBox( $currentWorkout );
		} else {
			
			E20R_Tracker::dbg( "Workout::addMeta_WorkoutSettings() - Loaded an empty/defaul workout definition..." );
			echo $this->view->viewSettingsBox( $this->model->defaultSettings() );
		}
		
	}
	
	public function getActivities( $aIds = null ) {

//        global $currentProgram;
		
		if ( empty( $aIds ) ) {
			E20R_Tracker::dbg( 'Workout::getActivities() - Loading all activities from DB' );
			$activities = $this->model->find( 'id', 'any' ); // Will return all of the defined activities
		} else {
			E20R_Tracker::dbg( 'Workout::getActivities() - Loading specific activity from DB' );
			$activities = $this->model->find( 'id', $aIds );
		}
		
		E20R_Tracker::dbg( "Workout::getActivities() - Found " . count( $activities ) . " activities." );
		
		return $activities;
	}
	
	/**
	 * For the e20r_activity_archive shortcode.
	 *
	 * @param null $attributes
	 *
	 * @return string
	 * @since 0.8.0
	 */
	public function shortcode_act_archive( $attributes = null ) {
		
		E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Loading shortcode data for the activity archive." );
		
		global $current_user;
		global $currentProgram;
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		$config                = new \stdClass();
		$config->userId        = $current_user->ID;
		$config->programId     = $currentProgram->id;
		$config->expanded      = false;
		$config->show_tracking = 0;
		$config->phase         = 0;
		$config->print_only    = null;
		
		$tmp = shortcode_atts( array(
			'period'     => 'current',
			'print_only' => null,
		), $attributes );
		
		foreach ( $tmp as $key => $val ) {
			
			if ( ! empty( $val ) ) {
				$config->{$key} = $val;
			}
		}
		
		// Valid "false" responses for print_only atribute can include: array( 'no', 'false', 'null', '0', 0, false, null );
		$true_responses = array( 'yes', 'true', '1', 1, true );
		
		if ( in_array( $config->print_only, $true_responses ) ) {
			E20R_Tracker::dbg( "Workout::shortcode_act_archive() - User requested the archive be printed (i.e. include all unique exercises for the week)" );
			$config->print_only = true;
		} else {
			E20R_Tracker::dbg( "Workout::shortcode_act_archive() - User did NOT request the archive be printed" );
			$config->print_only = false;
		}
		
		if ( 'current' == $config->period ) {
			$period = E20R_CURRENT_WEEK;
		}
		
		if ( 'previous' == $config->period ) {
			$period = E20R_PREVIOUS_WEEK;
		}
		
		if ( 'next' == $config->period ) {
			$period = E20R_UPCOMING_WEEK;
		}
		
		E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Period set to {$config->period}." );
		
		$activities = $this->getActivityArchive( $current_user->ID, $currentProgram->id, $period );
		
		E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Check whether we're generating the list of exercises for print only: " . ( $config->print_only ? 'Yes' : 'No' ) );
		
		if ( true === $config->print_only ) {
		 
			E20R_Tracker::dbg( "Workout::shortcode_act_archive() - User requested this activity archive be printed. Listing unique exercises." );
			
			$printable         = array();
			$already_processed = array();
			
			foreach ( $activities as $key => $workout ) {
				
				if ( 'header' !== $key && ( ! in_array( $workout->id, $already_processed ) ) ) {
					
					$routine = new \stdClass();
					
					if ( ( 0 == $config->phase ) || ( $config->phase < $workout->phase ) ) {
						
						$routine->phase = $workout->phase;
						E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Setting phase number for the archive: {$config->phase}." );
					}
					
					$routine->id          = $workout->id;
					$routine->name        = $workout->title;
					$routine->description = $workout->excerpt;
					$routine->started     = $workout->startdate;
					$routine->ends        = $workout->enddate;
					$routine->days        = $workout->days;
					
					$list = array();
					
					foreach ( $workout->groups as $grp ) {
						
						E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Adding " . count( $grp->exercises ) . " to list of exercises for routine # {$routine->id}" );
						$list = array_merge( $list, $grp->exercises );
					}
					
					$routine->exercises = array_unique( $list, SORT_NUMERIC );
					
					E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Total number of exercises for  routine #{$routine->id}: " . count( $routine->exercises ) );
					$already_processed[] = $routine->id;
					$printable[]         = $routine;
				}
			}
			
			E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Will display " . count( $printable ) . " workouts and their respective exercises for print" );
			
			return $this->view->display_printable_list( $printable, $config );
		}
		
		E20R_Tracker::dbg( "Workout::shortcode_act_archive() - Grabbed activity count: " . count( $activities ) );
		
		return $this->view->displayArchive( $activities, $config );
	}
	
	/**
	 *
	 * Returns an archive of activities based on the requested period.
	 * Currently supports previous, current and next week constants.
	 *
	 * @param int $userId    -- User's Id
	 * @param int $programId -- Program to process for
	 * @param int $period    -- The period (
	 *
	 * @return array - list of activities keyed by day id (day 1 - 7, 1 == Monday)
	 */
	public function getActivityArchive( $userId, $programId, $period = E20R_CURRENT_WEEK ) {
	 
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		$Article = Article::getInstance();
		
		$startedTS = $Program->startdate( $userId, $programId, true );
		$started   = date( 'Y-m-d H:i:s', $startedTS );
		// $started = $currentProgram->startdate;
		
		// $currentDay  = $Tracker->getDelay( 'now', $userId );
		$currentDate = date( 'Y-m-d', current_time( 'timestamp' ) );
		
		$user = new \WP_User( $userId );
		
		foreach ( (array) $user->roles as $role ) {
			
			if ( false !== strpos( $role, 'e20r_tracker_exp' ) ) {
				
				$user_role = $role;
				break;
			}
		}
		
		E20R_Tracker::dbg( "Workout::getActivityArchive() - User ({$userId}) started program ({$programId}) on: {$started}" );
		
		// Calculate release_days to include for the $period
		switch ( $period ) {
			
			case E20R_UPCOMING_WEEK:
				E20R_Tracker::dbg( "Workout::getActivityArchive() - For the upcoming (next) week" );
				
				if ( date( 'N', current_time( 'timestamp' ) ) == 7 ) {
					$mondayTS = strtotime( "next monday {$currentDate}" );
					$sundayTS = strtotime( "next sunday {$currentDate}" );
				} else {
					$mondayTS = strtotime( "monday next week {$currentDate} " );
					$sundayTS = strtotime( "sunday next week {$currentDate}" );
				}
				
				$period_string = "Activities next week";
				if ( date( 'N', current_time( 'timestamp' ) ) <= 5 ) {
					E20R_Tracker::dbg( "Workout::getActivityArchive() - Monday: {$mondayTS}, Sunday: {$sundayTS}, day number today: " . date( 'N' ) );
					E20R_Tracker::dbg( "Workout::getActivityArchive() - User requested archive for 'next week', but we've not yet reached Friday, so not returning anything" );
					
					return null;
				}
				
				break;
			
			case E20R_PREVIOUS_WEEK:
				
				E20R_Tracker::dbg( "Workout::getActivityArchive() - For last week" );
				if ( date( 'N', current_time( 'timestamp' ) ) == 7 ) {
					$mondayTS = strtotime( "monday -2 weeks {$currentDate}" );
					$sundayTS = strtotime( "last sunday {$currentDate}" );
				} else {
					$mondayTS = strtotime( "monday last week {$currentDate}" );
					$sundayTS = strtotime( "last sunday {$currentDate}" );
				}
				
				$period_string = "Activities previous week";
				break;
			
			case E20R_CURRENT_WEEK:
				
				E20R_Tracker::dbg( "Workout::getActivityArchive() - For the current week including: {$currentDate}" );
				
				if ( date( 'N', current_time( 'timestamp' ) ) == 1 ) {
					// It's monday
					
					$mondayTS = strtotime( "monday {$currentDate}" );
					$sundayTS = strtotime( "this sunday {$currentDate}" );
				} else {
					
					$mondayTS = strtotime( "last monday {$currentDate}" );
					$sundayTS = strtotime( "this sunday {$currentDate}" );
				}
				
				$period_string = "Activities this week";
				break;
			
			default:
				return null;
		}

//        $startDelay = ($startDelay + $currentDay);
//        $endDelay = ( $endDelay + $currentDay );
		
		E20R_Tracker::dbg( "Workout::getActivityArchive() - Monday TS: {$mondayTS}, Sunday TS: {$sundayTS}" );
		$startDelay = $Tracker->daysBetween( $startedTS, $mondayTS, get_option( 'timezone_string' ) );
		$endDelay   = $Tracker->daysBetween( $startedTS, $sundayTS, get_option( 'timezone_string' ) );
		
		if ( $startDelay < 0 ) {
			$startDelay = 1;
		}
		
		if ( $endDelay <= 0 ) {
			$endDelay = 6;
		}
		
		
		E20R_Tracker::dbg( "Workout::getActivityArchive() - Delay values -- start: {$startDelay}, end: {$endDelay}" );
		$val = array( $startDelay, $endDelay );
		
		// Load articles in the program that have a release day value between the start/end delay values we calculated.
		$articles = $Article->findArticles( 'release_day', $val, $programId, 'BETWEEN', true );
		
		E20R_Tracker::dbg( "Workout::getActivityArchive() - Found " . count( $articles ) . " articles" );
		// E20R_Tracker::dbg($articles);
		
		$activities = array( 'header' => $period_string );
		$unsorted   = array();
		
		// Pull out all activities for the sequence list
		if ( ! is_array( $articles ) && ( false !== $articles ) ) {
			
			$articles = array( $articles );
		}
		
		foreach ( $articles as $id => $article ) {
			
			// Save activity list as a hash w/weekday => workout )
			E20R_Tracker::dbg( "Workout::getActivityArchive() - Getting " . count( $article->activity_id ) . " activities for article ID: {$article->id}" );
			if ( count( $article->activity_id ) != 0 ) {
				$act = $this->find( 'id', $article->activity_id, $programId, 'IN' );
				
				foreach ( $act as $a ) {
					
					$access = $Tracker->allowedActivityAccess( $a, $userId, $user_role );
					
					if ( true === $access['group'] || true === $access['user'] ) {
						E20R_Tracker::dbg( "Workout::getActivityArchive() - Pushing {$a->id} to array to be sorted" );
						$unsorted[] = $a;
					}
				}
			} else {
				E20R_Tracker::dbg( "Workout::getActivityArchive() - No activities defined for article {$article->id}, moving on." );
			}
		}
		
		E20R_Tracker::dbg( "Workout::getActivityArchive() - Have " . count( $unsorted ) . " workout objects to process/sort" );
		E20R_Tracker::dbg( $unsorted );
		
		// Save activities in an hash keyed on the weekday the activity is scheduled for.
		foreach ( $unsorted as $activity ) {
			
			// $mon = date( 'l', strtotime( 'monday' ) );
			
			foreach ( $activity->groups as $gID => $group ) {
				
				$group->group_tempo       = $this->model->getType( $group->group_tempo );
				$activity->groups[ $gID ] = $group;
			}
			
			foreach ( $activity->days as $dayNo ) {
				
				$dNo = $dayNo;
				$day = date( 'l', strtotime( "monday + " . ( $dNo - 1 ) . " days" ) );
				E20R_Tracker::dbg( "Workout::getActivityArchive() - Saving workout for weekday: {$day} -> ID: {$activity->id}" );
				
				$activities[ $dNo ] = $activity;
			}
		}
		
		
		// Sort based on day id
		ksort( $activities );
		
		// Return the hash of activities to the calling function.
		return $activities;
	}
	
	public function shortcode_activity( $attributes = null ) {
		
		E20R_Tracker::dbg( "Workout::shortcode_activity() - Loading shortcode data for the activity." );
		E20R_Tracker::dbg( $_REQUEST );
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		$config                = new \stdClass();
		$config->show_tracking = true;
		$config->display_type  = 'row';
		$config->print_only    = null;
		
		$tmp = shortcode_atts( array(
			'activity_id'   => null,
			'show_tracking' => true,
			'display_type'  => 'row', // Valid types: 'row', 'column', 'print'
		), $attributes );
		
		// E20R_Tracker::dbg( $tmp );
		
		foreach ( $tmp as $key => $val ) {
			
			if ( ( 'activity_id' == $key ) && ( ! is_null( $val ) ) ) {
				$val = array( $val );
			}
			
			if ( ! is_null( $val ) ) {
				// E20R_Tracker::dbg("Workout::shortcode_activity() - Setting {$key} to {$val}");
				$config->{$key} = $val;
			}
		}
		
		if ( false === in_array( strtolower( $config->show_tracking ), array( 'yes', 'no', 'true', 'false', 1, 0 ) ) ) {
			
			E20R_Tracker::dbg( "Workout::shortcode_activity() - User didn't specify a valid show_tracking value in the shortcode!" );
			
			return sprintf( '<div class="error">%s</div>', __( 'Incorrect show_tracking value in the e20r_activity shortcode! (Valid values are: "yes", "no", "true", "false", "1", "0")', 'e20r-tracker' ) );
		}
		
		
		if ( ! in_array( $config->display_type, array( 'row', 'column', 'print' ) ) ) {
			
			E20R_Tracker::dbg( "Workout::shortcode_activity() - User didn't specify a valid display_type in the shortcode!" );
			
			return sprintf( '<div class="error">%s</div>', __( 'Incorrect display_type value in the e20r_activity shortcode! (Valid values are "row", "column", "print")', 'e20r-tracker' ) );
		}
		
		$config->show_tracking = in_array( strtolower( $config->show_tracking ), array( 'yes', 'true', 1 ) );
		
		if ( 'print' === $config->display_type ) {
			
			$config->print_only = true;
		}
		
		E20R_Tracker::dbg( "Value of show_tracking is: {$config->show_tracking} -> " . ( $config->show_tracking ? 'true' : 'false' ) );
		
		return $this->prepare_activity( $config );
	}
	
	/**
	 * @param $config
	 *
	 * @return string
	 */
	public function prepare_activity( $config ) {
		
		global $current_user;
		
		$Program = Program::getInstance();
		$Article = Article::getInstance();
		$Tracker = Tracker::getInstance();
		
		global $currentProgram;
		global $currentArticle;
		
		$config->userId            = ( ! isset( $config->userId ) ? $current_user->ID : $config->userId );
		$config->programId         = ( ! isset( $currentProgram->id ) ? $Program->getProgramIdForUser( $config->userId ) : $currentProgram->id );
		$config->startTS           = strtotime( $currentProgram->startdate );
		$config->userGroup         = $Tracker->getGroupIdForUser( $config->userId );
		$config->expanded          = false;
		$config->activity_override = false;
		$config->dayNo             = date_i18n( 'N', current_time( 'timestamp' ) );
		
		if ( ! isset( $config->show_tracking ) ) {
			$config->show_tracking = true;
		}
		
		$workoutData = array();
		
		// $config->hide_input = ( $tmp['hide_input'] == 0 ? false : true );
		
		E20R_Tracker::dbg( $config );
		E20R_Tracker::dbg( $_POST );
		
		$actId_from_dash = isset( $_REQUEST['activity-id'] ) ? $Tracker->sanitize( $_REQUEST['activity-id'] ) : array();
		
		if ( ! is_array( $actId_from_dash ) ) {
			$actId_from_dash = array( $actId_from_dash );
		}
		
		$act_override = isset( $_REQUEST['activity-override'] ) ? $Tracker->sanitize( $_REQUEST['activity-override'] ) : false;
		
		// Make sure we won't load anything but the short code requested activity
		if ( empty( $config->activity_id ) ) {
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - No user specified activity ID in short code config" );
			// E20R_Tracker::dbg($_POST);
			
			// Check whether we go called via the dashboard and an activity Id is given to us from there.
			if ( ( empty( $config->activity_id ) && ! empty( $actId_from_dash ) ) ||
			     ( false !== $act_override && ! empty( $actId_from_dash ) ) ) {
				
				$articleId    = isset( $_REQUEST['article-id'] ) ? $Tracker->sanitize( $_REQUEST['article-id'] ) : null;
				$checkin_date = isset( $_REQUEST['for-date'] ) ? $Tracker->sanitize( $_REQUEST['for-date'] ) : null;
				
				E20R_Tracker::dbg( "Workout::prepare_activity() - Original activity ID is: " . ( isset( $config->activity_id ) ? $config->activity_id : 'Not defined' ) );
				E20R_Tracker::dbg( "Workout::prepare_activity() - Dashboard requested " . count( $actId_from_dash ) . " specific activity ID(s)" );
				
				$config->activity_override = true;
				$config->activity_id       = $actId_from_dash;
				
				if ( ! isset( $currentArticle->id ) || ( $currentArticle->id != $articleId ) ) {
					
					E20R_Tracker::dbg( "Workout::prepare_activity() - Loading article with id {$articleId}" );
					$Article->init( $articleId );
				}
				
				$config->date  = $checkin_date;
				$config->delay = $Tracker->getDelay( $config->date, $config->userId );
				$config->dayNo = date_i18n( 'N', strtotime( $config->date ) );
				
				E20R_Tracker::dbg( "Workout::prepare_activity() - Overridden configuration: " );
				E20R_Tracker::dbg( $config );
			}
		}
		
		if ( ! isset( $config->delay ) || empty( $config->delay ) ) {
			$config->delay = $Tracker->getDelay( 'now' );
		}
		
		if ( ! isset( $config->date ) || empty( $config->date ) ) {
			$config->date = $Tracker->getDateForPost( $config->delay );
		}
		
		// E20R_Tracker::dbg( $config );
		
		E20R_Tracker::dbg( "Workout::prepare_activity() - Using delay: {$config->delay} which gives date: {$config->date} for program {$config->programId}" );
		// E20R_Tracker::dbg( $config->activity_id );
		// If the activity ID is set, don't worry about anything but loading that activity (assuming it's permitted).
		
		if ( ! empty( $config->activity_id ) ) {
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - Admin specified activity ID. Using array of activity ids with " . count( $config->activity_id ) . " included activities" );
			$articles = $Article->findArticles( 'activity_id', $config->activity_id, $config->programId, 'IN', true );
			
		} else {
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - Attempting to locate article by configured delay value: {$config->delay}" );
			$articles = $Article->findArticles( 'release_day', $config->delay, $config->programId );
		}
		
		// E20R_Tracker::dbg("Workout::prepare_activity() - (Hopefully located) article: ");
		// E20R_Tracker::dbg($article);
		
		
		if ( ! is_array( $articles ) ) {
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - No articles found!" );
			$articles = array( $Article->emptyArticle() );
		}
		
		$ignore_delay_value = apply_filters( 'e20r-tracker-activity-override-delay', CONST_NULL_ARTICLE );
		
		// Process all articles we've found.
		foreach ( $articles as $a_key => $article ) {
			
			if ( intval( $article->release_day ) === intval( $ignore_delay_value ) ) {
				$config->activity_override = true;
			}
			
			if ( false === $config->activity_override && $config->delay != $article->release_day ) {
				E20R_Tracker::dbg( "Workout::prepare_activity() - Skipping {$article->id} because its delay value is incorrect: {$config->delay} vs {$article->release_day}" );
				continue;
			}
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - Processing article ID {$article->id}" );
			
			// if ( isset( $article->activity_id ) && ( !empty( $article->activity_id) ) ) {
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - Activity count for article: " . ( isset( $article->activity_id ) ? count( $article->activity_id ) : 0 ) );
			
			$workoutData = $this->model->find( 'id', $article->activity_id, $config->programId, 'IN' );
			
			foreach ( $workoutData as $k => $workout ) {
				
				E20R_Tracker::dbg( "Workout::prepare_activity() - Iterating through the fetched workout IDs. Now processing workoutData entry {$k}" );
				// E20R_Tracker::dbg($workout);
				
				if ( ! in_array( $config->programId, $workoutData[ $k ]->program_ids ) ) {
					
					E20R_Tracker::dbg( "Workout::prepare_activity() - The workout is not part of the same program as the user - {$config->programId}: " );
					unset( $workoutData[ $k ] );
				}
				
				if ( isset( $config->dayNo ) && ! in_array( $config->dayNo, $workout->days ) ) {
					
					E20R_Tracker::dbg( "Workout::prepare_activity() - The specified day number ({$config->dayNo}) is not one where {$workout->id} is scheduled to be used. Today is: " . date( 'N' ) );
					unset( $workoutData[ $k ] );
					unset( $articles[ $a_key ] );
				}
				
				if ( ! empty( $workoutData[ $k ]->assigned_user_id ) || ! empty( $workoutData[ $k ]->assigned_usergroups ) ) {
					
					E20R_Tracker::dbg( "Workout::prepare_activity() - User Group or user list defined for this workout..." );
					$has_access = $Tracker->allowedActivityAccess( $workoutData[ $k ], $config->userId, $config->userGroup );
					
					if ( ! in_array( true, $has_access ) ) {
						
						E20R_Tracker::dbg( "Workout::prepare_activity() - current user is NOT listed as a member of this activity: {$config->userId}" );
						E20R_Tracker::dbg( "Workout::prepare_activity() - The activity is not part of the same group(s) as the user: {$config->userGroup}: " );
						
						unset( $workoutData[ $k ] );
						unset( $articles[ $a_key ] );
					}
				}
			}
		}
		
		$config->articleId = isset( $currentArticle->id ) ? $currentArticle->id : null;
		
		E20R_Tracker::dbg( "Workout::prepare_activity() - WorkoutData prior to processing" );
		
		foreach ( $workoutData as $k => $w ) {
			
			E20R_Tracker::dbg( "Workout::prepare_activity() - Processing workoutData entry {$k} to test whether to load user data" );
			
			if ( $k !== 'error' ) {
				
				E20R_Tracker::dbg( "Workout::prepare_activity() - Attempting to load user specific workout data for workoutData entry {$k}." );
				$saved_data = $this->model->getRecordedActivity( $config, $w->id );
				
				if ( ( false == $config->activity_override ) && isset( $w->days ) && ( ! empty( $w->days ) ) && ( ! in_array( $config->dayNo, $w->days ) ) ) {
					
					E20R_Tracker::dbg( "Workout::prepare_activity() - day {$config->dayNo} on day {$config->delay} is wrong for this specific workout/activity #{$w->id}" );
					E20R_Tracker::dbg( $w->days );
					E20R_Tracker::dbg( "Workout::prepare_activity() - Removing workout ID #{$w->id} as a result" );
					unset( $workoutData[ $k ] );
				} else {
					
					foreach ( $w->groups as $gid => $g ) {
						
						if ( ! empty( $saved_data ) ) {
							
							E20R_Tracker::dbg( "Workout::prepare_activity() - Integrating saved data for group # {$gid}" );
							$workoutData[ $k ]->groups[ $gid ]->saved_exercises = isset( $saved_data[ $gid ]->saved_exercises ) ? $saved_data[ $gid ]->saved_exercises : array();
						}
						
						
						if ( isset( $g->group_tempo ) ) {
							E20R_Tracker::dbg( "Workout::prepare_activity() - Setting the tempo identifier" );
							$workoutData[ $k ]->groups[ $gid ]->group_tempo = $this->model->getType( $g->group_tempo );
						}
					}
				}
			}
		}
		
		if ( empty( $workoutData ) ) {
			$workoutData['error'] = 'No Activity found';
		}
		
		ob_start(); ?>
        <div id="e20r-daily-activity-page">
			<?php echo $this->view->display_printable_activity( $config, $workoutData ); ?>
        </div> <?php
		
		$html = ob_get_clean();
		
		return $html;
	}
	
	public function getMemberGroups() {
		$memberGroups = apply_filters( 'e20r-tracker-configured-roles', array() );
		
		return $memberGroups;
	}
	
	public function workout_attributes_dropdown_pages_args( $args, $post ) {
		
		if ( Workout_Model::post_type == $post->post_type ) {
			E20R_Tracker::dbg( 'Workout::changeSetParentType()...' );
			$args['post_type'] = Workout_Model::post_type;
		}
		
		return $args;
	}
	
	public function add_new_exercise_to_group_callback() {
		
		E20R_Tracker::dbg( "Workout::add_new_exercise_to_group_callback() - add_to_group data" );
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-workout-settings-nonce' );
		
		$Tracker  = Tracker::getInstance();
		$Exercise = Exercise::getInstance();
		
		E20R_Tracker::dbg( "Workout::add_new_exercise_to_group_callback() - Received POST data:" );
		E20R_Tracker::dbg( $_POST );
		
		$exerciseId = isset( $_POST['e20r-exercise-id'] ) ? $Tracker->sanitize( $_POST['e20r-exercise-id'] ) : null;
		
		if ( $exerciseId ) {
			
			$exerciseData = $Exercise->getExerciseSettings( $exerciseId );
			
			// Replace the $type variable before sending to frontend (make it comprehensible).
			$exerciseData->type = $Exercise->getExerciseType( $exerciseData->type );
			
			E20R_Tracker::dbg( "Workout::add_new_exercise_to_group_callback() - loaded Workout info: " );
			
			wp_send_json_success( $exerciseData );
			exit();
		}
		
		wp_send_json_error( __( "Unknown error processing new exercise request.", "e20r-tracker" ) );
		exit();
	}
	
	public function add_new_exercise_group_callback() {
		
		E20R_Tracker::dbg( "Workout::add_new_exercise_group_callback() - addGroup data" );
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-workout-settings-nonce' );
		
		$Tracker = Tracker::getInstance();
		
		E20R_Tracker::dbg( "Workout::add_new_exercise_group_callback() - Received POST data:" );
		E20R_Tracker::dbg( $_POST );
		
		$groupId = isset( $_POST['e20r-workout-group-id'] ) ? $Tracker->sanitize( $_POST['e20r-workout-group-id'] ) : null;
		
		if ( ! $groupId ) {
			wp_send_json_error( __( 'Unable to add more groups. Please contact support!', 'e20r-tracker' ) );
			exit();
		}
		
		E20R_Tracker::dbg( "Workout::add_new_exercise_group_callback() - Adding clean/default workout settings for new group. ID={$groupId}." );
		
		$workout = $this->model->defaultSettings();
		$data    = $this->view->newExerciseGroup( $workout->groups[0], $groupId );
		
		if ( $data ) {
			
			E20R_Tracker::dbg( "Workout::add_new_exercise_group_callback() - New group table completed. Sending..." );
			wp_send_json_success( array( 'html' => $data ) );
			exit();
		} else {
			
			E20R_Tracker::dbg( "Workout::add_new_exercise_group_callback() - No data (not even the default values!) generated." );
			wp_send_json_error( __( "Error: Unable to generate new group", "e20r-tracker" ) );
			exit();
		}
	}
} 