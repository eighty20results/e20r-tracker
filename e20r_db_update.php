<?php
use E20R\Tracker\Controllers\Tracker;
use E20R\Utilities\Utilities;

if ( !function_exists( "e20r_update_db_to_12" ) ) {
	
	function e20r_update_db_to_12( $version )
	{
		
		if (!e20r_should_we_run($version)) {
			return;
		}
		
		$real_version = $version[0];
		$Tracker = Tracker::getInstance();
		
		Utilities::get_instance()->log("Updating version setting so we won't re-run");
		$Tracker->updateSetting('e20r_db_version', $real_version );
	}
}

if ( !function_exists( "e20r_update_db_to_11" ) ) {

    function e20r_update_db_to_11( $version )
    {
	    
        if (!e20r_should_we_run($version)) {
            return;
        }

        $real_version = $version[0];
        global $wpdb;
        $Tracker = Tracker::getInstance();
	
	    Utilities::get_instance()->log("Updating version setting so we won't re-run");
	    $Tracker->updateSetting('e20r_db_version', $real_version );
    }
}

if ( !function_exists( "e20r_update_db_to_10" ) ) {

    function e20r_update_db_to_10( $version )
    {

        if (!e20r_should_we_run($version)) {
            return;
        }

        $real_version = $version[0];
        global $wpdb;
	    $Tracker = Tracker::getInstance();
	
	    Utilities::get_instance()->log("Updating version setting so we won't re-run");
        $Tracker->updateSetting('e20r_db_version', $real_version );
    }
}

if ( !function_exists( "e20r_update_db_to_6" ) ) {

    function e20r_update_db_to_6( $version )
    {

        if ( ! e20r_should_we_run( $version ) ) {
            return;
        }

        global $wpdb;
        $Tracker = Tracker::getInstance();
	    	  
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

            ("e20r_update_db_to_6() - Updating record # {$record->id}");

            if (false === $wpdb->query($sql)) {
	
	            Utilities::get_instance()->log("Error when updating record # {$record['id']}: " . $wpdb->print_error());
                $error = true;
            }
        }

        if (!$error) {
            $Tracker->updateSetting('e20r_db_version', 6);
        }
    }
}

if ( !function_exists( "e20r_update_db_to_7" ) ) {

    function e20r_update_db_to_7( $version ) {

        if ( ! e20r_should_we_run( $version ) ) {
            return;
        }

        global $wpdb;
        $Tracker = Tracker::getInstance();
	    	  
        $error = false;
	
	    Utilities::get_instance()->log("Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION );

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
	
	        Utilities::get_instance()->log("Updating record # {$record->id} in e20r_checkin table");

            if ( false === $wpdb->query( $sql ) ) {
	
	            Utilities::get_instance()->log("Error when updating record # {$record->id}: " . $wpdb->print_error() );
                $error = true;
            }
        }

        if (! $error ) {
            $Tracker->updateSetting( 'e20r_db_version', 7 );
        }

    }
}
if ( !function_exists( "e20r_update_db_to_8" ) ) {

    function e20r_update_db_to_8( $version ) {

        if ( ! e20r_should_we_run( $version ) ) {
            return;
        }

        global $wpdb;
        $Tracker = Tracker::getInstance();
	    	  
        $error = false;
	
	    Utilities::get_instance()->log("Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION );

        $sql = $wpdb->prepare(
            "SELECT id, checkin_date
            FROM {$wpdb->prefix}e20r_checkin
            WHERE checkin_date >= %s",
            '2015-07-29'
        );

        $result = $wpdb->get_results( $sql );

        foreach(  $result as $record ) {

            $checkin_date = $record->checkin_date;
            $new_checkin_date = date('Y-m-d H:i:s', strtotime( "{$checkin_date} +5 day" ) );

            $sql = $wpdb->prepare(
                "UPDATE {$wpdb->prefix}e20r_checkin
                 SET checkin_date = %s
                 WHERE id = %d",
                $new_checkin_date,
                $record->id
            );
	
	        Utilities::get_instance()->log("Updating record # {$record->id} in e20r_checkin table");

            if ( false === $wpdb->query( $sql ) ) {
	
	            Utilities::get_instance()->log("Error when updating record # {$record->id}: " . $wpdb->print_error() );
                $error = true;
            }
        }

        if (! $error ) {
            $Tracker->updateSetting( 'e20r_db_version', 8 );
        }

    }
}
if ( !function_exists( "e20r_update_db_to_9" ) ) {

    function e20r_update_db_to_9( $version )
    {

        if ( ! e20r_should_we_run( $version ) ) {
            return;
        }

        global $wpdb;
        $Tracker = Tracker::getInstance();
	    	  
        $error = false;

        // Start updating e20r_workout records with for_date >= 07-29-2015 and set the for_date to $for_date + 5 day.

        $sql = $wpdb->prepare(
            "SELECT id, for_date
            FROM {$wpdb->prefix}e20r_workout
            WHERE for_date >= %s",
            '2015-07-29'
        );

        $result = $wpdb->get_results($sql);

        foreach ($result as $record) {

            $for_date = $record->for_date;
            $new_for_date = date('Y-m-d H:i:s', strtotime("{$for_date} +5 day"));

            $sql = $wpdb->prepare(
                "UPDATE {$wpdb->prefix}e20r_workout
                 SET for_date = %s
                 WHERE id = %d",
                $new_for_date,
                $record->id
            );
	
	        Utilities::get_instance()->log("Updating record # {$record->id}");

            if (false === $wpdb->query($sql)) {
	
	            Utilities::get_instance()->log("Error when updating record # {$record['id']}: " . $wpdb->print_error());
                $error = true;
            }
        }

        if (!$error) {
            $Tracker->updateSetting('e20r_db_version', 9);
        }
    }
}
if ( !function_exists( "e20r_update_db_to_5" ) ) {

    function e20r_update_db_to_5( $version ) {

        global $wpdb;
        $Tracker = Tracker::getInstance();
	    	  
        if ( ! e20r_should_we_run( $version ) ) {
            return;
        }

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
	        Utilities::get_instance()->log( "SQL statement resulted in error: " . $wpdb->print_error() );
	        Utilities::get_instance()->log( "Content: " . print_r( $add_enum, true ) );
        }

        foreach( $update_enums as $upd ) {

            if  ( false === $wpdb->query( $upd ) ) {
                $error = true;
	            Utilities::get_instance()->log("SQL statement resulted in error: " . $wpdb->print_error());
	            Utilities::get_instance()->log( "Content: " . print_r( $upd, true ) );
            }
        }

        if  ( false === $wpdb->query( $remove_enum ) ) {
            $error = true;
	        Utilities::get_instance()->log("SQL statement resulted in error: " . $wpdb->print_error());
	        Utilities::get_instance()->log("Content: " . print_r( $remove_enum, true ));
        }

        if (! $error ) {
            $Tracker->updateSetting( 'e20r_db_version', 5 );
        }
    }
} // End of function_exists('update_db_to_4')

if ( ! function_exists( 'e20r_should_we_run' ) ) {

    function e20r_should_we_run( $version ) {
    	
    	$version = $version[0];

        if ( ( $version != E20R_DB_VERSION ) && ( $version > E20R_DB_VERSION ) ) {
	        Utilities::get_instance()->log("update_db_to_{$version}() - Already ran {$version} to " . E20R_DB_VERSION . " upgrade. Skipping");
            return false;
        }
	
	    Utilities::get_instance()->log("update_db_to_{$version}() - Upgrading database for e20r-tracker plugin to version " . E20R_DB_VERSION );
        return true;
    }
}