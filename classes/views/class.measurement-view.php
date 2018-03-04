<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits, educational reminders, etc.
 * Copyright (c) 2014-2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Views;

use E20R\Tracker\Models\Tables;
use E20R\Tracker\Controllers\Client;
use E20R\Utilities\Utilities;

/**
 * Class Measurement_View
 *
 * @package E20R\Tracker\Views
 *
 */
class Measurement_View {

    private $id;
    private $when;

    private $data;
    private $fields;

    private static $instance = null;

    public function __construct() {

        $Tables = Tables::getInstance();
        $this->fields = $Tables->getFields( 'measurements' );
    }

    /**
	 * @return Measurement_View
	 */
	static function getInstance() {

    	if ( is_null( self::$instance ) ) {
    		self::$instance = new self;
	    }

	    return self::$instance;
	}

    public function init( $when = null, $data = null, $who = null ) {

        $this->id = $who;
        $this->when = $when;
        $this->data = $data;
    }

    public function startProgressForm( $articleId, $programId ) {

        // $this->load_scripts();

        ob_start(); ?>
        <div id="saturday-progress-container" class="progress-container clearfix">
            <h3><?php printf( __( 'Your Weekly Progress %1$sUpdate%2$s', 'e20r-tracker' ), '<span>', '</span>' ); ?></h3>
            <div id="e20r-progress-canvas" style="min-height: 100px;">
                <form method="POST">
                <?php wp_nonce_field( 'e20r-tracker-progress', 'e20r-progress-nonce'); ?>
                <table class="e20r-progress-form">
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>
                                <input type="hidden" name="date" id="date" data-measurement-type="date" value="<?php esc_attr_e( $this->when ); ?>">
                                <input type="hidden" name="article_id" id="article_id" data-measurement-type="article_id" value="<?php esc_attr_e( $articleId ); ?>">
                                <input type="hidden" name="program_id" id="program_id" data-measurement-type="program_id" value="<?php esc_attr_e( $programId ); ?>">
                                <button class="submit e20r-button" id="submit-weekly-progress-button">
                                    <div><?php _e( 'Save Your Weekly Progress Update', 'e20-tracker' ); ?></div>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                </form><?php
        return ob_get_clean();
    }

    public function endProgressForm() {
        ob_start();?>
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
		<div class="modal"><!-- At end of form --></div><?php
        return ob_get_clean();
    }

