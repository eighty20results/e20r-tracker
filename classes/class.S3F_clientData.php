<?php

class S3F_clientData {

    public $tables;
    private $nourish_level = array(); // Empty array

    function __construct() {

        dbg("Constructor for S3F_clientData");

        global $wpdb;

        $this->load_levels();

        $this->tables = new stdClass();

        $this->tables->Assignments = "{$wpdb->prefix}s3f_nourishAssignments";
        $this->tables->Habits = "{$wpdb->prefix}s3f_nourishHabits";
        $this->tables->Surveys = "{$wpdb->prefix}s3f_Surveys";
        $this->tables->Measuremetns = "{$wpdb->prefix}nourish_measurements";
        $this->tables->Meals = "{$wpdb->prefix}wp_s3f_nourishMeals";

    }

    private function load_levels( $name = null ) {

        global $wpdb;

        if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
            $this->raise_pmpro_error();
        }

        $allLevels = pmpro_getAllLevels( true );

        if ( ! empty( $name ) ) {
            $pattern = "/{$name}/i";
        }

        foreach( $allLevels as $level ) {

            if ( preg_match($pattern, $level->name ) == 1 ) {
                $this->nourish_level[] = $level->id;
            }
            elseif ( empty( $name ) ) {
                $this->nourish_level[] = $level->id;
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

    public function get_nourishLevels() {

        return $this->nourish_level;
    }

    public function set_nourishLevel( $level ) {

        $this->nourish_level[] = $level; // Add a level
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

    private function createMemberSelect( ) {

        global $wpdb;

        if ( ! defined( 'PMPRO_VERSION' ) ) {
            // Display error message.
            $this->raise_error( 'pmpro' );
        }

        $levels = $this->get_nourishLevels();

        ob_start(); ?>
        <div class="e20r_selectMember">
            <div class="e20r-client-list">
                <div class="seq_spinner"></div>
                <form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                    <?php wp_nonce_field( 'e20r-tracker-data', 'e20r-tracker-levels-nonce' ); ?>
                    <div class="e20r-client-select">
                        <label for="e20r_tracker_client">Select client to view data for:</label>
                        <select name="e20r_tracker_client" id="e20r_tracker_client">
                        <?php

                        $sql = "
                                SELECT m.user_id AS id, u.display_name AS name
                                FROM $wpdb->users AS u
                                  INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                                    ON ( u.ID = m.user_id )
                                WHERE ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
                        ";

                        // $sql = $wpdb->prepare( $sql );
                        $sql = $this->prepare_in( $sql, $levels );

                        $user_list = $wpdb->get_results( $sql, OBJECT );

                        foreach ( $user_list as $user ) {

                            ?><option value="<?php echo esc_attr( $user->ID ); ?>"  ><?php echo esc_attr($user->name); ?></option><?php
                        } ?>
                        </select>
                        <input type="hidden" name="hidden_e20r_tracker_user" id="hidden_e20r_tracker_user" value="0" >
                    </div>
                </form>
            </div>
        </div>
        <?php

        $html = ob_get_clean();

        return $html;
    }

    /* TODO: Create the client pages for the menu */
    public function client_page() {

        ?>
        <H1>Client Data</H1>
        <div class="e20r-client-select">
            <?php echo $this->createMemberSelect(); ?>
        </div>
        <hr class="e20r-admin-hr" />
        <div class="e20r-data-choices">
            <!-- Where the choices for the client data to fetch gets listed -->
            <table style="e20r-single-row-table">
                <tbody>
                    <tr>
                        <td><a href="#e20r_tracker_client" id="e20r-client-billing" class="e20r-choice-button button"><?php _e('Billing Info', 'e20r-tracker'); ?></a></td>
                        <td><a href="#e20r_tracker_client" id="e20r-client-compliance" class="e20r-choice-button button"><?php _e('Compliance', 'e20r-tracker'); ?></a></td>
                        <td><a href="#e20r_tracker_client" id="e20r-client-assignments" class="e20r-choice-button button"><?php _e('Assignments', 'e20r-tracker'); ?></a></td>
                        <td><a href="#e20r_tracker_client" id="e20r-client-measurements" class="e20r-choice-button button"><?php _e('Measurements', 'e20r-tracker'); ?></a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <hr class="e20r-admin-hr" />
<?php
    }

    public function load_billing_data( $client_id = 0 ) {

        if ( $client_id == 0 ) {

            global $current_user;

            $client_id = $current_user->ID;
        }

    }

    public function assignments_page() {

    }

    public function measurements_page() {

    }

    public function compliance_page() {

    }

    public function meals_page() {

    }

    /**
     *  Add the datepicker scripts for the admin pages (TODO: Figure out how to use the jQuery datepicker in Wordpress
     */
    public function load_admin_scripts() {

    }

    public function registerAdminPages() {

        dbg("Running Init for wp-admin");

        $page = add_menu_page( 'S3F Clients', __('S3F Clients','e20r_tracker'), 'manage_options', 'e20r_tracker', array( &$this, 'client_page' ), 'dashicons-admin-generic', '71.1' );
        add_submenu_page( 'e20r_tracker', __('Assignments','e20r_tracker'), __('Assignments','e20r_tracker'), 'manage-options', "e20r_tracker_assign", array( &$this,'assignment_page' ));
        add_submenu_page( 'e20r_tracker', __('Measurements','e20r_tracker'), __('Measurements','e20r_tracker'), 'manage-options', "e20r_tracker_measure", array( &$this,'measurement_page' ));
        add_submenu_page( 'e20r_tracker', __('Compliance','e20r_tracker'), __('Compliance','e20r_tracker'), 'manage_options', "e20r_tracker_habit", array( &$this,'compliance_page'));
        add_submenu_page( 'e20r_tracker', __('Meals','e20r_tracker'), __('Meal History','e20r_tracker'), 'manage_options', "e20r_tracker_meals", array( &$this,'meals_page'));

        add_action( "admin_print_scripts-$page", array( &$this, 'load_admin_scripts') ); // Load datepicker, etc (see apppontments+)
    }
} 