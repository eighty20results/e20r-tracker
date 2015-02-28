<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rExercise {

    private $exercise = array();
    public $model = null;
    public $view = null;

    public function e20rExercise() {

        dbg("e20rProgram:: - Initializing Exercise class");

        $this->model = new e20rExerciseModel();
        $this->view = new e20rExerciseView();
    }


    public function getExercise( $shortName ) {

        if ( ! isset( $this->model ) ) {
            $this->init();
        }

        $pgmList = $this->model->loadAllExercises( 'any' );

        foreach ($pgmList as $pgm ) {
            if ( $pgm->shortname == $shortName ) {
                unset($pgmList);
                return $pgm;
            }
        }

        unset($pgmList);
        return false; // Returns false if the program isn't found.
    }

    public function getAllExercises() {

        return $this->model->loadAllData();

    }
    public function getExerciseSettings( $id ) {

        return $this->model->loadSettings( $id );
    }

    public function editor_metabox_setup( $object, $box ) {

        add_meta_box('e20r-tracker-exercise-settings', __('Exercise Settings', 'e20rtracker'), array( &$this, "addMeta_ExerciseSettings" ), 'e20r_exercises', 'normal', 'high');

    }

    public function saveSettings( $post_id ) {

        global $post;

        dbg("e20rExercise::saveSettings() - Saving Program Settings to DB");

        if ( (! isset( $post->post_type ) ) || ( $post->post_type != 'e20r_exercises' ) ) {
            return $post_id;
        }

        if ( empty( $post_id ) ) {
            dbg("e20rExercise::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }

        dbg("e20rExercise::saveSettings()  - Saving metadata for the post_type(s)");

        $settings = new stdClass();
        $settings->id = $post_id;
        $settings->reps = isset( $_POST['e20r-exercise-reps'] ) ? intval( $_POST['e20r-exercise-reps'] ) : null;
        $settings->rest = isset( $_POST['e20r-exercise-rest'] ) ? intval( $_POST['e20r-exercise-rest'] ) : null;

        $this->model->saveSettings( $settings );
    }

    public function getPeers( $exerciseId = null ) {

        if ( is_null( $exerciseId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $exerciseId = $post->post_parent;
        }

        $exercises = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $exerciseId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $exerciseList = array(
            'pages' => $exercises->posts,
        );

        foreach ( $exerciseList->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $exercises->posts[$k-1] ) ) {

                    $exerciseList['prev'] = $exercises->posts[ $k - 1 ];
                }

                if( isset( $exercises->posts[$k+1] ) ) {

                    $exerciseList['next'] = $exercises->posts[ $k + 1 ];
                }
            }
        }

        return $exerciseList;
    }

    public function addMeta_ExerciseSettings() {

        global $post;

        dbg("e20rExercise::addMeta_ExerciseSettings() - Loading settings metabox for exercise page");
        echo $this->view->viewSettingsBox( $this->model->loadExerciseData( $post->ID ) );

    }

    public function changeSetParentType( $args, $post ) {

        if ( 'e20r_exercises' == $post->post_type ) {

            dbg('e20rExercise::changeSetParentType() - linking ourselves to Workouts only');
            $args['post_type'] = 'e20r_workout';
        }

        return $args;
    }
} 