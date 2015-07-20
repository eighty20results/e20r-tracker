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
		    dbg("e20rWorkout::init() - received id value: {$id}" );

		    // $currentWorkout = parent::init( $id );
		    $this->model->init( $id );

		    dbg("e20rWorkout::init() - Loaded settings for {$id}:");
		    // dbg($currentWorkout);
	    }
    }

	public function getActivity( $identifier ) {

        dbg("e20rWorkout::getActivity() - Loading Activity data for {$identifier}");

		if ( !isset( $this->model ) ) {
			$this->init();
		}

		$workout = array();

		if ( is_numeric( $identifier ) ) {
			// Given an ID
			$workout = $this->model->loadWorkoutData( $identifier, 'any' );
		}

/*		if (is_string( $identifier ) ) {
			// Given a short_name
			$workout[] = $this->getWorkout( $identifier );
		}
*/
        dbg("e20rWorkout::getActivity() - Returning Activity data for {$identifier}");
		return $workout;

	}

    public function getWorkout( $shortName ) {

        if ( ! isset( $this->model ) ) {
            $this->init();
        }

        $pgmList = $this->model->loadAllData( 'any' );

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

        // global $currentWorkout;

        dbg("e20rWorkout::editor_metabox_setup() - Loading settings for workout page: " . $post->ID );
        $this->init( $post->ID );

        // $currentWorkout = $this->model->find( 'id', $post->ID );

        add_meta_box('e20r-tracker-workout-settings', __('Workout Settings', 'e20rtracker'), array( &$this, "addMeta_WorkoutSettings" ), 'e20r_workout', 'normal', 'core');

    }

	public function listUserActivities( $userId ) {

		return false;
	}

	public function saveExData_callback() {

		global $current_user;
		global $e20rTracker;

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Please log in to access this service');
        }

        check_ajax_referer('e20r-tracker-activity', 'e20r-tracker-activity-input-nonce');

		dbg("e20rWorkout::saveExData_callback() - Has the right privs to save data: ");
		dbg($_POST);

		$data = array();
		$skip = array( 'action', 'e20r-tracker-activity-input-nonce' );

		foreach( $_POST as $k => $v ) {

			if ( $k == 'recorded' ) {

				dbg("e20rWorkout::saveExData_callback() - Saving date/time of record.");
				$v = date_i18n('Y-m-d H:i:s', $e20rTracker->sanitize( $v ) );
			}

			if ( $k == 'for_date' ) {

				dbg("e20rWorkout::saveExData_callback() - Saving date/time for when the record should have been recorded: {$v}.");
				$v = date_i18n('Y-m-d H:i:s', strtotime( $e20rTracker->sanitize( $v ) ) );
			}
            if ( !in_array( $k, $skip ) ) {


                dbg("e20rWorkout::saveExData_callback() - Saving {$k} as {$v} for record.");
                $data[$k] = $e20rTracker->sanitize( $v );
            }
		}

        dbg("e20rWorkout::saveExData_callback() - Data array to use");
        dbg($data);

        $format = $e20rTracker->setFormatForRecord( $data );
        dbg($format);

        if ( ( $id = $this->model->save_userData( $data, $format ) ) === false ) {
            dbg("e20rWorkout::saveExData_callback() - Error saving user data record!");
            wp_send_json_error();
        }
        dbg("e20rWorkout::saveExData_callback() - Saved record with ID: {$id}");
        wp_send_json_success( array( 'id' => $id ) );
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

	    $groupData = isset( $_POST['e20r-workout-group'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group']) : array( $post_id => $this->model->defaultGroup() );
	    $exData = isset( $_POST['e20r-workout-group_exercise_id'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group_exercise_id'] ) : array();
	    $orderData = isset( $_POST['e20r-workout-group_exercise_order'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group_exercise_order'] ) : array();
	    $groupSetCount = isset( $_POST['e20r-workout-group_set_count'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-group_set_count'] ) : array();
	    $groupSetTempo = isset( $_POST['e20r-workout-groups-group_tempo'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-groups-group_tempo'] ) : array();
		$groupSetRest  = isset( $_POST['e20r-workout-groups-group_rest'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-groups-group_rest'] ) : array();

	    $workout->programs = isset( $_POST['e20r-workout-programs'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-programs'] ) : array( 0 ) ;
	    $workout->days = isset( $_POST['e20r-workout-days'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-days'] ) : array();
	    $workout->workout_ident = isset( $_POST['e20r-workout-workout_ident'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-workout_ident'] ) : 'A';
	    $workout->phase = isset( $_POST['e20r-workout-phase'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-phase'] ) : 1;
	    $workout->assigned_user_id = isset( $_POST['e20r-workout-assigned_user_id'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-assigned_user_id'] ) : array( -1 ); // Default is "everybody"
	    $workout->assigned_usergroups = isset( $_POST['e20r-workout-assigned_usergroups'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-assigned_usergroups'] ) : array( -1 ) ;
	    $workout->startdate = isset( $_POST['e20r-workout-startdate'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-startdate'] ) : null;
	    $workout->enddate = isset( $_POST['e20r-workout-enddate'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-enddate'] ) : null;
        $workout->startday = isset( $_POST['e20r-workout-startday'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-startday'] ) : null;
        $workout->endday = isset( $_POST['e20r-workout-endday'] ) ? $e20rTracker->sanitize( $_POST['e20r-workout-endday'] ) : null;

	    $test = (array)$exData;

	    if ( !empty( $test ) ) {

		    foreach ($groupData as $key => $groupNo ) {

                $groups[ $groupNo ]->group_set_count = $groupSetCount[ $groupNo ];
                $groups[ $groupNo ]->group_tempo = $groupSetTempo[ $groupNo ];
                $groups[ $groupNo ]->group_rest = $groupSetRest[ $groupNo ];

                if ( isset( $exData[$key] ) ) {
                    dbg("e20rWorkout::saveSettings() - Adding exercise data from new definition");
				    $groups[ $groupNo ]->exercises[ $orderData[ $key ] ] = $exData[$key];
			    }

                if (  ( count( $workout->groups[$groupNo]->exercises ) > 1 ) &&
                    ( isset( $workout->groups[$groupNo]->exercises[0] ) ) ) {

                    dbg("e20rWorkout::saveSettings() - Clearing data we don't need");
                    unset($groups[$groupNo]->exercises[$orderData[$key]][0]);
                }

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

    /**
     *
     * Returns an archive of activities based on the requested period.
     * Currently supports previous, current and next week constants.
     *
     * @param int $userId -- User's Id
     * @param int $programId -- Program to process for
     * @param int $period -- The period (
     * @return array - list of activities keyed by day id (day 1 - 7, 1 == Monday)
     */
	public function getActivityArchive( $userId, $programId, $period = E20R_CURRENT_WEEK ) {

        global $e20rProgram;
        global $e20rTracker;
        global $e20rArticle;

        global $currentProgram;

        $startedTS = $e20rProgram->startdate( $userId, $programId, true );
        $started = date('Y-m-d H:i:s', $startedTS);
        // $started = $currentProgram->startdate;

        $currentDay = $e20rTracker->getDelay('now', $userId );
        $currentDate = date('Y-m-d', current_time( 'timestamp' ) );

        dbg("e20rWorkout::getActivityArchive() - User ({$userId}) started program ({$programId}) on: {$started}");

        // Calculate release_days to include for the $period
        switch ( $period ) {

            case E20R_UPCOMING_WEEK:
                dbg("e20rWorkout::getActivityArchive() - For the upcoming (current) week");
                $mondayTS = strtotime( "monday next week {$currentDate} " );
                $sundayTS = strtotime( "sunday next week {$currentDate}" );

                $period_string = "Activities next week";
                break;

            case E20R_PREVIOUS_WEEK:

                dbg("e20rWorkout::getActivityArchive() - For last week");
                $mondayTS = strtotime( "monday last week {$currentDate}");
                $sundayTS = strtotime( "sunday last week {$currentDate}" );

                $period_string = "Activities previous week";
                break;

            case E20R_CURRENT_WEEK:

                dbg("e20rWorkout::getActivityArchive() - For the current week");

                $sundayTS = strtotime( "next sunday {$currentDate}" );

                if ( date('N') == 1 ) {
                    // It's monday
                    $mondayTS = strtotime( "{$currentDate}");
                }
                else {
                    $mondayTS = strtotime( "last monday {$currentDate}");
                }

                $period_string = "Activities this week";
                break;

            default:
                return null;
        }

//        $startDelay = ($startDelay + $currentDay);
//        $endDelay = ( $endDelay + $currentDay );

        dbg( "e20rWorkout::getActivityArchive() - Monday TS: {$mondayTS}, Sunday TS: {$sundayTS}");
        $startDelay = $e20rTracker->daysBetween( $startedTS, $mondayTS, get_option('timezone_string') );
        $endDelay = $e20rTracker->daysBetween( $startedTS, $sundayTS, get_option('timezone_string') );

        if ( $startDelay < 0 ) {
            $startDelay = 1;
        }

        if ( $endDelay <= 0 ) {
            $endDelay = 6;
        }


        dbg("e20rWorkout::getActivityArchive() - Delay values -- start: {$startDelay}, end: {$endDelay}");
        $val = array( $startDelay, $endDelay );

        // Load articles in the program that have a release day value between the start/end delay values we calculated.
        $articles = $e20rArticle->loadArticlesByMeta( 'release_day', $val, 'numeric', $programId, 'BETWEEN' );

        dbg("e20rWorkout::getActivityArchive() - Found " . count($articles) . " articles");
        // dbg($articles);

        $activities = array( 'header' => $period_string );
        $unsorted = array();

        // Pull out all activities for the sequence list
        if ( is_array( $articles ) ) {

            foreach ($articles as $id => $article) {

                // Save activity list as a hash w/weekday => workout )
                dbg("e20rWorkout::getActivityArchive() - Getting activity {$article->activity_id} for article ID: {$article->id}");
                $act = $this->getActivity( $article->activity_id );

                if ( -1 !== $act ) {
                    $unsorted[] = array_pop($act);
                }
            }
        }
        else {
            dbg("e20rWorkout::getActivityArchive() - Single Article, activity ID: {$articles->activity_id}");
            $unsorted[] = $this->getActivity( $articles->activity_id );
        }

        // Save activities in an hash keyed on the weekday the activity is scheduled for.
        foreach( $unsorted as $activity ) {

            $mon = date('l', strtotime('monday'));

            foreach( $activity->days as $dayNo ) {

                $dNo = $dayNo;
                $day = date( 'l', strtotime( "monday + " . ($dNo - 1) ." days" ) );
                dbg("e20rWorkout::getActivityArchive() - Saving workout for weekday: {$day} -> ID: {$activity->id}");

                $activities[$dNo] = $activity;
            }
        }

        // Sort based on day id
        ksort( $activities );

        // Return the hash of activities to the calling function.
        return $activities;
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

        // global $post;
	    global $currentWorkout;

        dbg("e20rWorkout::addMeta_WorkoutSettings() - Loading settings metabox for workout page: " . $currentWorkout->id );
        // $this->init( $post->ID );
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

//        global $currentProgram;

	    if ( empty( $aIds ) ) {
		    dbg('e20rWorkout::getActivities() - Loading all activities from DB');
		    $activities = $this->model->find( 'id', 'any' ); // Will return all of the defined activities
	    }
/*        elseif ( is_array( $aIds ) ) {
            dbg("e20rWorkout::getActivities() - Supplied list of activity IDs, using 'IN' search");
            $activities = $this->model->find( 'delay', $aIds, 'numeric', $currentProgram->id, 'IN' );
        } */
	    else {
		    dbg('e20rWorkout::getActivities() - Loading specific activity from DB');
		    $activities = $this->model->find( 'id', $aIds );
	    }

        dbg("e20rWorkout::getActivities() - Found " . count($activities) . " activities.");

        return $activities;
    }

    /**
     * For the e20r_activity_archive shortcode.
     *
     * @param null $attributes
     *
     * @since 0.8.0
     */
    public function shortcode_act_archive( $attributes = null ) {

        dbg("e20rWorkout::shortcode_act_archive() - Loading shortcode data for the activity archive.");

        global $current_user;
        global $currentProgram;

        if ( ! is_user_logged_in() ) {

            auth_redirect();
        }

        $config = new stdClass();
        $config->userId = $current_user->ID;
        $config->programId = $currentProgram->id;
        $config->withInput = false;

        $workoutData = array();

        $tmp = shortcode_atts( array(
            'period' => 'current',
        ), $attributes );

        foreach ( $tmp as $key => $val ) {

            if ( !empty( $val ) ) {
                $config->{$key} = $val;
            }
        }

        if ( 'current' == $config->period ) {
            $period = E20R_CURRENT_WEEK;
        }

        if ('previous' == $config->period ) {
            $period = E20R_PREVIOUS_WEEK;
        }

        if ( 'next' == $config->period ) {
            $period = E20R_UPCOMING_WEEK;
        }

        dbg("e20rWorkout::shortcode_act_archive() - Period set to {$config->period}.");

        $activities = $this->getActivityArchive( $current_user->ID, $currentProgram->id, $period );

        dbg("e20rWorkout::shortcode_act_archive() - Grabbed activity count: " . count( $activities ) );

        echo $this->view->displayArchive( $activities, $config );
        // dbg($activities);

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
        global $currentProgram;
		global $post;

		$config = new stdClass();
		$workoutData = array();

		$tmp = shortcode_atts( array(
			'type' => 'activity',
			'activity_id' => null,
		), $attributes );

		foreach ( $tmp as $key => $val ) {

            if ( !empty( $val ) ) {
                $config->{$key} = $val;
            }
		}

		$config->userId = $current_user->ID;
		// $config->programId = $e20rProgram->getProgramIdForUser( $config->userId );
        $config->programId = $currentProgram->id;
        $config->startTS = strtotime( $currentProgram->startdate );
		// $config->startTS = $e20rProgram->startdate( $config->userId );
		$config->delay = $e20rTracker->getDelay( 'now' );
		$config->date = $e20rTracker->getDateForPost( $config->delay );
		$config->userGroup = $e20rTracker->getGroupIdForUser( $config->userId );
        $config->withInput = true;

		$config->dayNo = date_i18n( 'N', current_time('timestamp') );

        dbg( $config );

		dbg("e20rWorkout::shortcode_activity() - Using delay: {$config->delay} which gives date: {$config->date} for program {$config->programId}");

        // If the activity ID is set, don't worry about anything but loading that activity (assuming it's permitted).
        if ( isset( $config->activity_id ) && ( $config->activity_id !== null ) ) {

            dbg("e20rWorkout::shortcode_activity() - Admin specified activity ID of {$config->activity_id}" );
            $article = $e20rArticle->loadArticlesByMeta( 'activity_id', $config->activity_id, 'numeric', $config->programId );

        }
        else {

            dbg("e20rWorkout::shortcode_activity() - Attempting to locate article by configured delay value: {$config->delay}" );
            $articleId = $e20rArticle->findArticleByDelay($config->delay);

            if ( false !== $articleId ) {

                dbg("e20rWorkout::shortcode_activity() - Found the article ID {$articleId} based on the delay value: {$config->delay}" );
                $article = $e20rArticle->findArticle('id', $articleId);
            }

        }

        // dbg("e20rWorkout::shortcode_activity() - (Hopefully located) article: ");
        // dbg($article);


        if ( !isset( $article->id ) ) {
            dbg("e20rWorkout::shortcode_activity() - No article found!");
            $article = $currentArticle;
        }

        if ( isset( $article->activity_id ) && ( !empty( $article->activity_id) ) ) {

            dbg( "e20rWorkout::shortcode_activity() - Activity is defined for article: " . isset($article->activity_id) ? $article->activity_id : "(no activity)" );

            $workoutData = $this->model->find( 'id', $article->activity_id );

            foreach ( $workoutData as $k => $workout ) {

                dbg( "e20rWorkout::shortcode_activity() - Iterating through the fetched workout IDs. Now processing workoutData entry {$k}");
                // dbg($workout);

                if ( ! in_array( $config->programId, $workoutData[$k]->programs ) ) {

                    dbg( "e20rWorkout::shortcode_activity() - The workout is not part of the same program as the user - {$config->programId}: " );
                    unset( $workoutData[ $k ] );
                }

                if ( ! empty( $workoutData[$k]->assigned_user_id ) || ! empty( $workoutData[$k]->assigned_usergroups ) ) {

                    dbg( "e20rWorkout::shortcode_activity() - User Group or user list defined for this workout..." );

                    if ( !$e20rTracker->allowedActivityAccess( $workoutData[$k], $config->userId, $config->userGroup ) ) {

                        dbg( "e20rWorkout::shortcode_activity() - current user is NOT listed as a member of this activity: {$config->userId}" );
                        dbg( "e20rWorkout::shortcode_activity() - The activity is not part of the same group(s) as the user: {$config->userGroup}: " );

                        unset( $workoutData[ $k ] );
                    }
                }
            }
        }
/*
        if ( !isset( $article->id ) ) {

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


						if ( !$e20rTracker->allowedActivityAccess( $workoutData[$wid], $config->userId, $config->userGroup ) ) {

							dbg( "e20rWorkout::shortcode_activity() - current user is NOT listed as a member of this activity: {$config->userId}" );
							dbg( "e20rWorkout::shortcode_activity() - The activity is not part of the same group(s) as the user: {$config->userGroup}: " );

							unset( $workoutData[ $wid ] );
						}
					}
				}
			}
		}
*/
        $recorded = array();

		dbg( "e20rWorkout::shortcode_activity() - WorkoutData prior to processing");

		foreach ( $workoutData as $k => $w ) {

            dbg( "e20rWorkout::shortcode_activity() - Processing workoutData entry {$k} to test whether to load user data");

			if ( $k !== 'error' ) {

                dbg( "e20rWorkout::shortcode_activity() - Attempting to load user specific workout data for workoutData entry {$k}.");
				$saved_data = $this->model->getRecordedActivity( $config, $w->id );

				if ( ( ! empty( $w->days ) ) && ( ! in_array( $config->dayNo, $w->days ) ) ) {

					dbg( "e20rWorkout::shortcode_activity() - day {$config->dayNo} is wrong for this specific workout/activity" );
					dbg( $w->days );
                    dbg( "e20rWorkout::shortcode_activity() - Removing workout ID #{$w->id} as a result");
					unset( $workoutData[ $k ] );
				}
				else {

					foreach ( $w->groups as $gid => $g ) {

						if ( !empty( $saved_data ) ) {

							dbg("e20rWorkout::shortcode_activity() - Integrating saved data for group # {$gid}");
							$workoutData[ $k ]->groups[ $gid ]->saved_exercises = isset( $saved_data[$gid]->saved_exercises ) ? $saved_data[$gid]->saved_exercises : array();
						}


						if ( isset( $g->group_tempo ) ) {
                            dbg("e20rWorkout::shortcode_activity() - Setting the tempo identifier");
							$workoutData[ $k ]->groups[ $gid ]->group_tempo = $this->model->getType( $g->group_tempo );
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