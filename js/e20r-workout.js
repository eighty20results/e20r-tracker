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
        this.$rows = jQuery(".e20r-exercise-set-row");
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

//            console.log("Processing exercise row for show/hide");
            cls.show_hide( jQuery(this) );
        })

        return cls;
    },
    bindInput: function (me, c_self) {

        // console.log("bindInput: " , me);
        var $div = me.closest('.e20r-exercise-set-row');
        var $edit_elem = $div.find('div.e20r-edit');
        var $show_elem = $div.find('div.e20r-saved');

        $show_elem.find('a.e20r-edit-weight-value, a.e20r-edit-rep-value').unbind('click').on('click', function(){

//            console.log('Edit weight entry');
            c_self.show_hide( jQuery(this) );

        });

        $div.find('.e20r-save-set-row').unbind('click').on('click', function(){

//            console.log("Save button for edit of set row");
            c_self.attemptSave( me, c_self );

        })
/*        $show_elem.find('a.e20r-edit-rep-value').unbind('click').on('click', function(){

            console.log('Edit based on rep entry');
            c_self.show_hide( jQuery(this) );

        });
*/
        me.on('focus', function(){

            c_self.activate(me);
        });

        me.on('blur', function() {

            if ( ( $div.find('.e20r-activity-input-weight').val() != $div.find('.e20r-activity-input-weight_h').val() ) &&
                ( $div.find('.e20r-activity-input-reps').val() != $div.find('.e20r-activity-input-reps_h').val() )){

                me.removeClass('active');
                me.addClass('edited');
                //c_self.attemptSave( me, c_self );
            }
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

//        console.log("Element is: ", me );

        var $div = me.closest('.e20r-exercise-set-row');
        var $edit = $div.find('div.e20r-edit')
        var $show = $div.find('div.e20r-saved');

        if ( ( this._hasData( $div.find('.e20r-activity-input-weight') ) ||
                this._hasData( $div.find('.e20r-activity-input-reps') ) ) &&
                ( $show.hasClass('startHidden') ) ) {

//            console.log("The 'show' element is hidden and shouldn't be 'cause there's data entered" );
            $show.removeClass("startHidden");
            $edit.addClass("startHidden");
        }
        else if ( $edit.hasClass('startHidden') ) {
//            console.log("A 'show' element was clicked. Switch to edit mode." );
//            console.log($show);
            $show.addClass("startHidden");
            $edit.removeClass("startHidden");
            $div.find('.e20r-save-set-row').removeClass('startHidden');
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

            return false;
        }

        return true;
    },
    attemptSave: function( inp, self ) {

        event.preventDefault();

//         console.log("Getting ready to save data in the field...");

        var $div = inp.closest('.e20r-exercise-set-row');
        var $show = $div.find('div.e20r-saved');
        var $edit = $div.find('div.e20r-edit');

        if ( ! ( inp instanceof jQuery ) ) {

//            console.log("inp isn't a jquery object. Converting.");
            inp = jQuery( inp );
        }

        inp.removeClass("active");
        $body.addClass("loading")

/*        if ( inp.val() != '' ) {

            console.log("Data: ", inp.val());
        }
*/
        if ( self._needToSave( $div ) ) {

            var $weight = null;
            var $reps = null;

            if (!inp.hasClass('e20r-activity-input-reps')) {

                // console.log("Attempting to save weight. Wait until the user attempts to edit/save reps.");
                // Update value in show location
                $show.find('a.e20r-edit-weight-value').text(inp.val());
            }

            // Check if both input boxes for this rep/weight row contains data. If so, save it.
            if (inp.hasClass('e20r-activity-input-weight') &&
                jQuery.isNumeric(inp.siblings('.e20r-activity-input-reps').val())) {

                // console.log("User editing the rep input");
                $show.find('a.e20r-edit-rep-value').text(inp.val());
            }

            var $hWeight = $div.find('.e20r-activity-input-weight_h').val();
            var $hReps = $div.find('.e20r-activity-input-reps_h').val();

            $reps = $edit.find('.e20r-activity-input-reps').val();
            $weight = $edit.find('.e20r-activity-input-weight').val();

            jQuery.ajax({
                url: e20r_workout.url,
                type: 'POST',
                timeout: 7000,
                data: {
                    action: 'e20r_save_activity',
                    'e20r-tracker-activity-input-nonce': this.$nonce,
                    'user_id': this.$userId,
                    'activity_id': this.$activityId,
                    'program_id': this.$programId,
                    'recorded': ( Math.floor(Date.now() / 1000) ),
                    'id': inp.siblings('.e20r-activity-input-record_id').val(),
                    'for_date': this.$forDate,
                    'group_no': inp.siblings('.e20r-activity-input-group_no').val(),
                    'set_no': inp.siblings('.e20r-activity-input-set_no').val(),
                    'exercise_id': inp.siblings('.e20r-activity-input-ex_id').val(),
                    'exercise_key': inp.siblings('.e20r-activity-input-ex_key').val(),
                    'weight': ( $weight !== '' ? $weight : null ),
                    'reps': ( $reps !== '' ? $reps : null )
                },
                success: function (resp) {

                    console.log("Data saved!", resp);

                    var $id = resp.data.id;

                    $div.find('.e20r-activity-input-record_id').val($id);

                    $div.find('.e20r-activity-input-reps').val($reps);
                    $div.find('.e20r-activity-input-reps_h').val($reps);

                    $div.find('.e20r-activity-input-weight').val($weight);
                    $div.find('.e20r-activity-input-weight_h').val($weight);

                    $show.find('a.e20r-edit-rep-value').text($reps);
                    $show.find('a.e20r-edit-weight-value').text($weight);

                    self.show_hide($div);

                },
                error: function (xhdr, errstr, error) {
                    console.log("Error saving data");

                    $div.find('.e20r-activity-input-reps').val($hReps);
                    $div.find('.e20r-activity-input-weight').val($hWeight);

                    $show.find('a.e20r-edit-rep-value').text($hReps);
                    $show.find('a.e20r-edit-weight-value').text($hWeight);

                },
                complete: function () {
                    console.log("Completed processing of activity set/rep update");
                    $body.removeClass("loading")
                }
            });
        }
        else {
            self.show_hide($div);
        }
    },
    saveAll: function() {

        event.preventDefault();

        var $data = jQuery("#e20r-activity-input-form").serialize();

        console.log("Serialized form: ", $data );

    }
};

jQuery(document).ready( function(){

    console.log("Loaded user script for the workout tracking form");
    e20rActivity.init();

});
