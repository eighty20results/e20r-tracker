<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckinView extends e20rSettingsView {

    private $checkins = null;

    public function __construct() {

        parent::__construct('checkin', 'e20r_checkins' );
    }

    public function viewCheckInField( $checkin, $habitEntries ) {

        if ( ! is_array( $checkin ) ) {
            $this->setError("No valid checkin data provided");
        }

        ob_start();
        ?>
        <div id="daily-progress-container" class="progress-container">
            <h3><?php _e("Daily Coaching <span>Update</span>", "e20rtracker"); ?></h3>
            <!--<p><span style="background: #e6f4fa;">The Lean Eating program has one simple rule: Every night before you go to bed, take 60 seconds to update us on your progress. Be honest, because we're here to help!</span></p>-->
            <div id="daily-progress-canvas" class="progress-canvas">
                <div class="clear-after">
                    <fieldset class="did-you workout">
                        <legend><?php _e("Did you work out today?", "e20rtracker"); ?></legend>
                        <div>
                            <ul style="width: 300px;">
                                <li><input type="radio" value="1"<?php checked( $checkin['activity_status'], 1 ); ?>name="did-activity-today" id="did-activity-today-radio-1" /><label for="did-activity-today-radio-1"><?php _e("I did my workout", "e20rtracker"); ?></label></li>
                                <li><input type="radio" value="2"<?php checked( $checkin['activity_status'], 2 ); ?>name="did-activity-today" id="did-activity-today-radio-2" /><label for="did-activity-today-radio-2"><?php _e("I was active but didn't complete my workout", "e20rtracker"); ?></label></li>
                                <li><input type="radio" value="0"<?php checked( $checkin['activity_status'], 0 ); ?>name="did-activity-today" id="did-activity-today-radio-3" /><label for="did-activity-today-radio-3"><?php _e("I missed my workout", "e20rtracker"); ?></label></li>
                                <li><input type="radio" value="3"<?php checked( $checkin['activity_status'], 3 ); ?>name="did-activity-today" id="did-activity-today-radio-4" /><label for="did-activity-today-radio-4"><?php _e("There was no workout scheduled", "e20rtracker"); ?></label></li>
                            </ul>
                            <div class="notification-entry-saved" style="width: 300px; display: none;">
                                <div><?php _e("Workout entry saved", "e20rtracker"); ?></div>
                            </div>
                        </div>

                    </fieldset><!--//left-->

                    <fieldset class="did-you habit">
                        <legend style="padding-bottom: 9px;"><?php _e("Did you practice your new habit today?", "e20rtracker"); ?></legend>
                        <div>
                            <p style="margin-bottom: 4px;" id="habit-names"><span class="faded"><?php echo $habitEntries[0]; ?><br/>
                    <?php echo $habitEntries[1]; ?><br/></span>
                                <?php echo $habitEntries[2]; ?></p>

                            <ul style="width: 295px;">
                                <li><input type="radio" value="1"<?php checked( $checkin['action_status'], 1 ); ?>name="did-action-today" id="did-action-today-radio-1" /><label for="did-action-today-radio-1"><?php _e("Yes", "e20rtracker");?></label></li>
                                <li><input type="radio" value="0"<?php checked( $checkin['action_status'], 0 ); ?>name="did-action-today" id="did-action-today-radio-2" /><label for="did-action-today-radio-2"><?php _e("No", "e20rtracker"); ?></label></li>
                                <li><input type="radio" value="0"<?php checked( $checkin['action_status'], 0 ); ?>name="did-action-today" id="did-action-today-radio-2" /><label for="did-action-today-radio-2"><?php _e("No", "e20rtracker"); ?></label></li>
                            </ul>

                            <div class="notification-entry-saved" style="width: 295px; display:none;">
                                <div><?php _e("Habit entry saved", "e20rtracker"); ?></div>
                            </div>
                        </div>
                    </fieldset><!--.did-you //right-->
                </div><!--.clear-after-->

                <hr />

                <fieldset class="notes">
                    <legend><?php _e("Notes", "e20rtracker"); ?></legend>

                    <p><?php _e("Please, feel free to add any notes that you'd like to record for this day. The notes are for your benefit; your coaches won't read them unless you ask them to.", "e20rtracker"); ?></p>

                    <div id="note-display">

                        <div style="margin: 8px;">hidden text</div>
                    </div>

                    <textarea name="value" id="note-textarea"></textarea>

                    <div id="note-display-overflow-pad"></div>

                    <div class="notification-entry-saved" style="width: auto; height: 30px; position: absolute;">
                        <div style="border: 1px solid #84c37e; width: 140px;"><?php _e("Note saved", "e20rtracker"); ?></div>
                    </div>


                    <div class="button-container">
                        <button data-assignment-id="6807271" id="save-note"><?php _e("Save Note", "e20rtracker"); ?></button>
                    </div>

                </fieldset><!--.notes-->


            </div><!--#daily-progress-canvas-->
        </div><!--#daily-progress-container-->
        <?php
        $html = ob_get_clean();
        return $html;
    }

    public function viewSettingsBox( $checkinData, $programs ) {

        dbg( "e20rCheckinView::viewSettingsBox() - Supplied data: " . print_r( $checkinData, true ) );
        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-checkin-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-checkin-id" id="hidden-e20r-checkin-id"
                       value="<?php echo( ( ! empty( $checkinData ) ) ? $checkinData->ID : 0 ); ?>">
                <table id="e20r-checkin-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-checkin-checkin_type">Type</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-starttime">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-endtime">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-number_days">Max # Check-ins</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-program_ids">Program</label></th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <hr width="100%"/>
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ( is_null( $checkinData->startdate ) ) {
                        $start = '';
                    } else {
                        $start = new DateTime( $checkinData->startdate );
                        $start = $start->format( 'Y-m-d' );
                    }

                    if ( is_null( $checkinData->enddate ) ) {
                        $end = '';
                    } else {
                        $end = new DateTime( $checkinData->enddate );
                        $end = $end->format( 'Y-m-d' );
                    }

                    if ( ( $checkinData->maxcount <= 0 ) && ( ! empty( $checkinData->enddate ) ) ) {

                        $interval              = $start->diff( $end );
                        $checkinData->maxcount = $interval->format( '%a' );
                    }

                    dbg( "Checkin - Start: {$start}, End: {$end}" );
                    ?>
                    <tr id="<?php echo $checkinData->ID; ?>" class="checkin-inputs">
                        <td>
                            <select id="e20r-checkin-checkin_type" name="e20r-checkin-checkin_type">
                                <option value="0"><?php _e("Not configured", "e20rtracker"); ?></option>
                                <option value="1"><?php _e("Action", "e20rtracker"); ?></option>
                                <option value="2"><?php _e("Assignment", "e20rtracker"); ?></option>
                                <option value="3"><?php _e("A", "e20rtracker"); ?></option>
                                <option value="4"><?php _e("Survey", "e20rtracker"); ?></option>
                            </select>
                        </td>
                        <td class="text-input">
                            <input type="date" id="e20r-checkin-startdate" name="e20r-checkin-startdate" value="<?php echo $start; ?>">
                        </td>
                        <td class="text-input">
                            <input type="date" id="e20r-checkin-enddate" name="e20r-checkin-enddate" value="<?php echo $end; ?>">
                        </td>
                        <td class="text-input">
                            <input type="number" id="e20r-checkin-maxcount" name="e20r-checkin-maxcount" value="<?php echo $checkinData->maxcount; ?>">
                        </td>
                        <td>
                            <select class="select2-container" id="e20r-checkin-program_ids" name="e20r-checkin-program_ids[]" multiple="multiple">
                                <option value="0">Not configured</option>
                                <?php
                                foreach ( $programs as $pgm ) {

                                    $selected = ( in_array( $pgm->ID, $checkinData->program_id ) ? ' selected="selected" ' : null); ?>
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
                                jQuery('#e20r-checkin-program_ids').select2();
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