<?php

/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 10/22/14
 * Time: 1:16 PM
 */

class e20rClient
{

    private $id;
    private $model = null; // Client Model.
    private $view = null; // Client Views.

    private $weightunits;
    private $lengthunits;

    public $client_loaded = false;
    public $actionsLoaded = false;
    public $scriptsLoaded = false;

    /**
     * e20rClient constructor.
     * @param integer|null $user_id
     */
    public function __construct($user_id = null)
    {

        global $currentClient;

        $this->model = new e20rClientModel();
        $this->view = new e20rClientViews($user_id);

        if ($user_id !== null) {

            $currentClient->user_id = $user_id;
        }

    }

    public function init()
    {

        global $e20rTracker;
        global $currentClient;

        if (empty($currentClient->user_id)) {

            global $current_user;
            // $this->id = $current_user->ID;
            $currentClient->user_id = $current_user->ID;
        }

        dbg('e20rClient::init() - Running INIT for e20rClient Controller: ' . $e20rTracker->whoCalledMe());

        if ($this->client_loaded !== true) {

            $this->model->setUser($currentClient->user_id);
            $this->model->load_basic_clientdata($currentClient->user_id);
            $this->client_loaded = true;
        }

    }

    public function record_login($user_login)
    {
        $user = get_user_by('login', $user_login);

        dbg("e20rClient::record_login() - Check that {$user_login} has a valid role...");
        $this->check_role_setting( $user->ID );

        if ( $user->ID != 0 ) {
            dbg("e20rClient::record_login() - Saving login information about {$user_login}");
            update_user_meta($user->ID, '_e20r-tracker-last-login', current_time('timestamp'));
        }
    }

    public function isNourishClient($user_id = 0)
    {
        __return_false();
        /*
                global $e20r_isClient;

                if ( ! is_user_logged_in() ) {

                    $e20r_isClient = false;
                    return $e20r_isClient;
                }

                if ( is_null( $e20r_isClient ) ) {

                    $e20r_isClient = false;

                    /*
                    if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {

                        dbg("e20rClient::isNourishClient() - Checking against Paid Memberships Pro");
                        // Fetch this from an option (multi-select on plugin settings page)
                        $nourish_levels = array( 16, 21, 22, 23, 18 );

                        if ( pmpro_hasMembershipLevel( $nourish_levels, $user_id ) ) {

                            dbg("e20rClient::isNourishClient() - User with id {$user_id} has a Nourish Coaching membership");
                            $e20r_isClient = true;
                        }
                    }

                }

                dbg("e20rClient::isNourishClient() - Is user with id {$user_id} a nourish client? " . ( $e20r_isClient ? 'Yes' : 'No'));
                return $e20r_isClient;
        */
    }

    public function clientId()
    {

        global $currentClient;

        return $currentClient->user_id;
    }

    public function getLengthUnit()
    {

        global $currentClient;

        return $this->model->get_data($currentClient->user_id, 'lengthunits');
    }

    public function getWeightUnit()
    {

        global $currentClient;

        return $this->model->get_data($currentClient->user_id, 'weightunits');
    }

    public function getBirthdate($user_id)
    {

        return $this->model->get_data($user_id, 'birthdate');
    }

    public function getUploadPath($user_id)
    {

        return $this->model->get_data($user_id, 'program_photo_dir');
    }

    public function getUserImgUrl($who, $when, $imageSide)
    {

        global $e20rTables;

        if ($this->isNourishClient($who) && $e20rTables->isBetaClient()) {

            return $this->model->getBetaUserUrl($who, $when, $imageSide);
        }

        return false;
    }

    public function setClient($userId)
    {

        $this->client_loaded = false;
        $this->model->setUser($userId);
        $this->init();
    }

    public function get_data($clientId, $private = false, $basic = false)
    {

        if (!$this->client_loaded) {

            dbg("e20rClient::get_data() - No client data loaded yet...");
            $this->setClient($clientId);
            // $this->get_data( $clientId );
        }

        if (true === $basic) {
            dbg("e20rClient::get_data() - Loading basic client data - not full survey.");
            $data = $this->model->load_basic_clientdata($clientId);
        } else {
            dbg("e20rClient::get_data() - Loading all client data including survey");
            $data = $this->model->get_data($clientId);
        }

        if (true === $private) {

            dbg("e20rClient::get_data() - Removing private data");
            $data = $this->strip_private_data($data);
        }

        dbg("e20rClient::get_data() - Returned data for {$clientId} from client_info table:");
        // dbg($data);

        return $data;
    }

    private function strip_private_data($cData)
    {

        $birthdateSet = false;
        $genderSet = false;

        $data = $cData;

        $data->display_birthdate = 1;
        $data->incomplete_interview = 1;

        $include = array(
            'id', 'birthdate', 'first_name', 'gender', 'lengthunits', 'weightunits', 'incomplete_interview',
            'program_id', 'progress_photo_dir', 'program_start', 'user_id'
        );

        foreach ($data as $field => $value) {

            if (!in_array($field, $include)) {

                unset($data->{$field});
            }
        }

        if (strtotime($data->birthdate)) {

            $data->display_birthdate = false;
        }

        if (isset($data->first_name) && (isset($data->id)) && isset($data->display_birthdate)) {

            $data->incomplete_interview = false;
        }

        return $data;
    }

    public function getGender()
    {

        global $currentClient;

        return strtolower($this->model->get_data($currentClient->user_id, 'gender'));
    }

    public function get_client_info($client_id)
    {

        return $this->model->load_client_settings($client_id);
    }

    public function completeInterview($userId)
    {

        dbg("e20rClient::completeInterview() - Checking if interview was completed");
        // $data = $this->model->get_data( $userId, 'completed_date');

        if ( !isset( $this->interview_status_loaded ) || ( false === $this->interview_status_loaded )) {

            dbg("e20rClient::completeInterview() - Not previously checked for interview status. Doing so now.");
            $is_complete = $this->model->interview_complete($userId);

            $this->interview_status = $is_complete;
            $this->interview_status_loaded = true;
        }
        else {
            dbg("e20rClient::completeInterview() - Interview status has been checked already. Returning status");
            $is_complete = $this->interview_status;
        }
        // dbg("e20rClient::completeInterview() - completed_date field contains: ");
        // dbg($data);
        dbg("e20rClient::completeInterview() - Returning interview status of: " . ( $is_complete ? 'true' : 'false' ) );
        return (!$is_complete ? false : true);
    }

    public function load_interview($form)
    {

        global $e20rTracker;
        global $currentClient;
        global $current_user;
        global $post;

        dbg("e20rClient::load_interview() - Start: " . $e20rTracker->whoCalledMe());

        // dbg( $form );

        if (stripos($form['cssClass'], 'nourish-interview-identifier') === false) {

            dbg('e20rClient::load_interview()  - Not the Program Interview form: ' . $form['cssClass']);
            return $form;
        }
        else {
            dbg("e20rClient::load_interview() - Processing a Program Interview form");
        }

        if (!is_user_logged_in()) {

            dbg("e20rTracker::load_interview()  - User accessing form without being logged in.");
            return $form;
        }

        /*		if ( ! $e20rTracker->hasAccess( $current_user->ID, $post->ID ) ) {

                    dbg("e20rTracker::load_interview()  - User {$current_user->ID} doesn't have access to this form on {$post->ID}.");
                    return $form;
                }
        */
        dbg("e20rClient::load_interview() - Loading form data: " . count( $form ) . " elements");
        // dbg( "Form: " . print_r( $form, true) );

        dbg("e20rClient::load_interview() Processing form object as to load existing info if needed. ");

        if (!isset($currentClient->user_id) || ($current_user->ID !== $currentClient->user_id)) {

            dbg("e20rClient::load_interview_data_for_client() - Loading interview for user ID {$current_user->ID} (currentClient->user_id is either undefined or different)");
            $this->setClient( $current_user->ID );
        }

        return $this->model->load_interview_data_for_client($current_user->ID, $form);

        // return $form;
    }

