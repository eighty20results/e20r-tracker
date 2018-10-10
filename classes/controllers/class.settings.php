<?php

namespace E20R\Tracker\Controllers;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Models\ Article_Model;
use E20R\Tracker\Models\Assignment_Model;
use E20R\Tracker\Models\Action_Model;
use E20R\Tracker\Models\Exercise_Model;
use E20R\Tracker\Models\Measurement_Model;
use E20R\Tracker\Models\Program_Model;
use E20R\Tracker\Models\Workout_Model;

use E20R\Tracker\Models\Client_Model;
use E20R\Tracker\Views\Action_View;
use E20R\Tracker\Views\Exercise_View;
use E20R\Tracker\Views\Measurement_View;
use E20R\Tracker\Views\Program_View;
use E20R\Tracker\Views\Assignment_View;
use E20R\Tracker\Views\Article_View;
use E20R\Tracker\Views\Workout_View;

use E20R\Sequences\Sequence AS Sequence;
use E20R\Sequences\Data\Model;
use E20R\Utilities\Utilities;

class Settings {
	
	private static $instance = null;
	/**
	 * @var null|Program_Model|Client_Model|Exercise_Model|Measurement_Model|Workout_Model|Assignment_Model| Article_Model|Action_Model
	 */
	protected $model;
	/**
	 * @var null|Program_View|Action_View|Exercise_View|Measurement_View|Workout_View|Assignment_View|Article_View|Action_View
	 */
	protected $view;
	
	protected $cpt_slug;
	protected $type;
	
	/**
	 * Settings constructor.
	 *
	 * @param null|string                                                                                                  $type
	 * @param null|string                                                                                                  $cpt_slug
	 * @param null|Program_Model|Client_Model|Exercise_Model|Measurement_Model|Workout_Model|Assignment_Model| Article_Model|Action_Model $model
	 * @param null|Program_View|Action_View|Exercise_View|Measurement_View|Workout_View|Assignment_View|Article_View|Action_View         $view
	 */
	protected function __construct( $type = null, $cpt_slug = null, $model = null, $view = null ) {
		
		$this->cpt_slug = $cpt_slug;
		$this->type     = $type;
		
		$this->model = $model;
		$this->view  = $view;
	}
	
	/**
	 * Return the Model class for the specific Settings child class
	 *
	 * @return Action_Model|Article_Model|Assignment_Model|Client_Model|Exercise_Model|Measurement_Model|Program_Model|Workout_Model|null
	 */
	public function getModelClass() {
		return $this->model;
	}
	
	/**
	 * Return the View class for the specific Settings child class
	 *
	 * @return Action_View|Article_View|Assignment_View|Exercise_View|Measurement_View|Program_View|Workout_View|null
	 */
	public function getViewClass() {
		return $this->view;
	}
	
	/**
	 * Get the instance of this class
	 *
	 * @return Settings
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Search for settings/a CPT
	 *
	 * @param string $key
	 * @param string $value
	 * @param int    $programId
	 * @param string $comp
	 * @param string $order
	 * @param string $dataType
	 *
	 * @return array|bool|mixed
	 */
	public function find( $key, $value, $programId = - 1, $comp = '=', $order = 'DESC', $dataType = 'numeric' ) {
		
		return $this->model->find( $key, $value, $programId, $comp, $order, $dataType );
	}
	
	/**
	 * Find a CPT based on the stub
	 *
	 * @param string $shortName The CPT stub
	 *
	 * @return bool
	 */
	public function findByName( $shortName ) {
		
		$list = $this->model->loadAllSettings( 'any' );
		$key  = false;
		
		if ( empty( $list ) ) {
			return false;
		}
		
		foreach ( $list as $settings ) {
			
			if ( isset( $settings->short_name ) ) {
				$key = 'short_name';
			}
			
			if ( isset( $settings->program_shortname ) ) {
				$key = 'program_shortname';
			}
			
			if ( ! $key ) {
				return false;
			}
			
			if ( $settings->{$key} == $shortName ) {
				unset( $list );
				
				return $settings;
			}
		}
		
		unset( $list );
		
		return false; // Returns false if the requested settings aren't found
	}
	
