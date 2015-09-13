<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/15/14
 * Time: 1:20 PM
 */

class e20rProgramView {

    private $programs = null;

    public function e20rProgramView( $programData = null ) {

        $this->programs = $programData;

    }

    public function profile_view_client_settings( $programList, $activePgm, $coachList, $coach_id ) {

	    if ( empty( $programList ) ) {
		    $programList = array();
	    }


        ob_start();
        ?>
        <h3><?php _e("E20R Tracker Settings", "e20rtracker"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="e20r-tracker-user-program"><?php _e( "Coaching program", "e20rtracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-user-program" name="e20r-tracker-user-program" class="select2-container">
                        <option value="0" <?php selected( $activePgm, 0 ) ?>>Not Applicable</option>
                        <?php

                        foreach( $programList as $id => $obj ) {
                            ?><option value="<?php echo esc_attr($id); ?>" <?php selected( $activePgm, $id ); ?>><?php echo esc_attr($obj->title); ?></option> <?php
                        }

                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="e20r-tracker-user-coach_id"><?php _e( "Assigned Coach", "e20rtracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-user-coach_id" name="e20r-tracker-user-coach_id" class="select2-container">
                        <option value="0" <?php selected( $activePgm, 0 ) ?>>Unassigned</option>
                        <?php

                        foreach( $coachList as $id => $name ) {
                            ?><option value="<?php echo esc_attr($id); ?>" <?php selected( $coach_id, $id ); ?>><?php echo esc_attr($name); ?></option> <?php
                        }

                        ?>
                    </select>
                </td>

            </tr>
        </table>
        <?php

        $html = ob_get_clean();
        return $html;
    }

    public function old_viewSettingsBox( $programData, $feeds ) {

        global $e20rTracker;

        dbg("e20rProgramView::viewProgramSettingsBox() - Supplied data: " . print_r($programData, true));
        ?>
        <form action="" method="post">
            <?php wp_nonce_field('e20r-tracker-data', 'e20r-tracker-program-settings'); ?>
            <div class="e20r-editform">
                <input type="hidden" name="hidden-e20r-program-id" id="hidden-e20r-program-id" value="<?php echo ( ( ! empty($programData) ) ? $programData->id : 0 ); ?>">
                <table id="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-program-startdate"><?php _e("Starts on", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-enddate"><?php _e("Ends on", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-group"><?php _e("Membership Level", "e20rtracker"); ?></label></th>
                        <th class="e20r-label header"><label for="e20r-program-dripfeed"><?php _e("Lesson feed", "e20rtracker"); ?></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-intake_form"><?php _e("Intake form", "e20rtracker"); ?></label></th>
<!--	                    <th class="e20r-label header"><label for="e20r-program-activity_sequences"><?php _e("Activity feed", "e20rtracker"); ?></label></th> -->
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
                            $start = new DateTime( $programData->startdate );
                            $start = $start->format( 'Y-m-d' );
                        }

                        if ( is_null( $programData->enddate ) ) {
                            $end = '';
                        } else {
                            $end = new DateTime( $programData->enddate );
                            $end = $end->format( 'Y-m-d' );
                        }

                        dbg( "Program - Start: {$start}, End: {$end}" );
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
                                    <option value="-1" <?php selected( $programData->group, 0 ); ?>><?php _e("Not Applicable", "e20rtracker"); ?></option>
                                    <?php
                                        $levels = $e20rTracker->getMembershipLevels( null, true );

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
                                <style>
                                    .select2-container {min-width: 75px; max-width: 300px; width: 90%;}
                                </style>
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

	                        <!--	                        <td>
									<select class="select2-container" id="e20r-program-activity_sequences" name="e20r-program-activity_sequences[]" multiple="multiple">
										<option value="0">Not configured</option>
										<?php

										foreach($feeds as $df) {

											$selected = ( in_array( $df->ID, $programData->sequences ) ? ' selected="selected" ' : null ); ?>
											<option value="<?php echo $df->ID;?>"<?php echo $selected; ?>><?php echo esc_textarea($df->post_title);?> (#<?php echo $df->ID;?>)</option>
										<?php   } ?>
									</select>
									<style>
										.select2-container {min-width: 75px; max-width: 300px; width: 90%;}
									</style>
									<script>
										jQuery("#e20r-program-groups").select2();
										jQuery('#e20r-program-sequences').select2();
										jQuery('#e20r-program-activity_sequences').select2();
									</script>
								</td>
	-->
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php
    }

    public function viewSettingsBox( $programData, $feeds ) {

        global $e20rTracker;
        global $e20rClient;

        $pages = get_pages();
        $posts = get_posts();

        $list = array_merge( $pages, $posts );

        // FixMe: Load all users designated as coaches.
        $coaches = $e20rClient->get_coach();

        dbg("e20rProgramView::viewProgramSettingsBox() - Supplied data: " . print_r($programData, true));
        dbg( "e20rProgramView::viewProgramSettingsBox() - Defined coaches in system: ");
        dbg( $coaches );

        wp_reset_postdata();

        ?>
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
                        <th class="e20r-label header"><label for="e20r-program-startdate"><strong><?php _e("Starts on", "e20rtracker"); ?></strong></label></th>
                        <th class="e20r-label header"><label for="e20r-program-enddate"><strong><?php _e("Ends on", "e20rtracker"); ?></strong></label></th>
                        <th class="e20r-label header"><label for="e20r-program-measurement_day"><strong><?php _e("Measurement day", "e20rtracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="3"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        if ( is_null( $programData->startdate ) ) {
                            $start = '';
                        } else {
                            $start = new DateTime( $programData->startdate );
                            $start = $start->format( 'Y-m-d' );
                        }

                        if ( is_null( $programData->enddate ) ) {
                            $end = '';
                        } else {
                            $end = new DateTime( $programData->enddate );
                            $end = $end->format( 'Y-m-d' );
                        }

                        dbg( "Program - Start: {$start}, End: {$end}" );
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
                                    <option value="0" <?php selected( 0, $programData->measurement_day); ?>>Sunday</option>
                                    <option value="1" <?php selected( 1, $programData->measurement_day); ?>>Monday</option>
                                    <option value="2" <?php selected( 2, $programData->measurement_day); ?>>Tuesday</option>
                                    <option value="3" <?php selected( 3, $programData->measurement_day); ?>>Wednesday</option>
                                    <option value="4" <?php selected( 4, $programData->measurement_day); ?>>Thursday</option>
                                    <option value="5" <?php selected( 5, $programData->measurement_day); ?>>Friday</option>
                                    <option value="6" <?php selected( 6, $programData->measurement_day); ?>>Saturday</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
                        <th class="e20r-label header"><label for="e20r-program-group"><strong><?php _e("Membership level", "e20rtracker"); ?></strong></label></th>
                        <th class="e20r-label header"><label for="e20r-program-dripfeed"><strong><?php _e("Lesson/Reminder feed", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-intake_form"><strong><?php _e("Intake form", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-sales_page_ids"><strong><?php _e("Sales page", "e20rtracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="4"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
                            <td>
                                <select class="select2-container" id="e20r-program-group" name="e20r-program-group">
                                    <option value="-1" <?php selected( $programData->group, 0 ); ?>><?php _e("Not Applicable", "e20rtracker"); ?></option>
                                    <?php
                                        $levels = $e20rTracker->getMembershipLevels( null, true );

                                        foreach( $levels as $id => $name ) { ?>

                                            <option value="<?php echo $id; ?>" <?php echo selected( $programData->group, $id ); ?>><?php echo $name; ?></option> <?php
                                        }
                                    ?>
                                </select>
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
			                        <option value="-1" <?php selected( -1, $programData->intake_form) ?>>No intake form/page needed</option><?php

			                        foreach( $list as $p ) { ?>
	                                    <option value="<?php echo $p->ID; ?>"<?php selected( $p->ID, $programData->intake_form );?>><?php echo esc_textarea($p->post_title); ?></option> <?php
                                    } ?>
		                        </select>
	                        </td>
                            <td>
                                <select class="select2-container" id="e20r-program-sales_page_ids" name="e20r-program-sales_page_ids[]" multiple="multiple">
                                    <option value="-1" <?php echo ( empty( $programData->sales_page_ids) || in_array( -1, $programData->sales_page_ids)  ? 'selected="selected"' : null); ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php echo ( isset( $programData->sales_page_ids) && in_array( $p->ID, $programData->sales_page_ids) ? 'selected="selected"' : null); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="e20r-program-settings wp-list-table widefat fixed">
                    <thead>
                    <tr>
	                    <th class="e20r-label header"><label for="e20r-program-activity_page_id"><strong><?php _e("Activity Page", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-dashboard_page_id"><strong><?php _e("Dashboard Page", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-progress_page_id"><strong><?php _e("Status Page", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-measurements_page_id"><strong><?php _e("Measurements Page", "e20rtracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="4"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
	                        <td>
                                <select class="select2-container" id="e20r-program-activity_page_id" name="e20r-program-activity_page_id">
                                    <option value="-1" <?php selected( -1, $programData->activity_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->activity_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-dashboard_page_id" name="e20r-program-dashboard_page_id">
                                    <option value="-1" <?php selected( -1, $programData->dashboard_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->dashboard_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-progress_page_id" name="e20r-program-progress_page_id">
                                    <option value="-1" <?php selected( -1, $programData->progress_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->progress_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-measurements_page_id" name="e20r-program-measurements_page_id">
                                    <option value="-1" <?php selected( -1, $programData->measurements_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

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
	                    <th class="e20r-label header"><label for="e20r-program-welcome_page_id"><strong><?php _e("Preparation Page", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-incomplete_intake_form_page"><strong><?php _e("Incomplete Intake Form", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-account_page_id"><strong><?php _e("Account Profile Page", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-contact_page_id"><strong><?php _e("Contact the Coach Page", "e20rtracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="4"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
	                        <td>
                                <select class="select2-container" id="e20r-program-welcome_page_id" name="e20r-program-welcome_page_id">
                                    <option value="-1" <?php selected( -1, $programData->welcome_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->welcome_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-incomplete_intake_form_page" name="e20r-program-incomplete_intake_form_page">
                                    <option value="-1" <?php selected( -1, $programData->incomplete_intake_form_page) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->incomplete_intake_form_page ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-account_page_id" name="e20r-program-account_page_id">
                                    <option value="-1" <?php selected( -1, $programData->account_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

                                foreach( $list as $p ) { ?>
                                    <option value="<?php echo $p->ID;?>"<?php selected( $p->ID, $programData->account_page_id ); ?>><?php echo esc_textarea($p->post_title);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-contact_page_id" name="e20r-program-contact_page_id">
                                    <option value="-1" <?php selected( -1, $programData->contact_page_id) ?>><?php _e("No page defined", "e20rtracker");?></option><?php

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
	                    <th class="e20r-label header"><label for="e20r-program-male_coaches"><strong><?php _e("Coaches for Male clients", "e20rtracker"); ?></strong></label></th>
	                    <th class="e20r-label header"><label for="e20r-program-female_coaches"><strong><?php _e("Coaches for Female clients", "e20rtracker"); ?></strong></label></th>
                    </tr>
                    <tr>
                        <td colspan="2"><hr width="100%"/></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr class="program-inputs">
	                        <td>
                                <select class="select2-container" id="e20r-program-male_coaches" name="e20r-program-male_coaches[]" multiple="multiple">
                                    <option value="-1" <?php selected( -1, $programData->male_coaches) ?>><?php _e("None added", "e20rtracker");?></option><?php

                                foreach( $coaches as $cId => $cName ) {
                                    $selected = (in_array( $cId, $programData->male_coaches ) ? 'selected="selected"' : null );?>
                                    <option value="<?php echo $cId;?>" <?php echo $selected; ?>><?php echo esc_textarea($cName);?></option><?php
                                } ?>
                                </select>
                            </td>
                            <td>
                                <select class="select2-container" id="e20r-program-female_coaches" name="e20r-program-female_coaches[]" multiple="multiple">
                                    <option value="-1" <?php selected( -1, $programData->female_coaches) ?>><?php _e("None added", "e20rtracker");?></option><?php

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

                // dbg("Select List " . print_r( $this->programs, true ) );

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

    private function showSequenceListForMetaBox() {

    }

    public function viewProgramEditSelect() {

        //$this->programs = $this->load_program_info( null, true ); // Load all programs & generate a select <div></div>

        ob_start();

        ?>
        <div id="program-select-div">
            <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_select_programs_nonce' ); ?>
                <div class="e20r-select">
                    <input type="hidden" name="hidden_e20r_program_id" id="hidden_e20r_program_id" value="0" >
                    <label for="e20r_programs">Select Program</label>
                    <span class="e20r-program-select-span">
                        <select name="e20r_programs" id="e20r_programs">
                            <?php

                            dbg("e20rProgramView:: - List: " . print_r( $this->programs, true ) );
                            foreach( $this->programs as $program ) {
                                ?><option value="<?php echo esc_attr( $program->id ); ?>"  ><?php echo esc_attr( $program->program_name ); ?></option><?php
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

        // Fetch the Checkin Item we're looking to manage
        // $program_list = $this->load_program_info( null, false );
        dbg("e20rProgramView::view_listPrograms() - Loading list of programs");
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
                        <th class="e20r-label header"><label for="e20r-program_id">Edit</label></th>
                        <th class="e20r-label header"><label for="e20r-program_id">ID</label></th>
                        <th class="e20r-label header"><label for="e20r-program_name">Name</label></th>
                        <th class="e20r-label header"><label for="e20r-program-startdate">Starts on</label></th>
                        <th class="e20r-label header"><label for="e20r-program-enddate">Ends on</label></th>
                        <th class="e20r-label header"><label for="e20r-program-descr">Description</label></th>
                        <th class="e20r-label header"><label for="e20r-memberships">Belongs to (Membership)</label></th>
                        <th class="e20r-save-col hidden">Save</td>
                        <th class="e20r-cancel-col hidden">Cancel</td>
                        <th class="e20r-delete-col hidden">Remove</td>
                        <th class="e20r-label header hidden"></td>
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
                                $start = new DateTime( $program->startdate );
                                $start = $start->format( 'Y-m-d' );
                            }

                            if ( is_null( $program->enddate ) ) {
                                $end = '';
                            } else {
                                $end = new DateTime( $program->enddate );
                                $end = $end->format( 'Y-m-d' );
                            }

                            $pid = $program->id;

                            dbg( "Program - Start: {$start}, End: {$end}" );
                            ?>
                            <tr id="<?php echo $pid; ?>" class="program-inputs">
                                <td class="text-input">
                                    <input type="checkbox" name="edit_<?php echo $pid; ?>" id="edit_<?php echo $pid ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-program_id_<?php echo $pid; ?>" disabled name="e20r_program_id" size="5" value="<?php echo( ( ! empty( $program->id ) ) ? $program->id : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-program_name_<?php echo $pid; ?>" disabled name="e20r_program_name" size="25" value="<?php echo( ( ! empty( $program->program_name ) ) ? $program->program_name : null ); ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-program-startdate_<?php echo $pid; ?>" disabled name="e20r_program_startdate" value="<?php echo $start; ?>">
                                </td>
                                <td class="text-input">
                                    <input type="date" id="e20r-program-enddate_<?php echo $pid; ?>" disabled name="e20r_program_enddate" value="<?php echo $end; ?>">
                                </td>
                                <td class="text-descr">
                                    <textarea class="expand" id="e20r-program-descr_<?php echo $pid; ?>" disabled name="e20r_program_descr" rows="1" wrap="soft"><?php echo ( ! empty( $program->description ) ) ? $program->description : null; ?></textarea>
                                </td>
                                <td class="select-input">
                                    <?php echo $this->view_selectMemberships( $program->member_id, $pid ); ?>
                                </td>
                                <td class="hidden save-button-row" id="e20r-td-save_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-save-edit-program button">Save</a>
                                </td>
                                <td class="hidden cancel-button-row" id="e20r-td-cancel_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-cancel-edit-program button">Cancel</a>
                                </td>
                                <td class="hidden delete-button-row" id="e20r-td-delete_<?php echo $pid; ?>">
                                    <a href="#" class="e20r-delete-program button">Remove</a>
                                </td>
                                <td class="hidden-input">
                                    <input type="hidden" class="hidden_id" value="<?php echo $pid; ?>">
                                </td>
                            </tr>
                        <?php
                        }
                    }
                    else { ?>
                        <tr>
                            <td colspan="7">No programs found in the database. Please add a new program by clicking the "Add New" button.</td>
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
                        <td class="save"><a class="e20r-button button" id="e20r-save-new-program" href="#">Save</a></td>
                        <td class="cancel"><a class="e20r-button button" id="e20r-cancel-new-program" href="#">Cancel</a></td>
                        <td class="hidden"><!-- Nothing here, it's for the delete/remove button --></td>
                        <td class="hidden-input"><input type="hidden" class="hidden_id" value="<?php echo $pid; ?>"></td>
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

                ?><select name="e20r-memberships_<?php echo $rowId; ?>" id="e20r-memberships_<?php echo $rowId; ?>" disabled><?php
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


    public function view_programMetaTable() {

        global $post;

        // $this->init();

        $pgms = get_post_meta($post->ID, 'e20r_tracker_program_ids', true);
        $pgms = array_unique( $pgms );

        dbg("e20rProgramView:: Read from post meta for {$post->ID}: " . print_r( $pgms, true));

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
                <?php dbg("e20rProgramView:: - Adding rows for {$id}");?>
                <tr><td><fieldset></td></tr>
                <tr class="select-row-label<?php echo ( $id == 0 ? ' new-program-select-label' : ' program-select-label' ); ?>">
                    <td>
                        <label for="e20r-tracker-memberof-programs"><?php _e("Program:", "e20rtracker"); ?></label>
                    </td>
                </tr>
                <tr class="e20r-select-row-input<?php echo ( $id == 0 ? ' new-e20rprogram-select' : ' program-select' ); ?>">
                    <td class="program-list-dropdown">
                        <select class="<?php echo ( $id == 0 ? 'new-e20rprogram-select' : 'e20r-tracker-memberof-programs'); ?>" name="e20r-tracker-programs[]">
                            <option value="0" <?php echo ( $id == 0 ? 'selected' : '' ); ?>><?php _e("Not assigned", "e20rtracker"); ?></option>
                            <?php
                            // Loop through all of the sequences & create an option list
                            foreach ( $this->programs as $program ) {
                                if ( $program->id != 0 ) {
                                    ?>
                                    <option value="<?php echo $program->id; ?>" <?php echo selected( $program->id, $id ); ?>><?php echo $program->program_name; ?></option><?php
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
            <a href="#" id="e20r-tracker-new-meta" class="button-primary"><?php _e( "Add", "e20rtracker" ); ?></a>
            <a href="#" id="e20r-tracker-new-meta-reset" class="button"><?php _e( "Reset", "e20rtracker" ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function view_programPostMetabox() {

        dbg("e20rProgramView::view_programPostMetabox() - Rendering metabox...");

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


}