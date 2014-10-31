<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/25/14
 * Time: 1:50 PM
 */

class e20rMeasurements {

    private $user_id;
    private $measurements;
    private $measurementDate = null;
    private $mBydate = array();
    private $tables = array();
    private $unit_type;

    private $measured_items;

    public function e20rMeasurements( $user_id = null, $forDate = null ) {

        global $wpdb;

        if ( is_null($user_id) ) {

            global $current_user;

            if ( $current_user->ID == 0 ) {
                throw new Exception( "User needs to be logged in to access measurements " );
            }
            else {
                $user_id = $current_user->ID;
            }
        }

        if ( $forDate !== null ) {
            $this->measurementDate = new DateTime( $forDate, new DateTimeZone( get_option( 'timezone_string' ) ) );
        }
        else {
            $this->measurementDate = new DateTime( 'NOW', new DateTimeZone( get_option( 'timezone_string' ) ) );
        }

        $this->unit_type = $this->getUserSetting( 'unit-type' );

        $this->measured_items = array(
            'Body Weight',
            'Girth' => array( 'neck', 'shoulder', 'arm', 'chest', 'waist', 'hip', 'thigh', 'calf' ),
            'Photos',
            'Other Progress Indicators',
            'Progress Questionnaire'
        );

        $this->user_id = $user_id;

        return true;
    }

    public function init() {

        global $wpdb;

        if ( ! function_exists( 'in_betagroup' ) ) {
            dbg("in_betagroup function is missing???");
        }

        if ( ! in_betagroup( $this->user_id ) ) {

            dbg("User {$this->user_id} is NOT in the beta group");

            $this->tables['name'] = $wpdb->prefix . 'e20r_measurements';

            $this->tables['fields'] = array(
                'id' => 'id',
                'user_id' => 'user_id',
                'recorded_date' => 'recorded_date',
                'weight' => 'weight',
                'neck' => 'neck',
                'shoulder' => 'shoulder',
                'chest' => 'chest',
                'arm' => 'arm',
                'waist' => 'waist',
                'hip' => 'hip',
                'thigh' => 'thigh',
                'calf' => 'calf',
                'girth' => 'girth'
            );

        }
        else {

            $this->tables['name'] = $wpdb->prefix . 'nourish_measurements';

            $this->tables['fields'] = array(
                'id' => 'lead_id',
                'user_id' => 'created_by',
                'recorded_date' => 'recordedDate',
                'weight' => 'weight',
                'neck' => 'neckCM',
                'shoulder' => 'shoulderCM',
                'chest' => 'chestCM',
                'arm' => 'armCM',
                'waist' => 'waistCM',
                'hip' => 'hipCM',
                'thigh' => 'thighCM',
                'calf' => 'calfCM',
                'girth' => 'totalGrithCM'
            );

        }

        $this->loadMeasurements();
        // $this->loadUnitInfo();
    }

    /*
    public function loadUnitInfo() {

        $this->unit_descr = 'lbs (pounds)';

    }
    */

    public function getItems() {

        return $this->measured_items;
    }

    public function get_Measurements() {

        if ( empty($this->measurements ) ) {
            $this->loadMeasurements();
        }

        return $this->measurements;
    }

    public function sc_editMeasurements( $attributes ) {

        global $current_user;

        extract( shortcode_atts( array(
            'day' => 0,
        ), $attributes ) );

        // Has permission...
        $this->user_id = $current_user->ID;
        $this->init();

        return $this->view_editMeasurement();

    }

