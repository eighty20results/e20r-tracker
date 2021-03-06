/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery(document).ready(function () {

    jQuery('#e20r-assignments-id').select2();
    jQuery('#e20r-article-post_id').select2();
    jQuery('#e20r-article-action_ids').select2();
    jQuery('#e20r-article-program_ids').select2();
    jQuery('#e20r-article-activity_id').select2();

    jQuery(document).on('change', '#e20r-article-post_id', function () {

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: e20r_tracker.timeout,
            dataType: 'JSON',
            data: {
                action: 'e20r_getDelayValue',
                'post_ID': jQuery('#e20r-article-post_id').find('option:selected').val(),
                'e20r-tracker-article-settings-nonce': jQuery('#e20r-tracker-article-settings-nonce').val()
            },
            success: function ($response) {

                console.log("Received from e20r_getDelayValue: ", $response);

                if ($response.data.delay > 0) {

                    console.log("Got delay value from back-end: " + $response.data.delay);
                    jQuery('#e20r-article-release_day').val($response.data.delay);
                }

                // Add the excerpt if available
                if ($response.data.summary.length > 0) {

                    window.console.log("Got a summary text from the back-end");
                    var ex_element = jQuery('textarea#excerpt');
                    var excerpt = ex_element.val().trim();
                    window.console.log(excerpt);

                    if (excerpt.length === 0) {
                        window.console.log("Setting the Excerpt to: " + $response.data.summary);
                        ex_element.val($response.data.summary);
                    }
                }
            },
            error: function ($response, $errString, $errType) {
                console.log($errString + ' error returned from e20r_getDelayValue action: ' + $errType);

                var $msg;

                if ('timeout' === $errString) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to save this data. If you\'d like to try again, please ";
                $string += "click your selection once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using our Contact form ";
                $string += "at the top of this page.";

                alert($msg + $string);
            }
        });

    });
});

function e20r_assignmentEdit(assignmentId, orderNum) {

    console.log("AssignmentId to edit: " + assignmentId);
    jQuery('#new-assignments').focus();
    jQuery('#e20r-add-assignment-id').val(assignmentId).trigger("change");
    jQuery('#e20r-add-assignment-order_num').val(orderNum);
    jQuery('#e20r-article-assignment-save').empty().append(e20r_tracker.lang.edit);

}

function e20r_assignmentRemove(assignmentId) {

    console.log("AssignmentId to remove: " + assignmentId);
    var saveBtn = jQuery('#e20r-article-assignment-save');

    if ('' == jQuery('#e20r-assignments-id').val() || undefined != saveBtn.attr('disabled'))
        return false; //already processing, ignore this request

    // Disable save button
    saveBtn.attr('disabled', 'disabled');
    saveBtn.html(e20r_tracker.lang.saving);

    wp.ajax.send({
        url: e20r_tracker.ajaxurl,
        type: 'POST',
        timeout: e20r_tracker.timeout,
        dataType: 'JSON',
        data: {
            action: "e20r_removeAssignment",
            'e20r-tracker-article-settings-nonce': jQuery('#e20r-tracker-article-settings-nonce').val(),
            'e20r-assignment-id': assignmentId,
            'e20r-article-id': jQuery('#post_ID').val()
        },
        success: function (resp) {
            // console.log("success() - Returned data: ", resp );

            if (resp) {
                console.log('Removed assignment& refreshing metabox content');
                jQuery('#e20r-assignment-settings').empty().append(resp);
            } else {
                console.log('No HTML returned???');
            }

        },
        error: function (jqxhr, $errString, $errType) {
            // console.log("error() - Returned data: ", jqxhr );
            console.log("Error String: " + $errString + " and errorType: " + $errType);

        },
        complete: function (response) {

            // Re-enable save button
            saveBtn.removeAttr('disabled');

        }
    });
}

function e20r_assignmentSave() {

    console.log("Save assignment to article");

    var saveBtn = jQuery('#e20r-article-assignment-save');

    if ('' == jQuery('#e20r-assignments-id').val() || undefined != saveBtn.attr('disabled'))
        return false; //already processing, ignore this request

    var $assignmentId = jQuery('#e20r-add-assignment-id').find("option:selected").val();

    if ($assignmentId === 0) {
        return false;
    }

    // Disable save button
    saveBtn.attr('disabled', 'disabled');
    saveBtn.empty().append(e20r_tracker.lang.saving);

    var resp = null;

    //pass field values to AJAX service and refresh table above - Timeout is 5 seconds
    wp.ajax.send({
        url: e20r_tracker.ajaxurl,
        type: 'POST',
        timeout: e20r_tracker.timeout,
        dataType: 'JSON',
        data: {
            action: "e20r_addAssignment",
            'e20r-tracker-article-settings-nonce': jQuery('#e20r-tracker-article-settings-nonce').val(),
            'e20r-assignment-id': $assignmentId,
            'e20r-assignment-post_id': jQuery('#e20r-article-post_id').val(),
            'e20r-assignment-order_num': jQuery('#e20r-add-assignment-order_num').val(),
            'e20r-article-id': jQuery('#post_ID').val()
        },
        success: function (resp) {
            console.log("success() - Returned data: ", resp);

            if (resp.reload === true) {

                location.reload();
            }
            else {

                if (resp.html !== '') {
                    // console.log('Entry added to sequence & refreshing metabox content');

                    var mBox = jQuery('#e20r-assignment-settings');
                    // console.log(mBox);
                    mBox.empty().append(resp.html);
                } else {
                    console.log('No HTML returned???');
                }
            }
        },
        error: function (jqxhr, $errString, $errType) {
            console.log("error() - Returned data: ", jqxhr);
            console.log("Error String: " + $errString + " and errorType: " + $errType);

            /*
            if ( resp.data ) {
                alert(resp.data);
                // pmpro_seq_setErroMsg(resp.data);
            } */
        },
        complete: function (response) {

            // Re-enable save button
            saveBtn.empty().append(e20r_tracker.lang.save);
            saveBtn.removeAttr('disabled');

        }
    });
}