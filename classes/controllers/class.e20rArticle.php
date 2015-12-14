<?php

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */
class e20rArticle extends e20rSettings
{

    /* Articles contain list of programs it belongs to along with the PostID and all metadata.*/
    protected $articleId;

    protected $model;
    protected $view;

    public function __construct()
    {

        dbg("e20rArticle::__construct() - Initializing Article class");
        $this->model = new e20rArticleModel();
        $this->view = new e20rArticleView();

        parent::__construct('article', 'e20r_articles', $this->model, $this->view);
    }

    public function emptyArticle()
    {

        return $this->model->defaultSettings();
    }

    public function findArticlesNear($key, $value, $programId = -1, $comp = '<=', $sort_order = 'DESC', $limit = 1, $type = 'numeric')
    {

        // findClosestArticle( $key, $value, $programId = -1, $comp = '<=', $limit = 1, $type = 'numeric', $sort_order = 'DESC' )
        return $this->model->findClosestArticle($key, $value, $programId, $comp, $limit, $sort_order, $type);
    }

    public function getCheckins($aConfig)
    {

        dbg("e20rArticle::getCheckins() - Get array of checkin IDs for {$aConfig->articleId}");

        $checkin_ids = $this->model->getSetting($aConfig->articleId, 'action_ids');
        // $activity = $this->model->getSetting( $aConfig->articleId, 'activity_id' );

        if (!is_array($checkin_ids)) {
            // $setting = array_merge( array( $checkin ), array( $activity ) );
            $checkin = array($checkin_ids);
        }

        if (empty($checkin_ids) /* && is_null( $activity ) */) {
            dbg("e20rArticle::getCheckins() - No check-in IDs found for this article ({$aConfig->articleId})");
            return false;
        }

        return $checkin_ids;
    }

    public function releaseDay($articleId)
    {

        return $this->model->getSetting($articleId, 'release_day');
    }

    public function isSurvey($id)
    {

        dbg("e20rArticle::isSurvey() - checking survey setting for {$id}");

        $setting = $this->model->getSetting($id, 'is_survey');

        if (empty($setting)) {

            dbg("e20rArticle::isSurvey() - is_survey setting is empty/null");
            return false;
        } else {
            return (0 != $setting ? true : false);
        }
    }

    /**
     * Get the 'prefix' setting for the specified articleId
     *
     * @param $articleId - The Article ID to look up settings for
     *
     * @return string - The actual prefix (or NULL) for the articleId
     */
    public function getArticlePrefix($articleId)
    {

        $settings = $this->getSettings($articleId);

        if (!empty($settings)) {

            return $settings->prefix;
        }

        return null;
    }

    public function getSettings($articleId)
    {

        global $currentArticle;

        // $article = $this->model->getSettings();

        if ($currentArticle->id == $articleId) {
            return $currentArticle->id;
        } else {
            $this->model->loadSettings($articleId);
            return $currentArticle;
        }

        return false;
    }

    public function getPostUrl($articleId)
    {

        if (!$articleId) {

            dbg("e20rArticle::getPostUrl() - No article ID provided?!?");
            return false;
        }

        $postId = $this->model->getSetting($articleId, 'post_id');
        $url = get_permalink($postId);

        dbg("e20rArticle::getPostUrl() - Got URL for post ({$postId}): {$url}");

        return (empty($url) ? false : $url);
    }

    public function get_checkin_shortname($articleId, $checkin_type)
    {

        global $e20rAction;

        $article = $this->getSettings($articleId);

        foreach ($article->action_ids as $cId) {

            $activity = $e20rAction->init($cId);

            if ($checkin_type == $activity->checkin_type) {
                return $activity->short_code;
            }
        }

        return false;
    }

    public function article_archive_shortcode($attr = null)
    {


        global $e20rClient;
        global $currentClient;
        global $current_user;

        $e20rClient->setClient($current_user->ID);
        $articles = $this->get_article_archive($currentClient->user_id);

        foreach ($articles as $article) {

            $cards = $this->get_card_info($article, $currentClient->user_id);
            dbg("e20rArticle::article_archive_shortcode() - Received " . count($cards) . " cards for day # {$article->release_day}");
        }

        dbg($cards);
    }

    public function get_article_archive($user_id = null)
    {

        global $e20rProgram;
        global $currentProgram;

        if (is_null($user_id)) {

            global $currentClient;
            global $current_user;

            if (!isset($currentClient->user_id)) {

                global $e20rClient;
                $user_id = $e20rClient->setClient($current_user->ID);
                $e20rClient->init();

            } else {
                $user_id = $currentClient->user_id;
            }
        }

        dbg("e20rArticle::get_article_archive() - Loading article archive for user {$user_id}");

        $program_id = $e20rProgram->getProgramIdForUser($user_id);

        // The archive goes up to (but doesn't include) the current day.
        $up_to = ($currentProgram->active_delay - 1);

        $archive = $this->model->load_for_archive($up_to);

        dbg("e20rArticle::get_article_archive() - Returned " . count($archive) . " articles for archive for user {$user_id} with a last-day value of {$currentProgram->active_delay}");
        return $archive;

        // $e20rTracker->get_closest_release_day( $array, $day );
    }

    public function get_card_info($article, $userId = null, $type = 'action')
    {

        global $post;
        global $e20rTracker;
        global $e20rWorkout;

        global $current_user;
        global $currentProgram;

        $postId = null;
        $activityField = null;

        $cards = array();
        $actions = array();
        $activities = array();
        $lessons = array();

        $oldPost = $post;

        if (is_null($userId)) {
            // Assuming current_user->ID..
            $userId = $current_user->ID;
        }

        $lesson = new stdClass();

        // Generate the post (lesson/reminder/text) card
        if (isset($article->post_id) && (!empty($article->post_id))) {

            if (!is_array($article->post_id)) {
                $article->post_id = array($article->post_id);
            }

            foreach ($article->post_id as $post_id) {

                $p = get_post($post_id);
                $a = get_post($article->id);
                wp_reset_postdata();

                $lesson->summary = $this->get_summary($p, $a);
                $lesson->feedback = $this->get_feedback($article, CONST_ARTICLE_FEEDBACK_LESSONS);

                $lessons[] = $lesson;
            }

            $cards['lesson'] = $lessons;
        }


        if (isset($article->activity_id) && (!empty($article->activity_id))) {

            if (!is_array($article->activity_id)) {

                $article->activity_id = array($article->activity_id);
            }

            foreach ($article->activity_id as $activity_id) {


                $activity = $e20rWorkout->load_user_activity($activity_id, $userId);
                $activity->saved =
                $activities[] = $activity;
            }

            $cards['activity'] = $activities;
        }


        if (isset($article->action_ids) && (!empty($article->action_ids))) {

            global $e20rAction;

            if (!is_array($article->action_ids)) {
                $article->action_ids = array($article->action_ids);
            }

            foreach ($article->action_ids as $action_id) {

                $action = $e20rAction->find('id', $action_id, $currentProgram->id);
                dbg("e20rArticle::get_card_info() - Found " . count($action) . " action(s) for article {$article->id}");

                // $action = new stdClass();
                // $action->title = "Some title";
                $actions[] = $action;
            }

            $cards['actions'] = $actions;
        }

        // Reset $post content
        $post = $oldPost;

        return $cards;
    }

