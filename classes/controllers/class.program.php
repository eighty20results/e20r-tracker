<?php
/*
    Copyright 2015-2018 Thomas Sjolshagen / Wicked Strong Chicks, LLC (info@eighty20results.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace E20R\Tracker\Controllers;

use E20R\Sequences\Sequence;
use E20R\Sequences\Data\Model;
use E20R\Utilities\Utilities;
use E20R\Tracker\Models\Program_Model;
use E20R\Tracker\Views\Program_View;

/**
 * Class Program - Settings controller for the e20r_program post type
 *
 * @package E20R\Tracker\Controllers
 */
class Program extends Settings {
	
	/**
	 * @var null|Program
	 */
	private static $instance = null;
	/**
	 * @var array
	 */
	private $programTree = array();
	/**
	 * @var array
	 */
	private $male_coaches = array();
	/**
	 * @var array
	 */
	private $female_coaches = array();
	
	/**
	 * Program constructor.
	 */
	public function __construct() {
		
		Utilities::get_instance()->log( "Initializing Program data" );
		try {
			parent::__construct( 'program', Program_Model::post_type, new Program_Model(), new Program_View() );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Error instantiating Program class: " . $exception->getMessage() );
			
			return false;
		}
	}
	
	/**
	 * Return Program class (or instantiate it)
	 *
	 * @return Program|null
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
	public function getActiveDelay() {
		
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
	public function setActiveDelay( $value ) {
		
		$Tracker = Tracker::getInstance();
		
		if ( is_object( $this->model->settings ) &&
		     isset( $this->model->settings->active_delay ) &&
		     ( null !== $this->model->settings->active_delay ) || ( '' !== $this->model->settings->active_delay ) ) {
			
			Utilities::get_instance()->log( "Saving pre-existing active_delay value: {$this->model->settings->active_delay}" );
			$this->model->settings->previous_delay = $this->model->settings->active_delay;
		}
		
		$this->model->settings->active_delay = $Tracker->sanitize( $value );
	}
	
	/**
	 * Returns the value of the previous_delay setting OR null if it's not configured/set
	 *
	 * @return int|null - A nil or integer value
	 */
	public function getPreviousDelay() {
		
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
	public function setPreviousDelay( $value ) {
		
		$Tracker = Tracker::getInstance();
		
		$this->model->settings->previous_delay = $Tracker->sanitize( $value );
	}
	
	/**
	 * Return users belonging to any programs
	 *
	 * @return array
	 */
	public function getUsersInPrograms() {
		return $this->model->getUsersInPrograms();
	}
	
	/**
	 * Get list of all active programs
	 *
	 * @return array
	 */
	public function getPrograms() {
		return $this->model->getAllProgramNamesIds( 'published' );
	}
	
	/**
	 * Return the program name from the program ID
	 *
	 * @param $program_id
	 *
	 * @return mixed
	 */
	public function getProgramName( $program_id ) {
		
		global $currentProgram;
		
		if ( $program_id != $currentProgram->id ) {
			$this->model->loadSettings( $program_id );
		}
		
		Utilities::get_instance()->log( "Name for program with id {$program_id}: {$currentProgram->title}" );
		
		return $currentProgram->title;
	}
	
	/**
	 * Configure and return the start date for the program (by the user)
	 *
	 * @param int $program_id
	 * @param int $user_id
	 *
	 * @return string
	 */
	public function getProgramStart( $program_id, $user_id ) {
		
		global $currentProgram;
		
		$this->configureStartdate( $program_id, $user_id );
		
		return $currentProgram->startdate;
	}
	
	/**
	 * Set the startdate for the program (use the user's sign-up date, unless they signed up before the program
	 * started..)
	 *
	 * @param       integer $program_id - The program ID
	 * @param       integer $userId     - The user's ID for whom we're trying to set the startdate
	 *
	 * Implicitly returns the startdate via the $currentProgram object
	 */
	public function configureStartdate( $program_id, $userId ) {
		
		global $currentProgram;
		global $current_user;
		global $currentClient;
		global $e20r_user_startdates;
		
		$startTS = null;
		
		Utilities::get_instance()->log( "Current program startdate value: {$currentProgram->startdate} for program ID {$program_id}" );
		
		if ( isset( $e20r_user_startdates[ $userId ] ) && $e20r_user_startdates[ $userId ] == $currentProgram->startdate ) {
			Utilities::get_instance()->log( "Using cached date as the start: {$e20r_user_startdates[$userId]} for {$userId}" );
			
			return;
		}
		
		if ( is_admin() && ( $userId == $current_user->ID ) && ( defined( 'DOING_AJAX' ) && false === DOING_AJAX ) ) {
			Utilities::get_instance()->log( "user ID ({$userId}) matches current logged in user ({$current_user->ID}) AND we're in the admin UI" );
			
			return;
		}
		
		$pgm_startdate = $currentProgram->startdate;
		
		if ( function_exists( 'pmpro_getMemberStartdate' ) && ! empty( $userId ) ) {
			
			$last_level = PMPro::getInstance()->getLastLevelForUser( $userId );
			if ( isset( $last_level->startdate ) ) {
				$startTS = strtotime( $last_level->startdate, current_time('timestamp' ) );
			}
			
			Utilities::get_instance()->log( "Finding PMPro's member startdate for user ID {$userId}: {$startTS}" );
		}
		
		$startTS = apply_filters( "e20r-tracker-program-start-timestamp", $startTS );
		
		if ( empty( $startTS ) ) {
			Utilities::get_instance()->log( "No start timestamp found in membership system. Setting to 'today' (right now)" );
			$startTS = current_time( 'timestamp' );
		}
		
		$user_startdate = date( 'Y-m-d', $startTS );
		
		if ( ! empty( $currentProgram->startdate ) && $user_startdate > $pgm_startdate ) {
			$currentProgram->startdate = $user_startdate;
			$currentClient->program_start = $user_startdate;
			$currentClient->started_date = $startTS;
			Utilities::get_instance()->log( "Using user date as the start: {$currentProgram->startdate} for {$userId}" );
			
		} else {
			$currentProgram->startdate = $pgm_startdate;
			$currentClient->program_start = $pgm_startdate;
			$currentClient->started_date = strtotime( $pgm_startdate, current_time('timestamp' ) );
			
			Utilities::get_instance()->log( "Using program date as the start: {$currentProgram->startdate} for {$userId}" );
		}
		// In-memory cache of user start dates
		$e20r_user_startdates[ $userId ] = $currentProgram->startdate;
	}
	
	/**
	 * Does the user ID belong to the program specified
	 *
	 * @param null|int $program_id
	 * @param int      $user_id
	 *
	 * @return bool
	 */
	public function isInProgram( $program_id = null, $user_id ) {
		
		$users   = array();
		$in_list = array();
		
		if ( empty( $program_id ) ) {
			$users = $this->model->getUsersInPrograms();
		} else {
			$user_program_id = get_user_meta( $user_id, 'e20r-tracker-program-id', false );
			if ( ! empty( $user_program_id ) ) {
				$users = array( get_user_by( 'ID', $user_id ) );
			}
		}
		
		foreach ( $users as $user ) {
			$in_list[] = $user->ID;
		}
		
		return in_array( $user_id, $in_list );
	}
	
	/**
	 * Load all users/members who belong to the program (ID)
	 *
	 * @param int $program_id
	 *
	 * @return array
	 */
	public function getProgramMembers( $program_id ) {
		
		return $this->model->loadProgramMembers( $program_id );
	}
	
	/**
	 * Is the specified program currently active (running)
	 *
	 * @param $program_shortname
	 *
	 * @return bool
	 */
	public function isActive( $program_shortname ) {
		
		$program = $this->findByName( $program_shortname );
		
		if ( ( $program !== false ) && ( ! in_array( $program->post_status, array( 'publish', 'private' ) ) ) ) {
			
			Utilities::get_instance()->log( "Program::isActive() - Program not found or not published" );
			
			return false;
		}
		
		$now   = current_time( 'timestamp' );
		$start = strtotime( $program->startdate );
		$end   = strtotime( $program->enddate );
		
		// It's available since no start has been configured.
		if ( ! $start ) {
			Utilities::get_instance()->log( "Program::isActive() - Start value not set, program is available" );
			
			return true;
		}
		
		// It's available since no end-time has been configured and it's after the starttime
		if ( ( ! $end ) && ( $now >= $start ) ) {
			Utilities::get_instance()->log( "Program::isActive() - It's after the start date, and end value not set, program is available" );
			
			return true;
		}
		
		if ( ( $now >= $start ) && ( $now <= $end ) ) {
			Utilities::get_instance()->log( "Program::isActive() - Currently somewhere between start and end for the program. it's available " );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Load the program settings (by ID)
	 *
	 * @param int $program_id
	 */
	public function setProgram( $program_id ) {
		
		global $currentProgram;
		
		if ( ! isset( $currentProgram->id ) || ( $currentProgram->id !== $program_id ) ) {
			
			Utilities::get_instance()->log( "Program::loadProgram() - Need to init the program object" );
			$this->model->loadSettings( $program_id );
		}
	}
	
	/**
	 * Return the welcome survey link for the user
	 *
	 * @param int $userId
	 *
	 * @return false|string
	 */
	public function getWelcomeSurveyLink( $userId ) {
		
		global $currentProgram;
		
		$this->loadProgram( $userId );
		
		$link = get_permalink( $currentProgram->intake_form );
		
		Utilities::get_instance()->log( "Link: {$link}" );
		
		return $link;
	}
	
	/**
	 * Load the program for the specified user ID
	 *
	 * @param int $userId
	 */
	private function loadProgram( $userId = 0 ) {
		
		global $currentProgram;
		
		if ( ! isset( $currentProgram->id ) || ( ! in_array( $userId, $currentProgram->users ) ) ) {
			
			if ( is_user_logged_in() && ( $userId != 0 ) ) {
				
				Utilities::get_instance()->log( "Program::loadProgram() - Loading usermeta for ID {$userId}" );
				$programId = get_user_meta( $userId, 'e20r-tracker-program-id', true );
				
				if ( ( false !== $programId ) &&
				     ( ! isset( $currentProgram->id ) || ( $currentProgram->id !== $programId ) ) ) {
					
					Utilities::get_instance()->log( "Program::loadProgram() - Need to init the program object" );
					$this->model->loadSettings( $programId );
				}
			}
			
			$this->configureStartdate( $programId, $userId );
		}
		
		Utilities::get_instance()->log( "Program::loadProgram() - User's programID: " . isset( $currentProgram->id ) ? $currentProgram->id : 'null' );
	}
	
	/**
	 * Configure the program for the specified user ID
	 *
	 * @param string    $startdate
	 * @param int       $user_id
	 * @param \stdClass $level_obj
	 *
	 * @return string
	 */
	public function setProgramForUser( $startdate, $user_id, $level_obj ) {
		
		$Tracker = Tracker::getInstance();
		global $currentProgram;
		
		// We only need the membership ID value to find the program (if it's defgined.
		if ( ! empty( $level_obj->id ) ) {
			$membership_id = $level_obj->id;
		} else {
			return $startdate;
		}
		
		Utilities::get_instance()->log( "Program::setProgramForUser() - Called from: " . $Tracker->whoCalledMe() );
		Utilities::get_instance()->log( "Program::setProgramForUser() - Locating programs from membership id # {$membership_id} on behalf of user {$user_id}" );
		
		if ( false === ( $pId = $this->model->findByMembershipId( $membership_id ) ) ) {
			
			Utilities::get_instance()->log( "Program::setProgramForUser() - ERROR: No program IDs returned!" );
			
			$addr = get_option( 'admin_email' );
			
			$subj = "Error: Cannot locate program definition for the '{$level_obj->name}'' membership level (ID: {$membership_id})";
			
			$msg = "Membership Level '{$level_obj->name}' (ID: {$membership_id}) does NOT appear to be associated with a published program.\n";
			$msg .= "Please correct the program definitions for the '{$level_obj->name}' (ID: {$membership_id}) membership level\n";
			
			wp_mail( $addr, $subj, $msg );
			
			return $startdate;
			
		}
		
		Utilities::get_instance()->log( "Program::setProgramForUser() - Returned groups/membership IDs: " . print_r( $pId, true ) );
		
		if ( is_array( $pId ) ) {
			
			Utilities::get_instance()->log( "Program::setProgramForUser() - ERROR: More than one program ID associated with membership!" );
			$addr = get_option( 'admin_email' );
			
			$subj = "Error: Unexpected program definition(s)";
			
			$msg = "Membership Level {$membership_id} is associated with more than a single program ID.\n";
			$msg .= "Please correct the program definitions for the following programs:\n\n";
			
			foreach ( $pId as $id ) {
				
				$msg .= get_the_title( $id ) . "({$id})\n";
			}
			
			wp_mail( $addr, $subj, $msg );
			
			return $startdate;
		}
		
		update_user_meta( $user_id, 'e20r-tracker-program-id', $pId );
		
		if ( $pId !== get_user_meta( $user_id, 'e20r-tracker-program-id', true ) ) {
			
			$addr = get_option( 'admin_email' );
			
			$subj = "Error: Unable to set program for user ID";
			
			$msg = "Membership Level '{$level_obj->name}' (ID: {$membership_id}) could not be configured for user ID {$user_id}.\n";
			$msg .= "Please update the profile for user with ID {$user_id} in the admin panel.\n";
			
			wp_mail( $addr, $subj, $msg );
			
			return $startdate;
		}
		
		Utilities::get_instance()->log( "Program::setProgramForUser() - Testing whether to add user to program list" );
		
		if ( empty( $currentProgram->id ) || ( $pId != $currentProgram->id ) ) {
			
			Utilities::get_instance()->log( "Program::setProgramForUser() - Configure program ({$pId}) for new user/member" );
			$this->model->loadSettings( $pId );
			$startTS = $this->startdate( $user_id, $pId, false );
			
			if ( false !== update_user_meta( $user_id, 'e20r-tracker-program-startdate', date( 'Y-m-d', $startTS ) ) ) {
				
				$startdate = date_i18n( 'Y-m-d', $startTS ) . " 00:00:00";
			}
		}
		
		$currentProgram->users[] = $user_id;
		
		if ( ! in_array( $user_id, $currentProgram->users ) ) {
			
			Utilities::get_instance()->log( "Program::setProgramForUser() - Adding user to the program 'users' list" );
			$this->model->set( 'users', $currentProgram->users, $currentProgram->id );
		}
		
		return $startdate;
	}
	
	/**
	 * Calculates the startdate (as a 'seconds since epoch') value and returns it to the calling function.
	 *
	 * Currently supports:
	 *      Internal usermeta value. (e20r-program-startdate => 'When this user started the program'
	 *      Paid Memberships Pro.
	 *
	 * @param int  $userId - ID of user to find the startdate for.
	 * @param int  $program_id
	 * @param bool $membership
	 *
	 * @return int|mixed - Timestamp (seconds since UNIX epoch
	 */
	public function startdate( $userId, $program_id = null, $membership = true ) {
		
		global $currentProgram;
		
		if ( ( empty( $program_id ) ) ) {
			
			Utilities::get_instance()->log( "Program::startdate() - Loading program for user with ID: {$userId}" );
			$program_id = $this->getProgramIdForUser( $userId );
		}
		
		if ( ( ! empty( $program_id ) && ! empty( $currentProgram->id ) && ( $currentProgram->id === false ) ) ||
		     ( ( ! empty( $program_id ) ) && ( $program_id != $currentProgram->id ) ) ) {
			
			Utilities::get_instance()->log( "Program::startdate() - Loading new program {$program_id} in place of {$currentProgram->id}" );
			$this->model->loadSettings( $program_id );
		}
		
		// This is a date of the 'Y-m-d' PHP format. (eg 2015-01-01).
		return ! empty( $currentProgram->startdate ) ? strtotime( $currentProgram->startdate ) : current_time( 'timestamp' );
	}
	
	/**
	 * Identify the program the user belongs to when given the article ID
	 *
	 * @param int  $userId
	 * @param null $articleId
	 *
	 * @return bool|false|int|mixed|null
	 */
	public function getProgramIdForUser( $userId = 0, $articleId = null ) {
		
		global $currentProgram;
		
		$user_program = false;
		
		Utilities::get_instance()->log( "Processing user ID: {$userId}" );
		
		if ( 0 < $userId ) {
			
			$user_program = get_user_meta( $userId, 'e20r-tracker-program-id', true );
		} else {
			return $user_program;
		}
		
		if ( ! empty( $currentProgram->id ) && ! empty( $user_program ) && ( $currentProgram->id == $user_program ) ) {
			
			Utilities::get_instance()->log( "User program and current program id are the same: {$currentProgram->id} vs {$user_program}" );
			$this->configureStartdate( $user_program, $userId );
			
			return $currentProgram->id;
		}
		
		if ( empty( $currentProgram->id ) || ( ! empty( $user_program ) && ( ! empty( $currentProgram->id ) && ( $currentProgram->id != $user_program ) ) ) ) {
			
			Utilities::get_instance()->log( "currentProgram->id isn't configured or its different from what this user ({$userId}) needs it to be ({$user_program})." );
			
			if ( empty( $user_program ) ) {
				
				if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
					
					// locate the user's program.
					$level = pmpro_getMembershipLevelForUser( $userId );
					
					if ( ! isset( $level->id ) ) {
						return false;
					}
					
					$user_program = $this->model->findByMembershipId( $level->id );
					
					if ( is_null( $user_program ) ) {
						
						Utilities::get_instance()->log( "Unable to locate program ID for user {$userId}" );
						
						return false;
					}
				} else {
					Utilities::get_instance()->log( "Error loading program information for {$userId}" );
				}
			}
			
			Utilities::get_instance()->log( "currentProgram being configured for {$userId} -> {$user_program}." );
			$this->model->loadSettings( $user_program );
			
			$this->configureStartdate( $user_program, $userId );
			
			if ( empty( $currentProgram->id ) ) {
				
				Utilities::get_instance()->log( "currentProgram getting set to default values" );
				$this->init();
			}
		}
		
		Utilities::get_instance()->log( "Loaded program ID ($currentProgram->id) for user {$userId}" );
		
		return ( isset( $currentProgram->id ) ? $currentProgram->id : false );
	}
	
	/**
	 * Configure the program (load settings, etc).
	 *
	 * @param null     $programId - Optional argument containing the program ID value (integer)
	 * @param null|int $delay     -   Delay value for program start
	 *
	 * @return bool - True = initialized and configured parameters/settings for specified program ID
	 *                False = failed to init and configure parameters/settings for specified program ID
	 */
	public function init( $programId = null, $delay = null ) {
		
		global $currentProgram;
		global $current_user;
		
		Utilities::get_instance()->log( "Argument: " . ( empty( $programId ) ? 'Null' : $programId ) );
		Utilities::get_instance()->log( "Current program value: " . ( empty( $currentProgram->id ) ? 'Null' : $currentProgram->id ) );
		
		if ( isset( $currentProgram->id ) && ! empty( $programId ) && ( $currentProgram->id == $programId ) ) {
			
			Utilities::get_instance()->log( "Program {$currentProgram->id} was loaded already" );
			
			return true;
		}
		
		if ( is_null( $programId ) ) {
			
			Utilities::get_instance()->log( "Grabbing program ID for user {$current_user->ID} from DB." );
			$programId = get_user_meta( $current_user->ID, 'e20r-tracker-program-id', true );
		}
		
		if ( ! empty( $programId ) &&
		     ( ( ! isset( $currentProgram->id ) ) || ( $currentProgram->id != $programId ) ) ) {
			
			Utilities::get_instance()->log( "Loading program settings for {$programId}." );
			$currentProgram = $this->model->loadSettings( $programId );
			
			$this->configureStartdate( $programId, $current_user->ID );
			
			Utilities::get_instance()->log( "Program info has been loaded for: {$currentProgram->id}" );
			
			return true;
		}
		
		Utilities::get_instance()->log( "No Program ID found or user not logged in!" );
		
		return false;
	}
	
	/**
	 * Load all settings (and programs)
	 *
	 * @return array
	 */
	public function getProgramList() {
		
		$program_list = $this->model->loadAllSettings();
		
		Utilities::get_instance()->log( "Content of Program list being returned " );
		
		return $program_list;
	}
	
	/**
	 * Add Intake form warning to content if applicable
	 *
	 * @return string
	 */
	public function incompleteIntakeForm() {
		
		global $currentProgram;
		global $current_user;
		
		if ( ! isset( $currentProgram->id ) ) {
			
			Utilities::get_instance()->log( "Program::incompleteIntakeForm() - Loading program ID" );
			$this->getProgramIdForUser( $current_user->ID );
		}
		
		if ( ! empty( $currentProgram->incomplete_intake_form_page ) ) {
			
			$post    = get_post( $currentProgram->incomplete_intake_form_page );
			$content = apply_filters( 'the_content', $post->post_content );
		} else {
			$post = get_post( $currentProgram->intake_form );
			
			$default_text = sprintf(
				__( '<p>Please complete %s (<a href="%s" target="_blank">link</a>)</p>', "e20r-tracker" ),
				$post->post_title,
				get_permalink( $post->ID )
			);
			
			$content = apply_filters( 'e20r_tracker_default_incomplete_form_text', $default_text );
		}
		
		wp_reset_postdata();
		
		return $content;
	}
	
	/**
	 * Assign the start date (in the program) to the User ID
	 *
	 * @param int       $ts
	 * @param int       $user_id
	 * @param \stdClass $level
	 *
	 * @return string
	 */
	public function setStartdateForUser( $ts, $user_id, $level ) {
		
		$program_id = $this->getProgramIdForUser( $user_id );
		$sd         = $this->startdate( $user_id, $program_id );
		
		if ( empty( $sd ) ) {
			if ( ! empty( $level->startdate ) ) {
				$sd = $level->startdate;
			}
		}
		
		$start_date = date_i18n( 'Y-m-d', $sd ) . " 00:00:00";
		
		Utilities::get_instance()->log( "Using startdate of: {$start_date} ({$sd} vs {$ts}) for {$user_id} in program {$program_id}" );
		
		return $start_date;
	}
	
	/**
	 * Returns the user IDs for the coaches assigned to the program
	 *
	 * @param int $program_id
	 *
	 * @return string[]
	 */
	public function getCoachesForProgram( $program_id ) {
		
		$program   = $this->init( $program_id );
		$coach_ids = array_merge( $program->male_coaches, $program->female_coaches );
		$coaches   = array();
		
		foreach ( $coach_ids as $id ) {
			
			$tmp            = get_user_by( 'id', $id );
			$coaches[ $id ] = $tmp->display_name;
		}
		
		return $coaches;
	}
	
	/**
	 * Wrapper for the Model::getSetting() method
	 *
	 * @param int    $programId
	 * @param string $fieldName
	 *
	 * @return mixed
	 */
	public function getValue( $programId, $fieldName = 'id' ) {
		
		return $this->model->getSetting( $programId, $fieldName );
	}
	
	/**
	 * Wrapper for the Model::set() method
	 *
	 * @param mixed $value
	 * @param string $fieldName
	 * @param int    $programId
	 *
	 * @return bool
	 */
	public function setValue( $value, $fieldName = 'id', $programId ) {
		return $this->model->set( $fieldName, $value, $programId );
	}
} 