<?php

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */
class e20rAction extends e20rSettings
{

    private $checkin = array();

    protected $model;
    protected $view;

    // checkin_type: 0 - action (habit), 1 - lesson, 2 - activity (workout), 3 - survey
    // "Enum" for the types of check-ins
    protected $types = array(
        'none' => 0,
        'action' => CHECKIN_ACTION, // 1
        'assignment' => CHECKIN_ASSIGNMENT, // 2
        'survey' => CHECKIN_SURVEY, // 3
        'activity' => CHECKIN_ACTIVITY, // 4
        'note' => CHECKIN_NOTE // 5
    );

    // checkedin values: 0 - false, 1 - true, 2 - partial, 3 - not applicable
    // "Enum" for the valid statuses.
    private $status = array(
        'no' => 0,
        'yes' => 1,
        'partial' => 2,
        'na' => 3
    );

    public function __construct()
    {

        dbg("e20rAction::__construct() - Initializing Action class");

        $this->model = new e20rActionModel();
        $this->view = new e20rActionView();

        parent::__construct('action', 'e20r_actions', $this->model, $this->view);
    }

    public function getTypeDescr($typeId)
    {

        $descr = array_search($typeId, $this->types);

        if (is_string($descr)) {
            return $descr;
        }

        return null;
    }

    public function set_custom_edit_columns($columns)
    {

        unset($columns['post_type']);

        $columns['e20r_checkin_type'] = __("Action Type", "e20rtracker");
        return $columns;
    }

    public function custom_column($column, $post_id)
    {

        if ($column == 'e20r_checkin_type') {

            $typeId = get_post_meta($post_id, '_e20r-checkin-checkin_type', true);

            dbg("e20rAction::custom_column() - Type ID: {$typeId}");
            $type = $this->getTypeDescr($typeId);

            dbg("e20rAction::custom_column() - Type Name: {$type}");
            echo ucfirst($type);
        }
    }

    public function sortable_column($columns)
    {
        $columns['e20r_checkin_type'] = 'e20r_checkin_type';

        return $columns;
    }

    public function sort_column($query) {

        if( $query->is_main_query() && is_admin() && ( $orderby = $query->get('orderby'))) {

            switch( $orderby ) {
                case 'e20r_checkin_type':
                    $query->set('meta_key', '_e20r-action-checkin_type');
                    $query->set('orderby', 'meta_value_num');
                    break;
            }
        }
    }
    public function hasCompletedLesson($articleId, $postId = null, $userId = null, $delay = null)
    {

        dbg("e20rAction::hasCompletedLesson() - Verify whether the current UserId has checked in for this lesson.");

        global $currentArticle;
        global $currentProgram;

        global $e20rArticle;
        global $e20rProgram;

        $config = new stdClass();

        if (is_null($postId)) {

            global $post;
            $postId = (isset($post->ID) ? $post->ID : null);
        }

        if (empty($currentProgram->id)) {

            $e20rProgram->getProgramIdForUser($userId);
        }

        dbg("e20rAction::hasCompletedLesson() - Requested post ID #{$postId} v.s. currentArticle post_id: {$currentArticle->post_id}");

        if (isset($currentArticle->post_id) && ($currentArticle->post_id != $postId)) {

            dbg("e20rAction::hasCompletedLesson() - loading settings for article post #{$postId} (ID)");
            $e20rArticle->init($postId);
        }

        $config->articleId = $currentArticle->id;

        if (!isset($config->articleId)) {

            dbg("e20rAction::hasCompletedLesson() - No article ID defined..? Exiting.");
            return false;
        }

        if ($delay !== null) {

            $config->delay = $delay;
        } else {
            $config->delay = $e20rArticle->releaseDay($config->articleId);
        }

        dbg("e20rAction::hasCompletedLesson() - Check if the database indicates a completed lesson...");
        $checkin = $this->model->get_user_checkin($config, $userId, CHECKIN_ASSIGNMENT);

        if (isset($checkin->checkedin) && ($checkin->checkedin == 1)) {

            dbg("e20rAction::hasCompletedLesson() - User has completed this check-in.");
            return true;
        } else {

            dbg("e20rAction::hasCompletedLesson() - No user check-in found.");
            return false;
        }

        // return $this->model->lessonComplete( $this->articleId );

    }

