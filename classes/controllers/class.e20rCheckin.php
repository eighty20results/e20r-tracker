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

    public function shortcode_dailyProgress( $attributes ) {

        global $post;
        global $current_user;
        global $e20rTracker;
        global $e20rArticle;
        global $e20rProgram;

        $type = 'action';
        $survey_id = null;

        extract( shortcode_atts( array(
            'type' => $type,
            'form_id' => $survey_id,
        ), $attributes ) );

        $articleId = $e20rArticle->init($post->ID);
        $programId = $e20rProgram->getProgramIdForUser( $current_user->ID, $articleId );

        if ( ( strtolower($type) == 'action' ) || ( strtolower($type) == 'activity' ) ) {

            dbg("e20rCheckin::shortcode_dailyProgress() - Configured daily action check-ins");

            // Check if this is a "Yesterday/tomorrow" link click.
            if ( ! isset( $_POST['e20r-checkin-day'] ) ) {

                dbg("e20rCheckin::shortcode_dailyProgress() - No delay specified. Using 'now' for user {$current_user->ID}");
                $delay = 'now';
            }
            else {

                $delay = intval( $_POST['e20r-checkin-day'] );
            }

            $delay = $e20rTracker->getDelay( $delay );
            dbg("e20rCheckin::shortcode_dailyProgress() - Using delay value of {$delay} days");

            if ( $articleId === false ) {

                $articleId = $e20rArticle->findArticleByDelay( $delay );
            }

            // Get the check-in id list for the specified article ID
            $checkinIds = $e20rArticle->getCheckins( $articleId );

            if ( ! $checkinIds ) {

                $checkinIds = $this->model->findActionByDate( date( 'Y-m-d', current_time('timestamp') ), $programId );
            }

            dbg( "e20rCheckin::shortcode_dailyProgress() - Article info loaded." );
            dbg( $checkinIds );

            foreach ( $checkinIds as $id ) {

                $settings = $this->model->loadSettings( $id );

                switch ( $settings->checkin_type ) {

                    case $this->types['assignment']:

                        dbg( "e20rCheckin::shortcode_dailyProgress() - Loading data for assignment check-in" );

                        // TODO: Load view for assignment check-in (including check for questions & answers to load).
                        $checkin = null;
                        break;

                    case $this->types['survey']:

                        dbg( "e20rCheckin::shortcode_dailyProgress() - Loading data for survey check0-in" );
                        // TODO: Load view for survey data (pick up survey(s) from Gravity Forms entry.
                        $checkin = null;
                        break;

                    case $this->types['action']:

                        dbg( "e20rCheckin::shortcode_dailyProgress() - Loading data for daily action checkin" );

                        $checkin            = $this->model->loadUserCheckin( $articleId, $current_user->ID, $settings->checkin_type, $settings->short_name );
                        $checkin->actionList = $this->model->getActions( $id, $settings->checkin_type, - 3 );

                        break;

                    case $this->types['activity']:

                        dbg( "e20rCheckin::shortcode_dailyProgress() - Loading data for daily activity checkin" );
                        $checkin = $this->model->loadUserCheckin( $articleId, $current_user->ID, $settings->checkin_type, $settings->short_name );
                        break;

                    case $this->types['note']:
                        // TODO: Decide.. Do we handler this in the action check-in?
                        dbg( "e20rCheckin::shortcode_dailyProgress() - Loading data for daily activity note(s)" );
                        break;

                    default:

                        // Load action and acitvity view.
                        dbg( "e20rCheckin::shortcode_dailyProgress() - No default action to load!" );
                        $checkin = null;

                }

                // Reset the value to true Y-m-d format
                $checkin->checkin_date                    = date( 'Y-m-d', strtotime( $checkin->checkin_date ) );
                $this->checkin[ $settings->checkin_type ] = $checkin;
            }

            dbg( "e20rCheckin::shortcode_dailyProgress() - Loading checkin for user and delay{$delay}.." );
            dbg( $this->checkin );

            return $this->view->load_UserCheckin( $this->checkin, $delay, $settings->checkin_type );
        }

        if ( strtolower($type) == 'assignment' ) {

            if ( $articleId === false ) {

                dbg("e20rCheckin::shortcode_dailyProgress() - No article defined. Quitting.");
                return false;
            }

            dbg("e20rCheckin::shortcode_dailyProgress() - Loading pre-existing data for the lesson/assignment ");

            // $assignments = $e20rAssignment->getAssignment( $articleId );

            // return $this->view->viewAssignment( $assignments, $articleId, $delay );
        }
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