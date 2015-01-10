<?php

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
                            'purge_tables' => true,
                            'measurement_day' => CONST_SATURDAY,
            )
        );

        dbg("e20rTracker::constructor() - Loading the types of posts we'll be allowed to manage");
        $this->managed_types = apply_filters("e20r-tracker-post-types", array("post", "page") );

//        dbg("e20rTracker::constructor() - Loading e20rTables class");
//        $this->tables = new e20rTables();

    }

    public function loadAllHooks() {

        global $current_user, $pagenow;
        global $e20rClient, $e20rMeasurements, $e20rArticle, $e20rCheckin, $e20rExercise, $e20rProgram, $e20rWorkout;

        if ( ! $this->hooksLoaded ) {

            dbg("e20rTracker::loadAllHooks() - Adding action hooks for plugin");

            // $e20rProgram = new e20rProgram(); // TODO: Figure out a better way to handle this (the e20rProgram Hook)

            add_action( 'init', array( &$this, "dependency_warnings" ), 10 );
            add_action( "init", array( &$this, "e20r_tracker_girthCPT" ), 10 );
            // add_action( "init", array( &$this, "e20r_tracker_articleCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_programCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_exerciseCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_workoutCPT"), 10 );
            add_action( "init", array( &$this, "e20r_tracker_checkinCPT"), 10 );

            //e20r_checkins

            dbg("e20rTracker::loadAllHooks() - Load upload directory filter? ". $e20rClient->isNourishClient( $current_user->ID));
            dbg("e20rTracker::loadAllHooks() - Pagenow = {$pagenow}" );

            if ( ( $pagenow == 'async-upload.php' || $pagenow == 'media-upload.php') )  {
                dbg("e20rTracker::loadAllHooks() - Loading filter to change the upload directory for Nourish clients");
                // add_filter( 'media-view-strings', array( &$e20rMeasurements, 'clientMediaUploader' ) );
                add_filter( 'upload_dir', array( &$e20rMeasurements, 'set_progress_upload_dir' ) );
                // add_filter( 'wp_handle_upload_prefilter', array( &$e20rMeasurements, 'setFilenameForClientUpload' ) );
            }

            // add_action("gform_after_submission", array( &$e20rClient, "afterGFSubmission" ), 10, 2);
            /* Control access to the media uploader for Nourish users */
            // add_action( 'parse_query', array( &$this, 'current_user_only' ) );
            add_action( 'pre_get_posts', array( &$this, 'restrict_media_library') );
            add_filter( 'media_view_settings', array( &$this, 'media_view_settings'), 99 );

            add_filter( 'page_attributes_dropdown_pages_args', array( &$e20rExercise, 'changeSetParentType'), 10, 2);
            add_filter( 'enter_title_here', array( &$this, 'setEmptyTitleString' ) );

            // add_filter( 'media_upload_default_tab', array( &$this, 'default_media_tab') );

            /* Load scripts & CSS */
            add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_scripts') );
            add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_frontend_css') );
            // add_action( 'wp_footer', array( &$this, 'enqueue_user_scripts' ) );

            // add_action( 'wp_print_scripts', array( &$e20rClient, 'load_scripts' ) );
            add_action( '', array( $e20rClient, 'save_gravityform_entry'), 10, 2 );
            add_action( 'wp_ajax_updateUnitTypes', array( &$e20rClient, 'updateUnitTypes') );
            add_action( 'wp_ajax_e20r_clientDetail', array( &$e20rClient, 'ajax_clientDetail' ) );
            add_action( 'wp_ajax_e20r_complianceData', array( &$e20rClient, 'ajax_complianceData' ) );
            add_action( 'wp_ajax_e20r_assignmentData', array( &$e20rClient, 'ajax_assignmentData' ) );
            add_action( 'wp_ajax_get_memberlistForLevel', array( &$e20rClient, 'ajax_getMemberlistForLevel' ) );
            add_action( 'wp_ajax_e20r_userinfo', array( &$e20rClient, 'ajax_userInfo_callback' ) );
            add_action( 'wp_ajax_saveMeasurementForUser', array( &$e20rMeasurements, 'saveMeasurement_callback' ) );
            add_action( 'wp_ajax_checkCompletion', array( &$e20rMeasurements, 'checkProgressFormCompletion_callback' ) );
            add_action( 'wp_ajax_e20r_measurementDataForUser', array( &$e20rMeasurements, 'ajax_getPlotDataForUser' ) );
            add_action( 'wp_ajax_deletePhoto', array( &$e20rMeasurements, 'ajax_deletePhoto_callback' ) );
            add_action( 'wp_ajax_addPhoto', array( &$e20rMeasurements, 'ajax_addPhoto_callback' ) );
            add_action( 'wp_ajax_addWorkoutGroup', array( &$e20rWorkout, 'ajax_addGroup_callback' ) );

            add_action( 'wp_ajax_get_checkinItem', array( &$e20rCheckin, 'ajax_getCheckin_item' ) );
            add_action( 'wp_ajax_save_item_data', array( &$e20rCheckin, 'ajax_save_item_data' ) );

            add_action( 'save_post', array( &$this, 'save_girthtype_order' ), 10, 2 );
            add_action( 'save_post', array( &$e20rProgram, 'saveSettings' ), 10, 2 );
            add_action( 'save_post', array( &$e20rExercise, 'saveSettings' ), 10, 2 );
            add_action( 'save_post', array( &$e20rWorkout, 'saveSettings' ), 10, 2 );
            add_action( 'save_post', array( &$e20rCheckin, 'saveSettings' ), 10, 20);
            add_action( 'post_updated', array( &$e20rProgram, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rExercise, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rWorkout, 'saveSettings' ) );
            add_action( 'post_updated', array( &$e20rCheckin, 'saveSettings' ) );


            add_action( 'wp_enqueue_scripts', array( &$this, 'has_weeklyProgress_shortcode' ) );

            add_action( 'add_meta_boxes', array( &$e20rArticle, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes', array( &$e20rProgram, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes', array( &$e20rExercise, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes', array( &$e20rWorkout, 'editor_metabox_setup') );
            add_action( 'add_meta_boxes', array( &$e20rCheckin, 'editor_metabox_setup') );

            add_action( 'admin_init', array( &$this, 'registerSettingsPage' ) );

            add_action( 'admin_head', array( &$this, 'post_type_icon' ) );
            add_action( 'admin_menu', array( &$this, 'loadAdminPage') );
            add_action( 'admin_menu', array( &$this, 'registerAdminPages' ) );
            add_action( 'admin_menu', array(&$this, "renderGirthTypesMetabox"));


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


            // TODO: Investigate the need for this.
            add_action( 'add_meta_boxes', array( &$this, 'editor_metabox_setup') );

            /* Gravity Forms data capture for Check-Ins, Assignments, Surveys, etc */
            add_action( 'gform_after_submission', array( &$this, 'gravityform_submission' ), 10, 2);

            add_shortcode( 'weekly_progress', array( &$e20rMeasurements, 'shortcode_weeklyProgress' ) );
            add_shortcode( 'progress_overview', array( &$e20rMeasurements, 'shortcode_progressOverview') );

            unset($e20rProgram);
            dbg("e20rTracker::loadAllHooks() - Action hooks for plugin are loaded");
        }

        $this->hooksLoaded = true;
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

            if ( ctype_alpha( $field ) ||
                 strtotime( $field ) ||
                 is_string( $field ) ) {

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
                break;

            case 'e20r_checkins':

                $title = 'Enter Check-in Short-code Here';
                remove_meta_box( 'postexcerpt', 'e20r_checkins', 'side' );
                add_meta_box('postexcerpt', __('Check-in text'), 'post_excerpt_meta_box', 'e20r_checkins', 'normal', 'high');

                break;

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

    public function media_view_settings( $settings, $post ) {

        global $e20rMeasurementDate, $e20rTracker;

//        unset( $settings['mimeTypes']['audio'] );
//        unset( $settings['mimeTypes']['video'] );

        dbg("Measurement date: {$e20rMeasurementDate}");

        if ( $e20rMeasurementDate == '2014-01-01') {
            $tmp = $e20rTracker->datesForMeasurements( date( 'Y-m-d', current_time('timestamp') ) );
            $e20rMeasurementDate = $tmp['current'];
            dbg("Measurement date is now: {$e20rMeasurementDate}");
        }
        $monthYear = date('F Y',strtotime( $e20rMeasurementDate ) );
        $settings['currentMonth'] = $monthYear;

        foreach ( $settings['months'] as $key => $month ) {

            if ( $month->text != $monthYear ) {
                unset( $settings['months'][$key]);
            }
        }

        // dbg("Media view settings: " . print_r( $settings, true ) );

        /*
        if ( isset( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['post_id'] ) ) {
            $post_type = get_post_type( absint( $_REQUEST['post_id'] ) );

            if ( $post_type ) {
                if ( 'page' == $post_type ) {
                    // Simply uncomment any of the following to remove them from the media uploader.
                    //unset( $media_tabs['type'] ); // This removes the "From Computer" tab
                    unset( $media_tabs['type_url'] ); // This removes the "From URL" tab
                    unset( $media_tabs['gallery'] ); // This removes the "Gallery" tab
                    unset( $media_tabs['library'] ); // This remove the "Media Library" tab
                }
            }
        }
*/

        //dbg("e20rTracker::remove_media_tabs() - Now using: " . print_r( $media_tabs, true));
        return $settings;
    }

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

    public function gravityform_submission( $entries, $form ) {

        dbg("e20rTracker::gravityform_submission() - Entry: " . print_r( $entries, true));
        // dbg("e20rTracker::gravityform_submission() - Form: " . print_r( $form, true));

        if ( stripos( $form['title'], 'Welcome' ) ) {
            dbg("e20rTracker::gravityform_submission - Processing intake form");

            foreach ( $entries as $key => $entry ) {

                dbg("e20rTracker::form_data - Field: .{$form['fields'][$key]['label']}=>{$entry}.");



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

        if ( $post->post_type != 'e20r_girth_types') {
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

    public static function enqueue_admin_scripts() {

        dbg("e20rTracker::enqueue_admin_scripts() - Loading javascript");

        if ( ! is_admin() ) {
            return;
        }

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

    function loadAdminPage() {

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

        // add_settings_field( 'e20r_tracker_measured', __('Progress measurements', 'e20r_tracker'), array( $this, 'render_measurement_list'), 'e20r_tracker_opt_page', 'e20r_tracker_deactivate' );

        // $this->render_settings_page();

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

    public function render_measurement_list() {

        global $current_user;

        $options = get_option( $this->setting_name );
/*
        dbg( "Measured Items: " . print_r($measured_items, true ) );
        ?>
        <table class="e20r-settings-table">
            <tbody>
            <?php

            foreach ( $measured_items as $key => $list ) {

                if ( ! is_array( $list ) ) {
                    dbg("Item: {$list}");
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" id="<?php echo $list; ?>-measurement" name="<?php echo $this->setting_name; ?>[measuring][<?php echo $list; ?>]" value="1" <?php checked( $options['measuring'][ $list ], 1 ); ?>>
                        </td>
                        <td>
                            <label for="<?php echo $list ?>-measurement"><strong style="text-transform: capitalize;"><?php echo $list; ?></strong></label>
                        </td>
                    </tr>
                <?php
                }
                else {
                    $i = 1;
                    dbg("Item List: " . print_r( $list, true ) );
                    ?>
                    <tr>
                        <td colspan="2" style="text-transform: capitalize;"><h3><?php echo $key; ?></h3></td>
                    </tr>
                    <tr><td colspan="2">
                        <table class="e20r-inline-table">
                            <tbody>
                                <tr><?php

                            foreach ( $list as $item ) {
                                $i++;
                                    ?>
                                    <td>
                                        <input type="checkbox" id="<?php echo $item; ?>-measurement" name="<?php echo $this->setting_name; ?>[measuring][<?php echo $key; ?>][<?php echo $item; ?>]" value="1" <?php checked( $options['measuring'][ $key ][ $item ], 1 ); ?>>
                                    </td>
                                    <td>
                                        <label for="<?php echo $item ?>-measurement"><strong style="text-transform: capitalize;"><?php echo $item; ?></strong></label>
                                    </td><?php

                                if ( $i == 3 ) {
                                    $i = 1;
                                    ?>
                                </tr><tr><?php
                                }
                            }
                            ?>
                            </tr>
                            </tbody>
                        </table>
                        </td>
                    </tr><?php
                }
            }
            ?>
            </tbody>
        </table>
        <?php
*/
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

        dbg("e20rTracker::registerAdminPages() - Loading E20R Tracker Admin Menu");
        $page = add_menu_page( 'E20R Tracker', __( 'E20R Tracker','e20r_tracker'), 'manage_options', 'e20r-tracker', array( &$e20rClient, 'render_client_page' ), 'dashicons-admin-generic', '71.1' );
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

        dbg("datesForMeasurements(): {$startDate}, {$weekdayNumber}");

        if ( $startDate != null ) {

            if ( strtotime( $startDate ) ) {
                dbg("datesForMeasurements() - Received a valid date value: {$startDate}");
                $baseDate = " {$startDate}";
            }
        }

        if ( ! $weekdayNumber ) {

            dbg("datesForMeasurements() - Using option value(s)");
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
            wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery.timeago' ), '0.1', true );
            wp_register_script('e20r_tracker_admin', E20R_PLUGINS_URL . '/js/e20r-tracker-admin.js', array('jquery'), '0.1', false); // true == in footer of body.

            /* Localize ajax script */
            /*wp_localize_script('e20r_tracker_admin', 'e20r_tracker',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                )
            );*/

            $e20r_plot_jscript = true;
            self::enqueue_plotSW();
            $e20r_plot_jscript = false;

            wp_print_scripts( 'e20r-tracker-js' );
            wp_print_scripts( 'e20r_tracker_admin' );
        }
    }

    /**
     * Load Javascript for the Weekly Progress page/shortcode
     */
    public function has_weeklyProgress_shortcode() {

        dbg("e20rTracker::has_weeklyProgress_shortcode() -- Loading & adapting javascripts. ");
        global $e20rMeasurements, $e20rClient, $e20rMeasurementDate, $e20rArticle, $pagenow, $post;

        dbg("e20rTracker::has_weeklyProgress_shortcode() -- pagenow is '{$pagenow}'. ");

        if ( has_shortcode( $post->post_content, 'weekly_progress' ) ) {

            $e20rArticle->setId( $post->ID );

            if ( ! $e20rClient->client_loaded ) {
                $e20rClient->init();
                dbg( "e20rTracker::has_weeklyProgress_shortcode() - Have to init e20rClient class & grab data..." );
            }

            // $e20rMeasurements->init( $e20rArticle->releaseDate(), $e20rClient->clientId() );

            $this->enqueue_plotSW();

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Register scripts");

            wp_register_script( 'e20r-jquery-json', E20R_PLUGINS_URL . '/js/libraries/jquery.json.min.js', array( 'jquery' ), '0.1', true );
            wp_register_script( 'jquery.timeago', E20R_PLUGINS_URL . '/js/libraries/jquery.timeago.js', array( 'jquery' ), '0.1', true );
            wp_register_script( 'e20r-tracker-js', E20R_PLUGINS_URL . '/js/e20r-tracker.js', array( 'jquery.timeago' ), '0.1', true );
            wp_register_script( 'e20r-progress-js', E20R_PLUGINS_URL . '/js/e20r-progress.js', array( 'e20r-tracker-js' ) , '0.1', true );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Find client info");

            $lw_measurements = $e20rMeasurements->getMeasurement( 'last_week', true );

            // $userData = $e20rClient->data->info;

            if ( $e20rClient->data->info->incomplete_interview ) {
                dbg("e20rTracker::has_weeklyProgress_shortcode() - No USER DATA found in the database. Redirect to User interview page!");
            }

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Localizing progress script for use on measurement page");

            /* Load user specific settings */
            wp_localize_script( 'e20r-progress-js', 'e20r_progress',
                array(
                    'ajaxurl'   => admin_url('admin-ajax.php'),
                    'settings'     => array(
                        'article_id'   => $e20rArticle->getID(),
                        'lengthunit'   => $e20rClient->getLengthUnit(),
                        'weightunit'   => $e20rClient->getWeightUnit(),
                        'imagepath'    => E20R_PLUGINS_URL . '/images/',
                        'overrideDiff' => ( isset( $lw_measurements->id ) ? false : true )
                    ),
                    'measurements' => array(
                        'last_week' => json_encode( $lw_measurements, JSON_NUMERIC_CHECK ),
                    ),
                    'user_info'    => array(
                        'userdata'          => json_encode( $e20rClient->data->info, JSON_NUMERIC_CHECK ),
                        'progress_pictures' => '',
                        'display_birthdate' => ( empty( $e20rClient->data->info->birthdate ) ? false : true ),

                    ),
                )
            );

            dbg("e20rTracker::has_weeklyProgress_shortcode() - Loading scripts in footer of page");
            wp_enqueue_media();
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

                    if ( typeof NourishUser.birthdate === "undefined" ) {
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

            dbg("e20rTracker::enqueue_frontend_css() - Need to load CSS for e20rTracker.");
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

        if ( $e20r_plot_jscript || has_shortcode( $post->post_content, 'user_progress_info' ) ) {

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
        $intakeTableSql =
            "CREATE TABLE {$wpdb->prefix}e20r_client_info (
                    id int not null,
                    user_id int not null,
                    program_id int not null,
                    birthdate date not null,
                    program_start date not null,
                    height decimal(18,3) null,
                    heritage int null,
                    waist_circumference decimal(18,3),
                    weight decimal(18,3) null,
                    is_metric tinyint default 0,
                    is_imperial tinyint default 0,
                    is_gb_imperial tinyint default 0,
                    lengthunits varchar(20) null,
                    weightunits varchar(20) null,
                    gender varchar(1) null,
                    progress_photo_dir varchar(255) not null default 'e20r-pics/',
                    user_enc_key varchar(64) not null,
                    use_pictures tinyint default 0,
                    for_research tinyint default 0,
                    chronic_pain tinyint default 0,
                    injuries tinyint default 0,

                    primary key (id),
                    key user_id (user_id asc),
                    key programstart (program_start asc)
              )
                  {$charset_collate}
        ";

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
                    program_id int default 0,
                    primary key id ( id ),
                    key user_id ( user_id asc) )
                  {$charset_collate}
              ";
/*        // TODO: Add item_text on admin page.
        $itemsTableSql =
            "CREATE TABLE {$wpdb->prefix}e20r_checkin_items (
                    id int not null auto_increment,
                    short_name varchar(20) null,
                    program_id int null,
                    item_name varchar(50) null,
                    item_text mediumtext null,
                    startdate datetime null,
                    enddate datetime null,
                    item_order int not null default 1,
                    maxcount int null,
                primary key  ( id ) ,
                unique key shortname_UNIQUE ( short_name asc ) )
                {$charset_collate}";
*/
/*
        $businessRulesSql =
            "CREATE TABLE {$wpdb->prefix}e20r_checkin_rules (
                    id int not null auto_increment,
                    checkin_id int null,
                    success_rule mediumtext null,
                    primary key  ( id ),
                    key checkin_id ( checkin_id asc ) )
                {$charset_collate}";
*/
        /**
         *
         */
        // TODO: How do you combine Assignments (flexibility, unlimited # of boxes & questions) and
        $checkinSql =
            "CREATE TABLE {$wpdb->prefix}e20r_checkin (
                    id int not null auto_increment,
                    user_id int null,
                    program_id int null,
                    checkin_type int null,
                    checkin_date datetime null,
                    checkin_item_id int not null,
                    checkedin tinyint not null default 0,
                    primary key  (id),
                        key program_id ( program_id asc ),
                        key checkin_item_id ( checkin_item_id asc ) )
                {$charset_collate}";


        /**
         * For assignments
         */
        $assignmentQsSql =
            "CREATE TABLE {$wpdb->prefix}e20r_question (
                    id int not null auto_increment,
                    article_id int not null,
                    question text null,
                    primary key (id)
                    ) {$charset_collate}
        ";

        $assignmentAsSql =
            "CREATE TABLE {$wpdb->prefix}e20r_assignment (
                    id int not null auto_increment,
                    article_id int not null,
                    question_id int not null,
                    user_id int not null,
                    answer_date datetime null,
                    answer text null,
                    field_type enum( 'textbox', 'input', 'checkbox', 'radio' ),
                    primary key id (id),
                     key lessons (article_id asc),
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
                    back_image int default null
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
/*        dbDelta( $itemsTableSql );
        dbDelta( $businessRulesSql ); */
        dbDelta( $checkinSql );
        dbDelta( $measurementTableSql );
        dbDelta( $intakeTableSql );
        dbDelta( $assignmentAsSql );
        dbDelta( $assignmentQsSql );
/*        dbDelta( $programsTableSql );
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
//            $wpdb->prefix . 'e20r_checkin_items',
//            $wpdb->prefix . 'e20r_checkin_rules',
            $wpdb->prefix . 'e20r_checkin',
            $wpdb->prefix . 'e20r_assignment',
            $wpdb->prefix . 'e20r_question',
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

    public function e20r_tracker_programCPT() {

        $labels =  array(
            'name' => __( 'Program', 'e20rtracker'  ),
            'singular_name' => __( 'Programs', 'e20rtracker' ),
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
            'name' => __( 'Article', 'e20rtracker'  ),
            'singular_name' => __( 'Articles', 'e20rtracker' ),
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
                   'supports' => array('title', 'excerpt', 'custom-fields','author'),
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
            'name' => __( 'Girth Type', 'e20rtracker'  ),
            'singular_name' => __( 'Girth Types', 'e20rtracker' ),
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
            'name' => __( 'Exercise', 'e20rtracker'  ),
            'singular_name' => __( 'Exercises', 'e20rtracker' ),
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

    public function e20r_tracker_workoutCPT() {

        $labels =  array(
            'name' => __( 'Workout', 'e20rtracker'  ),
            'singular_name' => __( 'Workouts', 'e20rtracker' ),
            'slug' => 'e20r_workout',
            'add_new' => __( 'New Workout', 'e20rtracker' ),
            'add_new_item' => __( 'New Workout', 'e20rtracker' ),
            'edit' => __( 'Edit Workout', 'e20rtracker' ),
            'edit_item' => __( 'Edit Workout', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Workouts', 'e20rtracker' ),
            'view_item' => __( 'View This Workout', 'e20rtracker' ),
            'search_items' => __( 'Search Workouts', 'e20rtracker' ),
            'not_found' => __( 'No Workouts Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Workouts Found In Trash', 'e20rtracker' )
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
                       'slug' => apply_filters('e20r-tracker-workout-cpt-slug', 'tracker-workout'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-workout-cpt-archive-slug', 'tracker-workout')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_workout CPT: ' . $error->get_error_message);
        }
    }

    public function e20r_tracker_checkinCPT() {

        $labels =  array(
            'name' => __( 'Check-in', 'e20rtracker'  ),
            'singular_name' => __( 'Check-ins', 'e20rtracker' ),
            'slug' => 'e20r_checkins',
            'add_new' => __( 'New Check-in', 'e20rtracker' ),
            'add_new_item' => __( 'New Check-in', 'e20rtracker' ),
            'edit' => __( 'Edit Check-in', 'e20rtracker' ),
            'edit_item' => __( 'Edit Check-in', 'e20rtracker'),
            'new_item' => __( 'Add New', 'e20rtracker' ),
            'view' => __( 'View Check-ins', 'e20rtracker' ),
            'view_item' => __( 'View This Check-in', 'e20rtracker' ),
            'search_items' => __( 'Search Check-ins', 'e20rtracker' ),
            'not_found' => __( 'No Check-ins Found', 'e20rtracker' ),
            'not_found_in_trash' => __( 'No Check-ins Found In Trash', 'e20rtracker' )
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
                       'slug' => apply_filters('e20r-tracker-checkin-cpt-slug', 'tracker-checkin'),
                       'with_front' => false
                   ),
                   'has_archive' => apply_filters('e20r-tracker-checkin-cpt-archive-slug', 'tracker-checkin')
            )
        );

        if ( is_wp_error($error) ) {
            dbg('ERROR: Failed to register e20r_checkin CPT: ' . $error->get_error_message);
        }
    }
    /**
     * Configure & display the icon for the Sequence Post type (in the Dashboard)
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

        if ( function_exists('pmpro_hasMembershipLevel' ) ) {

            $ud = get_user_by( 'id', $userId );

            $notFreeMember = (! pmpro_hasMembershipLevel( 13, $userId ) );
            $notDummyUser = (! $ud->has_cap('app_dmy_users') );

            return ( $notFreeMember && $notDummyUser );
        }

        return true;
    }

}