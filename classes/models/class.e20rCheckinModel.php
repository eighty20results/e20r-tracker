<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckinModel extends e20rSettingsModel {


    public function e20rCheckinModel()  {

        parent::__construct( 'checkin', 'e20r_checkins' );

    }

    public function defaultSettings() {

        global $post;

        $settings = parent::defaultSettings();

        $settings->checkin_type = 0; // 1 = Action, 2 = Assignment, 3 = Workout, 4 = Survey.
        $settings->item_text = ( isset( $post->post_excerpt ) ? $post->post_excerpt : null );
        $settings->short_name =  ( isset( $post->post_title ) ? $post->post_title : null );
        $settings->startdate = null;
        $settings->enddate = null;
        $settings->maxcount = 0;
        $settings->program_ids = null;

        return $settings;
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

        $defaults = $this->defaultSettings();

        dbg("e20rCheckinModel::saveSettings() - Saving checkin Metadata: " . print_r( $settings, true ) );

        $error = false;

        foreach ( $defaults as $key => $value ) {

            if ( in_array( $key, array( 'id', 'short_name', 'item_text' ) ) ) {
                continue;
            }

            if ( false === $this->settings( $checkinId, 'update', $key, $settings->{$key} ) ) {

                dbg( "e20rCheckin::saveSettings() - ERROR saving {$key} setting ({$settings->{$key}}) for check-in definition with ID: {$checkinId}" );

                $error = true;
            }
        }

        return ( !$error ) ;
    }

}