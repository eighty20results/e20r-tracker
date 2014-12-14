<?php

class e20rMeasurementViews {

    private $count;
    private $items;
    private $when;

    private $data;
    private $fields;

    private $unitInfo;

    public function e20rMeasurementViews( $when, $data, $fields, $unit ) {

        dbg("When: {$when}");
        dbg("Data: " . print_r( $data, true ) );
        dbg("Fields: " . print_r( $fields, true ) );

        $this->when = $when;
        $this->data = $data;
        $this->fields = $fields;
        $this->unitInfo = $unit;

    }

    public function startProgressForm() {

        ob_start();
        ?>
        <div id="saturday-progress-container" class="progress-container">
            <h3>Your Weekly Progress
                <span>Update</span>
            </h3>
            <div id="e20r-progress-canvas" style="min-height: 100px;">
                <form action="POST">
                <?php wp_nonce_field( 'e20r-tracker-progress', 'e20r-progress-nonce'); ?>
                <table class="e20r-progress-form">
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>
                                <input type="hidden" name="date" id="date" data-measurement-type="date" value="<?php echo $this->when; ?>">
                                <input type="hidden" name="article_id" id="article_id" data-measurement-type="article_id" value="<?php echo $this->data->article_id; ?>">
                                <button class="submit" id="submit-weekly-progress-button">
                                    <div>Save Your Weekly Progress Update</div>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
        <?php
        return ob_get_clean();
    }

    /**
     * @param string $type - The type of measurement to check ('weight', 'girth', 'photo', 'progress')
     *
     * @return bool|int -- True if the measurement field is complete. Otherwise a percentage value (integer)
     */
    private function isComplete( $type ) {
        return false;
    }

    public function endProgressForm() {
        ob_start();?>
                    </tbody>
                </table>
                </form>
            </div> <!-- End of progress-canvas -->
        </div>

        <?php
        return ob_get_clean();
    }

