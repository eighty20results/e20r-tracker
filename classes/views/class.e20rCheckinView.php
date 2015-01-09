<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */

class e20rCheckinView {

    private $checkins = null;

    public function e20rCheckinView( $checkinData = null ) {

        $this->checkins = $checkinData;

    }

    public function viewSettingsBox( $checkinData, $programs ) {

        dbg( "e20rCheckinView::viewCheckinSettingsBox() - Supplied data: " . print_r( $checkinData, true ) );
        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-checkin-settings' ); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-checkin-id" id="hidden-e20r-checkin-id"
                       value="<?php echo( ( ! empty( $checkinData ) ) ? $checkinData->ID : 0 ); ?>">
                <table id="e20r-checkin-settings">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-checkin-starttime">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-endtime">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-number_days">Max # Check-ins</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-shortname">Shortname</label></th>
                        <th class="e20r-label header"><label for="e20r-checkin-dripfeed">Program</label></th>
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
                            <input type="date" id="e20r-checkin-starttime" name="e20r-checkin-startdate"
                                   value="<?php echo $start; ?>">
                        </td>
                        <td class="text-input">
                            <input type="date" id="e20r-checkin-endtime" name="e20r-checkin-enddate"
                                   value="<?php echo $end; ?>">
                        </td>
                        <td class="text-input">
                            <input type="date" id="e20r-checkin-maxcount" name="e20r-checkin-maxcount"
                                   value="<?php echo $checkinData->maxcount; ?>">
                        </td>
                        <td class="text-input">
                            <input type="text" id="e20r-checkin-shortname" name="e20r-checkin-short_name" size="25"
                                   value="<?php echo( ( ! empty( $checkinData->short_name ) ) ? $checkinData->short_name : null ); ?>">
                        </td>
                        <td>
                            <select class="select2-container" id="e20r-checkin-program" name="e20r-checkin-program">
                                <option value="0">Not configured</option>
                                <?php

                                foreach ( $programs as $pgm ) { ?>
                                    <option
                                        value="<?php echo $pgm->ID; ?>"<?php selected( $checkinData->program_id, $pgm->ID ); ?>><?php echo esc_textarea( $pgm->post_title ); ?>
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
                                jQuery('#e20r-checkin-program').select2();
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