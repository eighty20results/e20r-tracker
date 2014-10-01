<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rPrograms {

    public $_tables = array();
    private $programId = null;
    private $type;
    private $programs;
    private $loadedTS;

    public function __construct( $programId = null ) {

        global $wpdb;

        $this->programId = $programId;

        $this->_tables['programs'] = $wpdb->prefix . "e20r_programs";

        $this->programs = $this->load_program_info( $programId );

        if ( ! empty( $this->programs ) ) {
            $this->loadedTS = current_time('timestamp');
        }
    }

    public function load_program_info( $programId = null, $add_new = true ) {

        global $wpdb;

        if ( ( $programId !== null ) && ( is_number( $programId ) ) ) {

            $sql = $wpdb->prepare("
                    SELECT *
                    FROM {$this->_tables['programs']}
                    WHERE id = %d
              ",
                $programId
            );
        }
        else {
            $sql = $wpdb->prepare("
                    SELECT *
                    FROM {$this->_tables['programs']}
              ",
                $programId
            );
        }

        $this->programs = $wpdb->get_results( $sql , OBJECT );

        // Do we want to be able to add new programs to the database

        if ( $add_new ) {

            $data = new stdClass();
            $data->id = 0;
            $data->program_name = 'Add a new program';

            return ( array( $data ) + $this->programs );
        }
        else {
            // Just give the list of existing programs.
            return $this->programs;
        }
    }

    public function programSelector( $add_new = true, $selectedId ) {

        $this->programs = $this->load_program_info( null, $add_new ); // Get all programs in the DB

        ob_start();
        ?>
        <select name="e20r_choose_program" id="e20r_choose_program">
            <?php

            dbg("Select List " . print_r( $this->programs, true ) );

            foreach( $this->programs as $program ) {
                ?><option value="<?php echo esc_attr( $program->id ); ?>"  <?php selected( $selectedId, $program->id, true); ?>><?php echo esc_attr( $program->program_name ); ?></option><?php
            }
            ?>
        </select>
        <?php

        $html = ob_get_clean();

        dbg("Returning select box: " . $html);
        return $html;

    }

    public function viewProgramSelectDropDown( $add_new = true ) {

        // Generate a select box for the program and highlight the requested ProgramId

        $this->programs = $this->load_program_info( null, $add_new ); // Get all programs in the DB

        ob_start();
        ?>
        <label for="e20r_choose_programs">Program</label>
        <span class="e20r-program-select-span">
            <select name="e20r_choose_programs" id="e20r_choose_programs">
                <?php

                dbg("Select List " . print_r( $this->programs, true ) );

                foreach( $this->programs as $program ) {
                ?><option value="<?php echo esc_attr( $this->program->id ); ?>"  <?php selected( $this->programId, $this->program->id, true); ?>><?php echo esc_attr( $this->program->program_name ); ?></option><?php
                }
                ?>
            </select>
        </span>
        <?php

        $html = ob_get_clean();

        return $html;

    }

    public function viewProgramEditSelect() {

        $programs = $this->load_program_info( null, true ); // Load all programs & generate a select <div></div>

        ob_start();

        ?>
        <div id="program-select-div">
            <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_select_programs_nonce' ); ?>
                <div class="e20r-select">
                    <input type="hidden" name="hidden_e20r_program_id" id="hidden_e20r_program_id" value="0" >
                    <label for="e20r_programs">Select Program</label>
                    <span class="e20r-program-select-span">
                        <select name="e20r_programs" id="e20r_programs">
                            <?php

                            dbg("List: " . print_r( $programs, true ) );
                            foreach( $programs as $program ) {
                                ?><option value="<?php echo esc_attr( $program->id ); ?>"  ><?php echo esc_attr( $program->program_name ); ?></option><?php
                            }
                            ?>
                        </select>
                    </span>
                    <span class="e20r-program-select-span"><a href="#e20r_tracker_programs" id="e20r-load-programs" class="e20r-choice-button button"><?php _e('Select', 'e20r-tracker'); ?></a></span>
                    <span class="seq_spinner" id="spin-for-programs"></span>
                </div>
            </form>
        </div>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function view_listPrograms() {

        // Fetch the Checkin Item we're looking to manage
        $program_list = $this->load_program_info( null, false );

        ob_start();
        ?>
        <H1>List of Programs</H1>
        <hr />
            <form action="" method="post">
                <?php wp_nonce_field('e20r-tracker-data', 'e20r_tracker_edit_programs'); ?>
                <div class="e20r-editform">
                    <input type="hidden" name="hidden_e20r_program_id" id="hidden_e20r_program_id" value="<?php echo ( ( ! empty($program) ) ? $program->id : 0 ); ?>">
                    <table id="e20r-list-programs-table">
                        <thead>
                        <tr>
                            <th class="e20r-label header"><label for="e20r-program_id">Edit</label></th>
                            <th class="e20r-label header"><label for="e20r-program_id">ID</label></th>
                            <th class="e20r-label header"><label for="e20r-program_name">Name</label></th>
                            <th class="e20r-label header"><label for="e20r-program-starttime">Starts on</label></th>
                            <th class="e20r-label header"><label for="e20r-program-endtime">Ends on</label></th>
                            <th class="e20r-label header"><label for="e20r-program-descr">Description</label></th>
                            <th class="e20r-label header"><label for="e20r-memberships">Belongs to (Membership)</label></th>
                            <th class="e20r-save-col hidden">Save</td>
                            <th class="e20r-cancel-col hidden">Cancel</td>
                            <th class="e20r-delete-col hidden">Remove</td>
                            <th class="e20r-label header hidden"></td>
                        </tr>
                        <tr>
                            <td colspan="11"><hr/></td>
                            <!-- select for choosing the membership type to tie this check-in to -->
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        if ( count($program_list) > 0) {

                            foreach ($program_list as $program) {

                            if ( is_null( $program->starttime ) ) {
                                $start = '';
                            } else {
                                $start = new DateTime( $program->starttime );
                                $start = $start->format( 'Y-m-d' );
                            }

                            if ( is_null( $program->endtime ) ) {
                                $end = '';
                            } else {
                                $end = new DateTime( $program->endtime );
                                $end = $end->format( 'Y-m-d' );
                            }

                            $pid = $program->id;

                            dbg( "Program - Start: {$start}, End: {$end}" );
                            ?>
                            <tr id="<?php echo $pid; ?>" class="program-inputs">
                                <td class="text-input">
                                    <input type="checkbox" name="edit_<?php echo $pid; ?>" id="edit_<?php echo $pid ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-program_id_<?php echo $pid; ?>" disabled name="e20r_program_id" size="5" value="<?php echo( ( ! empty( $program->id ) ) ? $program->id : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-program_name_<?php echo $pid; ?>" disabled name="e20r_program_name" size="25" value="<?php echo( ( ! empty( $program->program_name ) ) ? $program->program_name : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-program-starttime_<?php echo $pid; ?>" disabled name="e20r_program_starttime" value="<?php echo $start; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-program-endtime_<?php echo $pid; ?>" disabled name="e20r_program_endtime" value="<?php echo $end; ?>">
                                </td>
                                <td class="text-descr">
                                    <textarea class="expand" id="e20r-program-descr_<?php echo $pid; ?>" disabled name="e20r_program_descr" rows="1" wrap="soft"><?php echo ( ! empty( $program->description ) ) ? $program->description : null; ?></textarea>
                                </td>
                                <td class="select-input">
                                    <?php echo $this->view_selectMemberships( $program->member_id, $pid ); ?>
                                </td>
                                <td class="hidden save-button-row" id="e20r-td-save_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-save-edit button">Save</a>
                                </td>
                                <td class="hidden cancel-button-row" id="e20r-td-cancel_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-cancel-edit button">Cancel</a>
                                </td>
                                <td class="hidden delete-button-row" id="e20r-td-delete_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-delete button">Remove</a>
                                </td>
                                <td class="hidden-input">
                                    <input type="hidden" class="hidden_id" value="<?php echo $pid; ?>">
                                </td>
                            </tr>
                            <?php
                            }
                        }
                        else { ?>
                            <tr>
                                <td colspan="7">No programs found in the database. Please add a new program by clicking the "Add New" button.</td>
                            </tr><?php
                        }
                        ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7"><hr/></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="add-new" style="text-align: left;"><a class="e20r-button button" id="e20r-add-new-program" href="#">Add New</a></td>
                            </tr>
                            <tr id="add-new-program" class="hidden">
                                <td class="text-input"><input type="checkbox" disabled name="edit" id="edit"></td>
                                <td class="text-input"><input type="text" id="e20r-program_id" name="e20r_program_id" disabled size="5" value="auto"></td>
                                <td class="text-input"><input type="text" id="e20r-program_name" name="e20r_program_name" size="25" value=""></td>
                                <td class="text-input"><input type="date" id="e20r-program-starttime" name="e20r_program_starttime" value=""></td>
                                <td class="text-input"><input type="date" id="e20r-program-endtime" name="e20r_program_endtime" value=""></td>
                                <td class="text-descr"><textarea class="expand" id="e20r-program-descr" name="e20r_program_descr" rows="1" wrap="soft"></textarea></td>
                                <td class="select-input"><?php echo $this->view_selectMemberships( 0, null ); ?></td>
                                <td class="save"><a class="e20r-button button" id="e20r-save-new-program" href="#">Save</a></td>
                                <td class="cancel"><a class="e20r-button button" id="e20r-cancel-new-program" href="#">Cancel</a></td>
                                <td class="hidden"><!-- Nothing here, it's for the delete/remove button --></td>
                                <td class="hidden-input"><input type="hidden" class="hidden_id" value="<?php echo $pid; ?>"></td>
                            </tr>
                            </tfoot>
                    </table>

                </div>
            </form>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function view_selectMemberships( $mId, $rowId = null ) {

        if ( function_exists( 'pmpro_getAllLevels' ) ) {

           $levels = pmpro_getAllLevels(false, true);

            ob_start();

            if ( ! empty( $rowId ) ) {

                ?><select name="e20r-memberships_<?php echo $rowId; ?>" id="e20r-memberships_<?php echo $rowId; ?>" disabled><?php
            }
            else {

                ?><select name="e20r-memberships" id="e20r-memberships"><?php
            }

            foreach ( $levels as $level ) { ?>

                <option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $level->id, $mId ); ?>><?php echo esc_attr( $level->name ); ?></option><?php
            } ?>

            </select>
            <?php
            $html = ob_get_clean();
        }

        return $html;
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
                    if ( false === $wpdb->insert( $this->_tables['programs'], $data ) ) {

                        wp_send_json_error( $wpdb->last_error );
                    }

                } elseif ( is_numeric( $program_id ) ) {

                    dbg( "We're updating: " . print_r( $data, true ) );
                    $where = array( 'id' => $program_id );

                    if ( false === $wpdb->update( $this->_tables['programs'], $data, $where ) ) {

                        wp_send_json_error( $wpdb->last_error );
                    }
                }
            }
             else {

                 dbg("Deleting record # {$program_id}");

                 if ( false === $wpdb->delete( $this->_tables['programs'], array( 'id' => $program_id ) ) ) {

                     wp_send_json_error( $wpdb->last_error );
                 }
             }

            wp_send_json_success( $this->view_listPrograms() );
        }
        else {

            wp_send_json_error( 'You do not have permission to add/edit programs' );
        }
    }
} 