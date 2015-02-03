<?php
/**
 * Created by PhpStorm.
 * User: sjolshag
 * Date: 12/8/14
 * Time: 2:52 PM
 */

if ( ! class_exists( 'e20rTables' ) ):

class e20rTables {

    protected $tables = null;
    protected $oldTables = null;
    protected $fields = array();

    protected $inBeta = false;

    public function e20rTables() {

        if ( ! function_exists( 'in_betagroup' ) ) {
            dbg( "Error: in_betagroup function is missing!" );
            wp_die( "Critical plugin functionality is missing: in_betagroup()" );
        }

    }


    public function init( $user_id = null ) {

        if ( ! function_exists( 'get_user_by' ) ) {
            dbg("e20rTables::init() - Wordpress not fully loaded yet...");
            return;
        }

        dbg("e20rTables::constructor() - Initializing the e20rTables() class");
        global $wpdb, $current_user;

        if ( $user_id === null ) {

            $user_id = $current_user->ID;
        }

        $this->inBeta = in_betagroup( $user_id );

        $this->tables = new stdClass();

        /* The database tables used by this plugin */
        $this->tables->checkin       = $wpdb->prefix . 'e20r_checkin';
        $this->tables->assignments   = $wpdb->prefix . 'e20r_assignments';
        $this->tables->responses     = $wpdb->prefix . 'e20r_answers';
        $this->tables->measurements  = $wpdb->prefix . 'e20r_measurements';
        $this->tables->client_info   = $wpdb->prefix . 'e20r_client_info';
        $this->tables->program       = $wpdb->prefix . 'e20r_programs';
        $this->tables->sets          = $wpdb->prefix . 'e20r_sets';
        $this->tables->exercise      = $wpdb->prefix . 'e20r_exercises';
        $this->tables->appointments  = $wpdb->prefix . 'app_appointments';
        $this->tables->assignments   = $wpdb->prefix . 'e20r_assignment';
        $this->tables->questions     = $wpdb->prefix . 'e20r_question';


        if ( ( $this->inBeta ) ) {

            dbg("User $user_id IS in the beta group");
            $this->tables->assignments  = "{$wpdb->prefix}s3f_nourishAssignments";
            $this->tables->compliance   = "{$wpdb->prefix}s3f_nourishHabits";
            $this->tables->surveys      = "{$wpdb->prefix}e20r_Surveys";
            $this->tables->measurements = "{$wpdb->prefix}nourish_measurements";
            $this->tables->meals        = "{$wpdb->prefix}wp_s3f_nourishMeals";

        }
    }

    // TODO: Implement this as a custome post type.
    private function loadAssignmentFields() {

    }

    // TODO: Implement this as a custom post type.
    private function loadQuestionFields() {

    }

    /**
     * Returns status for membership in the Nourish Beta group.
     *
     * @return bool -- True if the user is a member of the Nourish Beta group
     */
    public function isBetaClient() {

        return $this->inBeta;
    }

    private function loadCheckinFields() {

        $this->fields['checkin'] = array(
            'id'                => 'id',
            'user_id'           => 'user_id',
            'program_id'        => 'program_id',
            'article_id'        => 'article_id',
            'checkin_type'      => 'checkin_type',
            'checkin_date'      => 'checkin_date',
            'checkin_short_name' => 'checkin_short_name',
            'checkin_type'      => 'checkin_type',
            'checkin_note'      => 'checkin_note',
        );
    }

    private function loadProgramFields() {

        $this->fields['program'] = array(
            'id'                => 'id',
            'program_name'      => 'program_name',
            'program_shortname' => 'program_shortname',
            'description'       => 'description',
            'starttime'         => 'starttime',
            'endtime'           => 'endtime',
            'member_id'         => 'member_id',
            'belongs_to'        => 'belongs_to'
        );
    }

