<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rProgram extends e20rSettings {

    private $programTree = array();

    public function e20rProgram() {

        dbg("e20rProgram::init() - Initializing Program data");
        parent::__construct( 'program', 'e20r_programs', new e20rProgramModel(), new e20rProgramView() );

    }

    public function init( $programId = null ) {

        global $post;

        if ( $programId !== null ) {
            $this->programTree = ( is_array( $programId ) ? $programId : array( $programId ) );
        }
        else {
            $this->programTree = get_post_meta( $post->ID, 'e20r_tracker_program_ids', true );

            if ( ( $this->programTree !== false ) && ( is_array( $this->programs ) ) ) {
                $this->programTree = array_unique( $this->programs );
            }
        }

        return true;
    }

    public function isActive( $program_shortname ) {

        $program = $this->findByName( $program_shortname );

        if ( ( $program !== false ) && ( ! in_array( $program->post_status, array( 'publish', 'private' ) ) ) ) {

            dbg("e20rProgram::isActive() - Program not found or not published");
            return false;
        }

        $now = current_time( 'timestamp' );
        $start = strtotime( $program->startdate );
        $end = strtotime( $program->enddate );

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


    protected function loadDripFeed( $feedId ) {

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