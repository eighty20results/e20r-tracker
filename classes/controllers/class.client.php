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

use Braintree\Util;
use E20R\Tracker\Views\Client_Views;
use E20R\Tracker\Models\Client_Model;
use E20R\Tracker\Models\Tables;
use E20R\Utilities\Utilities;

/**
 * Class Client
 * @package E20R\Tracker\Controllers
 *
 * @since   1.0
 */
class Client {
	
	/**
	 * @var null|Client
	 */
	private static $instance = null;
	
	/**
	 * @var bool $client_loaded
	 */
	public $client_loaded = false; // Client Model.
	
	/**
	 * @var bool $actionsLoaded
	 */
	public $actionsLoaded = false; // Client Views.
	
	/**
	 * @var bool $scriptsLoaded
	 */
	public $scriptsLoaded = false;
	
	private $id;
	/**
	 * @var Client_Model|null
	 */
	private $model = null;
	
	/**
	 * @var Client_Views|null
	 */
	private $view = null;
	
	/**
	 * @var string $weightunits
	 */
	private $weightunits;
	
	/**
	 * @var string $lengthunits
	 */
	private $lengthunits;
	
	/**
	 * @var bool $interview_status
	 */
	private $interview_status = false;
	
	/**
	 * @var bool $interview_status_loaded
	 */
	private $interview_status_loaded = false;
	
	/**
	 * Client constructor.
	 *
	 * @param integer|null $user_id
	 */
	public function __construct( $user_id = null ) {
		
		global $currentClient;
		
		$this->model = new Client_Model();
		$this->view  = new Client_Views();
		
		if ( $user_id !== null ) {
			
			$currentClient->user_id = $user_id;
		}
		
	}
	
	/**
	 * Get or instantiate the Client class
	 *
	 * @return Client
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Save the login action for client(s)
	 *
	 * @param string $user_login
	 */
	public function recordLogin( $user_login ) {
		$user = get_user_by( 'login', $user_login );
		
		if ( empty( $user ) ) {
			return;
		}
		
		Utilities::get_instance()->log( "Check that {$user_login} has a valid role..." );
		$this->checkRoleSetting( $user->ID );
		
		if ( $user->ID != 0 ) {
			Utilities::get_instance()->log( "Saving login information about {$user_login}" );
			update_user_meta( $user->ID, '_e20r-tracker-last-login', current_time( 'timestamp' ) );
		}
	}
	
	/**
	 * Validate that the user ID has an exercise level role on the system
	 * Set to "beginner" if they don't.
	 *
	 * @param       int $user_id The User ID to check
	 *
	 * @return      true
	 */
	public function checkRoleSetting( $user_id ) {
		
		Utilities::get_instance()->log( "Make sure {$user_id} has an exercise experience role configured" );
		
		$user       = new \WP_User( $user_id );
		$user_roles = apply_filters( 'e20r-tracker-configured-roles', array() );
		
		// Assume the user does _NOT_ have one of the expected roles
		$has_role = false;
		
		// Verify whether they have one of the expected roles
		foreach ( $user_roles as $key => $role ) {
			
			$has_role = $has_role || in_array( $role['role'], $user->roles );
		}
		
		Utilities::get_instance()->log( "{$user_id} DOES have the exercise experience role configured? " . ( $has_role === true ? 'Yes' : 'No' ) );
		
		// No role yet. Have to give them a basic beginner role...
		if ( false === $has_role ) {
			
			Utilities::get_instance()->log( "Assigning a default role (Beginner) until they complete the Welcome interview" );
			$user->add_role( $user_roles['beginner']['role'] );
			wp_cache_flush();
			$has_role = true;
		}
		
		return $has_role;
	}
	
	/**
	 * Returns the URL to the user-uploaded image(s)
	 *
	 * @param int    $who
	 * @param string $when
	 * @param string $imageSide
	 *
	 * @return bool|string
	 */
	public function getUserImgUrl( $who, $when, $imageSide ) {
		
		$Tables = Tables::getInstance();
		
		if ( $this->isNourishClient( $who ) && $Tables->isBetaClient() ) {
			
			return $this->model->getBetaUserUrl( $who, $when, $imageSide );
		}
		
		return false;
	}
	
	/**
	 * Compatibility layer (Deprecated)
	 *
	 * @param int $user_id
	 *
	 * return false
	 */
	public function isNourishClient( $user_id = 0 ) {
		__return_false();
	}
	
	/**
	 * Returns client data for the specified $client_id
	 *
	 * @param  int $client_id
	 * @param bool $private
	 * @param bool $basic
	 *
	 * @return array|bool|mixed
	 */
	public function getClientData( $client_id, $private = false, $basic = false ) {
		
		if ( ! $this->client_loaded ) {
			
			Utilities::get_instance()->log( "No client data loaded yet..." );
			$this->setClient( $client_id );
			// $this->get_data( $client_id );
		}
		
		if ( true === $basic ) {
			Utilities::get_instance()->log( "Loading basic client data - not full survey." );
			$data = $this->model->load_basic_clientdata( $client_id );
		} else {
			Utilities::get_instance()->log( "Loading all client data including survey" );
			$data = $this->model->get_data( $client_id );
		}
		
		if ( true === $private ) {
			
			Utilities::get_instance()->log( "Removing private data" );
			$data = $this->strip_private_data( $data );
		}
		
		Utilities::get_instance()->log( "Returned data for {$client_id} from client_info table:" );
		
		// Utilities::get_instance()->log($data);
		
		return $data;
	}
	
	/**
	 * Load the client data for the $user_id
	 *
	 * @param int $user_id
	 */
	public function setClient( $user_id ) {
		
		$this->client_loaded = false;
		$this->model->setUser( $user_id );
		$this->init();
	}
	
	/**
	 * Load client specific info (the basics)
	 */
	public function init() {
		
		$Tracker = Tracker::getInstance();
		global $currentClient;
		
		if ( empty( $currentClient->user_id ) ) {
			
			global $current_user;
			// $this->id = $current_user->ID;
			$currentClient->user_id = $current_user->ID;
		}
		
		Utilities::get_instance()->log( 'Running INIT for Client Controller: ' . $Tracker->whoCalledMe() );
		
		if ( $this->client_loaded !== true && true === $currentClient->user_id ) {
			
			$this->model->setUser( $currentClient->user_id );
			$this->model->load_client_settings( $currentClient->user_id );
			$this->client_loaded = true;
		}
		
	}
	
	/**
	 * Remove data that should not be shown (private)
	 *
	 * @param array $cData
	 *
	 * @return array
	 */
	private function strip_private_data( $cData ) {
		
		$data = $cData;
		
		if ( is_object( $data ) ) {
			$data->display_birthdate    = 1;
			$data->incomplete_interview = 1;
			
			
			$include = array(
				'id',
				'birthdate',
				'first_name',
				'gender',
				'lengthunits',
				'weightunits',
				'incomplete_interview',
				'program_id',
				'progress_photo_dir',
				'program_start',
				'user_id',
			);
			
			foreach ( $data as $field => $value ) {
				
				if ( ! in_array( $field, $include ) ) {
					
					unset( $data->{$field} );
				}
			}
			
			if ( isset( $data->birthdate ) && strtotime( $data->birthdate ) ) {
				
				$data->display_birthdate = false;
			}
			
			if ( isset( $data->first_name ) && ( isset( $data->id ) ) && isset( $data->display_birthdate ) ) {
				
				$data->incomplete_interview = false;
			}
		}
		
		return $data;
	}
	
	/**
	 * Return the gender for the currently loaded Client
	 *
	 * @return string
	 */
	public function getGender() {
		
		global $currentClient;
		
		return strtolower( $this->model->get_data( $currentClient->user_id, 'gender' ) );
	}
	
