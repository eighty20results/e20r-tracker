<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rExerciseModel {

    private $settings;

    public function e20rExerciseModel( $exerciseId = null ) {

        if ( $exerciseId === null ) {

            global $post;

            if ( isset( $post->post_type) && ( $post->post_type == 'e20r_exercises' ) ) {

                $exerciseId = $post->ID;
            }
        }


        $this->settings = $this->loadSettings( $exerciseId );
    }

    private function defaultSettings() {

        $settings = new stdClass();
        $settings->reps = null;
        $settings->rest = null;

        return $settings;
    }


    /**
     * Returns an array of all programs merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return program data for.
     * @return mixed - Array of program objects
     */
    public function loadAllData( $statuses = 'any' ) {

        $query = array(
            'post_type' => 'e20r_exercises',
            'post_status' => $statuses,
        );

        wp_reset_query();

        /* Fetch all Sequence posts */
        $exercise_list = get_posts( $query );

        if ( empty( $exercise_list ) ) {

            return false;
        }

        dbg("e20rExerciseModel::loadAllExerciseData() - Loading exercise settings for " . count( $exercise_list ) . ' settings');

        foreach( $exercise_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $exercise_list[$key] = $loaded_settings;
        }

        return $exercise_list;
    }

    public function loadExerciseData( $id, $statuses = 'any' ) {

        if ( $id == null ) {
            dbg("Error: Unable to load exercise data. No ID specified!");
            return false;
        }

        $query = array(
            'post_type' => 'e20r_exercises',
            'post_status' => $statuses,
            'p' => $id,
        );

        wp_reset_query();

        /* Fetch Exercises */
        $exercise_list = get_posts( $query );

        if ( empty( $exercise_list ) ) {
            dbg("e20rExerciseModel::loadExerciseData() - No exercises found!");
            return false;
        }

        foreach( $exercise_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $exercise_list[$key] = $loaded_settings;
        }


        return $exercise_list[0];
    }

    /**
     * Save the Exercise Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific program.
     *
     * @return bool - True if successful at updating program settings
     */
    public function saveSettings( $settings ) {

        $exerciseId = $settings->id;
        unset($settings->id);

        $settings = (object) array_replace( (array)$this->defaultSettings(), (array)$settings );

        dbg("e20rExerciseModel::saveSettings() - Saving exercise Metadata: " . print_r( $settings, true ) );

        if ( false === update_post_meta( $exerciseId, 'e20r-exercise-settings', $settings ) ) {

            dbg("e20rExercise::saveSettings() - ERROR saving settings for exercise with ID: {$exerciseId}");
            return false;
        }

        return true;
    }

    /**
     * Load the Exercise Settings from the metadata table.
     *
     * @param $id (int) - The ID of the program to load settings for
     *
     * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
     */
    public function loadSettings( $id ) {

        if ( false === ( $settings = get_post_meta( $id, 'e20r-exercise-settings', true ) ) ) {

            dbg("e20rExerciseModel::loadSettings() - ERROR loading settings for exercise with ID: {$id}");
            $settings = $this->defaultSettings();
        }

        $settings = (object) array_replace( (array)$this->defaultSettings(), (array)$settings );

        return $settings;
    }

}