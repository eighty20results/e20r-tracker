<?php

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rSettingsModel {

    protected $id = null;
    protected $type;
    protected $cpt_slug;

    private $settings;

    protected $table;
    protected $fields;

    /**
     * @param $type
     * @param $cpt_slug
     *
     * @throws Exception - In the event of a failure to get the table
     * @access protected
     */
    protected function __construct( $type, $cpt_slug ) {

        $this->type = $type;
        $this->cpt_slug = $cpt_slug;

        global $e20rTables;

        dbg("e20r" . ucfirst($this->type) . "Model::e20rSettingsModel() - Type: {$type}");
        try {
            $this->table  = $e20rTables->getTable( $this->type );
            $this->fields = $e20rTables->getFields( $this->type );
        }
        catch( Exception $e ) {

            dbg("e20r" . ucfirst($this->type) . "Model::e20rSettingsModel - Warning while loading tables & fields: " . $e->getMessage() );
            $this->table = null;
            $this->fields = null;
            return;
        }

    }

    public function init( $id = null ) {

	    $typeVar = 'current' . ucfirst($this->type);
	    global ${$typeVar};

	    $this->id = $id;

        if ( $this->id === null ) {

            global $post;

            if ( isset( $post->post_type) && ( $post->post_type == $this->cpt_slug ) ) {

                $this->id = $post->ID;
            }
        }

	    if ( empty( ${$typeVar} ) || ( ${$typeVar}->id != $this->id ) ) {

		    dbg("e20r" . ucfirst($this->type) . "Model::init() -  Loading settings");

		    $this->settings = $this->loadSettings( $this->id );
		    ${$typeVar} = $this->settings;
	    }
    }

    protected function defaultSettings( ) {

        $this->settings = new stdClass();

	    if (! $this->id ) {

		    global $post;

		    if ( isset( $post->ID ) ) {
			    $this->settings->id = $post->ID;
		    }
	    }
	    else {
			$this->settings->id = $this->id;
	    }

        return $this->settings;
    }

    public function getSetting( $typeId, $fieldName ) {

	    $typeVar = 'current' . ucfirst($this->type);
	    global ${$typeVar};

	    if ( empty( ${$typeVar} ) || ( ${$typeVar}->id != $typeId )) {

		    dbg("e20r" . ucfirst( $this->type ) . "Model::getSetting() -  {$typeVar} data for {$typeId} not found");

		    if ( ! $typeId ) {

			    dbg( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - No " . ucfirst( $this->type ) . " ID!" );
			    return false;
		    }

		    $this->init( $typeId );

		    if ( ! $fieldName ) {
			    dbg( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - No field!" );

			    return false;
		    }

		    if ( empty( $this->settings->{$fieldName} ) ) {

			    $setting = self::settings( $typeId, 'get', $fieldName );
			    // dbg( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - Fetched {$fieldName} for {$typeId} with result of {$setting->{$fieldName}}" );

			    return $setting->{$fieldName};
		    } else {
			    /*
			    if ( ! is_array( $this->settings->{$fieldName} ) ) {
				    dbg( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - Returning value={$this->settings->{$fieldName}} for {$fieldName} and {$this->type} {$typeId}" );
			    }
				*/
			    return $this->settings->{$fieldName};
		    }
	    }
	    else {

		    // dbg( "e20r" . ucfirst( $this->type ) . "Model::getSetting() - Using global settings entry and returning value={$this->settings->{$fieldName}} for {$fieldName} and {$this->type} {$typeId}" );
		    return ${$typeVar}->{$fieldName};
	    }

        return false;
    }

    public function set( $fieldName, $fieldValue, $post_id = null ) {

        if ( ! $post_id ) {
            $this->settings->{$fieldName} = $fieldValue;
            return true;
        }
        else {
            return $this->settings( $post_id, 'update', $fieldName, $fieldValue );
        }

    }

    /**
     * Load the Settings from the metadata table.
     *
     * @param $id (int) - The ID to load settings for
     *
     * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
     */
    public function loadSettings( $id  ) {

	    $typeVar = 'current' . ucfirst($this->type);
	    global ${$typeVar};

	    if ( ! empty( ${$typeVar} ) && ( ${$typeVar}->id == $id )) {

		    dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Already loaded settings ({$id})");
		    return ${$typeVar};
	    }

	    if ( $id == null ) {

            $id = $this->id;
        }

        if ( $id === null ) {
            dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Error: Unable to load settings. No ID specified!");
            return false;
        }

        $this->id = $id;

        dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Loading settings for {$this->type} ID {$this->id}");
        $defaults = $this->defaultSettings();

        if ( ! is_object( $this->settings ) ) {

            $this->settings = $defaults;
        }

        foreach( $this->settings as $key => $value ) {

            if ( false === ( $this->settings = self::settings( $id, 'get', $key ) ) ) {

                if ( $key == 'id' ) {

                    $this->settings->{$key} = $this->id;
                    continue;
                }

                dbg( "e20r" . ucfirst( $this->type ) . "Model::loadSettings() - ERROR loading setting {$key} for {$this->type} with ID: {$this->id}" );

                return false;
            }

            // dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Loaded {$this->settings->{$key}} for {$key} - a {$this->type} ID {$this->id}");
        }

	    $this->settings->id = $this->id;

	    ${$typeVar} = $this->settings;
	    return $this->settings; // For compatibility
    }

    /**
     * Returns an array of all settings merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return checkin data for.
     * @return mixed - Array of checkin objects
     */
    public function loadAllSettings( $statuses = 'any' ) {

        $settings_list = array();

        $query = array(
            'post_type' => $this->cpt_slug,
            'post_status' => $statuses,
        );

        wp_reset_query();

        /* Fetch all Sequence posts */
        $sList = get_posts( $query );

        if ( empty( $sList ) ) {

            return false;
        }

        dbg("e20r{$this->type}Model::load{$this->type}Data() - Loading {$this->type} settings for " . count( $sList ) . ' settings');
        $default = $this->defaultSettings();

        foreach( $sList as $key => $data ) {

            $settings = self::loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$default, (array)$settings );

            $settings_list[$data->ID] = $loaded_settings;
        }

        return $settings_list;
    }

	public function get_slug() {

		return $this->cpt_slug;
	}

	public function find( $key, $value, $dataType = 'numeric', $programId = -1, $comp = '=', $order = 'DESC' ) {

		global $e20rProgram;

		$programKey = null;
		$pArray = false;

		if ( $key != 'id' ) {
			$args = array(
				'posts_per_page' => -1,
				'post_type' => $this->cpt_slug,
				'post_status' => apply_filters( 'e20r-tracker-model-data-status', array( 'publish' )),
				'order_by' => 'meta_value',
				'order' => $order,
				'meta_query' => array(
					array(
						'key' => "_e20r-{$this->type}-{$key}",
						'value' => $value,
						'compare' => $comp,
						'type' => $dataType,
					),
				)
			);
		}
		else {
			$args = array(
				'posts_per_page' => -1,
				'post_type' => $this->cpt_slug,
				'post_status' => 'publish',
				'page_id' => $value,
			);
		}

		$dataList = $this->loadForQuery( $args );

		dbg("e20r" . ucfirst($this->type) ."Model::find() - List of data: " );
		dbg( $dataList );

		if ( is_array( $dataList ) && ( ! empty( $dataList ) ) ) {

			$pArray = true;
			$tId = $dataList[0]->id;
		}
		elseif ( !empty( $dataList )) {

			$pArray = false;
			$tId = $dataList->id;
		}
		else {
			$pArray = false;
			$tId = 0;
		}

		$metaKeys = get_post_custom_keys( $tId );

		if ( ! empty( $metaKeys ) ) {

			foreach ( $metaKeys as $mk ) {

				if ( strpos( $mk, 'program' ) !== false ) {

					dbg( "e20r" . ucfirst( $this->type ) . "Model::find() - found key containing program id's: {$mk}" );
					$programKey = $mk;
				}
			}
		}

		if ( ( $programKey !== null ) && ( $programId !== -1 ) ) {

			// Drop elements that do _not_ associate with the program specified.
			if ( $pArray ) {

				foreach( $dataList as $k => $data ) {

					if ( $this->inProgram( $programId, $programKey ,$data ) === false ) {

						dbg("e20r" . ucfirst($this->type) ."Model::find() - Dropping " . $dataList[$k]->id . " (doesn't belong to program #{$programId})" );
						unset($dataList[$k]);
					}
				}
			}
			else {

				if ( $this->inProgram( $programId, $programKey, $dataList ) === false ) {

					dbg("e20r" . ucfirst($this->type) ."Model::find() - Dropping " . $dataList->id . " (doesn't belong to program #{$programId})" );
					$dataList = false;
				}
			}
		}

		return $dataList;
	}

	private function inProgram( $pId, $key, $obj ) {

		if ( $pId == -1 ) {

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
	}

	private function loadForQuery( $args ) {

		$articleList = array();
		dbg( $args );

		$query = new WP_Query( $args );

		dbg("e20r" . ucfirst($this->type) ."Model::loadForQuery() - Returned {$this->type}s: {$query->post_count}" );

		if ( $query->post_count == 0 ) {

			return $articleList;
		}
		while ( $query->have_posts() ) {

			$query->the_post();

			$new = $this->loadSettings( get_the_ID() );
			$new->id = get_the_ID();

			if ( $query->post_count > 1 ) {

				$articleList[] = $new;
			}
			else {
				$articleList = $new;
			}
		}

		return $articleList;
	}

    /**
     * Load the setting from the WP database
     *
     * @param $post_id -- ID of the Checkin (post)
     * @param string $action -- Actions: 'update', 'delete', 'get'
     * @param null $key - The key in the $this->settings object
     * @param null $setting -- The actual setting value
     *
     * @return bool|mixed|void -- False or the complete $this->settings object.
     *
     * @access protected
     */
    protected function settings( $post_id, $action = 'get', $key = null, $setting = null ) {

	    if ( empty( $this->settings ) ) {

		    $this->loadSettings( $post_id );
	    }

	    /*
	    if ( is_array( $setting ) ) {
		    dbg( "e20r" . ucfirst( $this->type ) . "Model::settings() - {$post_id} -> {$action} -> {$key} -> " );
		    dbg($setting);
	    }
	    else {
		    dbg( "e20r" . ucfirst( $this->type ) . "Model::settings() - {$post_id} -> {$action} -> {$key} -> {$setting}" );
	    }
		*/
        switch ($action) {

            case 'update':

                $setting = ( empty($setting) ? 0 : $setting );

                if ( ( empty($setting) ) && ( !$key ) ) {
                    dbg("e20r" . ucfirst($this->type) . "Model::settings()  - No key nor settings. Returning quietly.");
                    return false;
                }

                if  ( ! in_array( $key, array( null, 'short_code', 'item_text') ) ) {

                    // dbg("e20r" . ucfirst($this->type) . "Model::settings()  - Key and setting defined. Saving.");

                    $this->settings->{$key} = $setting;

                    update_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $setting );

                    return true;
                }

                break;

            case 'delete':

                $defaults = $this->defaultSettings();

                unset( $this->settings->{$key});
                $this->settings->{$key} = $defaults->{$key};

                delete_post_meta( $post_id, "_e20r-{$this->type}-{$key}" );
                return true;
                break;

            case 'get':

                $val = get_post_meta( $post_id, "_e20r-{$this->type}-{$key}", true );
				/*
				if ( ! is_array( $val ) ) {
					dbg( "e20r" . ucfirst( $this->type ) . "Model::settings() - Got: {$val} (from: _e20r-{$this->type}-{$key}) for {$post_id}" );
				}
				else {
					dbg( "e20r" . ucfirst( $this->type ) . "Model::settings() - _e20r-{$this->type}-{$key}) for {$post_id} returns: " );
					dbg($val);
				}
				*/
				if ( $val == false ) {

					$this->settings->{$key} = null;
				}
				else {

					$this->settings->{$key} = $val;
				}


                break;

            default:
                return false;

        } // End switch

        return $this->settings;
    } // End function
}