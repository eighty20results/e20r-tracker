/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery.noConflict();
jQuery(document).ready( function($) {

    $("#e20r-checkin-startdate").datepicker({
        dateFormat: 'y-m-d',
        onSelect: function( $dateStr, inst ) {

            var $days = parseInt( $("#e20r-checkin-maxcount").val() );

            if ( $days == 0 ) {
                $days = 14; // Default value
            }

            var $endDate = $.datepicker.parseDate('y-m-d', $dateStr );
            $endDate.setDate($endDate.getDate('y-m-d') + $days );

            $("#e20r-checkin-enddate").val($endDate.toLocaleDateString());
        }
    });
});
