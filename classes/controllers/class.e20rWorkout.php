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

	    parent::__construct( 'workout', 'e20r_workouts', $this->model, $this->view );
    }


    public function init( $id = null ) {

	    global $currentWorkout;

	    if ( empty($currentWorkout) || ( isset( $currentWorkout->id) && ($currentWorkout->id != $id ) ) ) {

		    $currentWorkout = parent::init( $id );
		    $this->model->init( $currentWorkout->id );

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

    public function saveSettings( $post_id ) {

        global $post, $e20rTracker;

        if ( ( !isset($post->post_type) ) || ( $post->post_type != 'e20r_workouts' ) ) {
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

        dbg("e20rWorkout::saveSettings()  - Saving workout to database");
        $this->init( $post_id );

        $settings = $this->model->defaultSettings();

        foreach( $settings as $key => $value ) {

            $settings->{$key} = isset( $_POST["e20r-workout-{$key}"] ) ? $e20rTracker->sanitize( $_POST["e20r-workout-{$key}"] ) : null;
        }

        dbg("e20rWorkout::saveSettings()  - Looped through and saving form input: " . print_r( $settings, true ) );
        $settings->id = $post_id;

        $this->model->saveSettings( $settings );
    }

	/**
	 * Save the Workout Settings to the metadata table.
	 *
	 * @param $settings - Array of settings for the specific article.
	 *
	 * @return bool - True if successful at updating article settings
	 */
/*	public function saveSettings( stdClass $settings ) {

		$articleId = $settings->id;

		$defaults = self::defaultSettings();

		dbg("e20rWorkoutModel::saveSettings() - Saving workout Metadata: " . print_r( $settings, true ) );

		$error = false;

		foreach ( $defaults as $key => $value ) {

			if ( in_array( $key, array( 'id' ) ) ) {
				continue;
			}

			if ( $key == 'post_id' ) {

				dbg("e20rWorkoutModel::saveSettings() - Saving the workout ID with the post ");
				update_post_meta( $settings->{$key}, '_e20r-article-id', $articleId );
			}

			if ( false === $this->settings( $articleId, 'update', $key, $settings->{$key} ) ) {

				dbg( "e20rWorkoutModel::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for workout definition with ID: {$articleId}" );

				$error = true;
			}
		}

		return ( !$error ) ;
	}
*/
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

        dbg("e20rWorkout::addMeta_WorkoutSettings() - Loading settings metabox for workout page");
        $this->init( $post->ID );

        $workout = $this->model->find( 'id', $post->ID );

	    if ( !empty( $workout ) ) {
		    echo $this->view->viewSettingsBox( $workout );
	    }
	    else {
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

        if ( 'e20r_workouts' == $post->post_type ) {
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

        $workoutId = isset( $_POST['post_ID']) ? $e20rTracker->sanitize( $_POST['post_ID']) : null;

	    if ( ! $workoutId ) {
		    wp_send_json_error( 'Unable to save data. Please contact support!');
	    }

        $this->init( $workoutId );

        dbg("e20rWorkout::add_new_exercise_group_callback() - Requested to add another Group to workout with ID {$workoutId}.");

        if ( ( $workout = $this->model->loadWorkoutData( $workoutId ) ) ) {

            dbg("e20rWorkout::add_new_exercise_group_callback() - Adding default group settings to new group");
            $workout->groups[] = $this->model->defaultSettings();
            $groupNo = count( $workout->groups );

            if ( $this->model->saveWorkout( $workout ) )  {
                dbg("e20rWorkout::add_new_exercise_group_callback() - Saved the workout. - TODO!");

            }

            $data = $this->view->newExerciseGroup( $workout->groups[$groupNo], $groupNo );

            dbg("e20rWorkout::add_new_exercise_group_callback() - Table row generation completed. Sending...");
            wp_send_json_success( $data );
        }

        dbg("e20rWorkout::add_new_exercise_group_callback() - No data (not even the default values!) generated.");
        wp_send_json_error( "Error: Unable to generate new group");
    }
} 