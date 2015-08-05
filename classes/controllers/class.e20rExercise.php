<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rExercise extends e20rSettings {

    private $exercise = array();
    protected $model = null;
    protected $view = null;

    public function e20rExercise() {

        dbg("e20rProgram:: - Initializing Exercise class");

        $this->model = new e20rExerciseModel();
        $this->view = new e20rExerciseView();

	    parent::__construct( 'exercise', 'e20r_exercises', $this->model, $this->view );
    }


	public function empty_exercise() {

		return $this->model->defaultSettings();
	}

	public function getExerciseType( $typeId ) {

		$typeStr = $this->model->get_activity_type( $typeId );

		dbg("e20rExercise::getExerciseType( {$typeId} ) - Returning type of: {$typeStr}" );

		return $typeStr;
	}
	/**
	 * @param $shortname string - The unique exercise shortname
	 *
	 * @return bool|stdClass - False if not found, e20rExercise object if found.
	 */
    public function getExercise( $shortname ) {

        $ex = $this->model->findExercise( 'shortname', $shortname );

        return $ex; // Returns false if the exercise isn't found
    }

	public function set_currentExercise( $id = -1 ) {

		global $currentExercise;

		if ( !isset( $currentExercise->id) || ( $currentExercise->id !== $id ) ) {

			$arr = $this->model->findExercise( 'id', $id );

			if ( is_array( $arr ) ) {

				if ( count( $arr ) == 1 ) {
					dbg("e20rExercise::set_currentExercise() - Loading new exercise definition.");
					$currentExercise = $arr[0];
				} else {
					dbg( "e20rExercise::set_currentExercise() - Error: Incorrect number of exercises returned! " );
					$currentExercise = null;
				}
			}
		}
	}

    public function getAllExercises() {

        return $this->model->loadAllData();

    }

    public function getExerciseSettings( $id ) {

        return $this->model->loadSettings( $id );
    }

	public function print_exercise( $show = true ) {

		return $this->view->printExercise( $show );

	}
    public function editor_metabox_setup( $post ) {

        add_meta_box('e20r-tracker-exercise-settings', __('Exercise Settings', 'e20rtracker'), array( &$this, "addMeta_Settings" ), 'e20r_exercises', 'normal', 'high');

    }

	public function shortcode_exercise( $attributes = null ) {

		dbg("e20rExercise::shortcode_exercise() - Loading shortcode data for the exercise.");

		if ( ! is_user_logged_in() ) {

			auth_redirect();
		}

		global $current_user;
		global $post;

		$config = new stdClass();

		$tmp = shortcode_atts( array(
			'id' => null,
			'shortcode' => null,
		), $attributes );

		foreach ( $tmp as $key => $val ) {

			$config->{$key} = $val;
		}

		if (isset( $config->id ) && ( !is_null( $config->id ) ) ) {
			dbg("e20rExercise::shortcode_exercise() - Using ID to locate exercise: {$config->id}");
			$exInfo = $this->model->findExercise( 'id', $config->id );
		}

		if (isset( $config->shortcode ) && ( !is_null( $config->shortcode ) ) ) {
			dbg("e20rExercise::shortcode_exercise() - Using shortcode to locate exercise: {$config->shortcode}");
			$exInfo = $this->model->findExercise( 'shortcode', $config->shortcode );
		}

        if ( empty( $exInfo ) ) {
            return __('The administrator did not indicate which exercise to show', 'e20rtracker');
        }

		foreach( $exInfo as $ex ) {

			if ( isset( $ex->id ) && ( !is_null( $ex->id ) ) ) {

				dbg("e20rExercise::shortcode_exercise() - Loading exercise info: {$ex->id}");
				$this->init( $ex->id );
				echo $this->view->printExercise();
			}
			else {
				dbg("e20rExercise::shortcode_exercise() - No exercise found to display!");
				echo '';
			}
		}
	}

	/*
    public function saveSettings( $post_id ) {

        global $post;
	    global $e20rTracker;

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

        dbg("e20rExercise::saveSettings()  - Saving metadata");

        $settings = new stdClass();
        $settings->id = $post_id;
        $settings->reps = isset( $_POST['e20r-exercise-reps'] ) ? $e20rTracker->sanitize( $_POST['e20r-exercise-reps'] ) : null;
        $settings->rest = isset( $_POST['e20r-exercise-rest'] ) ? $e20rTracker->sanitize( $_POST['e20r-exercise-rest'] ) : null;
	    $settings->shortcode = isset( $_POST['e20r-exercise-shortcode'] ) ? $e20rTracker->sanitize( $_POST['e20r-exercise-shortcode'] ) : null;

        $this->model->saveSettings( $settings );
    }
*/
    public function addMeta_Settings() {

        global $post;

	    remove_meta_box( 'postexcerpt', 'e20r_exercises', 'side' );
	    remove_meta_box( 'wpseo_meta', 'e20r_exercises', 'side' );

	    add_meta_box('postexcerpt', __('Exercise Summary'), 'post_excerpt_meta_box', 'e20r_exercises', 'normal', 'high');

	    dbg("e20rExercise::addMeta_Settings() - Loading settings metabox for exercise page");
		$data = $this->model->find( 'id', $post->ID );
        echo $this->view->viewSettingsBox( $data[0], $this->model->get_activity_types() );

    }

	public function changeSetParentType( $args, $post ) {

        if ( 'e20r_exercises' == $post->post_type ) {

            dbg('e20rExercise::changeSetParentType() - linking ourselves to Workouts only');
            $args['post_type'] = 'e20r_workout';
        }

        return $args;
    }

	public function embed_default() {

		return array( 'width' => 0, 'height' => 0 );
	}

	public function col_head( $defaults ) {

		$defaults['ex_shortcode'] = 'Identifier';
		return $defaults;
	}

	public function col_content( $colName, $post_ID ) {

		global $e20rExercise;
		global $currentExercise;

		dbg( "e20rExercise::col_content() - ID: {$post_ID}" );

		if ( $colName == 'ex_shortcode' ) {

			$shortcode = $this->model->getSetting( $post_ID, 'shortcode' );

			dbg( "e20rExercise::col_content() - Used in shortcode: {$shortcode}" );

			if ($shortcode ) {

				echo $shortcode;
			}
		}
	}
} 