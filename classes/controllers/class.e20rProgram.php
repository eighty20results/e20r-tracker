<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rProgram {

    private $programs = array();
    public $model = null;
    public $view;

    private $type;

    private $loadedTS;

    public function init( $programId = null ) {

        dbg("e20rProgram::init() - Initializing Program data");

        global $post;

        if ( $programId !== null ) {
            $this->programs = ( is_array( $programId ) ? $programId : array( $programId ) );
        }
        else {
            $this->programs = get_post_meta( $post->ID, 'e20r_tracker_program_ids', true );

            if ( ( $this->programs !== false ) && ( is_array( $this->programs ) ) ) {
                $this->programs = array_unique( $this->programs );
            }
        }

        $this->model = new e20rProgramModel();
        $this->view = new e20rProgramView();

        // $this->programs = $this->model->loadPrograminfo( $this->programs, true );

        $this->loadedTS = current_time('timestamp');
    }

    public function isActive( $program_shortname ) {


        if ( ! isset($this->model) ) {
            $this->init();
        }

        $program = $this->getProgram( $program_shortname );

        if ( ( $program !== false ) && ( ! in_array( $program->post_status, array( 'publish', 'private' ) ) ) ) {

            dbg("e20rProgram::isActive() - Program not found or not published");
            return false;
        }

        $now = current_time( 'timestamp' );
        $start = strtotime( $program->starttime );
        $end = strtotime( $program->endtime );

        // It's available since no start has been configured.
        if ( ! $start ) {
            dbg("e20rProgram::isActive() - Start value not set, program is available");
            return true;
        }

        // It's available since no end-time has been configured and it's after the starttime
        if ( ( ! $end )  && ( $now >= $start ) ) {
            dbg("e20rProgram::isActive() - It's after the start date, and end value not set, program is available");
            return true;
        }

        if ( ( $now >= $start ) && ( $now <= $end ) ) {
            dbg("e20rProgram::isActive() - Currently somewhere between start and end for the program. it's available ");
            return true;
        }

        return false;
    }

    public function getProgram( $shortName ) {

        if ( ! isset( $this->model ) ) {
            $this->init();
        }

        $pgmList = $this->model->loadAllPrograms( 'any' );

        foreach ($pgmList as $pgm ) {
            if ( $pgm->program_shortname == $shortName ) {
                unset($pgmList);
                return $pgm;
            }
        }

        unset($pgmList);
        return false; // Returns false if the program isn't found.
    }

    public function editor_metabox_setup( $object, $box ) {

        global $e20rTracker;

        // $this->view = new e20rProgramView( $this->model->load_program_info() );
        add_meta_box('e20r-tracker-program-settings', __('Program Settings', 'e20rtracker'), array(&$this, "addMeta_ProgramSettings"), 'e20r_programs', 'normal', 'high');

        /*
        dbg("e20rProgram::editor_metablox_setup() - Metabox for Post/Page editor being loaded");

        foreach( $e20rTracker->managed_types as $type ) {

            add_meta_box( 'e20r-program-meta', __( 'Eighty/20 Tracker', 'e20rtracker' ),
                array( &$this->view, 'view_programPostMetabox' ), $type, 'side', 'high' );
        }
        */
    }

    public function saveSettings( $post_id ) {

        global $post;

        dbg("e20rProgram::saveSettings() - Saving Program Settings to DB");

        if ( $post->post_type != 'e20r_programs') {
            return $post_id;
        }

        if ( empty( $post_id ) ) {
            dbg("e20rProgram::saveSettings() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }

        $this->init( $post->ID );

        dbg("e20rProgram::saveSettings()  - Saving metadata for the post_type(s)");

        $settings = new stdClass();
        $settings->id = $post_id;
        $settings->starttime = isset( $_POST['e20r-program-starttime'] ) ? sanitize_text_field( $_POST['e20r-program-endtime'] ) : null;
        $settings->endtime = isset( $_POST['e20r-program-endtime'] ) ? sanitize_text_field( $_POST['e20r-program-endtime'] ) : null;
        $settings->program_shortname = isset( $_POST['e20r-program-shortname']) ? sanitize_text_field( $_POST['e20r-program-shortname'] ) : null;
        $settings->sequences = isset( $_POST['e20r-program-dripfeed']) ? intval( $_POST['e20r-program-dripfeed'] ) : null;

        $this->model->saveSettings( $settings );
    }

    public function getPeerPrograms( $programId = null ) {

        if ( is_null( $programId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $programId = $post->post_parent;
        }

        $programs = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $programId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $ProgramList = array(
            'pages' => $programs->posts,
        );

        foreach ( $programs->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $programs->posts[$k-1] ) ) {

                    $ProgramList['prev'] = $programs->posts[ $k - 1 ];
                }

                if( isset( $programs->posts[$k+1] ) ) {

                    $ProgramList['next'] = $programs->posts[ $k + 1 ];
                }
            }
        }

        return $ProgramList;
    }

    public function addMeta_ProgramSettings() {

        global $post;

        $this->init( $post->ID );

        dbg("e20rProgram::addMeta_ProgramSettings() - Loading settings metabox for program page");
        echo $this->view->viewProgramSettingsBox( $this->model->loadProgramData( $post->ID ), $this->loadDripFeed( 'all' ) );

    }

    private function loadDripFeed( $feedId ) {

        if ( $feedId == 'all' ) {
            $id = null;
        }
        else {
            $id = $feedId;
        }

        if ( class_exists( 'PMProSequence' ) ) {

            $dripFeed = new PMProSequence();
            return $dripFeed->getAllSequences('publish');
        }

        return false;
    }
    /********************** OBSOLETE ***************************/

    /**
     * Function renders the page to add/edit/remove programs from the E20R tracker plugin
     */
    public function render_submenu_page() {

        dbg("e20rProgram::render_submenu_page() - Loading program list...");
        $this->init();

        ?><div id="e20r-program-list"><?php

        echo $this->view->view_listPrograms();

        ?></div><?php
    }

    public function getValue( $fieldName = 'id' ) {

        return $this->model->getFieldValue( $fieldName );
    }

} 