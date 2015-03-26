/**
 * Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
 * Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
 *
 * License Information:
 *  the GPL v2 license(?)
 */


/*
id, user_id,
    article_id, recorded_date,
    weight, neck,
    shoulder, chest,
    arm, waist,
    hip, thigh,
    calf, girth,
    essay1, behaviorprogress,
    front_image, side_image,
    back_image
*/

jQuery.noConflict();

var progMeasurements = {

    init: function( self, attrs ) {

        var $class = this;

        $class.clientId = attrs.id;
        $class.$measurements = self;
        $class.wPlot = null;
        $class.gPlot = null;

        if ( $class.clientId === null ) {

            console.log("We should be on a wp-admin page...");
            $class.$ajaxUrl = ajaxurl;

            // Used by admin UI only
            $class.$levelSelect = jQuery("#e20r-selectLevel");
            $class.$levelSelector = $class.$levelSelect.find('select#e20r_levels');
            $class.$levelId = $class.$levelSelector.find('option:selected').val();

            $class.$memberSelect = jQuery("#e20r-selectMember");
            $class.$memberSelector = $class.$memberSelect.find('#e20r_members')
            $class.$oldClientId = $class.$memberSelector.find('option:selected').val();

            $class.$spinner = jQuery('#spinner');
            $class.$btnRow = jQuery(".e20r-data-choices");
            $class.$hClientId = jQuery("#hidden_e20r_client_id");

            $class.$adminLoadBtn = jQuery("#e20r-load-data");

            $class.$detailBtn = jQuery("#e20r-client-info");
            $class.$complianceBtn = jQuery("#e20r-client-compliance");
            $class.$assignBtn = jQuery("#e20r-client-assignments");
            $class.$measureBtn = jQuery("#e20r-client-measurements");

            $class.$clientInfo = jQuery("#e20r-client-info");

            $class.$clientCompliance = jQuery("#e20r-client-compliance");

            $class.$clientAssignments = jQuery("#e20r-client-assignments");

            /* Configure admin page events */
            jQuery.bindEvents({
                self: $class,
                elem: $class.$levelSelect,
                events: {
                    change: function(self, e) {
                        self.loadMemberList();
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$memberSelector,
                events: {
                    click: $class.saveClientId,
                    change: function(self, e) {
                        console.log("Lading user data after drop-down change");
                        self.$spinner.show();

                        var $id = self.$memberSelector.find('option:selected').val();

                        self.saveClientId(self);
                        self.adminLoadData( $id )
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$measureBtn,
                events: {
                    click: function(self, e) {
                        console.log("Admin clicked Measurements button");
                        self.$spinner.show();
                        $class.$memberSelector = $class.$memberSelect.find('#e20r_members')
                        console.log("Value: ",  $class.$memberSelector.find('option:selected') );
                        var $id = $class.$memberSelector.find('option:selected').val();
                        self.saveClientId(self);
                        self.adminLoadData( $id )
                    }
                }
            })
            jQuery.bindEvents({
                self: $class,
                elem: $class.$adminLoadBtn,
                events: {
                    click: function(self, e) {
                        console.log("Loading data");
                        self.$spinner.show();

                        var $id = self.$memberSelector.find('option:selected').val();

                        self.saveClientId(self);
                        self.adminLoadData( $id )
                    }
                }
            });
        }
        else {
            $class.$ajaxUrl = e20r_progress.ajaxurl;
            $class.$spinner = jQuery('#spinner');

            $class.$spinner.show();
            jQuery(function(){
                jQuery("#status-tabs").tabs({
                    heightStyle: "content"
                });
            });

            setTimeout(function() {
                $class.loadMeasurementData();
            }, 10);

        }

        $class.$body = jQuery("body");

        jQuery(document).on({
            ajaxStart: function() { $class.$body.addClass("loading");    },
            ajaxStop: function() { $class.$body.removeClass("loading"); }
        });

    },
    adminLoadData: function(id) {

        console.log("in adminLoadData() for user with id: " + id);

        // $class.$memberSelect.prop("disabled", true);

        this.loadMeasurementData(id);

        // $class.$memberSelect.prop("disabled", false);
    },
    loadComplianceData: function(self) {

        console.log("Loading the Client checkin compliance data");
    },
    loadAssignmentData: function(self) {

        console.log("Loading the Client assignment data");
    },
    loadClientInfo: function(self) {
        console.log("Loading the Client Admin Info");
    },
    saveClientId: function(self) {

        var $currId = self.$memberSelector.find('option:selected').val();

        if ( $currId != self.$oldClientId ) {
            self.$hClientId.val($currId);
        }

    },
    loadMeasurementData: function( $clientId ) {

        var $class = this;

        if ( $class.loadMeasurementData.arguments.length != 1 ) {
            console.log("No arguments specified?");
            $clientId = $class.clientId
        };

        $class.$spinner.show();

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 5000,
            dataType: 'JSON',
            data: {
                action: 'e20r_measurementDataForUser',
                'e20r_tracker_client_detail_nonce': jQuery('#e20r_tracker_client_detail_nonce').val(),
                'h_dimension': jQuery("#h_dimension").val(),
                'w_dimension': jQuery("#w_dimension").val(),
                'h_dimension_type': jQuery("#h_dimension_type").val(),
                'w_dimension_type': jQuery("#w_dimension_type").val(),
                client_id: $clientId
            },
            error: function (data, $errString, $errType) {
                console.log($errString + ' error returned from e20r_measurementDataForUser action: ' + $errType, data );
                $class.$spinner.hide();

            },
            success: function (data) {

                if ( ( data.html !== '' ) ) {

                    $class.$measurements.html( data.html );
                    jQuery(".e20r_progress_text").html(null);
                    var firstDate;
                    var lastDate;

                    var $minTick;
                    var $maxTick;

                    var $entries;
                    var $stepSize;

                    console.log("Loading Weight chart");

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

                        $class.wPlot = jQuery.jqplot('weight_chart', [data.weight], {
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
                    else {
                        console.log("No Weight Data loaded...");
                    }

                    console.log("Loading Girth chart");
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

                        $class.gPlot = jQuery.jqplot('girth_chart', [data.girth], {
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
                        jQuery("div#inner-tabs").hide();
                        alert("No measurement data found");
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
                $class.$spinner.hide();
            }
        });
    },
    loadMemberList: function() {

        //var self = this;
        var $class = this;

        if ( $class.$levelSelector.find("option:selected").val() == -1 ) {
            console.log("No selection given. Ignore");
            return;
        }

        $class.$detailBtn.prop('disabled', true);
        $class.$complianceBtn.prop('disabled', true);
        $class.$assignBtn.prop('disabled', true);
        $class.$measureBtn.prop('disabled', true);

        $class.$levelId = $class.$levelSelector.find("option:selected").val();

        $class.$spinner.show();

        jQuery.ajax({
            url: this.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: {
                action: 'get_memberlistForLevel',
                'e20r-tracker-clients-nonce': jQuery('#e20r-tracker-clients-nonce').val(),
                'hidden_e20r_level': $class.$levelId
            },
            error: function (data, $errString, $errType) {

                console.log($errString + ' error returned from get_memberlistForLevel action: ' + $errType );
                console.log('Available data: ', data );
                alert(data.data);
            },
            success: function ($data) {

                console.log("Data returned from server: ", $data );

                // Refresh the sequence post list (include the new post.
                if ($data.data !== '') {

                    $class.$memberSelect.html($data.data);

                    console.log("Loading member select drop-down");
                    $class.$memberSelect.show();
                    $class.$btnRow.show();

                    $class.$detailBtn.prop('disabled', false);
                    $class.$complianceBtn.prop('disabled', false);
                    $class.$assignBtn.prop('disabled', false);
                    $class.$measureBtn.prop('disabled', false);
                }
            },
            complete: function () {

                $class.$spinner.hide();
                // $class.$memberSelector.prop('disabled', false);

            }
        });
    },
    loadProgressPage: function(link) {

        event.preventDefault();

        var $form = jQuery(link).parent();

        var $date = $form.children("input[name='e20r-progress-form-date']").val();
        var $article = $form.children("input[name='e20r-progress-form-article']").val();
        // var $program = $form.children("input[name='program_id']").val();

        console.log("Found date field: ", $date);
        console.log("Found article field: ", $article);
        // console.log("Found program field: ", $program);

        $form.submit( function () {
            jQuery('.load_progress_data').post(
                '/weekly-progress/',
                jQuery(this).serialize(),
                function(data){
                    alert("Data sent: ")
                }
            );
            return false;
        });
/*        jQuery().post(, $form.serialize(), function(data) {
                alert("Sent data");
            }); */
/*        $form.submit({
            'e20r-progress-form-date': $date,
            'e20r-progress-form-article': $article
        });*/
    }
};

jQuery(document).ready( function($) {

    if ( typeof e20r_progress !== 'undefined' ) {

        console.log("User ID is defined so we're working from the front-end");
        var $clientId = e20r_progress.clientId;
    }
    else {
        var $clientId = null;
    }

    progMeasurements.init( $('#e20r-progress-measurements'), { id: $clientId } );

});
