if ('' == CPUSER['weightunits']) {
    CPUSER['weightunits'] = 'lbs';
}

/* !TabNav */
var TabNav = {
    init: function() {
        var self = this;

        if (document.URL.indexOf('.php') != -1) {
            var tabs = {
                1: 'overview',
                2: 'habits',
                3: 'workouts',
                5: 'measurements',
                4: 'assignments',
                6: 'photos'
            };

            window.tab = tabs[parseQueryString(location.search)['t'] || 1];
        }
        else {
            window.tab = (document.URL.match(/\/progress\/([a-z]+)/) || ['blah', 'overview'])[1];

        }

        this._stageActiveTab(window.tab);
    },

    gotoTab: function(targetId) {
        jQuery('#app-tabs ul li a[rel=' + targetId + ']').trigger('click');
    },

    _stageActiveTab: function(tab) {

        var tabRel = 'profile-' + tab;

        jQuery('#app-tabs ul li a[rel=' + tabRel + ']')
            .addClass('active');

        var $activeTab = jQuery('#app-tabs a.active');

        jQuery('<span id="app-tab-flower-pixel"></span>')
            .appendTo('#app-tabs')
            .css('left', ($activeTab.position().left + 5 ) + 'px')
            .css('width', $activeTab.width() + 'px');
    }
};

var Overview = {
    init: function() {

    }
};

/* !PhotoCompare */
var PhotoCompare = {
    init: function() {
        //
        // timeago
        jQuery('.compare-control select')
            .find('option')
            .each(function() {
                jQuery(this)
                    .text(function(text) {
                        var date = text.match(/\(.+\)/)[0].replace(/[\(\)]/g, '');

                        var timeago = jQuery.timeago(date);

                        var pieces = text.split(/[\(\)]/g);

                        return pieces[0] + '(' + timeago + ')' + pieces[2];
                    });
            });

        // hasFlexedPics
        if (1 <= jQuery('.flexed-photo-set-container').length) {
            window.hasFlexedPics = 1;
        }

        //

        jQuery('#profile-photos-compare-launcher')
            .lightBoxCompare({
                overlayOpacity: .6,
                showPhotoNums: 0,
                heightPushPx: 82
            });

        function createLightboxDataHtml($photoContainer, lightboxEq) {
            var $photoLauncher = jQuery('#profile-photos-compare-launcher');

            var $lightbox = $photoLauncher.find('.lightboxDataHtml:eq(' + lightboxEq + ')');

            $lightbox
                .find('h3 .date')
                .html(function() {
                    var date = $photoContainer.prev('.app-fb-header').children('div').text();
                    date = date.replace(/Most recent\s.\s/i, '');

                    var timeago = $photoContainer.find('.date-for-humans').text();

                    return date + ' (<span class="timeago">' + timeago + '</span>)';
                });

            $lightbox
                .find('.info .value')
                .each(function(i) {
                    jQuery(this)
                        .text(function() {
                            return $photoContainer.find('.info-item:eq(' + i + ') span').text()
                        });
                });
        }

        function getPhotoHref($photoRowContainer, eq) {
            return $photoRowContainer
                .find('.photo-set-container a:eq(' + eq + ')')
                .attr('href');
        }

        function setPhotoWidth(href) {
            return href.replace(/w=[0-9]+/, 'w=449');
        }

        jQuery('.compare-button')
            .click(function() {

                var $photoLauncher = jQuery('#profile-photos-compare-launcher');

                var dval = jQuery(this).prev('select').val();

                var $thisPhotoContainer = jQuery(this).closest('.photo-row-container');
                var $thatPhotoContainer = jQuery('.photo-row-container.' + dval);

                try {
                    var dThis = int($thisPhotoContainer.attr('class').match(/d([0-9]+)/)[1]);
                    var dThat = int(dval.substr(1));

                    if (!dThis || !dThat)
                        throw('photo-row-container needs a class like \
								"d20090801" for the comparison feature to work');

                    // make sure the chronologically older photo is always on the left
                    if (dThis > dThat) {
                        $thatPhotoContainer
                            .find('.compare-control select')
                            .val('d' + dThis);

                        $thatPhotoContainer
                            .find('.compare-control button')
                            .trigger('click');

                        return false;
                    }
                }
                catch(err) {
                    console.log(err);
                }

                $photoLauncher.data('these', $thisPhotoContainer);
                $photoLauncher.data('those', $thatPhotoContainer);

                var thisPhotoSrc = getPhotoHref($thisPhotoContainer, 0);
                var thatPhotoSrc = getPhotoHref($thatPhotoContainer, 0);

                createLightboxDataHtml($thisPhotoContainer, 0);
                createLightboxDataHtml($thatPhotoContainer, 1);

                $photoLauncher.attr('href', setPhotoWidth(thisPhotoSrc));
                $photoLauncher.attr('data-href-compare', setPhotoWidth(thatPhotoSrc));



                $photoLauncher
                    .trigger('click');
            });
    }
};

