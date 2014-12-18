/* !Home global */

// fix relative "#" links from going to the homepage due to the basehref
jQuery(function() {
    jQuery('a[href^="#"]')
        .each(function() {
            jQuery(this)
                .attr('href', document.URL + jQuery(this).attr('href'));
        });
});

/* !Date picker */
jQuery(function() {
    var calendarHasFocus = 0;

    var datePicker = jQuery('#datepicker-inline')[0];

    if (null == datePicker) {
        return;
    }

    var match;

    if (location.search.indexOf('date=') != -1) {
        var urlDate = parseQueryString(location.search)['date'];

        var defaultDate = Date.parse(urlDate);
    }
    else if (match = /\/([0-9]{4}\-[0-9]{2}\-[0-9]{2})/.exec(document.URL)) { // clean URLs
        var defaultDate = Date.parse(match[1]);
    }
    else {
        var defaultDate = Date.today();
    }

    jQuery(datePicker)
        .datepicker({
            showAnim: 'fadeIn',
            dateFormat: 'yy-mm-dd',
            defaultDate: defaultDate,
            beforeShow: function() {
                /*jQuery('#datepicker-description-container')
                 .fadeIn();*/
            },
            onClose: function() {
                /*jQuery('#datepicker-description-container')
                 .fadeOut();*/

                setTimeout(function() {
                    jQuery(datePicker)
                        .data('hasFocus', false)
                        .trigger('blur');
                }, 100);
            },
            onSelect: function(date) {
                var currenturl = location.pathname;

                if (document.URL.indexOf('.php') != -1) { // ugly URLs
                    location.href = currenturl + '?date=' + date;
                    return;
                }

                currenturl = currenturl.replace(/\/[0-9]+\-[0-9]+\-[0-9]+$/, '');

                location.href = currenturl + '/' + date
            }
        });

    jQuery('.toggle-datepicker')
        .click(function() {
            var calendarHasFocus = bool(jQuery(datePicker).data('hasFocus'));

            if (false == calendarHasFocus) {
                jQuery(datePicker)
                    .data('hasFocus', true)
                    .trigger('focus');

                jQuery('#tooltip')
                    .css('display', 'none');
            }
        });
});

if (document.URL.match(/(vbuserid|vbusername)=[^&]+/) && CPUSER && CPUSER['id'] > 0) {
    location.href = document.URL.replace(/(vbuserid|vbusername)=[^&]+/, 'u=' + CPUSER['id']);
}

/* !Block IE6 */
;(function($) {
    function isIE8() {
        var rv = -1;
        var re = new RegExp("Trident\/([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(navigator.userAgent) != null) {
            rv = parseFloat(RegExp.$1);
        }
        return (rv == 4); // is Trident 4.0?
    }

    var isIE6 = ($.browser.msie && $.browser.version == '6.0');

    if (!isIE6 || jQuery.cookie('ie6continue') || isIE8()) {
        return;
    }

    $.getScript('js/jquery.blockUI.js', function() {
        setTimeout(function() {
            var message = []
            message.push('<h3 style="font-size: 32px; margin-bottom: 12px; color: #0054bb;">Your Web Browser is Too Old</h3>');
            message.push('<div style="width: 440px; margin: 0 auto; font-size: 16px; line-height: 18px;">You probably wouldn\'t wear gym shoes that you\'d had for 10 years. Well, your web browser is indeed a decade old. Unfortunately we use new web technology in the Lean Eating application which your browser can\'t display.   Because we only want you to have the best possible browsing experience, you\'ll need to upgrade to use the Lean Eating application.</div>');
            message.push('<h4 style="margin-top: 40px; margin-bottom: 10px; font-size: 20px;">Upgrade Now (it\'s free)</h4>');
            message.push('<a href="http://www.firefox.com"><img src="cp-images/firefox-logo.gif" /></a>');
            message.push('<a href="http://www.microsoft.com/windows/internet-explorer/worldwide-sites.aspx"><img src="cp-images/ie8-logo.gif" /></a>');
            message.push('<div style="height: 1px; background: #ccc; margin-bottom: 16px;"></div>');
            message.push('<div>If there is no possible way you can upgrade right now, you can continue, but keep in mind pages may display a little weird and some of the more advanced functions may not be available.</div>');
            message.push('<button onclick="jQuery.unblockUI(); jQuery.cookie(\'ie6continue\', \'true\', { expires: 1 });" style="margin-top: 16px;">Continue</button>');


            $.blockUI({
                message: message.join(''),

                centerY: false,

                css: {
                    cursor: 'default',
                    width: '600px',
                    top: '15%',
                    left: '23%',
                    padding: '16px'
                },

                overlayCSS: {
                    cursor: 'default',
                    opacity: .8
                }
            });
        }, 500);
    });

})(jQuery);

