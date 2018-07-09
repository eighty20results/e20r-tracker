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

class PMPro {
	
	private $sql_statement = null;
	
	private static $instance = null;
	
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
		
		Utilities::get_instance()->log("Found level for {$user_id}: " . print_r( $result, true ) );
		
		if ( empty( $result ) ) {
			$result = new \stdClass();
			$result->status = 'inactive';
		}
		
		return $result;
	}
}