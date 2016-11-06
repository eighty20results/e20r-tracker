<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use \Defuse\Crypto\Crypto as Crypt;
use \Defuse\Crypto\Exception as Ex;

use \E20R\Sequences\Sequence as Sequence;

class e20rTracker {

    private $client = null;

    protected $settings = array();
    protected $setting_name = 'e20r-tracker';

    public $tables;
    private $model;

    public $managed_types = array( 'post', 'page');

    private $hooksLoaded = false;

    public function __construct() {

        $this->model = new e20rTrackerModel();

        // Set defaults (in case there are none saved already
        $this->settings = get_option( $this->setting_name, array(
                            'delete_tables' => false,
                            'purge_tables' => false,
                            // 'measurement_day' => CONST_SATURDAY,
//                            'lesson_source' => null,
                            'roles_are_set' => false,
                            'auth_timeout' => 3,
                            'remember_me_auth_timeout' => 1,
                            'encrypt_surveys' => 0,
                            'e20r_db_version' => E20R_DB_VERSION,
                            'unserialize_notice' => null,
                            'run_unserialize' => 0,
                            'converted_metadata_e20r_articles' => false,
                            'converted_metadata_e20r_assignments' => false,
                            'converted_metadata_e20r_workout' => false,
                            'converted_metadata_e20r_actions' => false,
            )
        );

        dbg("e20rTracker::constructor() - Loading the types of posts we'll be allowed to manage");
        $this->managed_types = apply_filters("e20r-tracker-post-types", array("post", "page") );

//        dbg("e20rTracker::constructor() - Loading e20rTables class");
//        $this->tables = new e20rTables();

    }

	/*
    Give admin members access to everything.
    Add this to your active theme's functions.php or a custom plugin.
	*/
	public function admin_access_filter($access, $post, $user ) {

		if ( ( current_user_can('administrator') ) ) {
			// dbg("e20rTracker::admin_access_filter() - Administrator is attempting to access protected content.");
			return true;    //level 2 (and administrator) ALWAYS has access
		}

		return $access;
	}

