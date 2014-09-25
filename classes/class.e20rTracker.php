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
        register_activation_hook( E20R_PLUGIN_DIR, 'activateE20R_Plugin' );
        register_deactivation_hook( E20R_PLUGIN_DIR, 'deactivateE20R_Plugin' );

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

} 