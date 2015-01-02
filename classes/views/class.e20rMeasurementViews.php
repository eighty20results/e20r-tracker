<?php

class e20rMeasurementViews {

    private $count;
    private $items;
    private $when;

    private $data;
    private $fields;


    public function e20rMeasurementViews( $when, $data ) {

        global $e20rTables;

        $this->when = $when;
        $this->data = $data;
        $this->fields = $e20rTables->getFields( 'measurements' );

        dbg("When: {$this->when}");
        dbg("Data: " . print_r( $this->data, true ) );
        dbg("Fields: " . print_r( $this->fields, true ) );

    }

    public function startProgressForm() {

        // $this->load_scripts();
        global $e20rArticle;

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
                                <input type="hidden" name="date" id="date" data-measurement-type="date" value="<?php echo $e20rArticle->releaseDate(); ?>">
                                <input type="hidden" name="article_id" id="article_id" data-measurement-type="article_id" value="<?php echo $e20rArticle->getID(); ?>">
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

    public function showGirthRow( $girths ) {

        global $e20rClient;

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
                    <?php dbg("showGirthRow() -> Length Units changer loaded. Girth Count: " . count($girths) ); ?>

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
                                    <input type="text" <?php echo ( empty( $this->data->{$this->fields[ 'girth_' . strtolower( $girth->type ) ]} ) ? 'value=""' : 'value="' . $this->data->{$this->fields[ 'girth_' . strtolower( $girth->type ) ]} . '"' ); ?> class="highlight-handle measurement-input" data-measurement-type="<?php echo 'girth_' . strtolower($girth->type); ?>" style="width: 70px; font-size: 20px; text-align: center;">
                                    <span class="unit length"><?php echo $this->prettyUnit( $e20rClient->getLengthUnit() ); ?></span>
                                </div>
                                <div class="measurement-saved-container">
                                    <div class="label-container">
                                        <label>Entered <?php echo ucfirst( $girth->type ); ?> Girth</label>
                                    </div>
                                    <?php dbg("measurementViews() - Data for {$this->fields[ 'girth_' . strtolower($girth->type) ]} => {$this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]}}"); ?>
                                    <span class="value"><?php echo ( ! empty( $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} ) ? $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} : '' ); ?></span>
                                    <span class="unit length"><?php echo ( ! empty( $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} ) ? $this->prettyUnit( $e20rClient->getLengthUnit() ) : null ); ?></span>
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

    public function showOtherIndicatorsRow( $showPhotos ) {

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

    public function showProgressQuestionRow( $showPhotos ) {

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

    public function showPhotoRow( $showPhotos ) {

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
                    <div style="clear: both;"></div>
                    <p class="hide-if-no-js">
                        <div class="uploader">
                        <table id="photo-upload-table" class="advanced">
                            <thead>
                            <tr class="e20r-noline">
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title">Front Photo</div>
                                        <button id="photo-upload-front">Select Image: Front</button>
                                    </div>
                                </th>
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title">Side Photo</div>
                                        <button id="photo-upload-side">Select Image: Side</button>
                                    </div>
                                </th>
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title">Back Photo</div>
                                        <button id="photo-upload-back">Select Image: Back</button>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="e20r-noline">
                                <td>
                                    <div class="photo-upload-notifier">
                                        Photo Saved
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-front-url-hidden" id="photo-front-url-hidden" value="<?php echo ( is_null( $this->data->{$this->fields["front_image"]} ) ? null : $this->data->{$this->fields["front_image"]} ); ?>" />
                                        <div class="descript-overlay">Front</div>
                                        <img id="photo-front" src="<?php echo $this->loadImage( 'front' ); ?>" class="photo<?php echo ( is_null( $this->data->{$this->fields["front_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="photo-upload-notifier">
                                        Photo Saved
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-side-url-hidden" id="photo-side-url-hidden" value="<?php echo ( is_null( $this->data->{$this->fields["side_image"]} ) ? null : $this->data->{$this->fields["side_image"]} ); ?>" />
                                        <div class="descript-overlay">Side</div>
                                        <img id="photo-side" src="<?php echo $this->loadImage( 'side' ); ?>" class="photo<?php echo ( is_null( $this->data->{$this->fields["side_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="photo-upload-notifier">
                                        Photo Saved
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-back-url-hidden" id="photo-back-url-hidden" value="<?php echo ( is_null( $this->data->{$this->fields["back_image"]} ) ? null : $this->data->{$this->fields["back_image"]} ); ?>" />
                                        <div class="descript-overlay">Back</div>
                                        <img id="photo-back" src="<?php echo $this->loadImage( 'front' ); ?>" class="photo<?php echo ( is_null( $this->data->{$this->fields["back_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                            </tr class="e20r-noline">
                            </tbody>
                            <tfoot>
                            <tr>
                                <td><a style="display: none;" href="javascript:" class="delete-photo front" data-orientation="front">Delete Front Image</a></td>
                                <td><a style="display: none;" href="javascript:" class="delete-photo side" data-orientation="side">Delete Side Image</a></td>
                                <td><a style="display: none;" href="javascript:" class="delete-photo back" data-orientation="back">Delete Back Image</a></td>
                            </tr>
                            </tfoot>
                        </table>
                        </div>
                    </p>
                    <!-- End of photo uploader table -->
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();

    }

    private function loadImage( $side ) {

        $id = ( isset( $this->data->{$this->fields[$side . "_image"]} ) ?  $this->data->{$this->fields[$side . "_image"]} : null );

        if ( ( $url = wp_get_attachment_thumb_url( $id ) ) === false ) {

            dbg( "e20rMeasurementViews::loadImage() - No image ID saved. Loading placeholder" );
            $url = E20R_PLUGINS_URL . "/images/no-image-uploaded.jpg";
        }

        dbg("e20rMeasurementviews::loadImage() - Loading: {$url}");
        return $url;

    }
    public function showWeightRow() {

        global $e20rClient;

        dbg("Weight Units: " . print_r($e20rClient->getWeightUnit(), true));
        ob_start();
        ?>
        <tr>
            <td>
                <div class="e20r-number-col">1:</div>
            </td>
            <td class="content validate-body-weight" id="body-weight">
                <fieldset>
                    <legend>
                        <a href="e20r-tracker-help.php?topic=weight" class="lbp_secondary"><img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="Display the Body Weight Measurement Instructions"></a>
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
                        <input type="text" <?php echo ( empty( $this->data->weight ) ? 'value=""' : 'value="' . $this->data->weight . '"' ); ?> class="highlight-handle measurement-input" data-measurement-type="weight" style="width: 70px; font-size: 20px; text-align: center;">
                        <span class="unit weight"><?php echo $this->prettyUnit( $e20rClient->getWeightUnit() ); ?></span>
                    </div>
                    <div class="measurement-saved-container">
                        <div class="label-container">
                            <label>Entered Body Weight</label>
                        </div>
                        <span class="value"><?php echo (! empty( $this->data->weight ) ? $this->data->weight : null ); ?></span>
                        <span class="unit weight"><?php echo (! empty( $this->data->weight ) ? $this->prettyUnit( $e20rClient->getWeightUnit() ) : null ); ?></span>
                        <button class="edit">Change Body Weight</button>
                    </div>
                </fieldset>
            </td>
        </tr>
    <?php

        return ob_get_clean();
    }

    private function bgImage( $what, $type ) {
        return 'background-image: url("' . E20R_PLUGINS_URL . '/images/' . $what . '-' . $type . ( $this->unitInfo->gender == 'F' ? '-f.png");' : '-m.png");' );
    }

    private function prettyUnit( $type ) {

       // dbg("Pretty Unit request: {$type}");

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
            case 'st_uk':
                $descr = 'stone (st) - (UK)';
                break;
            case 'in':
                $descr = 'inches (in)';
                break;
            case 'cm':
                $descr = 'centimeters (cm)';
                break;
            default:
                $descr = '';
        }

        return $descr;
    }

    private function showChangeWeightUnit() {

        global $e20rClient;

        ob_start();
        ?>
        <div style="margin-bottom: 24px;">
            Preferred weight units:
            <span id="preferred-weight-unit" style="font-weight: bold; display: inline;"><?php echo $this->prettyUnit($e20rClient->getWeightUnit()); ?></span>
            <select id="selected-weight-unit" style="display: none; font-size: 14px; margin-top: 8px;">
                <option value="lbs"<?php selected( $e20rClient->getWeightUnit(), 'lbs' ); ?>><?php echo $this->prettyUnit('lbs'); ?></option>
                <option value="kg"<?php selected( $e20rClient->getWeightUnit(), 'kg' ); ?>><?php echo $this->prettyUnit('kg'); ?></option>
                <option value="st"<?php selected( $e20rClient->getWeightUnit(), 'st' ); ?>><?php echo $this->prettyUnit('st'); ?></option>
                <option value="st"<?php selected( $e20rClient->getWeightUnit(), 'st_uk' ); ?>><?php echo $this->prettyUnit('st_uk'); ?></option>
            </select>
            (<a class="change-measurement-unit" data-dimension="weight">change this</a>)
        </div>
        <?php
        return ob_get_clean();
    }

    private function showChangeLengthUnit() {

        global $e20rClient;
        ob_start();
        ?>
        <div style="margin-bottom: 24px;">
            Preferred length units:
            <span id="preferred-length-unit" style="font-weight: bold; display: inline;"><?php echo $this->prettyUnit( $e20rClient->getLengthUnit() ); ?></span>
            <select id="selected-length-unit" style="display: none; font-size: 14px; margin-top: 8px;">
                <option value="in"<?php selected( $e20rClient->getLengthUnit(), 'in' ); ?>><?php echo $this->prettyUnit('in'); ?></option>
                <option value="cm"<?php selected( $e20rClient->getLengthUnit(), 'cm' ); ?>><?php echo $this->prettyUnit('cm'); ?></option>
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