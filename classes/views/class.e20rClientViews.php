<?php

class e20rClientViews {

    protected $client_id;

    public function __construct( $id = null ) {

        dbg("e20rClientviews::__construct() - Setting client ID");

        if ( ! empty( $id ) ) {

            $this->client_id = $id;
        }
    }

    public function viewMessageHistory( $client_id, $messages ) {

        /*
         * $messages[$when] = stdClass()
         *                  sender_id
         *                  topic
         *                  message
         *                  sent
         */
         ob_start();
         // add_thickbox(); // Load thickbox to enable display of full message.
         ?>
         <table class="e20r-client-message-history-table">
            <thead>
                <tr>
                    <th><?php _e("Sent", "e20rtracker"); ?></th>
                    <th><?php _e("Subject", "e20rtracker"); ?></th>
                    <th><?php _e("From", "e20rtracker");?></th>
                </tr>
            </thead>
            <tbody><?php
            if ( empty( $messages ) ) { ?>
                <tr class="e20r-client-message-history-entry">
                    <td colspan="3">
                        <h3><?php _e("No message history found", "e20rtracker" ); ?></h3>
                    </td>
                </tr><?php
            }
            else {

                foreach( $messages as $when => $message ) {

                    $sender = get_user_by('id', $message->sender_id ); ?>

                <tr class="e20r-client-message-history-entry">
                    <td class="e20r-client-message-history-date">
                        <?php echo esc_html( $when ); ?>
                    </td>
                    <td class="e20r-client-message-history-subject">
                        <a href="#TB_inline?width=500&height=300&inlineId=message_<?php echo $message->id; ?>" class="thickbox"><?php echo esc_html( $message->topic ); ?></a>
                        <div id="message_<?php echo $message->id; ?>" class="e20r-message-history-content" style="display:none">
                            <div class="e20r-message-content">
                                <h3 class="e20r-client-message-title"><?php echo esc_html($message->topic); ?></h3>
                                <hr/>
                                <p><?php echo $message->message; ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="e20r-client-message-history-sender">
                        <?php echo esc_html( $sender->user_firstname ); ?>
                    </td>
                </tr>
                <?php
                }
            } ?>
            </tbody>
        </table><?php

        $html = ob_get_clean();

        return $html;
    }

    public function displayData() {

        global $e20rClient;
        global $e20rTracker;

        // TODO: Create page for back-end to display customers.
        ?>
        <div id="e20r-tracker-progress-display">

            <div id="e20r-progress-measurements">

            </div>

            <?php echo $e20rClient->showUserProgress(); ?>
        </div>
        <?php
    }

    public function get_item_count( $item_id, $habit_name, $user_id ) {

        $item_list = $this->get_items( $item_id );

    }

    public function viewLevelSelect() {

        global $e20rClient, $e20rTracker;

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $e20rTracker->dependency_warnings();
        }

        ob_start(); ?>

        <div id="e20r-selectLevel">
            <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' ); ?>
                <div class="e20r-select">
                    <input type="hidden" name="hidden_e20r_level" id="hidden_e20r_level" value="0" >
                    <label for="e20r_levels">Filter by Membership Level:</label>
                    <span class="e20r-level-select-span">
                        <select name="e20r_levels" id="e20r_levels">
                            <option value="-1"></option>
                            <option value="0">All levels</option>
                        <?php

                        $level_list = $e20rTracker->getMembershipLevels( null, false );

                        foreach( $level_list as $key => $name ) {
                            ?><option value="<?php echo esc_attr( $key ); ?>"  ><?php echo esc_attr( $name ); ?></option><?php
                        }
                ?>
                        </select>
                    </span>
                </div>
            </form>
        </div>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function viewMemberSelect( $levelId = null ) {

        global $e20rClient, $e20rTracker;

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $e20rTracker->dependency_warnings();
        }

        ob_start(); ?>
        <div class="e20r-client-list">
                <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <?php // wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' ); ?>
                    <div class="e20r-select">
                        <label for="e20r_tracker_client">Select client to view data for:</label>
                        <select name="e20r_tracker_client" id="e20r_members">
                        <?php

                        $user_list = $e20rTracker->getUserList( $levelId );

