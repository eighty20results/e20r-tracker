/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery(document).ready( function(){

    jQuery('#e20r-assignments-id').select2();
    jQuery('#e20r-article-post_id').select2();

    jQuery(document).on('change', '#e20r-article-post_id', function() {

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: {
                action: 'getDelayValue',
                'post_ID': jQuery('#e20r-article-post_id').find('option:selected').val(),
                'e20r-tracker-article-settings-nonce': jQuery('#e20r-tracker-article-settings-nonce').val()
            },
            success: function( $response ) {

                console.log("Received from getDelayValue: ", $response );

                if ( $response.data.nodelay != 0) {
                    console.log("No delay specified. Exiting!");
                    return false;
                }

                if ( $response.data.delay > 0 ) {

                    console.log("Got delay value from back-end: " + $response.data.delay);
                    jQuery('#e20r-article-release_day').val($response.data.delay);
                }
            },
            error: function( $response, $errString, $errType ) {
                console.log($errString + ' error returned from getDelayValue action: ' + $errType );
            }
        });
    });
});

function e20r_assignmentEdit( assignmentId ) {

    console.log("AssignmentId to edit: " + assignmentId );
};

function e20r_assignmentRemove( assignmentId ) {

    console.log("AssignmentId to remove: " + assignmentId );
};

function e20r_assignmentSave() {

    console.log("Save assignment to article" );

    var saveBtn = jQuery('#e20r-article-assignment-save');

    if ('' == jQuery('#e20r-assignments-id').val() || undefined != saveBtn.attr('disabled'))
        return false; //already processing, ignore this request

    // Disable save button
    saveBtn.attr('disabled', 'disabled');
    saveBtn.html(e20r_tracker.lang.saving);

    var resp = null;

    //pass field values to AJAX service and refresh table above - Timeout is 5 seconds
    wp.ajax.send({
        url: e20r_tracker.ajaxurl,
        type:'POST',
        timeout:5000,
        dataType: 'JSON',
        data: {
            action: "e20r_addAssignment",
            'e20r-tracker-article-settings-nonce': jQuery('#e20r-tracker-article-settings-nonce').val(),
            'e20r-assignment-id': jQuery('#e20r-add-assignment-id').find("option:selected").val(),
            'e20r-assignment-order_num': jQuery('#e20r-add-assignment-order_num').val(),
            'e20r-article-id': jQuery('#post_ID').val()
        },
        success: function( resp ){
            console.log("success() - Returned data: ", resp );

            if (resp.data) {
                console.log('Entry added to sequence & refreshing metabox content');
                jQuery('#e20r-assignment-settings').html(resp.data);
            } else {
                console.log('No HTML returned???');
            }

        },
        error: function(jqxhr, $errString, $errType){
            // console.log("error() - Returned data: ", jqxhr );
            console.log("Error String: " + $errString + " and errorType: " + $errType);

            /*
            if ( resp.data ) {
                alert(resp.data);
                // pmpro_seq_setErroMsg(resp.data);
            } */
        },
        complete: function(response) {

            // Re-enable save button
            saveBtn.html(e20r_tracker.lang.save);
            saveBtn.removeAttr('disabled');

        }
    });
}