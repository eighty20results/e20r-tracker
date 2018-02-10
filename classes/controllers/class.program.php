<?php
namespace E20R\Tracker\Controllers;
/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

use E20R\Sequences\Sequence;
use E20R\Sequences\Data\Model;

use E20R\Tracker\Models\Program_Model;
use E20R\Tracker\Views\Program_View;

class Program extends Settings {

    private $programTree = array();

    private static $instance = null;

    public function __construct() {

        E20R_Tracker::dbg("Program::__construct() - Initializing Program data");
        parent::__construct( 'program', 'e20r_programs', new Program_Model(), new Program_View() );
    }

	/**
	 * @return Program
	 */
	static function getInstance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
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
        $Tracker = Tracker::getInstance();

        if ( is_object( $this->model->settings ) &&
            isset( $this->model->settings->active_delay ) &&
            ( null !== $this->model->settings->active_delay ) || ( '' !== $this->model->settings->active_delay ) ) {

            E20R_Tracker::dbg("Program::set_active_delay() - Saving pre-existing active_delay value: {$this->model->settings->active_delay}");
            $this->model->settings->previous_delay = $this->model->settings->active_delay;
        }

        $this->model->settings->active_delay = $Tracker->sanitize($value);
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

        $Tracker = Tracker::getInstance();

