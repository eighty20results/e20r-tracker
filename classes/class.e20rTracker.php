<?php

class e20rTracker {

    private $clientData;
    private $checkinData;
    private $exercises;
    private $programInfo;

    protected $settings = array();
    protected $setting_name = 'e20r-tracker';

    public $tables;

    public function __construct() {

        // Set defaults (in case there are none saved already
        $this->settings = array(
            'delete_tables' => false,
            'purge_tables' => true,
        );
    }

    public function init() {

        global $wpdb;

        $this->tables = new stdClass();

        /* The database tables used by this plugin */
        $this->tables->checkin_items = $wpdb->prefix . 'e20r_checkin_items';
        $this->tables->checkin_rules = $wpdb->prefix . 'e20r_checkin_rules';
        $this->tables->checkin = $wpdb->prefix . 'e20r_checkin';
        $this->tables->measurements = $wpdb->prefix . 'e20r_measurements';
        $this->tables->client_info = $wpdb->prefix . 'e20r_client_info';
        $this->tables->programs = $wpdb->prefix . 'e20r_programs';
        $this->tables->sets = $wpdb->prefix . 'e20r_sets';
        $this->tables->exercise = $wpdb->prefix . 'e20r_exercises';

        /* Load required classes used by the plugin */
        $this->clientData = new S3F_clientData();
        $this->checkinData = new E20Rcheckin();
        $this->exercises = new ExercisePrograms();
        $this->programInfo = new e20rPrograms();

        /* Load scripts & CSS */
        add_action( 'admin_enqueue_scripts', array( &$this, 'load_plotSW') );
        add_action( 'admin_enqueue_scripts', array( &$this, 'load_adminJS') );
        add_action( 'wp_enqueue_scripts', array( &$this, 'load_plotSW' ) );

        /* AJAX call-backs */
        add_action( 'wp_ajax_get_checkinItem', array( &$this->checkinData, 'ajax_getCheckin_item' ) );
        add_action( 'wp_ajax_e20r_clientDetail', array( &$this->clientData, 'ajax_clientDetail' ) );
        add_action( 'wp_ajax_e20r_complianceData', array( &$this->clientData, 'ajax_complianceData' ) );
        add_action( 'wp_ajax_e20r_assignmentsData', array( &$this->clientData, 'ajax_assignmentsData' ) );
        add_action( 'wp_ajax_e20r_measurementsData', array( &$this->clientData, 'ajax_measurementsData' ) );
        add_action( 'wp_ajax_get_memberlistForLevel', array( &$this->clientData, 'ajax_getMemberlistForLevel' ) );

        add_action( 'wp_ajax_save_program_info', array( &$this->programInfo, 'ajax_save_program_info' ) );

        /* AJAX call-backs if user is unprivileged */
        add_action( 'wp_ajax_nopriv_e20r_clientDetail', array( &$this->clientData, 'ajaxUnprivError' ) );
        add_action( 'wp_ajax_nopriv_e20r_complianceData', array( &$this->clientData, 'ajaxUnprivError' ) );
        add_action( 'wp_ajax_nopriv_e20r_assignmentsData', array( &$this->clientData, 'ajaxUnprivError' ) );
        add_action( 'wp_ajax_nopriv_e20r_measurementsData', array( &$this->clientData, 'ajaxUnprivError' ) );

        /* Load various back-end pages/settings */
        add_action( 'admin_menu', array( &$this, 'loadAdminPage') );
        add_action( 'admin_init', array( &$this, 'registerSettingsPage' ) );

    }

    public function validate( $input ) {

        $valid = array();

        dbg('Running validation: ' . print_r( $input, true ) );

        foreach ( $input as $key => $value ) {

            $valid[$key] = apply_filters( 'e20r_settings_validation_' . $key, $value );

        }

        /*
         * Use add_settings_error( $title (title of setting), $errorId (text identifier), $message (Error message), 'error' (type of message) ); if needed
         */

        unset( $input ); // Free.

        return $valid;
    }

    public function validate_bool( $value ) {

        return ( 1 == intval( $value ) ? 1 : 0);
    }

    /**
     * @return mixed -- stdClass() list of tables used by the tracker.
     */
    function get_tables() {

        return $this->tables;
    }

    function loadAdminPage() {

        add_options_page( 'Eighty / 20 Tracker', 'E20R Tracker', 'manage_options', 'e20r_tracker_opt_page', array( $this, 'render_settings_page' ) );

        // $this->registerAdminPages();

    }

