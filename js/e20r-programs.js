/**
 * Created by sjolshag on 1/4/15.
 */

jQuery(function() {

    var programEdit = {
        init: function() {

            this.$selectProgram = jQuery('select.new-e20rprogram-select');
            this.$selectLabelRow = jQuery(this.$SelectProgram).parent().parent().prev();
            this.$selectRow = jQuery(this.$SelectProgram).parent().parent();
            this.$spinner = jQuery('#e20r-postmeta-setprogram').find('e20r_spinner');
            this.programMBox = jQuery('#e20r-program-list');
            this.$programList = jQuery('.e20r-tracker-memberof-programs');
            this.$removeProgramBtn = jQuery('.e20r-remove-program');
            this.$newProgramRow = jQuery('tr#e20r-tracker-new');
            this.$newProgramBtn = jQuery('#e20r-tracker-new-meta');
            this.$clearMetaBtn = jQuery('#e20r-tracker-new-meta-reset');

        },
        visibility: function( level ) {

            if ( ( level == 'all') || ( level == 'select' ) ) {

                jQuery(this.$selectLabelRow).show();
                jQuery(this.$selectRow).show();
            }
            else {

                jQuery(this.$selectLabelRow).hide();
                jQuery(this.$selectRow).hide();
            }
        },
        lockRow: function( state ) {

            var $count = 0;

            this.$programList.each( function() {

                this.visibility( 'all' );
                $count++;
            });

            console.log('Number of selects with defined entries: ' + $count);

            // Check if there's more than one select box in metabox. If so, the post already belongs to sequences
            if ( $count >= 1 ) {

                // Hide the 'new sequence' select and show the 'new' button.
                this.visibility( 'none' );

                this.$newProgramRow.show();
                this.$newProgramBtn.show();
                this.$clearMetaBtn.hide();
            }
            else {

                // Show the row for the 'Not defined' in the New sequence drop-down
                this.visibility( 'select' );

                // Hide all buttons
                this.$newProgramRow.hide();
            }
        },
        lockMeta: function( state ) {

            this.$programList.each( function() {
                jQuery(this).attr( 'disabled', state );
            });

            this.$selectProgram.each( function() {
                jQuery(this).attr( 'disabled', state);
            });

            this.$removeProgramBtn.each( function() {
                jQuery( this).attr( 'disabled', state );
            });

            this.$newProgramBtn.attr( 'disabled', state );
            this.$clearMetaBtn.attr( 'disabled', state );
        },
        save: function( self, $programInfo ) {

            var $delete_action = false;

            if ( $programInfo['delete'] == true ) {
                $delete_action = true;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 10000,
                dataType: 'JSON',
                data: {
                    action: 'save_program_info',
                    e20r_tracker_edit_programs_nonce: jQuery('#e20r_tracker_edit_programs').val(),
                    e20r_program_id: $programInfo['id'],
                    e20r_program_name: $programInfo['name'],
                    e20r_program_start: $programInfo['start'],
                    e20r_program_end: $programInfo['end'],
                    e20r_program_descr: $programInfo['descr'],
                    e20r_program_memberships: $programInfo['membership_id'],
                    e20r_program_delete: $delete_action
                },
                error: function( $response, $errString, $errType ) {

                    console.log("From server: ", $response );
                    console.log("Error String: " + $errString + " and errorType: " + $errType);

                    var $msg = '';

                    if ( 'timeout' === $errString ) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to save this data. If you\'d like to try again, please ";
                    $string += "click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert( $msg + $string );

                    return;
                },
                success: function (data) {

                    // Refresh the sequence post list (include the new post.
                    if ( data.data !== '' ) {
                        self.$programMBox.html(data.data);
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
        },
        postMetaChanged: function( handler ) {

            this.lockMeta( true );

            console.log("Changed the Program that this post is a member of");
            self.$spinner.show();

            var $program_id = jQuery( self ).val();
            var $oldId = jQuery( self ).next('.e20r-program-oldval');

            if (( ! $program_id ) && (! $oldId.val()) ) {
                console.log("The empty program row");
                // return;
            }

            console.log("Program ID: " + $program_id );
            console.log("Old program ID: " + $oldId.val() );

            // Disable delay and sequence input.
            /*            jQuery.ajax({
             url: ajaxurl,
             type:'POST',
             timeout:10000,
             dataType: 'JSON',
             data: {
             action: 'e20r_add_program',
             'e20r-program-id': $program_id,
             'e20r-old-program-id': $oldId.val(),
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

             }); */

            this.$spinner.hide();
            e20rPgm_showMetaControls();
            this.lockMeta( false );
            this.$newProgramRow.hide();

        },
        rowVisibility: function( handler ) {
            this.call( handler )
        }
    };

    var $programEditor = construct( programEdit );

    $programEditor.init();
});

