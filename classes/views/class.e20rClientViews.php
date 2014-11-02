<?php

class e20rClientViews {

    public $old_tables;
    public $tables;

    private $levels = array(); // Empty array
    protected $client_id;

    function e20rClientViews( $id = null ) {

        dbg("Constructor for e20rClientViews");

        if ( ! empty( $id ) ) {
            $this->client_id = $id;
        }

        $tmp = new e20rTracker();

        $this->tables = $tmp->tables;


    }

    public function init() {

        $this->load_levels();
    }


    public function raise_error( $type ) {

        switch ( $type ) {

            case 'pmpro':

                if ( current_user_can( 'manage_options' ) ) {

                    echo '<div class="error">
                        <p>Client data depends on the Paid Memberships Pro plugin!</p>
                    </div>';
                }

                wp_die('Paid Memberships Pro plugin is not installed');

                break;
            case 'other':

                break;
            default:
                warn('Generic error during client data processing');
        }

    }

    public function get_level_ids() {

        return $this->levels;
    }

    public function set_levels( $level ) {

        $this->levels[] = $level; // Add a level
    }

    public function displayData() {

        // TODO: Create page for back-end to display customers.
    }

    public function get_item_count( $item_id, $habit_name, $user_id ) {

        $item_list = $this->get_items( $item_id );

    }

    private function prepare_in( $sql, $values, $type = '%d' ) {

        global $wpdb;

        $not_in_count = substr_count( $sql, '[IN]' );

        if ( $not_in_count > 0 ) {

            $args = array( str_replace( '[IN]',
                        implode( ', ', array_fill( 0, count( $values ), ( $type == '%d' ? '%d' : '%s' ) ) ),
                        str_replace( '%', '%%', $sql ) ) );

            for ( $i = 0; $i < substr_count( $sql, '[IN]' ); $i++ ) {
                $args = array_merge( $args, $values );
            }

            $sql = call_user_func_array(
                    array( $wpdb, 'prepare' ),
                    array_merge( $args ) );

        }

        return $sql;
    }

    public function viewLevelSelect() {

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $this->raise_error( 'pmpro' );
        }

        ob_start(); ?>

        <div class="e20r-selectLevel">
            <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_levels_nonce' ); ?>
                <div class="e20r-select">
                    <input type="hidden" name="hidden_e20r_level" id="hidden_e20r_level" value="0" >
                    <label for="e20r_levels">Filter by Membership Level:</label>
                    <span class="e20r-level-select-span">
                        <select name="e20r_levels" id="e20r_levels">
                            <option value="0" selected="selected">All levels</option>
                        <?php

                        $level_list = $this->fetch_levelList( false );

                        foreach( $level_list as $key => $name ) {
                            ?><option value="<?php echo esc_attr( $key ); ?>"  ><?php echo esc_attr( $name ); ?></option><?php
                        }
                ?>
                        </select>
                    </span>
                    <span class="e20r-level-select-span"><a href="#e20r_tracker_clientData" id="e20r-load-users" class="e20r-choice-button button"><?php _e('Load Users', 'e20r-tracker'); ?></a></span>
                    <span class="seq_spinner" id="spin-for-level"></span>
                </div>
            </form>
        </div>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function viewMemberSelect( $levelId = '' ) {

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $this->raise_error( 'pmpro' );
        }

        ob_start(); ?>
        <div class="startHidden e20r-selectMember">
            <div class="e20r-client-list">
                <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' ); ?>
                    <div class="e20r-select">
                        <label for="e20r_tracker_client">Select client to view data for:</label>
                        <select name="e20r_tracker_client" id="e20r_tracker_client">
                        <?php
                        $user_list = $this->fetch_userList( $levelId );

                        foreach ( $user_list as $user ) {

                            ?><option value="<?php echo esc_attr( $user->id ); ?>"  ><?php echo esc_attr($user->name); ?></option><?php
                        } ?>
                        </select>
                        <div id="spin-for-member" class="seq_spinner"></div>
                        <input type="hidden" name="hidden_e20r_client_id" id="hidden_e20r_client_id" value="0" >
                    </div>
                </form>
            </div>
        </div>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    public function fetch_levelList( $onlyVisible = false ) {

        $allLevels = pmpro_getAllLevels();
        $levels = array();

        foreach ($allLevels as $level ) {

            $visible = ( $level->allow_signups == 1 ? true : false );

            if ( ( ! $onlyVisible) || ( $visible && $onlyVisible )) {
                $levels[ $level->id ] = $level->name;
            }
        }

        asort( $levels );

        // dbg("Levels fetched: " . print_r( $levels, true ) );

        return $levels;
    }

