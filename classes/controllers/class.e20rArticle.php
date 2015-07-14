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

    public function e20rArticle() {

        dbg("e20rArticle::__construct() - Initializing Article class");
        $this->model = new e20rArticleModel();
        $this->view = new e20rArticleView();

        parent::__construct( 'article', 'e20r_articles', $this->model, $this->view );
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

            dbg( "e20rArticle::addMeta_Settings() - No lessons found!" );
        }

        dbg("e20rArticle::addMeta_Settings() - Loaded " . $lessons->found_posts . " lessons");
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

    public function init( $postId = NULL ) {

	    global $currentArticle;

        if ( ( isset( $currentArticle->post_id) && ($currentArticle->post_id != $postId ) ) || !isset($currentArticle->id) ) {

	        $currentArticle = parent::init( $postId );
            dbg("e20rArticle::init() - Loaded settings for post ID {$postId}:");

	        $this->articleId = ( ! isset($currentArticle->id) ? false : $currentArticle->id);
            dbg("e20rArticle::init() -  Loaded global currentArticle and set it for article ID {$this->articleId}");
        }
	    else {
		    dbg("e20rArticle::init() - No need to load settings (previously loaded): ");
		    dbg($currentArticle);
	    }

        if ( ( $this->articleId  !== false ) && ( $currentArticle->post_id != $postId ) ) {

	        dbg("e20rArticle::init() - No article defined for this postId: {$currentArticle->post_id} & {$postId}");
            $this->articleId = false;
        }

        return $this->articleId;
    }

	public function emptyArticle() {

		$article = $this->model->defaultSettings();
	}

    public function getPostUrl( $articleId ) {

        if ( !$articleId ) {
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

        $aId = $this->model->getSetting( $articleId, 'activity_id');
        $delay = $this->model->getSetting( $articleId, 'release_day');

	    $mGroupId = $e20rTracker->getMembershipLevels();
	    $activities = $e20rWorkout->getActivities( $aId );

        $art_day_no = date( 'N', strtotime( $e20rTracker->getDateForPost( $delay ) ) );
        dbg("e20rArticle::getActivity() - For article #{$articleId}, delay: {$delay}, on day: {$art_day_no}.");

        // Loop through all the defined activities for the $articleId
        foreach( $activities as $a ) {

            if ( in_array( $art_day_no, $a->days ) ) {

                // The delay value for the $articleId releases the $articleId on one of the $activity days.
                dbg("e20rArticle::getActivity() - ID for the correct Activity: {$a->id}");
	            $activity = $e20rWorkout->getActivity( $a->id );

	            foreach ( $activity as $b ) {

		            if ( true === $e20rTracker->allowedActivityAccess( $b, $userId, $mGroupId ) ) {

                        dbg( "e20rArticle::getActivity() - User has permission to see activity #{$a->id}" );
			            $postId = $b->id;
		            }
	            }
            }
        }

        return $postId;
    }

    public function getExcerpt( $articleId, $userId = null, $type = 'action' ) {

        global $post;
        global $e20rTracker;
        global $currentProgram;

	    $postId = null;

        switch( $type ) {
            case 'action':
                $postId = $this->model->getSetting( $articleId, 'post_id' );
	            $prefix = $this->model->getSetting( $articleId, 'prefix' );
				dbg("e20rArticle::getExcerpt() - Loaded post ID ($postId) for the action in article {$articleId}");
                break;

            case 'activity':
                // $postId = $this->getActivity( $articleId );
				$postId = $this->model->getSetting( $articleId, 'activity_id' );
	            $prefix = null; // Using NULL prefix for activities
	            dbg("e20rArticle::getExcerpt() - Loaded post ID ($postId) for the activity in article {$articleId}");
		        break;
        }

        if ( is_null( $postId ) ) {
            return null;
        }

	    dbg( "e20rArticle::getExcerpt() - Prefix for {$type}: {$prefix}");

        $articles = new WP_Query( array(
            'post_type'           => apply_filters( "e20r-tracker-{$type}-type-filter", array( 'any' ) ),
            'post_status'         => apply_filters( 'e20r-tracker-post-status-filter', array( 'publish' ) ),
            'posts_per_page'      => 1,
            'p'                   => $postId,
            'ignore_sticky_posts' => true,
        ) );

        dbg( "e20rArticle::getExcerpt() - Number of posts for ID {$postId} in article {$articleId} is {$articles->found_posts}" );

        if ( $articles->found_posts > 0 ) {

            ob_start();

            while ( $articles->have_posts() ) : $articles->the_post();

                $image = ( has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail( $post->ID, 'pmpro_seq_recentpost_widget_size' ) : '<div class="noThumb"></div>' );


                if ( !empty( $post->post_excerpt ) ) {

                    $pExcerpt = $post->post_excerpt;
                }
                else {
                    $pExcerpt = $post->post_content;
                }

                $pExcerpt = wp_trim_words( $pExcerpt, 30, " [...]" );
                $pExcerpt = preg_replace("/\<br(\s+)\/\>/i", null, $pExcerpt );

                ?>
                <h4>
                    <span class="e20r-excerpt-prefix"><?php echo "{$prefix} "; ?></span><?php echo get_the_title(); ?>
                </h4>
                <p class="e20r-descr"><?php echo $pExcerpt; ?></p> <?php

                if ( $type == 'action' ) {

                    $url = get_permalink();
                }
                else if ($type == 'activity' ) {

                    $url = null;

                    dbg("e20rArticle::getExcerpt() - Loading URL for activity...");

                    $url = get_permalink( $currentProgram->activity_page_id );
                    dbg("e20rArticle::getExcerpt() - URL is: {$url}");

/*                     $urls = $e20rTracker->getURLToPageWithShortcode( "e20r_activity" );

                    if (!empty($urls)) {

                        if (count($urls) > 1) {
                            dbg("e20rArticle::getExcerpt() - ERROR: More than a single page containing the 'e20r_activity' short code! List follows: ");
                            dbg($urls);
                        }

                        $url = array_pop($urls);

                    }
                    else {
                        dbg("e20rArticle::getExcerpt() - No page with 'e20r_activity' short code has been found! Returning nothing...");
                        ?><p class="e20r-descr"></p><?php
                    }
                    */
                }?>
                <p class="e20r-descr"><a href="<?php echo $url; ?>" title="<?php get_the_title(); ?>">
                        <?php _e('Click to read', 'e20tracker'); ?>
                    </a>
                </p><?php
            endwhile;

            wp_reset_postdata();

            $html = ob_get_clean();
        }
        else {
            dbg("e20rArticle::getExcerpt() - No posts found. Returning null for the excerpt");
            $html = null;
        }

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

		$assignments = array();

        dbg("e20rArticle::getAssignments() - Loading assignments for article # {$articleId}");

		if ( ! empty( $articleSettings->assignments ) ) {

            dbg("e20rArticle::getAssignments() - Have predefined assignments for article");

			foreach ( $articleSettings->assignments as $assignmentId ) {

                dbg("e20rArticle::getAssignments() - Loading assignment {$assignmentId} for article");

				// Load the user specific assignment data (if available. If not, load default data)
				$tmp                               = $e20rAssignment->load_userAssignment( $articleId, $assignmentId, $userId );
				$assignments[ $tmp[0]->order_num ] = $tmp[0];
			}
		}
		else {
            dbg("e20rArticle::getAssignments() - No defined explicit assignments for this article.");
			$assignments[0] = $e20rAssignment->loadAssignment( );
		}

		dbg("e20rArticle::getAssignments() - Sorting assignments for article # {$articleId} by order number");
		ksort( $assignments );

		return $assignments;
	}

    public function getArticleForCheckin( ) {

        global $e20rProgram;
        global $e20rTracker;

        global $current_user;

        $pId = $e20rProgram->getProgramIdForUser( $current_user->ID );
        $startTS = $e20rProgram->startdate( $current_user->ID );

        $endTS = current_time('timestamp');

        $currentDelay = $e20rTracker->daysBetween( $startTS );

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

    public function getSettings( $articleId ) {

        $article = $this->model->getSettings();

        if ( $article->id == $articleId ) {
            return $article;
        }

        return false;
    }

    public function findArticleByDelay( $delayVal ) {

        global $e20rProgram;
        global $currentProgram;
        global $current_user;

        // $programId = $e20rProgram->getProgramIdForUser( $current_user->ID );

        if ( $currentProgram->id == -1 ) {
            // No article either.
            return false;
        }

        $article = $this->model->findArticle( 'release_day', $delayVal, 'numeric', $currentProgram->id );

        if ( count($article) == 1 ) {

            dbg("e20rArticle::findArticleByDelay() - Returning a single articleID: {$article->id}");
            return $article->id;
        }
        elseif ( count($article) > 1 ) {
            // TODO: Won't return all of the articles defined for this program!

            dbg("e20rArticle::findArticleByDelay() - Returning first of multiple articleIDs");
            return $article[0]->id;
        }

        return false;
    }

    /**
     * Filters the content for a post to check whether ot inject e20rTracker Article info in the post/page.
     *
     * @param $content - Content to prepend data to if the article meta warrants is.
     *
     * @return string -- The content to display on the page/post.
     */
    public function contentFilter( $content ) {

        // Quit if this isn't a single-post display and we're not in the main query of wordpress.
        if ( ! ( is_singular() && is_main_query() ) ) {
            return $content;
        }

	    if ( has_shortcode( $content, 'weekly_progress') ||
            has_shortcode( $content, 'progress_overview' ) ||
            has_shortcode( $content, 'daily_progress') ||
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
	    global $currentArticle;

	    if ( ! in_array( $post->post_type, $e20rTracker->trackerCPTs() ) ) {
		    return $content;
	    }

        dbg("e20rArticle::contentFilter() - loading article settings for post ID {$post->ID}");
        $articleId = $this->init( $post->ID );

        if ( $articleId == false ) {

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
        $rDate =  $this->releaseDate( $this->articleId );
	    // $rDate = $currentArticle->release_date;
        $programId = $e20rProgram->getProgramIdForUser( $current_user->ID );

        dbg("e20rArticle::contentFilter() - Release Date for article: {$rDate} calculated from {$rDay}");

        $md = $this->isMeasurementDay( $this->articleId );

	    if ( $e20rCheckin->hasCompletedLesson( $this->articleId, $post->ID, $current_user->ID ) && ( !$md ) ) {

		    dbg("e20rArticle::contentFilter() - Processing a defined article, but it's not for measurements.");
		    $data = $this->view->viewLessonComplete( $rDay, false, $this->articleId );
		    $content = $data . $content;
	    }

	    if ( $md ) {

            $measured = $e20rMeasurements->areCaptured( $this->articleId, $programId, $current_user->ID, $rDate );
            dbg( "e20rArticle::contentFilter() - Result from e20rMeasurements::areCaptured: " );
            dbg( $measured );

            // dbg("e20rArticle::contentFilter() - Settings: " . print_r( $settings, true));
            dbg("e20rArticle::contentFilter() - Check whether it's a measurement day or not: {$md}, {$measured}");

            if ( $md && !$measured['status'] ) {

                dbg("e20rArticle::contentFilter() - It's a measurement day!");
                $data = $this->view->viewMeasurementAlert( $this->isPhotoDay( $this->articleId ), $rDay, $this->articleId );
                $content = $data . $content;
            }

            if ( $md && $measured['status'] ) {

                dbg("e20rArticle::contentFilter() - Measurement day, and we've measured.");
                $data = $this->view->viewMeasurementComplete( $rDay, $md, $this->articleId );
                $content = $data . $content;
            }
        }
	    dbg("e20rArticle::contentFilter() - Content being returned.");
        return $content;
    }

	public function loadArticlesByMeta($key, $value, $type = 'numeric', $programId = -1, $comp = '=' ) {

		if (is_array( $value ) ) {
            dbg("e20rArticle::loadArticlesByMeta() - {$key}, " . print_r( $value, true ) . ", {$type}, {$programId}, {$comp}");
        }
        else {
            dbg("e20rArticle::loadArticlesByMeta() - {$key}, {$value}, {$type}, {$programId}, {$comp}");
        }
		return $this->model->findArticle($key, $value, $type, $programId, $comp );
	}

	public function findArticleNear( $key, $value, $programId = -1, $comp = '<=', $sort_order = 'DESC', $limit = 1, $type = 'numeric' ) {
		// findClosestArticle( $key, $value, $programId = -1, $comp = '<=', $limit = 1, $type = 'numeric', $sort_order = 'DESC' )
		return $this->model->findClosestArticle( $key, $value, $programId, $comp, $limit, $type , $sort_order );
	}

	public function remove_assignment_callback() {

		global $e20rAssignment;

		dbg("e20rArticle::remove_assignment_callback().");
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );
		dbg("e20rArticle::remove_assignment_callback() - Deleting assignment for article.");

		$articleId = isset($_POST['e20r-article-id']) ? intval( $_POST['e20r-article-id']) : null;
		$assignmentId = isset($_POST['e20r-assignment-id']) ? intval( $_POST['e20r-assignment-id']) : null;

		$this->articleId = $articleId;
		$this->init( $this->articleId );

		$artSettings = $this->model->getSettings();
		dbg("e20rArticle::remove_assignment_callback() - Article settings for ({$articleId}): ");
		dbg($artSettings);

		$assignment = $e20rAssignment->loadAssignment( $assignmentId );

		dbg("e20rArticle::remove_assignment_callback() - Updating Assignment ({$assignmentId}) settings & saving.");
		$assignment->article_id = null;

		dbg("e20rArticle::remove_assignment_callback() - Assignment settings for ({$assignmentId}): ");
		$e20rAssignment->saveSettings( $articleId, $assignment );

		dbg("e20rArticle::remove_assignment_callback() - Updating Article settings for ({$articleId}): ");

		if ( in_array( $assignmentId, $artSettings->assignments) ) {

			$artSettings->assignments = array_diff($artSettings->assignments, array( $assignmentId ));
		}

		$this->model->set( 'assignments', $artSettings->assignments, $articleId );

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

        dbg("e20rArticle::add_assignment_callback().");
        check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );

        dbg("e20rArticle::add_assignment_callback() - Saving new assignment for article.");
	    // dbg($_POST);

        $articleId = isset($_POST['e20r-article-id']) ? $e20rTracker->sanitize( $_POST['e20r-article-id']) : null;
	    $postId = isset($_POST['e20r-assignment-post_id']) ? $e20rTracker->sanitize( $_POST['e20r-assignment-post_id']) : null;
        $assignmentId = isset($_POST['e20r-assignment-id']) ? $e20rTracker->sanitize( $_POST['e20r-assignment-id']) : null;
        $assignment_orderNum = isset($_POST['e20r-assignment-order_num']) ? $e20rTracker->sanitize( $_POST['e20r-assignment-order_num'] ) : null;

        dbg("e20rArticle::add_assignment_callback() - Article: {$articleId}, Assignment: {$assignmentId}, Assignment Order#: {$assignment_orderNum}");

        $this->articleId = $articleId;
        $this->init( $postId );

        $artSettings = $this->model->getSettings();
	    dbg("e20rArticle::add_assignment_callback() - Article settings for ({$articleId}): ");
	    dbg($artSettings);

        $assignment = $e20rAssignment->loadAssignment( $assignmentId );

	    dbg("e20rArticle::add_assignment_callback() - Updating Assignment ({$assignmentId}) settings & saving.");
        $assignment->order_num = $assignment_orderNum;
        $assignment->article_id = $articleId;

	    dbg("e20rArticle::add_assignment_callback() - Assignment settings for ({$assignmentId}): ");
	    dbg($assignment);

	    dbg("e20rArticle::add_assignment_callback() - Saving Assignment settings for ({$assignmentId}): ");
	    $e20rAssignment->saveSettings( $assignmentId, $assignment );

	    dbg("e20rArticle::add_assignment_callback() - Updating Article settings for ({$articleId}): ");
        if ( empty( $artSettings->assignments) ) {

            $artSettings->assignments = ( $assignmentId !== null || $assignmentId !== -1 )? array( $assignmentId ) : array() ;
        }
        elseif ( ( ( -1 != $assignmentId ) || !empty($assignmentId ) ) && ( ! in_array( $assignmentId, $artSettings->assignments ) ) ) {

		        $artSettings->assignments[] = $assignmentId;
        }

	    dbg("e20rArticle::add_assignment_callback() - Saving Article settings for ({$articleId}): ");
	    dbg($artSettings);

        $this->saveSettings( $articleId, $artSettings );

        dbg("e20rArticle::add_assignment_callback() - Generating the assignments metabox for the article {$articleId} definition");

	    $html = $e20rAssignment->configureArticleMetabox( $articleId, true );

/*	    $toBrowser = array(
	        'success' => true,
	        'data' => array( 'data' => $html ),
        );
*/
/*         if ( ! empty( $toBrowser['data'] ) ) { */

            dbg("e20rArticle::add_assignment_callback() - Transmitting new HTML for metabox");
            wp_send_json_success( $html );

            // dbg($html);
/*            echo json_encode( $toBrowser );
            wp_die();
        }*/

        dbg("e20rArticle::add_assignment_callback() - Error generating the metabox html!");
        wp_send_json_error( "No assignments found for this article!" );
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

            if ( is_null( $articleId ) ) {

                $articleId = $this->articleId;
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

    public function getCheckins( $aConfig ) {

        dbg("e20rArticle::getCheckins() - Get array of checkin IDs for {$aConfig->articleId}");

        $checkin = $this->model->getSetting( $aConfig->articleId, 'checkins' );
        // $activity = $this->model->getSetting( $aConfig->articleId, 'activity_id' );

        if ( ! is_array( $checkin ) ) {
            // $setting = array_merge( array( $checkin ), array( $activity ) );
            $checkin = array( $checkin );
        }

        if ( empty( $checkin ) /* && is_null( $activity ) */ ) {
            dbg("e20rArticle::getCheckins() - No check-in IDs found for this article ({$aConfig->articleId})");
            return false;
        }

        return $checkin;
    }

    public function setId( $id = null ) {

        if ( $id === null ) {

            global $post;

            $id = $post->ID;
        }

        $this->post_id = $id;
        $this->init( $id );
    }

    public function addArticle( $obj ) {

        $key = $this->findArticle( $obj->id );

        if ($key !== false ) {

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

    public function findArticle( $key = 'id', $val = null, $type = 'numeric', $programId = -1, $comp = '=' ) {

        return $this->model->findArticle( $key, $val, $type, $programId, $comp );
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

        if ( ( !isset($post->post_type) ) || ( $post->post_type !== $this->cpt_slug ) ) {

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

                if ( 'assignments' == $field ) {

                    $assignments = array();

                    dbg("e20rArticle::saveSettings() - Process the assignments array");

                    if ( empty( $tmp[0] ) ) {

                        dbg("e20rArticle::saveSettings() - Assignments Array: The assignments key contains no data");
                        dbg("e20rArticle::saveSettings() - Create a new default assignment for the article ID: {$currentArticle->id}");

                        //Generate a default assignment for this article.
                        $tmp[0] = $e20rAssignment->createDefaultAssignment( $currentArticle );
                    }
                    else {

                        foreach ($tmp as $k => $assignmentId) {

                            if (is_null($assignmentId)) {

                                dbg("e20rArticle::saveSettings() - Assignments Array: Setting key {$k} has no ID}");
                                dbg("e20rArticle() - Create a new default assignment for this article ID: {$settings->id}");

                                //Generate a default assignment for this article.
                                $assignmentId = $e20rAssignment->createDefaultAssignment($settings);

                                dbg("e20rArticle::saveSettings() - Replacing empty assignment key #{$k} with value {$assignmentId}");
                            }

                            $tmp[$k] = $assignmentId;
                        }
                    }
                }

                if ( empty( $tmp ) ) {

                    $tmp = $defaults->{$field};
                    $settings->{$field} = $tmp;
                }
                else {

                    $settings->{$field} = $tmp;
                }
            }

            // Add post ID (article ID)
            $settings->id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;

            dbg( "e20rArticle::saveSettings() - Saving: " );
            dbg( $settings );

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
} 