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

        $class.is_running = true;

        $class.clientId = attrs.id;
        $class.$measurements = self;

        $class.profiletabs = jQuery('div#profile-tabs.ct');
        $class.statustabs = jQuery('div#status-tabs.ct');

        $class.wPlot = null;
        $class.gPlot = null;
        $class.statPlot = [];
        $class.$loadStatsBtn = jQuery(".e20r-workout-statistics-loader");

        if ($class.clientId === null) {

            console.log("We should be on a wp-admin page...");
            $class.$ajaxUrl = ajaxurl;

            // Used by admin UI only
            $class.$levelSelect = jQuery("#e20r-selectLevel");
            $class.$levelSelector = $class.$levelSelect.find('select#e20r_levels');
            $class.$levelId = $class.$levelSelector.find('option:selected').val();

            $class.$memberSelect = jQuery("#e20r-selectMember");
            $class.$memberSelector = $class.$memberSelect.find('#e20r_members');
            $class.$oldClientId = $class.$memberSelector.find('option:selected').val();

            $class.$spinner = jQuery('#spinner');
            $class.$btnRow = jQuery(".e20r-data-choices");
            $class.$hClientId = jQuery("#hidden_e20r_client_id");

            $class.$adminLoadBtn = jQuery("#e20r-load-data");

            $class.$detailBtn = jQuery("#e20r-client-info");
            $class.$complianceBtn = jQuery("#e20r-client-compliance");
            $class.$assignBtn = jQuery("#e20r-client-assignments");
            $class.$measureBtn = jQuery("#e20r-client-load-measurements");

            $class.$clientInfo = jQuery("#e20r-client-info");
            $class.$clientAssignmentsInfo = jQuery("#e20r-progress-assignments");
            $class.$clientAchievementsInfo = jQuery("#e20r-progress-accomplishments");
            $class.$clientActivityInfo = jQuery("#e20r-progress-activities");

            $class.clientMsgForm = jQuery("div#e20r-client-contact");
            $class.clientMsgHistory = jQuery("table.e20r-client-message-history-table");

            $class.$clientMessageTab = jQuery("#ui-id-5");
            $class.$clientMessageForm = jQuery("#e20r-client-contact");

            $class.$clientComplianceTab = jQuery("#e20r-client-compliance");
            $class.$clientAssignmentsTab = jQuery("#e20r-client-assignments");

            console.log("Loading tabs for wp-admin page");
/*
            jQuery('div.status-tabs .ct').codetabs({
                fxIn: 'roWheelDownIn',
                fxOut: 'roWheelUpOut',
                isAutoRun: true,
                isKeyboard: true,
                name: '.status-tab',
                pag: {
                    dirs: 'hor',
                    pos: 'top',
                    align: 'justified',
                }
            });
*/

             jQuery('#status-tabs').tabs({
                 heightStyle: "content"
             });

            $class.$statusTabs = jQuery('#status-tabs');
/*
             jQuery("#status-tabs").zozoTabs({
             theme: 'white',
             style: 'clean',
             select: progMeasurements._tab_selected,
             orientation: "vertical",
             animation: {
             duration: 800,
             effects: "slideH"
             }
             });
             */
            /* Configure admin page events */
            jQuery.bindEvents({
                self: $class,
                elem: $class.$levelSelect,
                events: {
                    change: function (self, e) {
                        jQuery("div.status-tabs").addClass("startHidden");
                        self.loadMemberList();
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$memberSelector,
                events: {
                    click: function (self) {
                        console.log("Saving client ID");
                        // jQuery("div#status-tabs").addClass("startHidden");
                        self.saveClientId();
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$memberSelector,
                events: {
                    change: function (self, e) {

                        jQuery("div.e20r-data-choices").hide();
                        console.log("Loading user data after drop-down change");
                        // self.$spinner.show();
                        // var $id = self.$memberSelector.find('option:selected').val();

                        // self.saveClientId(self);
                        // self.adminLoadData( $id )
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$measureBtn,
                events: {
                    click: function (self, e) {
                        console.log("Admin clicked Measurements button");
                        //self.$spinner.show();
                        $class.$memberSelector = $class.$memberSelect.find('#e20r_members');
                        console.log("Value: ", $class.$memberSelector.find('option:selected'));
                        var $id = $class.$memberSelector.find('option:selected').val();
                        self.saveClientId(self);
                        self.adminLoadData($id)
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$detailBtn,
                events: {
                    click: function (self, e) {
                        console.log("Admin clicked Client Info button");
                        //self.$spinner.show();
                        $class.$memberSelector = $class.$memberSelect.find('#e20r_members');
                        console.log("Value: ", $class.$memberSelector.find('option:selected'));
                        var $id = $class.$memberSelector.find('option:selected').val();
                        self.saveClientId(self);
                        self.adminLoadData($id)
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$clientMessageTab,
                events: {
                    click: function (self, e) {
                        console.log("Admin clicked Client Messages button");
                        //self.$spinner.show();
                        $class.$memberSelector = $class.$memberSelect.find('#e20r_members');
                        console.log("Value: ", $class.$memberSelector.find('option:selected'));
                        var $id = $class.$memberSelector.find('option:selected').val();
                        self.saveClientId(self);
                        self.loadClientMessages($id)
                    }
                }
            });

            jQuery.bindEvents({
                self: $class,
                elem: $class.$adminLoadBtn,
                events: {
                    click: function (self, e) {
                        console.log("Loading data");
                        // self.$spinner.show();

                        var $id = self.$memberSelector.find('option:selected').val();

                        self.saveClientId(self);
                        self.adminLoadData($id)
                    }
                }
            });

            if ( $class._loaded_from_coachpage() ) {

                console.log("The coach has loaded this page by clicking the client link from the e20r_client_overview shortcode");
                $class.saveClientId($class);
                $class.$statusTabs.show();
                $class.$memberSelect.show();
                $class.$btnRow.show();

                var $id = $class.$memberSelector.find('option:selected').val();
                $class.loadMeasurementData( $id );

            }
            // TODO Bind click events to the assignments, etc. on the wp-admin page.
        }
        else {
            if ( typeof( e20r_progress ) === 'undefined' ) {

                console.log("e20r_progress constant not defined. using e20r_checkin instead");
                $class.$tag = e20r_checkin;
            }
            else {
                $class.$tag = e20r_progress;
            }

            $class.$ajaxUrl = $class.$tag.ajaxurl;
            $class.$spinner = jQuery('#spinner');

            // $class.$spinner.show();

            // Process click on load Statistics button(s)
            $class.$loadStatsBtn.each(function () {
                jQuery.bindEvents({
                    self: $class,
                    elem: jQuery(this),
                    events: {
                        click: function (self, e) {
                            console.log("Loading statistics for exercise", this);

                            var $exercise_id = jQuery(this).closest('.e20r-exercise-statistics').find('.e20r-workout-statistics-exercise_id').val();
                            var $client_id = jQuery('#user_id').val();
                            var $graph = jQuery(this).closest('.e20r-exercise-statistics').find('div#exercise_stats_' + $exercise_id);

                            console.log("Exercise Id: " + $exercise_id + " and client id: " + $client_id, $graph);
                            $class.loadActivityStats($client_id, $exercise_id, $graph);
                        }
                    }
                });
            });

            if ($class.$tag.is_profile_page) {

                console.log("Loading status and profile tabs in e20r_profile shortcode");
                jQuery(".entry-header").hide();

                $class.profiletabs.codetabs({
                    fxOne: 'wheelVer',
                    speed: 1600,
                    isAutoRun: true,
                    isKeyboard: true,
                    idBegin: 0,
                    margin: 10,
                    name: '.profile-tab',
                    pag: {
                        dirs: 'hor',
                        pos: 'top',
                        align: 'justified'
                    }
                });

                $class.statustabs.codetabs({
                    fxOne: 'foldHor',
                    name: '.status-tab',
                    speed: 800,
                    idBegin: 0,
                    margin: 10,
                    isAutoRun: true,
                    isKeyboard: true,
                    pag: {
                        dirs: 'hor',
                        pos: 'top',
                        align: 'justified'
                    }
                });

                $class.profiletabs.ev = $class.profiletabs.data('codetabs').ev;

                $class.profiletabs.ev.on('selectID', function( e, ID ){

                    console.log("Tabs for the profile-tabs have loaded. Showing tab #" + ID );
                    $class._hide_progress( ID );
                });
            }
            else {

                if ( 0 !== $class.statustabs.length ) {

                    console.log("Loading progress overview tabs while in /wp-admin/");
                    $class.statustabs.codetabs({
                        fxOne: 'foldHor',
                        isAutoRun: true,
                        isKeyboard: true,
                        name: '.status-tab',
                        pag: {
                            dirs: 'hor',
                            pos: 'top',
                            align: 'justified'
                        }
                    });
                }
            }

            jQuery(".exercise-stats-container").each(function () {
                jQuery(this).hide();
            });

            setTimeout(function () {
                $class.loadMeasurementData();
            }, 10);

            setTimeout(function () {

                $class._resize_chart();
            }, 1000);
        }

        if ( $class.profiletabs.length !== 0) {

            console.log("Profile-Tabs: Loading event handler when selecting a tab");

            if ( 'undefined' === typeof $class.profiletabs.ev ) {
                $class.profiletabs.ev = $class.profiletabs.data('codetabs').ev;
            }

            $class.profiletabs.ev.on( "loadEnd", function( e, $slide, ID) {

                console.log("Profile-tabs: Hide the progress overview data when loading the e20r_profile shortcode tabs");
                $class._hide_progress(ID);
            });

            $class.profiletabs.ev.on('selectID', function ($e, $id) {
                console.log("Profile-tabs: SelectID for ", $id );
                $class._tab_selected($e, $id);
            });
        }

        if ( $class.statustabs.length !== 0) {

            console.log("Status-Tabs: Loading event handler when selecting a tab");

            if ( 'undefined' === typeof $class.statustabs.ev ) {
                $class.statustabs.ev = $class.statustabs.data('codetabs').ev;
            }

            $class.statustabs.ev.on('selectID', function ($e, $id) {
                console.log("Status-Tabs: SelectID for ", $id );
                $class._tab_selected($e, $id);
            });
        }

        var $resizeId;

        jQuery(window).on('resize', function() {

            console.log("Window was resized!");

            clearTimeout($resizeId);

            $resizeId = setTimeout( function() {
                $class._resize_chart();
                console.log("Updated chart");
            }, 500);
        });

        $class.$body = jQuery("body");

        jQuery(document).on({
            ajaxStart: function() { $class.$body.addClass("loading");    },
            ajaxStop: function() { $class.$body.removeClass("loading"); }
        });

    },
    _loaded_from_coachpage: function() {
        var field = 'e20r-client-id';
        var url = window.location.href;
        if(url.indexOf('?' + field + '=') != -1)
            return true;
        else if(url.indexOf('&' + field + '=') != -1)
            return true;
        return false
    },
    _hide_progress: function( ID ) {

        var $status = jQuery("#status-tabs").find('.ct-cur');

        if ( 1 !== ID ) {

            console.log("Profile tabs progress overview IS NOT visible: ID = " + ID);
            $status.each(function() {

                console.log("Removing ct-cur for: ", this);
                jQuery(this).removeClass( "ct-cur" );
            });

        }
        else {
            console.log("Status should be shown. On profile tab #" + ID);
            var $tab = jQuery("div#status-tabs").find(".ct-pagitem:first");
            var $panel = jQuery("#status-tabs").find("div#status-tab-1");

            console.log("The tab: ", $tab );
            console.log("The panel: ", $panel );

            $tab.addClass("ct-cur");
            $panel.addClass("ct-cur");
        }
    },
    _tab_selected: function( event, $id ) {

        if ( $id == 2 ) {

            console.log("Resize the graphs on the Activities page");
            progMeasurements._resize_chart();
        }
        else if ( $id == 0 ) {

            console.log("Redrawing weight/girth graphs");
            progMeasurements._resize_chart();
            //progMeasurements.gPlot.replot({resetAxes: true});
        }
    },
    _bind_memberselect: function() {

        var $class = this;

        $class.$memberSelect = jQuery("#e20r-selectMember");
        $class.$memberSelector = $class.$memberSelect.find('#e20r_members');
        $class.$oldClientId = $class.$memberSelector.find('option:selected').val();

        jQuery.bindEvents({
            self: $class,
            elem: $class.$memberSelector,
            events: {
                click: function(self) {
                    console.log("Saving client ID");
                    // jQuery("div.status-tabs").addClass("startHidden");
                    self.saveClientId();
                }
            }
        });

        jQuery.bindEvents({
            self: $class,
            elem: $class.$memberSelector,
            events: {
                change: function (self, e) {

                    console.log("Loading user data after drop-down change");
                    self._hide_client_info();
                    // self.$spinner.show();
                    var $id = self.$memberSelector.find('option:selected').val();

                    self.saveClientId(self);
                    self.adminLoadData( $id )
                }
            }
        });
    },
    _hide_client_info: function() {
        jQuery('div.status_tabs').hide();
    },
    _show_client_info: function() {
        jQuery('div.status_tabs').show();
    },
    _resize_chart: function() {

        var $class = this;
        // console.log("Iterating through the statPlot array()", this.statPlot );
        var $new_width;

        for( var key in this.statPlot ) {

            if ( this.statPlot.hasOwnProperty(key) ) {

                var $plot = this.statPlot[key];
                var $plotId = $plot.targetId;

                var element = jQuery($plotId);
                var $resized = element.closest('.e20r-faq-container');

                $new_width = $resized.width() * 0.87;

                if ( $new_width > 0 ) {
                    console.log("New width for element: ", $new_width );
                    element.width($new_width);
                    $plot.replot({resetAxes: true});
                }
            }
        }

        var $tabspace = jQuery('#e20r-progress-measurements').width() * 0.87;

        if ( ( $tabspace > 0 ) &&
            ( ( $class.wPlot !== null ) || ( $class.gPlot !== null ) ) ) {

            console.log("Width for Weight/Girth charts: ", $tabspace);

            jQuery("#weight_chart").width($tabspace);
            jQuery('#girth_chart').width($tabspace);

            $class.wPlot.replot({resetAxes: true});
            $class.gPlot.replot({resetAxes: true});
        }
    },
    _closestChild: function( $me, selector) {

        var $children, $results;

        $children = $me.children();

        if ($children.length === 0)
            return jQuery();

        $results = $children.filter(selector);

        if ($results.length > 0)
            return $results;
        else
            return $children.closestChild(selector);
    },
    adminLoadData: function(id) {

        console.log("in adminLoadData() for user with id: " + id);

        // $class.$memberSelect.prop("disabled", true);

        this.loadMeasurementData(id);
        this.loadAchivementsData(id);
        this.loadAssignmentData( id );
        this.loadActivityData(id);
        this.loadClientInfo(id);
        this.loadClientMessages(id);
        this.loadClientMessageHistory(id);

        jQuery("div#status-tabs").removeClass("startHidden");

        // $class.$memberSelect.prop("disabled", false);
    },
    loadAchivementsData: function( $clientId ) {

        console.log("Loading the Client achievements data");

        var $class = this;
        var $html = $class._loadInfo( $clientId, 'achievements', $class.$clientAchievementsInfo );

        /*
        console.dir($html);

        if ( $html ) {
            $class.$clientAchievementsInfo.html($html);
        }
        else {
            console.log("ERROR: Unable to load the client achievements information!");
        }*/

    },
    loadAssignmentData: function( $clientId ) {

        console.log("Loading the Client assignment data");

        var $class = this;
        var $html = $class._loadInfo( $clientId, 'assignments', $class.$clientAssignmentsInfo );

        // console.log("Assignment HTML: ", $html );

        /*
        if ( $html ) {
            $class.$clientAssignmentsInfo.html($html);
        }
        else {
            console.log("ERROR: Unable to load the client assignment information!");
        }*/

    },
    loadActivityData: function( $clientId ) {

        console.log("Loading the Client activity data");

        var $class = this;
        var $html = $class._loadInfo( $clientId, 'activities', $class.$clientActivityInfo );

        /*
        if ( $html ) {
            $class.$clientActivityInfo.html($html);
        }
        else {
            console.log("ERROR: Unable to load the client activity information!");
        }*/

    },
    get_tinymce_content: function() {

        if ( jQuery( "#wp-content-wrap" ).hasClass( "tmce-active" ) ){

            return tinyMCE.activeEditor.getContent();
        }
        else {

            return jQuery('#content').val();
        }
    },
    loadClientMessages: function( $clientId ) {

        console.log("Loading the Client message panel (send email)");

        var $class = this;

        $class.$spinner.show();

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: {
                action: 'e20r_showClientMessage',
                'e20r-tracker-clients-nonce': jQuery('#e20r-tracker-clients-nonce').val(),
                'client-id': $clientId
            },
            error: function( $response, $errString, $errType ) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to save this data. If you\'d like to try again, please ";
                $string += "click your selection once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using our Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

                $class.$spinner.hide();

            },
            success: function (res) {

                if ( ( res.success ) ) {

                    console.log("Returned for client Messages: ");
                    // console.log( res.data.html );

                    $class.clientMsgForm.html( res.data.html );

                    tinymce.execCommand('mceRemoveEditor', true, 'content');

                    // init editor for newly appended div
                    var init = tinymce.extend( {}, tinyMCEPreInit.mceInit[ 'content' ] );
                    try { tinymce.init( init ); } catch(e){}

                    jQuery("div#wp-content-editor-container").hide();

                    jQuery('input#e20r-send-email-message').on('click', function(){

                        console.log("Click event for sendClientMessage button");
                        $class.$spinner.show();
                        // $class.saveClientId(self);
                        $class.sendMessage();
                        $class.$spinner.hide();
                    });

                    var $datePicker = jQuery('#e20r-tracker-send-message-datetime');

                    console.log("Loading datepicker() function in back-end");
                    $datePicker.datetimepicker({
                        format: "Y-m-d H:i",
                        minDate: 0,
                        allowTimes:[
                            '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30',
                            '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30',
                            '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
                            '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30',
                            '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30'
                        ]
                    });

                    $class.loadClientMessageHistory( $clientId );
                }

            },
            complete: function () {

                // Disable the spinner again
                $class.$spinner.hide();
            }
        });
    },
    loadClientMessageHistory: function( $clientId ) {

        var $class = this;

        $class.$spinner.show();

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: {
                action: 'e20r_showMessageHistory',
                'e20r-tracker-clients-nonce': jQuery('#e20r-tracker-clients-nonce').val(),
                'client-id': $clientId
            },
            error: function( $response, $errString, $errType ) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to load the message history. If you\'d like to try again, please ";
                $string += "click your selection once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using our Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

                $class.$spinner.hide();

            },
            success: function (res) {

                if ( ( res.success ) ) {

                    console.log("Returned for client Messages: ", res.data );
                    // console.log( res.data.html );

                    $class.clientMsgHistory.html( res.data.html );
                }

            },
            complete: function () {

                // Disable the spinner again
                $class.$spinner.hide();
            }
        });
    },
    sendMessage: function() {

        var $class = this;

        console.log("Coach/Admin is attempting to send email message...");
        event.preventDefault();

        $class.$spinner.show();

        var $clientId = jQuery("input#e20r-send-to-id").val();

        var $when = jQuery('#e20r-tracker-send-message-datetime').val();

        if ( $when == '' ) {
            console.log("No date/time specified. Setting to NULL");
            $when = null;
        }
        else {
            console.log("When: ", $when);
        }

        var $data = {
            action: 'e20r_sendClientMessage',
            'e20r-tracker-clients-nonce': jQuery('#e20r-tracker-clients-nonce').val(),
            'subject': jQuery('input#e20r-email-subject').val(),
            'when-to-send': $when,
            'content': $class.get_tinymce_content(),
            'email-to': jQuery("input#e20r-send-message-to").val(),
            'email-cc': jQuery("input#e20r-send-message-cc").val(),
            'email-from': jQuery("input#e20r-email-from").val(),
            'email-from-id': jQuery("input#e20r-send-from-id").val(),
            'email-from-name': jQuery("input#e20r-email-from-name").val()
        };

        console.log( 'Data to transmit for message: ', $data );

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: $data,
            error: function( $response, $errString, $errType ) { //function (data, $errString, $errType) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }
                else {
                    $msg = "Warning:\n\nWe may have been unable to send your message to this client!";
                }

                var $string;
                $string = "An error occurred while trying to send your message. If you\'d like to try again, please ";
                $string += "click the send button once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

                $class.$spinner.hide();

            },
            success: function (res) {

                console.dir(res);

                if ( ( res.success ) ) {

                    console.log("Returned for e20r_sendClientMessage: ", res );
                    alert("Email system received your message.\n\nIt should be under way at this time.");
                    $class.loadClientMessages( $clientId );
                    return false;
                }

            },
            complete: function () {

                // Disable the spinner again
                $class.$spinner.hide();
            }
        });
    },
    _loadInfo: function( $clientId, $requested_action, $caller ) {

        var $class = this;
        var $res = { data: { html: '' } };

        $class.$spinner.show();

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: {
                action: 'e20r_clientDetail',
                'e20r-tracker-clients-nonce': jQuery('#e20r-tracker-clients-nonce').val(),
                'tab-id': $requested_action,
                'client-id': $clientId
            },
            error: function( $response, $errString, $errType ) { //function (data, $errString, $errType) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to fetch client information. If you\'d like to try again, please ";
                $string += "click the Load Information button once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

                $class.$spinner.hide();

            },
            success: function (res) {

                if ( ( res.success ) ) {

                    // console.log("Returned for clientData: ", res);
                    // console.log( res.data.html );
                    $caller.html( res.data.html );
                }
            },
            complete: function () {

                // Disable the spinner again
                $class.$spinner.hide();
            }
        });
    },
    loadClientInfo: function( $clientId ) {

        console.log("Loading the Client Admin Info");

        var $class = this;
        var $html = $class._loadInfo( $clientId, 'client-info', $class.$clientInfo);

        /*
        if ( false != $html ) {
            $class.$clientInfo.html($html);
        }
        else {
            console.log("ERROR: Unable to load the client information!");
        }*/

    },
    saveClientId: function(self) {

        var $class = this;
        var $currId = $class.$memberSelector.find('option:selected').val();

        if ( $currId != $class.$oldClientId ) {
            $class.$hClientId.val($currId);
        }

    },
    loadActivityStats: function( $clientId, $exercise_id, $graph ) {

        var $class = this;

        if ( $clientId === null ) {
            console.log("No arguments specified?");
            $clientId = $class.clientId
        }

        var $data = {
            action: 'load_activity_stats',
            'e20r-weight-rep-chart': jQuery('#e20r-weight-rep-chart').val(),
            'wh_h_dimension': jQuery("#wh_h_dimension").val(),
            'wh_w_dimension': jQuery("#wh_w_dimension").val(),
            'wh_h_dimension_type': jQuery("#wh_h_dimension_type").val(),
            'wh_w_dimension_type': jQuery("#wh_w_dimension_type").val(),
            client_id: $clientId,
            exercise_id: $exercise_id
        };

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            data: $data,
            error: function( $response, $errString, $errType ) { //function (data, $errString, $errType) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to fetch client measurements. If you\'d like to try again, please ";
                $string += "click the tab or button once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

                $class.$spinner.hide();

            },
            success: function (data) {

                if ( ( data.html !== '' ) ) {

                    $graph.html( data.html );

                    // jQuery(".e20r_progress_text").html(null);
                    var firstDate;
                    var lastDate;

                    var $minTick;
                    var $maxTick;
                    var $tickPad;

                    var $entries;
                    var $stepSize;

                    var $max_weight = data.stats[0];
                    var $reps = data.stats[1];

                    if ( ( typeof $max_weight !== 'undefined' ) && ( $max_weight[0].length > 0 ) ) {

                        firstDate = $max_weight[0][0];
                        lastDate = $max_weight[$max_weight.length - 1][0];

                        $entries = $max_weight.length;

                        $stepSize = '1 day';
                        $tickPad = 3600*24*0.5;

                        if ( $entries >= 10 ) {
                            $tickPad = 3600*24*7;
                            $stepSize = '1 week';
                        }

                        if ($entries >= 26) {
                            $tickPad = 3600*24*14;
                            $stepSize = '2 weeks';
                        }

                        $minTick = firstDate - $tickPad;
                        $maxTick = lastDate + $tickPad;

                        var $plot_target = "exercise_stats_" + $exercise_id;

                        var $resizable = $graph.closest('.e20r-exercise-statistics').find('button.e20r-workout-statistics-loader').hide();
                        $graph.show();

                        $class.statPlot[$plot_target] = jQuery.jqplot( $plot_target, [ $max_weight, $reps ], {
                            title: 'Weights and Reps',
                            gridPadding: {right: 35},
                            stackSeries: true,
                            seriesDefaults: {
                                showMarker: true,
                                pointLabels: {show: false}
                            },
                            axesDefaults: {
                                tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                tickOptions: {
                                    fontFamily: 'Verdana',
                                    fontSize: '9px',
                                    angle: 30,
                                    formatString: '%v',
                                    showLabel: true
                                }
                            },
                            axes: {
                                xaxis: {
                                    renderer: jQuery.jqplot.DateAxisRenderer,
                                    labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                    showTicks: true,
                                    tickInterval: $stepSize,
                                    min: $minTick,
                                    max: $maxTick,
                                    pad: 0
                                },
                                yaxis: {
                                    labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                    tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                    label: 'Max resistance (weight)',
                                    showTicks: true,
                                    showTickMarks: true,
                                    autoScale: true,
                                    pointLabels: { show: true },
                                    min: -1,
                                    tickOptions: {
                                        formatString: '%.0f',
                                        showLabel: true
                                    }
                                },
                                y2axis: {
                                    label: 'Reps at max weight',
                                    labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                                    tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                                    showTicks: true,
                                    showTickMarks: true,
                                    autoScale: true,
                                    pointLabels: { show: true },
                                    min: -1,
                                    tickOptions: {
                                        showLabel: true,
                                        formatString: '%.0f'
                                    }
                                }
                            },
                            series: [{
                                yaxis: 'yaxis',
                                label: 'Max resistance (weight)',
                                disableStack: true,
                                color: '#9C0000',
                                lineWidth: 2,
                                markerOptions: {
                                    style: 'diamond',
                                    size: 10
                                },
                                rendererOptions: {
                                    smooth: true
                                }
                            },
                            {
                                yaxis: 'y2axis',
                                color: '#004DFF',
                                disableStack: true,
                                lineWidth: 2,
                                label: 'Reps at max weight',
                                linePattern: 'dashed',
                                markerOptions: {
                                    showLine: false,
                                    style: "circle",
                                    size: 10
                                    },
                                    rendererOptions: {
                                        smooth: true
                                    }
                            }],
                            grid: {
                                drawGridlines: false
                            },
                            legend: {
                                show: true,
                                location: 'nw',
                                labels: [ 'Max weight', 'Repetitions' ],
                                placement: 'inside'
                            }
                        });

                        jQuery("#" + $plot_target).show();
                        $class._resize_chart();

                    }
                    else {
                        console.log("No data found in database for the current user with ID: " + $clientId );
                        // alert("No measurement data found");
                    }
                }

            },
            complete: function () {

                /**
                jQuery('.timeago')
                    .each(function() {
                        jQuery(this)
                            .text(function(text) {
                                return jQuery.timeago(text);
                            });
                    });
                **/

                // Disable the spinner again
                $class.$spinner.hide();
            }
        });

    },
    loadMeasurementData: function( $clientId ) {

        var $class = this;

        if ( $class.loadMeasurementData.arguments.length != 1 ) {

            $clientId = $class.clientId
            console.log("No arguments specified.. Loading for user ID: ", $class.clientId );
        }

        $class.$spinner.show();

        jQuery.ajax({
            url: $class.$ajaxUrl,
            type: 'POST',
            timeout: 10000,
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
            error: function( $response, $errString, $errType ) { //function (data, $errString, $errType) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType);

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to fetch client measurements. If you\'d like to try again, please ";
                $string += "click the tab or button once more. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string );

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
                        console.log("No measurement data found in database for user with ID: " + $clientId );
                        // alert("No measurement data found");
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

                if ( typeof $class.profiletabs.data('codetabs') !== 'undefined' ) {

                    var current_tab = $class.profiletabs.data('codetabs').curId();

                    if (1 !== current_tab) {

                        console.log("Profile-Tab: Hiding the progress overview tab since we're on tab #" + current_tab);
                        $class._hide_progress(current_tab)
                    }
                }
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
            error: function( $response, $errString, $errType ) { //function (data, $errString, $errType) {

                console.log("From server: ", $response );
                console.log("Error String: " + $errString + " and errorType: " + $errType + " from get_memberlistForLevel()");

                var $msg = '';

                if ( 'timeout' === $errString ) {

                    $msg = "Error: Timeout while the server was processing data.\n\n";
                }

                var $string;
                $string = "An error occurred while trying to fetch a list of available membership levels. If you\'d like to try again, please ";
                $string += "reload this page. \n\nIf you get this error a second time, ";
                $string += "please contact Technical Support by using the Contact form ";
                $string += "at the top of this page.";

                alert( $msg + $string + "\n\n" + $response.data );

                $class.$spinner.hide();

                console.log($errString + ' error returned from get_memberlistForLevel action: ' + $errType );

            },
            success: function ($data) {

                console.log("Data returned from server: ", $data );

                // Refresh the sequence post list (include the new post.
                if ($data.data !== '') {

                    $class.$memberSelect.html($data.data);

                    console.log("Loading member select drop-down");

                    $class._bind_memberselect();

                    $class.$memberSelect.show();
                    $class.$btnRow.show();

                    $class.$detailBtn.prop('disabled', false);
                    $class.$complianceBtn.prop('disabled', false);
                    $class.$assignBtn.prop('disabled', false);
                    $class.$measureBtn.prop('disabled', false);

                    $class._show_client_info();

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
                $class.$tag.settings.weekly_progress,
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

    if ( progMeasurements.is_running ) {
        return;
    }

    if ( ( typeof e20r_progress !== 'undefined' ) || ( typeof e20r_checkin !== 'undefined' ) ) {

        console.log("User ID is defined so we're working from the front-end");
        var $clientId = null;

        if ( ( null === $clientId ) && ( typeof e20r_progress !== 'undefined') ) {

            console.log("User ID is defined with the e20r_progress tag: ", e20r_progress.clientId);
            var $clientId = e20r_progress.clientId;
        }

        if ( ( null === $clientId ) &&  ( typeof e20r_checkin !== 'undefined' ) ) {

            console.log("User ID is defined with the e20r_checkin tag: " , e20r_checkin.clientId);
            var $clientId = e20r_checkin.clientId;
        }
    }
    else {
        console.log("Client ID wasn't configured as a constant so assuming we're running from wp-admin");
        var $clientId = null;
    }

    progMeasurements.init( jQuery('#e20r-progress-measurements'), {id: $clientId});
});