    public function showGirthRow( $girths, $when ) {
        dbg("showGirthRow() - Loading Girth information");
        ob_start();
        ?>
        <tr>
            <td>
                <div class="e20r-number-col">2:</div>
            </td>
            <td class="content validate-girth-measurements" id="girth-measurements">
                <fieldset>
                    <legend>
                        <a href="e20r-tracker-help.php?topic=girths" class="lbp_secondary">
                            <img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="Display the girth Measurement Instructions">
                        </a>Girth Measurements
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help with taking your girth measurements? Check out the
                        <a href="e20r-tracker-help.php?topic=girth" class="lbp_secondary">instructions</a>
                        or download the comprehensive
                        <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
                        in PDF format.
                    </div>
                    <?php echo $this->showChangeLengthUnit(); ?>
                    <?php dbg("showGirthRow() -> Lenght Units changer loaded. Girth Count: " . count($girths) ); ?>

                    <?php foreach( $girths as $order => $girth) { ?>
                        <?php dbg("showGirthRow() -> type: {$girth->type}"); ?>
                        <h5 class="measurement-header">
                            <span class="title"><?php echo ucfirst($girth->type); ?> Girth</span>
                            <img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="measurement-descript-toggle">
                        </h5>

                        <div class="girth-row-container">
                            <div class="girth-image" style='<?php echo $this->bgImage( $girth->type, "girth" ); ?>'></div>
                            <div class="girth-descript-container">
                                <p style="margin-bottom: 20px;" class="girth-description">
                                    <?php echo $girth->descr; ?>
                                </p>
                                <div class="measurement-field-container">
                                    <div class="label-container">
                                        <label>Enter <?php echo ucfirst($girth->type)?> Girth</label>
                                    </div>
                                    <input type="text" <?php echo ( empty( $this->data->{$this->fields['girth_' . strtolower( $girth->type )]} ) ? 'value' : 'value="' . $this->data->{$this->fields['girth_' . strtolower( $girth->type )]} . '"' ); ?> class="highlight-handle measurement-input" data-measurement-type="girth_<?php echo strtolower($girth->type); ?>" style="width: 70px; font-size: 20px; text-align: center;">
                                    <span class="unit length"><?php echo $this->prettyUnit( $this->unitInfo->lengthunits ); ?></span>
                                </div>
                                <div class="measurement-saved-container">
                                    <div class="label-container">
                                        <label>Entered <?php echo ucfirst( $girth->type ); ?> Girth</label>
                                    </div>
                                    <?php dbg("measurementViews() - Data for {$this->fields['girth_' . strtolower($girth->type)]} => {$this->data->{$this->fields['girth_' . strtolower($girth->type)]}}"); ?>
                                    <span class="value"><?php echo ( ! empty( $this->data->{$this->fields['girth_' . strtolower($girth->type)]} ) ? $this->data->{$this->fields['girth_' . strtolower($girth->type)]} : '' ); ?></span>
                                    <span class="unit length"><?php echo ( ! empty( $this->data->{$this->fields['girth_' . strtolower($girth->type)]} ) ? $this->prettyUnit( $this->unitInfo->lengthunits ) : null ); ?></span>
                                    <button class="edit">Change Girth</button>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php dbg("showGirthRow() -> Girth specifics loaded"); ?>
                </fieldset>
            </td>
        </tr>

        <?php
        return ob_get_clean();
    }

    public function generateMeasurementEnd() {

        ob_start();
        ?>
        </fieldset>
        </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    public function addUserData() {


    }

    public function showOtherIndicatorsRow( $when, $showPhotos ) {

        ob_start();
        ?>
        <tr>
            <td id="other-indicators">
                <div class="e20r-number-col"><?php echo ( $showPhotos ? '4' : '3' ); ?>:</div>
            </td>
            <td class="content">
                <fieldset>
                    <legend>
                        Other Indicators of Progress I'm Tracking
                    </legend>
                    <div>
                        <textarea name="essay1" id="essay1" rows="5" cols="73" data-measurement-type="essay1"><?php echo ( ( ! empty( $this->data->{$this->fields['essay1']} ) ) || ($this->data->{$this->fields['essay1']} != 'NULL' ) ? stripslashes($this->data->{$this->fields['essay1']}) : '' ); ?></textarea>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function showProgressQuestionRow( $when, $showPhotos ) {

        ob_start();
        ?>
        <tr id="progress-questionnaire">
            <td >
                <div class="e20r-number-col"><?php echo ( $showPhotos ? '5' : '4' ); ?>:</div>
            </td>
            <td class="content">
                <fieldset>
                    <legend>Progress Questionnaire</legend>
                    <h5>Did my behaviors and actions this week lead to progress towards my goals?</h5>
                    <div>
                        <ul>
                            <li>
                                <input type="radio" name="pquestion-1" id="pquestion-1-1" value="1" data-measurement-type="behaviorprogress" <?php checked( $this->data->{$this->fields['behaviorprogress']} , 1, true ); ?>>
                                <label for="pquestion-1-1">Yes</label>
                            </li>
                            <li>
                                <input type="radio" name="pquestion-1" id="pquestion-1-2" value="2" data-measurement-type="behaviorprogress" <?php checked( $this->data->{$this->fields['behaviorprogress']} , 2, true ); ?>>
                                <label for="pquestion-1-2">No</label>
                            </li>
                        </ul>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();

    }

    public function showPhotoRow( $when, $showPhotos ) {

        dbg("In createPhotoBlock()");

        if ( ! $showPhotos ) {
            return;
        }

        dbg("Expecting to see photos being uploaded");
        ob_start();
        ?>
        <tr>
            <td><div class="e20r-number-col">3:</div></td>
            <td class="content validate-photos" id="photos">
                <fieldset>
                    <legend>
                        <a href="e20r-tracker-help.php?topic=photos" class="lbp_secondary">
                            <img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip=""Display the Photo Instructions">
                        </a>
                        Photos
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help figuring out how to take your progress photos? Check out the
                        <a href="e20r-tracker-help.php?topic=photo" class="lbp_secondary">instructions</a>
                        or download the comprehensive
                        <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
                        in PDF format.
                    </div>
                    <!-- Consider how to integrate the standard WP media uploader and place files somewhere secure (per user). OR use a plugin perhaps? -->
                    <div class="orange-notice" style="margin-bottom: 16px;" id="notice-basic-photo-uploader">
                        Having problems when uploading photos? Try using our <a href="#" class="basic-photo-uploader-toggle">Basic Photo Uploader</a>.
                    </div>
                    <div style="clear: both;"></div>
                    <div class="orange-notice" style="margin-bottom: 16px; display: none;" id="notice-standard-photo-uploader">
                        <a href="#" class="basic-photo-uploader-toggle">Standard Photo Uploader</a>.
                    </div>
                    <!-- TODO: photo uploader table (stuff) will follow here -->
                    <!-- End of photo uploader table -->
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();

    }

    public function showWeightRow( $date ) {
        dbg("Weight Units: " . print_r($this->unitInfo, true));
        ob_start();
        ?>
        <tr>
            <td>
                <div class="e20r-number-col">1:</div>
            </td>
            <td class="content validate-body-weight" id="body-weight">
                <fieldset>
                    <legend>
                        <a href="e20r-tracker-help.php?topic=weight" class="lbp_secondary"><img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="Display the Body Weight Measurement Instructions">
                        </a>
                        Body Weight Measurement
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help with taking your body weight measurements? Check out the
                        <a href="e20r-tracker-help.php?topic=weight" class="lbp_secondary">instructions</a>
                        or download the comprehensive
                        <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
                        in PDF format.
                    </div>
                    <?php echo $this->showChangeWeightUnit(); ?>
                    <div class="measurement-field-container">
                        <div class="label-container">
                            <label>Enter Current Body Weight</label>
                        </div>
                        <input type="text" <?php echo ( empty( $this->data->weight ) ? 'value' : 'value="' . $this->data->weight . '"' ); ?> class="highlight-handle measurement-input" data-measurement-type="weight" style="width: 70px; font-size: 20px; text-align: center;">
                        <span class="unit weight"><?php echo $this->prettyUnit( $this->unitInfo->weightunits ); ?></span>
                    </div>
                    <div class="measurement-saved-container">
                        <div class="label-container">
                            <label>Entered Body Weight</label>
                        </div>
                        <span class="value"><?php echo (! empty( $this->data->weight ) ? $this->data->weight : null ); ?></span>
                        <span class="unit weight"><?php echo $this->prettyUnit( $this->unitInfo->weightunits ); ?></span>
                        <button class="edit">Change Body Weight</button>
                    </div>
                </fieldset>
            </td>
        </tr>
    <?php

        return ob_get_clean();
    }

    private function bgImage( $what, $type ) {
        return 'background-image: url("' . E20R_PLUGINS_URL . '/images/' . $what . '-' . $type . ( $this->unitInfo->gender == 'M' ? '-m.png");' : '-f.png");' );
    }

    private function prettyUnit( $type ) {

        dbg("Pretty Unit request: {$type}");

        switch ( $type ) {
            case 'lbs':
                $descr = 'pounds (lbs)';
                break;
            case 'kg':
                $descr = 'kilogram (kg)';
                break;
            case 'st':
                $descr = 'stone (st)';
                break;
            case 'in':
                $descr = 'inches (in)';
                break;
            case 'cm':
                $descr = 'centimeters (cm)';
                break;
        }

        return $descr;
    }

    private function showChangeWeightUnit() {

        ob_start();
        ?>
        <div style="margin-bottom: 24px;">
            Preferred weight units:
            <span id="preferred-weight-unit" style="font-weight: bold; display: inline;"><?php echo $this->prettyUnit($this->unitInfo->weightunits); ?></span>
            <select id="selected-weight-unit" style="display: none; font-size: 14px; margin-top: 8px;">
                <option value="lbs"<?php selected( $this->unitInfo->weightunits, 'lbs' ); ?>><?php echo $this->prettyUnit('lbs'); ?></option>
                <option value="kg"<?php selected( $this->unitInfo->weightunits, 'kg' ); ?>><?php echo $this->prettyUnit('kg'); ?></option>
                <option value="st"<?php selected( $this->unitInfo->weightunits, 'st' ); ?>><?php echo $this->prettyUnit('st'); ?></option>
            </select>
            (<a class="change-measurement-unit" data-dimension="weight">change this</a>)
        </div>
        <?php
        return ob_get_clean();
    }

    private function showChangeLengthUnit() {

        ob_start();
        ?>
        <div style="margin-bottom: 24px;">
            Preferred length units:
            <span id="preferred-length-unit" style="font-weight: bold; display: inline;"><?php echo $this->prettyUnit( $this->unitInfo->lengthunits ); ?></span>
            <select id="selected-length-unit" style="display: none; font-size: 14px; margin-top: 8px;">
                <option value="in"<?php selected( $this->unitInfo->lengthunits, 'in' ); ?>><?php echo $this->prettyUnit('in'); ?></option>
                <option value="cm"<?php selected( $this->unitInfo->lengthunits, 'cm' ); ?>><?php echo $this->prettyUnit('cm'); ?></option>
            </select>
            (<a class="change-measurement-unit" data-dimension="length">change this</a>)
        </div>
        <?php
        return ob_get_clean();

    }

    public function showChangeBirthdate() {

        ob_start();
        ?>
        <tr id="birth-date">
            <td>&nbsp;</td>
            <td>
                <fieldset>
                    <legend>Birth Date</legend>
                    <p style="clear: left;">
                        We will need your birth date to help us accurately calculate your body fat percentage. This is a one-time request.
                    </p>
                    <select id="bdmonth" onchange="getBirthdate()" style="width: 130px;">
                        <option></option> <?php

                        foreach ( range( 1, 12) as $month ) { ?>
                            <option value="<?php echo date('m', mktime(0, 0, 0, $month, 10)); ?>"><?php echo date('F', mktime(0, 0, 0, $month, 10)); ?></option><?php
                        } ?>

                    </select>
                    <select id="bdday" onchange="getBirthdate()" style="width: 70px;">
                        <option></option> <?php

                        foreach ( range( 1, 31 ) as $day ) { ?>
                            <option value="<?php echo date( 'd', mktime( 0, 0, 0, 1, $day ) ); ?>"><?php echo $day; ?></option><?php
                        }
                        ?>
                    </select>
                    <select id="bdyear" onchange="getBirthdate()" style="width: 80px;">
                        <option></option>
                        <?php

                        $start = date('Y') - 15;
                        $end = date('Y') - 95;

                        foreach ( range( $start, $end ) as $year ) { ?>
                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option><?php
                        }
                        ?>
                    </select>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }
} 