    private function loadMeasurementFields() {

        if ( ! $this->inBeta ) {

            $this->fields[ 'measurements' ] = array(
                'id'                    => 'id',
                'user_id'               => 'user_id',
                'article_id'            => 'article_id',
                'program_id'            => 'program_id',
                'recorded_date'         => 'recorded_date',
                'weight'                => 'weight',
                'girth_neck'            => 'neck',
                'girth_shoulder'        => 'shoulder',
                'girth_chest'           => 'chest',
                'girth_arm'             => 'arm',
                'girth_waist'           => 'waist',
                'girth_hip'             => 'hip',
                'girth_thigh'           => 'thigh',
                'girth_calf'            => 'calf',
                'girth'                 => 'girth',
                'behaviorprogress'      => 'behaviorprogress',
                'essay1'                => 'essay1',
                'front_image'           => 'front_image',
                'side_image'            => 'side_image',
                'back_image'            => 'back_image',
            );
        }
        else {

            $this->fields[ 'measurements' ] = array(
                'id' => 'lead_id',
                'user_id' => 'created_by',
                'article_id' => 'article_id',
                'program_id' => 'program_id',
                'recorded_date' => 'recordedDate',
                'weight' => 'weight',
                'girth_neck' => 'neckCM',
                'girth_shoulder' => 'shoulderCM',
                'girth_chest' => 'chestCM',
                'girth_arm' => 'armCM',
                'girth_waist' => 'waistCM',
                'girth_hip' => 'hipCM',
                'girth_thigh' => 'thighCM',
                'girth_calf' => 'calfCM',
                'girth' => 'totalGrithCM',
                'behaviorprogress' => 'behaviorprogress',
                'essay1' => 'essay1',
                'front_image'           => 'front_image',
                'side_image'            => 'side_image',
                'back_image'            => 'back_image',
            );
        }
    }