/* !Photos */
var Photos = {
    init: function() {
        jQuery('#profile-photos div.photo')
            .hover(function() {
                jQuery(this)
                    .find('.magnifier-base')
                    .addClass('hover');
            }, function() {
                jQuery(this)
                    .find('.magnifier-base')
                    .removeClass('hover');
            });


        //

        var numPhotoRows = jQuery('.photo-row-container').length;

        if (!numPhotoRows) {

            var blah = parseQueryString(location.search)['m'] == 1 ? '' : '-w';

            jQuery('#profile-photos')
                .append('<div class="orange-notice rounded-corners" style="background-image: url(imagescpprofile/picture16.png);">\
				<strong style="display: block; font-size: 16px; margin-bottom: 4px;">No photos yet</strong>\
		   	 		Photo days are once a week.	Once you\'ve uploaded your first set of photos, they\'ll show up here.	Below is a preview of what to expect.\
		   	 		</div>')
                //.append('<img src="imagescpprofile/photo-set-preview.png" style="display: block; margin-bottom: 12px;"/>')
                .append('<img src="imagescpprofile/photo-set-preview' + blah + '.png"/>');
        }

        //

        var ymd = jQuery('.photo-row-container:first').find('.date-for-humans').attr('data-ymd').replace(/\//g, '-');



        jQuery('.photo-row-container:first')
            .filter('.missing-photo')
            //.css('background', 'none')
            .prepend('<div class="orange-notice photo-missing-upload-notice" style="margin-top: -8px; margin-bottom: 8px; border-right: none; border-left: none; border-top: none; background-image: url(imagescpprofile/picture_go16.png);">\
			It\'s not too late!  <a href="/coaching/weekly_update/' + ymd + '#photos">Upload photos for this day</a>.\
		</div>')
            .find('.compare-control')
            .hide()
            .end()
            .find('.photo')
            .css('height', '126px')
            .css('cursor', 'default')
            .click(function() {
                return false;
            })
            .find('.magnifier, .magnifier-base')
            .hide();

        // break the cache on the first row of photos
        // right now this causes "double loading" .. but will satisfice for the time being

        /*jQuery('.photo-row-container:first')
         .find('.photo')
         .each(function() {
         jQuery(this)
         .css('background-image', function(bimg) {
         return bimg.replace(')', '&rand=' + Math.random() + ')');
         });
         });*/
    }
};

function stripDeepLink(href) {
    var pos = href.indexOf('#');

    return (pos == -1)
        ? href : href.substr(0, pos);
}

/* !Assignments */
var Assignments = {
    init: function() {
        // assignment answers hover
        jQuery('.tally-table tbody td.assignment')
            .each(function() {

                if (jQuery.browser.msie) {
                    return false;
                }

                var hasLessonAnswers = (jQuery(this).find('.lesson-answers').length);

                if (!hasLessonAnswers) {
                    return true;
                }

                var html = jQuery(this).find('.lesson-answers').html();
                var tooltipText = base64.encode(html);

                jQuery('<img src="cp-images/report16.png" class="tooltip-handle base64" data-tooltip="' + tooltipText + '" data-tooltip-max-width="500" style="float: right; cursor: pointer;" />')
                    .click(function() {
                        jQuery(this).colorbox({ html: '<div style="font-size: 16px; padding: 16px; max-width: 900px;">' + html + '</div>' });
                    })
                    .prependTo(this);
            });


        var numAssignmentRows = jQuery('.tally-table tbody tr').length;

        if (!numAssignmentRows) {
            jQuery('#profile-assignments')
                .prepend('<img src="imagescpprofile/assignments-no-data-preview.png" style="display: block;"/>')
                .prepend('<div class="orange-notice no-icon rounded-corners" style="padding: 8px 24px; text-align: center; position: absolute; top: 64px; left: 232px; -moz-box-shadow: 0px 0px 2px #333;">\
		   	 		<strong style="display: block; font-size: 16px;">No data yet</strong>\
		   	 		</div>');


            jQuery('.tally-table tbody')
                .append('<tr><td colspan="3" style="padding: 8px;"><div style="background-image: url(imagescpprofile/page_white16.png); margin-bottom: 0pt;" class="orange-notice"><strong style="display: block; font-size: 16px; margin-bottom: 4px;">No data yet</strong>As you begin completing assignments, rows will begin showing up here.</div></td></tr>');

            jQuery('#profile-overview')
                .hide();
        }
    }
};

/* !Measurements */
var Measurements = {
    init: function() {
        var self = this;

        jQuery.address.change(function(e) {
            if (jQuery.browser.msie) {
                if (e.value.length > 1) {
                    e.value = e.value.substr(1);
                }
            }

            jQuery('.graph-nav').removeClass('active');

            switch(e.value) {
                case 'graph/girth':
                    jQuery('.highcharts-graph').hide(); // hide them all
                    jQuery('#girthcontainer').fadeIn(200); // show girth graph
                    jQuery('.girth-link').addClass('active');
                    break;
                case 'graph/skinfold':
                    jQuery('.highcharts-graph').hide(); // hide them all
                    jQuery('#skinfoldcontainer').fadeIn(200); // show skinfold graph
                    jQuery('.skinfold-link').addClass('active');
                    break;
                default:
                    jQuery('.highcharts-graph').hide(); // hide them all
                    jQuery('#weightcontainer').fadeIn(200); // show weight graph
                    jQuery('.weight-link').addClass('active');
                    break;
            }
        });

        jQuery('a[rel=lightbox-new]')
            .lightBoxProfilePhotos({
                overlayOpacity: .6,
                showPhotoNums: 0,
                heightPushPx: 82
            });

        // graph nav

        jQuery('.measurement-graph-nav a')
            .click(function(e) {
                e.preventDefault();

                var type = jQuery(this).attr('data-type');

                location.href = stripDeepLink(location.href) + '#graph/' + type; // trigger jQuery.address.change
            });

        //

        jQuery('.measurement-table tr')
            .hover(function() {
                jQuery(this)
                    .addClass('hover');
            }, function() {
                jQuery(this)
                    .removeClass('hover');
            });

        jQuery('.measurement-table td')
            .hover(function() {
                var index = jQuery(this).closest('tr').children('td').index(this);

                if (jQuery(this).is(':first-child')
                    || jQuery(this).is(':last-child'))
                    return;

                jQuery('.measurement-table tr')
                    .not(jQuery(this).closest('tr'))
                    .each(function() {
                        jQuery(this)
                            .children('td:eq(' + index	+ ')')
                            .addClass('column-hover');
                    });

                jQuery(this)
                    .addClass('hover');
            }, function() {
                var index = jQuery(this).closest('tr').children('td').index(this);

                if (jQuery(this).is(':first-child')
                    || jQuery(this).is(':last-child'))
                    return;

                jQuery('.measurement-table tr')
                    .not(jQuery(this).closest('tr'))
                    .each(function() {
                        jQuery(this)
                            .children('td:eq(' + index	+ ')')
                            .removeClass('column-hover');
                    });

                jQuery(this)
                    .removeClass('hover');
            });

        //

        var girthRows = jQuery('#girth-measurement-table tbody tr').length;
        var skinfoldRows = jQuery('#skinfold-measurement-table tbody tr').length;

        if (!girthRows) {
            // tables

            jQuery('#girth-measurement-table tbody')
                .append('<tr><td style="padding: 8px;">\
		   			<div class="orange-notice" style="background-image: url(imagescpprofile/page_white16.png); margin-bottom: 0;"><strong style="display: block; font-size: 16px; margin-bottom: 4px;">No data yet</strong>We collect your weight and girth information once a week. Once you\'ve input your first measurement, rows will begin showing up here.</div>\
		   			</td></tr>');

            // graphs

            jQuery('#measurement-graph-container')
                .addClass('no-data')
                .height(334)
                .prepend('<img src="imagescpprofile/measurement-weight-graph.png" class="graph-preview" />')
                .prepend('<div class="orange-notice no-icon rounded-corners" style="padding: 8px; text-align: center; position: absolute; width: 175px; top: 84px; left: 260px; -moz-box-shadow: 0px 0px 2px #333;">\
		   	 		<strong style="display: block; font-size: 16px; margin-bottom: 4px;">No data yet</strong>\
		   	 		After a couple of measurement days, a graph will be here displaying how your weight is changing over time.\
		   	 		</div>');

            jQuery('table.measurement-graph').remove();
        }

        if (!skinfoldRows) {
            jQuery('#skinfold-measurement-table tbody')
                .append('<tr><td style="padding: 8px;">\
		   			<div class="orange-notice" style="background-image: url(imagescpprofile/page_white16.png); margin-bottom: 0;"><strong style="display: block; font-size: 16px; margin-bottom: 4px;">No data yet</strong>We collect your skinfold information every three months. Once you\'ve input your first measurement, rows will begin showing up here.</div>\
		   			</td></tr>');
        }


        //


        jQuery('.measurement-table-head')
            .each(function() {

                var browsersWithGoodCSS3 = ['.browserFirefox35', '.browserFirefox36', '.browserChrome', '.browserSafari4'];

                var hasGoodCSS3 = jQuery('body').is(browsersWithGoodCSS3.join(', '));

                if (false === bool(hasGoodCSS3)) {
                    var which = jQuery(this).hasClass('girth') ?
                        'weight-girth' : 'skinfold';

                    var lengthunits = (which == 'skinfold') ? 'mm' : CPUSER['lengthunits'];

                    jQuery('<img src="cp-images/' + which + '-oblique-header-' + CPUSER['weightunits'] + '-' + lengthunits + '.png" />')
                        .css({
                            position: 'absolute',
                            zIndex: 2,
                            right: 0
                        })
                        .prependTo(this);

                    jQuery(this)
                        .find('span.th')
                        .hide();

                    return true;
                }


                var left = 113;

                if (jQuery.browser.safari) {
                    left += 1;
                }

                jQuery(this)
                    .find('span.th')
                    .each(function() {
                        left += jQuery(this).width() - 76;
                        jQuery(this)
                            .css('left', left + 'px')
                            .width(function(width) {
                                if (jQuery.browser.safari) {
                                    return width + 1;
                                }

                                return width;
                            });
                        jQuery(this).after('<span class="connector" style="left: ' + ( left ) + 'px"></span>');
                    })
            });
    },

    _changeGraph: function(type, graphFileURL) {

        if(jQuery('#measurement-graph-container')
                .hasClass('no-data')) {

            var img = {
                weight: 'measurement-weight-graph.png',
                girth: 'measurement-weight-graph-2.png',
                skinfold: 'measurement-weight-graph-3.png'
            }[type]

            jQuery('#measurement-graph-container')
                .find('img.graph-preview')
                .attr('src', 'imagescpprofile/' + img)

            return;
        }

        var yLabel = {
            weight: 'Weight<br/>(' + CPUSER['weightunits'] + ')',
            girth: 'Total Girth<br/>(' +  CPUSER['lengthunits']  + ')',
            skinfold: 'Total Skinfold<br/>(mm)'
        }[type];

        jQuery('table.measurement-graph .y-legend')
            .html(yLabel);

        if (jQuery.browser.msie) {
            swfobject.embedSWF("open-flash-chart.swf", "measurement-graph",
                "608", "300", "9.0.0", "expressInstall.swf",
                {"data-file": encodeURIComponent(graphFileURL)}
            );
        }
        else {
            jQuery('#measurement-graph')
                .attr('data', 'open-flash-chart.swf?' + Math.random())
                .find('param[name=flashvars]')
                .attr('value', 'data-file=' + encodeURIComponent(graphFileURL));
        }
    },

    event: {
        changeGraphNav: function(self, e) {
            var type = jQuery(this).attr('data-type');
// 		 	var graphFileURL = jQuery(this).attr('data-graph-file') || '';
//
// 		 	graphFileURL = graphFileURL.replace('%(user)s', parseQueryString(location.search)['u'] || 0);
// 		 	graphFileURL = graphFileURL.replace('%(ts)s', +new Date());
//
            jQuery(this)
                .addClass('active')
                .siblings()
                .removeClass('active');

// 			 self._changeGraph(type, graphFileURL);
//
// 			 //
            switch (type) {
                case 'weight':
                    jQuery('.highcharts-graph').hide(); // hide them all
                    jQuery('#weightcontainer').hide(); // show weight graph
                    break;
                case 'girth':
                    jQuery('.highcharts-graph').hide(); // hide them all
                    jQuery('#girthcontainer').hide(); // show girth graph
                    break;
                case 'skinfold':
                    jQuery('.highcharts-graph').hide(); // hide them all
                    jQuery('#weightcontainer').hide(); // show weight graph
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
        }
    }
};

var Habits = {
    init: function() {
        jQuery('.tally-table .habit-header')
            .click(function() {
                jQuery(this)
                    .toggleClass('active')
                    .closest('tbody')
                    .next('.collapsible-tbody')
                    .toggle();
            })
            .hover(function() {
                jQuery(this)
                    .addClass('hover');
            }, function() {
                jQuery(this)
                    .removeClass('hover');
            });

        jQuery('.tally-table .habit-header:first')
            .addClass('active');

        jQuery('.tally-table .collapsible-tbody:first')
            .show();


        var numHabitRows = jQuery('.tally-table tbody tr').length;

        if (!numHabitRows) {
            jQuery('#profile-habits')
                .prepend('<img src="imagescpprofile/habits-no-data-preview.png" style="display: block;"/>')
                .prepend('<div class="orange-notice no-icon rounded-corners" style="padding: 8px 24px; text-align: center; position: absolute; top: 64px; left: 232px; -moz-box-shadow: 0px 0px 2px #333;">\
		   	 		<strong style="display: block; font-size: 16px;">No data yet</strong>\
		   	 		</div>');

            jQuery('.tally-table tbody')
                .append('<tr><td colspan="3" style="padding: 8px;"><div style="background-image: url(imagescpprofile/page_white16.png); margin-bottom: 0pt;" class="orange-notice"><strong style="display: block; font-size: 16px; margin-bottom: 4px;">No data yet</strong>As you begin completing habits, rows will begin showing up here.</div></td></tr>');

            jQuery('#profile-overview')
                .hide();
        }
    }
};

function Graph_(attrs) {
    for(var k in attrs) {
        this[k] = attrs[k];
    }
}

Graph_.prototype.embed = function() {

    var attrs = this;

    jQuery(function() {
        var $container = jQuery('#' + attrs.container);

        var hasNoData = ($container.hasClass('no-data') || attrs.dataFile.length == 0)

        if (hasNoData) {
            attrs.noData.call(null, $container)
            return;
        }

        swfobject.embedSWF("open-flash-chart.swf", attrs.container,
            attrs.width, attrs.height, "9.0.0", "expressInstall.swf",
            {"data-file": attrs.dataFile}
        );
    });
}

// ideally a class instead of a singleton
var Graph = {
    embed: function(attrs) {
        jQuery(function() {
            var $container = jQuery('#' + attrs.container);

            if ($container.hasClass('no-data')) {
                attrs.noData.call(null, $container)
                return;
            }

            swfobject.embedSWF("open-flash-chart.swf", attrs.container,
                attrs.width, attrs.height, "9.0.0", "expressInstall.swf",
                {"data-file": attrs.dataFile}
            );
        });
    }
};

/* !Workouts */
var Workouts = {
    init: function() {
        /*
         var phase = -1;
         jQuery('.tally-table tbody tr')
         .each(function() {
         var thisPhase = jQuery(this).attr('class').substr(6);

         if (phase == thisPhase || thisPhase == 0)
         return true;

         //jQuery(this)
         //	.before('<tr class="stop"><td colspan="3" class="tally-subset-header"><span class="percent">&nbsp;</span>Phase ' + thisPhase + '</td></tr>');

         phase = thisPhase;
         });


         jQuery('.tally-table .tally-subset-header')
         .click(function() {
         jQuery(this)
         .toggleClass('active')
         .closest('tr')
         .nextUntil('.stop')
         .toggle();
         })
         .hover(function() {
         jQuery(this)
         .addClass('hover');
         }, function() {
         jQuery(this)
         .removeClass('hover');
         });

         //

         jQuery('.tally-table .tally-subset-header:first')
         .addClass('active');

         // confusing

         var secondStopIndex = jQuery('.tally-table tr').index(jQuery('.tally-table .stop:eq(1)'));

         jQuery('.tally-table tr:gt(' + secondStopIndex + ')')
         .not('.stop')
         .hide();

         //
         */

        var numWorkoutRows = jQuery('.tally-table tbody tr').length;

        if (!numWorkoutRows) {
            jQuery('#profile-workouts')
                .prepend('<img src="imagescpprofile/workout-no-data-preview.png" style="display: block;"/>')
                .prepend('<div class="orange-notice no-icon rounded-corners" style="padding: 8px 24px; text-align: center; position: absolute; top: 88px; left: 232px; -moz-box-shadow: 0px 0px 2px #333;">\
		   	 		<strong style="display: block; font-size: 16px;">No data yet</strong>\
		   	 		</div>');

            jQuery('.tally-table tbody')
                .append('<tr><td colspan="3" style="padding: 8px;"><div style="background-image: url(imagescpprofile/page_white16.png); margin-bottom: 0pt;" class="orange-notice"><strong style="display: block; font-size: 16px; margin-bottom: 4px;">No data yet</strong>As you begin completing workouts, rows will begin showing up here.</div></td></tr>');

            jQuery('#profile-overview')
                .hide();
        }
    }
};

/* !Document Ready */
jQuery(function() {
    TabNav.init();

    jQuery(".show-face-action").click(function(){
        jQuery.get("/members/cp-progress.php?u=" + CPUSER.id + "&action=update-show-face&value=0",
            function(data) {
                console.log(data);
                jQuery("#show-face-notice").hide();
                jQuery("#saved-changes").fadeIn("slow").fadeOut(20000);
            }
        );
    });
    jQuery("#close-green-notice").click(function(){
        jQuery(this).parent().hide();
    });
    if (CPUSER.yesno_blockoutface == 1)
    {
        jQuery("#show-face-notice").show().fadeIn(3);
    }

    switch(window.tab) {
        case 'habits':
            Habits.init();
            break;
        case 'workouts':
            Workouts.init();
        case 'measurements':
            Measurements.init();
            break;
        case 'photos':
            Photos.init();
            PhotoCompare.init();
            break;
        case 'assignments':
            Assignments.init();
            break;
        default:
            Overview.init();
            break;
    }

    jQuery('#profile-photos .photo-set-container, #profile-photos .flexed-photo-set-container')
        .each(function() {
            jQuery(this)
                .children('a')
                .lightBoxProfilePhotos({
                    overlayOpacity: .6,
                    showPhotoNums: 0,
                    heightPushPx: 82
                });
        });

    jQuery('.tally-table tr:not(.stop)')
        .hover(function() {
            jQuery(this)
                .addClass('hover');
        }, function() {
            jQuery(this)
                .removeClass('hover');
        });


    jQuery('.timeago')
        .each(function() {
            jQuery(this)
                .text(function(text) {
                    return jQuery.timeago(text);
                });
        });
});