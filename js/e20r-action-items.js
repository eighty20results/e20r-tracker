/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery(function() {

    var actionItem = {
        init: function() {
            var self = this;
            self.$actionList = jQuery('#e20r-action-items');
        },
        save: function( $valueArray ) {
            var $delete_action = false;

            if ( $valueArray['delete'] == true ) {
                console.log("User requested we delete an action item.");
                $delete_action = true;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: e20r_action.timeout,
                dataType: 'JSON',
                data: {
                    action: 'e20r_save_item_data',
                    e20r_tracker_edit_nonce: $valueArray['nonce'],
                    e20r_action_item_id:  $valueArray['id'],
                    e20r_action_item_order: $valueArray['order'],
                    e20r_action_item_program_id: $valueArray['program_id'],
                    e20r_action_item_short_name: $valueArray['short_name'],
                    e20r_action_item_name: $valueArray['item_name'],
                    e20r_action_item_startdate: $valueArray['startdate'],
                    e20r_action_item_enddate: $valueArray['enddate'],
                    e20r_action_item_maxcount: $valueArray['maxcount'],
                    e20r_action_item_delete: $delete_action
                },
                error: function( $response, $errString, $errType ) {

                    var $msg;

                    if ( 'timeout' === $errString ) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to save this data. If you\'d like to try again, please ";
                    $string += "click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert( $msg + $string );

                },
                success: function (data) {

                    // Refresh the sequence post list (include the new post.
                    if ( data.data !== '' ) {
                        console.dir( data );
                        self.$actionList.html(data.data);
                        console.log("Data returned from save action item functionality");
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