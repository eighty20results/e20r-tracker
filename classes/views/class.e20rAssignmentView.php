<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rAssignmentView extends e20rSettingsView {

    private $assignments = null;

    public function __construct() {

        parent::__construct('assignment', 'e20r_assignments' );
    }

    public function setAssignment( $type, $config ) {

        $this->assignments[$type] = $config;

    }
    
    public function viewSettingsBox( $assignmentData, $programs ) {

        dbg( "e20rAssignmentView::viewSettingsBox() - Supplied data: " . print_r( $assignmentData, true ) );
        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-assignment-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-assignment-id" id="hidden-e20r-assignment-id"
                       value="<?php echo( ( ! empty( $assignmentData ) ) ? $assignmentData->ID : 0 ); ?>">
                <table id="e20r-assignment-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-assignment-assignment_type">Type</label></th>
                        <th class="e20r-label header"><label for="e20r-assignment-maxcount">Max # Check-ins</label></th>
                        <th class="e20r-label header"><label for="e20r-assignment-startdate">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-assignment-enddate">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-assignment-program_ids">Program</label></th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    if ( is_null( $assignmentData->startdate ) ) {

                        $start = '';
                    } else {

                        $start = new DateTime( $assignmentData->startdate );
                        $start = $start->format( 'Y-m-d' );
                    }

                    if ( is_null( $assignmentData->enddate ) ) {

                        $end = '';
                    } else {

                        $end = new DateTime( $assignmentData->enddate );
                        $end = $end->format( 'Y-m-d' );
                    }

                    if ( ( $assignmentData->maxcount <= 0 ) && ( ! empty( $assignmentData->enddate ) ) ) {

                        $interval              = $start->diff( $end );
                        $assignmentData->maxcount = $interval->format( '%a' );
                    }

                    dbg( "Assignment - Start: {$start}, End: {$end}" );
                    ?>
                    <tr id="<?php echo $assignmentData->ID; ?>" class="assignment-inputs">
                        <td>
                            <select id="e20r-assignment-assignment_type" name="e20r-assignment-assignment_type">
                                <option value="0" <?php selected( $assignmentData->assignment_type, 0 ); ?><?php _e("Not configured", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_ACTION; ?>" <?php selected( $assignmentData->assignment_type, CHECKIN_ACTION ); ?>><?php _e("Action", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_ASSIGNMENT; ?>" <?php selected( $assignmentData->assignment_type, CHECKIN_ASSIGNMENT ); ?>><?php _e("Assignment", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_SURVEY; ?>" <?php selected( $assignmentData->assignment_type, CHECKIN_SURVEY ); ?>><?php _e("Survey", "e20rtracker"); ?></option>
                                <option value="<?php echo CHECKIN_ACTIVITY; ?>" <?php selected( $assignmentData->assignment_type, CHECKIN_ACTIVITY ); ?>><?php _e("Activity", "e20rtracker"); ?></option>
                            </select>
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-assignment-maxcount" name="e20r-assignment-maxcount" value="<?php echo $assignmentData->maxcount; ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-assignment-startdate" name="e20r-assignment-startdate" value="<?php echo $start; ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-assignment-enddate" name="e20r-assignment-enddate" value="<?php echo $end; ?>">
                        </td>
                        <td>
                            <select class="select2-container" id="e20r-assignment-program_ids" name="e20r-assignment-program_ids[]" multiple="multiple">
                                <option value="0">Not configured</option>
                                <?php
                                foreach ( $programs as $pgm ) {

                                    $selected = ( in_array( $pgm->ID, $assignmentData->program_ids ) ? ' selected="selected" ' : null); ?>
                                    <option
                                        value="<?php echo $pgm->ID; ?>"<?php echo $selected; ?>><?php echo esc_textarea( $pgm->post_title ); ?>
                                        (#<?php echo $pgm->ID; ?>)
                                    </option>
                                <?php } ?>
                            </select>
                            <style>
                                .select2-container {
                                    min-width: 150px;
                                    max-width: 300px;
                                    width: 90%;
                                }
                            </style>
                            <script>
                                jQuery('#e20r-assignment-program_ids').select2();
                            </script>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }
}