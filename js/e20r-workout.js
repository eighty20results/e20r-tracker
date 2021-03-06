/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

var $body = jQuery("body");

/*
jQuery(document).on({
    ajaxStart: function() { $body.addClass("loading");   },
    ajaxStop: function() { $body.removeClass("loading"); }
});
*/
var e20rActivity = {
    init: function () {

        this.$weight_fields = jQuery('.e20r-activity-input-weight');
        this.$rep_fields = jQuery('.e20r-activity-input-reps');
        this.$rows = jQuery(".e20r-exercise-tracking-row");
        this.$print = jQuery("#e20r-activity-print");
        this.$tracked = jQuery(".e20r-exercise-set-row");
        this.$nonce = jQuery('#e20r-tracker-activity-input-nonce').val();
        this.$saveBtn = jQuery('#e20r-activity-input-button');
        this.$userId = jQuery('#e20r-activity-input-user_id').val();
        this.$programId = jQuery('#e20r-activity-input-program_id').val();
        this.$activityId = jQuery('#e20r-activity-input-activity_id').val();
        this.$forDate = jQuery('#e20r-activity-input-for_date').val();

        var activity = this;

        jQuery(".e20r-exercise-description > p").each( function() {

            var $txt = jQuery(this).text().replace( /\n/g, "<br/>" );

            jQuery(this).html($txt);
        });

        activity.$rows.each(function() {

            var row = jQuery(this);
            activity.bindInput( row, activity );

        });

        activity.$tracked.each( function(){

            activity.hide( jQuery(this) );
        });

        activity.$saveBtn.unbind('click').on('click', function(){

//            console.log("Save button clicked.");
            activity.saveAll();
        });

        activity.$print.unbind('click').on('click', function() {
            console.log("User requested print function");
        });

        return activity;
    },
    close_print: function() {
        document.body.removeChild(this.__container__);
    },
    set_print: function () {

        var $class = this;

        this.contentWindow.__container__ = this;

        this.contentWindow.onbeforeunload = $class.close_print;
        this.contentWindow.onafterprint = $class.close_print;

        this.contentWindow.focus(); // Required for IE
        this.contentWindow.print();
    },
    print_page: function( sURL ) {

        var $class = this;
        var oHiddFrame = document.createElement("iframe");

        oHiddFrame.onload = $class.set_print;
        oHiddFrame.style.visibility = "hidden";
        oHiddFrame.style.position = "fixed";
        oHiddFrame.style.right = "0";
        oHiddFrame.style.bottom = "0";
        oHiddFrame.src = sURL;

        document.body.appendChild(oHiddFrame);
    },
    bindInput: function (me, activity) {

        var $eRow = me.find('.e20r-exercise-set-row');
        var $sRow = me.find('.e20r-saved');

        $sRow.find('a.e20r-edit-weight-value, a.e20r-edit-rep-value').unbind('click').on('click', function(){

            console.log('Edit weight or rep entry');
            activity.hide( jQuery(this).closest('.e20r-saved').prev() );
        });

        $eRow.find('.e20r-save-set-row').unbind('click').on('click', function(){

            var $save_btn = jQuery(this);

//            console.log("Save button for edit of set row");
            activity.attemptSave( $save_btn, activity );

        });

        /** Set focus and blur functionality for the input elements **/
        $eRow.find('input.e20r-activity-input-weight, input.e20r-activity-input-reps').each(function(){

            var inp = jQuery(this);

            inp.on('focus', function(){

                activity.activate(inp);
            });

            inp.on('blur', function() {

                if ( activity._complete() ) {

                    jQuery('#e20r-activity-input-button').removeClass('startHidden');
                }

                var $w = $eRow.find('.e20r-activity-input-weight').val();
                var $r = $eRow.find('.e20r-activity-input-reps').val();

                var $hW = $eRow.find('.e20r-activity-input-weight_h').val();
                var $hR = $eRow.find('.e20r-activity-input-reps_h').val();

                if ( ( $w !=  $hW ) ||
                    ( $r != $hR )){

                    inp.removeClass('active');
                    inp.addClass('edited');

                    //activity.attemptSave( inp, activity );
                }

                // console.log("Element being blurred: ", inp);

            });

            // Trap the 'enter key' event (and trigger a blur action)
            inp.keypress( function( event, self ) {

                if ( event.which == 13 ) {

                    inp.trigger('blur');
                }
            });
        });

    },
    _hasData: function( elem ) {

        if ( ! ( elem instanceof jQuery ) ) {

            console.log("Element isn't a jquery object. Convert it.");
            elem = jQuery( elem );
        }

        // console.log("hasData for: ", elem);
        var $value = elem.val();

        if ( $value ) {
            console.log("Value: " + $value);
            return true;
        }

        return false;
    },
    hide: function( element ) {

        var eRow = element;
        var sRow = element.next();

        if ( ( this._hasData( eRow.find('input.e20r-activity-input-weight') ) ||
            this._hasData( eRow.find('input.e20r-activity-input-reps') ) ) &&
            sRow.hasClass('startHidden') )
        {

            console.log("The display element is hidden and should not be - we've got new data");
            sRow.removeClass('startHidden');
            eRow.addClass('startHidden');
        }
        else if ( eRow.hasClass('startHidden') ) {

            console.log("User may want to edit (they clicked) the data");
            sRow.addClass('startHidden');
            eRow.removeClass('startHidden');
            eRow.find('button.e20r-button').removeClass('startHidden');
        }
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

        // console.log("Element to save?", $elem);

        var $hWeight = $elem.find('.e20r-activity-input-weight_h').val();
        var $hReps = $elem.find('.e20r-activity-input-reps_h').val();

        var $weight = $elem.find('.e20r-activity-input-weight').val();
        var $reps = $elem.find('.e20r-activity-input-reps').val();

        return ( ( $hWeight != $weight ) || ( $hReps != $reps ) )
    },
    attemptSave: function( $btn, activity ) {

        event.preventDefault();

        $body.addClass("loading");

//        console.log("Getting ready to save data in the field...");

        if ( ! ( $btn instanceof jQuery ) ) {

//            console.log("inp isn't a jquery object. Converting.");
            $btn = jQuery( $btn );
        }
        // console.log("Activity causing save: ", $btn );

        var $edit = $btn.closest('.e20r-edit');
        var $show = $edit.next('.e20r-saved');
        var $rInput = $edit.find('input.e20r-activity-input-reps');
        var $wInput = $edit.find('input.e20r-activity-input-weight');
        var $ex_def = $edit.closest('.e20r-exercise-tracking-row').prev().prev();

        // console.log("Exercise Row: ", $ex_def);

        $rInput.removeClass("active");
        $wInput.removeClass("active");

        // jQuery("body").addClass("loading");

        /*
        if ( inp.val() != '' ) {

            console.log("Data: ", inp.val());
        }
        */
        if ( activity._needToSave( $edit ) ) {

            var $weight = null;
            var $reps = null;

           // Update value in show location
            $show.find('a.e20r-edit-weight-value').text($rInput.val());

            // Check if both input boxes for this rep/weight row contains data. If so, save it.
            if ($wInput.val() && jQuery.isNumeric( $rInput.val() ) ) {

                $show.find('a.e20r-edit-rep-value').text($rInput.val());
            }

            var $hWeight = $edit.find('.e20r-activity-input-weight_h').val();
            var $hReps = $edit.find('.e20r-activity-input-reps_h').val();

            $reps = $rInput.val();
            $weight = $wInput.val();

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

            // console.log("Sending data: ", $data );

            jQuery.ajax({
                url: e20r_workout.ajaxurl,
                type: 'POST',
                timeout: e20r_workout.timeout,
                data: $data,
                success: function (resp) {

                    var $id = resp.data.id;

                    $edit.find('input.e20r-activity-input-record_id').val($id);

                    $edit.find('input.e20r-activity-input-reps').val($reps);
                    $edit.find('input.e20r-activity-input-reps_h').val($reps);

                    $edit.find('input.e20r-activity-input-weight').val($weight);
                    $edit.find('input.e20r-activity-input-weight_h').val($weight);

                    $show.find('a.e20r-edit-rep-value').text($reps);
                    $show.find('a.e20r-edit-weight-value').text($weight);

                    // console.log("Save Row: ", $edit);
                    activity.hide($edit);

                },
                error: function($response, $errString, $errType) {

                    console.log("Error saving data");

                    $rInput.val($hReps);
                    $wInput.val($hWeight);

                    $show.find('a.e20r-edit-rep-value').text($hReps);
                    $show.find('a.e20r-edit-weight-value').text($hWeight);

                    console.log("From server: ", $response );
                    console.log("Error String: " + $errString + " and errorType: " + $errType + " from e20r_updateUnitTypes()");

                    var $msg = '';

                    if ( 'timeout' === $errString ) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to save this set/reps entry. If you\'d like to try again, please ";
                    $string += "reload the page and edit this value again. \n\nShould you get this error a second time, ";
                    $string += "please contact Technical Support by using the Contact form ";
                    $string += "at the top of this page. When you contact Technical Support, please include this message in its entirety.";

                    alert( $msg + $string + "\n\n" + $response.data );

                },
                complete: function () {
                    console.log("Completed processing of activity set/rep update");
                }
            });
        }
        else {
            activity.hide($edit);
        }

        activity._clearLoading();
    },
    saveAll: function() {

        var $class = this;
        event.preventDefault();

        $body.addClass("loading");

        // var $data = jQuery("#e20r-activity-input-form").serialize();

        var $data = {
            action: 'e20r_save_activity',
            'e20r-tracker-activity-input-nonce': this.$nonce,
            'user_id': $class.$userId,
            'activity_id': $class.$activityId,
            'article_id': jQuery('#e20r-activity-input-article_id').val(),
            'program_id': $class.$programId,
            'for_date': $class.$forDate,
            'recorded': ( Math.floor( Date.now() / 1000) ),
            'completed': $class._complete()
        };

        if ( $class._complete() ) {

            jQuery.ajax({
                url: e20r_workout.ajaxurl,
                type: 'POST',
                timeout: e20r_workout.timeout,
                data: $data,
                success: function (resp) {

                    var $id = resp.data.id;

                },
                error: function($response, $errString, $errType) {

                    console.log("Error confirming completion of the activity");

                    console.log("From server: ", $response );
                    console.log("Error String: " + $errString + " and errorType: " + $errType + " from e20r_updateUnitTypes()");

                    var $msg = '';

                    if ( 'timeout' === $errString ) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to confirm your completion of this activity. If you\'d like to try again, please ";
                    $string += "try again.\n\nShould you get this error a second time, ";
                    $string += "please contact Technical Support by using the Contact form. ";
                    $string += "When you contact Technical Support, please include this message in its entirety.";

                    alert( $msg + $string + "\n\n" + $response.data );

                },
                complete: function () {
                    console.log("Completed processing of activity.");
                }
            });

            $class._clearLoading();
        }

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

        return ( 0.89 < $pct );
    },
    _clearLoading: function() {
        $body.removeClass("loading");
    }
};

