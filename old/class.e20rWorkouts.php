<?php

class e20rWorkouts {

    private $_tables;

    public function __construct() {

        global $wpdb;

        dbg("Running constructor for e20rWorkouts");

        $this->_tables = new stdClass();

        $this->_tables->programs = $wpdb->prefix . 'e20r_programs';
        $this->_tables->sets = $wpdb->prefix . 'e20r_sets';
        $this->_tables->exercise = $wpdb->prefix . 'e20r_exercises';

    }


} 