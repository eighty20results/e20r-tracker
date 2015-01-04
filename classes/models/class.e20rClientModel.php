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

        global $current_user, $wpdb, $e20rTables;

        if ( ( $user_id == 0 ) && ( $current_user->ID !== 0 ) ) {

            $user_id = $current_user->id;
        }

        $this->id = $user_id;

        try {
            $this->table = $e20rTables->getTable( 'client_info' );
        }
        catch ( Exception $e ) {
            dbg("e20rClientModel::load() - Error loading client_info table: " . $e->getMessage() );
        }
    }

    public function setUser( $id ) {

        $this->id = $id;
    }

    public function load() {

        try {
            dbg("e20rClientModel::load() - Loading clientInfo for user {$this->id}");

            if ( false === ( $this->info = get_transient( "e20r_client_info_{$this->id}" ) ) ) {

                dbg("e20rClientModel::load() - Loading client information for {$this->id} from the database");

                // Not stored yet, so grab the data from the DB and store it.
                $this->info = $this->loadInfo();
                set_transient( "e20r_client_info_{$this->id}", $this->info, 1 * HOUR_IN_SECONDS );
            }

            if ( empty( $this->info ) ) {
                dbg("e20rClientModel::load() - No Client information in the database for {$this->id}");
            }

            dbg("e20rClientModel::load() - Client info loaded: " . print_r( $this->info, true ) );

        } catch ( Exception $e ) {

            dbg( "e20rClientModel::load() - Error loading user information for {$this->id}: " . $e->getMessage() );
        }

    }

    private function loadInfo() {

        global $wpdb;

        dbg("e20rClientModel::loadInfo() - Client ID is: {$this->id}");

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

            dbg("e20rClientmodel::loadInfo() - No client data found in DB");

            $data = new stdClass();
            $data->lengthunits = 'in';
            $data->weightunits = 'lbs';
            $data->gender = 'M';
            $data->incomplete_interview = true; // Will redirect the user to the interview page.
            $data->progress_photo_dir = "e20r_pics/client_{$this->id}";
            $data->user_id = $this->id;
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

        global $current_user, $wpdb, $appointments, $e20rTracker, $e20rTables;

        $appTable = $e20rTables->getTable('appointments');

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

        $sql = $e20rTracker->prepare_in( $sql, $statuses, '%s' );
        // dbg("SQL for appointment list: " . print_r( $sql, true ) );
        $this->appointments = $wpdb->get_results( $sql, OBJECT);
    }

    /*
    private function load_levels( $name = null ) {

        global $wpdb;

        if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
            $this->raise_error( 'pmpro' );
        } else {

            dbg("Loading levels from PMPro");

            $allLevels = pmpro_getAllLevels( true );

            if ( ! empty( $name ) ) {
                dbg("e20rClientModel::load_levels() -- Name for pattern: {$name}");
                $name = str_replace( '+', '\+', $name);
                $pattern = "/{$name}/i";
                dbg("e20rClientModel::load_levels() -- Membership level pattern: {$pattern}");
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
    */
    public function getInfo() {

        return $this->info;
    }

    public function saveUnitInfo( $lengthunit, $weightunit ) {

        global $wpdb;

        if ( $wpdb->update( $this->table,
            array( 'lengthunits' => $lengthunit, 'weightunits' => $weightunit ),
            array( 'user_id' => $this->id ), array( '%d' ) ) === false ) {

            dbg("e20rClientModel::saveUnitInfo() - Error updating unit info");
            throw new Exception("Error updating weight/length units: " . $wpdb->print_error() );
        }

        delete_transient("e20r_client_info_{$this->id}");

        return true;
    }

    /**
     * @param $who - User ID
     * @param $when - Date of captured measurement
     * @param $imageSide -- Front/Side/Back
     * @param $imageSize -- 'thumbnail', 'large', 'medium', etc.
     *
     * @return mixed - URL to image
     */
    public function getBetaUserUrl( $who, $when, $imageSide ) {

        global $e20rTables, $wpdb;

        if ( $e20rTables->isBetaClient() ) {

            dbg("e20rClientModel::getBetaUserUrl() - User with ID {$who} IS a member of the Nourish BETA group");

            switch ( $imageSide ) {

                case 'front':
                    $fid = 2;
                    break;

                case 'side':
                    $fid = 3;
                    break;

                case 'back':
                    $fid = 4;
                    break;
            }

            $sql = $wpdb->prepare("SELECT gf_detail.value as URL
                                    FROM {$wpdb->prefix}rg_lead AS gf_lead
                                      INNER JOIN {$wpdb->prefix}rg_lead_detail AS gf_detail
                                      ON ( gf_lead.id = gf_detail.lead_id
                                          AND ( gf_lead.created_by = %d )
                                          AND ( gf_lead.form_id = %d )
                                          AND ( gf_lead.date_created < %s )
                                          AND ( gf_detail.field_number = %d )
                                  )
                                  ORDER BY gf_lead.date_created DESC
                                  LIMIT 1",
                    $who,
                    GF_PHOTOFORM_ID,
                    date('Y-m-d', strtotime($when) + (24 * 60 * 60) ),
                    $fid
            );

            // dbg("e20rClientModel::getBetaUserUrl() - SQL: {$sql}");

            $imageLink = $wpdb->get_row( $sql );

            if ( empty( $imageLink ) ) {
                $imageUrl = E20R_PLUGINS_URL . '/images/no-image-uploaded.jpg';
            }
            else {
                $imageUrl = $imageLink->URL;
            }
        }
        else {

            dbg("e20rClientModel::getBetaUserUrl() - User with ID {$who} is NOT a member of the Nourish BETA group");
            $imageUrl = false;

        }

        return $imageUrl;
    }

    /**
     * Configure the weight & length unit settings for the current user
     *
     * Generates an array:
     *
     * array( 'weightunits' => array(
     *                          'key' => 'lbs',
     *                          'desc' => 'pounds (lbs)',
     *                       ),
     *        'lengthunits' => array(
     *                          'key' => 'in',
     *                          'desc' => 'inches (in)',
     *                       ),
     *          'default' => 'lbs',
     *          '
     *
     *
     * @param stdClass $client_data -- The client data from the database (object)
     */
    private function setUnitType( $client_data = null ) {

        if ( empty ( $this->unit_type ) ) {
            $this->unit_type = array();
        }

        if ( $client_data != null ) {

            $this->unit_type['weightunits'] = $client_data->weightunits;
            $this->unit_type['lengthunits'] = $client_data->lengthunits;

        }
        else {

            global $wpdb;

            if ( $this->id != 0 ){
                $sql = $wpdb->prepare( "
                    SELECT weightunits, lengthunits
                    FROM {$wpdb->prefix}e20r_client_info
                    WHERE user_id = %d
                    LIMIT 1
                    ",
                    $this->id
                );

                $res = $wpdb->get_row( $sql );

                if ( ! empty( $res ) ) {
                    $this->unit_type['weightunits'] = $res->weightunits;
                    $this->unit_type['lengthunits'] = $res->lengthunits;
                }
                else {
                    $this->unit_type['weightunits'] = null;
                    $this->unit_type['lengthunits'] = null;
                }
            }

        }
    }

    public function getMeasurements() {

        if ( $this->measurements == null ) {

            try {
                dbg( "load() - Loading measurements for user {$this->id}" );

                $tmp                = new e20rMeasurements( $this->id );
                $this->measurements = $tmp->getMeasurement( 'all' );

                if ( empty( $this->measurements ) ) {
                    dbg( "No measurements in the database for {$this->id}" );
                }
            } catch ( Exception $e ) {

                dbg( "Error loading measurements for {$this->id}: " . $e->getMessage() );
            }
        }


        return $this->measurements;

    }

    public function getAppointments() {

        if ( empty( $this->appointments ) ) {
            $this->load_appointments();
        }

        return $this->appointments;
    }
    /*
    public function getUserList( $level = '' ) {

        global $wpdb, $e20rTracker;

        if ( ! empty($level) ) {
            $this->load_levels( $level );
        }

        $levels = $this->get_level_ids();

        dbg("Levels being loaded: " . print_r( $levels, true ) );

        if ( ! empty($levels) ) {
            $sql = "
                    SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                    FROM {$wpdb->users} AS u
                      INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                        ON ( u.ID = m.user_id )
                        INNER JOIN {$wpdb->usermeta} AS um
                        ON ( u.ID = um.user_id )
                    WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
                    ORDER BY last_name ASC
            ";
        }
        else {
            $sql = "
                    SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                    FROM {$wpdb->users} AS u
                      INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                        ON ( u.ID = m.user_id )
                        INNER JOIN {$wpdb->usermeta} AS um
                        ON ( u.ID = um.user_id )
                    WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' )
                    ORDER BY last_name ASC
            ";
        }
        $sql = $e20rTracker->prepare_in( $sql, $levels );

        dbg("SQL for user list: " . print_r( $sql, true));

        $user_list = $wpdb->get_results( $sql, OBJECT );

        if (! empty( $user_list ) ) {
            return $user_list;
        }
        else {
            $data = new stdClass();
            $data->id = 0;
            $data->name = 'No users found';

            return array( $data );
        }
    }

    public function getLevelList( $onlyVisible = false ) {

        $allLevels = pmpro_getAllLevels();
        $levels = array();

        foreach ($allLevels as $level ) {

            $visible = ( $level->allow_signups == 1 ? true : false );

            if ( ( ! $onlyVisible) || ( $visible && $onlyVisible )) {
                $levels[ $level->id ] = $level->name;
            }
        }

        asort( $levels );

        // dbg("Levels fetched: " . print_r( $levels, true ) );

        return $levels;
    }
    */
} 