	/**
	 * Automatically propose/recommend an exercise experience level for the user based
	 * on survey results.
	 *
	 * @param integer $user_id - The User ID to update the role for
	 * @param array   $data    - The survey response(s) given
	 */
	public function assignExerciseLevel( $user_id, $data ) {
		
		// can the client even be at the "experienced" level (by default, no).
		$can_be_ex  = false;
		$user_roles = apply_filters( 'e20r-tracker-configured-roles', array() );
		$el_score   = 0;
		
		if ( !isset( $data['exercise_hours_per_week'])) {
		    $data['exercise_hours_per_week'] = 0;
        }
        
		Utilities::get_instance()->log( print_r( $data, true) );
		
		switch ( $data['exercise_level'] ) {
			case 'complete-beginner':
			case 'some-experience':
				Utilities::get_instance()->log( " Self-reported as inexperienced exerciser" );
				$el_score = 1;
				break;
			
			case 'comfortable':
				Utilities::get_instance()->log( " Self-reported as intermediate exerciser" );
				$el_score = 2;
				break;
			
			case 'very-experienced':
			case 'advanced':
				Utilities::get_instance()->log( " Self-reported as experienced exerciser" );
				$el_score = 3;
				break;
		}
		
		// Lower the user's exercise level score if they're injured
		if ( 'yes' === strtolower( $data['limiting_injuries'] ) ) {
			
			Utilities::get_instance()->log( " Lowering exercise level score due to injury." );
			
			if ( 1 === $el_score ) {
				$el_score = 1;
			}
			if ( 2 === $el_score ) {
				$el_score = 1;
			}
			if ( 3 === $el_score ) {
				$el_score = 2;
			}
		}
		
		switch ( $data['exercise_hours_per_week'] ) {
			
			case '1-3': // 1-3 hours/week
				$hw_score  = 1;
				$can_be_ex = false;
				break;
			
			case '3-5': // 3-5 hours
				$hw_score  = 2;
				$can_be_ex = false;
				break;
			
			case '5-7':
			case '7+':
				$hw_score = 3;
				if ( 3 === $el_score ) {
					$can_be_ex = true;
				}
				break;
			
			default:
				$hw_score = 0;
		}
		
		Utilities::get_instance()->log( " Exercise per hour score: {$hw_score}" );
		
		// Can't be "experienced" if they don't currently exercise
		if ( 1 !== $data['exercise_plan'] ) {
			Utilities::get_instance()->log( " Performs regular exercise, so is allowed to be selected for experienced level." );
			$can_be_ex = false || $can_be_ex;
		}
		
		$total_exp = $el_score + $hw_score;
		
		// "experienced"
		if ( true === $can_be_ex && ( 6 === $total_exp || ( 3 === $el_score && $hw_score == 2 ) ) ) {
			Utilities::get_instance()->log( " User {$user_id} qualifies as 'Experienced'" );
			$role = $user_roles['experienced']['role'];
			
			// $el_score = 1 or 2 and $hw_score = 1, 2, 3 ('intermediate')
		} else if ( $total_exp <= 5 || $total_exp >= 3 ) {
			Utilities::get_instance()->log( " User {$user_id} qualifies as 'Intermediate'" );
			$role = $user_roles['intermediate']['role'];
			
			// Beginner
		} else {
			Utilities::get_instance()->log( " User {$user_id} qualifies as 'New to Exercise'" );
			$role = $user_roles['beginner']['role'];
		}
		
		$user = new \WP_User( $user_id );
		
		// Clean up any pre-existing roles for this user
		foreach ( $user->roles as $r ) {
			
			Utilities::get_instance()->log( "Checking role '{$r}' for user {$user_id}" );
			
			if ( in_array( $r, array( 'e20r_tracker_exp_1', 'e20r_tracker_exp_2', 'e20r_tracker_exp_3' ) ) ) {
				Utilities::get_instance()->log( "User has pre-existing role {$r}. Being removed" );
				$user->remove_role( $r );
			}
		}
		
		Utilities::get_instance()->log( "Do we need to upgrade the user ({$user->ID}) from their current {$role} exericse level?" );
		$this->maybe_upgrade_role( $user, $role );
	}
	
	/**
	 * Do we upgrade the user's exercise role (based on past history)
	 *
	 * @param \WP_User $user
	 * @param string   $existing_role
	 */
	public function maybe_upgrade_role( $user, $existing_role ) {
		
		if ( ! $user->exists() ) {
			return;
		}
		
		if ( $existing_role === 'e20r_tracker_exp_3' ) {
			return;
		}
		
		$old_roles = $user->roles;
		
		$Tracker = Tracker::getInstance();
		
		$first_upgrade_day  = 155;
		$second_upgrade_day = 180;
		$current_day        = $Tracker->getDelay( 'now', $user->ID );
		
		$new_role = null;
		
		if ( $current_day >= $first_upgrade_day && $current_day < $second_upgrade_day ) {
			$new_role = $this->select_next_role( $existing_role );
			Utilities::get_instance()->log( "Yes we do.. Upgrading from {$existing_role} to {$new_role} on day # {$first_upgrade_day}" );
		}
		
		if ( $current_day >= $second_upgrade_day ) {
			$new_role = $this->select_next_role( $existing_role );
			Utilities::get_instance()->log( "Yes we do (2nd upgrade). Upgrading from {$existing_role} to {$new_role} on day # {$second_upgrade_day}" );
		}
		
		$user_upgrade_level = get_user_meta( $user->ID, '_Tracker_upgraded_to', true );
		
		if ( ( ! is_null( $new_role ) && $user_upgrade_level !== $new_role ) ) {
			
			if ( ( $key = array_search( $existing_role, $old_roles ) ) !== false ) {
				unset( $old_roles[ $key ] );
			}
			
			if ( ! in_array( $new_role, $old_roles ) ) {
				$old_roles[] = $new_role;
			}
			
			$user->set_role( '' );
			
			foreach ( $old_roles as $role_name ) {
				Utilities::get_instance()->log( "Updating roles for user {$user->ID} " );
				$user->add_role( $role_name );
			}
			
			update_user_meta( $user->ID, '_Tracker_upgraded_to', $new_role );
			wp_cache_flush();
		}
	}
	
	/**
	 * Select the role to assign
	 *
	 * @param string $role_name
	 *
	 * @return mixed
	 */
	private function select_next_role( $role_name ) {
		
		$roles = apply_filters( 'e20r-tracker-configured-roles', array() );
		
		foreach ( $roles as $r_key => $def ) {
			
			if ( $def['role'] === $role_name ) {
				
				switch ( $r_key ) {
					
					case 'beginner':
						$role_to_use = 'intermediate';
						break;
					
					case 'intermediate':
					case 'experienced':
						$role_to_use = 'experienced';
						break;
				}
			}
		}
		
		return $roles[ $role_to_use ]['role'];
	}
	
	/**
	 * Save the weight or length unit (when updated by the user)
	 *
	 * @param string $type
	 * @param string $unit
	 *
	 * @return bool
	 */
	public function saveNewUnit( $type, $unit ) {
		
		switch ( $type ) {
			
			case 'length':
				
				Utilities::get_instance()->log( "Saving new length unit: {$unit}" );
				try {
					$this->model->saveUnitInfo( $unit, $this->getWeightUnit() );
				} catch ( \Exception $exception ) {
					Utilities::get_instance()->log( "Unable to save length unit {$unit}: " . $exception->getMessage() );
					
					return false;
				}
				break;
			
			case 'weight':
				
				Utilities::get_instance()->log( "Saving new weight unit: {$unit}" );
				try {
					$this->model->saveUnitInfo( $this->getLengthUnit(), $unit );
				} catch ( \Exception $exception ) {
					Utilities::get_instance()->log( "Unable to save Weight unit {$unit}: " . $exception->getMessage() );
					
					return false;
				}
				break;
		}
		
		return true;
	}
	
	/**
	 * Return the configured weight unit for the $currentClient
	 *
	 * @return bool|string
	 *
	 * @since 3.0 - ENHANCEMENT: Added caching for the weight unit
	 */
	public function getWeightUnit() {
		
		global $currentClient;
		global $client_weight_unit;
		
		if ( empty( $client_weight_unit ) ) {
			$client_weight_unit = $this->model->get_data( $currentClient->user_id, 'weightunits' );
		}
		
		return $client_weight_unit;
	}
	
	/**
	 * Return the configured length unit for the $currentClient
	 *
	 * @return bool|string
	 *
	 * @since 3.0 - ENHANCEMENT: Added caching for the length unit
	 */
	public function getLengthUnit() {
		
		global $currentClient;
		global $client_length_unit;
		
		if ( empty( $client_length_unit ) ) {
			$client_length_unit = $this->model->get_data( $currentClient->user_id, 'lengthunits' );
		}
		
		return $client_length_unit;
	}
	
	/**
	 * Load the client data we have to the $currentClient global for the $user_id
	 *
	 * @param int $user_id
	 */
	public function loadClientInfo( $user_id ) {
		
		try {
			
			Utilities::get_instance()->log( "Loading data for client model" );
			$this->model->setUser( $user_id );
			$this->model->get_data( $user_id );
			
		} catch ( \Exception $e ) {
			
			Utilities::get_instance()->log( "Error loading user data for ({$user_id}): " . $e->getMessage() );
		}
		
	}
	
	/**
	 * Render page on back-end for client data (admin selectable).
	 *
	 * @param string $lvlName
	 * @param int    $client_id
	 */
	public function renderClientPage( $lvlName = '', $client_id = 0 ) {
		
		global $current_user;
		
		$utils  = Utilities::get_instance();
		$Access = Tracker_Access::getInstance();
		
		if ( ! $Access->is_a_coach( $current_user->ID ) ) {
			
			$this->setNotCoachMsg();
		}
		
		$w_client = $utils->get_variable( 'e20r-client-id', 0 );
		$w_level  = $utils->get_variable( 'e20r-level-id', - 1 );
		
		if ( ! is_null( $w_client ) ) {
			
			$client_id = $w_client;
		}
		
		if ( $client_id != 0 ) {
			
			Utilities::get_instance()->log( "Forcing client ID to {$client_id}" );
			$this->setClient( $client_id );
		}
		
		$this->init();
		$this->model->get_data( $client_id );
		
		echo $this->view->viewClientAdminPage( $lvlName, $w_level );
	}
	
