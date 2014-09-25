/**
 */

console.log("Loading back-end javascript script for e20r-tracker");

jQuery.noConflict();
jQuery(document).ready( function($) {


    console.log("WP-Admin script for E20R Tracker loaded");
    var $clientIdSelect = $("#e20r_tracker_client");
    var $levelIdSelect = $("#e20r_levels");

    var $oldClientId = $clientIdSelect.find('option:selected').val();

    var $detailBtn = $("#e20r-client-info");
    var $complianceBtn = $("#e20r-client-compliance");
    var $assignBtn = $("#e20r-client-assignments");
    var $measureBtn = $("#e20r-client-measurements");
    var $loadBtn = $("#e20r-load-users");

    $levelIdSelect.change( function() {

        $detailBtn.prop('disabled', true);
        $complianceBtn.prop('disabled', true);
        $assignBtn.prop('disabled', true);
        $measureBtn.prop('disabled', true);

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

        $("#e20r_tracker_client").prop('disabled', true);

        console.log("Loading the user(s) to select from.");

        var $levelId = $levelIdSelect.find('option:selected').val();

        loadMemberList( $levelId );

        $("#e20r_tracker_client").prop('disabled', false);
    })

    $clientIdSelect.change( function() {

        console.log("Client to find changed");
        saveClientId( $oldClientId );
/*
        $detailBtn.prop('disabled', false);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);
*/
    });

    $detailBtn.click( function() {

        /*
        // Flip all the buttons
        $detailBtn.prop('disabled', false);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);
        */
        // saveClientId($oldClientId);
        e20r_LoadClientData('info');
        saveClientId( $oldClientId );

    })

    $complianceBtn.click( function() {
        /*
        // Flip all the buttons
        $detailBtn.prop('disabled', false);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);
        */
        // saveClientId($oldClientId);
        e20r_LoadClientData('compliance');
        saveClientId( $oldClientId );

    })

    $assignBtn.click( function() {
        /*
        // Flip all the buttons
        $detailBtn.prop('disabled', false);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);
        */
        // saveClientId($oldClientId);
        e20r_LoadClientData('assignments');
        saveClientId( $oldClientId );

    })

    $measureBtn.click( function() {
        /*
        // Flip all the buttons
        $detailBtn.prop('disabled', false);
        $complianceBtn.prop('disabled', false);
        $assignBtn.prop('disabled', false);
        $measureBtn.prop('disabled', false);
        */
        // saveClientId($oldClientId);
        e20r_LoadClientData('measurements');
        saveClientId( $oldClientId );

    })

});

function saveClientId( $oldClientId ) {

    console.log("Modifying the client to process");

    var $clientId = jQuery('#e20r_tracker_client').find('option:selected').val();

    console.dir($clientId);

    if ( $clientId != $oldClientId ) {

        jQuery("#hidden_e20r_client_id").val($clientId);
    }

}

function loadMemberList( $levelId ) {

    jQuery('#spin-for-level').show();
    jQuery('#e20r_tracker_client').prop('disabled', true);

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
            jQuery('#e20r_tracker_client').prop('disabled', false);
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
            hidden_e20r_client_id: jQuery('#e20r_tracker_client').val()
        },
        error: function ($data) {
            console.dir($data);
            // alert($data);

        },
        success: function ($data) {

            setLabels();

            // Refresh the sequence post list (include the new post.
            if ( ( $data.data !== '' ) && ( $type == 'info') ) {
                jQuery('#e20r-info').html($data.data);
            }

            if ( ( $data.data !== '' ) && ( $type == 'measurements') ) {
                // console.dir($data);
                jQuery('#e20r-measurements').html($data.data);
                console.dir( 'Weight: ' + $data.weight );
                console.dir( 'Girth: ' + $data.girth );

                var $ticks = ['Date', 'Weight'];

                var $wPlot = jQuery.jqplot( 'weight_chart', [ $data.weight ], {
                    title: 'Weight History',
                    gridPadding:{right:35},
                    seriesDefaults: {
                        showMarker:false,
                        pointLabels: { show:true }
                    },
                    axes: {
                        xaxis: {
                            renderer: jQuery.jqplot.DateAxisRenderer
                            //tickOptions:{formatString:'%m-%#d-%y'}
                            // tickInterval:'1 week'
                        }
                    },
                    series:[{
                        color: '#9C0000',
                        lineWidth:4,
                        markerOptions:{
                            style:'circle'
                        }
                    }],
                    legend: {
                        show: true,
                        location: 'ne'
                    }
                } );

                var gPlot = jQuery.jqplot('girth_chart', [ $data.girth ], {
                    title: 'Total Girth',
                    gridPadding:{right:35},
                    seriesDefaults: {
                        showMarker:false,
                        pointLabels: { show:true }
                    },
                    axes: {
                        xaxis: {
                            renderer: jQuery.jqplot.DateAxisRenderer
                            // tickOptions:{formatString:'%m-%#d-%y'}
                            //tickInterval:'1 week'
                        }
                    },
                    series:[{
                        color: '#9C0000',
                        lineWidth:4,
                        markerOptions:{
                            style:'circle'
                        }
                    }]
                });

                }

        },
        complete: function () {

            // Enable the Save button again.
            // saveBtn.removeAttr('disabled');

            // Reset the text for the 'Save Settings" button
            // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

            // Disable the spinner again
            jQuery('#load-client-data').hide();
            $btn.removeAttr('disabled');
        }
    });


}