    public function loadClientMessages($clientId)
    {

        global $currentClient;
        global $currentProgram;

        if (!isset($currentClient->user_id)) {
            $this->setClient($clientId);
        }

        if (!isset($currentProgram->id)) {
            global $e20rProgram;

            $e20rProgram->getProgramIdForUser($clientId);
        }

        $client_messages = $this->model->load_message_history($clientId);

        return $this->view->viewMessageHistory($clientId, $client_messages);
    }

    private function find_gf_id($content)
    {

        preg_match_all("/\[[^\]]*\]/", $content, $matches);

        foreach ($matches as $k => $match) {

            foreach ($match as $sc) {

                preg_match('/id="(\d*)"/', $sc, $ids);
            }
        }

        return $ids;
    }

    /**
     * Prevents Gravity Form entries from being stored in the database.
     *
     * @global object $wpdb The WP database object.
     * @param array $entry Array of entry data.
     *
     * @return bool - False or nothing if error/nothing to do.
     */
    public function remove_survey_form_entry($entry)
    {

        global $e20rTracker;
        global $current_user;
        global $post;

        global $currentProgram;
        global $currentArticle;

        $ids = array();

        if (has_shortcode($post->post_content, 'gravityform') && ($post->ID == $currentProgram->intake_form)) {

            dbg("e20rClient::remove_survey_form_entry() - Processing on the intake form page with the gravityform shortcode present");
            $ids = $this->find_gf_id($post->post_content);
        }

        if (has_shortcode($post->post_content, 'gravityform') && ($currentArticle->is_survey == 1) && ($currentArticle->post_id == $post->ID)) {

            dbg("e20rClient::remove_survey_form_entry() - Processing on a survey article page with the gravityform shortcode present");
            $ids = $this->find_gf_id($post->post_content);
        }

        if (empty($ids)) {

            dbg("e20rTracker::remove_survey_form_entry()  - This is not the BitBetter Interview form!");
            return;
        }

        if (!is_user_logged_in()) {

            dbg("e20rTracker::remove_survey_form_entry()  - User accessing form without being logged in.");
            return;
        }

        if (!$e20rTracker->hasAccess($current_user->ID, $post->ID)) {

            dbg("e20rTracker::remove_survey_form_entry()  - User doesn't have access to this form.");
            return false;
        }

        global $wpdb;

        // Prepare variables.
        $lead_id = $entry['id'];
        $lead_table = RGFormsModel::get_lead_table_name();
        $lead_notes_table = RGFormsModel::get_lead_notes_table_name();
        $lead_detail_table = RGFormsModel::get_lead_details_table_name();
        $lead_detail_long_table = RGFormsModel::get_lead_details_long_table_name();

        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_detail_long_table");
        // Delete from lead detail long.
        $sql = $wpdb->prepare("DELETE FROM $lead_detail_long_table WHERE lead_detail_id IN(SELECT id FROM $lead_detail_table WHERE lead_id = %d)", $lead_id);
        $wpdb->query($sql);

        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_detail_table");
        // Delete from lead details.
        $sql = $wpdb->prepare("DELETE FROM $lead_detail_table WHERE lead_id = %d", $lead_id);
        $wpdb->query($sql);

        // Delete from lead notes.
        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_notes_table");
        $sql = $wpdb->prepare("DELETE FROM $lead_notes_table WHERE lead_id = %d", $lead_id);
        $wpdb->query($sql);

        // Delete from lead.
        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from the lead_table");
        $sql = $wpdb->prepare("DELETE FROM $lead_table WHERE id = %d", $lead_id);
        $wpdb->query($sql);

        dbg("e20rTracker::remove_survey_form_entry()  - Removing entries from any addons");
        // Finally, ensure everything is deleted (like stuff from Addons).
        GFAPI::delete_entry($lead_id);

    }

