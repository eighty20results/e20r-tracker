<?php
/**
 * The E20R Tracker Plugin – a coaching client management plugin for WordPress. Tracks client training, habits,
 * educational reminders, etc. Copyright (c) 2018, Wicked Strong Chicks, LLC
 *
 * The E20R Tracker Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation, either version 2 of the License or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <http://www.gnu.org/licenses/>
 *
 * You can contact us at info@eighty20results.com
 *
 *
 */

namespace E20R\Tracker\Controllers;


use E20R\Utilities\Utilities;
use E20R\Tracker\Controllers\Program;

class PMPro {
	
	private static $instance = null;
	private $sql_statement = null;
	
	private function __construct() {
	}
	
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Fetch the most recent (current) membership level data for the user
	 *
	 * @param int $user_id
	 *
	 * @return \stdClass
	 */
	public function getLastLevelForUser( $user_id ) {
		
		global $wpdb;
		$this->sql_statement = $wpdb->prepare(
			"SELECT *
				FROM {$wpdb->pmpro_memberships_users}
				WHERE user_id = %d
				ORDER BY id DESC
				LIMIT 1",
			$user_id
		);
		
		$result = $wpdb->get_row( $this->sql_statement );
		
		Utilities::get_instance()->log( "Found level for {$user_id}: " . print_r( $result, true ) );
		
		if ( empty( $result ) ) {
			$result         = new \stdClass();
			$result->status = 'inactive';
		}
		
		return $result;
	}
	
	/**
	 * Configure the member's program on checkout
	 *
	 * @param bool         $confirmed
	 * @param \MemberOrder $order
	 *
	 * @return bool
	 */
	public function setMemberProgram( $confirmed, $order ) {
		
		if ( class_exists( '\E20R\Tracker\Controllers\Program' ) && true === $confirmed ) {
			
			$Program = Program::getInstance();
			$utils   = Utilities::get_instance();
			
			$utils->log( "Set the program and start date for user with ID {$order->user_id}" );
			
			if ( empty( $order->user_id ) ) {
				$utils->log( "No user ID configured!?!" );
				
				return $confirmed;
			}
			
			$user_level = pmpro_getMembershipLevelForUser( $order->user_id );
			
			$Program->setProgramForUser( $user_level->startdate, $order->membership_id, $user_level );
		}
		
		return $confirmed;
	}
	
	/**
	 * Set startdate for client/member when signing up for the VPT program
	 *
	 * @param string $startdate
	 * @param int $userId
	 * @param \stdClass $levelObj
	 *
	 * @return false|string
	 */
	public function setVPTProgramStartDate( $startdate, $userId, $levelObj ) {
		
		$utils = Utilities::get_instance();
		$utils->log( "Received startdate: {$startdate}, userId: {$userId} " );
		
		if ( 2 != $levelObj->id ) {
			$utils->log( "Not processing a VPT membership level" );
			return $startdate;
		}
		
		//which day is it
		$checkout_day = date( "N" );
		
		switch ( $checkout_day ) {
			
			case  1: // It's monday.
				
				$utils->log( "Today is Monday. Setting start date to today" );
				$startdate = date( "Y-m-d H:i:s", strtotime( "today" ) );
				break;
			
			case 6: // Saturday...
				
				$utils->log( "Today is Between Saturday & Sunday. Setting start date to next Monday" );
				$startdate = date( "Y-m-d H:i:s", strtotime( "next monday" ) );
				break;
			
			case 7: // Sunday
				$utils->log( "Today is Sunday. Setting start date to tomorrow" );
				$startdate = date( "Y-m-d H:i:s", strtotime( "tomorrow" ) );
				break;
			
			default: // Any other day (Tuesday through Friday.
				$utils->log( "Today is Between Tuesday & Thursday. Setting start date to last Monday" );
				$startdate = date( "Y-m-d H:i:s", strtotime( "last monday" ) );
		}
		
		// Use monday next week if we're on a day after Friday
		$utils->log( "Startdate for {$userId} is being set to: {$startdate}" );
		
		return $startdate;
	}
}