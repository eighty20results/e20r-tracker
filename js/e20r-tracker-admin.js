/**
 */

console.log("Loading back-end javascript script for e20r-tracker");

/*
var $old_Id;
var $old_Name;
var $old_startDate;
var $old_endDate;
var $old_Description;
var $old_membershipId;
*/
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
    var $loadBtn = $("#e20r-load-data");

    var $loadItem = $("#e20r-load-checkin-items");
    var $spinner = $('#e20r-postmeta-setprogram').find('e20r_spinner');


    $(document).on('click', '#e20r-new-group-button', function() {

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 5000,
            dataType: 'JSON',
            data: {
                action: 'addWorkoutGroup',
                'e20r-tracker-workout-settings-nonce': $('#e20r-tracker-workout-settings-nonce').val(),
                'post_ID': $('#post_ID').val()
            },
            error: function($response, $errString, $errType) {
                console.log($errString + ' error returned from addWorkoutGroup action: ' + $errType );
                console.dir( $response );
                return;
            },
            success: function( $retVal ) {

                console.log( 'Group data being added', $retVal );

                if ( $retVal.data.groupHtml !== '' ) {

                    jQuery('#e20r-workout-tbody').append($retVal.data.groupHtml);

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
    });

    $(document).on('click', '.e20r-remove-group', function () {

        var $header = $(this).closest('tr');
        var $data = $header.next('tr');

        $header.remove();
        $data.remove();
        return false;
    });

    $(document).on('click', '.e20r-exercise-remove', function () {

        var $exLine = $(this).closest('tr');

        $exLine.remove();

    });

    $(document).on('click', '.e20r-workout-save-exercise', function() {

        var $exerciseList = $(this).closest('.e20r-exercise-list').find('tbody');

        var $tFooter = $(this).closest('.e20r-exercise-list').find('tfoot');
        var $groupId = $tFooter.find("input[id*='e20r-workout-group_id-']").val();

        var $exDef = $tFooter.find("select[class*='e20r-workout-group-exercise']");
        var $exReps = $tFooter.find("input[id*='e20r-workout-add-exercise-reps']");
        var $exRest = $tFooter.find("input[id*='e20r-workout-add-exercise-rest']");
        var $exType = $tFooter.find("select[id*='e20r-workout-add-exercise-type']");

        var $data = {
            'action': 'addExerciseToGroup',
            'e20r-tracker-workout-settings-nonce': $('#e20r-tracker-workout-settings-nonce').val(),
            'post_ID': $('#post_ID').val(),
            'group_id': $groupId,
            'exercise-id': $exDef.find("option:selected").val(),
            'exercise-type': $exType.find("option:selected").val(),
            'exercise-reps': $exReps.val(),
            'exercise-rest': $exRest.val()
        }

        console.log("Action data: ", $data );

        // Strip out the list of existing exercises.
        $exerciseList.find('tr').each( function() {
            if ( this.length == 1) {
                this.remove();
            }
        });

    });

    $(document).on("change", "#e20r_tracker_client",function() {

        console.log("Client to find changed");
        saveClientId( $oldClientId );

    });

    /*********************************************************/

    $(document).on("click", "#e20r-load-checkin-items", function() {

        $loadItem.prop('disabled', true);
        jQuery('#spin-for-checkin-item').show();

        loadCheckinItem( $('#e20r_checkin_items').find('option:selected').val() );
        jQuery('#spin-for-checkin-item').show();
        $loadItem.prop('disabled', false);
    });

    $(document).on("click","#e20r-client-info", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('info');
        saveClientId( $oldClientId );

    });

    $(document).on("click", "#e20r-client-compliance", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('compliance');
        saveClientId( $oldClientId );

    });

    $(document).on("click", "#e20r-client-assignments", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('assignments');
        saveClientId( $oldClientId );

    });
/*
    $(document).on("click", "#e20r-client-measurements", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('measurements');
        saveClientId( $oldClientId );

    });
*/


    $(document).on("focus", 'textarea.expand', function() {

        $(this).animate({height: "10em", width: "400px"}, 500);

    });

    $(document).on("focusout", 'textarea.expand', function() {

        $(this).animate({height: "28px", width: "250px"}, 500);

    });


