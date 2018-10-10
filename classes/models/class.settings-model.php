<?php

namespace E20R\Tracker\Models;

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Tracker\Controllers\Tracker;
use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Program;

class Settings_Model {
	
	private static $instance = null;
	protected $id = null;
	protected $type;
	protected $cpt_slug;
	protected $table;
	protected $fields;
	
	protected $serialized;
	private $settings;
	private $unrollable;
	
	/**
	 * @param $type
	 * @param $cpt_slug
	 *
	 * @throws \Exception - In the event of a failure to get the table
	 * @access protected
	 */
	protected function __construct( $type, $cpt_slug ) {
		
		$this->type     = $type;
		$this->cpt_slug = $cpt_slug;
		
		$this->unrollable = array(
			'program_ids',
			'article_ids',
			'action_ids',
			'assignment_ids',
			'select_options',
			'activity_id',
		);
		
		$Tables = Tables::getInstance();
		
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::Settings_Model() - Type: {$type}" );
		try {
			$this->table = $Tables->getTable( $this->type );
		} catch ( \Exception $e ) {
			
			Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::Settings_Model - Warning while loading tables & fields: " . $e->getMessage() );
			$this->table  = null;
			$this->fields = null;
			
			return;
		}
		
		$this->fields = $Tables->getFields( $this->type );
		
		$this->serialized = array(
			'groups',
			'assigned_user_id',
			'assigned_usergroups',
			'days',
			'exercises',
			'sequences',
			'users',
			'group',
		);
	}
	