    private function get_summary(WP_Post $post, WP_Post $article)
    {

        $excerpt = __("No information found", "e20rtracker");

        $article_has_excerpt = (empty($article->post_excerpt) ? false : true);
        $article_has_content = (empty($article->post_content) ? false : true);
        $post_has_excerpt = (empty($post->post_excerpt) ? false : true);
        $post_has_content = (empty($post->post_content) ? false : true);

        if ((false === $article_has_content) && (false === $article_has_excerpt)) {

            if (true === $post_has_excerpt) {
                $excerpt = $post->post_excerpt;
            }

            if ((false === $post_has_excerpt) && (true == $post_has_content)) {
                $excerpt = wp_trim_words($post->post_excerpt, 20, " [...]");
            }
        }

        if (true === $article_has_excerpt) {
            $excerpt = $article->post_excerpt;
        }

        if ((false === $article_has_excerpt) && (true === $article_has_content)) {
            $excerpt = wp_trim_words($article->post_content, 20, " [...]");
        }

        return $excerpt;
    }

    public function get_feedback($article, $type)
    {

        // TODO: Implement get_feedback() for cards.
        return null;
    }

    public function get_cards_for_day($article, $user_id = null)
    {

        global $currentArticle;

        if (!isset($article->activity_id)) {
            $this->init($article->article_id);
        }

        if (isset($article->article_id) && ($currentArticle->id != $article->article_id)) {

            $this->init($article->article_id);
        }


    }

    public function init($article_id = NULL)
    {

        global $currentArticle;

        /*
        if ( empty( $postId ) ) {


        }
        */
        if ((isset($currentArticle->id) && ($currentArticle->id != $article_id)) || !isset($currentArticle->id)) {

            $currentArticle = parent::init($article_id);
            dbg("e20rArticle::init() - Loaded settings for article ({$article_id})");

            $this->articleId = (!isset($currentArticle->id) ? false : $currentArticle->id);
            dbg("e20rArticle::init() -  Loaded global currentArticle and set it for article ID {$this->articleId}");
        } else {
            dbg("e20rArticle::init() - No need to load settings (previously loaded): ");
//		    dbg($currentArticle);
        }

        // dbg( $currentArticle );

        if (($this->articleId !== false) && ($currentArticle->id != $article_id)) {

            dbg("e20rArticle::init() - No article defined for this postId: {$currentArticle->id} & {$article_id}");
            $this->articleId = false;
        }

        return $this->articleId;
    }

    public function getExcerpt($articleId, $userId = null, $type = 'action', $in_card = false)
    {

        global $post;
        global $e20rTracker;
        global $e20rWorkout;

        global $current_user;
        global $currentProgram;

        $postId = null;
        $activityField = null;

        $oldPost = $post;

        if (is_null($userId)) {
            // Assuming current_user->ID..
            $userId = $current_user->ID;
        }

        switch ($type) {

            case 'action':
                $postId = $this->model->getSetting($articleId, 'post_id');
                $prefix = $this->model->getSetting($articleId, 'prefix');
                dbg("e20rArticle::getExcerpt() - Loaded post ID ($postId) for the action in article {$articleId}");

                if (-1 == $postId) {
                    dbg("e20rArticle::getExcerpt() - No activity excerpt to be found (no activity specified).");
                    return null;
                }

                break;

            case 'activity':

                $group = $e20rTracker->getGroupIdForUser($userId);

                dbg("e20rArticle::getExcerpt() - Searching for activities for {$articleId}");
                $actId = $this->getActivity($articleId, $userId);

                dbg("e20rArticle::getExcerpt() - Found activity: {$actId}");
                dbg($actId);

                if (false == $actId) {
                    $postId = -1;
                } else {

                    $postId = $actId;

                    /*
                    $activities = $e20rWorkout->find('id', $actId );
                    $has_access = array();


                    foreach ($activities as $activity) {

                        $has_access[$activity->id] = $e20rTracker->allowedActivityAccess($activity, $userId, $group);
                    }

                    foreach ($has_access as $actId => $access) {

                        if (true === $access['user']) {
                            dbg("e20rArticle::getExcerpt() - Found user specific access to activity {$actId}. Using it.");
                            $postId = $actId;
                            break;
                        }

                        if (true === $access['group']) {
                            dbg("e20rArticle::getExcerpt() - Found group specific access to activity {$actId}. Using it.");
                            $postId = $actId;
                            break;
                        }
                    }
                    */

                    $activityField = '<input type="hidden" id="e20r-activity-activity_id" value="' . $postId . '" name="e20r-activity-activity_id">';
                    $prefix = null; // Using NULL prefix for activities
                    dbg("e20rArticle::getExcerpt() - Loaded post ID ($postId) for the activity in article {$articleId}");
                }

                if (-1 == $postId) {
                    dbg("e20rArticle::getExcerpt() - No activity excerpt to be found (no activity specified).");
                    return null;
                }

                break;
        }

        dbg("e20rArticle::getExcerpt() - Post Id for article {$articleId}: {$postId}");

        if (empty($postId)) {
            return null;
        }

        $art = get_post($articleId);
        $post = get_post($postId);

        dbg("e20rArticle::getExcerpt() - Prefix for {$type}: {$prefix}");

        if (!empty($art->post_excerpt) && ('action' == $type)) {

            dbg("e20rArticle::getExcerpt() - Using the article summary.");
            $pExcerpt = $art->post_excerpt;
        } elseif (!empty($post->post_excerpt)) {

            dbg("e20rArticle::getExcerpt() - Using the post excerpt.");
            $pExcerpt = $post->post_excerpt;
        } else {

            dbg("e20rArticle::getExcerpt() - Using the post summary.");
            $pExcerpt = $post->post_content;
            $pExcerpt = wp_trim_words($pExcerpt, 30, " [...]");
        }

        $image = (has_post_thumbnail($post->ID) ? get_the_post_thumbnail($post->ID) : '<div class="noThumb"></div>');

        $pExcerpt = preg_replace("/\<br(\s+)\/\>/i", null, $pExcerpt);

        ob_start();
        ?>
        <h4>
            <?php if (false === $in_card): ?><span
                class="e20r-excerpt-prefix"><?php echo "{$prefix} "; ?></span><?php endif; ?><?php echo get_the_title($post->ID); ?>
        </h4>
        <?php echo !is_null($activityField) ? $activityField : null; ?>
        <p class="e20r-descr e20r-descr-text"><?php echo $pExcerpt; ?></p> <?php

        if ($type == 'action') {

            $url = get_permalink($post->ID);
        } else if ($type == 'activity') {

            $url = null;

            dbg("e20rArticle::getExcerpt() - Loading URL for activity...");

            $url = get_permalink($currentProgram->activity_page_id);
            dbg("e20rArticle::getExcerpt() - URL is: {$url}");

        } ?>
        <p class="e20r-descr e20r-descr-link">
        <a href="<?php echo $url; ?>" id="e20r-<?php echo $type; ?>-read-lnk" title="<?php get_the_title($post->ID); ?>">
            <?php _e('Click to read', 'e20tracker'); ?>
        </a>
        </p><?php

        wp_reset_postdata();

        $html = ob_get_clean();

        $post = $oldPost;

        return $html;
    }

