<?php

if ( !function_exists( "update_db_to_5" ) ) {

    function update_db_to_5() {

        dbg("update_db_to_5() - Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION );

        global $wpdb;
        global $e20rTracker;

        $error = false;
        $update_enums = array();

        $add_enum = "
                ALTER TABLE {$wpdb->prefix}e20r_assignments
                CHANGE field_type field_type enum('textbox','input','checkbox','radio','button','yesno','survey', 'multichoice', 'rank')";

        $update_enums[] = "
                UPDATE {$wpdb->prefix}e20r_assignments
                SET field_type = 'rank'
                WHERE field_type = 'survey'";

        $remove_enum = "
                ALTER TABLE {$wpdb->prefix}e20r_assignments
                CHANGE field_type field_type enum('button', 'input', 'textbox', 'checkbox', 'multichoice', 'rank', 'yesno')";

        if  ( false === $wpdb->query( $add_enum ) ) {
            $error = true;
            dbg("SQL statement resulted in error: " . $wpdb->print_error() );
            dbg( $add_enum );
        }

        foreach( $update_enums as $upd ) {

            if  ( false === $wpdb->query( $upd ) ) {
                $error = true;
                dbg("SQL statement resulted in error: " . $wpdb->print_error());
                dbg($upd);
            }
        }

        if  ( false === $wpdb->query( $remove_enum ) ) {
            $error = true;
            dbg("SQL statement resulted in error: " . $wpdb->print_error());
            dbg($remove_enum);
        }

        if (! $error ) {
            $e20rTracker->updateSetting( 'e20r_db_version', 5 );
        }
    }
} // End of function_exists('update_db_to_4')