	/**
	 * @return Settings_Model|null
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			try {
				self::$instance = new self;
			} catch ( \Exception $exception ) {
				Utilities::get_instance()->log( "Unable to instantiate Settings_Model class. Error: " . $exception->getMessage() );
				
				return null;
			}
		}
		
		return self::$instance;
	}
	
	public function doSerialize( $field_key ) {
		
		return ( in_array( $field_key, $this->serialized ) );
	}
	
	public function getSetting( $typeId, $fieldName ) {
		
		Utilities::get_instance()->log( "Settings_Model::getSetting() - Running from parent class" );
		
		$typeVar = 'current' . ucfirst( $this->type );
		global ${$typeVar};
		
		if ( empty( ${$typeVar} ) || ( ${$typeVar}->id != $typeId ) ) {
			
			Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::getSetting() -  {$typeVar} data for {$typeId} not found" );
			
			if ( ! $typeId ) {
				
				Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::getSetting() - No " . ucfirst( $this->type ) . " ID!" );
				
				return false;
			}
			
			$this->init( $typeId );
			
			if ( ! $fieldName ) {
				Utilities::get_instance()->log( ucfirst( $this->type ) . "_Model::getSetting() - No field!" );
				
				return false;
			}
			
			if ( empty( $this->settings->{$fieldName} ) ) {
				
				$setting = self::settings( $typeId, 'get', $fieldName );
				
				// Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - Fetched {$fieldName} for {$typeId} with result of {$setting->{$fieldName}}" );
				
				return $setting->{$fieldName};
			} else {
				/*
				if ( ! is_array( $this->settings->{$fieldName} ) ) {
					Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - Returning value={$this->settings->{$fieldName}} for {$fieldName} and {$this->type} {$typeId}" );
				}
				*/
				return $this->settings->{$fieldName};
			}
		} else {
			
			// Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - Using global settings entry and returning value={$this->settings->{$fieldName}} for {$fieldName} and {$this->type} {$typeId}" );
			return ${$typeVar}->{$fieldName};
		}
	}
	
	public function init( $id = null ) {
		
		$typeVar = 'current' . ucfirst( $this->type );
		global ${$typeVar};
		
		$this->id = $id;
		
		if ( $this->id === null ) {
			
			global $post;
			
			if ( isset( $post->post_type ) && ( $post->post_type == $this->cpt_slug ) ) {
				
				$this->id = $post->ID;
			}
		}
		
		if ( empty( ${$typeVar} ) || ( ${$typeVar}->id != $this->id ) ) {
			
			Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::init() -  Loading settings" );
			
			$this->settings = $this->loadSettings( $this->id );
			${$typeVar}     = $this->settings;
		}
	}
	
	/**
	 * Load the Settings from the metadata table.
	 *
	 * @param $id (int) - The ID to load settings for
	 *
	 * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
	 */
	public function loadSettings( $id ) {
		
		$typeVar = 'current' . ucfirst( $this->type );
		global ${$typeVar};
		
		if ( isset( ${$typeVar}->id ) && ( ${$typeVar}->id == $id ) ) {
			
			Utilities::get_instance()->log( ucfirst( $this->type ) . "_Model::loadSettings() - Settings for {$this->type} are already loaded ({$id})" );
			
			return ${$typeVar};
		}
		
		if ( $id == null ) {
			
			$id = $this->id;
		}
		
		if ( $id === null ) {
			Utilities::get_instance()->log( ucfirst( $this->type ) . "_Model::loadSettings() - Error: Unable to load settings. No ID specified!" );
			
			return false;
		}
		
		$this->id = $id;
		
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::loadSettings() - Loading settings for {$this->type} ID {$this->id}" );
		$defaults = $this->defaultSettings();
		
		if ( ! is_object( $this->settings ) ) {
			
			$this->settings = $defaults;
		}
		
		foreach ( $this->settings as $key => $value ) {
			
			if ( false === ( $this->settings = self::settings( $id, 'get', $key ) ) ) {
				
				if ( $key == 'id' ) {
					
					$this->settings->{$key} = $this->id;
					continue;
				}
				
				Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::loadSettings() - ERROR loading setting {$key} for {$this->type} with ID: {$this->id}" );
				
				return false;
			}
			
			// Utilities::get_instance()->log("e20r" . ucfirst($this->type) ."Model::loadSettings() - Loaded {$this->settings->{$key}} for {$key} - a {$this->type} ID {$this->id}");
		}
		
		$this->settings->id     = $this->id;
		$this->settings->loaded = false;
		
		${$typeVar} = $this->settings;
		
		return $this->settings; // For compatibility
	}
	
	protected function defaultSettings() {
		
		$this->settings = new \stdClass();
		
		if ( ! $this->id ) {
			
			global $post;
			
			if ( isset( $post->ID ) ) {
				$this->settings->id = $post->ID;
			}
		} else {
			$this->settings->id = $this->id;
		}
		
		$this->settings = $this->ifMigrating( $this->settings );
		
		return $this->settings;
	}
	
	private function ifMigrating( $migrate ) {
		
		$Tracker = Tracker::getInstance();
		
		$migrated = false;
		
		if ( 1 === $Tracker->loadOption( " converted_metadata_{$this->cpt_slug}" ) ) {
			$migrated = true;
		}
		
		if ( true === $migrated ) {
			
			switch ( $this->cpt_slug ) {
				case 'e20r-workout':
				case 'e20r-articles':
					$migrate->programs    = null;
					$migrate->assignments = null;
					$migrate->checkins    = null;
					break;
				
				case 'e20r-assignments':
					
					$migrate->program_id  = null;
					$migrate->program_ids = null;
					$migrate->article_id  = null;
					break;
				
				case 'e20r-checkins':
					
					$migrate->program_ids = null;
					break;
			}
		}
		
		return $migrate;
	}
	
