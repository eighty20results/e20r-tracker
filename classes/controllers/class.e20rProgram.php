<?php
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

class e20rProgram extends e20rSettings {

    private $programTree = array();

    public function __construct() {

        dbg("e20rProgram::init() - Initializing Program data");
        parent::__construct( 'program', 'e20r_programs', new e20rProgramModel(), new e20rProgramView() );
    }

    /**
     * Returns the value of the active_delay setting (for articles/records) OR null if it's not configured/set
     *
     * @return int|null - A nil or integer value
     */
    public function get_active_delay() {

        if ( isset( $this->model->settings->active_delay ) ) {

            // Preserve the previous delay value
            $this->model->settings->previous_delay = $this->model->settings->active_delay;

            $value = $this->model->settings->active_delay;
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * Set the delay value to use as the active value for articles/results/assignments in this program.
     *
     * @param $value - Integer value (delay value for finding article in program )
     */
    public function set_active_delay( $value )
    {

        global $e20rTracker;

        if ( is_object( $this->model->settings ) &&
            isset( $this->model->settings->active_delay ) &&
            ( null !== $this->model->settings->active_delay ) || ( '' !== $this->model->settings->active_delay ) ) {

            dbg("e20rProgram::set_active_delay() - Saving pre-existing active_delay value: {$$this->model->settings->active_delay}");
            $this->model->settings->previous_delay = $this->model->settings->active_delay;
        }

        $this->model->settings->active_delay = $e20rTracker->sanititze($value);
    }

    /**
     * Returns the value of the previous_delay setting OR null if it's not configured/set
     *
     * @return int|null - A nil or integer value
     */
    public function get_previous_delay() {

        if ( isset( $this->model->settings->previous_delay ) ) {
            $value = $this->model->settings->previous_delay;
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * Set the value we last used value for articles/results/assignments in this program.
     *
     * @param $value - Integer value (delay value for finding article in program )
     */
    public function set_previous_delay( $value ) {

        global $e20rTracker;

        $this->model->settings->previous_delay = $e20rTracker->sanititze( $value );
    }

    /**
     * Configure the program (load settings, etc).
     *
     * @param null $programId - Optional argument containing the program ID value (integer)
     * @return bool - True = initialized and configured parameters/settings for specified program ID
     *                False = failed to init and configure parameters/settings for specified program ID
     */
    public function init( $programId = null ) {

        global $e20rTracker;

	    global $currentProgram;
	    global $current_user;

	    if ( is_user_logged_in() ) {

            if ((is_null($programId))) {

                dbg("e20rProgram::init() - Fetching program ID for user {$current_user->ID}.");
                $programId = get_user_meta($current_user->ID, 'e20r-tracker-program-id', true);
            }

            if ((!isset($currentProgram->id) ||
                (!empty($programId) && ($currentProgram->id != $programId)))
            ) {

                dbg("e20rProgram::init() - Loading program settings for {$programId}.");
                $currentProgram = $this->model->loadSettings($programId);

                $this->configure_startdate($programId, $current_user->ID);

                dbg("e20rProgram::init() - Program info has been loaded for: {$currentProgram->id}");
                return true;
            }
        }

	    $currentProgram = new stdClass();
	    $currentProgram->id = null;

	    dbg("e20rProgram::init() - No Program ID found or user not logged in!");
	    return false;
    }

    public function get_programs() {

        $program_array = array();

        $programs = $this->model->loadAllSettings('published');

        foreach( $programs as $program ) {

            $program_array[ $program->id ] = $program->title;
        }
        return $program_array;
    }

    public function get_program_name( $program_id ) {

        global $currentProgram;

        if ( $program_id != $currentProgram->id ) {
            $this->model->load_settings( $program_id );
        }

        dbg("e20rProgram::get_program_name() - Name for program with id {$program_id}: {$currentProgram->title}");
        return $currentProgram->title;
    }

    public function get_program_start( $program_id, $user_id ) {

        global $currentProgram;

        $this->configure_startdate( $program_id, $user_id );

        return $currentProgram->startdate;
    }

    public function get_program_members( $program_id ) {

        return $this->model->load_program_members( $program_id );
    }

    public function isActive( $program_shortname ) {

        $program = $this->findByName( $program_shortname );

        if ( ( $program !== false ) && ( ! in_array( $program->post_status, array( 'publish', 'private' ) ) ) ) {

            dbg("e20rProgram::isActive() - Program not found or not published");
            return false;
        }

        $now = current_time( 'timestamp' );
        $start = strtotime( $program->startdate );
        $end = strtotime( $program->enddate );

        // It's available since no start has been configured.
        if ( ! $start ) {
            dbg("e20rProgram::isActive() - Start value not set, program is available");
            return true;
        }

        // It's available since no end-time has been configured and it's after the starttime
        if ( ( ! $end )  && ( $now >= $start ) ) {
            dbg("e20rProgram::isActive() - It's after the start date, and end value not set, program is available");
            return true;
        }

        if ( ( $now >= $start ) && ( $now <= $end ) ) {
            dbg("e20rProgram::isActive() - Currently somewhere between start and end for the program. it's available ");
            return true;
        }

        return false;
    }

    public function setProgram( $program_id ) {

        global $currentProgram;

        if ( !isset( $currentProgram->id) || ( $currentProgram->id !== $program_id ) ) {

            dbg( "e20rProgram::loadProgram() - Need to init the program object");
            $this->model->loadSettings($program_id);
        }
    }

    public function getPeerPrograms( $programId = null ) {

        if ( is_null( $programId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $programId = $post->post_parent;
        }

        $programs = new WP_Query( array(
            'post_type' => 'page',
            'post_parent' => $programId,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'ids',
        ) );

        $this->programTree = array(
            'pages' => $programs->posts,
        );

        foreach ( $programs->posts as $k => $v ) {

            if ( $v == get_the_ID() ) {

                if( isset( $programs->posts[$k-1] ) ) {

                    $this->programTree['prev'] = $programs->posts[ $k - 1 ];
                }

                if( isset( $programs->posts[$k+1] ) ) {

                    $this->programTree['next'] = $programs->posts[ $k + 1 ];
                }
            }
        }

        wp_reset_postdata();

        return $this->programTree;
    }

    public function getProgramList() {

	    global $post;

	    $list = $this->model->loadAllSettings();

	    dbg("e20rProgram::getProgramList() - Content of list being returned ");

        return $list;
    }

	public function get_welcomeSurveyLink( $userId ) {

		global $currentProgram;

		$this->loadProgram( $userId );

		$link = get_permalink( $currentProgram->intake_form );

		dbg("e20rProgram::get_welcomeSurveyLink(): Link: {$link}");

		return $link;
	}

    public function getProgramIdForUser( $userId = 0, $articleId = null ) {

	    global $currentProgram;

        global $e20rArticle;

        $user_program = false;

        if ( 0 != $userId ) {

            $user_program = get_user_meta( $userId, 'e20r-tracker-program-id', true);
        }
        else {
            return;
        }

        if ( !empty( $currentProgram->id ) && ( $currentProgram->id == $user_program ) ) {

            return $currentProgram->id;
        }

	    if ( !isset( $currentProgram->id ) || ( ( false !== $user_program ) && ( isset( $currentProgram->id ) && ( $currentProgram->id != $user_program ) ) ) ) {

		    dbg("e20rProgram::getProgramIdForUser() - currentProgram->id isn't configured or its different from what this user ({$userId}) needs it to be ({$user_program}).");

            dbg("e20rProgram::getProgramIdForUser() - currentProgram being configured for {$userId} -> {$user_program}.");
            $this->model->loadSettings( $user_program );

            $this->configure_startdate( $user_program, $userId );

		    if ( empty( $currentProgram->id ) ) {

			    dbg("e20rProgram::getProgramIdForUser() - currentProgram getting set to default values");
			    $this->init();
		    }
        }

        dbg("e20rProgram::getProgramIdForUser() - Loaded program ID ($currentProgram->id) for user {$userId}");
	    return ( isset( $currentProgram->id ) ? $currentProgram->id : false );
    }

    private function configure_startdate( $program_id, $userId ) {

        global $currentProgram;

        dbg("e20rProgram::configure_startdate() - Defined program startdate value: {$currentProgram->startdate}");

        if ( ( $currentProgram->id == $program_id ) && ( function_exists( 'pmpro_getMemberStartdate' ) ) ) {

            dbg( "e20rProgram::configure_startdate() - Using PMPro's member startdate for user ID {$userId}");

            if ( 0 == ( $startTS = apply_filters( "e20r-tracker-program-start-timestamp", pmpro_getMemberStartdate( $userId ) ) ) ) {

                dbg("e20rProgram::configure_startdate() - No start timestamp found in membership system. Setting to 'today'");
                $startTS = current_time('timestamp');
            }

            $currentProgram->startdate = date_i18n( 'Y-m-d', $startTS );
            dbg("e20rProgram::configure_startdate() - Startdate set to the member's start date for the program: {$currentProgram->startdate} for {$userId}");
        }
    }

    public function setProgramForUser( $user_id, $membership_id ) {

        global $e20rTracker;
        global $currentProgram;

        dbg("e20rTracker::setProgramForUser() - Called from: " . $e20rTracker->whoCalledMe() );
        dbg("e20rProgram::setProgramForUser() - Locating programs from membership id # {$membership_id} on behalf of user {$user_id}");

        if ( false === ( $pId = $this->model->findByMembershipId( $membership_id ) ) ) {

            dbg("e20rProgram::setProgramForUser() - ERROR: No program IDs returned!");

            $addr = get_option( 'admin_email' );

            $subj = "Error: Cannot locate program definition for {$membership_id}";

            $msg = "Membership Level {$membership_id} isn't associated with a published program ID.\n";
            $msg .= "Please correct the program definitions for the {$membership_id} membership level\n";

            wp_mail( $addr, $subj, $msg);

        }

        dbg("e20rProgram::setProgramForUser() - Returned groups/membership IDs: ");
        dbg($pId);

        if ( is_array( $pId ) ) {

            dbg("e20rProgram::setProgramForUser() - ERROR: More than one program ID associated with membership!");
            $addr = get_option( 'admin_email' );

            $subj = "Error: Unexpected program definition(s)";

            $msg = "Membership Level {$membership_id} is associated with more than a single program ID.\n";
            $msg .= "Please correct the program definitions for the following programs:\n\n";

            foreach ( $pId as $id ) {

                $msg .= get_the_title($id) . "({$id})\n";
            }

            wp_mail( $addr, $subj, $msg);
            return;
        }

        update_user_meta( $user_id, 'e20r-tracker-program-id', $pId );

        if ( $pId ===  get_user_meta( $user_id, 'e20r-tracker-program-id', true ) ) {

            $addr = get_option( 'admin_email' );

            $subj = "Error: Unable to set program for user ID";

            $msg = "Membership Level {$membership_id} could not be configured for user ID {$user_id}.\n";
            $msg .= "Please update the profile for user with ID {$user_id} in the admin panel.\n";

            wp_mail( $addr, $subj, $msg);
        }

        dbg("e20rProgram::setProgramForUser() - Testing whether to add user to program list");

        if ( !isset( $currentProgram->id ) || ( $pId != $currentProgram->id ) ) {

            $this->init($pId);
        }

        $currentProgram->users[] = $user_id;

        if ( !in_array( $user_id, $currentProgram->users ) ) {

            dbg("e20rProgram::setProgramForUser() - Adding user to the program 'users' list");
            $this->model->set('users', $currentProgram->users, $currentProgram->id);
        }

        return;
    }

    private function loadProgram( $userId = 0 ) {

		global $currentProgram;

		if ( !isset( $currentProgram->id ) || ( ! in_array( $userId, $currentProgram->users ) ) ) {

			if ( is_user_logged_in() && ( $userId != 0 ) ) {

                dbg( "e20rProgram::loadProgram() - Loading usermeta for ID {$userId}");
				$programId = get_user_meta( $userId, 'e20r-tracker-program-id', true );

				if ( ( false !== $programId ) &&
                    ( !isset( $currentProgram->id) || ( $currentProgram->id !== $programId ) ) ) {

                    dbg( "e20rProgram::loadProgram() - Need to init the program object");
                    $this->model->loadSettings($programId);
				}
			}

            $this->configure_startdate( $programId, $userId );
		}

        dbg( "e20rProgram::loadProgram() - User's programID: " . isset( $currentProgram->id ) ? $currentProgram->id : 'null' );
	}

    /**
     * Action Hook to add the E20R Tracker user specific settings (like adding a program for the user)
     *
     * @param $user -- WP_User object
     */
    public function selectProgramForUser( $user ) {

        global $e20rClient;
        global $currentClient;

        $coach_id = null;

        dbg("e20rProgram::selectProgramForUser() - user: {$user->ID}");

        $programlist = $this->getProgramList();
        $activeProgram = $this->getProgramIdForUser( $user->ID, null );

        dbg("e20rProgram::selectProgramForUser() - Loading coach for the specific user ({$user->ID})");
        $coach_id = $e20rClient->get_coach( $user->ID, $activeProgram );

        if ( empty( $coach_id ) && ( false !== $activeProgram ) ) {

            dbg("e20rProgram::selectProgramForUser() - No coach found for user {$user->ID}, but since they're members of a program we'll try to assign one automatically.");
            $e20rClient->get_client_info( $user->ID );

            if ( isset( $currentClient->loadedDefaults ) && ( false !== $currentClient->loadedDefaults ) ) {

                dbg("e20rProgram::selectProgramForUser() - Didn't have a coach but is member of a program so assigning a coach to user {$user->ID} with gender {$currentClient->gender}");
                $id = $e20rClient->assign_coach( $user->ID, $currentClient->gender );
                $u = get_user_by( 'ID', $id );
                $coach_id = array( $id => $u->display_name );
            }
            else {
                dbg("e20rProgram::selectProgramForUser() - User hasn't completed their intake interview so can't select coach automatically");
                $coach_id = array( -1 => 'Unassigned' );
            }
        }

        dbg("e20rProgram::selectProgramForUser() - Located pre-assigned(?) coach for user {$user->ID}");

        dbg("e20rProgram::selectProgramForUser() - Loading all coaches");
        $coachList = $e20rClient->get_coach();

        dbg("e20rProgram::selectProgramForUser() - Active Program: {$activeProgram}");

        echo $this->view->profile_view_client_settings( $programlist, $activeProgram, $coachList, $coach_id );
    }

    public function incompleteIntakeForm() {

        global $currentProgram;
        global $current_user;

        if ( !isset( $currentProgram->id ) ) {

            dbg("e20rProgram::incompleteIntakeForm() - Loading program ID");
            $this->getProgramIdForUser( $current_user->ID );
        }

        if ( !empty( $currentProgram->incomplete_intake_form_page ) ) {

            $post = get_post( $currentProgram->incomplete_intake_form_page );
            $content = apply_filters('the_content', $post->post_content);
        }
        else {
            $post = get_post( $currentProgram->intake_form );

            $default_text = sprintf(
                __('<p>Please complete %s (<a href="%s" target="_blank">link</a>)</p>', "e20rtracker"),
                    $post->post_title,
                    get_permalink( $post->ID )
            );

            $content = apply_filters('e20r_tracker_default_incomplete_form_text', $default_text );
        }

        wp_reset_postdata();
        return $content;
    }

    /**
     * Calculates the startdate (as a 'seconds since epoch') value and returns it to the calling function.
     *
     * Currently supports:
     *      Internal usermeta value. (e20r-program-startdate => 'When this user started the program'
     *      Paid Memberships Pro.
     *
     * @param $userId - ID of user to find the startdate for.
     *
     * @return int|mixed - Timestamp (seconds since UNIX epoch
     */
    public function startdate( $userId, $program_id = null, $membership = true) {

	    global $currentProgram;

        if ( ( !empty( $program_id ) && !empty( $currentProgram->id ) && ( $currentProgram->id === false ) ) ||
                ( (!empty( $program_id ) ) && ( $program_id != $currentProgram->id ) ) ) {

            dbg( "e20rProgram::startdate() - Loading new program {$program_id} in place of {$currentProgram->id}" );
            $this->model->loadSettings( $program_id );
        }

        if ( ( empty($program_id) ) ) {

            // dbg( "e20rProgram::startdate() - Loading program for user with ID: {$userId}" );
            $this->getProgramIdForUser( $userId );
        }

        dbg("e20rProgram::startdate() - Using startdate as configured for user ({$userId}) in program {$currentProgram->id}: {$currentProgram->startdate}");
        // dbg($currentProgram);

        // This is a date of the 'Y-m-d' PHP format. (eg 2015-01-01).
        return strtotime( $currentProgram->startdate );
    }

    public function get_coaches_for_program( $program_id ) {

        $program = $this->init( $program_id );

        $coach_ids  = array_merge( $program->male_coaches, $program->female_coaches );

        $coaches = array();

        foreach( $coach_ids as $id ) {

            $tmp = get_user_by( $id );
            $coaches[$id] = $tmp->display_name;
        }

        return $coaches;
    }

    /**
     * @param $userId - The USER id
     *
     * @return bool -- DIE()s if we're unable to save the settings.
     */
    public function updateProgramForUser( $userId ) {

        global $currentProgram;
        global $e20rClient;

        if ( ! current_user_can( 'edit_user', $userId ) ) {
            return false;
        }

        $programId = isset( $_POST['e20r-tracker-user-program'] ) ? intval( $_POST['e20r-tracker-user-program'] ) : 0;
        $coachId = isset( $_POST['e20r-tracker-user-coach_id'] ) ? intval( $_POST['e20r-tracker-user-coach_id'] ) : 0;

        dbg("e20rProgram::updateProgramForUser() - Setting program ID = {$programId} for user with ID of {$userId}");

        if ( $programId != 0 ) {

            update_user_meta( $userId, 'e20r-tracker-program-id', $programId );

            if ( get_user_meta( $userId, 'e20r-tracker-program-id', true ) != $programId ) {

                wp_die( 'Unable to save the program for this user' );
            }

            dbg("e20rProgram::updateProgramForUser() - Testing whether to add user to program list");

            if ( !isset( $currentProgram->id ) || ( $programId != $currentProgram->id ) ) {

                $this->init($programId);
            }

            $currentProgram->users[] = $userId;

            if ( !in_array( $userId, $currentProgram->users ) ) {
                dbg("e20rProgram::updateProgramForUser() - Adding user to the program 'users' list");
                $this->model->set( 'users', $currentProgram->users, $currentProgram->id);
            }
        }

        if ( $coachId != 0 ) {

            dbg("e20rProgram::updateProgramForUser() - Assigning & saving coach {$coachId} for user with ID {$userId}");

            $e20rClient->assign_client_to_coach( $programId, $coachId, $userId );

            update_user_meta( $userId, 'e20r-tracker-user-coach_id', $coachId );

            if ( get_user_meta( $userId, 'e20r-tracker-user-coach_id', true ) != $coachId ) {

                wp_die("Unable to save the assigned coach for this user");
            }
        }
    }

    protected function loadDripFeed( $feedId ) {

        if ( $feedId == 'all' ) {
            $id = null;
        }
        else {
            $id = $feedId;
        }

        if ( class_exists( 'PMProSequence' ) ) {

            return PMProSequence::all_sequences('publish');
        }

        return false;
    }

    public function getValue( $fieldName = 'id' ) {

        return $this->model->getFieldValue( $fieldName );
    }

} 