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
        this.$saveBtn = jQuery('#e20r-activity-input-button');

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

           c_self.attemptSave( me );
        });

        me.keypress( function( event, self ) {

            if ( event.which == 13 ) {
                me.trigger('blur');
            }
        });

        c_self.$saveBtn.unbind('click').on('click', function(){

            console.log("Save button clicked.");
        })
    },
    show_hide: function(me) {

        var $edit = me.closest('div.e20r-two-col');
        var $show = $edit.next('div.e20r-saved');

        if ( $show.hasClass('startHidden') ) {
            console.log("The 'show' element is hidden...");
            $show.removeClass("startHidden");
            $edit.addClass("startHidden");
        }

        if ( $edit.hasClass('startHidden') ) {

            console.log("The 'edit' element is hidden...");
            $edit.removeClass("startHidden");
            $show.addClass("startHidden");
        }
    },
    activate: function( self ) {
        console.log("Ready to activate/edit the field...", self);

        jQuery(self).addClass("active");
    },
    attemptSave: function( inp ) {
        console.log("Getting ready to save data in the field...");

        jQuery(inp).removeClass("active");

        var $data = jQuery("#e20r-activity-input-form").serialize();
        console.log("Serialized form: ", $data );
    }
};

jQuery(document).ready( function(){

    console.log("Loaded user script for the workout tracking form");
    e20rActivity.init();

});