                        foreach ( $user_list as $user ) {

                            ?><option value="<?php echo esc_attr( $user->id ); ?>"  ><?php echo esc_attr($user->name); ?></option><?php
                        } ?>
                        </select>
                        <!-- <span class="e20r-level-select-span"><a href="#" id="e20r-load-data" class="e20r-choice-button button"><?php _e('Load Progress', 'e20r-tracker'); ?></a></span> -->
                        <input type="hidden" name="hidden_e20r_client_id" id="hidden_e20r_client_id" value="0" >
                    </div>
                </form>
            </div>
        <!-- </div> -->
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function viewClientContact( $clientId ) {

        global $e20rProgram;
        global $e20rClient;

        global $currentProgram;
        global $currentClient;

        global $current_user;

        $e20rProgram->getProgramIdForUser( $clientId );

        // $interview = $e20rClient->loadClientInfo( $clientId );
        dbg("e20rClientViews::viewClientContact() - Loaded interview/survey data for {$currentClient->user_id}");
        // dbg( $currentClient );

        $client = get_user_by( 'id', $clientId );

        $first_name = ( isset( $currentClient) && empty( $currentClient->first_name ) ? $client->user_firstname : $currentClient->first_name );
        $last_name = ( isset( $currentClient) && empty( $currentClient->last_name ) ? $client->user_lastname : $currentClient->last_name );
        $email = ( isset( $currentClient) && empty( $currentClient->email ) ? $client->user_email : $currentClient->email );
        $incomplete = ( isset( $currentClient) && empty( $currentClient->email ) ? true : false );

        ob_start(); ?>
        <form id="e20r-message-form">
            <input type="hidden" name="e20r-send-to-id" id="e20r-send-to-id" value="<?php echo $currentClient->user_id; ?>">
            <input type="hidden" name="e20r-send-message-to" id="e20r-send-message-to" value="<?php echo $email; ?>">
            <input type="hidden" name="e20r-send-message-cc" id="e20r-send-message-cc" value="<?php echo $current_user->user_email; ?>">
            <input type="hidden" name="e20r-send-from-id" id="e20r-send-from-id" value="<?php echo $current_user->ID; ?>">
            <?php if ( true === $incomplete ) { ?>
                <div class="red-notice">
                    <?php _e("Incomplete client interview. Please ask {$first_name} to complete the welcome interview as quickly as possible", "e20rtracker" ); ?>
                </div><?php
            }?>
            <table id="e20r-send-message">
                <thead>
                <tr>
                    <th colspan="3" class="e20r-activity-table-header"><?php echo sprintf( __("Send message to: %s %s &lt;<em>%s</em>&gt;", "e20rtracker" ), $first_name, $last_name, $email ); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="e20r-message-header">
                        <?php _e("From", "e20rtracker"); ?>:
                    </td>
                    <td class="e20r-message-from">
                        <input class="e20r-message-input" type="text" name="e20r-email-from-name" id="e20r-email-from-name" autocomplete="off" value="<?php echo __("Coach", "e20rtracker") . " {$current_user->user_firstname}"; ?>" />
                    </td>
                    <td class="e20r-message-from">
                        <input class="e20r-message-input" type="email" name="e20r-email-from" id="e20r-email-from" autocomplete="off" value="<?php echo "{$current_user->user_email}"; ?>" />
                    </td>
                </tr>
                <tr>
                    <td class="e20r-message-header">
                        <?php _e("Subject", "e20rtracker"); ?>:
                    </td>
                    <td class="e20r-message-subject" colspan="2">
                        <input class="e20r-message-input" type="text" name="e20r-email-subject" id="e20r-email-subject" autocomplete="off" placeholder="<?php _e('Enter subject for email', 'e20rtracker'); ?>" />
                    </td>
                </tr>
                <tr>
                    <td colspan="3"><hr/></td>
                </tr>
                <tr>
                    <td class="e20r-message-subject" colspan="3">
                        <textarea class="e20r-message-input" id="content" autocomplete="off" name="content" placeholder="<?php _e('Message to client', 'e20rtracker'); ?>" rows="20" cols="75" type='textarea'> </textarea><?php
                        $settings = array(
                            'textarea_name' => 'content',
                            'quicktags' => false,
                            'textarea_rows' => 20,
                            'media_buttons' => false,
                            'tinymce' => array(
                                'theme_advanced_buttons1' => 'formatselect,|,bold,italic,underline,|,' .
                                    'bullist,blockquote,|,justifyleft,justifycenter' .
                                    ',justifyright,justifyfull,|,link,unlink,|' .
                                    ',spellchecker,wp_fullscreen,wp_adv'
                            )
                        );
                        wp_editor( '', 'content', $settings );

                        // wp_editor($content, $editor_id, $editor_settings); ?>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3">
                        <?php

                        $btn_attrs = array( 'id' => 'e20r-send-email-message' );
                        submit_button( __("Send message", "e20rtracker"), 'primary', 'e20r-send-email-message', true, $btn_attrs );
                        ?>
                    </td>
                </tr>
                </tfoot>
            </table>
        </form><?php

        $html = ob_get_clean();

        dbg("e20rClientViews::viewClientContact() - Data loaded and form being returned");
        return $html;
    }

