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

    private $type;

    private $loadedTS;

    public function init( $programId = null ) {

        global $e20rTracker, $post;

        if ( $programId !== null ) {
            $this->programs = ( is_array( $programId ) ? $programId : array( $programId ) );
        }
        else {
            $this->programs = get_post_meta( $post->ID, 'e20r_tracker_program_ids', true );

            if ( ( $this->programs !== false ) && ( is_array( $this->programs ) ) ) {
                $this->programs = array_unique( $this->programs );
            }
        }

        $this->model = new e20rProgramModel( $this->programs, $this->id );

        $this->programs = $this->model->load_program_info( $this->programs, true );

        $this->loadedTS = current_time('timestamp');
    }

    public function getModel() {

        return $this->model;
    }

    public function getValue( $fieldName = 'id' ) {

        return $this->model->getFieldValue( $fieldName );
    }

    public function getProgramId( $shortName ) {

    }

    public function editor_metabox_setup( $object, $box ) {

        global $e20rTracker;

        if ( ! class_exists( ' e20rProgramView' ) ) {

            dbg("e20rProgram::editor_metablox_setup() - Loading view class for Programs: " . E20R_PLUGIN_DIR );

            if ( ! include_once( E20R_PLUGIN_DIR . "classes/views/class.e20rProgramView.php" ) ) {
                wp_die("Error loading class.e20rProgramView.php file.");
                exit;
            }
            dbg("e20rProgram::editor_metablox_setup() - e20rProgramView class loaded");
        }

        $view = new e20rProgramView( $this->model->load_program_info() );

        dbg("e20rProgram::editor_metablox_setup() - Metabox for Post/Page editor being loaded");

        foreach( $e20rTracker->managed_types as $type ) {

            add_meta_box( 'e20r-program-meta', __( 'Eighty/20 Tracker', 'e20rtracker' ),
                array( &$this, 'view_programPostMetabox' ), $type, 'side', 'high' );
        }
    }

    /**
     * Function renders the page to add/edit/remove programs from the E20R tracker plugin
     */
    public function render_submenu_page() {

        ?><div id="e20r-program-list"><?php

        echo $this->view_listPrograms();

        ?></div><?php
    }

} 