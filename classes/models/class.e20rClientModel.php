<?php

class e20rClientModel {

    private $id = null;
    private $info = null;
    private $measurements = null;
    private $articles = null;
    private $programs = null;

    private $tables;

    public function e20rClientModel( $user_id = null ) {

        global $current_user, $wpdb;

        if ( ( $user_id == 0 ) && ( $current_user->ID !== 0 ) ) {

            $user_id = $current_user->id;
        }

        $this->id = $user_id;

        $this->old_tables = new stdClass();

        $this->old_tables->assignments = "{$wpdb->prefix}s3f_nourishAssignments";
        $this->old_tables->compliance = "{$wpdb->prefix}s3f_nourishHabits";
        $this->old_tables->surveys = "{$wpdb->prefix}e20r_Surveys";
        $this->old_tables->measurements = "{$wpdb->prefix}nourish_measurements";
        $this->old_tables->meals = "{$wpdb->prefix}wp_s3f_nourishMeals";


    }

    public function load() {

        if ( empty( $this->info ) ) {
            try {
                $this->info = $this->loadInfo();

                if ( empty( $this->info ) ) {
                    dbg("No Client information in the database for {$this->id}");
                }

                dbg("Client info loaded: " . print_r( $this->info, true ) );

            } catch ( Exception $e ) {
                dbg( "Error loading user information for {$this->id}" );

                return false;
            }
        }
    }

    private function loadInfo() {

        // TODO: Cache the info using WP's caching mech?
        global $wpdb;

        $sql = $wpdb->prepare( "
                    SELECT *
                    FROM {$wpdb->prefix}e20r_client_info
                    WHERE user_id = %d
                    ORDER BY program_start ASC
                    LIMIT 1
                  ", $this->id
        );

        return $wpdb->get_row( $sql );
    }

    public function load_appointments( $clientId ) {

        global $current_user, $wpdb, $appointments;

        if ( empty( $appointments ) ) {

            throw new Exception("Appointments+ Plugin is not installed.");
            return false;
        }

        $statuses = array( "completed", "removed" );

        if ( $clientId == 0 ) {

            $clientId = $current_user->ID;
        }

        $sql = $wpdb->prepare(
            "
                SELECT ID, user, start, status, created
                FROM {$appointments->app_table} AS app
                INNER JOIN {$wpdb->users} AS usr
                  ON ( app.user = usr.ID )
                WHERE user = %d AND status NOT IN ( [IN] )
                ORDER BY start ASC
            ",
            $clientId
        );

        $sql = $this->prepare_in( $sql, $statuses, '%s' );
        // dbg("SQL for appointment list: " . print_r( $sql, true ) );

        return $wpdb->get_results( $sql, OBJECT );
    }

    private function load_levels( $name = null ) {

        global $wpdb;

        if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
            $this->raise_error( 'pmpro' );
        } else {

            dbg("Loading levels from PMPro");

            $allLevels = pmpro_getAllLevels( true );

            if ( ! empty( $name ) ) {

                $name = str_replace( '+', '\+', $name);
                $pattern = "/{$name}/i";
                dbg("Pattern: {$pattern}");
            }

            foreach( $allLevels as $level ) {

                if ( preg_match($pattern, $level->name ) == 1 ) {
                    $this->levels[] = $level->id;
                }
                elseif ( empty( $name ) ) {
                    $this->levels[] = $level->id;
                }
            }
        }
    }

    public function getInfo() {

        return $this->info;
    }

    public function getMeasurements() {

        if ( empty(  $this->measurements ) ) {

            $this->measurments = new e20rMeasurementModel( $this->id );
        }

        return $this->measurements;
    }
} 