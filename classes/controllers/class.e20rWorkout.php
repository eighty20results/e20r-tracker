<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rWorkout extends e20rSettings {

    private $workout = array();
    public $model = null;
    public $view = null;

    public function e20rWorkout() {

        dbg("e20rWorkout::__construct() - Initializing Workout class");

	    $this->model = new e20rWorkoutModel();
	    $this->view = new e20rWorkoutView();

	    parent::__construct( 'workout', 'e20r_workout', $this->model, $this->view );
    }


    public function init( $id = null ) {

	    global $currentWorkout;

	    if ( empty($currentWorkout) || ( isset( $currentWorkout->id ) && ($currentWorkout->id != $id ) ) ) {
		    // dbg("e20rWorkout::init() - currentWorkout->id: {$currentWorkout->id} vs id: {$id}" );

		    // $currentWorkout = parent::init( $id );
		    $this->model->init( $id );

		    dbg("e20rWorkout::init() - Loaded settings for {$id}:");
		    dbg($currentWorkout);
	    }
    }

    public function getWorkout( $shortName ) {

        if ( ! isset( $this->model ) ) {
            $this->init();
        }

        $pgmList = $this->model->loadAllWorkouts( 'any' );

        foreach ($pgmList as $pgm ) {
            if ( $pgm->shortname == $shortName ) {
                unset($pgmList);
                return $pgm;
            }
        }

        unset($pgmList);
        return false; // Returns false if the program isn't found.
    }

    public function editor_metabox_setup( $post ) {

        add_meta_box('e20r-tracker-workout-settings', __('Workout Settings', 'e20rtracker'), array( &$this, "addMeta_WorkoutSettings" ), 'e20r_workout', 'normal', 'core');

    }

	public function listUserActivities( $userId ) {

		return false;
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
	    global $current_user;
	    global $e20rTracker;

        if ( ( !isset($post->post_type) ) || ( $post->post_type != 'e20r_workout' ) ) {

	        dbg( "e20rWorkout::saveSettings() - Not a e20r_workout CPT: " . $post->post_type );
            return $post_id;
        }

        if ( empty( $post_id ) ) {

            dbg("e20rWorkout::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {

            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {

            return $post_id;
        }

	    dbg("e20rWorkout::saveSettings()  - Saving workout to database.");

	    $groups = array();
	    $workout = $this->model->loadSettings( $post_id );

	    $groupData = isset( $_POST['e20r-workout-group'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group']) : array();
	    $exData = isset( $_POST['e20r-workout-group_exercise_id'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group_exercise_id'] ) : array();
	    $orderData = isset( $_POST['e20r-workout-group_exercise_order'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group_exercise_order'] ) : array();
	    $groupSetData = isset( $_POST['e20r-workout-group_set_count'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group_set_count'] ) : array();
	    $tempoData = isset( $_POST['e20r-workout-groups-group_tempo'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-groups-group_tempo'] ) : array();
		$restData  = isset( $_POST['e20r-workout-groups-group_rest'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-groups-group_rest'] ) : array();

	    $workout->workout_ident = isset( $_POST['e20r-workout-workout_ident'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-workout_ident'] ) : 'A';
	    $workout->phase = isset( $_POST['e20r-workout-phase'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-phase'] ) : 1;
	    $workout->assigned_user_id = isset( $_POST['e20r-workout-assigned_user_id'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-assigned_user_id'] ) : array( -1 ); // Default is "everybody"
	    $workout->assigned_usergroups = isset( $_POST['e20r-workout-assigned_usergroups'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-assigned_usergroups'] ) : array( -1 ) ;
	    $workout->startdate = isset( $_POST['e20r-workout-startdate'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-startdate'] ) : date( 'Y-m-d', current_time( 'timestamp' ) );
	    $workout->enddate = isset( $_POST['e20r-workout-enddate'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-enddate'] ) : null;

	    $test = (array)$exData;

	    if ( !empty( $test ) ) {

		    foreach ($groupData as $key => $groupNo ) {

			    if ( ( $workout->groups[$groupNo]->exercises[0] == 0 ) && ( ! isset( $groups[ $groupNo ]->exercises ) ) ) {

				    dbg("e20rWorkout::saveSettings() - Creating and adding group data");
				    $groups[ $groupNo ] = new stdClass();
				    $groups[ $groupNo ]->exercises = array();
				    $groups[ $groupNo ]->group_set_count = $groupSetData[ $groupNo ];
				    $groups[ $groupNo ]->group_tempo = $tempoData[ $groupNo ];
				    $groups[ $groupNo ]->group_rest = $restData[ $groupNo ];
			    }

			    dbg("e20rWorkout::saveSettings() - Adding Exercise group data");
			    $groups[ $groupNo ]->exercises[ $orderData[ $key ] ] = $exData[ $key ];
		    }
		    dbg("e20rWorkout::saveSettings() - Groups:");
		    dbg($groups);
	    }

	    // Add workout group data/settings
	    $workout->groups = $groups;

	    dbg('e20rWorkout::saveSettings() - Workout data to save:');
	    dbg($workout);

	    if ( $this->model->saveSettings( $workout ) ) {

		    dbg('e20rWorkout::saveSettings() - Saved settings/metadata for this e20r_workout CPT');
		    return $post_id;
	    }
	    else {
		    dbg('e20rWorkout::saveSettings() - Error saving settings/metadata for this e20r_workout CPT');
		    return false;
	    }


    }

    public function getPeers( $workoutId = null ) {

        if ( is_null( $workoutId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $workoutId = $post->post_parent;
        }

        $workouts = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $workoutId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $workoutList = array(
            'pages' => $workouts->posts,
        );

        foreach ( $workoutList->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $workouts->posts[$k-1] ) ) {

                    $workoutList['prev'] = $workouts->posts[ $k - 1 ];
                }

                if( isset( $workouts->posts[$k+1] ) ) {

                    $workoutList['next'] = $workouts->posts[ $k + 1 ];
                }
            }
        }

        return $workoutList;
    }

    public function addMeta_WorkoutSettings() {

        global $post;
	    global $currentWorkout;

        dbg("e20rWorkout::addMeta_WorkoutSettings() - Loading settings metabox for workout page: " . $post->ID );
        $this->init( $post->ID );

	    // $currentWorkout = $this->model->find( 'id', $post->ID );

	    if ( !empty( $currentWorkout ) ) {

		    dbg("e20rWorkout::addMeta_WorkoutSettings() - Loaded a workout with settings...");
		    echo $this->view->viewSettingsBox( $currentWorkout );
	    }
	    else {
		    dbg("e20rWorkout::addMeta_WorkoutSettings() - Loaded an empty/defaul workout definition...");
		    echo $this->view->viewSettingsBox( $this->model->defaultSettings() );
	    }

    }

    public function getMemberGroups() {

        $membersGroups = array();

        // For Paid Memberships Pro.
        if ( function_exists( 'pmpro_getAllLevels' ) ) {

            $memberships = pmpro_getAllLevels();

            foreach ( $memberships as $mId => $mInfo ) {
                $memberGroups[$mId] = $mInfo->name;
            }
        }

        return $memberGroups;
    }

    public function workout_attributes_dropdown_pages_args( $args, $post ) {

        if ( 'e20r_workout' == $post->post_type ) {
            dbg('e20rWorkout::changeSetParentType()...');
            $args['post_type'] = 'e20r_workout';
        }

        return $args;
    }

	public function add_new_exercise_to_group_callback() {

		dbg("e20rWorkout::add_new_exercise_to_group_callback() - add_to_group data");

		check_ajax_referer('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce');

		global $e20rTracker;
		global $e20rExercise;

		dbg("e20rWorkout::add_new_exercise_to_group_callback() - Received POST data:");
		dbg($_POST);

		$exerciseId = isset( $_POST['e20r-exercise-id']) ? $e20rTracker->sanitize( $_POST['e20r-exercise-id']) : null;

		if ( $exerciseId ) {

			$exerciseData = $e20rExercise->getExerciseSettings( $exerciseId );

			// Replace the $type variable before sending to frontend (make it comprehensible).
			$exerciseData->type = $e20rExercise->getExerciseType( $exerciseData->type );

			dbg( "e20rWorkout::add_new_exercise_to_group_callback() - loaded Workout info: " );

			wp_send_json_success( $exerciseData );
		}

		wp_send_json_error("Unknown error processing new exercise request.");
	}

    public function add_new_exercise_group_callback() {

        dbg("e20rWorkout::add_new_exercise_group_callback() - addGroup data");

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce');

	    global $e20rTracker;

	    dbg("e20rWorkout::add_new_exercise_group_callback() - Received POST data:");
	    dbg($_POST);

        $groupId = isset( $_POST['e20r-workout-group-id']) ? $e20rTracker->sanitize( $_POST['e20r-workout-group-id']) : null;

	    if ( ! $groupId ) {
		    wp_send_json_error( 'Unable to add more groups. Please contact support!');
	    }

        dbg("e20rWorkout::add_new_exercise_group_callback() - Adding clean/default workout settings for new group. ID={$groupId}.");

	    $workout = $this->model->defaultSettings();
        $data = $this->view->newExerciseGroup( $workout->groups[0], $groupId );

	    if ( $data ) {

		    dbg( "e20rWorkout::add_new_exercise_group_callback() - New group table completed. Sending..." );
		    wp_send_json_success( array( 'html' => $data ) );
	    }
	    else {

		    dbg("e20rWorkout::add_new_exercise_group_callback() - No data (not even the default values!) generated.");
		    wp_send_json_error( "Error: Unable to generate new group");
	    }
    }
} 