var e20rActivityView = {
    init: function() {

        this.isIE = this._isIE();
        this.isOldIE = this._isIE() && (this.isIE() < 9);
        this.isiPad = this._isIpad();
        this.tag = jQuery('.youtube-player');
        this.poster = this.tag.data('placeholder');
        this.video = this.tag.data('video');
        this.noVideo = this.tag.data('src');
        this.el = '';

        this.$timer_btn = jQuery('#start_exercise');
        this.workTimer = jQuery('input[name="e20r-duration-value"]').val();
        this.restTimer = jQuery('input[name="e20r-rest-value"]').val();

        var $class = this;

        this.$timer_btn.unbind('click').on('click', function() {

            this.start_timer( $class.workTimer,  $class.restTimer );
        });
    },
    load: function() {

        var $class = this;

        if ( jQuery(window).width() > 599 && !$class.isOldIE && !$class.isiPad) {

            $class.el += '<video poster="' + $class.poster + '">';
            $class.el += '  <source src="' + $class.video + '" type="video/mp4">';
            $class.el += '</video>';
        } else {

            $class.el = '<div class="video-element" style="background-image: url(' + $class.noVideo + ');"></div>';
        }

        this.tag.prepend($class.el);
    },
    start_timer: function( work, rest ) {

        var workCounter = 0;
        var $class = this;

        var workTimer = setInterval(function() {

            $class.change_color('work');

            workCounter++;
            jQuery('.e20r-overlay-timer').text(workCounter);

            if ( workCounter == work ) {

                clearInterval(workTimer);
            }
        }, 1000);

        var restCounter = 0;
        var restTimer = setInterval(function() {

            $class.change_color('work');

            restCounter++;
            rest = work + 1 + rest;

            if ( restCounter > (work+1) ) {


                jQuery('.e20r-overlay-timer').text(restCounter);
            }

            if ( restCounter == rest ) {

                $class.change_color('done');
                clearInterval(workTimer);
            }

        }, 1000);
    },
    // TODO: Implement change_color for overlay background
    change_color: function( $type ) {

        // Set background color for overlay
        if ( 'work' == $type ) {
            // Red?
        }

        if ( 'rest' == $type ) {
            // Green?
        }

        if ( 'done' == $type ) {
            // White?
        }

    },
    _isIE: function() {
        var nav = navigator.userAgent.toLowerCase();
        return (nav.indexOf('msie') != -1) ? parseInt(nav.split('msie')[1]) : false;
    },
    _isIpad: function() {
        return navigator.userAgent.match('/iPad/i');
    },

};

jQuery(document).ready( function($){

    console.log("Loaded user script for the workout tracking form");

    if ( $('#e20r-daily-activity-page').length ) {

        window.console.log("Hide the header for the [e20r_activity] shortcode page");
        $('header.entry-header').hide();
    }

    e20rActivity.init();

    $('.e20r-faq-question').unbind().on('click', function(){

        var $this_heading = $(this);
        var $module = $this_heading.closest('.e20r-faq-container');
        var $content = $module.find('.e20r-faq-answer-container');

        if ( $content.is( ':animated' ) ) {
            return;
        }

        $content.slideToggle( 700, function() {

            if ( $module.hasClass('e20r-toggle-close') ) {

                $module.removeClass('e20r-toggle-close').addClass('e20r-toggle-open');
            }
            else {

                $module.removeClass('e20r-toggle-open').addClass('e20r-toggle-close');
            }
        });
    });
});
