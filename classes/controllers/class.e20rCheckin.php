<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckin {

    private $checkin = array();
    public $model = null;
    public $view = null;

    public function e20rCheckin() {

        dbg("e20rProgram:: - Initializing Checkin class");

        $this->model = new e20rCheckinModel();
        $this->view = new e20rCheckinView();
    }


    public function getCheckin( $shortName ) {

        $chkinList = $this->model->loadAllCheckins( 'any' );

        foreach ($chkinList as $chkin ) {

            if ( $chkin->short_name == $shortName ) {

                unset($chkinList);
                return $chkin;
            }
        }

        unset($chkinList);
        return false; // Returns false if the program isn't found.
    }

    public function getAllCheckins() {

        return $this->model->loadAllData();

    }
    public function getCheckinSettings( $id ) {

        return $this->model->loadSettings( $id );
    }

    public function editor_metabox_setup( $object, $box ) {

        add_meta_box('e20r-tracker-checkin-settings', __('Checkin Settings', 'e20rtracker'), array( &$this, "addMeta_CheckinSettings" ), 'e20r_checkins', 'normal', 'high');

    }

    public function saveSettings( $post_id ) {

        global $post;

        dbg("e20rCheckin::saveSettings() - Saving Program Settings to DB");

        if ( $post->post_type != 'e20r_checkins') {
            return $post_id;
        }

        if ( empty( $post_id ) ) {
            dbg("e20rCheckin::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }

        dbg("e20rCheckin::saveSettings()  - Saving metadata for the post_type(s)");

        $settings = new stdClass();
        $settings->id = $post_id;
        $settings->reps = isset( $_POST['e20r-checkin-reps'] ) ? intval( $_POST['e20r-checkin-reps'] ) : null;
        $settings->rest = isset( $_POST['e20r-checkin-rest'] ) ? intval( $_POST['e20r-checkin-rest'] ) : null;

        $this->model->saveSettings( $settings );
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

    public function addMeta_CheckinSettings() {

        global $post;

        $query = array(
            'post_type'   => 'e20r_programs',
            'post_status' => 'publish',
        );

        wp_reset_query();

        /* Fetch Workouts */
        $programs = get_posts( $query );

        if ( empty( $programs ) ) {

            dbg( "e20rWorkoutModel::loadProgramData() - No programs found!" );
        }

        dbg("e20rCheckin::addMeta_CheckinSettings() - Loading settings metabox for checkin page");

        echo $this->view->viewSettingsBox( $this->model->loadCheckinData( $post->ID ), $programs );

    }
} 