    /**
     * @param $entry -- Survey entry (gravity forms entry object)
     * @param $form -- Form object
     * @return bool -- True/False
     */
    public function save_interview($entry, $form)
    {

        global $e20rTracker;
        global $current_user;
        global $post;

        dbg("e20rTracker::save_interview() - Start");

        if (false === stripos($form['cssClass'], 'nourish-interview-identifier')) {

            dbg('e20rTracker::save_interview()  - Not the BitBetter Interview form: ' . $form['cssClass']);
            return false;
        }

        if (!is_user_logged_in()) {

            dbg("e20rTracker::save_interview()  - User accessing form without being logged in.");
            return false;
        }

        if (!$e20rTracker->hasAccess($current_user->ID, $post->ID)) {

            dbg("e20rTracker::save_interview()  - User does NOT have access to this form.");
            return false;
        }

        dbg("e20rTracker::save_interview() - Processing the Bit Better Interview form(s).");

        global $e20rMeasurements;
        global $current_user;
        global $e20rProgram;
        global $e20rTracker;
        global $e20rArticle;
        global $page;

        global $currentProgram;
        global $currentArticle;

        $userId = $current_user->ID;
        $userProgramId = !empty($currentProgram->id) ? $currentProgram->id : $e20rProgram->getProgramIdForUser($userId);
        $userProgramStart = $currentProgram->startdate;
        $eKey = $e20rTracker->getUserKey($userId);
        $surveyArticle = $e20rArticle->findArticles('post_id', $currentProgram->intake_form, $userProgramId );

        // dbg($currentProgram);
        dbg($surveyArticle);

        $db_Data = array(
            'user_id' => $userId,
            'program_id' => $currentProgram->id,
            'page_id' => (isset($page->ID) ? $page->ID : CONST_NULL_ARTICLE),
            // 'article_id' => $currentArticle->id,
            'article_id' => $surveyArticle[0]->id,
            'program_start' => $currentProgram->startdate,
            'user_enc_key' => $eKey,
            'progress_photo_dir' => "e20r_pics/client_{$userProgramId}_{$userId}"
        );

        $fieldList = array('text', 'textarea', 'number', 'email', 'phone');

        dbg("e20rClient::save_interview() - Processing the Welcome Interview form");
        /*        dbg($form['fields']);
                dbg($entry);
        */
        foreach ($form['fields'] as $item) {

            $skip = true;

            dbg("e20rClient::save_interview() - Processing field type: {$item['type']}");

            if ('section' != $item['type']) {

                $fieldName = $item['label'];
                $subm_key = $item['id'];

                if (in_array($item['type'], $fieldList)) {

                    $skip = false;
                }

                if ($item['type'] == 'date') {

                    $skip = true;
                    $db_Data[$fieldName] = date('Y-m-d', strtotime($this->filterResponse($entry[$item['id']])));
                }

                if ($item['type'] == 'checkbox') {

                    $checked = array();

                    foreach ($item['inputs'] as $k => $i) {

                        if (!empty($entry[$i['id']])) {

                            $checked[] = $this->filterResponse($item['choices'][$k]['value']);
                        }
                    }

                    if (!empty($checked)) {

                        $db_Data[$fieldName] = join(';', $checked);

                    }

                    $skip = true;
                }

                if (($fieldName == 'calculated_weight_lbs') && (!empty($entry[$item['id']]))) {

                    dbg("e20rClient::save_interview() - Saving weight as LBS...");
                    $skip = false;
                    $e20rMeasurements->saveMeasurement('weight', $entry[$item['id']], -1, $userProgramId, $userProgramStart, $userId);
                }

                if (($fieldName == 'calculated_weight_kg') && (!empty($entry[$item['id']]))) {

                    dbg("e20rClient::save_interview() - Saving weight as KG");
                    $skip = false;
                    $e20rMeasurements->saveMeasurement('weight', $entry[$item['id']], -1, $userProgramId, $userProgramStart, $userId);
                }

                if ($item['type'] == 'survey') {

                    $key = $entry[$item['id']];
                    $subm_key = $item['id'];

                    if ($item['inputType'] == 'likert') {

                        foreach ($item['choices'] as $k => $i) {

                            foreach ($i as $lk => $val) {

                                if ($entry[$subm_key] == $item['choices'][$k]['value']) {

                                    $entry[$subm_key] = $item['choices'][$k]['score'];
                                    $skip = false;
                                }
                            }
                        }
                    }
                }

                if ($item['type'] == 'address') {

                    dbg("e20rClient::save_interview() - Saving address information: ");
                    // dbg($entry);

                    // $key = $item['id'];

                    foreach ($item['inputs'] as $k => $i) {

                        $key = $i['id'];
                        $val = $entry["{$key}"];

                        $splt = preg_split("/\./", $key);

                        switch ($splt[1]) {
                            case '1':
                                $fieldName = 'address_1';
                                break;

                            case '2':
                                $fieldName = 'address_2';
                                break;

                            case '3':
                                $fieldName = 'address_city';
                                break;

                            case '4':
                                $fieldName = 'address_state';
                                break;

                            case '5':
                                $fieldName = 'address_zip';
                                break;

                            case '6':
                                $fieldName = 'address_country';
                                break;
                        }

                        dbg("e20rClient::save_interview() - Field: {$fieldName}, Item #: {$key} -> Value: {$val}");

                        // dbg($entry[ {$i['id']} ]);


                        if (!empty($val)) {

                            dbg("e20rClient::save_interview() - Saving address item {$fieldName} -> {$val}");
                            $db_Data[$fieldName] = $this->filterResponse($val);
                        }

                    }

                    $skip = true;
                }

                if ($item['type'] == 'multiselect') {

                    dbg("e20rClient::save_interview() - Processing MultiSelect");

                    if (isset($entry[$subm_key])) {

//                        $selections = explode(",", $entry[$subm_key]);
//                        dbg( $selections );

                        dbg("e20rClient::save_interview() - Multiselect - Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}");
                        $db_Data[$fieldName] = $this->filterResponse($entry[$subm_key]);
                    }
                }

                if ($item['type'] == 'select') {

                    if (!empty($entry[$subm_key])) {

                        foreach ($item['choices'] as $k => $v) {

                            dbg("e20rClient::save_interview() - Select item: Field: {$fieldName}, subm_key={$subm_key}, entryVal={$entry[$subm_key]}, item={$item['choices'][$k]['value']}");

                            if ($item['choices'][$k]['value'] == $entry[$subm_key]) {

                                $db_Data[$fieldName] = $this->filterResponse($item['choices'][$k]['value']);
                            }
                        }
                    }
                }

                if ($item['type'] == 'radio') {

                    if (!empty($entry[$subm_key])) {

                        // Handle cases where Yes/No fields have 0/1 values.
                        dbg("e20rClient::save_interview() - Processing numeric radio button value: {$entry[$subm_key]}");
                        /*
                                                if ( in_array( $entry[$subm_key], array( 'Yes', 'No' ) ) ) {

                                                    switch ( $entry[$subm_key] ) {
                                                        case 'No':
                                                            $db_Data[ $fieldName ] = 0;
                                                            break;

                                                        case 'Yes':
                                                            $db_Data[ $fieldName ] = 1;
                                                            break;
                                                    }
                                                    // $data[ $fieldName ] = $this->filterResponse( $entry[$subm_key] );
                                                    $skip = true;
                                                }
                        */
                        foreach ($item['choices'] as $k => $v) {

                            // dbg($item['choices'][$k]);

                            if ($item['choices'][$k]['value'] == $entry[$subm_key]) {

                                dbg("e20rClient::save_interview() - Processing radio button: Value: {$item['choices'][ $k ]['value']}");
                                $db_Data[$fieldName] = $this->filterResponse($item['choices'][$k]['value']);
                                $skip = true;
                            }
                        }
                    }
                }

                if (!$skip) {

                    if (empty($entry[$subm_key])) {

                        continue;
                    }

                    $data = trim($entry[$subm_key]);
                    dbg("e20rClient::save_interview() - Data being stored: .{$data}.");

                    if ('textarea' == $item['type']) {

                        $data = wp_kses_post($data);
                    }

                    // Encrypt the data.
                    $encData = $this->filterResponse($data);
                    $db_Data[$fieldName] = $encData;
                } else {
                    dbg("e20rClient::save_interview() - Skipped field of type: {$item['type']} and with value: " . (isset($entry[$item['id']]) ? $entry[$item['id']] : 'null'));
                }
            }
        } // End of foreach loop for submitted form

        if (WP_DEBUG) {
            dbg("e20rClient::save_interview() - Data to save: ");
            dbg($db_Data);
        }

        dbg("e20rClient::save_interview() - Assigning a coach for this user ");
        $db_Data['coach_id'] = $this->assign_coach($userId, $db_Data['gender']);

        dbg("e20rClient::save_interview() - Configure the exercise level for this user");
        $this->assign_exercise_level( $userId, $db_Data );

        dbg("e20rClient::save_interview() - Saving the client interview data to the DB. ");

        if ($this->model->save_client_interview($db_Data)) {

            dbg("e20rClient::save_interview() - Saved data to the database ");

            dbg("e20rClient::save_interview() - Removing any GF entry data from database.");
            $this->remove_survey_form_entry($entry);

            return true;
        }

        return false;
    }

    /**
     * Validate that the user ID has an exercise level role on the system
     * Set to "beginner" if they don't.
     * 
     * @param       integer         $user_id        The User ID to check
     * @return      true                            
     */
    public function check_role_setting( $user_id ) {

        dbg("e20rClient::check_role_setting() - Make sure {$user_id} has an exercise experience role configured");

        $user = new WP_User($user_id);
        $user_roles = apply_filters('e20r-tracker-configured-roles', array() );

        // assume the user does _NOT_ have one of the expected roles
        $has_role = false;

        foreach( $user_roles as $key => $role ) {

            $has_role = $has_role || in_array( $role['role'], $user->roles );
        }

        dbg("e20rClient::check_role_setting() - {$user_id} DOES have the exercise experience role configured? " . ( $has_role === true ? 'Yes' : 'No') );

        if ( false === $has_role ) {
            dbg("e20rClient::check_role_setting() - Assigning a default role (Beginner) until they complete the Welcome interview" );
            $user->add_role( $user_roles['beginner']['role'] );
            $has_role = true;
        }

        return $has_role;
    }

