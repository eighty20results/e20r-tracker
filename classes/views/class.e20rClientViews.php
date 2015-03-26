<?php

class e20rClientViews {

    protected $client_id;

    public function __construct( $id = null ) {

        dbg("e20rClientviews::__construct() - Setting client ID");

        if ( ! empty( $id ) ) {

            $this->client_id = $id;
        }


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

    public function viewClientDetail( $clientId ) {
        // TODO: Display upcoming appointments, track attendance (and timeliness - multiple statuses)?
        // TODO: Pull data from appointments table and use Checkin tables for status(es)..?

        // $billingInfo = $this->load_billing_data( $clientId );
        $program_list = new e20rProgram();
        // $programData = $program_list->load_client_programs( $clientId );
        /*
        try {
            $appointments = $this->load_appointments();
        }
        catch ( Exception $e ) {
            dbg("Exception thrown: " . $e->getMessage() );
            return false;
        }
    */
    }

    public function viewClientAdminPage( $lvlName = '' ) {

	    global $e20rCheckin;
	    global $e20rAssignment;

        if ( is_admin() ) {

            add_thickbox();
        }
        ?>
        <H1>Client Data</H1>
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
                        <!--                            <td><a href="#" id="e20r-client-info" class="e20r-choice-button button" ><?php _e('Client Info', 'e20r-tracker'); ?></a></td>
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
			    <li><a href="#tabs-4">Client info</a></li>
		    </ul>
	        <div id="tabs-1">
		        <div id="e20r-progress-measurements">
		        </div>
	        </div>
		    <div id="tabs-2">
			    <div id="e20r-progress-assignments">
				    <?php echo $e20rAssignment->listUserAssignments( $this->client_id ) ?>
			    </div>
		    </div>
		    <div id="tabs-3">
		        <div id="e20r-progress-accomplishments">
			        <?php echo $e20rCheckin->listUserAccomplishments( $this->client_id ); ?>
		        </div>
		    </div>
		    <div id="tabs-4">
			    <div id="e20r-client-info">
				    <?php echo $this->viewClientDetail( $this->client_id ); ?>
			    </div>
		    </div>
	    </div>
	    <div class="modal"><!-- At end of form --></div>
    <?php
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