    /**
     * Use the articleId to locate the
     * @param $articleId - The ID of the article containing the workout/activity for this lesson.
     *
     * @returns int - The post ID for the activity/workout.
     */
    public function getActivity($articleId, $userId = null)
    {

        global $e20rArticle;
        global $e20rWorkout;
        global $e20rTracker;

        $postId = null;

        // $excerpt = __( "We haven't found an activity for this day", "e20rtracker" );

        $aIds = $this->model->getSetting($articleId, 'activity_id');

        // No activity defined
        if (empty($aIds) || (!is_array($aIds) && (false == $aIds))) {

            dbg("e20rArticle::getActivity() - No defined activity for this article ({$articleId})");
            return false;
        }

        $delay = $this->model->getSetting($articleId, 'release_day');

        $mGroupId = $e20rTracker->getGroupIdForUser($userId);
        $activities = $e20rWorkout->find('id', $aIds, -1, 'IN');

        $post_date = $e20rTracker->getDateForPost($delay, $userId);

        dbg("e20rArticle::getActvitiy() - Date for post with delay {$delay} for user {$userId}: {$post_date}");

        $art_day_no = date('N', strtotime($post_date));
        dbg("e20rArticle::getActivity() - For article #{$articleId}, delay: {$delay}, on day: {$art_day_no}.");
//         dbg($activities);

        // Loop through all the defined activities for the $articleId
        foreach ($activities as $a) {

            dbg("e20rArticle::getActivity() - On day # {$delay} for article {$articleId} processing activity {$a->id}: {$art_day_no}");
            // dbg($a->days);

            if (in_array($art_day_no, $a->days)) {

                // The delay value for the $articleId releases the $articleId on one of the $activity days.
                dbg("e20rArticle::getActivity() - ID for an activity allowed on {$art_day_no}: {$a->id}");
                // $activity = $e20rWorkout->getActivity( $a->id );

                $has_access = array();

                $access_map = new stdClass();
                $access_map->user = array();
                $access_map->group = array();

                dbg("e20rArticle::getActivity() - Testing if {$userId} is in group or user list for activity #{$a->id}");

                $has_access = $e20rTracker->allowedActivityAccess($a, $userId, $mGroupId);
                $access_map->user[$a->id] = $has_access['user'];
                $access_map->group[$a->id] = $has_access['group'];

                $postId = array_search(true, $access_map->user);

                if ($postId) {
                    dbg("e20rArticle::getActivity() - Found an activity ({$postId}) that is assigned to the specific user ({$userId})");
                    return $postId;
                }

                $postId = array_search(true, $access_map->group);

                if (!empty($postId)) {
                    dbg("e20rArticle::getActivity() - Found an activity ({$postId}) that is assigned to the group ({$mGroupId}) that the user ({$userId}) is in");
                    return $postId;
                }
            }
        }

        return false;
    }

    public function getAssignments($articleId, $userId = null)
    {

        global $e20rAssignment;
        global $currentArticle;

        dbg("e20rArticle::getAssignments() - Need to load settings for {$articleId}");
        // $this->init( $articleId );
        $articleSettings = $this->model->loadSettings($articleId);

        // $articleSettings = $this->model->loadSettings( $this->articleId );

        // dbg($articleSettings);

        $assignment_ids = array();

        dbg("e20rArticle::getAssignments() - Loading assignments for article # {$articleId}");

        if (!empty($articleSettings->assignment_ids)) {

            dbg("e20rArticle::getAssignments() - Have predefined assignments for article");

            foreach ($articleSettings->assignment_ids as $assignmentId) {

                dbg("e20rArticle::getAssignments() - Loading assignment {$assignmentId} for article");

                // Load the user specific assignment data (if available. If not, load default data)
                $tmp = $e20rAssignment->load_userAssignment($articleId, $assignmentId, $userId);
                $assignment_ids[$tmp[0]->order_num] = $tmp[0];
            }
        } else {
            dbg("e20rArticle::getAssignments() - No defined explicit assignments for this article.");
            $assignment_ids[0] = $e20rAssignment->loadAssignment();
        }

        dbg("e20rArticle::getAssignments() - Sorting assignments for article # {$articleId} by order number");
        ksort($assignment_ids);

        return $assignment_ids;
    }

    public function remove_assignment_callback()
    {

        global $e20rAssignment;
        global $currentArticle;

        dbg("e20rArticle::remove_assignment_callback().");
        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-article-settings-nonce');
        dbg("e20rArticle::remove_assignment_callback() - Deleting assignment for article.");

        $articleId = isset($_POST['e20r-article-id']) ? intval($_POST['e20r-article-id']) : null;
        $assignmentId = isset($_POST['e20r-assignment-id']) ? intval($_POST['e20r-assignment-id']) : null;

        // $this->articleId = $articleId;
        $this->init($articleId);

        $artSettings = $currentArticle;
        dbg("e20rArticle::remove_assignment_callback() - Article settings for ({$articleId}): ");
        dbg($artSettings);

        $assignment = $e20rAssignment->loadAssignment($assignmentId);

        dbg("e20rArticle::remove_assignment_callback() - Updating Assignment ({$assignmentId}) settings & saving.");
        $assignment->article_id = null;

        dbg("e20rArticle::remove_assignment_callback() - Assignment settings for ({$assignmentId}): ");
        $e20rAssignment->saveSettings($articleId, $assignment);

        dbg("e20rArticle::remove_assignment_callback() - Updating Article settings for ({$articleId}): ");

        if (in_array($assignmentId, $artSettings->assignment_ids)) {

            $artSettings->assignment_ids = array_diff($artSettings->assignment_ids, array($assignmentId));
        }

        $this->model->set('assignment_ids', $artSettings->assignment_ids, $articleId);

        dbg("e20rArticle::remove_assignment_callback() - Generating the assignments metabox for the article {$articleId} definition");

        $toBrowser = array(
            'success' => true,
            'data' => $e20rAssignment->configureArticleMetabox($articleId),
        );

        if (!empty($toBrowser['data'])) {

            dbg("e20rArticle::remove_assignment_callback() - Transmitting new HTML for metabox");
            // wp_send_json_success( $html );

            // dbg($html);
            echo json_encode($toBrowser);
            wp_die();
        }

        dbg("e20rArticle::remove_assignment_callback() - Error generating the metabox html!");
        wp_send_json_error("No assignments found for this article!");
    }

