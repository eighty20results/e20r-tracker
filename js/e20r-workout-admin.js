/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var e20rActivity = {
    init: function() {

        this.activityForm = jQuery('e20r-editform');
        this.$saveBtn = this.activityForm.find('#e20r-workout-add-exercise-save');
        this.lastGroupHeader = this.activityForm.find('table > tbody > tr.e20r-workout-exercise-group-header:last');
        this.lastGroupData = this.activityForm.find('table > tbody > tr.e20r-workout-exercise-group-data:last');


        var self = this;
    },
    addNewActivityGroup: function() {

    },
    addNewExerciseToGroup: function( self ) {

        console.log("Add exercise to activity" );

        if ('' == self.find('#e20r-assignments-id').val() || undefined != self.$saveBtn.attr('disabled'))
            return false; //already processing, ignore this request

        var $exerciseId = jQuery('#e20r-workout-add-exercise-id').find("option:selected").val();
        var $exOrder = jQuery('#e20r-workout-add-exercise-key').val();

        if ( $exerciseId == 0 ) {

            var $alertMsg = e20r_tracker.lang.no_entry + ' an exercise';
            alert( $alertMsg );

            return false;
        }

        if ( ( $exerciseId != 0 ) && ( $exOrder == '' ) ) {
            console.log("No order - key - specified. Setting it to 1");
            $exOrder = 1;
        }

        // Disable save button
        self.$saveBtn.attr('disabled', 'disabled');
        self.$saveBtn.html(e20r_tracker.lang.saving);

        var groupIdElem = saveBtn.closest('.e20r-workout-exercise-group-data').siblings('.e20r-workout-exercise-group-header').find('.e20r-group-id');

        console.log("Find group id: ", groupIdElem.val());

        var resp = null;

        //pass field values to AJAX service and refresh table above - Timeout is 5 seconds
        wp.ajax.send({
            url: e20r_tracker.ajaxurl,
            type:'POST',
            timeout:5000,
            dataType: 'JSON',
            data: {
                action: 'e20r_add_exercise',
                'e20r-tracker-workout-settings-nonce': jQuery('#e20r-tracker-workout-settings-nonce').val(),
                'e20r-exercise-group-id': groupIdElem.val(),
                'e20r-exercise-id': $exerciseId,
                'e20r-workout-add-exercise-key': $exOrder,
                'e20r-workout-id': jQuery('#post_ID').val()
            },
            success: function( resp ){
                // console.log("success() - Returned data: ", resp );

                if (resp) {
                    console.log('Entry added to workout & refreshing metabox content');
                    jQuery('#e20r-list-exercises-for-group').html(resp);
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
                self.$saveBtn.html(e20r_tracker.lang.save);
                self.$saveBtn.removeAttr('disabled');

            }
        });

        return false;
    },
    removeActivityGroup: function( self, id ) {

    }
}
jQuery(document).ready( function(){

    console.log("Loaded admin specific scripts for the e20r_workout CPT");

    jQuery('#e20r-workout-id').select2();

    jQuery('.e20r-workout-add-exercise-id').select2({
        placeholder: "Select Exercise",
        allowClear: true
    });

    jQuery("#e20r-workout-add-exercise-save").on('click', function(){

        event.preventDefault();
        e20r_save_exercise();
    });

    jQuery("#e20r-new-group-button").on('click', function() {

        event.preventDefault();
        var tr_header = jQuery(".e20r-workout-exercise-group-header:last");
        var tr_data = jQuery(".e20r-workout-exercise-group-data:last");

        wp.ajax.send({
            url: e20r_tracker.ajaxurl,
            type:'POST',
            timeout:5000,
            dataType: 'JSON',
            data: {
                action: "e20r_add_new_exercise_group",
                'e20r-tracker-workout-settings-nonce': jQuery('#e20r-tracker-workout-settings-nonce').val(),
                'e20r-workout-assigned_user_id': jQuery('#e20r-workout-assigned_user_id').find("option:selected").val(),
                'e20r-workout-assigned_usergroups': jQuery('#e20r-workout-assigned_usergroups').find("option:selected").val(),
                'e20r-workout-phase': jQuery('#e20r-workout-phase').val(),
                'e20r-workout-workout_ident': jQuery('#e20r-workout-workout_ident').find("option:selected").val(),
                'e20r-workout-startdate': jQuery('#e20r-workout-startdate').val(),
                'e20r-workout-enddate': jQuery('#e20r-workout-startdate').val(),
                'e20r-workout-group-id': jQuery('input[name^="e20r-workout-group-id"]').serializeArray(),
                'e20r-workout-group_set_count': jQuery('input[name^="e20r-workout-group_set_count"]').serializeArray(),
                'e20r-workout-group_rest': jQuery('input[name^="e20r-workout-group_rest"]').serializeArray(),
                'e20r-workout-group_tempo': jQuery('input[name^="e20r-workout-group_tempo"]').serializeArray(),
                'e20r-workout-group_exercise_id': jQuery('input[name^="e20r-workout-group_exercise_id"]').serializeArray()
            },
            success: function( resp ) {

                if (resp.success ) {
                    console.log('Entry workout saved. Now refreshing group list.');
                    // jQuery('#e20r-workout-add-groups').html(resp);
                }
            },
            error: function(jqxhr, $errString, $errType){
                // console.log("error() - Returned data: ", jqxhr );
                console.log("Error String: " + $errString + " and errorType: " + $errType);
            }
        });

        return false;
    });
});

function e20r_save_exercise() {


}
