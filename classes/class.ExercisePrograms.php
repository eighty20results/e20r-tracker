<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 8/15/14
 * Time: 10:06 AM
 */

class ExercisePrograms {

    private $_tables;

    public function __construct() {

        global $wpdb;

        dbg("Running constructor for ExercisePrograms");

        $this->_tables = new stdClass();

        $this->_tables->programs = $wpdb->prefix . 'e20r_programs';
        $this->_tables->sets = $wpdb->prefix . 'e20r_sets';
        $this->_tables->exercise = $wpdb->prefix . 'e20r_exercises';

    }


} 