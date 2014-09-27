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

    public function load_program_info( $programId = null ) {

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

        $data = new stdClass();
        $data->id = 0;
        $data->program_name = 'Add a new program';

        $this->programs = $wpdb->get_results( $sql , OBJECT );

        return ( array( $data ) + $this->programs );

    }

    public function viewProgramSelectDropDown() {

        // Generate a select box for the program and highlight the requested ProgramId

        $this->programs = $this->load_program_info( null ); // Get all programs in the DB

        // Todo: create simple select box w/the program identified in $this->programId as 'selected'.
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

        $programs = $this->load_program_info(); // Load all programs & generate a select <div></div>

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
} 