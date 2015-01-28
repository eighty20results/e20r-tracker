/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery.noConflict();
jQuery(document).ready( function() {

    jQuery("#e20r-checkin-startdate").datepicker({
        dateFormat: 'yy-mm-dd',
        onSelect: function( $dateStr, inst ) {

            var $days = parseInt( jQuery("#e20r-checkin-maxcount").val() );

            console.log("Days: ", $days );

            if ( $days == null ) {
                $days = 14; // Default value
            }

            var nextDate = jQuery('#e20r-checkin-startdate').datepicker('getDate');
            nextDate.setDate( nextDate.getDate() + ( $days - 1 ) );

            jQuery("#e20r-checkin-enddate").datepicker('setDate', nextDate);

            // .val( nextDate.getFullYear()+ '-' + nextDate.getMonth() + 1 + '-' + nextDate.getDate() );
        }
    });

    jQuery("#e20r-checkin-enddate").datepicker({
        dateFormat: 'yy-mm-dd',
        onSelect: function( $dateStr, inst ) {

            var $startDate = new Date( jQuery('#e20r-checkin-startdate').val() );
            var $endDate = new Date( $dateStr );

            jQuery("#e20r-checkin-maxcount").val( ( Math.abs( $endDate - $startDate ) / 86400000 ) );
        }
    });

});
