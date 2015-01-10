<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rCheckinView {

    private $checkins = null;

    public function e20rCheckinView( $checkinData = null ) {

        $this->checkins = $checkinData;

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
                        <th class="e20r-label header"><label for="e20r-checkin-starttime">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-endtime">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-number_days">Max # Check-ins</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-program_id">Program</label></th>
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
                            <select class="select2-container" id="e20r-checkin-program_id" name="e20r-checkin-program_id[]" multiple>
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
                                jQuery('#e20r-checkin-program_id').select2();
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