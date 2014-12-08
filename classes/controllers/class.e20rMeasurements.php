<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/25/14
 * Time: 1:50 PM
 */

class e20rMeasurements {

    // private $user_info;
    private $id;
    private $model = null;
    private $view = null;

    private $when = null;

    private $measurementDate = null;
    private $girths = array();

/*    private $lastMeasurement = null;
    private $measurementDate = null;
    private $mBydate = array();
*/

    private $unit_type;

    private $measured_items;

    public function e20rMeasurements( $user_id = null, $forDate = null ) {

        global $wpdb;

        $this->load_ajax_hooks();

        if ( is_null($user_id) ) {

            global $current_user;

            if ( $current_user->ID == 0 ) {
                throw new Exception( "User needs to be logged in to access measurements " );
            }
            else {

                $user_id = $current_user->ID;
                dbg("Loading measurements for user {$user_id} - in Measurements Controller");
            }
        }

        if ( $forDate !== null ) {

            $this->measurementDate = new DateTime( $forDate, new DateTimeZone( get_option( 'timezone_string' ) ) );
        }
        else {
            $this->measurementDate = new DateTime( 'NOW', new DateTimeZone( get_option( 'timezone_string' ) ) );
        }

        $this->id = $user_id;
    }

    private function load_girthTypes() {

        dbg("loadGirthInfo() - Running function to grab all girth posts");
        $this->girths = array();

        $girthQuery = new WP_Query(
            array(
                'post_type' => 'e20r_girth_types',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'caller_get_posts' => 1
            )
        );

        if ( $girthQuery->have_posts()) {
            dbg("loadGirthInfo() - There are Girth posts listed..");

            while ( $girthQuery->have_posts()) {

                $girthQuery->the_post();

                $obj = new stdClass();
                $obj->id = get_the_ID();
                $obj->type = strtolower(get_the_title());
                $obj->descr = get_the_content();
                $obj->sortOrder = get_post_meta( $obj->id, 'e20r_girth_type_sortorder', true );

                if ( ! empty( $obj->sortOrder ) ) {
                    dbg("Sort order is specified: {$obj->sortOrder} for {$obj->type}");
                    $this->girths[$obj->sortOrder] = $obj;
                }
                else {
                    $this->girths[] = $obj;
                }

                ksort($this->girths);
            }
        }
        wp_reset_query();
        dbg("loadGirthInfo() - Girth Info: " . print_r($this->girths, true));
    }

    private function load_ajax_hooks() {

        dbg("e20rMeasurements - Loading callback functions for AJAX operations");

        add_action( 'wp_ajax_saveMeasurement', array( &$this, 'saveProgressForm_callback' ) );
        add_action( 'wp_ajax_checkCompletion', array( &$this, 'checkProgressFormCompletion_callback' ) );
        add_action( 'wp_ajax_updateUnitTypes', array( &$this, 'updateUnitTypes') );

        add_action( 'wp_ajax_nopriv_saveMeasurement', 'e20r_ajaxUnprivError' );
        add_action( 'wp_ajax_nopriv_checkCompletion', 'e20r_ajaxUnprivError' );
        add_action( 'wp_ajax_nopriv_updateUnitTypes', 'e20r_ajaxUnprivError' );

    }

    public function updateUnitTypes() {
        dbg( "Attempting to update the Length or weight Units via AJAX");


    }

    public function loadData( $when = 'all') {

        // TODO: Support cached data (using WP Caching mech)
        dbg("Loading measurement data for {$when}");

        try {

            if ( ! class_exists( ' e20rMeasurementModel' ) ) {
                dbg("Loading model class for measurements: " . E20R_PLUGIN_DIR );
                if ( ! include_once( E20R_PLUGIN_DIR . "classes/models/class.e20rMeasurementModel.php" ) ) {
                    wp_die( "Unable to load e20rMeasurementModel class" );
                }
                dbg("Class loaded");
            }


            if ( empty( $this->model ) ) {

                dbg("Init of measurement model");
                $this->model = new e20rMeasurementModel( $this->id );
            }

            if ( $when != 'all' ) {
                dbg("Loading measurement data by date ({$when}) for {$this->id}");
                $this->model->loadForDate( $when );

                return true;
            }
            else {
                dbg("Loading all measurement data for user {$this->id}");
                $this->model->loadAll();

                if ( ! empty( $this->model->all ) )
                    return true;
            }
        }
        catch ( Exception $e ) {

            dbg("Error loading measurement data: " . $e->getMessage() );
            return false;
        }

        return true;
    }

    private function transformForJS( $data ) {

        $retVal = array();

        foreach ($data as $key => $value ) {

            $retVal[$key] = array(
                'value' => $value,
                'units' => ( $key != 'weight' ? $this->unit_type['lengthunits'] : $this->unit_type['weightunits'] ),
            );
        }

        return $retVal;
    }

    public function getGirthTypes() {

        if (empty( $this->girths ) ) {
            $this->load_girthTypes();
        }

        return $this->girths;
    }

