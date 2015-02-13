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

    public function loadAssignment( $assignmentId ) {

        $settings = $this->model->loadSettings( $assignmentId );

        dbg("e20rAssignment::init() - Loaded settings for {$assignmentId}");

        return $settings;
    }

    public function findAssignmentItemId( $articleId ) {

        global $e20rArticle;
    }

    public function configureArticleMetabox( $articleId ) {

        dbg("e20rAssignment::configureArticleMetabox() - For article {$articleId}");

        $assignments = $this->model->getArticleAssignments( $articleId );
        $answerDefs = $this->model->getAnswerDescriptions();

        if ( count( $assignments ) < 1 ) {

            dbg("e20rAssignment::configureArticleMetabox() - No assignments defined. Using default");

            $assignments = array();

            $assignments[0] = $this->model->defaultSettings();
            $assignments[0]->order_num = 1;
            $assignments[0]->question = __( "Lesson complete (default)", 'e20rtracker' );
            $assignments[0]->field_type = 0; // "Lesson complete" button.
        }

        ob_start();
        ?>
        <div id="e20r-assignment-settings">
            <?php echo $this->view->viewArticle_Assignments( $articleId, $assignments, $answerDefs ); ?>
        </div>
    <?php
        $html = ob_get_clean();
        ob_end_flush();

        return $html;
    }

    public function addMeta_answers() {

        global $post;

        dbg("e20rAssignment::addMeta_answers() - Loading the article answers metabox");

        echo $this->configureArticleMetabox( $post->ID );
    }

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

    public function getAllAssignments() {

        $assignments =  $this->model->loadAllAssignments();

        if ( count( $assignments) >= 1 ) {
            dbg("e20rAssignment::getAllAssignments() - Process the assignments?");
        }

        return $assignments;
    }
/*
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

        dbg("e20rAssignment::addMeta_Settings() - Loading settings metabox for assignment page {$post->ID}");
        $assignmentData = $this->model->loadSettings( $post->ID );

        echo $this->view->viewSettingsBox( $assignmentData, $this->model->getAnswerDescriptions() );
    }

    public function saveSettings( $post_id, $settings = null ) {

        global $e20rTracker;
        global $post;

        $savePost = $post;

        dbg("e20rAssignment::saveSettings() - Saving e20rAssignment settings to DB: {$post->post_type}");


        if ( $settings === null ) {

            dbg( "e20rAssignment::saveSettings()  - Saving metadata from edit.php page, related to the e20rAssignment post_type" );

            if ( $post->post_type != 'e20r_assignments' ) {
                dbg("e20rAssignment::saveSettings() - Incorrect type! {$post->post_type}");
                return $post_id;
            }

            if ( empty( $post_id ) ) {
                dbg("e20re20rAssignment::saveSettings() - No post ID supplied");
                return false;
            }

            if ( wp_is_post_revision( $post_id ) ) {
                return $post_id;
            }

            if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
                return $post_id;
            }

            $this->model->init( $post_id );

            $settings = $this->model->loadSettings( $post_id );
            $defaults = $this->model->defaultSettings();

            if ( ! $settings ) {

                $settings = $defaults;
            }

            foreach ( $settings as $field => $setting ) {

                $tmp = isset( $_POST["e20r-assignment-{$field}"] ) ? $e20rTracker->sanitize( $_POST["e20r-assignment-{$field}"] ) : null;

                dbg( "e20rAssignment::saveSettings() - Page data : {$field} -> {$tmp}" );

                if ( is_null( $tmp ) ) {

                    $tmp = $defaults->{$field};

                }

                $settings->{$field} = $tmp;
            }

            // Add post ID (checkin ID)
            $settings->id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;

            dbg( "e20rAssignment::saveSettings() - Saving: " . print_r( $settings, true ) );

            if ( ! $this->model->saveSettings( $settings ) ) {

                dbg( "e20rAssignment::saveSettings() - Error saving settings!" );
            }

        }
        else {

            dbg("e20rAssignment::saveSettings() - Received settings from calling function.");
            dbg($settings);

            if ( ! $this->model->saveSettings( $settings ) ) {

                dbg( "e20rAssignment::saveSettings() - Error saving settings!" );
            }

            $post = get_post( $post_id );

            setup_postdata( $post );

            $this->model->set( 'question', the_title() );
            $this->model->set( 'descr', the_excerpt() );

        }

        $post = $savePost;
    }

}