        $this->model->settings->previous_delay = $Tracker->sanitize( $value );
    }

    /**
     * Configure the program (load settings, etc).
     *
     * @param null $programId - Optional argument containing the program ID value (integer)
     * @param null|int      $delay  -   Delay value for program start
     * @return bool - True = initialized and configured parameters/settings for specified program ID
     *                False = failed to init and configure parameters/settings for specified program ID
     */
    public function init( $programId = null, $delay = null ) {

	    global $currentProgram;
	    global $current_user;

        E20R_Tracker::dbg("Program::init() - Argument: " . (empty($programId) ? 'Null' : $programId));
        E20R_Tracker::dbg("Program::init() - Current program value: " . (empty($currentProgram->id) ? 'Null' : $currentProgram->id));

        if ( isset($currentProgram->id) && !empty($programId) && ($currentProgram->id == $programId)) {

            E20R_Tracker::dbg("Program::init() - Program {$currentProgram->id} was loaded already");
            return true;
        }

        if (is_null($programId)) {

            E20R_Tracker::dbg("Program::init() - Grabbing program ID for user {$current_user->ID} from DB.");
            $programId = get_user_meta($current_user->ID, 'e20r-tracker-program-id', true);
        }

        if ( !empty($programId) &&
                ((!isset($currentProgram->id)) || ($currentProgram->id != $programId))) {

            E20R_Tracker::dbg("Program::init() - Loading program settings for {$programId}.");
            $currentProgram = $this->model->loadSettings($programId);

            $this->configure_startdate($programId, $current_user->ID);

            E20R_Tracker::dbg("Program::init() - Program info has been loaded for: {$currentProgram->id}");
            return true;
        }

	    E20R_Tracker::dbg("Program::init() - No Program ID found or user not logged in!");
	    return false;
    }

    public function get_programs() {

        $program_array = array();

        $programs = $this->model->loadAllSettings('published');

        if (! empty($programs)) {

            foreach ( $programs as $program ) {

                $program_array[ $program->id ] = $program->title;
            }
        }

        return $program_array;
    }

    public function get_program_name( $program_id ) {

        global $currentProgram;

        if ( $program_id != $currentProgram->id ) {
            $this->model->loadSettings( $program_id );
        }

        E20R_Tracker::dbg("Program::get_program_name() - Name for program with id {$program_id}: {$currentProgram->title}");
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

            E20R_Tracker::dbg("Program::isActive() - Program not found or not published");
            return false;
        }

        $now = current_time( 'timestamp' );
        $start = strtotime( $program->startdate );
        $end = strtotime( $program->enddate );

        // It's available since no start has been configured.
        if ( ! $start ) {
            E20R_Tracker::dbg("Program::isActive() - Start value not set, program is available");
            return true;
        }

        // It's available since no end-time has been configured and it's after the starttime
        if ( ( ! $end )  && ( $now >= $start ) ) {
            E20R_Tracker::dbg("Program::isActive() - It's after the start date, and end value not set, program is available");
            return true;
        }

        if ( ( $now >= $start ) && ( $now <= $end ) ) {
            E20R_Tracker::dbg("Program::isActive() - Currently somewhere between start and end for the program. it's available ");
            return true;
        }

        return false;
    }

    public function setProgram( $program_id ) {

        global $currentProgram;

        if ( !isset( $currentProgram->id) || ( $currentProgram->id !== $program_id ) ) {

            E20R_Tracker::dbg( "Program::loadProgram() - Need to init the program object");
            $this->model->loadSettings($program_id);
        }
    }

    public function getPeerPrograms( $programId = null ) {

        if ( is_null( $programId ) ) {

            global $post;
            // Use the parent value for the current post to get all of its peers.
            $programId = $post->post_parent;
        }

        $programs = new \WP_Query( array(
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

	    $list = $this->model->loadAllSettings();

	    E20R_Tracker::dbg("Program::getProgramList() - Content of list being returned ");

        return $list;
    }

	public function get_welcomeSurveyLink( $userId ) {

		global $currentProgram;

		$this->loadProgram( $userId );

		$link = get_permalink( $currentProgram->intake_form );

		E20R_Tracker::dbg("Program::get_welcomeSurveyLink(): Link: {$link}");

		return $link;
	}

    public function getProgramIdForUser( $userId = 0, $articleId = null ) {

	    global $currentProgram;

        $user_program = false;

	    E20R_Tracker::dbg("Program::getProgramIdForUser() - Processing user ID: {$userId}");

        if ( 0 < $userId ) {

            $user_program = get_user_meta( $userId, 'e20r-tracker-program-id', true);
        }
        else {
            return $user_program;
        }
        
        if ( !empty( $currentProgram->id ) && !empty( $user_program ) && ( $currentProgram->id == $user_program ) ) {
	        E20R_Tracker::dbg( "Program::getProgramIdForUser() - User program and current program id are the same: {$currentProgram->id} vs {$user_program}" );
	        $this->configure_startdate( $user_program, $userId );
            return $currentProgram->id;
        }
        
	    if ( empty( $currentProgram->id ) || ( !empty( $user_program ) && ( !empty( $currentProgram->id ) && ( $currentProgram->id != $user_program ) ) ) ) {

		    E20R_Tracker::dbg("Program::getProgramIdForUser() - currentProgram->id isn't configured or its different from what this user ({$userId}) needs it to be ({$user_program}).");

            if (empty($user_program)) {

                if (function_exists('pmpro_getMembershipLevelForUser')) {

                    // locate the user's program.
                    $level = pmpro_getMembershipLevelForUser($userId);

	                if ( !isset( $level->id ) ) {
	                	return false;
	                }

                    $user_program = $this->model->findByMembershipId($level->id);

                    if (is_null($user_program)) {

                        E20R_Tracker::dbg("Unable to locate program ID for user {$userId}");
                        return false;
                    }
                } else {
                    E20R_Tracker::dbg("Error loading program information for {$userId}");
                }
            }

            E20R_Tracker::dbg("Program::getProgramIdForUser() - currentProgram being configured for {$userId} -> {$user_program}.");
            $this->model->loadSettings( $user_program );

            $this->configure_startdate( $user_program, $userId );

		    if ( empty( $currentProgram->id ) ) {

			    E20R_Tracker::dbg("Program::getProgramIdForUser() - currentProgram getting set to default values");
			    $this->init();
		    }
        }

        E20R_Tracker::dbg("Program::getProgramIdForUser() - Loaded program ID ($currentProgram->id) for user {$userId}");
	    return ( isset( $currentProgram->id ) ? $currentProgram->id : false );
    }

    /**
     * Set the startdate for the program (use the user's sign-up date, unless they signed up before the program started..)
     * @param       integer         $program_id       - The program ID
     * @param       integer         $userId           - The user's ID for whom we're trying to set the startdate
     *
     * Implicitly returns the startdate via the $currentProgram object
     */
    public function configure_startdate( $program_id, $userId ) {

        global $currentProgram;
        global $current_user;

        $startTS = null;

        E20R_Tracker::dbg("Program::configure_startdate() - Current program startdate value: {$currentProgram->startdate} for program ID {$program_id}");

        if ( is_admin() && ( $userId == $current_user->ID ) && ( defined('DOING_AJAX') && false === DOING_AJAX ) ) {
            E20R_Tracker::dbg("Program::configure_startdate() - user ID ({$userId}) matches current logged in user ({$current_user->ID}) AND we're in the admin UI");
            return;
        }

        $pgm_startdate = $currentProgram->startdate;

        if (function_exists( 'pmpro_getMemberStartdate' ) && !empty($current_user->ID)) {
        	
            $startTS = pmpro_getMemberStartdate( $userId );
	        E20R_Tracker::dbg( "Program::configure_startdate() - Finding PMPro's member startdate for user ID {$userId}: {$startTS}");
        }

        $startTS = apply_filters( "e20r-tracker-program-start-timestamp", $startTS);

        if (empty( $startTS )) {
            E20R_Tracker::dbg("Program::configure_startdate() - No start timestamp found in membership system. Setting to 'today' (right now)");
            $startTS = current_time('timestamp');
        }

        $user_startdate = date_i18n( 'Y-m-d', $startTS );

        if ( !empty( $currentProgram->startdate ) && $user_startdate < $pgm_startdate ) {
            $currentProgram->startdate = $user_startdate;
            E20R_Tracker::dbg("Program::configure_startdate() - Using user date as the start: {$currentProgram->startdate} for {$userId}");

        } else {
            $currentProgram->startdate = $pgm_startdate;
            E20R_Tracker::dbg("Program::configure_startdate() - Using program date as the start: {$currentProgram->startdate} for {$userId}");

        }

    }
    
    public function setProgramForUser( $startdate, $user_id, $level_obj ) {

        $Tracker = Tracker::getInstance();
        global $currentProgram;

        // We only need the membership ID value to find the program (if it's defgined.
        if (!empty($level_obj->id))
        {
            $membership_id = $level_obj->id;
        }
        else
        {
            return $startdate;
        }


        E20R_Tracker::dbg("Program::setProgramForUser() - Called from: " . $Tracker->whoCalledMe() );
        E20R_Tracker::dbg("Program::setProgramForUser() - Locating programs from membership id # {$membership_id} on behalf of user {$user_id}");

        if ( false === ( $pId = $this->model->findByMembershipId( $membership_id ) ) ) {

            E20R_Tracker::dbg("Program::setProgramForUser() - ERROR: No program IDs returned!");

            $addr = get_option( 'admin_email' );

            $subj = "Error: Cannot locate program definition for the '{$level_obj->name}'' membership level (ID: {$membership_id})";

            $msg = "Membership Level '{$level_obj->name}' (ID: {$membership_id}) does NOT appear to be associated with a published program.\n";
            $msg .= "Please correct the program definitions for the '{$level_obj->name}' (ID: {$membership_id}) membership level\n";

            wp_mail( $addr, $subj, $msg);
            return $startdate;

        }

        E20R_Tracker::dbg("Program::setProgramForUser() - Returned groups/membership IDs: ");
        E20R_Tracker::dbg($pId);

        if ( is_array( $pId ) ) {

            E20R_Tracker::dbg("Program::setProgramForUser() - ERROR: More than one program ID associated with membership!");
            $addr = get_option( 'admin_email' );

            $subj = "Error: Unexpected program definition(s)";

            $msg = "Membership Level {$membership_id} is associated with more than a single program ID.\n";
            $msg .= "Please correct the program definitions for the following programs:\n\n";

            foreach ( $pId as $id ) {

                $msg .= get_the_title($id) . "({$id})\n";
            }

            wp_mail( $addr, $subj, $msg);
            return $startdate;
        }

        update_user_meta( $user_id, 'e20r-tracker-program-id', $pId );

        if ( $pId !==  get_user_meta( $user_id, 'e20r-tracker-program-id', true ) ) {

            $addr = get_option( 'admin_email' );

            $subj = "Error: Unable to set program for user ID";

            $msg = "Membership Level '{$level_obj->name}' (ID: {$membership_id}) could not be configured for user ID {$user_id}.\n";
            $msg .= "Please update the profile for user with ID {$user_id} in the admin panel.\n";

            wp_mail( $addr, $subj, $msg);
            return $startdate;
        }

        E20R_Tracker::dbg("Program::setProgramForUser() - Testing whether to add user to program list");

        if ( empty( $currentProgram->id ) || ( $pId != $currentProgram->id ) ) {

            E20R_Tracker::dbg("Program::setProgramForUser() - Configure program ({$pId}) for new user/member");
            $this->model->loadSettings($pId);
            $startTS = $this->startdate($user_id, $pId, false);

            if ( false !== update_user_meta( $user_id, 'e20r-tracker-program-startdate', date( 'Y-m-d', $startTS ) ) ) {

                $startdate = date_i18n('Y-m-d', $startTS) . " 00:00:00";
            }
        }

        $currentProgram->users[] = $user_id;

        if ( !in_array( $user_id, $currentProgram->users ) ) {

            E20R_Tracker::dbg("Program::setProgramForUser() - Adding user to the program 'users' list");
            $this->model->set('users', $currentProgram->users, $currentProgram->id);
        }

        return $startdate;
    }

    private function loadProgram( $userId = 0 ) {

		global $currentProgram;

		if ( !isset( $currentProgram->id ) || ( ! in_array( $userId, $currentProgram->users ) ) ) {

			if ( is_user_logged_in() && ( $userId != 0 ) ) {

                E20R_Tracker::dbg( "Program::loadProgram() - Loading usermeta for ID {$userId}");
				$programId = get_user_meta( $userId, 'e20r-tracker-program-id', true );

				if ( ( false !== $programId ) &&
                    ( !isset( $currentProgram->id) || ( $currentProgram->id !== $programId ) ) ) {

                    E20R_Tracker::dbg( "Program::loadProgram() - Need to init the program object");
                    $this->model->loadSettings($programId);
				}
			}

            $this->configure_startdate( $programId, $userId );
		}

        E20R_Tracker::dbg( "Program::loadProgram() - User's programID: " . isset( $currentProgram->id ) ? $currentProgram->id : 'null' );
	}

    /**
     * Action Hook to add the E20R Tracker user specific settings (like adding a program for the user)
     *
     * @param $user -- \WP_User object
     */
    public function selectProgramForUser( $user ) {

        $Client = Client::getInstance();
        global $currentClient;

        $coach_id = null;

        E20R_Tracker::dbg("Program::selectProgramForUser() - user: {$user->ID}");

        $programlist = $this->getProgramList();
        $activeProgram = $this->getProgramIdForUser( $user->ID, null );

        E20R_Tracker::dbg("Program::selectProgramForUser() - Loading coach for the specific user ({$user->ID})");
        $coach_id = $Client->get_coach( $user->ID, $activeProgram );

        if ( empty( $coach_id ) && ( false !== $activeProgram ) ) {

            E20R_Tracker::dbg("Program::selectProgramForUser() - No coach found for user {$user->ID}, but since they're members of a program we'll try to assign one automatically.");
            $Client->get_client_info( $user->ID );

            if ( isset( $currentClient->loadedDefaults ) && ( false !== $currentClient->loadedDefaults ) ) {

                E20R_Tracker::dbg("Program::selectProgramForUser() - Didn't have a coach but is member of a program so assigning a coach to user {$user->ID} with gender {$currentClient->gender}");
                $id = $Client->assign_coach( $user->ID, $currentClient->gender );
                $u = get_user_by( 'id', $id );
                $coach_id = array( $id => $u->display_name );
            }
            else {
                E20R_Tracker::dbg("Program::selectProgramForUser() - User hasn't completed their intake interview so can't select coach automatically");
                $coach_id = array( -1 => 'Unassigned' );
            }
        }

        E20R_Tracker::dbg("Program::selectProgramForUser() - Located pre-assigned(?) coach for user {$user->ID}");

        E20R_Tracker::dbg("Program::selectProgramForUser() - Loading all coaches");
        $coachList = $Client->get_coach();

        E20R_Tracker::dbg("Program::selectProgramForUser() - Active Program: {$activeProgram}");

        echo $this->view->profile_view_client_settings( $programlist, $activeProgram, $coachList, $coach_id, $user );
    }

    public function incompleteIntakeForm() {

        global $currentProgram;
        global $current_user;

        if ( !isset( $currentProgram->id ) ) {

            E20R_Tracker::dbg("Program::incompleteIntakeForm() - Loading program ID");
            $this->getProgramIdForUser( $current_user->ID );
        }

        if ( !empty( $currentProgram->incomplete_intake_form_page ) ) {

            $post = get_post( $currentProgram->incomplete_intake_form_page );
            $content = apply_filters('the_content', $post->post_content);
        }
        else {
            $post = get_post( $currentProgram->intake_form );

            $default_text = sprintf(
                __('<p>Please complete %s (<a href="%s" target="_blank">link</a>)</p>', "e20r-tracker"),
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
     * @param int $userId - ID of user to find the startdate for.
     * @param int $program_id
     * @param bool $membership
     *
     * @return int|mixed - Timestamp (seconds since UNIX epoch
     */
    public function startdate( $userId, $program_id = null, $membership = true) {

	    global $currentProgram;

        if ( ( empty($program_id) ) ) {

            E20R_Tracker::dbg( "Program::startdate() - Loading program for user with ID: {$userId}" );
            $program_id = $this->getProgramIdForUser( $userId );
        }

        if ( ( !empty( $program_id ) && !empty( $currentProgram->id ) && ( $currentProgram->id === false ) ) ||
                ( (!empty( $program_id ) ) && ( $program_id != $currentProgram->id ) ) ) {

            E20R_Tracker::dbg( "Program::startdate() - Loading new program {$program_id} in place of {$currentProgram->id}" );
            $this->model->loadSettings( $program_id );
        }

        // E20R_Tracker::dbg("Program::startdate() - Using startdate as configured for user ({$userId}) in program {$currentProgram->id}: {$currentProgram->startdate}");
        // E20R_Tracker::dbg($currentProgram);

        // This is a date of the 'Y-m-d' PHP format. (eg 2015-01-01).
        return !empty($currentProgram->startdate) ? strtotime( $currentProgram->startdate ) : current_time('timestamp' );
    }

    public function set_startdate_for_user( $ts, $user_id, $level )
    {
        $program_id = $this->getProgramIdForUser( $user_id );
        $sd = $this->startdate($user_id, $program_id );

        if (empty($sd))
        {
            if (!empty($level->startdate))
            {
                $sd = $level->startdate;
            }
        }

        $start_date = date_i18n('Y-m-d', $sd) . " 00:00:00";

        E20R_Tracker::dbg("Program::set_startdate_for_user() - Using startdate of: {$start_date} ({$sd} vs {$ts}) for {$user_id} in program {$program_id}");
        return $start_date;
    }

    public function get_coaches_for_program( $program_id ) {

        $program = $this->init( $program_id );

        $coach_ids  = array_merge( $program->male_coaches, $program->female_coaches );

        $coaches = array();

        foreach( $coach_ids as $id ) {

            $tmp = get_user_by( 'id', $id );
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
        
        $Client = Client::getInstance();
        $Tracker = Tracker::getInstance();

        if ( ! current_user_can( 'edit_user', $userId ) ) {
            return false;
        }

        $programId = isset( $_POST['e20r-tracker-user-program'] ) ? intval( $_POST['e20r-tracker-user-program'] ) : 0;
        $coachId = isset( $_POST['e20r-tracker-user-coach_id'] ) ? intval( $_POST['e20r-tracker-user-coach_id'] ) : 0;
        $ex_level = isset( $_POST['e20r-tracker-user-assigned_role'] ) ? $Tracker->sanitize( $_POST['e20r-tracker-user-assigned_role'] ) : 0;

        E20R_Tracker::dbg("Program::updateProgramForUser() - Setting program ID = {$programId} for user with ID of {$userId}");

        if ( 0 !== $programId ) {

            update_user_meta( $userId, 'e20r-tracker-program-id', $programId );

            if ( get_user_meta( $userId, 'e20r-tracker-program-id', true ) != $programId ) {

                wp_die( __( 'Unable to save the program information for this user', 'e20r-tracker' ) );
            }

            E20R_Tracker::dbg("Program::updateProgramForUser() - Testing whether to add user to program list");

            if ( !isset( $currentProgram->id ) || ( $programId != $currentProgram->id ) ) {

                $this->init($programId);
            }

            $currentProgram->users[] = $userId;

            if ( !in_array( $userId, $currentProgram->users ) ) {
                E20R_Tracker::dbg("Program::updateProgramForUser() - Adding user to the program 'users' list");
                $this->model->set( 'users', $currentProgram->users, $currentProgram->id);
            }
        }

        if ( 0 !== $coachId ) {

            E20R_Tracker::dbg("Program::updateProgramForUser() - Assigning & saving coach {$coachId} for user with ID {$userId}");

            $Client->assign_client_to_coach( $programId, $coachId, $userId );

            update_user_meta( $userId, 'e20r-tracker-user-coach_id', $coachId );

            if ( get_user_meta( $userId, 'e20r-tracker-user-coach_id', true ) != $coachId ) {

                wp_die("Unable to save the assigned coach for this user");
            }
        }

        // Add role for user if it's not already added
        if (0 !== $ex_level ) {

            $user = new \WP_User( $userId );

            if ( !in_array( $ex_level, $user->roles )) {
                E20R_Tracker::dbg("Program::updateProgramForUser() - Adding {$ex_level} to user {$userId}");
                $user->add_role($ex_level);
            }
        }
    }
	
	/**
	 * Return the specified sequence ID (or all configured sequences)
	 *
	 * @param int $feedId
	 *
	 * @return mixed
	 */
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

        if (class_exists( 'E20R\Sequences\Sequence\Controller')) {
            return Sequence\Controller::all_sequences('publish');
        }
	
	    if (class_exists( 'E20R\Sequences\Sequence\Sequence_Controller')) {
		    return Sequence\Sequence_Controller::all_sequences('publish');
	    }
	
	    if ( class_exists( 'E20R\Sequences\Data\Model')) {
        	return Model::all_sequences('publish');
	    }
	    return false;
    }

    public function getValue( $programId, $fieldName = 'id' ) {

        return $this->model->getSetting( $programId, $fieldName );
    }

} 