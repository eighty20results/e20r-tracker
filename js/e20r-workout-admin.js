/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var $e20rActivity = {
    init: function() {

        this.activityForm = jQuery('.e20r-editform');
        this.$addExerciseBtns = this.activityForm.find('.e20r-workout-add-exercise-save');
        this.$addGroupBtn = this.activityForm.find('#e20r-new-group-button');
        this.lastGroupHeader = this.activityForm.find('table > tbody > tr.e20r-workout-exercise-group-header:last');
        this.lastGroupData = this.activityForm.find('table > tbody > tr.e20r-workout-exercise-group-data:last');

        var self = this;

        jQuery('#e20r-workout-id').select2();

        jQuery('.e20r-workout-add-exercise-id').select2({
            placeholder: "Select Exercise",
            allowClear: true
        });

        self.$addExerciseBtns.each( function(){

            console.log(this);

            jQuery(this).on('click', function(){

                console.log("Adding new exercise to the current group");
                self.addNewExerciseToGroup( this, self );
            });
        });

        self.$addGroupBtn.on('click', function() {

            console.log("Adding new activity/workout group");

            self.addNewActivityGroup();

            // Reload the settings for the new elements.
            self.lastGroupHeader = this.activityForm.find('table > tbody > tr.e20r-workout-exercise-group-header:last');
            self.lastGroupData = this.activityForm.find('table > tbody > tr.e20r-workout-exercise-group-data:last');
            self.$addExerciseBtns = this.activityForm.find('.e20r-workout-add-exercise-save');

            self.$addExerciseBtns.each(function(){

                jQuery(this).on('click', function(){

                    console.log("Adding new exercise to the current group: ", this);
                    self.addNewExerciseToGroup( self );
                });
            });

        });

        console.log("Loaded Activity class");
    },
    addNewActivityGroup: function() {

        event.preventDefault();

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

    },
    addNewExerciseToGroup: function( self, me ) {

        event.preventDefault();
        console.log("Add exercise to activity" );

        var $addBtn = jQuery(self);

        var $exerciseAdd = $addBtn.parent().parent().find('.e20r-workout-add-exercise-id');
        console.log("Exercise Add: ", $exerciseAdd );

        var $exerciseId = $exerciseAdd.find("option:selected").val();
        var $exOrder = jQuery('.e20r-workout-add-exercise-key').val();

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
        $addBtn.attr('disabled', 'disabled');
        $addBtn.html(e20r_tracker.lang.saving);

        var groupIdElem = $addBtn.closest('.e20r-workout-exercise-group-data').siblings('.e20r-workout-exercise-group-header').find('.e20r-group-id');

        console.log("Find group id: ", groupIdElem.val());
        console.log("Exercise ID: ", $exerciseId);
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
                'e20r-exercise-id': $exerciseId
            },
            success: function( resp ){
                console.log("success() - Returned data: ", resp );

                var $exerciseList = $addBtn.closest('.e20r-list-exercises-for-group').find('table.e20r-exercise-list');
                var $rowCount = $exerciseList.find("tbody > tr > td:contains('" + e20r_tracker.lang.empty + "')").length;

                console.log("Number of exercises in list: " + $rowCount  );

                if (resp) {

                    console.log('Entry added to workout & refreshing metabox content', resp );

                    // var $newRow = "<tr>";
                    var $newRow = '<td colspan="2">' + resp.title + '  ( ' + resp.shortcode + ' )</td>';
                    $newRow += "<td>" + ( resp.type != null ? resp.type : e20r_tracker.lang.none ) + "</td>";
                    $newRow += "<td>" + ( resp.reps != null ? resp.reps : e20r_tracker.lang.none ) + "</td>";
                    $newRow += "<td>" + ( resp.rest != null ? resp.rest : e20r_tracker.lang.none ) + "</td>";
                    $newRow += '<td><a href="javascript:" class="e20r-exercise-edit">Edit</a></td>';
                    $newRow += '<td><a href="javascript:" class="e20r-exercise-remove">Remove</a>';
                    $newRow += '<input type="hidden" class="e20r-workout-group_exercise_id" name="e20r-workout-group_exercise_id[]" value="' + resp.id + '" >';
                    $newRow += '<input type="hidden" class="e20r-workout-group_id" name="e20r-workout-group[]" value="' + groupIdElem + '" ></td>';
                    // $newRow += '</tr>';

                    var $row = $exerciseList.find('tr:last');

                    console.log("Last TR row:", $row );

                    if ( $rowCount == 1 ) {
                        console.log("replacing dummy text..");
                        $row.html( $newRow );
                    }
                    else {
                        console.log("Adding new exercise to list of exercises");
                        $row.after( '<tr>' + $newRow + '</tr>' );
                    }

                } else {
                    console.log('No exercise data found?');
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
                $addBtn.html('Add');
                $addBtn.removeAttr('disabled');

            }
        });

    },
    removeActivityGroup: function( self, id ) {
        return false;
    }
}

jQuery(document).ready( function(){

    console.log("Loaded admin specific scripts for the e20r_workout CPT");
    $e20rActivity.init();

});
