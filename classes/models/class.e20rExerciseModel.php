<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rExerciseModel extends e20rSettingsModel {

    protected $settings;

	private $exercise_types = null;

	private $type_map = array(
		'reps' => 1,
		'time' => 2,
		'amrap'=> 3
	);

	public function e20rExerciseModel() {

		parent::__construct( 'exercise', 'e20r_exercises');

		$this->exercise_types = array(

			1 => __('Reps', "e20rtracker"),
			2 => __('Time', 'e20rtracker'),
			3 => __('AMRAP', 'e20rtracker'),
		);
	}

    public function init( $exerciseId = null ) {

        if ( $exerciseId === null ) {

            global $post;

            if ( isset( $post->post_type) && ( $post->post_type == 'e20r_exercises' ) ) {

                $exerciseId = $post->ID;
            }
        }

        $this->settings = $this->loadSettings( $exerciseId );
    }

    public function defaultSettings() {

        $settings = parent::defaultSettings();

	    $settings->title = null; // get_the_title();
	    $settings->descr = null; // $post->post_content;
	    $settings->image = null; // featured image

	    $settings->type = 0;
	    $settings->reps = null;
        $settings->rest = null;
	    $settings->shortcode = null;
	    $settings->video_link = null;

        return $settings;
    }

	public function get_activity_type( $typeId ) {

		return $this->exercise_types[$typeId];
	}

	public function get_activity_types() {

		return $this->exercise_types;
	}

	public function findExercise( $type = 'id', $value ) {

		global $e20rProgram;
		global $current_user;

		// NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'. Default value is 'CHAR'.
		switch ($type) {

			case 'id':
			case 'reps':
			case 'rest':
				$dType = 'numeric';
				break;

			default:
				$dType = 'char';
		}

		return parent::find( $type, $value, $dType );
	}

	public function loadSettings( $id = null ) {

		global $post;
		global $currentExercise;

		if ( ! empty( $currentExercise ) && ( $currentExercise->id == $id ) ) {

			return $currentExercise;
		}

		if ( $id == 0 ) {

			$this->settings              = $this->defaultSettings( $id );
			$this->settings->id          = $id;

		} else {

			$savePost = $post;

			$this->settings = parent::loadSettings( $id );

			$post = get_post( $id );
			setup_postdata( $post );

			if ( ! empty( $post->post_title ) ) {

				$this->settings->descr       = $post->post_content;
				$this->settings->title       = $post->post_title;
				$this->settings->id          = $id;
				$this->settings->image       = get_post_thumbnail_id( $id ); // Returns the <img> element
			}

			wp_reset_postdata();
			$post = $savePost;
		}

		$currentExercise = $this->settings;
		return $this->settings;

	}
    /**
     * Returns an array of all programs merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return program data for.
     * @return mixed - Array of program objects
     */
    public function loadAllData( $statuses = 'any' ) {

        $query = array(
            'posts_per_page' => -1,
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

            $exercise_list[$key] = $settings;
        }

		wp_reset_postdata();

        return $exercise_list;
    }

    public function loadData( $id = null, $statuses = 'any' ) {

        if ( $id == null ) {
            dbg("Error: Unable to load exercise data when no ID is specified!");
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

            $exercise_list[$key] = $settings;
        }

        wp_reset_postdata();

        return $settings;
    }

	public function getSettings() {

		return $this->settings;
	}

	/**
     * Save the Exercise Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific program.
     *
     * @return bool - True if successful at updating program settings
     */
    public function saveSettings( $settings ) {

	    $defaults = self::defaultSettings();

	    dbg("e20rExerciseModel::saveSettings() - Saving exercise Metadata: " . print_r( $settings, true ) );

	    $error = false;

	    $exerciseId = $settings->id;

	    foreach ( $defaults as $key => $value ) {

		    if ( in_array( $key, array( 'id', 'title', 'descr', 'image' ) ) ) {
			    continue;
		    }

		    if ( false === parent::settings( $exerciseId, 'update', $key, $settings->{$key} ) ) {

			    dbg("e20rExercise::saveSettings() - ERROR saving settings for exercise with ID: {$exerciseId}");

			    $error = true;
		    }
	    }

	    return ( !$error ) ;

    }
}