    /**
     * Automatically propose/recommend an exercise experience level for the user based
     * on survey results.
     *
     * @param integer   $user_id      - The User ID to update the role for
     * @param array     $data         - The survey response(s) given
     */
    public function assign_exercise_level( $user_id, $data ) {

        // can the client even be at the "experienced" level (by default, no).
        $can_be_ex = false;
        $user_roles = apply_filters('e20r-tracker-configured-roles', array() );
        
        dbg($data);

        switch ( $data['exercise_level'] ) {
            case 'complete-beginner':
            case 'some-experience':
                dbg("e20rClient::assign_exercise_level() -  Self-reported as inexperienced exerciser");
                $el_score = 1;
                break;

            case 'comfortable':
                dbg("e20rClient::assign_exercise_level() -  Self-reported as intermediate exerciser");
                $el_score = 2;
                break;

            case 'very-experienced':
            case 'advanced':
                dbg("e20rClient::assign_exercise_level() -  Self-reported as experienced exerciser");
                $el_score = 3;
                break;
        }

        // Lower the user's exercise level score if they're injured
        if ( 'yes' === strtolower($data['limiting_injuries']) ) {

            dbg("e20rClient::assign_exercise_level() -  Lowering exercise level score due to injury.");

            if ( 1 === $el_score ) {
                $el_score = 1;
            }
            if ( 2 === $el_score) {
                $el_score = 1;
            }
            if ( 3 === $el_score) {
                $el_score = 2;
            }
        }

        switch( $data['exercise_hours_per_week']) {

            case '1-3': // 1-3 hours/week
                $hw_score = 1;
                $can_be_ex = false;
                break;

            case '3-5': // 3-5 hours
                $hw_score = 2;
                $can_be_ex = false;
                break;

            case '5-7':
            case '7+':
                $hw_score = 3;
                if (3 === $el_score) {
                    $can_be_ex = true;
                }
                break;

            default:
                $hw_score = 0;
        }

        dbg("e20rClient::assign_exercise_level() -  Exercise per hour score: {$hw_score}");

        // Can't be "experienced" if they don't currently exercise
        if (1 == $data['exercise_plan']) {
            dbg("e20rClient::assign_exercise_level() -  Performs regular exercise, so is allowed to be selected for experienced level.");
            $can_be_ex = true;
        } else {
            $can_be_ex = false;
        }

        $total_exp = $el_score + $hw_score;

        // "experienced"
        if ( true === $can_be_ex && (6 === $total_exp || ( 3 === $el_score && $hw_score == 2 ) ))  {
            dbg("e20rClient::assign_exercise_level() -  User {$user_id} qualifies as 'Experienced'");
            $role = $user_roles['experienced']['role'];

        // $el_score = 1 or 2 and $hw_score = 1, 2, 3 ('intermediate')
        } elseif ( $total_exp <= 5 || $total_exp >= 3 ) {
            dbg("e20rClient::assign_exercise_level() -  User {$user_id} qualifies as 'Intermediate'");
            $role = $user_roles['intermediate']['role'];

            // Beginner
        } else {
            dbg("e20rClient::assign_exercise_level() -  User {$user_id} qualifies as 'New to Exercise'");
            $role = $user_roles['beginner']['role'];
        }

        $user = new WP_User($user_id);

        // Clean up any pre-existing roles for this user
        foreach( $user->roles as $r ) {

            dbg("e20rClient::assign_exercise_level() - Checking role '{$r}' for user {$user_id}");

            if (in_array($r, array('e20r_tracker_exp_1', 'e20r_tracker_exp_2', 'e20r_tracker_exp_3'))) {
                dbg("e20rClient::assign_exercise_level() - User has pre-existing role {$r}. Being removed");
                $user->remove_role($r);
            }
         }

        // assign new exercise exerience role
        dbg("e20rClient::assign_exercise_level() - Adding role {$role} to user {$user_id}.");
        $user->add_role($role);
    }

    /**
     * Assign a coach for the new client/user
     * @param   integer         $user_id
     * @param   string|null     $gender
     * @return bool|mixed
     */
    public function assign_coach($user_id, $gender = null)
    {

        global $e20rProgram;
        global $currentProgram;

        dbg("e20rClient::assign_coach() - Loading program settings for {$user_id}");

        $old_program = $currentProgram;
        $e20rProgram->getProgramIdForUser($user_id);

        $coach_id = false;

        switch (strtolower($gender)) {
            case 'm':
                dbg("e20rClient::assign_coach() - attempting to find a male coach for {$user_id} in program {$currentProgram->id}");
                dbg($currentProgram);

                $coach = $this->find_next_coach($currentProgram->male_coaches, $currentProgram->id);
                break;

            case 'f':
                dbg("e20rClient::assign_coach() - attempting to find a female coach for {$user_id} in program {$currentProgram->id}");
                $coach = $this->find_next_coach($currentProgram->female_coaches, $currentProgram->id);
                break;

            default:
                dbg("e20rClient::assign_coach() - attempting to find a coach for {$user_id} in program {$currentProgram->id}");
                $coaches = array_merge($currentProgram->male_coaches, $currentProgram->female_coaches);
                $coach = $this->find_next_coach($coaches, $currentProgram->id);
        }

        if (false !== $coach) {
            dbg("e20rClient::assign_coach() - Found coach: {$coach} for {$user_id}");
            $this->assign_client_to_coach($currentProgram->id, $coach, $user_id);
            $coach_id = $coach;
        }
        $currentProgram = $old_program;
        return $coach_id;
    }

    public function find_next_coach($coach_arr, $program_id)
    {
        dbg("e20rClient::find_next_coach() - Searching for the coach with the fewest clients so far..");

        $coaches = array();

        foreach ($coach_arr as $cId) {

            $client_list = get_user_meta($cId, 'e20r-tracker-client-program-list', true);
            dbg("e20rClient::find_next_coach() - Client list for coach {$cId} consists of " . (false === $client_list ? 'None' : count($client_list) . " entries"));
            dbg($client_list);

            if ((false !== $client_list) && (!empty($client_list))) {

                $coaches[$cId] = count($client_list[$program_id]);
            } else {
                $coaches[$cId] = 0;
            }
        }

        dbg("e20rClient::find_next_coach() - List of coaches and the number of clients they have been assigned...");
        dbg($coaches);

        if (asort($coaches)) {

            reset($coaches);
            $coach_id = key($coaches);

            dbg("e20rClient::find_next_coach() - Selected coach with ID: {$coach_id} in program {$program_id}");
            return $coach_id;
        }

        return false;
    }

    public function assign_client_to_coach($program_id, $coach_id, $client_id)
    {


        $client_list = get_user_meta($coach_id, 'e20r-tracker-client-program-list', true);
        dbg("e20rClient::assign_client_to_coach() - Coach {$coach_id} has the following programs & clients he/she is coaching: ");
        dbg($client_list);

        if ($client_list == false) {

            $client_list = array();
        }

        if (isset($client_list[$program_id])) {

            $clients = $client_list[$program_id];
        } else {

            $client_list[$program_id] = null;
            $clients = $client_list[$program_id];
        }

        if (empty($clients)) {

            $clients = array();
        }

        if (!in_array($client_id, $clients)) {

            $clients[] = $client_id;
            $client_list[$program_id] = $clients;
        }

        dbg("e20rClient::assign_client_to_coach() - Assigned client list for program {$program_id}: ");
        dbg($client_list);

        dbg("e20rClient::assign_client_to_coach() - Assigned user {$client_id} in program {$program_id} to coach {$coach_id}");
        if (false !== ($clients = get_user_meta($coach_id, 'e20r-tracker-coaching-client_ids'))) {

            if (!in_array($client_id, $clients)) {

                dbg("e20rClient::assign_client_to_coach() - Saved client Id to the array of clients for this coach ($coach_id)");
                add_user_meta($coach_id, 'e20r-tracker-coaching-client_ids', $client_id);
            }
        }

        if (false !== ($programs = get_user_meta($coach_id, 'e20r-tracker-coaching-program_ids'))) {

            if (!in_array($program_id, $programs)) {

                dbg("e20rClient::assign_client_to_coach() - Saved program id to the array of programs for this coach ($coach_id)");
                add_user_meta($coach_id, 'e20r-tracker-coaching-program_ids', $program_id);
            }
        }

        return update_user_meta($coach_id, 'e20r-tracker-client-program-list', $client_list);
    }

    public function get_coach($client_id = null, $program_id = null)
    {

        dbg("e20rClient::get_coach() - Loading coach information for program with ID: " . (is_null($program_id) ? 'Undefined' : $program_id));

        $coaches = $this->model->get_coach($client_id, $program_id);
        dbg("e20rClient::get_coach() - Returning coaches: ");
        dbg($coaches);

        return $coaches;
    }

