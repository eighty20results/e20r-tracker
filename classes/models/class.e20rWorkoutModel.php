<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rWorkoutModel extends e20rSettingsModel {

	protected $settings;
	protected $types;
	protected $table;

	public function e20rWorkoutModel() {

		global $e20rTables;

		parent::__construct( 'workout', 'e20r_workout' );

		$this->table = $e20rTables->getTable('workout');

		$this->types = array(
			0 => '',
			1 => __("Slow", "e20rtracker"),
			2 => __("Normal", "e20rtracker"),
			3 => __("Fast", "e20rtracker")
		);

        $this->settings = new stdClass();
	}

	public function getType( $tId ) {

		return $this->types[$tId];
	}

    public function defaultGroup() {

        $group = new stdClass();
        $group->group_set_count = null;
        $group->group_tempo = null;
        $group->group_rest = null;

        // Key for the exercises array is the exercise id value (i.e. the post id)
        $group->exercises = array();
        $group->exercises[0] = 0;

        return $group;
    }

    public function defaultSettings() {

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
	    $workout->groups[0] = $this->defaultGroup();

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

            dbg("e20rWorkoutModel::loadSettings() - Analyzing group content");

            $ex = array();
            $g_def = $this->defaultGroup();

            foreach( $this->settings->groups as $i => $g ) {

                dbg("e20rWorkoutModel::loadSettings() - Analyzing group #{$i}");

                if ( ! isset( $g->group_set_count ) || is_null( $g->group_set_count ) ) {

                    dbg("e20rWorkoutModel::loadSettings() - Adding default set count info");
                    $g->group_set_count = $g_def->group_set_count;
                }

                if ( ! isset( $g->group_tempo ) || is_null( $g->group_tempo ) ) {

                    dbg("e20rWorkoutModel::loadSettings() - Adding default set tempo");
                    $g->group_tempo = $g_def->group_tempo;
                }

                if ( ! isset( $g->group_rest ) || is_null( $g->group_rest ) ) {

                    dbg("e20rWorkoutModel::loadSettings() - Adding default set rest");
                    $g->group_rest = $g_def->group_rest;
                }

                if ( ! isset( $g->exercises[1] ) || is_null( $g->exercises[1] ) ) {

                    dbg("e20rWorkoutModel::loadSettings() - Adding default exercise array");
                    $g->exercises = $g_def->exercises;
                }

                $this->settings->groups[$i] = $g;
            }

			wp_reset_postdata();
			$post = $savePost;
		}

        // Test whether an exercise group is defined or not.
        if ( ! is_array( $this->settings->groups )  && ( !isset( $this->settings->groups[0]) ) ) {

            $this->settings->groups = array();
            $this->settings->groups[0] = $this->defaultGroup();
        }

		$currentWorkout = $this->settings;
		return $this->settings;
	}

	public function getRecordedActivity( $config, $id ) {

		global $wpdb;
		global $e20rTables;
		global $e20rTracker;

		$fields = $e20rTables->getFields('workout');

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

		// $sql = $e20rTracker->prepare_in( $sql, $group->exercises );

		$sql = $wpdb->prepare( $sql,
			$config->date . '%',
			$config->userId,
			$config->programId,
			$id
		);

		dbg("e20rWorkoutModel::getRecordedActivity() - After prepare() processing: {$sql}");

		$records = $wpdb->get_results( $sql );

		dbg("e20rWorkoutModel::getRecordedActivity() - Fetched " . count($records) . " records from DB");

		$saved = array();

		if ( !empty( $records ) ) {

			foreach ( $records as $r ) {

				if ( ! isset( $saved[ $r->group_no ] ) ) {

					dbg("e20rWorkoutModel::getRecordedActivity() - Adding new saved data..");
					$saved[ $r->group_no ] = new stdClass();
				}

				if ( !isset( $saved[ $r->group_no ]->saved_exercises ) ) {

					dbg("e20rWorkoutModel::getRecordedActivity() - Adding new exercises array.");
					$saved[ $r->group_no ]->saved_exercises = array();
				}

				if ( ! isset( $saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ] ) ) {

					dbg("e20rWorkoutModel::getRecordedActivity() - Adding new class to store set information.");
					$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ] = new stdClass();
				}

				if ( ! isset( $saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set ) ) {

					dbg("e20rWorkoutModel::getRecordedActivity() - Adding new sets array.");
					$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set = array();
				}

				if ( !isset($saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ] ) ) {

					dbg("e20rWorkoutModel::getRecordedActivity() - Adding new data object for set.");
					$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ] = new stdClass();
				}

				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->ex_id    = $r->exercise_id;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->id       = $r->id;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->recorded = $r->recorded;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->weight   = $r->weight;
				$saved[ $r->group_no ]->saved_exercises[ $r->exercise_key ]->set[ $r->set_no ]->reps     = $r->reps;
			}
		}

		dbg("e20rWorkoutModel::getRecordedActivity() - Returning: ");
		dbg($saved);

		return $saved;

	}
/*
	public function save_recordedActivity( $activityObj ) {

		$data = array();

		foreach( $activityObj as $gId => $obj ) {

			foreach( $obj->exercises as $exKey => $sets ) {

				foreach( $sets->set as $sId => $set ) {

					$array[] = array(

						'program_id' => $set
					);
				}
			}
		}
	}
*/
    public function save_userData( $data, $format ) {

        dbg("e20rWorkoutModel::save_userData() - Saving data: ");
        dbg($data);

	    global $wpdb;

	    if ( $wpdb->replace( $this->table, $data, $format ) !== false ) {

		    $id = $wpdb->insert_id;
            dbg("e20rWorkoutModel::save_userData() - Replaced/Inserted ID: {$id}");
            return $id;
	    }

        return false;
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

		    return $this->defaultSettings();
	    }
        else {

		    $query = array(
			    'post_type'   => $this->cpt_slug,
			    'post_status' => $statuses,
			    'p'           => $id,
		    );

		    /* Fetch Workouts */
		    $query = new WP_Query( $query );

		    if ( $query->post_count <= 0 ) {
			    dbg( "e20rWorkoutModel::loadWorkoutData() - No workout found!" );

			    return $this->defaultSettings();
		    }

		    while ( $query->have_posts() ) {

			    $query->the_post();

			    dbg("e20rWorkoutModel::loadWorkoutData() - For Workout id: " . get_the_ID() );
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