	/**
	 * Deny access to Coaching page (backend) if not a registered coach
	 */
	private function setNotCoachMsg() {
		
		$Tracker = Tracker::getInstance();
		
		Utilities::get_instance()->log( "User isn't a coach. Return error & force redirect" );
		
		$error = '<div class="error">';
		$error .= '    <p>' . __( "Sorry, as far as the Web Monkey knows, you are not a coach and will not be allowed to access the Coach's Page.", "e20r-tracker" ) . '</p>';
		$error .= '</div><!-- /.error -->';
		
		$Tracker->updateSetting( 'unserialize_notice', $error );
		
		wp_redirect( admin_url() );
		exit();
	}
	
	/**
	 * Load data and display the Client Settings page for the Tracker
	 *
	 * @param null|int $client_id
	 */
	public function renderClientSettingsPage( $client_id = null ) {
		
		global $current_user;
		global $currentClient;
		
		$Access   = Tracker_Access::getInstance();
		$Programs = Program::getInstance();
		$utils    = Utilities::get_instance();
		
		if ( ! $Access->is_a_coach( $current_user->ID ) ) {
			
			$this->setNotCoachMsg();
		}
		
		$w_client       = $utils->get_variable( 'e20r_tracker_client', 0 );
		$newProgramIDs  = $utils->get_variable( 'e20r-tracker-user-program', array() );
		$exerciseLevel  = $utils->get_variable( 'e20r-tracker-user-assigned_role', '' );
		$clientGender   = $utils->get_variable( 'e20r-tracker-user-gender', '' );
		$newCoachIDs    = $utils->get_variable( 'e20r-tracker-user-coach_id', array() );
		$resetPage      = $utils->get_variable( 'e20r-tracker-reset-btn', null );
		$startedOn      = $utils->get_variable( 'e20r-tracker-user-program_start_date', '' );
		$memberRecordId = $utils->get_variable( 'e20r-tracker-client-record_id', 0 );
		
		if ( ! empty( $resetPage ) ) {
			
			// page=e20r-client-settings
			wp_redirect( add_query_arg( 'page', 'e20r-client-settings', admin_url( 'admin.php' ) ) );
			exit();
		}
		
		if ( empty( $client_id ) && ! empty( $w_client ) ) {
			$client_id = $w_client;
		}
		
		$client = get_user_by( 'ID', $client_id );
		
		if ( empty( $client ) ) {
			$client = isset( $currentClient->user_id ) && ! empty( $currentClient->user_id ) ? get_user_by( 'ID', $currentClient->user_id ) : $current_user;
		}
		
		if ( empty( $clientGender ) ) {
			$this->getClientInfo( $client->ID );
			$clientGender = $this->getClientDataField( $client->ID, 'gender' );
		}
		
		$utils->log( "User: {$client->ID}: " . print_r( $currentClient, true ) );
		
		if ( ! empty( $startedOn ) && $startedOn != $currentClient->program_start ) {
			$currentClient->program_start = $startedOn;
		}
		
		$client->gender = $clientGender;
		$client->member_record_id = $memberRecordId;
		
		if ( ! empty( $memberRecordId ) || ( ! empty( $client->ID ) &&
		                                     ( ( ! empty( $client->ID ) && ! empty( $newProgramIDs ) ) ||
		                                       ( ! empty( $client->ID ) && ! empty( $exerciseLevel ) ) ||
		                                       ( ! empty( $client->ID ) && ! empty( $newCoachIDs ) ) ||
		                                       ( ! empty( $client->ID ) && ! empty( $startedOn ) ) ) )
		) {
		 
			$utils->log( "Saving settings for {$client->ID}" );
			$this->saveSettingsForClient( $client, $newProgramIDs, $exerciseLevel, $newCoachIDs );
		}
		
		$coach_id = null;
		
		$programlist   = $Programs->getProgramList();
		$activeProgram = $Programs->getProgramIdForUser( $client->ID, null );
		
		$utils->log( "Loading coach for the specific user ({$client->ID})" );
		$coach_ids = $this->get_coach( $client->ID, $activeProgram );
		
		if ( empty( $coach_ids ) && ( false !== $activeProgram ) ) {
			
			$utils->log( "No coach found for user {$client->ID}, but since they're members of a program we'll try to assign one automatically." );
			$this->getClientInfo( $client->ID );
			
			if ( isset( $currentClient->loadedDefaults ) && ( false !== $currentClient->loadedDefaults ) ) {
				
				$utils->log( "Didn't have a coach but is member of a program so assigning a coach to user {$client->ID} with gender {$currentClient->gender}" );
				$id = $this->assignCoach( $client->ID, $currentClient->gender );
				
				if ( empty( $id ) || $id === - 1 ) {
					$coach_ids = array( - 1 => __( 'Unassigned', 'e20r-tracker' ) );
				} else {
					$u         = get_user_by( 'id', $id );
					$coach_ids = array( $id => $u->display_name );
				}
				
			} else {
				$utils->log( "User hasn't completed their intake interview so can't select coach automatically" );
				$coach_ids = array( - 1 => 'Unassigned' );
			}
		}
		
		$coachList = $this->get_coach();
		
		$utils->log( "Active Program: {$activeProgram}" );
		
		echo $this->view->viewClientSettingsPage(
			$programlist,
			$activeProgram,
			$coachList,
			$coach_ids,
			$client
		);
	}
	
	/**
	 * Get/configure all client info for the $client_id
	 *
	 * @param int $client_id
	 *
	 * @return mixed
	 */
	public function getClientInfo( $client_id ) {
		
		return $this->model->load_client_settings( $client_id );
	}
	
	/**
	 * Pass-through: Returns field specific data for a client
	 *
	 * @param int    $user_id
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	public function getClientDataField( $user_id, $field_name ) {
		return $this->model->get_data( $user_id, $field_name );
	}
	
	/**
	 * Set/Assign the program for the user ID
	 *
	 * @param \WP_User $client - The USER
	 *
	 * @return bool -- DIE()s if we're unable to save the settings.
	 */
	public function saveSettingsForClient( $client, $newProgramIDs, $exerciseLevel, $newCoachIDs ) {
		
		global $currentProgram;
		global $currentClient;
		
		$Program = Program::getInstance();
		$utils   = Utilities::get_instance();
		
		if ( ! current_user_can( 'edit_user' ) ) {
			return false;
		}
		
		$gender    = isset( $client->gender ) ? $client->gender : null;
		$startdate = isset( $client->program_start ) ? $client->program_start : $currentClient->program_start;
		
		Utilities::get_instance()->log( "Setting program IDs for user with ID of {$client->ID}: " . print_r( $newProgramIDs, true ) );
		
		$oldProgramIDs    = get_user_meta( $client->ID, 'e20r-tracker-program-id', false );
		$removeProgramIDs = array_diff( $oldProgramIDs, $newProgramIDs );
		
		Utilities::get_instance()->log( "Remove the following Program IDs for {$client->ID}: " . print_r( $removeProgramIDs, true ) );
		
		if ( ! empty( $removeProgramIDs ) ) {
			
			foreach ( $removeProgramIDs as $programId ) {
				if ( false === delete_user_meta( $client->ID, 'e20r-tracker-program-id', $programId ) ) {
					$utils->log( "Unable to remove user {$client->ID} from program {$programId}" );
				}
			}
		}
		
		// TODO: Doesn't _remove_ a user from the program if they're cleared on the settings page...
		foreach ( $newProgramIDs as $programId ) {
			
			update_user_meta( $client->ID, 'e20r-tracker-program-id', $programId );
			Utilities::get_instance()->log( "Testing whether to add user to program list for {$programId}" );
			
			if ( ! isset( $currentProgram->id ) || ( $programId != $currentProgram->id ) ) {
				
				$Program->init( $programId );
			}
			
			if ( ! in_array( $client->ID, $currentProgram->users ) ) {
				
				$currentProgram->users[] = $client->ID;
				Utilities::get_instance()->log( "Updating program 'users' list with new user" );
				$Program->setValue( $currentProgram->users, 'users', $currentProgram->id );
			}
		}
		
		$existingCoachIDs = get_user_meta( $client->ID, 'e20r-tracker-user-coach_id', false );
		
		// FIXME: Doesn't return the IDs for the difference between the selected and the previously assigned coaches
		$removingCoachIDs = array_diff( $existingCoachIDs, $newCoachIDs );
		
		Utilities::get_instance()->log( "Existing IDs for {$client->ID}: " . print_r( $existingCoachIDs, true ) );
		Utilities::get_instance()->log( "New IDs for {$client->ID}: " . print_r( $newCoachIDs, true ) );
		Utilities::get_instance()->log( "Removing coach IDs for {$client->ID}: " . print_r( $removingCoachIDs, true ) );
		
		foreach ( $removingCoachIDs as $removeCoachID ) {
			
			Utilities::get_instance()->log( "Removing coach {$removeCoachID} for user with ID {$client->ID}" );
			
			if ( false === delete_user_meta( $client->ID, 'e20r-tracker-user-coach_id', $removeCoachID ) ) {
				Utilities::get_instance()->log( "Error removing coach {$removeCoachID} from user meta for {$client->ID}" );
			}
			
			if ( false === delete_user_meta( $removeCoachID, 'e20r-tracker-coaching-client_ids', $client->ID ) ) {
				Utilities::get_instance()->log( "Error removing {$client->ID} from coach's meta (Coach ID: {$removeCoachID})" );
			}
		}
		
		foreach ( $newCoachIDs as $coach_id ) {
			
			Utilities::get_instance()->log( "Assigning & saving coach {$coach_id} for user with ID {$client->ID}" );
			
			if ( false === $this->maybeAssignClientToCoach( $client->ID, $coach_id, $programId ) ) {
				Utilities::get_instance()->log( "Error assigning coach {$coach_id} for to user {$client->ID}" );
			}
			
			if ( ! in_array( $coach_id, $existingCoachIDs ) ) {
				if ( false === add_user_meta( $client->ID, 'e20r-tracker-user-coach_id', $coach_id ) ) {
					Utilities::get_instance()->log( "Error adding {$coach_id} to user's metadata for {$client->ID}" );
				}
			}
		}
		
		// Add role for user if it's not already added
		if ( ! empty( $exerciseLevel ) ) {
			
			$existing_roles = $client->roles;
			
			$roles = wp_roles();
			
			if ( null === $roles->get_role( $exerciseLevel ) ) {
				Utilities::get_instance()->log( "Role {$exerciseLevel} not found on system!!!" );
			}
			
			if ( ! in_array( $exerciseLevel, $existing_roles ) ) {
				
				$client->set_role( '' );
				$existing_roles[] = $exerciseLevel;
				
				foreach ( $existing_roles as $role_name ) {
					Utilities::get_instance()->log( "Adding exercise level {$exerciseLevel} for user {$client->ID}" );
					$client->add_role( $role_name );
				}
				
				wp_cache_flush();
			}
		}
		
		if ( ! empty( $gender ) ) {
			
			$client_data           = (array) $this->getClientInfo( $client->ID );
			
			$client_data['gender'] = $gender;
			
			if ( ! empty( $startdate ) ) {
				$client_data['started_date']  = $startdate;
				$client_data['program_start'] = $startdate;
			}
			
			if ( !empty( $client->member_record_id ) ) {
				$client_data['id'] = $client->member_record_id;
			}
			
			try {
				$this->model->saveData( $client_data );
			} catch ( \Exception $exception ) {
				$utils->log( "Error saving client data: " . $exception->getMessage() );
			}
		}
	}
	