    public function process_gf_fields($value, $field, $name)
    {

        global $currentClient;

        $type = GFFormsModel::get_input_type($field);

        if (('likert' == $type) && $field->allowsPrepopulate) {

            $col_value = null;

            foreach ($field->choices as $i) {

                if ($i['isSelected'] == 1) {

                    return $i['value'];
                }
            }
        }

        if (('multichoice' == $type) && $field->allowsPrepopulate) {

            $col_value = null;

            foreach ($field->choices as $i) {

                if (1 == $i['isSelected']) {

                    return $i['value'];
                }
            }
        }

        if (('address' == $type) && $field->allowsPrepopulate && !is_null($currentClient->user_id)) {

            $val = $this->model->get_data($currentClient->user_id, $name);

            dbg("e20rClient::process_gf_fields() - Found the {$name} field for user ({$currentClient->user_id}): {$val}");
            return $val;
        }

        return $value;
    }

    private function filterResponse($data)
    {

        switch ($data) {

            case 'Yes':
                $data = 1;
                break;

            case 'No':
                $data = 0;
                break;

            case 'M':
                $data = 'm';
                break;

            case 'F':
                $data = 'f';
                break;
        }

        // Preserve \n in textboxes
        $data = implode("\n", array_map('sanitize_text_field', explode("\n", $data)));
        return $data;
    }

    public function saveNewUnit($type, $unit)
    {

        switch ($type) {

            case 'length':

                dbg("e20rClient::saveNewUnit() - Saving new length unit: {$unit}");
                $this->model->saveUnitInfo($unit, $this->getWeightUnit());
                break;

            case 'weight':

                dbg("e20rClient::saveNewUnit() - Saving new weight unit: {$unit}");
                $this->model->saveUnitInfo($this->getLengthUnit(), $unit);
                break;
        }

        return true;
    }

    public function loadClientInfo($user_id)
    {

        try {

            dbg("e20rClient::loadClientInfo() - Loading data for client model");
            $this->model->setUser($user_id);
            $this->model->get_data($user_id);

        } catch (Exception $e) {

            dbg("Error loading user data for ({$user_id}): " . $e->getMessage());
        }

    }

    /**
     * Render page on back-end for client data (admin selectable).
     */
    public function render_client_page($lvlName = '', $client_id = 0)
    {

        global $current_user;
        global $currentClient;
        global $currentProgram;

        global $e20rProgram;
        global $e20rTracker;

        if (!$e20rTracker->is_a_coach($current_user->ID)) {

            $this->set_not_coach_msg();
        }

        $w_client = isset($_GET['e20r-client-id']) ? $e20rTracker->sanitize($_GET['e20r-client-id']) : null;
        $w_level = isset($_GET['e20r-level-id']) ? $e20rTracker->sanitize($_GET['e20r-level-id']) : -1;

        if (!is_null($w_client)) {

            $client_id = $w_client;
        }

        if ($client_id != 0) {

            dbg("e20rClient::render_client_page() - Forcing client ID to {$client_id}");
            $this->setClient($client_id);
        }

        $this->init();

        $this->model->get_data($client_id);

        dbg("e20rClient::render_client_page() - Loading admin page for the Client {$client_id}");
        echo $this->view->viewClientAdminPage($lvlName, $w_level);
        dbg("e20rClient::render_client_page() - Admin page for client {$client_id} has been loaded");
    }

    public function updateUnitTypes()
    {

        dbg("e20rClient::updateUnitTypes() - Attempting to update the Length or weight Units via AJAX");

        check_ajax_referer('e20r-tracker-progress', 'e20r-progress-nonce');

        dbg("e20rClient::updateUnitTypes() - POST content: " . print_r($_POST, true));

        global $current_user;
        global $currentClient;
        global $e20rMeasurements;

        $currentClient->user_id = isset($_POST['user-id']) ? intval($_POST['user-id']) : $current_user->ID;
        $dimension = isset($_POST['dimension']) ? sanitize_text_field($_POST['dimension']) : null;
        $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : null;

        // Configure the client data object(s).
        $this->init();
        $e20rMeasurements->setClient($currentClient->user_id);

        // Update the data for this user in the measurements table.
        try {

            $e20rMeasurements->updateMeasurementsForType($dimension, $value);
        } catch (Exception $e) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurements for new measurement type(s): " . $e->getMessage());
            wp_send_json_error("Error updating existing data: " . $e->getMessage());
        }

        // Save the actual setting for the current user

        $this->weightunits = $this->getWeightUnit();
        $this->lengthunits = $this->getLengthUnit();

        if ($dimension == 'weight') {
            $this->weightunits = $value;
        }

        if ($dimension == 'length') {
            $this->lengthunits = $value;
        }

        // Update the settings for the user
        try {
            $this->model->saveUnitInfo($this->lengthunits, $this->weightunits);
        } catch (Exception $e) {
            dbg("e20rClient::updateUnitTypes() - Error updating measurement unit for {$dimension}");
            wp_send_json_error("Unable to save new {$dimension} type ");
        }

