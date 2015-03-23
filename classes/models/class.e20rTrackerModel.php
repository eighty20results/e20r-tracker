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

}