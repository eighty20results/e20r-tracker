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
/*
    private $post_id;
    private $program_ids = false;
    private $user_programId = 0;
    private $meta;
    private $sequence_list = array();
*/
    protected $articleId;

    protected $model;
    protected $view;

    public function e20rArticle() {

        dbg("e20rArticle::__construct() - Initializing Article class");
        $this->model = new e20rArticleModel();
        $this->view = new e20rArticleView();

        parent::__construct( 'article', 'e20r_articles', $this->model, $this->view );
    }


    public function editor_metabox_setup( $object, $box ) {

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

        // Query to load all available programs (used with check-in definition)
        $query = array(
            'post_type'   => apply_filters( 'e20r_tracker_article_types', array( 'page', 'post' ) ),
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

        $this->articleId = parent::init( $postId );

        dbg("e20rArticle::init() - Loaded settings for {$this->articleId}");

        if ( ! $this->articleId ) {

            return false;
        }

        return $this->articleId;
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

    public function hasCompletedLesson( $postId = null ) {

        global $post;

        if ( is_null( $postId ) ) {

            $postId = $post->ID;
        }

        if ( is_null( $this->articleId ) ) {

            $this->articleId = $this->init( $postId );
        }

        if ( ! $this->articleId ) {

            return false;
        }

        return $this->model->lessonComplete( $this->articleId );

    }

    public function getLessonExcerpt( $articleId ) {

        global $post;

        $postId = $this->model->getSetting( $articleId, 'post_id');

        $articles = new WP_Query( array(
            'post_type'           => apply_filters( 'e20r-tracker-lesson-type-filter', array( 'any' ) ),
            'post_status'         => apply_filters( 'e20r-tracker-post-status-filter', array( 'publish' ) ),
            'posts_per_page'      => 1,
            'p'                   => $postId,
            'ignore_sticky_posts' => true,
        ) );

        dbg( "e20rArticle::getLessonExcerpt() - Number of posts in {$articleId} is {$articles->found_posts}" );

        $prefix = $this->model->getSetting( $articleId, 'prefix' );
        dbg( "e20rArticle::getLessonExcerpt() - Prefix for lesson: {$prefix}");

        if ( $articles->found_posts > 0 ) {

            ob_start();

            while ( $articles->have_posts() ) : $articles->the_post();

                $image = ( has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail( $post->ID, 'pmpro_seq_recentpost_widget_size' ) : '<div class="noThumb"></div>' );
                ?>
                <h4>
                    <span class="e20r-excerpt-prefix"><?php echo "{$prefix} "; ?></span><?php echo get_the_title(); ?>
                </h4>
                <p class="e20r-descr"><?php echo $post->post_excerpt; ?></p>
                <p class="e20r-descr"><a href="<?php echo get_permalink() ?>" title="<?php the_title(); ?>">
                        <?php _e( 'Click to read lesson', 'e20tracker' ); ?>
                    </a>
                </p>
            <?php
            endwhile;

            wp_reset_postdata();

            $html = ob_get_clean();
        }
        else {
            dbg("e20rArticle::getLessonExcerpt() - No posts found. Returning null ");
            $html = null;
        }

        return $html;
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
        global $current_user;

        $programId = $e20rProgram->getProgramIdForUser( $current_user->ID );

        if ( $programId == -1 ) {
            // No article either.
            return -1;
        }

        $article = $this->model->findArticle('release_day', $delayVal, 'numeric', $programId );

        if ( count($article) == 1 ) {

            dbg("e20rArticle::findArticleByDelay() - Returning a single articleID: {$article->id}");
            return $article->id;
        }
        else {
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

        global $post;
        global $current_user;
        global $e20rMeasurements;
        global $e20rProgram;
        global $e20rTracker;

        dbg("e20rArticle::contentFilter() - loading article settings for page {$post->ID}");
        $this->init( $post->ID );

        if ( empty($this->articleId) ) {

            dbg("e20rArticle::contentFilter() - No article defined for this content. Exiting the filter.");
            return $content;
        }

        if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

            dbg("e20rArticle::contentFilter() - User doesn't have access to this post/page. Exiting the filter.");
            return $content;
        }

        $measured = false;

        $rDay = $this->model->getSetting( $this->articleId, 'release_day' );
        $rDate =  $this->releaseDate( $this->articleId );
        $programId = $e20rProgram->getProgramIdForUser( $current_user->ID );

        dbg("e20rArticle::contentFilter() - Release Date for article: {$rDate} calculated from {$rDay}");

        $md = $this->isMeasurementDay( $this->articleId );

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
                $data = $this->view->viewLessonComplete( $rDay, $md, $this->articleId );
                $content = $data . $content;
            }
        }

        if ( $this->hasCompletedLesson( $post->ID ) && ( !$md ) ) {

             $data = $this->view->viewLessonComplete( $rDay, false, $this->articleId );
             $content = $data . $content;
        }

        return $content;
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

        dbg("e20rArticle::add_assignment_callback().");
        check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-article-settings-nonce' );
        dbg("e20rArticle::add_assignment_callback() - Saving new assignment for article.");

        $articleId = isset($_POST['e20r-article-id']) ? intval( $_POST['e20r-article-id']) : null;
        $assignmentId = isset($_POST['e20r-assignment-id']) ? intval( $_POST['e20r-assignment-id']) : null;
        $assignment_orderNum = isset($_POST['e20r-assignment-order_num']) ? intval( $_POST['e20r-assignment-order_num'] ) : null;

        dbg("e20rArticle::add_assignment_callback() - Article: {$articleId}, Assignment: {$assignmentId}, Assignment Order#: {$assignment_orderNum}");

        $this->articleId = $articleId;
        $this->init( $this->articleId );

        $artSettings = $this->model->getSettings();
	    dbg("e20rArticle::add_assignment_callback() - Article settings for ({$articleId}): ");
	    dbg($artSettings);

        $assignment = $e20rAssignment->loadAssignment( $assignmentId );

        dbg("e20rArticle::add_assignment_callback() - Updating Assignment ({$assignmentId}) settings & saving.");
        $assignment->order_num = $assignment_orderNum;
        $assignment->article_id = $articleId;

        dbg("e20rArticle::add_assignment_callback() - Assignment settings for ({$assignmentId}): ");
        $e20rAssignment->saveSettings( $articleId, $assignment );

        dbg("e20rArticle::add_assignment_callback() - Updating Article settings for ({$articleId}): ");
        if ( empty( $artSettings->assignments) ) {

            $artSettings->assignments = array( $assignmentId );
        }
        else {
	        if ( ! in_array( $assignmentId, $artSettings->assignments ) ) {
		        $artSettings->assignments[] = $assignmentId;
	        }
        }

        dbg($artSettings);

        $this->model->set( 'assignments', $artSettings->assignments, $articleId );

        dbg("e20rArticle::add_assignment_callback() - Generating the assignments metabox for the article {$articleId} definition");

	    $toBrowser = array(
	        'success' => true,
	        'data' => $e20rAssignment->configureArticleMetabox( $articleId ),
        );

        if ( ! empty( $toBrowser['data'] ) ) {

            dbg("e20rArticle::add_assignment_callback() - Transmitting new HTML for metabox");
            // wp_send_json_success( $html );

            // dbg($html);
            echo json_encode( $toBrowser );
            wp_die();
        }

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

        $release_date = $e20rTracker->getDateForPost( $this->model->getSetting( $articleId, 'release_day' ) );

        dbg( "e20rArticle::releaseDate: {$release_date}" );

        return ( !$release_date ? false : $release_date );
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

    public function getCheckins( $articleId ) {

        dbg("e20rArticle::getCheckins() - Get array of checkin IDs");

        $setting = $this->model->getSetting( $articleId, 'checkins' );

        if ( empty($setting)) {
            dbg("e20rArticle::getCheckins() - NO checkin IDs found for this article({$articleId})");
            return false;
        }

        return $setting;
    }

    public function setId( $id = null ) {

        if ( $id === null ) {

            global $post;

            $id = $post->ID;
        }

        $this->post_id = $post;
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

        if ( $key = $this->findArticle( $id ) !== false ) {
            return $this->article_list[$key];
        }

        return false;
    }


    public function getID() {

        return $this->post_id;
    }

    public function findArticle( $type = 'id', $val = null ) {

        return $this->model->findArticle( $type, $val );
    }
} 