    public function getMeasurement( $when = 'all', $forJS = false ) {

        if ( empty( $this->data ) ) {
            $this->data = new e20rMeasurementModel( $this->id );
        }

        switch ( strtolower( $when ) ) {
            case 'current':

                $date = $this->datesForMeasurements( date('Y-m-d', current_time('timestamp') ), '-1 week', CONST_SATURDAY);
                dbg("Saturday this week: " . print_r( $date[0], true) );

                return ( $forJS === true ? $this->transformForJS( $this->model->getByDate($date[0]) ) : $this->model->getByDate($date[0]) );

                break;

            case 'last_week':

                $date = $this->datesForMeasurements( date('Y-m-d', current_time('timestamp') ), '-2 weeks', CONST_SATURDAY);
                dbg("Saturday last week: " . print_r( $date[1], true) );

                return $this->model->getByDate( $date[1] );
                break;

            case 'all':

                return $this->model->getByDate();
                break;

            default:
                return $this->model->getAll();
        }

    }

    /*
    public function loadUnitInfo() {

        $this->unit_descr = 'lbs (pounds)';

    }
    */



    public function view_EditProgress( $date = null, $unitInfo ) {

        if ( ! class_exists( 'e20rMeasurementViews' ) ) {
            if ( ! include_once( E20R_PLUGIN_DIR . "classes/views/class.e20rMeasurementViews.php" ) )
                wp_die( "Unable to load the e20rMeasurementViews() class" );
        }

        $count = 1;

        if ( empty( $this->unit_type ) ) {
            $this->unit_type = $unitInfo;
        }

        if ( $this->unit_type->lengthunit != $unitInfo->lengthunit) {
            $this->unit_type = $unitInfo;
        }

        // FixMe: Need to use the Saturday date info for the article specified (use global $e20r_articleId)

        $date = ( ! empty( $this->measurementDate ) && ( ! empty( $date ) ) ? $this->measurementDate->format( 'Y-m-d' ) : date( 'Y-m-d', current_time( 'timestamp' ) ) );

        dbg("view_EditProgress() - Date for use with progress tracking form: {$date}");

        $data = $this->model->getByDate( $date );
        $fields = $this->model->getFields();

        $this->view = new e20rMeasurementViews( $date, $data, $fields, $this->unit_type );

        dbg("view_EditProgress() - Views for measurements are loaded");

        ob_start();

        echo $this->view->startProgressForm();
        dbg("Start of measurement form generated.");

        echo $this->view->showChangeBirthDate();

        dbg("Birth date portion of measurement form generated.");

        echo $this->view->showWeightRow( $this->measurementDate );

        dbg("Weight info for form generated.");
        $this->load_girthTypes();
        dbg("Girth Count: " . count($this->girths));

        echo $this->view->showGirthRow( $this->girths, $this->measurementDate );
        dbg("Girth Row generated");

        echo $this->view->showPhotoRow( $this->measurementDate, $this->requestPhotos() );
        dbg("Photo Row generated");

        echo $this->view->showOtherIndicatorsRow( $this->measurementDate, $this->requestPhotos() );
        dbg("Other Indicators row generated");

        echo $this->view->showProgressQuestionRow( $this->measurementDate, $this->requestPhotos() );
        dbg("Progress Questionnaire row generated");

        /*
        foreach ( $items as $key => $list ) {

            if ( is_array( $list ) ) {

                echo $this->view->generateMeasurementHelp( $count, $key );
                echo $this->view->createBlockTable( $list, $key, $count );
                echo $this->view->generateMeasurementEnd();
            }
            elseif ( ( ! is_array( $list ) ) && ( strtolower($list) != 'photos' ) ) {

                echo $this->view->generateMeasurementHelp( $count, $list );
                echo $this->view->createInputBlock( $list, $count );
                echo $this->view->generateMeasurementEnd();
            }
            elseif ( ( strtolower( $list ) == 'photos' ) && ( $this->requestPhotos() ) ) {

                echo $this->view->createPhotoBlock( $list, $count );
                echo $this->view->generateMeasurementEnd();
            }

            if ( ( strtolower( $list ) != 'photos' ) && ! $this->requestPhotos() )  {
                $count++;
            }

        } // End of foreach()
        */
        echo $this->view->endProgressForm();

        $html = ob_get_clean();

        return $html;
    }

    private function requestPhotos() {

        // Using the startdate for the current user + whether the current delay falls on a Saturday (and it's a "Photo" day - every 4 weeks starting the 2nd week of the program )x
        // Return true.
        return false;
    }

    /***********************************************************
     * Any and all ajax callback functions
     **********************************************************/

    /**
     * Weekly progress form submission/save
     */
    public function saveProgressForm_callback() {

        dbg("saveProgressForm() - Checking access");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("saveProgressForm() - Access approved");

        echo 'A-OK';
        exit;
    }