/* !My Progress animation */
jQuery(function() {

    // >= 1 means the user has already earned a tick for the current workout/lesson combo
    var hasGottenTick = 0;

    hasGottenTick = !!(jQuery('#did-workout-today-radio-1, #did-habit-today-radio-1, #did-workout-today-radio-4')
        .filter(':checked').length == 2)

    jQuery('fieldset.did-you input:radio', '#daily-progress-canvas')
        .click(function(e) {
            var bothYes = (2 == jQuery('#did-workout-today-radio-1, #did-habit-today-radio-1, #did-workout-today-radio-4').filter(':checked').length);

            if (bothYes && !hasGottenTick) {
                hasGottenTick = 1;
                myProgressTickAnimation(e);
            }
            else if (!bothYes && hasGottenTick) {
                hasGottenTick = 0;

                jQuery('#tickmarks-partial-row')
                    .width(function(width) {
                        return width - 22;
                    });

                jQuery('#my-progress-score')
                    .text(function(n) {
                        return parseInt(n) - 1;
                    });
            }
        });

    function myProgressTickAnimation(e) {

        var animateTime = 800;

        var tickPartialRow = jQuery('#tickmarks-partial-row')[0];

        var tickStartPos = jQuery(e.target).offset();
        var tickEndPos = jQuery(tickPartialRow).offset();

        tickEndPos.left += jQuery(tickPartialRow).width();

        jQuery('#progress-tick-32')
            .css('left', ( tickStartPos.left - 9 ) + 'px')
            .css('top', ( tickStartPos.top - 9 ) + 'px')
            .fadeIn();

        jQuery('#progress-tick-32')
            .animate({
                left: ( tickEndPos.left - 9 ) + 'px',
                top: ( tickEndPos.top - 9 ) + 'px'
            }, animateTime, function() {
                jQuery(this)
                    .animate({
                        width: '16px',
                        height: '16px',
                        left: tickEndPos.left + 'px',
                        top: tickEndPos.top + 'px',
                        alpha: 0,
                        filter: 'alpha(opacity=0)'
                    }, 200, function() {
                        jQuery(this)
                            .css('display', 'none')
                            .css('width', '32px')
                            .css('height', '32px');
                    });
            });

        setTimeout(function() {
            jQuery('#tickmarks-partial-row')
                .width(function(width) {
                    return width + 22; // 22 is a literal that should be a variable
                });

            jQuery('#my-progress-score')
                .fadeOut('medium', function() {
                    jQuery(this)
                        .text(function(n) {
                            return parseInt(n) + 1;
                        })
                        .fadeIn();
                });

        }, animateTime + 500);
    }
});

jQuery(function() {
    // !JS: Blank anchor no jump
    jQuery('a[href="#"]')
        .click(function(e) {
            e.preventDefault();
        });
});