    public function duplicate_cpt_link( $actions, $post ) {

        global $current_user;

        dbg("e20rTracker::duplicate_cpt_link() - Checking whether to add a 'Duplicate' link for the {$post->post_type} post type");

        $managed_types = apply_filters( 'e20r_tracker_duplicate_types', array(
                                                                'e20r_programs',
                                                                'e20r_workout',
                                                                'e20r_articles',
                                                                'e20r_assignments',
                                                                'e20r_exercises',
                                                            )
                            );

        if ( in_array( $post->post_type, $managed_types ) &&
                ($this->userCanEdit( $current_user->ID ) ||
                $this->is_a_coach( $current_user->ID )) ) {

            dbg("e20rTracker::duplicate_cpt_link() - Adding 'Duplicate' action for {$post->post_type} post(s)");
            $actions['duplicate'] = '<a href="admin.php?post=' . $post->ID . '&amp;action=e20r_duplicate_as_draft" title="' .__("Duplicate this item", "e20rtracker" ) .'" rel="permalink">' . __("Duplicate", "e20rtracker") . '</a>';
        }

        return $actions;
    }
    /**
      * Duplicate a E20R Tracker CPT and save the duplicate as 'draft'
      */
    public function duplicate_cpt_as_draft() {

        global $wpdb;

        dbg("e20rTracker::duplicate_cpt_as_draft() - Requested duplication of a CPT");
        dbg( $_GET['post'] );
        // dbg( $_POST['post'] );
        dbg( $_REQUEST['action'] );

        if ( !isset( $_GET['post'] ) && !isset( $_POST['post'] ) ) {
            dbg("e20rTracker::duplicate_cpt_as_draft() - One of the expected globals isn't set correctly?");
            wp_die("No E20R Tracker Custom Post Type found to duplicate!");
        }

        /*
         * Grab the old post (the one we're duplicating)
         */

        $e20r_post_id = ( isset( $_GET[ 'post'] ) ? $this->sanitize( $_GET[ 'post' ] ) : $this->sanitize( $_POST[ 'post' ] ) );
        $e20r_post = get_post( $e20r_post_id );

        /*
         * Set author as the current user.
         */
        $user = wp_get_current_user();

        if ( !$this->isEmpty( $e20r_post ) && !is_null( $e20r_post ) ) {

             // Copy the data for the new post.
            $new_post = array(
                'comment_status' => $e20r_post->comment_status,
                'ping_status'    => $e20r_post->ping_status,
                'post_author'    => $user->ID,
                'post_content'   => $e20r_post->post_content,
                'post_excerpt'   => $e20r_post->post_excerpt,
                'post_name'      => $e20r_post->post_name,
                'post_parent'    => $e20r_post->post_parent,
                'post_password'  => $e20r_post->post_password,
                'post_status'    => 'draft',
                'post_title'     => $e20r_post->post_title,
                'post_type'      => $e20r_post->post_type,
                'to_ping'        => $e20r_post->to_ping,
                'menu_order'     => $e20r_post->menu_order
            );


            dbg("e20rTracker::duplicate_cpt_as_draft() - Content for new post: ");
            dbg( $new_post );

            $new_id = wp_insert_post( $new_post, true );

            if ( is_wp_error( $new_id ) ) {
                dbg("e20rTracker::duplicate_cpt_as_draft() - Error: ");
                dbg( $new_id );
                wp_die("Unable to save the duplicate post!");
            }
            $taxonomies = get_object_taxonomies( $e20r_post->post_type );

            foreach( $taxonomies as $taxonomy ) {

               $post_terms = wp_get_object_terms( $e20r_post_id, $taxonomy, array( 'fields' => 'slugs') );
               wp_set_object_terms( $new_id, $post_terms, $taxonomy, false );
            }

            // Duplicate the post meta for the new post
            $sql = "SELECT meta_key, meta_value
                    FROM {$wpdb->postmeta}
                    WHERE post_id = {$e20r_post_id} AND meta_key LIKE '%e20r-%';";

            dbg( "e20rTracker::duplicate_cpt_as_draft() - SQL for postmeta: " . $sql );

            $meta_data = $wpdb->get_results( $sql );

            if ( !empty( $meta_data ) ) {

                $sql = "INSERT INTO {$wpdb->postmeta} ( post_id, meta_key, meta_value )";

                $query_sel = array();

                foreach( $meta_data as $meta ) {

                    $key = $meta->meta_key;
                    $value = addslashes( $meta->meta_value );
                    $query_sel[] = "SELECT {$new_id}, '{$key}', '{$value}'";
                }

                $sql .= implode( " UNION ALL ", $query_sel );

                $wpdb->query( $sql );
            }

            wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_id ) );
            exit;
        }
        else {
            wp_die("Unable to create a duplicate of post with ID {$e20r_post_id}. We couldn't locate it!");
        }
    }

	public function loadAllHooks() {

        global $current_user;
        global $pagenow;

        global $e20rClient;
        global $e20rMeasurements;
        global $e20rArticle;
        global $e20rAssignment;
        global $e20rAction;
        global $e20rExercise;
        global $e20rProgram;
        global $e20rWorkout;


        if ( ! $this->hooksLoaded ) {

            dbg("e20rTracker::loadAllHooks() - Adding action hooks for plugin");

	        $plugin = E20R_PLUGIN_NAME;

	        add_filter( 'e20r-tracker-configured-roles', array( $this, 'add_default_roles'), 5, 1);

            add_action( 'init', array( &$this, 'auth_timeout_reset'), 10 );
            add_action( 'init', array( &$this, 'update_db'), 7 );
            add_action( 'init', array( &$this, "dependency_warnings" ), 10 );
            add_action( 'init', array( &$this, "e20r_program_taxonomy" ), 10 );
            add_action( "init", array( &$this, "e20r_tracker_programCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_articleCPT"), 11 );
            add_action( "init", array( &$this, "e20r_tracker_actionCPT"), 12 );
            add_action( "init", array( &$this, "e20r_tracker_activitiesCPT"), 13 );
            add_action( "init", array( &$this, "e20r_tracker_assignmentsCPT"), 14 );
            add_action( "init", array( &$this, "e20r_tracker_exerciseCPT"), 15 );
            add_action( "init", array( &$this, "e20r_tracker_girthCPT" ), 16 );

            add_action( 'init', array( &$this, 'add_endpoint' ), 10 );
            add_action( 'init', array( &$this, 'add_rewrite_tags' ), 10);

            // add_action( 'heartbeat_received', array( &$e20rAssignment, 'heartbeat_received'), 10, 2);
            // add_filter( 'heartbeat_send', array( &$e20rAssignment, 'heartbeat_send'), 10, 2 );

            add_action( 'wp_ajax_e20r_coach_message', array( &$e20rAssignment, 'heartbeat_received'),10, 2);

            add_action( "wp_login", array( &$e20rClient, "record_login" ), 99, 2 );
            // add_action( 'plugins_loaded', array( &$this, "define_e20rtracker_roles" ) );
            add_action( 'e20r_schedule_email_for_client', array( &$e20rClient, 'send_email_to_client' ), 10, 2 );

            $action = ( isset( $_REQUEST['action'] ) && (false !== strpos($_REQUEST['action'], 'e20r')) ) ? $this->sanitize($_REQUEST['action']) : null;

             if ( !is_null( $action ) ) {
                add_action( "wp_ajax_nopriv_{$action}", "e20r_ajaxUnprivError" );
             }

	        add_action( 'wp_ajax_e20r_save_activity', array( &$e20rWorkout, 'saveExData_callback' ) );
	        add_action( 'wp_ajax_e20r_manage_option_list', array( &$e20rAssignment, 'manage_option_list') );

            add_filter( 'post_link', array( &$this, 'process_post_link' ), 10, 3 );
            add_filter( 'the_content', array( &$e20rArticle, 'contentFilter' ) );
            // add_filter( 'pmpro_after_change_membership_level', array( &$this, 'setUserProgramStart') );
            add_filter( 'pmpro_member_startdate', array( $e20rProgram, 'setProgramForUser'), 11, 3 );
            add_filter( 'auth_cookie_expiration', array( $this, 'login_timeout'), 100, 3 );
            add_filter( 'pmpro_email_data', array( &$this, 'filter_changeConfirmationMessage' ), 10, 2 );

            add_post_type_support( 'page', 'excerpt' );

            if ( !is_user_logged_in() ) {
                return;
            }

            // add_action( 'init', array( &$e20rAssignment, 'update_metadata' ), 20 );

            add_filter( "post_row_actions", array( &$this, 'duplicate_cpt_link'), 10, 2);
            add_filter( "page_row_actions", array( &$this, 'duplicate_cpt_link'), 10, 2);

            add_action( "admin_action_e20r_duplicate_as_draft", array( &$this, 'duplicate_cpt_as_draft') );
            add_action( 'admin_notices', array( &$this, 'display_admin_notice'  ) );

	        add_filter( "pmpro_has_membership_access_filter", array( &$this, "admin_access_filter" ), 10, 3);
	        add_filter( 'embed_defaults', array( &$e20rExercise, 'embed_default' ) );

	        dbg("e20rTracker::loadAllHooks() - Load upload directory filter? ". $e20rClient->isNourishClient( $current_user->ID));
            dbg("e20rTracker::loadAllHooks() - Pagenow = {$pagenow}" );

            $post_id =  ( !empty( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : null );

            if ( ( ! empty( $post_id ) ) && ( $pagenow == 'async-upload.php' || $pagenow == 'media-upload.php') ) {

                $parent = get_post($post_id)->post_parent;

                if (has_shortcode(get_post($post_id)->post_content, 'weekly_progress') ||
                    (!(('post' == get_post_type($post_id)) || ('page' == get_post_type($post_id))))
                ) {


                    dbg("e20rTracker::loadAllHooks() - Loading filter to change the upload directory for Nourish clients");
                    add_filter('media_view_strings', array(&$e20rMeasurements, 'clientMediaUploader'), 10);

                    dbg("e20rTracker::loadAllHooks() - Loaded filter to change the Media Library settings for client uploads");
                    add_filter("wp_handle_upload_prefilter", array(&$this, "pre_upload"));
                    add_filter("wp_handle_upload", array(&$this, "post_upload"));
                    dbg("e20rTracker::loadAllHooks() - Loaded filter to change the upload directory for Nourish clients");

                    dbg("e20rTracker::loadAllHooks() - Control Access to media uploader for e20rTracker users");

                    /* Control access to the media uploader for Nourish users */
                    add_action( 'pre_get_posts', array( &$this, 'restrict_media_library') );
                    add_filter( 'wp_handle_upload_prefilter', array( &$e20rMeasurements, 'setFilenameForClientUpload' ) );

                }
            }

            add_filter( 'page_attributes_dropdown_pages_args', array( &$e20rExercise, 'changeSetParentType'), 10, 2);
            add_filter( 'enter_title_here', array( &$this, 'setEmptyTitleString' ) );

            // add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_frontend_css') );
            // add_action( 'wp_footer', array( &$this, 'enqueue_user_scripts' ) );
            // add_action( 'wp_print_scripts', array( &$e20rClient, 'load_scripts' ) );
            // add_action( '', array( $e20rClient, 'save_gravityform_entry'), 10, 2 );

            add_action( 'wp_ajax_e20r_addAssignment', array( &$e20rArticle, 'add_assignment_callback') );
            add_action( 'wp_ajax_e20r_getDelayValue', array( &$e20rArticle, 'getDelayValue_callback' ) );
	        add_action( 'wp_ajax_e20r_removeAssignment', array( &$e20rArticle, 'remove_assignment_callback') );
            add_action( 'wp_ajax_e20r_add_reply', array( &$e20rAssignment, 'add_assignment_reply') );
            add_action( 'wp_ajax_e20r_assignmentData', array( &$e20rAssignment, 'ajax_assignmentData' ) );
            add_action( 'wp_ajax_e20r_manage_option_list', array( &$e20rAssignment, 'manage_option_list') );
            add_action( 'wp_ajax_e20r_update_message_status', array( &$e20rAssignment, 'update_message_status') );
            add_action( 'wp_ajax_e20r_daynav', array( &$e20rAction, 'nextCheckin_callback' ) );
            add_action( 'wp_ajax_e20r_save_item_data', array( &$e20rAction, 'ajax_save_item_data' ) );
            add_action( 'wp_ajax_e20r_saveCheckin', array( &$e20rAction, 'saveCheckin_callback' ) );
	        add_action( 'wp_ajax_e20r_save_daily_progress', array( &$e20rAction, 'dailyProgress_callback' ) );
            add_action( 'wp_ajax_e20r_clientDetail', array( &$e20rClient, 'ajax_clientDetail' ) );
            add_action( 'wp_ajax_e20r_complianceData', array( &$e20rClient, 'ajax_complianceData' ) );
            add_action( 'wp_ajax_e20r_getMemberListForLevel', array( &$e20rClient, 'ajax_getMemberlistForLevel' ) );
            add_action( 'wp_ajax_e20r_sendClientMessage', array( &$e20rClient, 'ajax_sendClientMessage' ) );
            add_action( 'wp_ajax_e20r_showClientMessage', array( &$e20rClient, 'ajax_showClientMessage' ) );
            add_action( 'wp_ajax_e20r_showMessageHistory', array( &$e20rClient, 'ajax_ClientMessageHistory') );
            add_action( 'wp_ajax_e20r_updateUnitTypes', array( &$e20rClient, 'updateUnitTypes') );
            add_action( 'wp_ajax_e20r_userinfo', array( &$e20rClient, 'ajax_userInfo_callback' ) );
            add_action( 'wp_ajax_e20r_checkCompletion', array( &$e20rMeasurements, 'checkProgressFormCompletion_callback' ) );
            add_action( 'wp_ajax_e20r_deletePhoto', array( &$e20rMeasurements, 'ajax_deletePhoto_callback' ) );
            add_action( 'wp_ajax_e20r_loadProgress', array( &$e20rMeasurements, 'ajax_loadProgressSummary' ) );
            add_action( 'wp_ajax_e20r_measurementDataForUser', array( &$e20rMeasurements, 'ajax_getPlotDataForUser' ) );
            add_action( 'wp_ajax_e20r_saveMeasurementForUser', array( &$e20rMeasurements, 'saveMeasurement_callback' ) );
            add_action( 'wp_ajax_e20r_add_exercise', array( &$e20rWorkout, 'add_new_exercise_to_group_callback' ) );
	        add_action( 'wp_ajax_e20r_add_new_exercise_group', array( &$e20rWorkout, 'add_new_exercise_group_callback' ) );
            add_action( 'wp_ajax_e20r_addWorkoutGroup', array( &$e20rWorkout, 'ajax_addGroup_callback' ) );
            add_action( 'wp_ajax_e20r_load_activity_stats', array( &$e20rWorkout, 'ajax_getPlotDataForUser' ) );
	        add_action( 'wp_ajax_e20r_save_activity', array( &$e20rWorkout, 'saveExData_callback' ) );

            /* AJAX call-backs if user is unprivileged */

            if ( is_admin() ) {
                add_action( 'save_post', array( &$this, 'save_girthtype_order' ), 10, 2 );
                add_action( 'save_post', array( &$e20rProgram, 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( &$e20rExercise, 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( &$e20rWorkout, 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( &$e20rAction, 'saveSettings' ), 10, 20);
                add_action( 'save_post', array( &$e20rArticle, 'saveSettings' ), 10, 20);
                add_action( 'save_post', array( &$e20rAssignment, 'saveSettings' ), 10, 20);

                add_action( 'post_updated', array( &$this, 'save_girthtype_order' ), 10, 2 );
                add_action( 'post_updated', array( &$e20rProgram, 'saveSettings' ) );
                add_action( 'post_updated', array( &$e20rExercise, 'saveSettings' ) );
                add_action( 'post_updated', array( &$e20rWorkout, 'saveSettings' ) );
                add_action( 'post_updated', array( &$e20rAction, 'saveSettings' ) );
                add_action( 'post_updated', array( &$e20rArticle, 'saveSettings' ) );
                add_action( 'post_updated', array( &$e20rAssignment, 'saveSettings' ) );

                add_action( 'add_meta_boxes_e20r_articles', array( &$e20rArticle, 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_assignments', array( &$e20rAssignment, 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_programs', array( &$e20rProgram, 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_exercises', array( &$e20rExercise, 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_workout', array( &$e20rWorkout, 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_actions', array( &$e20rAction, 'editor_metabox_setup') );

                add_action( 'admin_init', array( &$this, 'registerSettingsPage' ) );

                add_action( 'admin_head', array( &$this, 'post_type_icon' ) );
                add_action( 'admin_menu', array( &$this, 'loadAdminPage') );
                add_action( 'admin_menu', array( &$this, 'registerAdminPages' ) );
                add_action( 'admin_menu', array( &$this, "renderGirthTypesMetabox" ) );

                /* Allow admin to set the program ID for the user in their profile(s) */
                add_action( 'show_user_profile', array( &$e20rProgram, 'selectProgramForUser' ) );
                add_action( 'edit_user_profile', array( &$e20rProgram, 'selectProgramForUser' ) );
                add_action( 'edit_user_profile_update', array( &$e20rProgram, 'updateProgramForUser') );
                add_action( 'personal_options_update', array( &$e20rProgram, 'updateProgramForUser') );

                add_action( 'show_user_profile', array( &$e20rClient, 'selectRoleForUser' ) );
                add_action( 'edit_user_profile', array( &$e20rClient, 'selectRoleForUser' ) );
                add_action( 'edit_user_profile_update', array( &$e20rClient, 'updateRoleForUser') );
                add_action( 'personal_options_update', array( &$e20rClient, 'updateRoleForUser') );
                add_filter( "plugin_action_links_$plugin", array( &$this, 'plugin_add_settings_link' ) );

                // Custom columns
                add_filter( 'manage_edit-e20r_actions_columns', array( &$e20rAction, 'set_custom_edit_columns' ) );
                add_filter( 'manage_edit-e20r_assignments_columns', array( &$e20rAssignment, 'set_custom_edit_columns' ) );

                add_action( 'manage_e20r_actions_posts_custom_column' , array( &$e20rAction, 'custom_column'), 10, 2 );
                add_action( 'manage_e20r_assignments_posts_custom_column' , array( &$e20rAssignment, 'custom_column'), 10, 2 );

                add_filter( 'manage_edit-e20r_actions_sortable_columns', array( &$e20rAction, 'sortable_column' ) );
                add_filter( 'manage_edit-e20r_assignments_sortable_columns', array( &$e20rAssignment, 'sortable_column' ) );

                add_filter( 'pre_get_posts', array(&$e20rAction, 'sort_column') );
                add_filter( 'pre_get_posts', array(&$e20rAssignment, 'sort_column') );
				add_filter( 'posts_orderby', array(&$e20rAssignment, 'order_by') );

                // add_filter('manage_e20r_assignments_posts_columns', array( &$e20rAssignment, 'assignment_col_head' ) );
                // add_action('manage_e20r_assignments_posts_custom_column', array( &$e20rAssignment, 'assignment_col_content' ), 10, 2);

                add_filter('manage_e20r_exercises_posts_columns', array( &$e20rExercise, 'col_head' ) );
                add_action('manage_e20r_exercises_posts_custom_column', array( &$e20rExercise, 'col_content' ), 10, 2);

            }

            dbg("e20rTracker::loadAllHooks() - Scripts and CSS");
            /* Load scripts & CSS */
            add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_scripts') );
            add_action( 'wp_enqueue_scripts', array( &$this, 'has_weeklyProgress_shortcode' ) );
            add_action( 'wp_enqueue_scripts', array( &$this, 'has_measurementprogress_shortcode' ) );
            add_action( 'wp_enqueue_scripts', array( &$this, 'has_dailyProgress_shortcode' ) );
	        add_action( 'wp_enqueue_scripts', array( &$this, 'has_activity_shortcode' ) );
	        add_action( 'wp_enqueue_scripts', array( &$this, 'has_exercise_shortcode' ) );
	        add_action( 'wp_enqueue_scripts', array( &$this, 'has_profile_shortcode' ) );
	        add_action( 'wp_enqueue_scripts', array( &$this, 'has_clientlist_shortcode' ) );
	        add_action( 'wp_enqueue_scripts', array( &$this, 'has_summary_shortcode' ) );
	        add_action( 'wp_enqueue_scripts', array( &$this, 'has_gravityforms_shortcode' ) );

            dbg("e20rTracker::loadAllHooks() - Loading Short Codes");
            add_shortcode( 'weekly_progress', array( &$e20rMeasurements, 'shortcode_weeklyProgress' ) );
            add_shortcode( 'progress_overview', array( &$e20rMeasurements, 'shortcode_progressOverview') );
            add_shortcode( 'daily_progress', array( &$e20rAction, 'shortcode_dailyProgress' ) );
	        add_shortcode( 'e20r_activity', array( &$e20rWorkout, 'shortcode_activity' ) );
	        add_shortcode( 'e20r_activity_archive', array( &$e20rWorkout, 'shortcode_act_archive' ) );
	        add_shortcode( 'e20r_exercise', array( &$e20rExercise, 'shortcode_exercise' ) );
            add_shortcode( 'e20r_profile', array( &$e20rClient, 'shortcode_clientProfile' ) );
            add_shortcode( 'e20r_client_overview', array( &$e20rClient, 'shortcode_clientList') );
            add_shortcode( 'e20r_article_summary', array( &$e20rArticle, 'shortcode_article_summary') );
            add_shortcode( 'e20r_article_archive', array( &$e20rArticle, 'article_archive_shortcode') );

	        /* Gravity Forms data capture for Check-Ins, Assignments, Surveys, etc */
	        add_action( 'gform_after_submission', array( &$e20rClient, 'save_interview' ), 10, 2);
	        add_filter( 'gform_pre_render', array( &$e20rClient, 'load_interview' ) );
	        add_filter( 'gform_field_value', array( &$e20rClient, 'process_gf_fields'), 10, 3);

//	        add_action( 'gform_entry_post_save', array( &$e20rClient, 'save_interview'), 10, 2);
//  	    add_filter( 'gform_pre_validation', array( &$e20rClient, 'load_interview' ) );
//	        add_filter( 'gform_admin_pre_render', array( &$e20rClient, 'load_interview' ) );
// 	        add_filter( 'gform_pre_submission_filter', array( &$e20rClient, 'load_interview' ) );
//	        add_filter( 'gform_confirmation', array( &$this, 'gravity_form_confirmation') , 10, 4 );
//	        add_filter( 'wp_video_shortcode',  array( &$e20rExercise, 'responsive_wp_video_shortcode' ), 10, 5 );


	        dbg("e20rTracker::loadAllHooks() - Action hooks for plugin are loaded");
        }

        $this->hooksLoaded = true;
    }

    public function auth_timeout_reset() {

        global $current_user;
        $cookie_arr = null;

        dbg("e20rTracker::auth_timeout_reset() - Testing whether user's auth cookie needs to be reset");

        if ( is_user_logged_in() ) {

            foreach( $_COOKIE as $cKey => $cookie ) {

                if ( FALSE !== stripos( $cKey, "wordpress_logged_in_" ) ) {

                    $cookie_arr = preg_split( "/\|/", $cookie);

                    if ( !empty( $cookie_arr) && ( $current_user->user_login == $cookie_arr[0] ) ) {

                        $max_days = (int)$this->loadOption('remember_me_auth_timeout');

                        dbg("e20rTracker::auth_timeout_reset() - Found login cookie for user ID {$current_user->ID}");

                        $timeout = $cookie_arr[1];

                        if ( $timeout > ( current_time('timestamp', true ) + $max_days*3600*24 ) ) {

                            dbg("e20rTracker::auth_timeout_reset() - Will need to reset the auth cookie. Timeout is {$timeout}");

                            $days_since = $this->daysBetween( current_time('timestamp', true ), $timeout );

                            dbg("e20rTracker::auth_timeout_reset() - Days until: {$days_since} vs max ({$max_days}) ");

                            if ( $days_since > $max_days ) {

                                dbg("e20rTracker::auth_timeout_reset() - It will be {$days_since} days until the user ({$current_user->ID}) has to log in... Resetting the login cookie.");
                                wp_set_auth_cookie( $current_user->ID, false );
                            }
                        }
                    }
                }

                $cookie_arr = null;
                $cookie = null;
            }
        }
    }

    /**
      * Define the default roles as they pertain to the user's exercise level experience (Beginner, Intermediate, Experienced)
      *
      * @param      array       $roles      Associative array of defined roles for user's exercise level(s)
      * @return     array                   Associative array of defined roles for the user's exercise level(s).
      *
      * @since 1.5.50 - Initially added
      */
    public function add_default_roles( $roles ) {
    
        return array(
            'coach'         =>  array( 
                                        'role' => 'e20r_coach',
                                        'label' => __( "Coach", "e20rtracker"),
                                        'permissions' => array(
											'read' => true,
											'edit_users' => true,
											'upload_files' => true
                                         ),
                                ), 
            'beginner'      =>  array( 
                                        'role' => 'e20r_tracker_exp_1', 
                                        'label' => __( "Exercise Level 1 (NE)", "e20rtracker"),
                                        'permissions' => array(
											'read' => true,
                                            'upload_files' => true
                                         ),
                                ),
            'intermediate'  =>  array(
                                        'role' => 'e20r_tracker_exp_2',
                                        'label' => __( "Exercise Level 2 (IN)", "e20rtracker"),
                                        'permissions' => array(
											'read' => true,
                                            'upload_files' => true
                                         ),
                                ),
            'experienced'   =>  array(
                                        'role' => 'e20r_tracker_exp_3',
                                        'label' => __( "Exercise Level 3 (EX)", "e20rtracker"),
                                        'permissions' => array(
											'read' => true,
                                            'upload_files' => true
                                         ),
                                ),
        );
    }

    public function login_timeout( $seconds, $user_id, $remember ) {

        $expire_in = 0;

        dbg("e20rTracker::login_timeout() - Length requested by login process: {$seconds}, User: {$user_id}, Remember: {$remember}");

        /* "remember me" is checked */
        if ( $remember ) {

            dbg( "e20rTracker::login_timeout() - Remember me timeout value: " . $this->loadOption('remember_me_auth_timeout') );
            $expire_in = 60*60*24 * intval( $this->loadOption( 'remember_me_auth_timeout' ) );

            if ( $expire_in <= 0 ) { $expire_in = 60*60*24*1; } // 1 Day is the default

            dbg("e20rTracker::login_timeout() - Setting session timeout for user with 'Remember me' checked to: {$expire_in}");

        } else {

            dbg( "e20rTracker::login_timeout() - Timeout value in hours: " . $this->loadOption('auth_timeout') );
            $expire_in = 60 * 60 * intval( $this->loadOption( 'auth_timeout' ) );

            if ( $expire_in <= 0 ) {

                $expire_in = 60*60*3;
            } // 3 Hours is the default.

        }

        // check for Year 2038 problem - http://en.wikipedia.org/wiki/Year_2038_problem
        if ( PHP_INT_MAX - time() < $expire_in ) {

            $expire_in =  PHP_INT_MAX - time() - 5;
        }

        dbg("e20rTracker::login_timeout() - Setting session timeout for user {$user_id} to: {$expire_in}");

        return $expire_in;
    }

    public function pre_upload( $file ) {

        dbg("e20rTracker::pre_upload() -- Set upload directory path for the progress photos.");
        add_filter( 'upload_dir', array( &$this, "e20r_set_upload_dir" ) );
        // add_filter( 'upload_dir', "e20r_set_upload_dir" );

        dbg("e20rTracker::pre_upload() -- New upload directory path for the progress photos has been configured.");

        return $file;
    }

    public function post_upload( $fileinfo ) {

        dbg("e20rTracker::post_upload() -- Removing upload directory path hook.");
        remove_filter( "upload_dir", array( &$this, "e20r_set_upload_dir") );
        return $fileinfo;
    }

    public function e20r_set_upload_dir( $param ) {

        global $e20rClient;
        global $current_user;
        global $post;

        dbg("e20rTracker::set_progress_upload_dir() - Do we need to modify the upload directory?");
        dbg("Post ID: {$post->ID}" );
        /*
                if ( ! class_exists( "e20rClient" ) ) {

                    dbg("e20rTracker::set_progress_upload_dir() - No client class defined?!??");
                    return $param;
                }

                if ( ! isset( $post->ID ) ) {

                    dbg("e20rTracker::set_progress_upload_dir() - No page ID defined...");
                    return $param;
                }


                if ( ! $e20rClient->client_loaded ) {

                    dbg("e20rTracker::set_progress_upload_dir() - Need to load the Client information/settings...");
                    dbg("e20rTracker::set_progress_upload_dir() - Set ID for client info");
                    $e20rClient->setClient( $current_user->ID );
                    dbg("e20rTracker::set_progress_upload_dir() - Load default Client info");
                    $e20rClient->init();
                }

                dbg("e20rTracker::set_progress_upload_dir() - Fetching the upload path for client ID: " . $e20rClient->clientId());
                $path = $e20rClient->getUploadPath( $e20rClient->clientId() );

                $param['path'] = $param['basedir'] . "/{$path}";
                $param['url'] = $param['baseurl'] . "/{$path}";
                */
        dbg("e20rTracker::set_progress_upload_dir() - Directory: {$param['path']}");
        return $param;
    }

/*    public function shortcode_check( $post_id ) {

        global $post;

        if ( ( !isset($post->post_type) ) || !in_array( $post->post_type, array( 'post', 'page' ) ) ) {

            dbg( "e20rTracker::shortcode_check() - Not a post/page: " );
            return $post_id;
        }

        if ( empty( $post_id ) ) {

            dbg("e20rTracker::shortcode_check() - No post ID supplied");
            return false;
        }

        if ( wp_is_post_revision( $post_id ) ) {

            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE ) {

            return $post_id;
        }

        dbg("e20rTracker::shortcode_check() - Processing post/page for check against the e20r_activity short code");
        dbg( $post->post_content );

        if ( has_shortcode( $post->post_content, 'e20r_activity' ) ) {

            if ( ! is_user_logged_in() ) {

                auth_redirect();
            }

            dbg("e20rTracker::shortcode_check() - Found the activity shortcode. Save the ID ({$post_id}) for it!");
            $ePostId = $this->loadOption('e20r_activity_post');

            // if ( ( $ePostId != $post_id ) || ( false == $ePostId ) ) {

                $this->settings['e20r_activity_post'] = $post_id;
                $this->updateSetting( 'e20r_activity_post', $post_id );
                return $post_id;
            // }
        }

        return $post_id;
    }
*/
    private function inGroup( $id, $grpList ) {

        if ( in_array( -1, $grpList ) ) {

            dbg("e20rTracker::inGroup() - Admin has set 'All Groups'. Returning true");
            return true;
        }

        if ( in_array( $id, $grpList ) ) {

            dbg("e20rTracker::inGroup() - Group ID {$id} is in the group list. Returning true");
            return true;
        }
              

        dbg("e20rTracker::inGroup() - None of the tests returned true. Default is 'No access!'");
        return false;
    }

    private function inUserList( $id, $userList ) {

        if ( in_array( 0, $userList ) ) {

            dbg("e20rTracker::inUserList() - Admin has set 'Not Applicable' for user list. Returning false");
            return false;
        }

        if ( in_array( -1, $userList ) ) {

            dbg("e20rTracker::inUserList() - Admin has set 'All Users'. Returning true");
            return true;
        }

        if ( in_array( $id, $userList ) ) {

            dbg("e20rTracker::inUserList() - User ID {$id} is in the list of users. Returning true");
            return true;
        }

        dbg("e20rTracker::inUserList() - None of the tests returned true. Default is 'No access!'");
        return false;

    }

    /**
     * Check whether the user belongs to a group (membership level) or the user is directly specified in the list of users for the activity.
     *
     * @param $activity - The Activity object
     * @param $uId - The user ID
     * @param $grpId - The group ID
     *
     * @return array - True if the user is in the group list or user list for the activity
     *
     */
    public function allowedActivityAccess( $activity, $uId, $grpId ) {

        $ret = array( 'user' => false, 'group' => false );

        dbg("e20rTracker::allowedActivityAccess() - User: {$uId}, Group: {$grpId} and Activity: {$activity->id}");

        $statuses = apply_filters( 'e20r_tracker_article_post_status', array( 'publish', 'future', 'private' ));

        if ( !in_array( get_post_status( $activity->id ), $statuses ) ) {

            dbg("e20rTracker::allowedActivityAccess() - Access denied since activity post isn't in an allowed status");
            return $ret;
        }

        // Check against list of users for the specified activity.
        dbg("e20rTracker::allowedActivityAccess() - Check access for user ID {$uId}");
        $ret['user'] = $this->inUserList( $uId, $activity->assigned_user_id );

        // Check against group list(s) first.
        // Loop through any list of groups the user belongs to

        dbg("e20rTracker::allowedActivityAccess() - Check access for group ID {$grpId} vs " . print_r($activity->assigned_usergroups, true));
        $ret['group'] = $this->inGroup( $grpId, $activity->assigned_usergroups );

        // Return true if either user or group access is true.
        return $ret;
    }

    public function getURLToPageWithShortcode( $short_code = '' ) {

        $urls = array();

        switch ( $short_code ) {

            case 'e20r_activity':

                $id = $this->loadOption( 'e20r_activity_post' );

                if ( $id ) {
                    $urls[ $id ] = get_permalink( $id );
                }
                break;
        }
        /*
        if ( '' == $short_code ) {

            return null;
        }

        $args = array(
            's' => $short_code,
        );

        $the_query = new WP_Query( $args );

        if ( $the_query->have_posts() ) {

            while ( $the_query->have_posts() ) {

                $the_query->the_post();
                $urls[ the_ID() ] = the_permalink();

            }
        }
        else {
            dbg("e20rTracker::getURLToPageWithShortcode() - No page(s) with the short code '{$short_code}' was found!");
            return null;
        }

        wp_reset_postdata();
*/
        dbg("e20rTracker::getURLToPageWithShortcode() - Short code: {$short_code} -> Url(s): ");
        dbg($urls);

        return $urls;
    }

	public function trackerCPTs() {
		return array(
			'e20r_workout',
			'e20r_assignments',
			'e20r_programs',
			'e20r_articles',
			'e20r_exercises',
			'e20r_actions'
		);
	}

	function plugin_add_settings_link( $links ) {

		$settings_link = '<a href="options-general.php?page=e20r_tracker_opt_page">' . __( 'Settings', 'e20rtracker' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
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

            case 'e20r_actions':

                $title = 'Enter Action Short-code Here';
                remove_meta_box( 'postexcerpt', 'e20r_actions', 'side' );
                add_meta_box('postexcerpt', __('Action text'), 'post_excerpt_meta_box', 'e20r_actions', 'normal', 'high');

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

        if ( ( !class_exists('PMProSequence') && !class_exists("E20R\\Sequences\\Sequence\\Controller") )&& is_admin()) {

            ?>
            <div class="error">
            <?php if ( !class_exists('PMProSequence') && !class_exists("E20R\\Sequences\\Sequence\\Controller") ): ?>
                <?php dbg("e20rTracker::Error -  The The Sequences plugin is not installed"); ?>
                <p><?php _e( "Eighty / 20 Tracker - Missing dependency: Sequences plugin", 'e20rtracker' ); ?></p>
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
                $wp_query->set( 'author', $current_user->ID);
            }
        }
    }

    public function restrict_media_library( $wp_query_obj) {

        global $current_user, $pagenow;

        dbg("e20rTracker::restrict_media_library() - Check whether to restrict access to the media library...");

        if ( !is_a( $current_user, "WP_User") ) {

            return;
        }

        if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' ) {
            return;
        }

        if( ! current_user_can( 'manage_media_library') ) {

            dbg("e20rTracker::restrict_media_library() - User {$current_user->ID} is an author or better and has access to managing the media library");
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

	public function getUserKey( $userId = null ) {

		global $e20rTracker;
		global $post;
		global $current_user;

		if ( ! is_user_logged_in() ) {

			return null;
		}

/*
		if ( $userId === null ) {


			$userId = $current_user->ID;
		}
*/
        if ( ( $current_user->ID != 0 ) && ( null === $userId ) ) {

            $userId = $current_user->ID;
        }

        dbg("e20rTracker::getUserKey() - Test if user ({$userId}) can access their AES key based on the post {$post->ID}.");

		if ( $e20rTracker->hasAccess( $userId, $post->ID ) ) {
			dbg('e20rTracker::getUserKey() - User is permitted to access their AES key.');

			$key = Crypt::hexToBin( get_user_meta( $userId, 'e20r_user_key', true ) );

            // dbg( $key );

			if ( empty( $key ) ) {

				try {

                    dbg("e20rTracker::getUserKey() - Generating a new key for user {$userId}");
					$key = Crypt::createNewRandomKey();

					// WARNING: Do NOT encode $key with bin2hex() or base64_encode(),
					// they may leak the key to the attacker through side channels.

					if ( false === update_user_meta( $userId, 'e20r_user_key', Crypt::binToHex( $key ) ) ){

					    dbg("e20rTracker::getUserKey() - ERROR: Unable to save the key for user {$userId}");
					    return null;
					}

					dbg("e20rTracker::getUserKey() - New key generated for user {$userId}");
				}
				catch (Ex\CryptoTestFailedException $ex) {

					wp_die('Cannot safely create your encryption key');
				}
				catch (Ex\CannotPerformOperationException $ex) {

					wp_die('Cannot safely create an encryption key on your behalf');
				}
			}

            dbg("e20rTracker::getUserKey() - Returning key for user {$userId}");
			return $key;
		}
		else {
			return null;
		}

		return null;
	}

    public function encryptData( $data, $key ) {

        $enable = $this->loadOption('encrypt_surveys');

	    if ( $key === null ) {

            dbg("e20rTracker::encryptData() - No key defined!");
		    return base64_encode( $data );
	    }

        if ( empty( $key ) ) {

            dbg("e20rTracker::encryptData() - Unable to load encryption engine/key. Using Base64... *sigh*");

            return base64_encode( $data );
        }


        if ( 1 == $enable ) {

            dbg("e20rTracker::encryptData() - Configured to encrypt data.");

            try {

                $ciphertext = Crypt::encrypt($data, $key);
                return Crypt::binToHex($ciphertext);
            }
            catch (Ex\CryptoTestFailedException $ex) {

                wp_die('Cannot safely perform encryption');
            }
            catch (Ex\CannotPerformOperationException $ex) {
                wp_die('Cannot safely perform decryption');
            }
        }
        else {
            return $data;
        }
    }

    public function decryptData( $encData, $key, $encrypted = null ) {

        if ( is_null( $encrypted ) ) {

            $encrypted = $this->loadOption('encrypt_surveys');
        }

        dbg("e20rTracker::decryptData() - Encryption is " . ( $encrypted == 1 ? 'enabled' : 'disabled' ) );

	    if ( ( $key === null ) || ( ! $encrypted ) ) {

            dbg("e20rTracker::decryptData() - No decryption key - or encryption is disabled: {$encrypted}");
		    return base64_decode( $encData );
	    }

        try {

            dbg("e20rTracker::decryptData() - Attempting to decrypt data...");

            $data = Crypt::hexToBin( $encData );
            $decrypted = Crypt::decrypt( $data, $key);
            return $decrypted;
        }
        catch (Ex\InvalidCiphertextException $ex) { // VERY IMPORTANT
            // Either:
            //   1. The ciphertext was modified by the attacker,
            //   2. The key is wrong, or
            //   3. $ciphertext is not a valid ciphertext or was corrupted.
            // Assume the worst.
            wp_die('DANGER! DANGER! The encrypted information has been tampered with during transmission/load');
        }
        catch (Ex\CryptoTestFailedException $ex) {

            wp_die('Cannot safely perform encryption');
        }
        catch (Ex\CannotPerformOperationException $ex) {

            wp_die('Cannot safely perform decryption');
        }

    }

    public function updateSetting( $name, $value ) {

        dbg("e20rTracker::updateSetting() - Adding/updating setting: {$name} = {$value}");
        $this->settings[$name] = $value;
        update_option( $this->setting_name, $this->settings);
        return true;
    }

    public function save_girthtype_order( $post_id ) {

        global $post;

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

    public function validate( $input ) {

        dbg('e20rTracker::validate() - Running validation: ' . print_r( $input, true ) );

        $valid = $this->settings;

        foreach ( $input as $key => $value ) {

           if ( ( FALSE !== stripos( $key, 'converted_metadata' ) ) ||
                ( FALSE !== stripos( $key, '_tables' ) ) ||
                 ( FALSE !== stripos( $key, 'roles_' ) )) {

                if ( FALSE !== stripos( $key, 'converted_metadata' ) ) {

                    $value = false;
                }
                else {
                    $value = $this->validate_bool( $value );
                }
           }
           elseif ( ( FALSE !== stripos( $key, 'unserialize_notice' ) ) ) {

                $value = $value;
           }
           else {
                $value = intval($value);
           }

            if ( FALSE !== stripos( $key, 'e20r_db_version' ) ) {
                $value = E20R_DB_VERSION;
            }

            if ( FALSE !== stripos( $key, 'run_unserialize') ) {
                $value = E20R_RUN_UNSERIALIZE;
            }

           $valid[$key] = apply_filters( 'e20r_settings_validation_' . $key, $value );
        }

        /*
         * Use add_settings_error( $title (title of setting), $errorId (text identifier), $message (Error message), 'error' (type of message) ); if needed
         */

        unset( $input ); // Free.
        dbg('e20rTracker::validate() - Returning validation: ' . print_r( $valid, true ) );

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

        /* Add fields for the settings */
        add_settings_section( 'e20r_tracker_timeouts', 'User Settings', array( &$this, 'render_login_section_text' ), 'e20r_tracker_opt_page' );
        add_settings_field( 'e20r_tracker_login_timeout', __("Default login", 'e20r_tracker'), array( $this, 'render_logintimeout_select'), 'e20r_tracker_opt_page', 'e20r_tracker_timeouts');
        add_settings_field( 'e20r_tracker_rememberme_timeout', __("Extended login", 'e20r_tracker'), array( $this, 'render_remembermetimeout_select'), 'e20r_tracker_opt_page', 'e20r_tracker_timeouts');
        add_settings_field( 'e20r_tracker_encrypt_surveys', __("Encrypt Surveys", 'e20r_tracker'), array( $this, 'render_survey_select'), 'e20r_tracker_opt_page', 'e20r_tracker_timeouts');

        // add_settings_section( 'e20r_tracker_programs', 'Programs', array( &$this, 'render_program_section_text' ), 'e20r_tracker_opt_page' );
        // add_settings_field( 'e20r_tracker_measurement_day', __("Day to record progress", 'e20r_tracker'), array( $this, 'render_measurementday_select'), 'e20r_tracker_opt_page', 'e20r_tracker_programs');
        // add_settings_field( 'e20r_tracker_lesson_source', __("Drip Feed managing lessons", 'e20r_tracker'), array( $this, 'render_lessons_select'), 'e20r_tracker_opt_page', 'e20r_tracker_programs');

        add_settings_section( 'e20r_tracker_deactivate', 'Deactivation settings', array( &$this, 'render_deactivation_section_text' ), 'e20r_tracker_opt_page' );
        add_settings_field( 'e20r_tracker_purge_tables', __("Clear tables", 'e20r_tracker'), array( $this, 'render_purge_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');
        add_settings_field( 'e20r_tracker_delete_tables', __("Delete tables", 'e20r_tracker'), array( $this, 'render_delete_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');

        // add_settings_field( 'e20r_tracker_measured', __('Progress measurements', 'e20r_tracker'), array( $this, 'render_measurement_list'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate' );

        // $this->render_settings_page();

    }

    public function render_remembermetimeout_select() {

        $timeout = $this->loadOption( 'remember_me_auth_timeout' );
        ?>
        <select name="<?php echo $this->setting_name; ?>[remember_me_auth_timeout]" id="<?php echo $this->setting_name; ?>_remember_me_auth_timeout"> <?php
        foreach ( range( 1, 14 ) as $days ) { ?>
            <option value="<?php echo $days; ?>" <?php selected($days, $timeout); ?>><?php echo $days . ($days <= 1 ? " day" : " days") ?></option>
        <?php
        }
    }

    public function render_survey_select() {

            $encrypted = $this->loadOption( 'encrypt_surveys' );
        ?>
        <select name="<?php echo $this->setting_name; ?>[encrypt_surveys]" id="<?php echo $this->setting_name; ?>_encrypt_surveys">
            <option value="0" <?php selected(0, $encrypted); ?>>No</option>
            <option value="1" <?php selected(1, $encrypted); ?>>Yes</option>
        </select><?php

    }
    public function render_logintimeout_select() {

        $timeout = $this->loadOption( 'auth_timeout' );
        ?>
        <select name="<?php echo $this->setting_name; ?>[auth_timeout]" id="<?php echo $this->setting_name; ?>_auth_timeout"> <?php
        foreach ( range( 1, 12 ) as $hrs ) { ?>
            <option value="<?php echo $hrs; ?>" <?php selected($hrs, $timeout); ?>><?php echo $hrs . ($hrs <= 1 ? " hour" : " hours") ?></option>
        <?php
        } ?>
        </select><?php
    }

    public function render_delete_checkbox() {


        $options = get_option( $this->setting_name );
        $dVal = isset( $options['delete_tables'] ) ? $options['delete_tables'] : false;
        ?>
        <input type="checkbox" name="<?php echo $this->setting_name; ?>[delete_tables]" value="1" <?php checked( 1, $dVal ) ?> >
        <?php
    }

    public function render_purge_checkbox() {


        $options = get_option( $this->setting_name );
        $pVal = isset( $options['purge_tables'] ) ? $options['purge_tables'] : false;
        ?>
        <input type="checkbox" name="<?php echo $this->setting_name; ?>[purge_tables]" value="1" <?php checked( 1, $pVal ) ?> >
    <?php
    }

    public function render_login_section_text() {

        echo "<p>Configure user session timeout values. 'Extended' is the timeout value that will be used if a user selects 'Remember me' at login.</p><hr/>";
    }

    public function render_program_section_text() {

        echo "<p>Configure global Eighty / 20 Tracker settings.</p><hr/>";
    }

    public function render_deactivation_section_text() {

        echo "<p>Configure the behavior of the plugin when it gets deactivated.</p><hr/>";
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

        global $e20rClient, $e20rProgram, $e20rAction;
        global $e20rAdminPage;
        global $e20rClientInfoPage;

        dbg("e20rTracker::registerAdminPages() - Loading E20R Tracker Admin Menu");

        $e20rAdminPage = add_menu_page( __('E20R Tracker', "e20rtracker"), __( 'E20R Tracker','e20rtracker'), 'manage_options', 'e20r-tracker-info', array( &$e20rClient, 'render_client_page' ), 'dashicons-admin-generic', '71.1' );
        $e20rClientInfoPage = add_submenu_page( 'e20r-tracker-info', __( 'Client Info','e20rtracker'), __( "Coaching Page" ,'e20rtracker'), 'manage_options', 'e20r-client-info', array( &$e20rClient, 'render_client_page' ));

        $e20rProgramPage = add_menu_page( 'E20R Programs', __( 'E20R Programs','e20rtracker'), 'manage_options', 'e20r-tracker-programs', null, 'dashicons-admin-generic', '71.2' );
        $e20rArticles = add_menu_page( 'E20R Articles', __( 'E20R Articles','e20rtracker'), 'manage_options', 'e20r-tracker-articles', null, 'dashicons-admin-generic', '71.3' );
        $e20rActivies = add_menu_page( 'E20R Activities', __( 'E20R Actvities','e20rtracker'), 'manage_options', 'e20r-tracker-activities', null, 'dashicons-admin-generic', '71.4' );
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

        global $currentProgram;

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

            dbg("e20rTracker::datesForMeasurements() - Using program value");
            // $options = get_option( $this->setting_name );
            // $weekdayNumber = $options['measurement_day'];
            $weekdayNumber = $currentProgram->measurement_day;
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

/*    private function loadUserScripts() {

        global $currentClient;
        global $e20r_plot_jscript;


        // wp_print_scripts( 'e20r-jquery-json' );
    }
*/
    // URL: "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css"
    // URL: "//code.jquery.com/ui/1.11.2/jquery-ui.js"

	public function is_a_coach( $user_id ) {

        $user_roles = apply_filters('e20r-tracker-configured-roles', array() );

		$wp_user = get_user_by( 'id', $user_id );

		if ( $wp_user->has_cap( $user_roles['coach']['role'] ) ) {
		    return true;
        }

        return false;
	}

    public function hasAccess( $userId, $postId ) {

        global $e20rArticle;
        global $e20rProgram;

        global $currentArticle;

        $retVal = false;
        $saved = $currentArticle;

        dbg("e20rTracker::hasAccess() - Checking {$userId}'s access to {$postId}" );

        if ( user_can( $userId, 'publish_posts' ) && ( is_preview() ) ) {

            dbg("e20rTracker::hasAccess() - Post #{$postId} is a preview for {$userId}. Granting editor/admin access to the preview");
            return true;
        }

        dbg("e20rTracker::hasAccess() - Checking whether to use PMPro's pmpro_has_membership_access() function" );

        if ( function_exists( '\\pmpro_has_membership_access' ) ) {

            dbg("e20rTracker::hasAccess() - Preferring to use access check as provided by PMPro");
            $levels = pmpro_getMembershipLevelsForUser( $userId );
            $result = pmpro_has_membership_access( $postId, $userId, true ); //Using true to return all level IDs that have access to the sequence

            dbg("e20rTracker::hasAccess() - Checking if post {$postId} is accessible by {$userId}: " . print_r($result, true));

            if ( $result[0] ) {

                dbg("e20rTracker::hasAccess() - Does user {$userId} have access to this post {$postId}? " . ( $result[0] == 1 ? 'yes' : 'no' ));

                $filterVal = apply_filters('pmpro_has_membership_access_filter', $result[0], get_post($postId), get_user_by( 'id', $userId ), $levels );
                $retVal = ( $filterVal == 1 ? true : false );

                dbg( "e20rTracker::hasAccess() - After filter of access value for {$userId}: " . ( $retVal == true ? 'yes' : 'no' ));
            }
        }
        else {
            dbg("e20rTracker::hasAccess() - No membership access function found for Paid Memberships Pro");
        }

        $current_delay = $this->getDelay( 'now', $userId );
        $found = false;

        $programId = $e20rProgram->getProgramIdForUser( $userId );

        $articles = $e20rArticle->findArticles( 'post_id', $postId, $programId, $comp = '=' );
        // dbg( $articles);

        // if (!empty( $articles ) && ( 1 == count($articles ) ) ) {
        if ( !empty( $articles ) ) {

/*                $article = $articles[0];

                if ( $article->release_day <= $current_delay ) {
                    dbg("e20rTracker::hasAccess() - User {$userId} in program {$programId} has access to {$postId} because {$article->release_day} <= {$current_delay}");
                    $found = $article;
                }
            }
            elseif( !empty($articles ) ) {
*/
            $found = $this->get_closest_release_day( $current_delay, $articles );

            // dbg( $found );

            if ( !is_null($found) && ( $found->release_day <= $current_delay ) ) {
                dbg("e20rTracker::hasAccess() - User {$userId} in program {$programId} has access to {$postId} because {$found->release_day} <= {$current_delay}");
                $retVal = $retVal && true;
            }
            else {

                $retVal = false;
            }

        }

        $currentArticle = $saved;

        dbg("e20rTracker::hasAccess() - Returning " . ( $retVal ? 'true' : 'false' ) . " to calling function: " . $this->whoCalledMe() );
        return $retVal;
    }


    public function add_endpoint() {

        add_rewrite_rule(
            '^([^/]*)/([0-9]{4}-[0-9]{2}-[0-9]{2})/?$',
            'index.php?pagename=$matches[1]&article_date',
            'top'
        );
    }

    public function add_rewrite_tags() {

        add_rewrite_tag('%article_date%', '([0-9]{4}-[0-9]{2}-[0-9]{2})');
    }

    public function process_post_link( $permalink, $post, $leavename ) {

        global $currentArticle;

        global $current_user;
        // global $post;

        if ( !is_user_logged_in() ) {

            return str_replace( '%article_date%', '', $permalink );
        }

        if ( false === strpos( $permalink, '%article_date%' ) ) {

            dbg("e20rTracker::process_post_link() - No permalink containing the %article_date% tag");
            return $permalink;
        }

        if ( isset( $currentArticle->post_id ) && ( $currentArticle->post_id == $post->ID ) ) {

            $article_date = $this->getDateFromDelay( ($currentArticle->release_day -1), $current_user->ID );

            $article_date = ( !empty( $article_date ) ? $article_date : date('Y-m-d', current_time('timestamp') ) );
            $article_date = urlencode( $article_date );

            $permalink = str_replace( '%article_date%', $article_date, $permalink );

        } else {

            $permalink = str_replace( '%article_date%/', '', $permalink );
        }

        // dbg("e20rTracker::process_post_link() - Using permalink: {$permalink}");
        return esc_url($permalink);
    }

    public function add_query_vars( $vars ) {

        $vars[] = "article_date";

        return $vars;
    }

    private function get_closest_release_day($search, $posts) {

        $closest = null;

        foreach ($posts as $item) {

            if ( !isset($closest->release_day) || abs($search - $closest->release_day) > abs($item->release_day - $search)) {

                $closest = $item;
            }
        }

        return $closest;
    }

    public function filter_changeConfirmationMessage( $data, $email ) {

        global $current_user;
        global $wpdb;

        global $e20rProgram;
        global $currentProgram;

        if ( function_exists( 'pmpro_getMemberStartdate' ) ) {

            // If this is a "checkout" e-mail and we have a current user
            if (  isset( $current_user->ID ) && ( 0 != $current_user->ID ) ) {

                // Force the $currentProgram global to be populated
                $e20rProgram->getProgramIdForUser( $current_user->ID );

                // Grab the dates
                $available_when = $currentProgram->startdate;
                $today = date( 'Y-m-d', current_time( 'timestamp' ) );

                $membership_levels = pmpro_getMembershipLevelsForUser( $current_user->ID );
                $groups = $currentProgram->group;

                $levels = array();

                foreach( $membership_levels as $lvl ) {
                    $levels[]  = $lvl->id;
                }

                if ( !is_array( $groups ) ) {
                    $groups = array( $groups );
                }

                $is_in_program = array_intersect( $groups, $levels );

                dbg("e20rTracker::filter_changeConfirmationMessage() - Info: Today: {$today}, {$available_when}: ");
                dbg($is_in_program);

	            if ( !empty( $is_in_program ) ) {

                    if ( strtotime( $currentProgram->startdate )  > strtotime( $today ) ) {

                        $substitute = "Your membership account is active, <strong>but access to the Virtual Private Trainer content - including your daily workout routine - will <em>not</em> be available until Monday {$available_when}</strong>.<br/>In the mean time, why not take a peak in the Help Menu items and read through the Frequently Asked Questions?";

                    }else {
                        $substitute = "Your membership account is now active and you have full access to your Virtual Personal Trainer content. However, we recommend that you <a href=\"https://strongcubedfitness.com/login/\">log in</a> and spend some time reading the Help section (click the \"<a href=\"/faq/\">Help</a>\" menu)";
                    }

                   dbg("e20rTracker::filter_changeConfirmationMessage() - Sending: {$substitute}");

                    // Replace the message (after filters have been applied)
                    $data['e20r_status'] = $substitute;
                }
            }
/*            else {

                dbg("e20rTracker::filter_changeConfirmationMessage() - WARNING - User ({$email} isn't logged in while attempting to cancel");
                wp_mail( get_option( 'admin_email' ), 'Warning: Someone (not logged in to the site) attempted to update their membership status',  "User data: " . print_r( $current_user, true ) . "Data: " . print_r( $data, true ) . "User: " . print_r($email, true) );
                auth_redirect();
            }
*/
        }

        return $data;
    }

    public function isActiveClient( $clientId ) {

        global $e20rProgram;
        global $currentProgram;

        $retVal = false;

        dbg("e20rTracker::isActiveClient() - Run checks to see if the client is an active member of the site");

        $programId = $e20rProgram->getProgramIdForUser( $clientId );

        if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {

            dbg("e20rTracker::isActiveClient() - Check whether the user {$clientId} belongs to (one of) the program group(s)");
            $retVal = ( pmpro_hasMembershipLevel( $currentProgram->group, $clientId ) || $retVal );
        }

        return $retVal;
    }

    /**
     * Load all JS for Admin page
     */
    public function load_adminJS()
    {

        if ( is_admin() && ( ! wp_script_is( 'e20r_tracker_admin', 'enqueued' ) ) ) {

            global $e20r_plot_jscript;

	        wp_enqueue_style( "jquery-ui-tabs", "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css", false, '1.11.2' );

            wp_enqueue_style( "e20r-tracker-admin", E20R_PLUGINS_URL . "/css/e20r-tracker-admin.min.css", false, E20R_VERSION );
            wp_enqueue_style( "e20r-activity", E20R_PLUGINS_URL . "/css/e20r-activity.min.css", false, E20R_VERSION );
            wp_enqueue_style( "e20r-assignments", E20R_PLUGINS_URL . "/css/e20r-assignments.min.css", false, E20R_VERSION );
            wp_enqueue_style( "codetabs", E20R_PLUGINS_URL . "/css/codetabs/codetabs.css", false, E20R_VERSION );
            wp_enqueue_style( "code.animate", E20R_PLUGINS_URL . "/css/codetabs/code.animate.css", false, E20R_VERSION );
            wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
            wp_enqueue_style('jquery-ui-datetimepicker', E20R_PLUGINS_URL . "/css/jquery.datetimepicker.min.css", FALSE, E20R_VERSION);

            dbg("e20rTracker::load_adminJS() - Loading admin javascript");
            wp_register_script( 'select2', "//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js", array('jquery'), '4.0.0', true );
            wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js', array( 'jquery' ), '0.1', true );
            wp_register_script( 'jquery.autoresize', E20R_PLUGINS_URL . '/js/libraries/jquery.autogrowtextarea.min.js' , array('jquery'), E20R_VERSION, true );
            wp_register_script( 'codetabs', E20R_PLUGINS_URL . '/js/libraries/codetabs/codetabs.min.js', array( 'jquery' ), E20R_VERSION, true );
            wp_register_script( 'jquery-ui-tabs', "//code.jquery.com/ui/1.11.2/jquery-ui.js", array('jquery'), '1.11.2', true);
            wp_register_script( 'jquery-ui-datetimepicker', E20R_PLUGINS_URL . '/js/libraries/jquery.datetimepicker.min.js', array('jquery-ui-core' ,'jquery-ui-datepicker', 'jquery-ui-slider' ), E20R_VERSION, true);

            // wp_register_script( 'jquery-ui-timepicker-addon-slider', "//cdn.jsdelivr.net/jquery.ui.timepicker.addon/1.4.5/jquery-ui-sliderAccess.js", array( 'jquery-ui-core' ,'jquery-ui-datepicker', 'jquery-ui-slider' ), '1.11.2', true);
            // wp_register_script( 'jquery-ui-timepicker', "//cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.8.1/jquery.timepicker.min.js", array('jquery-ui-core' ,'jquery-ui-datepicker', 'jquery-ui-slider' ), '1.11.2', true);

            wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.min.js', array( 'jquery.timeago' ), '0.1', true );
            wp_register_script( 'e20r-progress-page', E20R_PLUGINS_URL . '/js/e20r-progress-measurements.min.js', array('jquery'), E20R_VERSION, false); // true == in footer of body.
            wp_register_script( 'e20r_tracker_admin', E20R_PLUGINS_URL . '/js/e20r-tracker-admin.min.js', array('jquery', 'e20r-progress-page'), E20R_VERSION, false); // true == in footer of body.
            wp_register_script( 'e20r-assignment-admin', E20R_PLUGINS_URL . '/js/e20r-assignment-admin.min.js', array( 'jquery' ), E20R_VERSION, true);
            wp_register_script( 'e20r-assignments', E20R_PLUGINS_URL . '/js/e20r-assignments.min.js', array( 'jquery', 'jquery.autoresize' ), E20R_VERSION, true);

            // $this->load_frontend_scripts('progress_overview');
            wp_localize_script( 'e20r-progress-page', 'e20r_admin',
                    array(
                        'timeout' => 30000,
                        'longpoll_timeout' => apply_filters('e20r-tracker-longpoll-timeout', 300000),
                    )
                );


            $e20r_plot_jscript = true;
	        self::register_plotSW();
            self::enqueue_plotSW();
            $e20r_plot_jscript = false;
            wp_print_scripts( 'select2' );
            wp_print_scripts( 'jquery.timeago' );
            wp_print_scripts( 'jquery.autoresize' );
            wp_print_scripts( 'jquery-ui-tabs' );
            wp_print_scripts( 'codetabs' );
            wp_print_scripts( 'jquery-ui-datetimepicker' );
            wp_print_scripts( 'e20r-tracker-js' );
            wp_print_scripts( 'e20r-progress-page' );
            wp_print_scripts( 'e20r_tracker_admin' );
            wp_print_scripts( 'e20r-assignments' );
            wp_print_scripts( 'e20r-assignment-admin' );
        }
    }

    public static function enqueue_admin_scripts( $hook ) {

        dbg("e20rTracker::enqueue_admin_scripts() - Loading javascript");

        global $e20rAdminPage;
        global $e20rClientInfoPage;

        global $e20rTracker;
        global $post;
        global $e20rTracker;

        wp_enqueue_style( 'fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', false, '4.4.0' );

        if( $hook == $e20rAdminPage || $hook == $e20rClientInfoPage ) {

            global $e20r_plot_jscript, $e20rTracker;
            global $e20rTracker;

            $e20rTracker->load_adminJS();

            $e20r_plot_jscript = true;
            $e20rTracker->register_plotSW( $hook );
            $e20rTracker->enqueue_plotSW( $hook );
            $e20r_plot_jscript = false;

            wp_enqueue_style( 'e20r_tracker', E20R_PLUGINS_URL . '/css/e20r-tracker.min.css', false, E20R_VERSION );
            // wp_enqueue_style( 'e20r_tracker-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
            wp_enqueue_style( 'select2', "//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css", false, '4.0.0' );
            wp_enqueue_script( 'jquery.timeago' );
            wp_enqueue_script( 'select2' );

        }

        if( $hook == 'edit.php' || $hook == 'post.php' || $hook == 'post-new.php' ) {

            switch( $e20rTracker->getCurrentPostType() ) {

                case 'e20r_actions':

                    wp_enqueue_script( 'jquery-ui-datepicker' );
                    wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

                    $type = 'action';
                    $deps = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker');
                    break;

                case 'e20r_programs':

                    wp_enqueue_style( 'e20r-tracker-program-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
	                $type = 'program';
					$deps = array('jquery', 'jquery-ui-core');
                    break;

                case 'e20r_articles':

                    $type = 'article';
                    $deps = array( 'jquery', 'jquery-ui-core' );

                    break;

	            case 'e20r_workout':

		            wp_enqueue_style( 'e20r-tracker-workout-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
		            $type = 'workout';
					$deps = array( 'jquery', 'jquery-ui-core' );
		            break;

                case 'e20r_assignments':

                    wp_enqueue_style( 'e20r-tracker-assignment-admin', E20R_PLUGINS_URL . '/css/e20r-tracker-admin.min.css', false, E20R_VERSION );
                    $type = 'assignment';
                    $deps = array( 'jquery', 'jquery-ui-core' );
                    break;

	            default:
		            $type = null;
            }

            dbg("e20rTracker::enqueue_admin_scripts() - Loading Custom Post Type specific admin script");

	        if ( $type !== null ) {

		        wp_register_script( 'e20r-cpt-admin', E20R_PLUGINS_URL . "/js/e20r-{$type}-admin.min.js", $deps, E20R_VERSION, true );

		        /* Localize ajax script */
		        wp_localize_script( 'e20r-cpt-admin', 'e20r_tracker',
			        array(
				        'ajaxurl' => admin_url( 'admin-ajax.php' ),
				        'timeout' => 30000,
				        'longpoll_timeout' => apply_filters('e20r-tracker-longpoll-timeout', 300000),
				        'lang'    => array(
					        'no_entry' => __( 'Please select', 'e20rtracker' ),
					        'no_ex_entry' => __( 'Please select an exercise', 'e20rtracker' ),
					        'adding' => __( 'Adding...', 'e20rtracker' ),
					        'add'   => __( 'Add', 'e20rtracker' ),
					        'saving' => __( 'Saving...', 'e20rtracker' ),
					        'save'   => __( 'Save', 'e20rtracker' ),
					        'edit'   => __( 'Update', 'e20rtracker' ),
					        'remove' => __( 'Remove', 'e20rtracker' ),
					        'empty'  => __( 'No exercises found.', 'e20rtracker' ),
					        'none'   => __( 'None', 'e20rtracker' ),
					        'no_exercises'  => __( 'No exercises found', 'e20rtracker' ),
				        ),
			        )
		        );

		        wp_enqueue_script( 'e20r-cpt-admin' );
	        }
        }
    }

    public function has_gravityforms_shortcode() {

        global $post;

        if ( ! isset( $post->ID ) ) {

            dbg("e20rTracker::has_gravityforms_shortcode() - No post ID present?");
            return;
        }

        if ( has_shortcode( $post->post_content, 'gravityform' ) ) {

            $this->load_frontend_scripts('defaults');
        }
    }

    public function has_measurementprogress_shortcode() {

        global $post;
        global $e20rClient;
        global $e20rProgram;
        global $currentClient;
        global $e20r_plot_jscript;

        global $current_user;


        if ( ! isset( $post->ID ) ) {

            dbg("e20rTracker::has_measurementprogress_shortcode() - No post ID present?");
            return;
        }

        if ( has_shortcode( $post->post_content, 'progress_overview' ) ) {

            if ( ! is_user_logged_in() ) {

                auth_redirect();
            }

            $e20rProgram->getProgramIdForUser( $current_user->ID );
            // $e20rArticle->init( $post->ID );

            if ( !isset( $currentClient->loadedDefaults ) || ( $currentClient->loadedDefaults == true ) ) {

                dbg( "e20rTracker::has_measurementprogress_shortcode() - Have to init e20rClient class & grab data..." );
                $e20rClient->init();
            }

            dbg("e20rTracker::has_measurementprogress_shortcode() - Loading scripts and styles for assignments and progress_overview");
            $this->load_frontend_scripts( array(
                            'assignments',
                            'progress_overview'
                        )
                    );
        }
    }

	public function has_exercise_shortcode() {

		global $post;

        if ( ! isset( $post->ID ) ) {
            return;
        }

        if ( has_shortcode( $post->post_content, 'e20r_exercise' ) ) {

            if ( ! is_user_logged_in() ) {

                auth_redirect();
            }

			dbg("e20rTracker::has_exercise_shortcode() -- Loading & adapting user javascripts for exercise form(s). ");
            $this->load_frontend_scripts( 'exercise' );
		}

	}

    public function has_summary_shortcode() {

        global $post;

        if ( !isset( $post->ID ) ) {
            return;
        }

        if ( has_shortcode( $post->post_content, 'e20r_article_summary' ) ) {

            if ( !is_user_logged_in() ) {

                auth_redirect();
            }

            dbg("e20rTracker::has_summary_shortcode() - Load CSS for weekly summary post");
            $this->load_frontend_scripts( 'article_summary' );
        }
    }

	public function has_activity_shortcode() {

		global $post;
		global $pagenow;

        global $e20rProgram;
        global $current_user;

        if ( ! isset( $post->ID ) ) {
            return;
        }

        if ( ( has_shortcode( $post->post_content, 'e20r_activity' ) || ( has_shortcode( $post->post_content, 'e20r_activity_archive' ) ) ) ) {

            if ( ! is_user_logged_in() ) {

                auth_redirect();
            }

            $e20rProgram->getProgramIdForUser( $current_user->ID );

            dbg("e20rTracker::has_activity_shortcode() -- Loading & adapting user javascripts for activity/exercise form(s). ");
            $this->load_frontend_scripts( 'activity' );
		}

	}

    public function has_dailyProgress_shortcode() {

        global $post;
        global $pagenow;

        global $currentProgram;
        global $current_user;

        global $e20rProgram;

        if ( ! isset( $post->ID ) ) {
            return;
        }


        if ( has_shortcode( $post->post_content, 'daily_progress' ) ) {

            if ( !is_user_logged_in() ) {

                auth_redirect();
            }

            $e20rProgram->getProgramIdForUser( $current_user->ID );

            dbg("e20rTracker::has_dailyProgress_shortcode() -- Loading & adapting activity/assignment CSS & Javascripts. ");

            $this->load_frontend_scripts( array( 'daily_progress', 'assignments' ) );
        }
    }

    public function has_clientlist_shortcode() {

        global $post;


        if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'e20r_client_overview' ) ) {

            if ( ! is_user_logged_in() ) {

                auth_redirect();
            }

            dbg("e20rTracker::has_clientlist_shortcode() -- Loading & adapting activity/assignment CSS & Javascripts. ");

            $this->load_frontend_scripts('client_overview');
        }
    }

    public function has_profile_shortcode() {

		global $post;

        if ( ! isset( $post->ID )  ) {
            return;
        }

        if ( has_shortcode( $post->post_content, 'e20r_profile' ) ) {

            if ( !is_user_logged_in()) {
                auth_redirect();
            }

			dbg("e20rTracker::has_profile_shortcode() -- Loading & adapting user javascripts for exercise form(s). ");
            $this->load_frontend_scripts( array( 'assignments', 'progress_overview', 'daily_progress', 'profile' ) );
        }

    }
    /**
     * Load Javascript for the Weekly Progress page/shortcode
     */
    public function has_weeklyProgress_shortcode() {

        global $e20rMeasurements;
        global $e20rClient;
        global $e20rMeasurementDate;
        global $e20rArticle;
        global $e20rProgram;
        global $pagenow;
        global $post;
        global $current_user;
	    global $currentArticle;
	    global $currentProgram;
	    global $currentClient;

        if ( !isset( $post->ID ) ) {
            return;
        }

        if ( has_shortcode( $post->post_content, 'weekly_progress' ) ) {

            if ( ! is_user_logged_in() ) {

                auth_redirect();
            }

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Found the weekly progress shortcode on page: {$post->ID}: ");

	        $this->register_plotSW();

            // Get the requested Measurement date & article ID (passed from the "Need your measuresments today" form.)
            $measurementDate = isset( $_POST['e20r-progress-form-date'] ) ? sanitize_text_field( $_POST['e20r-progress-form-date'] ) : null;
            $articleId = isset( $_POST['e20r-progress-form-article']) ? intval( $_POST['e20r-progress-form-article']) : null;

            $e20rMeasurementDate = $measurementDate;

            $userId = $current_user->ID;
            $e20rProgram->getProgramIdForUser( $userId );
            $programId = $currentProgram->id;
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

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Loading measurements for: " . ( !isset( $measurementDate ) ? 'No date given' : $measurementDate ) );
            $e20rMeasurements->init( $measurementDate, $userId );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Register scripts");

            $this->enqueue_plotSW();
            wp_register_script( 'e20r-jquery-json', E20R_PLUGINS_URL . '/js/libraries/jquery.json.min.js', array( 'jquery' ), '0.1', false );
            wp_register_script( 'jquery-colorbox', "//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.4.33/jquery.colorbox-min.js", array('jquery'), '1.4.33', false);
            wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js', array( 'jquery' ), E20R_VERSION, false );

            if (! WP_DEBUG) {
                wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.min.js', array( 'jquery.timeago' ), E20R_VERSION, false );
                wp_register_script( 'e20r-progress-js', E20R_PLUGINS_URL . '/js/e20r-progress.min.js', array( 'e20r-tracker-js' ) , E20R_VERSION, false );
            }
            else {
                wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery.timeago' ), E20R_VERSION, false );
                wp_register_script( 'e20r-progress-js', E20R_PLUGINS_URL . '/js/e20r-progress.js', array( 'e20r-tracker-js' ) , E20R_VERSION, false );
            }


            dbg("e20rTracker::has_weeklyProgress_shortcode() - Find last weeks measurements");

            $lw_measurements = $e20rMeasurements->getMeasurement( 'last_week', true );
            dbg("e20rTracker::has_weeklyProgress_shortcode() - Measurements from last week loaded:");

            $bDay = $e20rClient->getBirthdate( $userId );
            dbg("e20rTracker::has_weeklyProgress_shortcode() - Birthdate for {$userId} is: {$bDay}");

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Check if user has completed Interview?");
            if ( ! $e20rClient->completeInterview( $userId, $programId ) ) {

	            dbg("e20rTracker::has_weeklyProgress_shortcode() - No USER DATA found in the database. Redirect to User interview info!");


                if ( empty( $currentProgram->incomplete_intake_form_page ) ) {
                    $url = $e20rProgram->get_welcomeSurveyLink( $userId );
                }
                else {
                    $url = get_permalink( $currentProgram->incomplete_intake_form_page );
                }

                dbg("e20rTracker::has_weeklyProgress_shortcode() - URL to redirect to: {$url}");
                if ( !empty( $url ) ) {

	                wp_redirect( $url, 302 );
	                exit;
	                // wp_die("Tried to redirect to");
                }
	            else {
		            dbg("e20rTracker::has_weeklyProgress_shortcode() - No URL defined! Can't redirect.");
	            }
            }

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Localizing progress script for use on measurement page");
			dbg("e20rTracker::has_weeklyProgress_shortcode() - Loading survey data for user...");

            /* Load user specific settings */
            wp_localize_script( 'e20r-progress-js', 'e20r_progress',
                array(
                    'ajaxurl'   => admin_url('admin-ajax.php'),
                    'settings'     => array(
                        'article_id'        => $articleId,
                        'lengthunit'        => $e20rClient->getLengthUnit(),
                        'weightunit'        => $e20rClient->getWeightUnit(),
	                    'interview_url'     => $e20rProgram->get_welcomeSurveyLink($userId),
                        'imagepath'         => E20R_PLUGINS_URL . '/img/',
                        'overrideDiff'      => ( isset( $lw_measurements->id ) ? false : true ),
                        'measurementSaved'  => ( $articleURL ? $articleURL : E20R_COACHING_URL . 'home/' ),
                        'weekly_progress'   => get_permalink( $currentProgram->measurements_page_id ),
                    ),
                    'measurements' => array(
                        'last_week' => json_encode( $lw_measurements, JSON_NUMERIC_CHECK ),
                    ),
                    'user_info'    => array(
                        'userdata'          => json_encode( $e20rClient->get_data( $userId, true, true ), JSON_NUMERIC_CHECK ),
                        'interview_complete' => $e20rClient->completeInterview( $userId ),
//                        'progress_pictures' => '',
//                        'display_birthdate' => ( empty( $bDay ) ? false : true ),

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

                function getBirthday() {

	                var bdate = jQuery('#bdyear').val() + '-' + jQuery('#bdmonth').val() + '-' + jQuery('#bdday').val();

	                console.log("getBirthday() = ", bdate );

	                // TODO: Send to backend for processing/to be added.

                }

                console.log("WP script for E20R Progress Update (client-side) loaded");

                console.log("Loading user_info: ", NourishUser );
                console.log( "Loading Measurement data for last week", LAST_WEEK_MEASUREMENTS );
                console.log( "Interview is complete: ", e20r_progress.user_info.interview_complete );

                if ( e20r_progress.user_info.interview_complete === false ) {
                    console.log("Need to redirect this user to the Interview page!");
                    location.href=e20r_progress.settings.interview_url;
                }

                setBirthday();
            </script>
            <?php

            if ( ! wp_style_is( 'e20r-tracker', 'enqueued' )) {

                dbg("e20rTracker::has_weeklyProgress_shortcode() - Need to load CSS for e20rTracker.");
                wp_deregister_style("e20r-tracker");
                wp_enqueue_style( "e20r-tracker", E20R_PLUGINS_URL . '/css/e20r-tracker.min.css', false, E20R_VERSION );
            }

        } // End of shortcode check for weekly progress form
    }

    private function register_script( $script, $location, $deps ) {

        dbg("e20rTracker::register_script() - script: {$script}, location: {$location}, dependencies ");
        // dbg($deps);

        wp_register_script( $script, $location, $deps, E20R_VERSION, true );
    }

    public function load_frontend_scripts( $events ) {

        if (defined('DOING_AJAX') && DOING_AJAX) {

            dbg("e20rTracker::load_frontend_scripts() - Doing AJAX call. No need to load any scripts/styling");
            return;
        }

        global $e20r_plot_jscript;
        global $e20rTracker;

        global $current_user;
        global $post;

        global $currentClient;
        global $currentProgram;

        if ( !is_user_logged_in() ) {

            auth_redirect();
        }

        if ( !is_array( $events ) ) {
            $events = array( $events );
        }

        $load_jq_plot = false;

        dbg("e20rTracker::load_frontend_scripts() - Loading " . count( $events ) . " script events");
        foreach( $events as $event ) {

            $css_list = array( 'print', 'e20r-tracker', 'e20r-tracker-activity' );
            $css = array(
                "e20r-print" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/print.css' : '/css/print.min.css' ),
                "e20r-tracker" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-tracker.css' : '/css/e20r-tracker.min.css'),
                "e20r-tracker-activity" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-activity.css' : '/css/e20r-activity.min.css' ),
            );

            $scripts = array();
            $prereqs = array(
                'jquery' => null,
                'jquery-ui-core' => null,
                'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                'dependencies' => array(),
            );

            switch ( $event ) {

                case 'article_summary':

                    $load_jq_plot = false;
                    dbg("e20rTracker::load_frontend_scripts() - Loading CSS for the article summary page.");

                    $css = array_replace( $css, array(
                            'e20r-article-summary' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-article-summary.css' : '/css/e20r-article-summary.min.css' ),
                        )
                    );

                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                        )
                    ) );

                    break;

                case 'client_overview':

                    dbg("e20rTracker::load_frontend_scripts() - Loading for the 'e20r_client_overview' shortcode");
                    $load_jq_plot = false;

                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                        )
                    ) );

                    break;

                case 'profile':

                    dbg("e20rTracker::load_frontend_scripts() - Loading for the 'e20r_profile' shortcode");
                    $load_jq_plot = true;

                    $css = array_replace( $css, array(
                        "jquery-ui-tabs" => "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css",
                        "codetabs" => E20R_PLUGINS_URL . "/css/codetabs/codetabs.css",
                        "codetabs-animate" => E20R_PLUGINS_URL . "/css/codetabs/code.animate.css",
                    ) );

                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        "jquery-ui-tabs" => "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css",
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'jquery-ui-tabs' => array( 'jquery', 'jquery-ui-core' ),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                        )
                    ) );

                    $scripts = array_replace( $scripts, array(
                        'jquery.codetabs' => E20R_PLUGINS_URL . '/js/libraries/codetabs/codetabs.min.js',
                        'dependencies' => array(
                            'jquery.codetabs' => array( 'jquery' ),
                        ),
                    ) );

                    break;

                case 'assignments':

                    dbg("e20rTracker::load_frontend_scripts() - Loading the assignments javascripts");
                    dbg("e20rTracker::load_frontend_scripts() - Path to thickbox: " . home_url( '/' . WPINC . "/js/thickbox/thickbox.css" ));

                    $css = array_replace( $css, array(
                        "thickbox" => null,
                        "e20r-assignments" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? "/css/e20r-assignments.css" : "/css/e20r-assignments.min.css" ),
                    ) );

                    $prereqs = array_replace( $prereqs, array(
                        'heartbeat' => null,
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'thickbox' => null,
                        'jquery.autoresize' => E20R_PLUGINS_URL . '/js/libraries/jquery.autogrowtextarea.min.js',
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'dependencies' => array(
                            'heartbeat' => false,
                            'jquery' => false,
                            'jquery-ui-core' => array('jquery'),
                            'jquery.autoresize' => array('jquery'),
                            'jquery-touchpunch' => array('jquery', 'jquery-ui-core'),
                            'thickbox' => array('jquery'),
                        )
                    ) );

                    $scripts = array_replace( $scripts, array(
                        'e20r_assignments' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-assignments.js' : '/js/e20r-assignments.min.js' ),
                        'dependencies' => array(
                            'e20r_assignments' => array('jquery', 'thickbox', 'jquery.autoresize' ),
                        )
                    ) );

                    $script = 'e20r_assignments';
                    $id = 'e20r_assignments';

                    break;

                case 'progress_overview':

                    dbg("e20rTracker::load_frontend_scripts() - Loading for the 'progress_overview' shortcode");

                    $load_jq_plot = true;

                    $css = array_replace( $css, array(
                        'thickbox' => null,
                        "jquery-ui-tabs" => "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css",
                        "codetabs" => E20R_PLUGINS_URL . "/css/codetabs/codetabs.css",
                        "codetabs-animate" => E20R_PLUGINS_URL . "/css/codetabs/code.animate.css",
                    ) );

                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'thickbox' => null,
                        'jquery-ui-tabs' => "//code.jquery.com/ui/1.11.2/jquery-ui.min.js",
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'jquery.timeago' => E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js',
                        'jquery.codetabs' => E20R_PLUGINS_URL . '/js/libraries/codetabs/codetabs.min.js',
                        'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'thickbox' => array('jquery'),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                            'jquery-ui-tabs' => array( 'jquery', 'jquery-ui-core' ),
                            'jquery.easing' => array( 'jquery' ),
                            'jquery.timeago' => array( 'jquery' ),
                            'jquery.codetabs' => array( 'jquery' ),
                            'e20r_tracker' => array( 'jquery', 'jquery-ui-core', 'jquery-touchpunch', 'jquery.timeago', 'jquery.codetabs', 'jquery-ui-tabs' ),
                        )
                    ) );

                    $scripts = array_replace( $scripts, array(
                        'e20r-progress-measurements' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-progress-measurements.js' : '/js/e20r-progress-measurements.min.js' ),
                        'dependencies' => array(
                            'e20r-progress-measurements' => array( 'jquery', 'jquery-ui-core', 'jquery-touchpunch', 'jquery.timeago', 'jquery.codetabs', 'jquery-ui-tabs',  'e20r_tracker' )
                        )
                    ) );

                    $script = 'e20r-progress-measurements';
                    $id = 'e20r_progress';

                    break;

                case 'exercise':

                    dbg("e20rTracker::load_frontend_scripts() - Loading for the 'exercise' shortcode");
                    $load_jq_plot = false;

                    $css = array_replace( $css, array(
                                'e20r-exercise' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? "/css/e20r-exercise.css" : "/css/e20r-exercise.min.css" ),
                            )
                        );

                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'fitvids' => '//cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js',
                        'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                            'fitvids' => array( 'jquery' ),
                            'e20r_tracker' => array( 'jquery', 'jquery-ui-core', 'jquery-touchpunch', 'fitvids' ),
                        )
                    ) );

                    $scripts = array_replace( $scripts, array(
                        'e20r_exercise' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-exercise.js' : '/js/e20r-exercise.min.js'),
                        'dependencies' => array(
                            'e20r_exercise' => array( 'jquery', 'jquery-ui-core', 'jquery-touchpunch', 'fitvids', 'e20r_tracker' )
                        )
                    ) );

                    $script = 'e20r_exercise';
                    $id = 'e20r_exercise';

                    break;

                case 'activity':

                    dbg("e20rTracker::load_frontend_scripts() - Loading for the 'activity' shortcode");
                    $load_jq_plot = true;

                    $css = array_replace( $css, array(
                        "e20r-assignments" => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-assignments.css' : '/css/e20r-assignments.min.css' ),
                    ) );

                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'fitvids' => '//cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js',
                        'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
                        'e20r_exercise' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-exercise.js' : '/js/e20r-exercise.min.js' ),
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                            'fitvids' => array( 'jquery' ),
                            'e20r_tracker' => array( 'jquery', 'fitvids' ),
                            'e20r_exercise' => array( 'jquery', 'jquery-ui-core', 'jquery-touchpunch', 'fitvids', 'e20r_tracker' )
                        )
                    ) );

                    $scripts = array_replace( $scripts, array(
                        'e20r_workout' => E20R_PLUGINS_URL . '/js/e20r-workout.min.js',
                        'dependencies' => array(
                            'e20r_workout' => array( 'jquery', 'fitvids', 'e20r_tracker', 'e20r_exercise' )
                        )
                    ) );

                    $script = 'e20r_workout';
                    $id = 'e20r_workout';

                    break;

                case 'daily_progress':

                    dbg("e20rTracker::load_frontend_scripts() - Loading for the 'daily_progress' shortcode");
                    $load_jq_plot = false;

                    $css = array_replace( $css, array(
                        'select2' => 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css',
                        "codetabs" => E20R_PLUGINS_URL . "/css/codetabs/codetabs.css",
                        "codetabs-animate" => E20R_PLUGINS_URL . "/css/codetabs/code.animate.css",
                        'e20r_action'  => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/css/e20r-action.css' : '/css/e20r-action.min.css' ),
                    ) );

                    // 'jquery.ui.tabs' => "//code.jquery.com/ui/1.11.2/jquery-ui.min.js",
                    //  'jquery-effects-core' => null,
                    $prereqs = array_replace( $prereqs, array(
                        'jquery' => null,
                        'jquery-ui-core' => null,
                        'select2' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js',
                        'base64' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/libraries/Base64.js' : '/js/libraries/Base64.min.js' ),
                        'jquery-touchpunch' => E20R_PLUGINS_URL . '/js/libraries/jquery.ui.touch-punch.min.js',
                        'jquery.autoresize' => E20R_PLUGINS_URL . '/js/libraries/jquery.autogrowtextarea.min.js',
                        'jquery.timeago' => E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.min.js',
                        'jquery.redirect' => E20R_PLUGINS_URL . '/js/libraries/jquery.redirect.min.js',
                        'e20r_tracker' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-tracker.js' : '/js/e20r-tracker.min.js' ),
                        'dependencies' => array(
                            'jquery' => false,
                            'jquery-ui-core' => array( 'jquery' ),
                            'base64' => false,
                            'select2' => array( 'jquery' ),
                            'jquery-touchpunch' => array( 'jquery', 'jquery-ui-core' ),
                            'jquery.autoresize' => array( 'jquery' ),
                            'jquery.timeago' => array( 'jquery' ),
                            'jquery.redirect' => array( 'jquery' ),
                            'e20r_tracker' => array( 'jquery' ),
                        )
                    ) );

                    $scripts = array_replace( $scripts, array(
                        'e20r_action' => E20R_PLUGINS_URL . ( true === WP_DEBUG ? '/js/e20r-action.js' : '/js/e20r-action.min.js'),
                        'dependencies' => array(
                            'e20r_action' => array( 'jquery', 'base64', 'select2', 'jquery-ui-core', 'jquery-touchpunch', 'jquery.timeago', 'jquery.autoresize', 'jquery.redirect', 'e20r_tracker'),
                        ),
                    ) );

                    $script = 'e20r_action';
                    $id = 'e20r_action';

                    break;

                case 'default':
                    $load_jq_plot = false;
                    dbg("e20rTracker::load_frontend_scripts() - Loading CSS for the standard formatting & gravity forms pages.");
                    break;

            }

