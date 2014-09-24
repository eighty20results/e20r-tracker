<?php

class e20rTracker {

    private $clientData;
    protected $tables;

    public function init() {

        global $wpdb;

        $this->tables = new stdClass();

        $this->tables->checkin_items = $wpdb->prefix . 'e20r_checkin_items';
        $this->tables->checkin_rules = $wpdb->prefix . 'e20r_checkin_rules';
        $this->tables->checkin = $wpdb->prefix . 'e20r_checkin';
        $this->tables->measurements = $wpdb->prefix . 'e20r_measurements';
        $this->tables->client_info = $wpdb->prefix . 'e20r_client_info';

        dbg("Running e20r-Tracker init()");

        dbg("Loading S3F clientData class");
        $this->clientData = new S3F_clientData();

        dbg("Register activation/deactiviation hooks");
        register_activation_hook( E20R_PLUGIN_DIR, array( &$this, 'activateE20R_Plugin' ) );
        register_deactivation_hook( E20R_PLUGIN_DIR, array( &$this, 'deactivateE20R_Plugin' ) );

        dbg("Added action to load Admin Page");
        add_action( 'admin_menu', array( &$this, 'loadAdminPage') );

        dbg("Queue scrips for admin pages");

        add_action( 'admin_enqueue_scripts', array( &$this, 'load_plotSW') );
        add_action( 'admin_enqueue_scripts', array( &$this, 'load_adminJS') );

        dbg("Queue scripts for user pages");
        add_action( 'wp_enqueue_scripts', array( &$this, 'load_plotSW' ) );

        dbg("Add actions for privileged ajax handling");
        add_action( 'wp_ajax_e20r_clientDetail', array( &$this->clientData, 'ajax_clientDetail' ) );
        add_action( 'wp_ajax_e20r_complianceData', array( &$this->clientData, 'ajax_complianceData' ) );
        add_action( 'wp_ajax_e20r_assignmentsData', array( &$this->clientData, 'ajax_assignmentsData' ) );
        add_action( 'wp_ajax_e20r_measurementsData', array( &$this->clientData, 'ajax_measurementsData' ) );
        add_action( 'wp_ajax_get_memberlistForLevel', array( &$this->clientData, 'ajax_getMemberlistForLevel' ) );

        dbg("Add actions for unprivileged ajax handling");
        add_action( 'wp_ajax_nopriv_e20r_clientDetail', array( &$this->clientData, 'ajaxUnprivError' ) );
        add_action( 'wp_ajax_nopriv_e20r_complianceData', array( &$this->clientData, 'ajaxUnprivError' ) );
        add_action( 'wp_ajax_nopriv_e20r_assignmentsData', array( &$this->clientData, 'ajaxUnprivError' ) );
        add_action( 'wp_ajax_nopriv_e20r_measurementsData', array( &$this->clientData, 'ajaxUnprivError' ) );

        // $this->clientData = new S3F_clientData();

    }

    /**
     * @return mixed -- stdClass() list of tables used by the tracker.
     */
    function get_tables() {

        return $this->tables;
    }

    function loadAdminPage() {

        if (! empty($this->clientData ) ) {

            dbg("loadAdminPage() for client data - Starting...");
            // Init the S3F Client data class
            $this->clientData->init();

            dbg("Loading admin pages for client data");
            $this->clientData->registerAdminPages();
        }
    }

    /**
     * Load all JS for Admin page
     */
    public function load_adminJS()
    {
        dbg("Loading scripts and stuff for admin pages");

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

    /* Load graphing scripts */
    public function load_plotSW() {

        wp_deregister_script('jqplot' );
        wp_enqueue_script( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.js', false, '0.1' );

        wp_deregister_style( 'jqplot' );
        wp_enqueue_style( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.css', false, '0.1' );

    }

    public function activateE20R_Plugin() {

        global $wpdb;
        global $e20r_db_version;

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }


        $intakeTableSql =
            "CREATE TABLE If NOT EXISTS {$this->tables->client_info} (
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
                primary_key (id),
                key user_id (user_id asc) )
              {$charset_collate}
            ";

        $measurementTableSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->measurements} (
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
                primary key  (id),
                key user_id ( user_id asc) )
              {$charset_collate}
          ";

        $itemsTableSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->checkin_items} (
                id int not null auto_increment,
                short_name varchar(20) null,
                program_id int null,
                item_name varchar(50) null,
                startdate datetime null,
                enddate datetime null,
                item_order int not null default 1,
                maxcount int null,
                membership_level_id int not null default 0,
            primary key  (id) ,
            unique key shortname_UNIQUE (short_name asc) )
            {$charset_collate}";

        $businessRulesSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->checkin_rules} (
                id int not null auto_increment,
                checkin_id int null,
                success_rule mediumtext null,
                primary key  (id),
                key checkin_id (checkin_id asc) )
            {$charset_collate}";

        $checkinSql =
            "CREATE TABLE IF NOT EXISTS {$this->tables->checkin} (
                id int not null auto_increment,
                user_id int null,
                checkin_date datetime null,
                checkin_id int null,
                program_id int null, -- Uses the membership_level->ID value (unless it's nourish)
                primary key  (id) )
            {$charset_collate}";

        require_once( ABSPATH . "wp-admin/includes/upgrade.php" );

        dbg('dbInstall() - Creating tables in database');
        dbDelta( $itemsTableSql );
        dbDelta( $businessRulesSql );
        dbDelta( $checkinSql );
        dbDelta( $measurementTableSql );
        dbDelta( $intakeTableSql );

        add_option( 'e20rTracker_db_version', $e20r_db_version );

        flush_rewrite_rules();
    }

    public function deactivateE20R_Plugin() {

        global $wpdb;
        global $e20r_db_version;

        foreach ( $this->tables as $tblName ) {

            dbg("dbUninstall() - {$tblName} being dropped");

            $sql = "DROP TABLE IF EXISTS {$tblName}";
            $wpdb->query( $sql );
        }
    }

} 