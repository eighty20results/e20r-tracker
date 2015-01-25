<?php

class e20rMeasurementViews {

    private $id;
    private $count;
    private $items;
    private $when;

    private $model;
    private $data;
    private $fields;


    public function e20rMeasurementViews() {

        global $e20rTables;

        $this->fields = $e20rTables->getFields( 'measurements' );
    }

    public function init( $when = null, $data = null, $who = null ) {

        $this->id = $who;
        $this->when = $when;
        $this->data = $data;
    }
    public function startProgressForm( $articleId, $programId ) {

        // $this->load_scripts();

        ob_start();
        ?>
        <div id="saturday-progress-container" class="progress-container">
            <h3>Your Weekly Progress
                <span>Update</span>
            </h3>
            <div id="e20r-progress-canvas" style="min-height: 100px;">
                <form method="POST">
                <?php wp_nonce_field( 'e20r-tracker-progress', 'e20r-progress-nonce'); ?>
                <table class="e20r-progress-form">
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>
                                <input type="hidden" name="date" id="date" data-measurement-type="date" value="<?php echo $this->when; ?>">
                                <input type="hidden" name="article_id" id="article_id" data-measurement-type="article_id" value="<?php echo $articleId; ?>">
                                <input type="hidden" name="program_id" id="program_id" data-measurement-type="program_id" value="<?php echo $programId; ?>">
                                <button class="submit" id="submit-weekly-progress-button">
                                    <div>Save Your Weekly Progress Update</div>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                </form>
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

    public function showOtherIndicatorsRow( $showPhotos ) {

        ob_start();
        ?>
        <tr>
            <td id="other-indicators">
                <div class="e20r-number-col"><?php echo ( empty($showPhotos) ? '4' : '3' ); ?>:</div>
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
                <div class="e20r-number-col"><?php echo ( empty($showPhotos)? '5' : '4' ); ?>:</div>
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

        if ( empty($showPhotos) ) {
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
                                        <input type="hidden" name="photo-front-url-hidden" id="photo-front-url-hidden" value="<?php echo ( empty( $this->data->{$this->fields["front_image"]} ) ? null : $this->data->{$this->fields["front_image"]} ); ?>" />
                                        <div class="descript-overlay">Front</div>
                                        <img id="photo-front" src="<?php echo $this->loadImage( 'front' ); ?>" class="photo<?php echo ( empty( $this->data->{$this->fields["front_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="photo-upload-notifier">
                                        Photo Saved
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-side-url-hidden" id="photo-side-url-hidden" value="<?php echo ( empty( $this->data->{$this->fields["side_image"]} ) ? null : $this->data->{$this->fields["side_image"]} ); ?>" />
                                        <div class="descript-overlay">Side</div>
                                        <img id="photo-side" src="<?php echo $this->loadImage( 'side' ); ?>" class="photo<?php echo ( empty( $this->data->{$this->fields["side_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="photo-upload-notifier">
                                        Photo Saved
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-back-url-hidden" id="photo-back-url-hidden" value="<?php echo ( empty( $this->data->{$this->fields["back_image"]} ) ? null : $this->data->{$this->fields["back_image"]} ); ?>" />
                                        <div class="descript-overlay">Back</div>
                                        <img id="photo-back" src="<?php echo $this->loadImage( 'front' ); ?>" class="photo<?php echo ( empty( $this->data->{$this->fields["back_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                            </tr class="e20r-noline">
                            </tbody>
                            <tfoot>
                                <td><a href="javascript:" class="delete-photo front" data-orientation="front">Delete Front Image</a></td>
                                <td><a href="javascript:" class="delete-photo side" data-orientation="side">Delete Side Image</a></td>
                                <td><a href="javascript:" class="delete-photo back" data-orientation="back">Delete Back Image</a></td>
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

        dbg( "e20rMeasurementViews::loadImage() - Looking for {$side} image..." );

        $id = ( isset( $this->data->{$this->fields[$side . "_image"]} ) ?  $this->data->{$this->fields[$side . "_image"]} : null );

        dbg( "e20rMeasurementViews::loadImage() - Locate attachment ID {$id}..." );

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

    public function viewTabbedProgress( $progressEntries, $dimensions ) {

        ob_start();
        ?>
<!--        <style type="text/css">
            #status-tabs {
                position: relative;
                min-height: 200px;
                max-height: 95%;
                height: 1024px;
                min-width: 80%;
                width: 95%;
                clear: both;
                margin: 10px 0;
            }
        </style> -->
        <div id="status-tabs">
            <ul>
        <?php
            $count = 1;
            foreach( $progressEntries as $label => $contentHtml ) {
            ?><li><a href="#tabs-<?php echo $count++; ?>"><?php echo $label; ?></a></li><?php
            }
        ?>
            </ul>
            <div id="spinner" class="e20r-spinner"></div>
            <?php
                $count = 1;
                foreach( $progressEntries as $label => $contentHtml ) {
                ?>
                    <div id="tabs-<?php echo $count++; ?>">
                        <?php echo $contentHtml; ?>
                    </div>
                <?php
                }
                ?>
        </div> <!-- tabs div -->
        <?php
    }

    public function viewTableOfMeasurements( $clientId = null, $measurements, $dimensions = null, $tabbed = true, $admin = true ) {
        // TESTING: using $clientId = 12;
        // $clientId = 12;
        global $e20rTables;
        global $e20rClient;
        global $current_user;

        if ( $dimensions === null ) {

            $dimensions = array( 'width' => '650', 'height' => '500', 'htype' => 'px', 'wtype' => 'px' );
        }

        if ( $dimensions['htype'] != '%') {

            $maxHeight = ( ( (int) $dimensions['height']) + 95 );
            $height =   ( ( (int) $dimensions['height']) + 75 );
        }
        else {
            $maxHeight = ( (int)$dimensions['height'] + 10 ) <= 100 ? ( (int)$dimensions['height'] + 10 ) : $dimensions['height'];
            $height = ( (int)$dimensions['height'] + 5 ) <= 100 ? ( (int)$dimensions['height'] + 5 ) : $dimensions['height'];
        }

        if ( $dimensions['wtype'] != '%') {

            $minWidth = ( (int) $dimensions['width'] + 15 );
            $width = ( (int) $dimensions['width'] + 95 );
        }
        else {
            $minWidth = ( ( (int) $dimensions['width'] - 5 ) <= 100 ? ( (int) $dimensions['width'] - 5 ) : $dimensions['width']);
            $width = (  ( (int) $dimensions['width'] + 5 ) <= 100 ? ( (int) $dimensions['width'] + 5 ) : $dimensions['width']);
        }

        $maxHeight = $maxHeight . $dimensions['htype'];
        $height = $height . $dimensions['htype'];

        $minWidth = $minWidth . $dimensions['wtype'];
        $width = $width . $dimensions['wtype'];

        $this->id = $clientId;
        $user = get_user_by( 'id', $this->id );

        $reloadBtn = '
            <div id="e20r_reload_btn">
                <a href="#e20r_tracker_data" id="e20r-reload-measurements" class="e20r-choice-button button" > ' . __("Reload Measurements", "e20r-tracker") . '</a>
            </div>
        ';

        if ( count( $measurements ) < 1 ) {

            ob_start();
            // echo $reloadBtn;
            ?>
            <div id="e20r_errorMsg"><em><?php sprintf(__("No measurements found for %s", "e20rtracker"), $user->first_name . " " . $user->last_name ); ?></em></div>
            <?php
            $html = ob_get_clean();
        }
        else {

            ob_start();

            ?>
            <input type="hidden" name="h_dimension" id="h_dimension" value="<?php echo $dimensions['height']; ?>">
            <input type="hidden" name="h_dimension_type" id="h_dimension_type" value="<?php echo $dimensions['htype']; ?>">
            <input type="hidden" name="w_dimension" id="w_dimension" value="<?php echo $dimensions['width']; ?>">
            <input type="hidden" name="w_dimension_type" id="w_dimension_type" value="<?php echo $dimensions['wtype']; ?>">
            <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_client_detail_nonce'); ?>
        <?php if ( $tabbed ): ?>
            <div id="inner-tabs">
                <ul>
                    <li><a href="#inner-tab-1">Weight History</a></li>
                    <li><a href="#inner-tab-2">Total Girth</a></li>
                </ul>
                <div id="inner-tab-1">
                    <?php echo ( $admin ? '<h4 class="e20r_progress_text">' . sprintf( __("Loading weight history graph for %s", "e20rtracker"), $user->display_name) . "</h4>" : '' ); ?>
                    <div id="weight_chart" style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
                </div>
                <div id="inner-tab-2">
                    <?php echo ( $admin ? '<h4 class="e20r_progress_text">' . sprintf( __("Loading total girth graph for %s", "e20rtracker"), $user->display_name) . "</h4>" : '' ); ?>
                    <div id="girth_chart" style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
                </div>
            </div> <!-- tabs div -->
                <!-- Load tabs script here because this content is dynamic -->
                <script type="text/javascript">
                    <!-- jQuery(function() {
                        console.log("Configure tabs for weight/girth");
                        jQuery("#inner-tabs").tabs();

                        jQuery('#inner-tabs').on('tabsactivate', function(event, ui) {

                            if ( (ui.newTab.index() === 0) && ( progMeasurements.wPlot._drawCount === 0 ) ) {

                                console.log("Redrawing wPlot");
                                progMeasurements.wPlot.replot();
                            }
                            else if ( (ui.newTab.index() === 1 ) && (progMeasurements.gPlot._drawCount === 0) ) {

                                console.log("Redrawing gPlot");
                                progMeasurements.gPlot.replot();
                            }
                        });

                        jQuery('.load_progress_data').on("click", function() {
                            progMeasurements.loadProgressPage( this );
                        });

                    }); -->
                </script>
            <?php else: ?>
            <div id="weight_chart" style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
            <div id="girth_chart" style="height: <?php echo $height; ?>;width: <?php echo $width; ?>;"></div>
        <?php endif; ?>
            <hr class="e20r-big-hr" />
            <div class="e20r-measurements-container">
                <h4>Measurements for <?php echo $user->first_name; ?></h4>
                <a class="close" href="#">X</a>
                <div class="quick-nav">
                    <table class="e20r-measurement-table">
                        <thead>
                            <tr>
                                <th class="e20r_mHead rotate"></th>
                                <th class="e20r_mHead rotate"><div><span>Weight (<?php echo $e20rClient->getWeightUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Neck (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Shoulder (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Chest (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Arm (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Waist (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Hip (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Thigh (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Calf (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Total Girth (<?php echo $e20rClient->getLengthUnit(); ?>)</span></div></th>
                                <th class="e20r_mHead rotate"><div><span>Photo</span></div></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            $counter = 0;

                            foreach ( $measurements as $key => $measurement ) {

                                $measurement->girth = (
                                    $measurement->neck + $measurement->shoulder + $measurement->chest + $measurement->arm +
                                    $measurement->waist + $measurement->hip + $measurement->thigh + $measurement->calf
                                );

                                $when = date_i18n( "Y-m-d", strtotime( $measurement->recorded_date ) );
                                $showLink = ( $clientId == $current_user->ID ? true : false);

                                ?>
                            <tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?>">
                                <td class="e20r_mData">
                                    <div class="date">
                                    <!-- <span> -->
                                        <?php
                                        if ( $showLink ) {
                                            ?>
                                            <form method="POST" class="article_data" action="<?php echo URL_TO_PROGRESS_FORM; ?>">
                                                <input type="hidden" name="e20r-progress-form-date" class="e20r-progress-form-date" data-measurement-type="date" value="<?php echo $when; ?>">
                                                <input type="hidden" name="e20r-progress-form-article" class="e20r-progress-form-article" data-measurement-type="article_id" value="<?php echo $measurement->article_id; ?>">
                                                <!-- <input type="hidden" name="program_id" class="program_id" data-measurement-type="program_id" value="<?php echo $measurement->program_id; ?>"> -->
                                                <a href="<?php echo $when; ?>" onclick="jQuery(this).parent().submit();" class="load_progress_data" target="_blank" alt="<?php _e( "Opens in a separate window", 'e20r-tracker' ); ?>">
                                                    <?php echo date_i18n( "M j, Y", strtotime( $measurement->recorded_date ) ); ?>
                                                </a>
                                            </form>
                                        <?php
                                        }
                                        else {
                                            ?>
                                                <?php echo date_i18n( "M j, Y", strtotime( $measurement->recorded_date ) ); ?>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $measurement->recorded_date ) ); ?></div>
                                </td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->weight ) || ( $measurement->weight == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->weight, 1 ), 1 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->neck ) || ( $measurement->neck == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->neck, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->shoulder ) || ( $measurement->shoulder == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->shoulder, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->chest ) || ( $measurement->chest == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->chest, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->arm ) || ( $measurement->arm == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->arm, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->waist ) || ( $measurement->waist == 0 )? '&mdash;' : number_format( (float) round( $measurement->waist, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->hip ) || ( $measurement->hip == 0 )? '&mdash;' : number_format( (float) round( $measurement->hip, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->thigh ) || ( $measurement->thigh == 0 )? '&mdash;' : number_format( (float) round( $measurement->thigh, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->calf ) || ( $measurement->calf == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->calf, 2 ), 2 ) ); ?></td>
                                <td class="e20r_mData"><?php echo ( is_null( $measurement->girth ) || ( $measurement->girth == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->girth, 2 ), 2 ) ); ?></td>
                                <td class="smallPhoto"><?php echo $this->getProgressPhoto( $measurement, $user->ID, $key ); ?></td>
                            </tr>
                            <?php
                                $counter ++;
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
            if ( ! $e20rTables->isBetaClient() ) {
                ?>
                <!-- Load 'Other Progress Indicators' (the user isn't a beta group member) -->
                <div class="e20r-measurements-container">
                    <h4>Other Progress Indicators</h4>
                    <a class="close" href="#">X</a>
                    <div class="quick-nav other">
                        <table class="e20r-measurement-table">
                            <tbody>
                            <?php
                                $counter = 0;

                                foreach ( $measurements as $key => $measurement ) { ?>

                                    $when = date_i18n( "Y-m-d", strtotime( $measurement->recorded_date ) );
                                    $showLink = ( $clientId == $current_user->ID ? true : false);

                                    <tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?>">
                                    <td class="measurement-date">
                                        <div class="date">
                                            <form method="POST">
                                                <input type="hidden" name="date" id="date" data-measurement-type="date" value="<?php echo $when; ?>">
                                                <input type="hidden" name="article_id" id="article_id" data-measurement-type="article_id" value="<?php echo $measurement->article_id; ?>">
                                                <input type="hidden" name="program_id" id="program_id" data-measurement-type="program_id" value="<?php echo $measurement->program_id; ?>">
                                            </form>
                                                <!-- <span> -->
                                            <?php
                                            if ( $showLink ) {
                                                ?>
                                                    <a href="#load_progress" target="_blank" alt="<?php _e( "Opens in a separate window", 'e20r-tracker' ); ?>">
                                                        <?php echo date_i18n( 'M j, Y', strtotime( $measurement->recorded_date ) ); ?>
                                                    </a>
                                            <?php
                                            }
                                            else {
                                                ?>
                                                <?php echo date_i18n( 'M j, Y', strtotime( $measurement->recorded_date ) ); ?>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $measurement->recorded_date ) ); ?></div>
                                    </td>
                                    <td>
                                        <div class="other-progress-info">
                                            <?php echo $measurement->essay1; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                                $counter ++;
                            } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php
            }

            $html = ob_get_clean();
        }

        return $html;
    }

    private function resizeImage( $size ) {
        global $e20rTables;

        $style = '';
        switch ( $size ) {
            case 'thumbnail':
                break;
            case 'small':
                break;
            case 'medium':
                break;
            case 'large':
                break;
            case 'full':
                break;
        }
            return 'width: 38px; height: 38px;';

    }

    public function getProgressPhoto( $data, $userId, $key = null ) {

        global $e20rClient;

        $isBeta = true; // I know... This is a bit backwards
        $imageUrl = $e20rClient->getUserImgUrl( $userId, $data->recorded_date, 'front' );

        if ( $imageUrl === false ) {
            $imageUrl = $this->loadImage( 'front' );
            $isBeta = false;
        }

        list($width, $height, $type, $attr) = getimagesize( $imageUrl );

        if ( ! is_admin() ) { ?>
        <div style="display: none;" id="e20r-progress-pop-up">
        <!-- <div id="lbp-inline-href-<?php echo ( $key ? $key : 1 ); ?>" style="padding: 10px; background: #fff"> -->
            <div id="lbp-inline-href-1" style="padding: 10px; background: #fff; max-width: 400px; max-height: 600px; width: auto; height: auto;"> <?php
        }
        else {
        ?>
        <!-- <div id="e20r-progress-pic-<?php echo ( $key ? $key : 1 ); ?>" style="padding: 10px; background: #fff"> -->
            <div id="e20r-progress-pic-<?php echo ( $key ? $key : 1 ); ?>" style="display: none;"><?php
        }
        ?>
                <!-- max-width: 380px; max-height: 580px; width: 100%; height: auto; -->
                <div style="width: 100%; height: auto; margin-left: auto; margin-right: auto;">
                    <img class="photo" src="<?php echo $imageUrl; ?>" style="text-align: center; max-width: 580px; max-height: 730px; width: 100%; height: auto;" />
                    <div class="photo-overlay">
                        <h3>
                            <span class="orientation">Front Photo:</span>
                            <span class="date"><?php echo date( 'D M\. jS, Y', strtotime( $data->recorded_date ) ); ?></span>
                            <p class="timeago timeagosize"><?php echo date( 'Y/m/d', strtotime( $data->recorded_date ) ); ?></p>
                        </h3>
                        <div class="info">
                            <span class="key">Weight:</span>
                            <span class="value"><?php echo number_format( (float) round( $data->weight, 1 ), 1 ) . " " . $this->prettyUnit( $e20rClient->getWeightUnit() );?> </span>

                            <span class="key">Total Girth:</span>
                            <span class="value"><?php echo number_format( (float) round( $data->girth, 2 ), 2 ) . " " . $this->prettyUnit($e20rClient->getLengthUnit()); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        if ( ! is_admin() ) { ?>
        </div>
            <a class="lbp-inline-link-<?php echo ( $key ? $key : 1 ); ?> cboxElement" data-link="lbp-inline-href-<?php echo ( $key ? $key : 1 ); ?>" href="#"> <?php
        }
        else { ?>
            <a href="#TB_inline?width=<?php echo ( ( $width + 20 ) > 600 ? 600 : $width ); ?>&height=<?php echo ( ( $height + 20 ) > 730 ? 730 :  $height ); ?>&inlineId=e20r-progress-pic-<?php echo ( $key ? $key : 1 ); ?>" class="thickbox"><?php
        } ?>
                <img src="<?php echo $imageUrl; ?>" style="max-width: 38px; max-height: 38px; height: auto; width: auto;"/>
            </a>
        <?php
    }
} 