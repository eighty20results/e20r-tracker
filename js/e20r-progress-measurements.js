/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */

jQuery(document).ready( function() {

    var mProgress = new progMeasurements( jQuery('#e20r-progress-measurements'), { id: e20r_progress.clientId } );

    mProgress.loadData();
});


jQuery(function(){
    var progMeasurements = {
        init: function( self, attrs ){

            this.clientId = attrs.id
        },
        loadData: function() {

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 5000,
                dataType: 'JSON',
                async: false,
                data: {
                    action: 'e20r_measurementDataForUser',
                    'e20r-client-detail-nonce': jQuery('#e20r_tracker_client_detail_nonce').val(),
                    client_id: this.clientId
                },
                error: function (data, $errString, $errType) {
                    console.log($errString + ' error returned from e20r_measurementDataForUser action: ' + $errType );
                    console.dir(data);

                },
                success: function (data) {

                    if ( ( data.data !== '' ) ) {

                        jQuery('#e20r-progr-measurements').html(data.data);

                        var firstDate;
                        var lastDate;

                        var $minTick;
                        var $maxTick;

                        var $entries;
                        var $stepSize;


                        if ( ( typeof data.weight !== 'undefined' ) && ( data.weight.length > 0 ) ) {

                            firstDate = data.weight[0][0];
                            lastDate = data.weight[data.weight.length - 1][0];

                            $minTick = firstDate - 604800000;
                            $maxTick = lastDate + 604800000;

                            $entries = data.weight.length;
                            $stepSize = '1 week';

                            if ($entries >= 26) {
                                $stepSize = '2 weeks';
                            }

                            var $wPlot = jQuery.jqplot('weight_chart', [data.weight], {
                                title: 'Weight History',
                                gridPadding: {right: 35},
                                // legend: {show: false },
                                seriesDefaults: {
                                    showMarker: false,
                                    pointLabels: {show: false}
                                },
                                axes: {
                                    xaxis: {
                                        renderer: jQuery.jqplot.DateAxisRenderer,
                                        labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                        tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                        tickOptions: {
                                            fontFamily: 'Verdana',
                                            fontSize: '9px',
                                            angle: 30,
                                            formatString: '%v',
                                            showLabel: true
                                        },
                                        showTicks: true,
                                        tickInterval: $stepSize,
                                        min: $minTick,
                                        max: $maxTick
                                    },
                                    yaxis: {
                                        labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                        tickOptions: {formatString: '%.0f'}
                                    }
                                },
                                series: [{
                                    color: '#004DFF',
                                    lineWidth: 4,
                                    markerOptions: {
                                        style: 'square'
                                    },
                                    rendererOptions: {
                                        smooth: true
                                    }
                                }]
                            });
                        }

                        if ( ( typeof data.girth !== 'undefined' ) && ( data.girth.length > 0 ) ) {

                            firstDate = data.girth[0][0];
                            lastDate = data.girth[data.girth.length - 1][0];

                            $minTick = firstDate - 604800000;
                            $maxTick = lastDate + 604800000;

                            $entries = data.girth.length;
                            $stepSize = '1 week';

                            if ($entries >= 26) {
                                $stepSize = '2 weeks';
                            }

                            var gPlot = jQuery.jqplot('girth_chart', [data.girth], {
                                title: 'Total Girth',
                                gridPadding: {right: 35},
                                seriesDefaults: {
                                    showMarker: false,
                                    pointLabels: {show: false}
                                },
                                axes: {
                                    xaxis: {
                                        renderer: jQuery.jqplot.DateAxisRenderer,
                                        labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                        tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                        tickOptions: {
                                            fontFamily: 'Verdana',
                                            fontSize: '9px',
                                            angle: 30,
                                            formatString: '%v',
                                            showLabel: true
                                        },
                                        showTicks: true,
                                        tickInterval: $stepSize,
                                        min: $minTick,
                                        max: $maxTick
                                    },
                                    yaxis: {
                                        labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                        tickOptions: {formatString: '%.0f'}
                                    }
                                },
                                series: [{
                                    color: '#004DFF',
                                    lineWidth: 4,
                                    markerOptions: {
                                        style: 'square'
                                    },
                                    rendererOptions: {
                                        smooth: true
                                    }
                                }]
                            });

                        }
                        else {

                            alert("This user doesn't have any data to graph yet");
                        }
                    }

                },
                complete: function () {

                    jQuery('.timeago')
                        .each(function() {
                            jQuery(this)
                                .text(function(text) {
                                    return jQuery.timeago(text);
                                });
                        });

                    jQuery("#e20r-measurement-table").delegate('td','mouseover mouseleave', function(e) {
                        if (e.type == 'mouseover') {
                            jQuery(this).parent().addClass("hover");
                            jQuery("colgroup").eq(jQuery(this).index()).addClass("hover");
                        }
                        else {
                            jQuery(this).parent().removeClass("hover");
                            jQuery("colgroup").eq(jQuery(this).index()).removeClass("hover");
                        }
                    });

                    // Disable the spinner again
                    jQuery('#load-client-data').hide();
                }
            });
        }
    }
});