    public function fetch_userList( $level = '' ) {

        global $wpdb;

        if ( ! empty($level) ) {
            $this->load_levels( $level );
        }

        $levels = $this->get_level_ids();

        dbg("Levels being loaded: " . print_r( $levels, true ) );

        if ( ! empty($levels) ) {
            $sql = "
                    SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                    FROM {$wpdb->users} AS u
                      INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                        ON ( u.ID = m.user_id )
                        INNER JOIN {$wpdb->usermeta} AS um
                        ON ( u.ID = um.user_id )
                    WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
                    ORDER BY last_name ASC
            ";
        }
        else {
            $sql = "
                    SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                    FROM {$wpdb->users} AS u
                      INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                        ON ( u.ID = m.user_id )
                        INNER JOIN {$wpdb->usermeta} AS um
                        ON ( u.ID = um.user_id )
                    WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' )
                    ORDER BY last_name ASC
            ";
        }
        $sql = $this->prepare_in( $sql, $levels );

        dbg("SQL for user list: " . print_r( $sql, true));

        $user_list = $wpdb->get_results( $sql, OBJECT );

        if (! empty( $user_list ) ) {
            return $user_list;
        }
        else {
            $data = new stdClass();
            $data->id = 0;
            $data->name = 'No users found';

            return array( $data );
        }

    }

    /* TODO: Create the client pages for the menu */
    public function render_client_page( $lvlName = '', $client_id = 0 ) {

        global $current_user;

        if ( $client_id == 0 ) {

            $client_id = $current_user->ID;
        }

        ?>
        <H1>Client Data</H1>
        <div class="e20r-client-service-select">
            <?php echo $this->viewLevelSelect(); ?>
            <?php echo $this->viewMemberSelect( $lvlName ); ?>
        </div>
        <hr class="startHidden e20r-admin-hr" />
        <div class="startHidden e20r-data-choices">
            <!-- Where the choices for the client data to fetch gets listed -->
            <form action="" method="post">
                <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_client_detail_nonce' ); ?>
                <table class="e20r-single-row-table">
                    <tbody>
                        <tr>
<!--                            <td><a href="#e20r_tracker_data" id="e20r-client-info" class="e20r-choice-button button" ><?php _e('Client Info', 'e20r-tracker'); ?></a></td>
                            <td><a href="#e20r_tracker_data" id="e20r-client-compliance" class="e20r-choice-button button" ><?php _e('Compliance', 'e20r-tracker'); ?></a></td>
                            <td><a href="#e20r_tracker_data" id="e20r-client-assignments" class="e20r-choice-button button" ><?php _e('Assignments', 'e20r-tracker'); ?></a></td>
-->                            <td><a href="#e20r_tracker_data" id="e20r-client-measurements" class="e20r-choice-button button" ><?php _e('Measurements', 'e20r-tracker'); ?></a></td>
                            <td><div id="load-client-data" class="seq_spinner"></div></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <hr class="e20r-admin-hr" />
        <div id="e20r-info">

            <?php echo $this->viewClientDetail( $client_id ); ?>
        </div>
        <div id="e20r-compliance">
            <?php echo $this->viewCompliance( $client_id, null ); ?>
        </div>
        <div id="e20r-assignments">
            <?php echo $this->viewAssignments( $client_id ); ?>
        </div>
        <div id="e20r-admin-measurements">

        </div>
    <?php
    }

    public function viewClientDetail( $clientId ) {
        // TODO: Display upcoming appointments, track attendance (and timeliness - multiple statuses)?
        // TODO: Pull data from appointments table and use Checkin tables for status(es)..?

        $billingInfo = $this->load_billing_data( $clientId );
        $program_list = new e20rPrograms();
        // $programData = $program_list->load_client_programs( $clientId );
        try {
            $appointments = $this->client->load_appointments();
        }
        catch ( Exception $e ) {
            dbg("Exception thrown: " . $e->getMessage() );
            return false;
        }

    }

