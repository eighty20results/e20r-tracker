/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery(document).ready( function(){

    jQuery('#e20r-assignments-id').select2();

    $(document).on('change', '#e20r-article-post_id', function() {

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: {
                action: 'getDelayValue',
                'post_ID': $('#e20r-article-post_id').find('option:selected').val(),
                'e20r-tracker-article-settings-nonce': $('#e20r-tracker-article-settings-nonce').val()
            },
            success: function( $response ) {

                console.log("Received from getDelayValue: ", $response );

                if ( $response.data.nodelay != 0) {
                    console.log("No delay specified. Exiting!");
                    return false;
                }

                if ( $response.data.delay > 0 ) {

                    console.log("Got delay value from back-end: " + $response.data.delay);
                    $('#e20r-article-release_day').val($response.data.delay);
                }
            },
            error: function( $response, $errString, $errType ) {
                console.log($errString + ' error returned from getDelayValue action: ' + $errType );
            }
        });
    });

    function e20re20r_assignmentEdit( assignmentId ) {

    };

    function e20r_assignmentRemove( assignmentId ) {

    };

    function e20r_assignmentSave() {

    }
});