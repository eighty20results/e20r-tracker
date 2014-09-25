jQuery.noConflict();
jQuery(document).ready( function($) {


    console.log("WP script for E20R Tracker (client-side) loaded");

});

// TODO: How do I implement the jqplot for a shortcode...?
function e20r_load_measurements() {

    jQuery.ajax({
        url: e20r_tracker.ajaxurl,
        type: 'POST',
        timeout: 5000,
        dataType: 'JSON',
        data: {
            action: $action,
            e20r_client_detail_nonce: jQuery('#e20r_tracker_client_detail_nonce').val(),
            hidden_e20r_client_id: jQuery('#e20r_tracker_client').val()
        },
        error: function (data) {
            console.dir(data);
            // alert(data);

        },
        success: function (data) {

            // Refresh the sequence post list (include the new post.
            if ( ( data.data !== '' ) && ( $type == 'info') ) {
                jQuery('#e20r-info').html(data.data);
            }

            if ( ( data.data !== '' ) ) {

                jQuery('#e20r-measurements').html(data.data);

                var firstDate = data.weight[0][0];
                var lastDate = data.weight[data.weight.length - 1][0];

                var $minTick = firstDate - 604800000;
                var $maxTick = lastDate + 604800000;

                var $entries = data.weight.length;
                var $stepSize = '1 week';

                if ( $entries >= 26 ) {
                    $stepSize = '2 weeks';
                }

                var $wPlot = jQuery.jqplot( 'weight_chart', [ data.weight ], {
                    title: 'Weight History',
                    gridPadding:{right:35},
                    // legend: {show: false },
                    seriesDefaults: {
                        showMarker: false,
                        pointLabels: { show: false }
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
                            tickOptions: { formatString: '%.0f' }
                        }
                    },
                    series:[{
                        color: '#004DFF',
                        lineWidth:4,
                        markerOptions:{
                            style:'square'
                        },
                        rendererOptions: {
                            smooth: true
                        }
                    }]
                });

                var gPlot = jQuery.jqplot('girth_chart', [ data.girth ], {
                    title: 'Total Girth',
                    gridPadding:{right:35},
                    seriesDefaults: {
                        showMarker:false,
                        pointLabels: { show:false }
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
                            tickOptions: { formatString: '%.0f' }
                        }
                    },
                    series:[{
                        color: '#004DFF',
                        lineWidth:4,
                        markerOptions:{
                            style:'square'
                        },
                        rendererOptions: {
                            smooth: true
                        }
                    }]
                });

            }

        },
        complete: function () {

            // Enable the Save button again.
            // saveBtn.removeAttr('disabled');

            // Reset the text for the 'Save Settings" button
            // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

            // Disable the spinner again
            jQuery('#load-client-data').hide();
            $btn.removeAttr('disabled');
        }
    });
}