    public function viewCompliance( $clientId = null, $shortname = null ) {

        if ( empty( $clientId ) ) {

            global $current_user;
            $clientId = $current_user->id;

            if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
                $level_id = pmpro_getMembershipLevelForUser( $clientId );
            }
            else {
                throw new Exception("Paid Memberships Pro is not installed!");
            }
        }

        if ( empty( $shortname ) ) {

            if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
                $level_id = pmpro_getMembershipLevelForUser( $clientId );
            }
            else {
                throw new Exception("Paid Memberships Pro is not installed!");
            }
        }

        // TODO: Implement compliance data loader.
//        $checkins = new e20rCheckin();
//        $items = $checkins->get_checkinItems( $shortname, $level_id );

        // TODO: show a graph for the users compliance.
    }


    private function viewAssignments( $clientId ) {

    }

    private function viewTableOfMeasurements( $clientId, $measurements = null, $dimensions = null, $tabbed = true ) {
        // TESTING: using $clientId = 12;

        // $clientId = 12;

        if ( $dimensions === null ) {

            $dimensions = array( 'width' => '650', 'height' => '500', 'type' => 'px' );
        }

        if ( $measurements === null ) {

            $mClass = new e20rMeasurements( $clientId );
            $mClass->init();

            $measurements = $mClass->getMeasurements();
            // $measurements = $this->load_measurements($clientId);
        }

        $user = get_user_by( 'id', $clientId );

        $reloadBtn = '
            <div id="e20r_reload_btn">
                <a href="#e20r_tracker_data" id="e20r-reload-measurements" class="e20r-choice-button button" > ' . __("Reload Measurements", "e20r-tracker") . '</a>
            </div>
        ';

        if ( count( $measurements ) < 1 ) {

            ob_start();
            // echo $reloadBtn;
            ?>
                <div id="e20r_errorMsg"><em>No measurements found for <?php echo $user->first_name . " " . $user->last_name; ?></em></div>
         <?php
            $html = ob_get_clean();
        }
        else {
            // dbg( "Tabbed measurements for $clientId: " . print_r( $measurements, true ) );

            ob_start();
            // echo $reloadBtn;

            ?>
            <!--[if IE]>
            <style type="text/css">
                .box { display: block; }
                #box { overflow: hidden;position: relative; }
                b { position: absolute; top: 0px; right: 0px; width:1px; height: 251px; overflow: hidden; text-indent: -9999px; }
            </style>
            <![endif]-->
            <style type="text/css">
                .tabs {
                    position: relative;
                    min-height: 200px;
                    max-height: <?php echo ( ((int) $dimensions['height']) + 95) . $dimensions['type']; ?>;
                    height: <?php echo ( ((int) $dimensions['height']) + 75) . $dimensions['type']; ?>;
                    min-width: <?php echo ( ((int) $dimensions['width']) + 95) . $dimensions['type']; ?>;
                    width: <?php echo ( ((int) $dimensions['height']) + 95) . $dimensions['type']; ?>;
                    clear: both;
                    margin: 25px 0;
                }
            </style>
            <?php if ( $tabbed ): ?>
                <div class="tabs">

                    <div class="tab">
                        <input type="radio" id="girth-tab" name="tab-group-1" checked>
                        <label for="girth-tab">Total Girth</label>

                        <div class="tab-content">
                            <div id="girth_chart" style="height: <?php echo $dimensions['height'] . $dimensions['type']; ?>;width: <?php echo $dimensions['width'] . $dimensions['type']; ?>;"></div>
                        </div>
                    </div>

                    <div class="tab">
                        <input type="radio" id="weight-tab" name="tab-group-1" checked>
                        <label for="weight-tab">Weight History</label>

                        <div class="tab-content">
                            <div id="weight_chart" style="height: <?php echo $dimensions['height'] . $dimensions['type']; ?>; width: <?php echo $dimensions['width'] . $dimensions['type']; ?>;"></div>
                        </div>
                    </div>
                </div> <!-- tabs div -->
            <?php else: ?>
                <div id="weight_chart" style="height: <?php echo $dimensions['height']. $dimensions['type']; ?>; width: <?php echo $dimensions['width']. $dimensions['type']; ?>;"></div>
                <div id="girth_chart" style="height: <?php echo $dimensions['height']. $dimensions['type']; ?>;width: <?php echo $dimensions['width']. $dimensions['type']; ?>;"></div>
            <?php endif; ?>

            <hr class="e20r-big-hr" />
            <h3>Measurements for <?php echo $user->first_name; ?></h3>
            <table id="e20r-measurement-table">
                <thead>
                <tr>
                    <th class="e20r_mHead">Date</th>
                    <th class="e20r_mHead">Weight</th>
                    <th class="e20r_mHead">Neck</th>
                    <th class="e20r_mHead">Shoulder</th>
                    <th class="e20r_mHead">Chest</th>
                    <th class="e20r_mHead">Arm</th>
                    <th class="e20r_mHead">Waist</th>
                    <th class="e20r_mHead">Hip</th>
                    <th class="e20r_mHead">Thigh</th>
                    <th class="e20r_mHead">Calf</th>
                    <th class="e20r_mHead">Total Girth</th>
                </tr>
                </thead>
                <tbody>
                <?php

                $counter = 0;

                foreach ( $measurements as $measurement ) {

                    ?>
                    <tr class="<?php echo( ( $counter % 2 == 0 ) ? "e20rEven" : "e20rOdd" ) ?>">
                        <td class="e20r_mData "><?php echo date_i18n( get_option( 'date_format' ), strtotime( $measurement->recorded_date ) ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->weight, 1 ), 1 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->neck, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->shoulder, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->chest, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->arm, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->waist, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->hip, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->thigh, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->calf, 2 ), 2 ); ?></td>
                        <td class="e20r_mData"><?php echo number_format( (float) round( $measurement->girth, 2 ), 2 ); ?></td>
                    </tr>
                    <?php

                    $counter ++;
                }

                ?>
                </tbody>
            </table>
            <?php

            $html = ob_get_clean();
        }
        return $html;

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


    public function generate_plot_data ( $data, $variable ) {

        $data_matrix = array();

        foreach ( $data as $measurement ) {

            if ( is_object( $measurement ) ) {

                switch ( $variable ) {
                    case 'weight':

                        $data_matrix[] = array( (strtotime( $measurement->recorded_date ) * 1000), number_format( (float) $measurement->weight, 2) );
                        break;

                    case 'girth':

                        $data_matrix[] = array( (strtotime( $measurement->recorded_date ) * 1000), number_format( (float) $measurement->girth, 2 ) );
                        break;
                }
            }
        }

        return $data_matrix;
    }

    public function load_measurements( $clientId = 0 ) {

        global $wpdb, $current_user;

        $measurements = array();

        $oldNC = array( 12, 20, 21, 27 ); // Members of the beta group...

        if ( $clientId == 0 ) {

            $clientId = $current_user->ID;
        }

        if ( in_array( $clientId, $oldNC ) ) {

            $sql = $wpdb->prepare("
                SELECT recordedDate AS recorded_date,
                     weight AS weight,
                     neckCM as neck,
                     shoulderCM as shoulder,
                     chestCM as chest,
                     armCM as arm,
                     waistCM as waist,
                     hipCM as hip,
                     thighCM as thigh,
                     calfCM as calf,
                     totalGrithCM as girth
                FROM {$this->old_tables->measurements}
                WHERE created_by = %d
                ORDER BY recorded_date ASC
            ",
                $clientId
            );
        }
        else {

            $sql = $wpdb->prepare("
                SELECT recorded_date AS recorded_date,
                     weight,
                     neck,
                     shoulder,
                     chest,
                     arm,
                     wait,
                     hip,
                     thigh,
                     calf,
                     girth
                FROM {$this->tables->measurements}
                WHERE client_id = %d
                ORDER BY recorded_date ASC
            ",
                $clientId
            );
        }

        return $wpdb->get_results( $sql, OBJECT );
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

        $programs = new e20rPrograms();

        echo $programs->viewProgramEditSelect();

    }

