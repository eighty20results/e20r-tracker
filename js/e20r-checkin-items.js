/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery(function() {

    var checkinItem = {
        init: function() {
            var self = this;
            self.$chekinList = jQuery('#e20r-checkin-items');
        },
        save: function( $valueArray ) {
            var $delete_action = false;

            if ( $valueArray['delete'] == true ) {
                console.log("User requested we delete a check-in item.");
                $delete_action = true;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 5000,
                dataType: 'JSON',
                data: {
                    action: 'save_item_data',
                    e20r_tracker_edit_nonce: $valueArray['nonce'],
                    e20r_checkin_item_id:  $valueArray['id'],
                    e20r_checkin_item_order: $valueArray['order'],
                    e20r_checkin_item_program_id: $valueArray['program_id'],
                    e20r_checkin_item_short_name: $valueArray['short_name'],
                    e20r_checkin_item_name: $valueArray['item_name'],
                    e20r_checkin_item_startdate: $valueArray['startdate'],
                    e20r_checkin_item_enddate: $valueArray['enddate'],
                    e20r_checkin_item_maxcount: $valueArray['maxcount'],
                    e20r_checkin_item_delete: $delete_action
                },
                error: function (data) {
                    console.dir(data);
                    alert( data.data );

                },
                success: function (data) {

                    // Refresh the sequence post list (include the new post.
                    if ( data.data !== '' ) {
                        console.dir( data );
                        self.$checkinList.html(data.data);
                        console.log("Data returned from save checkin item functionality");
                    }

                },
                complete: function () {

                    // Enable the Save button again.
                    // saveBtn.removeAttr('disabled');

                    // Reset the text for the 'Save Settings" button
                    // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

                    // Disable the spinner again
                    // jQuery('#load-new-programs').hide();
                    // $btn.removeAttr('disabled');
                }
            });
        }
    }
});