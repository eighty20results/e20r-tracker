/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var e20rAssignments = {
    init: function () {

        jQuery('select#e20r-assignment-field_type').select2();

        this.editForm = jQuery('div#e20r-editform');
        this.$field_type = this.editForm.find('#e20r-assignment-field_type');
        this.$nonce = this.editForm.find('#e20r-tracker-assignment-settings-nonce').val();
        this.optionSeparator = this.editForm.find('hr.e20r-assignment-separator');
        this.optionsForm = this.editForm.find('table#e20r-assignment-options');
        this.$saveBtn = this.optionsForm.find('button#e20r-add-assignment-save-option');
        this.$newBtn = this.optionsForm.find('button#e20r-add-assignment-new-option');
        this.$deleteBtn = this.optionsForm.find('button#e20r-add-assignment-delete-option');
        this.$checkboxes = this.optionsForm.find('input.e20r-checked-assignment-option');
        this.$checked_all = this.optionsForm.find('input#e20r-select_option-check_all');

        var self = this;

        self._showOptions( self );
        self.bindButtons(self);

        console.log("Loaded Assignment Management class");

        return self;
    },
    reinit: function() {

        this.$checkboxes = this.optionsForm.find('input.e20r-checked-assignment-option');
        this.$checked_all = this.optionsForm.find('input#e20r-select_option-check_all');

        this.bindButtons( this );
    },
    bindButtons: function (self) {

        self.$checked_all.unbind('click').on('click', function() {

            console.log("Admin clicked the 'check-all' button");

            // Add all of the
            self.$checkboxes.each(function() {

                console.log("Updating checkbox status for: ", this );
                jQuery(this).prop('checked', true );
            });

        });

        self.$saveBtn.unbind('click').on('click', function(){

            console.log("Admin clicked the 'Save' button for the new option.");

            // self.sendToBackend( 'save' );
            self.updateOptionlist( 'save' );
        });

        self.$deleteBtn.unbind('click').on('click', function() {

            console.log("Admin clicked the 'delete' button");

            // self.sendToBackend( 'delete' );
            self.updateOptionlist( 'delete' );
        });

        self.$newBtn.unbind('click').on('click', function() {

            console.log("Admin clicked the 'Add' button");
            // self.sendToBackend( 'save' );
            self.updateOptionlist( 'save' );
        });

        self.editForm.find('select#e20r-assignment-field_type').unbind('change').bind('change', function() {

            console.log("Admin is changing the field type");
            self._showOptions( self );
        });
    },
    _showOptions: function( self ) {

        if ( 4 == self.$field_type.val() ) {

            self.optionSeparator.toggle();
            self.optionsForm.toggle();
        }
        else {
            self.optionSeparator.hide();
            self.optionsForm.hide();
        }
    },
    updateOptionlist: function( $operation ) {

        event.preventDefault();

        var $new_option = jQuery('input#e20r-new-assignment-option');
        var $cnt =  jQuery('tr.assignment-select-options').length;
        var $new_row = '';

        console.log( " Row counter: " + $cnt);

        var $option_text = $new_option.val().trim();

        if ( 'save' == $operation ) {

            if ( '' == $option_text ) {
                alert("Warning: No text found");
                return;
            }

            console.log("'Saving' a new option row for the select options.");
            $new_row = '<tr class="assignment-select-options">\n';
            $new_row += '   <td class="e20r-row-counter checkbox"><input type="checkbox" class="e20r-checked-assignment-option" name="e20r-add-option-checkbox[]" value="' + $cnt +'"></td>\n';
            $new_row += '   <td colspan="2" class="e20r-row-value">\n';
            $new_row += '       <input type="hidden" name="e20r-option_key[]" id="e20r-option-key" value="' + $cnt + '">\n';
            $new_row += '       <input type="hidden" name="e20r-assignment-select_options[]" id="e20r-assignment-select_option_' + $cnt +'" value="' + $option_text + '" >';
            $new_row += '       ' + $option_text;
            $new_row += '   </td>\n';
            $new_row +='</tr>\n';
            // $cnt++;

            this.optionsForm.find('tbody').append( $new_row );

            // Reset the option input field.
            $new_option.val('');

            this.reinit();
        }

        if ( 'delete' == $operation ) {

            console.log("Admin requested that an option gets deleted.");

            // Re-init the array.
            this.reinit();

            // Add all of the
            this.$checkboxes.each(function() {

                var $checkbox = jQuery(this);

                if ( $checkbox.is(':checked') ) {
                    console.log(" Has been checked: ", $checkbox );
                    $checkbox.closest('tr.assignment-select-options').remove();
                }
            });

            this.reinit();

            if ( ! this.$checkboxes.length ) {

                $new_row = '<tr class="assignment-select-options">\n';
                $new_row += '   <td class="e20r-row-counter checkbox"></td>\n';
                $new_row += '   <td colspan="2" class="e20r-row-value">\n';
                $new_row += '       <input type="hidden" name="e20r-option_key[]" id="e20r-option-key" value>\n';
                $new_row += '       <input type="hidden" name="e20r-assignment-select_options[]" id="e20r-assignment-select_option_0" value>';
                $new_row += 'No options found';
                $new_row += '   </td>\n';
                $new_row +='</tr>\n';

                this.optionsForm.find('tbody').append( $new_row );
            }

            this.reinit();
        }


        return false;
    },
    sendToBackend: function( $operation ) {

        event.preventDefault();

        // Set nonce value and operation for the AJAX action
        var $serialized_data = "action=e20r_manage_option_list&e20r-assignment-question_id=" + jQuery("#post_ID").val() + "&";
        $serialized_data += "operation=" + $operation + "&";
        $serialized_data += jQuery("form#post").find('input[id^="e20r-"],select[id^="e20r-"],input[class^="e20r-"],select[class^="e20r-"]').serialize();

        var $title = jQuery('input#post_title');
        var $save = jQuery('input#save-post');

        console.log("Serialize data: ", $serialized_data );

        // Check if this is a brand new post (assignment).
        if ( $save.length ) {

            // Make sure we actually ought to save it.
            console.log("This is a brand new post so we'll need to save a draft of it first.");

            if ( ( $title.val() == '' ) && ( 1 == this.$field_type.val() )) {
                alert("Saving an empty assignment probably doesn't make sense, right?");
                return;
            }

            if ( $title.val() == '' ) {
                alert("You should probably name this assignment.");
                return;
            }

            // Let's save it as a draft.
            $save.click();
            return;
        }

        console.log("No 'Save Draft' Button found. Assignment is already saved!");

        // Transmit to the back-end so we can update the option(s).
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: e20r_tracker.timeout,
            dataType: 'JSON',
            data: $serialized_data,
            success: function ( $response ) {

                console.log("Successfully completed " + $operation + ' operation');

                if ( '' != $response.data.html ) {
                    jQuery("div#e20r-assignment-option-div").html( $response.data.html );
                }
            },
            error: function( $response, $errString, $errType ) {
                console.log("Error while running " + $operation + ' operation: ' + $errString + " " + $errType );
                console.log($response);
            },
            complete: function() {

            }
        });

        return false;
    }
};

jQuery(document).ready( function(){

    console.log("Loaded admin specific scripts for the e20r_assignments CPT");
    e20rAssignments.init();

});
