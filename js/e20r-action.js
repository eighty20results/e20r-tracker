/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * Version 2.0
 *
 * License Information:
 *  the GPL v2 license
 */

jQuery.noConflict();
jQuery(document).ready(function ($) {

    var $body = $("body");

    var e20rCheckinEvent = {
        init: function () {

            this.is_running = true;

            this.body = $("body");
            this.$header_tag = $("#e20r-daily-progress");
            this.$checkinOptions = $('fieldset.did-you input:radio');
            this.$checkinDate = $('#e20r-action-checkin_date').val();
            this.$checkedinDate = $('#e20r-action-checkedin_date').val();
            this.$checkinAssignmentId = $('#e20r-action-assignment_id').val();
            this.$checkinArticleId = $('#e20r-action-article_id').val();
            this.$checkinProgramId = $('#e20r-action-program_id').val();
            this.$nonce = $('#e20r-action-nonce').val();
            this.$itemHeight = $('fieldset.did-you ul li').outerHeight();
            this.$ulList = this.$checkinOptions.parents('ul');
            this.$progressNav = $("#e20r-action-daynav");
            this.$tomorrowBtn = this.$progressNav.find("#e20r-action-tomorrow-lnk");
            this.$yesterdayBtn = this.$progressNav.find("#e20r-action-yesterday-lnk");
            this.$activityLnk = $("div.e20r-action-activity").find("#e20r-activity-read-lnk");
            this.$actionLnk = $(".e20r-action-lesson").find("#e20r-action-read-lnk");
            this.$allowActivityOverride = false;
            this.$cardSetting = $('input[name="e20r-use-card-based-display"]').val();

            var me = this;

            this.bindProgressElements(me);
        },
        bindProgressElements: function (self) {

            self.$tomorrowBtn = self.$header_tag.find("#e20r-action-daynav").find("#e20r-action-tomorrow-lnk");
            self.$yesterdayBtn = self.$header_tag.find("#e20r-action-daynav").find("#e20r-action-yesterday-lnk");
            // self.$activityLnk = self.$header_tag.find("td#e20r-action-activity").find("#e20r-activity-read-lnk");
            // self.$actionLnk = self.$header_tag.find("td#e20r-action-lesson").find("#e20r-action-read-lnk");

            self.$activityLnk = self.$header_tag.find("#e20r-activity-read-lnk");
            self.$actionLnk = self.$header_tag.find("#e20r-action-read-lnk");

            self.$header_tag.find('fieldset.did-you input:radio').unbind('click').on('click', function () {

                var $radioBtn = this;

                console.log("Action button: ", this);
                self.$actionOrActivity = $(this).attr('name').split('-')[1].title();
                console.log("Action or activity? ", self.$actionOrActivity);
                self.saveCheckin(this, self.$actionOrActivity, self);
            });

            self.showBtn(self);

            // To the next (tomorrow) day
            $(self.$tomorrowBtn).unbind('click').on('click', function () {

                event.preventDefault();
                self.dayNav(self, this);
            });

            // To the previous (yesterday) day
            $(self.$yesterdayBtn).unbind('click').on('click', function () {

                event.preventDefault();
                self.dayNav(self, this);

            });

            //
            $(self.$activityLnk).unbind('click').on("click", function () {

                console.log("Clicked the 'Read more' link for activity");
                event.preventDefault();

                self.toActivity(self);
            });

            $(self.$actionLnk).unbind('click').on("click", function () {

                console.log("Clicked the 'Read more' link for action");
                event.preventDefault();

                self.toAction(this, self);
            });

        },
        showBtn: function (self) {

            var radioFieldsActivity = $('fieldset.did-you.workout input:radio, fieldset.did-you.workout label', '#e20r-daily-action-canvas');
            var radioFieldsAction = $('fieldset.did-you.habit input:radio, fieldset.did-you.habit label', '#e20r-daily-action-canvas');

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

                if ( !$(set).parents('fieldset.did-you').find('button.edit-selection').length ) {

                    console.log("No pre-existing edit button found. Adding it.", set );
                    $(set)
                        .not(':checked')
                        .parents('li')
                        .addClass('concealed')
                        .parents('fieldset.did-you')
                        .append('<button class="e20r-button edit-selection">Edit check-in</button>');
                }
            };

            console.log("Deciding what to do with activity check-in", radioFieldsActivity);

            if ( $(radioFieldsActivity).filter(':checked').length ) {

                setEditState(radioFieldsActivity);

                $(document).unbind('click').on('click', 'fieldset.did-you .edit-selection', function () {

                    self.editCheckin(this);
                });
            }

            console.log("Deciding what to do with action check-in", radioFieldsAction);

            if ( $(radioFieldsAction).filter(':checked').length ) {

                setEditState(radioFieldsAction);

                $(document).unbind('click').on('click', 'fieldset.did-you .edit-selection', function () {

                    self.editCheckin(this);
                });
            }
        },
        saveCheckin: function (elem, $a, self) {

            var $data = {
                action: 'e20r_saveCheckin',
                'checkin-action': $a,
                'e20r-action-nonce': self.$nonce,
                'checkin-date': self.$checkinDate,
                'checkedin-date': self.$checkedinDate,
                'descr-id': self.$checkinAssignmentId,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'checkedin': $(elem).val(),
                'checkin-type': $(elem).closest('fieldset.did-you').find('.e20r-action-checkin_type:first').val(),
                'action-id': $(elem).closest('fieldset.did-you').find('.e20r-action-id:first').val(),
                'checkin-short-name': $(elem).closest('fieldset.did-you').find('.e20r-action-checkin_short_name:first').val()
            };

            $.ajax({
                url: e20r_action.ajaxurl,
                timeout: parseInt( e20r_action.timeout ),
                dataType: 'json',
                method: 'POST',
                data: $data,
                beforeSend: function() {
                    window.console.log("Add the overlay");
                    self.body.addClass( 'loading ');
                },
                success: function( response, textStatus, jqHXR) {
                    if (!response.success) {

                        var $string;
                        $string = "An error occurred when trying to save your choice to the database. If you\'d like to try again, reload ";
                        $string += "this page, and click your selection once more. \n\nIf you get this error a second time, please contact Technical Support by using our Contact form ";
                        $string += "at the top of this page.";

                        alert($string);
                    }
                    else {

                        $(elem).data('__persisted', true);

                        var ul = $(elem).parents('ul');

                        var index = $(ul).children('li').index($(elem).parent());
                        console.log("index: ", index);

                        if (null !== $(ul).data('activeIndex')
                            && index === $(ul).data('activeIndex'))
                            return false;

                        console.log("activeIndex..??");

                        $(ul)
                            .find('input[type=radio]')
                            .parent('li')
                            .removeClass('active');

                        $(elem)
                            .parent('li')
                            .addClass('active');

                        var notificationAnimation = function (fadeOutCallback) {
                            $(ul)
                                .next('.notification-entry-saved')
                                .children('div')
                                .fadeIn('medium', function () {
                                    var _self = this;
                                    setTimeout(function () {
                                        $(_self)
                                            .fadeOut('slow', fadeOutCallback);
                                    }, 1200);
                                });
                        };

                        var selectionSlideUp = function (self) {
                            var activeItemOffsetTop = index * self.$itemHeight;

                            $(ul)
                                .css('margin-top', (activeItemOffsetTop) + 'px')
                                .animate({
                                    marginTop: 0
                                }, 200 * index, function () {
                                    notificationAnimation(function () {

                                        $(ul)
                                            .parents('fieldset.did-you')
                                            .append('<button class="e20r-button edit-selection">Edit check-in</button>');

                                        $(document).on('click', 'fieldset.did-you .edit-selection', function () {
                                            console.log("Clicked the edit-selection button");
                                            console.log("This = ", this);
                                            console.log("Self = ", self);
                                            self.editCheckin(this);
                                        });

                                    });
                                });
                        };

                        var once = 0;

                        $(ul)
                            .children('li')
                            .not('.active')
                            .fadeOut('medium', function () {
                                if (1 === once) // only run this callback once
                                    return;

                                selectionSlideUp(self);

                                once = 1;
                            });

                        $(ul)
                            .data('activeIndex', index)
                            .data('updateMode', true);

                        // console.log("Set .data: ");
                    }
                },
                error: function( HXR, textStatus, errorThrown ) {
                    window.console.log("Error: " + errorThrown + ", status: " + textStatus );
                },
                complete: function() {
                    self.body.removeClass( 'loading' );
                }
            });
            /*
            $.post(e20r_action.ajaxurl, $data, function (response) {

                if (!response.success) {

                    var $string;
                    $string = "An error occurred when trying to save your choice to the database. If you\'d like to try again, reload ";
                    $string += "this page, and click your selection once more. \n\nIf you get this error a second time, please contact Technical Support by using our Contact form ";
                    $string += "at the top of this page.";

                    alert($string);
                }
                else {

                    $(elem).data('__persisted', true);

                    var ul = $(elem).parents('ul');

                    var index = $(ul).children('li').index($(elem).parent());
                    console.log("index: ", index);

                    if (null !== $(ul).data('activeIndex')
                        && index == $(ul).data('activeIndex'))
                        return false;

                    console.log("activeIndex..??");

                    $(ul)
                        .find('input[type=radio]')
                        .parent('li')
                        .removeClass('active');

                    $(elem)
                        .parent('li')
                        .addClass('active');

                    var notificationAnimation = function (fadeOutCallback) {
                        $(ul)
                            .next('.notification-entry-saved')
                            .children('div')
                            .fadeIn('medium', function () {
                                var _self = this;
                                setTimeout(function () {
                                    $(_self)
                                        .fadeOut('slow', fadeOutCallback);
                                }, 1200);
                            });
                    };

                    var selectionSlideUp = function (self) {
                        var activeItemOffsetTop = index * self.$itemHeight;

                        $(ul)
                            .css('margin-top', (activeItemOffsetTop) + 'px')
                            .animate({
                                marginTop: 0
                            }, 200 * index, function () {
                                notificationAnimation(function () {

                                    $(ul)
                                        .parents('fieldset.did-you')
                                        .append('<button class="e20r-button edit-selection">Edit check-in</button>');

                                    $(document).on('click', 'fieldset.did-you .edit-selection', function () {
                                        console.log("Clicked the edit-selection button");
                                        console.log("This = ", this);
                                        console.log("Self = ", self);
                                        self.editCheckin(this);
                                    });

                                });
                            });
                    };

                    var once = 0;

                    $(ul)
                        .children('li')
                        .not('.active')
                        .fadeOut('medium', function () {
                            if (1 === once) // only run this callback once
                                return;

                            selectionSlideUp(self);

                            once = 1;
                        });

                    $(ul)
                        .data('activeIndex', index)
                        .data('updateMode', true);

                    // console.log("Set .data: ");
                }

            });

            self.body.removeClass("loading");
            */
        },
        editCheckin: function (elem) {

            var button = $(elem);

            $(button)
                .parents('fieldset.did-you')
                .find('ul')
                .find('li')
                .removeClass('concealed')
                .css('display', 'block');

            $(button)
                .remove();

        },
        dayNav: function (self, elem) {

            event.preventDefault();

//            body.addClass("loading");

            var navDay = $(elem).next("input[name^='e20r-action-day']").val();
            var today = $('#e20r-action-today').val();

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

            $.ajax({
                url: e20r_action.ajaxurl,
                type: 'POST',
                timeout: e20r_action.timeout,
                data: data,
                beforeSend: function() {
                    window.console.log("Add the overlay");
                    self.body.addClass( 'loading ');
                },
                success: function (response) {

                    // console.log("Response: ", response);
                    var $string;

                    if (response.success) {

                        self.$header_tag.html(response.data);
                        self.$allowActivityOverride = true;

                        self.init();

                        console.log("Re-init for Note object(s)");
                        $(Note.init());
                    }
                    else {
                        console.log("success == false returned from AJAX call");

                        if (1 === parseInt( response.data.ecode )) {

                            console.log("Give the user an error message");

                            self.$allowActivityOverride = false;

                            $string = "Sorry, we're not quite ready to show you that yet...\n\n";
                            $string += "If you want see what tomorrow\'s workout is, ";
                            $string += "head over to the Archives.";

                            alert($string);
                        }

                        if (2 === parseInt( response.data.ecode ) ) {

                            self.$allowActivityOverride = false;

                            $string = "This day that you are on is the first day of the program.\n\n";
                            $string += "There is no past beyond this! Feel free to try to navigate into ";
                            $string += "the (relative) future from here!";

                            alert($string);

                        }

                        if (3 === parseInt( response.data.ecode ) ) {
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
                    self.body.removeClass( 'loading' );
//                    body.removeClass("loading");
                }
            });

            /*
             $.post(e20r_action.ajaxurl, data, function(response) {

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

             $('#e20r-daily-progress').html(response.data);

             self.bindProgressElements( self );

             }
             });

             body.removeClass("loading");
             */

//            body.removeClass("loading");
        },
        toAction: function (elem, self) {

            console.log("Clicked the 'Read more' link for the action");
            var $action_link = $(elem);

            console.log("Link for action link: ", $action_link.attr('href'));

            var data = {
                'e20r-action-nonce': self.$nonce,
                'for-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
            };

            console.log("Data to possibly send to action page: ", data);

            $.redirect($action_link.attr('href'), data);
        },
        toActivity: function (self) {

            console.log("Processing the 'Read more' link for the activity");

            var $url;
            var data = {
                'e20r-action-nonce': self.$nonce,
                'for-date': self.$checkinDate,
                'article-id': self.$checkinArticleId,
                'program-id': self.$checkinProgramId,
                'activity-id': $("#e20r-activity-activity_id").val(),
                'activity-override': self.$allowActivityOverride
            };

            if ( typeof e20r_workout !== 'undefined') {
                $url = e20r_workout.activity_url;
            }

            if ( typeof e20r_action !== 'undefined') {
                $url = e20r_action.activity_url;
            }

            console.log("Using URL: ", $url);
            $.redirect($url, data );
        }
    };

    var e20rDailyProgress = {
        init: function (assignmentElem) {

            console.log("Init of e20rDailyProgress() class.");
            this.body = $('body');
            this.$assignment = assignmentElem;
            this.$saveAssignmentBtn = this.$assignment.find("#e20r-assignment-save");
            this.$answerForm = this.$assignment.find("form#e20r-assignment-answers");
            this.$checkinBtn = this.$assignment.find("button#e20r-lesson-complete");
            this.$inputs = this.$answerForm.find('.e20r-assignment-response');
            this.checkcell_yes = this.$answerForm.find('.e20r-yes-checkbox');
            this.checkcell_no = this.$answerForm.find('.e20r-no-checkbox');
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

            this.checkcell_no.unbind('click').on('click', function() {

                var checkbox_yes = $(this).closest('tr').find('td.e20r-yes-checkbox > input.e20r-assignment-response');
                if (true === checkbox_yes.is(':checked')) {
                    window.console.log("Uncheck the 'yes' checkbox" );
                    checkbox_yes.removeAttr('checked');
                }
            });

            this.checkcell_yes.unbind('click').on('click', function() {

                var checkbox_no = $(this).closest('tr').find('td.e20r-no-checkbox > input.e20r-assignment-response');
                if (true === checkbox_no.is(':checked')) {
                    window.console.log("Uncheck the 'no' checkbox" );
                    checkbox_no.removeAttr('checked');
                }
            });

            this.$inputs.each(function () {
                console.log("Working through response fields");

                $(this).focus(function () {

                    console.log("Gave focus to", this);
                    $('#e20r-assignment-save-btn').show();
                    $('.e20r-assignment-complete').each(function () {
                        $(this).hide();
                    });
                });
            });

            this.$choices.each(function () {

                console.log("Working through likert fields");

                $(this).on('click', function () {

                    console.log("Gave focus to", this);
                    $('#e20r-assignment-save-btn').show();
                    $('.e20r-assignment-complete').each(function () {
                        $(this).hide();
                    });
                })
            });

            this.$multichoice.each(function () {

                console.log("Working through multichoice fields");
                $(this).on('select2:open', function () {

                    console.log(" Opening select2 entry.");
                    console.log("Clicked on:", this);

                    $('#e20r-assignment-save-btn').show();
                    $('.e20r-assignment-complete').each(function () {
                        $(this).hide();
                    });
                })
            });

        },
        lessonComplete: function (self) {

            event.preventDefault();
            console.log("lessonComplete()...");

            $.ajax({
                url: e20r_action.ajaxurl,
                type: 'POST',
                timeout: e20r_action.timeout,
                /* data: 'action=save_daily_checkin&' + self.$answerForm.serialize(), */
                data: 'action=e20r_save_daily_progress&' + self.$answerForm.serialize(),
                beforeSend: function() {
                    self.body.addClass( 'loading ');
                },
                success: function ($response) {

                    $('#e20r-lesson-complete').hide();
                    $(".e20r-lesson-highlight").hide();
                    $('.e20r-assignment-complete').each(function () {
                        $(this).show();
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

                },
                complete: function() {
                    self.body.removeClass('loading');
                }
            });

            return false;
        },
        saveAnswers: function (self) {

            event.preventDefault();

            var $mc_answers = $('.e20r-select2-assignment-options');

            $mc_answers.each(function () {

                var $resp_val = $(this);
                var $h_resp = $resp_val.siblings('.e20r-assignment-select2-hidden');

                console.log("Assignment answer(s) for this field: ", $resp_val.val());
                $h_resp.val(JSON.stringify($resp_val.val()));
                console.log("Stringified list of answers in multichoice: " + $h_resp.val());
            });

            var answers = self.$answerForm.serialize();
            console.log("saveAssignment(): " + answers, self.$answerForm);

            $.ajax({
                'type': 'POST',
                'url': e20r_action.ajaxurl,
                timeout: 10000,
                'data': 'action=e20r_save_daily_progress&' + answers,
                beforeSend: function() {
                    self.body.addClass( 'loading ');
                },
                success: function ($response) {

                    $('#e20r-assignment-save-btn').hide();
                    $('.e20r-assignment-complete').each(function () {
                        $(this).show();
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
                },
                complete: function() {
                    self.body.removeClass('loading');
                }
            });

            return false;
        }
    };

    var Note = {
        init: function () {
            this.body = $('body');
            this.noteField = $('fieldset.notes');
            this.actionFields = $('fieldset.did-you.habit');
            this.activityFields = $('fieldset.did-you.workout');

            this.note_id = this.noteField.find('.e20r-action-id').val();
            this.checkin_shortname = this.noteField.find('.e20r-action-checkin_short_name').val();
            this.note_article = $('#e20r-action-article_id').val();
            this.note_assignment = $('#e20r-action-assignment_id').val();
            this.note_program = $('#e20r-action-program_id').val();
            this.note_date = $('#e20r-action-checkin_date').val();
            this.note_actualdate = $('#e20r-action-checkedin_date').val();
            this.checkin_type = this.noteField.find('.e20r-action-checkin_type').val();
            this.checkin_value = this.actionFields.siblings('input[name^="did-action-today"]:checked').val();

            var self = this;

            // console.log('Note object: ', self);

            $('#note-textarea').autoGrow();

            self.noteValOnLoad = $('#note-textarea').val().strip();
            self.hadValPreviously = bool(self.noteValOnLoad);

            setTimeout(function () {

                $('#note-textarea')
                    .css('height', function() {

                        var height;

                        if ( self.hadValPreviously ) {

                            height = $('#note-textarea').height() + 'px';
                        }
                        else {
                            height = '4em';
                        }

                        return height;
                    });

                $('#note-display')
                    .css('height', function() {

                        var noteArea = $('div#e20r-action-notes').closest('.e20r-daily-action-row').outerHeight();
                        console.log("Note area height: ", noteArea );
                        return noteArea + 'px';
                    })
                    .css('color', 'rgba(92, 92, 92, 0.9 )')
                    .css('background-size', '40px 40px');

                $('fieldset.notes #note-display>div')
                    .css('padding-bottom', '20px');
            }, 200);

            if ( self.noteValOnLoad.length !== 0 ) {

                self._stickyNoteOverlay();
            }

            $('#save-note')
                .bind('attempt_save_with_empty_textarea', function () {
                    var $this = $(this);
                    var $noteTextarea = $('#note-textarea');

                    $this
                        .addClass('tooltip-handle')
                        .attr('data-tooltip', 'Enter your notes in the text area above')
                        .data('__attemptedSaveOnEmpty', true);

                    Tooltip.event.mouseover.call(this, {}, {pos: 'left'});
                    Tooltip.unbindHandle(this, {timeout: 1500});
                });

            $('#save-note').unbind('click').on( 'click', function() {
                self.save_note( self );
            }); // click

            $(window).resize( function() {

                setTimeout(function () {
                    self._stickyNoteOverlay(0);
                }, 200 );
            });

        },
        save_note: function ( self ) {

            console.log("Content of 'self': ", self);

            var $elem = $('#save-note');
            var $noteTextarea = $('#note-textarea');

            var notificationTimeout;

            // the note field is empty, and didn't have a value previously
            if (! self.hadValPreviously
                && '' === $noteTextarea.val().strip()) {

                console.log("Trigger the save operation while the textarea is empty logic");
                return $elem.triggerHandler('attempt_save_with_empty_textarea');
            }

            // save
            if (false === bool($elem.data('editMode'))) {

                // Unassigned (which is ok.
                if (self.note_assignment === '') {

                    self.note_assignment = 0;
                }

                // No defined article for this date/delay value (which is ok)
                if (self.note_article === '') {

                    self.note_article = 0;
                }

                var data = {
                    action: 'e20r_saveCheckin',
                    'e20r-action-nonce': $('#e20r-action-nonce').val(),
                    'action-id': self.note_id,
                    'checkin-short-name': self.checkin_shortname,
                    'checkin-date': self.note_date,
                    'checkedin-date': self.note_actualdate,
                    'assignment-id': self.note_assignment,
                    'descr-id': self.note_assignment,
                    'article-id': self.note_article,
                    'program-id': self.note_program,
                    'checkin-note': base64.encode($noteTextarea.val()),
                    'checkedin': self.checkin_value,
                    'checkin-type': self.checkin_type
                };

                console.log("Note --- Sending: ", data );

                $.ajax({
                    url: e20r_action.ajaxurl,
                    type: 'POST',
                    timeout: e20r_action.timeout,
                    dataType: 'JSON',
                    data: data,
                    beforeSend: function() {
                        self.body.addClass( 'loading ');
                    },
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

                            $('fieldset.notes')
                                .find('.notification-entry-saved')
                                .children('div')
                                .fadeIn('medium', function () {

                                    var me = this;
                                    notificationTimeout = setTimeout(function () {
                                        $(me)
                                            .fadeOut('slow');
                                    }, 1000);
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
                        console.log("Save operation is complete for user note");
                    }
                });

                $elem.data('__attemptedSaveOnEmpty', null);

            } else {

                self._setEditState();
                window.console.log("Changing edit state for note section");
            }
        },
        edit_note: function() {

            var $class = this;
            var $html = $('#note-textarea').val();

            var divHtml = $('div#note-display').html();
            var editableText = $('<textarea />');

            editableText.val(divHtml);

            console.log("Content of editableText: ", editableText );
            $('div#note-display').replaceWith(editableText);

            editableText.blur($class.note_text_blurred);
        },
        note_text_blurred: function() {

            var html = $('#note-textarea').val();
            var viewableText = $("<div>");

            viewableText.html(html);
            $(this).replaceWith(viewableText);
            viewableText.click($class.edit_note);
        },
        _setDisplayState: function () {

            var save_btn = $('#save-note');

            save_btn
                .data('editMode', true)
                .text('Edit Note')
                .trigger('blur');

            var noteDisplay = $('#note-display');
            var noteText = $('#note-textarea');

            var overflowHeight = (noteText.outerHeight() - noteDisplay.outerHeight());

            console.log("Setting height for overflow: ", overflowHeight );
            $('#note-display-overflow-pad')
                .height(overflowHeight);

            if ( noteText.hasClass('coaching-notice') ) {
                console.log("The note text is being hidden for the coach");
                save_btn.attr('disabled', true );
                save_btn.text('No Access');
            }

            noteText.focus();
            var v= noteText.val();
            noteText.val('');
            noteText.val(v);
        },
        _stickyNoteOverlay: function (fadeSpeed) {

            fadeSpeed = fadeSpeed || 0;

            var $class = this;
            var t_height;
            var note = $('#note-textarea')[0];

            if ('' == $(note).val().strip()) {
                return;
            }

            $('#note-display')
                .children('div')
                .html(function () {
                    return $('#note-textarea').val().replace(/[\r\n]/g, '<br />') + '<span id="note-para-end"></span>';
                })
                .end()
                .css('width', function() {

                    var t_width = $('div.e20r-action-notes').innerWidth();
                    var d_width = $('#note-display').outerWidth();
                    var n_width = $( 'div.e20r-action-notes').outerWidth();

                    n_width = int( n_width - 20 );

                    console.log("Width of textarea: ", t_width );
                    console.log("Width of note area: ", n_width );

                    return n_width + 'px';
                    // return '85%';
                })
                .css('height', function () {

                    var textarea = $('#note-textarea');
                    var area = $('fieldset.notes').closest('.e20r-action-notes');
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

                    // var t_height = $('#note-textarea').outerHeight();
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
                // .css('padding-left', '15px')
                .css('text-overflow', 'ellipsis')
                .css( 'text-align', 'left' )
                .css( 'margin-top', '-1px')
                .fadeIn(fadeSpeed);

            // $('#note-textarea').height( outer_height );

            $class._setDisplayState();
            $class.body.removeClass("loading");

            return true;
        },
        _setEditState: function () {
            $('#note-display')
                .fadeOut('fast');

            $('#note-textarea')
                .trigger('focus');

            $('#save-note')
                .data('editMode', false)
                .text('Save Note');

            $('#note-display-overflow-pad')
                .height(0);
        }
    };

    if (e20rCheckinEvent.is_running) {
        return;
    }

    var art_assignment = $('#e20r-article-assignment');
    if (art_assignment.length > 0) {

        $(e20rDailyProgress.init(art_assignment));
    }

    if ($("#e20r-daily-progress").length > 0) {

        $(e20rCheckinEvent.init());
    }

    if ($("textarea#note-textarea").length > 0) {

        $(Note.init());
    }
});
