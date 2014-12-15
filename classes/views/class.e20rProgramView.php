<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */

class e20rProgramView {

    private $programs = null;

    public function e20rProgramView( $programData ) {

        $this->programs = $programData;

    }

    public function programSelector( $add_new = true, $selectedId = 0, $listId = null, $disabled = false ) {

        // $this->programs = $this->load_program_info( null, $add_new ); // Get all programs in the DB

        ob_start();
        ?>
        <select name="e20r_choose_program" id="e20r-choose-program<?php echo ( is_null( $listId ) ? "" : "_{$listId}" ); ?>" <?php echo ( $disabled == true ? 'disabled' : ''); ?>>
            <?php

            foreach( $this->programs as $program ) {
                ?><option value="<?php echo esc_attr( $program->id ); ?>"  <?php selected( $selectedId, $program->id, true); ?>><?php echo esc_attr( $program->program_name ); ?></option><?php
            }
            ?>
        </select>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function viewProgramSelectDropDown( $add_new = true ) {

        // Generate a select box for the program and highlight the requested ProgramId

        // $this->programs = $this->load_program_info( null, $add_new ); // Get all programs in the DB

        ob_start();
        ?>
        <label for="e20r_choose_programs">Program</label>
        <span class="e20r-program-select-span">
            <select name="e20r_choose_programs" id="e20r-choose-program">
                <?php

                // dbg("Select List " . print_r( $this->programs, true ) );

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

        //$this->programs = $this->load_program_info( null, true ); // Load all programs & generate a select <div></div>

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

                            dbg("e20rProgramView:: - List: " . print_r( $this->programs, true ) );
                            foreach( $this->programs as $program ) {
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
        // $program_list = $this->load_program_info( null, false );

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

                    if ( count($this->programs) > 0) {

                        foreach ($this->programs as $program) {

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
                                    <a href="#" class="e20r-save-edit-program button">Save</a>
                                </td>
                                <td class="hidden cancel-button-row" id="e20r-td-cancel_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-cancel-edit-program button">Cancel</a>
                                </td>
                                <td class="hidden delete-button-row" id="e20r-td-delete_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-delete-program button">Remove</a>
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


    public function view_programMetaTable() {

        global $post;

        $this->init();

        $pgms = get_post_meta($post->ID, 'e20r_tracker_program_ids', true);
        $pgms = array_unique( $pgms );

        dbg("e20rProgramView:: Read from post meta for {$post->ID}: " . print_r( $pgms, true));

        $belongs_to = array();

        if ( ! empty( $pgms ) ) {

            foreach( $pgms as $id ) {

                if ( $id != 0) {
                    $belongs_to[] = $id;
                }
            }
        }

        $belongs_to[] = 0;

        ob_start();
        ?>
        <div class="seq_spinner vt-alignright"></div>
        <table style="width: 100%;" id="e20r-program-metatable">
            <tbody>
            <?php foreach( $belongs_to as $id ) { ?>
                <?php dbg("e20rProgramView:: - Adding rows for {$id}");?>
                <tr><td><fieldset></td></tr>
                <tr class="select-row-label<?php echo ( $id == 0 ? ' new-program-select-label' : ' program-select-label' ); ?>">
                    <td>
                        <label for="e20r-tracker-memberof-programs"><?php _e("Program:", "e20rtracker"); ?></label>
                    </td>
                </tr>
                <tr class="e20r-select-row-input<?php echo ( $id == 0 ? ' new-e20rprogram-select' : ' program-select' ); ?>">
                    <td class="program-list-dropdown">
                        <select class="<?php echo ( $id == 0 ? 'new-e20rprogram-select' : 'e20r-tracker-memberof-programs'); ?>" name="e20r-tracker-programs[]">
                            <option value="0" <?php echo ( $id == 0 ? 'selected' : '' ); ?>><?php _e("Not assigned", "e20rtracker"); ?></option>
                            <?php
                            // Loop through all of the sequences & create an option list
                            foreach ( $this->programs as $program ) {
                                if ( $program->id != 0 ) {
                                    ?>
                                    <option value="<?php echo $program->id; ?>" <?php echo selected( $program->id, $id ); ?>><?php echo $program->program_name; ?></option><?php
                                }
                            }
                            ?>
                        </select>
                        <input type="hidden" value="<?php echo $id; ?>" class="e20r-program-oldval" name="e20r-program-oldid[]">
                    </td>
                </tr>
                <tr><td></fieldset></td></tr>
            <?php } // Foreach ?>
            </tbody>
        </table>
        <div id="e20r-tracker-new">
            <hr class="e20r-hr" />
            <a href="#" id="e20r-tracker-new-meta" class="button-primary"><?php _e( "Add", "e20rtracker" ); ?></a>
            <a href="#" id="e20r-tracker-new-meta-reset" class="button"><?php _e( "Reset", "e20rtracker" ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function view_programPostMetabox() {

        dbg("e20rProgramView::view_programPostMetabox() - Rendering metabox...");

        ob_start();
        ?>
        <div class="submitbox" id="e20r-program-postmeta">
            <?php wp_nonce_field('e20r-tracker-program-meta', 'e20r-tracker-program-nonce');?>
            <div id="minor-publishing">
                <div id="e20r-postmeta-setprogram">
                    <?php echo $this->view_programMetaTable(); ?>
                </div>
            </div>
        </div>
        <?php

        $metabox = ob_get_clean();

        echo $metabox;

    }


}