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

        remove_meta_box( 'postexcerpt', 'e20r_articles', 'side' );
        remove_meta_box( 'wpseo_meta', 'e20r_articles', 'side' );
        add_meta_box('postexcerpt', __('Article Summary'), 'post_excerpt_meta_box', 'e20r_articles', 'normal', 'high');

        add_meta_box('e20r-tracker-article-settings', __('Article Settings', 'e20rtracker'), array( &$this, "addMeta_Settings" ), 'e20r_articles', 'normal', 'high');
        // add_meta_box('e20r-tracker-article-checkinlist', __('Check-in List', 'e20rtracker'), array( &$this, "addMeta_SettingsCheckin" ), 'e20r_articles', 'normal', 'high');

    }

    public function addMeta_Settings() {

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
        dbg("e20rArticle::addMeta_Settings() - Loading settings metabox for article page {$post->ID}");

        $settings = $this->model->loadSettings( $post->ID );
        ?>
        <div id="e20r-article-settings">
            <?php echo $this->view->viewArticleSettings( $settings , $lessons ); ?>
        </div>
        <?php
    }

    public function init( $postId = NULL ) {

        $this->articleId = parent::init( $postId );

        dbg("e20rArticle::contentFilter() - Loaded settings for {$this->articleId}");

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

    /**
     * Filters the content for a post to check whether ot inject e20rTracker Article info in the post/page.
     *
     * @param $content - Content to prepend data to if the article meta warrants is.
     *
     * @return string -- The content to display on the page/post.
     */
    public function contentFilter( $content ) {

        global $post;
        global $current_user;
        global $e20rMeasurements;

        dbg("e20rArticle::contentFilter() - loading article settings for page {$post->ID}");
        $this->articleId = $this->init( $post->ID );

        if ( ! $this->articleId ) {

            dbg("e20rArticle::contentFilter() - No article defined for this content. Exiting the filter.");
            return $content;
        }

        $measured = false;

        $rDay = $this->model->getSetting( $this->articleId, 'release_day' );
        $rDate =  $this->releaseDate( $this->articleId );

        dbg("e20rArticle::contentFilter() - Release Date for article: {$rDate} calculated from {$rDay}");

        $measured = $e20rMeasurements->areCaptured( $this->articleId, $current_user->ID, $rDate );

        $md = $this->isMeasurementDay( $this->articleId );

        // dbg("e20rArticle::contentFilter() - Settings: " . print_r( $settings, true));
        dbg("e20rArticle::contentFilter() - Check whether it's a measurement day or not: {$md}, {$measured}");

        if ( $md && !$measured ) {

            dbg("e20rArticle::contentFilter() - It's a measurement day!");
            $data = $this->view->viewMeasurementAlert( $this->isPhotoDay( $this->articleId ), $rDay, $this->articleId );
            $content = $data . $content;
        }

        if ( $md && $measured ) {

            dbg("e20rArticle::contentFilter() - Measurement day, and we've measured.");
            $data = $this->view->viewLessonComplete( $rDay, $md );
            $content = $data . $content;
        }

        if ( $this->hasCompletedLesson( $post->ID ) && ( !$md ) ) {

             $data = $this->view->viewLessonComplete( $rDay, false );
             $content = $data . $content;
        }

        return $content;
    }

    // Used on article post page to add check-in info for the article.
    public function addMeta_Checkin() {

    }

    public function addMeta_Assignments() {

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

    public function isMeasurementDay( $articleId ) {

        return ( $this->model->getSetting( $articleId, 'measurement_day' ) == 0 ? false : true );

    }

    public function isPhotoDay( $articleId ) {

        return ( $this->model->getSetting( $articleId, 'photo_day' ) == 0 ? false : true );
    }

/*
    private function defaults( ) {

        $this->meta = array (
            'category' => 'Lesson',
            'assignment_question_ids' => array(),
            'checkin_item_id' => 0,
            'is_measurement_day' => 1,
            'is_photo_day' => 1,
            'release_date' => '',
            'release_day' => 1, // Get from post_id's postmeta ('get sequence ID and delay value for sequence')
        );
    }
*/
    /**
     * An article can have multiple program IDs associated with it.
     *
     * A program can have one article per postID but many postIDs - also known as A program can have multiple article IDs.
     */

    /**
     * @param $programId
     */
    /*
    public function init( $userId = null ) {

        global $current_user, $post;

        if ( $userId === null ) {
            $userId = $current_user->ID;
        }

        if ( $userId == 0 ) {
            dbg("e20rArticle::init() - No logged in user defined. Returning...");
            return;
        }

        if ( ! $post->ID ) {
            dbg("e20rArticle::init() - No post ID available...?");
            return;
        }

        $this->post_id = $post->ID;

        if ( empty( $program_ids ) && ( $this->program_ids === false ) ) {

            dbg("e20rArticle::init() -- Loading array of program IDs from DB");
            $this->program_ids = get_post_meta( $this->post_id, 'e20r_tracker_program_ids', true);
        }

        if ( is_array( $program_ids ) && ( $this->program_ids === false ) ) {
            $this->program_ids = $program_ids;
        }
        elseif ( is_array( $program_ids ) && ( ! empty( $this->program_ids ) ) ) {

            dbg("e20rArticle::init() -- Received array of program IDs and existing array is present");
            $this->program_ids = array_merge( $this->program_ids, $program_ids );
            $this->program_ids = array_unique( $this->program_ids );
        }

        if ( (! is_array( $program_ids ) ) && ( $program_ids !== null ) ) {
            dbg("e20rArticle::init() -- Program ID specified, not an array of IDs");
            $this->program_ids = array( $program_ids );
        }

        dbg("e20rArticle::init() -- loading article settings");
        $this->meta = get_post_meta( $this->post_id, 'e20r_article_meta', true);

        $this->meta = wp_parse_args( $this->meta, $this->model->defaultSettings() );

        if ( empty( $this->meta ) ) {
            dbg("e20rArticle::init() -- Loading default article meta");
            $this->model->defaultSettings();
        }

        dbg("e20rArticle::init() -- loading program ID for user");
        $this->user_programId = get_user_meta( $userId, 'e20r_program', true);

        dbg("e20rArticle::init() -- loading drip-feed settings for article");
        $this->sequence_list = get_post_meta( $this->post_id, '_post_sequences', true);

        dbg("e20rArticle::init() - Belongs to sequence(s): " . print_r( $this->sequence_list, true ) );

    }
*/
    /*
    public function save() {

        if ( update_post_meta( $this->post_id, 'e20r_programs', $this->program_ids ) === false ) {
            throw new Exception( "Error saving list of programs for article (id: {$this->post_id})" );
        }

        if ( update_post_meta( $this->post_id, 'e20r_article_meta', $this->meta ) === false ) {
            throw new Exception( "Error saving article settings (id: {$this->post_id})" );
        }
    }

    public function view_articlePostMetabox() {

        $metabox = '';

        global $post;

        $this->programs =  ( $metabox['args']['program_ids'] != false ? $metabox['args']['program_ids'] : null );
        $this->init( $post->ID ); // Self-init.

        dbg("e20rArticle() - Article data for Post {$post->ID} loaded. " . print_r( $this->program_ids, true) );

        ob_start();
        ?>
        <div class="submitbox" id="e20r-tracker-article-postmeta">
            <div id="minor-publishing">
                <div id="e20r-article-postmetabox">
                    <?php echo $this->view_articleMetabox( $post->ID, $this->program_ids ) ?>
                </div>
            </div>
        </div>
        <?php

        $metabox = ob_get_clean();

        echo $metabox;
    }


    private function hasProgramId() {

        global $post;

        if ( $post->ID ) {
            dbg("hasProgramId() -  Post ID defined.. Looking up the program Ids.");
            get_post_meta( $post->ID, 'e20r_tracker_program_ids', true);
            return ( empty( $this->program_ids ) ? false : true );
        }

        return false;
    }
*/
/*
    public function editor_metabox_setup( $object, $box ) {

        if ( false === ( $program_ids = $this->hasProgramId() ) ) {

            dbg("e20rArticle::editor_metabox_setup() -  Warning: Not loading the metabox since there are no program(s) defined for the post");
            return;
        }

        $program_ids = 0;
        dbg("e20rArticle() - Metabox for Post/Page editor being loaded");
        global $e20rTracker;

        foreach( $e20rTracker->managed_types as $type ) {

            add_meta_box( 'e20r-article-meta', __( 'Article Configuration', 'e20r_tracker' ),
                array( &$this, 'view_articlePostMetabox' ), 'e20r_articles', 'advanced', 'high' );
        }
    }

    public function view_manageArticles() {

        $html = '';

        return $html;
    }

    public function setId( $id = null ) {

        if ( $id === null ) {

            global $post;

            $id = $post->ID;
        }

        $this->post_id = $post;
        $this->init( $id );
    }

    private function load_articles() {

        if ( ! empty( $this->program_ids ) ) {

            foreach ( $this->program_ids as $pid ) {

                $this->postArticles[ $pid ] = get_user_meta( $this->post_id, "e20r_articles_{$pid}", true );
            }
        }
    }

    public function render_submenu_page() {

        ?>
        <div id="e20r-articles">
            <?php

            echo $this->view_manageArticles();

            ?>
        </div>
    <?php

    }
*/
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

    private function findArticle( $id ) {

        foreach ( $this->article_list as $key => $article ) {

            if ( $article->id == $id ) {

                return $key;
            }
        }

        return false;
    }
} 