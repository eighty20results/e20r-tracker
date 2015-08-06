<?php

if ( !function_exists( "update_db_to_6" ) ) {

    function update_db_to_6( $version )
    {

        global $wpdb;
        global $e20rTracker;

        // $version = $e20rTracker->loadOption('e20r_db_version');

        if ( ( $version != E20R_DB_VERSION ) && ( $version > E20R_DB_VERSION ) ) {
            dbg("update_db_to_6() - Already ran this. Skipping");
            return;
        }

        dbg("update_db_to_6() - Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION);

        $error = false;

        // Start updating e20r_workout records with for_date >= 07-29-2015 and set the for_date to $for_date - 1 day.

        $sql = $wpdb->prepare(
            "SELECT id, for_date
            FROM {$wpdb->prefix}e20r_workout
            WHERE for_date >= %s",
            '2015-07-29'
        );

        $result = $wpdb->get_results($sql);

        foreach ($result as $record) {

            $for_date = $record->for_date;
            $new_for_date = date('Y-m-d H:i:s', strtotime("{$for_date} -1 day"));

            $sql = $wpdb->prepare(
                "UPDATE {$wpdb->prefix}e20r_workout
                 SET for_date = %s
                 WHERE id = %d",
                $new_for_date,
                $record->id
            );

            dbg("update_db_to_6() - Updating record # {$record->id}");

            if (false === $wpdb->query($sql)) {

                dbg("update_db_to_6() - Error when updating record # {$record['id']}: " . $wpdb->print_error());
                $error = true;
            }
        }

        if (!$error) {
            $e20rTracker->updateSetting('e20r_db_version', 6);
        }
    }
}

if ( !function_exists( "update_db_to_7" ) ) {

    function update_db_to_7( $version )
    {

        global $wpdb;
        global $e20rTracker;

        $error = false;
        // $version = $e20rTracker->loadOption('e20r_db_version');

        if ( ( $version != E20R_DB_VERSION ) && ( $version > E20R_DB_VERSION ) ) {
            dbg("update_db_to_7() - Already ran this. Skipping");
            return;
        }

        dbg("update_db_to_7() - Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION );

        $sql = $wpdb->prepare(
            "SELECT id, checkin_date
            FROM {$wpdb->prefix}e20r_checkin
            WHERE checkin_date >= %s",
            '2015-07-29'
        );

        $result = $wpdb->get_results( $sql );

        foreach(  $result as $record ) {

            $checkin_date = $record->checkin_date;
            $new_checkin_date = date('Y-m-d H:i:s', strtotime( "{$checkin_date} -1 day" ) );

            $sql = $wpdb->prepare(
                "UPDATE {$wpdb->prefix}e20r_checkin
                 SET checkin_date = %s
                 WHERE id = %d",
                $new_checkin_date,
                $record->id
            );

            dbg("update_db_to_6() - Updating record # {$record->id} in e20r_checkin table");

            if ( false === $wpdb->query( $sql ) ) {

                dbg("update_db_to_6() - Error when updating record # {$record->id}: " . $wpdb->print_error() );
                $error = true;
            }
        }

        if (! $error ) {
            $e20rTracker->updateSetting( 'e20r_db_version', 7 );
        }

    }
}
if ( !function_exists( "update_db_to_5" ) ) {

    function update_db_to_5( $version ) {

        global $wpdb;
        global $e20rTracker;

        // $version = $e20rTracker->loadOption('e20r_db_version');

        if ( ( $version != E20R_DB_VERSION ) && ( $version > E20R_DB_VERSION ) ) {
            dbg("update_db_to_5() - Already ran this. Skipping");
            return;
        }

        dbg("update_db_to_5() - Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION );

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