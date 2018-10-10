<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits, educational reminders, etc.
 * Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers;

use E20R\Utilities\Utilities;

class Tracker_Access {
	/**
	 * @var null|Tracker_Access
	 */
	static private $instance = null;
	
	/**
	 * Tracker_Access constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Tracker_Access|null;
	 */
	static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Load hooks for this class
	 */
	public function loadHooks() {
	
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
		$Tracker = Tracker::getInstance();
		
		global $currentArticle;
		
		$retVal = false;
		$saved = $currentArticle;
		
		Utilities::get_instance()->log("Checking {$userId}'s access to {$postId}" );
		
		if ( user_can( $userId, 'publish_posts' ) && ( is_preview() ) ) {
			
			Utilities::get_instance()->log("Post #{$postId} is a preview for {$userId}. Granting editor/admin access to the preview");
			return true;
		}
		
		Utilities::get_instance()->log("Checking whether to use PMPro's pmpro_has_membership_access() function" );
		
		if ( function_exists( 'pmpro_has_membership_access' ) ) {
			
			Utilities::get_instance()->log("Preferring to use access check as provided by PMPro");
			// $levels = pmpro_getMembershipLevelsForUser( $userId );
			$result = pmpro_has_membership_access( $postId, $userId, true ); //Using true to return all level IDs that have access to the sequence
			Utilities::get_instance()->log("Checking if post {$postId} is accessible by {$userId}: " . print_r($result, true));
			
			$retVal = $result[0];
			
			/*
			if ( true == $result[0] ) {
				
				Utilities::get_instance()->log("Does user {$userId} have access to this post {$postId}? " . ( $result[0] == 1 ? 'yes' : 'no' ));
				
				$filterVal = apply_filters('pmpro_has_membership_access_filter', $result[0], get_post($postId), get_user_by( 'id', $userId ), $levels );
				$retVal = ( $filterVal == 1 ? true : false );
				
				Utilities::get_instance()->log( "After filter of access value for {$userId}: " . ( $retVal == true ? 'yes' : 'no' ));
			}
			*/
		}
		else {
			Utilities::get_instance()->log("No membership access function found for Paid Memberships Pro");
		}
		
		$current_delay = $Tracker->getDelay( 'now', $userId );
		$found = false;
		
		if ( empty( $postId) ) {
			Utilities::get_instance()->log("No post ID given to check, will use the currentArticle post ID: {$currentArticle->post_id}");
			$postId = $currentArticle->post_id;
		}
		
		$programId = $Program->getProgramIdForUser( $userId );
		
		Utilities::get_instance()->log("Looking up by post ID: {$postId}");
		$articles = $Article->findArticles( 'post_id', $postId, $programId );
		// Utilities::get_instance()->log( $articles);
		
		// if (!empty( $articles ) && ( 1 == count($articles ) ) ) {
		if ( !empty( $articles ) ) {
			
			$found = $Tracker->get_closest_release_day( $current_delay, $articles );
			
			// Utilities::get_instance()->log( $found );
			
			if ( !is_null($found) && ( $found->release_day <= $current_delay ) ) {
				Utilities::get_instance()->log("Retval = " . ($retVal ? 'True' : 'False') . " & User {$userId} in program {$programId} has access to {$postId} because {$found->release_day} <= {$current_delay}");
				$retVal = $retVal && true;
			}
			else {
				
				$retVal = false;
			}
			
		}
		
		$currentArticle = $saved;
		
		Utilities::get_instance()->log("Tracker::hasAccess() - Returning " . ( $retVal ? 'true' : 'false' ) . " to calling function: " . $Tracker->whoCalledMe() );
		return $retVal;
	}
	
