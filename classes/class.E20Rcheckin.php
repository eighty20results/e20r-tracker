<?php

if ( ! class_exists( 'E20Rcheckin' ) ):

    class E20Rcheckin {

        public $_tables;

        function __construct() {

            global $wpdb;

            dbg("Loading E20Rcheckin class");
            $this->_tables = array(
                'items' => $wpdb->prefix . 'e20r_checkin_items',
                'rules' => $wpdb->prefix . 'e20r_checkin_rules',
                'checkin' => $wpdb->prefix . 'e20r_checkin',
            );

        } // end constructor

        public function viewManageCheckinItems() {

            dbg("Loading manage_checkin_items page");

            ob_start();
            ?>
            <H1>Manage Check-In/Activity Items</H1>
            <?php echo $this->checkinItemSelect(); ?>
            <hr />
            <div id="edit-checkin-items">
            </div>
            <?php
            $html = ob_get_clean();

            return $html;
        }

        public function checkinItemSelect() {

            ob_start(); ?>

            <div class="e20r-select-checkin">
                <form action="<?php admin_url('admin-ajax.php'); ?>" method="post">
                    <?php wp_nonce_field( 'e20r-tracker-data', 'e20r_tracker_checkin_items_nonce' ); ?>
                    <div class="e20r-select">
                        <input type="hidden" name="hidden_e20r_checkin_item_id" id="hidden_e20r_checkin_item_id" value="0" >
                        <label for="e20r_checkin_items">Add / Update Items</label>
                    <span class="e20r-checkin-select-span">
                        <select name="e20r_checkin_items" id="e20r_checkin_items">
                            <?php

                            $checkin_list = $this->load_checkin_itemList( false );

                            dbg("List: " . print_r( $checkin_list, true ) );
                            foreach( $checkin_list as $item ) {
                                ?><option value="<?php echo esc_attr( $item->id ); ?>"  ><?php echo esc_attr( $item->item_name ); ?></option><?php
                            }
                            ?>
                        </select>
                    </span>
                        <span class="e20r-checkin_item-select-span"><a href="#e20r_tracker_checkin_items" id="e20r-load-checkin-items" class="e20r-choice-button button"><?php _e('Load', 'e20r-tracker'); ?></a></span>
                        <span class="seq_spinner" id="spin-for-checkin-item"></span>
                    </div>
                </form>
            </div>
            <?php

            $html = ob_get_clean();

            return $html;

        }

        private function load_checkin_itemList( $cached = true ) {

            global $wpdb;

            $sql = "
                SELECT id, item_name
                FROM {$this->_tables['items']}
                ORDER BY item_name ASC
            ";

            $item_list = $wpdb->get_results( $sql, OBJECT );

            dbg(" SQL: " . $sql);

            $data = new stdClass();
            $data->id = 0;
            $data->item_name = 'New Check-in Item';

            return ( array( $data ) + $item_list );
        }

        public function ajax_getCheckin_item() {

            check_ajax_referer( 'e20r-tracker-data', 'e20r_tracker_checkin_items_nonce' );
            dbg("Running checkin_item locator");

            $itemId = isset( $_POST['hidden_e20r_checkin_item_id'] ) ? intval( $_POST['hidden_e20r_checkin_item_id'] ) : 0;

            $item = $this->getItem( $itemId );

            dbg("Item Object: " . print_r( $item, true  ) );

            return $item;
        }

        // Ben Code: BenK-F0ED76527F

        public function getItem( $itemId ) {

            global $wpdb;

            if ( $itemId == 0) {
                // Want a new item.
                $item = new stdClass();

                $item->id = null;
                $item->short_name = null;
                $item->program_id = null;
                $item->item_name = null;
                $item->startdate = date( 'Y-m-d' ) . " 00:00:00";
                $item->enddate = date( 'Y-m-d', (current_time('timestamp') + 1209600 ) ) . " 00:00:00";
                $item->item_order = $this->get_nextItemOrderNum( $this->short_name );
                $item->maxcount = null;
                $item->membership_level_id;

                $results = array( $item );
            }
            else {

                $sql = $wpdb->prepare("
                        SELECT *
                        FROM %s
                        WHERE id = %d
                    ",
                    $this->_tables['items'],
                    $itemId
                );

                $results = $wpdb->get_results( $sql );
            }

            return $results;
        }

        /**
         * @param $short_name -- Name of habit/item (short & unique)
         * @return mixed -- The next number in the sequence.
         */
        private function get_nextItemOrderNum( $short_name ) {

            global $wpdb;

            $sql = "
                SELECT MAX(item_order) as max_order
                FROM {$this->_tables['items']}
                WHERE short_name = '{$short_name}'
            ";

            $max_order = $wpdb->get_var( $sql );

            // Increment value
            $max_order++;

            return $max_order;
        }
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
                dbg("Start time defined as {$start}");
                $start = strtotime( $start );
            }

            // No end time provided, using $start + 2 weeks.
            if ( $end == null ) {

                // Timestamp + # of seconds in two weeks
                $end = $start + ( 604800 * 2 );
            } else {

                dbg("End time defined as {$end}");
                $end = strtotime( $end );
            }

            $dayDiff = ( $end - $start ) / ( 60 * 60 * 24 );
            dbg("Day Difference: {$dayDiff} vs max: {$max}");

            if ( $max > $dayDiff ) {
                dbg("Number of days specified as max is greater than specified end-date: {$dayDiff}" );
                $end = $start + ( $max * ( 60 * 60 * 24 ) );

            }

            dbg("Received start time: {$start}, " . date("Y-m-d H:i:s", $start));
            dbg("Received end time: {$end}, " . date("Y-m-d H:i:s", $end));

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

endif; // end if
