<?php

class e20rMeasurementViews {

    private $id;
    private $when;

    private $data;
    private $fields;

    public function __construct() {

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
                                <button class="submit e20r-button" id="submit-weekly-progress-button">
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

    public function endProgressForm() {
        ob_start();
        ?>
                    </tbody>
                </table>
                </form>
            </div> <!-- End of progress-canvas -->
        </div>
        <div style="display: none;">
            <div id="load_help_weight" class="colorbox-guide-container">
                <?php echo $this->loadHelp('weight'); ?>
            </div>
        </div>
        <div style="display: none;">
            <div id="load_help_girth" class="colorbox-guide-container">
                <?php echo $this->loadHelp("girth"); ?>
            </div>
        </div>
        <div style="display: none;">
            <div id="load_help_photo" class="colorbox-guide-container">
                <?php echo $this->loadHelp("photo"); ?>
            </div>
        </div>
		<div class="modal"><!-- At end of form --></div>
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
                        <a href="#load_help_girth" class="inline cboxElement">
                            <img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="Display the girth Measurement Instructions">
                        </a>Girth Measurements
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help with how to take your girth measurements? Check out the
                        <a href="#load_help_girth" class="inline cboxElement">instructions</a>.
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
                                    <input type="text" <?php echo ( empty( $this->data->{$this->fields[ 'girth_' . strtolower( $girth->type ) ]} ) ? 'value=""' : 'value="' . $this->data->{$this->fields[ 'girth_' . strtolower( $girth->type ) ]} . '"' ); ?> class="highlight-handle measurement-input girth-style" data-measurement-type="<?php echo 'girth_' . strtolower($girth->type); ?>">
                                    <span class="unit length"><?php echo $this->prettyUnit( $e20rClient->getLengthUnit() ); ?></span>
                                </div>
                                <div class="measurement-saved-container">
                                    <div class="label-container">
                                        <label>Entered <?php echo ucfirst( $girth->type ); ?> Girth</label>
                                    </div>
                                    <?php // dbg("measurementViews() - Data for {$this->fields[ 'girth_' . strtolower($girth->type) ]} => {$this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]}}"); ?>
                                    <span class="value"><?php echo ( ! empty( $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} ) ? $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} : '' ); ?></span>
                                    <span class="unit length"><?php echo ( ! empty( $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} ) ? $this->prettyUnit( $e20rClient->getLengthUnit() ) : null ); ?></span>
                                    <button class="edit e20r-button">Change Girth</button>
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

    public function showOtherIndicatorsRow( $showPhotos = null ) {

	    if ( isset( $this->data->{$this->fields['essay1']} ) ) {

		    $note = ( ( ! empty( $this->data->{$this->fields['essay1']} ) ) || ( $this->data->{$this->fields['essay1']} != 'NULL' ) ? stripslashes( $this->data->{$this->fields['essay1']} ) : '' );
	    }
	    else {

		    $note = '';
	    }
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
                        <textarea name="essay1" id="essay1" data-measurement-type="essay1" class="e20r-assignment-paragraph e20r-textarea e20r-note"><?php echo $note; ?></textarea>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function showProgressQuestionRow( $showPhotos = null ) {

        ob_start();

	    $behaviorProgress = isset( $this->data->{$this->fields['behaviorprogress']} ) ? $this->data->{$this->fields['behaviorprogress']} :  null;

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
                                <input type="radio" name="pquestion-1" id="pquestion-1-1" value="1" data-measurement-type="behaviorprogress" <?php checked( $behaviorProgress , 1, true ); ?>>
                                <label for="pquestion-1-1">Yes</label>
                            </li>
                            <li>
                                <input type="radio" name="pquestion-1" id="pquestion-1-2" value="2" data-measurement-type="behaviorprogress" <?php checked( $behaviorProgress , 2, true ); ?>>
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

    public function showPhotoRow( $showPhotos = null ) {

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
                        <a href="#load_help_photo" class="inline cboxElement">
                            <img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip=""Display the Photo Instructions">
                        </a>
                        Photos
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help figuring out how to take your progress photos? Check out the
                        <a href="#load_help_photo" class="inline cboxElement">instructions</a>.
<!--                        or download our comprehensive
                        <a href="/protected-downloads/resources/Measurement-Guide-Bit-Better.pdf">Measurement Guide</a>
                        in PDF format.
-->
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
                                        <button id="photo-upload-front" class="e20r-button">Select Image: Front</button>
                                    </div>
                                </th>
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title">Side Photo</div>
                                        <button id="photo-upload-side" class="e20r-button">Select Image: Side</button>
                                    </div>
                                </th>
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title">Back Photo</div>
                                        <button id="photo-upload-back" class="e20r-button">Select Image: Back</button>
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

    public function showWeightRow() {

        global $e20rClient;

        ob_start();
        ?>
        <tr>
            <td>
                <div class="e20r-number-col">1:</div>
            </td>
            <td class="content validate-body-weight" id="body-weight">
                <fieldset>
                    <legend>
                        <a href="#load_help_weight" class="inline cboxElement"><img src="<?php echo E20R_PLUGINS_URL . '/images/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="Display the Body Weight Measurement Instructions"></a>
                        Body Weight Measurement
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help with taking your body weight measurements? Check out the
                        <a href="#load_help_weight" class="inline cboxElement">instructions</a>.
                    </div>
                    <?php echo $this->showChangeWeightUnit(); ?>
                    <div class="measurement-field-container">
                        <div class="label-container">
                            <label>Enter Current Body Weight</label>
                        </div>
                        <input type="text" <?php echo ( empty( $this->data->weight ) ? 'value=""' : 'value="' . $this->data->weight . '"' ); ?> class="highlight-handle measurement-input weight-style" data-measurement-type="weight">
                        <span class="unit weight"><?php echo $this->prettyUnit( $e20rClient->getWeightUnit() ); ?></span>
                    </div>
                    <div class="measurement-saved-container">
                        <div class="label-container">
                            <label>Entered Body Weight</label>
                        </div>
                        <span class="value"><?php echo (! empty( $this->data->weight ) ? $this->data->weight : null ); ?></span>
                        <span class="unit weight"><?php echo (! empty( $this->data->weight ) ? $this->prettyUnit( $e20rClient->getWeightUnit() ) : null ); ?></span>
                        <button class="edit e20r-button">Change Body Weight</button>
                    </div>
                </fieldset>
            </td>
        </tr>
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
                    <select id="bdmonth" onchange="setBirthdate()">
                        <option></option> <?php

                        foreach ( range( 1, 12) as $month ) { ?>
                            <option value="<?php echo date('m', mktime(0, 0, 0, $month, 10)); ?>"><?php echo date('F', mktime(0, 0, 0, $month, 10)); ?></option><?php
                        } ?>

                    </select>
                    <select id="bdday" onchange="setBirthdate()">
                        <option></option> <?php

                        foreach ( range( 1, 31 ) as $day ) { ?>
                            <option value="<?php echo date( 'd', mktime( 0, 0, 0, 1, $day ) ); ?>"><?php echo $day; ?></option><?php
                        }
                        ?>
                    </select>
                    <select id="bdyear" onchange="setBirthdate()">
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

    public function viewTabbedProgress( $progressEntries, $dimensions, $withModal = true ) {

        ob_start();
        ?>
        <div id="status-tabs">
            <ul>
        <?php
            $count = 1;
            foreach( $progressEntries as $label => $contentHtml ) {
            ?><li><a href="#tabs-<?php echo $count++; ?>"><?php echo $label; ?></a></li><?php
            }
        ?>
            </ul>
            <!-- <div id="spinner" class="e20r-spinner"></div> -->
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
        </div> <!-- tabs div --><?php
        if ( $withModal ) { ?>
	        <div class="modal"><!-- At end of form --></div><?php
        }

        $html = ob_get_clean();
        return $html;

    }

    public function viewTableOfMeasurements( $clientId = null, $measurements, $dimensions = null, $tabbed = true, $admin = true ) {
        // TESTING: using $clientId = 12;
        // $clientId = 12;
        global $e20rTables;
        global $e20rClient;
        global $currentProgram;
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
                <a href="#e20r_tracker_data" id="e20r-reload-measurements" class="e20r-choice-button button e20r-button" > ' . __("Reload Measurements", "e20r-tracker") . '</a>
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

                            if ( ui.newTab.index() === 0 ) {

                                console.log("Redrawing wPlot");
                                progMeasurements.wPlot.replot({resetAxes: true});
                            }
                            else if ( ui.newTab.index() === 1 ) {

                                console.log("Redrawing gPlot");
                                progMeasurements.gPlot.replot({resetAxes: true});
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
                    <table class="e20r-measurement-table e20r-resp-table">
                        <thead class="e20r-resp-table-header">
                            <tr>
                                <th class="e20r_mHead rotate"></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Weight (%s)","e20rtracker"), $e20rClient->getWeightUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Neck (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Shoulder (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Chest (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Arm (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Waist (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Hip (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Thigh (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Calf (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Total Girth (%s)","e20rtracker"), $e20rClient->getLengthUnit() ); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php _e("Photo","e20rtracker"); ?></span></div></th>
                            </tr>
                        </thead>
                        <tbody class="e20r-resp-table-body">
                            <?php

                            $counter = 0;
                            if ( empty( $measurements ) ) { ?>
	                            <tr>
		                            <td colspan="12"><?php _e("No measurements recorded.", "e20rtracker"); ?></td>
	                            </tr>
                            <?php
                            }
                            else {

	                            foreach ( $measurements as $key => $measurement ) {

		                            $measurement->girth = (
			                            $measurement->neck + $measurement->shoulder + $measurement->chest + $measurement->arm +
			                            $measurement->waist + $measurement->hip + $measurement->thigh + $measurement->calf
		                            );

		                            $when     = date_i18n( "Y-m-d", strtotime( $measurement->recorded_date ) );
		                            $showLink = ( $clientId == $current_user->ID ? true : false );

		                            ?>
		                            <tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?>">
			                            <td class="e20r_mData">
				                            <div class="date">
					                            <!-- <span> -->
					                            <?php
					                            if ( $showLink ) {
						                            ?>
						                            <form method="POST" class="article_data"
						                                  action="<?php echo get_permalink( $currentProgram->measurements_page_id ); ?>">
							                            <input type="hidden" name="e20r-progress-form-date"
							                                   class="e20r-progress-form-date"
							                                   data-measurement-type="date"
							                                   value="<?php echo $when; ?>">
							                            <input type="hidden" name="e20r-progress-form-article"
							                                   class="e20r-progress-form-article"
							                                   data-measurement-type="article_id"
							                                   value="<?php echo $measurement->article_id; ?>">
							                            <!-- <input type="hidden" name="program_id" class="program_id" data-measurement-type="program_id" value="<?php echo $measurement->program_id; ?>"> -->
							                            <a href="<?php echo $when; ?>"
							                               onclick="jQuery(this).parent().submit();"
							                               class="load_progress_data" target="_blank"
							                               alt="<?php _e( "Opens in a separate window", 'e20r-tracker' ); ?>">
								                            <?php echo date_i18n( "M j, Y", strtotime( $measurement->recorded_date ) ); ?>
							                            </a>
						                            </form>
					                            <?php
					                            } else {
						                            ?>
						                            <?php echo date_i18n( "M j, Y", strtotime( $measurement->recorded_date ) ); ?>
					                            <?php
					                            }
					                            ?>
				                            </div>
				                            <div
					                            class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $measurement->recorded_date ) ); ?></div>
			                            </td>
			                            <td data-th="<?php echo sprintf( __("Weight (%s)","e20rtracker"), $e20rClient->getWeightUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->weight ) || ( $measurement->weight == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->weight, 1 ), 1 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Neck (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->neck ) || ( $measurement->neck == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->neck, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Shoulder (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->shoulder ) || ( $measurement->shoulder == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->shoulder, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Chest (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->chest ) || ( $measurement->chest == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->chest, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Arm (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->arm ) || ( $measurement->arm == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->arm, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Waist (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->waist ) || ( $measurement->waist == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->waist, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Hip (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->hip ) || ( $measurement->hip == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->hip, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Thigh (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->thigh ) || ( $measurement->thigh == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->thigh, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Calf (%s)","e20rtracker"), $e20rClient->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->calf ) || ( $measurement->calf == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->calf, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Total Girth (%s)","e20rtracker"), $e20rClient->getLengthUnit() ); ?>" class="e20r_mData"><?php echo( is_null( $measurement->girth ) || ( $measurement->girth == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->girth, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php _e("Photo","e20rtracker"); ?>" class="smallPhoto"><?php echo $this->getProgressPhoto( $measurement, $user->ID, $key ); ?></td>
		                            </tr>
		                            <?php
		                            $counter ++;
	                            }
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
					            if ( empty( $measurements ) ) { ?>
						            <tr>
							            <td colspan="2"><?php _e("No Progress indicators recorded.", "e20rtracker"); ?></td>
						            </tr>
					            <?php
					            }
					            else {

						            foreach ( $measurements as $key => $measurement ) {

							            $when     = date_i18n( "Y-m-d", strtotime( $measurement->recorded_date ) );
							            $showLink = ( $clientId == $current_user->ID ? true : false );

							            ?>
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
											            <a href="#load_progress" target="_blank"
											               alt="<?php _e( "Opens in a separate window", 'e20r-tracker' ); ?>">
												            <?php echo date_i18n( 'M j, Y', strtotime( $measurement->recorded_date ) ); ?>
											            </a>
										            <?php
										            } else {
											            ?>
											            <?php echo date_i18n( 'M j, Y', strtotime( $measurement->recorded_date ) ); ?>
										            <?php
										            }
										            ?>
									            </div>
									            <div
										            class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $measurement->recorded_date ) ); ?></div>
								            </td>
								            <td>
									            <div class="other-progress-info">
										            <?php echo $measurement->essay1; ?>
									            </div>
								            </td>
							            </tr>
							            <?php
							            $counter ++;
						            }
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
            <div id="inline-content-href-<?php echo ( $key ? $key : 1 ) ?>" style="padding: 10px; background: #fff; max-width: 400px; max-height: 600px; width: auto; height: auto;"> <?php
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
            <a class="inline cboxElement" data-link="lbp-inline-href-<?php echo ( $key ? $key : 1 ); ?>" href="#inline-content-href-<?php echo ( $key ? $key : 1 ) ?>">
            <!-- <a class="lbp-inline-link-<?php echo ( $key ? $key : 1 ); ?> inline cboxElement" data-link="lbp-inline-href-<?php echo ( $key ? $key : 1 ); ?>" href="#inline-content-href-<?php echo ( $key ? $key : 1 ) ?>"> --> <?php
        }
        else { ?>
            <a href="#TB_inline?width=<?php echo ( ( $width + 20 ) > 600 ? 600 : $width ); ?>&height=<?php echo ( ( $height + 20 ) > 730 ? 730 :  $height ); ?>&inlineId=e20r-progress-pic-<?php echo ( $key ? $key : 1 ); ?>" class="thickbox"><?php
        } ?>
                <img src="<?php echo $imageUrl; ?>" style="max-width: 38px; max-height: 38px; height: auto; width: auto;"/>
            </a>
        <?php
    }

    public function loadHelp($type = null) {

        ob_start();

        switch ($type) {

            case 'weight';
                ?>
                <h3>Body Weight Measurement Guide</h3>
                <table class="e20r-help-items">
                    <tbody>
                    <tr>
                        <td>
                            <div class="e20r-number-col">1</div>
                        </td>
                        <td class="content">
                            <h4>Find a scale.</h4>
                            Start with a good scale, preferably a pre-calibrated digital scale or a beam scale
                            (like the kind you find in doctor's offices).
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col">2</div>
                        </td>
                        <td class="content">
                            <h4>Test and calibrate.</h4>
                            Once you have a good scale, determine its accuracy and reliability. To do so, select an
                            object of
                            known weight (in a lab scientists use a pre-calibrated reference weight) and weigh it
                            five times
                            successively. If these five readings are within one pound or so of both the known weight
                            and the
                            other readings, your scale is as good as you're going to find. If the variation is
                            greater than
                            two pounds, you'll need to re-set your scale (if it's digital). If it still doesn't
                            produce
                            reliable or accurate readings, you need a better scale.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col">3</div>
                        </td>
                        <td class="content">
                            <h4>Test again.</h4>
                            Each time you weigh yourself, make sure to test your known object a few times (three
                            times
                            or so) beforehand to see if the scale is accurate and reliable on that day. The same
                            rules
                            above apply.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col">4</div>
                        </td>
                        <td class="content">
                            <h4>Weigh yourself.</h4>
                            Next, step on the scale yourself. Record your first reading. Weigh yourself two
                            additional
                            times and record these readings. If your measurements are within one pound of each
                            other,
                            take the average of the three. If not, weigh a fourth time and average the closest three
                            measurements.
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col">5</div>
                        </td>
                        <td class="content">
                            <h4>Input your measurement.</h4>
                            Record the mean (average) body weight measurement. Make sure you are recording in the
                            correct units: if your scale is in pounds, record in pounds; if it is in kilograms,
                            record in kilograms; etc. You can change your measurement units at any time by clicking
                            the "change this" link next to the "Preferred measurement units" at the top of the Body
                            Weight Measurement section.
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php
                $html = ob_get_clean();
                break;

            case 'girth':

                ?>
                <h3>Girth measurement guide</h3>
                <table class="e20r-help-items">
                    <tbody>
                        <tr>
                            <td>
                                <div class="e20r-number-col">1</div>
                            </td>
                            <td class="content">
                                <h4>Pick up a good measuring tape.</h4>
                                A simple cloth measuring tape will work, but we prefer to use the MyoTape device from
                                (<a href="http://www.accumeasurefitness.com/">www.accumeasurefitness.com</a>).
                                It's inexpensive, it's handy, and since the tape encircles the body part with a consistent
                                tightness, it allows for more accurate and consistent readings.
                                <p align="center">
                                    <img class="alignnone" src="<?php echo E20R_PLUGINS_URL; ?>/images/myotape.png" alt="" width="302" height="267" />
                                </p>
                            </td>
                            </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">2</div>
                            </td>
                            <td class="content">
                                <h4>Choose the body parts to record.</h4>
                                As part of the Bit Better Coaching program, we typically ask you to record girths for the following
                                eight body parts:
                                <ul>
                                    <li><strong>Neck girth</strong>: Measure just below the Adam’s apple and at the level of the 7th cervical vertebra.</li>
                                    <li><strong>Shoulder girth</strong>: Measure at the widest point of the shoulders, around the entire shoulder area.
                                        Make sure you’re standing upright and breathing normally. Record the measure after a normal (not a forced) exhalation.</li>
                                    <li><strong>Chest girth</strong>: The maximal horizontal girth of the chest at the nipple line.
                                        Stand upright and pass the tape measure over the shoulder blades and under the armpits. Record the measure after a normal (not a forced) exhalation.</li>
                                    <li><strong>Upper arm girth</strong>: Measure halfway between the elbow and the bony point on the top of your shoulder.
                                        Measure this distance if you have to and take the mid-point.</li>
                                    <li><strong>Waist girth</strong>: Measure at the navel. Stand upright and breathe normally with the abdomen relaxed.
                                        Record the measure after a normal (not a forced) exhalation.</li>
                                    <li><strong>Hip girth</strong>: Measure around the glutes at the level of maximal circumference (the widest point).</li>
                                    <li><strong>Thigh girth</strong>: Measure at the halfway point between the center of the kneecap and inguinal crease
                                        (the line where leg inserts into trunk). Measure the distance if you have to and take the mid-point.</li>
                                    <li><strong>Calf girth</strong>: Measure at the widest point of your calf muscle.</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">3</div>
                            </td>
                            <td class="content">&nbsp;
                                <h4>Wrap tape around body part.</h4>
                                If you’re using the MyoTape device (which we recommend), pull the end of the tape
                                around the body part that you want to measure (pressing the button in the center will
                                make it easier to pull) and place the rod at the end of the tape into the circular slot.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">4</div>
                            </td>
                            <td class="content">
                                <h4>Tighten tape to a snug fit.</h4>
                                Press the button in the center and let the tape retract to a snug fit. Make sure the tape
                                is perpendicular to the body part and parallel with the ground. If you’re using a regular
                                cloth tape, try to achieve a consistent tightness with each measurement.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">5</div>
                            </td>
                            <td class="content">
                                <h4>Read and record measurement.</h4>
                                Read your measurement on the outer edge of the tape measure (the end opposite the locked
                                in rod) and write it down. Remember to take three different measurements at each site and
                                record the average of the three.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">6</div>
                            </td>
                            <td class="content">&nbsp;
                                <h4>Input your measurements.</h4>
                                Once you have calculated the average measurements for all eight sites, return to the
                                Coaching Progress Update form and record your measurements. Make sure you are
                                recording in the correct units: if your tape measure is in inches, record in inches;
                                if it is in centimetres, record in centimetres. You can change your measurement units
                                at any time by clicking the "change this" link next to the "Preferred measurement
                                units" at the top of the Girth Measurements section.
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php
                $html = ob_get_clean();
                break;

            case 'photo':
                ?>
                <h3>Photo Guide</h3>
                <table class="e20r-help-items">
                    <tbody>
                        <tr>
                            <td>
                                <div class="e20r-number-col">1</div>
                            </td>
                            <td class="content">
                                <h4>Clothing and location.</h4>
                                Dressed in a swimsuit, or small pair of shorts and fitted/revealing top like a
                                sports bra, stand against a bare wall.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">2</div>
                            </td>
                            <td class="content">
                                <h4>Camera setup.</h4>
                                Set up your camera about 5-7 feet away from you so that it can capture your whole body
                                from head to toe. You can use a tripod or have a friend snap the photo.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">3</div>
                            </td>
                            <td class="content">
                                <h4>Lighting.</h4>
                                Make sure the room is well-lit and that you use the flash when taking your photo.
                                However, make sure there isn't a ton of overhead light; you don't want to cast
                                shadows.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">4</div>
                            </td>
                            <td class="content">
                                <h4>Write it down.</h4>
                                Write down exactly how you took the before pictures (camera settings, lighting
                                conditions, how far away the camera was, etc.). This will help you duplicate the same
                                conditions in the future.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">5</div>
                            </td>
                            <td class="content">
                                <h4>Take three photos.</h4>
                                Take three total photographs: one of your front side, one of your left side and one
                                of your back side.
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col">6</div>
                            </td>
                            <td class="content">
                                <h4>Copy photos to your hard drive.</h4>
                                <p>Connect your camera to your computer, typically with the USB cable that comes
                                with the camera. You should be able to copy the photos to a folder on your computer.</p>
                                <p>Here's a brief video on the basics of getting photos off your digital camera
                                and on to your Windows XP computer: <a href="http://www.youtube.com/watch?v=W_hxdY7g-sw">
                                http://www.youtube.com/watch?v=W_hxdY7g-sw</a></p>
                                <p>Here is another video on copying photos to a Windows Vista computer:
                                <a href="http://www.youtube.com/watch?v=nnlEcMwikuw">http://www.youtube.com/watch?v=nnlEcMwikuw</a></p>
                                <p>If you have a Mac, you might want to view the Apple article on
                                <a href="http://support.apple.com/kb/HT2498">Connecting Your Camera</a>.</p>
                            </td>
                        </tr>
                        <tr>
                        <td>
                            <div class="e20r-number-col">7</div>
                        </td>
                        <td class="content">
                            <h4>Upload the photos.</h4>
                            Using the photo upload form on the Coaching Progress Update page, browse to the photos
                            you copied. Select each one in the appropriate box (the front photo for the "Front
                            View" box, the side photo for the "Side View" box, etc) and upload them, one at a time.
                        </td>
                        </tr>
                    </tbody>
                </table>
            <?php
                $html = ob_get_clean();
                break;
        }

        return $html;
    }

    private function bgImage( $what, $type ) {

	    global $e20rClient;

        return 'background-image: url("' . E20R_PLUGINS_URL . '/images/' . $what . '-' . $type . ( $e20rClient->getGender() == 'f' ? '-f.png");' : '-m.png");' );
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
        <div class="e20r-measurement-setting" style="margin-bottom: 24px;">
            Preferred weight units:
            <span id="preferred-weight-unit" class="e20r-change-units"><?php echo $this->prettyUnit($e20rClient->getWeightUnit()); ?></span>
            <span class="e20r-change-unit-dropdown"><select id="selected-weight-unit">
                <option value="lbs"<?php selected( $e20rClient->getWeightUnit(), 'lbs' ); ?>><?php echo $this->prettyUnit('lbs'); ?></option>
                <option value="kg"<?php selected( $e20rClient->getWeightUnit(), 'kg' ); ?>><?php echo $this->prettyUnit('kg'); ?></option>
                <option value="st"<?php selected( $e20rClient->getWeightUnit(), 'st' ); ?>><?php echo $this->prettyUnit('st'); ?></option>
                <option value="st"<?php selected( $e20rClient->getWeightUnit(), 'st_uk' ); ?>><?php echo $this->prettyUnit('st_uk'); ?></option>
            </select></span>
            <span class="e20r-change-weight-unit-link">(<a class="change-measurement-unit" data-dimension="weight"><?php _e("change this", "e20rtracker"); ?></a>)</span>
            <span class="e20r-cancel-weight-unit-link">(<a class="cancel-measurement-unit-update"><?php _e("cancel", "e20rtracker"); ?></a>)</span>
        </div>
        <?php
        return ob_get_clean();
    }

    private function showChangeLengthUnit() {

        global $e20rClient;
        ob_start();
        ?>
        <div class="e20r-measurement-setting" style="margin-bottom: 24px;">
            Preferred length units:
            <span id="preferred-length-unit" class="e20r-change-units"><?php echo $this->prettyUnit( $e20rClient->getLengthUnit() ); ?></span>
            <span class="e20r-change-unit-dropdown"><select id="selected-length-unit">
                <option value="in"<?php selected( $e20rClient->getLengthUnit(), 'in' ); ?>><?php echo $this->prettyUnit('in'); ?></option>
                <option value="cm"<?php selected( $e20rClient->getLengthUnit(), 'cm' ); ?>><?php echo $this->prettyUnit('cm'); ?></option>
            </select></span>
            <span class="e20r-change-length-unit-link">(<a class="change-measurement-unit" data-dimension="length"><?php _e("change this", "e20rtracker"); ?></a>)</span>
            <span class="e20r-cancel-length-unit-link">(<a class="cancel-measurement-unit-update"><?php _e("cancel", "e20rtracker"); ?></a>)</span>
        </div>
        <?php
        return ob_get_clean();

    }

    private function loadImage( $side ) {

        $id = ( isset( $this->data->{$this->fields[$side . "_image"]} ) ?  $this->data->{$this->fields[$side . "_image"]} : null );

        if ( ( $url = wp_get_attachment_thumb_url( $id ) ) === false ) {

            $url = E20R_PLUGINS_URL . "/images/no-image-uploaded.jpg";
        }

        return $url;
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

} 