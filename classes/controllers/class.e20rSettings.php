<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rSettings {

    protected $model;
    protected $view;

    protected $cpt_slug;
    protected $type;

    protected function __construct( $type = null, $cpt_slug = null, $model = null , $view = null ) {

        $this->cpt_slug = $cpt_slug;
        $this->type = $type;

        $this->model = $model;
        $this->view = $view;
    }

    protected function init( $postId ) {

        $settingsId = get_post_meta( $postId, "_e20r-{$this->type}-id", true);

        if ( ! $settingsId ) {

            return false;
        }

        if ( false === ( $settings = $this->model->loadSettings( $settingsId ) ) ) {

            dbg("e20r" . ucfirst($this->type) . "::init() - FAILED to load settings for {$settingsId}");
            return false;
        }

        return $settingsId;
    }

    public function findByName( $shortName ) {

        if ( ! isset( $this->model ) ) {
            $this->init();
        }

        $list = $this->model->loadAllSettings( 'any' );
        $key = false;

        foreach ($list as $settings ) {

            if ( isset($settings->short_name) ) {
                $key = 'short_name';
            }

            if ( isset( $settings->program_shortname ) ) {
                $key = 'program_shortname';
            }

            if ( ! $key ) {
                return false;
            }

            if ( $settings->{$key} == $shortName ) {
                unset($list);
                return $settings;
            }
        }

        unset($list);

        return false; // Returns false if the requested settings aren't found
    }

    public function getAll() {

        return $this->model->loadAllSettings();

    }

    public function getSettings( $id ) {

        return $this->model->loadSettings( $id );
    }

    public function editor_metabox_setup( $object, $box ) {

        global $e20rTracker;

        $title =  ucfirst( $this->type ) . ' Settings';

        add_meta_box("e20r-tracker-{$this->type}-settings", __($title, 'e20rtracker'), array(&$this, "addMeta_Settings"), $this->cpt_slug, 'normal', 'high');

    }

    /*
    public function getID( $userId = null ) {

        if (is_null( $userId ) ) {
            global $current_user;

            $userId = $current_user->ID;
        }
*/
        /*
         * Fetch all programs this user is a member of.
         *
         * On user sign-up (to Nourish), select the program that has a startdate that matches the membership level.
         *
         * Set program_id in wp_usermeta ('e20r-tracker-program')
         *
         * in_array( user->membership_level, $e20rPrograms->levels ) or in_array( 'e20r-tracker-program'  + member_level->ID for user ==> program
         * Set PROGRAM ID when lesson (or measurement page) is being loaded.
         */
/*            return $this->model->id;
    }
*/
    public function addMeta_Settings() {

        global $post;

        $this->init( $post->ID );

        dbg("e20rSettings::addMeta_Settings() - Loading metabox for {$this->type} settings");
        echo $this->view->viewSettingsBox( $this->model->loadSettings( $post->ID ), $this->loadDripFeed( 'all' ) );
    }

    public function saveSettings( $post_id ) {

        global $e20rTracker, $post;

        dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Saving {$this->type} Settings to DB");

        if ( $post->post_type != $this->cpt_slug) {
            return $post_id;
        }

        if ( empty( $post_id ) ) {
            dbg("e20r" .ucfirst($this->type) . "::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }
        dbg("e20r" . ucfirst($this->type) . "::saveSettings()  - Saving metadata for the {$this->type} post_type");
        $this->model->init( $post_id );

        $settings = $this->model->loadSettings( $post_id );
        $defaults = $this->model->defaultSettings();

        if ( ! $settings ) {

            $settings = $defaults;
        }

        foreach( $settings as $field => $setting ) {

            $tmp = isset( $_POST["e20r-{$this->type}-{$field}"] ) ? $e20rTracker->sanitize( $_POST["e20r-{$this->type}-{$field}"] ) : null;

            dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Page data : {$field} -> {$tmp}");

            if ( is_null( $tmp ) ) {

                $tmp = $defaults->{$field};

            }

            $settings->{$field} = $tmp;
        }

        // Add post ID (checkin ID)
        $settings->id = isset( $_REQUEST["post_ID"] ) ? intval( $_REQUEST["post_ID"] ) : null;

        dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Saving: " . print_r( $settings, true ) );

        if ( ! $this->model->saveSettings( $settings ) ) {

            dbg("e20r" .ucfirst($this->type) . "::saveSettings() - Error saving settings!");
        }

    }
}