<?php

/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rSettingsModel {

    protected $id;
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

        dbg("e20rSettingsModel::e20rSettingsModel() - Type: {$type}");
        try {
            $this->table  = $e20rTables->getTable( $this->type );
            $this->fields = $e20rTables->getFields( $this->type );
        }
        catch( Exception $e ) {

            dbg("e20rSettingsModel::e20rSettingsModel() - Warning while loading tables & fields: " . $e->getMessage() );
            $this->table = null;
            $this->fields = null;
            return;
        }

    }

    public function init( $id = null ) {

        $this->id = $id;

        if ( $this->id === null ) {

            global $post;

            if ( isset( $post->post_type) && ( $post->post_type == $this->cpt_slug ) ) {

                $this->id = $post->ID;
            }
        }

        $this->settings = $this->loadSettings( $this->id );

    }

    protected function defaultSettings() {

        global $post;

        $this->settings = new stdClass();
        $this->settings->id = $post->ID;

        return $this->settings;
    }

    public function getSetting( $id, $fieldName ) {

        if (! $id ) {
            return false;
        }
        if ( !$fieldName ) {
            return false;
        }

        $value = $this->settings->{$fieldName};

        if ( $value === null ) {

            $value = $this->settings( $id, 'get', $fieldName );
        }

        return $value;
    }

    /**
     * Load the Settings from the metadata table.
     *
     * @param $id (int) - The ID to load settings for
     *
     * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
     */
    public function loadSettings( $id  ) {

        if ( $id == null ) {

            $id = $this->id;
        }

        if ( $id == null ) {
            dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Error: Unable to load settings. No ID specified!");
            return false;
        }

        dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Loading settings for {$this->type} ID {$id}");
        $defaults = $this->defaultSettings();

        if ( ! is_object( $this->settings ) ) {

            $this->settings = $defaults;
        }

        foreach( $this->settings as $key => $value ) {

            if ( false === ( $this->settings = self::settings( $id, 'get', $key ) ) ) {

                if ( $key == 'id' ) {

                    $this->settings->{$key} = $id;
                    continue;
                }

                dbg( "e20r" . ucfirst( $this->type ) . "Model::loadSettings() - ERROR loading setting {$key} for {$this->type} with ID: {$id}" );

                return false;
            }

            dbg("e20r" . ucfirst($this->type) ."Model::loadSettings() - Loaded {$this->settings->{$key}} for {$key} - a {$this->type} ID {$id}");
        }

        return $this->settings;
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

        dbg("e20r{$this->type}Model::loadAllCheckinData() - Loading {$this->type} settings for " . count( $sList ) . ' settings');
        $default = $this->defaultSettings();

        foreach( $sList as $key => $data ) {

            $settings = self::loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$default, (array)$settings );

            $settings_list[$data->ID] = $loaded_settings;
        }

        return $settings_list;
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

        dbg("e20r{$this->type}Model::settings() - {$post_id} -> {$action} -> {$key} -> {$setting}");
        switch ($action) {

            case 'update':

                if ( ( !$setting ) && ( !$key ) ) {
                    dbg("e20r{$this->type}Model::settings()  - No key nor settings. Returning quietly.");
                    return false;
                }

                if  ( ( $setting ) &&
                      ( ( ! in_array( $key, array( null, 'short_code', 'item_text') ) ) ) ) {

                    dbg("e20r{$this->type}Model::settings()  - Key and setting defined. Saving.");
                    $this->settings->{$key} = $setting;

                    update_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $setting, true ) or
                    add_post_meta( $post_id, "_e20r-{$this->type}-{$key}", $setting, true );
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

                dbg("e20rSettingsModel::settings() - Got: {$val} for {$post_id}");

                $this->settings->{$key} = ( false === $val ? null : $val );

                dbg("e20rSettingsModel::settings() - Loaded: {$this->settings->{$key}}");

                break;

            default:
                return false;

        } // End switch

        return $this->settings;
    } // End function
}