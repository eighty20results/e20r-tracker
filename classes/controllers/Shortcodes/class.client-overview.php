<?php
/**
 * Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
 *  ALL RIGHTS RESERVED
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  You can contact us at mailto:info@eighty20results.com
 */

namespace E20R\Tracker\Controllers\Shortcodes;


use E20R\Tracker\Controllers\Client;
use E20R\Tracker\Controllers\Program;
use E20R\Tracker\Controllers\Tracker_Access;

use E20R\Utilities\Utilities;

class Client_Overview {
	
	/**
	 * Instance of the Client_Overview class
	 *
	 * @var null|Client_Overview
	 */
	private static $instance = null;
	
	/**
	 * Client_Overview constructor.
	 */
	private function __construct() {
	}
	
	/**
	 * @return Client_Overview|null
	 */
	public static function getInstance() {
		
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Generate the front-end "Coaching" page list of clients (and their status) [e20r_client_overview]
	 *
	 * @param null|array $attributes
	 * @param string     $content
	 *
	 * @return string
	 */
	public function loadShortCode( $attributes = null, $content = '' ) {
		
		Utilities::get_instance()->log( "Loading shortcode for the coach list of clients" );
		
		$Program = Program::getInstance();
		$Access  = Tracker_Access::getInstance();
		$Client = Client::getInstance();
		
		$Model = $Client->getModelClass();
		$View = $Client->getViewClass();
		
		global $currentProgram;
		global $current_user;
		
		if ( ! is_user_logged_in() ) {
			
			auth_redirect();
			wp_die();
		}
		
		if ( ( ! $Access->is_a_coach( $current_user->ID ) ) ) {
			
			$Client->setNotCoachMsg();
			wp_die();
		}
		
		$client_list = $Model->get_clients( $current_user->ID );
		$list        = array();
		
		foreach ( $client_list as $pId => $clients ) {
			
			foreach ( $clients as $k => $client ) {
				
				// $Program->getProgramIdForUser( $client->ID );
				// $Program->setProgram( $pId );
				
				$coach = $Model->get_coach( $client->ID );
				
				$client->status                = new \stdClass();
				$client->status->program_id    = $Program->getProgramIdForUser( $client->ID );
				$client->status->program_start = $Program->getProgramStart( $client->status->program_id, $client->ID );
				$client->status->coach         = array( $currentProgram->id => key( $coach ) );
				$client->status->recent_login  = get_user_meta( $client->ID, '_e20r-tracker-last-login', true );
				$mHistory                      = $Model->load_message_history( $client->ID );
				
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
		
		return $View->displayClientList( $list );
		
	}
}