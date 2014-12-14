/**
 */

console.log("Loading back-end javascript script for e20r-tracker");

var $old_Id;
var $old_Name;
var $old_startDate;
var $old_endDate;
var $old_Description;
var $old_membershipId;

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

    var $loadItem = $("#e20r-load-checkin-items");
    var $spinner = $('#e20r-postmeta-setprogram').find('e20r_spinner');

    e20rPgm_showMetaControls();

    $(document).on("change", "#e20r_levels", function() {

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


    });

    // Load the list of users selected by the Level ID currently active.
    $(document).on("click", "#e20r-load-users", function() {

        $("#e20r_tracker_client").prop('disabled', true);

        console.log("Loading the user(s) to select from.");

        var $levelId = $levelIdSelect.find('option:selected').val();

        loadMemberList( $levelId );

        $("#e20r_tracker_client").prop('disabled', false);
    });


    $(document).on("change", "#e20r_tracker_client",function() {

        console.log("Client to find changed");
        saveClientId( $oldClientId );

    });

    $(document).on("click", "#e20r-load-checkin-items", function() {

        $loadItem.prop('disabled', true);
        jQuery('#spin-for-checkin-item').show();

        loadCheckinItem( $('#e20r_checkin_items').find('option:selected').val() );
        jQuery('#spin-for-checkin-item').show();
        $loadItem.prop('disabled', false);
    })

    $(document).on("click","#e20r-client-info", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('info');
        saveClientId( $oldClientId );

    })

    $(document).on("click", "#e20r-client-compliance", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('compliance');
        saveClientId( $oldClientId );

    })

    $(document).on("click", "#e20r-client-assignments", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('assignments');
        saveClientId( $oldClientId );

    })

    $(document).on("click", "#e20r-client-measurements", function() {

        // saveClientId($oldClientId);
        e20r_LoadClientData('measurements');
        saveClientId( $oldClientId );

    })

    $( document).on( "click", '.checkin-inputs input:checkbox', function() {

        console.log('Check-in Item List checkbox checked');

        if ( $(this).is(':checked') ) {

            jQuery('.e20r-save-col').show();
            jQuery('.e20r-cancel-col').show();
            jQuery('.e20r-delete-col').show();
            console.log("Enabling edit of this line");

            $(this).attr( 'checked', true);
            enableEditItem();
        }
        else {

            var $checkedIds = jQuery(":checkbox:checked").map(function() {
                return this.id.split('_')[1];
            }).get();

            if ($checkedIds.length == 0) {
                jQuery('.e20r-save-col').hide();
                jQuery('.e20r-cancel-col').hide();
                jQuery('.e20r-delete-col').hide();
            }

            console.log("Disabling edit of this line");
            $(this).attr( 'checked', false);
            disableEditItem();
        }

        // $('input:checkbox').attr('checked', false);
    });

    $( document).on( "click", '.program-inputs input:checkbox', function() {

        console.log('Program List checkbox checked');

        if ( $(this).is(':checked') ) {

            jQuery('.e20r-save-col').show();
            jQuery('.e20r-cancel-col').show();
            jQuery('.e20r-delete-col').show();
            console.log("Enabling edit of this line");

            $(this).attr( 'checked', true);
            enableEditProgram();
        }
        else {

            var $checkedIds = jQuery(":checkbox:checked").map(function() {
                return this.id.split('_')[1];
            }).get();

            if ($checkedIds.length == 0) {
                jQuery('.e20r-save-col').hide();
                jQuery('.e20r-cancel-col').hide();
                jQuery('.e20r-delete-col').hide();
            }

            console.log("Disabling edit of this line");
            $(this).attr( 'checked', false);
            disableEditProgram();
        }

        // $('input:checkbox').attr('checked', false);
    });

    $(document).on( "click", '#e20r-add-new-program', function() {

        $('.add-new').hide();
        $('#add-new-program').show();

    });

    $(document).on( "click", "#e20r-save-new-checkin-item", function() {

        console.log("Saving new Checkin-item to database - AJAX'ed.");

        var $itemInfo = new Array();

        $itemInfo['nonce'] = $( '#e20r_tracker_edit_nonce' ).val();
        $itemInfo['id'] = $( '#e20r-checkin-item-id' ).val();
        $itemInfo['program_id'] = $( '#e20r-choose-program' ).find( 'option:selected' ).val();
        $itemInfo['order'] = $( '#e20r-checkin-item-order' ).val();
        $itemInfo['short_name'] = $( '#e20r-checkin-item-short-name' ).val();
        $itemInfo['item_name'] = $( '#e20r-checkin-item-name' ).val();
        $itemInfo['startdate'] = $( '#e20r-checkin-item-startdate' ).val();
        $itemInfo['enddate'] = $( '#e20r-checkin-item-enddate' ).val();
        $itemInfo['maxcount'] = $( '#e20r-checkin-item-maxcount' ).val();

        console.dir( $itemInfo );

        saveItem( $itemInfo );
    });

    $(document).on( "click", '#e20r-save-new-program', function() {

        console.log("Save new program info to database - Ajax'ed");

        var $programInfo = new Array();

        $programInfo['id'] = $( '#e20r-program_id' ).val();
        $programInfo['name'] = $( '#e20r-program_name' ).val();
        $programInfo['start'] = $( '#e20r-program-starttime' ).val();
        $programInfo['end'] = $( '#e20r-program-endtime' ).val();
        $programInfo['descr'] = $( '#e20r-program-descr' ).val();
        $programInfo['membership_id'] = $( '#e20r-memberships' ).val();

        console.dir($programInfo);

        saveProgram( $programInfo );
    });

    $(document).on( "click", ".e20r-save-edit-checkin-item", function() {

        console.log("Saving edited check-in item to database - AJAX'ed.");

        var $itemInfo = new Array();
        var $id = $(this).parent().attr("id").split('_')[1];

        console.log(" Row ID: " + $id );

        $itemInfo['nonce'] = $( '#e20r_tracker_edit_nonce' ).val();
        $itemInfo['id'] = $( '#e20r-checkin-item-id_' + $id ).val();
        $itemInfo['program_id'] = $( '#e20r-choose-program_' + $id ).find( 'option:selected' ).val();
        $itemInfo['order'] = $( '#e20r-checkin-item-order_' + $id ).val();
        $itemInfo['short_name'] = $( '#e20r-checkin-item-short-name_' + $id ).val();
        $itemInfo['item_name'] = $( '#e20r-checkin-item-name_'  + $id ).val();
        $itemInfo['startdate'] = $( '#e20r-checkin-item-startdate_'  + $id ).val();
        $itemInfo['enddate'] = $( '#e20r-checkin-item-enddate_' + $id ).val();
        $itemInfo['maxcount'] = $( '#e20r-checkin-item-maxcount_'  + $id ).val();

        console.dir( $itemInfo );

        saveItem( $itemInfo );
    });

    $(document).on( "click", '.e20r-save-edit-program', function() {

        var $programInfo = new Array();

        // Get the ID to use for the edited input boxes
        var $id = $(this).parent().attr("id").split('_')[1];

        $programInfo['id'] = $( '#e20r-program_id_' + $id ).val();
        $programInfo['name'] = $( '#e20r-program_name_' + $id ).val();
        $programInfo['start'] = $( '#e20r-program-starttime_' + $id ).val();
        $programInfo['end'] = $( '#e20r-program-endtime_' + $id ).val();
        $programInfo['descr'] = $( '#e20r-program-descr_' + $id ).val();
        $programInfo['membership_id'] = $( '#e20r-memberships_' + $id ).val();

        console.dir($programInfo);
        saveProgram( $programInfo );

    });

    $(document).on( "click", '#e20r-cancel-new-checkin-item', function() {

        console.log("Clear & hide the new checkin item row");

        $('.add-new').show();
        $('#add-new-checkin-item').hide();

        /* Clear out any entries - we cancelled, remember...*/
        $( '#e20r-checkin-item-order' ).val( null );
        $( '#e20r-choose-program option:eq(0)' ).prop( 'selected', true );
        $( '#e20r-checkin-item-name' ).val( null );
        $( '#e20r-checkin-item-short-name' ).val( null );
        $( '#e20r-checkin-item-startdate' ).val( null );
        $( '#e20r-checkin-item-enddate' ).val( null );
        $( '#e20r-checkin-item-maxcount' ).val( null );

    });

    $(document).on( "click", '#e20r-cancel-new-program', function() {

        console.log("Clear & hide the new program row");

        $('.add-new').show();
        $('#add-new-program').hide();

        /* Clear out any entries - we cancelled, remember...*/
        $( '#e20r-program_name' ).val( null );
        $( '#e20r-program-starttime' ).val( null );
        $( '#e20r-program-endtime' ).val( null );
        $( '#e20r-program-descr' ).val( null );
        $( '#e20r-memberships' ).val(0);

    });

    $(document).on( "click", '.e20r-cancel-edit-program', function() {

        var $edit_id = '#edit_' + $old_Id;

        if ( $( $edit_id ).is(':checked') ) {
            console.log("Edit checkbox is checked, undo it.");
            $( $edit_id ).prop('checked', false);
        }

        // jQuery( '#e20r-program_id_' + $old_Id ).val($old_Id);
        $( '#e20r-program_name_' + $old_Id ).val($old_Name);
        $( '#e20r-program-starttime_' + $old_Id ).val($old_startDate);
        $( '#e20r-program-endtime_' + $old_Id ).val($old_endDate);
        $( '#e20r-program-descr_' + $old_Id ).val($old_Description);
        $( '#e20r-memberships_' + $old_Id).val($old_membershipId);

        disableEditProgram();

    });

    $(document).on("focus", 'textarea.expand', function() {

        $(this).animate({height: "10em", width: "400px"}, 500);

    });

    $(document).on("focusout", 'textarea.expand', function() {

        $(this).animate({height: "28px", width: "250px"}, 500);

    });

    $(document).on("click", ".e20r-delete-checkin-item", function() {

        var $itemInfo = new Array();

        // Get the ID to use for the edited input boxes
        var $id = $(this).parent().attr("id").split('_')[1];

        $itemInfo['nonce'] = $( '#e20r_tracker_edit_nonce' ).val();
        $itemInfo['id'] = $( '#e20r-checkin-item-id_' + $id ).val();
        $itemInfo['delete'] = true;

        console.dir($itemInfo);
        saveItem( $itemInfo );

    });

    $(document).on("click", ".e20r-delete-program", function() {

        var $programInfo = new Array();

        // Get the ID to use for the edited input boxes
        var $id = $(this).parent().attr("id").split('_')[1];

        $programInfo['id'] = $( '#e20r-program_id_' + $id ).val();
        $programInfo['delete'] = true;

        console.dir($programInfo);
        saveProgram( $programInfo );

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

/*    $(document).on( 'change', '.e20r-tracker-memberof-programs', function () {

        e20rPgm_postMetaSelectChanged( this );

    });
*/
 /*   $(document).on( "click", '.delay-row-input input:checkbox', function() {

        console.log("The 'remove' checkbox was clicked...");
        e20rPgm_lockMetaRows();

        $spinner.show();

        jQuery.ajax({
            url: e20r_tracker.ajaxurl,
            type:'POST',
            timeout:10000,
            dataType: 'JSON',
            data: {
                'action': 'e20r_rm_program',
                'e20r-program-id': $(this).val(),
                'e20r-program-post_id': $('#post_ID').val(),
                'e20r-tracker-program-nonce': $('#e20r-tracker-program-nonce').val()
            },
            error: function($data){

                console.dir($data);

                if ($data.data != '') {
                    alert($data.data);
                }

            },
            success: function($data){

                console.dir($data);

                if ($data.data) {
                    jQuery('#e20r-postmeta-setprogram').html( $data.data );
                    //showMetaControls();
                }

            },
            complete: function() {

                e20rPgm_showMetaControls();
                e20rPgm_unlockMetaRows();
                $spinner.hide();
            }
        });
    });
*/
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

function enableEditItem() {

    var $checkedIds = getCheckboxWithStatus( 'checked' );

    jQuery.each( $checkedIds, function() {

        // TODO: Save old values first - in case we cancel
        jQuery( '#e20r-checkin-item-id_' + this ).prop("disabled", false);
        jQuery( '#e20r-checkin-item-order_' + this ).prop("disabled", false );
        jQuery( '#e20r-choose-program_' + this ).prop("disabled", false );
        jQuery( '#e20r-checkin-item-short-name_' + this ).prop("disabled", false);
        jQuery( '#e20r-checkin-item-name_' + this ).prop("disabled", false);
        jQuery( '#e20r-checkin-item-startdate_' + this ).prop("disabled", false );
        jQuery( '#e20r-checkin-item-enddate_' + this ).prop("disabled", false );
        jQuery( '#e20r-checkin-item-maxcount_' + this ).prop("disabled", false );
        jQuery( '#e20r-td-save_' + this ).show();
        jQuery( '#e20r-td-cancel_' + this).show();
        jQuery( '#e20r-td-delete_' + this).show();
        // jQuery( '#e20r-edit-save_' + this ).show();
    });
}

function disableEditItem() {

    var $checkedIds = getCheckboxWithStatus( 'unchecked' );

    jQuery.each( $checkedIds, function() {

        jQuery( '#e20r-checkin-item-id_' + this ).prop("disabled", true);
        jQuery( '#e20r-checkin-item-order_' + this ).prop("disabled", true );
        jQuery( '#e20r-choose-program_' + this ).prop("disabled", true );
        jQuery( '#e20r-checkin-item-short-name_' + this ).prop("disabled", true);
        jQuery( '#e20r-checkin-item-name_' + this ).prop("disabled", true);
        jQuery( '#e20r-checkin-item-startdate_' + this ).prop("disabled", true );
        jQuery( '#e20r-checkin-item-enddate_' + this ).prop("disabled", true );
        jQuery( '#e20r-checkin-item-maxcount_' + this ).prop("disabled", true );
        jQuery( '#e20r-td-save_' + this ).hide();
        jQuery( '#e20r-td-cancel_' + this).hide();
        jQuery( '#e20r-td-delete_' + this).hide();
        // jQuery( '#e20r-edit-save_' + this ).show();
    });

}

function enableEditProgram() {

    var $checkedIds = getCheckboxWithStatus( 'checked' );

    jQuery.each( $checkedIds, function() {

        $old_Id = jQuery( '#e20r-program_id_' + this ).val();
        $old_Name = jQuery( '#e20r-program_name_' + this ).val();
        $old_startDate = jQuery( '#e20r-program-starttime_' + this ).val();
        $old_endDate = jQuery( '#e20r-program-endtime_' + this ).val();
        $old_Description = jQuery( '#e20r-program-descr_' + this ).val();
        $old_membershipId = jQuery( '#e20r-memberships_' + this).val();

        jQuery( '#e20r-program_id_' + this ).prop("disabled", false);
        jQuery( '#e20r-program_name_' + this ).prop("disabled", false);
        jQuery( '#e20r-program-starttime_' + this ).prop("disabled", false );
        jQuery( '#e20r-program-endtime_' + this ).prop("disabled", false );
        jQuery( '#e20r-program-descr_' + this ).prop("disabled", false );
        jQuery( '#e20r-memberships_' + this).prop("disabled", false);
        jQuery( '#e20r-td-save_' + this ).show();
        jQuery( '#e20r-td-cancel_' + this).show();
        jQuery( '#e20r-td-delete_' + this).show();
        // jQuery( '#e20r-edit-save_' + this ).show();
    });

}

function disableEditProgram() {

    var $unCheckedIds = getCheckboxWithStatus( 'unchecked' );

    jQuery.each( $unCheckedIds, function() {

        /* Disable all input boxes */
        jQuery( '#e20r-program_id_' + this ).prop("disabled", true );
        jQuery( '#e20r-program_name_' + this ).prop("disabled", true );
        jQuery( '#e20r-program-starttime_' + this ).prop("disabled", true );
        jQuery( '#e20r-program-endtime_' + this ).prop("disabled", true );
        jQuery( '#e20r-program-descr_' + this ).prop("disabled", true );
        jQuery( '#e20r-memberships_' + this).prop("disabled", true);
        jQuery( '#e20r-td-save_' + this ).hide();
        jQuery( '#e20r-td-cancel_' + this).hide();
        jQuery( '#e20r-td-delete_' + this).hide();
    });
}

function saveClientId( $oldClientId ) {

    var $clientId = jQuery('#e20r_tracker_client').find('option:selected').val();

    if ( $clientId != $oldClientId ) {

        jQuery("#hidden_e20r_client_id").val($clientId);
    }

}

function loadMemberList( $levelId ) {

    jQuery('#spin-for-level').show();
    jQuery('#e20r_tracker_client').prop('disabled', true);

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

function saveItem( $valueArray ) {

    var $delete_action = false;

    if ( $valueArray['delete'] == true ) {
        console.log("User requested we delete a check-in item.");
        $delete_action = true;
    }

    jQuery.ajax({
        url: e20r_tracker.ajaxurl,
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
        url: e20r_tracker.ajaxurl,
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

        $action = 'e20r_measurementDataForUser';
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
        error: function (data) {
            console.dir(data);
            // alert(data);

        },
        success: function (data) {

            // Refresh the sequence post list (include the new post.
            if ( ( data.data !== '' ) && ( $type == 'info') ) {
                jQuery('#e20r-info').html(data.data);
            }

            if ( ( data.data !== '' ) && ( $type == 'measurements') ) {

                jQuery('#e20r-admin-measurements').html(data.data);

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
                    alert("This user has no available data at this time");
                }
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

function loadCheckinItem( $itemId ) {

    jQuery.ajax({
        url: e20r_tracker.ajaxurl,
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

/****** Manage the meta boxes for the Post(s) ******/
function e20rPgm_rowVisibility ($element, $show ) {

    var $selectLabelRow = jQuery($element).parent().parent().prev();
    var $selectRow = jQuery($element).parent().parent();

    //var $delayLabelRow = jQuery($element).parent().parent().next();
    // var $delayRow = jQuery($selectRow).nextAll('.delay-row-input.new-e20rprogram:first');

    if ( $show == 'all') {

        jQuery($selectLabelRow).show();
        jQuery($selectRow).show();
        // jQuery($delayRow).show();
    }
    else if (  $show == 'none') {

        jQuery($selectLabelRow).hide();
        jQuery($selectRow).hide();
        // jQuery($delayRow).hide();
    }
    else if ( $show == 'select' ) {

        jQuery($selectLabelRow).show();
        jQuery($selectRow).show();
        // jQuery($delayRow).hide();
    }
}

function e20rPgm_showMetaControls() {

    var $count = 0;

    jQuery( 'select.e20r-tracker-memberof-programs').each( function() {

        e20rPgm_rowVisibility( this, 'all' );
        $count++;
    });

    console.log('Number of selects with defined entries: ' + $count);

    // Check if there's more than one select box in metabox. If so, the post already belongs to sequences
    if ( $count >= 1 ) {

        // Hide the 'new sequence' select and show the 'new' button.
        e20rPgm_rowVisibility( jQuery( 'select.new-e20rprogram-select') , 'none' );

        jQuery('#e20r-tracker-new').show();
        jQuery('#e20r-tracker-new-meta').show();
        jQuery('#e20r-tracker-new-meta-reset').hide();
    }
    else {

        // Show the row for the 'Not defined' in the New sequence drop-down
        e20rPgm_rowVisibility( jQuery( 'select.new-e20rprogram-select' ), 'select' );

        // Hide all buttons
        jQuery('#e20r-tracker-new').hide();
    }

}
function e20rPgm_lockMetaRows() {

    jQuery( '.e20r-tracker-memberof-programs' ).each( function() {

        jQuery( this ).attr( 'disabled', true );
    });

    jQuery( '.new-e20rprogram-select' ).each( function() {
        jQuery( this).attr( 'disabled', true);
    });

    jQuery( '.e20r-remove-program').each( function() {

        jQuery( this).attr( 'disabled', true );
    });

    jQuery( '#e20r-tracker-new-meta' ).attr( 'disabled', true );
    jQuery( '#e20r-tracker-new-meta-reset' ).attr( 'disabled', true );
}

function e20rPgm_unlockMetaRows() {

    jQuery( '.e20r-tracker-memberof-programs' ).each( function() {

        jQuery( this ).attr( 'disabled', false );
    });

    jQuery( '.new-e20rprogram-select' ).each( function() {
        jQuery( this ).attr( 'disabled', false );
    });

    jQuery( '.e20r-remove-program').each( function() {

        jQuery( this ).attr( 'disabled', false );
    });

    jQuery( '#e20r-tracker-new-meta' ).attr( 'disabled', false );
    jQuery( '#e20r-tracker-new-meta-reset' ).attr( 'disabled', false );

};
function e20rPgm_showAddNew() {

    jQuery('.new-program-select-label').show();
    jQuery('.new-e20rprogram-select').show();

    jQuery('#e20r-tracker-new').hide();

}

function e20rPgm_hideAddNew() {

    jQuery('.new-program-select-label').hide();
    jQuery('.new-e20rprogram-select').hide();

    // var $selectInp = jQuery('#pmpro-seq-metatable tbody>tr:last').prev('tr').prev('tr');
    // var $selectLabel = jQuery($selectInp).prev('tr');

    // $selectInp.slideToggle();
    // $selectLabel.slideToggle();
    var $addNew = jQuery(".e20r-select-row-input").length;

    console.log('defined programs (number): ' + $addNew );

    if ( $addNew > 1 ) {
        jQuery('#e20r-tracker-new').show();
        jQuery('#e20r-tracker-new-meta-reset').hide();
    }
}
function e20rPgm_postMetaSelectChanged( $self ) {

    var $spinner = jQuery('#e20r-postmeta-setprogram').find('e20r_spinner');

    e20rPgm_lockMetaRows();

    console.log("Changed the Program that this post is a member of");
    $spinner.show();

    var $program_id = jQuery( $self ).val();
    var $oldId = jQuery($self).next('.e20r-program-oldval');

    if (( ! $program_id ) && (! $oldId.val()) ) {
        console.log("The empty program row");
        // return;
    }

    console.log("Program ID: " + $program_id );
    console.log("Old program ID: " + $oldId.val() );
    // Disable delay and sequence input.
/*
    jQuery.ajax({
        url: e20r_tracker.ajaxurl,
        type:'POST',
        timeout:10000,
        dataType: 'JSON',
        data: {
            action: 'e20r_add_program',
            'e20r-program-id': $program_id,
            'e20r-old-program-id': $oldId.val();
            'e20r-tracker-program-nonce': jQuery('#e20r-tracker-program-nonce').val(),
            'post-id': jQuery('#post_ID').val()
        },
        error: function($data){
            console.log("error() - Returned data: " + $data.success + " and " + $data.data);
            console.dir($data);

            if ( $data.data ) {
                alert($data.data);
            }
        },
        success: function($data){
            console.log("success() - Returned data: " + $data.success);

            if ($data.data) {

                console.log('Program ID added to post & refreshing metabox content');
                jQuery('#e20r-postmeta-setprogram').html($data.data);
                console.log("Loaded program meta info.");
            } else {
                console.log('No HTML returned???');
            }

        },
        complete: function($data) {
            $spinner.hide();
            console.log("Ajax function complete...");
            e20rPgm_showMetaControls();
            e20rPgm_unlockMetaRows();
            jQuery( '#e20r-tracker-new').hide();
        }
    });
    */
    $spinner.hide();
    console.log("Ajax function complete...");
    e20rPgm_showMetaControls();
    e20rPgm_unlockMetaRows();
    jQuery( '#e20r-tracker-new').hide();

}