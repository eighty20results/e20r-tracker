<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rArticle extends e20rSettings {

    /* Articles contain list of programs it belongs to along with the PostID and all metadata.*/
    protected $articleId;

    protected $model;
    protected $view;

    public function __construct() {

        dbg("e20rArticle::__construct() - Initializing Article class");
        $this->model = new e20rArticleModel();
        $this->view = new e20rArticleView();

        parent::__construct( 'article', 'e20r_articles', $this->model, $this->view );
    }

    public function init( $article_id = NULL ) {

	    global $currentArticle;

        /*
        if ( empty( $postId ) ) {


        }
        */
        if ( ( isset( $currentArticle->id) && ($currentArticle->id != $article_id ) ) || !isset($currentArticle->id) ) {

	        $currentArticle = parent::init( $article_id );
            dbg("e20rArticle::init() - Loaded settings for article ({$article_id})");

	        $this->articleId = ( ! isset($currentArticle->id) ? false : $currentArticle->id);
            dbg("e20rArticle::init() -  Loaded global currentArticle and set it for article ID {$this->articleId}");
        }
	    else {
		    dbg("e20rArticle::init() - No need to load settings (previously loaded): ");
//		    dbg($currentArticle);
	    }

        // dbg( $currentArticle );

        if ( ( $this->articleId  !== false ) && ( $currentArticle->id != $article_id ) ) {

	        dbg("e20rArticle::init() - No article defined for this postId: {$currentArticle->id} & {$article_id}");
            $this->articleId = false;
        }

        return $this->articleId;
    }

	public function emptyArticle() {

		return $this->model->defaultSettings();
	}

	public function findArticlesNear( $key, $value, $programId = -1, $comp = '<=', $sort_order = 'DESC', $limit = 1, $type = 'numeric' ) {

		// findClosestArticle( $key, $value, $programId = -1, $comp = '<=', $limit = 1, $type = 'numeric', $sort_order = 'DESC' )
		return $this->model->findClosestArticle( $key, $value, $programId, $comp, $limit, $type , $sort_order );
	}

    public function findArticles( $key = 'id', $val = null, $type = 'numeric', $programId = -1, $comp = '=', $dont_drop = false ) {

        return $this->model->find( $key, $val, $type, $programId, $comp, 'DESC', $dont_drop );
    }

    public function getCheckins( $aConfig ) {

        dbg("e20rArticle::getCheckins() - Get array of checkin IDs for {$aConfig->articleId}");

        $checkin_ids = $this->model->getSetting( $aConfig->articleId, 'checkin_ids' );
        // $activity = $this->model->getSetting( $aConfig->articleId, 'activity_id' );

        if ( ! is_array( $checkin_ids ) ) {
            // $setting = array_merge( array( $checkin ), array( $activity ) );
            $checkin = array( $checkin_ids );
        }

        if ( empty( $checkin_ids ) /* && is_null( $activity ) */ ) {
            dbg("e20rArticle::getCheckins() - No check-in IDs found for this article ({$aConfig->articleId})");
            return false;
        }

        return $checkin_ids;
    }

    public function releaseDate( $articleId ) {

        global $e20rTracker;
        global $currentArticle;

        if ( ( empty( $articleId) || ( $articleId == -1 ) || ( $articleId == 0) ) ) {

            $delay = isset( $_POST['e20r-checkin-day'] ) ? $e20rTracker->sanitize( $_POST['e20r-checkin-day'] ) : null;

            if ( isset( $delay ) ) {
                dbg("e20rArticle::releaseDate() No articleId specified... Using delay value from _POST");
                $release_date = $e20rTracker->getDateForPost( $delay );

                return $release_date;
            }
            dbg("e20rArticle::releaseDate() No articleId specified and no delay value found... returning FALSE");
            return false;
        }

        if ( empty( $currentArticle ) ) {
            dbg("e20rArticle::releaseDate() - currentArticle is NOT defined.");
            $release_date = $e20rTracker->getDateForPost( $this->model->getSetting( $articleId, 'release_day' ) );
        }
        else {
            dbg("e20rArticle::releaseDate() - currentArticle is defined.");
            $release_date = $e20rTracker->getDateForPost( $currentArticle->release_day );
        }

        dbg( "e20rArticle::releaseDate: {$release_date}" );

        return ( empty($release_date) ? false : $release_date );
    }

    public function releaseDay( $articleId ) {

        return $this->model->getSetting( $articleId, 'release_day' );
    }

    public function isMeasurementDay( $articleId = null ) {

        global $currentArticle;

        if ( is_null( $articleId ) ) {

            $articleId = $currentArticle->id;
        }

        return ( $this->model->getSetting( $articleId, 'measurement_day' ) == 0 ? false : true );

    }

    public function isPhotoDay( $articleId ) {

        dbg("e20rArticle::isPhotoDay() - getting photo_day setting for {$articleId}");

        $setting = $this->model->getSetting( $articleId, 'photo_day' );
        dbg("e20rArticle::isPhotoDay() - Is ({$articleId}) on a photo day ({$setting})? " . ($setting==0 ? 'No' : 'Yes') );

        if ( empty($setting) ) {
            dbg("e20rArticle::isPhotoDay() - photo_day setting ID empty/null." );
            return false;
        }
        else {
            return ($setting != 0 ? true : false);
        }
        // return ( is_null( $retVal ) ? false : true );
    }

    public function isSurvey( $id ) {

        dbg("e20rArticle::isSurvey() - checking survey setting for {$id}");

        $setting = $this->model->getSetting( $id, 'is_survey' );

        if ( empty( $setting ) ) {

            dbg("e20rArticle::isSurvey() - is_survey setting is empty/null");
            return false;
        }
        else {
            return ( 0 != $setting ? true : false );
        }
    }

    /**
     * Get the 'prefix' setting for the specified articleId
     *
     * @param $articleId - The Article ID to look up settings for
     *
     * @return string - The actual prefix (or NULL) for the articleId
     */
    public function getArticlePrefix( $articleId ) {

        $settings = $this->getSettings( $articleId );

        if ( !empty( $settings ) ) {

            return $settings->prefix;
        }

        return null;
    }

    public function getPostUrl( $articleId ) {

        if ( !$articleId ) {

            dbg("e20rArticle::getPostUrl() - No article ID provided?!?");
            return false;
        }

        $postId = $this->model->getSetting( $articleId, 'post_id' );
        $url = get_permalink( $postId );

        dbg("e20rArticle::getPostUrl() - Got URL for post ({$postId}): {$url}");

        return ( empty($url) ? false : $url );
    }

    /**
     * Use the articleId to locate the
     * @param $articleId - The ID of the article containing the workout/activity for this lesson.
     *
     * @returns int - The post ID for the activity/workout.
     */
    public function getActivity( $articleId, $userId = null ) {

        global $e20rArticle;
        global $e20rWorkout;
        global $e20rTracker;

        $postId = null;

        // $excerpt = __( "We haven't found an activity for this day", "e20rtracker" );

        $aIds = $this->model->getSetting( $articleId, 'activity_id');

        // No activity defined
        if ( !is_array( $aIds ) && ( false == $aIds ) ) {

            dbg("e20rArticle::getActivity() - No defined activity for this article ({$articleId})");
            return false;
        }

        $delay = $this->model->getSetting( $articleId, 'release_day');

        $mGroupId = $e20rTracker->getGroupIdForUser( $userId );
        $activities = $e20rWorkout->find( 'id', $aIds, 'numeric', -1, 'IN' );

        $art_day_no = date( 'N', strtotime( $e20rTracker->getDateForPost( $delay ) ) );
        dbg("e20rArticle::getActivity() - For article #{$articleId}, delay: {$delay}, on day: {$art_day_no}.");

        // Loop through all the defined activities for the $articleId
        foreach( $activities as $a ) {

            if ( in_array( $art_day_no, $a->days ) ) {

                // The delay value for the $articleId releases the $articleId on one of the $activity days.
                dbg("e20rArticle::getActivity() - ID for an activity allowed on {$art_day_no}: {$a->id}");
                // $activity = $e20rWorkout->getActivity( $a->id );

                $has_access = array();

                $access_map = new stdClass();
                $access_map->user = array();
                $access_map->group = array();

                dbg( "e20rArticle::getActivity() - Testing if {$userId} is in group or user list for activity #{$a->id}" );

                $has_access = $e20rTracker->allowedActivityAccess( $a, $userId, $mGroupId );
                $access_map->user[$a->id] = $has_access['user'];
                $access_map->group[$a->id] = $has_access['group'];

                $postId = array_search( true, $access_map->user );

                if ( $postId ) {
                    dbg("e20rArticle::getActivity() - Found an activity ({$postId}) that is assigned to the specific user ({$userId})");
                    return $postId;
                }

                $postId = array_search( true, $access_map->group );

                if ( !empty( $postId ) ) {
                    dbg("e20rArticle::getActivity() - Found an activity ({$postId}) that is assigned to the group ({$mGroupId}) that the user ({$userId}) is in");
                    return $postId;
                }
            }
        }

        return false;
    }

    public function getExcerpt( $articleId, $userId = null, $type = 'action' ) {

        global $post;
        global $e20rTracker;
        global $e20rWorkout;

        global $current_user;
        global $currentProgram;

        $postId = null;
        $activityField = null;

        $oldPost = $post;

        if ( is_null( $userId ) ) {
            // Assuming current_user->ID..
            $userId = $current_user->ID;
        }

        switch( $type ) {

            case 'action':
                $postId = $this->model->getSetting( $articleId, 'post_id' );
                $prefix = $this->model->getSetting( $articleId, 'prefix' );
                dbg("e20rArticle::getExcerpt() - Loaded post ID ($postId) for the action in article {$articleId}");

                if ( -1 == $postId ) {
                    dbg("e20rArticle::getExcerpt() - No activity excerpt to be found (no activity specified).");
                    return null;
                }

                break;

            case 'activity':

                $group = $e20rTracker->getGroupIdForUser( $userId );

                dbg("e20rArticle::getExcerpt() - Searching for activities for {$articleId}");
                $actId = $this->getActivity( $articleId, $userId );

                dbg("e20rArticle::getExcerpt() - Found activity: {$actId}");
                dbg( $actId );

                if ( false == $actId ) {
                    $postId = -1;
                }
                else {

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

                    $activityField = '<input type="hidden" id="e20r-checkin-activity_id" value="' . $postId . '" name="e20r-checkin-activity_id">';
                    $prefix = null; // Using NULL prefix for activities
                    dbg("e20rArticle::getExcerpt() - Loaded post ID ($postId) for the activity in article {$articleId}");
                }

                if ( -1 == $postId ) {
                    dbg("e20rArticle::getExcerpt() - No activity excerpt to be found (no activity specified).");
                    return null;
                }

                break;
        }

        dbg("e20rArticle::getExcerpt() - Post Id for article {$articleId}: {$postId}");

        if ( empty( $postId ) ) {
            return null;
        }

        $art = get_post( $articleId );
        $post = get_post( $postId );

        dbg( "e20rArticle::getExcerpt() - Prefix for {$type}: {$prefix}");

        if ( !empty( $art->post_excerpt ) && ( 'action' == $type )) {

            dbg( "e20rArticle::getExcerpt() - Using the article summary.");
            $pExcerpt = $art->post_excerpt;
        }
        elseif ( !empty( $post->post_excerpt ) ) {

            dbg( "e20rArticle::getExcerpt() - Using the post excerpt.");
            $pExcerpt = $post->post_excerpt;
        }
        else {

            dbg( "e20rArticle::getExcerpt() - Using the post summary.");
            $pExcerpt = $post->post_content;
        }

        $image = ( has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail( $post->ID ) : '<div class="noThumb"></div>' );

        $pExcerpt = wp_trim_words( $pExcerpt, 30, " [...]" );
        $pExcerpt = preg_replace("/\<br(\s+)\/\>/i", null, $pExcerpt );

        ob_start();
        ?>
        <h4>
            <span class="e20r-excerpt-prefix"><?php echo "{$prefix} "; ?></span><?php echo get_the_title( $post->ID ); ?>
        </h4>
        <?php echo !is_null( $activityField ) ? $activityField : null; ?>
        <p class="e20r-descr"><?php echo $pExcerpt; ?></p> <?php

        if ( $type == 'action' ) {

            $url = get_permalink( $post->ID );
        }
        else if ($type == 'activity' ) {

            $url = null;

            dbg("e20rArticle::getExcerpt() - Loading URL for activity...");

            $url = get_permalink( $currentProgram->activity_page_id );
            dbg("e20rArticle::getExcerpt() - URL is: {$url}");

        }?>
        <p class="e20r-descr"><a href="<?php echo $url; ?>" id="e20r-<?php echo $type; ?>-read-lnk" title="<?php get_the_title( $post->ID ); ?>">
            <?php _e('Click to read', 'e20tracker'); ?>
        </a>
        </p><?php

        wp_reset_postdata();

        $html = ob_get_clean();

        $post = $oldPost;

        return $html;
    }

    public function getAssignments( $articleId, $userId = null ) {

        global $e20rAssignment;
        global $currentArticle;

        dbg("e20rArticle::getAssignments() - Need to load settings for {$articleId}");
        // $this->init( $articleId );
        $articleSettings = $this->model->loadSettings( $articleId );

        // $articleSettings = $this->model->loadSettings( $this->articleId );

        // dbg($articleSettings);

        $assignment_ids = array();

        dbg("e20rArticle::getAssignments() - Loading assignments for article # {$articleId}");

        if ( ! empty( $articleSettings->assignment_ids ) ) {

            dbg("e20rArticle::getAssignments() - Have predefined assignments for article");

            foreach ( $articleSettings->assignment_ids as $assignmentId ) {

                dbg("e20rArticle::getAssignments() - Loading assignment {$assignmentId} for article");

                // Load the user specific assignment data (if available. If not, load default data)
                $tmp                               = $e20rAssignment->load_userAssignment( $articleId, $assignmentId, $userId );
                $assignment_ids[ $tmp[0]->order_num ] = $tmp[0];
            }
        }
        else {
            dbg("e20rArticle::getAssignments() - No defined explicit assignments for this article.");
            $assignment_ids[0] = $e20rAssignment->loadAssignment();
        }

        dbg("e20rArticle::getAssignments() - Sorting assignments for article # {$articleId} by order number");
        ksort( $assignment_ids );

        return $assignment_ids;
    }

    public function getSettings( $articleId ) {

        global $currentArticle;

        // $article = $this->model->getSettings();

        if ( $currentArticle->id == $articleId ) {
            return $currentArticle->id;
        }
        else {
            $this->model->loadSettings( $articleId );
            return $currentArticle;
        }

        return false;
    }

    public function remove_assignment_callback() {

        global $e20rAssignment;
        global $currentArticle;

        dbg("e20rArticle::remove_assignment_callback().");
        check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );
        dbg("e20rArticle::remove_assignment_callback() - Deleting assignment for article.");

        $articleId = isset($_POST['e20r-article-id']) ? intval( $_POST['e20r-article-id']) : null;
        $assignmentId = isset($_POST['e20r-assignment-id']) ? intval( $_POST['e20r-assignment-id']) : null;

        // $this->articleId = $articleId;
        $this->init( $articleId );

        $artSettings = $currentArticle;
        dbg("e20rArticle::remove_assignment_callback() - Article settings for ({$articleId}): ");
        dbg($artSettings);

        $assignment = $e20rAssignment->loadAssignment( $assignmentId );

        dbg("e20rArticle::remove_assignment_callback() - Updating Assignment ({$assignmentId}) settings & saving.");
        $assignment->article_id = null;

        dbg("e20rArticle::remove_assignment_callback() - Assignment settings for ({$assignmentId}): ");
        $e20rAssignment->saveSettings( $articleId, $assignment );

        dbg("e20rArticle::remove_assignment_callback() - Updating Article settings for ({$articleId}): ");

        if ( in_array( $assignmentId, $artSettings->assignment_ids) ) {

            $artSettings->assignment_ids = array_diff($artSettings->assignment_ids, array( $assignmentId ));
        }

        $this->model->set( 'assignment_ids', $artSettings->assignment_ids, $articleId );

        dbg("e20rArticle::remove_assignment_callback() - Generating the assignments metabox for the article {$articleId} definition");

        $toBrowser = array(
            'success' => true,
            'data' => $e20rAssignment->configureArticleMetabox( $articleId ),
        );

        if ( ! empty( $toBrowser['data'] ) ) {

            dbg("e20rArticle::remove_assignment_callback() - Transmitting new HTML for metabox");
            // wp_send_json_success( $html );

            // dbg($html);
            echo json_encode( $toBrowser );
            wp_die();
        }

        dbg("e20rArticle::remove_assignment_callback() - Error generating the metabox html!");
        wp_send_json_error( "No assignments found for this article!" );
    }

    public function add_assignment_callback() {

        global $e20rAssignment;
        global $e20rTracker;

        global $currentArticle;

        $reloadPage = false;

        dbg("e20rArticle::add_assignment_callback().");
        check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );

        dbg("e20rArticle::add_assignment_callback() - Saving new assignment for article.");
        dbg($_POST);

        $articleId = isset($_POST['e20r-article-id']) ? $e20rTracker->sanitize( $_POST['e20r-article-id']) : null;
        $postId = isset($_POST['e20r-assignment-post_id']) ? $e20rTracker->sanitize( $_POST['e20r-assignment-post_id']) : null;
        $assignmentId = isset($_POST['e20r-assignment-id']) ? $e20rTracker->sanitize( $_POST['e20r-assignment-id']) : null;
        $new_order_num = isset($_POST['e20r-assignment-order_num']) ? $e20rTracker->sanitize( $_POST['e20r-assignment-order_num'] ) : null;

        if ( $new_order_num <= 0 ) {

            dbg("e20rArticle::add_assignment_callback() - Resetting the requested order number to a valid value ( >0 ).");
            $new_order_num = 1;
        }

        dbg("e20rArticle::add_assignment_callback() - Article: {$articleId}, Assignment: {$assignmentId}, Assignment Order#: {$new_order_num}");

        // $this->articleId = $articleId;

        $post = get_post( $articleId );
        setup_postdata( $post );


        // Just in case we're working on a brand new article
        if ( 'auto-draft' == $post->post_status ) {

            if ( empty( $post->post_title ) ) {

                $post->post_title = "Please update this title before updating";
            }

            $articleId = wp_insert_post( $post );
            $reloadPage = true;
        }

        wp_reset_postdata();

        $this->model->loadSettings( $articleId );

        // $artSettings = $this->model->getSettings();
        dbg("e20rArticle::add_assignment_callback() - Article settings for ({$articleId}): ");
        dbg($currentArticle);

        $assignment = $e20rAssignment->loadAssignment( $assignmentId );
        $assignment->order_num = $new_order_num;
        $assignment->article_id = null;

        dbg("e20rArticle::add_assignment_callback() - Updating assignment settings for ({$assignmentId}), with new order {$new_order_num}");
        $e20rAssignment->saveSettings( $assignmentId, $assignment );

        $ordered = array();
        $orig = $currentArticle->assignment_ids;
        $new = array();

        // Load assignments so we can sort (if needed)
        foreach( $currentArticle->assignment_ids as $aId ) {

            $a = $e20rAssignment->loadAssignment( $aId );

            if ( $a->order_num == 0 ) {
                $a->order_num = 1;
            }
            $ordered[ ($a->order_num - 1 )] = $a;
        }

        // Sort by order number.
        ksort( $ordered );
        $orig = $ordered;

        dbg("e20rArticle::add_assignment_callback() - Sorted previously saved assignments:");
        dbg($ordered);

        // Are we asking to reorder the assignment?
        if ( ( isset($ordered[$new_order_num]) ) && ( $assignmentId != $ordered[$new_order_num]->id ) ) {

            dbg("e20rArticle::add_assignment_callback() - Re-sorting list of assignments:");
            reset( $ordered );
            $first = key($ordered);

            for( $i = $first; $i < $new_order_num ; $i++ ) {

                if ( isset( $ordered[$i]) ) {
                    dbg("e20rArticle::add_assignment_callback() - Sorting assignment {$ordered[$i]->id} to position {$ordered[$i]->order_num}");
                    $ordered[$i]->order_num ==  $new_order_num;
                    $new[$i] = $ordered[$i];
                }
            }

            $new[$new_order_num] = $assignment;

            end($orig);
            $last = key($orig);

            for( $i = $new_order_num ; $i <= $last ; $i++ ) {

                if ( isset( $orig[$i]) ) {

                    dbg("e20rArticle::add_assignment_callback() - Sorting assignment {$orig[($i)]->id} to position " . ($orig[$i]->order_num + 1) );
                    $orig[$i]->order_num = $orig[$i]->order_num + 1;
                    $new[($i+1)] = $orig[$i];
                    $e20rAssignment->saveSettings( $new[($i+1)]->id, $new[($i+1)] );
                }
            }

            $ordered = $new;
        }
        else {


            dbg("e20rArticle::add_assignment_callback() - Adding {$assignment->id} to the list of assignments");
            if (! isset( $ordered[$assignment->order_num] ) ) {
                dbg("e20rArticle::add_assignment_callback() - Using position {$assignment->order_num}");
                $ordered[$assignment->order_num] = $assignment;
            }
            else {

                $ordered[] = $assignment;
                end($ordered);
                $new_order = key($ordered);
                $ordered[$new_order]->order_num = ($new_order + 1);

                dbg("e20rArticle::add_assignment_callback() - Using position {$ordered[$new_order]->order_num }");
                $e20rAssignment->saveSettings( $ordered[$new_order]->id, $ordered[$new_order]);
            }

        }

        dbg("e20rArticle::add_assignment_callback() - Sorted all of the assignments:");
        dbg($ordered);

        $new = array();

        foreach( $ordered as $a ) {

            $new[] = $a->id;
        }

        if ( empty( $new ) && ( !is_null( $assignmentId ) ) ) {

            dbg("e20rArticle::add_assignment_callback() - No previously defined assignments. Adding the new one");
            $new = array( $assignmentId );
        }

        dbg("e20rArticle::add_assignment_callback() - Assignment list to be used by {$articleId}:");
        dbg($new);

        dbg("e20rArticle::add_assignment_callback() - Updating Article settings for ({$articleId}): ");
        $currentArticle->assignment_ids = $new;

        dbg("e20rArticle::add_assignment_callback() - Saving Article settings for ({$articleId}): ");
        dbg($currentArticle);

        $this->saveSettings( $articleId, $currentArticle );

        dbg("e20rArticle::add_assignment_callback() - Generating the assignments metabox for the article {$articleId} definition");

        $html = $e20rAssignment->configureArticleMetabox( $articleId, true );

        dbg("e20rArticle::add_assignment_callback() - Transmitting new HTML for metabox");
        wp_send_json_success( array( 'html' => $html, 'reload' => $reloadPage ) );

        // dbg("e20rArticle::add_assignment_callback() - Error generating the metabox html!");
        // wp_send_json_error( "No assignments found for this article!" );
    }

    public function getDelayValue_callback() {

        global $e20rTracker;

        dbg("e20rArticle::getDelayValue() - Callback initiated");

        check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );

        dbg("e20rArticle::getDelayValue() - Nonce is OK");

        $postId = isset( $_POST['post_ID'] ) ? intval( $_POST['post_ID'] ) : null;

        if ( ! $postId ) {
            wp_send_json_error('Error: Not a valid Post/Page');
        }

        $dripFeedDelay = $e20rTracker->getDripFeedDelay( $postId );

        if ( $dripFeedDelay ) {
            wp_send_json_success( array(
                'delay' => $dripFeedDelay,
                'nodelay' => false
            ) );
        }

        wp_send_json_success( array( 'nodelay' => true ) );
    }

    /**
     * Save the Article Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific article.
     *
     * @return bool - True if successful at updating article settings
     */
    public function saveSettings( $articleId, $settings = null ) {

        global $e20rAssignment;
        global $currentArticle;
        global $e20rTracker;
        global $post;

        if ( empty( $articleId ) ) {

            dbg("e20rArticle::saveSettings() - No article ID supplied");
            return false;
        }

        if ( is_null($settings) && ( ( !isset($post->post_type) ) || ( $post->post_type !== $this->cpt_slug ) ) ) {

            dbg( "e20rArticle::saveSettings() - Not an article. " );
            return $articleId;
        }

        if ( empty( $articleId ) ) {

            dbg("e20rArticle::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $articleId ) ) {

            return $articleId;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {

            return $articleId;
        }

        $savePost = $post;

        if ( empty( $settings ) ) {

            dbg( "e20rArticle::saveSettings()  - Saving metadata from edit.php page, related to the {$this->type} post_type" );

            // $this->model->init( $articleId );

            $settings = $this->model->loadSettings( $articleId );
            $defaults = $this->model->defaultSettings();

            dbg($settings);

            if ( ! $settings ) {

                $settings = $defaults;
            }

            foreach ( $settings as $field => $setting ) {

                $tmp = isset( $_POST["e20r-article-{$field}"] ) ? $e20rTracker->sanitize( $_POST["e20r-article-{$field}"] ) : null;

                dbg( "e20rArticle::saveSettings() - Page data : {$field}->" );
                dbg($tmp);

                if ( 'assignment_ids' == $field ) {

                    dbg("e20rArticle::saveSettings() - Process the assignments array");

                    if ( empty( $tmp[0] ) ) {

                        dbg("e20rArticle::saveSettings() - Assignments Array: The assignments key contains no data");
                        dbg("e20rArticle::saveSettings() - Create a new default assignment for the article ID: {$currentArticle->id}");

                        //Generate a default assignment for this article.
                        $status = $e20rAssignment->createDefaultAssignment( $currentArticle );

                        if ( false !== $status ) {

                            $tmp[0] = $status;
                        }

                    }
                    else {

                        foreach ($tmp as $k => $assignmentId) {

                            if ( is_null($assignmentId) ) {

                                dbg("e20rArticle::saveSettings() - Assignments Array: Setting key {$k} has no ID}");
                                dbg("e20rArticle::saveSettings() - Create a new default assignment for this article ID: {$settings->id}");

                                //Generate a default assignment for this article.
                                $assignmentId = $e20rAssignment->createDefaultAssignment($settings);

                                if ( false !== $assignmentId ) {

                                    dbg("e20rArticle::saveSettings() - Replacing empty assignment key #{$k} with value {$assignmentId}");
                                    $tmp[$k] = $assignmentId;
                                }
                            }
                        }

                        dbg("e20rArticle::saveSettings() - Assignments array after processing: ");
                        dbg($tmp);
                    }
                }

                if ( empty( $tmp ) ) {

                    $tmp = $defaults->{$field};
                }

                $settings->{$field} = $tmp;
            }

            // Add post ID (article ID)
            $settings->id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;

            if ( ! $this->model->saveSettings( $settings ) ) {

                dbg( "e20rArticle::saveSettings() - Error saving settings!" );
            }

        }
        elseif ( get_class( $settings ) != 'WP_Post' ) {

            dbg("e20rArticle::saveSettings() - Received settings from calling function.");

            if ( ! $this->model->saveSettings( $settings ) ) {

                dbg( "e20rArticle::saveSettings() - Error saving settings!" );
            }

            $post = get_post( $articleId );

            setup_postdata( $post );

            $this->model->set( 'question', get_the_title() );
            $this->model->set( 'descr', get_the_excerpt() );

            wp_reset_postdata();

        }

        $post = $savePost;

    }

    public function editor_metabox_setup( $post ) {

        global $e20rAssignment;

        remove_meta_box( 'postexcerpt', 'e20r_articles', 'side' );
        remove_meta_box( 'wpseo_meta', 'e20r_articles', 'side' );
        add_meta_box('postexcerpt', __('Article Summary'), 'post_excerpt_meta_box', 'e20r_articles', 'normal', 'high');

        add_meta_box('e20r-tracker-article-settings', __('Article Settings', 'e20rtracker'), array( &$this, "addMeta_Settings" ), 'e20r_articles', 'normal', 'high');
        add_meta_box('e20r-tracker-answer-settings', __('Assignments', 'e20rtracker'), array( &$e20rAssignment, "addMeta_answers" ), 'e20r_articles', 'normal', 'high');
        // add_meta_box('e20r-tracker-article-checkinlist', __('Check-in List', 'e20rtracker'), array( &$this, "addMeta_SettingsCheckin" ), 'e20r_articles', 'normal', 'high');

    }

    public function addMeta_Settings() {

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
            'post_type'   => apply_filters( 'e20r_tracker_article_types', array( 'post', 'page' ) ),
            'post_status' => apply_filters( 'e20r_tracker_article_post_status', $def_status ),
            'posts_per_page' => -1,
        );

        wp_reset_query();

        //  Fetch Programs
        $lessons = new WP_Query( $query );

        if ( empty( $lessons ) ) {

            dbg( "e20rArticle::addMeta_Settings() - No posts/pages/lessons found!" );
        }

        dbg("e20rArticle::addMeta_Settings() - Loaded " . $lessons->found_posts . " posts/pages/lessons");
        dbg("e20rArticle::addMeta_Settings() - Loading settings metabox for article page {$post->ID} or {$savePost->ID}?");

        $settings = $this->model->loadSettings( $post->ID );

        $post = $savePost;

        ob_start();
        ?>
        <div id="e20r-article-settings">
            <?php echo $this->view->viewArticleSettings( $settings , $lessons ); ?>
        </div>
        <?php

        echo ob_get_clean();
        // ob_end_clean();
    }

    /**
     * Filters the content for a post to check whether ot inject e20rTracker Article info in the post/page.
     *
     * @param $content - Content to prepend data to if the article meta warrants is.
     *
     * @return string -- The content to display on the page/post.
     */
    public function contentFilter( $content ) {

        if ( ! is_user_logged_in() ) {
            return $content;
        }

        // Quit if this isn't a single-post display and we're not in the main query of wordpress.
        if ( ! ( is_singular() && is_main_query() ) ) {
            return $content;
        }

        /*
	    if ( has_shortcode( $content, 'weekly_progress') ||
            has_shortcode( $content, 'progress_overview' ) ||
            has_shortcode( $content, 'daily_progress') ||
            has_shortcode( $content, 'e20r_activity_archive') ) {
		    // Process in shortcode actions
		    return $content;
	    }
        */
        if ( has_shortcode( $content, 'progress_overview' ) ||
            has_shortcode( $content, 'e20r_activity_archive') ) {
            // Process in shortcode actions
            return $content;
        }

        global $post;
        global $current_user;
        global $e20rMeasurements;
        global $e20rProgram;
        global $e20rTracker;
        global $e20rCheckin;
        global $e20rClient;

        global $currentArticle;
        global $currentProgram;

        /*	    if ( ! in_array( $post->post_type, $e20rTracker->trackerCPTs() ) ) {
                    return $content;
                }
        */
        $e20rProgram->getProgramIdForUser( $current_user->ID );

        dbg("e20rArticle::contentFilter() - Is the user attempting to access the intake form: {$currentProgram->intake_form}");
        if ( isset( $currentProgram->intake_form ) && ( $currentProgram->intake_form == $post->ID ) && is_user_logged_in() ) {

            dbg("e20rArticle::contentFilter() - Attempting to access the intake form...");

            $today = current_time('timestamp' );
            $two_months = strtotime( "{$currentProgram->startdate} + 2 months" );

            if ( ( $two_months < $today ) && $e20rClient->completeInterview( $current_user->ID ) ) {

                dbg("e20rArticle::contentFilter() - WARNING: User started program more than two months ago and is attempting to access the completed interview. Redirecting");
                wp_redirect( get_permalink( $currentProgram->dashboard_page_id ) );
            }
        }

        if ( !empty( $currentProgram->sales_page_ids) && in_array( $post->ID, $currentProgram->sales_page_ids ) && is_user_logged_in() ) {

            dbg("e20rArticle::contentFilter() - WARNING: Logged in user requested sales page, suspect they want the dashboard...");
            if ( isset( $currentProgram->dashboard_page_id ) ) {

                $to_dashboard = get_permalink( $currentProgram->dashboard_page_id );
                wp_redirect($to_dashboard, 302);
            }
        }

        dbg("e20rArticle::contentFilter() - Check whether this user should have access to the dashboard page for their program (yet!)");
        $program_pages = array();

        $pgm_pages = array(
            'dashboard_page_id',
            'activity_page_id',
            'measurements_page_id',
            'progress_page_id',
        );

        foreach( $pgm_pages as $key ) {

            if ( !empty( $currentProgram->{$key}) ) {

                if ( !is_array( $currentProgram->{$key} ) ) {

                    $program_pages[] = $currentProgram->{$key};
                }
                else {

                    foreach( $currentProgram->{$key} as $id ) {

                        $program_pages[] = $id;
                    }
                }
            }
        }


        if ( ( !empty( $currentProgram->dashboard_page_id ) && in_array($post->ID, $program_pages ) ) ) {

            if ( function_exists( 'pmpro_getMemberStartdate' ) ) {

                $user_startdate = date_i18n( 'Y-m-d', pmpro_getMemberStartdate( $current_user->ID ) );
                $today = date_i18n( 'Y-m-d', current_time('timestamp') );

                if ( $today < $user_startdate ) {

                    dbg("e20rArticle::contentFitler() - Warning: User shouldn't have access to this dashboard yet. Redirect them to the start page(s)?");
                    if ( !empty( $currentProgram->welcome_page_id ) ) {

                        wp_redirect( get_permalink( $currentProgram->welcome_page_id ) );
                    }
                }
            }
        }

        dbg("e20rArticle::contentFilter() - loading article settings for post ID {$post->ID}");
        $articles = $this->findArticles( 'post_id', $post->ID );
        $dayNo = $e20rTracker->getDelay( 'now', $current_user->ID );

        foreach( $articles as $article ) {

            if ( in_array( $currentProgram->id, $article->program_ids ) ) {

                if ( $dayNo == $article->release_day ) {

                    dbg("e20rArticle::contentFilter() - Found the correct article for post {$post->ID}");
                    $this->init( $article->id );
                }
            }
        }

        if ( empty( $articles ) ) {

            dbg("e20rArticle::contentFilter() - No article defined for this content. Exiting the filter.");
            return $content;
        }

        if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

            dbg("e20rArticle::contentFilter() - User doesn't have access to this post/page. Exiting the filter.");
            return $content;
        }

        $measured = false;

        // $rDay = $this->model->getSetting( $this->articleId, 'release_day' );
        $rDay = $currentArticle->release_day;
        $rDate =  $this->releaseDate( $currentArticle->id );
        // $rDate = $currentArticle->release_date;
        //$programId = $e20rProgram->getProgramIdForUser( $current_user->ID );
        $programId = $currentProgram->id;

        dbg("e20rArticle::contentFilter() - Release Date for article: {$rDate} calculated from {$rDay}");

        $md = $this->isMeasurementDay( $currentArticle->id );

        if ( $e20rCheckin->hasCompletedLesson( $currentArticle->id, $post->ID, $current_user->ID ) && ( !$md ) ) {

            dbg("e20rArticle::contentFilter() - Processing a defined article, but it's not for measurements.");
            $data = $this->view->viewLessonComplete( $rDay, false, $currentArticle->id );
            $content = $data . $content;
        }

        if ( $md ) {

            $measured = $e20rMeasurements->areCaptured( $currentArticle->id, $programId, $current_user->ID, $rDate );
            dbg( "e20rArticle::contentFilter() - Result from e20rMeasurements::areCaptured: " );
            dbg( $measured );

            // dbg("e20rArticle::contentFilter() - Settings: " . print_r( $settings, true));
            dbg("e20rArticle::contentFilter() - Check whether it's a measurement day or not: {$md} ");

            if ( $md && !$measured['status'] ) {

                dbg("e20rArticle::contentFilter() - It's a measurement day!");
                $data = $this->view->viewMeasurementAlert( $this->isPhotoDay( $currentArticle->id ), $rDay, $currentArticle->id );
                $content = $data . $content;
            }

            if ( $md && $measured['status'] ) {

                dbg("e20rArticle::contentFilter() - Measurement day, and we've measured.");
                $data = $this->view->viewMeasurementComplete( $rDay, $md, $currentArticle->id );
                $content = $data . $content;
            }
        }
        dbg("e20rArticle::contentFilter() - Content being returned.");
        return $content;
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