	/**
	 * Assign a coach ($coach_id) to the specified client for the program ID
	 *
	 * @param int $client_id
	 * @param int $coach_id
	 * @param int $program_id
	 *
	 * @return bool
	 */
	public function maybeAssignClientToCoach( $client_id, $coach_id, $program_id ) {
		
		$program_coach_client_list = get_user_meta( $coach_id, 'e20r-tracker-client-program-list', true );
		
		Utilities::get_instance()->log( "Coach {$coach_id} has the following programs & clients he/she is coaching: " . print_r( $program_coach_client_list, true ) );
		
		// Clean up the client list (just in case)
		$program_coach_client_list = $this->cleanCoachClientList( $coach_id, $program_coach_client_list );
		
		if ( empty( $program_coach_client_list ) ) {
			
			$program_coach_client_list = array();
		}
		
		if ( ! isset( $program_coach_client_list[ $program_id ] ) ) {
			
			$program_coach_client_list[ $program_id ] = array();
		}
		
		$existing_program_client_list = $program_coach_client_list[ $program_id ];
		
		// Add client to list of program clients (save if needed)
		if ( ! in_array( $client_id, $existing_program_client_list ) ) {
			
			$existing_program_client_list[]           = $client_id;
			$program_coach_client_list[ $program_id ] = $existing_program_client_list;
			
		}
		
		$coach_client_list = get_user_meta( $coach_id, 'e20r-tracker-coaching-client_ids', false );
		
		Utilities::get_instance()->log( "Assigned user {$client_id} in program {$program_id} to coach {$coach_id}" );
		// TODO: Have to be able to remove clients from the coach's list too!
		
		if ( ! empty( $coach_client_list ) ) {
			
			if ( ! in_array( $client_id, $coach_client_list ) ) {
				
				Utilities::get_instance()->log( "Saved client Id to the array of clients for this coach ($coach_id)" );
				add_user_meta( $coach_id, 'e20r-tracker-coaching-client_ids', $client_id );
			}
		}
		
		if ( false !== ( $programs = get_user_meta( $coach_id, 'e20r-tracker-coaching-program_ids' ) ) ) {
			
			if ( ! in_array( $program_id, $programs ) ) {
				
				Utilities::get_instance()->log( "Saved program id to the array of programs for this coach ($coach_id)" );
				add_user_meta( $coach_id, 'e20r-tracker-coaching-program_ids', $program_id );
			}
		}
		
		return $this->saveCoachesClientList( $coach_id, $coach_client_list );
	}
	
	/**
	 * Fix/Clean up the program/client list for a coach
	 *
	 * @param int   $coach_id
	 * @param array $client_list
	 *
	 * @return array
	 */
	private function cleanCoachClientList( $coach_id, $client_list ) {
		
		$new_list = array();
		
		if ( empty( $client_list ) ) {
			return $new_list;
		}
		
		foreach ( $client_list as $program_id => $clients ) {
			
			if ( ! empty( $program_id ) && ! empty( $clients ) ) {
				$new_list[ $program_id ] = $clients;
			}
		}
		
		if ( ! empty( $new_list ) ) {
			$this->saveCoachesClientList( $coach_id, $new_list );
		}
		
		return $new_list;
	}
	
	/**
	 * Save the list of programs and clients managed by the coach (ID)
	 *
	 * @param int   $coach_id
	 * @param int[] $client_list
	 *
	 * @return bool
	 */
	private function saveCoachesClientList( $coach_id, $client_list ) {
		
		if ( empty( $coach_id ) ) {
			return false;
		}
		
		if ( empty( $client_list ) || ! is_array( $client_list ) ) {
			return false;
		}
		
		return update_user_meta( $coach_id, 'e20r-tracker-client-program-list', $client_list );
	}
	
	/**
	 * Returns the coach ID(s) for the specified client
	 *
	 * @param null $client_id
	 * @param null $program_id
	 *
	 * @return string[]
	 */
	public function get_coach( $client_id = null, $program_id = null ) {
		
		Utilities::get_instance()->log( "Loading coach information for program with ID: " . ( is_null( $program_id ) ? 'Undefined' : $program_id ) );
		
		$coaches = $this->model->get_coach( $client_id, $program_id );
		Utilities::get_instance()->log( "Returning coaches: " . print_r( $coaches, true ) );
		
		return $coaches;
	}
	
	/**
	 * Assign a coach for the new client/user
	 *
	 * @param   integer     $user_id
	 * @param   string|null $gender
	 *
	 * @return bool|mixed
	 */
	public function assignCoach( $user_id, $gender = null ) {
		
		$Program = Program::getInstance();
		global $currentProgram;
		
		Utilities::get_instance()->log( "Loading program settings for {$user_id}" );
		
		$old_program = $currentProgram;
		$Program->getProgramIdForUser( $user_id );
		
		$coach_id = false;
		
		switch ( strtolower( $gender ) ) {
			case 'm':
				Utilities::get_instance()->log( "attempting to find a male coach for {$user_id} in program {$currentProgram->id}: " . print_r( $currentProgram, true ) );
				
				$coach = $this->findNextCoach( $currentProgram->male_coaches, $currentProgram->id );
				break;
			
			case 'f':
				Utilities::get_instance()->log( "attempting to find a female coach for {$user_id} in program {$currentProgram->id}" );
				$coach = $this->findNextCoach( $currentProgram->female_coaches, $currentProgram->id );
				break;
			
			default:
				Utilities::get_instance()->log( "attempting to find a coach for {$user_id} in program {$currentProgram->id}" );
				$coaches = array_merge( $currentProgram->male_coaches, $currentProgram->female_coaches );
				$coach   = $this->findNextCoach( $coaches, $currentProgram->id );
		}
		
		if ( ! empty( $coach ) ) {
			Utilities::get_instance()->log( "Found coach: {$coach} for {$user_id}" );
			$this->maybeAssignClientToCoach( $currentProgram->id, $coach, $user_id );
			$coach_id = $coach;
		}
		
		$currentProgram = $old_program;
		
		return $coach_id;
	}
	
