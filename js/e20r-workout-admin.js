/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var $body = jQuery("body");

jQuery(document).on({
    ajaxStart: function() { $body.addClass("loading");   },
    ajaxStop: function() { $body.removeClass("loading"); }
});

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

        return self;
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

        jQuery('select#e20r-workout-days').select2({
            placeholder: "When to do this workout",
            allowClear: true
        });

        jQuery('#e20r-workout-program_ids').select2({
            placeholder: "Select user's program",
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

        self.removeBtns = self.activityForm.find('.e20r-remove-group.button');

        self.removeBtns.each(function(){

            var group = jQuery(this).closest('.e20r-exercise-group');
            var groupNo = group.find('input[type="hidden"][name^="e20r-group-id"]').val();
            console.log("Adding on-click event for button in group: ", groupNo );

            jQuery(this).unbind('click').on('click', function() {
                self.removeActivityGroup( groupNo, group );
            });
        });
    },
    addNewActivityGroup: function( self ) {

        event.preventDefault();

        var grouping = self.activityForm.find('.e20r-exercise-group:last');
        var currentGroupNo = parseInt( grouping.find('.e20r-group-id').val() ) + 1; // Increment for next group

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
                console.log("Error String: " + $errString + " and errogrouprType: " + $errType);
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

            alert( e20r_tracker.lang.no_ex_entry );
            return false;
        }


        // Disable save button
        $addBtn.attr('disabled', 'disabled');
        $addBtn.html(e20r_tracker.lang.adding);

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

                if ( ( $exerciseId != 0 ) && ( $exOrder == '' ) ) {
                    console.log("No order - key - specified. Setting it to 1");
                    $exOrder = 1;
                }

                console.log("'Empty' string present in list? " + $rowCount);

                if (resp) {

                    console.log('Entry added to workout & refreshing metabox content', resp);

                    // var $newRow = "<tr>";
                    var $newRow = me._createExRow( resp, $exOrder, groupIdElem.val() );
                    // $newRow += '</tr>';

                    var $last_row = $exerciseList.find('tr:last');
                    var table = $addBtn.closest('div.postcustomstuff').siblings('table.e20r-exercise-list');

                    if ($rowCount == 1) {

                        console.log("Empty list text found. Replacing with exercise.");
                        $last_row.html($newRow);
                    }
                    else {

                        if (  me._exists( table, 0, $exOrder ) == false ) {

                            console.log("Adding new exercise to list of exercises");
                            $last_row.after('<tr>' + $newRow + '</tr>');
                        }
                        else {
                            console.log("Have to replace existing row in table");

                            var count = 1;

                            var $tbl_row = table.find('tbody tr').filter( function () {
                                return jQuery.trim( jQuery(this).find('td').eq(0).text() ) === $exOrder
                            });

                            console.log("Row containing exercise #" + $exOrder + ": ", $tbl_row );

                            // Replace row data.
                            var new_row = me._createExRow( resp, $exOrder, groupIdElem.val() );
                            $tbl_row.html( new_row );
                        }
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
                $addBtn.html(e20r_tracker.lang.add);
                $addBtn.removeAttr('disabled');

            }
        });

    },
    removeActivityGroup: function( id, group  ) {

        console.log("Removing Group_id: " + id );
        group.detach();

        // Renumber the list of Groups
        var groupNum = 0;
        var groupList = jQuery('div#e20r-workout-add-groups').find('.e20r-exercise-group');
        var groups = groupList.find('.e20r-group-header');

        groups.each(function(){

            jQuery(this).find('.e20r-group-id').val( groupNum );
            jQuery(this).find('.group-id').text( groupNum + 1 );
            jQuery(this).siblings('td').find('.group-id').text( groupNum + 1 );

            // Process every row of the exercise list for the current group id.
            jQuery(this).closest('.e20r-exercise-group').find('.e20r-exercise-list tbody tr').each(function(){

                groupInfo = '\'group:' + groupNum + '\'';
                row = jQuery(this);

                ex_id = row.find('.e20r-workout-group_exercise_id').val();
                ex_order = row.find('.e20r-workout-group_exercise_order').val();

                if ( typeof(ex_id) != 'undefined') {

                    console.log('this: ', row);
                    console.log("ex_id:" + ex_id);
                    console.log("ex_order: " + ex_order );

                    var edit_lnk = 'javascript:e20rActivity.editExercise(' + groupInfo + ', ' + ex_id + ', ' + ex_order + ');';
                    var remove_lnk = 'javascript:e20rActivity.removeExercise(' + groupInfo + ', ' + ex_id + ', ' + ex_order + ');';

                    jQuery(this).find('.e20r-workout-group').val( groupNum );
                    jQuery(this).find('a.e20r-exercise-edit').attr('href', edit_lnk );
                    jQuery(this).find('a.e20r-exercise-remove').attr('href', edit_lnk );
                }
            });

            groupNum++;
        });
    },
    editExercise: function( groupStr, exId, order ) {

        var arr = groupStr.split(':');
        var groupId = arr[1];

        console.log("Editing exercise # " + exId + ' in group #' + groupId + ' with order #' + order );

        var group = jQuery('#e20r-workout-add-groups').find('input[type="hidden"][name^="e20r-group-id"][value="' + groupId + '"]').closest('.e20r-exercise-group');
        var row = group.find('input[type="hidden"][name^="e20r-workout-group_exercise_order"][value="' + order + '"]').closest('tr');

        // Put the order Id and exerciseId
        var curr_order = row.find('input[type="hidden"][name^="e20r-workout-group_exercise_order"]').val();
        var curr_ex = row.find('input[type="hidden"][name^="e20r-workout-group_exercise_id"]').val();

        console.log("Found exercise # " + curr_ex + ' with order ' + curr_order );

        group.find('.e20r-workout-add-exercise-key').val( curr_order );
        group.find('.e20r-workout-add-exercise-id').select2( 'val', curr_ex );
        group.find('e20r-workout-add-exercise-save').html(e20r_tracker.lang.save);
    },
    removeExercise: function( groupStr, exId, order ) {

        var arr = groupStr.split(':');
        var groupId = arr[1];

        console.log("Removing exercise # " + exId + ' in group #' + groupId + ' with order #' + order );

        var group = jQuery('#e20r-workout-add-groups').find('input[type="hidden"][name^="e20r-group-id"][value="' + groupId + '"]').closest('.e20r-exercise-group');
        var exRow = group.find('input[type="hidden"][name^="e20r-workout-group_exercise_order"][value="' + order + '"]').closest('tr');

        // var exRow = me._findExerciseRow( group, order );
        exRow.detach();

        var remaining = exRow.closest('table.e20r-exercise-list tbody tr');

        if ( remaining == 0 ) {
            // Empty table! Add the default message.
            console.log("No rows in table. Adding the 'no data found' message");
            exRow.after('<tr><td colspan="8">' + e20r_tracker.lang.no_exercises + '</td></tr>');
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

        if ( table.find('td').eq(field).text() == value ) {
            console.log("Field #: " + field + " contains the value: " + value );
            return true;
        }

        return false;
    },
    _findExerciseRow: function( groupId, orderNo ) {

        var group = jQuery('#e20r-workout-add-groups').find('input[type="hidden"][name^="e20r-group-id"][value="' + groupId + '"]').closest('.e20r-exercise-group');
        var row = group.find('input[type="hidden"][name^="e20r-workout-group_exercise_order"][value="' + orderNo + '"]').closest('tr');

        return row;
    },
    _createExRow: function( obj, order, group ) {

        var $row;
        var groupInfo = '\'group:' + group + '\'';


        $row = '<td class="exercise-order" style="width: 15px;">' + order + '</td>';
        $row += '<td colspan="2">' + obj.title + '  ( ' + obj.shortcode + ' )</td>';
        $row += "<td>" + ( obj.type != null ? obj.type : e20r_tracker.lang.none ) + "</td>";
        $row += "<td>" + ( obj.reps != null ? obj.reps : e20r_tracker.lang.none ) + "</td>";
        $row += "<td>" + ( obj.rest != null ? obj.rest : e20r_tracker.lang.none );
        $row += '<input type="hidden" class="e20r-workout-group_exercise_id" name="e20r-workout-group_exercise_id[]" value="' + obj.id + '" >';
        $row += '<input type="hidden" class="e20r-workout-group_exercise_order" name="e20r-workout-group_exercise_order[]" value="' + order + '" >';
        $row += '<input type="hidden" class="e20r-workout-group" name="e20r-workout-group[]" value="' + group + '" ></td>';
        $row += '<td><a href="javascript:e20rActivity.editExercise(' + groupInfo + ', ' + obj.id + ', ' + order + ')" class="e20r-exercise-edit">' + e20r_tracker.lang.edit + '</a></td>';
        $row += '<td><a href="javascript:e20rActivity.removeExercise(' + groupInfo + ', ' + obj.id + ', ' + order + ')" class="e20r-exercise-remove">' + e20r_tracker.lang.remove + '</a></td>';

        return $row;
    }
}

jQuery(document).ready( function(){

    console.log("Loaded admin specific scripts for the e20r_workout CPT");
    e20rActivity.init();

});
