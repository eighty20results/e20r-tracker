<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rWorkoutModel extends e20rSettingsModel {

	protected $settings;
	protected $types = array();
	protected $table;

    private static $exercise_levels = array();

    public function __construct() {

        global $e20rTables;

        parent::__construct( 'workout', 'e20r_workout' );

        $this->table = $e20rTables->getTable('workout');
        $this->fields = $e20rTables->getFields('workout');

        dbg("e20rWorkoutModel() - Constructor...");

        $this->types = array(
            0 => '',
            1 => __("Slow", "e20rtracker"),
            2 => __("Normal", "e20rtracker"),
            3 => __("Fast", "e20rtracker"),
            4 => __("Varying", "e20rtracker")
        );

        $this->settings = new stdClass();
    }

    /**
     * @return array - List of configured roles for
     */
    static function getExerciseLevels() {

        if (empty(self::$exercise_levels)) {
            
            self::$exercise_levels['e20r_coach'] = __( "Coach", "e20rtracker");
            self::$exercise_levels['e20r_tracker_exp_1'] = __( "Exercise Level 1 (NE)", "e20rtracker");
            self::$exercise_levels['e20r_tracker_exp_2'] = __( "Exercise Level 2 (IN)", "e20rtracker");
            self::$exercise_levels['e20r_tracker_exp_3'] = __( "Exercise Level 3 (EX)", "e20rtracker");
        }

        dbg("e20rWorkoutModel::getExerciseLevels() - Found levels: " . print_r(self::$exercise_levels, true));
        return self::$exercise_levels;
    }

    public function getTable() {

        return $this->table;
    }

    public function getField( $name = 'all' ) {

        if ( 'all' == $name ) {

            return $this->fields;
        }

        return $this->fields[$name];
    }

	public function getType( $tId = null ) {

        global $e20rTracker;

        if ( null == $tId ) {
            return $this->types[0];
        }

        dbg("e20rWorkoutModel::getType() - Type ID: {$tId}: {$this->types[$tId]} ->" . $e20rTracker->whoCalledMe() );
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
        $workout->program_ids = array();
	    $workout->workout_ident = 'A';
	    $workout->phase = null;
	    $workout->assigned_user_id = array( -1 );
	    $workout->assigned_usergroups = array( -1 );
	    // $workout->startdate = date( 'Y-m-d', current_time( 'timestamp' ) );
		$workout->startdate = null;
	    $workout->enddate = null;
		$workout->startday = null;
		$workout->endday = null;


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
		$tmp = $this->load_activity( $workoutId );

		$currentWorkout = $tmp[$workoutId];
	}

	public function loadSettings( $id ) {

		global $post;
		global $current_user;

		global $currentWorkout;

		global $e20rProgram;
        global $e20rTracker;

		if ( isset( $currentWorkout->id ) && ( $currentWorkout->id == $id ) ) {

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

            $this->settings->excerpt       = ( empty ($post->post_content ) ? $post->post_excerpt : $post->post_content );
            $this->settings->title    = $post->post_title;
            $this->settings->id          = $id;

            $this->settings->program_ids = get_post_meta($post->ID, '_e20r-workout-program_ids');

            dbg("e20rWorkoutModel::loadSettings() - Analyzing group content");
            // dbg( $this->settings );

            $ex = array();
            $g_def = $this->defaultGroup();

            if ( !is_array( $this->settings->groups ) ) {

                $this->settings->groups = array();
                $this->settings->groups[] = $g_def;
            }

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


        if ( !is_admin() ) {

            if (empty($this->settings->startdate) || empty($this->settings->enddate)) {

                dbg("e20rWorkoutModel::loadSettings() - The startdate or enddate settings contain no data...");
                // Grab membership start date for the current user.
                $startTS = $e20rProgram->startdate($current_user->ID, null, true);

                $userStarted = date_i18n('Y-m-d', $startTS);
                dbg("e20rWorkoutModel::loadSettings() - The start Timestamp for use {$current_user->ID}: {$startTS} gives a start date of {$userStarted}");
            }

            if (empty($this->settings->startdate) && !empty($this->settings->startday) && isset($current_user->ID)) {
                dbg("e20rWorkoutModel::loadSettings() - Calculate the startdate based on the startday number");

                $this->settings->startdate = date_i18n('Y-m-d', strtotime("{$userStarted} +{$this->settings->startday} days"));

                dbg("e20rWorkoutModel::loadSettings() - Result: start day number {$this->settings->startday} gives a workout start date of {$this->settings->startdate}");
            }

            if (empty($this->settings->enddate) && (!empty($this->settings->endday))) {
                dbg("e20rWorkoutModel::loadSettings() - Calculate the enddate based on the endday number");

                $this->settings->enddate = date_i18n('Y-m-d', strtotime("{$userStarted} +{$this->settings->endday} days"));

                dbg("e20rWorkoutModel::loadSettings() - Result: end day number {$this->settings->endday} gives a workout end date of {$this->settings->enddate}");
            }

            if ( empty($this->settings->enddate) && empty( $this->settings->endday) ) {
                dbg("e20rWorkoutModel::loadSettings() - No defined date/or day for the end of the workout");
                $this->settings->enddate = '2038-01-01';
            }

            if ( empty($this->settings->startdate) && empty( $this->settings->startdate) ) {
                dbg("e20rWorkoutModel::loadSettings() - No defined date/or day for the end of the workout");
                $this->settings->startdate = '2014-01-01';
            }

        }

		$currentWorkout = $this->settings;
		return $currentWorkout;
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

    public function save_activity_status( $post_data ) {

        global $e20rTracker;
        global $e20rAction;
        global $e20rArticle;

        $completed = isset( $_POST['completed'] ) ? $e20rTracker->sanitize( $_POST['completed'] ) : false;
        $userId = isset( $_POST['user_id'] ) ? $e20rTracker->sanitize( $_POST['user_id'] ) : null;
        $activityId = isset( $_POST['activity_id']) ? $e20rTracker->sanitize( $_POST['activity_id'] ) : null;
        $articleId = isset( $_POST['article_id']) ? $e20rTracker->sanitize( $_POST['article_id'] ) : null;
        $programId = isset( $_POST['program_id'] ) ? $e20rTracker->sanitize( $_POST['program_id'] ) : null;
        $checkedin_date = isset( $_POST['recorded'] ) ? $e20rTracker->sanitize( $_POST['recorded'] ) : null;
        $checkin_date = isset( $_POST['for_date'] ) ? $e20rTracker->sanitize( $_POST['for_date'] ) : null;
        $checkin_shortname = $e20rArticle->get_checkin_shortname( $articleId, CHECKIN_ACTIVITY );

        if ( false == $completed ) {

            $completed = 2;
        }

        $checkin = array(
            'user_id' => $userId,
            'activity_id' => $activityId,
            'article_id' => $articleId,
            'program_id' => $programId,
            'descr_id' => $activityId, // descr_id is the assignment ID (no assignment for an activity so using $activityId )
            'checkedin_date' => $checkedin_date,
            'checkin_type' => CHECKIN_ACTIVITY,
            'checkin_date' => $checkin_date,
            'checkedin' => $completed,
            'checkin_short_name' => $checkin_shortname,
        );

        if ( false === $e20rAction->save_check_in( $checkin, CHECKIN_ACTIVITY ) ) {

            dbg("e20rWorkoutModel::save_activity_status() - Error saving activity check-in for user {$userId}");
            return false;
        }

        return true;
    }

    public function getExerciseHistory( $exercise_id, $userId, $programId = null, $start_date = 'all' ) {

        global $wpdb;
        global $currentProgram;

        if ( is_null( $programId ) ) {

            $programId = $currentProgram->id;
        }

        if ( 'all' == $start_date ) {

            $start_date = $currentProgram->startdate;
        }

        /*
        $sql = $wpdb->prepare(
            "
            SELECT
              {$this->fields['id']}, {$this->fields['for_date']}, {$this->fields['user_id']}, {$this->fields['exercise_id']},
              MAX({$this->fields['weight']}) AS {$this->fields['weight']}, {$this->fields['reps']}
            FROM {$this->table}
              WHERE {$this->fields['program_id']} = %d AND {$this->fields['user_id']} = %d AND {$this->fields['for_date']} >= %s
              AND {$this->fields['weight']} != 0 AND {$this->fields['exercise_id']} = %d
              GROUP BY {$this->fields['exercise_id']}, {$this->fields['for_date']}
              ORDER BY {$this->fields['for_date']};
            ",
            $programId,
            $userId,
            $start_date,
            $exercise_id
          );
*/
        $sql = $wpdb->prepare("
                        SELECT UNIX_TIMESTAMP(for_date) AS for_date, MAX(weight) AS weight, reps
                          FROM {$this->table}
                          WHERE {$this->fields['program_id']} = %d AND {$this->fields['user_id']} = %d AND {$this->fields['for_date']} >= %s
                          AND {$this->fields['exercise_id']} = %d
                        GROUP BY {$this->fields['for_date']}",
            $programId,
            $userId,
            $currentProgram->startdate,
            $exercise_id
        );
        dbg("e20rWorkoutModel::getExerciseHistory() - SQL: {$sql}");

        $results = $wpdb->get_results( $sql );

        $weights = array();
        $reps = array();

        if ( empty( $results ) ) {

            dbg( "e20rWorkoutModel::getExerciseHistory() - Error loading from database: Zero records found & possible error:" . $wpdb->print_error() );
            return false;
        }
        else {

            dbg( "e20rWorkoutModel::getExerciseHistory() - loaded " . count( $results ) . " records" );

            foreach( $results as $rec ) {

                // $ts = strtotime(  );

                $weights[] = array( $rec->for_date * 1000, number_format( (float) $rec->weight, 2) );
                $reps[] = array( $rec->for_date * 1000, number_format( (float) $rec->reps, 2 ) );
            }
        }

        return array( $weights, $reps );
        // return $this->transformForJS( $records );
    }
/*
    private function transformForJS( $records ) {

        global $e20rTables;
        global $e20rClient;

        $retVal = array();

        if ( ! is_array( $records ) ) {
            dbg("e20rWorkout::transformForJS() - Convert to array of results");
            $records = array( $records );
        }

        $exclude = array(
            'id',
            'user_id',
            'exercise_id',
            'program_id',
            'for_date',
        );

        dbg("e20rWorkoutModel::transformForJS() - DB  data:");
        // dbg( $this->fields );
        // dbg( $records );

        foreach( $records as $date => $record ) {

            foreach ( $record as $key => $value ) {

                $mKey = array_search( $key, $this->fields );

                dbg( "e20rWorkout::transformForJS() - Key ({$key}) is really {$mKey}" );

                if ( ! in_array( $key, $exclude ) ) {

                    $retVal[ $mKey ] = array(
                        'value' => $value,
                        'units' => ( $key != 'weight' ? 'reps' : $e20rClient->getWeightUnit() ) // $e20rClient->getLengthUnit() : $e20rClient->getWeightUnit() ),
                    );
                }
            }
        }

        return ( empty( $retVal ) ? $record : $retVal );
    }
*/

    public function loadUserActivityData( $userId, $programId = null ) {

        dbg("e20rWorkoutModel::loadUserActivityData() - Loading activity data for {$userId} in program {$programId}");

        global $wpdb;
        global $currentProgram;
        global $e20rProgram;

        if ( ( is_null( $programId ) ) || ( $currentProgram->id != $programId ) ) {

            dbg("e20rWorkoutModel::loadUserActivityData() - Change program id from {$currentProgram->id} to {$programId}");
            $programId = $e20rProgram->getProgramForUserId( $userId );
        }

        $activities = array();

        $sql = $wpdb->prepare(
            "SELECT exercise_id, UNIX_TIMESTAMP(for_date) AS for_date,
                    exercise_key, group_no, set_no, weight, reps
             FROM {$this->table}
             WHERE user_id = %d AND program_id = %d AND for_date >= %s
             ORDER BY exercise_id, for_date, group_no, set_no, exercise_key DESC",
            $userId,
            $programId,
            $currentProgram->startdate
        );

        dbg("e20rWorkoutModel::loadUserActivityData() - SQL: {$sql}");

        $records = $wpdb->get_results( $sql, ARRAY_A );

        if ( !empty( $records ) ) {

            dbg("e20rWorkoutModel::loadUserActivityData() - Processing " . count($records) . " workout records for user {$userId}");

            foreach( $records as $wr ) {

                //$set = ( ($wr['group_no'] != 0 ? $wr['group_no'] : 1) * $wr['set_no'] * $wr['exercise_key']);
                // $set = ( ($wr['group_no'] != 0 ? $wr['group_no'] : 1) * $wr['set_no'] + $wr['exercise_key']);
                // $set = $wr['group_no'] + $wr['set_no'] + $wr['exercise_key'];

                if ( !isset( $activities[$wr['exercise_id']] ) ) {

                    $activities[$wr['exercise_id']] = new stdClass();
                }

                // if ( !isset( $activities[$wr['exercise_id']]->{$wr['for_date']}  ) ) {

                if ( !isset( $activities[$wr['exercise_id']]->when  ) ) {
                    $activities[$wr['exercise_id']]->when = array();
                }

                if ( !isset( $activities[$wr['exercise_id']]->when[$wr['for_date']] ) ) {
                    $activities[$wr['exercise_id']]->when[$wr['for_date']] = new stdClass();
                }

                if ( !isset( $activities[$wr['exercise_id']]->when[$wr['for_date']]->group ) ) {

                    $activities[$wr['exercise_id']]->when[$wr['for_date']]->group = array();
                }
/*                if ( !isset( $activities[$wr['exercise_id']]->{$wr['for_date']}->{$wr['group_no']} ) ) {

                    $activities[$wr['exercise_id']]->$wr['for_date']->{$wr['group_no']} = array();
                }
*/
                if ( !isset( $activities[$wr['exercise_id']]->when[$wr['for_date']]->group[$wr['group_no']] ) ) {

                    $activities[$wr['exercise_id']]->when[$wr['for_date']]->group[$wr['group_no']] = new stdClass();
                }

                $activities[$wr['exercise_id']]->when[$wr['for_date']]->group[$wr['group_no']]->set = $wr['set_no'];
                $activities[$wr['exercise_id']]->when[$wr['for_date']]->group[$wr['group_no']]->weight = $wr['weight'];
                $activities[$wr['exercise_id']]->when[$wr['for_date']]->group[$wr['group_no']]->reps = $wr['reps'];
            }

            dbg("e20rWorkoutModel::loadUserActivityData() - Completed processing: Returning data for " . count($activities) . " workouts");
            // dbg($activities);
        }

        return $activities;
    }

    public function loadAllUserActivities( $userId ) {

        global $e20rTracker;
        global $e20rExercise;
        global $e20rProgram;

        global $currentExercise;
        global $currentProgram;

        $today = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );

        $programId = $e20rProgram->getProgramIdForUser( $userId );

        $delay = $e20rTracker->getDelay( 'now', $userId );

        dbg("e20rWorkoutModel::loadAllUserActivities() - Loading assignments for user {$userId} in program {$programId}");

        $workout_byDate = $this->loadWorkoutByMeta( $programId, 'startdate', $today, '<=', 'date', 'startdate' );

        dbg("e20rWorkoutModel::loadAllUserActivities() - Not using  assignments for user {$userId} in program {$programId}");
        $workout_byDay = $this->loadWorkoutByMeta( $programId, 'startday', $delay, '<=', 'numeric', 'startday' );

        if ( is_array( $workout_byDate) && is_array( $workout_byDay) ) {
            $workouts = array_merge( $workout_byDay, $workout_byDate );
        }

        if ( is_array( $workout_byDate ) && ( !is_array( $workout_byDay) ) ) {
            $workouts = $workout_byDate;
        }

        if ( is_array( $workout_byDay ) && ( !is_array( $workout_byDate) ) ) {
            $workouts = $workout_byDay;
        }

        dbg("e20rAssignmentModel::loadAllUserAssignments() - Returned " . count( $workouts ) . " to process ");

        if ( empty( $workouts ) ) {

            dbg("e20rAssignmentModel::loadAllUserAssignments() - No records found.");
            return false;
        }

        $activities = array();
        $userData = $this->loadUserActivityData( $userId, $programId );

        /*
        foreach ( $workouts as $w ) {

            dbg("e20rWorkoutModel::loadAllUserActivities() - Processing workout {$w->id}");

             foreach( $w->groups as $g ) {
        */
                foreach( $userData as $e => $info ) {

                    dbg("e20rWorkoutModel::loadAllUserActivities() - Processing exercise {$e}");

                    if ( isset($userData[$e]) ) {

                        $e20rExercise->set_currentExercise($e);
                        $userData[$e]->name = $currentExercise->title;
                        $userData[$e]->type = $currentExercise->type;
                        // $userData[$e]->descr = $currentExercise->descr;
                    }
                }
        /*  }
        } */

        return $userData;
    }


    private function loadWorkoutByMeta( $programId, $key, $value, $comp = '=', $type = 'numeric', $orderbyKey = 'startday' ) {

        global $current_user;
        global $e20rProgram;
        global $e20rTracker;

        $records = array();

        dbg("e20rWorkoutModel::loadWorkoutByMeta() - for program #: {$programId}");

        $args = array(
            'posts_per_page' => -1,
            'post_type' => $this->cpt_slug,
            'post_status' => 'publish',
            'meta_key' => "_e20r-{$this->type}-{$orderbyKey}",
            'order_by' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => "_e20r-{$this->type}-{$key}",
                    'value' => $value,
                    'compare' => $comp,
                    'type' => $type,
                ),
                array(
                    'key' => "_e20r-{$this->type}-program_ids",
                    'value' => $programId,
                    'compare' => '=',
                    'type' => 'numeric',
                ),
            )
        );

        $query = new WP_Query( $args );

        dbg("e20rWorkoutModel::loadWorkoutByMeta() - Returned workouts: {$query->post_count}" );

        while ( $query->have_posts() ) {

            $query->the_post();

            $new = new stdClass();
            $records[] = $this->loadSettings( get_the_ID() );

            /*
            $new = $this->loadSettings( get_the_ID() );

            if ( empty( $new->program_ids ) || in_array( $programId, $new->program_ids ) ) {
                $records[] = $new;
            }
            */
        }

        dbg("e20rWorkoutModel::loadWorkoutByMeta() - Returning " .
            count( $records ) . " records to: " . $e20rTracker->whoCalledMe() );

        wp_reset_query();

        return $records;
    }

	public function load_userData( $userId, $start = 'start', $end = 'end', $programId = null, $fields = null ) {

        global $wpdb;

        global $e20rProgram;
        global $currentProgram;

        $result = array();
        $result['workout'] = array();

        // Set/get the correct program ID (and configure the $currentProgram global).
        if ( is_null( $programId ) ) {

            $programId = $e20rProgram->getProgramIdForUser( $userId );
        }
        else {

            if ( $programId != $currentProgram->id ) {
                dbg("e20rWorkoutModel::load_userData() - Loading new program config for program with ID: {$programId}");
                $e20rProgram->getProgram( $programId );
            }
        }

        // TODO: Have to handle the 'all' to/from date/time.

        // Make sure the $from_when time is a valid time/date value
        if ( !is_null( $start ) && ( false === ( $fromTS = strtotime( $start ) ) ) ) {

            dbg("e20rWorkoutModel::load_userData() - Error: Invalid date/time in 'from' value" );
            return false;
        }

        if ( !is_null( $end ) && ( false === ( $toTS = strtotime( $end ) ) ) ) {

            dbg("e20rWorkoutModel::load_userData() - Error: Invalid date/time in 'to' value. Setting to default ('now')");
            $toTS = strtotime( 'now' );
        }

        $period = null;

        if ( 'start' == $start ) {

            // We're starting from the beginning of the specified programId
            $from = date( 'Y-m-d', strtotime( $currentProgram->startdate ) );
        }
        else {
            $from = date( 'Y-m-d', $fromTS );
        }

        if ( 'end' == $end ) {
            // We're starting from the beginning of the specified programId
            $to = date( 'Y-m-d', strtotime( $currentProgram->enddate ) );
        }
        else {
            $to = date( 'Y-m-d', strtotime( $toTS ) );
        }

        if ( !( ( 'all' == $start ) && ( 'all' == $end ) ) ) {
            // We're not being asked to return all records.
            $period = "( ( {$this->fields['for_date']} >= '{$from} 00:00:00' ) AND ( {$this->fields['for_date']} <= '{$to} 23:59:59' ) )";
        }
        else {
            $period = null;
        }


        if (is_array( $fields ) ) {
            $selected = join( ',', $fields );
        }
        elseif ( is_string( $fields ) ) {
            $selected = $fields;
        }
        else {
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

        dbg("e20rWorkoutModel::load_userData() - SQL: {$sql}");

        $records = $wpdb->get_results( $sql );

        if ( !empty( $records ) ) {

            dbg("e20rWorkoutModel::load_userData() - Located " . count( $records ) . " records in DB for user {$userId} related to program {$currentProgram->id}");

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
            foreach( $records as $r ) {

                if ( !isset( $result[$r['{$this->fields["for_date"]}'] ] ) ) {

                    $result[$r["{$this->fields['for_date']}"]] = array();
                }

                if ( !isset( $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}" ] ] ) ) {

                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}" ] ] = array();
                }

                if ( !isset( $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]] ) ) {

                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]] = array();
                }

                if ( isset( $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]] ) ) {

                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]] = array();
                }

                if ( isset( $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]] ) ) {

                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]] = array();
                }

                if ( isset( $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]][$r["{$this->fields['set_no']}"]] ) ) {

                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]][$r["{$this->fields['set_no']}"]] = new stdClass();
                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]][$r["{$this->fields['set_no']}"]]->reps;
                    $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]][$r["{$this->fields['set_no']}"]]->weight;
                }

                $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]][$r["{$this->fields['set_no']}"]]->reps = $r["{$this->fields['reps']}"];
                $result[$r["{$this->fields['for_date']}"] ][$r["{$this->fields['activity_id']}"]][$r["{$this->fields['exercise_id']}"]][$r["{$this->fields['exercise_key']}"]][$r["{$this->fields['group_no']}"]][$r["{$this->fields['set_no']}"]]->weight = $r["{$this->fields['weight']}"];

            }

            dbg("e20rWorkoutModel::load_userData() - Loaded and formatted " . count( $result ) . " records for user {$userId} in program {$currentProgram->id} between {$start} and {$end}");
        }
        else {
            dbg("e20rWorkoutModel::load_userData() - No records found when specifying user: {$userId}, start: {$start}, end: {$end}, program: {$currentProgram->id}, SELECT {$selected}");
            dbg("e20rWorkoutModel::load_userData() - Error? {$wpdb->last_error}");
        }

        return $result;
	}

	/**
     * Returns an array of all workouts merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return program data for.
     * @return mixed - Array of program objects
     */
    public function loadAllData( $statuses = 'any' ) {

        $query = array(
            'posts_per_page' => -1,
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

        wp_reset_postdata();
        return $workout_list;
    }

    public function load_activity( $id, $statuses = 'any' ) {

	    global $post;

	    $savePost = $post;
	    $workouts = array();

	    dbg( "e20rWorkoutModel::load_activity() - Attempting to load workout settings for {$id}" );

	    if ( $id === null ) {

		    dbg( "e20rWorkoutModel::load_activity() - Warning: Unable to load workout data. No ID specified!" );

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
			    dbg( "e20rWorkoutModel::load_activity() - No workout found!" );

			    return array( $post->ID => $this->defaultSettings() );
		    }

		    while ( $query->have_posts() ) {

			    $query->the_post();

			    dbg("e20rWorkoutModel::load_activity() - For Workout id: " . get_the_ID() );
			    $new = $this->loadSettings( get_the_ID() );
                $post_title = get_the_title();

			    $new->id         = $id;

                dbg("e20rWorkoutModel::load_activity() - For {$post_title}: " . print_r($new->assigned_usergroups, true));

                // Convert/update the userGroup to role based model
                $for_ex = preg_match("/\([A-D|a-d][0-9][0-9][0-9][0-9]EX\)|\([A-D|a-d][0-9][0-9][0-9][0-9]EX-[1-9]\)/i", $post_title);

                dbg("e20rWorkoutModel::load_activity() - For {$post_title}: " . print_r($for_ex, true));

                if ( false != $for_ex && ( !in_array( 'e20r_tracker_exp_3', $new->assigned_usergroups) )) {
                    dbg("e20rWorkoutModel::load_activity() - Assigning/converting to use role for experienced" );
                    $new->assigned_usergroups[] = 'e20r_tracker_exp_3';
                }

                $for_in = preg_match("/\([A-D|a-d][0-9][0-9][0-9][0-9]IN\)|\([A-D|a-d][0-9][0-9][0-9][0-9]IN-[1-9]\)/i", $post_title);

                dbg("e20rWorkoutModel::load_activity() - For {$post_title}: " . print_r($for_in, true));

                if ( false != $for_in && ( !in_array( 'e20r_tracker_exp_2', $new->assigned_usergroups) ) ) {
                    dbg("e20rWorkoutModel::load_activity() - Assigning/converting to use role for intermediates" );
                    $new->assigned_usergroups[] = 'e20r_tracker_exp_2';
                }

                $for_ne = preg_match("/\([A-D|a-d][0-9][0-9][0-9][0-9]NE\)|\([A-D|a-d][0-9][0-9][0-9][0-9]NE-[1-9]\)/i", $post_title);
                dbg("e20rWorkoutModel::load_activity() - For {$post_title}: " . print_r($for_ne, true));

                if ( false !=  $for_ne && ( !in_array( 'e20r_tracker_exp_1', $new->assigned_usergroups) )) {
                    dbg("e20rWorkoutModel::load_activity() - Assigning/converting to use role for beginners" );
                    $new->assigned_usergroups[] = 'e20r_tracker_exp_1';
                }

                update_post_meta( $new->id, 'e20r-workout-assigned_usergroups', $new->assigned_usergroups);

                $workouts[$new->id] = $new;
		    }

            wp_reset_postdata();
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

		    if ( in_array( $key, array( 'id', 'excerpt' ) ) ) {

			    continue;
		    }

/*            if ( 'excerpt' == $key ) {
                $settings->{$key} = wpautop( $settings->{$key} );
            }
*/
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