	/**
	 * Locate the next available coach for the program (ID)
	 *
	 * @param array $coach_arr
	 * @param int   $program_id
	 *
	 * @return bool|int|null|string
	 */
	public function findNextCoach( $coach_arr, $program_id ) {
		
		Utilities::get_instance()->log( "Searching for the coach with the fewest clients so far.." );
		
		if ( empty( $coach_arr ) ) {
			return false;
		}
		
		$coaches = array();
		
		foreach ( $coach_arr as $cId ) {
			
			Utilities::get_instance()->log( "Processing coach info: " . print_r( $cId, true ) );
			
			$client_list = get_user_meta( $cId, 'e20r-tracker-client-program-list', true );
			
			if ( ( false !== $client_list ) && ( ! empty( $client_list ) ) ) {
				
				$coaches[ $cId ] = count( $client_list );
			} else if ( empty( $cId ) ) {
				$coaches[ - 1 ] = 0;
			} else {
				$coaches[ $cId ] = 0;
			}
			
			Utilities::get_instance()->log( "Client list for coach consists of " . ( false === $client_list ? 'None' : count( $client_list ) . " entries: " . print_r( $client_list, true ) ) );
			
		}
		
		Utilities::get_instance()->log( "List of coaches and the number of clients they have been assigned... " . print_r( $coaches, true ) );
		
		if ( asort( $coaches ) ) {
			
			reset( $coaches );
			$coach_id = key( $coaches );
			
			Utilities::get_instance()->log( "Selected coach with ID: {$coach_id} in program {$program_id}" );
			
			return $coach_id;
		}
		
		return false;
	}
	
	public function updateForClientInfo( $program_action ) {
		
		Utilities::get_instance()->log( "Program Action to update: " . print_r( $program_action, true ) );
		
		return $program_action;
	}
	
	/**
	 * AJAX handler for Lenght/Weight Unit updates (including recalculating)
	 */
	public function updateUnitTypes() {
		
		Utilities::get_instance()->log( "Attempting to update the Length or weight Units via AJAX" );
		
		check_ajax_referer( 'e20r-tracker-progress', 'e20r-progress-nonce' );
		
		Utilities::get_instance()->log( "POST content: " . print_r( $_POST, true ) );
		
		global $current_user;
		global $currentClient;
		
		$Measurements = Measurements::getInstance();
		
		$currentClient->user_id = isset( $_POST['user-id'] ) ? intval( $_POST['user-id'] ) : $current_user->ID;
		$dimension              = isset( $_POST['dimension'] ) ? sanitize_text_field( $_POST['dimension'] ) : null;
		$value                  = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : null;
		
		// Configure the client data object(s).
		$this->init();
		$Measurements->setClient( $currentClient->user_id );
		
		// Update the data for this user in the measurements table.
		try {
			
			$Measurements->updateMeasurementsForType( $dimension, $value );
		} catch ( \Exception $e ) {
			Utilities::get_instance()->log( "Error updating measurements for new measurement type(s): " . $e->getMessage() );
			wp_send_json_error( sprintf( __( "Error updating existing data: %s", "e20r-tracker" ), $e->getMessage() ) );
			exit();
		}
		
		// Save the actual setting for the current user
		
		$this->weightunits = $this->getWeightUnit();
		$this->lengthunits = $this->getLengthUnit();
		
		if ( $dimension == 'weight' ) {
			$this->weightunits = $value;
		}
		
		if ( $dimension == 'length' ) {
			$this->lengthunits = $value;
		}
		
		// Update the settings for the user
		try {
			$this->model->saveUnitInfo( $this->lengthunits, $this->weightunits );
		} catch ( \Exception $e ) {
			Utilities::get_instance()->log( "Error updating measurement unit for {$dimension}" );
			wp_send_json_error( sprintf( __( "Unable to save new %s type ", "e20r-tracker" ), $dimension ) );
			exit();
		}
		
		Utilities::get_instance()->log( "Unit type updated" );
		wp_send_json_success( __( "All data updated ", "e20r-tracker" ) );
		exit();
	}
	
	/**
	 * Return the list of user(s) for a specific membership level
	 */
	public function ajax_getMemberlistForLevel() {
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' );
		$levelId = ! empty( $_REQUEST['hidden_e20r_level'] ) ? intval( $_REQUEST['hidden_e20r_level'] ) : 0;
		
		$this->init();
		
		Utilities::get_instance()->log( "Program requested: {$levelId}" );
		
		if ( $levelId != 0 ) {
			
			// $levels = $Tracker->getMembershipLevels( $levelId );
			// $this->load_levels( $levelObj->name );
			// Utilities::get_instance()->log("Loading members: " . print_r( $levels, true ) );
			
			$data = $this->view->viewMemberSelect( $levelId );
		} else {
			
			// $this->view->load_levels();
			$data = $this->view->viewMemberSelect();
		}
		
		wp_send_json_success( $data );
		exit();
		
	}
	
	/**
	 * Update the role (exercise level) for the user_id
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function updateRoleForUser( $user_id ) {
		
		$utils = Utilities::get_instance();
		
		if ( ! current_user_can( 'edit_user' ) ) {
			return false;
		}
		
		$role_name   = $utils->get_variable( 'e20r-tracker-user-role', '' );
		$newPrograms = $utils->get_variable( 'e20r-tracker-coach-for-programs', array() );
		
		$user_roles = apply_filters( 'e20r-tracker-configured-roles', array() );
		
		if ( empty( $role_name ) ) {
			return false;
		}
		
		$utils->log( "Setting role name to: ({$role_name}) for user with ID of {$user_id}. Available roles: " . print_r( $user_roles, true ) );
		
		$u = get_user_by( 'id', $user_id );
		
		if ( ! empty( $u ) && ! empty( $role_name ) ) {
			
			$u->add_role( $role_name );
		} else if ( ! empty( $u ) ) {
			if ( in_array( $user_roles['coach']['role'], $u->roles ) ) {
				
				$utils->log( "Removing 'coach' capability/role for user {$user_id}" );
				$u->remove_role( $user_roles['coach']['role'] );
			}
		}
		
		if ( ! empty( $u ) ) {
			wp_cache_flush();
		}
		
		$existing_programs = get_user_meta( $user_id, "e20r-tracker-coaching-program_ids", false );
		$remove_programs   = array_diff( $existing_programs, $newPrograms );
		
		if ( ! empty( $remove_programs ) ) {
			foreach ( $remove_programs as $remove_program ) {
				if ( false === delete_user_meta( $user_id, 'e20r-tracker-coaching-program_ids', $remove_program ) ) {
					$utils->log( "Unable to delete program id {$remove_program} for user {$user_id}" );
				}
			}
		}
		
		foreach ( $newPrograms as $p ) {
			
			if ( ! in_array( $p, $existing_programs ) ) {
				$utils->log( "Adding program IDs this user is a coach for: " . print_r( $newPrograms, true ) );
				update_user_meta( $user_id, 'e20r-tracker-coaching-program_ids', $p );
			}
		}
		
		$current_programs = get_user_meta( $user_id, 'e20r-tracker-coaching-program_ids', false );
		
		if ( false === $current_programs ) {
			
			$utils->log( "Unable to save the list of programs this user is a coach for" );
			
			return false;
		}
		
		Utilities::get_instance()->log( "User roles are now: " . print_r( $u->caps, true ) );
		Utilities::get_instance()->log( "And they are a coach for: " . print_r( $current_programs, true ) );
		
		return true;
	}
	
	/**
	 * Generate 'select role' dialog on User's profile page (for admins)
	 *
	 * @param \WP_User $user
	 */
	public function selectRoleForUser( $user ) {
		
		$Program = Program::getInstance();
		
		Utilities::get_instance()->log( "Various roles & capabilities for user {$user->ID}" );
		
		$allPrograms = $Program->getPrograms();
		Utilities::get_instance()->log( "Programs: " . print_r( $allPrograms, true ) );
		
		echo $this->view->profileViewUserSettings( $user->ID, $allPrograms );
	}
	