*/

    public function render_meals_page() {

    }


    // TODO: Return data that can be viewed both by Shortcode and by back-end. I.e. only fetch data for user specified in request.

    function ajax_getMemberlistForLevel() {

        check_ajax_referer('e20r-tracker-data', 'e20r_tracker_levels_nonce');

        $level = ( isset($_POST['hidden_e20r_level']) ? intval( $_POST['hidden_e20r_level']) : 0 );

        dbg("Level returned: {$level}");

        if ( $level != 0 ) {

            $levelObj = pmpro_getLevel( $level );
            // $this->load_levels( $levelObj->name );
            dbg(" Loading members for {$levelObj->name}");
            $data = $this->viewMemberSelect( $levelObj->name );
        }
        else {

            $this->load_levels();
            $data = $this->viewMemberSelect();
        }

        wp_send_json_success( $data );

    }

    function ajax_clientDetail() {
        dbg('Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");

        dbg("Request: " . print_r($_REQUEST, true));

    }

    function ajax_complianceData() {

        dbg('Requesting Check-In details');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");

        $checkins = new E20Rcheckin();

        // TODO: 10/02/2014 - Multiple steps: For different habits, get & generate different graphs.
        // NOTE: Special care for existing Nourish group... :(
        // Get the list of check-ins so far - SQL.
        // Calculate the max # of check-ins per check-in type (day/calendar based)
        //


    }

    function ajax_assignmentData() {
        dbg('Requesting Assignment details');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");
    }

    function ajax_measurementData() {

        dbg('Requesting measurement data');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");

        $clientId = isset( $_POST['hidden_e20r_client_id'] ) ? intval( $_POST['hidden_e20r_client_id'] ) : null;

        if ( $this->validateClientAccess( $clientId ) ) {
            $this->client_id = $clientId;
        }
         else {
             dbg( "Logged in user ID does not have access to the data for user ${clientId}" );
             wp_send_json_error( 'You do not have permission to access the data you requested.' );
         }

        // $measurements = $this->fetchMeasurements( $this->client_id );
        dbg("Loading measurement data");
        $dimensions = array( 'width' => '650', 'height' => '500', 'type' => 'px' );

        // $measurements = $this->load_measurements( $clientId );
        $mClass = new e20rMeasurements( $this->client_id );
        $mClass->init();

        $measurements = $mClass->getMeasurements();

        $data = $this->viewTableOfMeasurements( $this->client_id, $measurements, $dimensions );

        $weight = $this->generate_plot_data( $measurements, 'weight' );
        $girth = $this->generate_plot_data( $measurements, 'girth' );

        $data = json_encode( array( 'success' => true, 'data' => $data, 'weight' => $weight, 'girth' => $girth ), JSON_NUMERIC_CHECK );
        echo $data;
        exit;
    }

    private function validateClientAccess( $clientId ) {

        global $current_user;

        dbg("Client to validate: " . $clientId );

        if ( $clientId ) {

            dbg("Real user Id provided ");
            $client = get_user_by("id", $clientId );

            if ( ($current_user->ID != $clientId ) &&  ( $current_user->membership_level->id == 18 ) ) {
                return true;
            }
            elseif ( $current_user->ID == $clientId ) {
                return true;
            }
            // Make sure the $current_user has the right to view the data for $clientId

        }

        return false;
    }

    function test_stripe_api( $new_level_id = 0, $user_id = 0 ) {

        if(!class_exists("Stripe")) {

            dbg( "Loading supporting libraries for Stripe" );
            require_once( dirname( __FILE__ ) . "/../../plugins/paid-memberships-pro/includes/lib/Stripe/Stripe.php" );
        }

        global $wpdb;

        $user_id = 62;
        /*
        if ( $user_id == 0 ) {

            global $current_user;
            $user_id = $current_user->ID;
        }
        */

        $customer_id = "cus_4iXJe8n4SR4phk"; // Test user

        $nourish = pmpro_getLevel( NOURISH_LEVEL );

        $next_payment = ( ( $paydate = pmpro_next_payment( $user_id ) !== false ) ? $paydate : null );

        //if ( $next_payment ) {

        $last_day = new DateTime( date('Y-m-d', $next_payment ) );
        $today = new DateTime();

        $days_left = $today->diff( $last_day );
        dbg("Last Day: {$last_day->format('Y-m-d')}, Today: {$today->format('Y-m-d')} Days: {$days_left->format('%a')}" );

        if ( ! empty( $nourish ) ) {

            $new_plan = array(
                'amount' => round( ( $nourish->billing_amount * 100 ), 0 ),
                'trial_period_days' => $days_left->format( '%a' ),
                'interval' => 'month',
                'interval_count' => 1,
                'currency' => 'usd',
            );
        }
        else {
            dbg("Error: Unable to fetch valid Nourish level info");
            return false;
        }

        if ( class_exists( 'Stripe' ) ) {

            dbg( "Stripe class is loaded" );

            try {
                // Stripe::setApiKey( pmpro_getOption( "stripe_secretkey" ) );
                // Use test key & test user.
                Stripe::setApiKey( "sk_test_J57vfoBXUGCNnJWY6gwuVt8I" );
            }
            catch ( Exception $e ) {

                dbg( "Unable to set the API key: " . $e->getMessage() );
                return false;
            }

            $sql = $wpdb->prepare("
                SELECT
                  code AS stripe_plan,
                  membership_id AS membership_level,
                  subscription_transaction_id AS stripe_cust_id,
                  timestamp AS created
                FROM wp_pmpro_membership_orders
                WHERE ( user_id = %d ) AND ( status = 'success' ) AND ( gateway = 'stripe' )
                ORDER BY user_id ASC
                LIMIT 1
              ",
                $user_id
            );

            $result = $wpdb->get_row($sql, OBJECT);

            if ( ! empty ($result ) ) {

                $customer_id = $result->stripe_cust_id;

                if ( ! empty( $new_plan ) ) {

                    $new_plan['id'] = $result->stripe_plan;
                    $new_plan['name'] = "{$nourish->name} for order {$result->stripe_plan}";

                } // endif

                // Create new temporary plan
                try {

                    Stripe_Plan::create( $new_plan );
                    dbg( "New temporary S3F plan created: {$new_plan['name']}" );
                }
                catch ( Exception $e ) {

                    dbg( "Error creating new plan: " . $e->getMessage() );
                    return false;
                }

                try {

                    $cu = Stripe_Customer::retrieve($customer_id);
                    dbg("Fetched customer data from Stripe systems for " . $customer_id );

                }
                catch ( Exception $e ) {

                    dbg("Stripe is unable to locate customer data: " . $e->getMessage());
                    return false;
                }

                /*
                $card_id = $cu->default_card;

                $card = $cu->cards->retrieve($card_id);

                */
                try {

                    $subscriptions = $cu->subscriptions->all();
                    dbg("Fetched " . count( $subscriptions->data ) . " subscriptions for user");

                }
                catch ( Exception $e ) {

                    dbg("Stripe is unable to locate subscriptions: " . $e->getMessage());
                    return false;
                }

                if ( count( $subscriptions->data ) < 2 ) {
                    foreach ($subscriptions->data as $subscr) {

                        try {

                            $plan = $cu->subscriptions->retrieve($subscr->id);
                            dbg("Subscription detail returned");
                        }
                        catch ( Exception $e ) {

                            dbg( "Error fetching subscription detail: " . $e->getMessage() );
                            return false;
                        }

                        dbg( "Subscription ID: {$subscr->id}" );

                        try {

                            $subscr->plan = $new_plan['id'];
                            $subscr->prorate = false;

                            $new_subcr = $subscr->save();

                            /* TODO: Update info in pmpro_memberhip_orders and pmpro_memberships_users for the user we're downgrading */
                            dbg("Updated subscription for Nourish: " . print_r( $new_subcr, true ) );


                        }
                        catch ( Exception $e ) {
                            dbg(" Error saving new plan... - " . $e->getMessage() );
                            return false;
                        }

                    }

                } // End foreach

                // Delete temporary plan
                try {

                    $tmp = Stripe_Plan::retrieve( $new_plan['id'] );
                    $res = $tmp->delete();

                    if ( $res->deleted ) {

                        dbg("New temporary S3F plan deleted");
                    }
                    else {

                        dbg("Error: Unable to delete temporary plan!");
                    }
                }
                catch ( Exception $e ) {

                    dbg( "Error deleting plan: " . $e->getMessage() );
                    return false;
                }
            }
            else {
                dbg("No active customer plans in the local database for user with ID: {$user_id}");
            } // endif

        }
        else {
            dbg("Error: Stripe libraries not loaded!");
        }
    }
} 