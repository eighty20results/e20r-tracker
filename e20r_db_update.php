<?php

if ( !function_exists( "update_db_to_2" ) ) {

    function update_db_to_2() {

        dbg("update_db_to_2() - Upgrading database for e20r-tracker plugin to version 2");

        global $wpdb;
        global $e20rTracker;

        $update_enums = array();

        $add_enum = "
                ALTER TABLE {$wpdb}e20_assignments
                CHANGE `field_type` `field_type` enum('textbox','input','checkbox','radio','button','yesno','survey', 'multichoice', 'rank')";

        $update_enums[] = "
                UPDATE TABLE {$wpdb}e20r_assignments
                SET `field_type` = 'rank'
                WHERE `field_type` = 'survey'";

        $remove_enum = "
                ALTER TABLE {$wpdb}e20r_assignments
                CHANGE `field_type` `field_type` enum('button', 'input', 'textbox', 'checkbox', 'multichoice', 'rank', 'yesno')}";

        $wpdb->query( $add_enum );
        dbg(" Testing whether last SQL statement resulted in error: " . $wpdb->print_error() );

        foreach( $update_enums as $upd ) {

            $wpdb->query( $upd );
            dbg(" Testing whether last SQL statement resulted in error: " . $wpdb->print_error() );
        }

        $wpdb->query( $remove_enum );
        dbg(" Testing whether last SQL statement resulted in error: " . $wpdb->print_error() );

        $e20rTracker->updateSetting( 'e20r_db_version', 2 );
    }
}