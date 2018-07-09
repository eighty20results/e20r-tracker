<?php
/*
    Copyright 2015-2018 Thomas Sjolshagen / Wicked Strong Chicks, LLC (info@eighty20results.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace E20R\Tracker\Views;

use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Client;
use E20R\Utilities\Utilities;

/**
 * Class Program_View
 * @package E20R\Tracker\Views
 */
class Program_View extends Settings_View {
    
    /**
     * Instance of this class
     * @var null|Program_View
     */
    private static $instance = null;
    
    /**
     * @var null|\stdClass
     */
    private $programs = null;

    /**
     * Program_View constructor.
     *
     * @param null|\stdClass  $programData
     */
    public function __construct( $programData = null ) {

        $this->programs = $programData;
    }

    /**
	 * @return Program_View|null
	 */
	static function getInstance() {

    	if ( is_null( self::$instance ) ) {
    		self::$instance = new self;
	    }

	    return self::$instance;
	}

    public function old_viewSettingsBox( $programData, $feeds ) {

        $Tracker = Tracker::getInstance();
        
        Utilities::get_instance()->log("Supplied data: " . print_r($programData, true)); ?>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-program-settings'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-program-id" value="<?php echo ( ( ! empty($programData) ) ? $programData->id : 0 ); ?>">
                <table id="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-program-startdate"><?php _e("Starts on", "e20r-tracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-enddate"><?php _e("Ends on", "e20r-tracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-group"><?php _e("Membership Level", "e20r-tracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-dripfeed"><?php _e("Lesson feed", "e20r-tracker"); ?></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-intake_form"><?php _e("Intake form", "e20r-tracker"); ?></label></th>
<!--	                    <th class="e20r-label header"><label for="e20r-program-activity_sequences"><?php _e("Activity feed", "e20r-tracker"); ?></label></th> -->
                    </tr>
                    <tr>
                        <td colspan="5"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        if ( is_null( $programData->startdate ) ) {
                            $start = '';
                        } else {
                            $start = new \DateTime( $programData->startdate );
                            $start = $start->format( 'Y-m-d' );
                        }

                        if ( is_null( $programData->enddate ) ) {
                            $end = '';
                        } else {
                            $end = new \DateTime( $programData->enddate );
                            $end = $end->format( 'Y-m-d' );
                        }

                        Utilities::get_instance()->log( "Program - Start: {$start}, End: {$end}" );
                        ?>
                        <tr id="<?php echo $programData->id; ?>" class="program-inputs">
                            <td class="text-input">
                                <input type="date" id="e20r-program-startdate" name="e20r-program-startdate" value="<?php echo $start; ?>">
                            </td>
                            <td class="text-input">
                                <input type="date" id="e20r-program-enddate" name="e20r-program-enddate" value="<?php echo $end; ?>">
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-group" name="e20r-program-group">
                                    <option value="-1" <?php selected( $programData->group, 0 ); ?>><?php _e("Not Applicable", "e20r-tracker"); ?></option>
                                    <?php
                                        $levels = $Tracker->getMembershipLevels( null, true );

                                        foreach( $levels as $id => $name ) { ?>

                                            <option value="<?php echo $id; ?>" <?php echo selected( $programData->group, $id ); ?>><?php echo $name; ?></option> <?php
                                        }
                                    ?>
                                </select>
                                <!-- <input type="text" id="e20r-program-groups" name="e20r-program-groups" size="25" value="<?php echo( ( ! empty( $programData->groups ) ) ? $programData->program_shortname : null ); ?>"> -->
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-sequences" name="e20r-program-sequences[]" multiple="multiple">
                                    <option value="0" <?php echo in_array( 0, $programData->sequences ) ? ' selected="selected" ' : null; ?>>Not configured</option>
                                    <?php
                                        foreach($feeds as $df) {

	                                        if ( !empty( $programData->sequences ) ) {
		                                        $selected = ( in_array( $df->ID, $programData->sequences ) ? ' selected="selected" ' : null );
	                                        }
	                                        else {
		                                        $selected = null;
	                                        } ?>
                                            <option value="<?php echo $df->ID;?>"<?php echo $selected; ?>><?php echo esc_textarea($df->post_title);?> (#<?php echo $df->ID;?>)</option>
                                <?php   } ?>
                                </select>
                            </td>
	                        <td>
		                        <select class="select2-container" id="e20r-program-intake_form" name="e20r-program-intake_form">
			                        <option value="-1" <?php selected( -1, $programData->intake_form) ?>>No intake form/page needed</option>
			                        <?php

			                        $pages = get_pages();
			                        $posts = get_posts();

			                        $list = array_merge( $pages, $posts );

			                        wp_reset_postdata();

			                        foreach( $list as $p ) { ?>
	                                    <option value="<?php echo $p->ID; ?>"<?php selected( $p->ID, $programData->intake_form );?>><?php echo $p->post_title; ?></option> <?php
                                    }

                                    ?>
		                        </select>
	                        </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }

    public function viewSettingsBox( $programData, $feeds ) {

        $Tracker = Tracker::getInstance();
        $Client = Client::getInstance();
        
        $pages = get_pages();
        $posts = get_posts();

        $list = array_merge( $pages, $posts );
        $weekdays = array(
                0 => __( 'Sunday', 'e20r-tracker' ),
                1 => __('Monday', 'e20r-tracker'),
                2 => __('Tuesday', 'e20r-tracker'),
                3 => __('Wednesday', 'e20r-tracker'),
                4 => __('Thursday', 'e20r-tracker'),
                5 => __('Friday', 'e20r-tracker'),
                6 => __('Saturday', 'e20r-tracker'),
        );
        
        $coaches = $Client->get_coach();

        wp_reset_postdata(); ?>
        <style>
            .select2-container {min-width: 75px; max-width: 300px; width: 90%;}
        </style>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-program-settings'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-program-id" value="<?php echo ( ( ! empty($programData) ) ? $programData->id : 0 ); ?>">
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-program-startdate"><strong><?php _e("Starts on", "e20r-tracker"); ?></strong></label></th>
                        <th class="e20r-label header"><label for="e20r-program-enddate"><strong><?php _e("Ends on", "e20r-tracker"); ?></strong></label></th>
                        <th class="e20r-label header"><label for="e20r-program-measurement_day"><strong><?php _e("Measurement day", "e20r-tracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="3"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        if ( empty( $programData->startdate ) ) {
                            $start = '';
                        } else {

                            $start = new \DateTime( $programData->startdate);
                            $start = $start->format('Y-m-d');
                        }

                        if ( empty( $programData->enddate ) ) {
                            $end = '';
                        } else {
                            $end = new \DateTime( $programData->enddate );
                            $end = $end->format( 'Y-m-d' );
                        }

                        Utilities::get_instance()->log( "Program - Start: {$start}, End: {$end}" );
                        ?>
                        <tr id="<?php echo $programData->id; ?>" class="program-inputs">
                            <td class="text-input">
                                <input type="date" id="e20r-program-startdate" name="e20r-program-startdate" value="<?php echo $start; ?>">
                            </td>
                            <td class="text-input">
                                <input type="date" id="e20r-program-enddate" name="e20r-program-enddate" value="<?php echo $end; ?>">
                            </td>
	                        <td>
                                <select class="select2-container" name="e20r-program-measurement_day" id="e20r-program-measurement_day">
                                <?php
                                foreach ($weekdays as $day_id => $day ) { ?>
                                    <option value="0" <?php selected( $day_id, $programData->measurement_day); ?>><?php esc_attr_e( $day ); ?></option>
                                <?php
                                } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-program-group"><strong><?php _e("Membership level", "e20r-tracker"); ?></strong></label></th>
                        <th class="e20r-label header"><label for="e20r-program-dripfeed"><strong><?php _e("Lesson/Reminder feed", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-intake_form"><strong><?php _e("Intake form", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-sales_page_ids"><strong><?php _e("Sales page", "e20r-tracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="4"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
                            <td>
                                <select class="select2-container" id="e20r-program-group" name="e20r-program-group">
                                    <option value="-1" <?php selected( $programData->group, 0 ); ?>><?php _e("Not Applicable", "e20r-tracker"); ?></option>
                                    <?php
                                        $levels = $Tracker->getMembershipLevels( null, true );

                                        foreach( $levels as $id => $name ) { ?>

                                            <option value="<?php esc_attr_e( $id ); ?>" <?php selected( $programData->group, $id ); ?>><?php esc_html_e( $name ); ?></option> <?php
                                        }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-sequences" name="e20r-program-sequences[]" multiple="multiple">
                                    <option value="0" <?php echo in_array( 0, $programData->sequences ) ? ' selected="selected" ' : null; ?>><?php _e('Not configured', 'e20r-tracker' ); ?></option>
                                    <?php
                                        foreach($feeds as $df) {

	                                        if ( !empty( $programData->sequences ) ) {
		                                        $selected = ( in_array( $df->ID, $programData->sequences ) ? ' selected="selected" ' : null );
	                                        }
	                                        else {
		                                        $selected = null;
	                                        } ?>
                                            <option value="<?php echo $df->ID;?>"<?php echo $selected; ?>><?php echo esc_textarea($df->post_title);?> (#<?php echo $df->ID;?>)</option>
                                <?php   } ?>
                                </select>
                            </td>
	                        <td>
		                        <select class="select2-container" id="e20r-program-intake_form" name="e20r-program-intake_form">
			                        <option value="-1" <?php selected( -1, $programData->intake_form) ?>>No intake form/page needed</option><?php

			                        foreach( $list as $p ) { ?>
	                                    <option value="<?php echo $p->ID; ?>"<?php selected( $p->ID, $programData->intake_form );?>><?php echo esc_textarea($p->post_title); ?></option> <?php
                                    } ?>
		                        </select>
	                        </td>
                            <td>
                                <select class="select2-container" id="e20r-program-sales_page_ids" name="e20r-program-sales_page_ids[]" multiple="multiple">
                                    <option value="-1" <?php echo ( empty( $programData->sales_page_ids) || in_array( -1, $programData->sales_page_ids)  ? 'selected="selected"' : null); ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php esc_attr_e( $p->ID ) ;?>"<?php echo ( isset( $programData->sales_page_ids) && in_array( $p->ID, $programData->sales_page_ids) ? 'selected="selected"' : null); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
	                    <th class="e20r-label header"><label for="e20r-program-activity_page_id"><strong><?php _e("Activity Page", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-dashboard_page_id"><strong><?php _e("Dashboard Page", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-progress_page_id"><strong><?php _e("Status Page", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-measurements_page_id"><strong><?php _e("Measurements Page", "e20r-tracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="4"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
	                        <td>
                                <select class="select2-container" id="e20r-program-activity_page_id" name="e20r-program-activity_page_id">
                                    <option value="-1" <?php selected( -1, $programData->activity_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->activity_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-dashboard_page_id" name="e20r-program-dashboard_page_id">
                                    <option value="-1" <?php selected( -1, $programData->dashboard_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->dashboard_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-progress_page_id" name="e20r-program-progress_page_id">
                                    <option value="-1" <?php selected( -1, $programData->progress_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->progress_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-measurements_page_id" name="e20r-program-measurements_page_id">
                                    <option value="-1" <?php selected( -1, $programData->measurements_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->measurements_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
	                    <th class="e20r-label header"><label for="e20r-program-welcome_page_id"><strong><?php _e("Preparation Page", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-incomplete_intake_form_page"><strong><?php _e("Incomplete Intake Form", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-account_page_id"><strong><?php _e("Account Profile Page", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-contact_page_id"><strong><?php _e("Contact the Coach Page", "e20r-tracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="4"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
	                        <td>
                                <select class="select2-container" id="e20r-program-welcome_page_id" name="e20r-program-welcome_page_id">
                                    <option value="-1" <?php selected( -1, $programData->welcome_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->welcome_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-incomplete_intake_form_page" name="e20r-program-incomplete_intake_form_page">
                                    <option value="-1" <?php selected( -1, $programData->incomplete_intake_form_page) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->incomplete_intake_form_page ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-account_page_id" name="e20r-program-account_page_id">
                                    <option value="-1" <?php selected( -1, $programData->account_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->account_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-contact_page_id" name="e20r-program-contact_page_id">
                                    <option value="-1" <?php selected( -1, $programData->contact_page_id) ?>><?php _e("No page defined", "e20r-tracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->contact_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
	                    <th class="e20r-label header"><label for="e20r-program-male_coaches"><strong><?php _e("Coaches for Male clients", "e20r-tracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-female_coaches"><strong><?php _e("Coaches for Female clients", "e20r-tracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="2"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
	                        <td>
                                <select class="select2-container" id="e20r-program-male_coaches" name="e20r-program-male_coaches[]" multiple="multiple">
                                    <option value="-1" <?php in_array( -1, $programData->male_coaches) ? 'selected="selected"' : null; ?>><?php _e("None added", "e20r-tracker");?></option><?php

                                foreach( $coaches as $cId => $cName ) {
                                    $selected = (in_array( $cId, $programData->male_coaches ) ? 'selected="selected"' : null );?>
                                    <option value="<?php echo $cId;?>" <?php echo $selected; ?>><?php echo esc_textarea($cName);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-female_coaches" name="e20r-program-female_coaches[]" multiple="multiple">
                                    <option value="-1" <?php in_array( -1, $programData->female_coaches) ? 'selected="selected"' : null; ?>><?php _e("None added", "e20r-tracker");?></option><?php

                                foreach( $coaches as $cId => $cName ) {
                                    $selected = (in_array( $cId, $programData->female_coaches ) ? 'selected="selected"' : null );?>
                                    <option value="<?php echo $cId; ?>" <?php echo $selected; ?>><?php echo esc_textarea( $cName);?></option><?php
                                } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <script>
                    jQuery('.e20r-editform').find('.select2-container').each(function(){
                        jQuery(this).select2();
                    });
                    /*
                    jQuery('#e20r-program-sequences').select2();
                    jQuery('#e20r-program-intake_form').select2();
                    jQuery('#e20r-program-measurement_day').select2();
                    jQuery('#e20r-program-activity_page_id').select2();
                    jQuery('#e20r-program-progress_page_id').select2();
                    jQuery('#e20r-program-dashboard_page_id').select2();
                    */
                </script>

            </div>
        </form>
    <?php
    }

    /****************** Obsolete ********************/

    public function programSelector( $add_new = true, $selectedId = 0, $listId = null, $disabled = false ) {

        // $this->programs = $this->load_program_info( null, $add_new ); // Get all programs in the DB

        ob_start();
        ?>
        <select name="e20r_choose_program" id="e20r-choose-program<?php echo ( is_null( $listId ) ? "" : "_{$listId}" ); ?>" <?php echo ( $disabled == true ? 'disabled' : ''); ?>>
            <?php

            foreach( $this->programs as $program ) {
                ?><option value="<?php echo esc_attr( $program->id ); ?>"  <?php selected( $selectedId, $program->id, true); ?>><?php echo esc_attr( $program->program_name ); ?></option><?php
            }
            ?>
        </select>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function viewProgramSelectDropDown( $add_new = true ) {

        // Generate a select box for the program and highlight the requested ProgramId

        // $this->programs = $this->load_program_info( null, $add_new ); // Get all programs in the DB

        ob_start();
        ?>
        <label for="e20r_choose_programs">Program</label>
        <span class="e20r-program-select-span">
            <select name="e20r_choose_programs" id="e20r-choose-program">
                <?php

                // Utilities::get_instance()->log("Select List " . print_r( $this->programs, true ) );

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

    public function relatedProgramsMeta() {
        ?>
        <div id="e20r-tracker-program-settings">
			<?php
				$box = $this->ProgramSettings();
				echo $box['html'];
			?>
			</div>
        <?
    }

    public function viewProgramEditSelect() {

        //$this->programs = $this->load_program_info( null, true ); // Load all programs & generate a select <div></div>
        $utils = Utilities::get_instance();
        
        ob_start();

        ?>
        <div id="program-select-div">
            <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_select_programs_nonce' ); ?>
                <div class="e20r-select">
                    <input type="hidden" name="hidden_e20r_program_id" id="hidden_e20r_program_id" value="0" >
                    <label for="e20r_programs"><?php _e('Select Program', 'e20r-tracker'); ?></label>
                    <span class="e20r-program-select-span">
                        <select name="e20r_programs" id="e20r_programs">
                            <?php

                            Utilities::get_instance()->log("Program_View:: - List: " . print_r( $this->programs, true ) );
                            foreach( $this->programs as $program ) {
                                ?><option value="<?php esc_attr_e( $program->id ); ?>"  ><?php esc_html_e( $program->program_name ); ?></option><?php
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

    public function view_listPrograms() {

        $utils = Utilities::get_instance();
        
        // Fetch the Checkin Item we're looking to manage
        // $program_list = $this->load_program_info( null, false );
        Utilities::get_instance()->log("Loading list of programs");
        ob_start();
        ?>
        <H1>List of Programs</H1>
        <hr />
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r_tracker_edit_programs'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden_e20r_program_id" id="hidden_e20r_program_id" value="<?php echo ( ( ! empty($program) ) ? $program->id : 0 ); ?>">
                <table id="e20r-list-programs-table">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-program_id"><?php _e('Edit', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program_id"><?php _e('ID', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program_name"><?php _e('Name', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-startdate"><?php _e('Starts on', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-enddate"><?php _e('Ends on', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-descr"><?php _e( 'Description', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-memberships"><?php _e('Belongs to (Membership)', 'e20r-tracker'); ?></label></th>
                        <th class="e20r-save-col hidden"><?php _e('Save', 'e20r-tracker'); ?></th>
                        <th class="e20r-cancel-col hidden"><?php _e('Cancel', 'e20r-tracker'); ?></th>
                        <th class="e20r-delete-col hidden"><?php _e('Remove', 'e20r-tracker'); ?></th>
                        <th class="e20r-label header hidden"></th>
                    </tr>
                    <tr>
                        <td colspan="11"><hr/></td>
                        <!-- select for choosing the membership type to tie this check-in to -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php

                    if ( count($this->programs) > 0) {

                        foreach ($this->programs as $program) {

                            if ( is_null( $program->startdate ) ) {
                                $start = '';
                            } else {
                                $start = new \DateTime( $program->startdate );
                                $start = $start->format( 'Y-m-d' );
                            }

                            if ( is_null( $program->enddate ) ) {
                                $end = '';
                            } else {
                                $end = new \DateTime( $program->enddate );
                                $end = $end->format( 'Y-m-d' );
                            }

                            $pid = $program->id;

                            Utilities::get_instance()->log( "Program - Start: {$start}, End: {$end}" );
                            ?>
                            <tr id="<?php esc_attr_e( $pid ); ?>" class="program-inputs">
                                <td class="text-input">
                                    <input type="checkbox" name="edit_<?php esc_attr_e( $pid ); ?>" id="edit_<?php echo $pid ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-program_id_<?php esc_attr_e( $pid ); ?>" disabled name="e20r_program_id" size="5" value="<?php echo( ( ! empty( $program->id ) ) ? $program->id : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-program_name_<?php esc_attr_e( $pid ); ?>" disabled name="e20r_program_name" size="25" value="<?php echo( ( ! empty( $program->program_name ) ) ? $program->program_name : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-program-startdate_<?php esc_attr_e( $pid ); ?>" disabled name="e20r_program_startdate" value="<?php echo $start; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-program-enddate_<?php esc_attr_e( $pid ); ?>" disabled name="e20r_program_enddate" value="<?php echo $end; ?>">
                                </td>
                                <td class="text-descr">
                                    <textarea class="expand" id="e20r-program-descr_<?php esc_attr_e( $pid ); ?>" disabled name="e20r_program_descr" rows="1" wrap="soft"><?php echo ( ! empty( $program->description ) ) ? $program->description : null; ?></textarea>
                                </td>
                                <td class="select-input">
                                    <?php echo $this->view_selectMemberships( $program->member_id, $pid ); ?>
                                </td>
                                <td class="hidden save-button-row" id="e20r-td-save_<?php esc_attr_e( $pid ); ?>">
                                    <a href="#" class="e20r-save-edit-program button">Save</a>
                                </td>
                                <td class="hidden cancel-button-row" id="e20r-td-cancel_<?php esc_attr_e( $pid ); ?>">
                                    <a href="#" class="e20r-cancel-edit-program button">Cancel</a>
                                </td>
                                <td class="hidden delete-button-row" id="e20r-td-delete_<?php esc_attr_e( $pid ); ?>">
                                    <a href="#" class="e20r-delete-program button">Remove</a>
                                </td>
                                <td class="hidden-input">
                                    <input type="hidden" class="hidden_id" value="<?php esc_attr_e( $pid ); ?>">
                                </td>
                            </tr>
                        <?php
                        }
                    }
                    else { ?>
                        <tr>
                            <td colspan="7"><?php _e('No programs found in the database. Please add a new program by clicking the "Add New" button.', 'e20r-tracker'); ?></td>
                        </tr><?php
                    }
                    ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="7"><hr/></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="add-new" style="text-align: left;"><a class="e20r-button button" id="e20r-add-new-program" href="#">Add New</a></td>
                    </tr>
                    <tr id="add-new-program" class="hidden">
                        <td class="text-input"><input type="checkbox" disabled name="edit" id="edit"></td>
                        <td class="text-input"><input type="text" id="e20r-program_id" name="e20r_program_id" disabled size="5" value="auto"></td>
                        <td class="text-input"><input type="text" id="e20r-program_name" name="e20r_program_name" size="25" value=""></td>
                        <td class="text-input"><input type="date" id="e20r-program-startdate" name="e20r_program_startdate" value=""></td>
                        <td class="text-input"><input type="date" id="e20r-program-enddate" name="e20r_program_enddate" value=""></td>
                        <td class="text-descr"><textarea class="expand" id="e20r-program-descr" name="e20r_program_descr" rows="1" wrap="soft"></textarea></td>
                        <td class="select-input"><?php echo $this->view_selectMemberships( 0, null ); ?></td>
                        <td class="save"><a class="e20r-button button" id="e20r-save-new-program" href="#"><?php _e('Save', 'e20r-tracker'); ?></a></td>
                        <td class="cancel"><a class="e20r-button button" id="e20r-cancel-new-program" href="#"><?php _e('Cancel', 'e20r-tracker'); ?></a></td>
                        <td class="hidden"><!-- Nothing here, it's for the delete/remove button --></td>
                        <td class="hidden-input"><input type="hidden" class="hidden_id" value="<?php esc_attr_e( $pid ); ?>"></td>
                    </tr>
                    </tfoot>
                </table>

            </div>
        </form>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function view_selectMemberships( $mId, $rowId = null ) {

        if ( function_exists( 'pmpro_getAllLevels' ) ) {

            $levels = pmpro_getAllLevels(false, true);

            ob_start();

            if ( ! empty( $rowId ) ) {

                ?><select name="e20r-memberships_<?php esc_attr_e($rowId ); ?>" id="e20r-memberships_<?php esc_attr_e( $rowId ); ?>" disabled><?php
            }
            else {

                ?><select name="e20r-memberships" id="e20r-memberships"><?php
            }

            foreach ( $levels as $level ) { ?>

                <option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( $level->id, $mId ); ?>><?php echo esc_attr( $level->name ); ?></option><?php
            } ?>

            </select>
            <?php
            $html = ob_get_clean();
        }

        return $html;
    }

    public function view_programPostMetabox() {
        $utils = Utilities::get_instance();
        Utilities::get_instance()->log("Rendering metabox...");

        ob_start();
        ?>
        <div class="submitbox" id="e20r-program-postmeta">
            <?php wp_nonce_field('e20r-tracker-program-meta', 'e20r-tracker-program-nonce');?>
            <div id="minor-publishing">
                <div id="e20r-postmeta-setprogram">
                    <?php echo $this->view_programMetaTable(); ?>
                </div>
            </div>
        </div>
        <?php

        $metabox = ob_get_clean();

        echo $metabox;

    }

    public function view_programMetaTable() {

        global $post;
        $utils = Utilities::get_instance();
        
        // $this->init();

        $pgms = get_post_meta($post->ID, 'e20r_tracker_program_ids', true);
        $pgms = array_unique( $pgms );

        Utilities::get_instance()->log("Program_View:: Read from post meta for {$post->ID}: " . print_r( $pgms, true));

        $belongs_to = array();

        if ( ! empty( $pgms ) ) {

            foreach( $pgms as $id ) {

                if ( $id != 0) {
                    $belongs_to[] = $id;
                }
            }
        }

        $belongs_to[] = 0;

        ob_start();
        ?>
        <div class="seq_spinner vt-alignright"></div>
        <table style="width: 100%;" id="e20r-program-metatable">
            <tbody>
            <?php foreach( $belongs_to as $id ) { ?>
                <?php Utilities::get_instance()->log("Program_View:: - Adding rows for {$id}");?>
                <tr><td><fieldset></td></tr>
                <tr class="select-row-label<?php echo ( $id == 0 ? ' new-program-select-label' : ' program-select-label' ); ?>">
                    <td>
                        <label for="e20r-tracker-memberof-programs"><?php _e("Program:", "e20r-tracker"); ?></label>
                    </td>
                </tr>
                <tr class="e20r-select-row-input<?php echo ( $id == 0 ? ' new-e20rprogram-select' : ' program-select' ); ?>">
                    <td class="program-list-dropdown">
                        <select class="<?php echo ( $id == 0 ? 'new-e20rprogram-select' : 'e20r-tracker-memberof-programs'); ?>" name="e20r-tracker-programs[]">
                            <option value="0" <?php selected( $id, 0 ); ?>><?php _e("Not assigned", "e20r-tracker"); ?></option>
                            <?php
                            // Loop through all of the sequences & create an option list
                            foreach ( $this->programs as $program ) {
                                if ( $program->id != 0 ) {
                                    ?>
                                    <option value="<?php echo $program->id; ?>" <?php echo selected( $program->id, $id ); ?>><?php esc_html_e( $program->program_name ); ?></option><?php
                                }
                            }
                            ?>
                        </select>
                        <input type="hidden" value="<?php echo $id; ?>" class="e20r-program-oldval" name="e20r-program-oldid[]">
                    </td>
                </tr>
                <tr><td></fieldset></td></tr>
            <?php } // Foreach ?>
            </tbody>
        </table>
        <div id="e20r-tracker-new">
            <hr class="e20r-hr" />
            <a href="#" id="e20r-tracker-new-meta" class="button-primary"><?php _e( "Add", "e20r-tracker" ); ?></a>
            <a href="#" id="e20r-tracker-new-meta-reset" class="button"><?php _e( "Reset", "e20r-tracker" ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    private function showSequenceListForMetaBox() {

    }


}