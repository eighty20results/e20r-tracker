/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 *
 *  @since 0.8.9
 */

jQuery.noConflict();

var $body = jQuery('body');

var e20rClientAssignment = {
    init: function () {

        this.assignment_replies = jQuery('.e20r-message-history-content');
        this.new_message_alert = jQuery('.e20r-new-message-alert');
        this.current_status_row = null;
        this.page_name = jQuery('#e20r-page-name').val();

        if (typeof this.longpoll_active === 'undefined') {
            this.longpoll_active = false;
        }

        if (typeof e20r_admin !== 'undefined') {

            this.url = ajaxurl;
            this.ajax_timeout = e20r_admin.timeout;
            this.ticks_to_skip = e20r_admin.ticks_to_skip;
            this.longpoll_timeout = e20r_admin.longpoll_timeout;
            this.coach_message_nonce = e20r_admin.coach_message_nonce;
            this.clientId = e20r_admin.clientId;

        } else if (typeof e20r_assignments !== 'undefined') {

            this.url = e20r_assignments.ajaxurl;
            this.ajax_timeout = e20r_assignments.timeout;
            this.ticks_to_skip = e20r_assignments.ticks_to_skip;
            this.longpoll_timeout = e20r_assignments.longpoll_timeout;
            this.coach_message_nonce = e20r_assignments.coach_message_nonce;
            this.clientId = e20r_assignments.clientId
        } else {
            window.console.log("Required localization not present!!!");
            return false;
        }

        this.timeout = this.longpoll_timeout;

        window.console.log("Timeout for long poll: " + this.timeout);

        var self = this;

        // reset all event handlers
        self._bind();
    },
    _cancel_polling: function () {

        // var self = this;

        if ( false !== this.longpoll_active) {
            clearInterval(this.longpoll_active);
        } else {
            window.console.log("No interval function for polling present?!?");
        }
    },
    _bind: function () {

        window.console.log("Running _bind() for e20rClientAssignment class");

        var self = this;

        if ( typeof self.page_name  !== 'undefined' && self.page_name.length > 0 ) {
            jQuery("div#e20r-" + self.page_name ).find('nav.e20r-pagination-links a.page-numbers').unbind('click').on('click', function() {

                var link = jQuery(this);

                self.pagination( self.page_name, link );
            });
        }

        jQuery('.e20r-assignment-reply_area').each(function () {

            jQuery(this).autoGrow({
                extraLine: true // Adds an extra line at the end of the textarea. Try both and see what works best for you.
            });
        });

        jQuery('table.e20r-measurement-table > tbody > tr').each(function () {

            var repl_entry = jQuery(this).find('td.e20r-coach-reply');
            var answer = repl_entry.find('div.message-history-content .e20r-message-content');

            if (answer.length) {
                window.console.log("Running through message history #" + answer.sibling('input[name^="e20r-assignment-assignment_id"]').val());
                var top = answer.find('.e20r-message-most-recent');

                var my_user_id = answer.closest('.e20r-message-history-content').find('input[name^="e20r-assignment-user_id"]').val();
                var sender = top.find('input[name^="e20r-message-sent-by-hidden"]');

                var sender_id = sender.val();
                window.console.log("Did I send this message?", my_user_id, sender_id, top.closest('.e20r-message-history-content').find('input[name^="e20r-assignment_id"]').val());

                if (my_user_id === sender_id) {
                    sender.closest('div.e20r-message-history-message').hide();
                    sender.closest('tr.e20r-messages-new').removeClass('e20r-messages-new');
                }
            }
        });

        jQuery('#e20r-new-message-dismiss-button').unbind('click').on('click', function (event) {

            event.preventDefault();

            self.new_message_alert.fadeOut(900);
            window.console.log("User clicked the dismiss button for the new message warning");
            self.new_message_alert.data('e20rHideWarnings', 1);
        });

        // Poll for new coach/client messages after the page has loaded
        if (false === self.longpoll_active) {

            window.console.log("Polling based on interval: " + self.timeout);
            self.longpoll_active = setInterval( this.poll_msgs.bind(this), self.timeout);
        }
        /*
         jQuery("#TB_window,#TB_overlay,#TB_HideSelect").on('unload', function(){
         window.console.log("User closed box without sending...")
         self._clear_textbox( this );
         });
         */
        jQuery("#e20r-begin-btn").unbind('click').on('click', function(event) {

            event.preventDefault();

            jQuery("form#e20r-start").submit();
        });

        jQuery(".e20r-assignment-reply-link").unbind('click').on('click', function () {

            var reply_lnk = jQuery(this);
            self.current_status_row = reply_lnk.closest('td.e20r-coach-reply').closest('tr');

            setTimeout(function () {

                window.console.log("Link is: ", reply_lnk);

                var response_window = jQuery("#TB_ajaxContent");

                response_window.find("textarea.e20r-assignment-reply_area").val('');
                response_window.find("div.e20r-message-status-text").empty();

                var button = response_window.find('button.e20r-assignment-reply-button');

                button.unbind('click').on('click', function () {

                    window.console.log("User or coach clicked the 'Send/Save' button");
                    self.save_assignment_reply(this);
                });

                var answer_text = response_window.find('input[name^="e20r-assignment-answer"]').val();
                // var question_text = response_window.find('input[name^="e20r-assignment-question"]').val();
                window.console.log("User/Coach clicking the 'See messages' button: ", answer_text);

                if (typeof answer_text !== 'undefined') {

                    answer_text = answer_text.replace("\"", "'");

                    response_window.find('div.e20r-message-status-text').append(answer_text);
                    response_window.find('div.e20r-message-status-text').fadeIn();

                    self.assignment_replies = jQuery('.e20r-message-history-content');
                    response_window.find("textarea.e20r-assignment-reply_area").focus();
                }
                // self.update_read_status( reply_lnk );

            }, 500);
        });

        jQuery('#e20r-read-messages-btn').unbind('click').on('click', function () {

            var assignments = jQuery('div#profile-tabs.ct').data('codetabs');
            var messages_tab = jQuery('div#status-tabs.ct').data('codetabs');

            setTimeout(function () {
                self.new_message_alert.fadeOut();
            }, 4000);


            if ( typeof assignments !== 'undefined' ) {
                assignments.goTo(1);
            }

            if ( typeof messages_tab !== 'undefined' ) {
                messages_tab.goTo(1);
            }

            jQuery('tr.e20r-messages-new:first').focus();
        });

        self.assignment_replies.each(function () {

            var entry = jQuery(this);
            // var tab = entry.closest('#e20r-progress-assignments');
            var button = entry.find('.e20r-assignment-reply-button');
            var ack_button = entry.find('.e20r-message-ack-button');
            var reply = entry.find('.e20r-assignment-reply-link');

            window.console.log("Setting up button click events");

            ack_button.unbind('click').on('click', function () {

                window.console.log("User is archiving feedback message");
                self.update_archive_status(this);
            });

            button.unbind('click').on('click', function () {

                window.console.log("User or coach clicked the 'Send/Save' button");
                self.save_assignment_reply(this);
            });
        });
    },
    _update_status: function (message_ids, $status_type, button) {

        var $class = this;

        var data = {
            action: 'e20r_update_message_status',
            'e20r-assignment-nonce': jQuery('#e20r-assignment-nonce').val(),
            'message-id': JSON.stringify(message_ids),
            'status-type': $status_type,
            'message-status': 1
        };

        window.console.log("Data to send: ", data);

        jQuery.ajax({
            url: $class.url,
            timeout: $class.ajax_timeout,
            type: 'POST',
            dataType: 'JSON',
            data: data,
            success: function (res) {

                if (res.success) {

                    window.console.log("Message acknowledged: " + $status_type);
                    var message_container = button.closest('.e20r-message-content');
                    var message_history = message_container.closest('e20r-message-history-message');
                    var unread_messages = message_container.find('.e20r-message-history-unread');

                    message_history.removeClass('e20r-message-most-recent');

                    unread_messages.each(function () {

                        window.console.log("Updating message background color class");
                        jQuery(this).removeClass('e20r-message-history-unread')
                        jQuery(this).addClass('e20r-message-history');
                    })
                }
            },
            error: function ($response, $errString, $errType) {

                tb_remove();

                window.console.log("From server: ", $response);
                window.console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ('timeout' === $errString) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                if ($response.message !== '') {
                    $msg = $response.message;
                }
                var $string;
                $string = "An error occurred while trying to update the status for this message. If you\'d like to try again, please ";
                $string += "reload the page. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert($msg + $string);

                $class.$spinner.hide();
                return false;
            },
            complete: function () {

            }
        });
    },
    _update_row_status: function (history, $btn_text) {

        var $class = this;

        if (typeof $btn_text === 'undefined') {
            window.console.log("No button text specified. Using 'Review' as the default");
            $btn_text = 'Review';
        }

        var row = $class.current_status_row;

        window.console.log("Number of rows found: ", row.length);

        if (row.hasClass('e20rEven')) {
            row.css('background-color', '#e5e5e5');
        }

        if (row.hasClass('e20rOdd')) {
            row.css('background-color', '#c5c5c5');
        }

        if ($btn_text === 'nil') {

            row.find('.e20r-assignment-reply-link').hide();
        }
        else {
            window.console.log("Setting the button text to: ", $btn_text);
            row.find('.e20r-assignment-reply-link').text($btn_text);
        }
    },
    update_read_status: function (element) {

        var $class = this;
        var message_content;
        var reply_link;

        window.console.log("Element given to update_read_status(): ", element);

        if (!(element instanceof jQuery )) {
            element = jQuery(element);
        }

        var found_id = jQuery(element).find('input[name^="e20r-message-id"]').val();

        if (typeof found_id === 'undefined') {

            window.console.log("Running from thickbox");
            reply_link = jQuery('#TB_ajaxContent');
            message_content = reply_link.find('div.e20r-message-content');
        }
        else {
            window.console.log("Running from regular page");
            reply_link = jQuery(element);
            message_content = reply_link.closest('div.e20r-message-content');
        }

        var message_history = message_content.find('div.e20r-message-history-message');

        var message_ids = {};

        message_history.each(function () {
            window.console.log("message_history entry: ", this);
            message_ids.push(jQuery(this).find('input[name^="e20r-message-id"]').val());
        });

        window.console.log("Message IDs to process for is_read update: ", message_ids);

        if (message_ids.length !== 0) {

            window.console.log("Updating read status for: ", message_ids);
            $class._update_status(message_ids, 'read', message_history.find('.e20r-assignment-reply-button'));
        }
    },
    update_archive_status: function (button) {

        var $class = this;

        if (!(button instanceof jQuery )) {
            button = jQuery(button);
        }

        var message_ids = {};
        message_ids.push(button.closest('.e20r-message-history-unread, .e20r-message-history').find('input[name^="e20r-message-id"]').val());

        if (message_ids.length !== 0) {

            window.console.log("Updating archive status for message # ", message_ids);
            $class._update_status(message_ids, 'archive', button);

            button.closest('.e20r-message-history-unread').css('background-color', '#fbfbfb');
            button.fadeOut();
            setTimeout(function () {

                button.closest('.e20r-message-history-message').fadeOut();
            }, 3000);
        }
    },
    save_assignment_reply: function (element) {

        var $class = this;

        $body.addClass("loading");

        window.console.log("Attempting to save the reply from the coach to the DB");

        if (!( element instanceof jQuery )) {
            element = jQuery(element);
        }

        window.console.log("Element is: ", element);

        var top = element.closest('#TB_ajaxContent');
        var assignment_id = top.find("input[name^='e20r-assignment-assignment_id']").val();
        var article_id = top.find("input[name^='e20r-assignment-article_id']").val();
        // var message_date = top.find("input[name^='e20r-assignment-message_date']").val();
        var message_date = new Date().toISOString().slice(0, 19).replace('T', ' ');
        var program_id = top.find("input[name^='e20r-assignment-program_id']").val();
        var delay = top.find("input[name^='e20r-assignment-delay']").val();
        var recipient_id = top.find("input[name^='e20r-assignment-recipient_id']").val();
        var client_id = top.find("input[name^='e20r-assignment-client_id']").val();
        var sent_by_id = top.find("input[name^='e20r-assignment-sent_by_id']").val();
        var replied_to_id = top.find('input[name^="e20r-assignment-replied_to_id"]').val();
        var reply_text = top.find("textarea[name^='e20r-assignment-message']").val();

        window.console.log("Message container: ", top);

        window.console.log("Client ID: " + client_id + " and Sent-by ID: " + sent_by_id);

        if (client_id === sent_by_id) {
            $class.update_read_status(element);
        }

        var data = {
            action: 'e20r_add_reply',
            'e20r-assignment-nonce': jQuery('#e20r-assignment-nonce').val(),
            'assignment-id': assignment_id,
            'assignment-delay': delay,
            'article-id': article_id,
            'program-id': program_id,
            'message-date': message_date,
            'client-id': client_id,
            'recipient-id': recipient_id,
            'sent-by-id': sent_by_id,
            'replied-to': replied_to_id,
            'reply-text': reply_text
        };

        window.console.log("Data to send: ", data);

        jQuery.ajax({
            url: $class.url,
            timeout: $class.ajax_timeout,
            type: 'POST',
            dataType: 'JSON',
            data: data,
            success: function (res) {

                var message_area;

                tb_remove();

                if (( res.success )) {

                    window.console.log("Successfully saved response for " + recipient_id);
                    window.console.log("Clearing the text field");

                    top.find("textarea[name^='e20r-assignment-message']").val('');

                    window.console.log("Received data from save: ", res.data.message_history);
                    var $content = top.find('.e20r-message-content');

                    if (!$content.length) {

                        top.find('.e20r-message-history-content').find('textarea.e20r-assignment-reply_area').insertBefore('<div class="e20r-message-content"></div>');
                        message_area = top.find('.e20r-message-content');

                    } else {

                        message_area = $content;
                    }

                    window.console.log("Updating message history data...");
                    message_area.empty();
                    message_area.append(res.data.message_history);

                    window.console.log("Updating the status of the row - color & button text");
                    $class._update_row_status(message_area, 'Review');
                    // $class.update_read_status();

                    return true;
                }
            },
            error: function ($response, $errString, $errType) {

                tb_remove();

                window.console.log("From server: ", $response);
                window.console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ('timeout' === $errString) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                if ($response.message !== '') {
                    $msg = $response.message;
                }
                var $string;
                $string = "An error occurred while trying to send the message. If you\'d like to try again, please ";
                $string += "copy/paste your message somewhere safe, and reload the page. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert($msg + $string);

                $class.$spinner.hide();
                return false;
            },
            complete: function () {

                return true;
            }
        });

        $body.removeClass("loading");
    },
    reload_assignments: function (client_id) {

        window.console.log("Load Assignments (using AJAX) for user: " + client_id);

        var $class = this;

        var assignments_list = jQuery("div#e20r-progress-assignments");

        var data = {
            action: 'e20r_assignmentData',
            'e20r-assignment-nonce': jQuery('#e20r-assignment-nonce').val(),
            'client-id': client_id
        };

        jQuery.ajax({
            url: $class.url,
            timeout: $class.ajax_timeout,
            type: 'POST',
            dataType: 'JSON',
            data: data,
            success: function (res) {

                if (( res.success )) {

                    window.console.log("Refreshing assignment data due to new messages being added");

                    assignments_list.html(res.data.assignments);
                    return true;
                }
            },
            error: function ($response, $errString, $errType) {

                window.console.log("From server: ", $response);
                window.console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ('timeout' === $errString) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                if ($response.message !== '') {
                    $msg = $response.message;
                }
                var $string;
                $string = "An error occurred while trying to fetch assignment data. If you\'d like to try again, please ";
                $string += "reload the page. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert($msg + $string);

                $class.$spinner.hide();
                return false;
            },
            complete: function () {

                return true;
            }
        });


    },
    poll_msgs: function() {

        var $class = this;

        if (typeof $class === 'undefined') {
            window.console.log("Class instance is not in scope!");
            return;
        }

        var data = {
            action: 'e20r_coach_message',
            'e20r-message-nonce': $class.coach_message_nonce,
            'e20r-message-client-id': $class.clientId
        };

        window.console.log("Poll data: ", data);
        window.console.log("Timeout value for polling: ", $class.timeout);

        jQuery.ajax({
            url: $class.url,
            timeout: $class.timeout,
            type: 'POST',
            dataType: 'JSON',
            data: data,
            success: function (data) {

                window.console.log("Poll acknowledged", data);

                if (data.success) {

                    if (data.errormsg === 'no-client-provided') {
                        return;
                    }

                    if (data.e20r_new_messages > 0) {

                        window.console.log("Received expected property in response");

                        var new_messages = parseInt(data['e20r_new_messages']);
                        var old_count = parseInt( jQuery("#e20r-messages-previous-count").val() );

                        window.console.log("Old message count: ", old_count);
                        window.console.log("New message count: ", new_messages);

                        if (old_count < new_messages) {

                            jQuery('#e20r-messages-previous-count').val(new_messages);

                            var dismissed = $class.new_message_alert.data('e20rHideWarnings');
                            var client_id = jQuery("#e20r-message-user-id").val();

                            window.console.log("Dismissed value is: ", dismissed );

                            if (1 === dismissed) {

                                window.console.log("Another new message");
                                $class.reload_assignments(client_id);
                                $class.new_message_alert.fadeIn(1500);

                            }
                        }

                        if (old_count > new_messages) {

                            window.console.log("Reset the old 'new messages' counter to", new_messages);
                            jQuery("#e20r-messages-previous-count").val(new_messages);
                        }
                    }
                } else {
                    $class._cancel_polling();
                }
            },
            error: function ($response, $errString, $errType) {

                window.console.log("From server: ", $response);
                window.console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ('timeout' === $errString) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                if ($response.message !== '') {
                    $msg = $response.message;
                }

                var $string;

                $string = "An error occurred while checking for new messages. If you\'d like to try again, please ";
                $string += "reload the page. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using our Contact form.";

                $class._cancel_polling();

                alert($msg + $string);

                return false;
            },
            complete: function() {

            }
        });
    },
    pagination: function( $pagename, link ) {

        var self = this;

        event.preventDefault();

        var $page_element_id = 'div#e20r-' + $pagename;
        var lnk_array = link.attr('href').split('/');
        var page_num = lnk_array[(lnk_array.length - 1)];

        var data = {
            action: "e20r_paginate_" + $pagename,
            'e20r-assignment-nonce': jQuery('#e20r-assignment-nonce').val(),
            'client-id': jQuery('input#e20r-assignment-pagination-cid').val(),
            'page_num': page_num
        };

        jQuery.ajax({
            url: self.url,
            timeout: self.timeout,
            type: 'POST',
            dataType: 'JSON',
            data: data,
            success: function (response, textStatus, XMLHttpRequest) {

                window.console.log("Data returned for pagination links: ", data, $pagename);

                var htmlelem = jQuery($page_element_id);
                htmlelem.replaceWith(response.data.assignments);

                self._bind();
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                window.alert(errorThrown);
            }
        });
    }
};

