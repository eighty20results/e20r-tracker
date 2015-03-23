<?php

class e20rTrackerModel {

    public function loadUsers( $levels ) {

        global $wpdb, $e20rTracker;

        $sql = "
                SELECT m.user_id AS id, u.display_name AS name, um.meta_value AS last_name
                FROM $wpdb->users AS u
                  INNER JOIN {$wpdb->pmpro_memberships_users} AS m
                    ON ( u.ID = m.user_id )
                    INNER JOIN {$wpdb->usermeta} AS um
                    ON ( u.ID = um.user_id )
                WHERE ( um.meta_key = 'last_name' ) AND ( m.status = 'active' AND m.membership_id IN ( [IN] ) )
                ORDER BY last_name ASC
        ";

        $sql = $e20rTracker->prepare_in( $sql, $levels );

        dbg("e20rTrackerModel::loadUsers() - SQL: " . print_r( $sql, true));

        $user_list = $wpdb->get_results( $sql, OBJECT );

        if (! empty( $user_list ) ) {
            return $user_list;
        }
        else {
            $data = new stdClass();
            $data->id = 0;
            $data->name = 'No users found';

            return array( $data );
        }

    }

	public function save_client_interview( $data ) {

		global $wpdb;
		global $e20rTables;
		global $e20rTracker;

		$cTable = $e20rTables->getTable('client_info');

		if ( ( $id = $this->recordExists( $data['user_id'], $data['program_id'] ) !== FALSE ) ) {

			dbg('e20rTrackerModel::save_client_interview() - User/Program exists in the client info table. Editing existing record.' );
			$data['edited_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
			$data['id'] = $id;
		}
		else {
			dbg('e20rTrackerModel::save_client_interview() - User/Program does NOT exist in the client info table. Adding record.' );
			$data['started_date'] = date('Y-m-d H:i:s', current_time('timestamp') );
		}

		$data['completed_date'] = date('Y-m-d H:i:s', current_time('timestamp') );

		dbg($data);

		// Generate format array.
		$format = $e20rTracker->setFormatForRecord( $data );

		dbg("e20rTrackerModel::save_client_interview() - Format for the record: ");
		dbg($format);

		if ( $wpdb->replace( $cTable, $data, $format ) ) {

			dbg("e20rTrackerModel::save_client_interview() - Data saved...");
			return true;
		}

		dbg("e20rTrackerModel::save_client_interview() - Error saving data: " . $wpdb->last_error );
		return false;
	}

	private function recordExists( $userId, $programId ) {

		global $wpdb;
		global $e20rTables;

		$cTable = $e20rTables->getTable('client_info');

		$sql = $wpdb->prepare("
			SELECT id
			FROM $cTable
			WHERE user_id = %d AND program_id = %d
		",
			$userId,
			$programId
		);

		$exists = $wpdb->get_var( $sql );

		if ( ! is_null( $exists ) ) {

			dbg("e20rTrackerModel::recordExists() - Found record with id: {$exists}");
			return (int)$exists;
		}

		return false;
	}
}