    public function registerSettingsPage() {

        // Register any global settings for the Plugin
        register_setting( 'e20r_options', $this->setting_name, array( $this, 'validate' ) );

        add_settings_section( 'e20r_tracker_deactivate', 'Deactivation settings', array( &$this, 'render_section_text' ), 'e20r_tracker_opt_page' );

        /* Add fields for the settings */
        add_settings_field( 'e20r_tracker_purge_tables', __("Clear tables", 'e20r_tracker'), array( $this, 'render_purge_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');
        add_settings_field( 'e20r_tracker_delete_tables', __("Delete tables", 'e20r_tracker'), array( $this, 'render_delete_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');

        // $this->render_settings_page();

    }

    public function render_delete_checkbox() {

        $options = get_option( $this->setting_name );
        ?>
        <input type="checkbox" name="<?php echo $this->setting_name; ?>[delete_tables]" value="1" <?php checked( 1, $options['delete_tables'] ) ?> >
        <?php
    }

    public function render_purge_checkbox() {


        $options = get_option( $this->setting_name );
        ?>
        <input type="checkbox" name="<?php echo $this->setting_name; ?>[purge_tables]" value="1" <?php checked( 1, $options['purge_tables'] ) ?> >
    <?php
    }

    public function render_section_text() {

        $html = "<p>These settings will determine the behavior of the plugin during deactivation.</p>";

        echo $html;
    }

    public function render_settings_page() {

        ob_start();
        ?>
        <div id="e20r-settings">
            <div class="wrap">
                <h2>Settings: Eighty / 20 Tracker</h2>
                <form method="post" action="options.php">
                    <?php settings_fields( 'e20r_options' ); ?>
                    <?php do_settings_sections( 'e20r_tracker_opt_page' ); ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
                    </p>
                </form>
            </div>
        </div>
    <?php
        $html = ob_get_clean();

        echo $html;
    }

    public function registerAdminPages() {

        $page = add_menu_page( 'S3F Clients', __('S3F Clients','e20r_tracker'), 'manage_options', 'e20r-tracker', array( &$this->clientData, 'render_client_page' ), 'dashicons-admin-generic', '71.1' );
//        add_submenu_page( 'e20r-tracker', __('Measurements','e20r_tracker'), __('Measurements','e20r_tracker'), 'manage-options', "e20r_tracker_measure", array( &$this,'render_measurement_page' ));

//      add_submenu_page( 'e20r-tracker', __('Manage Program','e20r_tracker'), __('Add Program','e20r_tracker'), 'manage_options', "e20r-add-new-program", array( &$this,'render_new_program_page'));
        add_submenu_page( 'e20r-tracker', __('Programs','e20r_tracker'), __('Programs','e20r_tracker'), 'manage_options', "e20r-tracker-list-programs", array( &$this->programInfo, 'render_submenu_page'));

//        add_submenu_page( 'e20r-tracker', __('Manage Item','e20r_tracker'), __('Add Item','e20r_tracker'), 'manage_options', "e20-add-new-item", array( &$this,'render_new_item_page'));
        add_submenu_page( 'e20r-tracker', __('Items','e20r_tracker'), __('Items','e20r_tracker'), 'manage_options', "e20r-tracker-list-items", array( &$this->checkinData, 'render_submenu_page'));
        add_submenu_page( 'e20r-tracker', __('Settings','e20r_tracker'), __('Settings','e20r_tracker'), 'manage_options', "e20r-tracker-settings", array( &$this, 'registerSettingsPage'));

        //add_submenu_page( 'e20r-tracker', __('Check-in Items','e20r_tracker'), __('Items','e20r_tracker'), 'manage-options', 'e20r-items', array( &$this, 'render_management_page' ) );

//        add_submenu_page( 'e20r-tracker', __('Meals','e20r_tracker'), __('Meal History','e20r_tracker'), 'manage_options', "e20r_tracker_meals", array( &$this,'render_meals_page'));

        // add_action( "admin_print_scripts-$page", array( 'e20rTracker', 'load_adminJS') ); // Load datepicker, etc (see apppontments+)
    }

    /**
     * Load all JS for Admin page
     */
    public function load_adminJS()
    {
        wp_register_script('e20r_tracker_admin', E20R_PLUGINS_URL . '/js/e20r-tracker-admin.js', array('jquery'), '0.1', true);

        /* Localize ajax script */
        wp_localize_script('e20r_tracker_admin', 'e20r_tracker',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'lang' => array(
                    'save' => __('Update', 'e20r-tracker'),
                    'saving' => __('Saving', 'e20r-tracker'),
                    'saveSettings' => __('Update Settings', 'e20r-tracker'),
                    'delay_change_confirmation' => __('Changing the delay type will erase all existing posts or pages in the Sequence list. (Cancel if your are unsure)', 'e20r-tracker'),
                    'saving_error_1' => __('Error saving sequence post [1]', 'e20r-tracker'),
                    'saving_error_2' => __('Error saving sequence post [2]', 'e20r-tracker'),
                    'remove_error_1' => __('Error deleting sequence post [1]', 'e20r-tracker'),
                    'remove_error_2' => __('Error deleting sequence post [2]', 'e20r-tracker'),
                    'undefined' => __('Not Defined', 'e20rtracker'),
                    'unknownerrorrm' => __('Unknown error removing post from sequence', 'e20r-tracker'),
                    'unknownerroradd' => __('Unknown error adding post to sequence', 'e20r-tracker'),
                    'daysLabel' => __('Delay', 'e20r-tracker'),
                    'daysText' => __('Days to delay', 'e20r-tracker'),
                    'dateLabel' => __('Avail. on', 'e20r-tracker'),
                    'dateText' => __('Release on (YYYY-MM-DD)', 'e20r-tracker'),
                )
            )
        );

        wp_enqueue_style("e20r_tracker_css", E20R_PLUGINS_URL . '/css/e20r-tracker.css' );
        wp_enqueue_script('e20r_tracker_admin');

    }

    public function load_JSscript() {

        wp_register_script('e20r_tracker_js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array('jquery'), '0.1', true);

        wp_localize_script('e20r_tracker_js', 'e20r_tracker',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
            )
        );

        wp_enqueue_script('e20r_tracker_js');
    }
    /* Load graphing scripts */
    public function load_plotSW() {

        wp_deregister_script('jqplot' );
        wp_enqueue_script( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.js', false, '0.1' );

        wp_deregister_script('jqplot_export' );
        wp_enqueue_script( 'jqplot_export', E20R_PLUGINS_URL . '/js/jQPlot/plugins/export/exportImg.min.js', false, '0.1' );

        wp_deregister_script('jqplot_pie' );
        wp_enqueue_script( 'jqplot_pie', E20R_PLUGINS_URL . '/js/jQPlot/plugins/pie/jqplot.pieRenderer.min.js', false, '0.1' );

        wp_deregister_script('jqplot_text' );
        wp_enqueue_script( 'jqplot_text', E20R_PLUGINS_URL . '/js/jQPlot/plugins/text/jqplot.canvasTextRenderer.min.js', false, '0.1' );

        wp_deregister_script('jqplot_mobile' );
        wp_enqueue_script( 'jqplot_mobile', E20R_PLUGINS_URL . '/js/jQPlot/plugins/mobile/jqplot.mobile.min.js', false, '0.1' );

        wp_deregister_script( 'jqplot_date');
        wp_enqueue_script( 'jqplot_date', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.dateAxisRenderer.min.js', false, '0.1');

        wp_deregister_script( 'jqplot_label');
        wp_enqueue_script( 'jqplot_label', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisLabelRenderer.min.js', false, '0.1');

        wp_deregister_script( 'jqplot_pntlabel');
        wp_enqueue_script( 'jqplot_pntlabel', E20R_PLUGINS_URL . '/js/jQPlot/plugins/points/jqplot.pointLabels.min.js', false, '0.1');

        wp_deregister_script( 'jqplot_ticks');
        wp_enqueue_script( 'jqplot_ticks', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisTickRenderer.min.js', false, '0.1');

        wp_deregister_style( 'jqplot' );
        wp_enqueue_style( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.css', false, '0.1' );

    }

    public function e20r_tracker_activate() {

        global $wpdb;
        global $e20r_db_version;

        // Create settings with default values
        update_option( $this->setting_name, $this->settings );

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        dbg("e20r_tracker_activate() - Loading table SQL");


        $programsTableSql = "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_programs (
                    id int not null auto_increment,
                    program_name varchar(255) null,
                    description mediumtext null,
                    starttime timestamp not null default current_timestamp,
                    endtime timestamp null,
                    member_id int null,
                    primary key (id) )
                  {$charset_collate}
        ";

        $setsTableSql = "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_sets (
                id int not null auto_increment,
                set_name varchar(50) null,
                rounds int not null default 1,
                set_rest int not null default 60,
                program_id int not null default 0,
                exercise_id int not null default 0,
                primary key (id),
                key exercises ( exercise_id asc ),
                key programs ( program_id asc ) )
                {$charset_collate}
        ";

        $exercisesTableSql = "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_exercises (
                id int not null auto_increment,
                exercise_name varchar(100) not null default '',
                description mediumtext null,
                repetitions int not null default 10,
                duration int null,
                ex_rest int not null default 30,
                primary key (id) )
                {$charset_collate}
        ";

        $intakeTableSql =
            "CREATE TABLE If NOT EXISTS {$wpdb->prefix}e20r_client_info (
                    id int not null auto_increment,
                    user_id int not null,
                    user_dob date not null,
                    height decimal(18, 3) null,
                    heritage int null,
                    waist_circumference decimal(18,3),
                    weight decimal(18,3) null,
                    is_metric tinyint default 0,
                    is_imperial tinyint default 0,
                    is_gb_imperial tinyint default 0,
                    use_pictures tinyint default 0,
                    for_research tinyint default 0,
                    chronic_pain tinyint default 0,
                    injuries tinyint default 0,
                    primary key (id),
                    key user_id (user_id asc) )
                  {$charset_collate}
                ";

        $measurementTableSql =
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_measurements (
                    id int not null auto_increment,
                    user_id int not null,
                    recorded_date datetime null,
                    weight decimal(18,3) null,
                    neck decimal(18,3) null,
                    shoulder decimal(18,3) null,
                    chest decimal(18,3) null,
                    arm decimal(18,3) null,
                    waist decimal(18,3) null,
                    hip decimal(18,3) null,
                    thigh decimal(18,3) null,
                    calf decimal(18,3) null,
                    girth decimal(18,3) null,
                    primary key  ( id ),
                    key user_id ( user_id asc) )
                  {$charset_collate}
              ";
        // TODO: Add item_text on admin page.
        $itemsTableSql =
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_checkin_items (
                    id int not null auto_increment,
                    short_name varchar(20) null,
                    program_id int null,
                    item_name varchar(50) null,
                    item_text mediumtext null,
                    startdate datetime null,
                    enddate datetime null,
                    item_order int not null default 1,
                    maxcount int null,
                    membership_level_id int not null default 0,
                primary key  ( id ) ,
                unique key shortname_UNIQUE ( short_name asc ) )
                {$charset_collate}";

        $businessRulesSql =
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_checkin_rules (
                    id int not null auto_increment,
                    checkin_id int null,
                    success_rule mediumtext null,
                    primary key  ( id ),
                    key checkin_id ( checkin_id asc ) )
                {$charset_collate}";

        // TODO: How do you combine Assignments (flexibility, unlimited # of boxes & questions) and
        $checkinSql =
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}e20r_checkin (
                    id int not null auto_increment,
                    user_id int null,
                    checkin_date datetime null,
                    checkin_item_id int null,
                    checkedin tinyint not null default 0,
                    answer_id int null,
                    primary key  (id),
                      key checkin_item_id ( checkin_item_id asc ),
                      key answer_id ( answer_id asc ) )
                {$charset_collate}";

        require_once( ABSPATH . "wp-admin/includes/upgrade.php" );

        dbg('e20r_tracker_activate() - Creating tables in database');
        dbDelta( $itemsTableSql );
        dbDelta( $businessRulesSql );
        dbDelta( $checkinSql );
        dbDelta( $measurementTableSql );
        dbDelta( $intakeTableSql );
        dbDelta( $programsTableSql );
        dbDelta( $setsTableSql );
        dbDelta( $exercisesTableSql );

        add_option( 'e20rTracker_db_version', $e20r_db_version );

        flush_rewrite_rules();
    }

    public function e20r_tracker_deactivate() {

        global $wpdb;
        global $e20r_db_version;

        $options = get_option( $this->setting_name );

        dbg("Deactivation options: " . print_r( $options, true ) );

        $tables = array(
            $wpdb->prefix . 'e20r_checkin_items',
            $wpdb->prefix . 'e20r_checkin_rules',
            $wpdb->prefix . 'e20r_checkin',
            $wpdb->prefix . 'e20r_measurements',
            $wpdb->prefix . 'e20r_client_info',
            $wpdb->prefix . 'e20r_programs',
            $wpdb->prefix . 'e20r_sets',
            $wpdb->prefix . 'e20r_exercises',
        );


        foreach ( $tables as $tblName ) {

            if ( $options['purge_tables'] == 1 ) {

                dbg("e20r_tracker_deactivate() - Truncating {$tblName}" );
                $sql = "TRUNCATE TABLE {$tblName}";
                $wpdb->query( $sql );
            }

            if ( $options['delete_tables'] == 1 ) {

                dbg( "e20r_tracker_deactivate() - {$tblName} being dropped" );

                $sql = "DROP TABLE IF EXISTS {$tblName}";
                $wpdb->query( $sql );
            }

        }

        // Remove existing options
        delete_option( $this->setting_name );
    }
}