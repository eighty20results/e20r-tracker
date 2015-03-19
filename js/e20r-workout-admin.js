/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var e20rActivity = {
    init: function() {

        this.activityForm = jQuery('.e20r-editform');
        this.$addExerciseBtns = this.activityForm.find('.e20r-workout-add-exercise-save');
        this.$addGroupBtn = this.activityForm.find('#e20r-new-group-button');
        this.lastGroupHeader = this.activityForm.find('tr.e20r-workout-exercise-group-header:last');
        this.lastGroupData = this.activityForm.find('tr.e20r-workout-exercise-group-data:last');

        var self = this;
        self.bindButtons( self );

        self.$addGroupBtn.unbind('click').on('click', function() {

            console.log("Adding new activity/workout group");
            self.addNewActivityGroup( self );
        });

        console.log("Loaded Activity class");
    },
    bindButtons: function( self ) {

        jQuery('select#e20r-workout-assigned_usergroups').select2({
            placeholder: "Select group(s)",
            allowClear: true
        });

        jQuery('select.e20r-workout-add-exercise-id').select2({
            placeholder: "Select Exercise",
            allowClear: true
        });

        jQuery('select#e20r-workout-assigned_user_id').select2({
            placeholder: "Select User",
            allowClear: true
        });

        jQuery('select.e20r-workout-groups-group_tempo').select2({
            placeholder: "Select tempo",
            allowClear: true
        });

        // Reload the settings for the new elements.
        self.lastGroupHeader = self.activityForm.find('tr.e20r-workout-exercise-group-header:last');
        self.lastGroupData = self.activityForm.find('tr.e20r-workout-exercise-group-data:last');
        self.$addExerciseBtns = self.activityForm.find('.e20r-workout-add-exercise-save');

        self.$addExerciseBtns.each( function(){

            jQuery(this).unbind('click').on('click', function(){

                console.log("Adding new exercise to the current group");
                self.addNewExerciseToGroup( this, self );
            });
        });

    },
    addNewActivityGroup: function( self ) {

        event.preventDefault();

        var grouping = self.activityForm.find('.e20r-exercise-group:last');
        var currentGroupNo = parseInt(grouping.find('.e20r-group-id').val()) + 1; // Increment for next group

        wp.ajax.send({
            url: e20r_tracker.ajaxurl,
            type:'POST',
            timeout:5000,
            dataType: 'JSON',
            data: {
                action: "e20r_add_new_exercise_group",
                'e20r-tracker-workout-settings-nonce': jQuery('#e20r-tracker-workout-settings-nonce').val(),
                'e20r-workout-group-id': currentGroupNo
            },
            success: function( resp ) {

                if (resp) {

                    grouping.after(resp.html);

                    // Go to the last exercise-group table & add new group ID info
                    self.activityForm = jQuery('.e20r-editform');

                    grouping = self.activityForm.find('tr.e20r-exercise-group:last');
                    grouping.find('.e20r-group-id').val( currentGroupNo );
                    grouping.find('.group-id').html( (currentGroupNo + 1) );

                    self.bindButtons( self );
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

        var asc = -1; // Ascending
        var desc = 1; // descending

        var $addBtn = jQuery(self);

        var $exerciseAdd = $addBtn.parent().parent().find('.e20r-workout-add-exercise-id');

        var $exerciseId = $exerciseAdd.find("option:selected").val();
        var $exOrder = $exerciseAdd.parent().parent().find('.e20r-workout-add-exercise-key').val();

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
        var resp = null;

        //pass field values to AJAX service and refresh table above - Timeout is 5 seconds
        wp.ajax.send({
            url: e20r_tracker.ajaxurl,
            type: 'POST',
            timeout: 5000,
            dataType: 'JSON',
            data: {
                action: 'e20r_add_exercise',
                'e20r-tracker-workout-settings-nonce': jQuery('#e20r-tracker-workout-settings-nonce').val(),
                'e20r-exercise-id': $exerciseId
            },
            success: function (resp) {

                var $exerciseList = $addBtn.closest('.e20r-list-exercises-for-group').find('table.e20r-exercise-list');
                var $rowCount = $exerciseList.find("tbody > tr > td:contains('" + e20r_tracker.lang.empty + "')").length;

                console.log("'Empty' string present in list? " + $rowCount);

                /*
                 * TODO: Replace row if the $exOrder exists in the table already
                 * var table = $addBtn.closest('div.postcustomstuff').siblings('table.e20r-exercise-list');
                 *
                 * if ( me._exists( table, 0, $exOrder ) == false ) {
                 *
                 * }
                 */

                if (resp) {

                    console.log('Entry added to workout & refreshing metabox content', resp);

                    // var $newRow = "<tr>";
                    var $newRow = '<td class="exercise-order" style="width: 15px;">' + $exOrder + '</td>';
                    $newRow += '<td colspan="2">' + resp.title + '  ( ' + resp.shortcode + ' )</td>';
                    $newRow += "<td>" + ( resp.type != null ? resp.type : e20r_tracker.lang.none ) + "</td>";
                    $newRow += "<td>" + ( resp.reps != null ? resp.reps : e20r_tracker.lang.none ) + "</td>";
                    $newRow += "<td>" + ( resp.rest != null ? resp.rest : e20r_tracker.lang.none );
                    $newRow += '<input type="hidden" class="e20r-workout-group_exercise_id" name="e20r-workout-group_exercise_id[]" value="' + resp.id + '" >';
                    $newRow += '<input type="hidden" class="e20r-workout-group_exercise_order" name="e20r-workout-group_exercise_order[]" value="' + $exOrder + '" >';
                    $newRow += '<input type="hidden" class="e20r-workout-group" name="e20r-workout-group[]" value="' + groupIdElem.val() + '" ></td>';
                    $newRow += '<td><a href="javascript:e20rActivity.editExercise(' + groupIdElem.val() + ', ' + resp.id + ', ' + $exOrder + ')" class="e20r-exercise-edit">Edit</a></td>';
                    $newRow += '<td><a href="javascript:e20rActivity.removeExercise(' + groupIdElem.val() + ', ' + resp.id + ', ' + $exOrder + ')" class="e20r-exercise-remove">Remove</a></td>';
                    // $newRow += '</tr>';

                    var $row = $exerciseList.find('tr:last');

                    if ($rowCount == 1) {
                        console.log("replacing dummy text..");
                        $row.html($newRow);
                    }
                    else {
                        console.log("Adding new exercise to list of exercises");
                        $row.after('<tr>' + $newRow + '</tr>');
                    }

                } else {
                    console.log('No exercise data found?');
                }

            },
            error: function (jqxhr, $errString, $errType) {
                // console.log("error() - Returned data: ", jqxhr );
                console.log("Error String: " + $errString + " and errorType: " + $errType);
            },
            complete: function (response) {

                $exerciseAdd.parent().parent().find('input.e20r-workout-add-exercise-key').val(null);
                $exerciseAdd.parent().parent().find('.e20r-workout-add-exercise-id').select2('data', null);

                var table = $addBtn.closest('div.postcustomstuff').siblings('table.e20r-exercise-list').find('tbody');

                me._sortTable(table, desc, 0);

                // Re-enable save button
                $addBtn.html('Add');
                $addBtn.removeAttr('disabled');

            }
        });

    },
    removeActivityGroup: function( id, self ) {

        console.log("Removing Group_id: " + id + " with jQuery object:", self );
    },
    editExercise: function( group, exId, order ) {

        console.log("Editing exercise # " + exId + ' in group #' + group + ' with order #' + order );
    },
    removeExercise: function( group, exId, order ) {

        console.log("Removing exercise # " + exId + ' in group #' + group + ' with order #' + order );

        var group = jQuery('#e20r-workout-add-groups').find('input[type="hidden"][name^="e20r-group-id"][value="' + group + '"]').closest('.e20r-exercise-group');
        var exRow = group.find('input[type="hidden"][name^="e20r-workout-group_exercise_order"][value="' + order + '"]').closest('tr');

        var remaining = exRow.closest('table.e20r-exercise-list tbody tr');

        console.log("Exercises in list: ", remaining );

        exRow.detach();

        if ( remaining == 0 ) {
            // Empty table! Add the default message.
            console.log("No rows in table. Adding the 'no data found' message");
        }

        console.log("Rows remaining: ", remaining.size());
    },
    _sortTable: function(table, order, colNo ) {

        var rows = table.find('tr');

        rows.sort( function( a, b ) {

            // get the text of n-th <td> of <tr>
            var A = jQuery(a).children('td').eq(colNo).text().toUpperCase();
            var B = jQuery(b).children('td').eq(colNo).text().toUpperCase();

            console.log('Col val A: ' + A);
            console.log('Col val B: ' + B);

            if ( A < B ) {

                return -1*order;
            }

            if ( A > B ) {

                return 1*order;
            }

            return 0;
        });

        jQuery.each(rows, function(index, row) {

            table.append(row);
        });
    },
    _exists: function( table, field, value ) {

        if ( table.children('td').eq(field).text() == value ) {
            console.log("Field #: " + field + " contains the value: " + value );
            return true;
        }

        return false;
    }
}

jQuery(document).ready( function(){

    console.log("Loaded admin specific scripts for the e20r_workout CPT");
    e20rActivity.init();

});
