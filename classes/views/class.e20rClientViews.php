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
                    $when = date_i18n('Y-m-d \a\t H:i', $when );
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

    public function display_client_list( $clients ) {

        global $e20rProgram;
        global $e20rTracker;

        global $currentProgram;

        global $current_user;

        $today = current_time( 'timestamp', true );

        ob_start(); ?>
        <div class="e20r-coach-data">
            <table id="e20r-client-list-legend">
                <tbody>
                    <tr>
                        <td colspan="2"><h5>Legend</h5></td>
                    </tr>
                    <tr>
                        <td class="e20r-strong-text"><?php _e("Strong text", "e20rtracker"); ?></td>
                        <td class="e20r-strong-text"><?php _e("Client accessed system in the past 3 days", "e20rtracker"); ?></td>
                    </tr>
                    <tr>
                        <td class="e20r-weak-text"><?php _e("Weaker text", "e20rtracker"); ?></td>
                        <td class="e20r-weak-text"><?php _e("Client accessed system in the past 3 - 10 days", "e20rtracker"); ?></td>
                    </tr>
                    <tr>
                        <td class="e20r-weakest-text"><?php _e("Weakest text", "e20rtracker"); ?></td>
                        <td class="e20r-weakest-text"><?php _e("More than 10 days since the client accessed this system", "e20rtracker"); ?></td>
                    </tr>
                    <tr class="e20r-followup-critical">
                        <td><?php _e("Critical", "e20rtracker"); ?></td>
                        <td><?php _e("No recorded contact with this client in more than 14 days, or there's never been any recorded contact", "e20rtracker"); ?></td>
                    </tr>
                    <tr class="e20r-followup-warning">
                        <td><?php _e("Warning", "e20rtracker"); ?></td>
                        <td><?php _e("No recorded contact with this client in between 7 and 14 days", "e20rtracker"); ?></td>
                    </tr>
                    <tr class="e20r-followup-normal">
                        <td><?php _e("Normal", "e20rtracker"); ?></td>
                        <td><?php _e("Client contact recorded sometime in past 7 days", "e20rtracker"); ?></td>
                    </tr>
                </tbody>
            </table>
            <table id="e20r-client-list">
            <tbody class="e20r-client-list-body"><?php
                foreach( $clients as $programId => $clientList ) {

                    dbg("e20rClientViews::display_client_list() - processing list of clients related to program # {$programId} for coach {$current_user->ID}");
                    dbg($clientList);

                    $program_name = $e20rProgram->get_program_name( $programId );
                    ?>
                <tr class="e20r-client-list-programs">
                    <td class="e20r-client-list-program-name" colspan="3"><h4><?php echo $program_name; ?></h4></td>
                </tr>
                <tr class="e20r-client-list-header-row">
                    <th class="e20r-client-name"><label><?php _e( "Client", "e20rtracker" ); ?></label></th>
                    <th class="e20r-client-last-login"><label><?php _e("Last login by client", "e20rtracker"); ?></label></th>
                    <th class="e20r-client-last-message"><label><?php _e("Last message from us", "e20rtracker"); ?></label></th>
                </tr><?php

                    foreach( $clientList as $client ) {

                        dbg( $client );

                        $level_id = $e20rTracker->getGroupIdForUser( $client->ID );

                        $days_since_login = $e20rTracker->daysBetween( $client->status->recent_login, $today, get_option('timezone_string') );
                        $program_length = $e20rTracker->daysbetween( strtotime( $client->status->program_start), $today );

                        $css_flag = "e20r-strong-text";

                        if ( ( $program_length >= 2 ) && ( 10 <= $days_since_login ) ) {
                            $css_flag = "e20r-weakest-text";
                        }

                        if ( ( $program_length >= 2 ) && ( 10 > $days_since_login && 3 <= $days_since_login ) ) {
                            $css_flag = "e20r-weak-text";
                        }

                        if ( ( $program_length >= 2 ) && ( 3 > $days_since_login ) ) {
                            $css_flag = "e20r-strong-text";
                        }

                        $msg_when = key( $client->status->last_message );
                        $msg_txt = $client->status->last_message[$msg_when];

                        $days_since_msg = ( $msg_when != 'empty' ? $e20rTracker->daysBetween( $msg_when, $today, get_option('timezone_string') ) : null );
                        $status_flag = 'e20r-followup-normal';

                        dbg("e20rClientViews::display_client_list() - User: {$client->ID}. Days since last message: {$days_since_msg}. Program length: {$program_length}. And days since last login: {$days_since_login}");

                        if ( ( $program_length >= 7 ) && ( $days_since_msg >= 7  ) ) {
                            $status_flag = 'e20r-followup-warning';
                        }

                        if ( ( $program_length >= 7 ) && ( ( null === $days_since_msg ) || ( $days_since_msg > 14 ) ) ) {
                            // Never sent a message to the client. Flag as "critical to follow up"
                            $status_flag = 'e20r-followup-critical';
                        }
                        dbg("e20rClientViews::display_client_list() - Loading info about sender of the last message to user {$client->ID}");

                        if ( !empty( $client->status->last_message_sender ) ) {

                            dbg("e20rClientViews::display_client_list() - user info for the sender: {$client->status->last_message_sender}");
                            $sender = get_user_by( 'id', $client->status->last_message_sender);
                            $message_info = "By " . $sender->user_firstname . " on " . date( 'l F jS, Y', $msg_when );

                        }
                        else {
                            $message_info = "No message sent";
                            $msg_txt = null;
                        }

                        dbg( "e20rClientViews::display_client_list() - Info about last message sent to user: " . $message_info );
                    ?>
                    <tr class="e20r-client-list-row <?php echo $css_flag; ?> <?php echo $status_flag; ?>">
                        <td class="e20r-client-name"><a href="<?php echo admin_url( "admin.php?page=e20r-client-info&e20r-client-id={$client->ID}&e20r-level-id={$programId}" );?>" target="_blank"><?php echo $client->display_name; ?></td>
                        <td class="e20r-client-last-login"><?php echo date( 'l F jS, Y', $client->status->recent_login ); ?></td>
                        <td class="e20r-client-last-message" <?php echo ( !is_null( $msg_txt) ? 'title="' .$msg_txt .'"' : null ); ?>><?php echo $message_info; ?></td>
                    </tr><?php
                    }
                } ?>
            </tbody>
            </table>
        </div><?php
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

    public function viewLevelSelect( $levelId = -1 ) {

        global $e20rClient;
        global $e20rProgram;
        global $e20rTracker;

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $e20rTracker->dependency_warnings();
        }

        if ( !is_numeric( $levelId ) ) {
            $levelId = -1;
        }
        ob_start(); ?>

        <div id="e20r-selectLevel">
            <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' ); ?>
                <div class="e20r-select">
                    <input type="hidden" name="hidden_e20r_level" id="hidden_e20r_level" value="0" >
                    <label for="e20r_levels"><?php _e("Program", "e20rtracker"); ?>:</label>
                    <span class="e20r-level-select-span">
                        <select name="e20r_levels" id="e20r_levels">
                            <option value="-1" <?php selected( -1, $levelId ); ?>><?php _e("None", "e20tracker");?></option>
                            <option value="0" <?php selected( 0, $levelId ); ?>><?php _e("All Programs", "e20rtracker" ); ?></option>
                        <?php

                        $program_list = $e20rProgram->get_programs();

                        foreach( $program_list as $key => $name ) {
                            ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $levelId ); ?>><?php echo esc_attr( $name ); ?></option><?php
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

        global $e20rClient;
        global $e20rTracker;
        global $e20rProgram;

        global $currentClient;

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $e20rTracker->dependency_warnings();
        }

        ob_start(); ?>
        <div class="e20r-client-list">
                <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <?php // wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' ); ?>
                    <div class="e20r-select">
                        <label for="e20r_members">Select client to view data for:</label>
                        <select name="e20r_tracker_client" id="e20r_members">
                        <?php

                        // $user_list = $e20rTracker->getUserList( $levelId );
                        $user_list = ( !is_null( $levelId ) ? $e20rProgram->get_program_members( $levelId ) : get_users() );

                        foreach ( $user_list as $user ) {

                            ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $user->ID, $currentClient->user_id ); ?> ><?php echo esc_attr($user->display_name); ?></option><?php
                        } ?>
                        </select>
                        <!-- <span class="e20r-level-select-span"><a href="#" id="e20r-load-data" class="e20r-choice-button button"><?php _e('Load Progress', 'e20r-tracker'); ?></a></span> -->
                        <input type="hidden" name="hidden_e20r_client_id" id="hidden_e20r_client_id" value="<?php echo ( isset( $currentClient->user_id ) && $currentClient->user_id != 0 ) ? $currentClient->user_id : 0 ?>" >
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
        // dbg("e20rClientViews::viewClientContact() - Loaded interview/survey data for {$currentClient->user_id}");
        // dbg( $currentClient );

        $client = get_user_by('id', $clientId );

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
                    <td>
                        <?php

                        $btn_attrs = array( 'id' => 'e20r-send-email-message' );
                        submit_button( __("Send message", "e20rtracker"), 'primary', 'e20r-send-email-message', true, $btn_attrs );
                        ?>
                    </td>
                    <td style="font-size: 0.7rem; text-align: right;"><?php _e("When to send (empty = now)", "e20rtracker"); ?></td>
                    <td style="font-size: 0.7rem;">
                        <input type="text" name="e20r-tracker-send-message-datetime" id="e20r-tracker-send-message-datetime">
                        <?php echo sprintf( __( "TZ: %s", "e20rtracker" ),  get_option( 'timezone_string' ) ); ?>
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
        global $currentUser;

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
                $days_since_login = $e20rTracker->daysBetween( $last_login, $today, get_option('timezone_string') );

            }
        }
        ob_start();

        $user_firstname = ( !isset( $user->user_firstname ) ? 'N/A': $user->user_firstname );

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
                <p><?php echo sprintf( __('The last recorded access for %s was: <em style="text-decoration: underline;">%s</em>', "e20rtracker"), $user_firstname, $when );?></p>
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

        echo $this->show_lastLogin( $currentClient->user_id ); ?>
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

    public function viewClientAdminPage( $lvlName = '', $level_id = -1 ) {

	    global $e20rAction;
        global $currentClient;
	    global $e20rAssignment;
        global $e20rWorkout;
        global $e20rClient;

        if ( is_admin() ) {

            add_thickbox();
        }

        if ( is_numeric( $lvlName ) ) {

            $level_id = $lvlName;
        }

        ob_start();
        ?>
        <H1><?php _e( "Coaching Page", "e20rtracker" ); ?></H1>
        <div class="e20r-client-service-select">
            <div id="spinner" class="e20r-spinner"></div>
            <?php echo $this->viewLevelSelect( $level_id ); ?>
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
	    <div id="status-tabs" style="max-width: 800px; width: 100%;" class="startHidden" data-role='z-tabs'>
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
			        <?php echo $e20rAction->listUserAccomplishments( $currentClient->user_id ); ?>
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

    public function view_clientProfile( $progressEntries ) {

        global $currentProgram;
        global $current_user;

        global $e20rClient;

        $today = current_time('timestamp');
        $two_weeks = strtotime("{$currentProgram->startdate} + 2 weeks");
        $complete_interview = $e20rClient->completeInterview($current_user->ID);

        ob_start(); ?>
        <div id="profile-tabs" class="ct ct-underline"><?php

            $count = 1;
            foreach( $progressEntries as $label => $contentHtml ) { ?>
            <div><?php
                if ( ! is_array( $contentHtml ) ) { ?>

                    <div class="ct-pagitem"><?php echo $label; ?></div>
                    <div class="profile-tab"><?php echo $contentHtml; ?></div>
                    <?php
                }
                else {
                    $span = $contentHtml[0];
                    $contentHtml = $contentHtml[1];
                    // id="profile-tab-<?php echo $count++; "
                    ?>
                    <div class="ct-pagitem"><?php echo $label; ?><span class="tab-desc"><?php echo $span; ?></span></div>
                    <div class="profile-tab"><?php echo $contentHtml; ?></div>
                    <?php
                } ?>
            </div><?php
            } ?>
        </div> <!-- profile-tabs div -->
        <?php
        dbg("e20rClientViews::view_clientProfile() - Checking whether to load pop-up warning for incomplete intake interview");

        if ( ($two_weeks <= $today) && (false === $complete_interview) ) {

            dbg("e20rClientViews::view_clientProfile() - Loading pop-up warning for incomplete intake interview: {$current_user->ID}");

            $message = apply_filters('e20r-tracker-interview-warning-message', sprintf(
                __("We know these are annoying, but we <em>really</em> need you to complete your \"Welcome Interview\" soon. Please help us out by clicking the \"<strong>%s</strong>\" button (below), and complete your interview. It should only take about 20 minutes to complete, and will help us adapt your program to your current situation. Also it will make everything \"A-OK\", legally. (Added bonus:  You help us avoid getting stern letters from our legal eagles!)", "e20rtracker"), __("I'll fix it", "e20rtracker") ) );

            $interview_warning = "<h4>" . __("Your Welcome Interview is incomplete", "e20rtracker") . "</h4>";
            $interview_warning .= "<p>" . $message . "</p>";

            echo $this->add_popup_overlay($current_user->ID, $interview_warning);
        }

        ?>
        <?php

        $html = ob_get_clean();
        return $html;
    }

    public function add_popup_overlay( $clientId, $popup_text ) {

        dbg("e20rArticleView::add_popup_overlay() - Loading pop-up for {$clientId}");

        $client = get_user_by('ID', $clientId );

        ob_start(); ?>
        <div class="e20r-boxes clearfix">
          <div class="<?php echo apply_filters("e20r-tracker-dialog-class", 'e20r-dialog' );?> window">
            <h3 class="<?php echo apply_filters("e20r-tracker-article-popup-h3", 'e20r-popup-h3' );?>"><?php echo apply_filters('e20r-tracker-article-popup-header-text', __("Warning", "e20rtracker") ); ?></h3>
            <div class="<?php echo apply_filters("e20r-tracker-article-popup-paragraph", 'e20r-popup-paragraph' );?> clearfix">
                <?php echo apply_filters('e20r-tracker-article-popup-message-text', $popup_text ); ?>
            </div>
            <div class="e20r-footer-placement">
                <div class="<?php echo apply_filters("e20r-tracker-article-popup-message-footer", 'e20r-popupfoot'); ?>">
                    <a href="#" class="close button <?php echo apply_filters("e20r-tracker-article-popup-agree-class", 'agree' );?>"><?php echo apply_filters( 'e20r-tracker-article-popup-agree-text', __("I'll fix it", "e20rtracker") );?></a>
                    <a class="button secondary <?php echo apply_filters("e20r-tracker-article-popup-agree-class", 'agree' );?>" href="#"><?php echo apply_filters( 'e20r-tracker-article-popup-disagree-text', __("Remind me", "e20rtracker") );?></a>
                </div>
            </div>
          </div>
          <div class="e20r-mask"></div>
        </div><?php
        $html = ob_get_clean();

        return $html;
    }

/*
    public function view_clientProfile( $progressEntries ) {

        ob_start(); ?>
        <!-- <div id="profile-tabs" class="z-tabs-loading" data-role='z-tabs' data-options='{"theme": "white", "style": "clean", "defaultTab": "tab1", "size": "medium", "position": "top-compact", "multiline": true, "rounded": false, "orientation": "horizontal", "animation": {"duration": 800, "effects": "slideH"}}'> -->
        <div id="profile-tabs" class="z-tabs-loading">
            <ul><?php

            $count = 1;
            foreach( $progressEntries as $label => $contentHtml ) {

                if ( ! is_array( $contentHtml ) ) { ?>

                <li><a href="#profile-tab-<?php echo $count++; ?>"><?php echo $label; ?></a></li><?php
                }
                else {
                    $span = $contentHtml[0];
                    $contentHtml = $contentHtml[1];
                ?>
                    <li><a href="#profile-tab-<?php echo $count++; ?>"><?php echo $label; ?><span><?php echo $span; ?></span></a></li><?php
                }
            } ?>

            </ul><?php
            $count = 1;
            foreach( $progressEntries as $label => $contentHtml ) {

            if (is_array( $contentHtml ) ) {
                $span = $contentHtml[0];
                $contentHtml = $contentHtml[1];
            } ?>
            <div id="profile-tab-<?php echo $count++; ?>">
                <?php echo $contentHtml; ?>
            </div>

    <?php   } ?>
        </div> <!-- profile-tabs div -->
        <span class="z-spinner"></span><?php

        $html = ob_get_clean();
        return $html;

    }
*/
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
//        $actions = new e20rAction();
//        $items = $actions->get_checkinItems( $shortname, $level_id );

        // TODO: show a graph for the users compliance.
    }

    public function profile_view_user_settings( $userId, $programs ) {

        $checked = null;

        if ( user_can( $userId, 'e20r_coach' ) ) {
            $checked = 'checked="checked"';
        }

        $included = get_user_meta( $userId, "e20r-tracker-coaching-program_ids" );
        //$included = $included;

        dbg("e20rClientViews::profile_view_user_settings() - The current UserId ({$userId}) is a coach for the following program IDs: ");
        dbg($included);

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
            <tr>
                <th><label for="e20r-tracker-coaching-programs"><?php _e( "Programs this user is a coach for", "e20rtracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-coach-for-programs" name="e20r-tracker-coach-for-programs[]" multiple="multiple">
                        <option value="0">None</option><?php
                        foreach( $programs as $pid => $program ) { ?>

                        <option value="<?php echo esc_attr( $pid ); ?>" <?php echo ( in_array( $pid, $included ) ? 'selected="selected"' : null ) ;?>><?php echo esc_attr( $program ); ?></option><?php

                        } ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php

        $html = ob_get_clean();
        return $html;
    }
/*
    private function viewAssignments( $clientId ) {

    }

    public function load_billing_data( $client_id = 0 ) {

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

        $manage_checkin_items = new e20rAction();
        $data = $manage_checkin_items->view_AddNewCheckinItem();

        echo $data;
    }

    public function render_new_program_page() {

        $programs = new e20rProgram();

        echo $programs->viewProgramEditSelect();

    }

    public function render_meals_page() {

    }
*/
} 