    public function add_assignment_callback()
    {

        global $e20rAssignment;
        global $e20rTracker;

        global $currentArticle;

        $reloadPage = false;

        dbg("e20rArticle::add_assignment_callback().");
        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-article-settings-nonce');

        dbg("e20rArticle::add_assignment_callback() - Saving new assignment for article.");
        dbg($_POST);

        $articleId = isset($_POST['e20r-article-id']) ? $e20rTracker->sanitize($_POST['e20r-article-id']) : null;
        $postId = isset($_POST['e20r-assignment-post_id']) ? $e20rTracker->sanitize($_POST['e20r-assignment-post_id']) : null;
        $assignmentId = isset($_POST['e20r-assignment-id']) ? $e20rTracker->sanitize($_POST['e20r-assignment-id']) : null;
        $new_order_num = isset($_POST['e20r-assignment-order_num']) ? $e20rTracker->sanitize($_POST['e20r-assignment-order_num']) : null;

        if ($new_order_num <= 0) {

            dbg("e20rArticle::add_assignment_callback() - Resetting the requested order number to a valid value ( >0 ).");
            $new_order_num = 1;
        }

        dbg("e20rArticle::add_assignment_callback() - Article: {$articleId}, Assignment: {$assignmentId}, Assignment Order#: {$new_order_num}");

        // $this->articleId = $articleId;

        $post = get_post($articleId);
        setup_postdata($post);


        // Just in case we're working on a brand new article
        if ('auto-draft' == $post->post_status) {

            if (empty($post->post_title)) {

                $post->post_title = "Please update this title before updating";
            }

            $articleId = wp_insert_post($post);
            $reloadPage = true;
        }

        wp_reset_postdata();

        $this->model->loadSettings($articleId);

        // $artSettings = $this->model->getSettings();
        dbg("e20rArticle::add_assignment_callback() - Article settings for ({$articleId}): ");
        dbg($currentArticle);

        $assignment = $e20rAssignment->loadAssignment($assignmentId);
        $assignment->order_num = $new_order_num;
        $assignment->article_id = null;

        dbg("e20rArticle::add_assignment_callback() - Updating assignment settings for ({$assignmentId}), with new order {$new_order_num}");
        $e20rAssignment->saveSettings($assignmentId, $assignment);

        $ordered = array();
        $orig = $currentArticle->assignment_ids;
        $new = array();

        // Load assignments so we can sort (if needed)
        foreach ($currentArticle->assignment_ids as $aId) {

            $a = $e20rAssignment->loadAssignment($aId);

            if ($a->order_num == 0) {
                $a->order_num = 1;
            }
            $ordered[($a->order_num - 1)] = $a;
        }

        // Sort by order number.
        ksort($ordered);
        $orig = $ordered;

        dbg("e20rArticle::add_assignment_callback() - Sorted previously saved assignments:");
        dbg($ordered);

        // Are we asking to reorder the assignment?
        if ((isset($ordered[$new_order_num])) && ($assignmentId != $ordered[$new_order_num]->id)) {

            dbg("e20rArticle::add_assignment_callback() - Re-sorting list of assignments:");
            reset($ordered);
            $first = key($ordered);

            for ($i = $first; $i < $new_order_num; $i++) {

                if (isset($ordered[$i])) {
                    dbg("e20rArticle::add_assignment_callback() - Sorting assignment {$ordered[$i]->id} to position {$ordered[$i]->order_num}");
                    $ordered[$i]->order_num == $new_order_num;
                    $new[$i] = $ordered[$i];
                }
            }

            $new[$new_order_num] = $assignment;

            end($orig);
            $last = key($orig);

            for ($i = $new_order_num; $i <= $last; $i++) {

                if (isset($orig[$i])) {

                    dbg("e20rArticle::add_assignment_callback() - Sorting assignment {$orig[($i)]->id} to position " . ($orig[$i]->order_num + 1));
                    $orig[$i]->order_num = $orig[$i]->order_num + 1;
                    $new[($i + 1)] = $orig[$i];
                    $e20rAssignment->saveSettings($new[($i + 1)]->id, $new[($i + 1)]);
                }
            }

            $ordered = $new;
        } else {


            dbg("e20rArticle::add_assignment_callback() - Adding {$assignment->id} to the list of assignments");
            if (!isset($ordered[$assignment->order_num])) {
                dbg("e20rArticle::add_assignment_callback() - Using position {$assignment->order_num}");
                $ordered[$assignment->order_num] = $assignment;
            } else {

                $ordered[] = $assignment;
                end($ordered);
                $new_order = key($ordered);
                $ordered[$new_order]->order_num = ($new_order + 1);

                dbg("e20rArticle::add_assignment_callback() - Using position {$ordered[$new_order]->order_num }");
                $e20rAssignment->saveSettings($ordered[$new_order]->id, $ordered[$new_order]);
            }

        }

        dbg("e20rArticle::add_assignment_callback() - Sorted all of the assignments:");
        dbg($ordered);

        $new = array();

        foreach ($ordered as $a) {

            $new[] = $a->id;
        }

        if (empty($new) && (!is_null($assignmentId))) {

            dbg("e20rArticle::add_assignment_callback() - No previously defined assignments. Adding the new one");
            $new = array($assignmentId);
        }

        dbg("e20rArticle::add_assignment_callback() - Assignment list to be used by {$articleId}:");
        dbg($new);

        dbg("e20rArticle::add_assignment_callback() - Updating Article settings for ({$articleId}): ");
        $currentArticle->assignment_ids = $new;

        dbg("e20rArticle::add_assignment_callback() - Saving Article settings for ({$articleId}): ");
        dbg($currentArticle);

        $this->saveSettings($articleId, $currentArticle);

        dbg("e20rArticle::add_assignment_callback() - Generating the assignments metabox for the article {$articleId} definition");

        $html = $e20rAssignment->configureArticleMetabox($articleId, true);

        dbg("e20rArticle::add_assignment_callback() - Transmitting new HTML for metabox");
        wp_send_json_success(array('html' => $html, 'reload' => $reloadPage));

        // dbg("e20rArticle::add_assignment_callback() - Error generating the metabox html!");
        // wp_send_json_error( "No assignments found for this article!" );
    }

