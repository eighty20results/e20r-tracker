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
    private $tables = array();

    public function e20rMeasurements( $user_id = null ) {

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

        $this->user_id = $user_id;

        if ( ! function_exists( 'in_betagroup' ) ) {
            dbg("in_betagroup function is missing???");
        }

        if ( ! in_betagroup( $user_id ) ) {

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

        return true;
    }

    public function get_Measurements() {

        if ( empty($this->measurements ) ) {
            $this->loadMeasurements();
        }

        return $this->measurements;
    }

    public function view_addMeasurements() {

        if ( empty( $this->user_id ) ) {
            throw new Exception( "No user specified" );
        }

        ob_start();
        ?>
        <table id="e20r-progress-update">
            <thead>
                <th class="e20r-progress-border"></th>
                <th colspan="2" id="e20r-progress-header"></th>
                <th class="e20r-progress-border"></th>
            </thead>
            <tbody>
                <td class="e20r-progress-border"></td>
                <td class="e20r-number-col">1</td>
                <td class="e20r-measurement-info" id="body-weight">
                    <fieldset>
                        <legend>
                            <a href="e20r-tracker-help.php?topic=weight" class=""lbpModal
                        </legend>
                    </fieldset>
                </td>
                <td class="e20r-progress-border"></td>
            </tbody>
        </table>
        <?php
        $html = ob_get_clean();

        return $html;
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
        }

        return $retArr;
    }
} 