function setSurveyState(me) {
    var elem = jQuery(me),
        input = elem.is('td.e20r-assignment-ranking-question-choice') ? elem.find('input') : elem;

    if (input.is(':disabled'))
        return false;

    input.prop('checked', true);
    input.closest('tr').find('.e20r-assignment-ranking-question-choice-selected').removeClass('e20r-assignment-ranking-question-choice-selected');
    input.parent().addClass('e20r-assignment-ranking-question-choice-selected');
    input.focus().change();
}

jQuery(document).ready(function () {

    var $multichoice = jQuery('select.e20r-select2-assignment-options');

    if ($multichoice.length) {

        $multichoice.select2();
    }

    window.console.log("Loading Assignment Survey processing");

    var rankingQuestion = jQuery("table.e20r-assignment-ranking-question");

    if (rankingQuestion.length > 0) {

        window.console.log("Found a Ranking request in the assignment");

        window.console.log("Processing checkboxes and radio inputs");

        jQuery('td.e20r-assignment-ranking-question-choice').find('input[type="radio"], input[type="checkbox"]').each(function () {

            var $elem = jQuery(this);

            if ($elem.is(':checked')) {
                $elem.parent().addClass('e20r-assignment-ranking-question-choice-selected');
            }

        });

        rankingQuestion.find('td.e20r-assignment-ranking-question-choice, input[type="radio"], input[type="checkbox"]').on('click', function (e) {

            window.console.log("Found a survey choice in the survey question");

            setSurveyState(this);
        });

        // add a hover state
        jQuery("table.e20r-assignment-ranking-question td").on('hover', function (e) {

            window.console.log("User is hovering above ranking option. Changing color");

            if (jQuery(e.target).is("td.e20r-assignment-ranking-question-choice-label") || jQuery(this).find("input").is(':disabled')) {
                return false;
            } else {
                jQuery(this).addClass("e20r-assignment-ranking-question-choice-hover");
            }

        }, function (e) {
            if (jQuery(e.target).is("td.e20r-assignment-ranking-question-choice-label") || jQuery(this).find("input").is(':disabled')) {
                return false;
            } else {
                jQuery(this).removeClass("e20r-assignment-ranking-question-choice-hover");
            }

        });

        jQuery('table.e20r-assignment-survey-question-choice input[type="radio"], input[type="checkbox"]').on('focus', function () {
            jQuery(this).parent().addClass('e20r-assignment-ranking-question-choice-hover');
        }).on('blur', function () {
            jQuery(this).parent().removeClass('e20r-assignment-ranking-question-choice-hover');
        });

    }

    // e20rAssignmentsConfigureSurveyFields();

    if (typeof e20rClientAssignment != 'undefined') {

        // Reset any 'dismiss' requests on load.
        var new_message_alert = jQuery('.e20r-new-message-alert');
        new_message_alert.data('e20rHideWarnings', 0);

        window.console.log("Loading the assignment handler for clients");
        e20rClientAssignment.init();

        /*
        setTimeout(function () {

            window.console.log("Triggering initial heartbeat transmission");

            jQuery(document).trigger('heartbeat-send');
        }, 2000);
        */
    }

});