    public function checkProgressFormCompletion_callback() {

        dbg("checkProgressFormCompletion_callback() - Checking access");

        check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("checkProgressFormCompletion_callback() - Access approved");

        echo 1;
        exit;
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
     * @param $startDate -- First date
     * @param $endDate -- End date
     * @param $weekdayNumber -- Day of the week (0 = Sun, 6 = Sat)
     *
     * @return array -- Array of days for the measurement(s).
     */
    private function datesForMeasurements( $startDate, $endDate, $weekdayNumber ) {

        dbg("datesForMeasurements(): {$startDate}, {$endDate}, {$weekdayNumber}");

        $startDate = strtotime($startDate . ' 00:00:00 ' . get_option('timezone_string') );
        $endDate = strtotime($endDate . ' 00:00:00 ' . get_option('timezone_string') );

        dbg("datesForMeasurements() - timestamps: {$startDate}, {$endDate}, {$weekdayNumber}");

        $dateArr = array();

        do {
            if( date( "w", $startDate ) != $weekdayNumber ) {

                $startDate = strtotime("-1 day", $startDate); // add 1 day
            }

        } while( date( "w", $startDate ) != $weekdayNumber );


        while ( $startDate >= $endDate ) {

            $dateArr[] = date( 'Y-m-d', $startDate );
            $startDate = strtotime("-1 week", $startDate); // add 7 days
            dbg('StartDate is now: ' . $startDate );
        }

        return($dateArr);
    }

    /**
     *
     */
    private function loadPageMeasurementInfo( $user_id ) {
        ?>
        <script type="text/javascript">
        var TRACK_ANALYTICS = 1;
        var CPUSER = {
            "id":"12798",
            "level":"0",
            "userid":"13660",
            "lastlogin":"2014-10-31 15:00:44",
            "regstatus":"current",
            "cp_program_id":"36",
            "type":"0",
            "coach":"245308",
            "gender":"M",
            "score":"0",
            "bio":null,
            "goal":null,
            "quote":null,
            "coachingnotes":"CgowOSBKYW51YXJ5IDIwMTQKClNlbnQgZW1haWw6ICdGcm9tIENvYWNoIEpheSAtIEdldHRpbmcgdG8ga25vdyB5b3UuICcKCjExIEphbnVhcnkgMjAxNAoKU2VudCBlbWFpbDogJ0Zyb20gSmF5IC0gWW91ciB3ZWVrZW5kIHN0cmF0ZWd5LiAnCgoxMyBKYW51YXJ5IDIwMTQKClNlbnQgZW1haWw6ICdGcm9tIEpheSAtIEhvdyB3ZSdsbCBrZWVwIGluIHRvdWNoLiAnCgoxNiBKYW51YXJ5IDIwMTQKClNlbnQgZW1haWw6ICdGcm9tIEpheSAtIENvbnRpbnVpbmcgdG8gY29ubmVjdCcKCjIwIEphbnVhcnkgMjAxNAoKU2VudCBlbWFpbDogJ0ltcG9ydGFudDogRG8gbm90IG9wZW4sIGxpbmsgdG8gRG9jdW1lbnQnCgowMSBGZWJydWFyeSAyMDE0CgpTZW50IGVtYWlsOiAnVGVhbSBGYWNlYm9vayBQYWdlICcKCjA0IEZlYnJ1YXJ5IDIwMTQKClNlbnQgZW1haWw6ICdHb29kIFJlYWRzOiBUb3AgNSBUaHJlYWRzICcKCjIzIEZlYnJ1YXJ5IDIwMTQKClNlbnQgZW1haWw6ICdUb3AgVGhyZWFkcycKCjAyIE1hcmNoIDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAvIFN1cGVyc2hha2UgVGlwJwoKMjMgTWFyY2ggMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBSZWFkaW5nIChTZWxmLUFjY2VwdGFuY2UpICcKCjMwIE1hcmNoIDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAoMjQtSG91ciBUcmFwOiBSZXRoaW5raW5nIERhaWx5IEhhYml0cykgJwoKMDYgQXByaWwgMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBSZWFkaW5nIChGYXRoZXJzIGFuZCBUaW1lIENydW5jaCkgJwoKMTMgQXByaWwgMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBSZWFkaW5nIChUaGUgQ3JhdmluZyBCcmFpbikgJwoKMjAgQXByaWwgMjAxNAoKU2VudCBlbWFpbDogJ1ZhY2F0aW9uIC8gQ29udGFjdGluZyBtZScKCjI3IEFwcmlsIDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAoTWluZCBPdmVyIE1pbGtzaGFrZSkgJwoKMDUgTWF5IDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgU3VydmV5ICcKCjEyIE1heSAyMDE0CgpTZW50IGVtYWlsOiAnQ29hY2ggVmFjYXRpb24gJwoKMjMgTWF5IDIwMTQKClNlbnQgZW1haWw6ICdXZWVrbHkgdmlkZW8gLSBMZWFybmluZyBmcm9tIHlvdXIgXCJzbGlwLXVwc1wiIGFuZCBcInNldGJhY2tzXCInCgozMCBNYXkgMjAxNAoKU2VudCBlbWFpbDogJ1ZpZGVvIG9uIHlvdXIgbmV3IGhhYml0IGFuZCBob3cgdG8gdGFyZ2V0IGJlbGx5IGZhdCcKCjAyIEp1bmUgMjAxNAoKU2VudCBlbWFpbDogJ1ZpZGVvIGFuZCBibG9nIHBvc3QgLSBXaGF0IGlzIFlPVVIgaWRlYWwgc3F1YXQgcGF0dGVybj8nCgowNiBKdW5lIDIwMTQKClNlbnQgZW1haWw6ICdNeSBhdmFpbGFiaWxpdHkgbmV4dCB3ZWVrIEFORCBhIHZJZGVvIG9uIFwiSG93IExvdyBTaG91bGQgWW91IFNxdWF0P1wiJwoKMDggSnVuZSAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKEV4dHJhcG9sYXRpb24pJwoKMjIgSnVuZSAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKFNsb3cgQ2hhbmdlKScKCjI5IEp1bmUgMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBWaWV3aW5nIChZb3VyIEZ1dHVyZSBTZWxmKScKCjA2IEp1bHkgMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBSZWFkaW5nIChPdmVyY29tcGVuc2F0aW9uKSAnCgoxMyBKdWx5IDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAoTW90aXZhdGlvbiBhbmQgUml0dWFscykgLyBQaG90byB1cGRhdGUgJwoKMjAgSnVseSAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKFBhc3N3b3JkIE1lc3NhZ2VzKSAvIFNob3J0IFZhY2F0aW9uJwoKMjMgSnVseSAyMDE0CgpTZW50IGVtYWlsOiAnV2VkbmVzZGF5IEVtYWlscyAnCgoyNyBKdWx5IDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAvIEVtYWlscyAnCgoxMCBBdWd1c3QgMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBSZWFkaW5nIChNeXN0ZXJpb3VzIEh1bmdlcikgJwoKMTcgQXVndXN0IDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgVmlld2luZyAoUHJlc2VudCB2cy4gRnV0dXJlIFNlbGYpJwoKMjQgQXVndXN0IDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAoU2VsZiBBd2FyZW5lc3MpICcKCjMxIEF1Z3VzdCAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFZpZXdpbmcgKE5lYXIgV2lucykgJwoKMDcgU2VwdGVtYmVyIDIwMTQKClNlbnQgZW1haWw6ICdTdW5kYXkgUmVhZGluZyAoV29yay1MaWZlIEJhbGFuY2UpICcKCjE0IFNlcHRlbWJlciAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKE5hbWUgb3VyIEZlYXIpICcKCjIxIFNlcHRlbWJlciAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKEVudmlyb25tZW50IGFuZCBIYWJpdHMpICcKCjI4IFNlcHRlbWJlciAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKEdsb3NzaW5nIE92ZXIgVGhlIEdvb2QpICcKCjA1IE9jdG9iZXIgMjAxNAoKU2VudCBlbWFpbDogJ1N1bmRheSBSZWFkaW5nICgiTXVzdCIgbGlzdHMpJwoKMTIgT2N0b2JlciAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKCJJIGFtIiB2cy4gIkkgZmVlbCIpJwoKMTkgT2N0b2JlciAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFJlYWRpbmcgKENvbW1vbiBIdW1hbml0eSkgJwoKMjYgT2N0b2JlciAyMDE0CgpTZW50IGVtYWlsOiAnU3VuZGF5IFEmQSAoU2VsZi1Db21wYXNzaW9uKSAn",
            "iscoach":"0",
            "lastcoachreview":"2014-10-29",
            "lastcoachcontact":"2014-10-29",
            "fullname":"Thomas Sjolshagen",
            "streetaddress":"12a Ryan Rd",
            "streetaddress2":"",
            "city":"Goffstown",
            "stateprov":"NH",
            "country":"United States",
            "postalcode":"03045",
            "age":"43",
            "height":"178",
            "waistcircumference":"0",
            "weight":"178",
            "end_weight":"0",
            "primarygoal":null,
            "secondarygoal":null,
            "primarysport":null,
            "secondarysport":null,
            "fitnessexperience":null,
            "whyworkwithus":null,
            "yesno_exercise":"0",
            "yesno_havegym":"0",
            "yesno_hirecoach":"0",
            "yesno_mealpreptime":"0",
            "yesno_dowhatwesay":"0",
            "yesno_workhard":"0",
            "yesno_giveussixmonths":"0",
            "yesno_provideprogress":"0",
            "yesno_providephotos":"0",
            "yesno_completetasks":"0",
            "frontpicname":null,
            "sidepicname":null,
            "backpicname":null,
            "yesno_imageconsent":"0",
            "yesno_medicalrelease":"0",
            "yesno_firstcohort":"0",
            "yesno_blockoutface":"0",
            "yesno_intakecompleted":"1",
            "yesno_exitsurveycompleted":"0",
            "altprogress":null,
            "emailnotification":"1",
            "notefromcoach":null,
            "program_startdate":"2014-01-13",
            "birthdate":"1971-03-06",
            "lengthunits":"in",
            "weightunits":"lbs",
            "lastvisiteddate":null,
            "on_leaderboard":"0",
            "open_links_in_new_window":"1",
            "active":"1",
            "is_test_account":"0",
            "is_finalist":"0",
            "tempemail":null,
            "encoded_coaching_agreement":"eyJ5ZXNub19pbnRha2Vjb21wbGV0ZWQiOiIxIiwieWVzbm9fZmlyc3Rjb2hvcnQiOiIwIiwiYWN0aW9uIjoiaW50YWtlIiwidmVyc2lvbiI6Imp1bHkyMDEzIiwiZmlyc3RuYW1lIjoiVGhvbWFzIiwibGFzdG5hbWUiOiJTam9sc2hhZ2VuIiwicHJpbWFyeS1waG9uZSI6IjYwMy03ODUtOTc4MCIsImFsdGVybmF0ZS1waG9uZSI6IjYwMy03ODUtOTc4MCIsImVtYWlsLWFkZHJlc3MiOiJ0aG9tYXNAc3Ryb25nY3ViZWRmaXRuZXNzLmNvbSIsInByZWZlcnJlZC1jb250YWN0Ijoic2t5cGUiLCJwcmVmZXJyZWQtY29udGFjdC1vdGhlciI6IiIsInN0cmVldGFkZHJlc3MiOiIxMmEgUnlhbiBSZCIsInN0cmVldGFkZHJlc3MyIjoiIiwiY2l0eSI6IkdvZmZzdG93biIsInN0YXRlcHJvdiI6Ik5IIiwicG9zdGFsY29kZSI6IjAzMDQ1IiwiY291bnRyeSI6IlVuaXRlZCBTdGF0ZXMiLCJlbWVyZ2VuY3ktbmFtZSI6IkNocmlzdGluZSBTam9sc2hhZ2VuIiwiZW1lcmdlbmN5LWVtYWlsIjoiY2hyaXN0aW5lQHNqb2xzaGFnZW4ubmV0IiwiZW1lcmdlbmN5LXBob25lIjoiNjAzLTc4NS01MTA3IiwiYmRfbSI6IjAzIiwiYmRfZCI6IjA2IiwiYmRfeSI6IjE5NzEiLCJldGhuaWMtaGVyaXRhZ2UiOlsiY2F1Y2FzaWFuIl0sImV0aG5pYy1oZXJpdGFnZS1vdGhlciI6IiIsImxlbmd0aHVuaXRzIjoiaW4iLCJoZWlnaHQtZnQiOiI1IiwiaGVpZ2h0LWluIjoiMTAiLCJoZWlnaHQtY20iOjAsIndlaWdodHVuaXRzIjoibGIiLCJ3ZWlnaHQiOiIxNzgiLCJyZWZlcnJlZC15ZXNubyI6Im5vIiwicmVmZXJyZWQtYnkiOiJDaHJpc3RpbmUgU2pvbHNoYWdlbiIsImZvdW5kLXBuIjoib3RoZXIiLCJmb3VuZC1wbi1vdGhlciI6IlZpYSBwZXJzb25hbCB0cmFpbmVyIGJhY2sgaW4gMjAwNShpc2gpIiwib3RoZXItY29uc2lkZXJhdGlvbnMiOiJOb3Qgd2VpZ2hpbmcgb3RoZXIgb3B0aW9ucy4iLCJnYWlud2VpZ2h0LXByaW9yaXR5IjoiNyIsImxvc2V3ZWlnaHQtcHJpb3JpdHkiOiI5IiwibG9va2JldHRlci1wcmlvcml0eSI6IjUiLCJjb25zaXN0ZW5jeS1wcmlvcml0eSI6IjEwIiwiZW5lcmd5LXByaW9yaXR5IjoiNiIsIm9mZm1lZHMtcHJpb3JpdHkiOiIxIiwiZm9vZGNvbnRyb2wtcHJpb3JpdHkiOiIxIiwiaGVhbHRoeXdlaWdodC1wcmlvcml0eSI6IjQiLCJnZXRzdHJvbmdlci1wcmlvcml0eSI6IjciLCJwaHlzaXF1ZS1wcmlvcml0eSI6IjEiLCJwZXJmb3JtYW5jZS1wcmlvcml0eSI6IjkiLCJhZGRpdGlvbmFsLWdvYWxzIjoiQmV0dGVyIHVuZGVyc3RhbmQgbXkgb3duIG1vdGl2YXRpb24gYW5kIGhvdyB0byBtYWludGFpbiBhIGhpZ2ggZGVncmVlIG9mIGl0LiAiLCJnb2FsLW1ldHJpY3MiOiJJJ20gYmFjayBhdCBiZWluZyBvbmUgb2YgdGhlIGZhc3RlciBndXlzIG9uIHRoZSBzb2NjZXIgZmllbGQuIFRoYXQncyB0eXBpY2FsbHkgYXQgYXJvdW5kIDE2MGxicywgdGhvdWdoIEkgdGhpbmsgSSBjb3VsZCBiZSBtYXJnaW5hbGx5IGZhc3RlciBpZiBJIGdvdCBkb3duIGJlbG93IDE2MGxicyBhbmQgYmFjayBpbnRvIHNpbmdsZSBkaWdpdCBib2R5IGZhdCAlIHBsdXMgZ2FpbmVkIGEgbGl0dGxlIG1vcmUgbGVhbiBtdXNjbGUgbWFzcy5cclxuIiwiZ29hbC1yZXdhcmRzIjoiTm90IHJlYWxseSB0aG91Z2h0IGFib3V0IGFuIGV4cGxpY2l0IHJld2FyZCBcImV2ZW50XCIuIFR5cGljYWxseSwgSSB2aWV3IG15IHdlZWtseSBzb2NjZXIgZ2FtZXMgKGFuZCBwbGF5aW5nIHdlbGwgaW4gdGhlbSkgYXMgYSAgcmV3YXJkIGZvciB0aGUgYW1vdW50IG9mIHdvcmsgSSBwdXQgaW50byBzdGF5aW5nIGZpdCAmIGhlYWx0aHkgdGhyb3VnaCB0aGUgd2Vlay5cclxuU2FtZSBhcHBsaWVzIHRvIHJ1bm5pbmcuIFRoZSByYWNlIGl0c2VsZiBpcyAod2FzKSB0aGUgcmV3YXJkLiIsImV4ZXJjaXNlLXJlZ3VsYXJseSI6InllcyIsImV4ZXJjaXNlLWZyZXF1ZW5jeSI6IjMtNSIsImV4ZXJjaXNlLXR5cGVzIjpbImVuZHVyYW5jZSIsInN0cmVuZ3RoIiwibWV0YWJvbGljIiwib3JnYW5pemVkIl0sImV4ZXJjaXNlLXR5cGVzLW90aGVyIjoiIiwiZXhlcmNpc2UtbGV2ZWwiOiJhZHZhbmNlZCIsInNwb3J0cy15ZXNubyI6InllcyIsInNwb3J0cy1kZXRhaWxzIjoiVHJhY2sgJiBGaWVsZCAtIGFnZSAxMiB0byAxNlxyXG5CYWRtaW50b24gLSBhZ2UgMTYgLSAxOFxyXG5Tb2NjZXIgLSBhZ2UgNyAtIDEyIHBsdXMgMzIgdG8gNDJcclxuIiwicHJlZmVycmVkLWFjdGl2aXR5IjoiU29jY2VyXHJcblJ1bm5pbmcgKGJ1dCBhbSBoYXZpbmcgYSB2ZXJ5IGhhcmQgdGltZSBnZXR0aW5nIHJlLW1vdGl2YXRlZCBhZnRlciBJIGNvbXBsZXRlZCB0aGUgRGlzbmV5IEdvb2Z5IENoYWxsZW5nZSBpbiBKYW4gb2YgMjAxMykuXHJcbkNpcmN1aXQgdHJhaW5pbmcgd1wvZnJpZW5kc1xyXG4iLCJleGVyY2lzZS1jaGFsbGVuZ2UiOiJSaWdodCBub3cgaXQncyBcInNpbXBseVwiIG1vdGl2YXRpb24gYW5kIHZhcmlhdGlvbi4iLCJjaHJvbmljLXBhaW4teWVzbm8iOiJubyIsImNocm9uaWMtcGFpbi1kZXRhaWxzIjoiIiwiaW5qdXJpZXMteWVzbm8iOiJubyIsImluanVyaWVzLW90aGVyIjoiIiwiaW5qdXJpZXMtZGV0YWlscyI6IiIsImJpZ2dlc3QtY2hhbGxlbmdlIjoiSSBkaXNsaWtlIHBsYW5uaW5nIHNvIHdoZW4gSSBnZXQgdXAgaW4gdGhlIG1vcm5pbmcsIEknZCByYXRoZXIgbm90IHRoaW5rIGFib3V0IHdoYXQgdG8gbWFrZSBteXNlbGYgZm9yIGZvb2QuIEFkZCB0byB0aGF0IGEgNDowMGFtIHdha2V1cCB0byBnbyBpbnRvIG91ciBmaXRuZXNzIHN0dWRpbyBldmVyeSBNXC9XXC9GIHBsdXMgVHVlc1wvVGh1cnMgYXQgYSBjb3Jwb3JhdGUgZ2lnIEkndmUgbm90IGJlZW4gYWxsIHRoYXQgaGFwcHkgd2l0aCAoY2hhbmdpbmcgaW4gRmViIHNvIHRoYXQgZXhjdXNlIGlzIG9mZiB0aGUgdGFibGUgc29vbikgYW5kIGl0IGJlY29tZXMgX3dheV8gdG9vIGVhc3kgdG8gc3dpbmcgYnkgRHVua2luIERvbnV0cyBvbiB0aGUgd2F5IGludG8gdGhlIHN0dWRpby4gSSBhbHNvIHJlYWxseSBsaWtlIG15IHNpbXBsZSBjYXJib2h5ZHJhdGVzLCBhIF9sb3RfIVxyXG5cclxuSSdtIHZlcnkgbXVjaCBhIFwicHJvamVjdCBiYXNlZFwiIHBlcnNvbmFsaXR5IChha2EgYSB0cnVlIG5lcmQpLiBJIGNhbiBiZSB2ZXJ5IGZvY3VzZWQgYW5kIHZlcnkgZGV2b3RlZCB0byBhIGZvb2RcL21lYWwgXCJwbGFuXCIgZm9yIGEgd2hpbGUsIHRoZW4gSSBnZXQgYm9yZWQgYW5kIGdvIGZvciB0aGUgZWFzeSBvcHRpb25zLlxyXG5cclxuSSdtIGFsc28gYSBob3JyaWJseSB1bmNyZWF0aXZlIGNvb2sgKEknbSBjdXJyZW50bHkgaW5jYXBhYmxlIG9mIGxvb2tpbmcgYXQgYSBsaXN0IG9mIGluZ3JlZGllbnRzIGFuZCBzZWUgc29tZXRoaW5nIEkgY291bGQgbWFrZSBmcm9tIGl0KSB3aGljaCBtYWtlcyBpdCBkaWZmaWN1bHQgdG8gZmVlbCBsaWtlIHNwZW5kaW5nIHRpbWUgY29taW5nIHVwIHdpdGggc29tZXRoaW5nLiBJIGFtLCBob3dldmVyLCBkZWNlbnQgYXQgZm9sbG93aW5nIHJlY2lwZXMuIFdlIGdvdCB0aGUgR291cm1ldCBOdXRyaXRpb24gY29va2Jvb2sgYW5kIGZvciBwZXJpb2RzIG9mIHRpbWUsIEkndmUgaGFkIHN1Y2Nlc3MgZWF0aW5nIGZyb20gaXQuIEJ1dCwgYWdhaW4sIEkgZ2V0IGJvcmVkIGZhaXJseSBxdWlja2x5IGFuZCB3YW50IHNvbWV0aGluZyBkaWZmZXJlbnQuICIsImdyb2NlcmllcyI6WyJvdGhlciJdLCJncm9jZXJpZXMtb3RoZXIiOiJNeSB3aWZlIiwiY29va2luZyI6WyJvdGhlciJdLCJjb29raW5nLW90aGVyIjoiTXkgd2lmZSAobW9zdGx5LiBPY2Nhc2lvbmFsbHkgSSdsbCBjb29rIHNvbWV0aGluZykiLCJzaGFyZXMtbWVhbHMiOlsicGFydG5lciJdLCJob21lLW1lYWxzIjoiMy00IiwiZWF0LW91dC1tZWFscyI6IjEtMiIsInNwZWNpYWwtZGlldC15ZXNubyI6Im5vIiwic3BlY2lhbC1kaWV0LW90aGVyIjoiIiwic3BlY2lhbC1kaWV0LWxlbmd0aCI6IiIsImZvb2QtYWxsZXJnaWVzLXllc25vIjoibm8iLCJmb29kLWFsbGVyZ2llcy1vdGhlciI6IiIsImZvb2Qtc2Vuc2l0aXZpdGllcy15ZXNubyI6InllcyIsImZvb2Qtc2Vuc2l0aXZpdGllcyI6WyJtaWxrIl0sImZvb2Qtc2Vuc2l0aXZpdGllcy1vdGhlciI6IiIsInN1cHBsZW1lbnRzLXllc25vIjoieWVzIiwic3VwcGxlbWVudHMiOlsiZmlzaC1vaWwiXSwidml0YW1pbnMtb3RoZXIiOiIiLCJzdXBwbGVtZW50cy1vdGhlciI6IiIsIndhdGVyLWludGFrZSI6IjMtNSIsInByb3RlaW4taW50YWtlIjoiMi0zIiwidmVnZXRhYmxlLWludGFrZSI6IjItMyIsIm51dHJpdGlvbi1rbm93bGVkZ2UiOiI5IiwibWVkaWNhbC1pc3N1ZXMteWVzbm8iOiJ5ZXMiLCJtZWRpY2FsLWlzc3VlcyI6Ik1pbGQgKHZlcnkgbWlsZCkgZXhlcmNpc2UgaW5kdWNlZCBhc3RobWEiLCJtZWRpY2F0aW9ucy15ZXNubyI6Im5vIiwibWVkaWNhdGlvbnMtb3RoZXIiOiIiLCJvdGhlci10cmVhdG1lbnRzLXllc25vIjoibm8iLCJvdGhlci10cmVhdG1lbnRzIjoiIiwiZW1wbG95ZWQteWVzbm8iOiJ5ZXMiLCJ3b3JrLWRldGFpbHMiOiJGaXRuZXNzIEJ1c2luZXNzIChvd25lZCB3aXRoIHdpZmUpLCBXZWIgZGV2ZWxvcGVyIGFuZCBhIChub3cgc2hvcnQgdGVybSkgcGFydCB0aW1lIEVuZ2luZWVyaW5nIFByb2dyYW0gTWFuYWdlciBmb3IgRm9ydHVuZSAxMDAgSVQgY29tcGFueSIsIndvcmstc2hpZnRzIjoiZGF5dGltZSIsIndvcmstaG91cnMiOiI4LTEwIiwid29yay1hY3Rpdml0eSI6Im1vZGVyYXRlIiwid29yay1zdHJlc3MiOiJtb2RlcmF0ZSIsIndvcmstdHJhdmVsIjoicmFyZWx5Iiwic3R1ZGVudC15ZXNubyI6Im5vIiwic3R1ZGVudC1kZXRhaWxzIjoiIiwiY2FyZWdpdmVyLXllc25vIjoibm8iLCJyZWxhdGlvbnNoaXAteWVzbm8iOiJ5ZXMiLCJyZWxhdGlvbnNoaXAtcGFydG5lciI6IkNocmlzdGluZSIsImNoaWxkcmVuLXllc25vIjoibm8iLCJjaGlsZHJlbi1udW1iZXIiOiIiLCJjaGlsZHJlbi1uYW1lcy1hZ2VzIjoiIiwicGV0cy15ZXNubyI6InllcyIsInBldHMtbnVtYmVyIjoiMSIsInBldHMtbmFtZXMtdHlwZXMiOiJGZW1hbGUgY2F0IG5hbWVkIEJhcnJvbiAiLCJob21lLXN0cmVzcyI6ImxvdyIsImNvcGluZy1zdHJlc3MiOiJtb2RlcmF0ZSIsInZhY2F0aW9uLWZyZXF1ZW5jeSI6InJhcmVseSIsImhvYmJpZXMiOiJIb21lIGF1dG9tYXRpb24gJiBwcm9ncmFtbWluZ1xyXG5SZXNlYXJjaGluZyBuZXcgdGVjaG5vbG9neSwgcHJvZ3JhbW1pbmdcclxuUHJvY2VzcyBhbmFseXNpcyAmIGltcHJvdmVtZW50XHJcbldyaXRpbmcgKGludGVybWl0dGVudGx5KVxyXG5TcGVuZGluZyB0aW1lIHdpdGggbXkgd2lmZVxyXG5SZWFkaW5nXHJcbkRpbmluZyBvdXRcclxuXHJcbiIsImFsY29ob2wtZnJlcXVlbmN5IjoicmVndWxhcmx5IiwiZHJ1Z3MtZnJlcXVlbmN5IjoicmFyZWx5Iiwic21va2luZy1mcmVxdWVuY3kiOiJuZXZlciIsImV4cGVjdGF0aW9ucy1wcm9ncmFtIjoiTGVhcm4gbW9yZSBhYm91dCBtb3RpdmF0aW9uICYgbWFpbnRhaW5pbmcgaXRcclxuSW1wcm92ZSBteSB1bmRlcnN0YW5kaW5nIG9mIHRoZSBQcmVjaXNpb24gTnV0cml0aW9uIGNvYWNoaW5nIG1ldGhvZHNcclxuIiwiZXhwZWN0YXRpb25zLWNvYWNoIjoiQ29uc2lzdGVudCBjb21tdW5pY2F0aW9uc1xyXG5LZWVwIG1lIGVuZ2FnZWQgYW5kIGludGVyZXN0ZWQgc28gZG9uJ3QgbGV0IG1lIGdldCBib3JlZC4uLiAoc29ycnksIG5vdCBhIHRocmVhdCwganVzdCA0MCsgeWVhcnMgb2YgbGVhcm5pbmcgd2hhdCBtYWtlcyBtZSB0aWNrKVxyXG4iLCJvdGhlciI6IlNpbmNlIG15IGJhY2tncm91bmQgaXMgYnVzaW5lc3MgJiBlbmdpbmVlcmluZywgSSdtIHZlcnkgbXVjaCBhIHNjaWVuY2UgYW5kIGRhdGEgZHJpdmVuIGluZGl2aWR1YWwuIE5vIHBoeXNpY2FsIGNoYWxsZW5nZSBpcyBcIm9mZiBsaW1pdHNcIiwgYW5kIEkndmUgYmVlbiBrbm93biB0byBiZSB3aWxsaW5nIHRvIHRyeSAocmVhbGx5KSBcIm9kZFwiIHRoaW5ncyBpZiBpdCBicmVha3MgdGhlIHJvdXRpbmUuIFRoaXMgYWxzbyBtZWFucyBJJ20gYWJsZSB0byAoYW5kIGRvKSBhcmd1ZSBjb3VudGVyLXBvaW50cyBpZiBJJ20gbm90IGNvbnZpbmNlZC4gTXkgd2lmZSBjYWxscyBtZSAobG92aW5nbHksIEkgdGhpbmsuLi4pIGFyZ3VtZW50YXRpdmUgYXQgdGltZXMuIEknbSBhbHNvIGEgY2VydGlmaWVkIHBlcnNvbmFsIHRyYWluZXIgKGFuZCBoYXZlIGEgZ3JvdXAgdHJhaW5pbmcgY2VydCkiLCJ5ZXNub19pbWFnZWNvbnNlbnQiOiIxIiwicmVzZWFyY2gteWVzbm8iOiIxIiwibWVkaWNhbHJlbGVhc2UiOiIxIiwic3VibWl0IjoiRmluYWxpemUgYW5kIFNlbmQhIiwiYWpheCI6MH0=",
            "sms_number":null,
            "opt_out_date":null,
            "exit_survey":null,
            "contact_frequency":null,
            "startdate":"2014-01-13",
            "username":"sjolshagen",
            "email":"thomas@strongcubedfitness.com",
            "firstname":"Thomas",
            "lastname":"Sjolshagen"
        };
        var IS_COMPLETED = 0;
        var DISPLAY_BIRTHDATE = 0;
            var PROGRESS_PHOTO_DIRECTORY = 'cp-pics/';
        var LAST_WEEK_MEASUREMENTS = {
                'weight': { 'value':  157.6, 'units' => 'lbs' },
                'girth_neck': { 'value': 14 , 'units': 'in' },
                'girth_shoulder': { 'value': 43 , 'units': 'in' },
                'girth_chest': { 'value': 36.3 , 'units': 'in' },
                'girth_upperarm': { 'value': 11.5 , 'units': 'in' },
                'girth_waist': { 'value': 30.5 , 'units': 'in' },
                'girth_hip': { 'value': 37.5 , 'units': 'in' },
                'girth_thigh': { 'value': null , 'units': 'in' },
                'girth_calf': { 'value': 14.1 , 'units': 'in' },
                'skinfold_triceps': { 'value': null , 'units': 'mm' },
                'skinfold_chest': { 'value': null , 'units': 'mm' },
                'skinfold_midaxillary': { 'value': null , 'units': 'mm' },
                'skinfold_subscapular': { 'value': null , 'units': 'mm' },
                'skinfold_suprailiac': { 'value': null , 'units': 'mm' },
                'skinfold_thigh': { 'value': null , 'units': 'mm' },
                'skinfold_abdominal': { 'value': null , 'units': 'mm' }
                };
        var PROGDATE = '20141101';
        var ASSIGNMENT_ID = 6541166;
    </script>
    <?php
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

    /*
    // private function getUserSetting( $setting, $user_id = null ) {
    private function userInfo( $setting, $user_id = null ) {
        global $wpdb;

        $value = get_user_meta( $this->user_info->id,  "{$wpdb->prefix}e20r-tracker-{$setting}", true);

        if ( empty( $value ) ) {

            switch ($setting) {

                case 'unit-type';
                    $value = new stdClass();
                    $value->weigth = 'lbs (pounds)';
                    $value->length = 'inches (in)';
            }
        }

        return $value;

    }
*/
    /**
     * @param $setting
     */
    /*
    private function setUserSettings( $setting ) {

    }
    */
} 