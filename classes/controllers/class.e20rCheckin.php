<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckin extends e20rSettings {

    private $checkin = array();

    protected $model;
    protected $view;

    // checkin_type: 0 - action (habit), 1 - lesson, 2 - activity (workout), 3 - survey
    // "Enum" for the types of check-ins
    private $types = array(
        'none' => 0,
        'action' => CHECKIN_ACTION,
        'assignment' => CHECKIN_ASSIGNMENT,
        'survey' => CHECKIN_SURVEY,
        'activity' => CHECKIN_ACTIVITY,
    );

    // checkedin values: 0 - false, 1 - true, 2 - partial, 3 - not applicable
    // "Enum" for the valid statuses.
    private $status = array(
        'no' => 0,
        'yes' => 1,
        'partial' => 2,
        'na' => 3
    );

    public function __construct() {

        dbg("e20rCheckin::__construct() - Initializing Checkin class");

        $this->model = new e20rCheckinModel();
        $this->view = new e20rCheckinView();

        parent::__construct( 'checkin', 'e20r_checkins', $this->model, $this->view );
    }

    public function findCheckinItemId( $articleId ) {

        global $e20rArticle;
    }

    public function saveCheckin_callback() {

        dbg("e20rCheckin::saveCheckin_callback() - Attempting to save checkin for user.");

        // Save the $_POST data for the Action callback
        global $current_user;

        dbg("e20rCheckin::saveCheckin_callback() - Content of POST variable:");
        dbg($_POST);

        $data = array(
            'user_id' => $current_user->ID,
            'id' => (isset( $_POST['id']) ? ( intval( $_POST['id'] ) != 0 ?  intval( $_POST['id'] ) : null ) : null),
            'checkin_type' => (isset( $_POST['checkin-type']) ? intval( $_POST['checkin-type'] ) : null),
            'article_id' => (isset( $_POST['article-id']) ? intval( $_POST['article-id'] ) : null ),
            'program_id' => (isset( $_POST['program-id']) ? intval( $_POST['program-id'] ) : -1 ),
            'checkin_date' => (isset( $_POST['checkin-date']) ? sanitize_text_field( $_POST['checkin-date'] ) : null ),
            'checkin_short_name' => isset( $_POST['checkin-short-name']) ? sanitize_text_field( $_POST['checkin-short-name'] ) : null,
            'checkedin' => (isset( $_POST['checkedin']) ? intval( $_POST['checkedin'] ) : null),
        );

        if ( ! $this->model->setCheckin( $data ) ) {

            dbg("e20rCheckin::saveCheckin_callback() - Error saving checkin information...");
            wp_send_json_error();
            wp_die();
        }

        wp_send_json_success();
        wp_die();
    }

    public function setArticleAsComplete( $userId, $articleId ) {

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        // $articleId = $e20rArticle->init( $articleId );
        $programId = $e20rProgram->getProgramIdForUser( $userId );

        $defaults = array(
            'user_id' => $userId,
            'checkedin' => $this->status['yes'],
            'article_id' => $articleId,
            'program_id' => $programId,
            'checkin_date' => $e20rArticle->getReleaseDate( $articleId ),
            'checkin_short_name' => null,
            'checkin_type' => $this->types['action'],
            'checkin_note' => null,
        );

        if ( $this->model->setCheckin( $defaults ) ) {
            dbg("e20rCheckin::setArticleAsComplete() - Check-in for user {$userId}, article {$articleId} in program ${$programId} has been saved");
            return true;
        }

        dbg("e20rCheckin::setArticleAsComplete() - Unable to save check0in value!");
        return false;
    }

    /*
    public function getCheckin( $shortName ) {

        $chkinList = $this->model->loadAllSettings( 'any' );

        foreach ($chkinList as $chkin ) {

            if ( $chkin->short_name == $shortName ) {

                unset($chkinList);
                return $chkin;
            }
        }

        unset($chkinList);
        return false; // Returns false if the program isn't found.
    }
*/
    /*
    public function getAllCheckins() {

        return $this->model->loadAllSettings();

    }
    public function getCheckinSettings( $id ) {

        return $this->model->loadSettings( $id );
    }
*/
    public function editor_metabox_setup( $object, $box ) {

        add_meta_box('e20r-tracker-checkin-settings', __('Check-In Settings', 'e20rtracker'), array( &$this, "addMeta_Settings" ), 'e20r_checkins', 'normal', 'high');

    }

    public function dailyProgress( $config ) {

        global $e20rTracker;
        global $e20rArticle;

        global $current_user;

        if ( $config->delay > ( $config->delay_byDate + 2 ) ) {
            // The user is attempting to view a day >2 days after today.
            $config->maxDelayFlag = CONST_MAXDAYS_FUTURE;
        }

        if ( $config->delay < ( $config->delay_byDate - 2 ) ) {

            // The user is attempting to view a day >2 days before today.
            $config->maxDelayFlag = CONST_MAXDAYS_PAST;
        }

        $config->prev = $config->delay - 1;
        $config->next = $config->delay + 1;

//        $config->articleId = $e20rArticle->findArticleByDelay( $config->delay );
//        dbg("e20rCheckinView::view_actionAndActivityCheckin() - ArticleID: {$articleId}");

        $t = $e20rTracker->getDateFromDelay( $config->next );
        $config->tomorrow = date_i18n( 'l M. jS', strtotime( $t ));

        $y = $e20rTracker->getDateFromDelay( $config->prev );
        $config->yesterday = date_i18n( 'l M. jS', strtotime( $y ) );


        if ( ( strtolower($config->type) == 'action' ) || ( strtolower($config->type) == 'activity' ) ) {

            dbg("e20rCheckin::dailyProgress() - Configured daily action check-ins for article ID(s):");
            dbg($config->articleId);

            if ( empty( $config->articleId ) ) {

                dbg("e20rCheckin::dailyProgress() -  No articleId specified. Searching...");
                $config->articleId = $e20rArticle->findArticleByDelay( $config->delay );

                if ( empty( $config->articleId ) ) {
                    dbg("e20rCheckin::dailyProgress() - No article found. Using default of -1");
                    $config->articleId = CONST_NULL_ARTICLE;
                }
            }

            if ( $config->articleId !== CONST_NULL_ARTICLE ) {

                dbg("e20rCheckin::dailyProgress() - Loading lesson & activity excerpts");

                $config->lessonExcerpt = $e20rArticle->getLessonExcerpt( $config->articleId );
                // TODO: Load $config->activityExcerpt (first need to create Activity stuff)
            }
            // Get the check-in id list for the specified article ID
            $checkinIds = $e20rArticle->getCheckins( $config->articleId );

            if ( ! $checkinIds ) {

                $config->post_date = $e20rTracker->getDateForPost( $config->delay );
                $checkinIds = $this->model->findActionByDate( $config->post_date , $config->programId );
            }

            dbg( "e20rCheckin::dailyProgress() - Checkin/article info loaded." );
            // dbg( $checkinIds );

            foreach ( $checkinIds as $id ) {

                $settings = $this->model->loadSettings( $id );

                switch ( $settings->checkin_type ) {

                    case $this->types['assignment']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for assignment check-in" );

                        // TODO: Load view for assignment check-in (including check for questions & answers to load).
                        $checkin = null;
                        break;

                    case $this->types['survey']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for survey check-in" );
                        // TODO: Load view for survey data (pick up survey(s) from Gravity Forms entry.
                        $checkin = null;
                        break;

                    case $this->types['action']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for daily action check-in" );

                        $checkin            = $this->model->loadUserCheckin( $config->articleId, $current_user->ID, $settings->checkin_type, $settings->short_name );
                        $checkin->actionList = $this->model->getActions( $id, $settings->checkin_type, - 3 );

                        break;

                    case $this->types['activity']:

                        dbg( "e20rCheckin::dailyProgress() - Loading data for daily activity check-in" );
                        $checkin = $this->model->loadUserCheckin( $config->articleId, $current_user->ID, $settings->checkin_type, $settings->short_name );
                        break;

                    case $this->types['note']:
                        // TODO: Decide.. Do we handler this in the action check-in?
                        dbg( "e20rCheckin::dailyProgress() - Loading data for daily activity note(s)" );
                        break;

                    default:

                        // Load action and acitvity view.
                        dbg( "e20rCheckin::dailyProgress() - No default action to load!" );
                        $checkin = null;

                }

                // Reset the value to true Y-m-d format
                $checkin->checkin_date                    = date( 'Y-m-d', strtotime( $checkin->checkin_date ) );
                $this->checkin[ $settings->checkin_type ] = $checkin;
            }

            dbg( "e20rCheckin::dailyProgress() - Loading checkin for user and delay {$config->delay}.." );
            dbg( $this->checkin );

            return $this->load_UserCheckin( $config, $this->checkin );
        }

        if ( strtolower($config->type) == 'assignment' ) {

            if ( $config->articleId === false ) {

                dbg("e20rCheckin::dailyProgress() - No article defined. Quitting.");
                return null;
            }

            dbg("e20rCheckin::dailyProgress() - Loading pre-existing data for the lesson/assignment ");

            $assignments = $e20rArticle->getAssignments( $config->articleId );
	        dbg($assignments);
            // return $this->view->viewAssignment( $assignments, $articleId, $delay );
        }
    }

    public function nextCheckin_callback() {

        dbg("e20rCheckin::nextCheckin_callback() - Checking ajax referrer privileges");
        check_ajax_referer('e20r-checkin-data', 'e20r-checkin-nonce');

        dbg("e20rCheckin::nextCheckin_callback() - Checking ajax referrer has the right privileges");

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        global $current_user;
        global $post;

        $config = new stdClass();

        $config->type = 'action';
        $config->survey_id = null;
        $config->post_date = null;

        $config->userId = $current_user->ID;
        $config->startTS = $e20rProgram->startdate( $config->userId );
        $config->url = URL_TO_CHECKIN_FORM;

        $config->articleId = ( ! isset( $_POST['article-id'] ) ? $e20rArticle->init($post->ID) : intval($_POST['article-id']) );
        $config->programId = ( ! isset( $_POST['program-id'] ) ? $e20rProgram->getProgramIdForUser( $config->userId, $config->articleId ) : intval( $_POST['program-id'] ) );

        $config->delay = ( ! isset( $_POST['e20r-checkin-day'] ) ? $e20rTracker->getDelay( 'now' ) : intval( $_POST['e20r-checkin-day'] ) );
        $config->delay_byDate = $e20rTracker->daysBetween( $config->startTS, current_time('timestamp') );

        dbg("e20rCheckin::nextCheckin_callback() - Article: {$config->articleId}, Program: {$config->programId}, delay: {$config->delay}");

        if ( ( $html = $this->dailyProgress( $config ) ) !== false ) {

            dbg("e20rCheckin::nextCheckin() - Sending new dailyProgress data (html)");
            wp_send_json_success( $html );
        }

        wp_send_json_error();
    }

    public function shortcode_dailyProgress( $attributes = null ) {

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        global $current_user;
        global $post;

        $config = new stdClass();

        $config->type = 'action';
        $config->survey_id = null;
        $config->post_date = null;
        $config->maxDelayFlag = null;
        $config->url = URL_TO_CHECKIN_FORM;

        $config->userId = $current_user->ID;
        $config->startTS = $e20rProgram->startdate( $config->userId );
        $config->delay = $e20rTracker->getDelay( 'now' );
        $config->delay_byDate = $config->delay;

        $tmp = shortcode_atts( array(
            'type' => 'action',
            'form_id' => null,
        ), $attributes );

	    foreach ( $tmp as $key => $val ) {

		    $config->{$key} = $val;
	    }

	    dbg( $config );

	    if ( $config->type == 'assignment' ) {

		    dbg("e20rCheckin::shortcode_dailyProgress() - Finding article info by post_id: {$post->ID}");
		    $article = $e20rArticle->findArticle( 'post_id', $post->ID );
	    }
	    elseif ( $config->type == 'action' ) {

		    dbg("e20rCheckin::shortcode_dailyProgress() - Finding article info by delay value of {$config->delay} days");
		    $article = $e20rArticle->findArticle( 'release_day', $config->delay );
	    }


	    dbg("e20rCheckin::shortcode_dailyProgress() - Article object:");
	    dbg( $article );

        $config->articleId = $article->id;
        $config->programId = $e20rProgram->getProgramIdForUser( $config->userId, $config->articleId );

        ob_start();
        ?>
        <div id="e20r-daily-progress">
            <?php echo $this->dailyProgress( $config ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function load_UserCheckin( $config, $checkinArr ) {

        $action = null;
        $activity = null;
        $assignment = null;
        $survey = null;
        $view = null;

        if ( ! empty($checkinArr) ) {

            dbg( "e20rCheckinView::load_UserCheckin() - Array of checkin values isn't empty..." );
            dbg($checkinArr);

            foreach ( $checkinArr as $type => $c ) {

                dbg( "e20rCheckinView::load_UserCheckin() - Loading view type {$type} for checkin" );

                if ( $type == CHECKIN_ACTION ) {

                    dbg( "e20rCheckinView::load_UserCheckin() - Setting Action checkin data" );
                    $action = $c;
                }

                if ( $type == CHECKIN_ACTIVITY ) {

                    dbg( "e20rCheckinView::load_UserCheckin() - Setting Activity checkin data" );
                    $activity = $c;
                }

                if ( $type == CHECKIN_ASSIGNMENT ) {
                    $assignment = $c;
                }

                if ( $type == CHECKIN_SURVEY ) {
                    $survey = $c;
                }
                /*
							if ( $type == CHECKIN_NOTE ) {
								$note = $c;
							}
				*/
            }

            if ( ( ! empty( $action ) ) && ( ! empty( $activity ) ) ) {

                dbg( "e20rCheckinView::load_UserCheckin() - Loading the view for the Actions & Activity check-in." );
                $view = $this->view->view_actionAndActivityCheckin( $config, $action, $activity, $action->actionList );
            }
        }
        else if ( ( $config->type == CHECKIN_ACTION ) || ( $config->type == CHECKIN_ACTIVITY ) ) {
            dbg("e20rCheckinView::load_UserCheckin() - An activity or Action check-in requested...");
            $view = $this->view->view_actionAndActivityCheckin( $config, $action, $activity, $action->actionList );
        }

        return $view;
    }

    public function getPeers( $checkinId = null ) {

        if ( is_null( $checkinId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $checkinId = $post->post_parent;
        }

        $checkins = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $checkinId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $checkinList = array(
            'pages' => $checkins->posts,
        );

        foreach ( $checkinList->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $checkins->posts[$k-1] ) ) {

                    $checkinList['prev'] = $checkins->posts[ $k - 1 ];
                }

                if( isset( $checkins->posts[$k+1] ) ) {

                    $checkinList['next'] = $checkins->posts[ $k + 1 ];
                }
            }
        }

        return $checkinList;
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
            'post_type'   => 'e20r_programs',
            'post_status' => apply_filters( 'e20r_tracker_checkin_status', $def_status ),
            'posts_per_page' => -1,
        );

        wp_reset_query();

        //  Fetch Programs
        $checkins = get_posts( $query );

        if ( empty( $checkins ) ) {

            dbg( "e20rCheckin::addMeta_Settings() - No programs found!" );
        }

        dbg("e20rCheckin::addMeta_Settings() - Loading settings metabox for checkin page {$post->ID}");
        $settings = $this->model->loadSettings( $post->ID );

        echo $this->view->viewSettingsBox( $settings , $checkins );

    }

    public function saveSettings( $post_id ) {

        $post = get_post( $post_id );

        setup_postdata( $post );

        $this->model->set( 'short_name', the_title() );
        $this->model->set( 'item_text', the_excerpt() );

        parent::saveSettings( $post_id );
    }
}