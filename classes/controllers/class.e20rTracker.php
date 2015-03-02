<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rTracker {

    private $client = null;

    protected $settings = array();
    protected $setting_name = 'e20r-tracker';

    public $tables;
    private $model;

    public $managed_types = array( 'post', 'page');

    private $hooksLoaded = false;

    public function e20rTracker() {

        $this->model = new e20rTrackerModel();

        // Set defaults (in case there are none saved already
        $this->settings = get_option( $this->setting_name, array(
                            'delete_tables' => false,
                            'purge_tables' => false,
                            'measurement_day' => CONST_SATURDAY,
                            'lesson_source' => null,
            )
        );

        dbg("e20rTracker::constructor() - Loading the types of posts we'll be allowed to manage");
        $this->managed_types = apply_filters("e20r-tracker-post-types", array("post", "page") );

//        dbg("e20rTracker::constructor() - Loading e20rTables class");
//        $this->tables = new e20rTables();

    }

    public function loadAllHooks() {

        global $current_user;
        global $pagenow;

        global $e20rClient;
        global $e20rMeasurements;
        global $e20rArticle;
        global $e20rAssignment;
        global $e20rCheckin;
        global $e20rExercise;
        global $e20rProgram;
        global $e20rWorkout;

        if ( ! $this->hooksLoaded ) {

            dbg("e20rTracker::loadAllHooks() - Adding action hooks for plugin");

            add_action( 'init', array( &$this, "dependency_warnings" ), 10 );
            add_action( "init", array( &$this, "e20r_tracker_girthCPT" ), 10 );
            add_action( "init", array( &$this, "e20r_tracker_assignmentsCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_articleCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_programCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_exerciseCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_activitiesCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_actionCPT"), 10 );

	        add_filter('manage_e20r_assignments_posts_columns', array( &$this, 'assignment_col_head' ) );
	        add_action('manage_e20r_assignments_posts_custom_column', array( &$this, 'assignment_col_content' ), 10, 2);

            dbg("e20rTracker::loadAllHooks() - Load upload directory filter? ". $e20rClient->isNourishClient( $current_user->ID));
            dbg("e20rTracker::loadAllHooks() - Pagenow = {$pagenow}" );

            if ( ( $pagenow == 'async-upload.php' || $pagenow == 'media-upload.php') )  {
                dbg("e20rTracker::loadAllHooks() - Loading filter to change the upload directory for Nourish clients");
                // add_filter( 'media-view-strings', array( &$e20rMeasurements, 'clientMediaUploader' ) );
                add_filter( 'upload_dir', array( &$e20rMeasurements, 'set_progress_upload_dir' ) );
            }

            /* Control access to the media uploader for Nourish users */
            // add_action( 'parse_query', array( &$this, 'current_user_only' ) );
            add_action( 'pre_get_posts', array( &$this, 'restrict_media_library') );
            // add_filter( 'media_view_settings', array( &$this, 'media_view_settings'), 99 );
            add_filter( 'wp_handle_upload_prefilter', array( &$e20rMeasurements, 'setFilenameForClientUpload' ) );

            add_filter( 'page_attributes_dropdown_pages_args', array( &$e20rExercise, 'changeSetParentType'), 10, 2);
            add_filter( 'enter_title_here', array( &$this, 'setEmptyTitleString' ) );

            // add_filter( 'media_upload_default_tab', array( &$this, 'default_media_tab') );

            /* Load scripts & CSS */
            add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_scripts') );
            add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_frontend_css') );
            // add_action( 'wp_footer', array( &$this, 'enqueue_user_scripts' ) );

            // add_action( 'wp_print_scripts', array( &$e20rClient, 'load_scripts' ) );
            // add_action( '', array( $e20rClient, 'save_gravityform_entry'), 10, 2 );
            add_action( 'wp_ajax_updateUnitTypes', array( &$e20rClient, 'updateUnitTypes') );
            add_action( 'wp_ajax_e20r_clientDetail', array( &$e20rClient, 'ajax_clientDetail' ) );
            add_action( 'wp_ajax_e20r_complianceData', array( &$e20rClient, 'ajax_complianceData' ) );
            add_action( 'wp_ajax_e20r_assignmentData', array( &$e20rClient, 'ajax_assignmentData' ) );
            add_action( 'wp_ajax_get_memberlistForLevel', array( &$e20rClient, 'ajax_getMemberlistForLevel' ) );
            add_action( 'wp_ajax_e20r_userinfo', array( &$e20rClient, 'ajax_userInfo_callback' ) );
            add_action( 'wp_ajax_e20r_loadProgress', array( &$e20rMeasurements, 'ajax_loadProgressSummary' ) );
            add_action( 'wp_ajax_saveMeasurementForUser', array( &$e20rMeasurements, 'saveMeasurement_callback' ) );
            add_action( 'wp_ajax_checkCompletion', array( &$e20rMeasurements, 'checkProgressFormCompletion_callback' ) );
            add_action( 'wp_ajax_e20r_measurementDataForUser', array( &$e20rMeasurements, 'ajax_getPlotDataForUser' ) );
            add_action( 'wp_ajax_deletePhoto', array( &$e20rMeasurements, 'ajax_deletePhoto_callback' ) );
            add_action( 'wp_ajax_addPhoto', array( &$e20rMeasurements, 'ajax_addPhoto_callback' ) );
            add_action( 'wp_ajax_addWorkoutGroup', array( &$e20rWorkout, 'ajax_addGroup_callback' ) );
            add_action( 'wp_ajax_getDelayValue', array( &$e20rArticle, 'getDelayValue_callback' ) );
            add_action( 'wp_ajax_saveCheckin', array( &$e20rCheckin, 'saveCheckin_callback' ) );
            add_action( 'wp_ajax_daynav', array( &$e20rCheckin, 'nextCheckin_callback' ) );
            add_action( 'wp_ajax_e20r_addAssignment', array( &$e20rArticle, 'add_assignment_callback') );
	        add_action( 'wp_ajax_e20r_removeAssignment', array( &$e20rArticle, 'remove_assignment_callback') );
	        add_action( 'wp_ajax_save_daily_progress', array( $e20rCheckin, 'dailyProgress_callback' ) );
	        add_action( 'wp_ajax_save_daily_checkin', array( $e20rCheckin, 'dailyCheckin_callback' ) );


            add_action( 'wp_ajax_get_checkinItem', array( &$e20rCheckin, 'ajax_getCheckin_item' ) );
            add_action( 'wp_ajax_save_item_data', array( &$e20rCheckin, 'ajax_save_item_data' ) );

            add_action( 'save_post', array( &$this, 'save_girthtype_order' ), 10, 2 );
            add_action( 'save_post', array( &$e20rProgram, 'saveSettings' ), 10, 2 );
            add_action( 'save_post', array( &$e20rExercise, 'saveSettings' ), 10, 2 );
            add_action( 'save_post', array( &$e20rWorkout, 'saveSettings' ), 10, 2 );
            add_action( 'save_post', array( &$e20rCheckin, 'saveSettings' ), 10, 20);
            add_action( 'save_post', array( &$e20rArticle, 'saveSettings' ), 10, 20);
            add_action( 'save_post', array( &$e20rAssignment, 'saveSettings' ), 10, 20);

            add_action( 'post_updated', array( &$e20rProgram, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rExercise, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rWorkout, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rCheckin, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rArticle, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rAssignment, 'saveSettings' ) );

            add_action( 'wp_enqueue_scripts', array( &$this, 'has_weeklyProgress_shortcode' ) );
            add_action( 'wp_enqueue_scripts', array( &$this, 'has_measurementprogress_shortcode' ) );
            add_action( 'wp_enqueue_scripts', array( &$this, 'has_dailyProgress_shortcode' ) );

            add_action( 'add_meta_boxes_e20r_articles', array( &$e20rArticle, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes_e20r_assignment', array( &$e20rAssignment, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes_e20r_programs', array( &$e20rProgram, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes_e20r_exercises', array( &$e20rExercise, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes_e20r_workout', array( &$e20rWorkout, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes_e20r_checkins', array( &$e20rCheckin, 'editor_metabox_setup') );

            add_action( 'admin_init', array( &$this, 'registerSettingsPage' ) );

            add_action( 'admin_head', array( &$this, 'post_type_icon' ) );
            add_action( 'admin_menu', array( &$this, 'loadAdminPage') );
            add_action( 'admin_menu', array( &$this, 'registerAdminPages' ) );
            add_action( 'admin_menu', array( &$this, "renderGirthTypesMetabox" ) );

            /* AJAX call-backs if user is unprivileged */
            add_action( 'wp_ajax_nopriv_e20r_clientDetail', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_e20r_complianceData', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_e20r_assignmentData', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_e20r_measurementData', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_updateUnitTypes', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_saveMeasurementForUser', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_checkCompletion', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_e20r_measurementDataForUser', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_deletePhoto', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_addPhoto', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_addWorkoutGroup', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_getDelayValue', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_saveCheckin', 'e20r_ajaxUnprivError' );
            add_action( 'wp_ajax_nopriv_daynav', 'e20r_ajaxUnprivError' );
	        add_action( 'wp_ajax_nopriv_e20r_addAssignment', 'e20r_ajaxUnprivError' );
	        add_action( 'wp_ajax_nopriv_e20r_removeAssignment', 'e20r_ajaxUnprivError' );


	        // TODO: Investigate the need for this.
            // add_action( 'add_meta_boxes', array( &$this, 'editor_metabox_setup') );

            /* Allow admin to set the program ID for the user in their profile(s) */
            add_action( 'show_user_profile', array( &$e20rProgram, 'selectProgramForUser' ) );
            add_action( 'edit_user_profile_update', array( &$e20rProgram, 'updateProgramForUser') );
            add_action( 'personal_options_update', array( &$e20rProgram, 'updateProgramForUser') );

            /* Gravity Forms data capture for Check-Ins, Assignments, Surveys, etc */
            add_action( 'gform_after_submission', array( &$this, 'gravityform_submission' ), 10, 2);

            add_shortcode( 'weekly_progress', array( &$e20rMeasurements, 'shortcode_weeklyProgress' ) );
            add_shortcode( 'progress_overview', array( &$e20rMeasurements, 'shortcode_progressOverview') );
            add_shortcode( 'daily_progress', array( &$e20rCheckin, 'shortcode_dailyProgress' ) );

            add_filter( 'the_content', array( &$e20rArticle, 'contentFilter' ) );

            if ( function_exists( 'pmpro_activation' ) ) {
                add_filter( 'pmpro_after_change_membership_level', array( &$this, 'setUserProgramStart') );
            }

            dbg("e20rTracker::loadAllHooks() - Action hooks for plugin are loaded");
        }

        $this->hooksLoaded = true;
    }

	public function assignment_col_head( $defaults ) {

		$defaults['used_day'] = 'Use on';
		return $defaults;
	}

	public function assignment_col_content( $colName, $post_ID ) {

		global $e20rAssignment;
		global $currentAssignment;

		dbg( "e20rTracker::assignment_col_content() - ID: {$post_ID}" );

		if ( $colName == 'used_day' ) {

			$post_releaseDay = $e20rAssignment->getDelay( $post_ID );

			dbg( "e20rTracker::assignment_col_content() - Used on day #: {$post_releaseDay}" );
			if ($post_releaseDay ) {
				echo 'Day ' . $post_releaseDay;
			}
		}
	}

    public function sanitize( $field ) {

        if ( ! is_numeric( $field ) ) {
            // dbg( "setFormat() - {$value} is NOT numeric" );

            if ( is_array( $field ) ) {

                foreach( $field as $key => $val ) {
                    $field[$key] = $this->sanitize( $val );
                }
            }

            if ( is_object( $field ) ) {

                foreach( $field as $key => $val ) {
                    $field->{$key} = $this->sanitize( $val );
                }
            }

            if ( (! is_array( $field ) ) && ctype_alpha( $field ) ||
                 ( (! is_array( $field ) ) && strtotime( $field ) ) ||
                 ( (! is_array( $field ) ) && is_string( $field ) ) ) {

                $field = sanitize_text_field( $field ) ;
            }

        }
        else {

            if ( is_float( $field + 1 ) ) {

                $field = sanitize_text_field( $field );
            }

            if ( is_int( $field + 1 ) ) {

                $field = intval( $field );
            }
        }

        return $field;
    }

    public function setEmptyTitleString( $title ) {

        $screen = get_current_screen();

        switch ( $screen->post_type ) {
            case 'e20r_exercises':

                $title = 'Enter Exercise Name Here';
                break;

            case 'e20r_programs':

                $title = 'Enter Program Name Here';
                remove_meta_box( 'postexcerpt', 'e20r_programs', 'side' );
                add_meta_box('postexcerpt', __('Summary'), 'post_excerpt_meta_box', 'e20r_programs', 'normal', 'high');

                break;

            case 'e20r_workout':

                $title = 'Enter Workout Name Here';
	            remove_meta_box( 'postexcerpt', 'e20r_workout', 'side' );
	            add_meta_box('postexcerpt', __('Summary'), 'post_excerpt_meta_box', 'e20r_workout', 'normal', 'high');

	            break;

            case 'e20r_checkins':

                $title = 'Enter Action Short-code Here';
                remove_meta_box( 'postexcerpt', 'e20r_checkins', 'side' );
                add_meta_box('postexcerpt', __('Action text'), 'post_excerpt_meta_box', 'e20r_checkins', 'normal', 'high');

                break;

            case 'e20r_articles':

                $title = 'Enter Article Prefix Here';

                break;

            case 'e20r_assignments':

                $title = "Enter Assignment/Question Here";
                remove_meta_box( 'postexcerpt', 'e20r_assignments', 'side' );
                add_meta_box('postexcerpt', __('Assignment description'), 'post_excerpt_meta_box', 'e20r_assignments', 'normal', 'high');


        }

        dbg("e20rTracker::setEmptyTitleString() - New title string defined");
        return $title;
    }

    public function dependency_warnings() {

        if ( ( ! class_exists( 'PrsoGformsAdvUploader' ) ) || ( ! class_exists('PMProSequence')) ) {

            ?>
            <div class="error">
            <?php if ( ! class_exists('PrsoGformsAdvUploader') ): ?>
                <?php dbg("e20rTracker::Error -  The Gravity Forms Advanced Uploader plugin is not installed"); ?>
                <p><?php _e( "Eighty / 20 Tracker - Missing dependency: Gravity Forms Advanced Uploader plugin", 'e20rtracker' ); ?></p>
            <?php endif; ?>
            <?php if ( ! class_exists('PMProSequence') ): ?>
                <?php dbg("e20rTracker::Error -  The PMPro Sequence plugin is not installed"); ?>
                <p><?php _e( "Eighty / 20 Tracker - Missing dependency: PMPro Sequence plugin", 'e20rtracker' ); ?></p>
            <?php endif; ?>
            </div><?php
        }
    }

    public function current_user_only( $wp_query ) {

        if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin/upload.php' )
            || strpos( $_SERVER[ 'REQUETST_URI' ], 'wp-admin/edit.php' )
            || strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin/ajax-upload.php' ) ) {

            dbg("e20rTracker::current_user_only - Uploading files...");

            if ( current_user_can( 'upload_files' )) {
                global $current_user;
                $wp_query->set( 'author', $current_user->id );
            }
        }
    }

    public function restrict_media_library( $wp_query_obj) {

        global $current_user, $pagenow;

        if ( !is_a( $current_user, "WP_User") ) {

            return;
        }

        if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) {
            return;
        }

        if( ! current_user_can( 'manage_media_library') ) {
            $wp_query_obj->set( 'author', $current_user->ID );
        }

        return;
    }

    /*
    public function media_view_settings( $settings, $post ) {

        global $e20rMeasurementDate, $e20rTracker;

//        unset( $settings['mimeTypes']['audio'] );
//        unset( $settings['mimeTypes']['video'] );

        dbg("e20rTracker::media_view_settings() - Measurement date: {$e20rMeasurementDate}");

        $monthYear = date('F Y',strtotime( $e20rMeasurementDate ) );
        $settings['currentMonth'] = $monthYear;

        foreach ( $settings['months'] as $key => $month ) {

            if ( $month->text != $monthYear ) {
                unset( $settings['months'][$key]);
            }
        }

        dbg("e20rTracker::media_view_settings() - Now using: ");
        dbg($settings);

        return $settings;
    }
*/
    public function default_media_tab( $tabName ) {

        if ( isset( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['post_id'] ) ) {
            $post_type = get_post_type( absint( $_REQUEST['post_id'] ) );

            if ( $post_type ) {
                if ( 'page' == $post_type ) {
                    dbg("e20rTracker::default_media_tab: {$tabName}");
                    // return 'type';
                }
            }
        }

        return $tabName;
    }

    public function encryptData( $data ) {

        if ( ! class_exists( 'GDS_Encryption_Class' ) ) {

            dbg("e20rTrackeModel::encryptData() - Unable to load encryption engine!");
            throw new Exception( 'GDS Encryption Engine not found!');

            return false;
        }

        return GDS_Encryption_Class::encrypt( $data );
    }

    public function decryptData( $encData ) {

        if ( ! class_exists( 'GDS_Encryption_Class' ) ) {
            dbg("e20rTrackeModel::decryptData() - Unable to load decryption engine!");
            throw new Exception( 'GDS Decryption Engine not found!');
            return false;
        }

        return GDS_Encryption_Class::decrypt( $encData );
    }

    public function gravityform_submission( $submitted, $form ) {

        dbg("e20rTracker::gravityform_submission() - Start");
        // dbg($form);
        // dbg($submitted);

        global $e20rMeasurements;
        global $current_user;
        global $e20rProgram;
        global $e20rClient;

        $userId = $current_user->ID;
        $userProgramId = $e20rProgram->getProgramIdForUser( $userId );
        $userProgramStart = $e20rProgram->startdate( $userId );

        dbg("e20rTracker::gravityform_submission() - Processing ");

        $db_Data = array(
            'user_id' => $userId,
            'program_id' => $userProgramId,
            'program_start' => $userProgramStart,
            'program_photo_dir'=> 'e20r-pics/',
        );

        if ( stripos( $form['title'], 'welcome' ) ) {
            dbg("e20rTracker::gravityform_submission - Processing the Welcome Interview form");

            foreach( $form['fields'] as $item ) {

                $skip = false;

                if ( ! in_array( $item['type'], array( 'section' ) ) ) {

                    $fieldName = $item['label'];
                    $subm_key = $item['id'];

                    if ( $item['type'] == 'checkbox' ) {

                        foreach( $item['inputs'] as $k => $i ) {

                            if ( !empty( $submitted[$i['id']] ) ) {
                                $subm_key = $i['id'];
                            }
                        }
                    }

                    if ( ( $item['label'] == 'calculated_weigth_lbs') && ( ! empty( $submitted[$item['id']] ) )  ) {

                        $e20rMeasurements->saveMeasurement( 'weight', $submitted[$item['id']], -1, $userProgramId, $userProgramStart, $userId );
                    }

                    if ( ( $item['label'] == 'calculated_weigth_kg') && ( ! empty( $submitted[$item['id']] ) )  ) {

                        $e20rMeasurements->saveMeasurement( 'weight', $submitted[$item['id']], -1, $userProgramId, $userProgramStart, $userId );
                    }

                    if ( $item['type'] == 'survey' ) {

                        $key = $submitted[$item['id']];
                        $subm_key = $item['id'];

                        if ( $item['type'] == 'likert' ) {

                            foreach( $item['choices'] as $k => $i ) {

                                if ( $key == $i['value']) {

                                    $submitted[$subm_key] = $item['choices'][$k]['score'];
                                }
                            }
                        }
                    }

                    if ( $item['type'] == 'address' ) {

                        $key = $item['id'];

                        foreach( $item['inputs'] as $k => $aItem ) {

                            $splt = preg_split( "/\./", $aItem['id'] );

                            switch ( $splt[1] ) {
                                case '1':
                                    $fieldName = 'address_1';
                                    break;

                                case '2':
                                    $fieldName = 'address_1';
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

                            $db_Data[$fieldName] = $submitted[$aItem['id']];
                            $skip = true;
                        }
                    }

                    // if ( $item['type'] == 'radio' ) {
                        // TODO Grab $item['chocies'][0-N]['text'] when the $item['chocies'][0-N]['value'] == $submitted[$subm_key]
                    //}
                    if ( ! $skip ) {

                        $db_Data[ $fieldName ] = $submitted[ $subm_key ];
                    }

                    dbg("{$fieldName} => {$submitted[$subm_key]}");
                }
            }
        }

        if ( stripos( $form['title'], 'assignment' ) ) {

            foreach ( $submitted as $key => $entry ) {

                if ( strpos( $form['fields'][$key]['label'], 'Day' ) ) {
                    $assignmentDay = $entry;
                }

                if ( stripos($form['fields'][$key]['label'], 'date' ) ) {
                    $assignmentDate = $entry;
                }

                if ( strpos( $form['fields'][$key]['label'], 'Assignment' ) ) {
                    $answer = $entry;
                }
            }

            dbg("e20rTracker::gravityform_submission - Processing Assignment form for day {$form['title']}");
            dbg("e20rTracker::gravityform_submission - Day: {$assignmentDay}" );
            dbg("e20rTracker::gravityform_submission - Date: {$assignmentDate}");
            dbg("e20rTracker::gravityform_submission - Answer: {$answer}");
        }

        if ( stripos( $form['title'], 'Habit' ) ) {

            foreach ( $submitted as $key => $entry ) {

                if ( strpos( $form['fields'][$key]['label'], 'checkin_day' ) ) {
                    $checkin_day = $entry;
                }

                if ( stripos($form['fields'][$key]['label'], 'date' ) ) {
                    $checkin_date = $entry;
                }

                if ( strpos($form['fields'][$key]['label'], 'short_code' ) ) {
                    $short_code = $entry;
                }

                if ( strpos( $form['fields'][$key]['label'], 'checkedin' ) ) {
                    $checkedin = $entry;
                }
            }

            dbg("e20rTracker::gravityform_submission - Processing Assignment form for day {$form['title']}");
            dbg("e20rTracker::gravityform_submission - Day: {$checkin_day}" );
            dbg("e20rTracker::gravityform_submission - Date: {$checkin_date}");
            dbg("e20rTracker::gravityform_submission - Habit: {$short_code}");
            dbg("e20rTracker::gravityform_submission - Status: {$checkedin}");
        }

        if ( ! empty( $db_Data['first_name'] ) ) {

            if ( ! $e20rClient->saveInterviewData( $db_Data ) ) {
                throw new Exception( "Unable to save the data from the Welcome Interview form" );
            }
        }
    }

    public function updateSetting( $name, $value ) {

        if ( array_key_exists( $name, $this->settings ) ) {
            $this->settings[ $name ] = $value;
            update_option( $this->setting_name, $this->settings);
            return true;
        }
        else {
            dbg("Error: The {$name} setting does not exist!");
        }

        return false;
    }

    public function save_girthtype_order( $post_id ) {

        global $post;

        dbg("save_gt_order() - Running save functionality");

        if ( ( ! isset( $post->post_type ) ) || ( $post->post_type != 'e20r_girth_types') ) {
            return $post_id;
        }

        if ( empty( $post_id ) ) {
            dbg("save_girthtype_order() No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return $post_id;
        }

        dbg("save_girthtype_order() - Saving metadata for the post_type(s)");
        /*        $girth_orders = count( $_POST['e20r_girth_order']) ? $_POST[ 'e20r_girth_order' ] : array();
                $girth_types = count( $_POST['e20r_girth_type']) ? $_POST[ 'e20r_girth_type' ] : array();
        */
        if ( isset( $_POST[ 'e20r-girth-type-sortorder-nonce' ] ) ) {

            $newOrder = isset( $_POST['e20r_girth_order'] ) ? $_POST['e20r_girth_order'] : null;
            update_post_meta( $post_id, 'e20r_girth_type_sortorder', $newOrder );
        }

    }

    public static function enqueue_admin_scripts( $hook ) {

        dbg("e20rTracker::enqueue_admin_scripts() - Loading javascript");

        global $e20rAdminPage;
        global $post;

        if( $hook == $e20rAdminPage ) {

            global $e20r_plot_jscript, $e20rTracker;

            self::load_adminJS();

            $e20r_plot_jscript = true;
            self::register_plotSW();
            self::enqueue_plotSW();
            $e20r_plot_jscript = false;

            wp_enqueue_style( 'e20r_tracker', E20R_PLUGINS_URL . '/css/e20r-tracker.css' );
            wp_enqueue_style( 'select2', "//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/select2.min.css" );
            wp_enqueue_script( 'jquery.timeago' );
            wp_enqueue_script( 'select2' );

        }

        if( $hook == 'edit.php' || $hook == 'post.php' || $hook == 'post-new.php' ) {

            switch( self::getCurrentPostType() ) {

                case 'e20r_checkins':

                    wp_enqueue_script( 'jquery-ui-datepicker' );
                    wp_enqueue_style( 'jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

                    $type = 'checkin';
                    $deps = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker');
                    break;

                case 'e20r_programs':

	                $type = 'program';
                    break;

                case 'e20r_articles':

                    $type = 'article';
                    $deps = array( 'jquery', 'jquery-ui-core' );

                    break;

	            default:
		            $type = null;
            }

            dbg("e20rTracker::enqueue_admin_scripts() - Loading Custom Post Type specific admin script");

	        if ( $type !== null ) {

		        wp_register_script( 'e20r-cpt-admin', E20R_PLUGINS_URL . "/js/e20r-{$type}-admin.js", $deps, '1.0', true );

		        /* Localize ajax script */
		        wp_localize_script( 'e20r-cpt-admin', 'e20r_tracker',
			        array(
				        'ajaxurl' => admin_url( 'admin-ajax.php' ),
				        'lang'    => array(
					        'saving' => __( 'Adding...', 'e20rtracker' ),
					        'save'   => __( 'Add', 'e20rtracker' ),
					        'edit'   => __( 'Update', 'e20rtracker' ),
				        ),
			        )
		        );

		        wp_enqueue_script( 'e20r-cpt-admin' );
	        }
        }
    }

    public function validate( $input ) {

        $valid = array();

        dbg('Running validation: ' . print_r( $input, true ) );

        foreach ( $input as $key => $value ) {

            $valid[$key] = apply_filters( 'e20r_settings_validation_' . $key, $value );

        }

        /*
         * Use add_settings_error( $title (title of setting), $errorId (text identifier), $message (Error message), 'error' (type of message) ); if needed
         */

        unset( $input ); // Free.

        return $valid;
    }

    public function validate_bool( $value ) {

        return ( 1 == intval( $value ) ? 1 : 0);
    }

    public function loadAdminPage() {

        add_options_page( 'Eighty / 20 Tracker', 'E20R Tracker', 'manage_options', 'e20r_tracker_opt_page', array( $this, 'render_settings_page' ) );

        // $this->registerAdminPages();

    }

    public function registerSettingsPage() {

        // Register any global settings for the Plugin
        register_setting( 'e20r_options', $this->setting_name, array( $this, 'validate' ) );

        add_settings_section( 'e20r_tracker_deactivate', 'Deactivation settings', array( &$this, 'render_section_text' ), 'e20r_tracker_opt_page' );

        /* Add fields for the settings */
        add_settings_field( 'e20r_tracker_purge_tables', __("Clear tables", 'e20r_tracker'), array( $this, 'render_purge_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');
        add_settings_field( 'e20r_tracker_delete_tables', __("Delete tables", 'e20r_tracker'), array( $this, 'render_delete_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');
        add_settings_field( 'e20r_tracker_measurement_day', __("Day to record progress", 'e20r_tracker'), array( $this, 'render_measurementday_select'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');
        add_settings_field( 'e20r_tracker_lesson_source', __("Drip Feed managing lessons", 'e20r_tracker'), array( $this, 'render_lessons_select'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');

        // add_settings_field( 'e20r_tracker_measured', __('Progress measurements', 'e20r_tracker'), array( $this, 'render_measurement_list'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate' );

        // $this->render_settings_page();

    }

    public function render_lessons_select() {

        $options = get_option( $this->setting_name );
        $sequences = new WP_Query( array(
            "post_type" => "pmpro_sequence",
        ) );

        ?>
        <select name="<?php echo $this->setting_name; ?>[lesson_source]" id="<?php echo $this->setting_name; ?>_lesson_source">
            <option value="0" <?php selected(0, $options['lesson_source']); ?>>Not Specified</option> <?php
            while ( $sequences->have_posts() ) : $sequences->the_post(); ?>
                <option	value="<?php echo the_ID(); ?>" <?php echo selected( the_ID(), $options['lesson_source'] ); ?> ><?php echo the_title_attribute(); ?></option><?php
            endwhile;
            wp_reset_postdata(); ?>
        </select>
        <?php
    }
    public function render_delete_checkbox() {

        $options = get_option( $this->setting_name );
        ?>
        <input type="checkbox" name="<?php echo $this->setting_name; ?>[delete_tables]" value="1" <?php checked( 1, $options['delete_tables'] ) ?> >
        <?php
    }

    public function render_purge_checkbox() {


        $options = get_option( $this->setting_name );
        ?>
        <input type="checkbox" name="<?php echo $this->setting_name; ?>[purge_tables]" value="1" <?php checked( 1, $options['purge_tables'] ) ?> >
    <?php
    }

    public function render_measurementday_select() {

        $options = get_option( $this->setting_name );
        ?>
        <select name="<?php echo $this->setting_name; ?>[measurement_day]" id="<?php echo $this->setting_name; ?>_measurement_day">
        <option value="0" <?php selected( 0, $options['measurement_day']); ?>>Sunday</option>
        <option value="1" <?php selected( 1, $options['measurement_day']); ?>>Monday</option>
        <option value="2" <?php selected( 2, $options['measurement_day']); ?>>Tuesday</option>
        <option value="3" <?php selected( 3, $options['measurement_day']); ?>>Wednesday</option>
        <option value="4" <?php selected( 4, $options['measurement_day']); ?>>Thursday</option>
        <option value="5" <?php selected( 5, $options['measurement_day']); ?>>Friday</option>
        <option value="6" <?php selected( 6, $options['measurement_day']); ?>>Saturday</option>
        <?php
    }

    public function render_section_text() {

        $html = "<p>These settings will determine the behavior of the plugin during deactivation.</p>";

        echo $html;
    }

    public function render_settings_page() {

        ob_start();
        ?>
        <div id="e20r-settings">
            <div class="wrap">
                <h2>Settings: Eighty / 20 Tracker</h2>
                <form method="post" action="options.php">
                    <?php settings_fields( 'e20r_options' ); ?>
                    <?php do_settings_sections( 'e20r_tracker_opt_page' ); ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
                    </p>
                </form>
            </div>
        </div>
    <?php
        $html = ob_get_clean();

        echo $html;
    }

    public function registerAdminPages() {

        global $e20rClient, $e20rProgram, $e20rCheckin;
        global $e20rAdminPage;

        dbg("e20rTracker::registerAdminPages() - Loading E20R Tracker Admin Menu");

        $e20rAdminPage = add_menu_page( 'E20R Tracker', __( 'E20R Tracker','e20r_tracker'), 'manage_options', 'e20r-tracker', array( &$e20rClient, 'render_client_page' ), 'dashicons-admin-generic', '71.1' );
        add_submenu_page( 'e20r-tracker', __( 'Client Data','e20r_tracker'), __( 'Client Data','e20r_tracker'), 'manage_options', 'e20r-tracker', array( &$e20rClient, 'render_client_page' ));

//        add_submenu_page( 'e20r-tracker', __( 'Check-in Item','e20r_tracker'), __('Check-in Items','e20r_tracker'), 'manage_options', "e20r-tracker-list-items", array( &$e20rCheckin, 'render_submenu_page'));
//        add_submenu_page( 'e20r-tracker', __( 'Program','e20r_tracker'), __('Programs','e20r_tracker'), 'manage_options', "e20r-tracker-list-programs", array( &$e20rProgram, 'render_submenu_page'));
//        add_submenu_page( 'e20r-tracker', __( 'Articles','e20r_tracker'), __('Articles','e20r_tracker'), 'manage_options', "e20-tracker-list-articles", array( &$e20rArticle,'render_submenu_page') );
//        add_submenu_page( 'e20r-tracker', __('Measurements','e20r_tracker'), __('Measurements','e20r_tracker'), 'manage-options', "e20r_tracker_measure", array( &$this,'render_measurement_page' ));
//        add_submenu_page( 'e20r-tracker', __('Manage Program','e20r_tracker'), __('Add Program','e20r_tracker'), 'manage_options', "e20r-add-new-program", array( &$this,'render_new_program_page'));
//        add_submenu_page( 'e20r-tracker', __('Settings','e20r_tracker'), __('Settings','e20r_tracker'), 'manage_options', "e20r-tracker-settings", array( &$this, 'registerSettingsPage'));
//        add_submenu_page( 'e20r-tracker', __('Check-in Items','e20r_tracker'), __('Items','e20r_tracker'), 'manage-options', 'e20r-items', array( &$this, 'render_management_page' ) );
//        add_submenu_page( 'e20r-tracker', __('Meals','e20r_tracker'), __('Meal History','e20r_tracker'), 'manage_options', "e20r_tracker_meals", array( &$this,'render_meals_page'));
//        add_action( "admin_print_scripts-$page", array( 'e20rTracker', 'load_adminJS') ); // Load datepicker, etc (see apppontments+)
    }

    public function renderGirthTypesMetabox() {

        add_meta_box('e20r_tracker_girth_types_meta', __('Sort order for Girth Type', 'e20rtracker'), array(&$this, "build_girthTypesMeta"), 'e20r_girth_types', 'side', 'high');
    }

    public function build_girthTypesMeta( $object, $box ) {

        global $post;

        $sortOrder = get_post_meta( $post->ID, 'e20r_girth_type_sortorder', true);
        dbg("post-meta sort Order: {$sortOrder}");
        ob_start();
        ?>
        <div class="submitbox" id="e20r-girth-type-postmeta">
            <div id="minor-publishing">
                <div id="e20r-tracker-postmeta">
                    <?php dbg("Loading metabox for Girth Type postmeta"); ?>
                    <?php wp_nonce_field('e20r-tracker-post-meta', 'e20r-girth-type-sortorder-nonce'); ?>
                    <label for="e20r-sort-order"><?php _e("Sort Order", "e20rtracker"); ?></label>
                    <input type="text" name="e20r_girth_order" id="e20r-sort-order" value="<?php echo $sortOrder; ?>">
                </div>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }

    /**
     * @param $startDate -- First date
     * @param $endDate -- End date
     * @param $weekdayNumber -- Day of the week (0 = Sun, 6 = Sat)
     *
     * @return array -- Array of days for the measurement(s).
     */
    public function datesForMeasurements( $startDate = null, $weekdayNumber = null ) {

        $dateArr = array();

        dbg("e20rTracker::datesForMeasurements(): {$startDate}, {$weekdayNumber}");

        if ( ( $startDate != null ) && ( strtotime( $startDate ) ) ) {

            dbg("e20rTracker::datesForMeasurements() - Received a valid date value: {$startDate}");
            $baseDate = " {$startDate}";
        }
	    else {

		    $baseDate = " " . date('Y-m-d', current_time('timestamp') );
	    }

	    dbg("e20rTracker::datesForMeasurements() - Using {$baseDate} as 'base date'");

        if ( ! $weekdayNumber ) {

            dbg("e20rTracker::datesForMeasurements() - Using option value(s)");
            $options = get_option( $this->setting_name );
            $weekdayNumber = $options['measurement_day'];
        }

        switch ( $weekdayNumber ) {
            case 1:
                $day = 'Monday';
                break;
            case 2:
                $day = 'Tuesday';
                break;
            case 3:
                $day = 'Wednesday';
                break;
            case 4:
                $day = 'Thursday';
                break;
            case 5:
                $day = 'Friday';
                break;
            case 6:
                $day = 'Saturday';
                break;
            case 0:
                $day = 'Sunday';
                break;
        }

        $dateArr['current'] = date( 'Y-m-d', strtotime( "this {$day}" . $baseDate ) );
        $dateArr['next'] = date( 'Y-m-d', strtotime( "next {$day}" . $baseDate ) );

        $dateArr['last_week'] = date( 'Y-m-d', strtotime( "last {$day}" . $baseDate ) );

        if ( $dateArr['current'] == $dateArr['next'] ) {

            $dateArr['current'] = date( 'Y-m-d', strtotime( "last {$day}"  . $baseDate) );
            $dateArr['last_week'] = date( 'Y-m-d', strtotime( "-2 weeks {$day}" . $baseDate) );
        }

        return $dateArr;
    }

    public function show_sortOrderSettings( $object, $box ) {

        dbg("Loading metabox to order girth types");

        global $post;

        if ( ! isset( $e20rMeasurements ) ) {

            $e20rMeasurements = new e20rMeasurements();
        }

        $girthTypes = new $e20rMeasurements->getGirthTypes();

        dbg("Have fetched " . count($girthTypes) . " girth types from db");
        ?>
        <div class="submitbox" id="e20r-girth_type-postmeta">
                <div id="minor-publishing">
                    <div id="e20r-girth_type-order">
                        <fieldset>
                            <table id="post-meta-table">
                                <thead>
                                    <tr id="post-meta-header">
                                        <td class="left_col">Order</td>
                                        <td class="right_col">Girth Measurement</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $girthTypes as $gObj ) {
                                        dbg("Object info: " . print_r( $gObj, true)); ?>
                                        <tr class="post-meta-row">
                                            <td class="left_col e20r_order"><input type="text" name="e20r_girth_order[]"id="e20r-<?php echo $gObj->type; ?>-order" value="<?php echo $gObj->sortOrder; ?>"></td>
                                            <td class="right_col e20r_girthType"><input type="text" name="e20r_girth_type[]" id="e20r-<?php echo $gObj->type; ?>-order" value="<?php echo $gObj->type; ?>"></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </fieldset>
                    </div>
                </div>
            </div>

        <?php
    }

	public function enqueue_frontend_css() {

		wp_deregister_style("e20r-tracker");
		wp_enqueue_style( "e20r-tracker", E20R_PLUGINS_URL . '/css/e20r-tracker.css', false, '0.1' );

	}
    /**
     * Load all JS for Admin page
     */
    public function load_adminJS()
    {

        if ( is_admin() && ( ! wp_script_is( 'e20r_tracker_admin', 'enqueued' ) ) ) {

            global $e20r_plot_jscript;

            dbg("e20rTracker::load_adminJS() - Loading admin javascript");
            wp_register_script( 'select2', "//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/select2.min.js", array('jquery'), '4.0', true );
            wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.js', array( 'jquery' ), '0.1', true );
            wp_register_script( 'jquery-ui-tabs', "//code.jquery.com/ui/1.11.2/jquery-ui.js", array('jquery'), '1.11.2', true);
            wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery.timeago' ), '0.1', true );
            wp_register_script( 'e20r-progress-page', E20R_PLUGINS_URL . '/js/e20r-progress-measurements.js', array('jquery'), '0.1', false); // true == in footer of body.
            wp_register_script( 'e20r_tracker_admin', E20R_PLUGINS_URL . '/js/e20r-tracker-admin.js', array('jquery', 'e20r-progress-page'), '0.1', false); // true == in footer of body.
            wp_register_script( 'e20r-assignment-admin', E20R_PLUGINS_URL . '/js/e20r-assignment-admin.js', array( 'jquery' ), '0.1', true);

            $e20r_plot_jscript = true;
            self::enqueue_plotSW();
            $e20r_plot_jscript = false;
            wp_print_scripts( 'jquery-ui-tabs' );
            wp_print_scripts( 'e20r-tracker-js' );
            wp_print_scripts( 'e20r-progress-page' );
            wp_print_scripts( 'e20r_tracker_admin' );
            wp_print_scripts( 'e20r-assignment-admin' );
        }
    }

    public function has_measurementprogress_shortcode() {

        global $post;
        global $e20rArticle;
        global $e20rClient;

        if ( has_shortcode( $post->post_content, 'progress_overview' ) ) {

            $e20rArticle->setId( $post->ID );

            if ( ! $e20rClient->client_loaded ) {
                $e20rClient->init();
                dbg( "e20rTracker::has_measurementprogress_shortcode() - Have to init e20rClient class & grab data..." );
            }

            $this->loadUserScripts();
            // $e20rMeasurements->init( $e20rArticle->releaseDate(), $e20rClient->clientId() );

        }
    }

    private function loadUserScripts() {

        global $current_user;
        global $e20r_plot_jscript;

        $e20r_plot_jscript = true;
        $this->register_plotSW();

        wp_enqueue_style( "jquery-ui-tabs", "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css", false, '1.11.2' );

        wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.js', array( 'jquery' ), '0.1', true );
        wp_register_script( 'e20r-tracker', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery' ), '0.1', true );
        wp_register_script( 'e20r-progress-measurements', E20R_PLUGINS_URL . '/js/e20r-progress-measurements.js', array( 'e20r-tracker' ), '0.1', true );
        wp_register_script( 'jquery-ui-tabs', "//code.jquery.com/ui/1.11.2/jquery-ui.js", array('jquery'), '1.11.2', true);

        wp_localize_script( 'e20r-progress-measurements', 'e20r_progress',
            array(
                'clientId' => $current_user->ID,
                'ajaxurl' => admin_url('admin-ajax.php'),
            )
        );

        // wp_print_scripts( 'e20r-jquery-json' );
        wp_print_scripts( 'jquery-ui-tabs' );
        wp_print_scripts( 'jquery.timeago' );
        $this->enqueue_plotSW();
        wp_print_scripts( 'e20r-tracker' );
        wp_print_scripts( 'e20r-progress-measurements' );

        $e20r_plot_jscript = true;

        if ( ! wp_style_is( 'e20r-tracker', 'enqueued' )) {

            dbg("e20rTracker::loadUserScripts() - Need to load CSS for e20rTracker.");
            wp_deregister_style("e20r-tracker");
            wp_enqueue_style( "e20r-tracker", E20R_PLUGINS_URL . '/css/e20r-tracker.css', false, '0.1' );
        }

    }
    // URL: "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css"
    // URL: "//code.jquery.com/ui/1.11.2/jquery-ui.js"

    public function hasAccess( $userId, $postId ) {

        if ( user_can( $userId, 'publish_posts' ) && ( is_preview() ) ) {

            dbg("Post #{$postId} is a preview for {$userId}");
            return true;
        }

        if ( function_exists( 'pmpro_has_membership_access' ) ) {

            $results = pmpro_has_membership_access( $postId, $userId, true ); //Using true to return all level IDs that have access to the sequence

            if ( $results[0] === true ) { // First item in results array == true if user has access

                dbg( "e20rTracker::hasAccess() - User {$userId} has access to this post" );
                return true;
            }
        }

        return false;
    }

    public function has_dailyProgress_shortcode() {

        global $post;
        global $pagenow;


        if ( has_shortcode( $post->post_content, 'daily_progress' ) ) {

            dbg("e20rTracker::has_dailyProgress_shortcode() -- Loading & adapting user javascripts. ");

	        wp_register_script( 'base64', '//javascriptbase64.googlecode.com/files/base64.js', array( 'jquery' ), '0.3', false);
	        wp_register_script( 'jquery-autoresize', E20R_PLUGINS_URL . '/js/libraries/jquery.autogrow-textarea.js', array( 'base64', 'jquery' ), '1.2', false );
            wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'base64', 'jquery', 'jquery-autoresize' ), '0.1', false );
            wp_register_script( 'e20r-checkin-js', E20R_PLUGINS_URL . '/js/e20r-checkin.js', array( 'base64', 'jquery', 'jquery-autoresize', 'e20r-tracker-js' ), '0.1', false );

            wp_localize_script( 'e20r-checkin-js', 'e20r_checkin',
                array(
                    'url' => admin_url('admin-ajax.php'),
                )
            );

            wp_print_scripts( array( 'base64', 'jquery-autoresize', 'e20r-tracker-js', 'e20r-checkin-js' ) );
        }
    }

    /**
     * Load Javascript for the Weekly Progress page/shortcode
     */
    public function has_weeklyProgress_shortcode() {

        dbg("e20rTracker::has_weeklyProgress_shortcode() -- Loading & adapting javascripts. ");
        global $e20rMeasurements;
        global $e20rClient;
        global $e20rMeasurementDate;
        global $e20rArticle;
        global $e20rProgram;
        global $pagenow;
        global $post;
        global $current_user;
	    global $currentArticle;

        dbg("e20rTracker::has_weeklyProgress_shortcode() -- pagenow is '{$pagenow}'. ");

        if ( has_shortcode( $post->post_content, 'weekly_progress' ) ) {

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Found the weekly progress shortcode on page: {$post->ID}: ");

            // Get the requested Measurement date & article ID (passed from the "Need your measuresments today" form.)
            $measurementDate = isset( $_POST['e20r-progress-form-date'] ) ? sanitize_text_field( $_POST['e20r-progress-form-date'] ) : null;
            $articleId = isset( $_POST['e20r-progress-form-article']) ? intval( $_POST['e20r-progress-form-article']) : null;

            $e20rMeasurementDate = $measurementDate;

            $userId = $current_user->ID;
            $programId = $e20rProgram->getProgramIdForUser( $userId );
            $articleId = $e20rArticle->init( $articleId );
            $articleURL = $e20rArticle->getPostUrl( $articleId );

            if ( ! $this->isActiveUser( $userId ) ) {
                dbg("e20rTracker::has_weeklyProgress_shortcode() - User isn't a valid user. Not loading any data.");
                return;
            }

            if ( ! $e20rClient->client_loaded ) {

                dbg( "e20rTracker::has_weeklyProgress_shortcode() - Have to init e20rClient class & grab data..." );
                $e20rClient->setClient( $userId );
                $e20rClient->init();
            }

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Loading measurements for {$measurementDate}");
            $e20rMeasurements->init( $measurementDate, $userId );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Register scripts");

            $this->enqueue_plotSW();
            wp_register_script( 'e20r-jquery-json', E20R_PLUGINS_URL . '/js/libraries/jquery.json.min.js', array( 'jquery' ), '0.1', false );
            wp_register_script( 'jquery-colorbox', "//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.4.33/jquery.colorbox-min.js", array('jquery'), '1.4.33', false);
            wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.js', array( 'jquery' ), '0.1', false );
            wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery.timeago' ), '0.1', false );
            wp_register_script( 'e20r-progress-js', E20R_PLUGINS_URL . '/js/e20r-progress.js', array( 'e20r-tracker-js' ) , '0.1', false );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Find last weeks measurements");

            $lw_measurements = $e20rMeasurements->getMeasurement( 'last_week', true );
            dbg("e20rTracker::has_weeklyProgress_shortcode() - Measurements from last week:");

            $bDay = $e20rClient->getBirthdate( $userId );
            dbg("e20rTracker::has_weeklyProgress_shortcode() - Birthdate for {$userId} is: {$bDay}");

            if ( $e20rClient->completedInterview( $userId, $programId) ) {
                dbg("e20rTracker::has_weeklyProgress_shortcode() - No USER DATA found in the database. Redirect to User interview page!");
                /* TODO: Uncomment the redirect to the welcome questionnaire */
                // wp_redirect( E20R_COACHING_URL . "/welcome-questionnaire/", 302 );
            }

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Localizing progress script for use on measurement page");

            /* Load user specific settings */
            wp_localize_script( 'e20r-progress-js', 'e20r_progress',
                array(
                    'ajaxurl'   => admin_url('admin-ajax.php'),
                    'settings'     => array(
                        'article_id'        => $articleId,
                        'lengthunit'        => $e20rClient->getLengthUnit(),
                        'weightunit'        => $e20rClient->getWeightUnit(),
                        'imagepath'         => E20R_PLUGINS_URL . '/images/',
                        'overrideDiff'      => ( isset( $lw_measurements->id ) ? false : true ),
                        'measurementSaved'  => ( $articleURL ? $articleURL : E20R_COACHING_URL . 'home/' ),
                    ),
                    'measurements' => array(
                        'last_week' => json_encode( $lw_measurements, JSON_NUMERIC_CHECK ),
                    ),
                    'user_info'    => array(
                        'userdata'          => json_encode( $e20rClient->getData( $userId ), JSON_NUMERIC_CHECK ),
//                        'progress_pictures' => '',
                        'display_birthdate' => ( empty( $bDay ) ? false : true ),

                    ),
                )
            );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Loading scripts in footer of page");
            wp_enqueue_media();
            wp_print_scripts( 'jquery-colorbox' );
            wp_print_scripts( 'e20r-jquery-json' );
            wp_print_scripts( 'e20r-tracker-js' );
            wp_print_scripts( 'e20r-progress-js' );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Add manually created javascript");
            ?>
            <script type="text/javascript">

                var user_data = e20r_progress.user_info.userdata.replace(/&quot;/g, '"');
                var NourishUser = jQuery.parseJSON( user_data );

                var $last_week_data = e20r_progress.measurements.last_week.replace( /&quot;/g, '"');
                var LAST_WEEK_MEASUREMENTS = jQuery.parseJSON( $last_week_data );

                function setBirthday() {

                    if ( ( typeof NourishUser.birthdate === "undefined" ) || ( NourishUser.birthdate === null ) ) {
                        console.log("Error: No Birthdate specified. Should we redirect to Interview page?");
                        return;
                    }

                    var $bd = NourishUser.birthdate;

                    console.log("Birthday: " + $bd );

                    var curbd = $bd.split('-');

                    jQuery('#bdyear').val(curbd[0]);
                    jQuery('#bdmonth').val(curbd[1]);
                    jQuery('#bdday').val(curbd[2]);

                }

                console.log("WP script for E20R Progress Update (client-side) loaded");

                console.log("Loading user_info...");
                console.dir( NourishUser );
                console.log( "Loading Measurement data for last week" );
                console.dir( LAST_WEEK_MEASUREMENTS );

                if ( NourishUser.incomplete_interview ) {
                    console.log("Need to redirect this user to the Interview page!");
                    console.log("location.href='/coaching-interview/?user_id=" + NourishUser.user_id + "'");
                }

                setBirthday();
            </script>
            <?php

        } // End of shortcode check for weekly progress form

        if ( ! wp_style_is( 'e20r-tracker', 'enqueued' )) {

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Need to load CSS for e20rTracker.");
            wp_deregister_style("e20r-tracker");
            wp_enqueue_style( "e20r-tracker", E20R_PLUGINS_URL . '/css/e20r-tracker.css', false, '0.1' );
        }

    }

    /**
     * Load the generic/general plugin javascript and localizations
     */

    /* Prepare graphing scripts */
    public function register_plotSW() {

        global $e20r_plot_jscript, $post;

        if ( $e20r_plot_jscript || has_shortcode( $post->post_content, 'user_progress_info' ) ) {

            dbg( "e20rTracker::register_plotSW() - Plotting javascript being registered." );

            wp_deregister_script( 'jqplot' );
            wp_register_script( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.js', array( 'jquery' ), '0.1' );

            wp_deregister_script( 'jqplot_export' );
            wp_register_script( 'jqplot_export', E20R_PLUGINS_URL . '/js/jQPlot/plugins/export/exportImg.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_pie' );
            wp_register_script( 'jqplot_pie', E20R_PLUGINS_URL . '/js/jQPlot/plugins/pie/jqplot.pieRenderer.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_text' );
            wp_register_script( 'jqplot_text', E20R_PLUGINS_URL . '/js/jQPlot/plugins/text/jqplot.canvasTextRenderer.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_mobile' );
            wp_register_script( 'jqplot_mobile', E20R_PLUGINS_URL . '/js/jQPlot/plugins/mobile/jqplot.mobile.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_date' );
            wp_register_script( 'jqplot_date', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.dateAxisRenderer.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_label' );
            wp_register_script( 'jqplot_label', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisLabelRenderer.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_pntlabel' );
            wp_register_script( 'jqplot_pntlabel', E20R_PLUGINS_URL . '/js/jQPlot/plugins/points/jqplot.pointLabels.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_script( 'jqplot_ticks' );
            wp_register_script( 'jqplot_ticks', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisTickRenderer.min.js', array( 'jqplot' ), '0.1' );

            wp_deregister_style( 'jqplot' );
            wp_enqueue_style( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.css', false, '0.1' );
        }
    }

    /**
     * Load graphing scripts (if needed)
     */
    private function enqueue_plotSW() {

        global $e20r_plot_jscript, $post;

        if ( $e20r_plot_jscript || has_shortcode( $post->post_content, 'progress_overview' ) ) {

            dbg("e20rTracker::enqueue_plotSW() -- Loading javascript for graph generation");
            wp_print_scripts('jqplot');
            wp_print_scripts('jqplot_export');
            wp_print_scripts('jqplot_pie');
            wp_print_scripts('jqplot_text');
            wp_print_scripts('jqplot_mobile');
            wp_print_scripts('jqplot_date');
            wp_print_scripts('jqplot_label');
            wp_print_scripts('jqplot_pntlabel');
            wp_print_scripts('jqplot_ticks');

        }
    }

    /**
     * Default permission check function.
     * Checks whether the provided user_id is allowed to publish_pages & publish_posts.
     *
     * @param $user_id - ID of user to check permissions for.
     * @return bool -- True if the user is allowed to edi/update
     *
     */
    public function userCanEdit( $user_id ) {

        $privArr = apply_filters('e20r-tracker-edit-rights', array( 'publish_pages', 'publish_posts') );

        $permitted = false;

        foreach( $privArr as $privilege ) {

            if ( user_can( $user_id, $privilege ) ) {

                $perm = true;
            } else {

                $perm = false;
            }

            $permitted = ( $permitted || $perm ) ? true : false;
        }

        if ( $permitted ) {
            dbg( "e20rTracker::userCanEdit() - User id ({$user_id}) has permission" );
        }
        else {
            dbg( "e20rTracker::userCanEdit() - User id ({$user_id}) does NOT have permission" );
        }
        return $permitted;
    }

    public function e20r_tracker_activate() {

        global $wpdb;
        global $e20r_db_version;

        // Create settings with default values
        update_option( $this->setting_name, $this->settings );

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        dbg("e20r_tracker_activate() - Loading table SQL");

/*
        $programsTableSql = "
            CREATE TABLE {$wpdb->prefix}e20r_programs (
                    id int not null auto_increment,
                    program_name varchar(255) null,
                    program_shortname varchar(50) null,
                    description mediumtext null,
                    starttime timestamp not null default current_timestamp,
                    endtime timestamp null,
                    member_id int null,
                    sequences varchar(512) null,
                    belongs_to int null,
                    primary key (id) )
                  {$charset_collate}
        ";

        $setsTableSql = "
            CREATE TABLE {$wpdb->prefix}e20r_sets (
                id int not null auto_increment,
                set_name varchar(50) null,
                rounds int not null default 1,
                set_rest int not null default 60,
                program_id int not null default 0,
                exercise_id int not null default 0,
                primary key (id),
                key exercises ( exercise_id asc ),
                key programs ( program_id asc ) )
                {$charset_collate}
        ";

        $exercisesTableSql = "
            CREATE TABLE {$wpdb->prefix}e20r_exercises (
                id int not null auto_increment,
                exercise_name varchar(100) not null default '',
                description mediumtext null,
                repetitions int not null default 10,
                duration int null,
                ex_rest int not null default 30,
                primary key (id) )
                {$charset_collate}
        ";
*/
        /**
         * Intake interview / exit interview data.
         */
        $intakeTableSql =
            "CREATE TABLE {$wpdb->prefix}e20r_client_info (
                    id int not null,
                    user_id int not null,
                    started_date timestamp default current_timestamp,
                    edited_date timestamp null,
                    completed_date timestamp null,
                    program_id int not null,
                    program_start date not null,
                    progress_photo_dir varchar(255) not null default 'e20r-pics/',
                    user_enc_key varchar(512) not null,
                    first_name varchar(20) null,
                    last_name varchar(50) null,
                    phone varchar(18) null,
                    alt_phone varchar(18) null,
                    contact_method varchar(15) null,
                    skype_name varchar(12) null,
                    address_1 varchar(255) null,
                    address_2 varchar(255) null,
                    address_city varchar(50) null,
                    address_zip varchar(10) null,
                    address_state varchar(30) null,
                    address_country varchar(30) null,
                    emergency_contact varchar(100) null,
                    emergency_mail varchar(255) null,
                    emergency_phone varchar(18) null,
                    birthdate date not null,
                    ethnicity varchar(255) null,
                    lengthunits varchar(3) not null default 'in',
                    height_ft int null default 0,
                    height_in int null default 0,
                    calculated_height_in int not null default 0,
                    height_m int not null default 0,
                    height_cm int not null default 0,
                    calculated_height_cm int not null default 0,
                    weightunits varchar(6) not null default 'lbs',
                    weight_lbs decimal(7,2) null,
                    weight_kg decimal(7,2) null,
                    weight_st decimal(7,2) null,
                    weight_st_uk decimal(7,2) null,
                    first_time tinyint not null default 1,
                    number_of_times smallint null default 0,
                    coaches varchar(255) null,
                    referred tinyint null default 0,
                    referral_name varchar(255) null,
                    referral_email varchar(255) null,
                    hear_about varchar(255) null,
                    other_programs_considered text null,
                    weight_loss smallint null default 0,
                    muscle_gain smallint null default 0,
                    look_feel smallint null default 0,
                    consistency smallint null default 0,
                    energy_vitality smallint null default 0,
                    off_medication smallint null default 0,
                    control_eating smallint null default 0,
                    learn_maintenance smallint null default 0,
                    stronger smallint null default 0,
                    modeling smallint null default 0,
                    sport_performance smallint null default 0,
                    goals_other text null,
                    goal_achievement text null,
                    goal_reward text null,
                    regular_exercise tinyint not null default 0,
                    exercise_hours_per_week varchar(3) not null default '0',
                    regular_exercise_type varchar(10) not null default 'none',
                    other_exercise text null,
                    exercise_plan tinyint not null default 0,
                    exercise_level varchar(20) default 'not-applicable',
                    competitive_sports tinyint null,
                    competitive_survey text null,
                    enjoyable_activities text null,
                    exercise_challenge text null,
                    chronic_pain tinyint not null default 0,
                    pain_symptoms text null,
                    limiting_injuries tinyint not null default 0,
                    injury_summary varchar(11) not null default 'none',
                    other_injuries text null,
                    injury_details text null,
                    nutritional_challenge text null,
                    buy_groceries varchar(6) null,
                    groceries_who varchar(255) null,
                    cooking varchar(6) null,
                    cooking_who varchar(255) null,
                    eats_with varchar(8) null,
                    meals_at_home varchar(4) null,
                    following_diet tinyint null default 0,
                    diet_summary varchar(18) null default 'none',
                    other_diet varchar(255) null,
                    diet_duration varchar(255) null,
                    food_allergies tinyint null default 0,
                    food_allergy_summary varchar(15) null,
                    food_allergy_other varchar(255) null,
                    food_sensitivity tinyint null default 0,
                    sensitivity_summary varchar(15) null,
                    sensitivity_other varchar(255) null,
                    supplements tinyint null default 0,
                    supplement_summary varchar(20) null default 'none',
                    other_vitamins varchar(255) null,
                    supplements_other varchar(255) null,
                    daily_water_servings varchar(4) null,
                    daily_protein_servings varchar(4) null,
                    daily_vegetable_servings varchar(4) null,
                    nutritional_knowledge smallint null default 0,
                    diagnosed_medical_problems tinyint null default 0,
                    medical_issues text null,
                    on_prescriptions tinyint null default 0,
                    prescription_summary varchar(20) null,
                    other_treatments tinyint null default 0,
                    treatment_summary text null,
                    working tinyint null default 0,
                    work_type varchar(150) null,
                    working_when varchar(10) null,
                    typical_hours_worked varchar(6) null,
                    work_activity_level varchar(12) null,
                    work_stress varchar(9) null,
                    work_travel varchar(11) null,
                    student tinyint null default 0,
                    school_stress varchar(9) null,
                    caregiver tinyint null default 0,
                    caregiver_for varchar(150) null,
                    caregiver_stress varchar(9) null,
                    committed_relationship tinyint null default 0,
                    partner varchar(50) null,
                    children tinyint null default 0,
                    children_count smallint null default 0,
                    child_name_age text null,
                    pets tinyint null default 0,
                    pet_count int null,
                    pet_names_types varchar(255) null,
                    home_stress varchar(9) null,
                    stress_coping varchar(15) null,
                    vacations varchar(11) null,
                    hobbies text null,
                    alcohol varchar(15) null,
                    smoking varchar(10) null,
                    non_prescriptiondrugs varchar(10) null,
                    program_expectations text null,
                    coach_expectations text null,
                    more_info text null,
                    photo_consent tinyint not null default 0,
                    research_consent tinyint not null default 0,
                    medical_release tinyint not null default 0,
                    primary key  (id),
                    key user_id  (user_id asc),
                    key programstart  (program_start asc)
              )
                  {$charset_collate}
        ";

        /**
         * Track user measurements/metrics
         */
        $measurementTableSql =
            "CREATE TABLE {$wpdb->prefix}e20r_measurements (
                    id int not null auto_increment,
                    user_id int not null,
                    article_id int default null,
                    recorded_date datetime null,
                    weight decimal(18,3) null,
                    neck decimal(18,3) null,
                    shoulder decimal(18,3) null,
                    chest decimal(18,3) null,
                    arm decimal(18,3) null,
                    waist decimal(18,3) null,
                    hip decimal(18,3) null,
                    thigh decimal(18,3) null,
                    calf decimal(18,3) null,
                    girth decimal(18,3) null,
                    essay1 text null,
                    behaviorprogress bool null,
                    front_image int default null,
                    side_image int default null,
                    back_image int default null,
                    program_id int default -1,
                    primary key  ( id ),
                    key user_id ( user_id asc) )
                  {$charset_collate}
              ";
        /**
         * For user Check-Ins of various types.
         *
         * check-in values: 0 - No, 1 - yes, 2 - partial, 3 - not applicable
         * check-in type: 1 - action (habit), 2 - assignment, 3 - survey, 4 - activity (workout)
         */
        $checkinSql =
            "CREATE TABLE {$wpdb->prefix}e20r_checkin (
                    id int not null auto_increment,
                    user_id int null,
                    program_id int null,
                    article_id int null,
                    descr_id int not null default 0,
                    action_id int null,
                    checkin_type int default 0,
                    checkin_date datetime null,
                    checkedin_date datetime null,
                    checkin_short_name varchar(50) null,
                    checkedin tinyint not null default 0,
                    checkin_note text null,
                    primary key  (id),
                        key program_id ( program_id asc ),
                        key checkin_short_name ( checkin_short_name asc ) )
                {$charset_collate}";


        /**
         * For assignments
         * Uses the post->ID of the e20r_assignments CPT for it's unique ID.
         */
        $assignmentAsSql =
            "CREATE TABLE {$wpdb->prefix}e20r_assignment (
                    id int not null,
                    article_id int not null,
                    program_id int not null,
                    delay int not null,
                    user_id int not null,
                    answer_date datetime null,
                    answer text null,
                    field_type enum( 'textbox', 'input', 'checkbox', 'radio', 'button' ),
                    primary key  (id),
                     key articles (article_id asc),
                     key user_id ( user_id asc ),
                     key questions ( question_id asc )
                     )
                    {$charset_collate}
        ";

        $oldMeasurementTableSql =
            "CREATE TABLE {$wpdb->prefix}nourish_measurements (
                    lead_id int(11) not null,
                    created_by int(11) not null,
                    date_created date not null,
                    username varchar(50) not null,
                    recordedDate date not null,
                    weight float not null,
                    neckCM float default null,
                    shoulderCM float default null,
                    chestCM float default null,
                    armCM float default null,
                    waistCM float default null,
                    hipCM float default null,
                    thighCM float default null,
                    calfCM float default null,
                    totalGrithCM float default null,
                    article_id int(11) DEFAULT NULL,
                    essay1 text NULL,
                    behaviorprogress tinyint NULL,
                    front_image int default null,
                    side_image int default null,
                    back_image int default null,
                    program_id int default 0
                    )
                    {$charset_collate}
            ";

        // FixMe: The trigger works but can only be installed if using the mysqli_* function. That causes "Command out of sync" errors.
        /* $girthTriggerSql =
            "DROP TRIGGER IF EXISTS {$wpdb->prefix}e20r_update_girth_total;
            CREATE TRIGGER {$wpdb->prefix}e20r_update_girth_total BEFORE UPDATE ON {$wpdb->prefix}e20r_measurements
            FOR EACH ROW
              BEGIN
                SET NEW.girth = COALESCE(NEW.neck,0) + COALESCE(NEW.shoulder,0) + COALESCE(NEW.chest,0) + COALESCE(NEW.arm,0) + COALESCE(NEW.waist,0) + COALESCE(NEW.hip,0) + COALESCE(NEW.thigh,0) + COALESCE(NEW.calf,0);
              END ;
            ";
        */
        require_once( ABSPATH . "wp-admin/includes/upgrade.php" );

        dbg('e20r_tracker_activate() - Creating tables in database');
        dbDelta( $checkinSql );
        dbDelta( $measurementTableSql );
        dbDelta( $intakeTableSql );
        dbDelta( $assignmentAsSql );
/*        dbDelta( $assignmentQsSql );
        dbDelta( $programsTableSql );
        dbDelta( $setsTableSql );
        dbDelta( $exercisesTableSql ); */
        dbDelta( $oldMeasurementTableSql );

        // dbg("e20r_tracker_activate() - Adding triggers in database");
        // mysqli_multi_query($wpdb->dbh, $girthTriggerSql );

        add_option( 'e20rTracker_db_version', $e20r_db_version );

        flush_rewrite_rules();
    }

    public function e20r_tracker_deactivate() {

        global $wpdb;
        global $e20r_db_version;

        $options = get_option( $this->setting_name );

        dbg("Deactivation options: " . print_r( $options, true ) );

        $tables = array(
            $wpdb->prefix . 'e20r_checkin',
            $wpdb->prefix . 'e20r_assignments',
            $wpdb->prefix . 'e20r_measurements',
            $wpdb->prefix . 'e20r_client_info',
//            $wpdb->prefix . 'e20r_programs',
//            $wpdb->prefix . 'e20r_sets',
//            $wpdb->prefix . 'e20r_exercises',
            $wpdb->prefix . 'e20r_articles',
        );


        foreach ( $tables as $tblName ) {

            if ( $options['purge_tables'] == 1 ) {

                dbg("e20r_tracker_deactivate() - Truncating {$tblName}" );
                $sql = "TRUNCATE TABLE {$tblName}";
                $wpdb->query( $sql );
            }

            if ( $options['delete_tables'] == 1 ) {

                dbg( "e20r_tracker_deactivate() - {$tblName} being dropped" );

                $sql = "DROP TABLE IF EXISTS {$tblName}";
                $wpdb->query( $sql );
            }

        }
        $wpdb->query("DROP TRIGGER IF EXISTS {$wpdb->prefix}e20r_update_girth_total");
        // Remove existing options
        delete_option( $this->setting_name );
    }

    public function e20r_tracker_assignmentsCPT() {

        $labels =  array(
            'name' => __( 'Assignments', 'e20rtracker'  ),
            'singular_name' => __( 'Assignment', 'e20rtracker' ),
            'slug' => 'e20r_assignment',
            'add_new' => __( 'New Assignment', 'e20rtracker' ),
            'add_new_item' => __( 'New Assignment', 'e20rtracker' ),
            'edit' => __( 'Edit assignments', 'e20rtracker' ),
            'edit_item' => __( 'Edit Assignment', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Assignments', 'e20rtracker' ),
            'view_item' => __( 'View This Assignment', 'e20rtracker' ),
            'search_items' => __( 'Search Assignments', 'e20rtracker' ),
            'not_found' => __( 'No Assignments Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Assignment Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_assignments',
            array( 'labels' => apply_filters( 'e20r-tracker-assignment-cpt-labels', $labels ),
                   'public' => false,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title', 'excerpt'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-assignment-cpt-slug', 'tracker-assignments'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-assignment-cpt-archive-slug', 'tracker-assignments')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_assignments CPT: ' . $error->get_error_message);
        }
    }

    public function e20r_tracker_programCPT() {

        $labels =  array(
            'name' => __( 'Programs', 'e20rtracker'  ),
            'singular_name' => __( 'Program', 'e20rtracker' ),
            'slug' => 'e20r_programs',
            'add_new' => __( 'New Program', 'e20rtracker' ),
            'add_new_item' => __( 'New Program', 'e20rtracker' ),
            'edit' => __( 'Edit program', 'e20rtracker' ),
            'edit_item' => __( 'Edit Program', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Programs', 'e20rtracker' ),
            'view_item' => __( 'View This Program', 'e20rtracker' ),
            'search_items' => __( 'Search Programs', 'e20rtracker' ),
            'not_found' => __( 'No Programs Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Programs Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_programs',
            array( 'labels' => apply_filters( 'e20r-tracker-program-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title', 'excerpt', 'custom-fields', 'page-attributes'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-program-cpt-slug', 'tracker-programs'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-program-cpt-archive-slug', 'tracker-programs')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_program CPT: ' . $error->get_error_message);
        }
    }

    public function e20r_tracker_articleCPT() {

        $labels =  array(
            'name' => __( 'Articles', 'e20rtracker'  ),
            'singular_name' => __( 'Article', 'e20rtracker' ),
            'slug' => 'e20r_articles',
            'add_new' => __( 'New Article', 'e20rtracker' ),
            'add_new_item' => __( 'New Tracker Article', 'e20rtracker' ),
            'edit' => __( 'Edit Article', 'pmprosequence' ),
            'edit_item' => __( 'Edit Tracker Article', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Articles', 'e20rtracker' ),
            'view_item' => __( 'View This Article', 'e20rtracker' ),
            'search_items' => __( 'Search Articles', 'e20rtracker' ),
            'not_found' => __( 'No Articles Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Articles Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_articles',
            array( 'labels' => apply_filters( 'e20r-tracker-article-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title', 'excerpt'),
                   'can_export' => true,
                   'show_in_nav_menus' => true,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-article-cpt-slug', 'tracker-articles'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-article-cpt-archive-slug', 'tracker-articles')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_articles CPT: ' . $error->get_error_message);
        }
    }

    public function e20r_tracker_girthCPT() {

        $labels =  array(
            'name' => __( 'Girth Types', 'e20rtracker'  ),
            'singular_name' => __( 'Girth Type', 'e20rtracker' ),
            'slug' => 'e20r_girth_types',
            'add_new' => __( 'New Girth Type', 'e20rtracker' ),
            'add_new_item' => __( 'New Girth Type', 'e20rtracker' ),
            'edit' => __( 'Edit Girth Type', 'pmprosequence' ),
            'edit_item' => __( 'Edit Girth Type', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Girth Types', 'e20rtracker' ),
            'view_item' => __( 'View This Girth Type', 'e20rtracker' ),
            'search_items' => __( 'Search Girths', 'e20rtracker' ),
            'not_found' => __( 'No Girth Types Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Girth Types Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_girth_types',
            array( 'labels' => apply_filters( 'e20r-tracker-girth-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','editor','excerpt','thumbnail','custom-fields','author'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-girth-cpt-slug', 'girth'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-girth-cpt-archive-slug', 'girths')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: when registering e20r_girth_types: ' . $error->get_error_message);
        }
    }

    public function e20r_tracker_exerciseCPT() {

        $labels =  array(
            'name' => __( 'Exercises', 'e20rtracker'  ),
            'singular_name' => __( 'Exercise', 'e20rtracker' ),
            'slug' => 'e20r_exercise',
            'add_new' => __( 'New Exercise', 'e20rtracker' ),
            'add_new_item' => __( 'New Exercise', 'e20rtracker' ),
            'edit' => __( 'Edit Exercise', 'e20rtracker' ),
            'edit_item' => __( 'Edit Exercise', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Exercises', 'e20rtracker' ),
            'view_item' => __( 'View This Exercise', 'e20rtracker' ),
            'search_items' => __( 'Search Exercises', 'e20rtracker' ),
            'not_found' => __( 'No Exercises Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Exercises Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_exercises',
            array( 'labels' => apply_filters( 'e20r-tracker-exercise-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','editor','excerpt','thumbnail', 'page-attributes'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-exercise-cpt-slug', 'tracker-exercise'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-exercise-cpt-archive-slug', 'tracker-exercises')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_exercise CPT: ' . $error->get_error_message);
        }
    }

    public function e20r_tracker_activitiesCPT() {

        $labels =  array(
            'name' => __( 'Activities', 'e20rtracker'  ),
            'singular_name' => __( 'Activity', 'e20rtracker' ),
            'slug' => 'e20r_workout',
            'add_new' => __( 'New Activity', 'e20rtracker' ),
            'add_new_item' => __( 'New Activity', 'e20rtracker' ),
            'edit' => __( 'Edit Activity', 'e20rtracker' ),
            'edit_item' => __( 'Edit Activity', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Activities', 'e20rtracker' ),
            'view_item' => __( 'View This Activity', 'e20rtracker' ),
            'search_items' => __( 'Search Activities', 'e20rtracker' ),
            'not_found' => __( 'No Activities Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Activities Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_workout',
            array( 'labels' => apply_filters( 'e20r-tracker-workout-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','excerpt','thumbnail', 'page-attributes'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-workout-cpt-slug', 'tracker-activity'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-workout-cpt-archive-slug', 'tracker-activity')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_workout CPT: ' . $error->get_error_message);
        }
    }

	public function e20r_tracker_actionCPT() {

        $labels =  array(
            'name' => __( 'Actions', 'e20rtracker'  ),
            'singular_name' => __( 'Action', 'e20rtracker' ),
            'slug' => 'e20r_checkins',
            'add_new' => __( 'New Action', 'e20rtracker' ),
            'add_new_item' => __( 'New Action', 'e20rtracker' ),
            'edit' => __( 'Edit Action', 'e20rtracker' ),
            'edit_item' => __( 'Edit Action', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Action', 'e20rtracker' ),
            'view_item' => __( 'View This Action', 'e20rtracker' ),
            'search_items' => __( 'Search Actions', 'e20rtracker' ),
            'not_found' => __( 'No Actions Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Actions Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_checkins',
            array( 'labels' => apply_filters( 'e20r-tracker-checkin-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','excerpt','thumbnail', 'page-attributes'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-checkin-cpt-slug', 'tracker-action'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-checkin-cpt-archive-slug', 'tracker-action')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_checkin CPT: ' . $error->get_error_message);
        }
    }

    /**
     * Configure & display the icon for the Tracker (in the Dashboard)
     */
    function post_type_icon() {
        ?>
        <style>
            /* Admin Menu - 16px */
            #menu-posts-e20r_tracker .wp-menu-image {
                background: url("<?php echo E20R_PLUGINS_URL; ?>/images/icon-sequence16-sprite.png") no-repeat 6px 6px !important;
            }
            #menu-posts-e20r_tracker:hover .wp-menu-image, #menu-posts-e20r_tracker.wp-has-current-submenu .wp-menu-image {
                background-position: 6px -26px !important;
            }
            /* Post Screen - 32px */
            .icon32-posts-pmpro_sequence {
                background: url("<?php echo E20R_PLUGINS_URL; ?>images/icon-sequence32.png") no-repeat left top !important;
            }
            @media
            only screen and (-webkit-min-device-pixel-ratio: 1.5),
            only screen and (   min--moz-device-pixel-ratio: 1.5),
            only screen and (     -o-min-device-pixel-ratio: 3/2),
                /* only screen and (        min-device-pixel-ratio: 1.5), */
            only screen and (                min-resolution: 1.5dppx) {

                /* Admin Menu - 16px @2x */
                #menu-posts-pmpro_sequence .wp-menu-image {
                    background-image: url("<?php echo E20R_PLUGINS_URL; ?>images/icon-sequence16-sprite_2x.png") !important;
                    -webkit-background-size: 16px 48px;
                    -moz-background-size: 16px 48px;
                    background-size: 16px 48px;
                }
                /* Post Screen - 32px @2x */
                .icon32-posts-pmpro_sequence {
                    background-image:url("<?php echo E20R_PLUGINS_URL ?>images/icon-sequence32_2x.png") !important;
                    -webkit-background-size: 32px 32px;
                    -moz-background-size: 32px 32px;
                    background-size: 32px 32px;
                }
            }
        </style>
    <?php
    }

    public function whoCalledMe() {

        $trace=debug_backtrace();
        $caller=$trace[2];

        $trace =  "Called by {$caller['function']}()";
        if (isset($caller['class']))
            $trace .= " in {$caller['class']}()";

        return $trace;
    }

    public function prepare_in( $sql, $values, $type = '%d' ) {

        global $wpdb;

        $not_in_count = substr_count( $sql, '[IN]' );

        if ( $not_in_count > 0 ) {

            $args = array( str_replace( '[IN]',
                implode( ', ', array_fill( 0, count( $values ), ( $type == '%d' ? '%d' : '%s' ) ) ),
                str_replace( '%', '%%', $sql ) ) );

            for ( $i = 0; $i < substr_count( $sql, '[IN]' ); $i++ ) {
                $args = array_merge( $args, $values );
            }

            $sql = call_user_func_array(
                array( $wpdb, 'prepare' ),
                array_merge( $args ) );

        }

        return $sql;
    }

    public function getUserList( $level = null ) {

        $levels = array_keys( $this->getMembershipLevels( $level, false ) );

        dbg("e20rTracker::getUserList() - Users being loaded for the following level(s): " . print_r( $levels, true ) );

        return $this->model->loadUsers( $levels );
    }

    public function getMembershipLevels( $level = null, $onlyVisible = false ) {

        if ( function_exists( 'pmpro_getAllLevels' ) ) {

            if ( is_numeric( $level ) ) {

                dbg("e20rTracker::getLevelList() - Requested ID: {$level}");
                $tmp = pmpro_getLevel( $level );
                $name = $tmp->name;
                dbg("e20rTracker::getLevelList() - Level Name: {$name}");
            }

            $allLevels = pmpro_getAllLevels();
            $levels    = array();

            if ( ! empty( $name ) ) {
                dbg("e20rTracker::getLevelList() - Supplied name for level: {$name}");
                $name = str_replace( '+', '\+', $name);
                $pattern = "/{$name}/i";
                dbg("e20rTracker::getLevelList() - Pattern for level: {$pattern}");
            }

            foreach ( $allLevels as $level ) {

                $visible = ( $level->allow_signups == 1 ? true : false );
                $inclLevel =  ( is_null( $name ) || ( preg_match( $pattern, $level->name ) == 1 ) ) ? true : false;

                if ( ( ! $onlyVisible ) || ( $visible && $onlyVisible ) ) {

                    if ( $inclLevel ) {

                        $levels[ $level->id ] = $level->name;
                    }
                }
            }

            asort( $levels );

            // dbg("Levels fetched: " . print_r( $levels, true ) );

            return $levels;
        }

        $this->dependency_warnings();
    }

    public function isActiveUser( $userId ) {

        dbg("e20Tracker::isActiveUser() - Supplied User ID: {$userId}");

        if ( $userId == 0 ) {
            return false;
        }

        if ( function_exists('pmpro_hasMembershipLevel' ) ) {

            dbg("e20Tracker::isActiveUser() - Using Paid Memberships Pro");
            $ud = get_user_by( 'id', $userId );

            $notFreeMember = (! pmpro_hasMembershipLevel( 13, $userId ) );
            $notDummyUser = (! $ud->has_cap('app_dmy_users') );

            dbg("e20Tracker::isActiveUser() - Dummy User? ({$notDummyUser}). Not Free member? ($notFreeMember)");
            return ( $notFreeMember && $notDummyUser );
        }

        return false;
    }

    public function getDelay( $delayVal, $userId = null ) {

        global $current_user;
        global $e20rProgram;

        // We've been given a numeric value so assuming it's the delay.
        if ( is_numeric( $delayVal ) ) {
            dbg("e20rTracker::getDelay() - Numeric delay value specified. Returning: {$delayVal}");
            return $delayVal;
        }

        if ( $delayVal == 'now' ) {
            dbg("e20rTracker::getDelay() - Calculating delay since startdate...");
            // Calculate the user's current "days in program".
            if ( ! $userId ) {

                $userId = $current_user->ID;
            }

            $startDate = $e20rProgram->startdate( $userId );

            dbg("e20rTracker::getDelay() - Based on startdate of {$startDate}...");

            $delay = $this->daysBetween( $startDate );

            dbg("e20rTracker::getDelay() - Days since startdate is: {$delay}...");

            return $delay;
        }

        return false;
    }

    public function setUserProgramStart( $levelId, $userId ) {

        global $e20rProgram;

        $levels = $this->coachingLevels();

        if ( in_array( $levelId, $levels) ) {

            $startDate = $e20rProgram->startdate( $userId );
            dbg( "e20rTracker::setuserProgramStart() - Received startdate of: {$startDate} aka " . date( 'Y-m-d', $startDate ) );

            if ( false !== update_user_meta( $userId, 'e20r-tracker-program-startdate', date( 'Y-m-d', $startDate ) ) ) {

                return true;
            }
        }

        return false;
    }

    public function getDateFromDelay( $delay = 0, $userId = null ) {

        global $current_user;
        global $e20rProgram;

        if ( ! $userId ) {
            $userId = $current_user->ID;
        }

        $startTS = $e20rProgram->startdate( $userId );

        if ( empty( $delay ) || ( $delay == 'now' ) ) {

            dbg("e20rTracker::getDateForPost() - Calculating 'now' based on current time and startdate for the user");
            $delay = $this->daysBetween( $startTS, current_time('timestamp') );
        }

        dbg("e20rTracker::getDateFromDelay() - user w/id {$userId} has a startdate timestamp of {$startTS}");

        if ( ! $startTS ) {

            dbg("e20rTracker::getDateFromDelay( {$delay} ) -> No startdate found for user with ID of {$userId}");
            return ( date( 'Y-m-d', current_time( 'timestamp' ) ) );
        }

        $sDate = date('Y-m-d', $startTS );
        dbg("e20rTracker::getDateFromDelay( {$delay} ) -> Startdate found for user with ID of {$userId}: {$sDate}");

        $rDate = date( 'Y-m-d', strtotime( "{$sDate} +{$delay} days") );

        dbg("e20rTracker::getDateFromDelay( {$delay} ) -> Startdate ({$sDate}) + delay ({$delay}) days = date: {$rDate}");

        return $rDate;
    }

    public function getDateForPost( $days, $userId = null ) {

        dbg("e20rTracker::getDateForPost() - Loading function...");
        global $current_user;
        global $e20rProgram;

        if ( $userId  === null ) {

            $userId = $current_user->ID;
        }

        $startDateTS = $e20rProgram->startdate( $userId );

        if (! $startDateTS ) {

            dbg("e20rTracker::getDateForPost( {$days} ) -> No startdate found for user with ID of {$userId}");
            return ( date( 'Y-m-d', current_time( 'timestamp' ) ) );
        }

        if ( empty( $days ) || ( $days == 'now' ) ) {
            dbg("e20rTracker::getDateForPost() - Calculating 'now' based on current time and startdate for the user");
            $days = $this->daysBetween( $startDateTS, current_time('timestamp') );
        }

        $startDate = date( 'Y-m-d', $startDateTS );
        dbg("e20rTracker::getDateForPost( {$days} ) -> Startdate found for user with ID of {$userId}: {$startDate}");

        $releaseDate = date( 'Y-m-d', strtotime( "{$startDate} +{$days} days") );

        dbg("e20rTracker::getDateForPost( {$days} ) -> Calculated date for delay of {$days}: {$releaseDate}");
        return $releaseDate;
    }

    public function getDripFeedDelay( $postId ) {

        if ( class_exists( 'PMProSequence') ) {

            dbg("e20rArticle::getDripFeedDelay() - Found the PMPro Sequence Drip Feed plugin");

            if ( false === ( $sequenceIds = get_post_meta( $postId, '_post_sequences', true ) ) ) {
                return false;
            }

            foreach ($sequenceIds as $id ) {

                $seq = new PMProSequence( $id );
                $details = $seq->get_postDetails( $postId );

                unset($seq);

                dbg("e20rArticle::getDripFeedDelay() - Delay details: " . print_r( $details, true  ) );

                if ( $id != false ) {
                    dbg("e20rArticle::getDripFeedDelay() - Returning {$details->delay}");
                    return $details->delay;
                }
            }
        }

        return false;
    }

    public function setFormatForRecord( $record ) {

        $format = array();

        foreach( $record as $key => $val ) {

            $varFormat = $this->setFormat( $val );

            if ( $varFormat !== false ) {

                $format = array_merge( $format, array( $varFormat ) );
            }
            else {

                dbg("e20rMeasurementModel::setFormatForRecord() - Invalid DB type for {$key}/{$val} pair");
                throw new Exception( "The value submitted to the Database for {$key} is of an invalid/unknown type" );
                return false;
            }
        }

        return ( ! empty( $format ) ? $format : false );
    }

    /**
     * Identify the format of the variable value.
     *
     * @param $value -- The variable to set the format for
     *
     * @return bool|string -- Either %d, %s or %f (integer, string or float). Can return false if unsupported format.
     *
     * @access private
     */
    private function setFormat( $value ) {

        if ( ! is_numeric( $value ) ) {
            // dbg( "setFormat() - {$value} is NOT numeric" );

            if ( ctype_alpha( $value ) ) {
                // dbg( "setFormat() - {$value} is a string" );
                return '%s';
            }

            if ( strtotime( $value ) ) {
                // dbg( "setFormat() - {$value} is a date (treating it as a string)" );
                return '%s';
            }

            if ( is_string( $value ) ) {
                // dbg( "setFormat() - {$value} is a string" );
                return '%s';
            }

            if (is_null( $value )) {
                // dbg( "setFormat() - it's a NULL value");
                return '%s';
            }
        }
        else {
            // dbg( "setFormat() - .{$value}. IS numeric" );

            if ( is_float( $value + 1 ) ) {
                // dbg( "setFormat() - {$value} is a float" );

                return '%f';
            }

            if ( is_int( $value + 1 ) ) {
                // dbg( "setFormat() - {$value} is an integer" );
                return '%d';
            }
        }
        return false;
    }

    public function getCurrentPostType() {

        global $post, $typenow, $current_screen;

        //we have a post so we can just get the post type from that
        if ( $post && $post->post_type ) {

            return $post->post_type;
        } //check the global $typenow - set in admin.php
        elseif( $typenow ) {

            return $typenow;
        } //check the global $current_screen object - set in sceen.php
        elseif( $current_screen && $current_screen->post_type ) {

            return $current_screen->post_type;
        } //lastly check the post_type querystring
        elseif( isset( $_REQUEST['post_type'] ) ) {

            return sanitize_key( $_REQUEST['post_type'] );
        }

        //we do not know the post type!
        return null;
    }

    private function coachingLevels( $invert = false ) {

        global $wpdb;

        $coaching_levels = array();

        if ( function_exists( 'pmpro_activation' ) ) {
            $sql = "SELECT id
                    FROM $wpdb->pmpro_membership_levels
                    WHERE name " . ( $invert ? "NOT " : '' ) . "LIKE '%coaching%' AND allow_signups = 1
                ";
        }

        if ( ! $sql ) {
            return false;
        }

        $results = $wpdb->get_results( $sql );

        foreach ( $results as $result ) {

            if ( $invert ) {
                $coaching_levels[] = 0 - $result->id;
            }
            else {
                $coaching_levels[] = $result->id;
            }
        }

        return $coaching_levels;
    }

    /**
     * Calculates the # of days between two dates (specified in UTC seconds)
     *
     * @param $startTS (timestamp) - timestamp value for start date
     * @param $endTS (timestamp) - timestamp value for end date
     * @return int ( the # of days )
     */
    public function daysBetween( $startTS, $endTS = null, $tz = 'UTC' ) {

        $days = 0;

        // use current day as $endTS if nothing is specified
        if ( ( is_null( $endTS ) ) && ( $tz == 'UTC') ) {

            $endTS = current_time( 'timestamp', true );
        }
        elseif ( is_null( $endTS ) ) {

            $endTS = current_time( 'timestamp' );
        }

        // Create two DateTime objects
        $dStart = new DateTime( date( 'Y-m-d', $startTS ), new DateTimeZone( $tz ) );
        $dEnd   = new DateTime( date( 'Y-m-d', $endTS ), new DateTimeZone( $tz ) );

        if ( version_compare( PHP_VERSION, "5.3", '>=' ) ) {

            /* Calculate the difference using 5.3 supported logic */
            $dDiff  = $dStart->diff( $dEnd );
            $dDiff->format( '%d' );
            //$dDiff->format('%R%a');

            $days = $dDiff->days;

            // Invert the value
            if ( $dDiff->invert == 1 )
                $days = 0 - $days;
        }
        else {

            // V5.2.x workaround
            $dStartStr = $dStart->format('U');
            $dEndStr = $dEnd->format('U');

            // Difference (in seconds)
            $diff = abs($dStartStr - $dEndStr);

            // Convert to days.
            $days = $diff * 86400; // Won't handle DST correctly, that's probably not a problem here..?

            // Sign flip if needed.
            if ( gmp_sign($dStartStr - $dEndStr) == -1)
                $days = 0 - $days;
        }

        return $days;
    }
}