/**
	 * Load the setting from the WP database
	 *
	 * @param int         $post_id -- ID of the Checkin (post)
	 * @param string      $action  -- Actions: 'update', 'delete', 'get'
	 * @param string|null $key     - The key in the $this->settings object
	 * @param string|null $setting -- The actual setting value
	 *
	 * @return bool|mixed -- False or the complete $this->settings object.
	 *
	 * @access protected
	 */
	protected function settings( $post_id, $action = 'get', $key = null, $setting = null ) {
		
		if ( empty( $this->settings ) ) {
			
			$this->loadSettings( $post_id );
		}
		
		switch ( $action ) {
			
			case 'update':
				
				$setting = ( empty( $setting ) ? 0 : $setting );
				
				if ( ( empty( $setting ) ) && ( ! $key ) ) {
					Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings()  - No key nor settings. Returning quietly." );
					
					return false;
				}
				
				if ( ! in_array( $key, array( null, 'short_code', 'item_text' ) ) ) {
					
					// Utilities::get_instance()->log("e20r" . ucfirst($this->type) . "Model::settings()  - Key and setting defined. Saving.");
					
					$this->settings->{$key} = $setting;
					
					// "Unroll" a setting that's represented as an array of entries
					if ( in_array( $key, $this->unrollable ) ) {
						
						Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings()  - {$key}: Simplifying search operations in the metadata table." );
						Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings()  - Clearing post meta for {$post_id} and key _e20r-{$this->type}-{$key}" );
						delete_post_meta( $post_id, "_e20r-{$this->type}-{$key}" );
						
						if ( is_array( $setting ) ) {
							
							Utilities::get_instance()->log( ucFirst( $this->type ) . "Model::settings() - Value is an array" );
							
							foreach ( $setting as $aVal ) {
								
								if ( false === $this->isStored( $post_id, $key, $aVal, true ) && ( 0 !== $aVal ) ) {
									Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings() - Not currently saved in DB. Saving {$key} = $aVal" );
									add_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $aVal );
								} else if ( 0 === $aVal ) {
									Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings() - Attemting to remove {$key} = $aVal" );
									delete_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $aVal );
								}
							}
						} else {
							if ( false === $this->isStored( $post_id, $key, $setting, true ) && ( 0 !== $setting ) ) {
								Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings() - Not currently saved in DB. Saving {$key} = $setting" );
								add_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $setting );
							} else if ( 0 === $setting ) {
								delete_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $setting );
							}
						}
					} else {
						update_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $setting );
					}
					
					return true;
				}
				
				break;
			
			case 'delete':
				
				$defaults = $this->defaultSettings();
				
				unset( $this->settings->{$key} );
				$this->settings->{$key} = $defaults->{$key};
				
				delete_post_meta( $post_id, "_e20r-{$this->type}-{$key}" );
				
				return true;
				break;
			
			case 'get':
				
				$asArray = false;
				// $val = get_post_meta( $post_id, "_e20r-{$this->type}-{$key}", true );
				
				// $newAFields = array( 'program_ids', 'article_ids', 'assignment_ids', 'activity_id', 'action_ids', 'select_options');
				
				if ( ! in_array( $key, $this->unrollable ) ) {
					$asArray = true;
				}
				
				$val = get_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $asArray );
				
				if ( in_array( $key, $this->unrollable ) &&
				     ( ! is_array( $val ) ) ) {
					
					// Clean up in case something isn't being returned correctly
					$val = array( $val );
				}
				
				if ( is_array( $val ) && ( 1 == count( $val ) ) ) {
					
					$copy = $val;
					$tmp  = array_pop( $copy );
					
					if ( empty( $tmp ) ) {
						
						Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::settings() - {$key} contains a single null array value/element" );
						$val = array();
					}
				}
				
				/*
								if ( ! is_array( $val ) ) {
									Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::settings() - Got: {$val} (from: _e20r-{$this->type}-{$key}) for {$post_id}" );
								}
								else {
									Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::settings() - _e20r-{$this->type}-{$key}) for {$post_id} returns: " );
									Utilities::get_instance()->log($val);
								}
				*/
				if ( ( ! is_array( $val ) ) && ( $val == false ) ) {
					
					$this->settings->{$key} = null;
				} else {
					
					$this->settings->{$key} = $val;
				}
				
				
				break;
			
			default:
				return false;
			
		} // End switch
		
		return $this->settings;
	}
	
	private function isStored( $post_id, $key, $value, $asArray ) {
		
		$metaContent = get_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $asArray );
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::isStored() - Value of _e20r-{$this->type}-{$key}: " . ( false === $metaContent ? 'Not found' : print_r( $metaContent, true ) ) );
		
		if ( ! in_array( $key, $this->unrollable ) && ! is_array( $metaContent ) && false !== $metaContent ) {
			$metaContent = array( $metaContent );
		}
		
		if ( is_array( $metaContent ) && in_array( $value, $metaContent ) ) {
			
			return true;
		}
		
		return false;
	}
	
	public function set( $fieldName, $fieldValue, $post_id = null ) {
		
		if ( ! $post_id ) {
			
			if ( empty( $this->settings ) ) {
				$this->settings = $this->defaultSettings();
			}
			
			$this->settings->{$fieldName} = $fieldValue;
			
			return true;
		} else {
			return $this->settings( $post_id, 'update', $fieldName, $fieldValue );
		}
		
	}
	
	/**
	 * Returns an array of all settings merged with their associated settings.
	 *
	 * @param $statuses string|array - Statuses to return data for.
	 *
	 * @return mixed - Array of objects
	 */
	public function loadAllSettings( $statuses = 'any', $order = 'desc', $orderby = 'post_date' ) {
		
		$settings_list = array();
		
		$query = array(
			'posts_per_page' => - 1,
			'post_type'      => $this->cpt_slug,
			'post_status'    => $statuses,
			'order'          => $order,
			'order_by'       => $orderby,
		);
		
		wp_reset_query();
		
		/* Fetch all Sequence posts */
		$sList = get_posts( $query );
		
		if ( empty( $sList ) ) {
			
			return false;
		}
		
		Utilities::get_instance()->log( "{$this->type}Model::load{$this->type}Data() - Loading {$this->type} settings for " . count( $sList ) . ' settings' );
		
		$default = $this->defaultSettings();
		
		foreach ( $sList as $key => $data ) {
			
			$settings = $this->loadSettings( $data->ID );
			
			// $loaded_settings = (object) array_replace( (array)$default, (array)$settings );
			
			$settings_list[ $data->ID ] = $settings;
		}
		
		wp_reset_postdata();
		
		return $settings_list;
	}
	
	public function get_slug() {
		
		return $this->cpt_slug;
	}
	
	public function findByDate( $date, $programId ) {
		
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::findByDate() - Searching by date: {$date}" );
		
		$list = array();
		
		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => $this->cpt_slug,
			'post_status'    => 'publish',
			'order_by'       => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => "_e20r-{$this->type}-startdate",
					'value'   => $date,
					'compare' => '<=',
					'type'    => 'DATE',
				),
				array(
					'key'     => "_e20r-{$this->type}-enddate",
					'value'   => $date,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'key'     => "_e20r-{$this->type}-program_ids",
					'value'   => $programId,
					'compare' => '=',
					'type'    => 'numeric',
				),
			),
		);
		
		$query = new \WP_Query( $args );
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::findByDate() - Returned actions: {$query->post_count} for query: " . print_r( $args, true ) );
		
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			$list[] = get_the_ID();
		}
		
		wp_reset_postdata();
		
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::findByDate() - Returning ids: " . print_r( $list, true ) );
		
		return $list;
	}
	
	/**
	 * Load the CPT (with settings) for the Tracker type
	 *
	 * @param string $key       - The metadata key value we're searching for. Valid values are 'any' or a defined key
	 *                          in the 'e20r-CPT_type-[key] metadata description (see the defaultSettings() function)
	 * @param mixed  $value     - The value we're searching for.
	 * @param string $dataType  -- A viable WP_Query data type (for the query args)
	 * @param int    $programId -- The program ID
	 * @param string $comp      -- A valid WP_Query comparison operator
	 * @param string $order     -- The sort order for the result
	 *
	 * @return array|bool|mixed - An array of WP_Post objects for the query.
	 */
	public function find( $key, $value, $programId = - 1, $comp = 'LIKE', $order = 'DESC', $dataType = 'numeric' ) {
		
		$pArray = false;
		
		if ( ( $key == 'id' ) && ( $value == 'any' ) ) {
			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => $this->cpt_slug,
				'post_status'    => apply_filters( 'e20r-tracker-model-data-status', array(
					'publish',
					'draft',
					'future',
				) ),
				'order'          => $order,
			);
		} else if ( ( $key == 'id' ) && ( ! is_array( $value ) ) ) {
			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => $this->cpt_slug,
				'post_status'    => apply_filters( 'e20r-tracker-model-data-status', array(
					'publish',
					'draft',
					'future',
				) ),
				'p'              => $value,
				'order'          => $order,
			);
		} else if ( $key != 'id' ) {
			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => $this->cpt_slug,
				'post_status'    => apply_filters( 'e20r-tracker-model-data-status', array(
					'publish',
					'draft',
					'future',
				) ),
				'order_by'       => 'meta_value',
				'order'          => $order,
				'meta_query'     => array(
					array(
						'key'     => "_e20r-{$this->type}-{$key}",
						'value'   => $value,
						'compare' => $comp,
						'type'    => $dataType,
					),
				),
			);
		} else {
			$args = array(
				'posts_per_page' => - 1,
				'post_type'      => $this->cpt_slug,
				'post_status'    => apply_filters( 'e20r-tracker-model-data-status', array(
					'publish',
					'draft',
					'future',
				) ),
				'post__in'       => $value,
				'order'          => $order,
			);
		}
		
		
		if ( ( - 1 != $programId ) && ( $key != 'id' ) ) {
			
			Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::find() - Program ID is: $programId" );
			
			$pExcl = array(
				'key'     => "_e20r-{$this->type}-program_ids",
				'value'   => $programId,
				'compare' => '=',
			);
			
			if ( array_key_exists( 'meta_query', $args ) ) {
				
				$args['meta_query'][1] = $pExcl;
			} else {
				
				$args['meta_query'] = array(
					$pExcl,
				);
			}
		}
		
		Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::find() - Using arguments: " . print_r( $args, true ) );
		// Utilities::get_instance()->log($args);
		
		$dataList = $this->loadForQuery( $args );
		
		if ( empty( $dataList ) && $key == 'id' ) {
			
			Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::find() - Loading default settings... " );
			$dataList[] = $this->defaultSettings();
		}
		
		Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::find() - Found " . count( $dataList ) . " records" );
		
		// Utilities::get_instance()->log( $dataList );
		
		return $dataList;
	}
	
	private function loadForQuery( $args ) {
		
		$list = array();
		
		$query = new \WP_Query( $args );
		
		Utilities::get_instance()->log( "e20r" . ucfirst( $this->type ) . "Model::loadForQuery() - Returned {$this->type}s: {$query->post_count}" );
		
		if ( $query->post_count == 0 ) {
			
			return $list;
		}
		while ( $query->have_posts() ) {
			
			$query->the_post();
			
			$new     = $this->loadSettings( get_the_ID() );
			$new->id = get_the_ID();
			
			$list[] = $new;
		}
		
		wp_reset_postdata();
		
		return $list;
	}
	
		protected function inProgram( $pId, $key, $obj ) {
		
		if ( $pId == - 1 ) {
			
			return true; // No program Id specified from calling function.
		}
		
		$pVal = get_post_meta( $obj->id, $key, true );
		
		if ( $pVal === false ) {
			
			return false;
		}
		
		if ( ! is_array( $pVal ) ) {
			
			$pVal = array( $pVal );
		}
		
		return in_array( $pId, $pVal );
	} // End function
	
	private function getProgramKey() {
		
		$defaults = $this->defaultSettings();
		
		foreach ( $defaults as $key => $val ) {
			
			if ( strpos( $key, 'program' ) !== false ) {
				
				Utilities::get_instance()->log( ucfirst( $this->type ) . "Model::getProgramKey() - found key base for program id(s): {$key}" );
				
				return $key;
			}
		}
		
		return null;
	}
}