<?php

class S3F_clientData {

    public $tables;
    private $nourish_level;

    public function S3F_clientData() {

        global $wpdb;

        $this->tables = new stdClass();

        $this->tables->Assignments = "{$wpdb->prefix}s3f_nourishAssignments";
        $this->tables->Habits = "{$wpdb->prefix}s3f_nourishHabits";
        $this->tables->Surveys = "{$wpdb->prefix}s3f_Surveys";
        $this->tables->Measuremetns = "{$wpdb->prefix}nourish_measurements";
        $this->tables->Meals = "{$wpdb->prefix}wp_s3f_nourishMeals";


    }

    public function get_nourishLevel() {
        return $this->nourish_level;
    }

    public function set_nourishLevel( $level ) {
        $this->nourish_level = $level;
    }

    public function displayData() {
        // TODO: Create page for back-end to display customers.
    }

    public function initAdmin() {
        // TODO: Understand what the add_menu_page() functions do.
        $page = add_menu_page('S3FClients', __('Client Data','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS),  'appointments', array(&$this,'e20r_clients'),'div');
        add_submenu_page('s3fclients', __('Measurements','e20r_tracker'), __('Measurements','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_TRANSACTIONS), "e20r_measurements", array(&$this,'transactions'));
        add_submenu_page('s3fclients', __('Settings','e20r_tracker'), __('Settings','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SETTINGS), "app_settings", array(&$this,'settings'));
/*        add_submenu_page('appointments', __('Shortcodes','e20r_tracker'), __('Shortcodes','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SHORTCODES), "app_shortcodes", array(&$this,'shortcodes_page'));
        add_submenu_page('appointments', __('FAQ','e20r_tracker'), __('FAQ','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_FAQ), "app_faq", array(&$this,'faq_page'));
        // Add datepicker to appointments page
        add_action( "admin_print_scripts-$page", array( &$this, 'admin_scripts' ) );
*/
    }
} 