	/**
	 * Queue an email message to the client (from the coach/admin)
	 */
	public function ajax_sendClientMessage() {
		
		$Tracker = Tracker::getInstance();
		$Program = Program::getInstance();
		
		global $currentProgram;
		
		$when = null;
		Utilities::get_instance()->log( 'Requesting client detail' );
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' );
		
		Utilities::get_instance()->log( "Nonce is OK" );
		Utilities::get_instance()->log( "Request: " . print_r( $_REQUEST, true ) );
		
		// $to_uid = isset( $_POST['email-to-id']) ? $Tracker->sanitize( $_POST['email-to-id']) : null;
		$email_args['to_email']  = isset( $_POST['email-to'] ) ? $Tracker->sanitize( $_POST['email-to'] ) : null;
		$email_args['cc']        = isset( $_POST['email-cc'] ) ? $Tracker->sanitize( $_POST['email-cc'] ) : null;
		$email_args['from_uid']  = isset( $_POST['email-from-id'] ) ? $Tracker->sanitize( $_POST['email-from-id'] ) : null;
		$email_args['from']      = isset( $_POST['email-from'] ) ? $Tracker->sanitize( $_POST['email-from'] ) : null;
		$email_args['from_name'] = isset( $_POST['email-from-name'] ) ? $Tracker->sanitize( $_POST['email-from-name'] ) : null;
		$email_args['subject']   = isset( $_POST['subject'] ) ? $Tracker->sanitize( $_POST['subject'] ) : ' ';
		$email_args['content']   = isset( $_POST['e20r-message-content'] ) ? wp_kses_post( $_POST['e20r-message-content'] ) : null;
		$email_args['time']      = isset( $_POST['when-to-send'] ) ? $Tracker->sanitize( $_POST['when-to-send'] ) : null;
		$email_args['content']   = stripslashes_deep( $email_args['content'] );
		
		
		Utilities::get_instance()->log( "Checking whether to schedule sending this message: {$email_args['time']}" );
		if ( ! empty( $email_args['time'] ) ) {
			
			if ( false === ( $when = strtotime( $email_args['time'] . " " . get_option( 'timezone_string' ) ) ) ) {
				wp_send_json_error( array( 'error' => 3 ) ); // 3 == 'Incorrect date/time provided'.
				exit();
			}
			
			Utilities::get_instance()->log( "Scheduled to be sent at: {$when}" );
		}
		
		Utilities::get_instance()->log( "Get the User info for the sender" );
		
		if ( ! is_null( $email_args['from_uid'] ) ) {
			$email_args['from_user'] = get_user_by( 'id', $email_args['from_uid'] );
		}
		
		Utilities::get_instance()->log( "Get the User info for the receiver" );
		
		$email_args['to_user'] = get_user_by( 'email', $email_args['to_email'] );
		$Program->getProgramIdForUser( $email_args['to_user']->ID );
		
		$email_args['program_id'] = $currentProgram->id;
		
		// $sendTo = "{$to->display_name} <{$to_email}>";
		Utilities::get_instance()->log( "Try to schedule the email for transmission" );
		
		$status = $this->schedule_email( $email_args, $when );
		
		if ( true == $status ) {
			Utilities::get_instance()->log( "Successfully scheduled the message to be sent" );
			
			wp_send_json_success();
			exit();
		}
		
		Utilities::get_instance()->log( "Error while scheduling message to be sent" );
		wp_send_json_error();
		exit();
	}
	
