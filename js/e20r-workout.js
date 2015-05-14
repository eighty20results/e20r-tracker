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
    init: function () {

        this.$weight_fields = jQuery('.e20r-activity-input-weight');
        this.$rep_fields = jQuery('.e20r-activity-input-reps');
        this.$rows = jQuery(".e20r-exercise-tracking-row");
        this.$nonce = jQuery('#e20r-tracker-activity-input-nonce').val();
        this.$saveBtn = jQuery('#e20r-activity-input-button');
        this.$userId = jQuery('#e20r-activity-input-user_id').val();
        this.$programId = jQuery('#e20r-activity-input-program_id').val();
        this.$activityId = jQuery('#e20r-activity-input-activity_id').val();
        this.$forDate = jQuery('#e20r-activity-input-for_date').val();

        var cls = this;

        cls.$weight_fields.each(function () {
            cls.bindInput( jQuery(this), cls );
        });

        cls.$rep_fields.each(function () {

            cls.bindInput( jQuery(this), cls );
        });

        console.log("Loaded Activity class");

        this.$rows.each( function(){

            console.log("Processing exercise row for show/hide");
            cls.show_hide( jQuery(this) );
        })

        return cls;
    },
    bindInput: function (me, c_self) {

        // console.log("bindInput: " , me);
        var $div = me.closest('.e20r-activity-exercise-tracking');
        var $edit_elem = $div.find('div.e20r-edit');
        var $show_elem = $div.find('div.e20r-saved');

        $show_elem.find('a.e20r-edit-weight-value, a.e20r-edit-rep-value').unbind('click').on('click', function(){

            console.log('Edit weight entry');
            c_self.show_hide( jQuery(this).closest('.e20r-exercise-tracking-row') );

        });

        $div.find('.e20r-save-set-row').unbind('click').on('click', function(){

            console.log("Save button for edit of set row");
            c_self.attemptSave( me, c_self );

        });
/*        $show_elem.find('a.e20r-edit-rep-value').unbind('click').on('click', function(){

            console.log('Edit based on rep entry');
            c_self.show_hide( jQuery(this) );

        });
*/
        me.on('focus', function(){

            c_self.activate(me);
        });

        me.on('blur', function() {

            if ( c_self._complete() ) {

                jQuery('#e20r-activity-input-button').removeClass('startHidden');
            }

            var $w = $div.find('.e20r-activity-input-weight').val();
            var $r = $div.find('.e20r-activity-input-reps').val();

            var $hW = $div.find('.e20r-activity-input-weight_h').val();
            var $hR = $div.find('.e20r-activity-input-reps_h').val();

            console.log("W: " + $w + " R: " + $r + " hW: " + $hW + " hR: " + $hR );

            if ( ( $w !=  $hW ) ||
                ( $r != $hR )){

/*                if ( $w == '' )  {

                    $div.find('.e20r-activity-input-weight_h').val(0);
                    $div.find('.e20r-activity-input-weight').val(0);
                }

                if ( $r == '' )  {

                    $div.find('.e20r-activity-input-reps_h').val(0);
                    $div.find('.e20r-activity-input-reps').val(0);
                }
*/
                me.removeClass('active');
                me.addClass('edited');

                //c_self.attemptSave( me, c_self );
            }

            console.log("Element being blurred: ", me);

        });

        me.keypress( function( event, self ) {

            if ( event.which == 13 ) {
                me.trigger('blur');
            }
        });

        c_self.$saveBtn.unbind('click').on('click', function(){

//            console.log("Save button clicked.");
            c_self.saveAll();
        });
    },
    _hasData: function( elem ) {

        if ( ! ( elem instanceof jQuery ) ) {

            console.log("Element isn't a jquery object. Convert it.");
            elem = jQuery( elem );
        }

        if ( elem.val() != '' ) {
            console.log("Element contains data...");
            return true;
        }

        return false;
    },
    show_hide: function(me) {

        console.log("Element is: ", me );

        var $div = me.find('.e20r-resp-table');
        var $edit = $div.find('.e20r-edit')
        var $show = $div.find('.e20r-saved');

        if ( ( this._hasData( $div.find('.e20r-activity-input-weight') ) ||
                this._hasData( $div.find('.e20r-activity-input-reps') ) ) &&
                ( $show.hasClass('startHidden') ) ) {

            console.log("The 'show' element is hidden and shouldn't be 'cause there's data entered" );
            $show.removeClass("startHidden");
            $edit.addClass("startHidden");
        }
        else if ( $edit.hasClass('startHidden') ) {
            console.log("A 'show' element was clicked. Switch to edit mode." );
            console.log($show);
            $show.addClass("startHidden");
            $edit.removeClass("startHidden");
            $edit.find('button.e20r-button').removeClass('startHidden');
        }

/*        if ( $edit.hasClass('startHidden') ) {

            console.log("The 'edit' element is hidden...");
            $edit.removeClass("startHidden");
            $show.addClass("startHidden");
        }
*/
    },
    activate: function( self ) {

        if ( ! ( self instanceof jQuery ) ) {

            console.log("No a jquery object. Convert it.");
            self = jQuery( self );
        }

        // console.log("Ready to activate/edit the field...", self);

        self.addClass("active");

    },
    _needToSave: function( $elem ) {

        var $hWeight = $elem.find('.e20r-activity-input-weight_h').val();
        var $hReps = $elem.find('.e20r-activity-input-reps_h').val();

        var $reps = $elem.find('.e20r-activity-input-reps').val();
        var $weight = $elem.find('.e20r-activity-input-weight').val();

        if ( ( $hWeight == $weight ) && ( $hReps == $reps ) ) {

            console.log("No need to save: ", $elem );
            return false;
        }

        return true;
    },
    attemptSave: function( inp, self ) {

        event.preventDefault();

//         console.log("Getting ready to save data in the field...");

        if ( ! ( inp instanceof jQuery ) ) {

//            console.log("inp isn't a jquery object. Converting.");
            inp = jQuery( inp );
        }

        var $track_row = inp.closest('tr.e20r-exercise-tracking-row');
        var $ex_def = inp.closest('tr.e20r-exercise-tracking-row').prev('.e20r-exercise-row');

        console.log("Exercise Row: ", $ex_def);

        var $show = $track_row.find('tr.e20r-saved');
        var $edit = $track_row.find('tr.e20r-edit');

        inp.removeClass("active");
        jQuery("body").addClass("loading");

/*        if ( inp.val() != '' ) {

            console.log("Data: ", inp.val());
        }
*/
        if ( self._needToSave( $track_row ) ) {

            var $weight = null;
            var $reps = null;

            if (!inp.hasClass('e20r-activity-input-reps')) {

                // Update value in show location
                $show.find('a.e20r-edit-weight-value').text(inp.val());
            }

            // Check if both input boxes for this rep/weight row contains data. If so, save it.
            if (inp.hasClass('e20r-activity-input-weight') &&
                jQuery.isNumeric(inp.siblings('.e20r-activity-input-reps').val())) {

                $show.find('a.e20r-edit-rep-value').text(inp.val());
            }

            var $hWeight = $track_row.find('.e20r-activity-input-weight_h').val();
            var $hReps = $track_row.find('.e20r-activity-input-reps_h').val();

            $reps = $edit.find('.e20r-activity-input-reps').val();
            $weight = $edit.find('.e20r-activity-input-weight').val();

            var $data = {
                action: 'e20r_save_activity',
                'e20r-tracker-activity-input-nonce': this.$nonce,
                'user_id': this.$userId,
                'activity_id': this.$activityId,
                'program_id': this.$programId,
                'recorded': ( Math.floor(Date.now() / 1000) ),
                'id': $edit.find('input.e20r-activity-input-record_id').val(),
                'for_date': this.$forDate,
                'group_no': $edit.find('input.e20r-activity-input-group_no').val(),
                'set_no': $edit.find('input.e20r-activity-input-set_no').val(),
                'exercise_id': $ex_def.find('input.e20r-display-exercise-id').val(),
                'exercise_key': $edit.find('input.e20r-activity-input-ex_key').val(),
                'weight': ( $weight !== '' ? $weight : null ),
                'reps': ( $reps !== '' ? $reps : null )
            };

            console.log("Sending data: ", $data );

            jQuery.ajax({
                url: e20r_workout.url,
                type: 'POST',
                timeout: 7000,
                data: $data,
                success: function (resp) {

                    console.log("Data saved!", resp);

                    var $id = resp.data.id;

                    $track_row.find('.e20r-activity-input-record_id').val($id);

                    $track_row.find('.e20r-activity-input-reps').val($reps);
                    $track_row.find('.e20r-activity-input-reps_h').val($reps);

                    $track_row.find('.e20r-activity-input-weight').val($weight);
                    $track_row.find('.e20r-activity-input-weight_h').val($weight);

                    $show.find('a.e20r-edit-rep-value').text($reps);
                    $show.find('a.e20r-edit-weight-value').text($weight);

                    self.show_hide($track_row);

                },
                error: function (xhdr, errstr, error) {
                    console.log("Error saving data");

                    $track_row.find('.e20r-activity-input-reps').val($hReps);
                    $track_row.find('.e20r-activity-input-weight').val($hWeight);

                    $show.find('a.e20r-edit-rep-value').text($hReps);
                    $show.find('a.e20r-edit-weight-value').text($hWeight);

                },
                complete: function () {
                    console.log("Completed processing of activity set/rep update");
                }
            });
        }
        else {
            self.show_hide($track_row);
        }

        this._clearLoading();
    },
    saveAll: function() {

        event.preventDefault();

        // TODO: Figure out whether there's data for all of the set entries. If yes then set activity to 'complete' and submit the form.
        var $data = jQuery("#e20r-activity-input-form").serialize();

        if ( ! this._complete() ) {

            console.log("Incomplete form...")
        }
        console.log("Serialized form: ", $data );

    },
    _complete: function() {

        var $total = this.$rows.length;
        var $compl = 0;

        this.$weight_fields.each(function() {

            if ( jQuery(this).val() !== '' ) {
                $compl++;
            }
        });

        var $pct = $compl/$total;

        console.log("Percent complete: " + $pct );

        if ( $total == $compl ) {
            return true;
        }

        if ( $pct > 0.89 ) {
            return true;
        }

        return false;
    },
    _clearLoading: function() {
        jQuery("body").removeClass("loading");
    }
};
/*
jQuery(function() {
    jQuery('.mejs-overlay-loading').closest('.mejs-overlay').addClass('load'); //just a helper class

    var $video = jQuery('div.video video');
    var vidWidth = $video.attr('width');
    var vidHeight = $video.attr('height');

    jQuery(window).resize(function() {
        var targetWidth = jQuery(this).width('400px'); //using window width here will proportion the video to be full screen; adjust as needed
        jQuery('div.video, div.video .mejs-container').css('height', Math.ceil( vidHeight * ( targetWidth / vidWidth ) ) );
    }).resize();
});
*/
jQuery(document).ready( function(){

    console.log("Loaded user script for the workout tracking form");
    e20rActivity.init();

});
