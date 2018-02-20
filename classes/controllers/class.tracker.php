<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits,
 * educational reminders, etc.
 *
 * Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 */

namespace E20R\Tracker\Controllers;

use E20R\Sequences\Sequence as Sequence;
use E20R\Tracker\Models\Article_Model;
use E20R\Tracker\Models\GF_Integration;
use E20R\Tracker\Models\Workout_Model;
use E20R\Tracker\Models\Tracker_Model;
use E20R\Tracker\Models\Assignment_Model;
use E20R\Tracker\Models\Action_Model;
use E20R\Tracker\Models\Program_Model;

use E20R\Tracker\Models\Tables;

class Tracker {

    private static $instance = null;
    public $tables;
    public $managed_types = array( 'post', 'page');
    protected $settings = array();
    protected $setting_name = 'e20r-tracker';
    
    private $model;
    private $hooksLoaded = false;

    public function __construct() {

        $this->model = new Tracker_Model();

        // Set defaults (in case there are none saved already
        $this->settings = get_option( $this->setting_name, array(
                            'delete_tables' => false,
                            'purge_tables' => false,
                            // 'measurement_day' => CONST_SATURDAY,
                            // 'lesson_source' => null,
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

        E20R_Tracker::dbg("Tracker::constructor() - Loading the types of posts we'll be allowed to manage");
        $this->managed_types = apply_filters("e20r-tracker-post-types", array("post", "page") );

        //E20R_Tracker::dbg("Tracker::constructor() - Loading Tables class");
        //$this->tables = new Tables();
    }
    
    public static function in_betagroup( $user_id ) {
		
		$user = get_user_by( 'id', $user_id );
		
		if ( ( ! empty( $user->roles ) ) && is_array( $user->roles ) ) {
			if ( in_array( 'nourish-beta', $user->roles ) ) {
				
				E20R_Tracker::dbg( "User {$user->display_name} is in the Nourish Beta group" );
				
				return true;
			}
			
		} else if ( ! empty( $user->roles ) ) {
			if ( $user->roles == 'nourish-beta' ) {
				
				E20R_Tracker::dbg( "User {$user->display_name} is in the Nourish Beta group" );
				
				return true;
			}
		}
		
		// E20R_Tracker::dbg("User {$user->display_name} is NOT in the Nourish Beta group");
		return false;
	}

    /**
	 * @return Tracker|null;
	 */
	static function getInstance() {

    	if ( is_null( self::$instance ) ) {
    		self::$instance = new self;
	    }

	    return self::$instance;
	}

    public function duplicate_cpt_link( $actions, $post ) {

        global $current_user;
        $access = Tracker_Access::getInstance();
        
        E20R_Tracker::dbg("Tracker::duplicate_cpt_link() - Checking whether to add a 'Duplicate' link for the {$post->post_type} post type");

        $managed_types = apply_filters( 'e20r_tracker_duplicate_types', array(
                                                                Program_Model::post_type,
                                                                Workout_Model::post_type,
                                                                 Article_Model::post_type,
                                                                Assignment_Model::post_type,
                                                                Exercise::post_type,
                                                            )
                            );

        if ( in_array( $post->post_type, $managed_types ) &&
                ($access->userCanEdit( $current_user->ID ) ||
                $access->is_a_coach( $current_user->ID )) ) {

            E20R_Tracker::dbg("Tracker::duplicate_cpt_link() - Adding 'Duplicate' action for {$post->post_type} post(s)");
            $actions['duplicate'] = sprintf(
                    '<a href="admin.php?post=%d&amp;action=e20r_duplicate_as_draft" title="%s" rel="permalink">%s</a>',
                    $post->ID,
                    __("Duplicate this item", "e20r-tracker" ),
                    __("Duplicate", "e20r-tracker" )
            );
        }

        return $actions;
    }

    /**
      * Duplicate a E20R Tracker CPT and save the duplicate as 'draft'
      */
    public function duplicate_cpt_as_draft() {

        global $wpdb;

        E20R_Tracker::dbg("Tracker::duplicate_cpt_as_draft() - Requested duplication of a CPT");
        E20R_Tracker::dbg( $_GET['post'] );
        // E20R_Tracker::dbg( $_POST['post'] );
        E20R_Tracker::dbg( $_REQUEST['action'] );

        if ( !isset( $_GET['post'] ) && !isset( $_POST['post'] ) ) {
            E20R_Tracker::dbg("Tracker::duplicate_cpt_as_draft() - One of the expected globals isn't set correctly?");
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
                'menu_order'     => $e20r_post->menu_order,
            );


            E20R_Tracker::dbg("Tracker::duplicate_cpt_as_draft() - Content for new post: ");
            E20R_Tracker::dbg( $new_post );

            $new_id = wp_insert_post( $new_post, true );

            if ( is_wp_error( $new_id ) ) {
                E20R_Tracker::dbg("Tracker::duplicate_cpt_as_draft() - Error: ");
                E20R_Tracker::dbg( $new_id );
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

            E20R_Tracker::dbg( "Tracker::duplicate_cpt_as_draft() - SQL for postmeta: " . $sql );

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

    public function sanitize( $field ) {

        if ( ! is_numeric( $field ) ) {
            // E20R_Tracker::dbg( "setFormat() - {$value} is NOT numeric" );

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

	public function isEmpty( $obj ) {

		if ( ! is_object( $obj ) ) {
            E20R_Tracker::dbg('Tracker::isEmpty() - Type is an array and the array contains data? ' . (empty( $obj ) === true ? 'No' : 'Yes'));
            // E20R_Tracker::dbg($obj);
			return empty( $obj );
		}

		$o = (array)$obj;
        E20R_Tracker::dbg('Tracker::isEmpty() - Type is an object but does it contain data?: ' . ( empty($o) === true ? 'No' : 'Yes') );
        // E20R_Tracker::dbg( $o );
		return empty( $o );
	}
	
	public function loadAllHooks() {

        global $current_user;
        global $pagenow;
        global $post;

        if ( ! $this->hooksLoaded ) {

            E20R_Tracker::dbg("Tracker::loadAllHooks() - Adding action hooks for plugin");

	        $plugin = E20R_PLUGIN_NAME;

	        add_filter( 'e20r-tracker-configured-roles', array( $this, 'add_default_roles'), 5, 1);

	        add_action( 'template_redirect', array( Article::getInstance(), 'send_to_post' ), 4, 1 );
	        
            add_action( 'init', array( $this, 'update_db'), 7 );
            add_action( 'init', array( $this, "dependency_warnings" ), 10 );
            
            add_action( 'init', 'E20R\Tracker\Models\Program_Model::registerTaxonomy', 10 );
            add_action( "init", 'E20R\Tracker\Models\Program_Model::registerCPT', 10 );
            add_action( "init", 'E20R\Tracker\Models\Article_Model::registerCPT', 11 );
            add_action( "init", 'E20R\Tracker\Models\Action_Model::registerCPT', 12 );
            add_action( "init", 'E20R\Tracker\Models\Workout_Model::registerCPT', 13 );
            add_action( "init", 'E20R\Tracker\Models\Assignment_Model::registerCPT', 14 );
            add_action( "init", 'E20R\Tracker\Models\Exercise_Model::registerCPT', 15 );
            add_action( "init", 'E20R\Tracker\Models\Tracker_Model::registerCPT', 16 );
            
            add_action( 'init', array( Permalinks::getInstance(), 'add_endpoint' ), 10 );
            add_action( 'init', array( Permalinks::getInstance(), 'add_rewrite_tags' ), 10);

            // add_action( 'heartbeat_received', array( Assignment::getInstance(), 'heartbeat_received'), 10, 2);
            // add_filter( 'heartbeat_send', array( Assignment::getInstance(), 'heartbeat_send'), 10, 2 );
            // add_action( 'plugins_loaded', array( $this, "define_Tracker_roles" ) );

            add_action( "wp_login", array( Client::getInstance(), "record_login" ), 99, 2 );
            
            $action = ( isset( $_REQUEST['action'] ) && (false !== strpos($_REQUEST['action'], 'e20r')) ) ? $this->sanitize($_REQUEST['action']) : null;

            if ( !is_null( $action ) ) {
               add_action( "wp_ajax_nopriv_{$action}", array( E20R_Tracker::getInstance(), "ajaxUnprivError" ) );
            }

            add_filter( 'post_link', array( Permalinks::getInstance(), 'process_post_link' ), 10, 3 );

            // add_filter( 'pmpro_after_change_membership_level', array( $this, 'setUserProgramStart') );

            add_filter( 'pmpro_member_startdate', array( Program::getInstance(), 'setProgramForUser'), 11, 3 );
            add_filter( 'auth_cookie_expiration', array( Tracker_Access::getInstance(), 'login_timeout'), 100, 3 );
            add_filter( 'pmpro_email_data', array( $this, 'filter_changeConfirmationMessage' ), 10, 2 );

            add_post_type_support( 'page', 'excerpt' );

            if ( !is_user_logged_in() ) {
                return;
            }

            add_action( 'init', array( Tracker_Access::getInstance(), 'auth_timeout_reset'), 10 );

            add_action( 'wp_ajax_e20r_coach_message', array( Assignment::getInstance(), 'heartbeat_received'),10, 2);
            add_action( 'wp_ajax_e20r_save_activity', array( Workout::getInstance(), 'saveExData_callback' ) );
	        add_action( 'wp_ajax_e20r_manage_option_list', array( Assignment::getInstance(), 'manage_option_list') );
            add_action( 'e20r_schedule_email_for_client', array( Client::getInstance(), 'send_email_to_client' ), 10, 2 );

            add_filter( 'the_content', array( Article::getInstance(), 'contentFilter' ) );

            // add_action( 'init', array( Assignment::getInstance(), 'update_metadata' ), 20 );

            add_action( "admin_action_e20r_duplicate_as_draft", array( $this, 'duplicate_cpt_as_draft') );
            add_action( 'admin_notices', array( $this, 'display_admin_notice'  ) );

	        add_filter( "pmpro_has_membership_access_filter", array( Tracker_Access::getInstance(), "admin_access_filter" ), 10, 3);
	        add_filter( 'embed_defaults', array( Exercise::getInstance(), 'embed_default' ) );

	        // E20R_Tracker::dbg("Tracker::loadAllHooks() - Load upload directory filter? ". $Client->isNourishClient( $current_user->ID));
            E20R_Tracker::dbg("Tracker::loadAllHooks() - Pagenow = {$pagenow}" );

            $post_id =  ( !empty( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : null );

            if ( ( ! empty( $post_id ) ) && ( $pagenow == 'async-upload.php' || $pagenow == 'media-upload.php') ) {

                $parent = get_post($post_id)->post_parent;

                if (has_shortcode(get_post($post_id)->post_content, 'weekly_progress') ||
                    (!(('post' == get_post_type($post_id)) || ('page' == get_post_type($post_id))))
                ) {


                    E20R_Tracker::dbg("Tracker::loadAllHooks() - Loading filter to change the upload directory for Nourish clients");
                    add_filter('media_view_strings', array(Media_Library::getInstance(), 'clientMediaUploader'), 10);

                    E20R_Tracker::dbg("Tracker::loadAllHooks() - Loaded filter to change the Media Library settings for client uploads");
                    add_filter("wp_handle_upload_prefilter", array( Media_Library::getInstance(), "pre_upload"));
                    add_filter("wp_handle_upload", array( Media_Library::getInstance(), "post_upload"));
                    E20R_Tracker::dbg("Tracker::loadAllHooks() - Loaded filter to change the upload directory for Nourish clients");

                    E20R_Tracker::dbg("Tracker::loadAllHooks() - Control Access to media uploader for Tracker users");

                    /* Control access to the media uploader for Nourish users */
                    add_action( 'pre_get_posts', array( Media_Library::getInstance(), 'restrict_media_library') );
                    add_filter( 'wp_handle_upload_prefilter', array( Measurements::getInstance(), 'setFilenameForClientUpload' ) );

                }
            }

            add_filter( 'page_attributes_dropdown_pages_args', array( Exercise::getInstance(), 'changeSetParentType'), 10, 2);
            add_filter( 'enter_title_here', array( $this, 'setEmptyTitleString' ) );

            // add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_frontend_css') );
            // add_action( 'wp_footer', array( &$this, 'enqueue_user_scripts' ) );
            // add_action( 'wp_print_scripts', array( Client::getInstance(), 'load_scripts' ) );
            // add_action( '', array( $Client, 'save_gravityform_entry'), 10, 2 );

            add_action( 'wp_ajax_e20r_addAssignment', array( Article::getInstance(), 'add_assignment_callback') );
            add_action( 'wp_ajax_e20r_getDelayValue', array( Article::getInstance(), 'getDelayValue_callback' ) );
	        add_action( 'wp_ajax_e20r_removeAssignment', array( Article::getInstance(), 'remove_assignment_callback') );
            add_action( 'wp_ajax_e20r_add_reply', array( Assignment::getInstance(), 'add_assignment_reply') );
            add_action( 'wp_ajax_e20r_assignmentData', array( Assignment::getInstance(), 'ajax_assignmentData' ) );
            add_action( 'wp_ajax_e20r_manage_option_list', array( Assignment::getInstance(), 'manage_option_list') );
            add_action( 'wp_ajax_e20r_update_message_status', array( Assignment::getInstance(), 'update_message_status') );
            add_action( 'wp_ajax_e20r_daynav', array( Action::getInstance(), 'nextCheckin_callback' ) );
            add_action( 'wp_ajax_e20r_save_item_data', array( Action::getInstance(), 'ajax_save_item_data' ) );
            add_action( 'wp_ajax_e20r_saveCheckin', array( Action::getInstance(), 'saveCheckin_callback' ) );
	        add_action( 'wp_ajax_e20r_save_daily_progress', array( Action::getInstance(), 'dailyProgress_callback' ) );
            add_action( 'wp_ajax_e20r_clientDetail', array( Client::getInstance(), 'ajax_clientDetail' ) );
            add_action( 'wp_ajax_e20r_complianceData', array( Client::getInstance(), 'ajax_complianceData' ) );
            add_action( 'wp_ajax_e20r_getMemberListForLevel', array( Client::getInstance(), 'ajax_getMemberlistForLevel' ) );
            add_action( 'wp_ajax_e20r_sendClientMessage', array( Client::getInstance(), 'ajax_sendClientMessage' ) );
            add_action( 'wp_ajax_e20r_showClientMessage', array( Client::getInstance(), 'ajax_showClientMessage' ) );
            add_action( 'wp_ajax_e20r_showMessageHistory', array( Client::getInstance(), 'ajax_ClientMessageHistory') );
            add_action( 'wp_ajax_e20r_updateUnitTypes', array( Client::getInstance(), 'updateUnitTypes') );
            add_action( 'wp_ajax_e20r_userinfo', array( Client::getInstance(), 'ajax_userInfo_callback' ) );
            add_action( 'wp_ajax_e20r_checkCompletion', array( Measurements::getInstance(), 'checkProgressFormCompletion_callback' ) );
            add_action( 'wp_ajax_e20r_deletePhoto', array( Measurements::getInstance(), 'ajax_deletePhoto_callback' ) );
            add_action( 'wp_ajax_e20r_loadProgress', array( Measurements::getInstance(), 'ajax_loadProgressSummary' ) );
            add_action( 'wp_ajax_e20r_measurementDataForUser', array( Measurements::getInstance(), 'ajax_getPlotDataForUser' ) );
            add_action( 'wp_ajax_e20r_saveMeasurementForUser', array( Measurements::getInstance(), 'saveMeasurement_callback' ) );
            add_action( 'wp_ajax_e20r_add_exercise', array( Workout::getInstance(), 'add_new_exercise_to_group_callback' ) );
	        add_action( 'wp_ajax_e20r_add_new_exercise_group', array( Workout::getInstance(), 'add_new_exercise_group_callback' ) );
            add_action( 'wp_ajax_e20r_addWorkoutGroup', array( Workout::getInstance(), 'ajax_addGroup_callback' ) );
            add_action( 'wp_ajax_e20r_load_activity_stats', array( Workout::getInstance(), 'ajax_getPlotDataForUser' ) );
	        add_action( 'wp_ajax_e20r_save_activity', array( Workout::getInstance(), 'saveExData_callback' ) );
			add_action( 'wp_ajax_e20r_paginate_assignment_answer_list', array( Assignment::getInstance(), 'ajax_paginateAssignments' ) );
			add_action( 'wp_ajax_e20r_paginate_measurements_list', array( Assignment::getInstance(), 'ajax_paginateMeasurements' ) );

            /* AJAX call-backs if user is unprivileged */

            if ( is_admin() ) {

                add_action( 'current_screen', array( $this, 'current_screen_hooks' ), 10 );

                add_action( 'save_post', array( Tracker_Model::getInstance(), 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( Program::getInstance(), 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( Exercise::getInstance(), 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( Workout::getInstance(), 'saveSettings' ), 10, 2 );
                add_action( 'save_post', array( Action::getInstance(), 'saveSettings' ), 10, 20);
                add_action( 'save_post', array( Article::getInstance(), 'saveSettings' ), 10, 20);
                add_action( 'save_post', array( Assignment::getInstance(), 'saveSettings' ), 10, 20);

                add_action( 'post_updated', array( Tracker_Model::getInstance(), 'saveSettings' ), 10, 2 );
                add_action( 'post_updated', array( Program::getInstance(), 'saveSettings' ) );
                add_action( 'post_updated', array( Exercise::getInstance(), 'saveSettings' ) );
                add_action( 'post_updated', array( Workout::getInstance(), 'saveSettings' ) );
                add_action( 'post_updated', array( Action::getInstance(), 'saveSettings' ) );
                add_action( 'post_updated', array( Article::getInstance(), 'saveSettings' ) );
                add_action( 'post_updated', array( Assignment::getInstance(), 'saveSettings' ) );

                add_action( 'add_meta_boxes_e20r_articles', array( Article::getInstance(), 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_assignments', array( Assignment::getInstance(), 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_programs', array( Program::getInstance(), 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_exercises', array( Exercise::getInstance(), 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_workout', array( Workout::getInstance(), 'editor_metabox_setup') );
                add_action( 'add_meta_boxes_e20r_actions', array( Action::getInstance(), 'editor_metabox_setup') );

                add_action( 'admin_init', array( $this, 'registerSettingsPage' ) );

                add_action( 'admin_head', array( $this, 'post_type_icon' ) );
                add_action( 'admin_menu', array( $this, 'loadAdminPage') );
                add_action( 'admin_menu', array( $this, 'registerAdminPages' ) );
                add_action( 'admin_menu', array( $this, "renderGirthTypesMetabox" ) );

                /* Allow admin to set the program ID for the user in their profile(s) */
                add_action( 'show_user_profile', array( Program::getInstance(), 'selectProgramForUser' ) );
                add_action( 'edit_user_profile', array( Program::getInstance(), 'selectProgramForUser' ) );
                add_action( 'edit_user_profile_update', array( Program::getInstance(), 'updateProgramForUser') );
                add_action( 'personal_options_update', array( Program::getInstance(), 'updateProgramForUser') );

                add_action( 'show_user_profile', array( Client::getInstance(), 'selectRoleForUser' ) );
                add_action( 'edit_user_profile', array( Client::getInstance(), 'selectRoleForUser' ) );
                add_action( 'edit_user_profile_update', array( Client::getInstance(), 'updateRoleForUser') );
                add_action( 'personal_options_update', array( Client::getInstance(), 'updateRoleForUser') );
                add_filter( "plugin_action_links_$plugin", array( $this, 'plugin_add_settings_link' ) );

                // Custom columns
                add_filter( 'manage_edit-e20r_actions_columns', array( Action::getInstance(), 'set_custom_edit_columns' ) );
                add_filter( 'manage_edit-e20r_assignments_columns', array( Assignment::getInstance(), 'set_custom_edit_columns' ) );

                add_action( 'manage_e20r_actions_posts_custom_column' , array( Action::getInstance(), 'custom_column'), 10, 2 );
                add_action( 'manage_e20r_assignments_posts_custom_column' , array( Assignment::getInstance(), 'custom_column'), 10, 2 );

                add_filter( 'manage_edit-e20r_actions_sortable_columns', array( Action::getInstance(), 'sortable_column' ) );
                add_filter( 'manage_edit-e20r_assignments_sortable_columns', array( Assignment::getInstance(), 'sortable_column' ) );

                add_filter( 'pre_get_posts', array(Action::getInstance(), 'sort_column') );
                add_filter( 'pre_get_posts', array(Assignment::getInstance(), 'sort_column') );
				add_filter( 'posts_orderby', array(Assignment::getInstance(), 'order_by') );

                // add_filter('manage_e20r_assignments_posts_columns', array( Assignment::getInstance(), 'assignment_col_head' ) );
                // add_action('manage_e20r_assignments_posts_custom_column', array( Assignment::getInstance(), 'assignment_col_content' ), 10, 2);

                add_filter('manage_e20r_exercises_posts_columns', array( Exercise::getInstance(), 'col_head' ) );
                add_action('manage_e20r_exercises_posts_custom_column', array( Exercise::getInstance(), 'col_content' ), 10, 2);

            }

            E20R_Tracker::dbg("Tracker::loadAllHooks() - Scripts and CSS");
            /* Load scripts & CSS */
            add_action( 'admin_enqueue_scripts', array( Tracker_Scripts::getInstance(), 'enqueue_admin_scripts') );
            add_action( 'wp_enqueue_scripts', array( Tracker_Scripts::getInstance(), 'loadHooks') );
            
            E20R_Tracker::dbg("Tracker::loadAllHooks() - Loading Short Codes");
            add_shortcode( 'weekly_progress', array( Measurements::getInstance(), 'shortcode_weeklyProgress' ) );
            add_shortcode( 'progress_overview', array( Measurements::getInstance(), 'shortcode_progressOverview') );
            add_shortcode( 'daily_progress', array( Action::getInstance(), 'shortcode_dailyProgress' ) );
	        add_shortcode( 'e20r_activity', array( Workout::getInstance(), 'shortcode_activity' ) );
	        add_shortcode( 'e20r_activity_archive', array( Workout::getInstance(), 'shortcode_act_archive' ) );
	        add_shortcode( 'e20r_exercise', array( Exercise::getInstance(), 'shortcode_exercise' ) );
            add_shortcode( 'e20r_profile', array( Client::getInstance(), 'shortcode_clientProfile' ) );
            add_shortcode( 'e20r_client_overview', array( Client::getInstance(), 'shortcode_clientList') );
            add_shortcode( 'e20r_article_summary', array( Article::getInstance(), 'shortcode_article_summary') );
            add_shortcode( 'e20r_article_archive', array( Article::getInstance(), 'article_archive_shortcode') );

            add_action( 'plugins_loaded', array( GF_Integration::getInstance() ,'loadHooks') );

	        //add_filter( 'wp_video_shortcode',  array( Exercise::getInstance(), 'responsive_wp_video_shortcode' ), 10, 5 );


	        E20R_Tracker::dbg("Tracker::loadAllHooks() - Action hooks for plugin are loaded");
        }

        $this->hooksLoaded = true;
    }

    /**
      * Load duplicate links on Edit page
      *
      * @param  \WP_Screen $screen
      */
    public function current_screen_hooks( $screen ) {

        $edit_pages = array(
            'edit-e20r_girth_types',
            'edit-e20r_programs',
            'edit-e20r_articles',
            'edit-e20r_actions',
            'edit-e20r_workout',
            'edit-e20r_assignments',
            'edit-e20r_exercises',
        );

        if ( in_array( $screen->id, $edit_pages ) ) {
            add_filter( "post_row_actions", array( $this, 'duplicate_cpt_link'), 10, 2);
            add_filter( "page_row_actions", array( $this, 'duplicate_cpt_link'), 10, 2);
        }
    }
    
    

	public function loadOption( $optionName ) {

		$value = false;

        // E20R_Tracker::dbg("Tracker::loadOption() - Looking for option with name: {$optionName}");
        $options = get_option( $this->setting_name );

        if ( empty( $options ) ) {

            // E20R_Tracker::dbg("Tracker::loadOption() - No options defined at all!");
            return false;
        }

        if ( empty( $options[$optionName] ) ) {
            // E20R_Tracker::dbg("Tracker::loadOption() - Option {$optionName} exists but contains no data!");
            return false;
        }
        else {
            // E20R_Tracker::dbg("Tracker::loadOption() - Option {$optionName} exists...");

            if ( 'e20r_interview_page' == $optionName ) {
                return E20R_COACHING_URL . "/welcome-questionnaire/";
            }

            return $options[$optionName];
        }
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
       
        E20R_Tracker::dbg("Tracker::getURLToPageWithShortcode() - Short code: {$short_code} -> Url(s): ");
        E20R_Tracker::dbg($urls);

        return $urls;
    }

	public function trackerCPTs() {
		return array(
			Workout_Model::post_type,
			Assignment_Model::post_type,
			Program_Model::post_type,
			Article_Model::post_type,
			Exercise::post_type,
			Action_Model::post_type,
		);
	}

	function plugin_add_settings_link( $links ) {

		$settings_link = sprintf( '<a href="options-general.php?page=e20r_tracker_opt_page">%s</a>', __( 'Settings', 'e20r-tracker' ) );
		array_push( $links, $settings_link );
		return $links;
	}

    public function setEmptyTitleString( $title ) {

        $screen = get_current_screen();

        switch ( $screen->post_type ) {
            case Exercise::post_type:

                $title = 'Enter Exercise Name Here';
                break;

            case Program_Model::post_type:

                $title = 'Enter Program Name Here';
                remove_meta_box( 'postexcerpt', Program_Model::post_type, 'side' );
                add_meta_box('postexcerpt', __('Summary'), 'post_excerpt_meta_box', Program_Model::post_type, 'normal', 'high');

                break;

            case Workout_Model::post_type:

                $title = 'Enter Workout Name Here';
	            remove_meta_box( 'postexcerpt', Workout_Model::post_type, 'side' );
	            add_meta_box('postexcerpt', __('Summary'), 'post_excerpt_meta_box', Workout_Model::post_type, 'normal', 'high');

	            break;

            case Action_Model::post_type:

                $title = 'Enter Action Short-code Here';
                remove_meta_box( 'postexcerpt', Action_Model::post_type, 'side' );
                add_meta_box('postexcerpt', __('Action text'), 'post_excerpt_meta_box', Action_Model::post_type, 'normal', 'high');

                break;

            case  Article_Model::post_type:

                $title = 'Enter Article Prefix Here';

                break;

            case Assignment_Model::post_type:

                $title = "Enter Assignment/Question Here";
                remove_meta_box( 'postexcerpt', Assignment_Model::post_type, 'side' );
                add_meta_box('postexcerpt', __('Assignment description'), 'post_excerpt_meta_box', Assignment_Model::post_type, 'normal', 'high');


        }

        E20R_Tracker::dbg("Tracker::setEmptyTitleString() - New title string defined");
        return $title;
    }

    public function validate( $input ) {

        E20R_Tracker::dbg('Tracker::validate() - Running validation: ' . print_r( $input, true ) );

        $valid = $this->settings;

        foreach ( $input as $key => $value ) {

           if ( ( false !== stripos( $key, 'converted_metadata' ) ) ||
                ( false !== stripos( $key, '_tables' ) ) ||
                 ( false !== stripos( $key, 'roles_' ) )) {

                if ( false !== stripos( $key, 'converted_metadata' ) ) {

                    $value = false;
                }
                else {
                    $value = $this->validate_bool( $value );
                }
           }
           elseif ( ( false !== stripos( $key, 'unserialize_notice' ) ) ) {

                $value = $value;
           }
           else {
                $value = intval($value);
           }

            if ( false !== stripos( $key, 'e20r_db_version' ) ) {
                $value = E20R_DB_VERSION;
            }

            if ( false !== stripos( $key, 'run_unserialize') ) {
                $value = E20R_RUN_UNSERIALIZE;
            }

           $valid[$key] = apply_filters( 'e20r_settings_validation_' . $key, $value );
        }

        /*
         * Use add_settings_error( $title (title of setting), $errorId (text identifier), $message (Error message), 'error' (type of message) ); if needed
         */

        unset( $input ); // Free.
        E20R_Tracker::dbg('Tracker::validate() - Returning validation: ' . print_r( $valid, true ) );

        return $valid;
    }

    public function validate_bool( $value ) {

        return ( 1 == intval( $value ) ? 1 : 0);
    }

    public function loadAdminPage() {

        add_options_page( __( 'Eighty / 20 Tracker', 'e20r-tracker'), __('E20R Tracker', 'e20r-tracker'), 'manage_options', 'e20r_tracker_opt_page', array( $this, 'render_settings_page' ) );

        // $this->registerAdminPages();

    }

    public function registerSettingsPage() {

        // Register any global settings for the Plugin
        register_setting( 'e20r_options', $this->setting_name, array( $this, 'validate' ) );

        /* Add fields for the settings */
        add_settings_section( 'e20r_tracker_timeouts', __( 'User Settings', 'e20r-tracker'), array( &$this, 'render_login_section_text' ), 'e20r_tracker_opt_page' );
        add_settings_field( 'e20r_tracker_login_timeout', __("Default login", 'e20r-tracker'), array( $this, 'render_logintimeout_select'), 'e20r_tracker_opt_page', 'e20r_tracker_timeouts');
        add_settings_field( 'e20r_tracker_rememberme_timeout', __("Extended login", 'e20r-tracker'), array( $this, 'render_remembermetimeout_select'), 'e20r_tracker_opt_page', 'e20r_tracker_timeouts');
        add_settings_field( 'e20r_tracker_encrypt_surveys', __("Encrypt Surveys", 'e20r-tracker'), array( $this, 'render_survey_select'), 'e20r_tracker_opt_page', 'e20r_tracker_timeouts');

        // add_settings_section( 'e20r_tracker_programs', 'Programs', array( &$this, 'render_program_section_text' ), 'e20r_tracker_opt_page' );
        // add_settings_field( 'e20r_tracker_measurement_day', __("Day to record progress", 'e20r-tracker'), array( $this, 'render_measurementday_select'), 'e20r_tracker_opt_page', 'e20r_tracker_programs');
        // add_settings_field( 'e20r_tracker_lesson_source', __("Drip Feed managing lessons", 'e20r-tracker'), array( $this, 'render_lessons_select'), 'e20r_tracker_opt_page', 'e20r_tracker_programs');

        add_settings_section( 'e20r_tracker_deactivate', __( 'Deactivation settings', 'e20r-tracker' ), array( &$this, 'render_deactivation_section_text' ), 'e20r_tracker_opt_page' );
        add_settings_field( 'e20r_tracker_purge_tables', __("Clear tables", 'e20r-tracker'), array( $this, 'render_purge_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');
        add_settings_field( 'e20r_tracker_delete_tables', __("Delete tables", 'e20r-tracker'), array( $this, 'render_delete_checkbox'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate');

        // add_settings_field( 'e20r_tracker_measured', __('Progress measurements', 'e20r-tracker'), array( $this, 'render_measurement_list'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate' );

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

        echo "<p>" . __("Configure user session timeout values. 'Extended' is the timeout value that will be used if a user selects 'Remember me' at login.", 'e20r-tracker' ) . "</p><hr/>";
    }

    // URL: "//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css"
    // URL: "//code.jquery.com/ui/1.11.2/jquery-ui.js"

    public function render_program_section_text() {

        echo "<p>" . __( "Configure global Eighty / 20 Tracker settings.", "e20r-tracker" ) . "</p><hr/>";
    }

    public function render_deactivation_section_text() {

        echo "<p>" . __( "Configure the behavior of the plugin when it gets deactivated.", "e20r-tracker" ) . "</p><hr/>";
    }

    public function render_settings_page() {

        ob_start();
        ?>
        <div id="e20r-settings">
            <div class="wrap">
                <h2><?php _e( 'Settings: Eighty / 20 Tracker', 'e20r-tracker' ); ?></h2>
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

       
        global $e20rAdminPage;
        global $ClientInfoPage;

        E20R_Tracker::dbg("Tracker::registerAdminPages() - Loading E20R Tracker Admin Menu");

        $e20rAdminPage = add_menu_page( __('E20R Tracker', "e20r-tracker"), __( 'E20R Tracker','e20r-tracker'), 'manage_options', 'e20r-tracker-info', array( Client::getInstance(), 'render_client_page' ), 'dashicons-admin-generic', '71.1' );
        $ClientInfoPage = add_submenu_page( 'e20r-tracker-info', __( 'Client Info','e20r-tracker'), __( "Coaching Page" ,'e20r-tracker'), 'manage_options', 'e20r-client-info', array( Client::getInstance(), 'render_client_page' ));

        $ProgramPage = add_menu_page( 'E20R Programs', __( 'E20R Programs','e20r-tracker'), 'manage_options', 'e20r-tracker-programs', null, 'dashicons-admin-generic', '71.2' );
        $Articles = add_menu_page( 'E20R Articles', __( 'E20R Articles','e20r-tracker'), 'manage_options', 'e20r-tracker-articles', null, 'dashicons-admin-generic', '71.3' );
        $e20rActivities = add_menu_page( 'E20R Activities', __( 'E20R Actvities','e20r-tracker'), 'manage_options', 'e20r-tracker-activities', null, 'dashicons-admin-generic', '71.4' );
    }

    public function renderGirthTypesMetabox() {

        add_meta_box('e20r_tracker_girth_types_meta', __('Sort order for Girth Type', 'e20r-tracker'), array(&$this, "build_girthTypesMeta"), 'e20r_girth_types', 'side', 'high');
    }

    public function build_girthTypesMeta( $object, $box ) {

        global $post;

        $sortOrder = get_post_meta( $post->ID, 'e20r_girth_type_sortorder', true);
        E20R_Tracker::dbg("post-meta sort Order: {$sortOrder}");
        ob_start();
        ?>
        <div class="submitbox" id="e20r-girth-type-postmeta">
            <div id="minor-publishing">
                <div id="e20r-tracker-postmeta">
                    <?php E20R_Tracker::dbg("Loading metabox for Girth Type postmeta"); ?>
                    <?php wp_nonce_field('e20r-tracker-post-meta', 'e20r-girth-type-sortorder-nonce'); ?>
                    <label for="e20r-sort-order"><?php _e("Sort Order", "e20r-tracker"); ?></label>
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

        E20R_Tracker::dbg("Tracker::datesForMeasurements(): {$startDate}, {$weekdayNumber}");

        if ( ( $startDate != null ) && ( strtotime( $startDate ) ) ) {

            E20R_Tracker::dbg("Tracker::datesForMeasurements() - Received a valid date value: {$startDate}");
            $baseDate = " {$startDate}";
        }
	    else {

		    $baseDate = " " . date('Y-m-d', current_time('timestamp') );
	    }

	    E20R_Tracker::dbg("Tracker::datesForMeasurements() - Using {$baseDate} as 'base date'");

        if ( ! $weekdayNumber ) {

            E20R_Tracker::dbg("Tracker::datesForMeasurements() - Using program value");
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

        E20R_Tracker::dbg("Loading metabox to order girth types");

        global $post;

        if ( ! isset( $e20rMeasurements ) ) {

            $e20rMeasurements = new Measurements();
        }

        $girthTypes = $e20rMeasurements->getGirthTypes();

        E20R_Tracker::dbg("Have fetched " . count($girthTypes) . " girth types from db");
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
                                        E20R_Tracker::dbg("Object info: " . print_r( $gObj, true)); ?>
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

	/**
     * Should the User ID have access to the post ID provided
     *
     * @param int $userId
     * @param int $postId
     *
     * @return bool
     */
    public function hasAccess( $userId, $postId ) {

        $Article = Article::getInstance();
        $Program = Program::getInstance();

        global $currentArticle;
        
        $retVal = false;
        $saved = $currentArticle;

        E20R_Tracker::dbg("Tracker::hasAccess() - Checking {$userId}'s access to {$postId}" );

        if ( user_can( $userId, 'publish_posts' ) && ( is_preview() ) ) {

            E20R_Tracker::dbg("Tracker::hasAccess() - Post #{$postId} is a preview for {$userId}. Granting editor/admin access to the preview");
            return true;
        }

        E20R_Tracker::dbg("Tracker::hasAccess() - Checking whether to use PMPro's pmpro_has_membership_access() function" );

        if ( function_exists( 'pmpro_has_membership_access' ) ) {

            E20R_Tracker::dbg("Tracker::hasAccess() - Preferring to use access check as provided by PMPro");
            $levels = pmpro_getMembershipLevelsForUser( $userId );
            $result = pmpro_has_membership_access( $postId, $userId, true ); //Using true to return all level IDs that have access to the sequence

            E20R_Tracker::dbg("Tracker::hasAccess() - Checking if post {$postId} is accessible by {$userId}: " . print_r($result, true));

            if ( $result[0] ) {

                E20R_Tracker::dbg("Tracker::hasAccess() - Does user {$userId} have access to this post {$postId}? " . ( $result[0] == 1 ? 'yes' : 'no' ));

                $filterVal = apply_filters('pmpro_has_membership_access_filter', $result[0], get_post($postId), get_user_by( 'id', $userId ), $levels );
                $retVal = ( $filterVal == 1 ? true : false );

                E20R_Tracker::dbg( "Tracker::hasAccess() - After filter of access value for {$userId}: " . ( $retVal == true ? 'yes' : 'no' ));
            }
        }
        else {
            E20R_Tracker::dbg("Tracker::hasAccess() - No membership access function found for Paid Memberships Pro");
        }

        $current_delay = $this->getDelay( 'now', $userId );
        $found = false;

        $programId = $Program->getProgramIdForUser( $userId );

        $articles = $Article->findArticles( 'post_id', $postId, $programId, $comp = '=' );
        // E20R_Tracker::dbg( $articles);

        // if (!empty( $articles ) && ( 1 == count($articles ) ) ) {
        if ( !empty( $articles ) ) {
            
            $found = $this->get_closest_release_day( $current_delay, $articles );

            // E20R_Tracker::dbg( $found );

            if ( !is_null($found) && ( $found->release_day <= $current_delay ) ) {
                E20R_Tracker::dbg("Tracker::hasAccess() - User {$userId} in program {$programId} has access to {$postId} because {$found->release_day} <= {$current_delay}");
                $retVal = $retVal && true;
            }
            else {

                $retVal = false;
            }

        }

        $currentArticle = $saved;
        
        E20R_Tracker::dbg("Tracker::hasAccess() - Returning " . ( $retVal ? 'true' : 'false' ) . " to calling function: " . $this->whoCalledMe() );
        return $retVal;
    }

    public function getDelay( $delayVal = 'now', $userId = null ) {

        global $current_user;
        global $currentProgram;

        // We've been given a numeric value so assuming it's the delay.
        if ( is_numeric( $delayVal ) ) {

            E20R_Tracker::dbg("Tracker::getDelay() - Numeric delay value specified. Returning: {$delayVal}");
            return $delayVal;
        }

        if ( !isset($currentProgram->startdate) || false === ( $startDate = strtotime( $currentProgram->startdate ) ) ) {
            E20R_Tracker::dbg("Unable to configure startdate for currentProgram (" . isset($currentProgram->id) && !empty($currentProgram->id) ? $currentProgram->id : 'None' . ")");
            return false;
        }

	    E20R_Tracker::dbg("Tracker::getDelay() - Based on startdate of {$currentProgram->startdate}...");

	    if ( $this->validateDate( $delayVal ) ) {

            E20R_Tracker::dbg("Tracker::getDelay() - {$delayVal} is a date.");
		    $delay = Time_Calculations::daysBetween( $startDate, strtotime( $delayVal ), get_option('timezone_string') );

		    E20R_Tracker::dbg("Tracker::getDelay() - Given a date {$delayVal} and returning {$delay} days since {$currentProgram->startdate}");
		    return $delay;
	    }


        if ( $delayVal == 'now' ) {
            E20R_Tracker::dbg("Tracker::getDelay() - Calculating delay since startdate (given 'now')...");

            // Calculate the user's current "days in program".
            if ( is_null( $userId ) ) {

	            E20R_Tracker::dbg("Tracker::getDelay() - Using current_user->ID for userid: {$current_user->ID}");
                $userId = $current_user->ID;
            }

            $delay = Time_Calculations::daysBetween( $startDate, current_time("timestamp") );

            // $delay = ($delay == 0 ? 1 : $delay);

            E20R_Tracker::dbg("Tracker::getDelay() - Days since startdate is: {$delay}...");

            return $delay;
        }

        return false;
    }

	public function validateDate($date)
	{
		$d = \DateTime::createFromFormat('Y-m-d', $date);
		return $d && ( $d->format('Y-m-d') == $date );
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

    public function whoCalledMe() {

        $trace=debug_backtrace();
        $caller=$trace[2];

        $trace =  "Called by {$caller['function']}()";
        if (isset($caller['class']))
            {$trace .= " in {$caller['class']}()";}

        return $trace;
    }

    public function getDateFromDelay( $rDelay = "now", $userId = null ) {

        global $current_user;
        $Program = Program::getInstance();
        global $currentProgram;

        if ( is_null( $userId ) ) {
            $userId = $current_user->ID;
        }

        E20R_Tracker::dbg("Tracker::getDateFromDelay() - Received Delay value of {$rDelay} from calling function: " . $this->whoCalledMe() );
        $startTS = $Program->startdate( $userId );

        if ( 0 == $rDelay ) {

            $delay = 0;
        }
        elseif ( "now" == $rDelay ) {

            E20R_Tracker::dbg("Tracker::getDateFromDelay() - Calculating 'now' based on current time and startdate for the user. Got delay value of {$rDelay}");
            $delay = Time_Calculations::daysBetween( $startTS, current_time('timestamp') );
        }
        else {

            $delay = ($rDelay);
            E20R_Tracker::dbg("Tracker::getDateFromDelay() - Adjusting delay value: {$rDelay} => {$delay}");
        }


        E20R_Tracker::dbg("Tracker::getDateFromDelay() - user w/id {$userId} has a startdate timestamp of {$startTS}");

        if ( ! $startTS ) {

            E20R_Tracker::dbg("Tracker::getDateFromDelay( {$delay} ) -> No startdate found for user with ID of {$userId}");
            return ( date( 'Y-m-d', current_time( 'timestamp' ) ) );
        }

        $sDate = date('Y-m-d', $startTS );
        E20R_Tracker::dbg("Tracker::getDateFromDelay( {$delay} ) -> Startdate found for user with ID of {$userId}: {$sDate}");

        $rDate = date( 'Y-m-d', strtotime( "{$sDate} +{$delay} days") );

        E20R_Tracker::dbg("Tracker::getDateFromDelay( {$delay} ) -> Startdate ({$sDate}) + delay ({$delay}) days = date: {$rDate}");

        if ( $delay < 0 ) {
            E20R_Tracker::dbg("Tracker::getDateFromDelay( {$delay} ) -> Returning 'startdate' as the correct date.");
            $rDate = $sDate;
        }

        return $rDate;
    }

    public function filter_changeConfirmationMessage( $data, $email ) {

        global $current_user;
        global $wpdb;

        $Program = Program::getInstance();
        global $currentProgram;

        if ( function_exists( 'pmpro_getMemberStartdate' ) ) {

            // If this is a "checkout" e-mail and we have a current user
            if (  isset( $current_user->ID ) && ( 0 != $current_user->ID ) ) {

                // Force the $currentProgram global to be populated
                $Program->getProgramIdForUser( $current_user->ID );

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

                E20R_Tracker::dbg("Tracker::filter_changeConfirmationMessage() - Info: Today: {$today}, {$available_when}: ");
                E20R_Tracker::dbg($is_in_program);

	            if ( !empty( $is_in_program ) ) {

                    if ( strtotime( $currentProgram->startdate )  > strtotime( $today ) ) {

                        $substitute = "Your membership account is active, <strong>but access to the Virtual Private Trainer content - including your daily workout routine - will <em>not</em> be available until Monday {$available_when}</strong>.<br/>In the mean time, why not take a peak in the Help Menu items and read through the Frequently Asked Questions?";

                    }else {
                        $substitute = "Your membership account is now active and you have full access to your Virtual Personal Trainer content. However, we recommend that you <a href=\"https://strongcubedfitness.com/login/\">log in</a> and spend some time reading the Help section (click the \"<a href=\"/faq/\">Help</a>\" menu)";
                    }

                   E20R_Tracker::dbg("Tracker::filter_changeConfirmationMessage() - Sending: {$substitute}");

                    // Replace the message (after filters have been applied)
                    $data['e20r_status'] = $substitute;
                }
            }

        }

        return $data;
    }
    
    public function isActiveClient( $clientId ) {

        $Program = Program::getInstance();
        global $currentProgram;

        $retVal = false;

        E20R_Tracker::dbg("Tracker::isActiveClient() - Run checks to see if the client is an active member of the site");

        $programId = $Program->getProgramIdForUser( $clientId );

        if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {

            E20R_Tracker::dbg("Tracker::isActiveClient() - Check whether the user {$clientId} belongs to (one of) the program group(s)");
            $retVal = ( pmpro_hasMembershipLevel( $currentProgram->group, $clientId ) || $retVal );
        }

        return $retVal;
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

    public function update_db() {

        if ( ( $db_ver = (int)$this->loadOption('e20r_db_version' ) ) < E20R_DB_VERSION ) {

            $path = E20R_PLUGIN_DIR . '/e20r_db_update.php';

            if ( file_exists( $path ) ) {

                E20R_Tracker::dbg("Tracker::update_db() - Loading: $path ");
                require( $path );
                E20R_Tracker::dbg("Tracker::update_db() - DB Upgrade functions loaded");
            }
            else {
                E20R_Tracker::dbg("Tracker::update_db() - ERROR: Can't load DB update script!");
                return;
             }


            $diff = ( E20R_DB_VERSION - $db_ver );
            E20R_Tracker::dbg("Tracker::update_db() - We've got {$diff} versions to upgrade... {$db_ver} to " . E20R_DB_VERSION );

            for ( $i = ($db_ver + 1) ; $i <= E20R_DB_VERSION ; $i++ ) {

                $version = $i;
                E20R_Tracker::dbg("Tracker::update_db() - Process upgrade function for Version: {$version}");

                if ( function_exists("e20r_update_db_to_{$version}" ) ) {

                    E20R_Tracker::dbg("Tracker::update_db() - Function to update version to {$version} is present. Executing...");
                    call_user_func( "e20r_update_db_to_{$version}", array( $version ) );
                }
                else {
                    E20R_Tracker::dbg("Tracker::update_db() - No version specific update function for database version: {$version} ");
                }
            }
        }
    }

    public function activate() {

        global $e20r_db_version;

        // Set the requested DB version.
        // $e20r_db_version = E20R_DB_VERSION;

        $e20r_db_version = $this->loadOption( 'e20r_db_version' );

        if ( $e20r_db_version < E20R_DB_VERSION ) {
            $this->manage_tables();
        }

        $this->updateSetting( 'e20r_db_version', $e20r_db_version );

        E20R_Tracker::dbg("Tracker::activate() - Should we attempt to unserialize the plugin settings?");
        $errors = '';

        if ( 0 != E20R_RUN_UNSERIALIZE ) {

            E20R_Tracker::dbg("Tracker::activate() - Attempting to unserialize the plugin program id and article id settings");

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

                        E20R_Tracker::dbg("Tracker::activate() - Error while unserializing:");
                        E20R_Tracker::dbg($success);
                    }

                    $errors .= $message;
                }
            }

            $this->updateSetting( 'unserialize_notice', $errors );

            $Assignment = Assignment::getInstance();
            E20R_Tracker::dbg("Tracker::activate() -- Updating assignment programs key to program_ids key. ");
            // $Assignment->update_metadata();
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

        if (false === $this->define_Tracker_roles() )
        {
            E20R_Tracker::dbg("ERROR: Unable to define the required roles for this plugin");
        }

        flush_rewrite_rules();
    }

    public function manage_tables() {

        global $wpdb;
        global $e20r_db_version;

        $current_db_version = $this->loadOption( 'e20r_db_version' );

        if ( $current_db_version == E20R_DB_VERSION ) {

            E20R_Tracker::dbg("Tracker::manage_tables() - No change in DB structure. Continuing...");
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

        E20R_Tracker::dbg("Tracker::manage_tables() - Loading table SQL...");

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

        E20R_Tracker::dbg('Tracker::manage_tables() - Loading/updating tables in database...');
        $result = dbDelta( $checkinSql );
        E20R_Tracker::dbg("Tracker::manage_tables() - Check-in table: ");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $measurementTableSql );
        E20R_Tracker::dbg("Tracker::manage_tables() - Measurements table: ");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $intakeTableSql );
        E20R_Tracker::dbg("Tracker::manage_tables() - Client Information table:");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $assignmentAsSql );
        E20R_Tracker::dbg("Tracker::manage_tables() - Assignments table:");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $activityTable );
        E20R_Tracker::dbg("Tracker::manage_tables() - Activity table:");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $surveyTable );
        E20R_Tracker::dbg("Tracker::manage_tables() - Survey table:");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $message_history );
        E20R_Tracker::dbg("Tracker::manage_tables() - Message history table:");
        E20R_Tracker::dbg($result);
        $result = dbDelta( $response_table );
        E20R_Tracker::dbg("Tracker::manage_tables() - Coach/Client response table:");
        E20R_Tracker::dbg($result);
        // E20R_Tracker::dbg("Tracker::manage_tables() - Adding triggers in database");
        // mysqli_multi_query($wpdb->dbh, $girthTriggerSql );

        // IMPORTANT: Always do this in the e20r_update_db_to_*() function!
        if ( $e20r_db_version != E20R_DB_VERSION ) {
            $this->updateSetting( 'e20r_db_version', E20R_DB_VERSION );
        }
        //
    }

    public function updateSetting( $name, $value ) {

        E20R_Tracker::dbg("Tracker::updateSetting() - Adding/updating setting: {$name} = {$value}");
        $this->settings[$name] = $value;
        update_option( $this->setting_name, $this->settings);
        return true;
    }

    private function getCPTInfo() {

        $Tables = new Tables();
        $Tables->init();

        $Workout = new Workout();
        $Assignment = new Assignment();
        $Program = new Program();
        $Action = new Action();
        $Article = new Article();

        $cpt_info = array(
			Workout_Model::post_type => null,
			Assignment_Model::post_type => null,
            Article_Model::post_type => null,
			Action_Model::post_type => null,
		);

        foreach( $cpt_info as $cpt => $data ) {

            E20R_Tracker::dbg("Tracker::getCPTInfo() - Processing {$cpt}");

            $cpt_info[$cpt] = new \stdClass();
            $cpt_info[$cpt]->keylist = array();

            switch ( $cpt ) {
                case Workout_Model::post_type:

                    $cpt_info[$cpt]->type = $Workout->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-programs"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    break;

                case  Article_Model::post_type:

                    $cpt_info[$cpt]->type = $Article->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-programs"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-assignments"] = "_e20r-{$cpt_info[$cpt]->type}-assignment_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-checkins"] = "_e20r-{$cpt_info[$cpt]->type}-action_ids";
                    // $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-activity_id"] = "_e20r-{$cpt_info[$cpt]->type}-activity_id";
                    break;

                case Assignment_Model::post_type:

                    $cpt_info[$cpt]->type = $Assignment->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-program_ids"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-article_id"] = "_e20r-{$cpt_info[$cpt]->type}-article_ids";
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-program_id"] = "delete";

                    break;

                case Action_Model::post_type:
                    $cpt_info[$cpt]->type = $Action->get_cpt_type();
                    $cpt_info[$cpt]->keylist["_e20r-{$cpt_info[$cpt]->type}-program_ids"] = "_e20r-{$cpt_info[$cpt]->type}-program_ids";
                    break;
            }

        }

        E20R_Tracker::dbg("Tracker::getCPTInfo() - Config is: ");
        E20R_Tracker::dbg($cpt_info);

        return $cpt_info;
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

            E20R_Tracker::dbg("Tracker::unserialize_settings() - Not being asked to convert serialized metadata.");
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

        E20R_Tracker::dbg("Tracker::unserialize_settings() - Converting from {$from_key} to {$to_key} for type {$post_type}");

        foreach ($posts_array as $serialized_post) {

            $serialized_post_id = $serialized_post->ID;
            $serialized_postmeta = get_post_meta( $serialized_post_id, $from_key, true );

            E20R_Tracker::dbg("Tracker::unserialize_settings() - Processing: {$serialized_post->ID} which contains " . gettype($serialized_postmeta));


            if ( "delete" == $to_key ) {

                E20R_Tracker::dbg("Tracker::unserialize_settings() - WARNING: Deleting metadata for {$serialized_post->ID}/{$from_key} ");
                delete_post_meta( $serialized_post_id, $from_key );
            }
            else {

                if ( !is_array( $serialized_postmeta ) ) {

                    E20R_Tracker::dbg( "Tracker::unserialize_settings() - Value isn't actually serialized: {$serialized_postmeta}");
                    $serialized_postmeta = array( $serialized_postmeta );
                }

                if ( is_array( $serialized_postmeta ) ) {

                    E20R_Tracker::dbg("Tracker::unserialize_settings() - serialized postmeta IS an array.");
                    E20R_Tracker::dbg($serialized_postmeta);

                    delete_post_meta( $serialized_post_id, $to_key );

                    foreach ( $serialized_postmeta as $k => $val ) {

                        E20R_Tracker::dbg("Tracker::unserialize_settings() - Update {$serialized_post_id} with key: {$from_key} to {$to_key} -> {$val} ");

                        if ( 0 !== $val ) {

                            add_post_meta( $serialized_post_id, $to_key, $val);
                        }
                        else {
                            E20R_Tracker::dbg("Tracker::unserialize_settings() - Zero value for {$to_key}. Won't save it");
                            unset( $serialized_postmeta[$k] );
                        }
                    }

                    E20R_Tracker::dbg("Tracker::unserialize_settings() - From {$serialized_post_id}/{$from_key}, delete " . gettype( $serialized_postmeta) );

                    $serialized_post_list[] = $serialized_post_id;
                }

                $unserialized_postmeta = get_post_meta( $serialized_post_id, $to_key );

                // E20R_Tracker::dbg("Tracker::unserialize_settings() - Because this is a simulation, the following data is wrong... ");
                E20R_Tracker::dbg("Tracker::unserialize_settings() - Still processing: {$serialized_post->ID}, but now with key {$to_key} which contains: ");
                E20R_Tracker::dbg($unserialized_postmeta);


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
                        E20R_Tracker::dbg("Tracker::unserialize_settings() - {$serialized_post_id} was unserialized and saved.");
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

    public function define_Tracker_roles() {

        global $wp_roles;

        $roles_set = $this->loadOption('roles_are_set');
        $roles = $this->add_default_roles(array());

        E20R_Tracker::dbg("Tracker::define_Tracker_roles() - Processing " . count($roles) . " roles:");
        foreach ( $roles as $key => $user_role ) {

            foreach ( $wp_roles->get_names() as $role_name => $display_name) {

                if ( $role_name === $user_role['role'] ) {
                    E20R_Tracker::dbg("Tracker::define_Tracker_roles() - Removing pre-existing role definition: {$role_name}");
                    $wp_roles->remove_role($role_name);
                }
            }

            E20R_Tracker::dbg("Tracker::define_Tracker_roles() - Adding role definition for {$user_role['role']} => {$user_role['label']}");
            if (! $wp_roles->add_role( $user_role['role'], $user_role['label'], $user_role['permissions'] ) ) {
                E20R_Tracker::dbg("Tracker::define_Tracker_roles() - Error adding {$key} -> '{$user_role['role']}' role!");
                return false;
            }
        }

        $this->updateSetting('roles_are_set',true);

        $admins = get_users( array( 'role' => 'administrator' ) );

        /**
* @var \WP_User $admin
 */
        foreach( $admins as $admin ) {

            if ( !in_array( $roles['coach']['role'], (array) $admin->roles ) ) {
                E20R_Tracker::dbg("Tracker::define_Tracker_roles() - User {$admin->ID} is not (yet) defined as a coach, but is an admin!");
                $admin->add_role( $roles['coach']['role'] );
                E20R_Tracker::dbg("Tracker::define_Tracker_roles() - Added 'coach' role to {$admin->ID}");
            }
        }

        return true;
    }

    /**
      * Define the default roles as they pertain to the user's exercise level experience (Beginner, Intermediate,
      * Experienced)
      *
      * @param      array       $roles      Associative array of defined roles for user's exercise level(s)
      *
      * @return     array                   Associative array of defined roles for the user's exercise level(s).
      *
      * @since 1.5.50 - Initially added
      */
    public function add_default_roles( $roles ) {
    
        return array(
            'coach'         =>  array(
                                        'role' => 'e20r_coach',
                                        'label' => __( "Coach", "e20r-tracker"),
                                        'permissions' => array(
											'read' => true,
											'edit_users' => true,
											'upload_files' => true,
                                         ),
                                ),
            'beginner'      =>  array(
                                        'role' => 'e20r_tracker_exp_1',
                                        'label' => __( "Exercise Level 1 (NE)", "e20r-tracker"),
                                        'permissions' => array(
											'read' => true,
                                            'upload_files' => true,
                                         ),
                                ),
            'intermediate'  =>  array(
                                        'role' => 'e20r_tracker_exp_2',
                                        'label' => __( "Exercise Level 2 (IN)", "e20r-tracker"),
                                        'permissions' => array(
											'read' => true,
                                            'upload_files' => true,
                                         ),
                                ),
            'experienced'   =>  array(
                                        'role' => 'e20r_tracker_exp_3',
                                        'label' => __( "Exercise Level 3 (EX)", "e20r-tracker"),
                                        'permissions' => array(
											'read' => true,
                                            'upload_files' => true,
                                         ),
                                ),
        );
    }

    public function deactivate() {

        global $wpdb;
        global $e20r_db_version;

        $options = get_option( $this->setting_name );

        E20R_Tracker::dbg("Tracker::deactivate() - Current options: " . print_r( $options, true ) );

        $tables = array(
            $wpdb->prefix . 'e20r_checkin',
            $wpdb->prefix . Assignment_Model::post_type,
            $wpdb->prefix . 'e20r_measurements',
            $wpdb->prefix . 'e20r_client_info',
            $wpdb->prefix . 'e20r_workouts',
            $wpdb->prefix .  Article_Model::post_type,
//            $wpdb->prefix . Program_Model::post_type,
//            $wpdb->prefix . 'e20r_sets',

        );


        foreach ( $tables as $tblName ) {

            if ( $options['purge_tables'] == 1 ) {

                E20R_Tracker::dbg("Tracker::deactivate() - Truncating {$tblName}" );
                $sql = "TRUNCATE TABLE {$tblName}";
                $wpdb->query( $sql );
            }

            if ( $options['delete_tables'] == 1 ) {

                E20R_Tracker::dbg( "Tracker::deactivate() - {$tblName} being dropped" );

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

                if ( false === unlink( E20R_PLUGIN_DIR . $file ) ) {
                    error_log("Tracker - Unable to remove requested file: {$file}");
                }
                else {
                    E20R_Tracker::dbg("Tracker::remove_old_files() - Removed: {$file}");
                }
            }
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

        E20R_Tracker::dbg("Tracker::getUserList() - Called by: " . $this->whoCalledMe() );
        $levels = array_keys( $this->getMembershipLevels( $level, false ) );

        E20R_Tracker::dbg("Tracker::getUserList() - Users being loaded for the following level(s): " . print_r( $levels, true ) );

        return $this->model->loadUsers( $levels );
    }

    public function getMembershipLevels( $level = null, $onlyVisible = false ) {

        if ( function_exists( 'pmpro_getAllLevels' ) ) {

	        $name = null;

            if ( is_numeric( $level ) ) {

                E20R_Tracker::dbg("Tracker::getLevelList() - Requested ID: {$level}");
                $tmp = pmpro_getLevel( $level );
                $name = $tmp->name;
                E20R_Tracker::dbg("Tracker::getLevelList() - Level Name: {$name}");
            }

            $allLevels = pmpro_getAllLevels( $onlyVisible, true );
            $levels    = array();

            if ( ! empty( $name ) ) {
                E20R_Tracker::dbg("Tracker::getLevelList() - Supplied name for level: {$name}");
                $name = str_replace( '+', '\+', $name);
                $pattern = "/{$name}/i";
                E20R_Tracker::dbg("Tracker::getLevelList() - Pattern for level: {$pattern}");
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

            // E20R_Tracker::dbg("Levels fetched: " . print_r( $levels, true ) );

            return $levels;
        }

        $this->dependency_warnings();
    }

    public function dependency_warnings() {

        if ( ( !class_exists('PMProSequence') &&
        !class_exists('E20R\Sequences\Sequence\Controller') ) &&
        !class_exists( 'E20R\Sequences\Sequence\Sequence_Controller' ) && is_admin()) {

            ?>
            <div class="error">
            <?php if ( !class_exists('PMProSequence') && !class_exists('E20R\Sequences\Sequence\Controller') && !class_exists('E20R\Sequences\Sequence\Sequence_Controller' ) ) : ?>
                <?php E20R_Tracker::dbg("Tracker::Error -  The The Sequences plugin is not installed"); ?>
                <p><?php _e( "Eighty / 20 Tracker - Missing dependency: Sequences plugin", 'e20r-tracker' ); ?></p>
            <?php endif; ?>
            </div><?php
        }
    }
/*
    public function getLevelIdForUser( $userId = null ) {

        $Program = Program::getInstance();

        $level_id = null;

        if ( is_null( $userId ) ) {

            global $current_user;
            $userId = $current_user->ID;
        }

        $program_id = $Program->getProgramIdForUser( $userId );

        return $program_id;
    }
*/
	public function getGroupIdForUser( $userId = null ) {

		$group_id = null;

		E20R_Tracker::dbg("Tracker::getGroupIdForUser() - Get membership/group data for {$userId}");

		if ( is_null( $userId ) ) {

			global $current_user;
			$userId = $current_user->ID;
		}

		$user = new \WP_User($userId);

		// E20R_Tracker::dbg("Tracker::getGroupIdForUser() - User object: " . print_r( $user, true));

		foreach( (array) $user->roles as $role ) {

			if ( false !== strpos( $role, 'e20r_tracker_exp') ) {
				E20R_Tracker::dbg("Tracker::getGroupIdForUser() - Returning the tracker role/group for {$userId}: {$role}");
				$group_id = $role;
			}
		}

		if (empty($group_id)) {

			$roles = apply_filters('e20r-tracker-configured-roles', array() );
			$group_id = $roles['beginner']['role'];

			E20R_Tracker::dbg("Tracker::getGroupIdForUser() - Assigning default group for the current user ID ({$userId}): {$group_id}");
		}
/*
		if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {

			E20R_Tracker::dbg("Tracker::getGroupIdForUser() - Using Paid Memberships Pro for group/level management for {$userId}");
			$obj = pmpro_getMembershipLevelForUser( $userId );

			$group_id = isset( $obj->ID ) ? $obj->ID : 0;

			E20R_Tracker::dbg("Tracker::getGroupIdForUser() - Returning group ID of {$group_id} for {$userId}");
		}
*/
		return $group_id;
	}

    public function getDripFeedDelay( $postId ) {

        $dripfeed_exists = false;

        if ( class_exists( 'PMProSequence') ) {
            $dripfeed_exists = true;
        }

        if ( class_exists( 'E20R\Sequences\Sequence\Controller') || class_exists( 'E20R\Sequences\Sequence\Sequence_Controller' ) ) {
            $dripfeed_exists = true;
        }

        if (true === $dripfeed_exists ) {

            E20R_Tracker::dbg("Tracker::getDripFeedDelay() - Found the PMPro Sequence Drip Feed plugin");
            
            if ( class_exists('\PMProSequence')) {
                $sequenceIds = \PMProSequence::sequences_for_post( $postId );
            }

            if ( class_exists('E20R\Sequences\Sequence\Controller') ) {
                $sequenceIds = Sequence\Controller::sequences_for_post( $postId );
            }

            if ( class_exists( 'E20R\Sequences\Sequence\Sequence_Controller' ) ) {
                $sequenceIds = E20R\Sequences\Sequence\Sequence_Controller::sequences_for_post( $postId );
            }
            
            foreach ($sequenceIds as $id ) {

                if ( class_exists('\PMProSequence')) {
                    $details = \PMProSequence::post_details( $id, $postId );
                }

                if ( class_exists('E20R\Sequences\Sequence\Controller')) {
                    $details = Sequence\Controller::post_details( $id, $postId );
                }

                if ( class_exists( 'E20R\Sequences\Sequence\Sequence_Controller' ) ) {
                    $details = E20R\Sequences\Sequence\Sequence_Controller::post_details( $id, $postId );
                }
/*
                $seq->get_options( $id );
                $details = $seq->get_post_details( $postId );

                unset($seq);
*/
                E20R_Tracker::dbg("Tracker::getDripFeedDelay() - Delay details: " . print_r( $details, true  ) );

                if ( $id != false ) {

                    E20R_Tracker::dbg("Tracker::getDripFeedDelay() - Returning {$details[0]->delay}");
                    return $details[0]->delay;
                }
            }
        }

        return false;
    }

    /**
* @param $record
 *
* @return array|bool
* @throws \Exception
 */
    public function setFormatForRecord( $record ) {

        $format = array();

        foreach( $record as $key => $val ) {

	        if ( stripos( $key, 'zip' ) ) {

		        E20R_Tracker::dbg("Tracker::setFormatForRecord() - Field contains Zip...");

		        $varFormat = '%s';
	        }
	        else {

		        $varFormat = $this->setFormat( $val );
	        }

            if ( $varFormat !== false ) {

                $format = array_merge( $format, array( $varFormat ) );
            }
            else {

                E20R_Tracker::dbg("Tracker::setFormatForRecord() - Invalid data type for {$key}/{$val} pair");
                throw new \Exception( "The value submitted for persistant storage as {$key} is of an invalid/unknown type" );
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
            // E20R_Tracker::dbg( "setFormat() - {$value} is NOT numeric" );

            if ( ctype_alpha( $value ) ) {
                // E20R_Tracker::dbg( "setFormat() - {$value} is a string" );
                return '%s';
            }

            if ( strtotime( $value ) ) {
                // E20R_Tracker::dbg( "setFormat() - {$value} is a date (treating it as a string)" );
                return '%s';
            }

            if ( is_string( $value ) ) {
                // E20R_Tracker::dbg( "setFormat() - {$value} is a string" );
                return '%s';
            }

            if (is_null( $value )) {
                // E20R_Tracker::dbg( "setFormat() - it's a NULL value");
                return '%s';
            }
        }
        else {

	        if ( filter_var( $value, FILTER_VALIDATE_INT ) !== false ) {
		        return '%d';
	        }

            // E20R_Tracker::dbg( "setFormat() - .{$value}. IS numeric" );

            if ( filter_var( $value, FILTER_VALIDATE_FLOAT) !== false ) {
                return '%f';
            }

        }

	    if ( is_bool( $value ) ) {
		    return '%d';
	    }

	    E20R_Tracker::dbg("Tracker::setFormat() - Value: {$value} doesn't have a recognized format..? " . gettype($value) );
        return '%s';
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
                if($a->{$fields[1]} == $b->{$fields[1]}) {return 0 ;}
                return ($a->{$fields[1]} < $b->{$fields[1]}) ? -1 : 1;
            }
            else
                {return ($a->{$fields[0]} < $b->{$fields[0]}) ? -1 : 1;}
        });

        return $data;
    }

    /**
     * @param $dayNo -- The day number (1 == Monday)
     *
*@return string - The day name
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
     * display_admin_notice function.
     * Displays an appropriate notice based on the results of the saved unserialize_notice option.
     * @access public
     * @return void
     */
    public function display_admin_notice() {

        $notice = $this->loadOption('unserialize_notice');
        
        if ( !empty( $notice ) ) {

            E20R_Tracker::dbg("Tracker::convert_postmeta_notice() - Loading error message");
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

    /**
     * Sort the two posts in ascending order
     *
     * @param $a -- Post to compare (including time variable)
     * @param $b -- Post to compare against (including time variable)
     *
*@return int -- Return -1 if the Delay for post $a is greater than the delay for post $b
     *
     * @access private
     */
    public function sort_descending( $a, $b )
    {
        if ($a->sent == $b->sent)
            {return 0;}

        // Descending Sort Order
        return ($a->sent > $b->sent) ? -1 : +1;
    }
}