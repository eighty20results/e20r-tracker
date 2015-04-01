<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rWorkoutModel extends e20rSettingsModel {

	protected $settings;

	public function e20rWorkoutModel() {

		parent::__construct( 'workout', 'e20r_workout' );
	}

    public function defaultSettings() {

	    $group = new stdClass();
	    $group->group_set_count = null;
	    $group->group_tempo = null;
	    $group->group_rest = null;

	    // Key for the exercises array is the exercise id value (i.e. the post id)
	    $group->exercises = array();
	    $group->exercises[0] = 0;

	    $workout = parent::defaultSettings();
	    $workout->excerpt = '';
	    $workout->title = '';
	    $workout->days = array();
	    $workout->programs = array( 0 );
	    $workout->workout_ident = 'A';
	    $workout->phase = null;
	    $workout->assigned_user_id = array( -1 );
	    $workout->assigned_usergroups = array( -1 );
	    $workout->startdate = date( 'Y-m-d', current_time( 'timestamp' ) );
	    $workout->enddate = null;

	    $workout->groups = array();
	    $workout->groups[0] = $group;

	    return $workout;
    }

	public function init( $workoutId = null ) {

		global $currentWorkout;

		if ( $workoutId === null ) {

			global $post;

			if ( isset( $post->post_type) && ( $post->post_type == 'e20r_workout' ) ) {

				$workoutId = $post->ID;
			}
		}
		dbg("e20rWorkoutModel::init() - Loading workoutData for {$workoutId}");
		$tmp = $this->loadWorkoutData( $workoutId );

		$currentWorkout = $tmp[$workoutId];
	}


	public function loadSettings( $id ) {

		global $post;
		global $currentWorkout;

		if ( ! empty( $currentWorkout ) && ( $currentWorkout->id == $id ) ) {

			return $currentWorkout;
		}

		if ( $id == 0 ) {

			$this->settings              = $this->defaultSettings( $id );
			$this->settings->id          = $id;

		} else {

			$savePost = $post;

			$this->settings = parent::loadSettings( $id );


			$post = get_post( $id );
			setup_postdata( $post );

			if ( ! empty( $post->post_title ) ) {

				$this->settings->excerpt       = $post->post_excerpt;
				$this->settings->title    = $post->post_title;
				$this->settings->id          = $id;
			}

			wp_reset_postdata();
			$post = $savePost;
		}

		$currentWorkout = $this->settings;
		return $this->settings;
	}

	/**
     * Returns an array of all workouts merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return program data for.
     * @return mixed - Array of program objects
     */
    public function loadAllData( $statuses = 'any' ) {

        $query = array(
            'post_type' => 'e20r_workout',
            'post_status' => $statuses,
        );

        wp_reset_query();

        /* Fetch all Sequence posts */
        $workout_list = get_posts( $query );

        if ( empty( $workout_list ) ) {

            return $this->meta;
        }

        dbg("e20rWorkoutModel::loadAllWorkoutData() - Loading program settings for " . count( $workout_list ) . ' settings');

        foreach( $workout_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $workout_list[$key] = $loaded_settings;
        }

        return $workout_list;
    }

    public function loadWorkoutData( $id, $statuses = 'any' ) {

	    global $post;

	    $savePost = $post;
	    $workouts = array();

	    dbg( "e20rWorkoutModel::loadWorkoutData() - Attempting to load workout settings for {$id}" );

	    if ( $id === null ) {

		    dbg( "e20rWorkoutModel::loadWorkoutData() - Warning: Unable to load workout data. No ID specified!" );

		    return $this->settings;
	    } else {

		    $query = array(
			    'post_type'   => $this->cpt_slug,
			    'post_status' => $statuses,
			    'p'           => $id,
		    );

		    /* Fetch Workouts */
		    $query = new WP_Query( $query );

		    if ( $query->post_count <= 0 ) {
			    dbg( "e20rWorkoutModel::loadWorkoutData() - No workout found!" );

			    return $this->settings;
		    }

		    while ( $query->have_posts() ) {

			    $query->the_post();

			    dbg("e20rWorkoutModel::loadWorkoutData() - Received ID: " . get_the_ID() );
			    $new = $this->loadSettings( get_the_ID() );

			    $new->id         = $id;
			    $workouts[$new->id] = $new;
		    }
	    }

	    return $workouts;
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

	    dbg("e20rWorkoutModel::saveSettings() - Saving metadata for new activity: " );
	    dbg($settings);

	    $error = false;

	    foreach ( $defaults as $key => $value ) {

		    if ( in_array( $key, array( 'id' ) ) ) {

			    continue;
		    }

		    if ( false === $this->settings( $workoutId, 'update', $key, $settings->{$key} ) ) {

			    if ( is_array( $settings->{$key} ) || is_object( $settings->{$key} ) ) {

				    dbg( "e20rWorkoutModel::saveSettings() - ERROR saving {$key} setting for workout definition with ID: {$workoutId}: " );
				    dbg( $settings->{$key} );
			    }
			    else {

				    dbg( "e20rWorkoutModel::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for workout definition with ID: {$workoutId}" );
			    }

			    $error = true;
		    }
	    }

	    return ( !$error ) ;
    }

    /**
     * Load the Workout Settings from the metadata table.
     *
     * @param $id (int) - The ID of the program to load settings for
     *
     * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
     */
/*    public function loadSettings( $id ) {

	    parent::loadSettings($id);

    }
*/
	/*
    private function processGroups( $groups, $defaults ) {

        foreach ( $groups as $key => $group ) {

            // dbg("Group settings: " .print_r($group, true) );
            $group = (object) array_replace( (array)$defaults->groups[0], (array)$groups[$key] );
            $groups[$key] = $group;
        }

        return $groups;
    }
	*/

}