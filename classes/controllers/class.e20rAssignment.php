<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rAssignment extends e20rSettings {

    private $assignment = array();

    protected $model;
    protected $view;

    public function __construct() {

        dbg("e20rAssignment::__construct() - Initializing Assignment class");

        $this->model = new e20rAssignmentModel();
        $this->view = new e20rAssignmentView();

        parent::__construct( 'assignment', 'e20r_assignments', $this->model, $this->view );
    }

    public function findAssignmentItemId( $articleId ) {

        global $e20rArticle;
    }

    /*
    public function saveAssignment_callback() {

        dbg("e20rAssignment::saveAssignment_callback() - Attempting to save assignment for user.");

        // Save the $_POST data for the Action callback
        global $current_user;

        dbg("e20rAssignment::saveAssignment_callback() - Content of POST variable:");
        dbg($_POST);

        $data = array(
            'user_id' => $current_user->ID,
            'id' => (isset( $_POST['id']) ? ( intval( $_POST['id'] ) != 0 ?  intval( $_POST['id'] ) : null ) : null),
            'assignment_type' => (isset( $_POST['assignment-type']) ? intval( $_POST['assignment-type'] ) : null),
            'article_id' => (isset( $_POST['article-id']) ? intval( $_POST['article-id'] ) : null ),
            'program_id' => (isset( $_POST['program-id']) ? intval( $_POST['program-id'] ) : -1 ),
            'assignment_date' => (isset( $_POST['assignment-date']) ? sanitize_text_field( $_POST['assignment-date'] ) : null ),
            'assignment_short_name' => isset( $_POST['assignment-short-name']) ? sanitize_text_field( $_POST['assignment-short-name'] ) : null,
            'checkedin' => (isset( $_POST['checkedin']) ? intval( $_POST['checkedin'] ) : null),
        );

        if ( ! $this->model->setAssignment( $data ) ) {

            dbg("e20rAssignment::saveAssignment_callback() - Error saving assignment information...");
            wp_send_json_error();
            wp_die();
        }

        wp_send_json_success();
        wp_die();
    }
    */
    /*
    public function getAssignment( $shortName ) {

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
    public function getAllAssignments() {

        return $this->model->loadAllSettings();

    }
    public function getAssignmentSettings( $id ) {

        return $this->model->loadSettings( $id );
    }
*/
    public function editor_metabox_setup( $object, $box ) {

        add_meta_box('e20r-tracker-assignment-settings', __('Assignment Settings', 'e20rtracker'), array( &$this, "addMeta_Settings" ), 'e20r_assignments', 'normal', 'high');

    }

    public function getPeers( $assignmentId = null ) {

        if ( is_null( $assignmentId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $assignmentId = $post->post_parent;
        }

        $assignments = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $assignmentId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $assignmentList = array(
            'pages' => $assignments->posts,
        );

        foreach ( $assignmentList->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $assignments->posts[$k-1] ) ) {

                    $assignmentList['prev'] = $assignments->posts[ $k - 1 ];
                }

                if( isset( $assignments->posts[$k+1] ) ) {

                    $assignmentList['next'] = $assignments->posts[ $k + 1 ];
                }
            }
        }

        return $assignmentList;
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
            'post_status' => apply_filters( 'e20r_tracker_assignment_status', $def_status ),
            'posts_per_page' => -1,
        );

        wp_reset_query();

        //  Fetch Programs
        $assignments = get_posts( $query );

        if ( empty( $assignments ) ) {

            dbg( "e20rAssignment::addMeta_Settings() - No programs found!" );
        }

        dbg("e20rAssignment::addMeta_Settings() - Loading settings metabox for assignment page {$post->ID}");
        $settings = $this->model->loadSettings( $post->ID );

        echo $this->view->viewSettingsBox( $settings , $assignments );

    }
/*
    public function saveSettings( $post_id ) {

        $post = get_post( $post_id );

        setup_postdata( $post );

        $this->model->set( 'question', the_title() );
        $this->model->set( 'descr', the_excerpt() );

        parent::saveSettings( $post_id );
    }
*/
}