    public function show_lastLogin( $clientId ) {

        global $e20rTracker;
        global $currentProgram;

        $when = __( 'Never.', 'e20rtracker' );
        $last_login = (int) get_user_meta( $clientId, '_e20r-tracker-last-login', true );
        $today = current_time( 'timestamp' );
        $user = get_user_by( 'id', $clientId );

        $days_since_login = 0;

        $program_length = $e20rTracker->getDateFromDelay( 'now', $clientId );

        if ( false !== $last_login ) {

            $format = apply_filters( "e20r-tracker-date-format", get_option( 'date_format') );

            if ( 0 != $last_login ) {
                $when = date_i18n( 'l, F j, Y', $last_login );
                $days_since_login = $e20rTracker->daysBetween( $last_login, $today );

            }
        }
        ob_start();


        if ( ( $program_length >= 2 ) && ( 10 <= $days_since_login ) ) { ?>
            <div class="red-notice">
                <h3 style="color: #004CFF;"><?php echo sprintf( __("Please send a reminder to %s", "e20rtracker"), $user->user_firstname ); ?></h3><?php
        }

        if ( ( $program_length >= 2 ) && ( 10 > $days_since_login && 3 < $days_since_login ) ) { ?>
            <div class="orange-notice">
            <h3 style="color: #004CFF;"><?php echo sprintf( __("Please send a reminder to %s", "e20rtracker"), $user->user_firstname ); ?></h3><?php
        }

        if ( ( $program_length >= 2 ) && ( 3 >= $days_since_login ) ) { ?>
            <div class="green-notice"><?php
        }?>
                <p><?php echo sprintf( __('The last recorded access for %s was: <em style="text-decoration: underline;">%s</em>', "e20rtracker"), $user->user_firstname, $when );?></p>
            </div><?php
        $html = ob_get_clean();

        return $html;
    }

    public function viewClientDetail( $clientId ) {

        global $e20rProgram;
        global $e20rClient;

        global $currentProgram;
        global $currentClient;

        global $current_user;

        $e20rProgram->getProgramIdForUser( $clientId );

        $hideFields = array(
            'user_enc_key',
            'incomplete_interview',
            'loadedDefaults',
            'id'
        );

        dbg("e20rClientViews::viewClientDetail() - Loaded interview/survey data for {$currentClient->user_id}");

        ob_start();

        echo $this->show_lastLogin( $clientId ); ?>
        <table class="e20r-client-information-table">
            <thead>
                <tr>
                    <th><?php _e("Question", "e20rtracker"); ?></th>
                    <th><?php _e("Content", "e20rtracker"); ?></th>
                </tr>
            </thead>
            <tbody><?php
            foreach( $currentClient as $field => $data ) {
                if ( 'user_enc_key' == $field ) {
                    continue;
                } ?>
                <tr class="e20r-clientdetail-row">
                    <td class="e20r-clientdetail-header"><?php echo $field; ?></td>
                    <td class="e20r-clientdetail-info"><?php echo $data; ?></td>
                </tr><?php
            } ?>
            </tbody>
        </table><?php

        $html = ob_get_clean();

        dbg("e20rClientViews::viewClientDetail() - Data loaded and form being returned");
        return $html;

    }