	/**
	 * Schedule when to send the email
	 *
	 * @param array $email_args
	 * @param null  $when
	 *
	 * @return bool
	 */
	public function schedule_email( $email_args, $when = null ) {
		
		if ( is_null( $when ) ) {
			Utilities::get_instance()->log( "No need to schedule the email for transmission. We're sending it right away." );
			
			return $this->send_email_to_client( $email_args );
		} else {
			// Send message to user at specified time.
			Utilities::get_instance()->log( "Schedule the email for transmission. {$when}" );
			$ret = wp_schedule_single_event( $when, 'e20r_schedule_email_for_client', array( $email_args ) );
			
			if ( is_null( $ret ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Trigger wp_mail() operation for client message(s)
	 *
	 * @param array $email_array
	 *
	 * @return bool
	 */
	public function send_email_to_client( $email_array ) {
		
		$headers[] = "Content-type: text/html";
		$headers[] = "Cc: " . $email_array['cc'];
		$headers[] = "From: " . $email_array['from'];
		
		$message = $this->createEmailBody( $email_array['subject'], $email_array['content'] );
		
		// Add filters that are email specific
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		add_action( 'wp_mail_failed', array( $this, 'logMailFailure' ), 10, 1 );
		
		$status = wp_mail( sanitize_email( $email_array['to_email'] ), sanitize_text_field( $email_array['subject'] ), $message, $headers, null );
		
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		
		if ( true == $status ) {
			Utilities::get_instance()->log( "Successfully transferred the info to wp_mail()" );
			
			if ( ! $this->model->save_message_to_history( $email_array['to_user']->ID, $email_array['program_id'], $email_array['from_uid'], $message, $email_array['subject'] ) ) {
				Utilities::get_instance()->log( "Error while saving message history for {$email_array['to_user']->ID}" );
				
				return false;
			}
			
			Utilities::get_instance()->log( "Successfully saved the message to the user message history table" );
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Generate the email message body
	 *
	 * @param string $subject
	 * @param string $content
	 *
	 * @return string
	 */
	private function createEmailBody( $subject, $content ) {
		
		ob_start();
		?>
        <html>
        <head>
            <title><?php esc_attr_e( wp_unslash( $subject ) ); ?></title>
        </head>
        <body>
        <div id="the_content">
			<?php echo wp_kses_post( $content ); ?>
        </div>
        </body>
        </html>
		<?php
		$html = ob_get_clean();
		
		return $html;
	}
	
	/**
	 * Log info about a failed email message and display it in the backend (if applicable)
	 *
	 * @param \WP_Error $wp_error
	 *
	 * @since 3.0 - ENHANCEMENT: Better logging of errors during email transmission
	 */
	public function logMailFailure( $wp_error ) {
		
		if ( is_wp_error( $wp_error ) ) {
			
			$utils        = Utilities::get_instance();
			$message_info = $wp_error->get_error_data( 'wp_mail_failed' );
			
			foreach ( $message_info['to'] as $to ) {
				$msg = sprintf(
					__( 'Warning: Did not send "%1$s" to "%2$s"! Status: %3$s', 'scba-customizations' ),
					$message_info['subject'],
					$to,
					$wp_error->get_error_message( 'wp_mail_failed' )
				);
				
				$utils->log( print_r( $msg, true) );
				$utils->add_message( $msg, 'error' );
			}
		}
	}
	
	/**
	 * Save the user's Interview responses (securely)
	 *
	 * @param $data
	 * @param $entry
	 *
	 * @return bool
	 */
	public function saveInterview( $data, $entry ) {
		
		try {
			$this->model->saveClientInterview( $data );
		} catch ( \Exception $exception ) {
			Utilities::get_instance()->log( "Problem saving the client interview. Error: " . $exception->getMessage() );
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Set the wp_mail() content type to HTML
	 *
	 * @return string
	 */
	public function set_html_content_type() {
		
		return 'text/html';
	}
	
	/**
	 * Debug logging of the mail message
	 *
	 * @param array $args
	 */
	public function test_wp_mail( $args ) {
		
		$debug = var_export( $args, true );
		Utilities::get_instance()->log( print_r( $debug, true) );
	}
	
	/**
	 * Fetch and generate the client's email message history
	 */
	public function ajax_ClientMessageHistory() {
		
		Utilities::get_instance()->log( 'Requesting client detail' );
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' );
		
		Utilities::get_instance()->log( "Nonce is OK" );
		
		Utilities::get_instance()->log( "Request: " . print_r( $_REQUEST, true ) );
		
		global $current_user;
		$Program = Program::getInstance();
		$Tracker = Tracker::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		if ( ! $Access->is_a_coach( $current_user->ID ) ) {
			
			Utilities::get_instance()->log( "User isn't a coach. Return error & force redirect" );
			wp_send_json_error( array( 'error' => 403 ) );
			exit();
		}
		
		$user_id = isset( $_POST['client-id'] ) ? $Tracker->sanitize( $_POST['client-id'] ) : $current_user->ID;
		$Program->getProgramIdForUser( $user_id );
		
		Utilities::get_instance()->log( "Loading message history from DB for {$user_id}" );
		$html = $this->loadClientMessages( $user_id );
		
		Utilities::get_instance()->log( "Generating message history HTML" );
		// $html = $this->view->viewMessageHistory( $user_id, $messages );
		
		wp_send_json_success( array( 'html' => $html ) );
		exit();
	}
	
	/**
	 * Load previously sent messages between client and coach
	 *
	 * @param int $client_id
	 *
	 * @return string
	 */
	public function loadClientMessages( $client_id ) {
		
		global $currentClient;
		global $currentProgram;
		
		if ( ! isset( $currentClient->user_id ) ) {
			$this->setClient( $client_id );
		}
		
		if ( ! isset( $currentProgram->id ) ) {
			$Program = Program::getInstance();
			
			$Program->getProgramIdForUser( $client_id );
		}
		
		$client_messages = $this->model->load_message_history( $client_id );
		
		return $this->view->viewMessageHistory( $client_id, $client_messages );
	}
	
	/**
	 * Send client message history (formatted) to front-end
	 */
	public function ajax_showClientMessage() {
		
		Utilities::get_instance()->log( 'Requesting client detail' );
		
		check_ajax_referer( 'e20r-tracker-data', 'e20r-tracker-clients-nonce' );
		
		Utilities::get_instance()->log( "Nonce is OK" );
		
		Utilities::get_instance()->log( "Request: " . print_r( $_REQUEST, true ) );
		
		global $current_user;
		$Program = Program::getInstance();
		$Article = Article::getInstance();
		
		$Tracker = Tracker::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		global $currentProgram;
		
		if ( ! $Access->is_a_coach( $current_user->ID ) ) {
			
			Utilities::get_instance()->log( "User isn't a coach. Return error & force redirect" );
			wp_send_json_error( array( 'error' => 403 ) );
			exit();
		}
		
		$user_id = isset( $_POST['client-id'] ) ? $Tracker->sanitize( $_POST['client-id'] ) : $current_user->ID;
		$Program->getProgramIdForUser( $user_id );
		
		$articles = $Article->findArticles( 'post_id', $currentProgram->intake_form, $currentProgram->id );
		$a        = $articles[0];
		
		Utilities::get_instance()->log( "Article ID: {$a->id}" );
		
		if ( ! $Article->isSurvey( $a->id ) ) {
			wp_send_json_error( array( 'error' => __( 'Configuration error. Please report to tech support.', 'e20r-tracker' ) ) );
			exit();
		} else {
			Utilities::get_instance()->log( "Loading article configuration for the survey!" );
			$Article->init( $a->id );
		}
		
		Utilities::get_instance()->log( "Load client data..." );
		
		// Loads the program specific client information we've got stored.
		$this->model->get_data( $user_id );
		
		$html = $this->view->viewClientContact( $user_id );
		
		wp_send_json_success( array( 'html' => $html ) );
		exit();
	}
	
	/**
	 * Send client data details to the front-end
	 */
	public function ajax_clientDetail() {
		
		global $current_user;
		$Tracker = Tracker::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		if ( ! is_user_logged_in() ) {
			
			Utilities::get_instance()->log( "User isn't logged in. Return error & force redirect" );
			wp_send_json_error( array( 'error' => 403 ) );
			exit();
		}
		
		$nonce             = isset( $_POST['e20r-tracker-clients-nonce'] ) ? $_POST['e20r-tracker-clients-nonce'] : null;
		$user_profile_page = $Tracker->sanitize( 'tracker-user-profile-page' );
		
		$valid_nonce = wp_verify_nonce( $nonce, 'e20r-tracker-data' );
		
		if ( ! $Access->is_a_coach( $current_user->ID ) && false === $user_profile_page ) {
			
			Utilities::get_instance()->log( "User isn't a coach. Return error & force redirect" );
			wp_send_json_error( array( 'error' => 403 ) );
			exit();
		}
		
		if ( false === $valid_nonce ) {
			Utilities::get_instance()->log( "Invalid Nonce" );
			wp_send_json_error( array( 'error' => 403 ) );
			exit();
		} else {
			Utilities::get_instance()->log( "Nonce is OK" );
		}
		
		Utilities::get_instance()->log( 'Requesting client detail' );
		Utilities::get_instance()->log( "Request: " . print_r( $_REQUEST, true ) );
		
		$Program = Program::getInstance();
		$Article = Article::getInstance();
		
		global $currentProgram;
		
		$user_id = isset( $_POST['client-id'] ) ? $Tracker->sanitize( $_POST['client-id'] ) : $current_user->ID;
		$type    = isset( $_POST['tab-id'] ) ? $Tracker->sanitize( $_POST['tab-id'] ) : 'client-info';
		$Program->getProgramIdForUser( $user_id );
		
		$articles = $Article->findArticles( 'post_id', $currentProgram->intake_form, $currentProgram->id );
		$a        = $articles[0];
		
		Utilities::get_instance()->log( "Article ID: " );
		Utilities::get_instance()->log( print_r( $a->id, true) );
		
		if ( ! $Article->isSurvey( $a->id ) ) {
			wp_send_json_error( array( 'error' => 'Configuration error. Please report to tech support.' ) );
			exit();
		} else {
			Utilities::get_instance()->log( "Loading article configuration for the survey!" );
			$Article->init( $a->id );
		}
		
		switch ( $type ) {
			case 'client-info':
				Utilities::get_instance()->log( "Loading client data" );
				$html = $this->load_clientDetail( $user_id );
				break;
			
			case 'achievements':
				Utilities::get_instance()->log( "Loading client achievement data" );
				$html = $this->load_achievementsData( $user_id );
				// Utilities::get_instance()->log($html);
				break;
			
			case 'assignments':
				Utilities::get_instance()->log( "Loading client assignment data" );
				$html = $this->load_assignmentsData( $user_id );
				break;
			
			case 'activities':
				Utilities::get_instance()->log( "Loading client activity data" );
				$html = $this->load_activityData( $user_id );
				break;
			
			default:
				Utilities::get_instance()->log( "Default: Loading client information" );
				$html = $this->load_clientDetail( $user_id );
		}
		
		wp_send_json_success( array( 'html' => $html ) );
		exit();
	}
	
	/**
	 * Generate client detail to display/include
	 *
	 * @param int $client_id
	 *
	 * @return null|string
	 */
	public function load_clientDetail( $client_id ) {
		
		Utilities::get_instance()->log( "Load client data..." );
		$Program = Program::getInstance();
		$Article = Article::getInstance();
		
		global $currentProgram;
		global $currentArticle;
		
		Utilities::get_instance()->log( "Load program info for this clientID." );
		// Loads the program specific client information we've got stored.
		$Program->getProgramIdForUser( $client_id );
		
		if ( empty( $currentProgram->id ) ) {
			Utilities::get_instance()->log( "ERROR: No program ID defined for user {$client_id}!!!" );
			
			return null;
		}
		
		Utilities::get_instance()->log( "Find article ID for the intake form {$currentProgram->intake_form} for the program ({$currentProgram->id})." );
		$article = $Article->findArticles( 'post_id', $currentProgram->intake_form, $currentProgram->id );
		
		Utilities::get_instance()->log( "Returned " . count( $article ) . " articles on behalf of the intake form" );
		
		if ( ! empty( $article ) ) {
			
			Utilities::get_instance()->log( "Load article configuration. " . print_r( $article[0], true ) );
			
			$Article->init( $article[0]->id );
		} else {
			Utilities::get_instance()->log( "ERROR: No article defined for the Welcome Survey!!!" );
			
			return null;
		}
		
		Utilities::get_instance()->log( "Load the client information for {$client_id} in program {$currentProgram->id} for article {$currentArticle->id}" );
		$this->model->get_data( $client_id );
		
		Utilities::get_instance()->log( "Show client detail for {$client_id} related to {$currentArticle->id} and {$currentProgram->id}" );
		
		return $this->view->viewClientDetail( $client_id );
	}
	
	/**
	 * Generate Achievement list for $client_id
	 *
	 * @param int $client_id
	 *
	 * @return string
	 */
	public function load_achievementsData( $client_id ) {
		
		$Action = Action::getInstance();
		
		return $Action->listUserAchievements( $client_id );
	}
	
	/**
	 * Generate Assignment list for $client_id
	 *
	 * @param int $client_id
	 *
	 * @return string
	 */
	public function load_assignmentsData( $client_id ) {
		
		$Assignment = Assignment::getInstance();
		
		return $Assignment->listUserAssignments( $client_id );
	}
	
	/**
	 * Generate Activity list for $client_id
	 *
	 * @param int $client_id
	 *
	 * @return string
	 */
	public function load_activityData( $client_id ) {
		
		$Workout = Workout::getInstance();
		
		return $Workout->listUserActivities( $client_id );
	}
	
	/**
	 * Short Code Handler: Client Profile page(s)
	 *
	 * @param null|array  $atts
	 * @param null|string $content
	 *
	 * @return null|string
	 */
	public function shortcode_clientProfile( $atts = null, $content = null ) {
		
		Utilities::get_instance()->log( "Loading shortcode data for the client profile page." );
		// Utilities::get_instance()->log($content);
		
		global $current_user;
		
		$Program      = Program::getInstance();
		$Action       = Action::getInstance();
		$Assignment   = Assignment::getInstance();
		$Workout      = Workout::getInstance();
		$Article      = Article::getInstance();
		$Measurements = Measurements::getInstance();
		
		global $currentArticle;
		
		$html = null;
		
		if ( ! is_user_logged_in() || ( ! $this->hasDataAccess( $current_user->ID ) ) ) {
			
			auth_redirect();
		} else {
			
			$user_id = $current_user->ID;
			$Program->getProgramIdForUser( $user_id );
		}
		
		/* Load views for the profile page tabs */
		$config = $Action->configure_dailyProgress();
		
		$code_atts = shortcode_atts( array(
			'use_cards' => false,
		), $atts );
		
		foreach ( $code_atts as $key => $val ) {
			
			Utilities::get_instance()->log( "e20r_profile shortcode --> Key: {$key} -> {$val}" );
			$config->{$key} = $val;
		}
		
		if ( in_array( strtolower( $config->use_cards ), array( 'yes', 'true', '1' ) ) ) {
			
			Utilities::get_instance()->log( "User requested card based dashboard: {$config->use_cards}" );
			$config->use_cards = true;
		}
		
		if ( in_array( strtolower( $config->use_cards ), array( 'no', 'false', '0' ) ) ) {
			
			Utilities::get_instance()->log( "User requested old-style dashboard: {$config->use_cards}" );
			$config->use_cards = false;
		}
		
		if ( ! isset( $config->use_cards ) ) {
			$config->use_cards = false;
		}
		
		if ( $this->completeInterview( $config->userId ) ) {
			$interview_descr = 'Saved interview';
		} else {
			
			$interview_descr = '<div style="color: darkred; text-decoration: underline; font-weight: bolder;">' . __( "Please complete interview", "e20r-tracker" ) . '</div>';
		}
		
		$interview_html = '<div id="e20r-profile-interview">' . $this->view_interview( $config->userId ) . '</div>';
		$interview      = array( $interview_descr, $interview_html );
		
		if ( ! $currentArticle->is_preview_day ) {
			
			Utilities::get_instance()->log( "Configure user specific data" );
			
			$this->model->setUser( $config->userId );
			// $this->setClient($user_id);
			
			$dimensions = array( 'width' => '500', 'height' => '270', 'htype' => 'px', 'wtype' => 'px' );
			// $pDimensions = array( 'width' => '90', 'height' => '1024', 'htype' => 'px', 'wtype' => '%' );
			
			Utilities::get_instance()->log( "Loading progress data..." );
			$measurements = $Measurements->getMeasurement( 'all', false );
			
			if ( true === $this->completeInterview( $config->userId ) ) {
				$measurement_view = $Measurements->showTableOfMeasurements( $config->userId, $measurements, $dimensions, true, false );
			} else {
				$measurement_view = '<div class="e20r-progress-no-measurement">' . $Program->incompleteIntakeForm() . '</div>';
			}
			
			$assignments  = $Assignment->listUserAssignments( $config->userId );
			$activities   = $Workout->listUserActivities( $config->userId );
			$achievements = $Action->listUserAchievements( $config->userId );
			
			$progress = array(
				'Measurements' => '<div id="e20r-progress-measurements">' . $measurement_view . '</div>',
				'Assignments'  => '<div id="e20r-progress-assignments"><br/>' . $assignments . '</div>',
				'Activities'   => '<div id="e20r-progress-activities">' . $activities . '</div>',
				'Achievements' => '<div id="e20r-progress-achievements">' . $achievements . '</div>',
			);
			
			$dashboard = array(
				'Your dashboard',
				'<div id="e20r-daily-progress">' . $Action->dailyProgress( $config ) . '</div>',
			);
			/*
                       $activity = array(
                           'Your activity',
                           '<div id="e20r-profile-activity">' . $Workout->prepare_activity( $config ) . '</div>'
                       );
            */
			$progress_html = array(
				'Your progress',
				'<div id="e20r-profile-status">' . $Measurements->showProgress( $progress, null, false ) . '</div>',
			);
			
			$tabs = array(
				'Home'      => $dashboard,
//                'Activity'          => $activity,
				'Progress'  => $progress_html,
				'Interview' => $interview,
			);
		} else {
			
			$lesson_prefix = preg_replace( '/\[|\]/', '', $currentArticle->prefix );
			$lesson        = array(
				'Your ' . lcfirst( $lesson_prefix ),
				'<div id="e20r-profile-lesson">' . $Article->load_lesson( $config->articleId ) . '</div>',
			);
			
			$tabs = array(
				$lesson_prefix => $lesson,
				'Interview'    => $interview,
			);
		}
		
		$html = $this->view->viewClientProfile( $tabs );
		Utilities::get_instance()->log( "Display the HTML for the e20r_profile short code: " . strlen( $html ) );
		
		return $html;
		
	}
	
	/**
	 * Does the $client_id have the permission to access data
	 *
	 * @param int $client_id
	 *
	 * @return bool
	 */
	public function hasDataAccess( $client_id ) {
		
		global $current_user;
		global $currentClient;
		
		$Tracker = Tracker::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		Utilities::get_instance()->log( "Client being validated: " . $client_id );
		
		if ( ! empty( $client_id ) ) {
			
			// $client = get_user_by( 'id', $client_id );
			Utilities::get_instance()->log( "Real user Id provided " );
			
			$has_access = false;
			if ( ( $current_user->ID != $client_id ) &&
			     ( ( $Tracker->isActiveClient( $client_id ) ) ||
			       ( $Access->is_a_coach( $current_user->ID ) ) )
			) {
				
				$has_access = true;
			} else if ( $current_user->ID == $client_id ) {
				$has_access = true;
			}
			
			if ( true == $has_access && ( empty( $currentClient->user_id ) || empty( $currentClient->program_id ) ) ) {
				$this->setClient( $client_id );
			}
			// Make sure the $current_user has the right to view the data for $client_id
			
		}
		
		return $has_access;
	}
	
	/**
	 * Verify whether the user completed their intake interview
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function completeInterview( $user_id ) {
		
		Utilities::get_instance()->log( "Checking if interview was completed" );
		// $data = $this->model->get_data( $user_id, 'completed_date');
		
		if ( ! isset( $this->interview_status_loaded ) || ( false === $this->interview_status_loaded ) ) {
			
			Utilities::get_instance()->log( "Not previously checked for interview status. Doing so now." );
			$is_complete = $this->model->interview_complete( $user_id );
			
			$this->interview_status        = $is_complete;
			$this->interview_status_loaded = true;
		} else {
			Utilities::get_instance()->log( "Interview status has been checked already. Returning status" );
			$is_complete = $this->interview_status;
		}
		// Utilities::get_instance()->log("completed_date field contains: ");
		// Utilities::get_instance()->log($data);
		Utilities::get_instance()->log( "Returning interview status of: " . ( $is_complete ? 'true' : 'false' ) );
		
		return ( ! $is_complete ? false : true );
	}
	
	/**
	 * Generate the Interview information
	 *
	 * @param int $client_id
	 *
	 * @return string
	 */
	public function view_interview( $client_id ) {
		
		$Article = Article::getInstance();
		
		global $currentProgram;
		
		$content = null;
		
		if ( isset( $currentProgram->intake_form ) ) {
			
			$interview = get_post( $currentProgram->intake_form );
			
			if ( isset( $interview->post_content ) && ! empty( $interview->post_content ) ) {
				
				Utilities::get_instance()->log( "Applying the content filter to the interview page content" );
				$content = apply_filters( 'the_content', $interview->post_content );
				
				Utilities::get_instance()->log( "Validate whether the interview has been completed by the user" );
				$complete = $this->completeInterview( $client_id );
				
				Utilities::get_instance()->log( "Loading the Welcome interview page & the users interview is saved already" );
				
				$update_reminder = $Article->interviewCompleteLabel( $interview->post_title, $complete );
				
				$content = $update_reminder . $content;
			}
		} else {
			Utilities::get_instance()->log( "ERROR: No client Interview form has been configured! " );
		}
		
		Utilities::get_instance()->log( "Returning HTML" );
		
		return $content;
	}
	
	/**
	 * Generate the front-end "Coaching" page list of clients (and their status)
	 *
	 * @param null|array $attributes
	 *
	 * @return string
	 */
	public function shortcode_clientList( $attributes = null ) {
		
		Utilities::get_instance()->log( "Loading shortcode for the coach list of clients" );
		
		$Program = Program::getInstance();
		$Access  = Tracker_Access::getInstance();
		
		global $currentProgram;
		global $current_user;
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
			wp_die();
		}
		
		if ( ( ! $Access->is_a_coach( $current_user->ID ) ) ) {
			
			$this->setNotCoachMsg();
			wp_die();
		}
		
		$client_list = $this->model->get_clients( $current_user->ID );
		$list        = array();
		
		foreach ( $client_list as $pId => $clients ) {
			
			foreach ( $clients as $k => $client ) {
				
				// $Program->getProgramIdForUser( $client->ID );
				// $Program->setProgram( $pId );
				
				$coach = $this->model->get_coach( $client->ID );
				
				$client->status                = new \stdClass();
				$client->status->program_id    = $Program->getProgramIdForUser( $client->ID );
				$client->status->program_start = $Program->getProgramStart( $client->status->program_id, $client->ID );
				$client->status->coach         = array( $currentProgram->id => key( $coach ) );
				$client->status->recent_login  = get_user_meta( $client->ID, '_e20r-tracker-last-login', true );
				$mHistory                      = $this->model->load_message_history( $client->ID );
				
				$client->status->total_messages = count( $mHistory );
				
				if ( ! empty( $mHistory ) ) {
					
					krsort( $mHistory );
					reset( $mHistory );
					
					Utilities::get_instance()->log( "Sorted message history for user {$client->ID}" );
					$when = key( $mHistory );
					$msg  = isset( $mHistory[ $when ] ) ? $mHistory[ $when ] : null;
					
					$client->status->last_message        = array( $when => $msg->topic );
					$client->status->last_message_sender = $msg->sender_id;
				} else {
					$client->status->last_message        = array( 'empty' => __( 'No message sent via this website', "e20r-tracker" ) );
					$client->status->last_message_sender = null;
				}
				
				Utilities::get_instance()->log( "Most recent message: " . print_r( $client->status->last_message, true ) );
				
				if ( ! isset( $list[ $currentProgram->id ] ) ) {
					
					$list[ $currentProgram->id ] = array();
				}
				
				$list[ $pId ][ $k ] = $client;
			}
		}
		
		Utilities::get_instance()->log( "Showing client information" );
		
		// Utilities::get_instance()->log($list);
		
		return $this->view->displayClientList( $list );
		
	}
}