    private function loadClientDataFields() {

        $this->fields[ 'client_info' ] = array(
            'id' => 'id',
            'user_id' => 'user_id',
            'program_id' => 'program_id',
            'program_start' => 'program_start',
            'progress_photo_dir' => 'progress_photo_dir',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone' => 'phone',
            'alt_phone' => 'alt_phone',
            'contact_method' => 'contact_method',
            'skype_name' => 'skype_name',
            'address_1' => 'address_1',
            'address_2' => 'address_2',
            'address_city' => 'address_city',
            'address_zip' => 'address_zip',
            'address_state' => 'address_state',
            'address_country' => 'address_country',
            'emergency_contact' => 'emergency_contact',
            'emergency_mail' => 'emergency_mail',
            'emergency_phone' => 'emergency_phone',
            'birthdate' => 'birthdate',
            'ethnicity' => 'ethnicity',
            'lengthunits' => 'lengthunits',
            'height_ft int' => 'height_ft int',
            'height_in int' => 'height_in int',
            'calculated_height_in' => 'calculated_height_in',
            'height_m' => 'height_m',
            'height_cm' => 'height_cm',
            'calculated_height_cm' => 'calculated_height_cm',
            'weightunits' => 'weightunits',
            'weight_lbs' => 'weight_lbs',
            'weight_kg' => 'weight_kg',
            'weight_st' => 'weight_st',
            'weight_st_uk' => 'weight_st_uk',
            'first_time' => 'first_time',
            'number_of_times' => 'number_of_times',
            'coaches' => 'coaches',
            'referred' => 'referred',
            'referral_name' => 'referral_name',
            'referral_email' => 'referral_email',
            'hear_about' => 'hear_about',
            'other_programs_considered' => 'other_programs_considered',
            'weight_loss' => 'weight_loss',
            'muscle_gain' => 'muscle_gain',
            'look_feel' => 'look_feel',
            'consistency' => 'consistency',
            'energy_vitality' => 'energy_vitality',
            'off_medication' => 'off_medication',
            'control_eating' => 'control_eating',
            'learn_maintenance' => 'learn_maintenance',
            'stronger' => 'stronger',
            'modeling' => 'modeling',
            'sport_performance' => 'sport_performance',
            'goals_other' => 'goals_other',
            'goal_achievement' =>'goal_achievement',
            'goal_reward' => 'goal_reward',
            'regular_exercise' => 'regular_exercise',
            'exercise_hours_per_week' => 'exercise_hours_per_week',
            'regular_exercise_type' => 'regular_exercise_type',
            'other_exercise' => 'other_exercise',
            'exercise_plan' => 'exercise_plan',
            'exercise_level' => 'exercise_level',
            'competitive_sports' => 'competitive_sports',
            'competitive_survey' => 'competitive_survey',
            'enjoyable_activities' => 'enjoyable_activities',
            'exercise_challenge' => 'exercise_challenge',
            'chronic_pain' => 'chronic_pain',
            'pain_symptoms' => 'pain_symptoms',
            'limiting_injuries' => 'limiting_injuries',
            'injury_summary' => 'injury_summary',
            'other_injuries' => 'other_injuries',
            'injury_details' => 'injury_details',
            'nutritional_challenge' => 'nutritional_challenge',
            'buy_groceries' => 'buy_groceries',
            'groceries_who' => 'groceries_who',
            'cooking' => 'cooking',
            'cooking_who' => 'cooking_who',
            'eats_with' => 'eats_with',
            'meals_at_home' => 'meals_at_home',
            'following_diet' => 'following_diet',
            'diet_summary' => 'diet_summary',
            'other_diet' => 'other_diet',
            'diet_duration' => 'diet_duration',
            'food_allergies' => 'food_allergies',
            'food_allergy_summary' => 'food_allergy_summary',
            'food_allergy_other' => 'food_allergy_other',
            'food_sensitivity' => 'food_sensitivity',
            'sensitivity_summary' => 'sensitivity_summary',
            'sensitivity_other' => 'sensitivity_other',
            'supplements' => 'supplements',
            'supplement_summary' => 'supplement_summary',
            'other_vitamins' => 'other_vitamins',
            'supplements_other' => 'supplements_other',
            'daily_water_servings' => 'daily_water_servings',
            'daily_protein_servings' => 'daily_protein_servings',
            'daily_vegetable_servings' => 'daily_vegetable_servings',
            'nutritional_knowledge' => 'nutritional_knowledge',
            'diagnosed_medical_problems' => 'diagnosed_medical_problems',
            'medical_issues' => 'medical_issues',
            'on_prescriptions' => 'medical_issues',
            'prescription_summary' => 'prescription_summary',
            'other_treatments' => 'other_treatments',
            'treatment_summary' => 'treatment_summary',
            'working' => 'working',
            'work_type' => 'work_type',
            'working_when' => 'working_when',
            'typical_hours_worked' => 'typical_hours_worked',
            'work_activity_level' => 'work_activity_level',
            'work_stress' => 'work_stress',
            'work_travel' => 'work_travel',
            'student' => 'student',
            'school_stress' => 'school_stress',
            'caregiver' => 'caregiver',
            'caregiver_for' => 'caregiver_for',
            'caregiver_stress' => 'caregiver_stress',
            'committed_relationship' => 'committed_relationship',
            'partner' => 'partner',
            'children' => 'children',
            'children_count' => 'children_count',
            'child_name_age' => 'child_name_age',
            'pets' => 'pets',
            'pet_count' => 'pet_count',
            'pet_names_types' => 'pet_names_types',
            'home_stress' => 'home_stress',
            'stress_coping' => 'stress_coping',
            'vacations' => 'vacations',
            'hobbies' => 'hobbies',
            'alcohol' => 'alcohol',
            'smoking' => 'smoking',
            'non_prescriptiondrugs' => 'non_prescriptiondrugs',
            'program_expectations' => 'program_expectations',
            'coach_expectations' => 'coach_expectations',
            'more_info' => 'more_info',
            'photo_consent' => 'photo_consent',
            'research_consent' => 'research_consent',
            'medical_release' => 'medical_release'
        );
    }

    private function loadFields( $name ) {

        switch ( $name ) {

            case 'measurements':
                $this->loadMeasurementFields();
                break;

            case 'program':
                $this->loadProgramFields();
                break;

            case 'checkin':
                $this->loadCheckinFields();
                break;

            case 'client_info':
                $this->loadClientDataFields();
                break;
        }
    }

    public function getTable( $name = null, $force = false  ) {

        if ( ! $name )  {
            return $this->tables;
        }

        if ( empty ( $this->tables->{$name} ) ) {
            throw new Exception( "No {$name} table exists" );
        }

        return $this->tables->{$name};
    }

    public function getFields( $name = null, $force = false ) {

        if ( ( empty( $this->fields[ $name ] ) ) || ( $force)  ) {

            $this->loadFields( $name );
        }

        return $this->fields[$name];
    }

    public function createTables() {

    }
}
endif;