<?php

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */
class e20rWorkout extends e20rSettings
{

    private $workout = array();
    public $model = null;
    public $view = null;

    protected $table;
    protected $fields;

    public function __construct()
    {

        dbg("e20rWorkout::__construct() - Initializing Workout class");

        $this->model = new e20rWorkoutModel();
        $this->view = new e20rWorkoutView();

        parent::__construct('workout', 'e20r_workout', $this->model, $this->view);
    }

    public function init($id = null)
    {

        global $currentWorkout;
        global $e20rTables;

        $this->table = $e20rTables->getTable('workout');
        $this->fields = $e20rTables->getFields('workout');

        if (empty($currentWorkout) || (isset($currentWorkout->id) && ($currentWorkout->id != $id))) {
            dbg("e20rWorkout::init() - received id value: {$id}");

            // $currentWorkout = parent::init( $id );
            $this->model->init($id);

            dbg("e20rWorkout::init() - Loaded settings for {$id}:");
            // dbg($currentWorkout);
        }
    }

    public function getActivity($identifier)
    {

        dbg("e20rWorkout::getActivity() - Loading Activity data for {$identifier}");

        if (!isset($this->model)) {
            $this->init();
        }

        $workout = array();

        if (is_numeric($identifier)) {
            // Given an ID
            $workout = $this->model->load_activity($identifier, 'any');
        }

        /*		if (is_string( $identifier ) ) {
                    // Given a short_name
                    $workout[] = $this->getWorkout( $identifier );
                }
        */
        dbg("e20rWorkout::getActivity() - Returning Activity data for {$identifier}");
        return $workout;

    }

    public function getWorkout($shortName)
    {

        if (!isset($this->model)) {
            $this->init();
        }

        $pgmList = $this->model->loadAllData('any');

        foreach ($pgmList as $pgm) {
            if ($pgm->shortname == $shortName) {
                unset($pgmList);
                return $pgm;
            }
        }

        unset($pgmList);
        return false; // Returns false if the program isn't found.
    }

    public function editor_metabox_setup($post)
    {

        // global $currentWorkout;

        dbg("e20rWorkout::editor_metabox_setup() - Loading settings for workout page: " . $post->ID);
        $this->init($post->ID);

        // $currentWorkout = $this->model->find( 'id', $post->ID );

        add_meta_box('e20r-tracker-workout-settings', __('Activity Settings', 'e20rtracker'), array(&$this, "addMeta_WorkoutSettings"), 'e20r_workout', 'normal', 'core');

    }

    public function ajax_getPlotDataForUser()
    {

        global $e20rProgram;
        global $e20rClient;
        global $e20rTracker;

        global $currentProgram;

        dbg('e20rWorkout::ajax_getPlotDataForUser() - Requesting workout data');
        check_ajax_referer('e20r-tracker-data', 'e20r-weight-rep-chart');
        dbg("e20rWorkout::ajax_getPlotDataForUser() - Nonce is OK");
        // dbg($_POST);

        $user_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : null;

        if ($e20rClient->validateAccess($user_id)) {
            $e20rProgram->getProgramIdForuser($user_id);
        } else {
            dbg("e20rWorkout::ajax_getPlotDataForUser() - Logged in user ID does not have access to the data for user {$user_id}");
            wp_send_json_error(__("Your membership level prevents you from accessing this data. Please upgrade.", "e20rtracker"));
            wp_die();
        }

        $exercise_id = isset($_POST['exercise_id']) ? $e20rTracker->sanitize($_POST['exercise_id']) : 0;

        dbg("e20rWorkout::ajax_getPlotDataForUser() - Using measurement data & configure dimensions");

        $stats = $this->model->getExerciseHistory($exercise_id, $user_id, $currentProgram->id, $currentProgram->startdate);

        // $stats = $this->generate_stats( $history );

        if (isset($_POST['wh_h_dimension'])) {

            dbg("e20rWorkout::ajax_getPlotDataForUser() - We're displaying the front-end user progress summary");
            $dimensions = array('width' => intval($_POST['wh_w_dimension']),
                'wtype' => sanitize_text_field($_POST['wh_w_dimension_type']),
                'height' => intval($_POST['wh_h_dimension']),
                'htype' => sanitize_text_field($_POST['wh_h_dimension_type'])
            );

            // $dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
        } else {

            dbg("e20rWorkout::ajax_getPlotDataForUser() - We're displaying on the admin page.");
            $dimensions = array('width' => '650', 'height' => '500', 'htype' => 'px', 'wtype' => 'px');
        }

        dbg("e20rWorkout::ajax_getPlotDataForuser() - Dimensions: ");
        // dbg($dimensions);

        dbg("e20rWorkout::ajax_getPlotDataForuser() - Stats: ");
        // dbg($stats);

        $html = $this->view->view_WorkoutStats($user_id, $exercise_id, $dimensions);

        // $stats = $this->generate_stats( $activities );
        // $reps = $this->generatePlotData( $workout_data, 'reps' );

        dbg("e20rWorkout::ajax_get_PlotDataForUser() - Generated plot data for measurements");
        $data = json_encode(array('success' => true, 'html' => $html, 'stats' => $stats), JSON_NUMERIC_CHECK);
        echo $data;
        // wp_send_json_success( array( 'html' => $data, 'weight' => $weight, 'girth' => $girth ) );
        wp_die();
    }