var Tooltip = {
    init: function() {
        jQuery('body')
            .prepend('<div id="tooltip" />');

        jQuery('.tooltip-handle')
            .livequery('mouseover', Tooltip.event.mouseover)
            .livequery('mouseout', Tooltip.event.mouseout);

        this._initiated = true;
    },

    show: function() {

    },

    hide: function() {

    },

    event: {
        mouseover: function(e, opts) {
            opts = opts || {
                pos: 'right'
            };
            var handleElem = this;
            var isXHR = jQuery(handleElem).hasClass('xhr');

            var participant_id = jQuery(handleElem).attr('data-cpuserid');
            var action = jQuery(handleElem).attr('data-ds-action');
            var $this = jQuery(handleElem),
                $tooltip = jQuery('#tooltip');

// 			if ($this.attr('data-tooltip').length == 0) {
// 				return;
// 			}

            var handlePos = $this.offset(),
                handleWidth = $this.width(),
                tooltipPosX = handlePos.left + handleWidth + 4,
                tooltipPosY;
            if (isXHR)
            {
                console.log("isXHR");
                jQuery.getJSON('cpds-assignments.php?action=' + action + '&uid=' + participant_id,
                    function (data) {
                        console.log(data);
                        jQuery('#tooltip').html(base64.decode(data.notes));
                    }
                );
            } else {
                console.log("is not XHR");
                var tooltipHtml = $this.attr('data-tooltip');

                if ($this.hasClass('base64')) {
                    tooltipHtml = base64.decode(tooltipHtml);
                }
                $tooltip
                    .html(tooltipHtml)
            }

            if ( (tooltipPosX + 200) > jQuery(window).width()
                || opts.pos == 'left')
                tooltipPosX -= ($tooltip.outerWidth() + handleWidth + 8);

            tooltipPosY = handlePos.top;

            var marginLeft = int($this.attr('data-tooltip-mleft')) || 0;
            var marginTop = int($this.attr('data-tooltip-mtop')) || 0;

            if ($this.attr('data-tooltip-max-width')) {
                $tooltip.css('max-width', $this.attr('data-tooltip-max-width') + 'px');
            }

            $tooltip
                .css('display', 'block')
                .css('left', (tooltipPosX + marginLeft) + 'px')
                .css('top', (tooltipPosY + marginTop) + 'px');
        },

        mouseout: function(e) {
            jQuery('#tooltip')
                .css('display', 'none');
        }
    },

    clickStick: function(handleElem) {

    },

    unbindHandle: function(handleElem, opts) {
        opts = opts || {
            timeout: 0
        };
        jQuery(handleElem)
            .removeClass('tooltip-handle')
            .one('mouseout', function(){
                setTimeout(function() {
                    Tooltip.event.mouseout.call(this);
                }, opts.timeout);
            });
    }
};


/* !My Progress Ticks */
jQuery(function() {
    var ticksPerLine = 10,
        tickRowHeight = 22,
        tickColWidth = tickRowHeight,
        numTicks = parseInt(jQuery('#my-progress-score').text()),
        defaultNumDisplayClicks = 40;

    var tickDisplayFullRows = tickActualFullRows = Math.floor(numTicks / ticksPerLine);
    var tickPartialCols = numTicks % ticksPerLine;
    var maxFullRows = (defaultNumDisplayClicks-1) / ticksPerLine;

    tickDisplayFullRows = (tickDisplayFullRows > maxFullRows) ?
        maxFullRows : tickDisplayFullRows;

    jQuery('#tickmarks-full-rows')
        .height(tickDisplayFullRows * tickRowHeight);

    jQuery('#tickmarks-partial-row')
        .height(tickRowHeight)
        .width(tickColWidth * tickPartialCols);

    // ..
    if (numTicks < defaultNumDisplayClicks)
        jQuery('#tickmark-expand-button')
            .addClass('concealed-important');


    jQuery('#tickmark-expand-button')
        .click(function() {

            var toHeight;

            if (false == !!jQuery('#tickmarks').data('allMode')) {
                jQuery('#tickmarks').data('allMode', true);
                toHeight = tickActualFullRows * tickRowHeight;
            }
            else {
                jQuery('#tickmarks').data('allMode', false);
                toHeight = tickDisplayFullRows * tickRowHeight;
            }

            jQuery('#tickmarks-full-rows')
                .animate({
                    height: toHeight + 'px'
                }, 500);

            jQuery(this)
                .children('img')
                .attr('src', function(src) {
                    return jQuery(this).hasClass('down') ?
                        src.replace('down', 'up') : src.replace('up', 'down');
                })
                .toggleClass('down');

            Tooltip.unbindHandle(this);
        });
});

