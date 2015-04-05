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

        var cls = this;

        cls.$weight_fields.each(function () {
            cls.bindInput( jQuery(this), cls );
        });

        cls.$rep_fields.each(function () {
            cls.bindInput( jQuery(this), cls );
        });

        console.log("Loaded Activity class");

        return cls;
    },
    bindInput: function (me, c_self) {

        // console.log("bindInput: " , me);

        me.on('focus', function(){
            c_self.activate();
        });

        me.on('blur', function() {

           c_self.attemptSave();
        });

        me.keypress( function( event, self ) {

            if ( event.which == 13 ) {
                me.trigger('blur');
            }
        });

    },
    activate: function() {
        console.log("Ready to activate/edit the field...");
    },
    attemptSave: function() {
        console.log("Getting ready to save data in the field...");
    }
};

jQuery(document).ready( function(){

    console.log("Loaded user script for the workout tracking form");
    e20rActivity.init();

});
