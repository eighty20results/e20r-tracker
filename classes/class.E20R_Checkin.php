<?php

if ( ! array_key_exists( 'E20R_Checkin', $GLOBALS ) ) {

    class E20R_Checkin {

        public $_tables;

        function __construct() {

            global $wpdb;

            $this->_tables = array(
                'items' => $wpdb->prefix . 'e20r_checkinItems',
                'rules' => $wpdb->prefix . 'e20r_checkinRules',
                'checkin' => $wpdb->prefix . 'e20r_checkin',
            );

        } // end constructor

        /**
         * @param string $sname - The short name for the check-in
         * @param string $fname - The full name for the check-in
         * @param string $start - The start date/timestamp
         * @param string $end -- the end date/timestamp for the item
         * @param int $prgm -- The Exercise or nutrition program this belongs to
         * @param int $order -- The sort order for the check-in items
         * @param int $max -- The maximum number of days from when the check-in value begins to when it ends.
         * @param null $m_id -- The required membership ID (level)
         *
         * @return mixed -- The ID of the newly inserted record.
         * @throws Exception
         */
        public function addItem(
            $sname = '', $fname = '', $start = null, $end = null,
            $prgm = null, $order = 1,  $max = 15, $m_id = null ) {

            global $wpdb;

            // No start time provided. Using "now"
            if ( $start === null ) {

                $start = current_time( 'timestamp' );
            }
            else {
                dbgOut("Start time defined as {$start}");
                $start = strtotime( $start );
            }

            // No end time provided, using $start + 2 weeks.
            if ( $end == null ) {

                // Timestamp + # of seconds in two weeks
                $end = $start + ( 604800 * 2 );
            } else {

                dbgOut("End time defined as {$end}");
                $end = strtotime( $end );
            }

            $dayDiff = ( $end - $start ) / ( 60 * 60 * 24 );
            dbgOut("Day Difference: {$dayDiff} vs max: {$max}");

            if ( $max > $dayDiff ) {
                dbgOut("Number of days specified as max is greater than specified end-date: {$dayDiff}" );
                $end = $start + ( $max * ( 60 * 60 * 24 ) );

            }

            dbgOut("Received start time: {$start}, " . date("Y-m-d H:i:s", $start));
            dbgOut("Received end time: {$end}, " . date("Y-m-d H:i:s", $end));

            $data = array(
                'program_id' => $prgm,
                'short_name' => $sname,
                'item_name' => $fname,
                'startdate' => date("Y-m-d H:i:s", $start),
                'enddate' => date("Y-m-d H:i:s", $end),
                'item_order' => $order,
                'maxcount' => $max,
                'membership_level_id' => $m_id,
            );

            $format = array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d' );

            if ( $wpdb->insert( $this->_tables['items'],
                    $data, $format) === false ) {

                throw new Exception( 'Database error on INSERT:' .
                                     $wpdb->print_error() );
            }

            return $wpdb->insert_id; // Return the ID for the new DB record.

        } // End addItem

        public function delItem( $itemId ) {

            global $wpdb;

            if ($itemId) {

                if ( $wpdb->delete( $this->_tables['items'],
                        array( 'ID', $itemId ) ) === false ) {

                    throw new Exception( 'Database error on DELETE: ' .
                                         $wpdb->print_error() );
                }
            }
            else {
                throw new Exception( 'deleteItem() - No ID given' );
            }
        }

        public function editItem( $id, $data ) {

            global $wpdb;

            if ( $id ) {

                if ( $wpdb->update( $this->_tables['items'],
                        $data,
                        array( 'id' => $id)) === false) {

                    throw new Exception('Datbase error on UPDATE: ' .
                                        $wpdb->print_error());
                }
            }
            else {

                throw new Exception( 'updateItem() - No ID given' );
            }
        }

    } // end class

    // Store reference to the plugin in $GLOBALS so our unit tests can access it
    $GLOBALS['E20R_Checkin'] = new E20R_Checkin();

} // end if