/* !Tooltip */
jQuery(function() {
    var pageHasTooltips = 1; // TODO

    if (pageHasTooltips) {
        Tooltip.init();
    }
});

/* !Daily progress load state */
jQuery(function() {
    // this code is redundant, except w/o the animations
    var radioFieldsWorkout = jQuery('fieldset.did-you:eq(0) input:radio', '#daily-progress-canvas');
    var radioFieldsHabit = jQuery('fieldset.did-you:eq(1) input:radio', '#daily-progress-canvas');

    jQuery(radioFieldsWorkout)
        .add(radioFieldsHabit)
        .filter(':checked')
        .parent('li')
        .addClass('active');

    var setEditState = function(set) {
        jQuery(set)
            .not(':checked')
            .parent('li')
            .addClass('concealed')
            .parents('fieldset.did-you')
            .append('<button class="edit-selection">Edit Selection</button>');
    }

    if (bool(jQuery(radioFieldsWorkout).filter(':checked').length))
        setEditState(radioFieldsWorkout);

    if (bool(jQuery(radioFieldsHabit).filter(':checked').length))
        setEditState(radioFieldsHabit);
});


/*!
 * jQuery Untils - v1.0 - 12/1/2009
 * http://benalman.com/projects/jquery-untils-plugin/
 * 
 * Copyright (c) 2009 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */

// Script: jQuery Untils: nextUntil, prevUntil, parentsUntil
//
// *Version: v1.0, Last updated: 12/1/2009*
// 
// Project Home - http://benalman.com/projects/jquery-untils-plugin/
// GitHub		- http://github.com/cowboy/jquery-untils/
// Source		- http://github.com/cowboy/jquery-untils/raw/master/jquery.ba-untils.js
// (Minified)	- http://github.com/cowboy/jquery-untils/raw/master/jquery.ba-untils.min.js (0.7kb)
// 
// About: License
// 
// Copyright (c) 2009 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/
// 
// About: Example
// 
// This working example, complete with fully commented code, illustrates one way
// in which this plugin can be used.
// 
// nextUntil	- http://benalman.com/code/projects/jquery-untils/examples/nextuntil/
// prevUntil	- http://benalman.com/code/projects/jquery-untils/examples/prevuntil/
// parentsUntil - http://benalman.com/code/projects/jquery-untils/examples/parentsuntil/
// 
// About: Support and Testing
// 
// Information about what version or versions of jQuery this plugin has been
// tested with, what browsers it has been tested in, and where the unit tests
// reside (so you can test it yourself).
// 
// jQuery Versions - 1.3.2
// Browsers Tested - Internet Explorer 6-8, Firefox 2-3.5, Safari 3-4, Chrome, Opera 9.6-10.
// Unit Tests	   - http://benalman.com/code/projects/jquery-untils/unit/
// 
// About: Release History
// 
// 1.0 - (12/1/2009) Initial release