    /**
     * Save the Article Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific article.
     *
     * @return bool - True if successful at updating article settings
     */
    public function saveSettings($articleId, $settings = null)
    {

        global $e20rAssignment;
        global $currentArticle;
        global $e20rTracker;
        global $post;

        if (empty($articleId)) {

            dbg("e20rArticle::saveSettings() - No article ID supplied");
            return false;
        }

        if (is_null($settings) && ((!isset($post->post_type)) || ($post->post_type !== $this->cpt_slug))) {

            dbg("e20rArticle::saveSettings() - Not an article. ");
            return $articleId;
        }

        if (empty($articleId)) {

            dbg("e20rArticle::saveSettings() - No post ID supplied");
            return false;
        }

        if (wp_is_post_revision($articleId)) {

            return $articleId;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {

            return $articleId;
        }

        $savePost = $post;

        if (empty($settings)) {

            dbg("e20rArticle::saveSettings()  - Saving metadata from edit.php page, related to the {$this->type} post_type");

            // $this->model->init( $articleId );

            $settings = $this->model->loadSettings($articleId);
            $defaults = $this->model->defaultSettings();

            dbg($settings);

            if (!$settings) {

                $settings = $defaults;
            }

            foreach ($settings as $field => $setting) {

                $tmp = isset($_POST["e20r-article-{$field}"]) ? $e20rTracker->sanitize($_POST["e20r-article-{$field}"]) : null;

                dbg("e20rArticle::saveSettings() - Page data : {$field}->");
                dbg($tmp);

                if ('assignment_ids' == $field) {

                    dbg("e20rArticle::saveSettings() - Process the assignments array");

                    if (empty($tmp[0])) {

                        dbg("e20rArticle::saveSettings() - Assignments Array: The assignments key contains no data");
                        dbg("e20rArticle::saveSettings() - Create a new default assignment for the article ID: {$currentArticle->id}");

                        //Generate a default assignment for this article.
                        $status = $e20rAssignment->createDefaultAssignment($currentArticle);

                        if (false !== $status) {

                            $tmp[0] = $status;
                        }

                    } else {

                        foreach ($tmp as $k => $assignmentId) {

                            if (is_null($assignmentId)) {

                                dbg("e20rArticle::saveSettings() - Assignments Array: Setting key {$k} has no ID}");
                                dbg("e20rArticle::saveSettings() - Create a new default assignment for this article ID: {$settings->id}");

                                //Generate a default assignment for this article.
                                $assignmentId = $e20rAssignment->createDefaultAssignment($settings);

                                if (false !== $assignmentId) {

                                    dbg("e20rArticle::saveSettings() - Replacing empty assignment key #{$k} with value {$assignmentId}");
                                    $tmp[$k] = $assignmentId;
                                }
                            }
                        }

                        dbg("e20rArticle::saveSettings() - Assignments array after processing: ");
                        dbg($tmp);
                    }
                }

                if (empty($tmp)) {

                    $tmp = $defaults->{$field};
                }

                $settings->{$field} = $tmp;
            }

            // Add post ID (article ID)
            $settings->id = isset($_REQUEST["post_ID"]) ? intval($_REQUEST["post_ID"]) : null;

            if (!$this->model->saveSettings($settings)) {

                dbg("e20rArticle::saveSettings() - Error saving settings!");
            }

        } elseif (get_class($settings) != 'WP_Post') {

            dbg("e20rArticle::saveSettings() - Received settings from calling function.");

            if (!$this->model->saveSettings($settings)) {

                dbg("e20rArticle::saveSettings() - Error saving settings!");
            }

            $post = get_post($articleId);

            setup_postdata($post);

            $this->model->set('question', get_the_title());
            $this->model->set('descr', get_the_excerpt());

            wp_reset_postdata();

        }

        $post = $savePost;

    }

    public function getDelayValue_callback()
    {

        global $e20rTracker;

        dbg("e20rArticle::getDelayValue() - Callback initiated");

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-article-settings-nonce');

        dbg("e20rArticle::getDelayValue() - Nonce is OK");

        $postId = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : null;

        if (!$postId) {
            wp_send_json_error('Error: Not a valid Post/Page');
        }

        $dripFeedDelay = $e20rTracker->getDripFeedDelay($postId);

        if ($dripFeedDelay) {
            wp_send_json_success(array(
                'delay' => $dripFeedDelay,
                'nodelay' => false
            ));
        }

        wp_send_json_success(array('nodelay' => true));
    }

    public function editor_metabox_setup($post)
    {

        global $e20rAssignment;

        remove_meta_box('postexcerpt', 'e20r_articles', 'side');
        remove_meta_box('wpseo_meta', 'e20r_articles', 'side');
        add_meta_box('postexcerpt', __('Article Summary'), 'post_excerpt_meta_box', 'e20r_articles', 'normal', 'high');

        add_meta_box('e20r-tracker-article-settings', __('Article Settings', 'e20rtracker'), array(&$this, "addMeta_Settings"), 'e20r_articles', 'normal', 'high');
        add_meta_box('e20r-tracker-answer-settings', __('Assignments', 'e20rtracker'), array(&$e20rAssignment, "addMeta_answers"), 'e20r_articles', 'normal', 'high');
        // add_meta_box('e20r-tracker-article-checkinlist', __('Check-in List', 'e20rtracker'), array( &$this, "addMeta_SettingsCheckin" ), 'e20r_articles', 'normal', 'high');

    }

    public function addMeta_Settings()
    {

        global $post;

        $savePost = $post;

        $def_status = array(
            'publish',
            'pending',
            'draft',
            'future',
            'private'
        );

        // Query to load all available settings (used with check-in definition)
        $query = array(
            'post_type' => apply_filters('e20r_tracker_article_types', array('post', 'page')),
            'post_status' => apply_filters('e20r_tracker_article_post_status', $def_status),
            'posts_per_page' => -1,
        );

        wp_reset_query();

        //  Fetch Programs
        $lessons = new WP_Query($query);

        if (empty($lessons)) {

            dbg("e20rArticle::addMeta_Settings() - No posts/pages/lessons found!");
        }

        dbg("e20rArticle::addMeta_Settings() - Loaded " . $lessons->found_posts . " posts/pages/lessons");
        dbg("e20rArticle::addMeta_Settings() - Loading settings metabox for article page {$post->ID} or {$savePost->ID}?");

        $settings = $this->model->loadSettings($post->ID);

        $post = $savePost;

        ob_start();
        ?>
        <div id="e20r-article-settings">
            <?php echo $this->view->viewArticleSettings($settings, $lessons); ?>
        </div>
        <?php

        echo ob_get_clean();
        // ob_end_clean();
    }

    public function interviewCompleteLabel($title, $is_complete)
    {

        return $this->view->viewInterviewComplete($title, $is_complete);
    }

    /**
     * Filters the content for a post to check whether ot inject e20rTracker Article info in the post/page.
     *
     * @param $content - Content to prepend data to if the article meta warrants is.
     *
     * @return string -- The content to display on the page/post.
     */
    public function contentFilter($content)
    {
        $new_messages = null;
        $md_alert = null;
        $update_reminder = null;

        dbg("e20rArticle::contentFilter() - Processing the_content() filter");

        // Quit if this isn't a single-post display and we're not in the main query of wordpress.
        if (!is_singular() && !is_main_query() || !is_user_logged_in() ) {
            return $content;
        }

        dbg("e20rArticle::contentFilter() - Processing a single page and we're in the main query");
        /*
	    if ( has_shortcode( $content, 'weekly_progress') ||
            has_shortcode( $content, 'progress_overview' ) ||
            has_shortcode( $content, 'daily_progress') ||
            has_shortcode( $content, 'e20r_activity_archive') ) {
		    // Process in shortcode actions
		    return $content;
	    }
        */
        if (has_shortcode($content, 'e20r_profile') ||
            has_shortcode($content, 'progress_overview') ||
            has_shortcode($content, 'e20r_activity_archive')
        ) {
            // Process in shortcode actions
            return $this->view->new_message_warning() . $content;
        }

        global $post;
        global $current_user;
        global $e20rMeasurements;
        global $e20rProgram;
        global $e20rTracker;
        global $e20rAction;
        global $e20rClient;

        global $currentArticle;
        global $currentProgram;

        /*	    if ( ! in_array( $post->post_type, $e20rTracker->trackerCPTs() ) ) {
                    return $content;
                }
        */

        $article_id = isset($_REQUEST['article-id']) ? $e20rTracker->sanitize($_REQUEST['article-id']) : null;
        $for_date = isset($_REQUEST['for-date']) ? $e20rTracker->sanitize($_REQUEST['for-date']) : null;
        $program_id = isset($_REQUEST['program-id']) ? $e20rTracker->sanitize($_REQUEST['program-id']) : null;

        if (is_null($for_date)) {
            $for_date = $e20rTracker->sanitize(get_query_var('article_date', null));
            dbg("e20rArticle::contentFilter() - Loaded date for article: {$for_date}");

        }

        if (!is_null($program_id)) {

            $e20rProgram->setProgram($program_id);
        } else {

            $e20rProgram->getProgramIdForUser($current_user->ID);
        }

        /*        dbg("e20rArticle::contentFilter() - Is the user attempting to access the intake form: {$currentProgram->intake_form}");
                if (isset($currentProgram->intake_form) && ($currentProgram->intake_form == $post->ID) && is_user_logged_in()) {

                    dbg("e20rArticle::contentFilter() - Attempting to access the intake form...");

                    $today = current_time('timestamp');
                    $two_months = strtotime("{$currentProgram->startdate} + 2 months");

                    if (($two_months < $today) && $e20rClient->completeInterview($current_user->ID)) {

                        dbg("e20rArticle::contentFilter() - WARNING: User started program more than two months ago and is attempting to access the completed interview. Redirecting");
                        wp_redirect(get_permalink($currentProgram->dashboard_page_id));
                    }
                }
        */

        if (!empty($currentProgram->sales_page_ids) && in_array($post->ID, $currentProgram->sales_page_ids) && is_user_logged_in()) {

            dbg("e20rArticle::contentFilter() - WARNING: Logged in user requested sales page, suspect they want the dashboard...");
            if (isset($currentProgram->dashboard_page_id)) {

                $to_dashboard = get_permalink($currentProgram->dashboard_page_id);
                wp_redirect($to_dashboard, 302);
            }
        }

        $program_pages = array();

        $pgm_pages = array(
            'dashboard_page_id',
            'activity_page_id',
            'measurements_page_id',
            'progress_page_id',
        );

        foreach ($pgm_pages as $key) {

            if (!empty($currentProgram->{$key})) {

                if (!is_array($currentProgram->{$key})) {

                    $program_pages[] = $currentProgram->{$key};
                } else {

                    foreach ($currentProgram->{$key} as $id) {

                        $program_pages[] = $id;
                    }
                }
            }
        }


        if ((!empty($currentProgram->dashboard_page_id) && in_array($post->ID, $program_pages))) {

            dbg("e20rArticle::contentFilter() - Check whether this user should have access to the dashboard page for their program (yet!)");

            if (function_exists('pmpro_getMemberStartdate')) {

                $user_startdate = date_i18n('Y-m-d', pmpro_getMemberStartdate($current_user->ID));
                $today = date_i18n('Y-m-d', current_time('timestamp'));

                if ($today < $user_startdate) {

                    dbg("e20rArticle::contentFitler() - Warning: User shouldn't have access to this dashboard yet. Redirect them to the start page(s)?");
                    if (!empty($currentProgram->welcome_page_id)) {

                        wp_redirect(get_permalink($currentProgram->welcome_page_id));
                    }
                }
            }
        }

        dbg("e20rArticle::contentFilter() - loading article settings for post ID {$post->ID} and article id: " . (is_null($article_id) ? 'null' : $article_id));
        $articles = array();

        if (!is_null($article_id)) {

            $articles = $this->find('id', $article_id, $currentProgram->id);
        }

        if (empty($articles) && is_null($article_id) && !empty($for_date)) {

            dbg("e20rArticle::contentFilter() - Searching for article based on date argument passed by calling entity/page: {$for_date}");
            $delayVal = $e20rTracker->getDelay($for_date, $current_user->ID);
            $articles = $this->findArticles('release_day', $delayVal, $currentProgram->id);
            dbg("e20rArticle::contentFilter() - Found: ");
            dbg($articles);
        }

        if (empty($articles) && is_null($article_id) && is_null($for_date)) {

            dbg("e20rArticle::contentFilter() - Searching for article based on the ID of the current post: {$post->ID}");
            $articles = $this->findArticles('post_id', $post->ID, $currentProgram->id);
        }

        if (empty($articles) && is_null($article_id) && is_null($for_date)) {

            $dayNo = $e20rTracker->getDelay('now', $current_user->ID);
            dbg("e20rArticle::contentFilter() - Searching for article based on the current delay value: {$dayNo}");

            foreach ($articles as $article) {

                if (in_array($currentProgram->id, $article->program_ids)) {

                    if ($dayNo == $article->release_day) {

                        dbg("e20rArticle::contentFilter() - Found the correct article for post {$post->ID}");
                        $this->init($article->id);
                    }
                }
            }
        }


        if (empty($articles)) {

            dbg("e20rArticle::contentFilter() - No article defined for this content. Exiting the filter.");
            return $content;

        } else {

            dbg("e20rArticle::contentFilter() - Found article(s). Using first in list returned by search.");
            $article = array_pop($articles);
            $this->init($article->id);
        }


        if (!$e20rTracker->hasAccess($current_user->ID, $currentArticle->post_id)) {

            dbg("e20rArticle::contentFilter() - User doesn't have access to this post/page. Exiting the filter.");
            return $content;
        }

        dbg("e20rArticle::contentFilter() - Restoring article: {$article->id} as the current article after access check");

        dbg("e20rArticle::contentFilter() - User HAS access to post/page: {$post->ID}.");
        $measured = false;

        $rDay = $currentArticle->release_day;
        $rDate = $this->releaseDate($currentArticle->id);

        $programId = $currentProgram->id;

        dbg("e20rArticle::contentFilter() - Release Date for article: {$rDate} calculated from {$rDay}");

        $md = $this->isMeasurementDay($currentArticle->id);

        $info = '';

        // && ( !$md )
        if ($e20rAction->hasCompletedLesson($currentArticle->id, $post->ID, $current_user->ID)) {

            dbg("e20rArticle::contentFilter() - Processing a defined article to see if lesson is completed. This is not for a measurement day.");

            $currentArticle->complete = true;
        }

        $lesson_complete = $this->view->viewLessonComplete($rDay, false, $currentArticle->id);
        // $content = $data . $content;

        if ($currentArticle->post_id == $post->ID) {

            $new_messages = $this->view->new_message_warning();
        }

        if ($md) {

            $measured = $e20rMeasurements->areCaptured($currentArticle->id, $programId, $current_user->ID, $rDate);
            dbg("e20rArticle::contentFilter() - Result from e20rMeasurements::areCaptured: ");
            dbg($measured);

            // dbg("e20rArticle::contentFilter() - Settings: " . print_r( $settings, true));
            dbg("e20rArticle::contentFilter() - Check whether it's a measurement day or not: {$md} ");

            if ($md && !$measured['status']) {

                dbg("e20rArticle::contentFilter() - It's a measurement day!");
                $md_alert = $this->view->viewMeasurementAlert($this->isPhotoDay($currentArticle->id), $rDay, $currentArticle->id);
            }

            if ($md && $measured['status']) {

                dbg("e20rArticle::contentFilter() - Measurement day, and we've measured.");
                $md_alert = $this->view->viewMeasurementComplete($rDay, $md, $currentArticle->id);
            }
        }

        if (true === ($is_complete = $e20rClient->completeInterview($current_user->ID) && ($post->ID == $currentProgram->intake_form))) {

            dbg("e20rArticle::contentFilter() - User is viewing the Welcome interview page & their interview is saved already");
            $interview_title = get_the_title($currentProgram->intake_form);
            $update_reminder = $this->view->viewInterviewComplete($interview_title, $is_complete);
        }

        // Construct content based on available data.

        if ( !empty($lesson_complete)) {

            dbg("e20rArticle::contentFilter() - Adding lesson complete flag");
            $info .= $lesson_complete;
        }

        if ( !empty( $update_reminder ) ) {
            dbg("e20rArticle::contentFilter() - Adding update reminder for welcome interview");
            $info .= $update_reminder;
        }

        if (!empty( $new_messages ) ) {
            dbg("e20rArticle::contentFilter() - Adding new messages warning");
            $info .= $new_messages;
        }

        if ( !empty( $md_alert ) ) {
            dbg("e20rArticle::contentFilter() - Adding Weekly Progress reminder");
            $info .= $md_alert;
        }

        dbg("e20rArticle::contentFilter() - Content being returned.");
        return $info . $content;
    }

    public function findArticles($key = 'id', $val = null, $programId = -1, $comp = '=', $dont_drop = false, $type = 'numeric')
    {

        return $this->model->find($key, $val, $programId, $comp, 'DESC', $dont_drop, $type);
    }

    public function releaseDate($articleId)
    {

        global $e20rTracker;
        global $currentArticle;

        if ((empty($articleId) || ($articleId == -1) || ($articleId == 0))) {

            $delay = isset($_POST['e20r-checkin-day']) ? $e20rTracker->sanitize($_POST['e20r-checkin-day']) : null;

            if (isset($delay)) {
                dbg("e20rArticle::releaseDate() No articleId specified... Using delay value from _POST");
                $release_date = $e20rTracker->getDateForPost($delay);

                return $release_date;
            }
            dbg("e20rArticle::releaseDate() No articleId specified and no delay value found... returning FALSE");
            return false;
        }

        if (!isset($currentArticle->id) || ($currentArticle->id != $articleId)) {
            dbg("e20rArticle::releaseDate() - currentArticle is NOT defined.");
            $release_date = $e20rTracker->getDateForPost($this->model->getSetting($articleId, 'release_day'));
        } else {
            dbg("e20rArticle::releaseDate() - currentArticle is defined.");
            $release_date = $e20rTracker->getDateForPost($currentArticle->release_day);
        }

        dbg("e20rArticle::releaseDate: {$release_date}");

        return (empty($release_date) ? false : $release_date);
    }

    public function isMeasurementDay($articleId = null)
    {

        global $currentArticle;

        if (is_null($articleId)) {

            $articleId = $currentArticle->id;
        }

        return ($this->model->getSetting($articleId, 'measurement_day') == 0 ? false : true);

    }

    public function isPhotoDay($articleId)
    {

        dbg("e20rArticle::isPhotoDay() - getting photo_day setting for {$articleId}");

        $setting = $this->model->getSetting($articleId, 'photo_day');
        dbg("e20rArticle::isPhotoDay() - Is ({$articleId}) on a photo day ({$setting})? " . ($setting == 0 ? 'No' : 'Yes'));

        if (empty($setting)) {
            dbg("e20rArticle::isPhotoDay() - photo_day setting ID empty/null.");
            return false;
        } else {
            return ($setting != 0 ? true : false);
        }
        // return ( is_null( $retVal ) ? false : true );
    }

    public function load_lesson($article_id = null, $reading_time = true)
    {

        global $currentArticle;

        global $current_user;
        $html = null;

        if (is_null($article_id)) {

            if (!isset($currentArticle->id)) {

                return null;
            }
        }

        if ($article_id != $currentArticle->id) {

            dbg("e20rArticle::load_lesson() - Requested article ID != currentArticle id");

            $this->init($article_id);
        }
        global $e20rTracker;

        $post = get_post($currentArticle->post_id);
        setup_postdata($post);

        ob_start(); ?>
        <article class="e20r-article-lesson">
            <div class="e20r-article-lesson-header clear-after">
                <span class="e20r-article-lesson-title">
                    <h2><?php echo apply_filters('the_title', $post->post_title); ?></h2>
                </span>
                <span class="e20r-article-lesson-date">
                    <?php
                    $when = $e20rTracker->getDateFromDelay(($currentArticle->release_day - 1), $current_user->ID);
                    echo date_i18n("M jS", strtotime($when));
                    ?>
                </span>
            </div>
            <div class="e20r-article-lesson">
                <?php
                if ($reading_time) { ?>
                    <div
                        class="e20r-article-lesson-readingtime"><?php echo $this->reading_time($post->post_content); ?></div>
                    <br/><?php
                } ?>
                <?php echo apply_filters('the_content', $post->post_content); ?>
            </div>
        </article>
        <?php

        wp_reset_postdata();

        $html = ob_get_clean();

        return $html;
    }

    public function reading_time($content)
    {

        $words = str_word_count(strip_tags($content));
        $minutes = floor($words / 180);
        $seconds = floor($words % 180 / (180 / 60));

        $estimated_time = '<em>Time to read (approximately): </em> <strong>';

        if (1 <= $minutes) {

            $estimated_time .= $minutes;

            if ($seconds >= 30) {
                $estimated_time .= " &dash; " . ($minutes + 1);
            }

            $estimated_time .= ' minute' . (($minutes <= 1 && $seconds < 30) ? '' : 's');
        } else {

            $estimated_time .= 'Less than a minute';
        }

        $estimated_time .= '</strong>';

        return $estimated_time;
    }

    public function shortcode_article_summary($attributes = null)
    {
        global $e20rProgram;
        global $e20rTracker;

        global $currentProgram;
        global $currentArticle;

        global $current_user;
        global $post;

        $html = null;
        $article = null;
        $article_id = null;

        $for_date = $e20rTracker->sanitize(get_query_var('article_date'));
        dbg("e20rArticle::shortcode_article_summary() - Loading article summary based on shortcode: {$for_date}");

        if (!is_user_logged_in()) {

            auth_redirect();
            wp_die();
        }

        if (!empty($_REQUEST)) {

            check_ajax_referer('e20r-checkin-data', 'e20r-checkin-nonce');
            dbg("e20rArticle::shortcode_article_summary() - Received valid Nonce");

            $article_id = isset($_REQUEST['article-id']) ? $e20rTracker->sanitize($_REQUEST['article-id']) : null;
            $for_date = isset($_REQUEST['for-date']) ? $e20rTracker->sanitize($_REQUEST['for-date']) : null;
            $program_id = isset($_REQUEST['program-id']) ? $e20rTracker->sanitize($_REQUEST['program-id']) : null;

        }

        if (empty($program_id)) {

            dbg("e20rArticle::shortcode_article_summary() - Loading program info for user {$current_user->ID}");
            $e20rProgram->getProgramIdForUser($current_user->ID);
        } else {
            dbg("e20rArticle::shortcode_article_summary() - Loading program config for {$program_id}");
            $e20rProgram->init($program_id);
        }

        if (!empty($for_date)) {

            dbg("e20rArticle::shortcode_article_summary() - Received date: {$for_date} and will calculate # of days from that");
            $days_since_start = $e20rTracker->daysBetween(strtotime($currentProgram->startdate), strtotime($for_date));
        } else {
            $days_since_start = $e20rTracker->getDelay('now', $current_user->ID);
        }

        dbg("e20rArticle::shortcode_article_summary() - using delay value of: {$days_since_start}");

        if (is_null($article_id)) {

            global $post;

            $articles = $this->model->find('post_id', $post->ID, $currentProgram->id);

            dbg("e20rArticle::shortcode_article_summary() - Found " . count($articles) . " for this post ID ({$post->ID})");

            foreach ($articles as $a) {

                if ($a->release_day == $days_since_start) {

                    dbg("e20rArticle::shortcode_article_summary() - Found article {$a->id} and release day {$a->release_day}");
                    $currentArticle = $a;
                    break;
                }
            }

            if (!isset($currentArticle->id) || ($currentArticle->id == 0)) {
                dbg("e20rArticle::shortcode_article_summary() - No article ID specified by calling post/page. Not displaying anything");
                return;
            }
        }

        dbg("e20rArticle::shortcode_article_summary() - Loading article summary shortcode for: {$currentArticle->id}");

        // $program_id = $e20rProgram->getProgramIdForUser($current_user->ID);
        // $days_since_start = $e20rTracker->getDelay('now', $current_user->ID);

        if (!isset($currentArticle->id)) { // || !empty( $article_id ) && ( $article_id != $currentArticle->id )

            $articles = $this->model->find('id', $article_id, $currentProgram->id);
            dbg("e20rArticle::shortcode_article_summary() - Found " . count($articles) . " article(s) with post ID {$post->ID}");
            $article = array_pop($articles);
            $article_id = $article->id;
            $currentArticle = $article;
        }


        if (!isset($currentArticle->id) && (!is_null($article_id))) {

            dbg("e20rArticle::shortcode_article_summary() - Configure article settings (not needed?) ");
            $this->init($article_id);
        }

        $defaults = $this->model->defaultSettings();

        $days_of_summaries = (!isset($currentArticle->max_summaries) || is_null($currentArticle->max_summaries) ? $defaults->max_summaries : $currentArticle->max_summaries);

        $tmp = shortcode_atts(array(
            'days' => $days_of_summaries,
        ), $attributes);

        dbg("e20rArticle::shortcode_article_summary() - Article # {$currentArticle->id} needs to locate {$tmp['days']} or {$days_of_summaries} days worth of articles to pull summaries from, ending on day # {$currentArticle->release_day}");

        if ($days_of_summaries != $tmp['days']) {

            $days_of_summaries = $tmp['days'];
        }

        $start_day = ($currentArticle->release_day - $days_of_summaries);
        $gt_days = ($currentArticle->release_day - $days_of_summaries);

        $start_TS = strtotime("{$currentProgram->startdate} +{$start_day} days");

        dbg("e20rArticle::shortcode_article_summary() - Searching for articles with release_day between start: {$start_day} and end: {$currentArticle->release_day}");

        $history = $this->find('release_day', array(($start_day - 1), $currentArticle->release_day), $currentProgram->id, 'BETWEEN');

        dbg("e20rArticle::shortcode_article_summary() - Fetched " . count($history) . " articles to pull summaries from");
        // dbg($history);

        $summary = array();

        foreach ($history as $k => $a) {

            $new = array(
                'title' => null,
                'summary' => null,
                'day' => null
            );

            if (!empty($a->post_id)) {

                $p = get_post($a->post_id);
                $art = get_post($a->id);

                dbg("e20rArticle::shortcode_article_summary() - Loading data for post {$a->post_id} vs {$p->ID}");

                $new['day'] = $a->release_day;
                $new['title'] = $p->post_title;

                if (!empty($art->post_excerpt)) {

                    dbg("e20rArticle::shortcode_article_summary() - Using the article summary.");
                    $new['summary'] = $art->post_excerpt;

                } elseif (!empty($p->post_excerpt)) {

                    dbg("e20rArticle::shortcode_article_summary() - Using the post excerpt.");
                    $new['summary'] = $p->post_excerpt;
                } else {

                    dbg("e20rArticle::shortcode_article_summary() - Using the post summary.");
                    $new['summary'] = $p->post_content;
                    $new['summary'] = wp_trim_words($new['summary'], 30, " [...]");
                }

                // $new['summary'] = $this->getExcerpt( $a->id, $current_user->ID );

                // if (!empty($new['title']) && !empty($new['summary'])) {

                dbg("e20rArticle::shortcode_article_summary() - Current day: {$currentArticle->release_day} + Last release day to include: {$gt_days}.");

                if (($new['day'] > $gt_days)) {

                    if (($a->measurement_day != true) && ($a->summary_day != true)) {

                        dbg("e20rArticle::shortcode_article_summary() - Adding {$new['title']} (for day# {$new['day']}) to list of posts to summarize");
                        $summary[$a->release_day] = $new;
                    }
                }

                $new = array();
                $a = null;
                wp_reset_postdata();
            }
        }

        ksort($summary);

        dbg("e20rArticle::shortcode_article_summary() - Original prefix of {$currentArticle->prefix}");
        $prefix = lcfirst(preg_replace('/\[|\]/', '', $currentArticle->prefix));
        dbg("e20rArticle::shortcode_article_summary() - Scrubbed prefix: {$prefix}");
        // dbg($summary);

        $summary_post = get_post($currentArticle->id);
        $info = null;

        wp_reset_postdata();

        if (!empty($summary_post->post_content)) {

            $info = wpautop($summary_post->post_content_filtered);
        }

        // Since we're saving the array using the delay day as the key we'll have to do some jumping through hoops
        // to get the right key for the last day in the list.
        $k_array = array_keys($summary);
        $last_day_key = $k_array[(count($summary) - 1)];
        $last_day_summary = $summary[$last_day_key];
        $end_day = $last_day_summary['day'];

        dbg("e20rArticle::shortcode_article_summary() - Using end day for summary period: {$end_day}");
        // dbg($last_day_summary);

        $end_TS = strtotime("{$currentProgram->startdate} +{$end_day} days");

        $html = $this->view->view_article_history($prefix, $summary, $start_TS, $end_TS, $info);

        return $html;
    }
    /*
    public function addArticle( $obj ) {

        $articles = $this->findArticles( 'id', $obj->id );

        if ( $key !== false ) {

            $this->article_list[ $key ] = $obj;
        }
        else {

            $this->article_list[] = $obj;
        }
    }

    public function getArticles() {

        return $this->article_list;
    }

    public function getArticle( $id ) {

        if ( $key = $this->findArticle( 'id', $id ) !== false ) {

            return $this->article_list[$key];
        }

        return false;
    }

    public function getID() {

        return $this->post_id;
    }

    public function setId( $id = null ) {

        if ( $id === null ) {

            global $post;

            $id = $post->ID;
        }

        $this->init( $id );
    }
*/


} 