    public function showGirthRow( $girths ) {

        $Client = Client::getInstance();

        Utilities::get_instance()->log("Loading Girth information");
        ob_start(); ?>
        <tr>
            <td>
                <div class="e20r-number-col">2:</div>
            </td>
            <td class="content validate-girth-measurements" id="girth-measurements">
                <fieldset>
                    <legend>
                        <a href="#load_help_girth" class="inline cboxElement">
                            <img src="<?php echo E20R_PLUGINS_URL . '/img/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="Display the girth Measurement Instructions">
                        </a><?php _e("Girth Measurements", "e20r-tracker" ); ?>
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        <?php printf( __( 'Need some help with how to take your girth measurements? Check out the %sinstructions%s', 'e20-tracker' ), '<a href="#load_help_girth" class="inline cboxElement">', '</a>' ); ?>
                    </div>
                    <?php echo $this->showChangeLengthUnit(); ?>
                    <?php Utilities::get_instance()->log("showGirthRow() -> Length Units changer loaded. Girth Count: " . count($girths) ); ?>

                    <?php foreach( $girths as $order => $girth) { ?>
                        <?php Utilities::get_instance()->log("showGirthRow() -> type: {$girth->type}"); ?>
                        <h5 class="measurement-header">
                            <span class="title"><?php esc_html_e( ucfirst($girth->type) ); ?> <?php _e( 'Girth', 'e20r-tracker' ); ?></span>
                            <img src="<?php echo E20R_PLUGINS_URL . '/img/help.png'; ?>" class="measurement-descript-toggle">
                        </h5>

                        <div class="girth-row-container">
                            <div class="girth-image" style='<?php echo $this->bgImage( $girth->type, "girth" ); ?>'></div>
                            <div class="girth-descript-container">
                                <p style="margin-bottom: 20px;" class="girth-description">
                                    <?php esc_html_e( $girth->descr ); ?>
                                </p>
                                <div class="measurement-field-container">
                                    <div class="label-container">
                                        <label><?php printf( __( 'Enter %s Girth', 'e20r-tracker' ), esc_attr( ucfirst($girth->type ) ) ); ?></label>
                                    </div>
                                    <input type="text" <?php echo ( empty( $this->data->{$this->fields[ 'girth_' . strtolower( $girth->type ) ]} ) ? 'value=""' : 'value="' . $this->data->{$this->fields[ 'girth_' . strtolower( $girth->type ) ]} . '"' ); ?> class="highlight-handle measurement-input girth-style" data-measurement-type="<?php echo 'girth_' . strtolower($girth->type); ?>">
                                    <span class="unit length"><?php echo $this->prettyUnit( $Client->getLengthUnit() ); ?></span>
                                </div>
                                <div class="measurement-saved-container">
                                    <div class="label-container">
                                        <label>Entered <?php echo ucfirst( $girth->type ); ?> Girth</label>
                                    </div>
                                    <span class="value"><?php echo ( ! empty( $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} ) ? $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} : '' ); ?></span>
                                    <span class="unit length"><?php echo ( ! empty( $this->data->{$this->fields[ 'girth_' . strtolower($girth->type) ]} ) ? $this->prettyUnit( $Client->getLengthUnit() ) : null ); ?></span>
                                    <button class="edit e20r-button"><?php _e( 'Change Girth', 'e20r-tracker' ); ?></button>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php Utilities::get_instance()->log("Girth specifics loaded"); ?>
                </fieldset>
            </td>
        </tr><?php
        return ob_get_clean();
    }

    public function showOtherIndicatorsRow( $showPhotos = null ) {

	    if ( isset( $this->data->{$this->fields['essay1']} ) ) {

		    $note = ( ( ! empty( $this->data->{$this->fields['essay1']} ) ) || ( $this->data->{$this->fields['essay1']} != 'NULL' ) ? stripslashes( $this->data->{$this->fields['essay1']} ) : '' );
	    }
	    else {

		    $note = '';
	    }
        ob_start();?>
        <tr>
            <td id="other-indicators">
                <div class="e20r-number-col"><?php echo ( $showPhotos ? '4' : '3' ); ?>:</div>
            </td>
            <td class="content">
                <fieldset>
                    <legend>
                        <?php _e( "Other Indicators of Progress I'm Tracking", "e20r-tracker" ); ?>
                    </legend>
                    <div>
                        <textarea name="essay1" id="essay1" data-measurement-type="essay1" class="e20r-assignment-paragraph e20r-textarea e20r-note"><?php esc_textarea( $note ); ?></textarea>
                    </div>
                </fieldset>
            </td>
        </tr><?php
        return ob_get_clean();
    }

    public function showProgressQuestionRow( $showPhotos = null ) {

        ob_start();

	    $behaviorProgress = isset( $this->data->{$this->fields['behaviorprogress']} ) ? $this->data->{$this->fields['behaviorprogress']} :  null; ?>
        <tr id="progress-questionnaire">
            <td >
                <div class="e20r-number-col"><?php echo ( $showPhotos ? '5' : '4' ); ?>:</div>
            </td>
            <td class="content">
                <fieldset>
                    <legend><?php _e( 'Progress Questionnaire', 'e20r-tracker' ); ?></legend>
                    <h5><?php _e( 'Did my behaviors and actions this week lead to progress towards my goals?', 'e20r-tracker' ); ?></h5>
                    <div>
                        <ul>
                            <li>
                                <input type="radio" name="pquestion-1" id="pquestion-1-1" value="1" data-measurement-type="behaviorprogress" <?php checked( $behaviorProgress , 1, true ); ?>>
                                <label for="pquestion-1-1"><?php _e("Yes", 'e20r-tracker' ); ?></label>
                            </li>
                            <li>
                                <input type="radio" name="pquestion-1" id="pquestion-1-2" value="2" data-measurement-type="behaviorprogress" <?php checked( $behaviorProgress , 2, true ); ?>>
                                <label for="pquestion-1-2"><?php _e( 'No', 'e20r-tracker' ); ?></label>
                            </li>
                        </ul>
                    </div>
                </fieldset>
            </td>
        </tr><?php
        return ob_get_clean();

    }

    public function showPhotoRow( $showPhotos = false ) {

        Utilities::get_instance()->log("In createPhotoBlock()");

        if ( false === $showPhotos ) {
            return;
        }

        Utilities::get_instance()->log("Expecting to see photos being uploaded");
        ob_start();
        ?>
        <tr>
            <td><div class="e20r-number-col">3:</div></td>
            <td class="content validate-photos" id="photos">
                <fieldset>
                    <legend>
                        <a href="#load_help_photo" class="inline cboxElement">
                            <img src="<?php echo E20R_PLUGINS_URL . '/img/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="<?php _e( 'Display the Photo Instructions', 'e20r-tracker' ); ?>">
                        </a>
                        Photos
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        <?php printf( __( 'Need some help figuring out how to take your progress photos? Check out the %sinstructions%s.', 'e20r-tracker' ), '<a href="#load_help_photo" class="inline cboxElement">', '</a>' ); ?>
                    </div>
                    <div style="clear: both;"></div>
                    <p class="hide-if-no-js">
                        <div class="uploader">
                        <table id="photo-upload-table" class="advanced">
                            <thead>
                            <tr class="e20r-noline">
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title"><?php _e('Front Photo', 'e20r-tracker' ); ?></div>
                                        <button id="photo-upload-front" class="e20r-button"><?php _e('Select Image: Front', 'e20r-tracker' ); ?></button>
                                    </div>
                                </th>
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title"><?php _e('Side Photo', 'e20r-tracker' ); ?></div>
                                        <button id="photo-upload-side" class="e20r-button"><?php _e('Select Image: Side', 'e20r-tracker' ); ?></button>
                                    </div>
                                </th>
                                <th>
                                    <div class="photo-upload-container">
                                        <div class="title"><?php _e('Back Photo','e20r-tracker' ); ?></div>
                                        <button id="photo-upload-back" class="e20r-button"><?php _e('Select Image: Back', 'e20r-tracker' ); ?></button>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="e20r-noline">
                                <td>
                                    <div class="photo-upload-notifier">
                                        <?php _e( 'Photo Saved', 'e20r-tracker' ); ?>
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-front-url-hidden" id="photo-front-url-hidden" value="<?php echo ( empty( $this->data->{$this->fields["front_image"]} ) ? null : $this->data->{$this->fields["front_image"]} ); ?>" />
                                        <div class="descript-overlay"><?php _e('Front', 'e20r-tracker' ); ?></div>
                                        <img id="photo-front" src="<?php echo $this->loadImage( 'front' ); ?>" class="photo<?php echo ( empty( $this->data->{$this->fields["front_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="photo-upload-notifier">
                                        <?php _e( 'Photo Saved', 'e20r-tracker' ); ?>
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-side-url-hidden" id="photo-side-url-hidden" value="<?php echo ( empty( $this->data->{$this->fields["side_image"]} ) ? null : $this->data->{$this->fields["side_image"]} ); ?>" />
                                        <div class="descript-overlay"><?php _e('Side', 'e20r-tracker' ); ?></div>
                                        <img id="photo-side" src="<?php echo $this->loadImage( 'side' ); ?>" class="photo<?php echo ( empty( $this->data->{$this->fields["side_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="photo-upload-notifier">
                                        <?php _e( 'Photo Saved', 'e20r-tracker' ); ?>
                                    </div>
                                    <div class="photo-container">
                                        <input type="hidden" name="photo-back-url-hidden" id="photo-back-url-hidden" value="<?php echo ( empty( $this->data->{$this->fields["back_image"]} ) ? null : $this->data->{$this->fields["back_image"]} ); ?>" />
                                        <div class="descript-overlay"><?php _e('Back', 'e20r-tracker' ); ?></div>
                                        <img id="photo-back" src="<?php echo $this->loadImage( 'front' ); ?>" class="photo<?php echo ( empty( $this->data->{$this->fields["back_image"]} ) ? ' null' : null ); ?>">
                                    </div>
                                </td>
                            </tr class="e20r-noline">
                            </tbody>
                            <tfoot>
                                <td><a href="javascript:" class="delete-photo front" data-orientation="front"><?php _e( 'Delete Front Image', 'e20r-tracker' ); ?></a></td>
                                <td><a href="javascript:" class="delete-photo side" data-orientation="side"><?php _e( 'Delete Side Image', 'e20r-tracker' ); ?></a></td>
                                <td><a href="javascript:" class="delete-photo back" data-orientation="back"><?php _e( 'Delete Back Image', 'e20r-tracker' ); ?></a></td>
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

        $Client = Client::getInstance();

        ob_start(); ?>
        <tr>
            <td>
                <div class="e20r-number-col"><?php _e( '1:', 'e20r-tracker' ); ?></div>
            </td>
            <td class="content validate-body-weight" id="body-weight">
                <fieldset>
                    <legend>
                        <a href="#load_help_weight" class="inline cboxElement"><img src="<?php echo E20R_PLUGINS_URL . '/img/help.png'; ?>" class="help-icon tooltip-handle" data-tooltip="<?php _e( 'Display the Body Weight Measurement Instructions', 'e20r-tracker' ); ?>"></a>
                        <?php _e('Body Weight Measurement', 'e20r-tracker' ); ?>
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        <?php printf( __('Need some help with taking your body weight measurements? Check out the %sinstructions%s.', 'e20r-tracker'),
                        '<a href="#load_help_weight" class="inline cboxElement">',
                        '</a>'
                        ); ?>
                    </div>
                    <?php echo $this->showChangeWeightUnit(); ?>
                    <div class="measurement-field-container">
                        <div class="label-container">
                            <label><?php _e('Enter Current Body Weight' ,'e20r-tracker' ); ?></label>
                        </div>
                        <input type="text" <?php echo ( empty( $this->data->weight ) ? 'value=""' : 'value="' . $this->data->weight . '"' ); ?> class="highlight-handle measurement-input weight-style" data-measurement-type="weight">
                        <span class="unit weight"><?php echo $this->prettyUnit( $Client->getWeightUnit() ); ?></span>
                    </div>
                    <div class="measurement-saved-container">
                        <div class="label-container">
                            <label><?php _e('Entered Body Weight', 'e20r-tracker' ); ?></label>
                        </div>
                        <span class="value"><?php echo (! empty( $this->data->weight ) ? $this->data->weight : null ); ?></span>
                        <span class="unit weight"><?php echo (! empty( $this->data->weight ) ? $this->prettyUnit( $Client->getWeightUnit() ) : null ); ?></span>
                        <button class="edit e20r-button"><?php _e('Change Body Weight', 'e20r-tracker' ); ?></button>
                    </div>
                </fieldset>
            </td>
        </tr><?php

        return ob_get_clean();
    }

    public function showChangeBirthdate() {

        ob_start(); ?>
        <tr id="birth-date">
            <td>&nbsp;</td>
            <td>
                <fieldset>
                    <legend><?php _e("Birth Date", "e20r-tracker" ); ?></legend>
                    <p style="clear: left;">
                        <?php _e("We will need your birth date to help us accurately calculate your body fat percentage. This is a one-time request.", "e20r-tracker" ); ?>
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
                            <option value="<?php echo date( 'd', mktime( 0, 0, 0, 1, $day ) ); ?>"><?php esc_attr_e($day ); ?></option><?php
                        }
                        ?>
                    </select>
                    <select id="bdyear" onchange="setBirthdate()">
                        <option></option>
                        <?php

                        $start = date('Y') - 15;
                        $end = date('Y') - 95;

                        foreach ( range( $start, $end ) as $year ) { ?>
                            <option value="<?php esc_attr_e($year ); ?>"><?php esc_attr_e($year ); ?></option><?php
                        }
                        ?>
                    </select>
                </fieldset>
            </td>
        </tr><?php

        return ob_get_clean();
    }
    
    public function viewTabbedProgress( $progressEntries, $dimensions, $withModal = true ) {

        ob_start(); ?>
        <div id="status-tabs" class="ct ct-flatbox">
        <?php
            $count = 1;
            foreach( $progressEntries as $label => $contentHtml ) { ?>
            <div id="status-tab-<?php echo $count++; ?>">
                <div class="ct-pagitem"><?php echo $label; ?></div>
                <div class="status-tab"><?php echo $contentHtml; ?></div>
            </div><?php
            } ?>

        </div> <!-- div.status-tabs --><?php
        wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce', 'mv' , true ); ?>
        <input type="hidden" id="tracker-user-profile-page" name="tracker-user-profile-page" value="1" /><?php
        if ( $withModal ) { ?>
        <!-- <div class="modal">--><!-- At end of form --><!--</div>--><?php
        }

        $html = ob_get_clean();
        return $html;

    }
    
    public function viewTableOfMeasurements( $clientId = null, $measurements, $dimensions = null, $tabbed = true, $admin = true ) {
        // TESTING: using $clientId = 12;
        // $clientId = 12;
        $Tables = Tables::getInstance();
        $Client = Client::getInstance();
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

            ob_start(); // echo $reloadBtn; ?>
            <div id="e20r_errorMsg">
                <em><?php sprintf(__("No measurements found for %s", "e20r-tracker"), $user->first_name . " " . $user->last_name ); ?></em>
            </div> <?php
            $html = ob_get_clean();
        }
        else {
            $measurements = array_reverse( $measurements );
            ob_start();?>
            <div class="e20r-hidden-inputs">
                <input type="hidden" name="h_dimension" id="h_dimension" value="<?php esc_attr_e( $dimensions['height'] ); ?>">
                <input type="hidden" name="h_dimension_type" id="h_dimension_type" value="<?php esc_attr_e( $dimensions['htype'] ); ?>">
                <input type="hidden" name="w_dimension" id="w_dimension" value="<?php esc_attr_e( $dimensions['width'] ); ?>">
                <input type="hidden" name="w_dimension_type" id="w_dimension_type" value="<?php esc_attr_e( $dimensions['wtype'] ); ?>">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_client_detail_nonce'); ?>
            </div>

        <?php if ( true === $tabbed ): ?>
            <script type="text/javascript">
            /* <![CDATA[ */
                    jQuery(function() {
                    console.log("Configure tabs for weight/girth");

                    var $tabs = jQuery('div#inner-tabs');

                    $tabs.tabs({
                        heightStyle: "content"
                    });

                    jQuery('.load_progress_data').on("click", function() {

                        console.log("Loading progress data...", this );
                        progMeasurements.loadProgressPage( this );
                    });

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
                });
            /* ]]> */
            </script>
            <div id="inner-tabs">
                <ul>
                    <li class="ui-corner-top" role="tab"><a href="#inner-tab-1" role="presentation"><?php _e("Weight History", "e20r-tracker"); ?></a></li>
                    <li class="ui-corner-top" role="tab"><a href="#inner-tab-2" role="presentation"><?php _e("Total Girth", "e20r-tracker"); ?></a></li>
                </ul>
                <!-- Weight Tab -->
                <div>
                    <div id="inner-tab-1" class="inner-tab">
                        <?php echo ( $admin ? '<h4 class="e20r_progress_text">' . sprintf( __("Loading weight history graph for %s", "e20r-tracker"), $user->display_name) . "</h4>" : '' ); ?>
                        <div id="weight_chart" style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
                    </div>
                </div><!-- end of Weight inner-tab -->
                <!-- Girth tab -->
                <div>
                    <div id="inner-tab-2" class="inner-tab">
                        <?php echo ( $admin ? '<h4 class="e20r_progress_text">' . sprintf( __("Loading total girth graph for %s", "e20r-tracker"), $user->display_name) . "</h4>" : '' ); ?>
                        <div id="girth_chart" style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
                    </div><!-- End of Girth Inner-tab tab -->
                </div>
                <!-- Load tabs script here because this content is dynamic -->
            </div><!-- inner-tabs div -->
        <?php else: ?>
            <div id="weight_chart" style="height: <?php echo $height; ?>; width: <?php echo $width; ?>;"></div>
            <div id="girth_chart" style="height: <?php echo $height; ?>;width: <?php echo $width; ?>;"></div>
        <?php endif; ?>
            <div class="e20r-measurements-container">
                <h4>Measurements for <?php esc_attr_e( $user->first_name ); ?></h4>
                <a class="close" href="#">X</a>
                <div class="quick-nav">
                    <table class="e20r-measurement-table e20r-resp-table">
                        <thead class="e20r-resp-table-header">
                            <tr>
                                <th class="e20r_mHead rotate"></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Weight (%s)","e20r-tracker"), $Client->getWeightUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Neck (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Shoulder (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Chest (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Arm (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Waist (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Hip (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Thigh (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Calf (%s)","e20r-tracker"), $Client->getLengthUnit()); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php echo sprintf( __("Total Girth (%s)","e20r-tracker"), $Client->getLengthUnit() ); ?></span></div></th>
                                <th class="e20r_mHead rotate"><div><span><?php _e("Photo","e20r-tracker"); ?></span></div></th>
                            </tr>
                        </thead>
                        <tbody class="e20r-resp-table-body"><?php

                            $counter = 0;
                            if ( empty( $measurements ) ) { ?>
	                            <tr>
		                            <td colspan="12"><?php _e("No measurements recorded.", "e20r-tracker"); ?></td>
	                            </tr><?php
                            }
                            else {

	                            foreach ( $measurements as $key => $measurement ) {

		                            $measurement->girth = (
			                            $measurement->neck + $measurement->shoulder + $measurement->chest + $measurement->arm +
			                            $measurement->waist + $measurement->hip + $measurement->thigh + $measurement->calf
		                            );

		                            $when     = date_i18n( "Y-m-d", strtotime( $measurement->recorded_date ) );
		                            $showLink = ( $clientId == $current_user->ID ? true : false ); ?>
		                            <tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?>">
			                            <td class="e20r_mData">
				                            <div class="date">
					                            <!-- <span> --><?php
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
						                            </form><?php
					                            } else {
						                            ?>
						                            <?php echo date_i18n( "M j, Y", strtotime( $measurement->recorded_date ) );
					                            }
					                            ?>
				                            </div>
				                            <div
					                            class="timeago timeagosize"><?php echo date_i18n( "Y/m/d", strtotime( $measurement->recorded_date ) ); ?></div>
			                            </td>
			                            <td data-th="<?php echo sprintf( __("Weight (%s)","e20r-tracker"), $Client->getWeightUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->weight ) || ( $measurement->weight == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->weight, 1 ), 1 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Neck (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->neck ) || ( $measurement->neck == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->neck, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Shoulder (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->shoulder ) || ( $measurement->shoulder == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->shoulder, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Chest (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->chest ) || ( $measurement->chest == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->chest, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Arm (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->arm ) || ( $measurement->arm == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->arm, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Waist (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->waist ) || ( $measurement->waist == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->waist, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Hip (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->hip ) || ( $measurement->hip == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->hip, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Thigh (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->thigh ) || ( $measurement->thigh == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->thigh, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Calf (%s)","e20r-tracker"), $Client->getLengthUnit()); ?>" class="e20r_mData"><?php echo( is_null( $measurement->calf ) || ( $measurement->calf == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->calf, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php echo sprintf( __("Total Girth (%s)","e20r-tracker"), $Client->getLengthUnit() ); ?>" class="e20r_mData"><?php echo( is_null( $measurement->girth ) || ( $measurement->girth == 0 ) ? '&mdash;' : number_format( (float) round( $measurement->girth, 2 ), 2 ) ); ?></td>
			                            <td data-th="<?php _e("Photo","e20r-tracker"); ?>" class="smallPhoto"><?php echo $this->getProgressPhoto( $measurement, $user->ID, $key ); ?></td>
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
            if ( ! $Tables->isBetaClient() ) { ?>
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
                                    <td colspan="2"><?php _e("No Progress indicators recorded.", "e20r-tracker"); ?></td>
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
            </div><?php
            }

            $html = ob_get_clean();
        }

        return $html;
    }

    public function getProgressPhoto( $data, $userId, $key = null ) {

        $Client = Client::getInstance();

        $isBeta = true; // I know... This is a bit backwards
        $imageUrl = $Client->getUserImgUrl( $userId, $data->recorded_date, 'front' );

        if ( $imageUrl === false ) {
            $imageUrl = $this->loadImage( 'front' );
            $isBeta = false;
        }

        if ( false !== stripos( $imageUrl, 'no-image-uploaded.jpg') ) {
            $imgPath = E20R_PLUGIN_DIR . '/img/no-image-uploaded.jpg';
        } else {
            // TODO: Handle cases where the user's download is located
            Utilities::get_instance()->log("Image path: {$imageUrl}");
            $imgPath = $imageUrl;
        }

        list($width, $height, $type, $attr) = getimagesize( $imgPath );
        ob_start();
        
        if ( ! is_admin() ) { ?>
        <div style="display: none;" id="e20r-progress-pop-up">
        <!-- <div id="lbp-inline-href-<?php echo ( $key ? $key : 1 ); ?>" style="padding: 10px; background: #fff"> -->
            <div id="inline-content-href-<?php echo ( $key ? $key : 1 ) ?>" style="padding: 10px; background: #fff; max-width: 400px; max-height: 600px; width: auto; height: auto;"> <?php
        }
        else { ?>
        <!-- <div id="e20r-progress-pic-<?php echo ( $key ? $key : 1 ); ?>" style="padding: 10px; background: #fff"> -->
            <div id="e20r-progress-pic-<?php echo ( $key ? $key : 1 ); ?>" style="display: none;"><?php
        }?>
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
                            <span class="value"><?php echo number_format( (float) round( $data->weight, 1 ), 1 ) . " " . $this->prettyUnit( $Client->getWeightUnit() );?> </span>

                            <span class="key">Total Girth:</span>
                            <span class="value"><?php echo number_format( (float) round( $data->girth, 2 ), 2 ) . " " . $this->prettyUnit($Client->getLengthUnit()); ?></span>
                        </div>
                    </div>
                </div>
            </div> <?php
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
        
        return ob_get_clean();
    }

    public function loadHelp($type = null) {

        ob_start();

        switch ($type) {

            case 'weight';?>
                <h3><?php _e('Body Weight Measurement Guide', 'e20r-tracker' ); ?></h3>
                <table class="e20r-help-items">
                    <tbody>
                    <tr>
                        <td>
                            <div class="e20r-number-col"><?php _e( '1', 'e20r-tracker' ); ?></div>
                        </td>
                        <td class="content">
                            <h4><?php _e('Find a scale.', 'e20r-tracker' ); ?></h4>
                            <?php _e( "Start with a good scale, preferably a pre-calibrated digital scale or a beam scale (like the kind you find in doctor's offices).", 'e20r-tracker' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col"><?php _e( '2', 'e20r-tracker' ); ?></div>
                        </td>
                        <td class="content">
                            <h4><?php _e( 'Test and calibrate.', 'e20r-tracker' ); ?></h4>
                            <?php _e( "Once you have a good scale, determine its accuracy and reliability. To do so, select an
                            object of known weight (in a lab scientists use a pre-calibrated reference weight) and weigh it
                            five times successively. If these five readings are within one pound or so of both the known weight
                            and the other readings, your scale is as good as you're going to find. If the variation is
                            greater than two pounds, you'll need to re-set your scale (if it's digital). If it still doesn't
                            produce reliable or accurate readings, you need a better scale.", 'e20r-tracker' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col"><?php _e( '3', 'e20r-tracker' ); ?></div>
                        </td>
                        <td class="content">
                            <h4><?php _e('Test again.', 'e20r-tracker' ); ?></h4>
                            <?php _e( "Each time you weigh yourself, make sure to test your known object a few times (three
                            times or so) beforehand to see if the scale is accurate and reliable on that day. The same
                            rules as above apply.", 'e20r-tracker' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col"><?php _e('4', 'e20r-tracker' ); ?></div>
                        </td>
                        <td class="content">
                            <h4><?php _e( 'Weigh yourself.', 'e20r-tracker' ); ?></h4>
                            <?php _e("Next, step on the scale yourself. Record your first reading. Weigh yourself two
                            additional times and record these readings. If your measurements are within one pound of each
                            other, take the average of the three. If not, weigh a fourth time and average the closest three
                            measurements.", 'e20r-tracker' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="e20r-number-col"><?php _e( '5', 'e20r-tracker' ); ?></div>
                        </td>
                        <td class="content">
                            <h4><?php _e('Input your measurement.', 'e20r-tracker' ); ?></h4>
                            <?php _e( 'Record the mean (average) body weight measurement. Make sure you are recording in the
                            correct units: if your scale is in pounds, record in pounds; if it is in kilograms,
                            record in kilograms; etc. You can change your measurement units at any time by clicking
                            the "change this" link next to the "Preferred measurement units" at the top of the Body
                            Weight Measurement section.', 'e20r-tracker' ); ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php
                $html = ob_get_clean();
                break;

            case 'girth': ?>
                <h3><?php _e('Girth measurement guide', 'e20r-tracker' ); ?></h3>
                <table class="e20r-help-items">
                    <tbody>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '1', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Pick up a good measuring tape.', 'e20r-tracker' ); ?></h4>
                                <?php printf( __('A simple cloth measuring tape will work, but we prefer to use the MyoTape device from
                                (%1$swww.accumeasurefitness.com%2$s). It\'s inexpensive, it\'s handy, and since the tape encircles the body part with a consistent tightness, it allows for more accurate and consistent readings.', 'e20r-tracker' ), '<a href="http://www.accumeasurefitness.com/">', '</a>' ); ?>
                                <p align="center">
                                    <img class="alignnone" src="<?php echo E20R_PLUGINS_URL; ?>/img/myotape.png" alt="" width="302" height="267" />
                                </p>
                            </td>
                            </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '2', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _('Choose the body parts to record.', 'e20r-tracker' ); ?></h4>
                                <?php _e('As part of the Bit Better Coaching program, we typically ask you to record girths for the following
                                eight body parts:', 'e20r-tracker' ); ?>
                                <ul>
                                    <li><strong><?php _e('Neck girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure just below the Adam’s apple and at the level of the 7th cervical vertebra.', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e('Shoulder girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure at the widest point of the shoulders, around the entire shoulder area.', 'e20r-tracker' ); ?>
                                        <?php _e('Make sure you’re standing upright and breathing normally. Record the measure after a normal (not a forced) exhalation.', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e('Chest girth:', 'e20r-tracker' ); ?></strong> <?php _e('The maximal horizontal girth of the chest at the nipple line.', 'e20r-tracker' ); ?>
                                        <?php _e('Stand upright and pass the tape measure over the shoulder blades and under the armpits. Record the measure after a normal (not a forced) exhalation.', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e('Upper arm girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure halfway between the elbow and the bony point on the top of your shoulder.', 'e20r-tracker' ); ?>
                                        <?php _e('Measure this distance if you have to and take the mid-point.', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e('Waist girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure at the navel. Stand upright and breathe normally with the abdomen relaxed.', 'e20r-tracker' ); ?>
                                        <?php _e('Record the measure after a normal (not a forced) exhalation.', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e( 'Hip girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure around the glutes at the level of maximal circumference (the widest point).', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e('Thigh girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure at the halfway point between the center of the kneecap and inguinal crease (the line where leg inserts into trunk). Measure the distance if you have to and take the mid-point.', 'e20r-tracker' ); ?></li>
                                    <li><strong><?php _e('Calf girth:', 'e20r-tracker' ); ?></strong> <?php _e('Measure at the widest point of your calf muscle.', 'e20r-tracker' ); ?></li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '3', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">&nbsp;
                                <h4><?php _e('Wrap tape around body part.', 'e20r-tracker' ); ?></h4>
                                <?php _e("If you’re using the MyoTape device (which we recommend), pull the end of the tape
                                around the body part that you want to measure (pressing the button in the center will
                                make it easier to pull) and place the rod at the end of the tape into the circular slot.", 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '4', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Tighten tape to a snug fit.', 'e20r-tracker' ); ?></h4>
                                <?php _e("Press the button in the center and let the tape retract to a snug fit. Make sure the tape
                                is perpendicular to the body part and parallel with the ground. If you’re using a regular
                                cloth tape, try to achieve a consistent tightness with each measurement.", 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '5', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Read and record measurement.', 'e20r-tracker' ); ?></h4>
                                <?php _e("Read your measurement on the outer edge of the tape measure (the end opposite the locked
                                in rod) and write it down. Remember to take three different measurements at each site and
                                record the average of the three.", 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '6', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">&nbsp;
                                <h4><?php _e("Input your measurements.", 'e20r-tracker' ); ?></h4>
                                <?php _e('Once you have calculated the average measurements for all eight sites, return to the
                                Coaching Progress Update form and record your measurements. Make sure you are
                                recording in the correct units: if your tape measure is in inches, record in inches;
                                if it is in centimetres, record in centimetres. You can change your measurement units
                                at any time by clicking the "change this" link next to the "Preferred measurement
                                units" at the top of the Girth Measurements section.', 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table><?php
                $html = ob_get_clean();
                break;

            case 'photo': ?>
                <h3><?php _e('Photo Guide', 'e20r-tracker' ); ?></h3>
                <table class="e20r-help-items">
                    <tbody>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '1', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Clothing and location.', 'e20r-tracker' ); ?></h4>
                                <?php _e('Dressed in a swimsuit, or small pair of shorts and fitted/revealing top like a sports bra, stand against a bare wall.', 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '2', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Camera setup.', 'e20r-tracker' ); ?></h4>
                                <?php _e('Set up your camera about 5-7 feet away from you so that it can capture your whole body
                                from head to toe. You can use a tripod or have a friend snap the photo.', 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '3', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Lighting.', 'e20r-tracker' ); ?></h4>
                                <?php _e("Make sure the room is well-lit and that you use the flash when taking your photo.
                                However, make sure there isn't a ton of overhead light; you don't want to cast
                                shadows.", 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '4', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Write it down.', 'e20r-tracker' ); ?></h4>
                                <?php _e('Write down exactly how you took the before pictures (camera settings, lighting
                                conditions, how far away the camera was, etc.). This will help you duplicate the same
                                conditions in the future.', 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '5', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Take three photos.', 'e20r-tracker' ); ?></h4>
                                <?php _e('Take three total photographs: one of your front side, one of your left side and one
                                of your back side.', 'e20r-tracker' ); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="e20r-number-col"><?php _e( '6', 'e20r-tracker' ); ?></div>
                            </td>
                            <td class="content">
                                <h4><?php _e('Copy photos to your hard drive.', 'e20r-tracker' ); ?></h4>
                                <?php printf( __( '%1$sConnect your camera to your computer, typically with the USB cable that comes
                                with the camera. You should be able to copy the photos to a folder on your computer.%2$s', 'e20r-tracker' ), '<p>', '</p>' ); ?>
                                <?php printf( '$1$sHere\'s a brief video on the basics of getting photos off your digital camera
                                and on to your Windows XP computer: %2$s%3$s', '<p>', '<a href="http://www.youtube.com/watch?v=W_hxdY7g-sw">
                                http://www.youtube.com/watch?v=W_hxdY7g-sw</a>', '</p>' ); ?>
                                <?php printf( __( '%1$sHere is another video on copying photos to a Windows Vista computer: %2$s%3$s', 'e20r-tracker' ), '<p>','<a href="http://www.youtube.com/watch?v=nnlEcMwikuw">http://www.youtube.com/watch?v=nnlEcMwikuw</a>', '</p>' ); ?>
                                <p><?php printf( __('If you have a Mac, you might want to view the Apple article on %1$sConnecting Your Camera%2$s', 'e20r-tracker' ), '<a href="http://support.apple.com/kb/HT2498">', '</a>'); ?></p>
                            </td>
                        </tr>
                        <tr>
                        <td>
                            <div class="e20r-number-col"><?php _e( '7', 'e20r-tracker' ); ?></div>
                        </td>
                        <td class="content">
                            <h4><?php _e('Upload the photos.', 'e20r-tracker' ); ?></h4>
                            <?php _e('Using the photo upload form on the Coaching Progress Update page, browse to the photos
                            you copied. Select each one in the appropriate box (the front photo for the "Front
                            View" box, the side photo for the "Side View" box, etc) and upload them, one at a time.', 'e20r-tracker' ); ?>
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

	    $Client = Client::getInstance();

        return 'background-image: url("' . E20R_PLUGINS_URL . "/img/{$what}-{$type}" . ( $Client->getGender() == 'f' ? '-f.png");' : '-m.png");' );
    }

    private function prettyUnit( $type ) {

        // Utilities::get_instance()->log("Pretty Unit request: {$type}");

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

        $Client = Client::getInstance();

        ob_start();
        ?>
        <div class="e20r-measurement-setting" style="margin-bottom: 24px;">
            <?php _e('Preferred weight units:', 'e20r-tracker' ); ?>
            <span id="preferred-weight-unit" class="e20r-change-units"><?php echo $this->prettyUnit($Client->getWeightUnit()); ?></span>
            <span class="e20r-change-unit-dropdown"><select id="selected-weight-unit">
                <option value="lbs"<?php selected( $Client->getWeightUnit(), 'lbs' ); ?>><?php echo $this->prettyUnit('lbs'); ?></option>
                <option value="kg"<?php selected( $Client->getWeightUnit(), 'kg' ); ?>><?php echo $this->prettyUnit('kg'); ?></option>
                <option value="st"<?php selected( $Client->getWeightUnit(), 'st' ); ?>><?php echo $this->prettyUnit('st'); ?></option>
                <option value="st"<?php selected( $Client->getWeightUnit(), 'st_uk' ); ?>><?php echo $this->prettyUnit('st_uk'); ?></option>
            </select></span>
            <span class="e20r-change-weight-unit-link">(<a class="change-measurement-unit" data-dimension="weight"><?php _e("change this", "e20r-tracker"); ?></a>)</span>
            <span class="e20r-cancel-weight-unit-link">(<a class="cancel-measurement-unit-update"><?php _e("cancel", "e20r-tracker"); ?></a>)</span>
        </div>
        <?php
        return ob_get_clean();
    }

    private function showChangeLengthUnit() {

        $Client = Client::getInstance();
        ob_start();
        ?>
        <div class="e20r-measurement-setting" style="margin-bottom: 24px;">
            <?php _e( 'Preferred length units:', 'e20r-tracker' ); ?>
            <span id="preferred-length-unit" class="e20r-change-units"><?php echo $this->prettyUnit( $Client->getLengthUnit() ); ?></span>
            <span class="e20r-change-unit-dropdown"><select id="selected-length-unit">
                <option value="in"<?php selected( $Client->getLengthUnit(), 'in' ); ?>><?php echo $this->prettyUnit('in'); ?></option>
                <option value="cm"<?php selected( $Client->getLengthUnit(), 'cm' ); ?>><?php echo $this->prettyUnit('cm'); ?></option>
            </select></span>
            <span class="e20r-change-length-unit-link">(<a class="change-measurement-unit" data-dimension="length"><?php _e("change this", "e20r-tracker"); ?></a>)</span>
            <span class="e20r-cancel-length-unit-link">(<a class="cancel-measurement-unit-update"><?php _e("cancel", "e20r-tracker"); ?></a>)</span>
        </div>
        <?php
        return ob_get_clean();

    }

    private function loadImage( $side ) {

        $id = ( isset( $this->data->{$this->fields[$side . "_image"]} ) ?  $this->data->{$this->fields[$side . "_image"]} : null );

        if ( ( $url = wp_get_attachment_thumb_url( $id ) ) === false ) {

            $url = E20R_PLUGINS_URL . "/img/no-image-uploaded.jpg";
        }

        return $url;
    }

    private function resizeImage( $size ) {
        $Tables = Tables::getInstance();

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