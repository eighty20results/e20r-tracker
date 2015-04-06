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
        this.$wRows = jQuery(".e20r-activity-input-weight");
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

        this.$wRows.each( function(){

            cls.show_hide( jQuery(this) );
        })


        return cls;
    },
    bindInput: function (me, c_self) {

        // console.log("bindInput: " , me);
        var $edit_elem = me.closest('.e20r-two-col');
        var $show_elem = $edit_elem.next('div.e20r-saved');

        $show_elem.find('a.e20r-edit-weight-value').on('click', function(){

            c_self.show_hide( jQuery(this) );
            console.log('Edit based on weight entry');
        });

        $show_elem.find('a.e20r-edit-rep-value').on('click', function(){

            c_self.show_hide( jQuery(this) );
            console.log('Edit based on rep entry');
        });

        me.on('focus', function(){

            c_self.activate(this);
        });

        me.on('blur', function() {

            if ( me.val() != '' ) {

                me.removeClass('active');
                me.addClass('edited');
            }

            c_self.attemptSave( me );

        });

        me.keypress( function( event, self ) {

            if ( event.which == 13 ) {
                me.trigger('blur');
            }
        });

        c_self.$saveBtn.unbind('click').on('click', function(){

            console.log("Save button clicked.");
            this.saveAll();
        });
    },
    _hasData: function( elem ) {

        if ( ! ( elem instanceof jQuery ) ) {

            console.log("Element isn't a jquery object. Convert it.");
            elem = jQuery( elem );
        }

        if ( elem.val() != '' ) {
            return true;
        }

        return false;
    },
    show_hide: function(me) {

        var $edit = me.closest('div.e20r-two-col');
        var $show = $edit.next('div.e20r-saved');

        if ( ( this._hasData( $edit.find('.e20r-activity-input-weight') ) ||
                this._hasData( $edit.find('.e20r-activity-input-reps') ) ) &&
                ( $show.hasClass('startHidden') ) ) {

            console.log("The 'show' element is hidden and shouldn't be 'cause there's data entered" );
            $show.removeClass("startHidden");
            $edit.addClass("startHidden");
        }

/*        if ( $edit.hasClass('startHidden') ) {

            console.log("The 'edit' element is hidden...");
            $edit.removeClass("startHidden");
            $show.addClass("startHidden");
        }
*/
    },
    activate: function( self ) {
        console.log("Ready to activate/edit the field...", self);

        jQuery(self).addClass("active");
    },
    attemptSave: function( inp ) {

        console.log("Getting ready to save data in the field...");

        if ( ! ( inp instanceof jQuery ) ) {

            console.log("inp isn't a jquery object. Converting.");
            inp = jQuery( inp );
        }

        inp.removeClass("active");

        if ( inp.val() != '' ) {

            console.log("Data: ", inp.val());
        }

        if ( inp.hasClass == '' ) {
            console.log('');
        }

        jQuery.ajax({
            url: e20r_workout.url,
            type: 'POST',
            timeout: 7000,
            data: {
                action: 'e20r_save_activity',
                'e20r-tracker-activity-input-nonce': this.$nonce,
                'id': inp.siblings('.e20r-activity-input-record_id').val(),
                'for_date': inp.siblings('.e20r-activity-input-recorded').val(),
                'recorded': ( Math.floor( Date.now() / 1000 ) ),
                'group_no': inp.closest('.e20r-activity-input-group_no').val(),
                'user_id': this.$userId,
                'activity_id': this.$activityId,
                'program_id': this.$programId,
                'exercise_id': inp.siblings('.e20r-activity-input-ex_id').val(),
                'exercise_key': inp.siblings('.e20r-activity-input-ex_key').val(),
                'weight': '',
                'reps': ''
            },
            success: function( resp ) {
                console.log("Data saved?");
            },
            error: function( xhdr, errstr, error ) {
                console.log("Error saving data");
            }
        });
    },
    saveAll: function() {

        var $data = jQuery("#e20r-activity-input-form").serialize();

        console.log("Serialized form: ", $data );

    }
};

jQuery(document).ready( function(){

    console.log("Loaded user script for the workout tracking form");
    e20rActivity.init();

});
