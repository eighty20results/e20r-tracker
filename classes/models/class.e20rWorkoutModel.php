<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rWorkoutModel extends e20rSettingsModel {

    private $meta;

	public function e20rWorkoutModel() {

		parent::__construct( 'workout', 'e20r_workout' );
	}

    public function defaultSettings() {

        $group = new stdClass();
        $group->group_sets = null;
        $group->group_tempo = null;
        $group->exercises = array(
            'exercise_id' => null,
            'exercise_type' => null, /* 0 = Reps, 1 = Time, 2 = AMRAP */
            'exercise_reps' => null, /* Could be time or # of reps*/
            'exercise_rest' => null
        );

        $workout = new stdClass();
        $workout->sets = null;
        $workout->set_rest = null;
        $workout->workout_id = 'A';
        $workout->phase = null;
        $workout->user_id = null;
        $workout->group_id = null;
        $workout->startdate = date( 'Y-m-d', current_time( 'timestamp' ) );
        $workout->enddate = null;

        $workout->groups = array();
        $workout->groups[] = $group;

        return $workout;
    }

	public function init( $workoutId = null ) {

		if ( $workoutId === null ) {

			global $post;

			if ( isset( $post->post_type) && ( $post->post_type == 'e20r_workouts' ) ) {

				$workoutId = $post->ID;
			}
		}

		$this->meta = $this->loadSettings( $workoutId );
	}


	/**
     * Returns an array of all programs merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return program data for.
     * @return mixed - Array of program objects
     */
    public function loadAllData( $statuses = 'any' ) {

        $query = array(
            'post_type' => 'e20r_workouts',
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

	    if ( $id === null ) {

		    dbg( "e20rWorkoutModel::loadWorkoutData() - Warning: Unable to load workout data. No ID specified!" );

		    return $this->meta;
	    } else {

		    $query = array(
			    'post_type'   => 'e20r_workouts',
			    'post_status' => $statuses,
			    'p'           => $id,
		    );

		    wp_reset_query();

		    /* Fetch Workouts */
		    $workout_list = new WP_Query( $query );

		    if ( empty( $workout_list ) ) {
			    dbg( "e20rWorkoutModel::loadWorkoutData() - No workouts found!" );

			    return $this->meta;
		    }

		    while ( $workout_list->have_posts() ) {

			    $workout_list->the_post();

			    $new = $this->loadSettings( get_the_ID() );

			    $new->id         = get_the_ID();
			    $new->item_text  = $query->post->post_excerpt;
			    $new->short_name = $query->post->post_title;

			    $workouts[] = $new;
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

	    // TODO: Handle workout groupings
	    // $new_groups = $this->processGroups( $settings->groups, $this->defaultSettings() );

	    $defaults = $this->defaultSettings();

	    dbg("e20rWorkoutModel::saveSettings() - Saving assignment Metadata: " . print_r( $settings, true ) );

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
    public function loadSettings( $id ) {

        $defaults = $this->defaultSettings();

        if ( false === ( $this->meta = get_post_meta( $id, 'e20r-workout-settings', true ) ) ) {

            dbg("e20rWorkoutModel::loadSettings() - No settings found for workout with ID: {$id}");
            $this->meta = $defaults;
        }

        dbg("Workout Meta: " . print_r( $this->meta, true ) );

        $new_groups = $this->processGroups( $this->meta->groups, $defaults );

        $this->meta = (object) array_replace( (array)$defaults, (array)$this->meta );

        $this->meta->groups = $new_groups;

        return $this->meta;
    }

    private function processGroups( $groups, $defaults ) {

        foreach ( $groups as $key => $group ) {

            // dbg("Group settings: " .print_r($group, true) );
            $group = (object) array_replace( (array)$defaults->groups[0], (array)$groups[$key] );
            $groups[$key] = $group;
        }

        return $groups;
    }

}