<?php

class S3F_clientData {

    public $tables;
    private $levels = array(); // Empty array
    protected $client_id;

    function __construct() {

        dbg("Constructor for S3F_clientData");

        global $wpdb;

       $this->tables = new stdClass();


        $this->tables->Assignments = "{$wpdb->prefix}s3f_nourishAssignments";
        $this->tables->Habits = "{$wpdb->prefix}s3f_nourishHabits";
        $this->tables->Surveys = "{$wpdb->prefix}e20r_Surveys";
        $this->tables->Measurements = "{$wpdb->prefix}nourish_measurements";
        $this->tables->Meals = "{$wpdb->prefix}wp_s3f_nourishMeals";

    }

    public function init() {

        $this->load_levels();
    }
    private function load_levels( $name = null ) {

        global $wpdb;

        if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
            $this->raise_error( 'pmpro' );
        } else {

            dbg("Loading levels from PMPro");

            $allLevels = pmpro_getAllLevels( true );

            if ( ! empty( $name ) ) {

                $name = str_replace( '+', '\+', $name);
                $pattern = "/{$name}/i";
                dbg("Pattern: {$pattern}");
            }

            foreach( $allLevels as $level ) {

                if ( preg_match($pattern, $level->name ) == 1 ) {
                    $this->levels[] = $level->id;
                }
                elseif ( empty( $name ) ) {
                    $this->levels[] = $level->id;
                }
            }
        }
    }

    private function raise_error( $type ) {

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

    private function prepare_in( $sql, $values ) {

        global $wpdb;

        $not_in_count = substr_count( $sql, '[IN]' );

        if ( $not_in_count > 0 ) {

            $args = array( str_replace( '[IN]',
                        implode( ', ', array_fill( 0, count( $values ), '%d' ) ),
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

    public function fetch_levelList( $onlyVisible = false) {

        $allLevels = pmpro_getAllLevels();
        $levels = array();

        foreach ($allLevels as $level ) {

            $visible = ( $level->allow_signups == 1 ? true : false );

            if ( ( ! $onlyVisible) || ( $visible && $onlyVisible )) {
                $levels[ $level->id ] = $level->name;
            }
        }
        // dbg("Levels fetched: " . print_r( $levels, true ) );
        return $levels;
    }

    public function fetch_userList( $level = '' ) {

        global $wpdb;

        if ( ! empty($level) ) {
            $this->load_levels( $level );
        }

        $levels = $this->get_level_ids();

        //dbg("Levels being loaded: " . print_r( $levels, true ) );

        $sql = "
                SELECT m.user_id AS id, u.display_name AS name
                FROM $wpdb->users AS u
                  INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                    ON ( u.ID = m.user_id )
                WHERE ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
        ";

        $sql = $this->prepare_in( $sql, $levels );

        ///dbg("SQL for user list: " . print_r( $sql, true));

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
            <?php echo $this->viewCompliance( $client_id ); ?>
        </div>
        <div id="e20r-assignments">
            <?php echo $this->viewAssignments( $client_id ); ?>
        </div>
        <div id="e20r-measurements">
            <?php //echo $this->viewTaMeasurements( $client_id ); ?>
        </div>
    <?php
    }

    public function viewClientDetail( $clientId ) {
        // TODO: Display upcoming appointments, track attendance (and timeliness - multiple statuses)?
        // TODO: Pull data from appointments table and use Checkin tables for status(es)..?

        $billingInfo = $this->load_billing_data( $clientId );
        $appointments = $this->load_client_appointments( $clientId );

    }

    private function viewCompliance( $clientId ) {

    }

    private function viewAssignments( $clientId ) {

    }

    private function viewTableOfMeasurements( $clientId ) {
        // TESTING: using $clientId = 12;

        // $clientId = 12;

        $measurements = $this->load_measurements( $clientId );
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
            dbg( "Measurements for $clientId: " . print_r( $measurements, true ) );

            ob_start();
            // echo $reloadBtn;

            ?>
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

        // TODO: Build array (of stdClass objects?) containing the billing info for this client
        if ( $client_id == 0 ) {

            global $current_user;

            $client_id = $current_user->ID;
        }

    }

    public function load_client_appointments( $clientId ) {

        // TODO: Build an array (of stdClass objects?) containing the booked appointments for this client (all "confirmed" ones?)

        if ( $clientId == 0 ) {

            global $current_user;

            $client_id = $current_user->ID;
        }

        global $wpdb;

        // TODO: Complete this to get a list of appointments for the clientId so we can set the status for it in the checkin table(s).

        $sql = $wpdb->prepare(
            "
                SELECT ID, user, start, status, created
                FROM $wpdb->app_table AS app
                INNER JOIN ( app.user = )
                 WHERE user = %d AND status NOT IN ( )

            ",
            $client_id
        );

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
                FROM {$this->tables->Measurements}
                WHERE created_by = %d
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
                FROM {$wpdb->prefix}e20r_measurements
                WHERE client_id = %d
            ",
                $clientId
            );
        }

        $results = $wpdb->get_results( $sql );

        foreach ( $results as $measurement ) {

            $measurements[] = $measurement;
        }

        return $measurements;
    }

    public function render_assignments_page() {

    }

    public function render_measurements_page() {

    }

    public function render_compliance_page() {

    }

    public function render_meals_page() {

    }

    public function registerAdminPages() {

        dbg("Loading Client data page for wp-admin");

        $page = add_menu_page( 'S3F Clients', __('S3F Clients','e20r_tracker'), 'manage_options', 'e20r_tracker', array( &$this, 'render_client_page' ), 'dashicons-admin-generic', '71.1' );
        add_submenu_page( 'e20r_tracker', __('Assignments','e20r_tracker'), __('Assignments','e20r_tracker'), 'manage-options', "e20r_tracker_assign", array( &$this,'render_assignment_page' ));
        add_submenu_page( 'e20r_tracker', __('Measurements','e20r_tracker'), __('Measurements','e20r_tracker'), 'manage-options', "e20r_tracker_measure", array( &$this,'render_measurement_page' ));
        add_submenu_page( 'e20r_tracker', __('Compliance','e20r_tracker'), __('Compliance','e20r_tracker'), 'manage_options', "e20r_tracker_habit", array( &$this,'render_compliance_page'));
        add_submenu_page( 'e20r_tracker', __('Meals','e20r_tracker'), __('Meal History','e20r_tracker'), 'manage_options', "e20r_tracker_meals", array( &$this,'render_meals_page'));

        // add_action( "admin_print_scripts-$page", array( 'e20rTracker', 'load_adminJS') ); // Load datepicker, etc (see apppontments+)
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
    }

    function ajax_assignmentsData() {
        dbg('Requesting Assignment details');

        check_ajax_referer('e20r-tracker-data', 'e20r_client_detail_nonce');

        dbg("Nonce is OK");
    }

    function ajax_measurementsData() {

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
        dbg("Loading table of measurements, sort of.");
        $data = $this->viewTableOfMeasurements( $this->client_id );

        wp_send_json_success( $data );
    }

    /**
     * Functions returns error message. Used by nopriv Ajax traps.
     */
    function ajaxUnprivError() {

        dbg('Unprivileged ajax call attempted');

        wp_send_json_error( array(
            'message' => __('You must be logged in to access/view tracker data', 'e20r_tracker')
        ));

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
} 