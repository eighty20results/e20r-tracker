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

use E20R\Tracker\Controllers\Action;
use E20R\Tracker\Controllers\Assignment;
use E20R\Tracker\Controllers\PMPro;use E20R\Tracker\Controllers\Tracker;
use E20R\Tracker\Controllers\Client;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Workout;
use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Time_Calculations;

/**
 * Class Client_Views
 * @package E20R\Tracker\Views
 */
class Client_Views {

    /**
     * @var null|Client_Views
     */
    private static $instance = null;
    
    /**
     * @var int
     */
    protected $client_id;
    
    /**
     * Instantiate or return the Client_Views class
     *
	 * @return Client_Views
	 */
	static function getInstance() {

    	if ( is_null( self::$instance ) ) {
    		self::$instance = new self;
	    }

	    return self::$instance;
	}

	/**
     * Show message history for client
     *
     * @param int $client_id
     * @param string[] $messages
     *
     * @return string
     */
    public function viewMessageHistory( $client_id, $messages ) {
     
	    ob_start(); ?>
         <table class="e20r-client-message-history-table">
            <thead>
                <tr>
                    <th><?php _e("Sent", "e20r-tracker"); ?></th>
                    <th><?php _e("Subject", "e20r-tracker"); ?></th>
                    <th><?php _e("From", "e20r-tracker");?></th>
                </tr>
            </thead>
            <tbody><?php
            if ( empty( $messages ) ) { ?>
                <tr class="e20r-client-message-history-entry">
                    <td colspan="3">
                        <h3><?php _e("No message history found", "e20r-tracker" ); ?></h3>
                    </td>
                </tr><?php
            }
            else {

                foreach( $messages as $when => $message ) {
                    $when = date_i18n('Y-m-d', $when );
                    $sender = get_user_by('id', $message->sender_id ); ?>

                <tr class="e20r-client-message-history-entry">
                    <td class="e20r-client-message-history-date">
                        <?php echo esc_html( $when ); ?>
                    </td>
                    <td class="e20r-client-message-history-subject">
                        <a href="#TB_inline?width=500&height=300&inlineId=message_<?php  esc_attr_e( $message->id ); ?>" class="thickbox"><?php esc_attr_e( stripslashes($message->topic) ); ?></a>
                        <div id="message_<?php esc_attr_e( $message->id ); ?>" class="e20r-message-history-content" style="display:none">
                            <div class="e20r-message-content">
                                <h3 class="e20r-client-message-title"><?php echo esc_attr(stripslashes($message->topic)); ?></h3>
                                <hr/>
                                <?php echo wp_kses_post( $this->remove_html_comments( $message->message ) ); ?>
                            </div>
                        </div>
                    </td>
                    <td class="e20r-client-message-history-sender">
                        <?php echo esc_attr( stripslashes($sender->user_firstname) ); ?>
                    </td>
                </tr>
                <?php
                }
            } ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Remove HTML comments
     *
     * @param string $content
     *
     * @return null|string|string[]
     */
    private function remove_html_comments($content = '') {
	    return preg_replace('/<!--(.|\s)*?-->/', '', $content);
    }

    /**
     * Generate HTML for list of clients
     *
     * @param $clients
     *
     * @return string
     */
    public function displayClientList( $clients ) {

        $Program = Program::getInstance();
        
        global $current_user;

        $today = current_time( 'timestamp', true );

        ob_start(); ?>
        <div class="e20r-coach-data">
            <table id="e20r-client-list-legend">
                <tbody>
                    <tr>
                        <td colspan="2"><h5><?php _e('Legend', 'e20r-tracker' ); ?></h5></td>
                    </tr>
                    <tr>
                        <td class="e20r-weakest-text"><?php _e("Weakest text", "e20r-tracker"); ?></td>
                        <td class="e20r-weakest-text"><?php _e("Client accessed system in the past 3 days", "e20r-tracker"); ?></td>
                    </tr>
                    <tr>
                        <td class="e20r-weak-text"><?php _e("Weaker text", "e20r-tracker"); ?></td>
                        <td class="e20r-weak-text"><?php _e("Client accessed system in the past 3 - 10 days", "e20r-tracker"); ?></td>
                    </tr>
                    <tr>
                        <td class="e20r-strong-text"><?php _e("Strong text", "e20r-tracker"); ?></td>
                        <td class="e20r-strong-text"><?php _e("More than 10 days since the client accessed this system", "e20r-tracker"); ?></td>
                    </tr>
                    <tr class="e20r-followup-critical">
                        <td><?php _e("Critical", "e20r-tracker"); ?></td>
                        <td><?php _e("No recorded contact with this client in more than 14 days, or there's never been any recorded contact", "e20r-tracker"); ?></td>
                    </tr>
                    <tr class="e20r-followup-warning">
                        <td><?php _e("Warning", "e20r-tracker"); ?></td>
                        <td><?php _e("No recorded contact with this client in between 7 and 14 days", "e20r-tracker"); ?></td>
                    </tr>
                    <tr class="e20r-followup-normal">
                        <td><?php _e("Normal", "e20r-tracker"); ?></td>
                        <td><?php _e("Client contact recorded sometime in past 7 days", "e20r-tracker"); ?></td>
                    </tr>
                </tbody>
            </table>
            <table id="e20r-client-list">
            <tbody class="e20r-client-list-body"><?php
                foreach( $clients as $programId => $clientList ) {

                    Utilities::get_instance()->log("Processing list of clients related to program # {$programId} for coach {$current_user->ID}: " . print_r( $clientList, true ));

                    $program_name = $Program->getProgramName( $programId ); ?>
                <tr class="e20r-client-list-programs">
                    <td class="e20r-client-list-program-name" colspan="3"><h4><?php esc_attr_e( $program_name ); ?></h4></td>
                </tr>
                <tr class="e20r-client-list-header-row">
                    <th class="e20r-client-name"><label><?php _e( "Client", "e20r-tracker" ); ?></label></th>
                    <th class="e20r-client-last-login"><label><?php _e("Last login by client", "e20r-tracker"); ?></label></th>
                    <th class="e20r-client-last-message"><label><?php _e("Last message from us", "e20r-tracker"); ?></label></th>
                </tr><?php

                    foreach( $clientList as $client ) {

                        Utilities::get_instance()->log( "Client info: " . print_r( $client, true ) );

                        // $level_id = $Tracker->getGroupIdForUser( $client->ID );
                        
                        if ( ! empty( $client->status->recent_login ) ) {
                            $days_since_login = Time_Calculations::daysBetween( $client->status->recent_login, $today, get_option('timezone_string') );
                        } else {
                            $days_since_login = PHP_INT_MAX;
                        }
                        
                        $program_length = Time_Calculations::daysBetween( strtotime( $client->status->program_start), $today, get_option('timezone_string') );

                        $css_flag = "e20r-strong-text";

                        if ( ( $program_length >= 2 ) && ( 10 <= $days_since_login ) ) {
                            $css_flag = "e20r-strong-text";
                        }

                        if ( ( $program_length >= 2 ) && ( 10 > $days_since_login && 3 <= $days_since_login ) ) {
                            $css_flag = "e20r-weak-text";
                        }

                        if ( ( $program_length >= 2 ) && ( 3 > $days_since_login ) ) {
                            $css_flag = "e20r-weakest-text";
                        }

                        $msg_when = key( $client->status->last_message );
                        $msg_txt = $client->status->last_message[$msg_when];

                        $days_since_msg = ( $msg_when != 'empty' ? Time_Calculations::daysBetween( $msg_when, $today, get_option('timezone_string') ) : null );
                        $status_flag = 'e20r-followup-normal';

                        Utilities::get_instance()->log("User: {$client->ID}. Days since last message: {$days_since_msg}. Program length: {$program_length}. And days since last login: {$days_since_login}");

                        if ( ( $program_length >= 7 ) && ( $days_since_msg >= 7  ) ) {
                            $status_flag = 'e20r-followup-warning';
                        }

                        if ( ( $program_length >= 7 ) && ( ( null === $days_since_msg ) || ( $days_since_msg > 14 ) ) ) {
                            // Never sent a message to the client. Flag as "critical to follow up"
                            $status_flag = 'e20r-followup-critical';
                        }
                        Utilities::get_instance()->log("Loading info about sender of the last message to user {$client->ID}");

                        if ( !empty( $client->status->last_message_sender ) ) {

                            Utilities::get_instance()->log("user info for the sender: {$client->status->last_message_sender}");
                            $sender = get_user_by( 'id', $client->status->last_message_sender);
                            $message_info = "By " . $sender->user_firstname . " on " . date( 'l F jS, Y', $msg_when );

                        }
                        else {
                            $message_info = "No message sent";
                            $msg_txt = null;
                        }

                        Utilities::get_instance()->log( "Info about last message sent to user: " . $message_info );
                    ?>
                    <tr class="e20r-client-list-row <?php esc_attr_e(  $css_flag ); ?> <?php esc_attr_e( $status_flag ); ?>">
                        <td class="e20r-client-name"><a href="<?php echo esc_url_raw( add_query_arg( array( 'page' => 'e20r-client-info', 'e20r-client-id' => $client->ID, 'e20r-level-id' => $programId ), admin_url( "admin.php" ) ) );?>" target="_blank"><?php echo $client->display_name; ?></td>
                        <td class="e20r-client-last-login"><?php echo ( !empty($client->status->recent_login) ? date_i18n( 'l F jS, Y', $client->status->recent_login ) : __('Never', 'e20r-tracker' ) ); ?></td>
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
    
    
    /**
     * Display the Settings for the client
     *
     * @param int[] $programList
     * @param int $activePgm
     * @param int[] $coachList
     * @param int[] $coach_ids
     * @param \WP_User $client
     *
     * @return string
     */
    public function viewClientSettingsPage( $programList, $activePgm, $coachList, $coach_ids, $client ) {

	    if ( empty( $programList ) ) {
		    $programList = array();
	    }
	    
	    $utils = Utilities::get_instance();
	    global $current_user;
	    global $currentProgram;
	    global $currentClient;
	    
        $settings_user_id = $utils->get_variable( 'e20r_tracker_client', 0 );
        $gender = isset( $client->gender ) ? $client->gender : ''; // TODO: Load gender from settings
        
        if ( !empty( $settings_user_id ) ) {
            $client = get_user_by( 'ID', $settings_user_id );
        }
        
        $programIDs = array_keys( $programList );
        $coachIDs = array_keys( $coach_ids );
        $is_active = false;
        
        if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
            $has_level =  PMPro::getInstance()->getLastLevelForUser( $client->ID );
            $utils->log("Member level info: " . print_r( $has_level, true ));
            $is_active = ( isset($has_level->status ) && 'active' === $has_level->status );
        }
        
        ob_start(); ?>
        <form action="" method="post" class="e20r-tracker-client-settings">
            <input type="hidden" name="e20r-tracker-client-record_id" value="<?php echo !empty($currentClient->id) ? esc_attr( $currentClient->id ) : null; ?>" />
        <?php
        if ( empty( $client ) || ( $current_user->ID === $client->ID && current_user_can( 'manage_options' ) ) ) {
            
            $utils->log("Loading the member selection dialog...");
            $label = __( 'Load Client', 'e20r-tracker' );
            echo $this->loadMemberSelect(null);
        } else {
            
            $label = __( 'Save Settings', 'e20r-tracker' );
            $reset_label = null; ?>
        <h3><?php printf( __("Program Settings for %s", "e20r-tracker"), $client->display_name ); ?></h3>
        <table class="form-table">
            <input type="hidden" name="e20r_tracker_client" value="<?php esc_attr_e( $client->ID ); ?>" />
            <tr>
                <th><label for="e20r-tracker-user-program"><?php _e( "Member of program(s)", "e20r-tracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-user-program" name="e20r-tracker-user-program[]" class="select2-container" multiple="multiple">
                        <option value="0" <?php selected( $activePgm, 0 ) ?>><?php _e( "Not Applicable", "e20r-tracker" ); ?></option>
                        <?php

                        foreach( $programList as $id => $obj ) {
                            ?><option value="<?php esc_attr_e($id); ?>" <?php selected( $id, $activePgm ); ?>><?php esc_attr_e($obj->title); ?></option> <?php
                        } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="e20r-tracker-user-program_startdate"><?php _e( "Started on", "e20r-tracker"); ?></label></th>
                <td>
                <input type="datetime-local" name="e20r-tracker-user-program_start_date" id="e20r-tracker-user-program_start_date" value="<?php esc_attr_e( $currentClient->program_start ); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="e20r-tracker-user-program_active"><?php _e( "Active member?", "e20r-tracker"); ?></label></th>
                <td>
                    <input type="text" name="e20r-tracker-user-program_active" id="e20r-tracker-user-program_active" value="<?php echo ( true === $is_active ? __('Yes', 'e20r-tracker' ) : __( 'No', 'e20r-tracker') ); ?>" disabled="disabled" />
                </td>
            </tr>
            <?php if ( false === $is_active  ) { ?>
             <tr>
                <th><label for="e20r-tracker-user-program_ended"><?php _e( "End of membership", "e20r-tracker"); ?></label></th>
                <td>
                    <input type="datetime-local" name="e20r-tracker-user-program_ended" id="e20r-tracker-user-program_ended" value="<?php esc_attr_e( $has_level->enddate ); ?>" disabled="disabled" />
                </td>
            </tr>
            <?php } ?>
            <tr>
                <th><label for="e20r-tracker-user-gender"><?php _e( "Identifies as", "e20r-tracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-user-gender" name="e20r-tracker-user-gender" class="select2-container">
                        <option value="" <?php selected( $gender, '');  ?>><?php _e( "Not Specified", "e20r-tracker" ); ?></option>
                        <option value="m" <?php selected( $gender, 'm' ) ?>><?php _e( "Male", "e20r-tracker" ); ?></option>
                        <option value="f" <?php selected( $gender, 'f' ) ?>><?php _e( "Female", "e20r-tracker" ); ?></option>
                    </select>
                </td>
            </tr>
            <tr><?php
                        $user_roles = apply_filters('e20r-tracker-configured-roles', array() );
                        $has_exercise_role = false;
                        
                        foreach ( $user_roles as $role_key => $role_info ) {
                            $has_exercise_role = $has_exercise_role || user_can( $client->ID, $role_info['role'] );
                        } ?>
                <th><label for="e20r-tracker-user-assigned_role"><?php _e( "Exercise Experience", "e20r-tracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-user-assigned_role" name="e20r-tracker-user-assigned_role" class="select2-container">
                        <option value="0" <?php echo (false === $has_exercise_role ? 'selected="selected"' : null);  ?>><?php _e( "Unassigned", "e20r-tracker" ); ?></option>
                        <?php

                        foreach( $user_roles as $key => $role_def ) { ?>

                            <option value="<?php echo esc_attr($role_def['role']); ?>" <?php echo (true == user_can( $client->ID, $role_def['role'])? 'selected="selected"' : null); ?>>
                                <?php echo esc_attr($role_def['label']); ?>
                            </option> <?php
                        } ?>

                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="e20r-tracker-user-coach_id"><?php _e( "Assigned Coach", "e20r-tracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-user-coach_id" name="e20r-tracker-user-coach_id[]" class="select2-container">
                        <option value="0" <?php echo $utils->selected( 0, $coachIDs ) ?>><?php _e( "Unassigned", "e20r-tracker" ); ?></option><?php

                        foreach( $coachList as $id => $name ) { ?>
                        <option value="<?php esc_attr_e($id); ?>" <?php echo $utils->selected( $id, $coachIDs, false ); ?>><?php esc_attr_e($name ); ?></option> <?php
                        } ?>
                    </select>
                </td>
            </tr>
        </table><?php
        $reset_label = __( 'Back', 'e20r-tracker' );
        }
        
        if ( !empty( $reset_label ) ) { ?>
            <input type="submit" name="e20r-tracker-reset-btn" class="button button-reset" value="<?php esc_attr_e( $reset_label ); ?>">
            <?php
        } ?>
            <input type="submit" name="e20r-tracker-client-settings" class="button button-primary" value="<?php esc_attr_e( $label ); ?>">
        </form><?php
        
        $html = ob_get_clean();
        return $html;
    }
    
    private function loadMemberSelect( $levelId = null ) {
        
        global $currentClient;
        $Program = Program::getInstance();
        ob_start(); ?>
        <?php // wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' ); ?>
        <div class="e20r-select">
            <label for="e20r_members"><?php _e('Select client to view data for:', 'e20r-tracker' ); ?></label>
            <select name="e20r_tracker_client" id="e20r_members">
            <?php

            // $user_list = $Tracker->getUserList( $levelId );
            $user_list = ( !empty( $levelId ) ? $Program->getProgramMembers( $levelId ) : $Program->getUsersInPrograms() );

            if ( empty( $user_list ) ) { ?>
                <option value="-1"><?php _e("No users found!", 'e20r-tracker'); ?></option><?php
            } else {
                foreach ( $user_list as $user ) {

                    ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $user->ID, $currentClient->user_id ); ?> ><?php echo esc_attr($user->display_name); ?></option><?php
                }
            }?>
            </select>
            <!-- <span class="e20r-level-select-span"><a href="#" id="e20r-load-data" class="e20r-choice-button button"><?php _e('Load Progress', 'e20r-tracker'); ?></a></span> -->
            <input type="hidden" name="hidden_e20r_client_id" id="hidden_e20r_client_id" value="<?php echo ( isset( $currentClient->user_id ) && $currentClient->user_id != 0 ) ? $currentClient->user_id : 0 ?>" >
        </div><?php
        
        return ob_get_clean();
    }
    
    /**
     * Show Client data dashboard page for Coaches in the wp-admin back-end
     *
     * @param string $lvlName
     * @param int $level_id
     *
     * @return string
     */
    public function viewClientAdminPage( $lvlName = '', $level_id = -1 ) {
	    
        global $currentClient;
        
        $Action = Action::getInstance();
	    $Assignment = Assignment::getInstance();
        $Workout = Workout::getInstance();
        $Client = Client::getInstance();

        if ( is_admin() ) {

            add_thickbox();
            global $current_user;
            if ( $currentClient->user_id === $current_user->ID ) {
                $user_id = null;
            } else {
                $user_id = $currentClient->user_id;
            }
        }

        if ( is_numeric( $lvlName ) ) {

            $level_id = $lvlName;
        }

        ob_start();
        ?>
        <H1><?php _e( "Coaching Page", "e20r-tracker" ); ?></H1>
        <div class="e20r-client-service-select">
            <div id="spinner" class="e20r-spinner"></div>
            <?php echo $this->viewLevelSelect( $level_id ); ?>
            <div id="e20r-selectMember" class="startHidden">
            <?php echo $this->viewMemberSelect( $level_id ); ?>
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
                        <td>
                            <a href="#" id="e20r-client-load-measurements" class="e20r-choice-button button" >
                                <?php _e('Load', 'e20r-tracker'); ?>
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <hr class="e20r-admin-hr" />
	    <div id="status-tabs" style="max-width: 800px; width: 100%;" class="startHidden" data-role='z-tabs'>
		    <ul>
			    <li><a href="#tabs-1"><?php _e("Measurements", "e20r-tracker");?></a></li>
			    <li><a href="#tabs-2"><?php _e("Assignments", "e20r-tracker");?></a></li>
			    <li><a href="#tabs-3"><?php _e("Achievements", "e20r-tracker");?></a></li>
			    <li><a href="#tabs-4"><?php _e("Activities", "e20r-tracker");?></a></li>
                <li><a href="#tabs-5"><?php _e("Client Info", "e20r-tracker");?></a></li>
                <li><a href="#tabs-6"><?php _e("Send E-Mail", "e20r-tracker");?></a></li>
                <li><a href="#tabs-7"><?php _e("Message History", "e20r-tracker");?></a></li>
		    </ul>
	        <div id="tabs-1">
		        <div id="e20r-progress-measurements">
		        </div>
	        </div>
		    <div id="tabs-2">
			    <div id="e20r-progress-assignments">
				    <?php echo $Assignment->listUserAssignments( $user_id ) ?>
			    </div>
		    </div>
		    <div id="tabs-3">
		        <div id="e20r-progress-accomplishments">
			        <?php echo $Action->listUserAchievements( $user_id ); ?>
		        </div>
		    </div>
            <div id="tabs-4">
                <div id="e20r-progress-activities">
                    <?php echo $Workout->listUserActivities( $user_id ); ?>
                </div>
            </div>

            <div id="tabs-5">
			    <div id="e20r-client-info">
				    <?php echo $this->viewClientDetail( $user_id ); ?>
			    </div>
		    </div>
            <div id="tabs-6">
                <div id="e20r-client-contact">
                    <?php echo $this->viewClientContact( $user_id ); ?>
                </div>
            </div>
            <div id="tabs-7">
                <div id="e20r-client-message-history">
                    <?php echo $Client->loadClientMessages( $currentClient->user_id ); ?>
                </div>
            </div>
        </div>
	    <!-- <div class="modal"> --><!-- At end of form --><!-- </div> --><?php
        Utilities::get_instance()->log("Returning content for admin page.");
        return ob_get_clean();
    }
    
    public function viewLevelSelect( $levelId = -1 ) {
	    
        $Program = Program::getInstance();
        $Tracker = Tracker::getInstance();

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $Tracker->dependency_warnings();
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
                    <label for="e20r_levels"><?php _e("Product:", "e20r-tracker"); ?></label>
                    <span class="e20r-level-select-span">
                        <select name="e20r_levels" id="e20r_levels">
                            <option value="-1" <?php selected( -1, $levelId ); ?>><?php _e("None", "e20tracker");?></option>
                            <option value="0" <?php selected( 0, $levelId ); ?>><?php _e("All Programs", "e20r-tracker" ); ?></option>
                        <?php

                        $program_list = $Program->getPrograms();

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

    /**
     * Generate a list of program members
     *
     * @param null|int $levelId
     *
     * @return string
     */
    public function viewMemberSelect( $levelId = null ) {
	    
        $Tracker = Tracker::getInstance();

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $Tracker->dependency_warnings();
        }

        ob_start(); ?>
        <div class="e20r-client-list">
                <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <?php echo $this->loadMemberSelect( $levelId ); ?>
                </form>
            </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function viewClientDetail( $clientId = null ) {

	    if ( empty( $clientId ) ) {
	        return null;
	    }
	    
        $Program = Program::getInstance();

        global $currentClient;
        
        $Program->getProgramIdForUser( $clientId );

        $hideFields = array(
            'user_enc_key',
            'incomplete_interview',
            'loadedDefaults',
            'id',
        );

        Utilities::get_instance()->log("Client_Views::viewClientDetail() - Loaded interview/survey data for {$currentClient->user_id}");

        ob_start();

        echo $this->showLastLogin( $currentClient->user_id ); ?>
        <table class="e20r-client-information-table">
            <thead>
                <tr>
                    <th><?php _e("Question", "e20r-tracker"); ?></th>
                    <th><?php _e("Content", "e20r-tracker"); ?></th>
                </tr>
            </thead>
            <tbody><?php
            
            foreach( $currentClient as $field => $data ) {
                if ( 'user_enc_key' == $field ) {
                    continue;
                } ?>
                <tr class="e20r-clientdetail-row">
                    <td class="e20r-clientdetail-header"><?php esc_attr_e( $field ); ?></td><?php

                if ( ! is_array( $data ) ) { ?>
                    <td class="e20r-clientdetail-info"><?php esc_attr_e( $data ); ?></td><?php
                } else { ?>
                    <td class="e20r-clientdetail-info"><?php esc_attr_e( implode( ',', $data ) ); ?></td><?php
                } ?>
                </tr>
                <?php
            } ?>
            </tbody>
        </table><?php

        $html = ob_get_clean();

        Utilities::get_instance()->log("Client_Views::viewClientDetail() - Data loaded and form being returned");
        return $html;

    }

    /**
     * Display info about the most recent login by the specified client
     *
     * @param int $clientId
     *
     * @return string
     */
    public function showLastLogin( $clientId ) {

        $Tracker = Tracker::getInstance();

        $when = __( 'Never.', 'e20r-tracker' );
        $last_login = (int) get_user_meta( $clientId, '_e20r-tracker-last-login', true );
        $today = current_time( 'timestamp' );
        $user = get_user_by( 'id', $clientId );

        $days_since_login = 0;

        $program_length = $Tracker->getDateFromDelay( 'now', $clientId );

        if ( !empty( $last_login ) ) {

            $format = apply_filters( "e20r-tracker-date-format", get_option( 'date_format') );

            if ( 0 != $last_login ) {
                $when = date_i18n( $format, $last_login );
                $days_since_login = Time_Calculations::daysBetween( $last_login, $today, get_option('timezone_string') );
            }
        }
        else {
            Utilities::get_instance()->log("No last_login information found for {$clientId}!");
            $when = 'Not recorded?';
            $days_since_login = -1000;
        }

        ob_start();

        $user_firstname = ( !isset( $user->user_firstname ) ? 'N/A': $user->user_firstname );

        if ( ( $program_length >= 2 ) && ( 10 <= $days_since_login ) ) { ?>
            <div class="red-notice">
                <h3 style="color: #004CFF;"><?php echo sprintf( __("Please send a reminder to %s", "e20r-tracker"), $user->user_firstname ); ?></h3><?php
        }

        if ( ( $program_length >= 2 ) && ( 10 > $days_since_login && 3 < $days_since_login ) ) { ?>
            <div class="orange-notice">
            <h3 style="color: #004CFF;"><?php echo sprintf( __("Please send a reminder to %s", "e20r-tracker"), $user->user_firstname ); ?></h3><?php
        }

        if ( ( $program_length >= 2 ) && ( 3 >= $days_since_login ) ) { ?>
            <div class="green-notice"><?php
        }?>
                <p><?php echo sprintf( __('The last recorded access for %s was: <em style="text-decoration: underline;">%s</em>', "e20r-tracker"), $user_firstname, $when );?></p>
            </div><?php
        $html = ob_get_clean();

        return $html;
    }
    

    /**
     * Generate the "Contact the client" form(s) for the Coach page
     *
     * @param null|int $clientId
     *
     * @return string
     */
    public function viewClientContact( $clientId = null ) {

	    if ( empty( $clientId ) ) {
	        return null;
	    }
	    
        $Program = Program::getInstance();
        
        global $currentClient;
        global $current_user;

        $Program->getProgramIdForUser( $clientId );
        
        $client = get_user_by('id', $clientId );

        $first_name = ( isset( $currentClient) && empty( $currentClient->first_name ) ? $client->user_firstname : $currentClient->first_name );
        $last_name = ( isset( $currentClient) && empty( $currentClient->last_name ) ? $client->user_lastname : $currentClient->last_name );
        $email = ( isset( $currentClient) && empty( $currentClient->email ) ? $client->user_email : $currentClient->email );
        $incomplete = ( isset( $currentClient) && empty( $currentClient->email ) ? true : false );

        ob_start(); ?>
        <form id="e20r-message-form">
            <input type="hidden" name="e20r-send-to-id" id="e20r-send-to-id" value="<?php esc_attr_e( $currentClient->user_id ); ?>">
            <input type="hidden" name="e20r-send-message-to" id="e20r-send-message-to" value="<?php esc_attr_e(  $email ); ?>">
            <input type="hidden" name="e20r-send-message-cc" id="e20r-send-message-cc" value="<?php esc_attr_e( $current_user->user_email ); ?>">
            <input type="hidden" name="e20r-send-from-id" id="e20r-send-from-id" value="<?php esc_attr_e( $current_user->ID ); ?>">
            <?php if ( true === $incomplete ) { ?>
                <div class="red-notice">
                    <?php _e("Incomplete client interview. Please ask {$first_name} to complete the welcome interview as quickly as possible", "e20r-tracker" ); ?>
                </div><?php
            }?>
            <table id="e20r-send-message">
                <thead>
                <tr>
                    <th colspan="3" class="e20r-activity-table-header"><?php printf( __("Send message to: %s %s &lt;<em>%s</em>&gt;", "e20r-tracker" ), esc_attr( $first_name ), esc_attr( $last_name ), esc_attr( $email ) ); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="e20r-message-header">
                        <?php _e("From", "e20r-tracker"); ?>:
                    </td>
                    <td class="e20r-message-from">
                        <input class="e20r-message-input" type="text" name="e20r-email-from-name" id="e20r-email-from-name" autocomplete="off" value="<?php printf( '%s %s',__("Coach", "e20r-tracker"), esc_attr( $current_user->user_firstname ) ); ?>" />
                    </td>
                    <td class="e20r-message-from">
                        <input class="e20r-message-input" type="email" name="e20r-email-from" id="e20r-email-from" autocomplete="off" value="<?php esc_attr_e($current_user->user_email ); ?>" />
                    </td>
                </tr>
                <tr>
                    <td class="e20r-message-header">
                        <?php _e("Subject", "e20r-tracker"); ?>:
                    </td>
                    <td class="e20r-message-subject" colspan="2">
                        <input class="e20r-message-input" type="text" name="e20r-email-subject" id="e20r-email-subject" autocomplete="off" placeholder="<?php _e('Enter subject for email', 'e20r-tracker'); ?>" />
                    </td>
                </tr>
                <tr>
                    <td colspan="3"><hr/></td>
                </tr>
                <tr>
                    <td class="e20r-message-subject" colspan="3">
                        <!--
                        <div class="gutenberg">
                            <div id="editor" class="gutenberg__editor"></div>
                        </div>
                        -->
                        <textarea class="e20r-message-input" id="e20r-message-content" autocomplete="off" name="e20r-message-content" placeholder="<?php _e('Message to client', 'e20r-tracker'); ?>" rows="20" cols="75"> </textarea><?php
                        
                        $settings = array(
                            'textarea_name' => 'e20r-message-content',
                            'quicktags' => false,
                            'textarea_rows' => 20,
                            'media_buttons' => false,
                            'tinymce' => array(
                                'theme_advanced_buttons1' => 'formatselect,|,bold,italic,underline,|,' .
                                    'bullist,blockquote,|,justifyleft,justifycenter' .
                                    ',justifyright,justifyfull,|,link,unlink,|' .
                                    ',spellchecker,wp_fullscreen,wp_adv',
                            ),
                        );
                        // wp_editor( '', 'e20r-message-content', $settings ); ?>
                    </td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td>
                        <?php

                        $btn_attrs = array( 'id' => 'e20r-send-email-message' );
                        submit_button( __("Send message", "e20r-tracker"), 'primary', 'e20r-send-email-message', true, $btn_attrs );
                        ?>
                    </td>
                    <td style="font-size: 0.7rem; text-align: right;"><?php _e("When to send (empty = now)", "e20r-tracker"); ?></td>
                    <td style="font-size: 0.7rem;">
                        <input type="text" name="e20r-tracker-send-message-datetime" id="e20r-tracker-send-message-datetime">
                        <?php printf( __( "TZ: %s", "e20r-tracker" ),  get_option( 'timezone_string' ) ); ?>
                    </td>
                </tr>
                </tfoot>
            </table>
        </form><?php

        $html = ob_get_clean();

        Utilities::get_instance()->log("Client_Views::viewClientContact() - Data loaded and form being returned");
        return $html;
    }

    /**
     * Generates the user's Tracker status page (dashboard)
     *
     * @param array $progressEntries
     *
     * @return string
     */
    public function viewClientProfile( $progressEntries ) {

        global $currentProgram;
        global $current_user;

        $Client = Client::getInstance();

        $today = current_time('timestamp');
        $four_days = strtotime("{$currentProgram->startdate} + 5 days");
        $complete_interview = $Client->completeInterview($current_user->ID);

        ob_start(); ?>
        <div id="profile-tabs" class="ct ct-underline"><?php
          
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
        Utilities::get_instance()->log("Checking whether to load pop-up warning for incomplete intake interview");

        if ( ($four_days <= $today) && (false === $complete_interview) ) {

            Utilities::get_instance()->log("Loading pop-up warning for incomplete intake interview: {$current_user->ID}");

            $message = apply_filters('e20r-tracker-interview-warning-message', sprintf(
                __("We know these are annoying, but we <em>really</em> need you to complete your \"Welcome Interview\" soon. Please help us out by clicking the \"<strong>%s</strong>\" button (below), and complete your interview. It should only take about 20 minutes to complete, and will help us adapt your program to your current situation. Also it will make everything \"A-OK\", legally. (Added bonus:  You help us avoid getting stern letters from our legal eagles!)", "e20r-tracker"), __("I'll fix it", "e20r-tracker") ) );

            $interview_warning = "<h4>" . __("Your Welcome Interview is incomplete", "e20r-tracker") . "</h4>";
            $interview_warning .= "<p>" . $message . "</p>";

            echo $this->addPopupOverlay($current_user->ID, $interview_warning);
        }

        ?>
        <?php

        $html = ob_get_clean();
        return $html;
    }

    /**
     * Add overlay for client "Welcome Interview Incomplete" warning
     *
     * @param int $clientId
     * @param string $popup_text
     *
     * @return string
     */
    public function addPopupOverlay( $clientId, $popup_text ) {

        Utilities::get_instance()->log("Loading pop-up for {$clientId}");

        ob_start(); ?>
        <div class="e20r-boxes clearfix">
          <div class="<?php echo apply_filters("e20r-tracker-dialog-class", 'e20r-dialog' );?> window">
            <h3 class="<?php echo apply_filters("e20r-tracker-article-popup-h3", 'e20r-popup-h3' );?>"><?php echo apply_filters('e20r-tracker-article-popup-header-text', __("Warning", "e20r-tracker") ); ?></h3>
            <div class="<?php echo apply_filters("e20r-tracker-article-popup-paragraph", 'e20r-popup-paragraph' );?> clearfix">
                <?php echo apply_filters('e20r-tracker-article-popup-message-text', $popup_text ); ?>
            </div>
            <div class="e20r-footer-placement">
                <div class="<?php echo apply_filters("e20r-tracker-article-popup-message-footer", 'e20r-popupfoot'); ?>">
                    <div class="e20r-footer-button-div">
                        <a href="#" class="close button <?php echo apply_filters("e20r-tracker-article-popup-agree-class", 'agree' );?>"><?php echo apply_filters( 'e20r-tracker-article-popup-agree-text', __("I'll fix it", "e20r-tracker") );?></a>
                    </div>
                    <div class="e20r-footer-button-div">
                        <a class="button  <?php echo apply_filters("e20r-tracker-article-popup-agree-class", 'disagree' );?>" href="#"><?php echo apply_filters( 'e20r-tracker-article-popup-disagree-text', __("Remind me", "e20r-tracker") );?></a>
                    </div>
                </div>
            </div>
          </div>
          <div class="e20r-mask"></div>
        </div><?php
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Display the program compliance data
     *
     * @param null|int $clientId
     * @param null|string $shortname
     */
    public function viewCompliance( $clientId = null, $shortname = null ) {

        if ( empty( $clientId ) ) {

            $Tracker = Tracker::getInstance();
            
            global $current_user;
            $clientId = $current_user->ID;

            if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
                $level_id = pmpro_getMembershipLevelForUser( $clientId );
            }
            else {
                $Tracker->dependency_warnings();
            }
        }

        if ( empty( $shortname ) ) {

            if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
                $level_id = pmpro_getMembershipLevelForUser( $clientId );
            }
            else {
                $Tracker->dependency_warnings();
            }
        }

        // TODO: Implement compliance data loader.
    }

    /**
     * Load the user specific Tracker settings for the /wp-admin/ User Profile page
     *
     * @param int $userId
     * @param int[] $programs
     *
     * @return string
    */
    public function profileViewUserSettings( $userId, $programs ) {

        $checked = null;
        $user_roles = apply_filters('e20r-tracker-configured-roles', array());

        if ( user_can( $userId, $user_roles['coach']['role']) ) {
            $checked = 'checked="checked"';
        }

        $included = get_user_meta( $userId, "e20r-tracker-coaching-program_ids" );
        //$included = $included;

        Utilities::get_instance()->log("The current UserId ({$userId}) is a coach for the following program IDs: " . print_r( $included, true ) );

        ob_start(); ?>
        <h3><?php _e("E20R-Tracker: Coach Info", "e20r-tracker"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="e20r-tracker-user-role"><?php _e( "User is a program coach", "e20r-tracker"); ?></label></th>
                <td>
                    <input type="checkbox" id="e20r-tracker-user-role" name="e20r-tracker-user-role" value="<?php esc_attr_e( $user_roles['coach']['role'] ); ?>" <?php echo $checked; ?>>
                </td>
            </tr>
            <tr>
                <th><label for="e20r-tracker-coach-for-programs"><?php _e( "Programs this user is a coach for", "e20r-tracker"); ?></label></th>
                <td>
                    <select id="e20r-tracker-coach-for-programs" name="e20r-tracker-coach-for-programs[]" multiple="multiple">
                        <option value="0"><?php _e('None', 'e20r-tracker' ); ?></option><?php
                        foreach( $programs as $pid => $program ) { ?>

                        <option value="<?php esc_attr_e( $pid ); ?>" <?php Utilities::get_instance()->selected( $pid, $included ); ?>><?php esc_attr_e( $program ); ?></option><?php

                        } ?>
                    </select>
                </td>
            </tr>
        </table><?php

        $html = ob_get_clean();
        return $html;
    }
}