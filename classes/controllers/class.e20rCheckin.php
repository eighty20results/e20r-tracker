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
        'action' => 0,
        'lesson' => 1,
        'activity' => 2,
        'survey' => 3
    );

    // checkedin values: 0 - false, 1 - true, 2 - partial, 3 - not applicable
    // "Enum" for the valid statuses.
    private $status = array(
        'no' => 0,
        'yes' => 1,
        'partial' => 2,
        'na' => 3
    );

    public function e20rCheckin() {

        dbg("e20rCheckin::__construct() - Initializing Checkin class");

        $this->model = new e20rCheckinModel();
        $this->view = new e20rCheckinView();

        parent::__construct( 'checkin', 'e20r_program', $this->model, $this->view );
    }

    public function findCheckinItemId( $articleId ) {

        global $e20rArticle;
    }

    public function setArticleAsComplete( $userId, $articleId ) {

        global $e20rArticle;
        global $e20rProgram;
        global $e20rTracker;

        // $articleId = $e20rArticle->init( $articleId );
        $programId = $e20rProgram->getProgramIdForUser( $userId );

        $checkin = array(
            'user_id' => $userId,
            'checkedin' => $this->status['yes'],
            'article_id' => $articleId,
            'program_id' => $programId,
            'checkin_date' => $e20rArticle->getReleaseDate( $articleId ),
            'checkin_item_id' => null, // This is the 'checkin_item_id', aka post->ID for the checkin CPT in question.
            'checkin_type' => 2,
        );

        if ( $this->model->setCheckin( $checkin ) ) {
            dbg("e20rCheckin::setArticleAsComplete() - Check-in for user {$userId}, article {$articleId} in program ${$programId} has been saved");
            return true;
        }

        dbg("e20rCheckin::setArticleAsComplete() - Unable to save checkin value!");
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

        // Query to load all available programs (used with check-in definition)
        $query = array(
            'post_type'   => 'e20r_programs',
            'post_status' => 'publish',
        );

        wp_reset_query();

        //  Fetch Programs
        $programs = get_posts( $query );

        if ( empty( $programs ) ) {

            dbg( "e20rCheckin::addMeta_CheckinSettings() - No programs found!" );
        }

        dbg("e20rCheckin::addMeta_CheckinSettings() - Loading settings metabox for checkin page {$post->ID}");
        $settings = $this->model->loadSettings( $post->ID );

        echo $this->view->viewSettingsBox( $settings , $programs );

    }

} 