    public function generate_stats($data)
    {

        // global $e20rTables;
        // $fields = $this->model->getField();

        // dbg($data);

        $data_matrix = array();

        if (empty($data)) {

            return array();
        }

        foreach ($data as $exercise) {

            foreach ($exercise as $workout)
                if (is_object($exercise->history)) {

                    $workout_weight[] = array((strtotime($workout->for_date) * 1000), number_format((float)$workout->weight, 2));
                    $workout_reps[] = array((strtotime($workout->for_date) * 1000), number_format((float)$workout->reps, 2));
                }
        }
        return array($workout_weight, $workout_reps);
    }

    public function listUserActivities($userId)
    {

        global $current_user;
        global $e20rProgram;
        global $e20rTracker;
        global $currentProgram;

        $config = new stdClass();
        $config->type = 'activity';
        $config->post_date = null;

        $config->userId = $userId; // $userId;
        $config->startTS = $e20rProgram->startdate($config->userId);
        $config->delay = $e20rTracker->getDelay('now');

        $activities = $this->model->loadAllUserActivities($userId);

        dbg("e20rWorkout::listUserActivities() - Received " . count($activities) . " activity records...");
        // dbg($activities);

        // Get and load the statistics for the user.
        if (isset($_POST['wh_h_dimension'])) {

            dbg("e20rWorkout::listUserActivities() - We're displaying the front-end user progress summary");
            $dimensions = array('width' => intval($_POST['wh_w_dimension']),
                'wtype' => sanitize_text_field($_POST['wh_w_dimension_type']),
                'height' => intval($_POST['wh_h_dimension']),
                'htype' => sanitize_text_field($_POST['wh_h_dimension_type'])
            );

            // $dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
        } else {

            dbg("e20rWorkout::listUserActivities() - We're displaying on the admin page.");
            $dimensions = array('width' => '650', 'height' => '300', 'htype' => 'px', 'wtype' => 'px');
        }

        /*        foreach( $activities as $key => $activity ) {

                    //$activity->graph = $this->model->getExerciseDataByDate( $config->userId, date_i18n( 'Y-m-d', $config->startTS ), $currentProgram->id, $activity );
                    $activity->graph = $this->model->getExerciseDataByDate( $config->userId, '2015-06-01', $currentProgram->id, $activity );

                    $activities[$key] = $activity;
                }
        */
        // $html = $this->view->view_WorkoutStats( $config->userId, $data, $dimensions );
        return $this->view->viewExerciseProgress($activities, null, $userId, $dimensions);
    }

    public function saveExData_callback()
    {

        global $current_user;
        global $e20rTracker;

        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to access this service');
        }

        check_ajax_referer('e20r-tracker-activity', 'e20r-tracker-activity-input-nonce');

        dbg("e20rWorkout::saveExData_callback() - Has the right privs to save data: ");
        // dbg($_POST);

        if (isset($POST['completed']) && (intval($_POST['completed'] == 1))) {

            dbg("e20rWorkout::saveExData_callback() - User indicated their workout is complete.");
            $id = $this->model->save_activity_status($_POST);
            wp_send_json_success(array('id' => $id));
        }

        $data = array();
        $skip = array('action', 'e20r-tracker-activity-input-nonce');

        foreach ($_POST as $k => $v) {

            if ($k == 'recorded') {

                dbg("e20rWorkout::saveExData_callback() - Saving date/time of record.");
                $v = date_i18n('Y-m-d H:i:s', $e20rTracker->sanitize($v));
            }

            if ($k == 'for_date') {

                dbg("e20rWorkout::saveExData_callback() - Saving date/time for when the record should have been recorded: {$v}.");
                $v = date_i18n('Y-m-d H:i:s', strtotime($e20rTracker->sanitize($v)));
            }
            if (!in_array($k, $skip)) {


                dbg("e20rWorkout::saveExData_callback() - Saving {$k} as {$v} for record.");
                $data[$k] = $e20rTracker->sanitize($v);
            }
        }

        dbg("e20rWorkout::saveExData_callback() - Data array to use");
        // dbg($data);

        $format = $e20rTracker->setFormatForRecord($data);
        // dbg($format);

