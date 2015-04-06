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

	protected $table;
	protected $fields;

    public function e20rWorkout() {

        dbg("e20rWorkout::__construct() - Initializing Workout class");

	    $this->model = new e20rWorkoutModel();
	    $this->view = new e20rWorkoutView();

	    parent::__construct( 'workout', 'e20r_workout', $this->model, $this->view );
    }


    public function init( $id = null ) {

	    global $currentWorkout;
	    global $e20rTables;

	    $this->table = $e20rTables->getTable('workout');
	    $this->fields = $e20rTables->getFields( 'workout' );

	    if ( empty($currentWorkout) || ( isset( $currentWorkout->id ) && ($currentWorkout->id != $id ) ) ) {
		    // dbg("e20rWorkout::init() - currentWorkout->id: {$currentWorkout->id} vs id: {$id}" );

		    // $currentWorkout = parent::init( $id );
		    $this->model->init( $id );

		    dbg("e20rWorkout::init() - Loaded settings for {$id}:");
		    dbg($currentWorkout);
	    }
    }

	public function getActivity( $identifier ) {

		if ( !isset( $this->model ) ) {
			$this->init();
		}

		$workout = array();

		if ( is_numeric( $identifier ) ) {
			// Given an ID
			$workout = $this->model->loadWorkoutData( $identifier, 'any' );
		}

		if (is_string( $identifier ) ) {
			// Given a short_name
			$workout[] = $this->getWorkout( $identifier );
		}

		return $workout;

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

	public function saveExData_callback() {

		global $current_user;
		global $e20rTracker;

		check_ajax_referer('e20r-tracker-activity', 'e20r-tracker-activity-input-nonce');

		if ( ! $e20rTracker->userCanEdit( $current_user->ID ) ) {

			wp_send_json_error( 'Incorrect privileges for this application');
		}

		dbg("e20rWorkout::saveExData_callback() - Has the right privs to save data: ");
		dbg($_POST);


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

	        dbg( "e20rWorkout::saveSettings() - Not a e20r_workout CPT: " );
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

	    $workout->programs = isset( $_POST['e20r-workout-programs'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-programs'] ) : array( 0 ) ;
	    $workout->days = isset( $_POST['e20r-workout-days'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-days'] ) : array();
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

    public function getActivities( $aIds  = null) {

	    if ( empty( $aIds ) ) {
		    dbg('e20rWorkout::getActivities() - Loading all activities from DB');
		    $activities = $this->model->find( 'id', 'any' ); // Will return all of the defined activities
	    }
	    else {
		    dbg('e20rWorkout::getActivities() - Loading specific activity from DB');
		    $activities = $this->model->find( 'id', $aIds );
	    }

        dbg("e20rWorkout::getActivities() - Found " . count($activities) . " activities.");

        return $activities;
    }

	public function shortcode_activity( $attributes = null ) {

		dbg("e20rWorkout::shortcode_activity() - Loading shortcode data for the activity.");

		if ( ! is_user_logged_in() ) {

			auth_redirect();
		}

		global $e20rArticle;
		global $e20rProgram;
		global $e20rTracker;

		global $current_user;
		global $currentArticle;
		global $post;

		$config = new stdClass();
		$workoutData = array();

		$tmp = shortcode_atts( array(
			'type' => 'action',
			'form_id' => null,
		), $attributes );

		foreach ( $tmp as $key => $val ) {

			$config->{$key} = $val;
		}

		dbg( $config );

		$config->userId = $current_user->ID;
		$config->programId = $e20rProgram->getProgramIdForUser( $config->userId );
		$config->startTS = $e20rProgram->startdate( $config->userId );
		$config->delay = $e20rTracker->getDelay( 'now' );
		$config->date = $e20rTracker->getDateForPost( $config->delay );
		$config->userGroup = $e20rTracker->getGroupIdForUser( $config->userId );

		$config->dayNo = date_i18n( 'N', current_time('timestamp') );

		dbg("e20rWorkout::shortcode_activity() - Using delay: {$config->delay} which gives date: {$config->date} for program {$config->programId}");

		dbg("e20rWorkout::shortcode_activity() - Looking up article by delay value of {$config->delay} days");
		$article = $e20rArticle->findArticle( 'release_day', $config->delay );

		if ( isset( $article->id ) ) {

			if ( isset( $article->activity_id ) ) {

				dbg( "e20rWorkout::shortcode_activity() - Activity is defined for article {$article->id} with activity ID {$article->activity_id}" );
				$workoutData = $this->model->find( 'id', $article->activity_id );
			}
		}
		else {

			dbg( "e20rWorkout::shortcode_activity() - No Activity defined for article, searching by date of {$config->date}, aka delay {$config->delay}" );
			$workoutIds = $this->model->findByDate( $config->date, $config->programId );

			if ( ! empty( $workoutIds ) ) {

				foreach ( $workoutIds as $wid ) {

					dbg("e20rWorkout::shortcode_activity() - Loading activity data for {$wid}:");
					$workoutData = $this->model->loadWorkoutData( $wid );

					if ( ! in_array( $config->programId, $workoutData[$wid]->programs ) ) {

						dbg( "e20rWorkout::shortcode_activity() - The workout is not part of the same program as the user - {$config->programId}: " );
						unset( $workoutData[ $wid ] );
					}

					if ( ! empty( $workoutData[$wid]->assigned_user_id ) || ! empty( $workoutData[$wid]->assigned_usergroups ) ) {

						dbg( "e20rWorkout::shortcode_activity() - User Group or user list defined for this workout..." );

						if ( ! ( in_array( $config->userId, $workoutData[$wid]->assigned_user_id ) ||
						         in_array( $config->userGroup, $workoutData[$wid]->assigned_usergroups ) )
						) {

							dbg( "e20rWorkout::shortcode_activity() - current user is NOT listed as a member of this activity: {$config->userId}" );
							dbg( "e20rWorkout::shortcode_activity() - The activity is not part of the same group(s) as the user - {$config->userGroup}: " );

							unset( $workoutData[ $wid ] );
						}
					}
				}
			}
		}

		$recorded = array();

		dbg( "e20rWorkout::shortcode_activity() - WorkoutData prior to processing");
		dbg($workoutData);

		foreach ( $workoutData as $wid => $w ) {

			if ( $wid !== 'error' ) {

				$saved_data = $this->model->getRecordedActivity( $config, $wid );

				if ( ( ! empty( $w->days ) ) && ( ! in_array( $config->dayNo, $w->days ) ) ) {

					dbg( "e20rWorkout::shortcode_activity() - day {$config->dayNo} is wrong for this specific workout/activity" );
					dbg( $w->days );
					unset( $workoutData[ $wid ] );
				}
				else {

					foreach ( $w->groups as $gid => $g ) {

						if ( !empty( $saved_data ) ) {

							dbg("e20rWorkout::shortcode_activity() - Integrating saved data for group # {$gid}");
							$workoutData[ $wid ]->groups[ $gid ]->saved_exercises = $saved_data[$gid]->saved_exercises;
						}


						if ( isset( $g->group_tempo ) ) {

							$workoutData[ $wid ]->groups[ $gid ]->group_tempo = $this->model->getType( $g->group_tempo );
						}
					}
				}
			}
		}

		if ( empty( $workoutData ) ) {
			$workoutData['error'] = 'No Activity found';
		}


		ob_start();
		?>
		<div id="e20r-daily-activity-page">
			<?php echo $this->view->displayActivity( $config, $workoutData ); ?>
		</div>
		<?php
		$html = ob_get_clean();

		echo $html;
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