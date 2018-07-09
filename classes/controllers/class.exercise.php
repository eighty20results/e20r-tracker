<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

namespace E20R\Tracker\Controllers;

use E20R\Tracker\Models\Exercise_Model;
use E20R\Tracker\Views\Exercise_View;
use E20R\Tracker\Models\Workout_Model;
use E20R\Utilities\Utilities;

class Exercise extends Settings {
	
	const post_type = 'e20r_exercise';
	
	/**
	 * @var null|Exercise
	 */
	private static $instance = null;
	
	/**
	 * @var Exercise_Model|null
	 */
	protected $model = null;
	
	/**
	 * @var Exercise_View|null
	 */
	protected $view = null;
	
	public function __construct() {
		
		Utilities::get_instance()->log( "Initializing Exercise class" );
		
		$this->model = new Exercise_Model();
		$this->view  = new Exercise_View();
		
		parent::__construct( 'exercise', self::post_type, $this->model, $this->view );
	}
	
	/**
	 * @return Exercise
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function empty_exercise() {
		
		return $this->model->defaultSettings();
	}
	
	public function getExerciseType( $typeId ) {
		
		$typeStr = $this->model->get_activity_type( $typeId );
		
		Utilities::get_instance()->log( "Exercise::getExerciseType( {$typeId} ) - Returning type of: {$typeStr}" );
		
		return $typeStr;
	}
	
	/**
	 * @param $shortname string - The unique exercise shortname
	 *
	 * @return bool|\stdClass - False if not found, Exercise object if found.
	 */
	public function getExercise( $shortname ) {
		
		$ex = $this->model->findExercise( 'shortname', $shortname );
		
		return $ex; // Returns false if the exercise isn't found
	}
	
	public function set_currentExercise( $id = - 1 ) {
		
		global $currentExercise;
		
		if ( ! isset( $currentExercise->id ) || ( $currentExercise->id !== $id ) ) {
			
			$arr = $this->model->findExercise( 'id', $id );
			
			if ( is_array( $arr ) ) {
				
				if ( count( $arr ) == 1 ) {
					Utilities::get_instance()->log( "Loading new exercise definition." );
					$currentExercise = $arr[0];
				} else {
					Utilities::get_instance()->log( "Error: Incorrect number of exercises returned! " );
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
	
	public function print_exercise( $show = true, $display = 'old', $printing = false ) {
		
		switch ( $display ) {
			
			case 'new':
				
				$html = $this->view->view_exercise_as_columns( $show, $printing );
				break;
			
			default:
				$html = $this->view->view_exercise_as_row( $show, $printing );
		}
		
		return $html;
	}
	
	public function editor_metabox_setup( $post ) {
		
		add_meta_box( 'e20r-tracker-exercise-settings', __( 'Exercise Settings', 'e20r-tracker' ), array(
			&$this,
			"addMeta_Settings",
		), Exercise::post_type, 'normal', 'high' );
		
	}
	
	/**
	 * Process the Exercise shortcode
	 *
	 * @param null|array $attributes
	 *
	 * @return string|null
	 */
	public function shortcode_exercise( $attributes = null ) {
		
		Utilities::get_instance()->log( "Loading shortcode data for the exercise." );
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
		}
		
		$config = new \stdClass();
		
		$tmp = shortcode_atts( array(
			'id'        => null,
			'display'   => 'row',
			'shortcode' => null,
		), $attributes );
		
		foreach ( $tmp as $key => $val ) {
			
			$config->{$key} = $val;
		}
		
		if ( isset( $config->id ) && ( ! is_null( $config->id ) ) ) {
			Utilities::get_instance()->log( "Using ID to locate exercise: {$config->id}" );
			$exInfo = $this->model->findExercise( 'id', $config->id );
		}
		
		if ( isset( $config->shortcode ) && ( ! is_null( $config->shortcode ) ) ) {
			Utilities::get_instance()->log( "Using shortcode to locate exercise: {$config->shortcode}" );
			$exInfo = $this->model->findExercise( 'shortcode', $config->shortcode );
		}
		
		if ( empty( $exInfo ) ) {
			return __( 'The administrator did not indicate which exercise to show', 'e20r-tracker' );
		}
		
		foreach ( $exInfo as $ex ) {
			
			if ( isset( $ex->id ) && ( ! is_null( $ex->id ) ) ) {
				
				Utilities::get_instance()->log( "Loading exercise info: {$ex->id}" );
				$this->init( $ex->id );
				
				if ( $config->display != 'new' ) {
					
					Utilities::get_instance()->log( "Printing with old layout" );
					return $this->view->view_exercise_as_row();
				} else {
					Utilities::get_instance()->log( "Printing with NEW layout" );
					return $this->view->view_exercise_as_columns();
					
				}
				
			} else {
				Utilities::get_instance()->log( "No exercise found to display!" );
				return null;
			}
		}
		
		return null;
	}
	
	public function addMeta_Settings() {
		
		global $post;
		
		remove_meta_box( 'postexcerpt', Exercise::post_type, 'side' );
		remove_meta_box( 'wpseo_meta', Exercise::post_type, 'side' );
		
		add_meta_box( 'postexcerpt', __( 'Exercise Summary' ), 'post_excerpt_meta_box', Exercise::post_type, 'normal', 'high' );
		
		Utilities::get_instance()->log( "Loading settings metabox for exercise page" );
		$data = $this->model->find( 'id', $post->ID );
		$this->view->viewSettingsBox( $data[0], $this->model->get_activity_types() );
		
	}
	
	public function changeSetParentType( $args, $post ) {
		
		if ( Exercise::post_type == $post->post_type ) {
			
			Utilities::get_instance()->log( 'linking ourselves to Workouts only' );
			$args['post_type'] = Workout_Model::post_type;
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
		
		Utilities::get_instance()->log( "ID: {$post_ID}" );
		
		if ( $colName == 'ex_shortcode' ) {
			
			$shortcode = $this->model->getSetting( $post_ID, 'shortcode' );
			
			Utilities::get_instance()->log( "Used in shortcode: {$shortcode}" );
			
			if ( $shortcode ) {
				
				echo $shortcode;
			}
		}
	}
} 