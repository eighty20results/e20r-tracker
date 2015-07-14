/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery.noConflict();

var $body = jQuery("body");

jQuery(document).on({
    ajaxStart: function() { $body.addClass("loading");   },
    ajaxStop: function() { $body.removeClass("loading"); }
});

jQuery(document).ready(function() {

    var e20rCheckinEvent = {
        init: function() {

            this.$body = jQuery("body");
            this.$checkinOptions = jQuery('#e20r-daily-checkin-canvas fieldset.did-you input:radio');
            this.$checkinDate = jQuery('#e20r-checkin-checkin_date').val();
            this.$checkedinDate = jQuery('#e20r-checkin-checkedin_date').val();
            this.$checkinAssignmentId = jQuery('#e20r-checkin-assignment_id').val();
            this.$checkinArticleId = jQuery('#e20r-checkin-article_id').val();
            this.$checkinProgramId = jQuery('#e20r-checkin-program_id').val();
            this.$nonce = jQuery('#e20r-checkin-nonce').val();
            this.$itemHeight = jQuery('#e20r-daily-checkin-canvas fieldset.did-you ul li').outerHeight();
            this.$ulList = this.$checkinOptions.parents('ul');
            this.$tomorrowBtn = jQuery("#e20r-checkin-daynav").find("#e20r-checkin-tomorrow-lnk");
            this.$yesterdayBtn = jQuery("#e20r-checkin-daynav").find("#e20r-checkin-yesterday-lnk");

            var me = this;

            this.bindProgressElements( me );
        },
        bindProgressElements: function(self) {

            self.$tomorrowBtn = jQuery("#e20r-daily-progress").find("#e20r-checkin-daynav").find("#e20r-checkin-tomorrow-lnk");
            self.$yesterdayBtn = jQuery("#e20r-daily-progress").find("#e20r-checkin-daynav").find("#e20r-checkin-yesterday-lnk");

            jQuery("#e20r-daily-progress").find('#e20r-daily-checkin-canvas fieldset.did-you input:radio').on('click', function(){

                var $radioBtn = this;

                console.log("Checkin button: ", this);
                self.$actionOrActivity = jQuery(this).attr('name').split('-')[1].title();
                console.log("Action or activity? ", self.$actionOrActivity);
                self.saveCheckin(this, self.$actionOrActivity, self);
            });

            self.showBtn( self );

            jQuery(self.$tomorrowBtn).on('click', function() {

                jQuery("body").addClass("loading");

                event.preventDefault();
                self.dayNav(self, this);

            });

            jQuery(self.$yesterdayBtn).on('click', function() {

                jQuery("body").addClass("loading");

                event.preventDefault();
                self.dayNav(self, this);

            });
        },
        showBtn: function( self ) {

            var radioFieldsActivity = jQuery('fieldset.did-you:eq(0) input:radio', '#e20r-daily-checkin-canvas');
            var radioFieldsAction = jQuery('fieldset.did-you:eq(1) input:radio', '#e20r-daily-checkin-canvas');

            jQuery(radioFieldsActivity)
                .add(radioFieldsAction)
                .filter(':checked')
                .parent('li')
                .addClass('active');

            var setEditState = function(set) {
                jQuery(set)
                    .not(':checked')
                    .parent('li')
                    .addClass('concealed')
                    .parents('fieldset.did-you')
                    .append('<button class="e20r-button edit-selection">Edit check-in</button>');
            }

            if (bool(jQuery(radioFieldsActivity).filter(':checked').length)) {

                setEditState(radioFieldsActivity);

                jQuery(document).on('click', '#e20r-daily-checkin-canvas .edit-selection', function(){

                    self.editCheckin( this );
                });
            }

            if (bool(jQuery(radioFieldsAction).filter(':checked').length)) {

                setEditState(radioFieldsAction);

                jQuery(document).on('click', '#e20r-daily-checkin-canvas .edit-selection', function(){

                    self.editCheckin( this );
                });
            }
        },
        saveNotes: function( elem, $a, self ) {

            var $data = {

            }
        },
        saveCheckin: function( elem, $a, self ){

//            console.log("Element is: ", elem );

//            console.log("Type: ", jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_type:first') );

            jQuery('body').addClass("loading");

            var $data = {
                action: 'saveCheckin',
                'checkin-action': $a,
                'e20r-checkin-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'checkedin-date': self.$checkedinDate,
                'descr-id': self.$checkinAssignmentId,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'checkedin': jQuery(elem).val(),
                'checkin-type': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_type:first').val(),
                'id': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-id:first').val(),
                'checkin-short-name': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-checkin-checkin_short_name:first').val()
            };

            jQuery.post( e20r_checkin.url, $data, function(response) {

                if ( ! response.success ) {

                    var $string;
                    $string = "An error occurred when trying to save your choice to the S3F Nourish Coaching database. If you\'d like to try again, reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);

                    return;
                }
                else {

                    jQuery(elem).data('__persisted', true);

                    var ul = jQuery(elem).parents('ul');

                    var index = jQuery(ul).children('li').index(jQuery(elem).parent());
                    console.log("index: ", index);

                    if (null !== jQuery(ul).data('activeIndex')
                        && index == jQuery(ul).data('activeIndex'))
                        return false;

                    console.log("activeIndex..??");

                    jQuery(ul)
                        .find('input[type=radio]')
                        .parent('li')
                        .removeClass('active');

                    jQuery(elem)
                        .parent('li')
                        .addClass('active');

                    var notificationAnimation = function(fadeOutCallback) {
                        jQuery(ul)
                            .next('.notification-entry-saved')
                            .children('div')
                            .fadeIn('medium', function() {
                                var _self = this;
                                setTimeout(function() {
                                    jQuery(_self)
                                        .fadeOut('slow', fadeOutCallback);
                                }, 2200);
                            });
                    };

                    var selectionSlideUp = function( self ) {
                        var activeItemOffsetTop = index * self.$itemHeight;

                        jQuery(ul)
                            .css('margin-top', (activeItemOffsetTop) + 'px')
                            .animate({
                                marginTop: 0
                            }, 200 * index, function() {
                                notificationAnimation( function() {
                                    jQuery(ul)
                                        .parents('fieldset.did-you')
                                        .append('<button class="e20r-button edit-selection">Edit check-in</button>');

                                    jQuery(document).on('click', '#e20r-daily-checkin-canvas .edit-selection', function(){
                                        console.log("Clicked the edit-selection button");
                                        console.log("This = ", this );
                                        console.log("Self = ", self);
                                        self.editCheckin( this );
                                    });

                                });
                            });
                    }

                    var once = 0;

                    jQuery(ul)
                        .children('li')
                        .not('.active')
                        .fadeOut('medium', function() {
                            if (1 == once) // only run this callback once
                                return;

                            selectionSlideUp( self );

                            once = 1;
                        });

                    jQuery(ul)
                        .data('activeIndex', index)
                        .data('updateMode', true);

                    console.log("Set .data: ");
                }

            });

            jQuery('body').removeClass("loading");
        },
        editCheckin: function( elem ) {

            var button = jQuery(elem);

            jQuery(button)
                .parents('fieldset.did-you')
                .find('ul')
                .find('li')
                .removeClass('concealed')
                .css('display', 'block');

            jQuery(button)
                .remove();

        },
        dayNav: function( self, elem ) {

            var navDay = jQuery(elem).next("input[name^='e20r-checkin-day']").val();

            console.log("Day Nav value: ", navDay );

            var data = {
                action: 'daynav',
                'e20r-checkin-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'e20r-checkin-day': navDay
            }

            console.log("toNext data: ", data);

            jQuery.ajax({
                url: e20r_checkin.url,
                type: 'POST',
                timeout: 5000,
                data: data,
                success: function (response) {

                    console.log("Response: ", response);
                    jQuery('#e20r-daily-progress').html(response.data);

                    self.bindProgressElements(self);
                },
                error: function (jqx, errno, errtype) {

                    console.log("Error: ", jqx );

                    var $string;
                    $string = "An error occurred while trying to load the requested page. If you\'d like to try again, please reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);

                    return;

                },
                complete: function () {
                    jQuery("body").removeClass("loading");
                }
            });

/*
            jQuery.post(e20r_checkin.url, data, function(response) {

                console.log("Daily progress response: ", response );

                if ( ! response.success ) {

                    var $string;
                    $string = "An error occurred while trying to load the requested page. If you\'d like to try again, please reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);

                    return;
                }
                else {

                    jQuery('#e20r-daily-progress').html(response.data);

                    self.bindProgressElements( self );

                }
            });

            $body.removeClass("loading");
            */
        }
    };

    var e20rDailyProgress = {
        init: function( assignmentElem ) {

            console.log("Init of e20rDailyProgress() class.");

            this.$assignment = assignmentElem;
            this.$saveAssignmentBtn = this.$assignment.find("#e20r-assignment-save");
            this.$answerForm = this.$assignment.find("form#e20r-assignment-answers");
            this.$checkinBtn = this.$assignment.find("button#e20r-lesson-complete");
            this.$inputs = this.$answerForm.find('.e20r-assignment-response');

            var self = this;

            this.$saveAssignmentBtn.on('click', function(){
                console.log("Clicked 'Save' for the assignment");
                self.saveAnswers( self );
            });

            this.$checkinBtn.on('click', function(){
                console.log("Clicked 'Read lesson' button");
                self.lessonComplete( self );
            })

            this.$inputs.each(function() {
                console.log("Working through response fields");

                jQuery(this).focus(function(){

                    console.log("Gave focus to", this);
                    jQuery('#e20r-assignment-save-btn').show();
                    jQuery('#e20r-assignment-complete').hide();
                })
            })
        },
        lessonComplete: function( self ) {

            event.preventDefault();
            console.log("lessonComplete()...");

            jQuery.ajax({
                url: e20r_checkin.url,
                type: 'POST',
                /* data: 'action=save_daily_checkin&' + self.$answerForm.serialize(), */
                data: 'action=save_daily_progress&' + self.$answerForm.serialize(),
                success: function( $response ) {

                    jQuery('#e20r-lesson-complete').hide();
                    jQuery(".e20r-lesson-highlight").hide();
                    jQuery('#e20r-assignment-complete').each(function() {
                        jQuery(this).show();
                    });
                },
                error: function( jqxhr, $errString, $errType ) {
                    console.log("Error String: " + $errString + " and errorType: " + $errType);
                }
            });

            return false;
        },
        saveAnswers: function( self ) {

            event.preventDefault();

            var answers = self.$answerForm.serialize();
            console.log("saveAssignment(): " + answers, self.$answerForm);

            jQuery.ajax({
                'type': 'POST',
                'url': e20r_checkin.url,
                'data': 'action=save_daily_progress&' + answers,
                success: function( $response ) {

                    jQuery('#e20r-assignment-save-btn').hide();
                    jQuery('#e20r-assignment-complete').each(function() {
                        jQuery(this).show();
                    });

                },
                error: function( jqxhr, $errString, $errType ) {
                    console.log("Error String: " + $errString + " and errorType: " + $errType, jqxhr);
                }
            });

            return false;
        }
    };

    var Note = {
        init: function() {

            this.noteField = jQuery('fieldset.notes');
            this.actionFields = jQuery('fieldset.did-you.habit');
            // this.activityFields = jQuery('fieldset.did-you-workout');

            this.note_id = this.noteField.find('.e20r-checkin-id').val();
            this.checkin_shortname = this.noteField.find('.e20r-checkin-checkin_short_name').val();
            this.note_article = this.noteField.siblings('#e20r-checkin-article_id').val();
            this.note_assignment = this.noteField.siblings('#e20r-checkin-assignment_id').val();
            this.note_program = this.noteField.siblings('#e20r-checkin-program_id').val();
            this.note_date = this.noteField.siblings('#e20r-checkin-checkin_date').val();
            this.note_actualdate = this.noteField.siblings('#e20r-checkin-checkedin_date').val();
            this.checkin_type = this.actionFields.find('.e20r-checkin-checkin_type').val();
            this.checkin_value = this.actionFields.siblings('input[name^="did-action-today"]:checked').val()

            var self = this;

            console.log('Note object: ', self);

            jQuery('#note-textarea').autogrow().trigger('keyup');

            setTimeout(function() {
                jQuery('#note-display')
                    .css('height', function() {
                        return jQuery('#note-textarea').outerHeight() + 'px';
                    })
                    .css('color', '#757575')
            }, 200);

            var noteValOnLoad = jQuery('#note-textarea').val().strip();
            var hadValPreviously = bool(noteValOnLoad);

            var stickyNoteOverlay = function(fadeSpeed) {
                fadeSpeed = fadeSpeed || 0;

                var note = jQuery('#note-textarea')[0];

                if ('' == jQuery(note).val().strip()) {
                    return;
                }

                jQuery('#note-display')
                    .children('div')
                    .html(function() {
                        return jQuery('#note-textarea').val().replace(/[\r\n]/g, '<br />') + '<span id="note-para-end"></span>';
                    })
                    .end()
                    .css('height', function() {
                        return jQuery('#note-textarea').outerHeight() + 'px';
                    })
                    .fadeIn(fadeSpeed);

                setDisplayState();

                return true;
            };

            var setDisplayState = function() {
                jQuery('#save-note')
                    .data('editMode', true)
                    .text('Edit Note')
                    .trigger('blur');

                var noteDisplay = jQuery('#note-display');

                var overflowHeight = noteDisplay.outerHeight() - parseInt(noteDisplay.css('height'));

                jQuery('#note-display-overflow-pad')
                    .height(overflowHeight);
            }

            var setEditState = function() {
                jQuery('#note-display')
                    .fadeOut('fast');

                jQuery('#note-textarea')
                    .trigger('focus');

                jQuery('#save-note')
                    .data('editMode', false)
                    .text('Save Note');

                jQuery('#note-display-overflow-pad')
                    .height(0);
            };

            // initial state
            stickyNoteOverlay(0);

            jQuery('#save-note')
                .bind('attempt_save_with_empty_textarea', function() {
                    var $this = jQuery(this);
                    var $noteTextarea = jQuery('#note-textarea');

                    $this
                        .addClass('tooltip-handle')
                        .attr('data-tooltip', 'Enter your notes in the text area above')
                        .data('__attemptedSaveOnEmpty', true);

                    Tooltip.event.mouseover.call(this, {}, { pos: 'left' });
                    Tooltip.unbindHandle(this, { timeout: 1500 });

                    return;
                });

            jQuery('#save-note')
                .click(function() {
                    var $this = jQuery(this);
                    var $noteTextarea = jQuery('#note-textarea');

                    // the note field is empty, and didn't have a value previously
                    if (!hadValPreviously
                        && '' == $noteTextarea.val().strip()) {

                        return $this.triggerHandler('attempt_save_with_empty_textarea');
                    }

                    // save
                    if (false == bool($this.data('editMode'))) {

                        // Unassigned (which is ok.
                        if ( self.note_assignment == '' ) {

                            self.note_assignment = 0;
                        }

                        // No defined article for this date/delay value (which is ok)
                        if ( self.note_article == '' ) {

                            self.note_article = 0;
                        }

                        var data = {
                            action: 'saveCheckin',
                            'e20r-checkin-nonce': jQuery('#e20r-checkin-nonce').val(),
                            'id': self.note_id,
                            'checkin-short-name': self.checkin_shortname,
                            'checkin-date': self.note_date,
                            'checkedin_date': self.note_actualdate,
                            'assignment-id': self.note_assignment,
                            'article-id': self.note_article,
                            'program-id': self.note_program,
                            'checkin-note': Base64.encode($noteTextarea.val()),
                            'checkedin': self.checkin_value,
                            'checkin-type': self.checkin_type
                        };

                        console.log("Sending: ", data );

                        jQuery.post( e20r_checkin.url, data, function( response, status ) {

                            if (status == 'success') {
                                $this.data('__persisted', true);
                            }
                        });

                        $this.data('__attemptedSaveOnEmpty', null);
                    }


                    if (bool($this.data('editMode'))) {
                        setEditState();

                        return;
                    }

                    var notificationTimeout;

                    /* save notification */
                    jQuery('fieldset.notes')
                        .find('.notification-entry-saved')
                        .children('div')
                        .fadeIn('medium', function() {
                            var self = this;
                            notificationTimeout = setTimeout(function() {
                                jQuery(self)
                                    .fadeOut('slow');
                            }, 4200);
                        });

                    setTimeout(function() {
                        stickyNoteOverlay(300);
                    }, 2000);

                }); // click
        }
    };

    if (jQuery('#e20r-article-assignment').length > 0) {

        jQuery( e20rDailyProgress.init( jQuery('#e20r-article-assignment') ) );
    }

    if ( jQuery("#e20r-daily-checkin-canvas").length > 0 ) {

        jQuery(e20rCheckinEvent.init());
    }

    if ( jQuery("textarea#note-textarea").length > 0 ) {

        jQuery(Note.init());
    }
});
