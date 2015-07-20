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

function e20rAssignmentsConfigureSurveyFields(){

    console.log("Do nothing...");
}

function setSurveyState( me ) {
    var elem = jQuery( me ),
        input = elem.is( 'td.e20r-assignment-survey-question-choice' ) ? elem.find( 'input' ) : elem;

    if( input.is( ':disabled' ) )
        return false;

    input.prop( 'checked', true );
    input.closest( 'tr' ).find( '.e20r-assignment-survey-question-choice-selected' ).removeClass( 'e20r-assignment-survey-question-choice-selected' );
    input.parent().addClass( 'e20r-assignment-survey-question-choice-selected' );
    input.focus().change();
}

jQuery(document).ready(function() {

    console.log("Loading Assignment Survey processing");

    if (jQuery( "table.e20r-assignment-survey-question").length > 0) {

        console.log("Found a survey question in the assignment");

        console.log("Processing checkboxes and radio inputs");

        jQuery('td.e20r-assignment-survey-question-choice').find('input[type="radio"], input[type="checkbox"]').each(function() {

            var $elem = jQuery(this);

            if ( $elem.is(':checked') ) {
                $elem.parent().addClass( 'e20r-assignment-survey-question-choice-selected' );
            }

        });

        jQuery( "table.e20r-assignment-survey-question" ).find( 'td.e20r-assignment-survey-question-choice, input[type="radio"], input[type="checkbox"]' ).on('click', function(e) {

            console.log("Found a survey choice in the survey question");

            setSurveyState( this );
            /*
            var elem = jQuery( this ),
                input = elem.is( 'td.e20r-assignment-survey-question-choice' ) ? elem.find( 'input' ) : elem;

            if( input.is( ':disabled' ) )
                return false;

            input.prop( 'checked', true );
            input.closest( 'tr' ).find( '.e20r-assignment-survey-question-choice-selected' ).removeClass( 'e20r-assignment-survey-question-choice-selected' );
            input.parent().addClass( 'e20r-assignment-survey-question-choice-selected' );
            input.focus().change();
            */
        });

        // add a hover state
        jQuery("table.e20r-assignment-survey-question td").on( 'hover', function(e){

            console.log("User is hovering above survey option. Changing color");

            if (jQuery(e.target).is("td.e20r-assignment-survey-question-choice-label") || jQuery(this).find("input").is(':disabled')) {
                return false;
            } else {
                jQuery(this).addClass("e20r-assignment-survey-question-choice-hover");
            }

        }, function(e){
            if (jQuery(e.target).is("td.e20r-assignment-survey-question-choice-label") || jQuery(this).find("input").is(':disabled')){
                return false;
            } else {
                jQuery(this).removeClass("e20r-assignment-survey-question-choice-hover");
            }

        });

        jQuery( 'table.e20r-assignment-survey-question-choice input[type="radio"], input[type="checkbox"]' ).on( 'focus', function() {
            jQuery( this ).parent().addClass( 'e20r-assignment-survey-question-choice-hover' );
        } ).on( 'blur', function() {
            jQuery( this ).parent().removeClass( 'e20r-assignment-survey-question-choice-hover' );
        } );

    }

    // e20rAssignmentsConfigureSurveyFields();
});