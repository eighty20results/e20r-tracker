/**
 */

console.log("Loading back-end javascript script for e20r-tracker");

jQuery.noConflict();
jQuery(document).ready( function($) {


    console.log("WP-Admin script for E20R Tracker loaded");
    var $clientIdSelect = jQuery('#e20r_tracker_client');
    var $levelIdSelect = jQuery("#e20r_levels");

    var $oldClientId = $clientIdSelect.find('option:selected').val();

    var $detailBtn = jQuery("#e20r-client-info");
    var $complianceBtn = $("#e20r-client-compliance");
    var $assignBtn = $("#e20r-client-assignments");
    var $measureBtn = $("#e20r-client-measurements");
    var $loadBtn = $("#e20r-load-users");

    $levelIdSelect.change( function() {

        $detailBtn.prop('disabled', true);
        $complianceBtn.prop('disabled', true);
        $assignBtn.prop('disabled', true);
        $measureBtn.prop('disabled', true);

        console.log("Loading the user(s) to select from.");

        var $levelId = $levelIdSelect.find('option:selected').val();

        loadMemberList( $levelId );

        $("#e20r_tracker_client").prop('disabled', false);

        $detailBtn.prop('disabled', false);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);


    })
    // Load the list of users selected by the Level ID currently active.
    $loadBtn.click( function() {

        $("#e20r_tracker_client").attr('disabled', 'disabled');
        console.log("Loading the user(s) to select from.");

        var $levelId = $levelIdSelect.find('option:selected').val();

        loadMemberList( $levelId );

        $("#e20r_tracker_client").prop('disabled', false);
    });

    $clientIdSelect.change( function() {

        console.log("Modifying the client to process");

        var $clientId = $('#e20r_tracker_client').find('option:selected').val();

        console.dir($clientId);

        if ( $clientId != $oldClientId ) {

            $("#hidden_e20r_client_id").val($clientId);
        }

    });

    $detailBtn.click( function() {

        // Flip all the buttons
        $detailBtn.prop('disabled', true);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);

        // saveClientId($oldClientId);
        e20r_LoadClientData('info');

    })

    $complianceBtn.click( function() {
        // Flip all the buttons
        $detailBtn.removeAttr('disabled');
        $complianceBtn.attr('disabled', 'disabled');;
        $assignBtn.removeAttr('disabled');
        $measureBtn.removeAttr('disabled');

        // saveClientId($oldClientId);
        e20r_LoadClientData('compliance');

    })

    $assignBtn.click( function() {
        // Flip all the buttons
        $detailBtn.removeAttr('disabled');
        $complianceBtn.removeAttr('disabled');
        $assignBtn.attr('disabled', 'disabled');
        $measureBtn.removeAttr('disabled');

        // saveClientId($oldClientId);
        e20r_LoadClientData('assignments');

    })

    $measureBtn.click( function() {
        // Flip all the buttons
        $detailBtn.removeAttr('disabled');
        $complianceBtn.removeAttr('disabled');
        $assignBtn.removeAttr('disabled');
        $measureBtn.attr('disabled', 'disabled');

        // saveClientId($oldClientId);
        e20r_LoadClientData('measurements');

    })



});

function saveClientId( $oldClientId ) {

}

function loadMemberList( $levelId ) {

    jQuery('#spin-for-level').show();
    jQuery('#e20r_tracker_client').attr('disabled', 'disabled');

    console.log("Loading the list of members for he specified level");

    jQuery.ajax({
        url: e20r_tracker.ajaxurl,
        type: 'POST',
        timeout: 5000,
        dataType: 'JSON',
        data: {
            action: 'get_memberlistForLevel',
            e20r_tracker_levels_nonce: jQuery('#e20r_tracker_levels_nonce').val(),
            hidden_e20r_level: $levelId
        },
        error: function ($data) {
            if ($data.data !== '') {
                alert($data.data);
            }
        },
        success: function ($data) {

            // console.dir( $data );

            // Refresh the sequence post list (include the new post.
            if ($data.data !== '') {
                jQuery(".e20r-selectMember").html($data.data);
                console.log("Member list returned")
                jQuery(".e20r-selectMember").show();
                jQuery(".e20r-admin-hr").show();
                jQuery(".e20r-data-choices").show();
            }
        },
        complete: function () {

            // Enable the Save button again.
            // saveBtn.removeAttr('disabled');

            // Reset the text for the 'Save Settings" button
            // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

            jQuery('#spin-for-level').hide();
            //jQuery('#e20r_tracker_client').removeAttr('disabled');
        }
    });

}

function e20r_LoadClientData( $type ) {

    console.log("e20r_LoadClientData");

    var $btn;
    var $action = '';

    if ($type === 'info') {
        console.log("Looking for client detail");
        $action = 'e20r_clientDetail';
        $btn = jQuery("#e20r-client-info");

    } else if ($type === 'compliance') {

        $action = 'e20r_complianceData';
        $btn = jQuery("#e20r-client-compliance");

    } else if ($type === 'assignments') {

        $action = 'e20r_assignmentsData';
        $btn = jQuery("#e20r-client-assignments");

    } else if ($type === 'measurements') {

        $action = 'e20r_measurementsData';
        $btn = jQuery("#e20r-client-measurements");

    } else {
        console.log('Nothing to see');
    }

    jQuery('#load-client-data').show();

    // Disable save button
    // var $btn = jQuery('#pmpro_settings_save');
    $btn.attr('disabled', 'disabled');
    // saveBtn.html(e20r-tracker-admin.lang.saving);

    jQuery.ajax({
        url: e20r_tracker.ajaxurl,
        type: 'POST',
        timeout: 5000,
        dataType: 'JSON',
        data: {
            action: $action,
            e20r_client_detail_nonce: jQuery('#e20r_tracker_client_detail_nonce').val(),
            hidden_e20r_client_id: jQuery('#hidden_e20r_client_id').val()
        },
        error: function ($data) {
            if ($data.data !== '') {
                alert($data.data);
            }
        },
        success: function ($data) {

            setLabels();

            // Refresh the sequence post list (include the new post.
            if ($data.data !== '') {
                jQuery('#e20r-client-data').html($data.data);
            }
        },
        complete: function () {

            // Enable the Save button again.
            // saveBtn.removeAttr('disabled');

            // Reset the text for the 'Save Settings" button
            // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

            // Disable the spinner again
            jQuery('#load-client-data').hide();
        }
    });
}