        if (($id = $this->model->save_userData($data, $format)) === false) {
            dbg("e20rWorkout::saveExData_callback() - Error saving user data record!");
            wp_send_json_error();
        }
        dbg("e20rWorkout::saveExData_callback() - Saved record with ID: {$id}");
        wp_send_json_success(array('id' => $id));
    }

    public function load_user_activity($activity_id, $user_id)
    {

        $this->init( $activity_id );


    }

    public function loadUserData($userId, $start = 'start', $end = 'end', $programId = null, $fields = null)
    {

        return $this->model->load_userData($userId, $start, $end, $programId, $fields);
    }

    /**
     * Save the Workout Settings to the metadata table.
     *
     * @param $post_id (int) - ID of CPT settings for the specific article.
     *
     * @return bool - True if successful at updating article settings
     */
    public function saveSettings($post_id)
    {

        global $post;
        global $current_user;
        global $e20rTracker;

        if ((!isset($post->post_type)) || ($post->post_type != 'e20r_workout')) {

            dbg("e20rWorkout::saveSettings() - Not a e20r_workout CPT: ");
            return $post_id;
        }

        if (empty($post_id)) {

            dbg("e20rWorkout::saveSettings() - No post ID supplied");
            return false;
        }

        if (wp_is_post_revision($post_id)) {

            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {

            return $post_id;
        }

        dbg("e20rWorkout::saveSettings()  - Saving workout to database.");

        $groups = array();
        $workout = $this->model->loadSettings($post_id);

        $groupData = isset($_POST['e20r-workout-group']) ? $e20rTracker->sanitize($_POST['e20r-workout-group']) : array($post_id => $this->model->defaultGroup());
        $exData = isset($_POST['e20r-workout-group_exercise_id']) ? $e20rTracker->sanitize($_POST['e20r-workout-group_exercise_id']) : array();
        $orderData = isset($_POST['e20r-workout-group_exercise_order']) ? $e20rTracker->sanitize($_POST['e20r-workout-group_exercise_order']) : array();
        $groupSetCount = isset($_POST['e20r-workout-group_set_count']) ? $e20rTracker->sanitize($_POST['e20r-workout-group_set_count']) : array();
        $groupSetTempo = isset($_POST['e20r-workout-groups-group_tempo']) ? $e20rTracker->sanitize($_POST['e20r-workout-groups-group_tempo']) : array();
        $groupSetRest = isset($_POST['e20r-workout-groups-group_rest']) ? $e20rTracker->sanitize($_POST['e20r-workout-groups-group_rest']) : array();

        $workout->program_ids = isset($_POST['e20r-workout-program_ids']) ? $e20rTracker->sanitize($_POST['e20r-workout-program_ids']) : array();
        $workout->days = isset($_POST['e20r-workout-days']) ? $e20rTracker->sanitize($_POST['e20r-workout-days']) : array();
        $workout->workout_ident = isset($_POST['e20r-workout-workout_ident']) ? $e20rTracker->sanitize($_POST['e20r-workout-workout_ident']) : 'A';
        $workout->phase = isset($_POST['e20r-workout-phase']) ? $e20rTracker->sanitize($_POST['e20r-workout-phase']) : 1;
        $workout->assigned_user_id = isset($_POST['e20r-workout-assigned_user_id']) ? $e20rTracker->sanitize($_POST['e20r-workout-assigned_user_id']) : array(-1); // Default is "everybody"
        $workout->assigned_usergroups = isset($_POST['e20r-workout-assigned_usergroups']) ? $e20rTracker->sanitize($_POST['e20r-workout-assigned_usergroups']) : array(-1);
        $workout->startdate = isset($_POST['e20r-workout-startdate']) ? $e20rTracker->sanitize($_POST['e20r-workout-startdate']) : null;
        $workout->enddate = isset($_POST['e20r-workout-enddate']) ? $e20rTracker->sanitize($_POST['e20r-workout-enddate']) : null;
        $workout->startday = isset($_POST['e20r-workout-startday']) ? $e20rTracker->sanitize($_POST['e20r-workout-startday']) : null;
        $workout->endday = isset($_POST['e20r-workout-endday']) ? $e20rTracker->sanitize($_POST['e20r-workout-endday']) : null;

        $test = (array)$exData;

        if (!empty($test)) {

            foreach ($groupData as $key => $groupNo) {

                $groups[$groupNo]->group_set_count = $groupSetCount[$groupNo];
                $groups[$groupNo]->group_tempo = $groupSetTempo[$groupNo];
                $groups[$groupNo]->group_rest = $groupSetRest[$groupNo];

                if (isset($exData[$key])) {
                    dbg("e20rWorkout::saveSettings() - Adding exercise data from new definition");
                    $groups[$groupNo]->exercises[$orderData[$key]] = $exData[$key];
                }

                if ((count($workout->groups[$groupNo]->exercises) > 1) &&
                    (isset($workout->groups[$groupNo]->exercises[0]))
                ) {

                    dbg("e20rWorkout::saveSettings() - Clearing data we don't need");
                    unset($groups[$groupNo]->exercises[$orderData[$key]][0]);
                }

            }
            dbg("e20rWorkout::saveSettings() - Groups:");
            // dbg($groups);
        }

        // Add workout group data/settings
        $workout->groups = $groups;

        dbg('e20rWorkout::saveSettings() - Workout data to save:');
        // dbg($workout);

        if ($this->model->saveSettings($workout)) {

            dbg('e20rWorkout::saveSettings() - Saved settings/metadata for this e20r_workout CPT');
            return $post_id;
        } else {
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
    public function getActivityArchive($userId, $programId, $period = E20R_CURRENT_WEEK)
    {

        global $e20rProgram;
        global $e20rTracker;
        global $e20rArticle;

        global $currentProgram;

        $startedTS = $e20rProgram->startdate($userId, $programId, true);
        $started = date('Y-m-d H:i:s', $startedTS);
        // $started = $currentProgram->startdate;

        $currentDay = $e20rTracker->getDelay('now', $userId);
        $currentDate = date('Y-m-d', current_time('timestamp'));

        dbg("e20rWorkout::getActivityArchive() - User ({$userId}) started program ({$programId}) on: {$started}");

        // Calculate release_days to include for the $period
        switch ($period) {

            case E20R_UPCOMING_WEEK:
                dbg("e20rWorkout::getActivityArchive() - For the upcoming (next) week");

                if (date('N', current_time('timestamp')) == 7) {
                    $mondayTS = strtotime("next monday {$currentDate}");
                    $sundayTS = strtotime("next sunday {$currentDate}");
                } else {
                    $mondayTS = strtotime("monday next week {$currentDate} ");
                    $sundayTS = strtotime("sunday next week {$currentDate}");
                }

                $period_string = "Activities next week";
                if (date('N', current_time('timestamp')) <= 5) {
                    dbg("e20rWorkout::getActivityArchive() - Monday: {$mondayTS}, Sunday: {$sundayTS}, day number today: " . date('N'));
                    dbg("e20rWorkout::getActivityArchive() - User requested archive for 'next week', but we've not yet reached Friday, so not returning anything");
                    return null;
                }

                break;

            case E20R_PREVIOUS_WEEK:

                dbg("e20rWorkout::getActivityArchive() - For last week");
                if (date('N', current_time('timestamp')) == 7) {
                    $mondayTS = strtotime("monday -2 weeks {$currentDate}");
                    $sundayTS = strtotime("last sunday {$currentDate}");
                } else {
                    $mondayTS = strtotime("monday last week {$currentDate}");
                    $sundayTS = strtotime("last sunday {$currentDate}");
                }

                $period_string = "Activities previous week";
                break;

            case E20R_CURRENT_WEEK:

                dbg("e20rWorkout::getActivityArchive() - For the current week including: {$currentDate}");

                if (date('N', current_time('timestamp')) == 1) {
                    // It's monday

                    $mondayTS = strtotime("monday {$currentDate}");
                    $sundayTS = strtotime("this sunday {$currentDate}");
                } else {

                    $mondayTS = strtotime("last monday {$currentDate}");
                    $sundayTS = strtotime("this sunday {$currentDate}");
                }

                $period_string = "Activities this week";
                break;

            default:
                return null;
        }

//        $startDelay = ($startDelay + $currentDay);
//        $endDelay = ( $endDelay + $currentDay );

        dbg("e20rWorkout::getActivityArchive() - Monday TS: {$mondayTS}, Sunday TS: {$sundayTS}");
        $startDelay = $e20rTracker->daysBetween($startedTS, $mondayTS, get_option('timezone_string'));
        $endDelay = $e20rTracker->daysBetween($startedTS, $sundayTS, get_option('timezone_string'));

        if ($startDelay < 0) {
            $startDelay = 1;
        }

        if ($endDelay <= 0) {
            $endDelay = 6;
        }


        dbg("e20rWorkout::getActivityArchive() - Delay values -- start: {$startDelay}, end: {$endDelay}");
        $val = array($startDelay, $endDelay);

        // Load articles in the program that have a release day value between the start/end delay values we calculated.
        $articles = $e20rArticle->findArticles('release_day', $val, $programId, 'BETWEEN', true);

        dbg("e20rWorkout::getActivityArchive() - Found " . count($articles) . " articles");
        // dbg($articles);

        $activities = array('header' => $period_string);
        $unsorted = array();

        // Pull out all activities for the sequence list
        if (!is_array($articles) && (false !== $articles)) {

            $articles = array($articles);
        }

        foreach ($articles as $id => $article) {

            // Save activity list as a hash w/weekday => workout )
            dbg("e20rWorkout::getActivityArchive() - Getting " . count($article->activity_id) . " activities for article ID: {$article->id}");
            if (count($article->activity_id) != 0) {
                $act = $this->find('id', $article->activity_id, $programId, 'IN');

                foreach ($act as $a) {
                    dbg("e20rWorkout::getActivityArchive() - Pushing {$a->id} to array to be sorted");
                    $unsorted[] = $a;
                }
            } else {
                dbg("e20rWorkout::getActivityArchive() - No activities defined for article {$article->id}, moving on.");
            }
        }

        /*        }
                else {
                    dbg("e20rWorkout::getActivityArchive() - Single Article, activity ID: {$articles->activity_id}");
                    $unsorted[] = $this->getActivity( $articles->activity_id );
                }
        */

        dbg("e20rWorkout::getActivityArchive() - Have " . count($unsorted) . " workout objects to process/sort");

        // Save activities in an hash keyed on the weekday the activity is scheduled for.
        foreach ($unsorted as $activity) {

            $mon = date('l', strtotime('monday'));

            foreach ($activity->groups as $gID => $group) {

                $group->group_tempo = $this->model->getType($group->group_tempo);
                $activity->groups[$gID] = $group;
            }

            foreach ($activity->days as $dayNo) {

                $dNo = $dayNo;
                $day = date('l', strtotime("monday + " . ($dNo - 1) . " days"));
                dbg("e20rWorkout::getActivityArchive() - Saving workout for weekday: {$day} -> ID: {$activity->id}");

                $activities[$dNo] = $activity;
            }
        }


        // Sort based on day id
        ksort($activities);

        // Return the hash of activities to the calling function.
        return $activities;
    }

    public function getPeers($workoutId = null)
    {

        if (is_null($workoutId)) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $workoutId = $post->post_parent;
        }

        $workouts = new WP_Query(array(
            'post_type' => 'page',
            'post_parent' => $workoutId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ));

        $workoutList = array(
            'pages' => $workouts->posts,
        );

        foreach ($workoutList->posts as $k => $v) {

            if ($v == get_the_ID()) {

                if (isset($workouts->posts[$k - 1])) {

                    $workoutList['prev'] = $workouts->posts[$k - 1];
                }

                if (isset($workouts->posts[$k + 1])) {

                    $workoutList['next'] = $workouts->posts[$k + 1];
                }
            }
        }

        wp_reset_postdata();

        return $workoutList;
    }

    public function addMeta_WorkoutSettings()
    {

        // global $post;
        global $currentWorkout;

        dbg("e20rWorkout::addMeta_WorkoutSettings() - Loading settings metabox for workout page: " . $currentWorkout->id);
        // $this->init( $post->ID );
        // $currentWorkout = $this->model->find( 'id', $post->ID );

        if (!empty($currentWorkout)) {

            dbg("e20rWorkout::addMeta_WorkoutSettings() - Loaded a workout with settings...");
            echo $this->view->viewSettingsBox($currentWorkout);
        } else {

            dbg("e20rWorkout::addMeta_WorkoutSettings() - Loaded an empty/defaul workout definition...");
            echo $this->view->viewSettingsBox($this->model->defaultSettings());
        }

    }

    public function getActivities($aIds = null)
    {

//        global $currentProgram;

        if (empty($aIds)) {
            dbg('e20rWorkout::getActivities() - Loading all activities from DB');
            $activities = $this->model->find('id', 'any'); // Will return all of the defined activities
        } /*        elseif ( is_array( $aIds ) ) {
            dbg("e20rWorkout::getActivities() - Supplied list of activity IDs, using 'IN' search");
            $activities = $this->model->find( 'delay', $aIds, $currentProgram->id, 'IN' );
        } */
        else {
            dbg('e20rWorkout::getActivities() - Loading specific activity from DB');
            $activities = $this->model->find('id', $aIds);
        }

        dbg("e20rWorkout::getActivities() - Found " . count($activities) . " activities.");

        return $activities;
    }

    public function prepare_activity($config)
    {

        global $current_user;

        global $e20rProgram;
        global $e20rArticle;
        global $e20rTracker;

        global $currentProgram;
        global $currentArticle;

        $config->userId = (!isset($config->userId) ? $current_user->ID : $config->userId);
        $config->programId = (!isset($currentProgram->id) ? $e20rProgram->getProgramIdForUser($config->userId) : $currentProgram->id);
        $config->startTS = strtotime($currentProgram->startdate);
        $config->userGroup = $e20rTracker->getGroupIdForUser($config->userId);
        $config->expanded = false;
        $config->activity_override = false;
        $config->show_tracking = 1;
        $config->dayNo = date_i18n('N', current_time('timestamp'));

        // $config->hide_input = ( $tmp['hide_input'] == 0 ? false : true );

        dbg($config);
        dbg($_POST);

        $actId_from_dash = isset($_POST['activity-id']) ? array( $e20rTracker->sanitize($_POST['activity-id']) ) : array();
        $act_override = isset($_POST['activity-override']) ? $e20rTracker->sanitize($_POST['activity-override']) : false;

        // Make sure we won't load anything but the short code requested activity
        if (empty($config->activity_id)) {

            dbg("e20rWorkout::prepare_activity() - No user specified activity ID in short code config");
            // dbg($_POST);

            // Check whether we go called via the dashboard and an activity Id is given to us from there.
            if ( (empty($config->activity_id) && !empty($actId_from_dash)) ||
                ( false !== $act_override  && !empty($actId_from_dash) ) )  {

                $articleId = isset($_POST['article-id']) ? $e20rTracker->sanitize($_POST['article-id']) : null;
                $checkin_date = isset($_POST['for-date']) ? $e20rTracker->sanitize($_POST['for-date']) : null;

                dbg("e20rWorkout::prepare_activity() - Original activity ID is: " . (isset($config->activity_id) ? $config->activity_id : 'Not defined'));
                dbg("e20rWorkout::prepare_activity() - Dashboard requested " . count($actId_from_dash). " specific activity ID(s)");

                $config->activity_override = true;
                $config->activity_id = $actId_from_dash;

                if (!isset($currentArticle->id) || ($currentArticle->id != $articleId)) {

                    dbg("e20rWorkout::prepare_activity() - Loading article with id {$articleId}");
                    $e20rArticle->init($articleId);
                }

                $config->date = $checkin_date;
                $config->delay = $e20rTracker->getDelay($config->date, $config->userId);
                $config->dayNo = date_i18n('N', strtotime( $config->date ) );

                dbg("e20rWorkout::prepare_activity() - Overridden configuration: ");
                dbg($config);
            }
        }

        if (!isset($config->delay) || empty($config->delay)) {
            $config->delay = $e20rTracker->getDelay('now');
        }

        if (!isset($config->date) || empty($config->date)) {
            $config->date = $e20rTracker->getDateForPost($config->delay);
        }

        // dbg( $config );

        dbg("e20rWorkout::prepare_activity() - Using delay: {$config->delay} which gives date: {$config->date} for program {$config->programId}");
        // dbg( $config->activity_id );
        // If the activity ID is set, don't worry about anything but loading that activity (assuming it's permitted).

        if (!empty($config->activity_id)) {

            dbg("e20rWorkout::prepare_activity() - Admin specified activity ID. Using array of activity ids with " . count($config->activity_id) . " included activities");
            $articles = $e20rArticle->findArticles('activity_id', $config->activity_id, $config->programId, 'IN', true);

        } else {

            dbg("e20rWorkout::prepare_activity() - Attempting to locate article by configured delay value: {$config->delay}");
            $articles = $e20rArticle->findArticles('release_day', $config->delay, $config->programId);

            /*            if ( false !== $articles ) {

                            dbg("e20rWorkout::prepare_activity() - Found the article ID {$articleId} based on the delay value: {$config->delay}" );
                            $articles = $e20rArticle->findArticles( 'id', $articleId, $config->programId, 'IN' );
                        }
            */
        }

        // dbg("e20rWorkout::prepare_activity() - (Hopefully located) article: ");
        // dbg($article);


        if (!is_array($articles)) {

            dbg("e20rWorkout::prepare_activity() - No articles found!");
            $articles = array($e20rArticle->emptyArticle());
        }

        // Process all articles we've found.
        foreach ($articles as $a_key => $article) {

            if ( (true === $config->activity_override) && $config->delay != $article->release_day ) {
                dbg("e20rWorkout::prepare_activity() - Skipping {$article->id} because its delay value is incorrect: {$config->delay} vs {$article->release_day}");
                continue;
            }

            dbg("e20rWorkout::prepare_activity() - Processing article ID {$article->id}");

            // if ( isset( $article->activity_id ) && ( !empty( $article->activity_id) ) ) {

            dbg("e20rWorkout::prepare_activity() - Activity count for article: " . (isset($article->activity_id) ? count($article->activity_id) : 0));

            $workoutData = $this->model->find('id', $article->activity_id, $config->programId, 'IN');

            foreach ($workoutData as $k => $workout) {

                dbg("e20rWorkout::prepare_activity() - Iterating through the fetched workout IDs. Now processing workoutData entry {$k}");
                // dbg($workout);

                if (!in_array($config->programId, $workoutData[$k]->program_ids)) {

                    dbg("e20rWorkout::prepare_activity() - The workout is not part of the same program as the user - {$config->programId}: ");
                    unset($workoutData[$k]);
                }

                if ( isset($config->dayNo) && !in_array( $config->dayNo, $workout->days )) {

                    dbg("e20rWorkout::prepare_activity() - The specified day number ({$config->dayNo}) is not one where {$workout->id} is scheduled to be used. Today is: " . date('N'));
                    unset($workoutData[$k]);
                    unset($articles[$a_key]);
                }

                $has_access = array();

                if (!empty($workoutData[$k]->assigned_user_id) || !empty($workoutData[$k]->assigned_usergroups)) {

                    dbg("e20rWorkout::prepare_activity() - User Group or user list defined for this workout...");
                    $has_access = $e20rTracker->allowedActivityAccess($workoutData[$k], $config->userId, $config->userGroup);

                    if (!in_array(true, $has_access)) {

                        dbg("e20rWorkout::prepare_activity() - current user is NOT listed as a member of this activity: {$config->userId}");
                        dbg("e20rWorkout::prepare_activity() - The activity is not part of the same group(s) as the user: {$config->userGroup}: ");

                        unset($workoutData[$k]);
                        unset($articles[$a_key]);
                    }
                }
            }
        }


        $config->articleId = $currentArticle->id;
        $recorded = array();

        dbg("e20rWorkout::prepare_activity() - WorkoutData prior to processing");

        foreach ($workoutData as $k => $w) {

            dbg("e20rWorkout::prepare_activity() - Processing workoutData entry {$k} to test whether to load user data");

            if ($k !== 'error') {

                dbg("e20rWorkout::prepare_activity() - Attempting to load user specific workout data for workoutData entry {$k}.");
                $saved_data = $this->model->getRecordedActivity($config, $w->id);

                if ((false == $config->activity_override) && isset($w->days) && (!empty($w->days)) && (!in_array($config->dayNo, $w->days))) {

                    dbg("e20rWorkout::prepare_activity() - day {$config->dayNo} on day {$config->delay} is wrong for this specific workout/activity #{$w->id}");
                    dbg($w->days);
                    dbg("e20rWorkout::prepare_activity() - Removing workout ID #{$w->id} as a result");
                    unset($workoutData[$k]);
                } else {

                    foreach ($w->groups as $gid => $g) {

                        if (!empty($saved_data)) {

                            dbg("e20rWorkout::prepare_activity() - Integrating saved data for group # {$gid}");
                            $workoutData[$k]->groups[$gid]->saved_exercises = isset($saved_data[$gid]->saved_exercises) ? $saved_data[$gid]->saved_exercises : array();
                        }


                        if (isset($g->group_tempo)) {
                            dbg("e20rWorkout::prepare_activity() - Setting the tempo identifier");
                            $workoutData[$k]->groups[$gid]->group_tempo = $this->model->getType($g->group_tempo);
                        }
                    }
                }
            }
        }

        if (empty($workoutData)) {
            $workoutData['error'] = 'No Activity found';
        }

        ob_start(); ?>
        <div id="e20r-daily-activity-page">
            <?php echo $this->view->display_printable_activity($config, $workoutData); ?>
        </div> <?php

        $html = ob_get_clean();

        return $html;
    }

    /**
     * For the e20r_activity_archive shortcode.
     *
     * @param null $attributes
     * @return html
     * @since 0.8.0
     */
    public function shortcode_act_archive($attributes = null)
    {

        dbg("e20rWorkout::shortcode_act_archive() - Loading shortcode data for the activity archive.");

        global $current_user;
        global $currentProgram;

        if (!is_user_logged_in()) {

            auth_redirect();
        }

        $config = new stdClass();
        $config->userId = $current_user->ID;
        $config->programId = $currentProgram->id;
        $config->expanded = false;
        $config->show_tracking = 0;
        $config->phase = 0;
        $config->print_only = null;

        $workoutData = array();

        $tmp = shortcode_atts(array(
            'period' => 'current',
            'print_only' => null,
        ), $attributes);

        foreach ($tmp as $key => $val) {

            if (!empty($val)) {
                $config->{$key} = $val;
            }
        }

        // Valid "false" responses for print_only atribute can include: array( 'no', 'false', 'null', '0', 0, false, null );
        $true_responses = array('yes', 'true', '1', 1, true);

        if (in_array($config->print_only, $true_responses)) {
            dbg("e20rWorkout::shortcode_act_archive() - User requested the archive be printed (i.e. include all unique exercises for the week)");
            $config->print_only = true;
        } else {
            dbg("e20rWorkout::shortcode_act_archive() - User did NOT request the archive be printed");
            $config->print_only = false;
        }

        if ('current' == $config->period) {
            $period = E20R_CURRENT_WEEK;
        }

        if ('previous' == $config->period) {
            $period = E20R_PREVIOUS_WEEK;
        }

        if ('next' == $config->period) {
            $period = E20R_UPCOMING_WEEK;
        }

        dbg("e20rWorkout::shortcode_act_archive() - Period set to {$config->period}.");

        $activities = $this->getActivityArchive($current_user->ID, $currentProgram->id, $period);

        dbg("e20rWorkout::shortcode_act_archive() - Check whether we're generating the list of exercises for print only: " . ($config->print_only ? 'Yes' : 'No'));

        if (true === $config->print_only) {

            $exercises = array(); // '$exercise_id' => $exercise_definition
            dbg("e20rWorkout::shortcode_act_archive() - User requested this activity archive be printed. Listing unique exercises.");
            // dbg( $activities );
            $printable = array();
            $already_processed = array();

            foreach ($activities as $key => $workout) {

                if ('header' !== $key && (!in_array($workout->id, $already_processed))) {

                    $routine = new stdClass();

                    if ((0 == $config->phase) || ($config->phase < $workout->phase)) {

                        $routine->phase = $workout->phase;
                        dbg("e20rWorkout::shortcode_act_archive() - Setting phase number for the archive: {$config->phase}.");
                    }

                    $routine->id = $workout->id;
                    $routine->name = $workout->title;
                    $routine->description = $workout->excerpt;
                    $routine->started = $workout->startdate;
                    $routine->ends = $workout->enddate;
                    $routine->days = $workout->days;

                    $list = array();

                    foreach ($workout->groups as $grp) {

                        dbg("e20rWorkout::shortcode_act_archive() - Adding " . count($grp->exercises) . " to list of exercises for routine # {$routine->id}");
                        $list = array_merge($list, $grp->exercises);
                    }

                    $routine->exercises = array_unique($list, SORT_NUMERIC);

                    dbg("e20rWorkout::shortcode_act_archive() - Total number of exercises for  routine #{$routine->id}: " . count($routine->exercises));
                    $already_processed[] = $routine->id;
                    $printable[] = $routine;
                }
            }

            dbg("e20rWorkout::shortcode_act_archive() - Will display " . count($printable) . " workouts and their respective exercises for print");
            return $this->view->display_printable_list($printable, $config);
        }

        dbg("e20rWorkout::shortcode_act_archive() - Grabbed activity count: " . count($activities));

        echo $this->view->displayArchive($activities, $config);
        // dbg($activities);
    }

    public function shortcode_activity($attributes = null)
    {

        dbg("e20rWorkout::shortcode_activity() - Loading shortcode data for the activity.");

        dbg($_REQUEST);

        if (!is_user_logged_in()) {

            auth_redirect();
        }

        $config = new stdClass();
        $config->show_tracking = 1;
        $config->display_type = 'row';
        $config->print_only = null;

        $tmp = shortcode_atts(array(
            'activity_id' => null,
            'show_tracking' => 1,
            'display_type' => 'row', // Valid types: 'row', 'column', 'print'
        ), $attributes);

        // dbg( $tmp );

        foreach ($tmp as $key => $val) {

            if (('activity_id' == $key) && (!is_null($val))) {
                $val = array($val);
            }

            /*            if ( 'hide_input' == $key ) {

                            $val = ( $val == 0 ? 0 : 1 );
                        } */

            if (!is_null($val)) {
                // dbg("e20rWorkout::shortcode_activity() - Setting {$key} to {$val}");
                $config->{$key} = $val;
            }
        }

        if (!in_array($config->show_tracking, array('yes', 'no', 'true', 'false', 1, 0))) {

            dbg("e20rWorkout::shortcode_activity() - User didn't specify a valid display_type in the shortcode!");
            return '<div class="error">Incorrect show_tracking value in the e20r_activity shortcode! (Valid values are: "yes", "no", "true", "false", "1", "0")</div>';

        }
        if (!in_array($config->display_type, array('row', 'column', 'print'))) {

            dbg("e20rWorkout::shortcode_activity() - User didn't specify a valid display_type in the shortcode!");
            return '<div class="error">Incorrect display_type value in the e20r_activity shortcode! (Valid values are "row", "column", "print")</div>';
        }

        if (in_array(strtolower($config->show_tracking), array('no', 'false', '0'))) {

            $config->show_tracking = 0;
        }

        if ('print' === $config->display_type) {

            $config->print_only = true;
        }

        echo $this->prepare_activity($config);
    }

    public function getMemberGroups()
    {
        $memberGroups = apply_filters( 'e20r-tracker-configured-roles', array() );
            
        // For Paid Memberships Pro.
/*
        if (function_exists('pmpro_getAllLevels')) {

            $memberships = pmpro_getAllLevels();

            foreach ($memberships as $mId => $mInfo) {
                $memberGroups[$mId] = $mInfo->name;
            }
        }
*/
        return $memberGroups;
    }

    public function workout_attributes_dropdown_pages_args($args, $post)
    {

        if ('e20r_workout' == $post->post_type) {
            dbg('e20rWorkout::changeSetParentType()...');
            $args['post_type'] = 'e20r_workout';
        }

        return $args;
    }

    public function add_new_exercise_to_group_callback()
    {

        dbg("e20rWorkout::add_new_exercise_to_group_callback() - add_to_group data");

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce');

        global $e20rTracker;
        global $e20rExercise;

        dbg("e20rWorkout::add_new_exercise_to_group_callback() - Received POST data:");
        dbg($_POST);

        $exerciseId = isset($_POST['e20r-exercise-id']) ? $e20rTracker->sanitize($_POST['e20r-exercise-id']) : null;

        if ($exerciseId) {

            $exerciseData = $e20rExercise->getExerciseSettings($exerciseId);

            // Replace the $type variable before sending to frontend (make it comprehensible).
            $exerciseData->type = $e20rExercise->getExerciseType($exerciseData->type);

            dbg("e20rWorkout::add_new_exercise_to_group_callback() - loaded Workout info: ");

            wp_send_json_success($exerciseData);
        }

        wp_send_json_error("Unknown error processing new exercise request.");
    }

    public function add_new_exercise_group_callback()
    {

        dbg("e20rWorkout::add_new_exercise_group_callback() - addGroup data");

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-workout-settings-nonce');

        global $e20rTracker;

        dbg("e20rWorkout::add_new_exercise_group_callback() - Received POST data:");
        dbg($_POST);

        $groupId = isset($_POST['e20r-workout-group-id']) ? $e20rTracker->sanitize($_POST['e20r-workout-group-id']) : null;

        if (!$groupId) {
            wp_send_json_error('Unable to add more groups. Please contact support!');
        }

        dbg("e20rWorkout::add_new_exercise_group_callback() - Adding clean/default workout settings for new group. ID={$groupId}.");

        $workout = $this->model->defaultSettings();
        $data = $this->view->newExerciseGroup($workout->groups[0], $groupId);

        if ($data) {

            dbg("e20rWorkout::add_new_exercise_group_callback() - New group table completed. Sending...");
            wp_send_json_success(array('html' => $data));
        } else {

            dbg("e20rWorkout::add_new_exercise_group_callback() - No data (not even the default values!) generated.");
            wp_send_json_error("Error: Unable to generate new group");
        }
    }
} 