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

        // BAD! and incorrect.
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
        $program_list = new e20rProgram();
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

    private function viewTableOfMeasurements( $clientId, $measurements, $dimensions = null, $tabbed = true ) {
        // TESTING: using $clientId = 12;

        // $clientId = 12;

        if ( $dimensions === null ) {

            $dimensions = array( 'width' => '650', 'height' => '500', 'type' => 'px' );
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

                    $girth = ($measurement->neck + $measurement->shoulder + $measurement->chest + $measurement->arm + $measurement->waist + $measurement->hip + $measurement->thigh + $measurement->calf );
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
                        <td class="e20r_mData"><?php echo number_format( (float) round( $girth, 2 ), 2 ); ?></td>
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