//            dbg("e20rTracker::load_frontend_scripts() - Scripts to print, prerequisites, scripts and CSS:");
//            dbg($prereqs);

            $prereq = array( 'jquery', 'jquery-ui-core', 'jquery-touchpunch' );

            foreach( $prereqs as $tag => $url ) {

                if ( 'dependencies' != $tag ) {

                    if ( !empty( $url ) ) {

                        dbg("e20rTracker::load_frontend_scripts() - Adding {$tag} as prerequisite for {$event}");
                        $this->register_script( $tag, $url, $prereqs['dependencies'][$tag] );
                    }

                    if ( !in_array( $tag, $prereq ) ) {

                        dbg("e20rTracker::load_frontend_scripts() - Adding {$tag} to list of prerequisites to print/enqueue");
                        $prereq[] = $tag;
                    }
                }
            }

            // $prereq = array_keys( $prereq );
            dbg("e20rTracker::load_frontend_scripts() - For the prerequisites -- wp_print_scripts( " . print_r( $prereq, true) . " )");
            wp_enqueue_script( $prereq );

            $list = array();

            foreach( $scripts as $tag => $url ) {

                if ( 'dependencies' != $tag ) {

                    if ( !empty( $url ) ) {

                        dbg("e20rTracker::load_frontend_scripts() - Adding {$tag} as script for {$event}");
                        $this->register_script( $tag, $url, $scripts['dependencies'][$tag] );
                    }

                    if ( !in_array( $tag, $list ) ) {

                        dbg("e20rTracker::load_frontend_scripts() - Adding {$tag} to list of scripts to print/enqueue");
                        $list[] = $tag;
                    }
                }
            }

            if ( ( !empty( $script) ) && ( !empty($id) ) ) {

                dbg("e20rTracker::load_frontendscripts() - localizing tag ({$script}) with name {$id}");
                global $e20rClient;

                wp_localize_script( $script, $id,
                    array(
                        'timeout' => 30000,
                        'longpoll_timeout' => apply_filters('e20r-tracker-longpoll-timeout', 300000),
                        'coach_message_nonce' => wp_create_nonce('e20r-coach-message'),
                        'ticks_to_skip' => apply_filters('e20r-tracker-heartbeat-skip-count', 5),
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'interview_complete' => $e20rClient->completeInterview( $current_user->ID ),
                        'clientId' => $current_user->ID,
                        'is_profile_page' => has_shortcode( $post->post_content, 'e20r_profile' ),
                        'activity_url' => get_permalink( $currentProgram->activity_page_id ),
                        'login_url' => wp_login_url( get_permalink( $currentProgram->dashboard_page_id ) ),
                    )
                );
            }

            dbg("e20rTracker::load_frontend_scripts() - For the script(s) -- wp_print_scripts( " . print_r( $list, true) . " )");
            wp_enqueue_script( $list );


            foreach( $css as $tag => $url ) {

                $css_list[] = $tag;

                if ( !is_null( $url ) ) {

                    dbg("e20rTracker::load_frontend_scripts() - Adding {$tag} CSS");
                    wp_enqueue_style( $tag, $url, false, E20R_VERSION );
                }
                else {

                    wp_enqueue_style( $tag );
                }
            }

