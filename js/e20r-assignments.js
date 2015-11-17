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

var e20rClientAssignment = {
    init: function() {

        this.assignment_replies = jQuery('.e20r-message-history-content');

        var self = this;

        self._bind();
    },
    _bind: function() {
        console.log("Running _bind() for e20rClientAssignment class");
        var self = this;

        jQuery(".e20r-assignment-reply-link").unbind('click').on('click', function() {
            console.log("Clicked the 'Respond to' button");
            self.assignment_replies = jQuery('.e20r-message-history-content');
        });

        self.assignment_replies.each(function() {

            var entry = jQuery(this);
            // var tab = entry.closest('#e20r-progress-assignments');
            var button = entry.find('button.e20r-assignment-reply-button');
            var reply = entry.find('a.e20r-assignment-reply-link');

            // console.log("Entry info:", this);

            /*
            tab.unbind('contentchanged').bind("contentchanged", function(){

                console.log("Content has changed for the assignments tab/page");
                self._bind();
            });
            */

            reply.unbind('click').on('click', function() {

                console.log("User or coach clicked the 'Reply' button");
            });

            button.unbind('click').on('click', function() {

                self.save_assignment_reply( this );
            })
        });


    },
    save_assignment_reply: function( element ) {
        console.log("Attempting to save the reply from the coach to the DB");

        if ( !( element instanceof jQuery ) ) {
            element = jQuery(element);
        }

        console.log("Element is: ", element);

        var top = element.closest('#TB_ajaxContent');
        var assignment_id = top.find("input[name^='e20r-assignment-assignment_id']").val();
        var article_id = top.find("input[name^='e20r-assignment-article_id']").val();
        var message_date = top.find("input[name^='e20r-assignment-message_date']").val();
        var program_id = top.find("input[name^='e20r-assignment-program_id']").val();
        var user_id = top.find("input[name^='e20r-assignment-user_id']").val();
        var reply_text = top.find("textarea[name^='e20r-assignment-message']").val();

        console.log("Message container: ", top);

        if ( typeof e20r_admin != 'undefined' ) {

            var url = ajaxurl;
            var ajax_timeout = e20r_admin.timeout;

        } else {

            var url = e20r_assignment.ajaxurl;
            var ajax_timeout = e20r_assignment.timeout;
        }

        var data = {
            action: 'e20r_add_reply',
            'e20r-assignment-nonce': jQuery('#e20r-assignment-nonce').val(),
            'assignment-id': assignment_id,
            'article-id': article_id,
            'program-id': program_id,
            'message-date': message_date,
            'user-id': user_id,
            'reply-text': reply_text
        };

        console.log("Data to transmit: ", data);

        jQuery.ajax({
            url: url,
            timeout: ajax_timeout,
            type: 'POST',
            dataType: 'JSON',
            data: data,
            success: function(res) {

                if ( ( res.success ) ) {

                    console.log("Successfully saved response for " + user_id );
                    return true;
                }
            },
            error: function( $response, $errString, $errType ) {


                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to fetch client information. If you\'d like to try again, please ";
                $string += "click the Load Information button once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

                $class.$spinner.hide();
                return false;
            },
            complete: function() {

                return true;
            }
        });
    }
}
function setSurveyState( me ) {
    var elem = jQuery( me ),
        input = elem.is( 'td.e20r-assignment-ranking-question-choice' ) ? elem.find( 'input' ) : elem;

    if( input.is( ':disabled' ) )
        return false;

    input.prop( 'checked', true );
    input.closest( 'tr' ).find( '.e20r-assignment-ranking-question-choice-selected' ).removeClass( 'e20r-assignment-ranking-question-choice-selected' );
    input.parent().addClass( 'e20r-assignment-ranking-question-choice-selected' );
    input.focus().change();
}

jQuery(document).ready(function() {

    var $multichoice = jQuery('select.e20r-select2-assignment-options');

    if ( $multichoice.length ) {

        $multichoice.select2();
    }

    console.log("Loading Assignment Survey processing");

    if (jQuery( "table.e20r-assignment-ranking-question").length > 0) {

        console.log("Found a Ranking request in the assignment");

        console.log("Processing checkboxes and radio inputs");

        jQuery('td.e20r-assignment-ranking-question-choice').find('input[type="radio"], input[type="checkbox"]').each(function() {

            var $elem = jQuery(this);

            if ( $elem.is(':checked') ) {
                $elem.parent().addClass( 'e20r-assignment-ranking-question-choice-selected' );
            }

        });

        jQuery( "table.e20r-assignment-ranking-question" ).find( 'td.e20r-assignment-ranking-question-choice, input[type="radio"], input[type="checkbox"]' ).on('click', function(e) {

            console.log("Found a survey choice in the survey question");

            setSurveyState( this );
            /*
            var elem = jQuery( this ),
                input = elem.is( 'td.e20r-assignment-ranking-question-choice' ) ? elem.find( 'input' ) : elem;

            if( input.is( ':disabled' ) )
                return false;

            input.prop( 'checked', true );
            input.closest( 'tr' ).find( '.e20r-assignment-ranking-question-choice-selected' ).removeClass( 'e20r-assignment-survey-question-choice-selected' );
            input.parent().addClass( 'e20r-assignment-ranking-question-choice-selected' );
            input.focus().change();
            */
        });

        // add a hover state
        jQuery("table.e20r-assignment-ranking-question td").on( 'hover', function(e){

            console.log("User is hovering above ranking option. Changing color");

            if (jQuery(e.target).is("td.e20r-assignment-ranking-question-choice-label") || jQuery(this).find("input").is(':disabled')) {
                return false;
            } else {
                jQuery(this).addClass("e20r-assignment-ranking-question-choice-hover");
            }

        }, function(e){
            if (jQuery(e.target).is("td.e20r-assignment-ranking-question-choice-label") || jQuery(this).find("input").is(':disabled')){
                return false;
            } else {
                jQuery(this).removeClass("e20r-assignment-ranking-question-choice-hover");
            }

        });

        jQuery( 'table.e20r-assignment-survey-question-choice input[type="radio"], input[type="checkbox"]' ).on( 'focus', function() {
            jQuery( this ).parent().addClass( 'e20r-assignment-ranking-question-choice-hover' );
        } ).on( 'blur', function() {
            jQuery( this ).parent().removeClass( 'e20r-assignment-ranking-question-choice-hover' );
        } );

    }

    // e20rAssignmentsConfigureSurveyFields();

    if ( typeof e20rClientAssignment != 'undefined') {

        console.log("Loading the assignment handler for clients");
        e20rClientAssignment.init();
    }

});