        dbg("e20rClient::updateUnitTypes() - Unit type updated");
        wp_send_json_success("All data updated ");
        wp_die();
    }

    function ajax_getMemberlistForLevel()
    {
        // dbg($_POST);
        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        global $e20rTracker;

        $levelId = (isset($_POST['hidden_e20r_level']) ? intval($_POST['hidden_e20r_level']) : 0);

        $this->init();

        dbg("e20rClient::getMemberListForLevel() - Program requested: {$levelId}");

        if ($levelId != 0) {

            // $levels = $e20rTracker->getMembershipLevels( $levelId );
            // $this->load_levels( $levelObj->name );
            // dbg("e20rClient::getMemberListForLevel() - Loading members:");
            // dbg($levels);

            $data = $this->view->viewMemberSelect($levelId);
        } else {

            // $this->view->load_levels();
            $data = $this->view->viewMemberSelect();
        }

        wp_send_json_success($data);

    }

    private function createEmailBody($subject, $content)
    {

        ob_start(); ?>
        <html>
    <head>
        <title><?php echo esc_attr( stripslashes( $subject ) ); ?></title>
    </head>
    <body>
    <div id="the_content">
        <?php echo wp_kses_post( $content ); ?>
    </div>
    </body>
        </html><?php
        $html = ob_get_clean();
        return $html;
    }

    public function updateRoleForUser($userId)
    {

        // global $currentProgram;
        global $e20rTracker;

        if (!current_user_can('edit_user')) {
            return false;
        }

        $role_name = isset($_POST['e20r-tracker-user-role']) ? $e20rTracker->sanitize($_POST['e20r-tracker-user-role']) : null;
        $programs = isset($_POST['e20r-tracker-coach-for-programs']) ? $e20rTracker->sanitize($_POST['e20r-tracker-coach-for-programs']) : array();

        $user_roles = apply_filters('e20r-tracker-configured-roles', array());

        dbg("e20rTracker::updateRoleForUser() - Setting role name to: ({$role_name}) for user with ID of {$userId}");

        $u = get_user_by('id', $userId);

        if (!empty($role_name)) {

            $u->add_role($role_name);
        } else {
            if ($u->has_role( $user_roles['coach']['role'])) {

                dbg("e20rClient::updateRoleForUser() - Removing 'coach' capability/role for user {$userId}");
                $u->remove_role($user_roles['coach']['role']);
            }

            // wp_die( "Unable to remove the {$role_name} role for this user ({$userId})" );
        }


        if (false !== ($pgmList = get_user_meta($userId, "e20r-tracker-coaching-program_ids"))) {

            foreach ($programs as $p) {

                if (!in_array($p, $pgmList)) {
                    dbg("e20rClient::updateRoleForUser() - Adding program IDs this user is a coach for");
                    dbg($programs);
                    add_user_meta($userId, 'e20r-tracker-coaching-program_ids', $programs);
                }
            }
        }


        if (false === ($pgms = get_user_meta($userId, 'e20r-tracker-coaching-program_ids'))) {

            wp_die("Unable to save the list of programs this user is a coach for");
        }

        dbg("e20rClient::updateRoleForUser() - User roles are now: ");
        dbg($u->caps);
        dbg("e20rClient::updateRoleForUser() - And they are a coach for: ");
        dbg($pgms);

        return true;
    }

    public function selectRoleForUser($user)
    {

        global $e20rProgram;
        
        dbg("e20rClient::selectRoleForUser() - Various roles & capabilities for user {$user->ID}");

        $allPrograms = $e20rProgram->get_programs();
        dbg($allPrograms);

        echo $this->view->profile_view_user_settings($user->ID, $allPrograms);
    }

    public function schedule_email($email_args, $when = null)
    {

        if (is_null($when)) {
            dbg("e20rClient::schedule_email() - No need to schedule the email for transmission. We're sending it right away.");
            return $this->send_email_to_client($email_args);
        } else {
            // Send message to user at specified time.
            dbg("e20rClient::schedule_email() - Schedule the email for transmission. {$when}");
            $ret = wp_schedule_single_event($when, 'e20r_schedule_email_for_client', array($email_args));

            if (is_null($ret)) {
                return true;
            }
        }

        return false;
    }

    public function send_email_to_client($email_array)
    {

        dbg($email_array);
        $headers[] = "Content-type: text/html";
        $headers[] = "Cc: " . $email_array['cc'];
        $headers[] = "From: " . $email_array['from'];

        $message = $this->createEmailBody($email_array['subject'], $email_array['content']);

        // $headers[] = "From: \"{$from_name}\" <{$from}>";

        dbg($email_array['to_email']);
        dbg($headers);
        dbg($email_array['subject']);
        dbg($message);

        add_filter('wp_mail', array($this, 'test_wp_mail'));

        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));

        $status = wp_mail( sanitize_email( $email_array['to_email'] ), sanitize_text_field( $email_array['subject']), $message, $headers, null);

        remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));

        if (true == $status) {
            dbg("e20rClient::ajax_sendClientMessage() - Successfully transferred the info to wp_mail()");

            if (!$this->model->save_message_to_history($email_array['to_user']->ID, $email_array['program_id'], $email_array['from_uid'], $message, $email_array['subject'])) {
                dbg("e20rClient::ajax_sendClientMessage() - Error while saving message history for {$email_array['to_user']->ID}");
                return false;
            }

            dbg("e20rClient::ajax_sendClientMessage() - Successfully saved the message to the user message history table");
            return true;
        }

        return false;
    }

    public function ajax_sendClientMessage()
    {

        global $e20rTracker;
        global $e20rProgram;

        global $currentProgram;

        $headers = array();
        $when = null;
        dbg('e20rClient::ajax_sendClientMessage() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_sendClientMessage() - Nonce is OK");

        dbg("e20rClient::ajax_sendClientMessage() - Request: " . print_r($_REQUEST, true));

        // $to_uid = isset( $_POST['email-to-id']) ? $e20rTracker->sanitize( $_POST['email-to-id']) : null;
        $email_args['to_email'] = isset($_POST['email-to']) ? $e20rTracker->sanitize($_POST['email-to']) : null;
        $email_args['cc'] = isset($_POST['email-cc']) ? $e20rTracker->sanitize($_POST['email-cc']) : null;
        $email_args['from_uid'] = isset($_POST['email-from-id']) ? $e20rTracker->sanitize($_POST['email-from-id']) : null;
        $email_args['from'] = isset($_POST['email-from']) ? $e20rTracker->sanitize($_POST['email-from']) : null;
        $email_args['from_name'] = isset($_POST['email-from-name']) ? $e20rTracker->sanitize($_POST['email-from-name']) : null;
        $email_args['subject'] = isset($_POST['subject']) ? $e20rTracker->sanitize($_POST['subject']) : ' ';
        $email_args['content'] = isset($_POST['content']) ? wp_kses_post($_POST['content']) : null;
        $email_args['time'] = isset($_POST['when-to-send']) ? $e20rTracker->sanitize($_POST['when-to-send']) : null;
        $email_args['content'] = stripslashes_deep($email_args['content']);


        dbg("e20rClient::ajax_sendClientMessage() - Checking whether to schedule sending this message: {$email_args['time']}");
        if (!empty($email_args['time'])) {

            if (false === ($when = strtotime($email_args['time'] . " " . get_option('timezone_string')))) {
                wp_send_json_error(array('error' => 3)); // 3 == 'Incorrect date/time provided'.
            }

            dbg("e20rClient::ajax_sendClientMessage() - Scheduled to be sent at: {$when}");
        }

        dbg("e20rClient::ajax_sendClientMessage() - Get the User info for the sender");
        if (!is_null($email_args['from_uid'])) {
            $f = get_user_by('id', $email_args['from_uid']);
        }

        dbg("e20rClient::ajax_sendClientMessage() - Get the User info for the receiver");
        $email_args['to_user'] = get_user_by('email', $email_args['to_email']);
        $e20rProgram->getProgramIdForUser($email_args['to_user']->ID);

        $email_args['program_id'] = $currentProgram->id;

        // $sendTo = "{$to->display_name} <{$to_email}>";
        dbg("e20rClient::ajax_sendClientMessage() - Try to schedule the email for transmission");
        $status = $this->schedule_email($email_args, $when);

        if (true == $status) {
            dbg("e20rClient::ajax_sendClientMessage() - Successfully scheduled the message to be sent");

            wp_send_json_success();
            wp_die();
        }

        dbg("e20rClient::ajax_sendClientMessage() - Error while scheduling message to be sent");
        wp_send_json_error();
    }

    public function set_html_content_type()
    {

        return 'text/html';
    }

    public function test_wp_mail($args)
    {

        $debug = var_export($args, true);
        dbg($debug);
    }

    public function ajax_ClientMessageHistory()
    {

        dbg('e20rClient::ajax_ClientMessageHistory() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_ClientMessageHistory() - Nonce is OK");

        dbg("e20rClient::ajax_ClientMessageHistory() - Request: " . print_r($_REQUEST, true));

        global $current_user;
        global $e20rProgram;
        global $e20rTracker;

        global $currentProgram;
        global $currentClient;

        if (!$e20rTracker->is_a_coach($current_user->ID)) {

            dbg("e20rClient::ajax_showClientMessage() - User isn't a coach. Return error & force redirect");
            wp_send_json_error(array('error' => 403));
        }

        $userId = isset($_POST['client-id']) ? $e20rTracker->sanitize($_POST['client-id']) : $current_user->ID;
        $e20rProgram->getProgramIdForUser($userId);

        dbg("e20rClient::ajax_showClientMessage() - Loading message history from DB for {$userId}");
        $html = $this->loadClientMessages($userId);

        dbg("e20rClient::ajax_showClientMessage() - Generating message history HTML");
        // $html = $this->view->viewMessageHistory( $userId, $messages );

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_showClientMessage()
    {

        dbg('e20rClient::ajax_showClientMessage() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_showClientMessage() - Nonce is OK");

        dbg("e20rClient::ajax_showClientMessage() - Request: " . print_r($_REQUEST, true));

        global $current_user;
        global $e20rProgram;
        global $e20rMeasurements;
        global $e20rArticle;

        global $e20rTracker;

        global $currentProgram;
        global $currentClient;

        if (!$e20rTracker->is_a_coach($current_user->ID)) {

            dbg("e20rClient::ajax_showClientMessage() - User isn't a coach. Return error & force redirect");
            wp_send_json_error(array('error' => 403));
        }

        $userId = isset($_POST['client-id']) ? $e20rTracker->sanitize($_POST['client-id']) : $current_user->ID;
        $e20rProgram->getProgramIdForUser($userId);

        $articles = $e20rArticle->findArticles('post_id', $currentProgram->intake_form, $currentProgram->id);
        $a = $articles[0];

        dbg("e20rClient::ajax_showClientMessage() - Article ID: ");
        dbg($a->id);

        if (!$e20rArticle->isSurvey($a->id)) {
            wp_send_json_error(array('error' => 'Configuration error. Please report to tech support.'));
        } else {
            dbg("e20rClient::ajax_showClientMessage() - Loading article configuration for the survey!");
            $e20rArticle->init($a->id);
        }

        dbg("e20rClient::ajax_showClientMessage() - Load client data...");

        // Loads the program specific client information we've got stored.
        $this->model->get_data($userId);

        $html = $this->view->viewClientContact($userId);

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_clientDetail()
    {

        global $current_user;
        global $e20rTracker;

        if (!is_user_logged_in()) {

            dbg("e20rClient::ajax_clientDetail() - User isn't logged in. Return error & force redirect");
            wp_send_json_error(array('error' => 403));
        }

        if (!$e20rTracker->is_a_coach($current_user->ID)) {

            dbg("e20rClient::ajax_clientDetail() - User isn't a coach. Return error & force redirect");
            wp_send_json_error(array('error' => 403));
        }
        dbg('e20rClient::ajax_clientDetail() - Requesting client detail');

        check_ajax_referer('e20r-tracker-data', 'e20r-tracker-clients-nonce');

        dbg("e20rClient::ajax_clientDetail() - Nonce is OK");

        dbg("e20rClient::ajax_clientDetail() - Request: " . print_r($_REQUEST, true));

        global $e20rProgram;
        global $e20rMeasurements;
        global $e20rArticle;

        global $currentProgram;
        global $currentArticle;
        global $currentClient;

        $userId = isset($_POST['client-id']) ? $e20rTracker->sanitize($_POST['client-id']) : $current_user->ID;
        $type = isset($_POST['tab-id']) ? $e20rTracker->sanitize($_POST['tab-id']) : 'client-info';
        $e20rProgram->getProgramIdForUser($userId);

        $articles = $e20rArticle->findArticles('post_id', $currentProgram->intake_form, $currentProgram->id);
        $a = $articles[0];

        dbg("e20rClient::ajax_clientDetail() - Article ID: ");
        dbg($a->id);

        if (!$e20rArticle->isSurvey($a->id)) {
            wp_send_json_error(array('error' => 'Configuration error. Please report to tech support.'));
        } else {
            dbg("e20rClient::ajax_clientDetail() - Loading article configuration for the survey!");
            $e20rArticle->init($a->id);
        }

        switch ($type) {
            case 'client-info':
                dbg("e20rClient::ajax_clientDetail() - Loading client data");
                $html = $this->load_clientDetail($userId, $currentProgram->id, $currentArticle->id);
                break;

            case 'achievements':
                dbg("e20rClient::ajax_clientDetail() - Loading client achievement data");
                $html = $this->load_achievementsData($userId);
                // dbg($html);
                break;

            case 'assignments':
                dbg("e20rClient::ajax_clientDetail() - Loading client assignment data");
                $html = $this->load_assignmentsData($userId);
                break;

            case 'activities':
                dbg("e20rClient::ajax_clientDetail() - Loading client activity data");
                $html = $this->load_activityData($userId);
                break;

            default:
                dbg("e20rClient::ajax_clientDetail() - Default: Loading client information");
                $html = $this->load_clientDetail($userId);
        }

        wp_send_json_success(array('html' => $html));
    }

    public function load_clientDetail($clientId)
    {

        dbg("e20rClient::load_clientDetail() - Load client data...");
        global $e20rProgram;
        global $e20rArticle;

        global $currentProgram;
        global $currentArticle;

        dbg("e20rClient::load_clientDetail() - Load program info for this clientID.");
        // Loads the program specific client information we've got stored.
        $e20rProgram->getProgramIdForUser($clientId);

        if (empty($currentProgram->id)) {
            dbg("e20rClient::load_clientDetail() - ERROR: No program ID defined for user {$clientId}!!!");
            return null;
        }

        dbg("e20rClient::load_clientDetail() - Find article ID for the intake form {$currentProgram->intake_form} for the program ({$currentProgram->id}).");
        $article = $e20rArticle->findArticles('post_id', $currentProgram->intake_form, $currentProgram->id);

        dbg("e20rClient::load_clientDetail() - Returned " . count($article) . " articles on behalf of the intake form");

        if (!empty($article)) {

            dbg("e20rClient::load_clientDetail() - Load article configuration.");
            dbg($article[0]);
            $e20rArticle->init($article[0]->id);
        } else {
            dbg("e20rClient::load_clientDetail() - ERROR: No article defined for the Welcome Survey!!!");
            return null;
        }

        dbg("e20rClient::load_clientDetail() - Load the client information for {$clientId} in program {$currentProgram->id} for article {$currentArticle->id}");
        $this->model->get_data($clientId);

        dbg("e20rClient::ajax_clientDetail() - Show client detail for {$clientId} related to {$currentArticle->id} and {$currentProgram->id}");
        return $this->view->viewClientDetail($clientId);
    }

    public function load_achievementsData($clientId)
    {

        global $e20rAction;

        return $e20rAction->listUserAccomplishments($clientId);
    }

    public function load_assignmentsData($clientId)
    {

        global $e20rAssignment;

        return $e20rAssignment->listUserAssignments($clientId);
    }

    public function load_activityData($clientId)
    {

        global $e20rWorkout;

        return $e20rWorkout->listUserActivities($clientId);
    }

    public function validateAccess($clientId)
    {

        global $current_user;
        global $e20rTracker;

        dbg("e20rClient::validateAccess() - Client being validated: " . $clientId);

        if ($clientId) {

            $client = get_user_by('id', $clientId);
            dbg("e20rClient::validateAccess() - Real user Id provided ");

            if (($current_user->ID != $clientId) &&
                (($e20rTracker->isActiveClient($clientId)) ||
                    ($e20rTracker->is_a_coach($current_user->ID)))
            ) {

                return true;
            } elseif ($current_user->ID == $clientId) {
                return true;
            }
            // Make sure the $current_user has the right to view the data for $clientId

        }

        return false;
    }

    public function view_interview($clientId)
    {

        global $e20rArticle;

        global $currentProgram;

        global $current_user;

        $content = null;

        if (isset($currentProgram->intake_form)) {

            $interview = get_post( $currentProgram->intake_form );

            if (isset($interview->post_content) && !empty($interview->post_content)) {

                dbg("e20rClient::view_interview() - Applying the content filter to the interview page content");
                $content = apply_filters('the_content', $interview->post_content);

                dbg("e20rClient::view_interview() - Validate whether the interview has been completed by the user");
                $complete = $this->completeInterview($clientId);

                dbg("e20rClient::view_interview() - Loading the Welcome interview page & the users interview is saved already");

                $update_reminder = $e20rArticle->interviewCompleteLabel($interview->post_title, $complete);

                $content = $update_reminder . $content;
            }
        }
        else {
            dbg( "e20rClient::view_interview() - ERROR: No client Interview form has been configured! ");
        }

        dbg("e20rClient::view_interview() - Returning HTML");
        return $content;
    }

    public function shortcode_clientProfile( $atts = null, $content = null )
    {

        dbg("e20rClient::shortcode_clientProfile() - Loading shortcode data for the client profile page.");
		// dbg($content);

        global $current_user;

        global $e20rProgram;
        global $e20rAction;
        global $e20rAssignment;
        global $e20rWorkout;
        global $e20rArticle;
        global $e20rMeasurements;

        global $currentProgram;
        global $currentArticle;

        $html = null;
        $tabs = array();

        if (!is_user_logged_in() || (!$this->validateAccess($current_user->ID))) {

            auth_redirect();
        } else {

            $userId = $current_user->ID;
            $e20rProgram->getProgramIdForUser($userId);
        }


        /* Load views for the profile page tabs */
        $config = $e20rAction->configure_dailyProgress();

        $code_atts = shortcode_atts(array(
            'use_cards' => false,
        ), $atts);

        foreach ($code_atts as $key => $val) {

            dbg("e20rClient::shortcode_clientProfile() - e20r_profile shortcode --> Key: {$key} -> {$val}");
            $config->{$key} = $val;
        }

        if ( in_array( strtolower( $config->use_cards ), array( 'yes', 'true', '1' ) ) ) {

            dbg("e20rClient::shortcode_clientProfile() - User requested card based dashboard: {$config->use_cards}");
            $config->use_cards = true;
        }

        if ( in_array( strtolower( $config->use_cards ), array( 'no', 'false', '0' ) ) ) {

            dbg("e20rClient::shortcode_clientProfile() - User requested old-style dashboard: {$config->use_cards}");
            $config->use_cards = false;
        }

        if ( !isset( $config->use_cards ) ) {
            $config->use_cards = false;
        }

        if ($this->completeInterview($config->userId)) {
            $interview_descr = 'Saved interview';
        } else {

            $interview_descr = '<div style="color: darkred; text-decoration: underline; font-weight: bolder;">' . __("Please complete interview", "e20rtracker") . '</div>';
        }

        $interview_html = '<div id="e20r-profile-interview">' . $this->view_interview($config->userId) . '</div>';
        $interview = array($interview_descr, $interview_html );

        if (!$currentArticle->is_preview_day) {

            dbg("e20rClient::shortcode_clientProfile() - Configure user specific data");

            $this->model->setUser($config->userId);
            // $this->setClient($userId);

            $dimensions = array('width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px');
            $pDimensions = array('width' => '90', 'height' => '1024', 'htype' => 'px', 'wtype' => '%');

            dbg("e20rClient::shortcode_clientProfile() - Loading progress data...");
            $measurements = $e20rMeasurements->getMeasurement('all', false);

            if ( true === $this->completeInterview($config->userId) ) {
                $measurement_view = $e20rMeasurements->showTableOfMeasurements($config->userId, $measurements, $dimensions, true, false);
            } else {
                $measurement_view = '<div class="e20r-progress-no-measurement">' . $e20rProgram->incompleteIntakeForm() . '</div>';
            }

            $assignments = $e20rAssignment->listUserAssignments($config->userId);
            $activities = $e20rWorkout->listUserActivities($config->userId);
            $achievements = $e20rAction->listUserAccomplishments($config->userId);

            $progress = array(
                'Measurements' => '<div id="e20r-progress-measurements">' . $measurement_view . '</div>',
                'Assignments' => '<div id="e20r-progress-assignments"><br/>' . $assignments . '</div>',
                'Activities' => '<div id="e20r-progress-activities">' . $activities . '</div>',
                'Achievements' => '<div id="e20r-progress-achievements">' . $achievements . '</div>',
            );

            $dashboard = array(
                'Your dashboard',
                '<div id="e20r-daily-progress">' . $e20rAction->dailyProgress($config) . '</div>'
            );
            /*
                       $activity = array(
                           'Your activity',
                           '<div id="e20r-profile-activity">' . $e20rWorkout->prepare_activity( $config ) . '</div>'
                       );
            */
            $progress_html = array(
                'Your progress',
                '<div id="e20r-profile-status">' . $e20rMeasurements->show_progress($progress, null, false) . '</div>'
            );

            $tabs = array(
                'Home' => $dashboard,
//                'Activity'          => $activity,
                'Progress' => $progress_html,
                'Interview' => $interview,
            );
        } else {

            $lesson_prefix = preg_replace('/\[|\]/', '', $currentArticle->prefix);
            $lesson = array(
                'Your ' . lcfirst($lesson_prefix),
                '<div id="e20r-profile-lesson">' . $e20rArticle->load_lesson($config->articleId) . '</div>'
            );

            $tabs = array(
                $lesson_prefix => $lesson,
                'Interview' => $interview,
            );
        }

        $html = $this->view->view_clientProfile($tabs);
        dbg("e20rClient::shortcode_clientProfile() - Display the HTML for the e20r_profile short code: " . strlen($html));

        return $html;

    }

    private function set_not_coach_msg()
    {

        global $e20rTracker;

        dbg("e20rClient::set_not_coach_msg() - User isn't a coach. Return error & force redirect");

        $error = '<div class="error">';
        $error .= '    <p>' . __("Sorry, as far as the Web Monkey knows, you are not a coach and will not be allowed to access the Coach's Page.", "e20rtracker") . '</p>';
        $error .= '</div><!-- /.error -->';

        $e20rTracker->updateSetting('unserialize_notice', $error);
        wp_redirect(admin_url());

    }

    public function shortcode_clientList($attributes = null)
    {

        dbg("e20rClient::shortcode_clientList() - Loading shortcode for the coach list of clients");

        global $e20rAction;
        global $e20rProgram;
        global $e20rTracker;

        global $currentProgram;

        global $current_user;

        if (!is_user_logged_in()) {

            auth_redirect();
            wp_die();
        }

        if ((!$e20rTracker->is_a_coach($current_user->ID))) {

            $this->set_not_coach_msg();
            wp_die();
        }

        $client_list = $this->model->get_clients($current_user->ID);
        $list = array();

        foreach ($client_list as $pId => $clients) {

            foreach ($clients as $k => $client) {

                // $e20rProgram->getProgramIdForUser( $client->ID );
                // $e20rProgram->setProgram( $pId );

                $coach = $this->model->get_coach($client->ID);

                $client->status = new stdClass();
                $client->status->program_id = $e20rProgram->getProgramIdForUser($client->ID);
                $client->status->program_start = $e20rProgram->get_program_start($client->status->program_id, $client->ID);
                $client->status->coach = array($currentProgram->id => key($coach));
                $client->status->recent_login = get_user_meta($client->ID, '_e20r-tracker-last-login', true);
                $mHistory = $this->model->load_message_history($client->ID);

                $client->status->total_messages = count($mHistory);

                if (!empty($mHistory)) {

                    krsort($mHistory);
                    reset($mHistory);

                    dbg("e20rClient::shortcode_clientList() - Sorted message history for user {$client->ID}");
                    $when = key($mHistory);
                    $msg = isset($mHistory[$when]) ? $mHistory[$when] : null;

                    $client->status->last_message = array($when => $msg->topic);
                    $client->status->last_message_sender = $msg->sender_id;
                } else {
                    $client->status->last_message = array('empty' => __('No message sent via this website', "e20rtracker"));
                    $client->status->last_message_sender = null;
                }

                dbg("e20rClient::shortcode_clientList() - Most recent message:");
                dbg($client->status->last_message);

                if (!isset($list[$currentProgram->id])) {

                    $list[$currentProgram->id] = array();
                }

                $list[$pId][$k] = $client;
            }
        }

        dbg("e20rClient::shortcode_clientList() - Showing client information");
        // dbg($list);

        return $this->view->display_client_list($list);

    }
}