/*    $('input:checkbox').change( function() {

        console.log("Processing the list of programs.")

        var $checkedIds = $(":checkbox:checked").map(function() {
            return this.id.split('_')[1];
        }).get();

    })
 */

    $(document).on( "click", '#e20r-add-new-item', function() {

        $('.add-new').hide();
        $('#add-new-checkin-item').show();

    });

    $(document).on( 'change', '.new-e20rprogram-select', function () {

        e20rPgm_postMetaSelectChanged( this );
    });

    $(document).on( "click", "#e20r-tracker-new-meta", function() {

        e20rPgm_lockMetaRows();
        $spinner.show();

        e20rPgm_rowVisibility( jQuery( '.new-e20rprogram-select' ), 'select' );

        $spinner.hide();
        // $(this).hide();
        // $('#pmpro-seq-new-meta-reset').show();
        e20rPgm_unlockMetaRows();
    });

    $(document).on( "click", "#e20r-tracker-new-meta-reset", function() {

        e20rPgm_lockMetaRows();

        $spinner.show();

        e20rPgm_showMetaControls();

        $spinner.hide();

        e20rPgm_unlockMetaRows();
    });

});


/*
function getCheckboxWithStatus( $status ) {

    if ( $status == 'unchecked' ) {
        return jQuery(":checkbox:not(:checked)").map(function () {
            return this.id.split('_')[1];
        }).get();
    }
    else {
        return jQuery(":checkbox:checked").map(function() {
            return this.id.split('_')[1];
        }).get();
    }
}

function saveItem( $valueArray ) {

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
                jQuery('#e20r-checkin-items').html(data.data);
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

function saveProgram( $programArray ) {

    var $delete_action = false;

    if ( $programArray['delete'] == true ) {
        $delete_action = true;
    }

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        timeout: 5000,
        dataType: 'JSON',
        data: {
            action: 'save_program_info',
            e20r_tracker_edit_programs_nonce: jQuery('#e20r_tracker_edit_programs').val(),
            e20r_program_id: $programArray['id'],
            e20r_program_name: $programArray['name'],
            e20r_program_start: $programArray['start'],
            e20r_program_end: $programArray['end'],
            e20r_program_descr: $programArray['descr'],
            e20r_program_memberships: $programArray['membership_id'],
            e20r_program_delete: $delete_action
        },
        error: function (data) {
            console.dir(data);
            alert( data.data );

        },
        success: function (data) {

            // Refresh the sequence post list (include the new post.
            if ( data.data !== '' ) {
                jQuery('#e20r-program-list').html(data.data);
                console.log("Data returned from save program functionality");
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

        $action = 'e20r_loadProgress';
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
        url: ajaxurl,
        type: 'POST',
        timeout: 5000,
        dataType: 'JSON',
        async: false,
        data: {
            action: $action,
            e20r_client_detail_nonce: jQuery('#e20r_tracker_client_detail_nonce').val(),
            hidden_e20r_client_id: jQuery('#e20r_tracker_client').val()
        },
        error: function (data) {
            console.dir(data);
            // alert(data);

        },
        success: function (data) {

            // Refresh the sequence post list (include the new post.
            if ( ( data.data !== '' ) && ( $type == 'info') ) {
                jQuery('#e20r-progr-info').html( data.data );
            }

            if ( ( data.data !== '' ) && ( $type == 'measurements') ) {

                jQuery('#e20r-progr-measurements').html(data.data);

                var firstDate;
                var lastDate;

                var $minTick;
                var $maxTick;

                var $entries;
                var $stepSize;


                if ( ( typeof data.weight !== 'undefined' ) && ( data.weight.length > 0 ) ) {

                    firstDate = data.weight[0][0];
                    lastDate = data.weight[data.weight.length - 1][0];

                    $minTick = firstDate - 604800000;
                    $maxTick = lastDate + 604800000;

                    $entries = data.weight.length;
                    $stepSize = '1 week';

                    if ($entries >= 26) {
                        $stepSize = '2 weeks';
                    }

                    var $wPlot = jQuery.jqplot('weight_chart', [data.weight], {
                        title: 'Weight History',
                        gridPadding: {right: 35},
                        // legend: {show: false },
                        seriesDefaults: {
                            showMarker: false,
                            pointLabels: {show: false}
                        },
                        axes: {
                            xaxis: {
                                renderer: jQuery.jqplot.DateAxisRenderer,
                                labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                tickOptions: {
                                    fontFamily: 'Verdana',
                                    fontSize: '9px',
                                    angle: 30,
                                    formatString: '%v',
                                    showLabel: true
                                },
                                showTicks: true,
                                tickInterval: $stepSize,
                                min: $minTick,
                                max: $maxTick
                            },
                            yaxis: {
                                labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                tickOptions: {formatString: '%.0f'}
                            }
                        },
                        series: [{
                            color: '#004DFF',
                            lineWidth: 4,
                            markerOptions: {
                                style: 'square'
                            },
                            rendererOptions: {
                                smooth: true
                            }
                        }]
                    });
                }

                if ( ( typeof data.girth !== 'undefined' ) && ( data.girth.length > 0 ) ) {

                    firstDate = data.girth[0][0];
                    lastDate = data.girth[data.girth.length - 1][0];

                    $minTick = firstDate - 604800000;
                    $maxTick = lastDate + 604800000;

                    $entries = data.girth.length;
                    $stepSize = '1 week';

                    if ($entries >= 26) {
                        $stepSize = '2 weeks';
                    }

                    var gPlot = jQuery.jqplot('girth_chart', [data.girth], {
                        title: 'Total Girth',
                        gridPadding: {right: 35},
                        seriesDefaults: {
                            showMarker: false,
                            pointLabels: {show: false}
                        },
                        axes: {
                            xaxis: {
                                renderer: jQuery.jqplot.DateAxisRenderer,
                                labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                tickOptions: {
                                    fontFamily: 'Verdana',
                                    fontSize: '9px',
                                    angle: 30,
                                    formatString: '%v',
                                    showLabel: true
                                },
                                showTicks: true,
                                tickInterval: $stepSize,
                                min: $minTick,
                                max: $maxTick
                            },
                            yaxis: {
                                labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                tickOptions: {formatString: '%.0f'}
                            }
                        },
                        series: [{
                            color: '#004DFF',
                            lineWidth: 4,
                            markerOptions: {
                                style: 'square'
                            },
                            rendererOptions: {
                                smooth: true
                            }
                        }]
                    });

                }
                else {
                    alert("This user doesn't have any data to graph yet");
                }
            }

        },
        complete: function () {

            // Enable the Save button again.
            // saveBtn.removeAttr('disabled');

            // Reset the text for the 'Save Settings" button
            // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

            jQuery('.timeago')
                .each(function() {
                    jQuery(this)
                        .text(function(text) {
                            return jQuery.timeago(text);
                        });
                });

            jQuery("#e20r-measurement-table").delegate('td','mouseover mouseleave', function(e) {
                if (e.type == 'mouseover') {
                    jQuery(this).parent().addClass("hover");
                    jQuery("colgroup").eq(jQuery(this).index()).addClass("hover");
                }
                else {
                    jQuery(this).parent().removeClass("hover");
                    jQuery("colgroup").eq(jQuery(this).index()).removeClass("hover");
                }
            });

            // Disable the spinner again
            jQuery('#load-client-data').hide();
            $btn.removeAttr('disabled');
        }
    });
}

function loadCheckinItem( $itemId ) {

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        timeout: 5000,
        dataType: 'JSON',
        data: {
            action: 'get_checkinItem',
            e20r_tracker_checkin_items_nonce: jQuery('#e20r_tracker_checkin_items_nonce').val(),
            hidden_e20r_checkin_item_id: $itemId
        },
        error: function (data) {
            console.dir(data);
            // alert(data);

        },
        success: function (data) {

            // Refresh the sequence post list (include the new post.
            if (data.data !== '') {
                jQuery('#edit-checkin-items').html(data.data);
            }
        }
    });
}


*/