/*
            $depKey = array_search( 'dependencies', $list );

            if ( $depKey ) {

                dbg("e20rTracker::load_frontend_scripts() - Removing the dummy entry 'dependencies' from the list of scripts to enqueue/print");
                unset( $list[$depKey] );
            }
*/
        }

        $e20r_plot_jscript = $load_jq_plot;

        $this->register_plotSW();
        dbg("e20rTracker::load_frontend_scripts() - Loading CSS for front-end");

        $this->enqueue_plotSW();
        $e20r_plot_jscript = false;

        // Extract the javascripts scripts to load/print for the short code.
        // $list = array_keys( $list );
        // dbg("e20rTracker::load_frontend_scripts() - wp_print_scripts( " . print_r( $list, true) . " )");
        // wp_print_scripts( $list );
    }

   /* Prepare graphing scripts */
    public function register_plotSW( $hook = null ) {

        global $e20r_plot_jscript;
        global $post;
	    global $e20rAdminPage;
	    global $e20rClientInfoPage;

        if ( $e20r_plot_jscript || ( !is_null($hook) && $hook == $e20rClientInfoPage ) || ( !is_null($hook) && $hook == $e20rAdminPage ) || has_shortcode( $post->post_content, 'user_progress_info' ) ) {

            dbg( "e20rTracker::register_plotSW() - Plotting javascript being registered." );

            wp_deregister_style( 'jqplot' );
            wp_enqueue_style( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.css', false, E20R_VERSION );

            wp_deregister_script( 'jqplot' );
            wp_register_script( 'jqplot', E20R_PLUGINS_URL . '/js/jQPlot/core/jquery.jqplot.min.js', array( 'jquery' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_export' );
            wp_register_script( 'jqplot_export', E20R_PLUGINS_URL . '/js/jQPlot/plugins/export/exportImg.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_pie' );
            wp_register_script( 'jqplot_pie', E20R_PLUGINS_URL . '/js/jQPlot/plugins/pie/jqplot.pieRenderer.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_text' );
            wp_register_script( 'jqplot_text', E20R_PLUGINS_URL . '/js/jQPlot/plugins/text/jqplot.canvasTextRenderer.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_mobile' );
            wp_register_script( 'jqplot_mobile', E20R_PLUGINS_URL . '/js/jQPlot/plugins/mobile/jqplot.mobile.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_date' );
            wp_register_script( 'jqplot_date', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.dateAxisRenderer.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_label' );
            wp_register_script( 'jqplot_label', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisLabelRenderer.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_pntlabel' );
            wp_register_script( 'jqplot_pntlabel', E20R_PLUGINS_URL . '/js/jQPlot/plugins/points/jqplot.pointLabels.min.js', array( 'jqplot' ), E20R_VERSION );

            wp_deregister_script( 'jqplot_ticks' );
            wp_register_script( 'jqplot_ticks', E20R_PLUGINS_URL . '/js/jQPlot/plugins/axis/jqplot.canvasAxisTickRenderer.min.js', array( 'jqplot' ), E20R_VERSION );
        }
    }

        /**
     * Load graphing scripts (if needed)
     */
    private function enqueue_plotSW( $hook = null ) {

        global $e20r_plot_jscript, $post;
	    global $e20rAdminPage;
	    global $e20rClientInfoPage;

        if ( $e20r_plot_jscript || $hook == $e20rClientInfoPage || $hook == $e20rAdminPage || has_shortcode( $post->post_content, 'progress_overview' ) ) {

            dbg("e20rTracker::enqueue_plotSW() -- Loading javascript for graph generation");

            wp_print_scripts( array(
                'jqplot', 'jqplot_export', 'jqplot_pie', 'jqplot_text', 'jqplot_mobile', 'jqplot_date', 'jqplot_label', 'jqplot_pntlabel', 'jqplot_ticks'
                )
            );

        }
    }

	public function loadOption( $optionName ) {

		$value = false;

        // dbg("e20rTracker::loadOption() - Looking for option with name: {$optionName}");
        $options = get_option( $this->setting_name );

        if ( empty( $options ) ) {

            // dbg("e20rTracker::loadOption() - No options defined at all!");
            return false;
        }

        if ( empty( $options[$optionName] ) ) {
            // dbg("e20rTracker::loadOption() - Option {$optionName} exists but contains no data!");
            return false;
        }
        else {
            // dbg("e20rTracker::loadOption() - Option {$optionName} exists...");

            if ( 'e20r_interview_page' == $optionName ) {
                return E20R_COACHING_URL . "/welcome-questionnaire/";
            }

            return $options[$optionName];
        }


		return false;
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

            $permitted = ( $permitted || $perm );
        }


        if ( $permitted ) {
            dbg( "e20rTracker::userCanEdit() - User id ({$user_id}) has permission" );
        }
        else {
            dbg( "e20rTracker::userCanEdit() - User id ({$user_id}) does NOT have permission" );
        }
        return $permitted;
    }

    public function manage_tables() {

        global $wpdb;
        global $e20r_db_version;

        $current_db_version = $this->loadOption( 'e20r_db_version' );

        if ( $current_db_version == E20R_DB_VERSION ) {

            dbg("e20rTracker::manage_tables() - No change in DB structure. Continuing...");
            return;
        }

        $e20r_db_version = $current_db_version;

        $charset_collate = '';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        dbg("e20rTracker::manage_tables() - Loading table SQL...");

        $message_history = "
            CREATE TABLE {$wpdb->prefix}e20r_client_messages (
                id int not null auto_increment,
				user_id int not null,
				program_id int not null,
				sender_id int not null,
				topic varchar(255) null,
				message text null,
                sent datetime null,
				transmitted timestamp not null default current_timestamp,
                primary key (id) ,
                index cm_user_program ( user_id, program_id ) )
                {$charset_collate}
        ";
        $surveyTable = "
            CREATE TABLE {$wpdb->prefix}e20r_surveys (
                id int not null auto_increment,
				user_id int not null,
				program_id int not null,
				article_id int not null,
				survey_type int null,
				survey_data text null,
				is_encrypted tinyint null,
				recorded timestamp not null default current_timestamp,
				completed datetime null,
				for_date datetime null,
                primary key (id) ,
                index user_program ( user_id, program_id ),
                index program_article ( program_id, article_id ),
                index dated ( for_date ) )
                {$charset_collate}
        ";

        $activityTable = "
            CREATE TABLE {$wpdb->prefix}e20r_workout (
                id int not null auto_increment,
				recorded timestamp not null default current_timestamp,
				for_date datetime null,
				user_id int not null,
				program_id int not null,
				activity_id int not null,
				exercise_id int not null,
				exercise_key tinyint not null,
				group_no int not null,
				set_no int not null,
                reps int null,
				weight decimal(5,2) null,
                primary key (id) )
                {$charset_collate}
        ";

        /**
         * Intake interview / exit interview data.
         */
        $intakeTableSql =
            "CREATE TABLE {$wpdb->prefix}e20r_client_info (
                    id int not null auto_increment,
                    user_id int not null,
                    started_date timestamp default current_timestamp,
                    edited_date timestamp null,
                    completed_date timestamp null,
                    program_id int not null,
                    article_id int not null,
                    page_id int not null,
                    program_start date not null,
                    progress_photo_dir varchar(255) not null default 'e20r-pics/',
                    user_enc_key varchar(512) not null,
                    first_name varchar(20) null,
                    last_name varchar(50) null,
                    gender varchar(2) null,
                    email varchar(255) null,
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
                    exercise_hours_per_week varchar(4) not null default '0',
                    regular_exercise_type text null,
                    other_exercise text null,
                    exercise_plan tinyint not null default 0,
                    exercise_level varchar(255) default 'not-applicable',
                    competitive_sports tinyint null,
                    competitive_survey text null,
                    enjoyable_activities text null,
                    exercise_challenge text null,
                    chronic_pain tinyint not null default 0,
                    pain_symptoms text null,
                    limiting_injuries tinyint not null default 0,
                    injury_summary varchar(512) not null default 'none',
                    other_injuries text null,
                    injury_details text null,
                    nutritional_challenge text null,
                    buy_groceries varchar(50) null,
                    groceries_who varchar(255) null,
                    cooking varchar(50) null,
                    cooking_who varchar(255) null,
                    eats_with varchar(100) null,
                    meals_at_home varchar(20) null,
                    meals_not_home varchar(20) null,
                    following_diet tinyint null default 0,
                    diet_summary varchar(512) null default 'none',
                    other_diet varchar(255) null,
                    diet_duration varchar(255) null,
                    food_allergies tinyint null default 0,
                    food_allergy_summary text null,
                    food_allergy_other varchar(255) null,
                    food_sensitivity tinyint null default 0,
                    sensitivity_summary varchar(512) null,
                    sensitivity_other varchar(255) null,
                    supplements tinyint null default 0,
                    supplement_summary varchar(512) null default 'none',
                    other_vitamins varchar(255) null,
                    supplements_other varchar(255) null,
                    daily_water_servings varchar(15) null,
                    daily_protein_servings varchar(15) null,
                    daily_vegetable_servings varchar(15) null,
                    nutritional_knowledge smallint null default 0,
                    diagnosed_medical_problems tinyint null default 0,
                    medical_issues text null,
                    on_prescriptions tinyint null default 0,
                    prescription_summary text,
                    other_treatments tinyint null default 0,
                    treatment_summary text null,
                    working tinyint null default 0,
                    work_type varchar(150) null,
                    working_when varchar(10) null,
                    typical_hours_worked varchar(15) null,
                    work_activity_level varchar(12) null,
                    work_stress varchar(9) null,
                    work_travel varchar(11) null,
                    student tinyint null default 0,
                    school varchar(150) null,
                    school_stress varchar(9) null,
                    caregiver tinyint null default 0,
                    caregiver_for varchar(255) null,
                    caregiver_stress varchar(9) null,
                    committed_relationship tinyint null default 0,
                    partner varchar(50) null,
                    children tinyint null default 0,
                    children_count smallint null default 0,
                    child_name_age varchar(512) null,
                    pets tinyint null default 0,
                    pet_count int null,
                    pet_names_types varchar(255) null,
                    home_stress varchar(255) null,
                    stress_coping varchar(512) null,
                    vacations varchar(11) null,
                    hobbies text null,
                    alcohol varchar(15) null,
                    smoking varchar(10) null,
                    non_prescription_drugs varchar(10) null,
                    program_expectations text null,
                    coach_expectations text null,
                    more_info text null,
                    photo_consent tinyint not null default 0,
                    research_consent tinyint not null default 0,
                    medical_release tinyint not null default 0,
                    primary key  (id),
                    index user_id  (user_id),
                    index programstart  (program_start)
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
                    index user_id ( user_id ) )
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
                        index program_id ( program_id ),
                        index checkin_short_name ( checkin_short_name ) )
                {$charset_collate}";


        /**
         * For assignments
         * Uses the post->ID of the e20r_assignments CPT for it's unique ID.
         */
        $assignmentAsSql =
            "CREATE TABLE {$wpdb->prefix}e20r_assignments (
                    id int not null auto_increment,
                    article_id int not null,
                    program_id int not null,
                    question_id int not null,
                    delay int not null,
                    user_id int not null,
                    answer_date datetime null,
                    answer text null,
                    response_id int null,
                    field_type enum( 'button', 'input', 'textbox', 'checkbox', 'multichoice', 'rank', 'yesno' ),
                    primary key  (id),
                     index articles (article_id ),
                     index questions (question_id ),
                     index user_id ( user_id )
                     )
                    {$charset_collate}
        ";

        $response_table =
            "CREATE TABLE {$wpdb->prefix}e20r_response (
                    id int not null auto_increment,
                    assignment_id int not null,
                    article_id int null,
                    program_id int null,
                    client_id int not null,
                    recipient_id int not null,
                    sent_by_id int not null,
                    replied_to int null,
                    archived tinyint not null default 0,
                    message_read tinyint not null default 0,
                    message_time timestamp not null default current_timestamp,
                    message text null,
                    primary key  (id),
                     index articles (article_id ),
                     index program_id (program_id ),
                     index recipient_id ( recipient_id ),
                     index client_id ( client_id )
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

        dbg('e20rTracker::manage_tables() - Loading/updating tables in database...');
        $result = dbDelta( $checkinSql );
        dbg("e20rTracker::manage_tables() - Check-in table: ");
        dbg($result);
        $result = dbDelta( $measurementTableSql );
        dbg("e20rTracker::manage_tables() - Measurements table: ");
        dbg($result);
        $result = dbDelta( $intakeTableSql );
        dbg("e20rTracker::manage_tables() - Client Information table:");
        dbg($result);
        $result = dbDelta( $assignmentAsSql );
        dbg("e20rTracker::manage_tables() - Assignments table:");
        dbg($result);
        $result = dbDelta( $activityTable );
        dbg("e20rTracker::manage_tables() - Activity table:");
        dbg($result);
        $result = dbDelta( $surveyTable );
        dbg("e20rTracker::manage_tables() - Survey table:");
        dbg($result);
        $result = dbDelta( $message_history );
        dbg("e20rTracker::manage_tables() - Message history table:");
        dbg($result);
        $result = dbDelta( $response_table );
        dbg("e20rTracker::manage_tables() - Coach/Client response table:");
        dbg($result);
        // dbg("e20rTracker::manage_tables() - Adding triggers in database");
        // mysqli_multi_query($wpdb->dbh, $girthTriggerSql );

        // IMPORTANT: Always do this in the e20r_update_db_to_*() function!
        if ( $e20r_db_version != E20R_DB_VERSION ) {
            $this->updateSetting( 'e20r_db_version', E20R_DB_VERSION );
        }
        //
    }

    public function update_db() {

        if ( ( $db_ver = (int)$this->loadOption('e20r_db_version' ) ) < E20R_DB_VERSION ) {

            $path = E20R_PLUGIN_DIR . '/e20r_db_update.php';

            if ( file_exists( $path ) ) {

                dbg("e20rTracker::update_db() - Loading: $path ");
                require( $path );
                dbg("e20rTracker::update_db() - DB Upgrade functions loaded");
            }
            else {
                dbg("e20rTracker::update_db() - ERROR: Can't load DB update script!");
                return;
             }


            $diff = ( E20R_DB_VERSION - $db_ver );
            dbg("e20rTracker::update_db() - We've got {$diff} versions to upgrade... {$db_ver} to " . E20R_DB_VERSION );

            for ( $i = ($db_ver + 1) ; $i <= E20R_DB_VERSION ; $i++ ) {

                $version = $i;
                dbg("e20rTracker::update_db() - Process upgrade function for Version: {$version}");

                if ( function_exists("e20r_update_db_to_{$version}" ) ) {

                    dbg("e20rTracker::update_db() - Function to update version to {$version} is present. Executing...");
                    call_user_func( "e20r_update_db_to_{$version}", array( $version ) );
                }
                else {
                    dbg("e20rTracker::update_db() - No version specific update function for database version: {$version} ");
                }
            }
        }
    }

    public function activate() {

        global $e20r_db_version;

        // Set the requested DB version.
        // $e20r_db_version = E20R_DB_VERSION;

        $e20r_db_version = $this->loadOption( 'e20r_db_version' );
/**
        * if ( empty( $e20r_db_version ) ) {
 *
* update_option( $this->setting_name, $this->settings );
            * $e20r_db_version = $this->loadOption( 'e20r_db_version' );
        * }
**/
        if ( $e20r_db_version < E20R_DB_VERSION ) {
            $this->manage_tables();
        }

        $this->updateSetting( 'e20r_db_version', $e20r_db_version );

        dbg("e20rTracker::activate() - Should we attempt to unserialize the plugin settings?");
        $errors = '';

        if ( 0 != E20R_RUN_UNSERIALIZE ) {

            dbg("e20rTracker::activate() - Attempting to unserialize the plugin program id and article id settings");

            $what = $this->getCPTInfo();

            foreach( $what as $cpt_type => $options ) {

                $this->updateSetting( "converted_metadata_{$cpt_type}", 0 );

                foreach( $options->keylist as $from => $to ) {

                    $success = $this->unserialize_settings( $cpt_type, $from, $to );

                    // Check to see if post meta was updated and store appropriate admin notice	in options table
                    if ( -1 === $success )
                    {
                        $message = '<div class="error">';
                            $message .= '<p>';
                                $message .= __( "Error: no specified ({$from}/{$to}) postmeta {$from}/{$to} for {$cpt_type} was found. Deactivate the plugin, specify different meta keys, then activate the plugin to try again." );
                            $message .= '</p>';
                        $message .= '</div><!-- /.error -->';
                    }
                    elseif ( empty( $success ) )
                    {
                        $message = '<div class="updated">';
                            $message .= '<p>';
                                $message .= __( "All specified postmeta ({$from}/{$to}) for {$cpt_type} was unserialized and saved back to the database." );
                            $message .= '</p>';
                        $message .= '</div><!-- /.updated -->';

                        $this->updateSetting( "converted_metadata_{$cpt_type}", 1 );
                    }
                    else
                    {
                        $message = '<div class="error">';
                            $message .= '<p>';
                                $message .= __( "Error: not all postmeta {$from}/{$to} for {$cpt_type} was unserialized" );
                            $message .= '</p>';
                        $message .= '</div><!-- /.error -->';

                        dbg("e20rTracker::activate() - Error while unserializing:");
                        dbg($success);
                    }

                    $errors .= $message;
                }
            }

            $this->updateSetting( 'unserialize_notice', $errors );

            global $e20rAssignment;
            dbg("e20rTracker::activate() -- Updating assignment programs key to program_ids key. ");
            // $e20rAssignment->update_metadata();
        }

        $user_ids = get_users(
                array(
	                'blog_id' => '',
                    'fields'  => 'ID',
                )
        );

        foreach ( $user_ids as $user_id ) {

            if ( false === get_user_meta( $user_id, '_e20r-tracker-last-login', true ) ) {
                update_user_meta( $user_id, '_e20r-tracker-last-login', 0 );
            }
        }

        if (false === $this->define_e20rtracker_roles() )
        {
            dbg("ERROR: Unable to define the required roles for this plugin");
        }

        flush_rewrite_rules();
    }

    public function deactivate() {

        global $wpdb;
        global $e20r_db_version;

        $options = get_option( $this->setting_name );

        dbg("e20rTracker::deactivate() - Current options: " . print_r( $options, true ) );

        $tables = array(
            $wpdb->prefix . 'e20r_checkin',
            $wpdb->prefix . 'e20r_assignments',
            $wpdb->prefix . 'e20r_measurements',
            $wpdb->prefix . 'e20r_client_info',
            $wpdb->prefix . 'e20r_workouts',
            $wpdb->prefix . 'e20r_articles',
//            $wpdb->prefix . 'e20r_programs',
//            $wpdb->prefix . 'e20r_sets',

        );


        foreach ( $tables as $tblName ) {

            if ( $options['purge_tables'] == 1 ) {

                dbg("e20rTracker::deactivate() - Truncating {$tblName}" );
                $sql = "TRUNCATE TABLE {$tblName}";
                $wpdb->query( $sql );
            }

            if ( $options['delete_tables'] == 1 ) {

                dbg( "e20rTracker::deactivate() - {$tblName} being dropped" );

                $sql = "DROP TABLE IF EXISTS {$tblName}";
                $wpdb->query( $sql );
            }

        }

        $wpdb->query("DROP TRIGGER IF EXISTS {$wpdb->prefix}e20r_update_girth_total");

        // $this->unserialize_deactivate();

        // Remove existing options
        // delete_option( $this->setting_name );

        $this->remove_old_files();
    }

    public function e20r_program_taxonomy() {

        register_taxonomy(
            'programs',
            'e20r_program',
            array(
                'label' => __( 'Programs', 'e20rtracker' ),
                'rewrite' => array( 'slug' => 'programs' ),
                'public' => false,
                'show_tagcloud' => false,
                'show_in_quick_edit' => false,
                'hierarchical' => true,
                'capabilities' => array(
                    'assign_terms' => 'edit_posts',
                    'edit_terms' => 'manage_categories'
                )
            )
        );
    }

    public function e20r_tracker_assignmentsCPT() {

        $labels =  array(
            'name' => __( 'Assignments', 'e20rtracker'  ),
            'singular_name' => __( 'Assignment', 'e20rtracker' ),
            'slug' => 'e20r_assignments',
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
            array( 'labels' => apply_filters( 'e20r-tracker-assignments-cpt-labels', $labels ),
                   'public' => false,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'menu_icon' => '',
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title', 'excerpt'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-articles',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-assignments-cpt-slug', 'tracker-assignments'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-assignments-cpt-archive-slug', 'tracker-assignments-archive')
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
                   'menu_icon' => '',
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title', 'excerpt', 'custom-fields', 'page-attributes'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-programs',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-program-cpt-slug', 'tracker-programs'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-program-cpt-archive-slug', 'tracker-program-archive')
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
                   'menu_icon' => '',
                   // 'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title', 'excerpt', 'editor' ),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-articles',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-article-cpt-slug', 'tracker-articles'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-article-cpt-archive-slug', 'tracker-articles-archive')
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
                   'menu_icon' => '',
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','editor','excerpt','thumbnail','custom-fields','author'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-info',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-girth-cpt-slug', 'girth'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-girth-cpt-archive-slug', 'girths-archive')
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
                   'menu_icon' => '',
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','editor','excerpt','thumbnail', 'page-attributes'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-activities',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-exercise-cpt-slug', 'tracker-exercise'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-exercise-cpt-archive-slug', 'tracker-exercises-archive')
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
                   'menu_icon' => '',
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','editor','thumbnail'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-articles',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-workout-cpt-slug', 'tracker-activity'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-workout-cpt-archive-slug', 'tracker-activity-archive')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_workout CPT: ' . $error->get_error_message);
        }
    }

	public function e20r_tracker_actionCPT() {

        $this->rename_action_cpt();

        $labels =  array(
            'name' => __( 'Actions', 'e20rtracker'  ),
            'singular_name' => __( 'Action', 'e20rtracker' ),
            'slug' => 'e20r_actions',
            'add_new' => __( 'New Action', 'e20rtracker' ),
            'add_new_item' => __( 'New Action', 'e20rtracker' ),
            'edit' => __( 'Edit Action', 'e20rtracker' ),
            'edit_item' => __( 'Edit Action', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Action', 'e20rtracker' ),
            'view_item' => __( 'View This Actions', 'e20rtracker' ),
            'search_items' => __( 'Search Actions', 'e20rtracker' ),
            'not_found' => __( 'No Actions Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Actions Found In Trash', 'e20rtracker' )
        );

        $error = register_post_type('e20r_actions',
            array( 'labels' => apply_filters( 'e20r-tracker-action-cpt-labels', $labels ),
                   'public' => true,
                   'show_ui' => true,
                   'show_in_menu' => true,
                   'menu_icon' => '',
                   'publicly_queryable' => true,
                   'hierarchical' => true,
                   'supports' => array('title','excerpt','thumbnail'),
                   'can_export' => true,
                   'show_in_nav_menus' => false,
                   'show_in_menu' => 'e20r-tracker-articles',
                   'rewrite' => array(
                       'slug' => apply_filters('e20r-tracker-action-cpt-slug', 'tracker-action'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-action-cpt-archive-slug', 'tracker-action-archive')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_actions CPT: ' . $error->get_error_message);
        }
    }

    /**
     * Configure & display the icon for the Tracker (in the Dashboard)
     */
    function post_type_icon() {
        ?>
        <style>
/*          @font-face {
                font-family: FontAwesome;
                src: url(https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.min.css);
            }
*/
            #adminmenu .menu-top.toplevel_page_e20r-tracker-activities div.wp-menu-image:before {
                font-family:  FontAwesome !important;
                content: '\f1e3';
            }

            #adminmenu .menu-top.toplevel_page_e20r-tracker-articles div.wp-menu-image:before {
                font-family:  FontAwesome !important;
                content: '\f1ea';
            }

            #adminmenu .menu-top.toplevel_page_e20r-tracker-programs div.wp-menu-image:before {
                font-family:  FontAwesome !important;
                content: '\f278';
            }

            #adminmenu .menu-top.toplevel_page_e20r-tracker-info div.wp-menu-image:before {
                font-family:  FontAwesome !important;
                content: '\f1b0';
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

        dbg("e20rTracker::getUserList() - Called by: " . $this->whoCalledMe() );
        $levels = array_keys( $this->getMembershipLevels( $level, false ) );

        dbg("e20rTracker::getUserList() - Users being loaded for the following level(s): " . print_r( $levels, true ) );

        return $this->model->loadUsers( $levels );
    }

    public function getLevelIdForUser( $userId = null ) {

        global $e20rProgram;

        $level_id = null;

        if ( is_null( $userId ) ) {

            global $current_user;
            $userId = $current_user->ID;
        }

        $program_id = $e20rProgram->getProgramIdForUser( $userId );

        return $program_id;
    }

	public function getGroupIdForUser( $userId = null ) {

		$group_id = null;

		dbg("e20rTracker::getGroupIdForUser() - Get membership/group data for {$userId}");

		if ( is_null( $userId ) ) {

			global $current_user;
			$userId = $current_user->ID;
		}

		$user = new WP_User($userId);

		dbg("e20rTracker::getGroupIdForUser() - User object: " . print_r( $user, true));

		foreach( (array) $user->roles as $role ) {

			if ( false !== strpos( $role, 'e20r_tracker_exp') ) {
				dbg("e20rTracker::getGroupIdForUser() - Returning the tracker role/group for {$userId}: {$role}");
				$group_id = $role;
			}
		}

		if (empty($group_id)) {

			$roles = apply_filters('e20r-tracker-configured-roles', array() );
			$group_id = $roles['beginner']['role'];

			dbg("e20rTracker::getGroupIdForUser() - Assigning default group for the current user ID ({$userId}): {$group_id}");
		}
/*
		if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {

			dbg("e20rTracker::getGroupIdForUser() - Using Paid Memberships Pro for group/level management for {$userId}");
			$obj = pmpro_getMembershipLevelForUser( $userId );

			$group_id = isset( $obj->ID ) ? $obj->ID : 0;

			dbg("e20rTracker::getGroupIdForUser() - Returning group ID of {$group_id} for {$userId}");
		}
*/
		return $group_id;
	}

    public function getMembershipLevels( $level = null, $onlyVisible = false ) {

        if ( function_exists( 'pmpro_getAllLevels' ) ) {

	        $name = null;

            if ( is_numeric( $level ) ) {

                dbg("e20rTracker::getLevelList() - Requested ID: {$level}");
                $tmp = pmpro_getLevel( $level );
                $name = $tmp->name;
                dbg("e20rTracker::getLevelList() - Level Name: {$name}");
            }

            $allLevels = pmpro_getAllLevels( $onlyVisible, true );
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

        if ( $userId == 0 ) {
            return false;
        }

        if ( function_exists('pmpro_hasMembershipLevel' ) ) {

            $ud = get_user_by( 'id', $userId );

            $notFreeMember = (! pmpro_hasMembershipLevel( 13, $userId ) );
            $notDummyUser = (! $ud->has_cap('app_dmy_users') );

	        return ( $notFreeMember && $notDummyUser );
        }

        return false;
    }

	public function isEmpty( $obj ) {

		if ( ! is_object( $obj ) ) {
            dbg('e20rTracker::isEmpty() - Type is an array and the array contains data? ' . (empty( $obj ) === true ? 'No' : 'Yes'));
            // dbg($obj);
			return empty( $obj );
		}

		$o = (array)$obj;
        dbg('e20rTracker::isEmpty() - Type is an object but does it contain data?: ' . ( empty($o) === true ? 'No' : 'Yes') );
        // dbg( $o );
		return empty( $o );
	}

	public function validateDate($date)
	{
		$d = DateTime::createFromFormat('Y-m-d', $date);
		return $d && ( $d->format('Y-m-d') == $date );
	}

    public function getDelay( $delayVal = 'now', $userId = null ) {

        global $current_user;
        global $currentProgram;

        // We've been given a numeric value so assuming it's the delay.
        if ( is_numeric( $delayVal ) ) {

            dbg("e20rTracker::getDelay() - Numeric delay value specified. Returning: {$delayVal}");
            return $delayVal;
        }

        if ( false === $startDate = strtotime( $currentProgram->startdate ) ) {
            dbg("Unable to configure startdate for currentProgram (" . !empty($currentProgram->id) ? $currentProgram->id : 'None' . ")");
            return false;
        }

	    dbg("e20rTracker::getDelay() - Based on startdate of {$currentProgram->startdate}...");

	    if ( $this->validateDate( $delayVal ) ) {

            dbg("e20rTracker::getDelay() - {$delayVal} is a date.");
		    $delay = $this->daysBetween( $startDate, strtotime( $delayVal ), get_option('timezone_string') );

		    dbg("e20rTracker::getDelay() - Given a date {$delayVal} and returning {$delay} days since {$currentProgram->startdate}");
		    return $delay;
	    }


        if ( $delayVal == 'now' ) {
            dbg("e20rTracker::getDelay() - Calculating delay since startdate (given 'now')...");

            // Calculate the user's current "days in program".
            if ( is_null( $userId ) ) {

	            dbg("e20rTracker::getDelay() - Using current_user->ID for userid: {$current_user->ID}");
                $userId = $current_user->ID;
            }

            $delay = $this->daysBetween( $startDate, current_time("timestamp") );

            // $delay = ($delay == 0 ? 1 : $delay);

            dbg("e20rTracker::getDelay() - Days since startdate is: {$delay}...");

            return $delay;
        }

        return false;
    }

    public function getDateFromDelay( $rDelay = "now", $userId = null ) {

        global $current_user;
        global $e20rProgram;
        global $currentProgram;

        if ( is_null( $userId ) ) {
            $userId = $current_user->ID;
        }

        dbg("e20rTracker::getDateFromDelay() - Received Delay value of {$rDelay} from calling function: " . $this->whoCalledMe() );
        $startTS = $e20rProgram->startdate( $userId );

        if ( 0 == $rDelay ) {

            $delay = 0;
        }
        elseif ( "now" == $rDelay ) {

            dbg("e20rTracker::getDateFromDelay() - Calculating 'now' based on current time and startdate for the user. Got delay value of {$rDelay}");
            $delay = $this->daysBetween( $startTS, current_time('timestamp') );
        }
        else {

            $delay = ($rDelay);
            dbg("e20rTracker::getDateFromDelay() - Adjusting delay value: {$rDelay} => {$delay}");
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

        if ( $delay < 0 ) {
            dbg("e20rTracker::getDateFromDelay( {$delay} ) -> Returning 'startdate' as the correct date.");
            $rDate = $sDate;
        }

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
            return ( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) );
        }

        if ( empty( $days ) || ( $days == 'now' ) ) {
            dbg("e20rTracker::getDateForPost() - Calculating 'now' based on current time and startdate for the user");
            $days = $this->daysBetween( $startDateTS, current_time('timestamp') );
        }

        $startDate = date_i18n( 'Y-m-d', $startDateTS );
        dbg("e20rTracker::getDateForPost( {$days} ) -> Startdate found for user with ID of {$userId}: {$startDate} and days: {$days}");

        $releaseDate = date_i18n( 'Y-m-d', strtotime( "{$startDate} +" . ($days -1 ) ." days") );

        dbg("e20rTracker::getDateForPost( {$days} ) -> Calculated date for delay of {$days}: {$releaseDate}");
        return $releaseDate;
    }

    public function getDripFeedDelay( $postId ) {

        $dripfeed_exists = false;

        if ( class_exists( 'PMProSequence') ) {
            $dripfeed_exists = true;
        }

        if ( class_exists( "E20R\\Sequences\\Sequence\\Controller") ) {
            $dripfeed_exists = true;
        }

        if (true === $dripfeed_exists ) {

            dbg("e20rArticle::getDripFeedDelay() - Found the PMPro Sequence Drip Feed plugin");

/*
            if ( false === ( $sequenceIds = get_post_meta( $postId, '_post_sequences', true ) ) ) {
                return array();
            }
*/
            if ( class_exists('PMProSequence')) {
                $sequenceIds = PMProSequence::sequences_for_post( $postId );
            }

            if ( class_exists("E20R\\Sequences\\Sequence\\Controller")) {
                $sequenceIds = Sequence\Controller::sequences_for_post( $postId );
            }

            foreach ($sequenceIds as $id ) {

                if ( class_exists('PMProSequence')) {
                    $details = PMProSequence::post_details( $id, $postId );
                }

                if ( class_exists("E20R\\Sequences\\Sequence\\Controller")) {
                    $details = Sequence\Controller::post_details( $id, $postId );
                }

/*
                $seq->get_options( $id );
                $details = $seq->get_post_details( $postId );

                unset($seq);
*/
                dbg("e20rArticle::getDripFeedDelay() - Delay details: " . print_r( $details, true  ) );

                if ( $id != false ) {

                    dbg("e20rArticle::getDripFeedDelay() - Returning {$details[0]->delay}");
                    return $details[0]->delay;
                }
            }
        }

        return false;
    }

    public function setFormatForRecord( $record ) {

        $format = array();

        foreach( $record as $key => $val ) {

	        if ( stripos( $key, 'zip' ) ) {

		        dbg("e20rTracker::setFormatForRecord() - Field contains Zip...");

		        $varFormat = '%s';
	        }
	        else {

		        $varFormat = $this->setFormat( $val );
	        }

            if ( $varFormat !== false ) {

                $format = array_merge( $format, array( $varFormat ) );
            }
            else {

                dbg("e20rTracker::setFormatForRecord() - Invalid data type for {$key}/{$val} pair");
                throw new Exception( "The value submitted for persistant storage as {$key} is of an invalid/unknown type" );
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

	        if ( filter_var( $value, FILTER_VALIDATE_INT ) !== false ) {
		        return '%d';
	        }

            // dbg( "setFormat() - .{$value}. IS numeric" );

            if ( filter_var( $value, FILTER_VALIDATE_FLOAT) !== false ) {
                return '%f';
            }

        }

	    if ( is_bool( $value ) ) {
		    return '%d';
	    }

	    dbg("e20rTracker::setFormat() - Value: {$value} doesn't have a recognized format..? " . gettype($value) );
        return '%s';
    }

    public static function getCurrentPostType() {

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

	    dbg("e20rTracker::daysBetween() - StartTS: {$startTS}, endTS: {$endTS} " );

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

        // Correct the $days value because include the "to" day.
        $days = $days + 1;
        dbg("e20rTracker::daysBetween() - Returning: {$days}");
        return $days;
    }

    public function define_e20rtracker_roles() {

        global $wp_roles;

        $roles_set = $this->loadOption('roles_are_set');      
        $roles = $this->add_default_roles(array());

        dbg("e20rTracker::define_e20rtracker_roles() - Processing " . count($roles) . " roles:");
        foreach ( $roles as $key => $user_role ) {

            foreach ( $wp_roles->get_names() as $role_name => $display_name) {

                if ( $role_name === $user_role['role'] ) {
                    dbg("e20rTracker::define_e20rtracker_roles() - Removing pre-existing role definition: {$role_name}");
                    $wp_roles->remove_role($role_name);
                }
            }

            dbg("e20rTracker::define_e20rtracker_roles() - Adding role definition for {$user_role['role']} => {$user_role['label']}");
            if (! $wp_roles->add_role( $user_role['role'], $user_role['label'], $user_role['permissions'] ) ) {
                dbg("e20rTracker::define_e20rtracker_roles() - Error adding {$key} -> '{$user_role['role']}' role!");
                return false;
            }
        }

        $this->updateSetting('roles_are_set',true);

        $admins = get_users( array( 'role' => 'administrator' ) );

        foreach( $admins as $admin ) {

            if ( !in_array( $roles['coach']['role'], (array) $admin->roles ) ) {
                dbg("e20rTracker::define_e20rtracker_roles() - User {$admin->ID} is not (yet) defined as a coach, but is an admin!");
                $admin->add_role( $roles['coach']['role'] );
                dbg("e20rTracker::define_e20rtracker_roles() - Added 'coach' role to {$admin->ID}");
            }
        }

        return true;
    }
    /**
     * @param $data - The data to sort
     * @param array $fields - An array (2 elements) for the fields to sort by.
     *
     * @return array $data
     */
    public function sortByFields( array $data, array $fields = array() ) {

        uasort( $data, function( $a, $b ) use ($fields) {

            if ($a->{$fields[0]} == $b->{$fields[0]})
            {
                if($a->{$fields[1]} == $b->{$fields[1]}) return 0 ;
                return ($a->{$fields[1]} < $b->{$fields[1]}) ? -1 : 1;
            }
            else
                return ($a->{$fields[0]} < $b->{$fields[0]}) ? -1 : 1;
        });

        return $data;
    }

    /**
     * @param $dayNo -- The day number (1 == Monday)
     * @return string - The day name
     */
    public function displayWeekdayName( $dayNo ) {

        $retVal = '';

        switch ( $dayNo ) {
            case 1:
                // Monday
                $retVal = "Monday";
                break;

            case 2:
                $retVal = "Tuesday";
                break;

            case 3:
                $retVal = "Wednesday";
                break;

            case 4:
                $retVal = "Thursday";
                break;

            case 5:
                $retVal = "Friday";
                break;

            case 6:
                $retVal = "Saturday";
                break;

            case 7:
                $retVal = "Sunday";
                break;
        }

        return $retVal;
    }

    /**
     * Unserialize Post Meta plugin for WordPress
     *
     * Note: only acts upon post meta when plugin is activated
     *
     * @package WordPress
     * @subpackage WPAlchemy
     * @author Grant Kinney
     * @version 0.1
     * @license http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
     *
     */
    public function unserialize_settings( $post_type, $from_key, $to_key ) {

        $run_unserialize = $this->loadOption('run_unserialize');

        if ( 1 != $run_unserialize ) {

            dbg("e20rTracker::unserialize_settings() - Not being asked to convert serialized metadata.");
            return;
        }

        // Get all posts with post meta that have the specified meta key
        $posts_array = get_posts( array(
            'meta_key' => $from_key,
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'nopaging' => true,
            'post_status' => 'any',
            )
        );

        // Loop through posts, extract requested post meta, and send it back to the database in it's own row!
        // Keep a list of updated posts and check that the unserialized postmeta has been stored
        $serialized_post_list = array();
        $unserialized_post_list = array();

        dbg("e20rTracker::unserialize_settings() - Converting from {$from_key} to {$to_key} for type {$post_type}");

        foreach ($posts_array as $serialized_post) {

            $serialized_post_id = $serialized_post->ID;
            $serialized_postmeta = get_post_meta( $serialized_post_id, $from_key, true );

            dbg("e20rTracker::unserialize_settings() - Processing: {$serialized_post->ID} which contains " . gettype($serialized_postmeta));


            if ( "delete" == $to_key ) {

                dbg("e20rTracker::unserialize_settings() - WARNING: Deleting metadata for {$serialized_post->ID}/{$from_key} ");
                delete_post_meta( $serialized_post_id, $from_key );
            }
            else {

                if ( !is_array( $serialized_postmeta ) ) {

                    dbg( "e20rTracker::unserialize_settings() - Value isn't actually serialized: {$serialized_postmeta}");
                    $serialized_postmeta = array( $serialized_postmeta );
                }

                if ( is_array( $serialized_postmeta ) ) {

                    dbg("e20rTracker::unserialize_settings() - serialized postmeta IS an array.");
                    dbg($serialized_postmeta);

                    delete_post_meta( $serialized_post_id, $to_key );

                    foreach ( $serialized_postmeta as $k => $val ) {

                        dbg("e20rTracker::unserialize_settings() - Update {$serialized_post_id} with key: {$from_key} to {$to_key} -> {$val} ");

                        if ( 0 !== $val ) {

                            add_post_meta( $serialized_post_id, $to_key, $val);
                        }
                        else {
                            dbg("e20rTracker::unserialize_settings() - Zero value for {$to_key}. Won't save it");
                            unset( $serialized_postmeta[$k] );
                        }
                    }

                    dbg("e20rTracker::unserialize_settings() - From {$serialized_post_id}/{$from_key}, delete " . gettype( $serialized_postmeta) );

                    $serialized_post_list[] = $serialized_post_id;
                }

                $unserialized_postmeta = get_post_meta( $serialized_post_id, $to_key );

                // dbg("e20rTracker::unserialize_settings() - Because this is a simulation, the following data is wrong... ");
                dbg("e20rTracker::unserialize_settings() - Still processing: {$serialized_post->ID}, but now with key {$to_key} which contains: ");
                dbg($unserialized_postmeta);


                if ( is_array( $serialized_postmeta ) && ( is_array($unserialized_postmeta) ) ) {
                    $cmp = array_diff( $serialized_postmeta, $unserialized_postmeta );
                }
                else {
                    if ( $serialized_postmeta != $unserialized_postmeta ) {
                        $cmp = null;
                    }
                    else {
                        $cmp = true;
                    }
                }

                if ( empty( $cmp )  ) {
                        dbg("e20rTracker::unserialize_settings() - {$serialized_post_id} was unserialized and saved.");
                    $unserialized_post_list[] = $serialized_post_id;

                }
            }
        }

        $post_check = array_diff($serialized_post_list, $unserialized_post_list);

        wp_reset_postdata();

        if ( 0 == count( $posts_array ) ) {
            return -1;
        }
        else {
            return $post_check;
       }
    }

    /**
     * display_admin_notice function.
     * Displays an appropriate notice based on the results of the saved unserialize_notice option.
     * @access public
     * @return void
     */
    public function display_admin_notice() {

        if ( $notice = $this->loadOption('unserialize_notice') ) {

            dbg("e20rTracker::convert_postmeta_notice() - Loading error message");
            echo $notice;
            $this->updateSetting('unserialize_notice', null);
        }
    }

    /**
     * unserialize_deactivate function.
     * Cleanup plugin when deactivated
     * @access public
     * @return void
     */
    public function unserialize_deactivate() {

        delete_option('unserialize_notice');
        return;
    }

    private function getCPTInfo() {

        global $e20rTables;

        $e20rTables = new e20rTables();
        $e20rTables->init();

        $e20rWorkout = new e20rWorkout();
        $e20rAssignment = new e20rAssignment();
        $e20rProgram = new e20rProgram();
        $e20rAction = new e20rAction();
        $e20rArticle = new e20rArticle();

        $cpt_info = array(
			'e20r_workout' => null,
			'e20r_assignments' => null,
            'e20r_articles' => null,
			'e20r_actions' => null,
		);

        foreach( $cpt_info as $cpt => $data ) {

            dbg("e20rTracker::getCPTInfo() - Processing {$cpt}");

            $cpt_info[$cpt] = new stdClass();
            $cpt_info[$cpt]->keylist = array();

            switch ( $cpt ) {
                case 'e20r_workout':

                    $cpt_info[$cpt]->type = $e20rWorkout->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-programs"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    break;

                case 'e20r_articles':

                    $cpt_info[$cpt]->type = $e20rArticle->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-programs"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-assignments"] = "_e20r-{$cpt_info[$cpt]->type}-assignment_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-checkins"] = "_e20r-{$cpt_info[$cpt]->type}-action_ids";
                    // $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-activity_id"] = "_e20r-{$cpt_info[$cpt]->type}-activity_id";
                    break;

                case 'e20r_assignments':

                    $cpt_info[$cpt]->type = $e20rAssignment->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-program_ids"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-article_id"] = "_e20r-{$cpt_info[$cpt]->type}-article_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-program_id"] = "delete";

                    break;

                case 'e20r_actions':
                    $cpt_info[$cpt]->type = $e20rAction->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-program_ids"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    break;
            }

        }

        dbg("e20rTracker::getCPTInfo() - Config is: ");
        dbg($cpt_info);

        return $cpt_info;
    }

    public function rename_action_cpt() {

        $args = array(
            'post_type' => 'e20r_checkins',
            'posts_per_page' => -1,
            'post_status' => 'any'
        );

        $old_posts = get_posts( $args );

        foreach ( $old_posts as $p ) {

            $update = array();
            $update['ID'] = $p->ID;
            $update['post_type'] = 'e20r_actions';

            wp_update_post( $update );
        }

        global $wpdb;

        $find_sql = "SELECT meta_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '_e20r-checkin-%'";

        // $update_sql = "UPDATE {$wpdb->prefix}postmeta SET meta_key = %s WHERE meta_id = %d";

        $results = $wpdb->get_results( $find_sql );

        if ( count( $results ) == 0 ) {
            return;
        }

        dbg("e20rTracker::rename_action_cpt() - Found " . count($results) . " old metadata keys to convert");

        foreach ( $results as $record ) {

            if ( false === $this->replace_metakey( $record, '_e20r-checkin', '_e20r-action' ) ) {
                return;
            }
        }

        $results = null;
        $other_sql = "SELECT meta_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '%checkin_ids%'";
        $results = $wpdb->get_results( $other_sql );

        foreach( $results as $record ) {

            if ( false === $this->replace_metakey( $record, '-checkin_ids', '-action_ids' ) ) {
                return;
            }

        }

    }
    private function replace_metakey( $record, $old, $new ) {

        global $wpdb;

        $old_key = $record->meta_key;
        $new_key = str_replace( $old, $new, $old_key);

        dbg("e20rTracker::replace_metakey() - Changing key {$old_key} to {$new_key} for {$record->meta_id} in {$wpdb->postmeta}");

        $upd = $wpdb->update( "{$wpdb->prefix}postmeta",
            array( 'meta_key' => $new_key ),
            array( 'meta_id' => $record->meta_id ),
            array( '%s' ),
            array( '%d' )
         );

        if ( false !== $upd ) {
            dbg("e20rTracker::replace_metakey() - Changed key for {$record->meta_id} in {$wpdb->prefix}postmeta");
            return true;
        } else {
            dbg("e20rTracker::replace_metakey() - Error for record {$record->meta_id}!!!");
            return false;
        }
    }

    /**
     * Sort the two posts in ascending order
     *
     * @param $a -- Post to compare (including time variable)
     * @param $b -- Post to compare against (including time variable)
     * @return int -- Return -1 if the Delay for post $a is greater than the delay for post $b
     *
     * @access private
     */
    public function sort_descending( $a, $b )
    {
        if ($a->sent == $b->sent)
            return 0;

        // Descending Sort Order
        return ($a->sent > $b->sent) ? -1 : +1;
    }

    public function remove_old_files() {

        $files = array(
            'classes/controllers/e20rCheckin.php',
            'classes/models/e20rCheckinModel.php',
            'classes/views/e20rCheckinView.php',
            'css/e20r-checkin.css',
            'css/e20r-checkin.min.css',
            'js/e20r-checkin-admin.css',
            'js/e20r-checkin-admin.min.css',
            'js/e20r-checkin-items.css',
            'js/e20r-checkin-items.min.css',
            'js/e20r-checkin.css',
            'js/e20r-checkin.min.css',
        );

        foreach( $files as $file ) {

            if ( file_exists( E20R_PLUGIN_DIR . $file ) ) {

                if ( FALSE === unlink( E20R_PLUGIN_DIR . $file ) ) {
                    error_log("E20RTracker - Unable to remove requested file: {$file}");
                }
                else {
                    dbg("e20rTracker::remove_old_files() - Removed: {$file}");
                }
            }
        }
    }
}