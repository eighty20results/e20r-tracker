<?php

if ( ! array_key_exists( 'E20R_Checkin', $GLOBALS ) ) {

    class E20R_Checkin {

        function __construct() {

        } // end constructor

    } // end class

    // Store reference to the plugin in $GLOBALS so our unit tests can access it
    $GLOBALS['E20R_Checkin'] = new E20R_Checkin();

} // end if
