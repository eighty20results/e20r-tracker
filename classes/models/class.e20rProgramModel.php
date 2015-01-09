<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/23/14
 * Time: 9:17 AM
 */

class e20rProgramModel {

    private $id;
    private $program_name;
    private $description;
    private $startime;
    private $endtime;
    private $member_id;
    private $belongs_to;

    private $programTree;

    private $table;
    private $fields;

    public function e20rProgramModel( $program_id = null ) {

        if ( $program_id === null ) {

            global $post;

            if ( $post->post_type == 'e20r_programs' ) {

                $program_id = $post->ID;
            }
        }


        $this->settings = $this->loadSettings( $program_id );

        global $e20rTables;

        $this->table = $e20rTables->getTable('program');
        $this->fields = $e20rTables->getFields('program');

    }

    private function defaultSettings() {

        $settings = new stdClass();
        $settings->starttime = date_i18n( 'Y-m-d h:i:s', current_time('timestamp') );
        $settings->program_shortname = null;
        $settings->endtime = null;
        $settings->sequences = null;

        return $settings;
    }

    public function getFieldValue( $name = 'id' ) {

        return $this->{$name};
    }

    /**
     * Returns an array of all programs merged with their associated settings.
     *
     * @param $statuses string|array - Statuses to return program data for.
     * @return mixed - Array of program objects
     */
    public function loadAllProgramData( $statuses = 'any' ) {

        $query = array(
            'post_type' => 'e20r_programs',
            'post_status' => $statuses,
        );

        wp_reset_query();

        /* Fetch all Sequence posts */
        $program_list = get_posts( $query );

        if ( empty( $program_list ) ) {

            return false;
        }

        dbg("e20rProgramModel::loadAllProgramData() - Loading program settings for " . count( $program_list ) . ' settings');

        foreach( $program_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $program_list[$key] = $loaded_settings;
        }

        return $program_list;
    }

    public function loadProgramData( $id, $statuses = 'any' ) {

        if ( $id == null ) {
            dbg("Error: Unable to load program data. No ID specified!");
            return false;
        }

        $query = array(
            'post_type' => 'e20r_programs',
            'post_status' => $statuses,
            'p' => $id,
        );

        wp_reset_query();

        /* Fetch Programs */
        $program_list = get_posts( $query );

        if ( empty( $program_list ) ) {
            dbg("e20rProgramModel::loadProgramData() - No programs found!");
            return false;
        }

        foreach( $program_list as $key => $data ) {

            $settings = $this->loadSettings( $data->ID );

            $loaded_settings = (object) array_replace( (array)$data, (array)$settings );

            $program_list[$key] = $loaded_settings;
        }


        return $program_list[0];
    }

    /**
     * Save the Program Settings to the metadata table.
     *
     * @param $settings - Array of settings for the specific program.
     *
     * @return bool - True if successful at updating program settings
     */
    public function saveSettings( $settings ) {

        $programId = $settings->id;
        unset($settings->id);

        $settings = (object) array_replace( (array)$this->defaultSettings(), (array)$settings );

        dbg("e20rProgramModel::saveSettings() - Saving program Metadata: " . print_r( $settings, true ) );

        if ( false === update_post_meta( $programId, 'e20r-program-settings', $settings ) ) {

            dbg("e20rProgram::saveSettings() - ERROR saving settings for program with ID: {$programId}");
            return false;
        }

        return true;
    }

    /**
     * Load the Program Settings from the metadata table.
     *
     * @param $id (int) - The ID of the program to load settings for
     *
     * @return mixed - Array of settings if successful at loading the settings, otherwise returns false.
     */
    public function loadSettings( $id ) {

        if ( false === ( $settings = get_post_meta( $id, 'e20r-program-settings', true ) ) ) {

            dbg("e20rProgramModel::loadSettings() - ERROR loading settings for program with ID: {$id}");
        }

        $settings = (object) array_replace( (array)$this->defaultSettings(), (array)$settings );

        return $settings;
    }

    /**
     * Save program settings to the post_meta table.
     *
     * @param $data - Array of data to insert/update/delete.
     */
    public function saveProgram( $data ) {

        global $wpdb;

        if ( ( ! isset( $data['id'] ) ) || ( $data['id'] === null ) ) {

            // New program being added.
            dbg( "e20rProgramModel::saveProgram() - ADDING: " . print_r( $data, true ) );

            if ( false === $wpdb->insert( $this->table, $data ) ) {

                dbg("e20rProgramModel::saveProgram() - ERROR on ADD: {$wpdb->last_error}" );
            }

        } elseif ( $this->recordExists( $data['id'] )
                   && ( isset( $data['program_shortname'] ) ) ) {

            dbg( "e20rProgramModel::saveProgram() - UPDATING: " . print_r( $data, true ) );
            $where = array( 'id' => $data['id'] );

            if ( false === $wpdb->update( $this->table, $data, $where, array( '%d' ) ) ) {

                dbg("e20rProgramModel::saveProgram() - ERROR on UPDATE: {$wpdb->last_error}" );
            }
        } elseif ( ( $this->recordExists( $data['id'] ) ) && ( ! isset($data['program_shortname'])) ) {

            dbg( "e20rProgramModel::saveProgram() - Deleting record ({$data['id']})" );

            if ( false === $wpdb->delete( $this->table, array( 'id' => $data['id'] ) ) ) {

                dbg("e20rProgramModel::saveProgram() - ERROR on DELETE: {$wpdb->last_error}" );
            }
        }
    }

