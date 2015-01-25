
var UNIT = {
    _weight: ['kg', 'lbs', 'st'],
    _length: ['cm', 'in'],

    kg: 'kilograms (kg)',
    lbs: 'pounds (lbs)',
    st: 'stone (st)',
    'in': 'inches (in)',
    'cm': 'centimeters (cm)',


    printAbbr: function(fullForm) {
        return UNIT.abbr(fullForm);
    },

    printWord: function(fullForm) {
        return /[^\(]+/.exec(fullForm)[0].strip();
    },

    abbr: function(unitStr) {
        var abbr = /\((.+?)\)/.exec(unitStr);

        if (abbr !== null) {
            return abbr[1];
        }
        else {
            return unitStr; // probably already in abbr notation
        }
    },

    convert: function(from, to, value) {
        from = UNIT.abbr(from);
        to = UNIT.abbr(to);

        if (UNIT._weight.find(from) !== -1) {
            return UNIT._convertWeight(from, to, value);
        }
        else {
            return UNIT._convertLength(from, to, value);
        }
    },

    _convertWeight: function(from, to, value) {
        if (from === to || !from || from == 'unknown') {
            return value;
        }

        return {
            'lbs': {
                'st': function(n) {
                    return n / 14;
                },
                'kg': function(n) {
                    return n / 2.20462262;
                }
            },
            'st': {
                'lbs': function(n) {
                    return n * 14;
                },
                'kg': function(n) {
                    return n * 6.35029318;
                }
            },
            'kg': {
                'lbs': function(n) {
                    return n * 2.20462262;
                },
                'st': function(n) {
                    return n * 0.157473044;
                }
            }
        }[from][to].call(null, value);
    },

    _convertLength: function(from, to, value) {
        if (from === to || !from || from == 'unknown') {
            return value;
        }

        return {
            'in': {
                'cm': function(value) {
                    return value * 2.54;
                }
            },
            'cm': {
                'in': function(value) {
                    return value / 2.54;
                }
            }
        }[from][to].call(null, value);
    }
};

jQuery(function() {

    var MAX_ALLOWED_MEASUREMENT_CHANGE_PER_PERIOD = {
        'weight': 10, // lbs
        'girth_neck': 3, // in
        'girth_shoulder': 4,
        'girth_chest': 4,
        'girth_arm': 3,
        'girth_waist': 4,
        'girth_hip': 4,
        'girth_thigh': 3,
        'girth_calf': 3,
        'skinfold_triceps': 15, // mm
        'skinfold_chest': 15,
        'skinfold_midaxillary': 15,
        'skinfold_subscapular': 15,
        'skinfold_suprailiac': 15,
        'skinfold_thigh': 15,
        'skinfold_abdominal': 15
    };

    /* class */
    var MeasurementField = {
        init: function(self, attrs) {
            this.type = attrs.type;
            this.period = attrs.period;
            this.unit = attrs.unit || 'unknown';
            this.$field = jQuery('input.measurement-input[data-measurement-type=' + self.type + ']');
            this.value = float(this.$field.val());
            this.$fieldContainer = self.$field.closest('.measurement-field-container');
            this.$savedContainer = this.$fieldContainer.next('.measurement-saved-container');
            this.$value = this.$savedContainer.find('.value');
            this.$editButton = this.$savedContainer.child('button.edit');
            this.$girthRowContainer = this.$fieldContainer.closest('.girth-row-container');
            this.$girthImage = this.$girthRowContainer.find('.girth-image, .skinfold-image');
            this.$description = this.$girthRowContainer.find('.girth-descript-container, .skinfold-descript-container').children('p');
            this.$infoToggleIcon = this.$girthRowContainer.prev('h5.measurement-header').child('.measurement-descript-toggle');

            this._state = 'default';
            this._isSkinfold = this.type.indexOf('skinfold') != -1;
            this._allPossibleStates = ['default', 'active', 'saved', 'edit'];
            this.__overrideDifferenceCheck = 0; // e20r_progress.settings.overrideDiff;

            if (bool(this.$girthImage.length)) {

                this.$girthImage.addClass(NourishUser.gender);

                if ('F' === NourishUser.gender) { // ad hoc
                    this.$girthImage.css('background-image', function(url) {
                        return url.replace('-m', '-f');
                    });
                }
            }

            jQuery.bindEvents({
                self: this,
                elem: this.$field,
                events: {
                    focus: this.activate,
                    blur: this.attemptSave,
                    keypress: function(self, e) {
                        if (e.keyCode === 13) {
                            self.$field.trigger('blur');
                        }
                    }
                }
            });

            jQuery.bindEvents({
                self: this,
                elem: this.$infoToggleIcon,
                events: {
                    click: function(self) {
                        self.changeState('active');
                    }
                }
            });

            jQuery.bindEvents({
                self: this,
                elem: this.$editButton,
                events: {
                    click: this.edit
                }
            });

            this.stateTransition(['default', 'active'], 'saved', function(self) {
                self.$fieldContainer
                    .removeClass('edit')
                    .removeClass('active')
                    .addClass('saved');

                self.$savedContainer
                    .child('.value')
                    .text(self.value)
                    .end()
                    .child('.unit')
                    .text(self.unit)
                    .end()
                    .addClass('active');
            });

            this.stateTransition('default', 'active', function(self) {

                self.$fieldContainer.addClass('active');

                if (bool(self.$girthImage.length)) {
                    self.$girthImage.addClass('active');
                }

                self.$girthRowContainer.addClass('active');
                self.$description.parent().addClass('active'); // need to fix the para thing

                self.$description.show();
            });

            this.stateTransition('saved', 'active', function() {
                self.$savedContainer
                    .removeClass('active');

                self.$fieldContainer
                    .addClass('edit');
            });

            if (this.value > 0) {
                self.changeState('active'); // a little dirty
                self.changeState('saved');
            }
        },

        stateTransition: function(fromState, toState, handler) {
            if (undefined === this.__stateTransitionHandlers) {
                this.__stateTransitionHandlers = {};
            }

            if (false === isArray(fromState)) {
                fromState = [fromState];
            }

            for(var i = 0; i < fromState.length; i++) {
                var key = fromState[i] + '->' + toState;

                this.__stateTransitionHandlers[key] = handler;
            }
        },

        changeState: function(toState) {
            var key = this._state + '->' + toState;

            try {
                if (this._state == toState) {
                    throw 'State change attempted to already current state: ' + key; // this isn't a huge deal actually
                }
                else if (undefined === this.__stateTransitionHandlers[key]) {
                    throw 'State transition not defined: ' + key;
                }

                this.__stateTransitionHandlers[key].call(this, this);
            }
            catch(ex) {
                console.log(ex);
            }

            this._state = toState;
        },

        activate: function( self ) {

            self.changeState('active');
        },

        attemptSave: function( self ) {

            // validate

            var value = self.$field.val();

            if ('' === value) { // they focused and then unfocused the field without putting anything in... don't pester them with an error
                return;
            }

            try {
                value = parseFloat(value.replace(/[^0-9\.]/g, ''));

                if (isNaN(value)) {
                    throw 'INPUT_INVALID';
                }

                if (!self.__overrideDifferenceCheck
                    && LAST_WEEK_MEASUREMENTS
                    && LAST_WEEK_MEASUREMENTS[self.type]) {
                    var lastWeekValue = LAST_WEEK_MEASUREMENTS[self.type]['value'];

                    if (lastWeekValue) {
                        var diff = Math.abs(value - lastWeekValue);

                        var diffMaxAllowed = MAX_ALLOWED_MEASUREMENT_CHANGE_PER_PERIOD[self.type] * 5; // *5 temporary

                        if (diff > diffMaxAllowed) {
                            throw 'MEASUREMENT_DIFFERENCE_TOO_LARGE_FOR_PERIOD';
                        }
                    }
                }

                self.value = value;

                self._clearErrors();
                self.__overrideDifferenceCheck = 0;

                self.save(self);
            }
            catch (ex) {
                var exceptionHandlers = {
                    //'INPUT_INVALID': 'Please enter your measurement',
                    'MEASUREMENT_DIFFERENCE_TOO_LARGE_FOR_PERIOD': { errorText: 'The last time you took this measurement, you measured <strong>' + lastWeekValue + ' ' + UNIT.printWord(self.unit) + '</strong>.\
					 That\'s a difference of <strong>' + Math.round(diff) + ' ' + UNIT.printWord(self.unit) + '</strong> from this ' + self.period + '\'s measurement. Are you certain you\'ve entered this ' + self.period + '\'s measurement correctly?\
					<br /><br />\
					<button class="override-difference-check">I\'m certain, save this measurement</button>\
					<button class="cancel" style="margin-right: 4px;">Change this measurement</button>',
                        handler: function(self) {
                            jQuery('.floating-error button.override-difference-check')
                                .click(function() {
                                    self.__overrideDifferenceCheck = 1;
                                    self.attemptSave(self);
                                });

                            jQuery('.floating-error button.cancel')
                                .click(function() {
                                    self._clearErrors();
                                    self.$field.select();
                                });
                        }}
                };

                if (ex in exceptionHandlers) {
                    self._clearErrors();
                    self._displayError(exceptionHandlers[ex]['errorText']);
                    exceptionHandlers[ex].handler.call(null, self);
                }
                else {
                    console.log('Unhandled Exception: ' + ex);
                }
            }
        },

        save: function(self) {

            var $data = {
                'action': 'saveMeasurementForUser',
                'e20r-progress-nonce': jQuery( '#e20r-progress-nonce').val(),
                'article-id': jQuery('#article_id').val(),
                'program-id': jQuery('#program_id').val(),
                'date': jQuery( '#date').val(),
                'measurement-type': self.type,
                'measurement-value': self.value,
                'user-id': NourishUser.user_id
            };

            jQuery.ajax ({
                url: e20r_progress.ajaxurl,
                type: 'POST',
                timeout: 10000,
                dataType: 'JSON',
                data: $data,
                error: function($response, $errString, $errType) {
                    console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                    return;
                },
                success: function( $retVal ) {
                    console.log($retVal.data);
                }
            });

            self.changeState('saved');
        },

        edit: function( self ) {

            event.preventDefault();
            self.changeState('active');
            self.$field.focus();
        },

        _clearErrors: function() {
            jQuery('.floating-error[data-measurement-type="' + this.type + '"]')
                .remove();
        },

        _displayError: function(errorText) {
            jQuery('<div class="floating-error" data-measurement-type="' + this.type + '">' + errorText + '</div>')
                .positionAtOffset(this.$fieldContainer, +20, +66)
                .appendTo(document.body);
        },

        convertUnit: function(toUnit) {
            var fromUnit = this.unit;

            var newValue = round(UNIT.convert(fromUnit, toUnit, this.value), 2);

            this.unit = toUnit;

            this._val(newValue);

            return newValue;
        },

        _val: function(value) {
            this.value = value;

            this.$field.val(value);
            this.$value.text(value);

            if (self._state === 'saved') {
                this.save(this);
            }
        }
    };

    var MeasurementField_ = construct(MeasurementField);

    var weightUNIT = {'lbs': 'pounds (lbs)', 'kg': 'kilograms (kg)', 'st': 'stone (st)'}[NourishUser.weightunits];
    var lengthUNIT = {'in': 'inches (in)', 'cm': 'centimeters (cm)'}[NourishUser.lengthunits];

    var GIRTH_FIELDS = [],
        WEIGHT_FIELD = [];

    WEIGHT_FIELD.push(new MeasurementField_({ type: 'weight', period: 'week', unit: weightUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_neck', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_shoulder', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_arm', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_chest', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_waist', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_hip', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_thigh', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_calf', period: 'week', unit: lengthUNIT }));
/*
    new MeasurementField_({ type: 'skinfold_abdominal', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_triceps', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_chest', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_midaxillary', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_subscapular', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_suprailiac', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_thigh', period: 'month', unit: 'millimeters (mm)' });
*/
    jQuery('#submit-weekly-progress-button').click(function() {

        event.preventDefault(); // Disable POST action - handle it in AJAX instead

        jQuery('#validation-errors').remove();

        // Make sure at least one of the progress form sections are completed.
        var weightMissing = bool(jQuery('.validate-body-weight').find('.measurement-field-container:visible').length);
        var girthsMissing = jQuery('.validate-girth-measurements').find('.measurement-field-container:visible').length;
        var photosMissing = bool( jQuery('.validate-photos').find('img.photo.null').length );
        var otherMissing = ( jQuery('textarea[name=essay1]').val().length == 0 ) ? true: false;

        if ((jQuery('#photos').length > 0) && (weightMissing && (girthsMissing >= 8) && photosMissing && otherMissing)) {

            jQuery('.e20r-progress-form tfoot tr td:eq(1)').prepend(
                '<div class="red-notice" id="validation-errors" style="font-size: 16px;">\
                      <strong>You must answer at least one of the sections to complete the assignment:</strong>\
                      <br/><br/>\
                      <ul style="margin-bottom: 0;">\
                        <li><a href="' + location.href + '#body-weight">Body Weight</a></li>\
                        <li><a href="' + location.href + '#girth-measurements">Girth Measurements</a></li>\
                        <li><a href="' + location.href + '#photos">Photos</a></li>\
                        <li><a href="' + location.href + '#other-indicators">Other Progress Indicators</a></li>\
                      </ul>\
                \</div>'
            );
        }
        else if ((jQuery('#photos').length == 0) && (weightMissing && (girthsMissing >= 8) && otherMissing)) {

            jQuery('.e20r-progress-form tfoot tr td:eq(1)').prepend(
                '<div class="red-notice" id="validation-errors" style="font-size: 16px;">\
                      <strong>You must answer at least one of the sections to complete the assignment:</strong>\
                      <br/><br/>\
                      <ul style="margin-bottom: 0;">\
                        <li><a href="' + location.href + '#body-weight">Body Weight</a></li>\
                        <li><a href="' + location.href + '#girth-measurements">Girth Measurements</a></li>\
                        <li><a href="' + location.href + '#other-indicators">Other Progress Indicators</a></li>\
                      </ul>\
                \</div>'
            );
        }
        else if (girthsMissing > 0 && girthsMissing <= 7) {

            jQuery('.e20r-progress-form tfoot tr td:eq(1)').prepend(
                '<div class="red-notice" id="validation-errors" style="font-size: 16px;">\
                      <strong>You have missed some of the girth measurements. Please check the values and re-submit.</strong>\
                      <br/><br/>\
                      <ul style="margin-bottom: 0;">\
                        <li><a href="' + location.href + '#girth-measurements">Girth Measurements</a></li>\
                      </ul>\
                \</div>'
            );
        }
        else {

            // The user has completed enough of the progress form to let them proceed.
            jQuery.ajax ({
                url: e20r_progress.ajaxurl,
                type: 'POST',
                timeout: 10000,
                dataType: 'JSON',
                data: {
                    'article-id': jQuery('#article_id').val(),
                    'program-id': jQuery('#program_id').val(),
                    'action': 'saveMeasurementForUser',
                    'e20r-progress-nonce': jQuery('#e20r-progress-nonce').val(),
                    'date': jQuery('#date').val(),
                    'user-id': NourishUser.user_id,
                    'measurement-type': 'completed',
                    'measurement-value': 1
                },
                error: function($response, $errString, $errType) {
                    console.log($errString + ' error returned from saveMeasurementForUser action: ' + $errType );
                    return;
                },
                success: function($response) {
                    // location.href = e20r_progress.settings.measurementSaved;
                    console.log('Redirect to: ' + e20r_progress.settings.measurementSaved);
                    location.href = e20r_progress.settings.measurementSaved;
                }
            });
        }
    });

    var ProgressQuestionnaire = {
        init: function() {
            jQuery('#progress-questionnaire')
                .find('input[name^=pquestion]')
                .click(function() {
                    var $data = {
                        'action': 'saveMeasurementForUser',
                        'e20r-progress-nonce': jQuery('#e20r-progress-nonce').val(),
                        'date': jQuery('#date').val(),
                        'measurement-type': jQuery(this).attr('data-measurement-type'),
                        'measurement-value': jQuery(this).val(),
                        'user-id': NourishUser.user_id,
                        'article-id': jQuery('#article_id').val(),
                        'program-id': jQuery('#program_id').val(),
                    };

                    jQuery.ajax({
                        url: e20r_progress.ajaxurl,
                        type: 'POST',
                        timeout: 10000,
                        dataType: 'JSON',
                        data: $data,
                        error: function($response, $errString, $errType) {
                            console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                            return;
                        }
                    });
                });

            jQuery('textarea[name=essay1]')
                .blur(function() {
                    var $data = {
                        'action': 'saveMeasurementForUser',
                        'e20r-progress-nonce': jQuery( '#e20r-progress-nonce').val(),
                        'date': jQuery('#date').val(),
                        'measurement-type': jQuery(this).attr('data-measurement-type'),
                        'measurement-value': jQuery(this).val(),
                        'user-id': NourishUser.user_id,
                        'article-id': jQuery('#article_id').val(),
                        'program-id': jQuery('#program_id').val()
                    };

                    jQuery.ajax({
                        url: e20r_progress.ajaxurl,
                        type: 'POST',
                        timeout: 10000,
                        dataType: 'JSON',
                        data: $data,
                        error: function($response, $errString, $errType) {
                            console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                            return;
                        },
                        success: function($response) {
                            console.dir($response);
                        }
                    });
                });
        }
    };

    ProgressQuestionnaire.init();

    /* select units */
    jQuery('#save-units')
        .click(function() {
            jQuery('.unit-item-container')
                .find('.units')
                .each(function() {
                    jQuery(this)
                        .hide()
                        .next('.completed')
                        .text(function() {
                            return jQuery(this).prev('select').find('option:selected').text();
                        })
                        .show();
                });

            jQuery(this)
                .removeClass('save-state')
                .addClass('change-state')
                .text('Change Measurement UNITs')
                .blur();

            var queryString = jQuery('#measurement-inputs select').serialize();
            console.log("Query String: " + queryString);

            var $data = {
                'action': 'updateUnitTypes',
                'e20r-progress-nonce': jQuery('#e20r-progress-nonce').val(),
                'querystring': queryString,
                'user-id': NourishUser.user_id
            };

            jQuery.ajax({
                url: e20r_progress.ajaxurl,
                type: 'POST',
                timeout: 10000,
                dataType: 'JSON',
                data: $data,
                error: function($response, $errString, $errType) {
                    console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                },
                success: function() {
                    console.log("Updated all old values in DB")
                }

            });
        });


    jQuery('.help-lightbox-handle')
        .colorbox({
            opacity: .5,
            speed: 500,
            initialWidth: 100,
            initialHeight: 80
        });

    var PhotoUploader = {
        init: function() {
            var self = this;

            jQuery.each(['front', 'side', 'back'], function() {

                self.bindPhotoUploader(this);

                if (jQuery('#photo-' + this).hasClass('null')) {

                    console.log("Hiding for: " + this);
                    jQuery('.delete-photo.' + this).hide();
                    jQuery('.manip-container.' + this).hide();
                }
                else {

                    jQuery('.delete-photo.' + this).closest('tfoot > td').show();
                    jQuery('.manip-container.' + this).closest('tfoot > td').show();
                }
            });

            jQuery('.delete-photo')
                .click(function() {
                    var orientation = jQuery(this).attr('data-orientation');

                    var $data = {
                        'article-id': jQuery('#article_id').val(),
                        'program-id': jQuery('#program_id').val(),
                        'e20r-progress-nonce': jQuery('#e20r-progress-nonce').val(),
                        'image-id': jQuery("#photo-" + orientation + "-url-hidden").val(),
                        'view': orientation,
                        'action': 'deletePhoto',
                        'user-id': NourishUser.user_id
                    };

                    jQuery.ajax({
                        url: e20r_progress.ajaxurl,
                        type: 'POST',
                        timeout: 10000,
                        dataType: 'JSON',
                        data: $data,
                        error: function($response, $errString, $errType) {
                            console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                        },
                        success: function( $response ) {

                            jQuery('#photo-' + orientation)
                                .attr('src', $response.data.imageLink )
                                .addClass('null')
                                .closest('td')
                                .find('.photo-upload-notifier')
                                .hide();

                            // var $photo =  self._getPhoto$(orientation);
                            // self._getPhotoSaveNotifier$($photo).fadeOut('slow');

                            jQuery('.delete-photo.' + orientation).hide();
                        }
                    });

                    // return false;
                });
        },

        bindPhotoUploader: function(orientation) {

            var progress_uploader;
            var self = this;

            var $uploadButton = jQuery('#photo-upload-' + orientation);

            if ($uploadButton.length == 0) {
                return;
            }

            $uploadButton.live( 'click', function(e) {

                e.preventDefault();

                if ( progress_uploader ) {

                    progress_uploader.open();
                    jQuery("#media-attachment-date-filters option[value='all']").each( function() {
                        this.remove();
                    });
                    /*
                    jQuery("#media-attachment-date-filters").each( function() {
                        this.trigger('click');
                    });
                    */
                    return;
                }

                progress_uploader = wp.media.frames.file_frame = wp.media({
                    className: 'media-frame e20r-tracker-frame',
                    title: "Upload & Select " + titleCase(orientation) + " Image",
                    button: {
                        text: 'Use as the ' + titleCase(orientation) + ' Image'
                    },
                    library: {
                        type: 'image'
                    },
                    frame: 'select',
                    editing: false,
                    multiple: false
                });

                // When the file is selected, get the URL and set it as the text field value
                progress_uploader.on("select", function() {

                    var selection = progress_uploader.state().get('selection');
                    var attachment = selection.first().toJSON();

                    var attachment_thumbs = selection.map( function( attachment ) {
                        attachment = attachment.toJSON();
                        if( attachment.id != '' ) { return '<img src="' + attachment.sizes.thumbnail.url + '" id="id-' + attachment.id + '" />'; }
                    }).join(' ');

                    console.log( 'Attacment info:', attachment );

                    jQuery("#photo-" + orientation + "-url-hidden").val( attachment.id );

                    // Save the image value with the measurements data
                    var $data = {
                        'action': 'saveMeasurementForUser',
                        'e20r-progress-nonce': jQuery( '#e20r-progress-nonce').val(),
                        'date': jQuery('#date').val(),
                        'measurement-type': orientation + '_image',
                        'measurement-value': attachment.id,
                        'user-id': NourishUser.user_id,
                        'article-id': jQuery('#article_id').val(),
                        'program-id': jQuery('#program_id').val(),
                        'view': orientation
                    };

                    jQuery.ajax({
                        url: e20r_progress.ajaxurl,
                        type: 'POST',
                        timeout: 10000,
                        dataType: 'JSON',
                        data: $data,
                        error: function($response, $errString, $errType) {
                            console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                        },
                        success: function( $repsponse ) {

                            var $photo =  self._getPhoto$(orientation);
                            $photo
                                .removeClass('null')
                                .load(function() {
                                    setTimeout(function() {
                                        self._getPhotoSaveNotifier$($photo).fadeIn('slow');
                                    }, 800);

                                });

                            setTimeout(function() {
                                jQuery("#photo-" + orientation).attr('src', attachment.sizes.thumbnail.url).fadeIn('slow');
                            }, 800);

                            jQuery('.delete-photo.' + orientation).show();
                        }
                    });
                    return false;
                });

                progress_uploader.open();
                jQuery("#media-attachment-date-filters option[value='all']").each( function() {

                    this.remove();
                });

                /*
                 jQuery("#media-attachment-date-filters").each( function() {
                    this.trigger('click');
                 });
                 */

            });

        },

        _getPhoto$: function(orientation) {
            return jQuery('#photo-' + orientation);
        },

        _getPhotoSaveNotifier$: function($photo) {
            return $photo
                .closest('.photo-container')
                .prev('.photo-upload-notifier');
        }
    }

    PhotoUploader.init();

    jQuery('.change-measurement-unit')
        .click(function() {
            var dimension = jQuery(this).attr('data-dimension');

            jQuery('#selected-' + dimension + '-unit').css('display', 'inline');
            jQuery('#preferred-' + dimension + '-unit').css('display', 'none');
        });

    jQuery('#selected-length-unit, #selected-weight-unit')
        .each(function() {
            var dimension = jQuery(this).attr('id').split('-')[1]; // "weight" or "length"

            if ( dimension == 'weight' ) {
                var currentValue = NourishUser.weightunits;
            }
            else if ( dimension == 'length' ) {
                var currentValue = NourishUser.lengthunits
            }

            jQuery(this).val(currentValue);

            jQuery('#preferred-' + dimension + '-unit').text(UNIT[currentValue]);
        })
        .change(function() {
            var dimension = jQuery(this).attr('id').split('-')[1]; // "weight" or "length"

            var newUnitAbbr = jQuery(this).val(); // "in"
            var newUnitFull = jQuery(this).child('option:selected').text(); // "inches (in)"

            jQuery('.unit.' + dimension).text(newUnitFull);

            // update the actual objects (new MeasurementField)
            var fieldObjectArr = (dimension == 'weight')
                ? WEIGHT_FIELD : GIRTH_FIELDS;


            for(var i = 0, length = fieldObjectArr.length; i < length; i++) {
                var measurementField = fieldObjectArr[i];

                var newMeasurementValue = measurementField.convertUnit(newUnitAbbr);

                // update last week measurement hash table to avoid users getting "large difference from last week" alert
                LAST_WEEK_MEASUREMENTS[measurementField.type] = newMeasurementValue;
            }

            /* Saving & updating measurements & unit type if the user chooses to change it */

            var $data = {
                'action': 'updateUnitTypes',
                'e20r-progress-nonce': jQuery('#e20r-progress-nonce').val(),
                'user-id': NourishUser.user_id,
                'dimension': dimension, // "weight" or "length"
                'value': newUnitAbbr // i.e. "lbs" or "cm", etc
            };

            // persist to database
            jQuery.ajax({
                url: e20r_progress.ajaxurl,
                type: 'POST',
                timeout: 10000,
                dataType: 'JSON',
                data: $data,
                error: function($response, $errString, $errType) {
                    console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                    return;
                },
                success: function($response) {
                    console.log('Response: ' + $response);
                }
            });

            jQuery('#selected-' + dimension + '-unit').hide();
            jQuery('#preferred-' + dimension + '-unit')
                .text(newUnitFull)
                .css('display', 'inline');
        });

    jQuery(new Image)
        .attr('src', e20r_progress.settings.imagepath + 'myotape.png')
        .css('display', 'none')
        .appendTo(document.body);

    function checkFormCompletion() {
        var $data = {
            'action': 'checkCompletion',
            'article-id': jQuery('#article_id').val(),
            'program-id': jQuery('#program_id').val(),
            'date': jQuery('#date').val(),
            'e20r-progress-nonce': jQuery( '#e20r-progress-nonce' ).val(),
            'user-id': NourishUser.user_id
        };

        jQuery.ajax({
            url: e20r_progress.ajaxurl,
            type: 'POST',
            timeout: 10000,
            dataType: 'JSON',
            async: false,
            data: $data,
            error: function($response, $errString, $errType) {
                console.log($errString + ' error returned from ' + $data['action'] + ' action: ' + $errType );
                return;
            },
            success: function($response) {
                //var $resp = jQuery.map( $response, function(el){ return el; });
                console.log('Response: ');
                console.dir( $response );

                if ( $response.data.progress_form_completed  == true ) {
                    console.log("Setting form as saved");
                    formToSavedState();
                }
            }
        });
    }

    function formToSavedState() {
        var isInSavedState = (jQuery('#saved-state-message').length >= 1);

        if (isInSavedState) {
            return;
        }

        // hide submit button
        jQuery('.e20r-progress-form > tfoot').hide();

        // show saved message
        jQuery('<div style="background-image: url( ' + e20r_progress.settings.imagepath + 'checked.png); margin: 12px 0pt; background-position: 24px 9px;" class="green-notice big" id="saved-state-message">\
              <strong>You have completed this Progress Update.</strong> <a href="' + e20r_progress.settings.measurementSaved + '">Return to Lesson</a>.\
            </div>').appendTo('#e20r-progress-canvas');
    }

//    jQuery('#basic-photo-uploader-submit')
//        .removeAttr('disabled');

    if ( false === bool( e20r_progress.user_info.display_birthdate ) ) {
        jQuery('#birth-date').hide();
    }

    jQuery(".inline").colorbox({inline: true, width: '60%'})
    checkFormCompletion();
});
