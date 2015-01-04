
/**********************************************************/
jQuery.noConflict();

// TODO: How do I implement the jqplot for a shortcode...?

(function() {

    // prevent double loading
    if (window.__loadedSharedJS) {
        return;
    }

    window.__loadedSharedJS = true;

    if (jQuery) {
        window.Q = jQuery.noConflict();
    }

    // put URL GET params in an easily accessible place
    if (typeof MochiKit !== 'undefined' && MochiKit.Base && MochiKit.Base.parseQueryString) {
        window.GET = MochiKit.Base.parseQueryString(location.search);
    }

    String.prototype.strip = function() {
        return this.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
    }

    String.prototype.title = function() {
        return (this+'').replace(/^(.)|\s(.)/g, function ( $1 ) { return $1.toUpperCase( ); } );
    }

    // deprecate this ASAP
    Array.prototype.find = function(x) {
        for ( var i = 0, length = this.length; i < length; i++ )
            // Use === because on IE, window == document
            if ( this[ i ] === x )
                return i;

        return -1;
    }

    window.bool = function(expr) {
        return !!expr;
    }

    window.boolNotNull = function(expr) {
        if (expr === null || expr === undefined) {
            return expr;
        }

        return bool(expr);
    }

    window.int = function(x) {
        return Math.floor(Number(x));
    }

    window.float = function(x) {
        return Number(x);
    }

    window.isArray = function(a) {
        return jQuery.isArray(a);
    }

    window.titleCase = function(a) {
        return String(a).replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
    }

    window.round = function(n, precision) {
        precision = precision || 0;

        var magnitude = Math.pow(10, precision);

        return Math.round(n * magnitude) / magnitude;
    }

    jQuery.fn.log = function (msg) {
        console.log("%s: %o", msg, this);
        return this;
    };

    if (typeof(console) === 'undefined') {
        window.console = {
            log: function() {},
            info: function() {}
        };
    }

    for (var method in console) {
        window[method] = console[method];
    }

    window.construct = function(obj) {
        function Constructor_() {
            for (k in obj) {
                this[k] = obj[k];
            }

            if ('function' === typeof(obj.init)) {
                var args = [];

                args.push(this); // self as first argument

                for(var i = 0; i < arguments.length; i++) {
                    args.push(arguments[i]);
                }

                obj.init.apply(this, args);
            }
        }

        return Constructor_;
    }

        /* more meaningful sometimes */
    ;(function($) {
        $.fn.child = $.fn.children;
    })(jQuery);

    ;(function($) {
        $.fn.positionAtOffset = function(elem, moveLeft, moveTop) {
            moveLeft = moveLeft || 0;
            moveTop = moveTop || 0;

            var offset = jQuery(elem).offset(),
                left = offset.left + moveLeft,
                top = offset.top + moveTop;

            this.css('left', left + 'px')
                .css('top', top + 'px');

            return this;
        };
    })(jQuery);

    // Event System
    ;(function($) {
        jQuery.bindEvents = function(events, self, elem) {
            if (undefined === self) { // using verbose call method $.bindEvents({ /* opts */ })
                var opts = events;

                self = opts.self;
                elem = opts.elem;
                events = opts.events;
            }

            for(var k in events) {
                (function(k) {
                    jQuery(elem).bind(k, function(e) {
                        var jQueryContext = this;

                        events[k].call(jQueryContext, self, e);
                    });
                })(k);
            }
        };
    })(jQuery);

    /* Functions as Values */
    ;(function($) {
        var methods = ['html', 'text', 'val', 'parent', 'parents', 'next', 'nextAll', 'find', 'add', 'prev', 'prevAll', 'siblings', 'scrollLeft', 'scrollTop', 'height', 'width', 'css', 'attr'];

        // make sure this plugin hasn't already run... this would cause endless recursion
        if ('function' === typeof jQuery.fn['__html']) {
            return;
        }

        for(var i = 0, methodName; methodName = methods[i]; i++) {
            (function(methodName) {
                $.fn['__' + methodName] = $.fn[methodName];

                $.fn[methodName] = function() {
                    var expr = (arguments.length == 1)
                        ? arguments[0] : arguments[1];

                    var args = jQuery.makeArray(arguments);

                    if (typeof expr == 'function') {

                        // slice off the function
                        // in most cases this will be empty, except when a method which takes multiple arguments is called, such as `attr` and `css`, in which case args will become the attribute name or css property that the function is being called with
                        args = args.reverse().slice(1);

                        // call the method without the setter value (the evaluated function)
                        // this will return the method's current value, which is then passed in as the first argument to the function in context
                        var curval = $.fn['__' + methodName].apply(this, args);

                        // evaluate the value function and add it to the arguments stack, which will be used for the final (apply) call below
                        args.push(
                            expr.call(this, curval)
                        );
                    }

                    return $.fn['__' + methodName].apply(this, args);
                }
            })(methodName);
        }
    })(jQuery);

    // body class browser
    ;(function($) {
        // Checks the browser and adds classes to the body to reflect it.

        $(document).ready(function(){

            var userAgent = navigator.userAgent.toLowerCase();
            $.browser.chrome = /chrome/.test(navigator.userAgent.toLowerCase());

            // Is this a version of IE?
            if($.browser.msie){
                $('body').addClass('browserIE');

                // Add the version number
                $('body').addClass('browserIE' + $.browser.version.substring(0,1));
            }

            // Is this a version of Chrome?
            if($.browser.chrome){

                $('body').addClass('browserChrome');

                //Add the version number
                userAgent = userAgent.substring(userAgent.indexOf('chrome/') +7);
                userAgent = userAgent.substring(0,1);
                $('body').addClass('browserChrome' + userAgent);

                // If it is chrome then jQuery thinks it's safari so we have to tell it it isn't
                $.browser.safari = false;
            }

            // Is this a version of Safari?
            if($.browser.safari){
                $('body').addClass('browserSafari');

                // Add the version number
                userAgent = userAgent.substring(userAgent.indexOf('version/') +8);
                userAgent = userAgent.substring(0,1);
                $('body').addClass('browserSafari' + userAgent);
            }

            // Is this a version of Mozilla?
            if($.browser.mozilla){

                //Is it Firefox?
                if(navigator.userAgent.toLowerCase().indexOf('firefox') != -1){
                    $('body').addClass('browserFirefox');

                    // Add the version number
                    userAgent = userAgent.substring(userAgent.indexOf('firefox/') + 8).replace('.', '');
                    var version = userAgent.substring(0, 2); // '30', '35'
                    $('body').addClass('browserFirefox' + version);
                }
                // If not then it must be another Mozilla
                else{
                    $('body').addClass('browserMozilla');
                }
            }

            // Is this a version of Opera?
            if($.browser.opera){
                $('body').addClass('browserOpera');
            }

            if (navigator.platform.substr(0,3) == 'Win') {
                $('body').addClass('platformWindows');
            }
        });
    })(jQuery);

    /**
     * Cookie plugin
     *
     * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
     * Dual licensed under the MIT and GPL licenses:
     * http://www.opensource.org/licenses/mit-license.php
     * http://www.gnu.org/licenses/gpl.html
     *
     */

    /**
     * Create a cookie with the given name and value and other optional parameters.
     *
     * @example $.cookie('the_cookie', 'the_value');
     * @desc Set the value of a cookie.
     * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
     * @desc Create a cookie with all available options.
     * @example $.cookie('the_cookie', 'the_value');
     * @desc Create a session cookie.
     * @example $.cookie('the_cookie', null);
     * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
     *       used when the cookie was set.
     *
     * @param String name The name of the cookie.
     * @param String value The value of the cookie.
     * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
     * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
     *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
     *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
     *                             when the the browser exits.
     * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
     * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
     * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
     *                        require a secure protocol (like HTTPS).
     * @type undefined
     *
     * @name $.cookie
     * @cat Plugins/Cookie
     * @author Klaus Hartl/klaus.hartl@stilbuero.de
     */

    /**
     * Get the value of a cookie with the given name.
     *
     * @example $.cookie('the_cookie');
     * @desc Get the value of a cookie.
     *
     * @param String name The name of the cookie.
     * @return The value of the cookie.
     * @type String
     *
     * @name $.cookie
     * @cat Plugins/Cookie
     * @author Klaus Hartl/klaus.hartl@stilbuero.de
     */
    jQuery.cookie = function(name, value, options) {
        if (typeof value != 'undefined') { // name and value given, set cookie
            options = options || {};
            if (value === null) {
                value = '';
                options.expires = -1;
            }
            var expires = '';
            if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires == 'number') {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                } else {
                    date = options.expires;
                }
                expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
            }
            // CAUTION: Needed to parenthesize options.path and options.domain
            // in the following expressions, otherwise they evaluate to undefined
            // in the packed version for some reason...
            var path = options.path ? '; path=' + (options.path) : '';
            var domain = options.domain ? '; domain=' + (options.domain) : '';
            var secure = options.secure ? '; secure' : '';
            document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else { // only name given, get cookie
            var cookieValue = null;
            if (document.cookie && document.cookie != '') {
                var cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = jQuery.trim(cookies[i]);
                    // Does this cookie string begin with the name we want?
                    if (cookie.substring(0, name.length + 1) == (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }
    };

})();

(function() {
    if (typeof JSON != 'undefined') {
        return;
    }

    window.JSON = {};

    window.JSON.toJSON = function(o)
    {
        var type = typeof(o);

        if (o === null)
            return "null";

        if (type == "undefined")
            return undefined;

        if (type == "number" || type == "boolean")
            return o + "";

        if (type == "string")
            return window.JSON.quoteString(o);

        if (type == 'object')
        {
            if (typeof o.toJSON == "function")
                return window.JSON.toJSON( o.toJSON() );

            if (o.constructor === Date)
            {
                var month = o.getUTCMonth() + 1;
                if (month < 10) month = '0' + month;

                var day = o.getUTCDate();
                if (day < 10) day = '0' + day;

                var year = o.getUTCFullYear();

                var hours = o.getUTCHours();
                if (hours < 10) hours = '0' + hours;

                var minutes = o.getUTCMinutes();
                if (minutes < 10) minutes = '0' + minutes;

                var seconds = o.getUTCSeconds();
                if (seconds < 10) seconds = '0' + seconds;

                var milli = o.getUTCMilliseconds();
                if (milli < 100) milli = '0' + milli;
                if (milli < 10) milli = '0' + milli;

                return '"' + year + '-' + month + '-' + day + 'T' +
                    hours + ':' + minutes + ':' + seconds +
                    '.' + milli + 'Z"';
            }

            if (o.constructor === Array)
            {
                var ret = [];
                for (var i = 0; i < o.length; i++)
                    ret.push( window.JSON.toJSON(o[i]) || "null" );

                return "[" + ret.join(",") + "]";
            }

            var pairs = [];
            for (var k in o) {
                var name;
                var type = typeof k;

                if (type == "number")
                    name = '"' + k + '"';
                else if (type == "string")
                    name = window.JSON.quoteString(k);
                else
                    continue;  //skip non-string or number keys

                if (typeof o[k] == "function")
                    continue;  //skip pairs where the value is a function.

                var val = window.JSON.toJSON(o[k]);

                pairs.push(name + ":" + val);
            }

            return "{" + pairs.join(", ") + "}";
        }
    };

    window.JSON.stringify = window.JSON.toJSON;

    /** jQuery.evalJSON(src)
     Evaluates a given piece of json source.
     **/
    window.JSON.evalJSON = function(src)
    {
        return eval("(" + src + ")");
    };

    /** jQuery.secureEvalJSON(src)
     Evals JSON in a way that is *more* secure.
     **/
    window.JSON.secureEvalJSON = function(src)
    {
        var filtered = src;
        filtered = filtered.replace(/\\["\\\/bfnrtu]/g, '@');
        filtered = filtered.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
        filtered = filtered.replace(/(?:^|:|,)(?:\s*\[)+/g, '');

        if (/^[\],:{}\s]*$/.test(filtered))
            return eval("(" + src + ")");
        else
            throw new SyntaxError("Error parsing JSON, source is not valid.");
    };

    /** jQuery.quoteString(string)
     Returns a string-repr of a string, escaping quotes intelligently.
     Mostly a support function for toJSON.

     Examples:
     >>> jQuery.quoteString("apple")
     "apple"

     >>> jQuery.quoteString('"Where are we going?", she asked.')
     "\"Where are we going?\", she asked."
     **/
    window.JSON.quoteString = function(string)
    {
        if (string.match(_escapeable))
        {
            return '"' + string.replace(_escapeable, function (a)
                {
                    var c = _meta[a];
                    if (typeof c === 'string') return c;
                    c = a.charCodeAt();
                    return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
                }) + '"';
        }
        return '"' + string + '"';
    };

    var _escapeable = /["\\\x00-\x1f\x7f-\x9f]/g;

    var _meta = {
        '\b': '\\b',
        '\t': '\\t',
        '\n': '\\n',
        '\f': '\\f',
        '\r': '\\r',
        '"' : '\\"',
        '\\': '\\\\'
    };
})();

/*
(function() {
    var match = document.URL.match(/scrollTo\(([0-9]+)\)/);

    if (match === null) {
        return;
    }

    var scrollToY = match[1];

    jQuery.getScript(e20r_tracker.settings.jquery_scroll_plugin_path, function() {
        setTimeout(function() {
            jQuery.scrollTo(scrollToY, 500);
        }, 500);
    });
})();

var Tooltip = {
    init: function() {
        if (!jQuery.fn.livequery) {
            return;
        }

        if (jQuery('#tooltip').length == 0) {
            jQuery('body')
                .prepend('<div id="tooltip" />');
        }

        var $tooltipHandles = jQuery('.tooltip-handle');

        $tooltipHandles
            .livequery('mouseover', Tooltip.event.mouseover)
            .livequery('mouseout', Tooltip.event.mouseout);

        // TODO: Do we want to support base64? If so upload
        // var usesBase64 = ($tooltipHandles.filter('.base64').length >= 1);

        // if (usesBase64) {
        //    if (!base64) {
        //        jQuery.getScript(e20r_tracker.settings.base64_script_url);
        //    }
        //}

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

            var $this = jQuery(handleElem),
                $tooltip = jQuery('#tooltip');

            if ($this.attr('data-tooltip').length == 0) {
                return;
            }

            var handlePos = $this.offset(),
                handleWidth = $this.width(),
                tooltipPosX = handlePos.left + handleWidth + 4,
                tooltipPosY;

            var tooltipHtml = $this.attr('data-tooltip');

            if ($this.hasClass('base64')) {
                tooltipHtml = base64.decode(tooltipHtml);
            }

            if ($this.attr('data-tooltip-forcehtml')) {
                tooltipHtml = tooltipHtml.replace(/&gt;/g, '>');
                tooltipHtml = tooltipHtml.replace(/&lt;/g, '<');
            }

            $tooltip
                .html(tooltipHtml)

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
*/

//jQuery(function() {
//    Tooltip.init();
//});

jQuery(function() {
    jQuery('button p').remove();
});

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
            message.push('<div style="width: 440px; margin: 0 auto; font-size: 16px; line-height: 18px;">Wow, your web browser is more than a decade old. Just like you probably won\'t use something you wear every day for that long, you shouldn\'t use a geriatric web browser. Unfortunately we use new web technology in the Eighty / 20 Nutrition Coaching application which your browser won\'t be able to display.   Because we only want you to have the best possible browsing experience, you\'ll need to upgrade to use this application.</div>');
            message.push('<h4 style="margin-top: 40px; margin-bottom: 10px; font-size: 20px;">Upgrade Now (it\'s free)</h4>');
            message.push('<a href="http://www.google.com/chrome"><img src="https://www.google.com/chrome/assets/common/images/chrome_logo_2x.png" /></a>');
            message.push('<a href="http://www.firefox.com"><img src="https://mozorg.cdn.mozilla.net/media/img/firefox/new/header-firefox.png?2013-06" /></a>');
            message.push('<a href="http://support.apple.com/downloads/#safari"><img src="http://km.support.apple.com/kb/image.jsp?productid=PL165" /></a>');
            message.push('<a href="http://windows.microsoft.com/en-us/internet-explorer/download-ie"><img src="https://enterprisegrantapps.state.nj.us/NJSAGE/Images/imgIconIE_small.jpg" /></a>');
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


/* TODO -- Progress animation or Badge??*/
/*
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
    */
