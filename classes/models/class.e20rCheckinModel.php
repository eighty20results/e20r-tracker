<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rCheckinModel {

    private $settings;

    public function e20rCheckinModel( $checkinId = null ) {

        if ( $checkinId === null ) {

            global $post;

            if ( isset( $post->post_type) && ( $post->post_type == 'e20r_checkins' ) ) {

                $checkinId = $post->ID;
            }
        }


        $this->settings = $this->loadSettings( $checkinId );
    }

    private function defaultSettings() {

        global $post;

        $settings = new stdClass();
        $settings->short_name = null;
        $settings->item_text =  ( isset( $post->post_title ) ? $post->post_title : null );
        $settings->startdate = date_i18n( 'Y-m-d', current_time('timestamp') );
        $settings->enddate = null;
        $settings->maxcount = 0;

        return $settings;
    }

    /**
     * Returns an array of all checkins merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return checkin data for.
     * @return mixed - Array of checkin objects
     */
    public function loadAllData( $statuses = 'any' ) {

        $query = array(
            'post_type' => 'e20r_checkins',
            'post_status' => $statuses,
        );

        wp_reset_query();

        /* Fetch all Sequence posts */
        $checkin_list = get_posts( $query );

        if ( empty( $checkin_list ) ) {

            return false;
        }

        dbg("e20rCheckinModel::loadAllCheckinData() - Loading checkin settings for " . count( $checkin_list ) . ' settings');

        foreach( $checkin_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $checkin_list[$key] = $loaded_settings;
        }

        return $checkin_list;
    }

    public function loadCheckinData( $id, $statuses = 'any' ) {

        if ( $id == null ) {
            dbg("Error: Unable to load checkin data. No ID specified!");
            return false;
        }

        $query = array(
            'post_type' => 'e20r_checkins',
            'post_status' => $statuses,
            'p' => $id,
        );

        wp_reset_query();

        /* Fetch Checkins */
        $checkin_list = get_posts( $query );

        if ( empty( $checkin_list ) ) {
            dbg("e20rCheckinModel::loadCheckinData() - No checkins found!");
            return false;
        }

        foreach( $checkin_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $checkin_list[$key] = $loaded_settings;
        }

        return $checkin_list[0];
    }

    /**
     * Save the Checkin Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific checkin.
     *
     * @return bool - True if successful at updating checkin settings
     */
    public function saveSettings( $settings ) {

        $checkinId = $settings->id;
        unset($settings->id);

        $defaults = $this->defaultSettings();

        dbg("e20rCheckinModel::saveSettings() - Saving checkin Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $settings as $key => $value ) {

            if ( false === $this->settings( $checkinId, 'update', $key, $value ) ) {

                dbg( "e20rCheckin::saveSettings() - ERROR saving {$key} setting ({$value}) for check-in definition with ID: {$checkinId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

    /**
     * Load the Checkin Settings from the metadata table.
     *
     * @param $id (int) - The ID of the checkin to load settings for
     *
     * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
     */
    public function loadSettings( $id ) {

        $defaults = $this->defaultSettings();

        if ( ! is_object( $this->settings ) ) {

            $this->settings = new stdClass();
        }

        foreach( $defaults as $key => $value ) {

            if ( false === ( $this->settings( $id, 'get', $key, $value ) ) ) {

                dbg("e20rCheckinModel::loadSettings() - ERROR loading setting {$key} for checkin with ID: {$id}");
                return false;
            }

        }

        return $this->settings;
    }

    /**
     * @param $post_id -- ID of the Checkin (post)
     * @param string $action -- Actions: 'update', 'delete', 'get'
     * @param null $key - The key in the $this->settings object
     * @param null $setting -- The actual setting value
     *
     * @return bool|mixed|void -- False or the complete $this->settings object.
     */
    public function settings( $post_id, $action = 'get', $key = null, $setting = null ) {

        switch ($action) {
            case 'update':

                if ( ( !$setting ) && ( !$key ) ) {
                    return;
                }

                if ( ( $setting ) && ( $key ) ) {

                    $this->settings->{$key} = $setting;

                    add_post_meta( $post_id, "e20r-checkin-{$key}", $setting, true ) or
                    update_post_meta( $post_id, "e20r-checkin-{$key}", $setting );
                    return true;
                }

                break;

            case 'delete':

                $defaults = $this->defaultSettings();

                unset( $this->settings->{$key});
                $this->settings->{$key} = $defaults->{$key};

                delete_post_meta( $post_id, "e20r-checkin-{$key}" );

                break;

            case 'get':

                if ( !$key && !$setting ) {
                    // Load all settings for this device.
                    $this->loadSettings( $post_id );

                    $all = get_post_custom( $post_id );

                    dbg("e20rCheckinModel::settings() - post_customs: " . print_r( $all, true ) );
                }
                else {

                    $this->settings->{$key} = get_post_meta( $post_id, "e20r-checkin-{$key}", $setting, true );
                }
                return $this->settings;
                break;

            default:
                return false;
        } // End swithc
    } // End function
}