(function($){
    '$:nomunge'; // Used by YUI compressor.

    $.each({

        // These three methods use jQuery.dir internally, so it makes sense for them
        // to have an "until" mode.

        // Method: jQuery.fn.nextUntil
        //
        // From the selected element(s), get all (or selected) next sibling elements
        // until an "ending" element is reached. The ending element is not included
        // in the final collection of elements, which is uniqued and returned in
        // "traversal" order.
        //
        // Usage:
        //
        // > jQuery('selector').nextUntil( until_sel [, each_sel ] );
        //
        // Arguments:
        //
        //	until_sel - (String) A jQuery selector that matches the "ending"
        //	  element. Only elements preceding the first element matching this
        //	  selector will be returned.
        //	each_sel - (String) An optional jQuery selector that filters each
        //	  element that is iterated over. Excluding this argument is the same as
        //	  specifying "*".
        //
        // Returns:
        //
        //	(jQuery) A filtered jQuery collection of elements, returned in
        //	"traversal" order.

        nextUntil: 'nextAll',

        // Method: jQuery.fn.prevUntil
        //
        // From the selected element(s), get all (or selected) previous sibling
        // elements until an "ending" element is reached. The ending element is not
        // included in the final collection of elements, which is uniqued and
        // returned in "traversal" order.
        //
        // Usage:
        //
        // > jQuery('selector').prevUntil( until_sel [, each_sel ] );
        //
        // Arguments:
        //
        //	until_sel - (String) A jQuery selector that matches the "ending"
        //	  element. Only elements preceding the first element matching this
        //	  selector will be returned.
        //	each_sel - (String) An optional jQuery selector that filters each
        //	  element that is iterated over. Excluding this argument is the same as
        //	  specifying "*".
        //
        // Returns:
        //
        //	(jQuery) A filtered jQuery collection of elements, returned in
        //	"traversal" order.

        prevUntil: 'prevAll',

        // Method: jQuery.fn.parentsUntil
        //
        // From the selected element(s), get all (or selected) parent elements until
        // an "ending" element is reached. The ending element is not included in the
        // final collection of elements, which is uniqued and returned in "traversal"
        // order.
        //
        // Usage:
        //
        // > jQuery('selector').parentsUntil( until_sel [, each_sel ] );
        //
        // Arguments:
        //
        //	until_sel - (String) A jQuery selector that matches the "ending"
        //	  element. Only elements preceding the first element matching this
        //	  selector will be returned.
        //	each_sel - (String) An optional jQuery selector that filters each
        //	  element that is iterated over. Excluding this argument is the same as
        //	  specifying "*".
        //
        // Returns:
        //
        //	(jQuery) A filtered jQuery collection of elements, returned in
        //	"traversal" order.

        parentsUntil: 'parents'

    }, function( name, method ){

        $.fn[ name ] = function( until_selector, each_selector ) {

            // Store elements "for later".
            var elems = [],

            // An array containing the originally selected elements.
                that = this.get(),

            // If the method is prevUntil or parentsUntil, the selected elements
            // must be returned in traversal- or reverse-DOM-order.
                is_reverse = name.indexOf( 'p' ) === 0;

            // Because `this` is a DOM-ordered jQuery collection of elements, we need
            // to iterate over it in reverse for reverse-DOM-order methods.
            if ( is_reverse && that.length > 1 ) {
                that = that.reverse();
            }

            // For each element passed in,
            $.each( that, function(){

                // Get all elements (or those matching each_selector).
                var result = $(this)[ method ]( each_selector ),

                    browser = $.browser;

                if ( is_reverse ) {

                    // jQuery 1.3.2 doesn't always return elements in DOM order for IE6/7.
                    if ( that.length > 1 && browser.msie && browser.version < 8 ) {
                        result = result.filter( '*,*' ).get().reverse();

                        // Since jQuery 1.3.2 returns elements in DOM-order instead of
                        // reverse-DOM-order when a complex selector is passed to prevAll or
                        // parents, the resulting element set must be reversed.
                    } else if ( /,/.test( each_selector ) ) {
                        result = result.get().reverse();
                    }
                }

                $.each( result, function(){

                    // Store elements "for later", until the until_selector is matched.
                    return $(this).is( until_selector )
                        ? false
                        : elems.push( this );
                });
            });

            // Return a uniqued collection of the "stored for later" elements, which
            // can be reverted by using .end().
            return this.pushStack( $.unique( elems ), name, until_selector + ( each_selector ? ',' + each_selector : '' ) );
        };

    });

})(jQuery);