    public function view_editMeasurement() {

        if ( empty( $this->user_id ) ) {
            throw new Exception( "No user specified" );
        }

        // $class = ($task == 'new' ? 'saved' : 'add');

        ob_start();
        ?>
        <div id="saturday-progress-container" class="progress-container">
            <h3>Your Weekly Progress
                <span>Update</span>
            </h3>
            <div id="e20r-progress-canvas" style="min-height: 100px;">
                <table class="e20r-progress-form">
                    <tfoot>
                        <tr>
                            <td></td>
                            <td>
                                <button class="submit" id="submit-e20r-tracker-button">
                                    <div>Submit Your Weekly Progress Update</div>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php echo $this->generateMeasurementInputs(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    private function generateMeasurementHelp( $count, $key ) {

        ob_start();
        ?>
        <tr>
            <?php echo ( ! is_null( $count ) ? '<td class="e20r-number-col">' . $count . '</td>' : "&nbsp;" ); ?>
            <td class="content e20r-measurement-info" id="measured-<?php echo strtolower($key); ?>">
                <fieldset>
                    <legend>
                        <?php echo $key . " Measurement"; ?><a href="e20r-tracker-help.php?topic=<?php echo str_replace( ' ', '-', strtolower($key) ); ?>" class="lbp_secondary">
                            <img src="<?php echo plugins_url('../images/help.png', __FILE__); ?>">
                        </a>
                    </legend>
                    <div class="help" style="margin-bottom: 24px;">
                        Need some help with taking your <?php echo strtolower($key) ?> measurements? Check out the
                        <a href="e20r-tracker-help.php?topic=<?php echo str_replace( ' ', '-', strtolower($key) ); ?>" class="lbp_secondary">instructions</a>
                        or download the comprehensive
                        <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
                        in PDF format.
                    </div>
                    <?php
        return ob_get_clean();
    }

    private function generateMeasurementEnd() {

        ob_start();
        ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    private function generateMeasurementInputs() {

        $items = $this->loadSettings( 'items' );

        $count = 1;
        // dbg("Items: " . print_r($items, true) );

        ob_start();

        foreach ( $items as $key => $list ) {
            dbg("List item: " . print_r($key, true ) );

            if ( is_array( $list ) ) {

                echo $this->generateMeasurementHelp( $count, $key );
                echo $this->createBlockTable( $list, $key, $count);
                echo $this->generateMeasurementEnd();
            }
            elseif ( ( ! is_array( $list ) ) && ( strtolower($key) != 'photos' ) ) {

                echo $this->generateMeasurementHelp( $count, $key );
                echo $this->createInputBlock( $key, $count );
                echo $this->generateMeasurementEnd();
            }
            elseif ( ( strtolower( $key ) == 'photos' ) && ( $this->requestPhotos() ) ) {

                echo $this->createPhotoBlock( $list, $count );
                echo $this->generateMeasurementEnd();
            }

            if ( ( strtolower( $key ) != 'photos' ) && $this->requestPhotos() )  {
                $count++;
            }

        } // End of foreach()

        $html = ob_get_clean();

        return $html;
    }

    private function requestPhotos() {

        // Using the startdate for the current user + whether the current delay falls on a Saturday (and it's a "Photo" day - every 4 weeks starting the 2nd week of the program )x
        // Return true.
        return false;
    }
    private function createPhotoBlock( $key, $count = null ) {
        dbg("In createPhotoBlock()");
        ob_start();
        ?>
        <tr>
            <?php echo ( ! is_null( $count ) ? '<td class="e20r-number-col">' . $count . '</td>' : '' ); ?>
            <td class="content e20r-measurement-info" id="<?php echo strtolower($key); ?>">
                <fieldset>
                <legend>
                    Progress Photos <a href="e20r-tracker-help.php?topic=<?php echo str_replace( ' ', '-', strtolower($key) ); ?>" class="lbp_secondary">
                        <img src="<?php echo plugins_url('../images/help.png', __FILE__); ?>">
                    </a>
                </legend>
                <div class="help" style="margin-bottom: 24px;">
                    Need some help figuring out how to take your progress photos? Check out the
                    <a href="e20r-tracker-help.php?topic=photo" class="lbp_secondary">instructions</a>
                    or download the comprehensive
                    <a href="/protected-downloads/resources/Measurement-Guide-Nourish.pdf">Measurement Guide</a>
                    in PDF format.
                </div>
                <div></div>
        <?php
        return ob_get_clean();

    }

    private function createBlockTable( $list, $key, $count ) {
        dbg("In createBlockTable()");
        dbg("Content of list: " . print_r( $list, true));

        $date = ( empty( $this->measurementDate ) ? $this->measurementDate->format( 'Y-m-d' ) : date( 'Y-m-d', current_time( 'timestamp' ) ) );
        dbg( "{$key} measurements on {$date}" );

        ob_start();

        foreach ( $list as $item => $value ) {
            ?>
            <h5 class="measurement-header">
                <span class="title"><?php echo ucfirst($item) . " " . ucfirst($key); ?></span>
                <img src="<?php echo plugins_url('../images/help.png', __FILE__); ?>">
            </h5>
            <div class="<?php echo strtolower($key); ?>-row-container">
                <div class="measurement-image" style="<?php echo $this->bgImage( strtolower($item), strtolower($key) ); ?>"></div>
                <div class="measurement-descr-container">
                    <p style="margin-bottom: 52px;">
                        <?php echo $this->measurementDescr( $item ); ?>
                    </p>
                    <div class="measurement-field-container">
                        <div class="label-container">
                            <label>Enter <?php echo ucfirst($item) . ' ' . ucfirst($key); ?></label>
                        </div>
                        <input type="text" class="measurement-input" value="<?php echo ( ! empty( $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} ) ? $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} : '' ); ?>" data-measurement-type="<?php echo strtolower($key) .'_'. strtolower($item); ?>" style="width: 70px; font-size: 20px; text-align: center;">
                        <span class="unit length"><?php echo $this->unit_type->distance; ?></span>
                    </div>
                    <div class="measurement-saved-container active">
                        <div class="label-container">
                            <label>Entered <?php echo ucfirst($item) . ' ' . ucfirst($key); ?></label>
                        </div>
                        <span class="value"><?php echo ( ! empty( $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} ) ? $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} : '' ); ?></span>
                        <span class="unit length"><?php echo $this->unit_type->distance; ?></span>
                        <button class="edit">Change <?php echo $key; ?></button>
                    </div>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    private function createInputBlock( $item, $count = null ) {

        $date = ( empty( $this->measurementDate ) ? $this->measurementDate->format( 'Y-m-d' ) : date( 'Y-m-d', current_time( 'timestamp' ) ) );
        ?>
        <!-- TODO: Add option to change measurement Unit, if wanted when updating body weight -->
        <div class="measurement-field-container">
            <div class="e20r-label">
                <label>Enter Current <?php echo $item; ?></label>
            </div>
            <input type="text" value="<?php echo ( ! empty( $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} ) ? $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} : null ); ?>" class="e20r-measurement-input" data-measurement-type="<?php echo strtolower($item); ?>" style="width: 70px; font-size: 20px; text-align: center;">
            <span class="unit <?php echo strtolower($item); ?>"><?php echo $this->unit_type->mass; ?></span>
        </div>
        <div class="measurement-saved-container active">
            <div class="e20r-label">
                <label>Entered <?php echo $item; ?></label>
            </div>
            <span class="value"><?php echo (! empty( $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} ) ? $this->mBydate[$date]->{$this->tables['fields'][strtolower($item)]} : null ); ?></span>
            <span class="unit <?php echo strtolower($item); ?>"><?php echo $this->unit_type->mass; ?></span>
            <button class="edit">Change <?php echo $item; ?></button>
        </div>
    <?php
    }

    public function saveMeasurement( $form_key, $value) {

        if ( $this->user_id == 0 ) {

            throw new Exception( "User is not logged in" );
        }


    }
    /*********************************************************
     *      Private functions below here                     *
     *********************************************************/

    /**
     * Return the related (from DB) settings for this class.
     *
     * @param $setting -- The setting to retrieve.
     *
     * @return mixed -- The option values fetched from the DB.
     */
    private function loadSettings( $setting ) {

        $options = get_option( 'e20r-tracker' );

        switch ($setting) {

            case 'items':
                return $options['measuring'];
                break;

        }
    }
    /**
     * Loads any already recorded measurements from the database
     */
    private function loadMeasurements() {

        global $wpdb;

        $sql = $wpdb->prepare(
                "
              SELECT
                {$this->tables['fields']['id']}, {$this->tables['fields']['recorded_date']},
                {$this->tables['fields']['weight']}, {$this->tables['fields']['neck']},
                {$this->tables['fields']['shoulder']}, {$this->tables['fields']['chest']},
                {$this->tables['fields']['arm']}, {$this->tables['fields']['waist']},
                {$this->tables['fields']['hip']}, {$this->tables['fields']['thigh']},
                {$this->tables['fields']['calf']}, {$this->tables['fields']['girth']}
                FROM {$this->tables['name']}
                WHERE {$this->tables['fields']['user_id']} = %d
                ORDER BY {$this->tables['fields']['recorded_date']} ASC
            ", $this->user_id );

        dbg("SQL for measurements: " . $sql );


        $this->measurements = $this->remap_fields( $wpdb->get_results( $sql ) );
    }

    /**
     * Re-map the actual database fields to the correct variables (workaround for beta group & Gravity Forms/List plugin.
     *
     * @param (array) $data - List of $wpdb objects (from the database)
     *
     * @return array - Re-mapped DB fields
     */
    private function remap_fields( $data ) {

        $retArr = array();

        foreach ( $data as $record ) {

            $tmp = new stdClass();
            $tmp->id = $record->{$this->tables['fields']['id']};
            $tmp->recorded_date = $record->{$this->tables['fields']['recorded_date']};
            $tmp->weight = $record->{$this->tables['fields']['weight']};
            $tmp->neck = $record->{$this->tables['fields']['neck']};
            $tmp->shoulder = $record->{$this->tables['fields']['shoulder']};
            $tmp->chest = $record->{$this->tables['fields']['chest']};
            $tmp->arm = $record->{$this->tables['fields']['arm']};
            $tmp->waist = $record->{$this->tables['fields']['waist']};
            $tmp->hip = $record->{$this->tables['fields']['hip']};
            $tmp->thigh = $record->{$this->tables['fields']['thigh']};
            $tmp->calf = $record->{$this->tables['fields']['calf']};
            $tmp->girth = $record->{$this->tables['fields']['girth']};

            $retArr[] = $tmp;
            $this->mBydate[$tmp->recorded_date] = $tmp;
        }

        return $retArr;
    }

    private function bgImage( $what, $type ) {
        return "background-image: url(" . plugins_url( "../images/{$what}-{$type}.jpg);", __FILE__ );
    }

    private function measurementDescr( $key ) {
        dbg("Loading measurement description for {$key}");
        // TODO: Read XML document containing measurement descriptions and help items.
        $desc = "Measured just below the Adam's apple and at the level of the 7th cervical vertebra";
        return $desc;
    }

    private function setUnitType() {

        $unit = new stdClass();

        switch ( $this->getUserSetting( 'unit-type' ) ) {

            default:
                $unit->mass = 'lbs (pounds)';
                $unit->distance = 'inches (in)';
        }

        $this->unit_type = $unit;

    }

    private function getUserSetting( $setting, $user_id = null ) {

        global $wpdb, $current_user ;

        if ( $user_id === null ) {
            $user_id = $current_user->ID;
        }

        $value = get_user_meta( $user_id,  "{$wpdb->prefix}e20r-tracker-{$setting}", true);

        if ( empty( $value ) ) {

            switch ($setting) {

                case 'unit-type';
                    $value = new stdClass();
                    $value->mass = 'lbs (pounds)';
                    $value->distance = 'inches (in)';
            }
        }

        return $value;

    }

    private function setUserSettings( $setting ) {

    }
} 