	/**
	 * Default permission check function.
	 * Checks whether the provided user_id is allowed to publish_pages & publish_posts.
	 *
	 * @param $user_id - ID of user to check permissions for.
	 *
	 * @return bool -- True if the user is allowed to edi/update
	 *
	 */
	public function userCanEdit( $user_id ) {
		
		$privArr = apply_filters( 'e20r-tracker-edit-rights', array( 'publish_pages', 'publish_posts' ) );
		
		$permitted = false;
		
		foreach ( $privArr as $privilege ) {
			
			if ( user_can( $user_id, $privilege ) ) {
				
				$perm = true;
			} else {
				
				$perm = false;
			}
			
			$permitted = ( $permitted || $perm );
		}
		
		
		if ( $permitted ) {
			Utilities::get_instance()->log( "Tracker::userCanEdit() - User id ({$user_id}) has permission" );
		} else {
			Utilities::get_instance()->log( "Tracker::userCanEdit() - User id ({$user_id}) does NOT have permission" );
		}
		
		return $permitted;
	}
	
	/**
	 * Does the user ID belongs to somebody with the "Coach" role?
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function is_a_coach( $user_id ) {
		
		$user_roles     = apply_filters( 'e20r-tracker-configured-roles', array() );
		$coach_override = isset( $_REQUEST['e20r_confirmed_coach_override'] ) ? (bool) intval( $_REQUEST['e20r_confirmed_coach_override'] ) : false;
		
		$wp_user = get_user_by( 'id', $user_id );
		
		// Pretend it's false
		$is_a_coach = $wp_user->has_cap( $user_roles['coach']['role'] );
		
		if ( true === $is_a_coach && true === $coach_override ) {
			Utilities::get_instance()->log( "Overriding the 'coach' role" );
			$is_a_coach = false;
		}
		
		return $is_a_coach;
	}
	
	/**
	 * Set/Manage the login timeout for WordPress on this system
	 */
	public function auth_timeout_reset() {
		
		global $current_user;
		$cookie_arr = null;
		
		$controller = Tracker::getInstance();
		
		Utilities::get_instance()->log("Testing whether user's auth cookie needs to be reset");
		
		if ( is_user_logged_in() ) {
			
			foreach( $_COOKIE as $cKey => $cookie ) {
				
				if ( false !== stripos( $cKey, "wordpress_logged_in_" ) ) {
					
					$cookie_arr = preg_split( "/\|/", $cookie);
					
					if ( !empty( $cookie_arr) && ( $current_user->user_login == $cookie_arr[0] ) ) {
						
						$max_days = (int)$controller->loadOption('remember_me_auth_timeout');
						
						Utilities::get_instance()->log("Tracker::auth_timeout_reset() - Found login cookie for user ID {$current_user->ID}");
						
						$timeout = $cookie_arr[1];
						
						if ( $timeout > ( current_time('timestamp', true ) + $max_days*3600*24 ) ) {
							
							Utilities::get_instance()->log("Tracker::auth_timeout_reset() - Will need to reset the auth cookie. Timeout is {$timeout}");
							
							$days_since = Time_Calculations::daysBetween( current_time('timestamp', true ), $timeout );
							
							Utilities::get_instance()->log("Tracker::auth_timeout_reset() - Days until: {$days_since} vs max ({$max_days}) ");
							
							if ( $days_since > $max_days ) {
								
								Utilities::get_instance()->log("Tracker::auth_timeout_reset() - It will be {$days_since} days until the user ({$current_user->ID}) has to log in... Resetting the login cookie.");
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
	 * Defines the member should be logged out (automatically)
	 *
	 * @param $seconds
	 * @param $user_id
	 * @param $remember
	 *
	 * @return float|int
	 */
	public function login_timeout( $seconds, $user_id, $remember ) {
		
		$expire_in = 0;
		$Tracker = Tracker::getInstance();
		
		Utilities::get_instance()->log("Tracker::login_timeout() - Length requested by login process: {$seconds}, User: {$user_id}, Remember: {$remember}");
		
		/* "remember me" is checked */
		if ( $remember ) {
			
			Utilities::get_instance()->log( "Tracker::login_timeout() - Remember me timeout value: " . $Tracker->loadOption('remember_me_auth_timeout') );
			$expire_in = 60*60*24 * intval( $Tracker->loadOption( 'remember_me_auth_timeout' ) );
			
			if ( $expire_in <= 0 ) { $expire_in = 60*60*24*1; } // 1 Day is the default
			
			Utilities::get_instance()->log("Tracker::login_timeout() - Setting session timeout for user with 'Remember me' checked to: {$expire_in}");
			
		} else {
			
			Utilities::get_instance()->log( "Tracker::login_timeout() - Timeout value in hours: " . $Tracker->loadOption('auth_timeout') );
			$expire_in = 60 * 60 * intval( $Tracker->loadOption( 'auth_timeout' ) );
			
			if ( $expire_in <= 0 ) {
				
				$expire_in = 60*60*3;
			} // 3 Hours is the default.
			
		}
		
		// check for Year 2038 problem - http://en.wikipedia.org/wiki/Year_2038_problem
		if ( PHP_INT_MAX - time() < $expire_in ) {
			
			$expire_in =  PHP_INT_MAX - time() - 5;
		}
		
		Utilities::get_instance()->log("Tracker::login_timeout() - Setting session timeout for user {$user_id} to: {$expire_in}");
		
		return $expire_in;
	}
	
	/**
	 * Check whether the user belongs to a group (membership level) or the user is directly specified in the list of
	 * users for the activity.
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
		
		Utilities::get_instance()->log("User: {$uId}, Group: {$grpId} and Activity: {$activity->id}");
		
		$statuses = apply_filters( 'e20r_tracker_article_post_status', array( 'publish', 'future', 'private' ));
		
		if ( !in_array( get_post_status( $activity->id ), $statuses ) ) {
			
			Utilities::get_instance()->log("Access denied since activity post isn't in an allowed status");
			return $ret;
		}
		
		// Check against list of users for the specified activity.
		Utilities::get_instance()->log("Check access for user ID {$uId}");
		$ret['user'] = $this->inUserList( $uId, $activity->assigned_user_id );
		
		// Check against group list(s) first.
		// Loop through any list of groups the user belongs to
		
		Utilities::get_instance()->log("Check access for group ID {$grpId} vs " . print_r($activity->assigned_usergroups, true));
		$ret['group'] = $this->inGroup( $grpId, $activity->assigned_usergroups );
		
		// Return true if either user or group access is true.
		return $ret;
	}
	
	/**
	 * Can we locate the User ID in the specified User list
	 * @param int $id
	 * @param int[] $userList
	 *
	 * @return bool
	 */
	private function inUserList( $id, $userList ) {
		
		if ( in_array( 0, $userList ) ) {
			
			Utilities::get_instance()->log("Admin has set 'Not Applicable' for user list. Returning false");
			return false;
		}
		
		if ( in_array( -1, $userList ) ) {
			
			Utilities::get_instance()->log("Admin has set 'All Users'. Returning true");
			return true;
		}
		
		if ( in_array( $id, $userList ) ) {
			
			Utilities::get_instance()->log("User ID {$id} is in the list of users. Returning true");
			return true;
		}
		
		Utilities::get_instance()->log("None of the tests returned true. Default is 'No access!'");
		return false;
		
	}
	
	/**
	 * Can we locate the User ID in the specified Group list
	 *
	 * @param int $id
	 * @param int[] $grpList
	 *
	 * @return bool
	 */
	private function inGroup( $id, $grpList ) {
		
		if ( in_array( -1, $grpList ) ) {
			
			Utilities::get_instance()->log("Admin has set 'All Groups'. Returning true");
			return true;
		}
		
		if ( in_array( $id, $grpList ) ) {
			
			Utilities::get_instance()->log("Group ID {$id} is in the group list. Returning true");
			return true;
		}
		
		
		Utilities::get_instance()->log("None of the tests returned true. Default is 'No access!'");
		return false;
	}
	
	public function admin_access_filter($access, $post, $user ) {
		
		if ( ( current_user_can('manage_options') ) ) {
			// Utilities::get_instance()->log("Tracker::admin_access_filter() - Administrator is attempting to access protected content.");
			return true;    //level 2 (and administrator) ALWAYS has access
		}
		
		return $access;
	}
}