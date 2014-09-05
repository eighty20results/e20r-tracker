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

    /* TODO: Create the client pages for the menu */
    public function e20r_client_pages() {

    }

    public function client_assignments_page() {

    }

    public function client_measurements_page() {

    }

    public function client_habits_page() {

    }

    public function client_meals_page() {

    }

    /**
     *  Add the datepicker scripts for the admin pages (TODO: Figure out how to use datepicker JS in Wordpress
     */
    public function admin_scripts() {

    }

    public function initAdmin() {
        $page = add_menu_page('Client Tracker', __('Client Tracker','e20r_tracker'), 'manage_options',  'e20r-tracker', array(&$this,'e20r_client_pages'),'div');
        add_submenu_page('s3fclients', __('Assignments','e20r_tracker'), __('Assignments','e20r_tracker'), 'manage-options', "e20r-tracker-data", array(&$this,'client_assignments_page'));
        add_submenu_page('s3fclients', __('Measurements','e20r_tracker'), __('Measurements','e20r_tracker'), 'manage-options', "e20r-tracker-data", array(&$this,'client_measurements_page'));
        add_submenu_page('s3fclients', __('Habits','e20r_tracker'), __('Habits','e20r_tracker'), 'manage_options', "e20r-tracker-data", array(&$this,'client_habits_page'));
        add_submenu_page('s3fclients', __('Meals','e20r_tracker'), __('Meal History','e20r_tracker'), 'manage_options', "e20r-tracker-data", array(&$this,'client_meals_page'));

        /* TODO: add_action( "admin_print_scripts-$page", array( &$this, 'admin_scripts' ) ); */
/*
  add_submenu_page('appointments', __('Shortcodes','e20r_tracker'), __('Shortcodes','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SHORTCODES), "app_shortcodes", array(&$this,'shortcodes_page'));
        add_submenu_page('appointments', __('FAQ','e20r_tracker'), __('FAQ','e20r_tracker'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_FAQ), "app_faq", array(&$this,'faq_page'));
        // Add datepicker to appointments page
        add_action( "admin_print_scripts-$page", array( &$this, 'admin_scripts' ) );
*/
    }
} 