	/**
	 * Return all settings
	 *
	 * @return mixed
	 */
	public function getAll() {
		
		return $this->model->loadAllSettings();
		
	}
	
	/**
	 * Return the settings for the specified ID
	 *
	 * @param int $id
	 *
	 * @return mixed|\stdClass
	 */
	public function getSettings( $id ) {
		
		return $this->model->loadSettings( $id );
	}
	
	/**
	 * Load the Post Editor metabox for the Tracker Custom Posts
	 *
	 * @param \WP_Post $post
	 */
	public function editor_metabox_setup( $post ) {
		
		$title = ucfirst( $this->type ) . ' Settings';
		
		add_meta_box( "e20r-tracker-{$this->type}-settings", __( $title, 'e20r-tracker' ), array(
			&$this,
			"addMeta_Settings",
		), $this->cpt_slug, 'normal', 'high' );
		
	}
	
	/**
	 * Return the slug for the current Tracker Post Type
	 *
	 * @return null|string
	 */
	public function get_cpt_slug() {
		
		return $this->cpt_slug;
	}
	
	/**
	 * Return the type of Tracker Post
	 *
	 * @return null|string
	 */
	public function get_cpt_type() {
		
		return $this->type;
	}
	
	/**
	 * Load the default settings for (any) supported Tracker Post Types
	 *
	 * @return mixed|\stdClass
	 */
	public function get_defaults() {
		
		return $this->model->defaultSettings();
	}
	
	/**
	 * Set CPT specific meta box(es) and remove boxes we do not need/want
	 */
	public function addMeta_Settings() {
		
		global $post;
		
		$this->init( $post->ID );
		
		remove_meta_box( 'postexcerpt', $this->cpt_slug, 'side' );
		remove_meta_box( 'wpseo_meta', $this->cpt_slug, 'side' );
		add_meta_box( 'postexcerpt', __( ucfirst( $this->type ) . ' Summary' ), 'post_excerpt_meta_box', $this->cpt_slug, 'normal', 'high' );
		
		// Utilities::get_instance()->log("Settings::addMeta_Settings() - Loading metabox for {$this->type} settings");
		echo $this->view->viewSettingsBox( $this->model->loadSettings( $post->ID ), $this->loadDripFeed( 'all' ) );
	}
	
	/**
	 * Load the Settings for the specified CPT ID
	 *
	 * @param null|int $postId
	 *
	 * @return bool|mixed|\stdClass
	 */
	protected function init( $postId = null ) {
		
		if ( ! $postId ) {
			return false;
		}
		
		Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "::init() - Loading basic {$this->type} settings for id: {$postId}" );
		
		$settingsId = get_post_meta( $postId, "_e20r-{$this->type}-id", true );
		