    public function hasCheckedIn($userId, $articleId, $type = CHECKIN_ASSIGNMENT)
    {

        global $wpdb;
        global $currentArticle;
        global $currentProgram;

        global $current_user;
        global $e20rTracker;
        global $e20rTables;
        global $e20rProgram;
        global $e20rAction;

        if (is_null($userId)) {

            $userId = $current_user->ID;
        }

        if (empty($currentProgram->id)) {

            $e20rProgram->getProgramIdForUser($userId);

        }
        if ((empty($currentArticle)) || ($currentArticle->id != $articleId)) {

            dbg("e20rArticleModel::lessonComplete() - loading settings for article: {$articleId} (ID)");
            $currentArticle = $this->loadSettings($articleId);
        }

        $sql = $wpdb->prepare("
	    		    SELECT checkedin
	    		    FROM $e20rTables->getTable('action')
	    		    WHERE article_id = %d AND user_id = %d AND
	    		    	program_id = %d AND checkin_type =



	    ");
    }

    public function get_shortname($checkin_id)
    {

        global $currentAction;

        $this->init($checkin_id);

        return $currentAction->short_name;
    }

    public function findCheckinItemId($articleId)
    {

        global $e20rArticle;
    }

    public function setArticleAsComplete($userId, $articleId)
    {

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        // $articleId = $e20rArticle->init( $articleId );
        $programId = $e20rProgram->getProgramIdForUser($userId);

        $defaults = array(
            'user_id' => $userId,
            'checkedin' => $this->status['yes'],
            'article_id' => $articleId,
            'program_id' => $programId,
            'checkin_date' => $e20rArticle->releaseDate($articleId),
            'checkin_short_name' => null,
            'checkin_type' => $this->types['action'],
            'checkin_note' => null,
        );

        if ($this->model->setCheckin($defaults)) {
            dbg("e20rAction::setArticleAsComplete() - Check-in for user {$userId}, article {$articleId} in program {$programId} has been saved");
            return true;
        }

        dbg("e20rAction::setArticleAsComplete() - Unable to save check-in value!");
        return false;
    }

    private function count_actions($action_list, $type, $start_date, $end_date)
    {

        $action_count = 0;

        foreach ($action_list as $action) {

            $comp_date = date('Y-m-d', strtotime($action->checkin_date));

            if (($action->checkin_type == $type) && ($action->checkedin == 1) &&
                ($comp_date >= $start_date) && ($comp_date <= $end_date)
            ) {

                $action_count += 1;
            }
        }

        dbg("e20rAction::count_actions() - Counted {$action_count} completed actions of type {$type} between {$start_date} and {$end_date}");
        return $action_count;
    }

    private function days_of_action($checkin)
    {

        global $e20rTracker;

        if ($checkin->enddate <= date('Y-m-d', current_time('timestamp'))) {

            $days_to_add = $checkin->maxcount;
        } else {
            // Calculate the # of days passed since start of current action
            $days_to_add = $e20rTracker->daysBetween(strtotime($checkin->startdate . " 00:00:00"), current_time('timestamp'), get_option('timezone_string'));
        }

        return $days_to_add;
    }

    public function listUserAccomplishments($userId)
    {

        global $currentProgram;
        global $current_user;

        global $e20rArticle;
        global $e20rAssignment;

        global $e20rProgram;
        global $e20rTracker;

        // $config = new stdClass();

        if ($userId != $current_user->ID) {

            dbg("e20rAction::listUserAccomplishments() - Validate that the current user has rights to access this data!");
            if (!$e20rTracker->is_a_coach($current_user->ID)) {

                return null;
            }

        }

        $user_delay = $e20rTracker->getDelay('now', $userId);

        if (empty($currentProgram->id)) {

            $e20rProgram->getProgramIdForUser($userId);
        }

        $programId = $currentProgram->id;

        $art_list = $e20rArticle->findArticles('release_day', $user_delay, $programId, '<=');

        dbg("e20rAction::listUserAccomplishments() - Loading accomplishments related to " . count($art_list) . " articles related to user ({$userId}) in program {$programId}");

        dbg("e20rAction::listUserAccomplishments() - Article list loaded: " . count($art_list) . " articles");
        // dbg($art_list);

        if (empty($art_list) || (0 == count($art_list))) {

            dbg("e20rAction::listUserAccomplishments() - No articles to check against.");

            $results = array();

            $results['program_days'] = 0; //$user_delay;
            $results['program_score'] = 0;

            return $this->view->view_user_achievements($results);
        }

        dbg("e20rAction::listUserAccomplishments() - Loaded " . count($art_list) . " articles between start of program #{$programId} and day #{$user_delay}");

        $results = array();
        $aIds = array();
        $dates = array();
        $delays = array();
        $actions = array();

        // Get all articleIds to look for:
        foreach ($art_list as $article) {

            if (isset($article->id) /* &&  ( isset( $article->is_preview_day) && ( 0 == $article->is_preview_day ) ) */ ) {

                if ( 0 < $article->release_day ) {

                    $aIds[] = $article->id;
                    $delays[] = $article->release_day;

                    // dbg("e20rAction::listUserAccomplishments() - Article {$article->id} has a negative release day: {$article->release_day}");
                }
            }
        }

        if (!empty($delays)) {

            // Sort the delays (to find min/max delays)
            sort($delays, SORT_NUMERIC);

            $dates['min'] = $e20rTracker->getDateForPost($delays[0], $userId);
            $dates['max'] = $e20rTracker->getDateForPost($delays[(count($delays) - 1)], $userId);
        } else {
            $dates['min'] = date('Y-m-d', current_time('timestamp'));
            $dates['max'] = date('Y-m-d', current_time('timestamp'));
        }

        dbg("e20rAction::listUserAccomplishments() - Dates between: {$dates['min']} and {$dates['max']}");

        // Get an array of actions & Activities to match the max date for the $programId
        $cTypes = array($this->types['action'], $this->types['activity']);
        $curr_action_ids = $this->model->findActionByDate($e20rTracker->getDateForPost($user_delay, $userId), $programId);

        foreach ($curr_action_ids as $id) {

            $type = $this->model->getSetting($id, 'checkin_type');

            switch ($type) {

                case CHECKIN_ACTION:
                    $type_string = 'action';
                    break;

                case CHECKIN_ACTIVITY:
                    $type_string = 'activity';
                    break;

                case CHECKIN_ASSIGNMENT:
                    $type_string = 'assignment';
                    break;

                default:
                    dbg("e20rAction::getAllUserAccomplishments() - No activity type specified in record! ($id)");
            }

            // Get all actions of this type.
            $actions[$type_string] = $this->model->getActions($id, $type, -1);

        }

        $results['program_days'] = 0; //$user_delay;
        $results['program_score'] = 0;

        if (!empty($aIds)) {
            dbg("e20rAction::listUserAccomplishments() - Loaded " . count($actions) . " defined actions. I.e. all possible 'check-ins' for this program so far.");
            $checkins = $this->model->loadCheckinsForUser($userId, $aIds, $cTypes, $dates);
            $lessons = $this->model->loadCheckinsForUser($userId, $aIds, array($this->types['assignment']), $dates);
        }

        foreach ($actions as $type => $a_list) {

            foreach ($a_list as $action) {

                // Skip
                if ($action->checkin_type != $this->types['action']) {

                    dbg("e20rAction::listUserAccomplishments() - Skipping {$action->id} since it's not an action/habit: {$this->types['action']}");
                    dbg($action);
                    continue;
                }

                $count = 0;

                $action_count = 0;
                $activity_count = 0;
                $assignment_count = 0;

                $results[$action->startdate] = new stdClass();
                $results[$action->startdate]->actionText = $action->item_text;
                $results[$action->startdate]->days = $this->days_of_action($action);

                dbg("e20rAction::listUserAccomplishments() - Processing " . count($checkins) . " actions");
                $action_count = $this->count_actions($checkins, $this->types['action'], $action->startdate, $action->enddate);

                dbg("e20rAction::listUserAccomplishments() - Processing " . count($checkins) . " activities");
                $activity_count = $this->count_actions($checkins, $this->types['activity'], $action->startdate, $action->enddate);

                dbg("e20rAction::listUserAccomplishments() - Processing " . count($lessons) . " assignments");
                $assignment_count = $this->count_actions($lessons, $this->types['assignment'], $action->startdate, $action->enddate);

                $avg_score = 0;

                foreach (array('action', 'activity', 'assignment') as $key) {

                    $var_name = "{$key}_count";

                    $results[$action->startdate]->{$key} = new stdClass();
                    $score = round((${$var_name} / $results[$action->startdate]->days), 2);
                    $badge = null;

                    if (($score >= 0.7) && ($score < 0.8)) {

                        $badge = 'bronze';
                    } elseif (($score >= 0.8) && ($score < 0.9)) {

                        $badge = 'silver';
                    } elseif (($score >= 0.9) && ($score <= 1.0)) {

                        $badge = 'gold';
                    }

                    $results[$action->startdate]->{$key}->badge = $badge;
                    $results[$action->startdate]->{$key}->score = $score;
                    $avg_score += $score;
                }


                $results['program_days'] += $results[$action->startdate]->days;
                $avg_score = ($avg_score / 3);

                // All $action->shortname entries minus the two program_* entries in the array.
                $result_count = count($results) - 2;

                // Set the overall program score for this user.
                $results['program_score'] = ($results['program_score'] + $avg_score) / ($result_count + 1);
            }
        }

        // Get list of articles (assignment check-ins) we could have completed until now (as array w/articleId as key).
        // Get list of activities we could have completed until now (as array w/articleId as key).
        // Get list of actions we could have completed until now (as array w/articleId as key).

        return $this->view->view_user_achievements($results);
    }

    public function saveCheckin_callback()
    {

        dbg("e20rAction::saveCheckin_callback() - Attempting to save checkin for user.");

        dbg("e20rAction::saveCheckin_callback() - Checking ajax referrer privileges");
        check_ajax_referer('e20r-action-data', 'e20r-action-nonce');

        dbg("e20rAction::saveCheckin_callback() - Checking ajax referrer has the right privileges");

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        // Save the $_POST data for the Action callback
        global $current_user;

        global $e20rTracker;
        global $e20rProgram;

        dbg("e20rAction::saveCheckin_callback() - Content of POST variable:");
        dbg($_POST);

        $data = array(
            'user_id' => $current_user->ID,
            'id' => (isset($_POST['id']) ? ($e20rTracker->sanitize($_POST['id']) != 0 ? $e20rTracker->sanitize($_POST['id']) : null) : null),
            'action_id' => (isset($_POST['action-id']) ? ($e20rTracker->sanitize($_POST['action-id']) != 0 ? $e20rTracker->sanitize($_POST['action-id']) : null) : null),
            'checkin_type' => (isset($_POST['checkin-type']) ? $e20rTracker->sanitize($_POST['checkin-type']) : null),
            'article_id' => (isset($_POST['article-id']) ? $e20rTracker->sanitize($_POST['article-id']) : CONST_NULL_ARTICLE),
            'program_id' => (isset($_POST['program-id']) ? $e20rTracker->sanitize($_POST['program-id']) : -1),
            'checkin_date' => (isset($_POST['checkin-date']) ? $e20rTracker->sanitize($_POST['checkin-date']) : null),
            'checkedin_date' => (isset($_POST['checkedin-date']) ? $e20rTracker->sanitize($_POST['checkedin-date']) : null),
            'descr_id' => (isset($_POST['descr-id']) ? $e20rTracker->sanitize($_POST['descr-id']) : null),
            'checkin_note' => (isset($_POST['checkin-note']) ? $e20rTracker->sanitize($_POST['checkin-note']) : null),
            'checkin_short_name' => (isset($_POST['checkin-short-name']) ? $e20rTracker->sanitize($_POST['checkin-short-name']) : null),
            'checkedin' => (isset($_POST['checkedin']) ? $e20rTracker->sanitize($_POST['checkedin']) : 0),
        );

        if ($data['program_id'] !== -1) {

            $e20rProgram->init($data['program_id']);
        } else {

            $e20rProgram->getProgramIdForUser($current_user->ID, $data['article_id']);
        }

        if ($data['article_id'] == CONST_NULL_ARTICLE) {
            dbg("e20rAction::saveCheckin_callback() - No checkin needed/scheduled");
            wp_send_json_error();
            wp_die();
        }

        if (!$this->model->setCheckin($data)) {

            dbg("e20rAction::saveCheckin_callback() - Error saving checkin information...");
            wp_send_json_error();
            wp_die();
        }

        wp_send_json_success();
        wp_die();
    }

    public function save_check_in($checkin_data, $type = null)
    {

        if (!isset($checkin_data['checkin_type']) && (!is_null($type))) {

            $checkin_data['checkin_type'] = $this->types[$type];
        }

        if ($this->model->isValid($checkin_data)) {

            return $this->model->saveCheckin($checkin_data);
        }

        return false;
    }

    public function dailyProgress_callback()
    {

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-assignment-answer');
        dbg("e20rAction::dailyProgress_callback() - Ajax calleee has the right privileges");

        dbg($_POST);

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        global $currentArticle;
        global $e20rTracker;
        global $e20rProgram;
        global $e20rArticle;
        global $e20rAssignment;

        $descrId = null;
        $success = false;
        $answerIsDefaultBtn = false;

        $articleId = (isset($_POST['e20r-article-id']) ? $e20rTracker->sanitize($_POST['e20r-article-id']) : null);
        $userId = (isset($_POST['e20r-article-user_id']) ? $e20rTracker->sanitize($_POST['e20r-article-user_id']) : null);
        $delay = (isset($_POST['e20r-article-release_day']) ? $e20rTracker->sanitize($_POST['e20r-article-release_day']) : null);
        $answerDate = (isset($_POST['e20r-assignment-answer_date']) ? $e20rTracker->sanitize($_POST['e20r-assignment-answer_date']) : null);
        $recordIds = (isset($_POST['e20r-assignment-record_id']) ? $e20rTracker->sanitize($_POST['e20r-assignment-record_id']) : array());
        $answerIds = (isset($_POST['e20r-assignment-id']) && is_array($_POST['e20r-assignment-id']) ? $e20rTracker->sanitize($_POST['e20r-assignment-id']) : array());
        $questionIds = (isset($_POST['e20r-assignment-question_id']) && is_array($_POST['e20r-assignment-question_id']) ? $e20rTracker->sanitize($_POST['e20r-assignment-question_id']) : array());
        $fieldTypes = (isset($_POST['e20r-assignment-field_type']) && is_array($_POST['e20r-assignment-field_type']) ? $e20rTracker->sanitize($_POST['e20r-assignment-field_type']) : array());
        $answers = (isset($_POST['e20r-assignment-answer']) && is_array($_POST['e20r-assignment-answer']) ? $e20rTracker->sanitize($_POST['e20r-assignment-answer']) : array());

        $programId = $e20rProgram->getProgramIdForUser($userId, $articleId);

        if ((CONST_NULL_ARTICLE === $articleId) && (is_null($userId) || is_null($answerDate) || is_null($delay))) {
            dbg("e20rAction::dailyProgress_callback() - Can't save assignment info!");
            wp_send_json_error(__("Unable to save your answer. Please contact technical support!", "e20rtracker"));
        }

        if (count($questionIds) != count($answers)) {
            dbg("e20rAction::dailyProgress_callback() - Mismatch for # of questions and # of answers provided/supplied. ");
            dbg("e20rAction::dailyProgress_callback() - Questions: ");
            dbg($questionIds);
            dbg("e20rAction::dailyProgress_callback() - Answers: ");
            dbg($answers);

            // Is this a default "read this lesson" button?
            if (empty($answers) && (1 == count($fieldTypes)) && (0 == $fieldTypes[0])) {

                // It is, so flag the fact.
                $answerIsDefaultBtn = true;
            }
            // wp_send_json_error( __( "You didn't answer all of the questions we had for you. We're saving what we received.", "e20rtracker" ) );
        }


        /*		if ( ( count( $answerIds ) == 1 ) && ( $fieldTypes[0]) == 0 ) {

                    dbg("e20rAction::dailyProgress_callback() - user clicked 'lesson complete' button.");
                    $checkin = array(
                        'article_id' => $articleId,
                        'descr_id' => $descrId,
                        'user_id' => $userId,
                        'checkin_type' => CHECKIN_ASSIGNMENT,
                        'program_id' => $programId,
                        'checkin_date' => $e20rArticle->releaseDate( $articleId ),
                        'checkedin_date' => $answerDate,
                        'checkin_short_name' => 'daily_lesson',
                        'checkedin' => true,
                    );
                }
                else {
        */
        dbg("e20rAction::dailyProgress_callback() - Have an array of answers to process..");

        // Build answer objects to save to database
        foreach ($answerIds as $key => $id) {

            if (!$descrId) {
                $descrId = $id;
            }

            $checkin = array(
                'descr_id' => $descrId,
                'article_id' => $articleId,
                'user_id' => $userId,
                'checkin_type' => CHECKIN_ASSIGNMENT,
                'program_id' => $programId,
                'checkin_date' => $e20rArticle->releaseDate($articleId),
                'checkedin_date' => $answerDate,
                'checkin_short_name' => 'daily_lesson',
                'checkedin' => (!empty($answers[$key]) || (($answerIsDefaultBtn) && (0 == $fieldTypes[0]))),
            );

            dbg("e20rAction::dailyProgress_callback() - Saving answer(s) for assignment # {$id} ");

            $answer = array(
                /* 'id' => $id, */
                'article_id' => $articleId,
                'program_id' => $programId,
                'delay' => $delay,
                'question_id' => $questionIds[$key],
                'user_id' => $userId,
                'answer_date' => $answerDate,
                'answer' => (isset($answers[$key]) ? $answers[$key] : null),
                'field_type' => $e20rAssignment->getInputType($fieldTypes[$key]),
            );

            if (-1 != $recordIds[$key]) {

                $answer['id'] = $recordIds[$key];
            }

            dbg('e20rAction::dailyProgress_callback() - Answer Provided: ');
            dbg($answer);

            dbg("e20rAction::dailyProgress_callback() - Saving answer to question # {$answer['question_id']}");
            $new = $e20rAssignment->saveAssignment($answer);
            $success = ($success && $new);
        }
        /*		} */

        dbg("e20rAction::dailyProgress_callback() - Make sure check-in isn't empty");

        if ((!empty($checkin))) {

            dbg("e20rAction::dailyProgress_callback() - Saving checkin for date {$checkin['checkin_date']}");
            $ok = $this->model->setCheckin($checkin);

            if (!$ok) {

                global $wpdb;
                dbg("e20rAction::dailyProgress_callback() - DB error: " . $wpdb->last_error);
            } else {

                $success = true;
            }
        }

        if ($success == true) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__("Unable to save your update", "e20rtracke"));
        }
    }

    public function configure_dailyProgress($is_callback = false)
    {

        global $e20rProgram;
        global $e20rTracker;
        global $e20rArticle;

        global $currentProgram;
        global $currentArticle;

        global $current_user;
        global $post;

        $articles = array();
        $article_configured = false;

        $config = new stdClass();

        $config->type = 'action';
        $config->survey_id = null;
        $config->post_date = null;
        $config->maxDelayFlag = null;
        $config->assignment_id = null;

        $config->complete = false;
        $config->userId = $current_user->ID;
        $config->update_period = 'Today';
        $config->using_closest = false;
        $config->today = null;

        if ( isset( $currentArticle->id) && ($post->ID == $currentArticle->post_id) ) {
            dbg("e20rAction::configure_dailyProgress() - Article data already loaded: {$currentArticle->post_id} vs {$post->ID}");
            dbg($currentArticle);
            $article_configured = true;
        }

        $config->programId = (!isset($_POST['program-id']) ? $e20rProgram->getProgramIdForUser($config->userId) : intval($_POST['program-id']));

        if (!isset($currentProgram->id) || ($currentProgram->id !== $config->programId)) {

            $e20rProgram->init($config->programId);
        }

        $dashboard = ($currentProgram->dashboard_page_id != null || $currentProgram->dashboard_page_id != -1) ? get_permalink($currentProgram->dashboard_page_id) : null;
        $config->url = $dashboard;

        $config->startTS = strtotime($currentProgram->startdate);

        dbg("e20rAction::configure_dailyProgress() - POST vaues: " . print_r( $_POST, true ) );

        if (isset($_POST['e20r-use-card-based-display'])) {
            dbg("e20rAction::configure_dailyProgress() - Card status from the calling page/post: {$_POST['e20r-use-card-based-display']}");
            $card_setting = $e20rTracker->sanitize($_POST['e20r-use-card-based-display']);
            $config->use_cards = ($card_setting ? true : false);

            dbg("e20rAction::configure_dailyProgress() - using card-based display setting from calling page: ". (false === $config->use_cards ? 'false' : 'true'));
        }

        if (isset($_POST['e20r-action-day'])) {

            $config->delay = $e20rTracker->getDelay($e20rTracker->sanitize($_POST['e20r-action-day']), $config->userId);
            $config->today = isset($_POST['e20r-today']) ? $e20rTracker->sanitize($_POST['e20r-today']) : $e20rTracker->getDelay();

            dbg("e20rAction::configure_dailyProgress() - Was given a specific release_day to load the article for: {$config->delay}");
        }

        if (isset($_POST['article-id'])) {
            dbg("e20rAction::configure_dailyProgress() - Article ID is specified: {$_POST['article-id']}");
        }

        if (isset($_POST['article-id']) && !isset($config->delay)) {

            $config->articleId = $e20rTracker->sanitize($_POST['article-id']);
            dbg("e20rAction::configure_dailyProgress() - Loading article based on specified article ID ({$config->articleId}) from POST variable");

            // Article ID given in POST variable so load the requested article
            $e20rArticle->init($config->articleId);
        } else {

            if (!isset($config->delay) && (false === $article_configured)) {

                dbg("e20rAction::configure_dailyProgress() - Trying to load article based on post_id");

                $articles = $e20rArticle->findArticles('post_id', $post->ID, $config->programId);

                if (empty($articles)) {

                    dbg("e20rAction::configure_dailyProgress() - post_id got us nowhere so using the present ('now') as a delay value to try to find a valid article");
                    $config->delay = $e20rTracker->getDelay('now', $config->userId);
                }
            }

            if (isset($config->delay) && empty($articles) && (false === $article_configured)) {

                dbg("e20rAction::configure_dailyProgress() - Loading article (list?) based on the release day (delay value)");
                $articles = $e20rArticle->findArticles('release_day', $config->delay, $config->programId);
            }

            if (empty($articles) && (false === $article_configured)) {

                $articles = $e20rArticle->findArticlesNear('release_day', $config->delay, $config->programId, '<=');

                dbg("e20rAction::configure_dailyProgress() - Empty article for the actual day, so we're looking for the one the closest to requested day");
                $article = $articles[0];
                $config->using_closest = true;
                // $article = $e20rArticle->emptyArticle();
                // $article->id = CONST_NULL_ARTICLE;
            }

            if (is_array($articles) && (1 == count($articles)) && (false === $article_configured)) {

                $article = array_pop($articles);

            } elseif (1 < count($articles) && (false === $article_configured)) {
                dbg("e20rAction::configure_dailyProgress() - WARNING: Multiple articles have been returned. Select the one with a release data == the delay.");

                if (empty($config->delay)) {

                    $use = $e20rTracker->getDelay();
                } else {

                    $use = $config->delay;
                }

                $article = $this->getClosest( $use, $articles );
	            dbg("e20rAction::configure_dailyProgress() - Found an article w/what we think is the correct release_day and program ID. Using it: {$article->id}.");

            } elseif (is_object($articles) && (false === $article_configured)) {
                dbg("e20rAction::configure_dailyProgress() - Articles object: " . gettype($articles));
                dbg($articles);
                $article = $articles;
            }

            if ( (false === $article_configured) && isset( $article->release_day ) ) {
                $currentArticle = $article;
            }
        }

        dbg("e20rAction::configure_dailyProgress() - Loaded article info for {$currentArticle->id}");

        switch( count( $currentArticle->assignment_ids )) {
            case 0:
                $config->assignment_id = 0;
                break;
            case 1:

                if ( !is_array( $currentArticle->assignment_ids)) {
                    $config->assignment_id = $currentArticle->assignment_ids;
                } else {
                    $assignment = $currentArticle->assignment_ids;
                    $config->assignment_id = array_pop( $assignment);
                }
                break;

            default:
                $assignment = $currentArticle->assignment_ids;
                $config->assignment_id = array_pop( $assignment);
        }

        $config->delay = isset($currentArticle->release_day) ? $currentArticle->release_day : 0;
        $config->delay_byDate = $e20rTracker->getDelay();
        $config->is_survey = isset($currentArticle->is_survey) && ($currentArticle->is_survey == 0) ? false : true;
        $config->articleId = isset($currentArticle->id) ? $currentArticle->id : CONST_NULL_ARTICLE;
        $config->use_cards = (isset($config->use_cards) ? $config->use_cards : false);

        return $config;

    }

	public function getClosest($day, $articles) {

		$closest = null;

		foreach ($articles as $article) {

			if ($closest === null || abs($day - $closest->release_day) > abs($article->release_day - $day)) {
				$closest = $article;
			}
		}

		return $closest;
	}

    public function nextCheckin_callback()
    {

        dbg("e20rAction::nextCheckin_callback() - Checking ajax referrer privileges");
        check_ajax_referer('e20r-action-data', 'e20r-action-nonce');

        dbg("e20rAction::nextCheckin_callback() - Checking ajax referrer has the right privileges");

        if (!is_user_logged_in()) {

            dbg("e20rAction::nextCheckin_callback() - Return login error and force redirect to login page.");
            wp_send_json_error(array('ecode' => 3));
        }

        global $e20rArticle;
        global $e20rTracker;

        global $currentArticle;

        $config = $this->configure_dailyProgress(true);

        if ($config->delay != $currentArticle->release_day) {

            dbg("e20rAction::nextCheckin_callback() - Need to load a new article (by delay)");

            $articles = $e20rArticle->findArticles('release_day', $config->delay, $config->programId);
            dbg("e20rAction::nextCheckin_callback() - Found " . count($articles) . " articles for program {$config->programId} and with a release day of {$config->delay}");
            dbg($articles);

            if (is_array($articles) && (1 == count($articles))) {

                $articles = $articles[0];
            }

            // Single article returned.
            if (!empty($articles)) {

                dbg("e20rAction::nextCheckin_callback() - Loading the article info for the requested day");
                $e20rArticle->init($articles->id);
                $config->articleId = $articles->id;

                dbg("e20rAction::nextCheckin_callback() - Checking access to post # {$articles->post_id} for user ID {$config->userId}");
                $access = $e20rTracker->hasAccess($config->userId, $articles->post_id);
                dbg("e20rAction::nextCheckin_callback() - Access to post # {$articles->post_id} for user ID {$config->userId}: {$access}");
            } else {

                $access = false;
            }

            if ((false === $access) && (empty($articles))) {
                dbg("e20rAction::nextCheckin_callback() - Error: No article for user {$config->userId} in this program.");
                wp_send_json_error(array('ecode' => 2));
            } elseif ((false === $access) && (!empty($articles))) {

                dbg("e20rAction::nextCheckin_callback() - Error: User {$config->userId} DOES NOT have access to post ");
                wp_send_json_error(array('ecode' => 1));
            }

            $config->is_survey = isset($currentArticle->is_survey) && ($currentArticle->is_survey == 0) ? false : true;
            $config->articleId = isset($currentArticle->id) ? $currentArticle->id : CONST_NULL_ARTICLE;
        }

        dbg("e20rAction::nextCheckin_callback() - Article: {$config->articleId}, Program: {$config->programId}, delay: {$config->delay}, start: {$config->startTS}, delay_byDate: {$config->delay_byDate}");

        $access = $e20rTracker->hasAccess($config->userId, $currentArticle->post_id);
        dbg("e20rAction::nextCheckin_callback() - Access: " . ($access ? 'true' : 'false') . ". Using closest article and not current_day: " . ($config->using_closest ? 'true' : 'false') . ". delay_byDate: {$config->delay_byDate} vs delay: {$config->delay}");

        if ($access && $config->using_closest && ($config->delay > $config->delay_byDate)) {

            dbg("e20rAction::nextCheckin_callback( - Article & post isn't available to this user yet due to delay vs today");
            wp_send_json_error(array('ecode' => 1));
        }

        if (!$access) {
            dbg("e20rAction::nextCheckin_callback( - User doesn't have access to article.");
            wp_send_json_error(array('ecode' => 1));
        }

        if (($html = $this->dailyProgress($config)) !== false) {

            dbg("e20rAction::nextCheckin_callback() - Sending new dailyProgress data (html)");
            // dbg($html);
            wp_send_json_success($html);
        }

        wp_send_json_error();
    }

    public function shortcode_dailyProgress($atts = null)
    {

        if (!is_user_logged_in()) {

            auth_redirect();
        }

        global $e20rArticle;

        global $post;

        $articles = array();

        dbg("e20rAction::shortcode_dailyProgress() - Processing the daily_progress short code");

        $config = $this->configure_dailyProgress();

        $code_atts = shortcode_atts(array(
            'type' => 'action',
            'use_cards' => false,
        ), $atts);

        // Add shortcode settings to the $config object
        foreach ($code_atts as $key => $val) {

            dbg("e20rAction::shortcode_dailyProgress() - daily_progress shortcode --> Key: {$key} -> {$val}");
            $config->{$key} = $val;
        }

        if (in_array(strtolower($config->use_cards), array('yes', 'true', '1'))) {

            dbg("e20rAction::shortcode_dailyProgress() - User requested card based dashboard: {$config->use_cards}");
            $config->use_cards = true;
        }

        if (in_array(strtolower($config->use_cards), array('no', 'false', '0'))) {

            dbg("e20rAction::shortcode_dailyProgress() - User requested old-style dashboard: {$config->use_cards}");
            $config->use_cards = false;
        }

        if (!isset($config->use_cards)) {
            $config->use_cards = false;
        }

        dbg("e20rAction::shortcode_dailyProgress() - Config is currently: ");
        dbg($config);
/*
        if ($config->type == 'assignment') {

            dbg("e20rAction::shortcode_dailyProgress() - Finding article info by post_id: {$post->ID}");
            $articles = $e20rArticle->findArticles('post_id', $post->ID, $config->programId);
        }
*/
        dbg("e20rAction::shortcode_dailyProgress() - Article ID is currently set to: {$config->articleId}");

        ob_start();
        ?>
        <div id="e20r-daily-progress">
            <?php echo $this->dailyProgress($config); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function dailyProgress($config)
    {

        global $e20rTracker;
        global $e20rArticle;
        global $e20rAssignment;
        global $e20rWorkout;
        global $currentArticle;

        dbg("e20rAction::dailyProgress() - Start of dailyProgress(): " . $e20rTracker->whoCalledMe());
        dbg("e20rAction::dailyProgress() - Article Id: {$config->articleId} vs {$currentArticle->id}");
        // dbg( $config );

        if (!isset($config->delay) || $config->delay <= 0) {

            dbg("e20rAction::dailyProgress() - Negative delay value. No article to be found.");
            $config->articleId = CONST_NULL_ARTICLE;
        }

        if (!isset($currentArticle->post_id) || ($config->articleId != $currentArticle->id)) {

            dbg("e20rAction::dailyProgress() - No or wrong article is active. Updating...");
            $e20rArticle->init($config->articleId);
        }

        if ($config->delay > ($config->delay_byDate + 2)) {
            // The user is attempting to view a day >2 days after today.
            $config->maxDelayFlag = CONST_MAXDAYS_FUTURE;
        }

        /*
                if ( $config->delay < ( $config->delay_byDate - 2 ) ) {

                    // The user is attempting to view a day >2 days before today.
                    $config->maxDelayFlag = CONST_MAXDAYS_PAST;
                }
        */

        $config->prev = $config->delay - 1;
        $config->next = $config->delay + 1;

//        $articles = $e20rArticle->findArticles( 'release_day', $config->delay, $config->programId );
//        dbg("e20rActionView::view_actionAndActivityCheckin() - Articles found: " .count($articles) );

        dbg("e20rAction::dailyProgress() - Delay info: Now = {$config->delay}, 'tomorrow' = {$config->next}, 'yesterday' = {$config->prev}");

        $t = $e20rTracker->getDateFromDelay(($config->next - 1));
        $config->tomorrow = date_i18n('D M. jS', strtotime($t));

        $y = $e20rTracker->getDateFromDelay(($config->prev - 1));
        $config->yesterday = date_i18n('D M. jS', strtotime($y));

        if (!isset($config->userId)) {

            global $current_user;
            $config->userId = $current_user->ID;
        }

        $this->checkin = $this->load_default_checkins();

//		dbg("e20rAction::dailyProgress() - Config settings: ");
//		dbg($config);

        dbg("e20rAction::dailyProgress() - currentArticle is {$currentArticle->id} ");
        // dbg($currentArticle);

        /*		if ( ! $e20rTracker->hasAccess( $config->userId, $currentArticle->post_id ) ) {
                    return false;
                }
        */
        $config->complete = $this->hasCompletedLesson($config->articleId, $currentArticle->post_id, $config->userId);

        if ((strtolower($config->type) == 'action') || (strtolower($config->type) == 'activity')) {

            dbg("e20rAction::dailyProgress() - Processing action or activity");

            if (!isset($config->articleId) || empty($config->articleId)) {

                dbg("e20rAction::dailyProgress() -  No articleId specified. Searching...");

                $articles = $e20rArticle->findArticles('release_day', $config->delay, $config->programId);
                // dbg( $articles );

                foreach ($articles as $article) {

                    if ($config->delay == $article->release_day) {

                        $config->articleId = $article->id;
                        dbg("e20rAction::dailyProgress() -  Found article # {$config->articleId}");
                        break;
                    }
                }

                if (empty($articles)) {
                    dbg("e20rAction::dailyProgress() - No article found. Using default of: " . CONST_NULL_ARTICLE);
                    $config->articleId = CONST_NULL_ARTICLE;
                }
            }

            dbg("e20rAction::dailyProgress() - Configured daily action check-ins for article ID(s):");

            // if ( !is_array( $config->articleId ) && ( $config->articleId !== CONST_NULL_ARTICLE ) ) {
            // if ( $config->articleId !== CONST_NULL_ARTICLE ) {
            dbg("e20rAction::dailyProgress() - Generating excerpt for daily action lesson/reminder");
            $config->actionExcerpt = $e20rArticle->getExcerpt($config->articleId, $config->userId, 'action', $config->use_cards);

            dbg("e20rAction::dailyProgress() - Generating excerpt for daily activity");
            $config->activityExcerpt = $e20rArticle->getExcerpt($config->articleId, $config->userId, 'activity', $config->use_cards);
            //}

            // Get the check-in id list for the specified article ID
            $checkinIds = $e20rArticle->getCheckins($config);

            if (empty($checkinIds)) {

                dbg("e20rAction::dailyProgress() - No check-in ids stored for this user/article Id...");

                // Set default checkin data (to ensure rendering of form).
                $this->checkin[CHECKIN_ACTION] = $this->model->get_user_checkin($config, $config->userId, CHECKIN_ACTION);
                $this->checkin[CHECKIN_ACTION]->actionList = array();
                $this->checkin[CHECKIN_ACTION]->actionList[] = $this->model->defaultAction();

                $this->checkin[CHECKIN_ACTIVITY] = $this->model->get_user_checkin($config, $config->userId, CHECKIN_ACTIVITY);

                $config->post_date = $e20rTracker->getDateForPost($config->delay);
                $checkinIds = $this->model->findActionByDate($config->post_date, $config->programId);
            }

            dbg("e20rAction::dailyProgress() - Checkin info loaded (count): " . count($checkinIds));
            // dbg( $checkinIds );

            // $activity = $e20rArticle->getActivity( $config->articleId, $config->userId );
            // dbg( "e20rAction::dailyProgress() - Activity info loaded (count): " . count( $activity ) );
            // dbg($activity);

            $note = null;

            foreach ($checkinIds as $id) {

                dbg("e20rAction::dailyProgress() - Processing checkin ID {$id}");
                $settings = $this->model->loadSettings($id);

                switch ($settings->checkin_type) {

                    case $this->types['assignment']:

                        dbg("e20rAction::dailyProgress() - Loading data for assignment check-in");
                        $checkin = null;
                        break;

                    case $this->types['survey']:

                        dbg("e20rAction::dailyProgress() - Loading data for survey check-in");
                        // TODO: Load view for survey data (pick up survey(s) from Gravity Forms entry.
                        $checkin = null;
                        break;

                    case $this->types['action']:

                        dbg("e20rAction::dailyProgress() - Loading data for daily action check-in & action list");

                        $checkin = $this->model->get_user_checkin($config, $config->userId, $settings->checkin_type, $settings->short_name);
                        $note = $this->model->get_user_checkin($config, $config->userId, CHECKIN_NOTE, $settings->short_name);
                        $checkin->actionList = $this->model->getActions($id, $settings->checkin_type, -3);

                        break;

                    case $this->types['activity']:

                        dbg("e20rAction::dailyProgress() - Loading data for daily activity check-in");
                        $checkin = $this->model->get_user_checkin($config, $config->userId, $settings->checkin_type, $settings->short_name);
                        break;

                    case $this->types['note']:
                        // We handle this in the action check-in.
                        dbg("e20rAction::dailyProgress() - Explicitly loading data for daily activity note(s)");
                        $note = $this->model->get_user_checkin($config, $config->userId, CHECKIN_NOTE, $settings->short_name);
                        break;

                    default:

                        // Load action and acitvity view.
                        dbg("e20rAction::dailyProgress() - No default action to load!");
                        $checkin = null;
                }

                if (!empty($checkin)) {

                    // Reset the value to true Y-m-d format
                    $checkin->checkin_date = date('Y-m-d', strtotime($checkin->checkin_date));
                    $this->checkin[$settings->checkin_type] = $checkin;

                    if (!$e20rTracker->isEmpty($note)) {

                        dbg("e20rAction::dailyProgress() - Including data for daily progress note");
                        $this->checkin[CHECKIN_NOTE] = $note;
                    }
                }

            } // End of foreach()

            dbg("e20rAction::dailyProgress() - Loading checkin for user {$config->userId} and delay {$config->delay}..");
            dbg($this->checkin);

            return $this->load_UserCheckin($config, $this->checkin);
        }

        if (strtolower($config->type) == 'assignment') {

            dbg("e20rAction::dailyProgress() - Processing assignment");

            if ($config->articleId === false) {

                dbg("e20rAction::dailyProgress() - No article defined. Quitting.");
                return null;
            }

            dbg("e20rAction::dailyProgress() - Loading pre-existing data for the lesson/assignment ");

            // TODO: Decide whether or not the daily assignment is supposed to be a survey or not.
            $assignments = $e20rArticle->getAssignments($config->articleId, $config->userId);

            if (true === $config->is_survey) {

                dbg("e20rAction::dailyProgress() - We're being asked to render a survey with the article");

            }

	        return $e20rAssignment->showAssignment($assignments, $config);
        }

        if (strtolower($config->type == 'show_assignment')) {

            dbg("e20rAction::dailyProgress() - Processing display of assignments status");

            dbg("e20rAction::dailyProgress[show_assignment]() - Loading Assignment list");
            return $e20rAssignment->listUserAssignments($config);
        }

        if (strtolower($config->type == 'survey')) {

            dbg("e20rAction::dailyProgress() - Process a survey assignment/page");

            if ($config->articleId === false) {

                dbg("e20rAction::dailyProgress() - No article defined. Quitting.");
                return null;
            }

            dbg("e20rAction::dailyProgress() - Loading pre-existing data for the lesson/assignment ");

            $assignments = $e20rArticle->getAssignments($config->articleId, $config->userId);
            dbg($assignments);

            return $e20rAssignment->showAssignment($assignments, $config);

        }
    }

    public function load_default_checkins() {

        $defaults = array();

        $defaults[CHECKIN_ACTION] = $this->model->defaultCheckin( CHECKIN_ACTION );
        $defaults[CHECKIN_ASSIGNMENT] = $this->model->defaultCheckin( CHECKIN_ASSIGNMENT );
        $defaults[CHECKIN_NOTE] = $this->model->defaultCheckin( CHECKIN_NOTE );

        return $defaults;
    }

    public function load_UserCheckin($config, $checkinArr)
    {

        global $e20rTracker;

        $action = null;
        $activity = null;
        $assignment = null;
        $note = null;
        $survey = null;
        $view = null;

        dbg("e20rAction::load_UserCheckin() - For type: {$config->type}");

        if (!empty($checkinArr)) {

            dbg("e20rAction::load_UserCheckin() - Array of checkin values isn't empty...");
            // dbg($checkinArr);

            foreach ($checkinArr as $type => $c) {

                dbg("e20rAction::load_UserCheckin() - Loading view type {$type} for checkin");

                if ($type == CHECKIN_ACTION) {

                    dbg("e20rAction::load_UserCheckin() - Loading Action checkin data");
                    $action = $c;
                }

                if ($type == CHECKIN_ACTIVITY) {

                    dbg("e20rAction::load_UserCheckin() - Loading Activity checkin data");
                    $activity = $c;
                }

                if ($type == CHECKIN_NOTE) {

                    dbg("e20rAction::load_UserCheckin() - Loading check-in note(s)");
                    $note = $c;
                }

                if ($type == CHECKIN_ASSIGNMENT) {
                    $assignment = $c;
                }

                if ($type == CHECKIN_SURVEY) {
                    $survey = $c;
                }

            } // End of foreach()

            if ((!$e20rTracker->isEmpty($action)) && (!$e20rTracker->isEmpty($activity))) {

                dbg("e20rAction::load_UserCheckin() - Loading the view for the Dashboard");

                if (!isset($config->use_cards) || (false === $config->use_cards)) {

                    dbg("e20rAction::load_UserCheckin() - Using old view layout");
                    $view = $this->view->view_actionAndActivityCheckin($config, $action, $activity, $action->actionList, $note);
                } elseif (true === $config->use_cards) {

                    dbg("e20rAction::load_UserCheckin() - Using new view layout");
                    $view = $this->view->view_action_and_activity($config, $action, $activity, $action->actionList, $note);
                }
            }

        } // else if ( ( $config->type == 'action' ) || ( $config->type == 'activity' ) ) {
        else if (($config->type == $this->getTypeDescr(CHECKIN_ACTION)) ||
            ($config->type == $this->getTypeDescr(CHECKIN_ACTIVITY))
        ) {

            dbg("e20rAction::load_UserCheckin() - An activity or action check-in requested...");
            if (!isset($config->use_cards) || (false === $config->use_cards)) {

                dbg("e20rAction::load_UserCheckin() - Using old view layout");
                $view = $this->view->view_actionAndActivityCheckin($config, $action, $activity, $action->actionList, $note);
            } elseif (true === $config->use_cards) {

                dbg("e20rAction::load_UserCheckin() - Using new view layout");
                $view = $this->view->view_action_and_activity($config, $action, $activity, $action->actionList, $note);
            }
        }

        return $view;
    }

    public function getPeers($checkinId = null)
    {

        if (is_null($checkinId)) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $checkinId = $post->post_parent;
        }

        $checkins = new WP_Query(array(
            'post_type' => 'page',
            'post_parent' => $checkinId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ));

        $checkinList = array(
            'pages' => $checkins->posts,
        );

        foreach ($checkinList->posts as $k => $v) {

            if ($v == get_the_ID()) {

                if (isset($checkins->posts[$k - 1])) {

                    $checkinList['prev'] = $checkins->posts[$k - 1];
                }

                if (isset($checkins->posts[$k + 1])) {

                    $checkinList['next'] = $checkins->posts[$k + 1];
                }
            }
        }

        wp_reset_postdata();

        return $checkinList;
    }

    public function editor_metabox_setup($post)
    {

        if (isset($post->ID)) {
            $this->init($post->ID);
        }

        add_meta_box('e20r-tracker-checkin-settings', __('Action Settings', 'e20rtracker'), array(&$this, "addMeta_Settings"), 'e20r_actions', 'normal', 'high');

    }

    public function addMeta_Settings()
    {

        global $post;

        $def_status = array(
            'publish',
            'pending',
            'draft',
            'future',
            'private'
        );

        // Query to load all available programs (used with check-in definition)
        $query = array(
            'post_type' => 'e20r_programs',
            'post_status' => apply_filters('e20r_tracker_checkin_status', $def_status),
            'posts_per_page' => -1,
        );

        wp_reset_query();

        //  Fetch Programs
        $checkins = get_posts($query);

        if (empty($checkins)) {

            dbg("e20rAction::addMeta_Settings() - No programs found!");
        }

        wp_reset_postdata();

        dbg("e20rAction::addMeta_Settings() - Loading settings metabox for checkin page {$post->ID}");
        $settings = $this->model->loadSettings($post->ID);

        echo $this->view->viewSettingsBox($settings, $checkins);

    }

    public function saveSettings($post_id)
    {

        $post = get_post($post_id);

        if (isset($post->post_type) && ($post->post_type == $this->model->get_slug())) {

            setup_postdata($post);

            $this->model->set('short_name', get_the_title($post_id));
            $this->model->set('item_text', get_the_excerpt());
        }

        parent::saveSettings($post_id);
    }
}