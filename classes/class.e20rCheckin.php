<?php

if ( ! class_exists( 'e20rCheckin' ) ):

    class e20rCheckin {

        public $_tables = array();
        private $id;
        private $_fields = array();

        function e20rCheckin( $user_id = null ) {

            if (! $user_id ) {
                global $current_user;

                if ( isset( $current_user->ID) ) {
                    $this->id = $current_user->ID;
                }
            }
            else {
                $this->id = $user_id;
            }

            global $wpdb;

            dbg("Loading E20Rcheckin class");

            if ( ! function_exists( 'in_betagroup' ) ) {
                dbg("in_betagroup function is missing???");
            }

            if ( ! in_betagroup( $this->id ) ) {

                $this->_tables = array(
                    'items' => $wpdb->prefix . 'e20r_checkin_items',
                    'rules' => $wpdb->prefix . 'e20r_checkin_rules',
                    'checkin' => $wpdb->prefix . 'e20r_checkin',
                    'articles' => $wpdb->prefix . 'e20r_articles',
                );

            }
            else {

                $this->_tables = array(
                    'items' => $wpdb->prefix . 's3f_nourishHabits',
                    'rules' => $wpdb->prefix . 'e20r_checkin_rules',
                    'checkin' => $wpdb->prefix . 's3f_nourishHabits',
                    'articles' => $wpdb->prefix . 'e20r_articles',
                );


            }



        } // end constructor

        /**
         * Checks whether the current_user is a member of the Nourish Beta group
         *
         * @return bool -- If the current user is part of the Nourish beta group (means they're using different tables, etc)
         */
        public function isBeta() {

            global $current_user;

            return in_betagroup( $current_user->ID );
        }

        public function get_checkinList( $shortname = null, $level_id = 0) {

            global $wpdb;

            $item_list = array();

            // TODO: SQL to return the checkin count for the shortname and levelID provided.
            $checkin_table = ( $this->isBeta() ? $wpdb->prefix . "s3f_nourishHabits" : $this->_tables['checkin'] );

            if ( $this->isBeta() ) {
                $SQL = "SELECT COUNT(*) AS total
                    FROM {$checkin_table}
                    WHERE
                      habit_name = '{$shortname}' AND
                      check_in_value = 'Yes'";
            }
            else {
                $SQL = "SELECT COUNT(*) AS total
                        FROM {$checkin_table}
                        WHERE
                          des
                          ";

            }


            return $item_list;
        }

        public function view_AddNewCheckinItem( $itemId = null ) {

            dbg("Loading add new checkin item page");

            // Fetch the Checkin Item we're looking to manage
            if ( $itemId ) {
                $item = $this->getItem( $itemId );
            }

            $programs = new e20rPrograms();

            if ( ! empty($item) ) {

                $start = new DateTime( $item->startdate );
                $end = new DateTime( $item->enddate );

                $diff = $end->diff( $start );

                $max = $diff->format( '%a' );
            }
            else {
                $max = 14; // Default max if a habit is 14 days.
            }

            ob_start();
            ?>
            <H1>Manage Check-In/Activity Items</H1>
            <?php echo $this->checkinItemSelect(); ?>
            <hr />
            <div id="edit-checkin-items">
                <form action="" method="post">
                    <?php wp_nonce_field('e20r-tracker-data', 'e20r_tracker_edit_nonce'); ?>
                    <div class="e20r-checkin-editform">
                        <input type="hidden" name="hidden_e20r_checkin_item_id" id="e20r_checkin_item_id" value="<?php echo ( ( ! empty($item) ) ? $item->id : 0 ); ?>">
                        <table id="e20r-manage-checkin-items">
                            <tbody>
                                <tr>
                                    <td class="e20r-loabel">Check-in belongs to:</td>
                                    <td class="select-input"><?php echo $programs->programSelector( false ); ?></td>
                                    <td><a class="e20r-choice-button button" href="<?php echo admin_url('admin.php?page=e20r-add-new-program'); ?>" target="_blank">Add new</a></td>
                                </tr>
                                <tr>
                                    <td class="e20r-label"><label for="e20r-checkin-short_name">Short name:</label></td>
                                    <td class="text-input"><input type="text" name="e20r-checkin-short_name" id="e20r_checkin_item_short_name" size="25" value="<?php echo ( ( ! empty($item->short_name) ) ? $item->short_name : null ); ?>"></td>
                                </tr>
                                <tr>
                                    <td class="e20r-label"><label for="e20r-checkin-item_name">Descriptive name:</label></td>
                                    <td class="text-input"><input type="text" name="e20r-checkin-item_name" id="e20r_checkin_item_name" size="50" value="<?php echo ( ( ! empty($item->item_name) ) ? $item->item_name : null ); ?>" ></td>
                                </tr>
                                <tr>
                                    <td class="e20r-label"><label for="e20r-checkin-startdate">Starts on:</label></td>
                                    <td class="text-input"><input type="date" name="e20r-checkin-startdate" id="e20r_checkin_item_startdate" value="<?php echo ( ( ! empty($item->startdate) ) ? $item->startdate : null ); ?>" ></td>
                                </tr>
                                <tr>
                                    <td class="e20r-label"><label for="e20r-checkin-enddate">Ends on:</label></td>
                                    <td class="text-input"><input type="date" name="e20r-checkin-enddate" id="e20r_checkin_item_enddate" value="<?php echo ( ( ! empty($item->enddate) ) ? $item->enddate : null ); ?>" ></td>
                                </tr>
                                <tr>
                                    <td class="e20r-label"><label for="e20r-checkin-item_order">Sort order:</label></td>
                                    <td class="text-input"><input type="text" name="e20r-checkin-item_order" id="e20r_checkin_item_order" size="4" value="<?php echo ( ( ! empty($item->item_order) ) ? $item->item_order : null ); ?>" ></td>
                                </tr>
                                <tr>
                                    <td class="e20r-label"><label for="e20r-checkin-maxcount">Max check-in count:</label></td>
                                    <td class="e20r-label"><input type="text" name="e20r-checkin-maxcount" id="e20r_checkin_item_maxcount" size="4" value="<?php echo ( ( ! empty($item->maxcount) ) ? $item->maxcount : 14 ); ?>" ></td>
                                </tr>
                                <tr>
                                    <!-- select for choosing the membership type to tie this check-in to -->
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <?php
            $html = ob_get_clean();

            return $html;
        }

        public function view_manageCheckinItems() {

            // Fetch the Checkin Item we're looking to manage
            $item_list = $this->load_checkin_itemList( null, false );

            $programs = new e20rPrograms();

            ob_start();
            ?>
            <H1>List of Check-In/Activity Items</H1>
            <hr />
            <form action="" method="post">
                <?php wp_nonce_field('e20r-tracker-data', 'e20r_tracker_edit_nonce'); ?>
                <div class="e20r-checkin-editform">
                    <table id="e20r-manage-checkin-items">
                        <thead>
                            <tr>
                                <th class="e20r-label header"><label for="checkin-item-edit">Edit</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-item-id">Id</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-item-order">Order #</label></th>
                                <th class="e20r-label header"><label for="e20r-program">Program</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-short-name">Short name</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-item-name">Summary</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-startdate">Starts on</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-enddate">Ends on</label></th>
                                <th class="e20r-label header"><label for="e20r-checkin-maxcount">Max count</label></th>
                                <th class="e20r-save-col hidden">Save</th>
                                <th class="e20r-cancel-col hidden">Cancel</th>
                                <th class="e20r-delete-col hidden">Remove</th>
                                <th class="e20r-label header hidden"></th>
                            </tr>
                            <tr>
                                <td colspan="13"><hr/></td>
                                <!-- select for choosing the membership type to tie this check-in to -->
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                        if ( count($item_list) > 0) {

                            // dbg("Fetched Check-in items: " . print_r( $item_list, true ) );

                            foreach ( $item_list as $item ) {

                                if ( is_null( $item->startdate ) ) {
                                    $start = '';
                                } else {
                                    $start = new DateTime( $item->startdate );
                                    $start = $start->format( 'Y-m-d' );
                                }

                                if ( is_null( $item->enddate ) ) {
                                    $end = '';
                                } else {
                                    $end = new DateTime( $item->enddate );
                                    $end = $end->format( 'Y-m-d' );
                                }

                                if ( is_object( $end ) && is_object( $start ) ) {

                                    $diff = $end->diff( $start );

                                    $max = $diff->format( '%a' );
                                }
                                else {
                                    $max = 14; // Default max if a habit is 14 days.
                                }

                                if ( is_null($item->maxcount ) ) {
                                    $item->maxcount = $max;
                                }

                                $iId = $item->id;

                                ?>
                                <tr id="<?php echo $iId; ?>" class="checkin-inputs">
                                    <td class="text-input">
                                        <input type="checkbox" name="checkin-item-edit" id="edit_<?php echo $iId; ?>">
                                    </td>
                                    <td class="text-input">
                                        <input type="text" id="e20r-checkin-item-id_<?php echo $iId; ?>" disabled name="e20r-checkin-item-id" size="5" value="<?php echo ( ! empty( $item->id ) ? $item->id : null ); ?>">
                                    </td>

                                    <td class="text-input">
                                        <input type="text" name="e20r-checkin-item-order" id="e20r-checkin-item-order_<?php echo $iId; ?>" disabled size="4" value="<?php echo ( ! empty( $item->item_order ) ? $item->item_order : null ); ?>">
                                    </td>
                                    <td class="select-input" id="e20r-checkin-item-select_program-col_<?php echo $iId; ?>">
                                        <?php echo $programs->programSelector( true, ( ( $item->program_id == 0) ? null : $item->program_id ), $iId, true ); ?>
                                    </td>
                                    <td class="text-input">
                                        <input type="text" name="e20r-checkin-short-name" id="e20r-checkin-item-short-name_<?php echo $iId; ?>" disabled size="25" value="<?php echo ( ! empty( $item->short_name ) ? $item->short_name : null ); ?>">
                                    </td>
                                    <td class="text-input">
                                        <input type="text" name="e20r-checkin-item-name" id="e20r-checkin-item-name_<?php echo $iId; ?>" disabled size="50" value="<?php echo ( ! empty( $item->item_name ) ? $item->item_name : null ); ?>">
                                    </td>
                                    <td class="text-input">
                                        <input type="date" name="e20r-checkin-startdate" id="e20r-checkin-item-startdate_<?php echo $iId; ?>" disabled value="<?php echo $start; ?>">
                                    </td>
                                    <td class="text-input">
                                        <input type="date" name="e20r-checkin-enddate" id="e20r-checkin-item-enddate_<?php echo $iId; ?>" disabled value="<?php echo $end; ?>">
                                    </td>
                                    <td class="text-input">
                                        <input type="text" name="e20r-checkin-maxcount" id="e20r-checkin-item-maxcount_<?php echo $iId; ?>" disabled size="5" value="<?php echo ( ! empty( $item->maxcount ) ? $item->maxcount : null ); ?>" >
                                    </td>

                                    <td class="hidden select-input">
                                        <!-- Insert membership type this program belongs to -->
                                    </td>
                                    <td class="hidden save-button-row" id="e20r-td-save_<?php echo $iId; ?>">
                                        <a href="#" class="e20r-save-edit-checkin-item button">Save</a>
                                    </td>
                                    <td class="hidden cancel-button-row" id="e20r-td-cancel_<?php echo $iId; ?>">
                                        <a href="#" class="e20r-cancel-edit-checkin-item button">Cancel</a>
                                    </td>
                                    <td class="hidden delete-button-row" id="e20r-td-delete_<?php echo $iId; ?>">
                                        <a href="#" class="e20r-delete-checkin-item button">Remove</a>
                                    </td>
                                    <td class="hidden hidden-input">
                                        <input type="hidden" class="hidden_id" value="<?php echo $iId; ?>">
                                    </td>
                                </tr>
                            <?php
                            } // foreach
                        } // Endif
                         else {
                         ?>
                             <tr>
                                 <td colspan="13">
                                     No Items found in the database. Please add one or more new Items by clicking the "Add New" button.
                                 </td>
                             </tr>

                         <?php
                         }
                        ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="13"><hr/></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="add-new" style="text-align: left;"><a class="e20r-button button" id="e20r-add-new-item" href="#">Add New</a></td>
                            </tr>
                            <tr id="add-new-checkin-item" class="hidden">
                                <td class="text-input">
                                    <input type="checkbox" disabled name="edit" id="edit">
                                </td>
                                <td class="text-input">
                                    <input type="text" id="e20r-checkin-item-id" name="e20r-checkin-item-id" disabled size="5" value="auto">
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-item-order" id="e20r-checkin-item-order" size="4" value="">
                                </td>
                                <td class="select-input">
                                    <?php echo $programs->programSelector( true, null, null, false ); ?>
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-short_name" id="e20r-checkin-item-short-name" size="25" value="">
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-item_name" id="e20r-checkin-item-name" size="35" value="">
                                </td>
                                <td class="text-input">
                                    <input type="date" name="e20r-checkin-startdate" id="e20r-checkin-item-startdate" value="">
                                </td>
                                <td class="text-input">
                                    <input type="date" name="e20r-checkin-enddate" id="e20r-checkin-item-enddate" value="">
                                </td>
                                <td class="text-input">
                                    <input type="text" name="e20r-checkin-maxcount" id="e20r-checkin-item-maxcount" size="5" value="" >
                                </td>
                                <td class="select-input hidden">
                                    <!-- Insert membership type this program belongs to -->
                                </td>
                                <td class="save">
                                    <a class="e20r-button button" id="e20r-save-new-checkin-item" href="#">Save</a>
                                </td>
                                <td class="cancel">
                                    <a class="e20r-button button" id="e20r-cancel-new-checkin-item" href="#">Cancel</a>
                                </td>
                                <td class="hidden">
                                    <!-- Nothing here, it's for the delete/remove button -->
                                </td>
                                <td class="hidden-input">
                                    <input type="hidden" class="hidden_id" value="<?php echo $iId; ?>">
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
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

        private function get_lastThreeItemsByDate( $when ) {

            global $wpdb;

            $when = date_i18n( 'Y-m-d H:i:s', strtotime( $when ) );

            $sql = $wpdb->prepare("
                      SELECT short_name, item_name
                      FROM {$this->_tables['items']}
                      WHERE startdate <= %s
                      ORDER BY startdate, item_order DESC
                      LIMIT 1,3
                  ",
                    $when
            );

            $results = $wpdb->get_results( $sql, OBJECT );

            return $results;
        }

        public function view_userCheckinForm( $articleId, $headline, $when ) {

            // Form consists of last 3 checkin_items (by startdate )
            $items = $this->get_lastThreeItemsByDate( $when );

            $checkedIn = $this->get_userCheckin( $articleId );


        }

        private function get_userCheckin( $articleId = null ) {

            global $wpdb, $current_user;

            $sql = $wpdb->prepare("
                        SELECT *
                        FROM {$this->_tables['checkin']} AS c
                        INNER JOIN {$this->_tables['articles']} AS a
                          ON ( c.id = a.checkin_item_id )
                        WHERE c.user_id = %d
                    ",
                    $current_user->ID
                );

        }

        private function load_checkin_itemList( $cached = true, $add_new = true ) {

            global $wpdb;

            $sql = "
                SELECT *
                FROM {$this->_tables['items']}
                ORDER BY program_id ASC, item_order ASC, id ASC
            ";

            $item_list = $wpdb->get_results( $sql, OBJECT );

            dbg(" SQL: " . $sql);

            if ( $add_new ) {
                $data = new stdClass();
                $data->id = 0;
                $data->item_name = 'New Check-in Item';

                return ( array( $data ) + $item_list );
            }
            else {
                return $item_list;
            }
        }

        public function ajax_save_item_data() {

            global $current_user;

            check_ajax_referer('e20r-tracker-data', 'e20r_tracker_edit_nonce');

            $errorMsg = null;

            dbg("Saving item data");

            if ( current_user_can( 'manage_options' ) ) {

                dbg("User has permission to save new Check-in Item");

                $tmp = ( isset( $_POST['e20r_checkin_item_id'] ) ? $_POST['e20r_checkin_item_id'] : null );
                $item_id = is_numeric( $tmp ) ? intval( $tmp ) : sanitize_text_field( [ $_POST['e20r_checkin_item_id']] );

                dbg('Item Id: ' .print_r($item_id, true) );

                $delete_only = ( ( isset( $_POST['e20r_checkin_item_delete'] ) &&
                                   ( esc_attr( $_POST['e20r_checkin_item_delete'] ) == 'true' ) ) ? true : false );

                if ( ! $delete_only ) {

                    // Check if we're adding a new record
                    $item = array(
                        'id' => $item_id,
                        'program_id' => ( isset( $_POST['e20r_checkin_item_program_id'] ) ? intval( $_POST['e20r_checkin_item_program_id'] ) : null ),
                        'short_name' => ( isset( $_POST['e20r_checkin_item_short_name'] ) ? esc_attr( $_POST['e20r_checkin_item_short_name'] ) : null ),
                        'item_name' => ( isset( $_POST['e20r_checkin_item_name'] ) ? esc_attr( $_POST['e20r_checkin_item_name'] ) : null ),
                        'startdate' => ( isset( $_POST['e20r_checkin_item_startdate'] ) ? esc_attr( $_POST['e20r_checkin_item_startdate'] ) : null ) . " 00:00:00",
                        'enddate' => ( isset( $_POST['e20r_checkin_item_enddate'] ) ? esc_attr( $_POST['e20r_checkin_item_enddate'] ) : null ) . " 00:00:00",
                        'item_order' => ( isset( $_POST['e20r_checkin_item_order'] ) ? intval( $_POST['e20r_checkin_item_order'] ) : null ),
                        'maxcount' => ( isset( $_POST['e20r_checkin_item_maxcount'] ) ? intval( $_POST['e20r_checkin_item_maxcount'] ) : null ),
                    );

                    try {
                        dbg("Updating Item record");
                        dbg("Data being updated: " . print_r( $item, true) );
                        $this->updateItem( $item );
                    }
                    catch ( Exception $e ) {

                        $errorMsg = $e->getMessage();
                    }
                }
                else {
                    dbg("Deleting record # {$item_id}");

                    try {

                        $this->delItem( $item_id );
                    }
                    catch ( Exception $e ){

                        $errorMsg = $e->getMessage();
                    }
                }

                $html = $this->view_manageCheckinItems();
            }
            else {

                $errorMsg = "You do not have permission to manage Check-in Items.";
            }

            if ( $errorMsg ) {

                wp_send_json_error( $errorMsg );
            }
            else {
                wp_send_json_success( $html );
            }

        }

        public function ajax_getCheckin_item() {

            check_ajax_referer( 'e20r-tracker-data', 'e20r_tracker_checkin_items_nonce' );
            dbg("Running checkin_item locator");

            $itemId = isset( $_POST['hidden_e20r_checkin_item_id'] ) ? intval( $_POST['hidden_e20r_checkin_item_id'] ) : 0;

            $item = $this->getItem( $itemId );

            dbg("Item Object: " . print_r( $item, true  ) );
            // Build HTML for the item...

            $program = new e20rPrograms( $item->program_id );

            ob_start();
            ?>

            <!-- Data record for the Item object -->

            <!-- Load the selected program drop-down -->
            <?php echo $program->viewProgramSelectDropDown(); ?>

            <?php

            $html = ob_get_clean();

            wp_send_json_success( $html );
        }

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
                        FROM {$this->_tables['items']}
                        WHERE id = %d
                    ",
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
         * Add a new check-in item to the database.
         *
         * @param array $itemArr - An array of check-in item values to save.
         *
         * @return mixed -- The ID of the newly inserted record.
         * @throws Exception
         */
        public function updateItem( $itemArr ) {

            global $wpdb;

            if ( empty( $itemArr ) ) {

                throw new Exception( 'Database error on INSERT: No data to add/update' );
            }

            // No start time provided. Using "now"
            if ( $itemArr['startdate'] === null ) {

                $itemArr['startdate'] = current_time( 'timestamp' );
            }
            else {
                dbg("Start time defined as {$itemArr['startdate']}");
                $itemArr['startdate'] = strtotime( $itemArr['startdate'] );
            }

            // No end time provided, using $start + 2 weeks.
            if ( $itemArr['enddate'] == null ) {

                // Timestamp + # of seconds in two weeks
                $itemArr['enddate'] = ( $itemArr['startdate'] + ( 604800 * 2 ) );
            } else {

                dbg("End time defined as {$itemArr['enddate']}");
                $itemArr['enddate'] = strtotime( $itemArr['enddate'] );
            }

            $dayDiff = ( ( $itemArr['enddate'] - $itemArr['startdate'] ) + ( 60 * 60 * 24 ) ) / ( 60 * 60 * 24 );

            dbg("Day Difference: {$dayDiff} vs max: {$itemArr['maxcount']}");

            if ( $itemArr['maxcount'] > $dayDiff ) {
                dbg("Number of days specified as max is greater than specified end-date: {$dayDiff}" );
                $itemArr['enddate'] = $itemArr['startdate'] + ( $itemArr['maxcount'] * ( 60 * 60 * 24 ) );

            }

            $itemArr['startdate'] = date("Y-m-d H:i:s", $itemArr['startdate']);
            $itemArr['enddate'] = date("Y-m-d H:i:s", $itemArr['enddate']);

            dbg("Received start time: {$itemArr['startdate']}");
            dbg("Received end time: {$itemArr['enddate']}");

            /**
            $data = array(
                'program_id' => $itemArr['programId'],
                'short_name' => $itemArr['short_name'],
                'item_name' => $itemArr['name'],
                'startdate' => date("Y-m-d H:i:s", $itemArr['startdate']),
                'enddate' => date("Y-m-d H:i:s", $itemArr['enddate']),
                'item_order' => $itemArr['order'],
                'maxcount' => $itemArr['max'],
                'membership_level_id' => $itemArr['membership_id'],
            );
            */

            if ( $itemArr['id'] == 'auto' ) {

                unset($itemArr['id']);
                $format = array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d' );

            }
            else {

                $format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d' );
            }

            if ( $wpdb->replace( $this->_tables['items'], $itemArr, $format ) === false ) {

                throw new Exception( 'Database error on INSERT:' .
                                     $wpdb->print_error() );
            }

            return $wpdb->insert_id; // Return the ID for the new DB record.

        } // End updateItem

        public function delItem( $itemId = null ) {

            global $wpdb;

            if ($itemId) {

                if ( $wpdb->delete( $this->_tables['items'], array( 'id' => $itemId ) ) === false ) {

                    throw new Exception( 'Database error on DELETE: ' .
                                         $wpdb->print_error() );
                }
            }
            else {
                throw new Exception( 'deleteItem() - No ID given' );
            }
        }

/*        public function editItem( $id, $data ) {

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
*/
        /**
         * Function renders the page to add/edit/remove check-in items for the E20R tracker plugin
         */
        public function render_submenu_page() {

            // $items = new E20Rcheckin();

            ?>
            <div id="e20r-checkin-items">
                <?php

                echo $this->view_manageCheckinItems();

                ?>
            </div>
        <?php
        }

    } // end class

endif; // end if