		if ( $settingsId == false ) {
			
			$settingsId = $postId;
		}
		
		Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "::init() - Loaded {$this->type} id: {$settingsId}" );
		
		if ( false === ( $settings = $this->model->loadSettings( $settingsId ) ) ) {
			
			Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "::init() - FAILED to load settings for {$settingsId}" );
			
			return false;
		}
		
		$settings->id = $settingsId;
		
		return $settings;
	}
	
	/**
	 * Return the specified sequence ID (or all configured sequences)
	 *
	 * @param int $feedId
	 *
	 * @return mixed
	 */
	public function loadDripFeed( $feedId ) {
		
		if ( $feedId == 'all' ) {
			$id = null;
		} else {
			$id = $feedId;
		}
		
		if ( class_exists( '\E20R\Sequences\Sequence\Sequence_Controller' ) ) {
			return \E20R\Sequences\Sequence\Sequence_Controller::all_sequences( 'publish' );
		}
		
		if ( class_exists( 'E20R\\Sequences\\Data\\Model' ) ) {
			return Model::all_sequences( 'publish' );
		}
		
		return array();
	}
	
	/**
	 * Save the settings for the specified post ID
	 *
	 * @param int $post_id
	 *
	 * @return bool|int
	 */
	public function saveSettings( $post_id ) {
		
		global $post;
		
		$Tracker = Tracker::getInstance();
		$utils = Utilities::get_instance();
		
		if ( ( ! isset( $post->post_type ) ) || ( $post->post_type != $this->model->get_slug() ) ) {
			$utils->log( ucfirst( $this->type ) . ": Incorrect post type for " . $this->model->get_slug() );
			
			return $post_id;
		}
		
		if ( empty( $post_id ) ) {
			$utils->log( ucfirst( $this->type ) . ":  No post ID supplied" );
			
			return false;
		}
		
		if ( wp_is_post_revision( $post_id ) ) {
			return $post_id;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		$utils->log( ucfirst( $this->type ) . ": Saving metadata for the {$this->type} post type" );
		$this->model->init( $post_id );
		
		$settings = $this->model->loadSettings( $post_id );
		$defaults = $this->model->defaultSettings();
		
		if ( ! $settings ) {
			
			$utils->log( ucfirst( $this->type ) . ": No previous settings found. Using defaults!" );
			$settings = $defaults;
		}
		
		foreach ( $settings as $field => $setting ) {
			
			$tmp = $utils->get_variable("e20r-{$this->type}-{$field}", null );
			
			$utils->log( ucfirst( $this->type ) . ": Being saved : {$field} -> " );
			$utils->log( print_r( $tmp, true ) );
			
			if ( isset( $defaults->{$field} ) && empty( $tmp ) ) {
				
				$tmp = $defaults->{$field};
			}
			
			$settings->{$field} = $tmp;
		}
		
		// Add post ID (checkin ID)
		$settings->id = $utils->get_variable( 'post_ID', null );
		
		$utils->log( ucfirst( $this->type ) . ": Saving: " . print_r( $settings, true ) );
		
		if ( ! $this->model->saveSettings( $settings ) ) {
			
			$utils->log( ucfirst( $this->type ) . ": Error saving settings!" );
			
			return $post_id;
		}
		
		return $post_id;
	}
	
	/**
	 * Add programs
	 *
	 * @param int   $gid
	 * @param int[] $programIds
	 *
	 * @return bool
	 */
	public function addPrograms( $gid, $programIds ) {
		
		Utilities::get_instance()->log( ucfirst( $this->type ) . "::addProgram() - Adding " . count( $programIds ) . " programs to $this->type {$gid}" );
		
		$settings = $this->model->loadSettings( $gid );
		
		// Clean up settings with empty program id values.
		foreach ( $settings->program_ids as $k => $value ) {
			
			if ( empty( $value ) || ( 0 == $value ) ) {
				unset( $settings->program_ids[ $k ] );
			}
		}
		
		foreach ( $programIds as $pId ) {
			
			if ( ! in_array( $pId, $settings->program_ids ) ) {
				
				Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "::addProgram() - Adding program {$pId} to {$this->type} {$gid}" );
				$settings->program_ids[] = $pId;
			}
		}
		
		return $this->model->saveSettings( $settings );
	}
	
	/**
	 * Load the peers of the setting(s) (Programs, Articles, Assignements, etc)
	 *
	 * @param null|int $id
	 *
	 * @return array
	 */
	public function getPeers( $id = null ) {
		
		if ( is_null( $id ) ) {
			
			global $post;
			// Use the parent value for the current post to get all of its peers.
			$id = $post->post_parent;
		}
		
		$items = new \WP_Query( array(
			'post_type'      => 'page',
			'post_parent'    => $id,
			'posts_per_page' => - 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		
		$itemList = array(
			'pages' => $items->posts,
		);
		
		foreach ( $itemList['pages'] as $k => $v ) {
			
			if ( $v == get_the_ID() ) {
				
				if ( isset( $items->posts[ $k - 1 ] ) ) {
					
					$itemList['prev'] = $items->posts[ $k - 1 ];
				}
				
				if ( isset( $items->posts[ $k + 1 ] ) ) {
					
					$itemList['next'] = $items->posts[ $k + 1 ];
				}
			}
		}
		
		wp_reset_postdata();
		
		return $itemList;
	}
}