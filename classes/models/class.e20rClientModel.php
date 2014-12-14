<?php

class e20rClientModel {

    private $id = null;
    private $program_id = null;

    // Client data
    public $appointments = null;
    public $info = null;
    public $measurements = null;
    public $intakeInfo = null;
    public $checkinInfo = null;

    private $data_enc_key = null;

    private $table;

    public function e20rClientModel( $user_id = null ) {

        global $current_user, $wpdb, $e20rTracker;

        if ( ( $user_id == 0 ) && ( $current_user->ID !== 0 ) ) {

            $user_id = $current_user->id;
        }

        $this->id = $user_id;

        try {
            $this->table = $e20rTracker->tables->getTable( 'client_info' );
        }
        catch ( Exception $e ) {
            dbg("Error loading client_info table: " . $e->getMessage() );
        }
    }

    public function load() {

        try {
            dbg("load() - Loading clientInfo for user {$this->id}");

            if ( false === ( $this->info = get_transient( "e20r_client_info_{$this->id}" ) ) ) {

                dbg("Loading client information for {$this->id} from the database");

                // Not stored yet, so grab the data from the DB and store it.
                $this->info = $this->loadInfo();
                set_transient( "e20r_client_info_{$this->id}", $this->info, 1 * HOUR_IN_SECONDS );
            }

            if ( empty( $this->info ) ) {
                dbg("No Client information in the database for {$this->id}");
            }

            dbg("Client info loaded: " . print_r( $this->info, true ) );

        } catch ( Exception $e ) {

            dbg( "Error loading user information for {$this->id}: " . $e->getMessage() );
        }

    }

    private function loadInfo() {

        global $wpdb, $current_user;

        $sql = $wpdb->prepare( "
                    SELECT *
                    FROM {$this->table}
                    WHERE user_id = %d
                    ORDER BY program_start ASC
                    LIMIT 1
                  ", $this->id
        );

        $data = $wpdb->get_row( $sql );

        if ( empty ($data) ) {
            dbg("No client data found in DB");
            $data = new stdClass();
            $data->lengthunits = 'in';
            $data->weightunits = 'lbs';
            $data->gender = 'F';
            $data->incomplete_interview = true;
            $data->user_id = $current_user->ID;
        }

        if (isset($data->user_enc_key) ) {
            $this->data_enc_key = $data->user_enc_key;
            unset( $data->user_enc_key );
        }

        return $data;
    }

    public function loadSettings() {

    }

    public function getSettings() {
        return $this->settings;
    }

    private function load_appointments() {

        global $current_user, $wpdb, $appointments, $e20rTracker;

        $appTable = $e20rTracker->tables->getTable('appointments');

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
                FROM {$appTable} AS app
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

        try {
            dbg("load() - Loading measurements for user {$this->id}");

            $tmp = new e20rMeasurements( $this->id );
            $this->measurements = $tmp->getMeasurement('all');

            if ( empty($this->measurements) ) {
                dbg("No measurements in the database for {$this->id}");
            }
        }
        catch ( Exception $e ) {

            dbg("Error loading measurements for {$this->id}: " . $e->getMessage() );
        }

        return $this->measurements;
    }

    public function getAppointments() {

        if ( empty( $this->appointments ) ) {
            $this->load_appointments();
        }

        return $this->appointments;
    }
} 