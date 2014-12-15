<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rPrograms {

    private $table;

    private $programIds = array();

    public $programId;
    public $articles;

    private $type;
    private $programs = array();

    private $loadedTS;
    private $post_types = array();

    public function __construct( $programId = null ) {

        global $e20rTracker, $post;

        if ( $programId !== null ) {
            $this->programIds = ( is_array( $programId ) ? $programId : array( $programId ) );
        }
        else {
            $this->programIds = get_post_meta( $post->ID, 'e20r_tracker_program_ids', true );
            $this->programIds = array_unique( $this->programIds );
        }

        $this->table = $e20rTracker->tables->getTable('programs');

        $this->loadedTS = current_time('timestamp');
    }

    public function init() {

        $this->programs = $this->load_program_info( $this->programIds, true );
    }

    public function load_hooks() {
        dbg("e20rPrograms::load_hooks() - Loading action hooks");

        add_action( 'post_updated', array( &$this, 'postSave' ) );
        add_action( 'add_meta_boxes', array( &$this, 'editor_metabox_setup') );
        add_action( 'wp_ajax_save_program_info', array( &$this, 'ajax_save_program_info' ) );

    }

    public function getStart( $programId ) {

    }

    public function getProgramId( $shortName ) {

    }

    public function load_program_info( $programIds = null, $add_new = true ) {

        global $wpdb;

        dbg("e20rPrograms::load_program_info() - Loading programs from the DB");
        dbg("e20rPrograms::load_program_info() - Content of programIds: " . print_r($programIds, true));

        if ( $programIds == null ) {

            $sql = "
                    SELECT *
                    FROM {$this->table}
                    ORDER BY program_name ASC
              ";
        }
        else {

            if ( ( ! is_array( $programIds )) && ( $programIds != null ) ) {
                dbg("e20rPrograms::load_program_info() - programIds is a single value: {$programIds}");
                $programIds = array( $programIds );
            }

            $sql = "
                    SELECT *
                    FROM {$this->table}
                    WHERE id IN ( " . implode(',', $programIds ) . " )
                    ORDER BY program_name ASC
              ";
        }

        dbg("e20rPrograms::load_program_info() - SQL: " . print_r($sql, true) );
        $res = $wpdb->get_results( $sql , OBJECT );

        if ( ! empty( $res ) ) {
            $this->programs = $res;
        }
        else {
           $this->programs = array();
        }

        if ( $add_new ) {

            $data = new stdClass();
            $data->id = 0;
            $data->program_name = 'Add a new program';

            array_unshift( $this->programs, $data );

            return $this->programs;
        }
        else {
            // Just give the list of existing programs.
            return $this->programs;
        }
    }



    private function getName( $id ) {

        foreach( $this->programs as $program ) {

            if ( $program->ID == $id ) {
                return $program->program_name;
            }
        }
        return false;
    }

    public function editor_metabox_setup( $object, $box ) {

        global $e20rTracker;

        $view = new e20rProgramView( $this->load_program_info() );

        dbg("e20rPrograms() - Metabox for Post/Page editor being loaded");

        foreach( $e20rTracker->managed_types as $type ) {

            add_meta_box( 'e20r-program-meta', __( 'Eighty/20 Tracker', 'e20rtracker' ), array(
                &$this,
                'view_programPostMetabox'
            ), $type, 'side', 'high' );
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

    public function ajax_save_program_info() {

        dbg("Save new or updated information for a program");

        check_ajax_referer('e20r-tracker-data', 'e20r_tracker_edit_programs_nonce');

        dbg("Nonce ok, processing & saving data");

        global $wpdb, $current_user;

        if ( current_user_can( 'manage_options' ) ) {

            dbg("Has permission to update data");

            $tmp        = ( isset( $_POST['e20r_program_id'] ) ? $_POST['e20r_program_id'] : null );
            $program_id = is_numeric( $tmp ) ? intval( $tmp ) : sanitize_text_field( $_POST['e20r_program_id'] );

            dbg( "Delete: " . $_POST['e20r_program_delete'] );

            $delete_only = ( ( isset( $_POST['e20r_program_delete'] ) && ( esc_attr( $_POST['e20r_program_delete'] ) == 'true' ) ) ? true : false );

            if ( ! $delete_only ) {

                $data = array(
                    'program_name' => ( isset( $_POST['e20r_program_name'] ) ? sanitize_text_field( $_POST['e20r_program_name'] ) : null ),
                    'starttime'    => ( isset( $_POST['e20r_program_start'] ) ? sanitize_text_field( $_POST['e20r_program_start'] ) : null ) . " 00:00:00",
                    'endtime'      => ( isset( $_POST['e20r_program_end'] ) ? sanitize_text_field( $_POST['e20r_program_end'] ) : null ) . " 00:00:00",
                    'description'  => ( isset( $_POST['e20r_program_descr'] ) ? sanitize_text_field( $_POST['e20r_program_descr'] ) : null ),
                    'member_id'    => ( isset( $_POST['e20r_program_memberships'] ) ? sanitize_text_field( $_POST['e20r_program_memberships'] ) : null ),
                );

                if ( $program_id == 'auto' ) {
                    // We'll add this data as a new program
                    dbg( "We're adding: " . print_r( $data, true ) );
                    if ( false === $wpdb->insert( $this->table, $data ) ) {

                        wp_send_json_error( $wpdb->last_error );
                    }

                } elseif ( is_numeric( $program_id ) ) {

                    dbg( "We're updating: " . print_r( $data, true ) );
                    $where = array( 'id' => $program_id );

                    if ( false === $wpdb->update( $this->table, $data, $where ) ) {

                        wp_send_json_error( $wpdb->last_error );
                    }
                }
            }
            else {

                dbg("Deleting record # {$program_id}");

                if ( false === $wpdb->delete( $this->table, array( 'id' => $program_id ) ) ) {

                    wp_send_json_error( $wpdb->last_error );
                }
            }

            wp_send_json_success( $this->view_listPrograms() );
        }
        else {

            wp_send_json_error( 'You do not have permission to add/edit programs' );
        }
    }

    public function postSave( $post_id ) {

        global $current_user, $post, $e20rTracker;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            dbg("e20rPrograms::postSave() - Not saving during autosave");
            return;
        }

        if ( wp_is_post_revision( $post_id ) !== false ) {
            dbg("e20rPrograms::postSave() - Not saving for revisions ({$post_id})");
            return;
        }

        if ( ! in_array( $post->post_type, $e20rTracker->managed_types ) ) {
            dbg("e20rPrograms::postSave() - Not saving for {$post->post_type}");
            return;
        }

        dbg("e20rPrograms::postSave() - " . $e20rTracker->whoCalledMe());

        $program_ids = is_array( $_POST['e20r-tracker-programs'] ) ? $_POST['e20r-tracker-programs'] : null;
        $old_ids = is_array( $_POST['e20r-program-oldid'] ) ? $_POST['e20r-program-oldid'] : null;

        $zeroKey = array_search( 0, $program_ids );

        if ($zeroKey) {
            dbg("e20rPrograms::postSave() - Removing 0 value...");
            unset( $program_ids[ $zeroKey ] );
        }

        $zeroKey = array_search( 0, $old_ids );
        if ($zeroKey) {
            dbg("e20rPrograms::postSave() - Removing 0 value...");
            unset( $old_ids[$zeroKey] );
        }

        dbg("e20rPrograms::postSave() - OldIdArray: " . print_r( $old_ids, true ) );
        dbg("e20rPrograms::postSave() - ProgramIdArray: " . print_r( $program_ids, true ) );

        $already_in = get_post_meta( $post_id, "e20r_tracker_program_ids", true );

        if ( empty( $already_in ) ) {
            dbg( "e20rPrograms::postSave() - No pre-existing program associations for post #{$post_id}" );
            $already_in = array();
        }

        $already_in = array_unique( $already_in );

        if ( empty( $program_ids ) ) {
            dbg( "e20rPrograms::postSave() - No IDs in the program_ids array!");
            $program_ids = array();
        }

        // $program_ids = array_merge( $program_ids, $already_in );
        $program_ids = array_unique( $program_ids );

        foreach ($program_ids as $key => $pid ) {

            $pid = intval($pid);

            dbg("e20rPrograms::postSave() - Processing for program #{$pid}.");

            $user_can = $e20rTracker->userCanEdit( $current_user->ID );

            if (! $user_can ) {

                dbg("e20rPrograms::postSave() - User lacks privileges to update");
                return;
            }

            if (( $pid === 0 ) && ( $old_ids[$key] !== 0 )) {

                dbg("e20rPrograms::postSave() - The program was 'unassigned'");
                $oldKey = array_search( $old_ids[$key], $program_ids );

                if ( $oldKey ) {
                    unset($program_ids[$oldKey]);
                }

                dbg("e20rPrograms::postSave() - New array for program_ids: " . print_r($program_ids, true));
                if ( ( count( $program_ids ) > 1) && ( !in_array( 0, $program_ids ) ) ) {
                    dbg("e20rPrograms::postSave() - Updating program id meta after delete");
                    update_post_meta( $post_id, 'e20r_tracker_program_ids', $program_ids );
                }
                else {
                    dbg("e20rPrograms::postSave() - Deleting program meta");
                    delete_post_meta( $post_id, 'e20r_tracker_program_ids' );
                }
            }
            else {

                dbg( "e20rPrograms::postSave() - Processing program {$pid} for post {$post_id}." );

                if ( ( ! in_array( $pid, $already_in ) ) && ($pid !== 0 ) ) {
                    dbg( "e20rPrograms::postSave() - Adding program ID {$pid}" );
                    $program_ids[] = $pid;
                    update_post_meta( $post_id, 'e20r_tracker_program_ids', $program_ids );
                }

                if ( $pid === 0 ) {
                    continue;
                }
            }
        }
    }
} 