/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery.noConflict();

var $body = jQuery("body");

/*
jQuery(document).on({
    ajaxStart: function () {
        $body.addClass("loading");
    },
    ajaxStop: function () {
        $body.removeClass("loading");
    }
});
*/
jQuery(document).ready(function () {

    var e20rCheckinEvent = {
        init: function () {

            this.is_running = true;

            this.$body = jQuery("body");
            this.$header_tag = jQuery("#e20r-daily-progress");
            this.$checkinOptions = jQuery('#e20r-daily-action-canvas fieldset.did-you input:radio');
            this.$checkinDate = jQuery('#e20r-action-checkin_date').val();
            this.$checkedinDate = jQuery('#e20r-action-checkedin_date').val();
            this.$checkinAssignmentId = jQuery('#e20r-action-assignment_id').val();
            this.$checkinArticleId = jQuery('#e20r-action-article_id').val();
            this.$checkinProgramId = jQuery('#e20r-action-program_id').val();
            this.$nonce = jQuery('#e20r-action-nonce').val();
            this.$itemHeight = jQuery('#e20r-daily-action-canvas fieldset.did-you ul li').outerHeight();
            this.$ulList = this.$checkinOptions.parents('ul');
            this.$progressNav = jQuery("#e20r-action-daynav");
            this.$tomorrowBtn = this.$progressNav.find("#e20r-action-tomorrow-lnk");
            this.$yesterdayBtn = this.$progressNav.find("#e20r-action-yesterday-lnk");
            this.$activityLnk = jQuery(".e20r-action-activity").find("#e20r-activity-read-lnk");
            this.$actionLnk = jQuery(".e20r-action-lesson").find("#e20r-action-read-lnk");
            this.$allowActivityOverride = false;
            this.$cardSetting = jQuery('input[name="e20r-use-card-based-display"]').val();

            var me = this;

            this.bindProgressElements(me);
        },
        bindProgressElements: function (self) {

            self.$tomorrowBtn = self.$header_tag.find("#e20r-action-daynav").find("#e20r-action-tomorrow-lnk");
            self.$yesterdayBtn = self.$header_tag.find("#e20r-action-daynav").find("#e20r-action-yesterday-lnk");
            self.$activityLnk = self.$header_tag.find("td#e20r-action-activity").find("#e20r-activity-read-lnk");
            self.$actionLnk = self.$header_tag.find("td#e20r-action-lesson").find("#e20r-action-read-lnk");

            self.$header_tag.find('#e20r-daily-action-canvas fieldset.did-you input:radio').unbind('click').on('click', function () {

                var $radioBtn = this;

                console.log("Action button: ", this);
                self.$actionOrActivity = jQuery(this).attr('name').split('-')[1].title();
                console.log("Action or activity? ", self.$actionOrActivity);
                self.saveCheckin(this, self.$actionOrActivity, self);
            });

            self.showBtn(self);

            jQuery(self.$tomorrowBtn).unbind('click').on('click', function () {

                jQuery("body").addClass("loading");

                event.preventDefault();
                self.dayNav(self, this);

                jQuery("body").removeClass("loading");

            });

            jQuery(self.$yesterdayBtn).unbind('click').on('click', function () {

                jQuery("body").addClass("loading");

                event.preventDefault();
                self.dayNav(self, this);

                jQuery("body").removeClass("loading");

            });

            jQuery(self.$activityLnk).unbind('click').on("click", function () {

                console.log("Clicked the 'Read more' link for activity");

                // jQuery("body").addClass("loading");
                event.preventDefault();

                self.toActivity(self);

                // jQuery("body").removeClass("loading");
            });

            jQuery(self.$actionLnk).unbind('click').on("click", function () {

                console.log("Clicked the 'Read more' link for action");

//                jQuery("body").addClass("loading");
                event.preventDefault();

                self.toAction(this, self);

//                jQuery("body").removeClass("loading");
            });

        },
        showBtn: function (self) {

            var radioFieldsActivity = jQuery('fieldset.did-you.workout input:radio', '#e20r-daily-action-canvas');
            var radioFieldsAction = jQuery('fieldset.did-you.habit input:radio', '#e20r-daily-action-canvas');

            radioFieldsActivity
                .add(radioFieldsAction)
                .filter(':checked')
                .parents('li')
                .addClass('active');

            radioFieldsAction
                .add(radioFieldsActivity)
                .filter(':checked')
                .parents('li')
                .addClass('active');

            var setEditState = function (set) {

                if ( !jQuery(set).parents('fieldset.did-you').find('button.edit-selection').length ) {

                    console.log("No pre-existing edit button found. Adding it.", set );
                    jQuery(set)
                        .not(':checked')
                        .parents('li')
                        .addClass('concealed')
                        .parents('fieldset.did-you')
                        .append('<button class="e20r-button edit-selection">Edit check-in</button>');
                }
            };

            console.log("Deciding what to do with activity check-in", radioFieldsActivity);

            if ( jQuery(radioFieldsActivity).filter(':checked').length ) {

                setEditState(radioFieldsActivity);

                jQuery(document).unbind('click').on('click', '#e20r-daily-action-canvas .edit-selection', function () {

                    self.editCheckin(this);
                });
            }

            console.log("Deciding what to do with action check-in", radioFieldsAction);

            if ( jQuery(radioFieldsAction).filter(':checked').length ) {

                setEditState(radioFieldsAction);

                jQuery(document).unbind('click').on('click', '#e20r-daily-action-canvas .edit-selection', function () {

                    self.editCheckin(this);
                });
            }
        },
        saveCheckin: function (elem, $a, self) {

//            console.log("Element is: ", elem );

//            console.log("Type: ", jQuery(elem).closest('fieldset.did-you > div').find('.e20r-action-checkin_type:first') );

            // $body.addClass("loading");

            var $data = {
                action: 'e20r_saveCheckin',
                'checkin-action': $a,
                'e20r-action-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'checkedin-date': self.$checkedinDate,
                'descr-id': self.$checkinAssignmentId,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'checkedin': jQuery(elem).val(),
                'checkin-type': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-action-checkin_type:first').val(),
                'action-id': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-action-id:first').val(),
                'checkin-short-name': jQuery(elem).closest('fieldset.did-you > div').find('.e20r-action-checkin_short_name:first').val()
            };

            jQuery.post(e20r_action.ajaxurl, $data, function (response) {

                if (!response.success) {

                    var $string;
                    $string = "An error occurred when trying to save your choice to the database. If you\'d like to try again, reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);
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

                    var notificationAnimation = function (fadeOutCallback) {
                        jQuery(ul)
                            .next('.notification-entry-saved')
                            .children('div')
                            .fadeIn('medium', function () {
                                var _self = this;
                                setTimeout(function () {
                                    jQuery(_self)
                                        .fadeOut('slow', fadeOutCallback);
                                }, 1200);
                            });
                    };

                    var selectionSlideUp = function (self) {
                        var activeItemOffsetTop = index * self.$itemHeight;

                        jQuery(ul)
                            .css('margin-top', (activeItemOffsetTop) + 'px')
                            .animate({
                                marginTop: 0
                            }, 200 * index, function () {
                                notificationAnimation(function () {

                                    jQuery(ul)
                                        .parents('fieldset.did-you')
                                        .append('<button class="e20r-button edit-selection">Edit check-in</button>');

                                    jQuery(document).on('click', '#e20r-daily-action-canvas .edit-selection', function () {
                                        console.log("Clicked the edit-selection button");
                                        console.log("This = ", this);
                                        console.log("Self = ", self);
                                        self.editCheckin(this);
                                    });

                                });
                            });
                    };

                    var once = 0;

                    jQuery(ul)
                        .children('li')
                        .not('.active')
                        .fadeOut('medium', function () {
                            if (1 == once) // only run this callback once
                                return;

                            selectionSlideUp(self);

                            once = 1;
                        });

                    jQuery(ul)
                        .data('activeIndex', index)
                        .data('updateMode', true);

                    // console.log("Set .data: ");
                }

            });

            // $body.removeClass("loading");
        },
        editCheckin: function (elem) {

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
        dayNav: function (self, elem) {

            event.preventDefault();

//            $body.addClass("loading");

            var navDay = jQuery(elem).next("input[name^='e20r-action-day']").val();
            var today = jQuery('#e20r-action-today').val();

            // console.log("Day Nav value: ", navDay );

            var data = {
                action: 'e20r_daynav',
                'e20r-action-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'e20r-use-card-based-display': self.$cardSetting,
                'e20r-action-day': navDay,
                'e20r-today': today
            };

            // console.log("toNext data: ", data);

            jQuery.ajax({
                url: e20r_action.ajaxurl,
                type: 'POST',
                timeout: e20r_action.timeout,
                data: data,
                success: function (response) {

                    // console.log("Response: ", response);

                    if (response.success) {

                        self.$header_tag.html(response.data);
                        self.$allowActivityOverride = true;

                        self.init();

                        console.log("Re-init for Note object(s)");
                        jQuery(Note.init());
                    }
                    else {
                        console.log("success == false returned from AJAX call");

                        if (1 == response.data.ecode) {

                            console.log("Give the user an error message");

                            self.$allowActivityOverride = false;

                            var $string;

                            $string = "Sorry, we're not quite ready to show you that yet...\n\n";
                            $string += "If you want see what tomorrow\'s workout is, ";
                            $string += "head over to the Archives.";

                            alert($string);
                        }

                        if (2 == response.data.ecode) {

                            self.$allowActivityOverride = false;

                            var $string;

                            $string = "This day that you are on is the first day of the program.\n\n";
                            $string += "There is no past beyond this! Feel free to try to navigate into ";
                            $string += "the (relative) future from here!";

                            alert($string);

                        }

                        if (3 == response.data.ecode) {
                            console.log("User needs to log in again.");
                            location.href = e20r_action.login_url;
                        }
                    }
                },
                error: function ($response, $errString, $errType) { // function (jqx, errno, errtype) {

                    console.log("Error: ", $response);

                    self.$allowActivityOverride = false;

                    var $msg = '';

                    if ('timeout' === $errString) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to load the requested page. If you\'d like to try again, please reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($msg + $string);

                },
                complete: function () {
//                    $body.removeClass("loading");
                }
            });

            /*
             jQuery.post(e20r_action.ajaxurl, data, function(response) {

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

//            $body.removeClass("loading");
        },
        toAction: function (elem, self) {

            console.log("Clicked the 'Read more' link for the action");
            // jQuery('body').addClass("loading");

            var $action_link = jQuery(elem);

            console.log("Link for action link: ", $action_link.attr('href'));

            var data = {
                'e20r-action-nonce': self.$nonce,
                'for-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
            };

            console.log("Data to possibly send to action page: ", data);

            jQuery.redirect($action_link.attr('href'), data);
        },
        toActivity: function (self) {

            console.log("Clicked the 'Read more' link for the activity");
//            jQuery('body').addClass("loading");

            var data = {
                'e20r-action-nonce': self.$nonce,
                'for-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'activity-id': jQuery("#e20r-action-activity_id").val(),
                'activity-override': self.$allowActivityOverride
            };

            jQuery.redirect(e20r_action.activity_url, data);

            /*
             jQuery.ajax({
             url: e20r_action.ajaxurl,
             type: 'POST',
             timeout: 5000,
             data: data,
             success: function (response) {
             console.dir(response);

             return;
             },
             error: function (jqx, errno, errtype) {

             console.log("Error: ", jqx );

             return;

             },
             complete: function() {
             jQuery("body").removeClass("loading");
             }
             });
             */
        }
    };

    var e20rDailyProgress = {
        init: function (assignmentElem) {

            console.log("Init of e20rDailyProgress() class.");

            this.$assignment = assignmentElem;
            this.$saveAssignmentBtn = this.$assignment.find("#e20r-assignment-save");
            this.$answerForm = this.$assignment.find("form#e20r-assignment-answers");
            this.$checkinBtn = this.$assignment.find("button#e20r-lesson-complete");
            this.$inputs = this.$answerForm.find('.e20r-assignment-response');
            this.$choices = this.$answerForm.find('td.e20r-assignment-survey-question-choice, input[type="radio"], input[type="checkbox"]');
            this.$multichoice = this.$answerForm.find('.e20r-select2-assignment-options');

            var self = this;

            this.$saveAssignmentBtn.on('click', function () {
                console.log("Clicked 'Save' for the assignment");
                self.saveAnswers(self);
            });

            this.$checkinBtn.on('click', function () {
                console.log("Clicked 'Read lesson' button");
                self.lessonComplete(self);
            });


            this.$inputs.each(function () {
                console.log("Working through response fields");

                jQuery(this).focus(function () {

                    console.log("Gave focus to", this);
                    jQuery('#e20r-assignment-save-btn').show();
                    jQuery('.e20r-assignment-complete').each(function () {
                        jQuery(this).hide();
                    });
                });
            });

            this.$choices.each(function () {

                console.log("Working through likert fields");

                jQuery(this).on('click', function () {

                    console.log("Gave focus to", this);
                    jQuery('#e20r-assignment-save-btn').show();
                    jQuery('.e20r-assignment-complete').each(function () {
                        jQuery(this).hide();
                    });
                })
            });

            this.$multichoice.each(function () {

                console.log("Working through multichoice fields");
                jQuery(this).on('select2:open', function () {

                    console.log(" Opening select2 entry.");
                    console.log("Clicked on:", this);

                    jQuery('#e20r-assignment-save-btn').show();
                    jQuery('.e20r-assignment-complete').each(function () {
                        jQuery(this).hide();
                    });
                })
            });

        },
        lessonComplete: function (self) {

            event.preventDefault();
            console.log("lessonComplete()...");

            jQuery.ajax({
                url: e20r_action.ajaxurl,
                type: 'POST',
                timeout: e20r_action.timeout,
                /* data: 'action=save_daily_checkin&' + self.$answerForm.serialize(), */
                data: 'action=e20r_save_daily_progress&' + self.$answerForm.serialize(),
                success: function ($response) {

                    jQuery('#e20r-lesson-complete').hide();
                    jQuery(".e20r-lesson-highlight").hide();
                    jQuery('.e20r-assignment-complete').each(function () {
                        jQuery(this).show();
                    });
                },
                error: function ($response, $errString, $errType) {

                    console.log("From server: ", $response);
                    console.log("Error String: " + $errString + " and errorType: " + $errType);

                    self.$allowActivityOverride = false;

                    var $msg = '';

                    if ('timeout' === $errString) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to save this data. If you\'d like to try again, please ";
                    $string += "click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($msg + $string);

                }
            });

            return false;
        },
        saveAnswers: function (self) {

            event.preventDefault();
//            $body.addClass("loading");

            var $mc_answers = jQuery('.e20r-select2-assignment-options');

            $mc_answers.each(function () {

                var $resp_val = jQuery(this);
                var $h_resp = $resp_val.siblings('.e20r-assignment-select2-hidden');

                console.log("Assignment answer(s) for this field: ", $resp_val.val());
                $h_resp.val(JSON.stringify($resp_val.val()));
                console.log("Stringified list of answers in multichoice: " + $h_resp.val());
            });

            var answers = self.$answerForm.serialize();
            console.log("saveAssignment(): " + answers, self.$answerForm);

            jQuery.ajax({
                'type': 'POST',
                'url': e20r_action.ajaxurl,
                timeout: 10000,
                'data': 'action=e20r_save_daily_progress&' + answers,
                success: function ($response) {

                    jQuery('#e20r-assignment-save-btn').hide();
                    jQuery('.e20r-assignment-complete').each(function () {
                        jQuery(this).show();
                    });

                },
                error: function ($response, $errString, $errType) {

                    console.log("From server: ", $response);
                    console.log("Error String: " + $errString + " and errorType: " + $errType);

                    var $msg = '';

                    if ('timeout' === $errString) {

                        $msg = "Error: Timeout while the server was processing data.\n\n";
                    }

                    var $string;
                    $string = "An error occurred while trying to save this data. If you\'d like to try again, please ";
                    $string += "click your selection once more. \n\nIf you get this error a second time, ";
                    $string += "please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($msg + $string);
                }
            });

//            $body.removeClass("loading");
            return false;
        }
    };

    var Note = {
        init: function () {

            this.noteField = jQuery('fieldset.notes');
            this.actionFields = jQuery('fieldset.did-you.habit');
            this.activityFields = jQuery('fieldset.did-you.workout');

            this.note_id = this.noteField.find('.e20r-action-id').val();
            this.checkin_shortname = this.noteField.find('.e20r-action-checkin_short_name').val();
            this.note_article = jQuery('#e20r-action-article_id').val();
            this.note_assignment = jQuery('#e20r-action-assignment_id').val();
            this.note_program = jQuery('#e20r-action-program_id').val();
            this.note_date = jQuery('#e20r-action-checkin_date').val();
            this.note_actualdate = jQuery('#e20r-action-checkedin_date').val();
            this.checkin_type = this.noteField.find('.e20r-action-checkin_type').val();
            this.checkin_value = this.actionFields.siblings('input[name^="did-action-today"]:checked').val();

            var self = this;

            // console.log('Note object: ', self);

            jQuery('#note-textarea').autoGrow();

            self.noteValOnLoad = jQuery('#note-textarea').val().strip();
            self.hadValPreviously = bool(self.noteValOnLoad);

            setTimeout(function () {

                jQuery('#note-textarea')
                    .css('height', function() {

                        var height;

                        if ( self.hadValPreviously ) {

                            height = jQuery('#note-textarea').height() + 'px';
                        }
                        else {
                            height = '4em';
                        }

                        return height;
                    });

                jQuery('#note-display')
                    .css('height', function() {

                        var noteArea = jQuery('div#e20r-action-notes').closest('.e20r-daily-action-row').outerHeight();
                        console.log("Note area height: ", noteArea );
                        return noteArea + 'px';
                    })
                    .css('color', 'rgba(92, 92, 92, 0.9 )')
                    .css('background-size', '40px 40px');

                jQuery('#e20r-daily-action-container #e20r-daily-action-canvas fieldset.notes #note-display>div')
                    .css('padding-bottom', '20px');
            }, 200);

            if ( self.noteValOnLoad.length != 0 ) {

                self._stickyNoteOverlay();
            }

            jQuery('#save-note')
                .bind('attempt_save_with_empty_textarea', function () {
                    var $this = jQuery(this);
                    var $noteTextarea = jQuery('#note-textarea');

                    $this
                        .addClass('tooltip-handle')
                        .attr('data-tooltip', 'Enter your notes in the text area above')
                        .data('__attemptedSaveOnEmpty', true);

                    Tooltip.event.mouseover.call(this, {}, {pos: 'left'});
                    Tooltip.unbindHandle(this, {timeout: 1500});
                });

            jQuery('#save-note').unbind('click').on( 'click', function() {
                self.save_note( self );
            }); // click

            jQuery(window).resize( function() {

                setTimeout(function () {
                    self._stickyNoteOverlay(0);
                }, 200 );
            });

        },
        save_note: function ( self ) {

            console.log("Content of 'self': ", self);
            jQuery('body').addClass("loading");

            var $elem = jQuery('#save-note');
            var $noteTextarea = jQuery('#note-textarea');

            var notificationTimeout;

            // the note field is empty, and didn't have a value previously
            if (! self.hadValPreviously
                && '' == $noteTextarea.val().strip()) {

                console.log("Trigger the save operation while the textarea is empty logic");
                jQuery('body').removeClass("loading");
                return $elem.triggerHandler('attempt_save_with_empty_textarea');
            }

            // save
            if (false === bool($elem.data('editMode'))) {

                // Unassigned (which is ok.
                if (self.note_assignment == '') {

                    self.note_assignment = 0;
                }

                // No defined article for this date/delay value (which is ok)
                if (self.note_article == '') {

                    self.note_article = 0;
                }

                var data = {
                    action: 'e20r_saveCheckin',
                    'e20r-action-nonce': jQuery('#e20r-action-nonce').val(),
                    'action-id': self.note_id,
                    'checkin-short-name': self.checkin_shortname,
                    'checkin-date': self.note_date,
                    'checkedin-date': self.note_actualdate,
                    'assignment-id': self.note_assignment,
                    'article-id': self.note_article,
                    'program-id': self.note_program,
                    'checkin-note': Base64.encode($noteTextarea.val()),
                    'checkedin': self.checkin_value,
                    'checkin-type': self.checkin_type
                };

                console.log("Note --- Sending: ", data );

                jQuery.ajax({
                    url: e20r_action.ajaxurl,
                    type: 'POST',
                    timeout: e20r_action.timeout,
                    dataType: 'JSON',
                    data: data,
                    error: function( $response, $errString, $errType ) {

                        var $msg = '';

                        if ( 'timeout' === $errString ) {

                            $msg = "Error: Timeout while the server was processing data.\n\n";
                        }

                        var $string;
                        $string = "An error occurred while trying to save your notes.\n\n";
                        $string += "Please contact Technical Support by using the Contact form ";
                        $string += "at the top of this page. When you contact Technical Support, please include this entire message.";

                        alert( $msg + $string + "\n\n" + $response.data );

                    },
                    success: function( $response ) {

                        if ( $response.success === true ) {

                            console.log("Returned from the web server: ", $response);

                            // initial state
                            // self._stickyNoteOverlay(0);

                            $elem.data('__persisted', true);

                            /* Show saved notification */

                            // self.note_text_blurred();

                            jQuery('fieldset.notes')
                                .find('.notification-entry-saved')
                                .children('div')
                                .fadeIn('medium', function () {

                                    var me = this;
                                    notificationTimeout = setTimeout(function () {
                                        jQuery(me)
                                            .fadeOut('slow');
                                    }, 3000);
                                });

                            setTimeout(function () {
                                self._stickyNoteOverlay(300);
                            }, 2000);
                        }
                        else {
                            var $string;
                            var $msg = '';

                            $string = "An error occurred while trying to save your notes.\n\n";
                            $string += "Please contact Technical Support by using the Contact form ";
                            $string += "at the top of this page. When you contact Technical Support, please include this entire message.";

                            alert( $msg + $string + "\n\n" + $response.data );
                            return false;
                        }
                    },
                    complete: function() {

/*                      if (bool($class.data('editMode'))) {

                             self._setEditState();

                             console.log("Setting note section to edit state");
                             return;
                        }
*/
                        jQuery('body').removeClass("loading");
                        console.log("Save operation is complete for user note");
                    }
                });

                $elem.data('__attemptedSaveOnEmpty', null);

            } else {

                self._setEditState();
                jQuery('body').removeClass("loading");
                console.log("Changing edit state for note section");
                return;
            }
        },
        edit_note: function() {

            var $class = this;
            var $html = jQuery('#note-textarea').val();

            var divHtml = jQuery('div#note-display').html();
            var editableText = jQuery('<textarea />');

            editableText.val(divHtml);

            console.log("Content of editableText: ", editableText );
            jQuery('div#note-display').replaceWith(editableText);

            editableText.blur($class.note_text_blurred);
        },
        note_text_blurred: function() {

            var html = jQuery('#note-textarea').val();
            var viewableText = jQuery("<div>");

            viewableText.html(html);
            jQuery(this).replaceWith(viewableText);
            viewableText.click($class.edit_note);
        },
        _setDisplayState: function () {

            jQuery('#save-note')
                .data('editMode', true)
                .text('Edit Note')
                .trigger('blur');

            var noteDisplay = jQuery('#note-display');
            var noteText = jQuery('#note-textarea');

            var overflowHeight = (noteText.outerHeight() - noteDisplay.outerHeight());

            console.log("Setting height for overflow: ", overflowHeight );
            jQuery('#note-display-overflow-pad')
                .height(overflowHeight);

            noteText.focus();
            var v= noteText.val();
            noteText.val('');
            noteText.val(v);
        },
        _stickyNoteOverlay: function (fadeSpeed) {

            fadeSpeed = fadeSpeed || 0;

            var $class = this;
            var t_height;
            var note = jQuery('#note-textarea')[0];

            if ('' == jQuery(note).val().strip()) {
                return;
            }

            jQuery('#note-display')
                .children('div')
                .html(function () {
                    return jQuery('#note-textarea').val().replace(/[\r\n]/g, '<br />') + '<span id="note-para-end"></span>';
                })
                .end()
                .css('width', function() {

                    var t_width = jQuery('div.e20r-action-notes').innerWidth();
                    var d_width = jQuery('#note-display').outerWidth();

                    console.log("Width of textarea: ", t_width );
                    console.log("Width of note area: ", d_width );

                    return t_width + 'px';
                })
                .css('height', function () {

                    var textarea = jQuery('#note-textarea');
                    var area = jQuery('fieldset.notes').closest('.e20r-action-notes');
                    var area_height = area.outerHeight();
                    var legend = area.find('fieldset.notes > legend').outerHeight();
                    var notification = area.find('.notification-entry-saved').outerHeight();
                    var descr_height = area.find('fieldset.notes > p').outerHeight();
                    var button_height = area.find('.button-container').outerHeight();
                    var overflow_height = area.find('#note-display-overflow-pad').outerHeight();
                    var text_height = textarea.height();
                    var outer_height = textarea.outerHeight();

                    if ( text_height > outer_height ) {
                        console.log("Setting height to the value of the text height");
                        t_height = text_height;
                    }

                    if ( outer_height > text_height ) {
                        console.log("Setting height to the value of the outer height");
                        t_height = outer_height;
                    }

                    t_height = ( area_height - legend - descr_height - overflow_height - notification - button_height );

                    // var t_height = jQuery('#note-textarea').outerHeight();
                    console.log("Area height: ", area_height);
                    console.log("Legend height: ", legend );
                    console.log("Description height: ", descr_height );

                    console.log("Text height: ", text_height);
                    console.log("Outer height: ", outer_height);
                    console.log("Height value to use: ", t_height );

                    return t_height + 'px';
                })
                .css('border-top', '2px solid #c5c5c5')
                .css('overflow', 'hidden')
                .css('padding-right', '10px')
                .css('text-overflow', 'ellipsis')
                .fadeIn(fadeSpeed);

            // jQuery('#note-textarea').height( outer_height );

            $class._setDisplayState();
            jQuery('body').removeClass("loading");

            return true;
        },
        _setEditState: function () {
            jQuery('#note-display')
                .fadeOut('fast');

            jQuery('#note-textarea')
                .trigger('focus');

            jQuery('#save-note')
                .data('editMode', false)
                .text('Save Note');

            jQuery('#note-display-overflow-pad')
                .height(0);
        }
    };

    if (e20rCheckinEvent.is_running) {
        return;
    }

    if (jQuery('#e20r-article-assignment').length > 0) {

        jQuery(e20rDailyProgress.init(jQuery('#e20r-article-assignment')));
    }

    if (jQuery("#e20r-daily-action-canvas").length > 0) {

        jQuery(e20rCheckinEvent.init());
    }

    if (jQuery("textarea#note-textarea").length > 0) {

        jQuery(Note.init());
    }
});
