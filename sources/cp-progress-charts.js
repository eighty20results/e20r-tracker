
var weightchart;
var girthchart;
var skinfoldchart;

var weightDateArray = new Array();
var weightArray = new Array();

var girthTotalValue = new Array();
var girthNeckValue = new Array();
var girthShoulderValue = new Array();
var girthChestValue = new Array();
var girthUpperarmValue = new Array();
var girthWaistValue = new Array();
var girthHipValue = new Array();
var girthThighValue = new Array();
var girthCalfValue = new Array();

var skinfoldTotalValue = new Array();
var skinfoldDateArray = new Array();
var skinfoldWeightValue = new Array();
var skinfoldAbdominalValue = new Array();
var skinfoldChestValue = new Array();
var skinfoldMidaxillaryValue  = new Array();
var skinfoldSubscapularValue = new Array();
var skinfoldSuprailiacValue = new Array();
var skinfoldThighValue  = new Array();
var skinfoldTricepsValue = new Array();

jQuery(document).ready(function() {
    jQuery('.graph-nav').click ( function () {
        var type = jQuery(this).attr('data-type');
        jQuery(this)
            .addClass('active')
            .siblings()
            .removeClass('active');
        switch (type) {
            case 'weight':
                jQuery('.highcharts-graph').hide(); // hide them all
                jQuery('#weightcontainer').fadeIn(200); // show weight graph
                break;
            case 'girth':
                jQuery('.highcharts-graph').hide(); // hide them all
                jQuery('#girthcontainer').fadeIn(200); // show girth graph
                break;
            case 'skinfold':
                jQuery('.highcharts-graph').hide(); // hide them all
                jQuery('#skinfoldcontainer').fadeIn(200); // show skinfold graph
                break;
        }
        if (type == 'skinfold') {
            jQuery('#skinfold-measurements-container')
                .insertBefore('#girth-measurements-container');
        }
        else {
            jQuery('#girth-measurements-container')
                .insertBefore('#skinfold-measurements-container');

        }
    });

    // Get the dates from the tables to use as X-axis labels
    jQuery('span.weightgirth-assignmentdate').each( function () {
        weightDateArray.push(jQuery(this).text());
    });
    jQuery('span.skinfold-assignmentdate').each( function () {
        skinfoldDateArray.push(jQuery(this).text());
    });

    // Get all body weight entries from the weight/girths table (also in skinfolds table, but not required)
    jQuery('td.body-weight').each( function () {
        var myweight = parseFloat(jQuery(this).text());
        if (isNaN(myweight) || myweight == 'NaN' || !isFinite(myweight))			{
            myweight = null;
        }
        weightArray.push(myweight);
    });

    // Get all of the girths

    jQuery('td.girth-neck-value').each( function () {
        girthNeckValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-shoulder-value').each( function () {
        girthShoulderValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-chest-value').each( function () {
        girthChestValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-upperarm-value').each( function () {
        girthUpperarmValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-waist-value').each( function () {
        girthWaistValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-hip-value').each( function () {
        girthHipValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-thigh-value').each( function () {
        girthThighValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-calf-value').each( function () {
        girthCalfValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.girth-total-value').each( function () {
        var mygirth = parseFloat(jQuery(this).text());
        if (isNaN(mygirth) || mygirth == 'NaN' || !isFinite(mygirth))
        {
            mygirth = null;
        }
        girthTotalValue.push(mygirth);
    });

    // Now get all of the skinfolds
    jQuery('td.skinfold-total-value').each( function () {
        var myskinfold = parseFloat(jQuery(this).text());
        if (isNaN(myskinfold) || myskinfold == 'NaN' || !isFinite(myskinfold))
        {
            myskinfold = null;
        }
        skinfoldTotalValue.push(myskinfold);
    });
    // probably don't need this one, but we're keeping it just in case
    jQuery('td.skinfold-weight-value').each( function () {
        skinfoldWeightValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-abdominal-value').each( function () {
        skinfoldAbdominalValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-triceps-value').each( function () {
        skinfoldTricepsValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-chest-value').each( function () {
        skinfoldChestValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-midaxillary-value').each( function () {
        skinfoldMidaxillaryValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-subscapular-value').each( function () {
        skinfoldSubscapularValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-suprailiac-value').each( function () {
        skinfoldSuprailiacValue.push(parseFloat(jQuery(this).text()));
    });
    jQuery('td.skinfold-thigh-value').each( function () {
        skinfoldThighValue.push(parseFloat(jQuery(this).text()));
    });

    weightchart = new Highcharts.Chart({
        chart: {
            renderTo: 'weightcontainer',
            type: 'spline',
            margin: [ 50, 50, 100, 80]

        },
        title: {
            text: 'Weight',
            x: -20 //center
        },
        xAxis: {
            categories:  weightDateArray
            ,
            labels: {
                rotation: -90,
                align: 'right',
                style: {
                }
            }
        },
        yAxis: {
            title: {
                text: 'Weight (' + CPUSER['weightunits'] + ')'
            },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        plotOptions: {
            series: {
                connectNulls: true
            }
        },
        tooltip: {
            formatter: function() {
                return '<b>'+ this.series.name +'</b><br/>'+
                    this.x +': '+ this.y +' ' + CPUSER['weightunits'];
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -10,
            y: 100,
            borderWidth: 0
        },
        credits: {
            enabled: false// ,
        },
        series: [{
            name: 'Weight',
            data: weightArray,
        }
        ]
    });

    // chart for girths
    girthchart = new Highcharts.Chart({
        chart: {
            renderTo: 'girthcontainer',
            type: 'spline',
            margin: [ 50, 50, 100, 80]

        },
        title: {
            text: 'Total girths (' + CPUSER['lengthunits'] + ')',
            x: -20 //center
        },
        xAxis: {
            categories:  weightDateArray
            ,
            labels: {
                rotation: -90,
                align: 'right',
                style: {
                }
            }
        },
        yAxis: {
            title: {
                text: 'Girths (' + CPUSER['lengthunits'] + ')'
            },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        plotOptions: {
            series: {
                connectNulls: true
            }
        },
        tooltip: {
            formatter: function() {
                return '<b>Total girths</b><br/>'+
                    this.x +': '+ this.y +' ' + CPUSER['lengthunits'];;
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -10,
            y: 100,
            borderWidth: 0
        },
        credits: {
            enabled: false// ,
        },
        series: [
            {
                name: 'Total',
                data: girthTotalValue
            }
        ]
    });

    // chart for skinfolds
    girthchart = new Highcharts.Chart({
        chart: {
            renderTo: 'skinfoldcontainer',
            type: 'spline',
            margin: [ 50, 50, 100, 80]

        },
        title: {
            text: 'Total skinfolds (mm)',
            x: -20 //center
        },
        xAxis: {
            categories:  weightDateArray
            ,
            labels: {
                rotation: -90,
                align: 'right',
                style: {
                }
            }
        },
        yAxis: {
            title: {
                text: 'Skinfolds (mm)'
            },
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        plotOptions: {
            series: {
                connectNulls: true
            }
        },
        tooltip: {
            formatter: function() {
                return '<b>Total skinfolds</b><br/>'+
                    this.x +': '+ this.y +' mm';
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -10,
            y: 100,
            borderWidth: 0
        },
        credits: {
            enabled: false// ,
        },
        series: [
            {
                name: 'Total',
                data: skinfoldTotalValue
            }
        ]
    });

});