    public function viewClientAdminPage( $lvlName = '' ) {

	    global $e20rCheckin;
        global $currentClient;
	    global $e20rAssignment;
        global $e20rWorkout;
        global $e20rClient;

        if ( is_admin() ) {

            add_thickbox();
        }

        ob_start();
        ?>
        <H1><?php _e( "Coach's Page", "e20rtracker" ); ?></H1>
        <div class="e20r-client-service-select">
            <div id="spinner" class="e20r-spinner"></div>
            <?php echo $this->viewLevelSelect(); ?>
            <div id="e20r-selectMember" class="startHidden">
            <?php echo $this->viewMemberSelect( $lvlName ); ?>
            </div>
        </div>

        <div class="startHidden e20r-data-choices">
            <hr class="startHidden e20r-admin-hr" />
            <!-- Where the choices for the client data to fetch gets listed -->
            <form action="" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_client_detail_nonce' ); ?>
                <table class="e20r-single-row-table">
                    <tbody>
                    <tr>
                        <td><div id="load-client-data" class="e20r-spinner"></div></td>
<!--                        <td><a href="#" id="e20r-client-info" class="e20r-choice-button button" ><?php _e('Client Info', 'e20r-tracker'); ?></a></td>
                            <td><a href="#" id="e20r-client-compliance" class="e20r-choice-button button" ><?php _e('Compliance', 'e20r-tracker'); ?></a></td>
                            <td><a href="#" id="e20r-client-assignments" class="e20r-choice-button button" ><?php _e('Assignments', 'e20r-tracker'); ?></a></td> -->
                        <td><a href="#" id="e20r-client-load-measurements" class="e20r-choice-button button" ><?php _e('Load Information', 'e20r-tracker'); ?></a></td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <hr class="e20r-admin-hr" />
	    <div id="status-tabs" style="max-width: 800px; width: 100%;" class="startHidden">
		    <ul>
			    <li><a href="#tabs-1">Measurements</a></li>
			    <li><a href="#tabs-2">Assignments</a></li>
			    <li><a href="#tabs-3">Achievements</a></li>
			    <li><a href="#tabs-4">Activities</a></li>
                <li><a href="#tabs-5">Client Info</a></li>
                <li><a href="#tabs-6">Send Message</a></li>
                <li><a href="#tabs-7">Message History</a></li>
		    </ul>
	        <div id="tabs-1">
		        <div id="e20r-progress-measurements">
		        </div>
	        </div>
		    <div id="tabs-2">
			    <div id="e20r-progress-assignments">
				    <?php echo $e20rAssignment->listUserAssignments( $currentClient->user_id ) ?>
			    </div>
		    </div>
		    <div id="tabs-3">
		        <div id="e20r-progress-accomplishments">
			        <?php echo $e20rCheckin->listUserAccomplishments( $currentClient->user_id ); ?>
		        </div>
		    </div>
            <div id="tabs-4">
                <div id="e20r-progress-activities">
                    <?php echo $e20rWorkout->listUserActivities( $currentClient->user_id ); ?>
                </div>
            </div>

            <div id="tabs-5">
			    <div id="e20r-client-info">
				    <?php echo $this->viewClientDetail( $currentClient->user_id ); ?>
			    </div>
		    </div>
            <div id="tabs-6">
                <div id="e20r-client-contact">
                    <?php echo $this->viewClientContact( $currentClient->user_id ); ?>
                </div>
            </div>
            <div id="tabs-7">
                <div id="e20r-client-message-history">
                    <?php echo $e20rClient->loadClientMessages( $currentClient->user_id ); ?>
                </div>
            </div>

        </div>
	    <!-- <div class="modal"> --><!-- At end of form --><!-- </div> --><?php
        dbg("e20rClientView::viewClientAdminPage() - Returning content for admin page.");
        return ob_get_clean();
    }

    public function viewCompliance( $clientId = null, $shortname = null ) {

        if ( empty( $clientId ) ) {

            global $current_user, $e20rTracker;
            $clientId = $current_user->ID;

            if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
                $level_id = pmpro_getMembershipLevelForUser( $clientId );
            }
            else {
                $e20rTracker->dependency_warnings();
            }
        }

        if ( empty( $shortname ) ) {

            if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
                $level_id = pmpro_getMembershipLevelForUser( $clientId );
            }
            else {
                $e20rTracker->dependency_warnings();
            }
        }

        // TODO: Implement compliance data loader.
//        $checkins = new e20rCheckin();
//        $items = $checkins->get_checkinItems( $shortname, $level_id );

        // TODO: show a graph for the users compliance.
    }

    public function view_userProfile( $userId ) {

        $checked = null;

        if ( user_can( $userId, 'e20r_coach' ) ) {
            $checked = 'checked="checked"';
        }

        ob_start();
        ?>
        <h3><?php _e("E20R-Tracker: Coach Info", "e20rtracker"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="e20r-tracker-user-role"><?php _e( "User is a program coach", "e20rtracker"); ?></label></th>
                <td>
                    <input type="checkbox" id="e20r-tracker-user-role" name="e20r-tracker-user-role" value="e20r_coach" <?php echo $checked; ?>>
                </td>
            </tr>
        </table>
        <?php

        $html = ob_get_clean();
        return $html;
    }

    private function viewAssignments( $clientId ) {

    }

    public function load_billing_data( $client_id = 0 ) {

        // TODO: Build array wpdb objects containing the billing info for this client
        if ( $client_id == 0 ) {

            global $current_user;

            $client_id = $current_user->ID;
        }

        // timestamp is the billing date, Stripe customer_id (hidden)
        // gateway = stripe
        // subscription_transaction_id == Stripe plan?
        //


    }

    public function render_assignments_page() {

    }

    public function render_measurements_page() {

    }

    public function render_compliance_page() {

    }

    public function render_new_item_page() {

        $manage_checkin_items = new E20Rcheckin();
        $data = $manage_checkin_items->view_AddNewCheckinItem();

        echo $data;
    }
/*
    public function render_new_program_page() {

        $programs = new e20rProgram();

        echo $programs->viewProgramEditSelect();

    }

*/

    public function render_meals_page() {

    }

} 