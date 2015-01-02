
var weightchart;
var girthchart;

var weightDateArray = new Array();
var weightArray = new Array();

var girthTotalValue = new Array();

var skinfoldTotalValue = new Array();
var skinfoldDateArray = new Array();

jQuery(document).ready(function() {
    // Get the dates from the tables to use as X-axis labels

    weightchart = new Highcharts.Chart({
        chart: {
            renderTo: 'weightcontainer',
            type: 'spline',
            margin: [ 10,10,10,30]

        },
        title: {
            text: '',
            x: -20 //center
        },
        xAxis: {
            categories:  weightDateArray,
            labels: {
                enabled: false
            }
        },
        yAxis: {
            title: {
                text: ''
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
            },
            borderRadius: 0
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
            data: weightArray
        }
        ]
    });

    // chart for girths
    girthchart = new Highcharts.Chart({
        chart: {
            renderTo: 'girthcontainer',
            type: 'spline',
            margin: [ 10,10,10,30]

        },
        title: {
            text: '',
            x: -20 //center
        },
        xAxis: {
            categories:  weightDateArray,
            labels: {
                enabled: false
            }
        },
        yAxis: {
            title: {
                text: ''
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
            },
            borderRadius: 0

        },
        legend: {
            enabled: false,
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
});