    /********************** OBSOLETE ***************************/

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
                    'belongs_to'    => ( isset( $_POST['e20r_program_belongsto'] ) ? sanitize_text_field( $_POST['e20r_program_belongsto'] ) : null ),
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

    private function getProgram() {

    }

    public function loadPrograminfo( $programs = null, $add_new = true ) {

        global $wpdb;

        dbg("e20rProgramModel::loadProgramInfo() - Loading programs from the DB");
        dbg("e20rProgramModel::loadProgramInfo() - Content of programs: " . print_r($programs, true));

        if ( $programs == null ) {

            $sql = "
                    SELECT *
                    FROM {$this->table}
                    ORDER BY program_name ASC
              ";
        }
        else {

            if ( ( ! is_array( $programs )) && ( $programs != null ) ) {
                dbg("e20rProgram::loadProgramInfo() - programs is a single value: {$programs}");
                $programs = array( $programs );
            }

            $sql = "
                    SELECT *
                    FROM {$this->table}
                    WHERE id IN ( " . implode(',', $programs ) . " )
                    ORDER BY program_name ASC
              ";
        }

        dbg("e20rProgram::loadProgramInfo() - SQL: " . print_r($sql, true) );
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

    public function postSave( $post_id ) {

        global $current_user, $post, $e20rTracker;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            dbg("e20rProgram::postSave() - Not saving during autosave");
            return;
        }

        if ( wp_is_post_revision( $post_id ) !== false ) {
            dbg("e20rProgram::postSave() - Not saving for revisions ({$post_id})");
            return;
        }

        if ( ! in_array( $post->post_type, $e20rTracker->managed_types ) ) {
            dbg("e20rProgram::postSave() - Not saving for {$post->post_type}");
            return;
        }

        dbg("e20rProgram::postSave() - " . $e20rTracker->whoCalledMe());

        $program_ids = is_array( $_POST['e20r-tracker-programs'] ) ? $_POST['e20r-tracker-programs'] : null;
        $old_ids = is_array( $_POST['e20r-program-oldid'] ) ? $_POST['e20r-program-oldid'] : null;

        $zeroKey = array_search( 0, $program_ids );

        if ($zeroKey) {
            dbg("e20rProgram::postSave() - Removing 0 value...");
            unset( $program_ids[ $zeroKey ] );
        }

        $zeroKey = array_search( 0, $old_ids );
        if ($zeroKey) {
            dbg("e20rProgram::postSave() - Removing 0 value...");
            unset( $old_ids[$zeroKey] );
        }

        dbg("e20rProgram::postSave() - OldIdArray: " . print_r( $old_ids, true ) );
        dbg("e20rProgram::postSave() - ProgramIdArray: " . print_r( $program_ids, true ) );

        $already_in = get_post_meta( $post_id, "e20r_tracker_program_ids", true );

        if ( empty( $already_in ) ) {
            dbg( "e20rProgram::postSave() - No pre-existing program associations for post #{$post_id}" );
            $already_in = array();
        }

        $already_in = array_unique( $already_in );

        if ( empty( $program_ids ) ) {
            dbg( "e20rProgram::postSave() - No IDs in the program_ids array!");
            $program_ids = array();
        }

        // $program_ids = array_merge( $program_ids, $already_in );
        $program_ids = array_unique( $program_ids );

        foreach ($program_ids as $key => $pid ) {

            $pid = intval($pid);

            dbg("e20rProgram::postSave() - Processing for program #{$pid}.");

            $user_can = $e20rTracker->userCanEdit( $current_user->ID );

            if (! $user_can ) {

                dbg("e20rProgram::postSave() - User lacks privileges to update");
                return;
            }

            if (( $pid === 0 ) && ( $old_ids[$key] !== 0 )) {

                dbg("e20rProgram::postSave() - The program was 'unassigned'");
                $oldKey = array_search( $old_ids[$key], $program_ids );

                if ( $oldKey ) {
                    unset($program_ids[$oldKey]);
                }

                dbg("e20rProgram::postSave() - New array for program_ids: " . print_r($program_ids, true));
                if ( ( count( $program_ids ) > 1) && ( !in_array( 0, $program_ids ) ) ) {
                    dbg("e20rProgram::postSave() - Updating program id meta after delete");
                    update_post_meta( $post_id, 'e20r_tracker_program_ids', $program_ids );
                }
                else {
                    dbg("e20rProgram::postSave() - Deleting program meta");
                    delete_post_meta( $post_id, 'e20r_tracker_program_ids' );
                }
            }
            else {

                dbg( "e20rProgram::postSave() - Processing program {$pid} for post {$post_id}." );

                if ( ( ! in_array( $pid, $already_in ) ) && ($pid !== 0 ) ) {
                    dbg( "e20rProgram::postSave() - Adding program ID {$pid}" );
                    $program_ids[] = $pid;
                    update_post_meta( $post_id, 'e20r_tracker_program_ids', $program_ids );
                }

                if ( $pid === 0 ) {
                    continue;
                }
            }
        }
    }

    private function recordExists( $id ) {

        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare(
            "
                        SELECT COUNT(id)
                        FROM {$this->table}
                        WHERE id = %d"
        ),
            $id
        );

        return ( $id === false ? false : true );
    }

}