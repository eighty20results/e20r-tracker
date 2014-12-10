<?php

class e20rClientModel {

    private $id = null;
    private $program_id = null;

    // Client data
    public $appointments = null;
    public $info = null;
    public $measurements = null;
    public $intakeInfo = null;

    private $data_enc_key = null;

    private $tables;

    public function e20rClientModel( $user_id = null ) {

        global $current_user, $wpdb;

        if ( ( $user_id == 0 ) && ( $current_user->ID !== 0 ) ) {

            $user_id = $current_user->id;
        }

        $this->id = $user_id;

        $tmp = new e20rTables();
        $this->tables = $tmp->getTable();

    }

    public function load() {

        try {
            dbg("load() - Loading clientInfo for user {$this->id}");
            $this->info = $this->loadInfo();

            if ( empty( $this->info ) ) {
                dbg("No Client information in the database for {$this->id}");
            }

            dbg("Client info loaded: " . print_r( $this->info, true ) );

        } catch ( Exception $e ) {

            dbg( "Error loading user information for {$this->id}: " . $e->getMessage() );
        }

        try {
            dbg("load() - Loading measurements for user {$this->id}");
            $this->measurements = new e20rMeasurements( $this->id );
            $this->measurements->init();
            $this->measurements->loadData();
        }
        catch ( Exception $e ) {

            dbg("Error loading measurements for {$this->id}: " . $e->getMessage() );
        }
    }

    private function loadInfo() {

        // TODO: Cache the info using WP's caching mech?
        global $wpdb;

        $sql = $wpdb->prepare( "
                    SELECT *
                    FROM {$this->tables->client_info}
                    WHERE user_id = %d
                    ORDER BY program_start ASC
                    LIMIT 1
                  ", $this->id
        );

        $data = $wpdb->get_row( $sql );

        $this->data_enc_key = $data->user_enc_key;
        unset( $data->user_enc_key );

        if ( empty ($data) ) {
            $data = new stdClass();
            $data->lengthunits = 'in';
            $data->weightunits = 'lbs';
            $data->gender = 'F';
        }

        return $data;
    }

    public function loadSettings() {

    }

    public function getSettings() {
        return $this->settings;
    }

    private function load_appointments() {

        global $current_user, $wpdb, $appointments;

        if ( empty( $appointments ) ) {

            throw new Exception("Appointments+ Plugin is not installed.");
            return false;
        }

        $statuses = array( "completed", "removed" );

        if ( $this->id == 0 ) {

            $this->id = $current_user->ID;
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
            $this->id
        );

        $sql = $this->prepare_in( $sql, $statuses, '%s' );
        // dbg("SQL for appointment list: " . print_r( $sql, true ) );
        $this->appointments = $wpdb->get_results( $sql, OBJECT);
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

        return $this->measurements;
    }

    public function getAppointments() {

        if ( empty( $this->appointments ) ) {
            $this->load_appointments();
        }

        return $this->appointments;
    }
} 