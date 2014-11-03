/**
 * Created by sjolshag on 11/1/14.
 */

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
        'girth_upperarm': 3,
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
            this.__overrideDifferenceCheck = 0;

            if (bool(this.$girthImage.length)) {
                this.$girthImage.addClass(CPUSER['gender']);

                if (this._isSkinfold && 'F' === CPUSER['gender']) { // ad hoc
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

                if (self._isSkinfold) { // ad hoc
                    self.$girthImage.insertBefore(self.$description);
                    self.$description.filter('p:first').css('margin-top', '8px');
                    self.$fieldContainer.css('float', 'left').css('margin-top', '12px').css('width', '520px');
                    self.$savedContainer.css('float', 'left').css('margin-top', '12px').css('width', '520px');
                }

                if (bool(self.$girthImage.length)) {
                    self.$girthImage.addClass('active');
                    self.$girthImage.css('background-image', function(src) {
                        return 'url(' + src.match(/img=([^&]+)/)[1] + ')';
                    });
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


            /* */

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

        activate: function(self) {
            self.changeState('active');
        },

        attemptSave: function(self) {

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
                            jQuery('.bouyant-error button.override-difference-check')
                                .click(function() {
                                    self.__overrideDifferenceCheck = 1;
                                    self.attemptSave(self);
                                });

                            jQuery('.bouyant-error button.cancel')
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
            var data = {
                'action': 'saveMeasurement',
                'article-id': e20r_tracker.settings.article_id,
                'measurement-type': self.type,
                'measurement-value': self.value,
                'uid': CPUSER.id
            };

            jQuery.post('cpds-assignments.php', data, function(response) {
                console.log('Response: ' + response);
            });

            self.changeState('saved');
        },

        edit: function(self) {
            //self.changeState('edit');
            self.$field.focus();
        },

        _clearErrors: function() {
            jQuery('.bouyant-error[data-measurement-type="' + this.type + '"]')
                .remove();
        },

        _displayError: function(errorText) {
            jQuery('<div class="bouyant-error" data-measurement-type="' + this.type + '">' + errorText + '</div>')
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

    var weightUNIT = {'lbs': 'pounds (lbs)', 'kg': 'kilograms (kg)', 'st': 'stone (st)'}[e20r_tracker.user_info.weightunits];
    var lengthUNIT = {'in': 'inches (in)', 'cm': 'centimeters (cm)'}[e20r_tracker.user_info.lengthunits];

    var GIRTH_FIELDS = [],
        WEIGHT_FIELD = [];

    WEIGHT_FIELD.push(new MeasurementField_({ type: 'weight', period: 'week', unit: weightUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_neck', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_upperarm', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_shoulder', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_chest', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_waist', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_hip', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_thigh', period: 'week', unit: lengthUNIT }));
    GIRTH_FIELDS.push(new MeasurementField_({ type: 'girth_calf', period: 'week', unit: lengthUNIT }));

    new MeasurementField_({ type: 'skinfold_abdominal', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_triceps', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_chest', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_midaxillary', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_subscapular', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_suprailiac', period: 'month', unit: 'millimeters (mm)' });
    new MeasurementField_({ type: 'skinfold_thigh', period: 'month', unit: 'millimeters (mm)' });

    jQuery('#submit-wpu-button').click(function() {
        jQuery('#validation-errors').remove();

        // Ensure at least one section of the form is answered
        var weightMissing = bool(jQuery('.validate-body-weight').find('.measurement-field-container:visible').length);
        var girthsMissing = jQuery('.validate-girth-measurements').find('.measurement-field-container:visible').length;
        var photosMissing = bool(jQuery('.validate-photos').find('img.photo.null').length);
        var otherMissing = (jQuery('textarea[name=essay1]').val().length == 0) ? true: false;

        if ((jQuery('#photos').length > 0) && (weightMissing && (girthsMissing >= 8) && photosMissing && otherMissing)) {
            jQuery('.weekly-progress-form tfoot tr td:eq(1)')
                .prepend('<div class="red-notice" id="validation-errors" style="font-size: 16px;">\
          <strong>You must answer at least one of the sections to complete the assignment:</strong>\
          <br/><br/>\
          <ul style="margin-bottom: 0;">\
            <li><a href="' + location.href + '#body-weight">Body Weight</a></li>\
            <li><a href="' + location.href + '#girth-measurements">Girth Measurements</a></li>\
            <li><a href="' + location.href + '#photos">Photos</a></li>\
            <li><a href="' + location.href + '#other-indicators">Other Progress Indicators</a></li>\
          </ul>\
        \</div>');
        }
        else if ((jQuery('#photos').length == 0) && (weightMissing && (girthsMissing >= 8) && otherMissing)) {
            jQuery('.weekly-progress-form tfoot tr td:eq(1)')
                .prepend('<div class="red-notice" id="validation-errors" style="font-size: 16px;">\
          <strong>You must answer at least one of the sections to complete the assignment:</strong>\
          <br/><br/>\
          <ul style="margin-bottom: 0;">\
            <li><a href="' + location.href + '#body-weight">Body Weight</a></li>\
            <li><a href="' + location.href + '#girth-measurements">Girth Measurements</a></li>\
            <li><a href="' + location.href + '#other-indicators">Other Progress Indicators</a></li>\
          </ul>\
        \</div>');
        }
        else if (girthsMissing > 0 && girthsMissing <= 7) {
            jQuery('.weekly-progress-form tfoot tr td:eq(1)')
                .prepend('<div class="red-notice" id="validation-errors" style="font-size: 16px;">\
          <strong>You have missed some girth measurements. Please check the values and re-submit.</strong>\
          <br/><br/>\
          <ul style="margin-bottom: 0;">\
            <li><a href="' + location.href + '#girth-measurements">Girth Measurements</a></li>\
          </ul>\
        \</div>');
        }
        else {
            // passed
            var data = {
                'article-id': e20r_tracker.settings.article_id,
                'action': 'saveMeasurement',
                'measurement-type': 'completed',
                'measurement-value': 1
            };

            jQuery.post('cpds-assignments.php', data, function(response) {
                location.href = '/members/cp-home.php?wpucompleted=1';
            });
        }
    });

    var ProgressQuestionnaire = {
        init: function() {
            jQuery('#progress-questionnaire')
                .find('input[name^=pquestion]')
                .click(function() {
                    var data = {
                        'action': 'saveMeasurement',
                        'measurement-type': jQuery(this).attr('data-measurement-type'),
                        'measurement-value': jQuery(this).val(),
                        'assignment-id': e20r-tracker.user_info.article_id
                    }


                    jQuery.post('cpds-assignments.php', data,
                        function(response) {
                            console.log(response);
                        });
                });

            jQuery('textarea[name=essay1]')
                .blur(function() {
                    var data = {
                        'action': 'saveMeasurement',
                        'measurement-type': jQuery(this).attr('data-measurement-type'),
                        'measurement-value': jQuery(this).val(),
                        'article-id': e20r_tracker.settings.article_id
                    }


                    jQuery.post('cpds-assignments.php', data,
                        function(response) {
                            console.log(response);
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

            jQuery.post('cp-participantSaveSettings.php?savesettings=true&' + queryString, function(response) {

            });
        });
/*
    jQuery('.help-lightbox-handle')
        .colorbox({
            opacity: .5,
            speed: 500,
            initialWidth: 100,
            initialHeight: 80
        });
*/
/*
    var PhotoUploader = {
        init: function() {
            var self = this;

            jQuery.each(['front', 'side', 'back', 'flexedfront', 'flexedside', 'flexedback'], function() {
                self.bindUploadify(this);
                if (jQuery('#photo-' + this).hasClass('null')) {
                    jQuery('.delete-photo.' + this).hide();
                    jQuery('.manip-container.' + this).hide();
                }
                else {
                    jQuery('.delete-photo.' + this).closest('tfoot').show();
                    jQuery('.manip-container.' + this).closest('tfoot').show();
                }
            });

            jQuery('.manip-container img')
                .click(function() {
                    var isCounterClockwise = jQuery(this).is(':first-child');

                    var deg = (isCounterClockwise) ? 270 : 90;

                    var orientation = jQuery(this).parent('.manip-container').attr('data-orientation');

                    var $loading = jQuery('<img src="cp-images/ajax-loader.gif" style="display: block; margin: 0 auto; margin-bottom: 8px;" class="loading" />').prependTo(jQuery(this).closest('td'));

                    var postdata = {
                        'action': 'rotatephoto',
                        'article-id': e20r_tracker.settings.article_id,
                        'degrees': deg,
                        'view': orientation,
                        'uid': CPUSER.id
                    };

                    jQuery.post('cpds-assignments.php', postdata, function() {
                        jQuery('#photo-' + orientation)
                            .attr('src', function(src) {
                                return src + '&rand=' + Math.random();
                            })
                            .load(function() {
                                $loading.remove();
                            });
                    })
                    //?action=rotatephoto&assignment-id=[assignmentid]&degrees=90&view=[front|side|back]
                });

            jQuery('.delete-photo')
                .click(function() {
                    var orientation = jQuery(this).attr('data-orientation');

                    var data = {
                        'article-id': e20r_tracker.settings.article_id,
                        'view': orientation,
                        'action': 'removephoto',
                        'uid': CPUSER.id
                    };

                    jQuery.post('cpds-assignments.php', data, function() {
                        jQuery('#photo-' + orientation)
                            .attr('src', 'cp-showImage.php?w=165&uid=' + CPUSER.id + '&img=')
                            .addClass('null')
                            .closest('td')
                            .find('.photo-upload-notifier')
                            .hide();

                        jQuery('.delete-photo.' + orientation).hide();
                    });

                    return false;
                });
        },

        bindUploadify: function(orientation) {
            var self = this;

            var queryString = 'orientation=' + orientation;
            queryString += '&date=' + PROGDATE;
            queryString += '&id=' + CPUSER.id;

            queryString = encodeURIComponent(queryString);

            var $uploadButton = jQuery('#photo-upload-' + orientation);

            if ($uploadButton.length == 0) {
                // S2B asks for relaxed/flexing images. The upload button doesn't
                // exist for the flexing images in LE
                return;
            }

            $uploadButton
                .uploadify({
                    buttonImg: '/members/cp-images/button-choose-photo.png',
                    width: 142,
                    uploader: '/members/js/uploadify/uploadify.swf',
                    script: '/members/cp-uploadify.php?' + queryString,
                    method: 'POST',
                    //script: '/members/js/uploadify/uploadify.php',
                    cancelImg: '/members/js/uploadify/cancel.png',
                    folder: PROGRESS_PHOTO_DIRECTORY,
                    auto: true,
                    fileDesc: 'Please select a standard photo file type (jpg, gif, png)',
                    fileExt: '*.jpg;*.jpeg;*.gif;*.png;',
                    sizeLimit: 10485760,
                    scriptAccess: 'always',
                    onError: function() {
                        console.log('Upload Error: %o', arguments);
                    },

                    onProgress: function() {
                        //console.log('Photo Progress: %o', arguments);
                    },

                    onSelect: function() {
                        self._getPhotoSaveNotifier$(self._getPhoto$(orientation)).hide();

                        console.log('Photo Selected: %o', arguments);
                    },

                    onClearQueue: function() {
                        //console.log('Photo Queue Cleared: %o', arguments);
                    },

                    onComplete: function(event, queueID, file, response, data) {
                        //console.log('Upload Complete: %o', arguments);
//						alert('Upload Complete: ' +  response);

                        var $photo =  self._getPhoto$(orientation);

                        // display a loader

                        var filePath = 'cp-showImage.php?uid=' + CPUSER.id + '&w=165&img=' + response;
                        filePath += '&rand=' + Math.random();

                        //alert (filePath);
                        $photo
                            .attr('src', filePath) // should be cropped copy file
                            .removeClass('null')
                            .load(function() {
                                setTimeout(function() {
                                    self._getPhotoSaveNotifier$($photo).fadeIn('slow');
                                }, 800);

                            });

                        jQuery('.delete-photo.' + orientation).show();
                        jQuery('.manip-container.' + orientation).show();

                    }
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
*/
    /* !jQuery('.change-measurement-unit') */

    jQuery('.change-measurement-unit')
        .click(function() {
            var dimension = jQuery(this).attr('data-dimension');

            jQuery('#selected-' + dimension + '-unit').css('display', 'inline');
            jQuery('#preferred-' + dimension + '-unit').css('display', 'none');
        });

    jQuery('#selected-length-unit, #selected-weight-unit')
        .each(function() {
            var dimension = jQuery(this).attr('id').split('-')[1]; // "weight" or "length"

            var currentValue = CPUSER[dimension + 'units'];

            jQuery(this).val(currentValue);

            jQuery('#preferred-' + dimension + '-unit').text(UNIT[currentValue]);
        })
        .change(function() {
            var dimension = jQuery(this).attr('id').split('-')[1]; // "weight" or "length"

            var newUnitAbbr = jQuery(this).val(); // "in"
            var newUnitFull = jQuery(this).child('option:selected').text(); // "inches (in)"

            jQuery('.unit.' + dimension).text(newUnitFull);

            // update the acutal objects (new MeasurementField)
            var fieldObjectArr = (dimension == 'weight')
                ? WEIGHT_FIELD : GIRTH_FIELDS;


            for(var i = 0, length = fieldObjectArr.length; i < length; i++) {
                var measurementField = fieldObjectArr[i];

                var newMeasurementValue = measurementField.convertUnit(newUnitAbbr);

                // update last week measurement hash table to avoid users getting "large difference from last week" alert
                LAST_WEEK_MEASUREMENTS[measurementField.type] = newMeasurementValue;
            }

            /* TODO: Add Ajax support for saving & updating measurements when unit type changes */

            var data = {
                'userid': CPUSER.id,
                'dimension': dimension, // "weight" or "length"
                'value': newUnitAbbr // "lbs" or "cm"
            };

            // persist to database
            jQuery.post('cp-participantSaveSettings.php', data, function(response) {
                // nothing needs to go here yet, eventually I will have error handling
            });

            /* end alaina */

            jQuery('#selected-' + dimension + '-unit').hide();
            jQuery('#preferred-' + dimension + '-unit')
                .text(newUnitFull)
                .css('display', 'inline');
        });

    jQuery(new Image)
        .attr('src', '/members/resources/myotape.png')
        .css('display', 'none')
        .appendTo(document.body);

    // basic photo uploader
/*
    jQuery('.basic-photo-uploader-toggle')
        .click(function() {
            jQuery('#photo-upload-table.advanced thead').toggle();

            // CHERYL: Added an inner div to the WP template (cp_weekly_update)
            // and toggled on it since toggling on the form was buggy
            jQuery('#basic-photo-uploader-innerdiv').toggle();

            jQuery('#notice-standard-photo-uploader').toggle();
            jQuery('#notice-basic-photo-uploader').toggle();

            return false;
        });
*/
    /*
    jQuery('form#basic-photo-uploader')
        .submit(function() {
            jQuery('#upload-channel').contents().find('body').html('');

            var self = this;

            jQuery(this).after('<div class="yellow-notice" id="notice-photos-are-being-uploaded">Your photos are being uploaded. This can take up to 5 minutes to complete.</div>');

            jQuery('#basic-photo-uploader-submit')
                .trigger('blur')
                .attr('disabled', true);

            jQuery('#basic-photo-uploader-ajax-loading').show();

            // poll for return code
            var pollCount = 0;
            var interval = setInterval(function() {
                if (pollCount === 20000) {
                    clearInterval(interval);

                    jQuery('#basic-photo-uploader')
                        .append('<div class="red-notice">Upload failed, max time exceeded.</div>');
                }

                var response = jQuery('#upload-channel').contents().find('body').html();

                if (response != '') {
                    clearInterval(interval);

                    //checkFormCompletion();

                    // success
                    jQuery('#basic-photo-uploader-submit')
                        .removeAttr('disabled');

                    jQuery('#basic-photo-uploader-ajax-loading').hide();
                    jQuery('#basic-photo-uploader input:file')
                        .each(function() {
                            jQuery(this).val('');
                        });
                    jQuery('#notice-photos-are-being-uploaded').remove();

                    response = jQuery.evalJSON(response);

                    for(var orientation in response.pics) {
                        var photoSrc = response.pics[orientation];

                        if (photoSrc == '') {
                            continue;
                        }

                        var $photo = jQuery('#photo-' + orientation);
                        var filePath = 'cp-showImage.php?w=165&uid=' + CPUSER.id + '&img=' + photoSrc + '&rand=' + Math.random();

                        // this is duplicate code from PhotoUploader onComplete
                        $photo
                            .attr('src', filePath)
                            .removeClass('null')
                            .each(function() {
                                (function($photo) {
                                    $photo.load(function() {
                                        setTimeout(function() {
                                            PhotoUploader._getPhotoSaveNotifier$($photo).fadeIn('slow');
                                        }, 800);
                                    });
                                })($photo);
                            });


                        jQuery('.delete-photo.' + orientation).show();
                        jQuery('.manip-container.' + orientation).show();
                    }

                    // **************************************************************************************************************
                    // CHERYL: Added this to properly handle iPads using iCab. Since iCab wraps the existing file inputs with its own
                    // functionality, we need to remove them and then re-add them so that the fields get reset.
                    // **************************************************************************************************************
                    var new_string;

                    if (response.program_initials == "S2B") {
                        new_string = 	'<table><tbody><tr><td>Choose your Front Photo (<strong>relaxed</strong>)</td><td><input name="photo_front" type="file" /></td></tr>' +
                        '<tr><td>Choose your Side Photo (<strong>relaxed</strong>)</td><td><input name="photo_side" type="file" /></td></tr>' +
                        '<tr><td>Choose your Back Photo (<strong>relaxed</strong>)</td><td><input name="photo_back" type="file" /></td></tr></tbody>' +
                        '<tr><td>Choose your Front Photo (<strong>flexing</strong>):</td><td><input name="photo_flexedfront" type="file" /></td></tr>' +
                        '<tr><td>Choose your Side Photo (<strong>flexing</strong>):</td><td><input name="photo_flexedside" type="file" /></td></tr>' +
                        '<tr><td>Choose your Back Photo (<strong>flexing</strong>):</td><td><input name="photo_flexedback" type="file" /></td></tr></table>';
                    }
                    else {
                        new_string = 	'<table><tbody><tr><td>Choose your Front Photo:</td><td><input name="photo_front" type="file" /></td></tr>' +
                        '<tr><td>Choose your Side Photo:</td><td><input name="photo_side" type="file" /></td></tr>' +
                        '<tr><td>Choose your Back Photo:</td><td><input name="photo_back" type="file" /></td></tr></tbody></table>';
                    }
                    jQuery('#basic-photo-input-fields').html(new_string);
                    // **************************************************************************************************************
                    // End of block
                    // **************************************************************************************************************
                }

                ++pollCount;
            }, 100);
        });
    */
    function checkFormCompletion() {
        var data = {
            'action': 'checkCompletion',
            'article-id': e20r_tracker.settings.article_id,
            'uid': e20r_tracker.user_info.user_id
        };

        jQuery.post('cpds-assignments.php', data, function(response) {
            console.log('Response: ' + response);

            if (jQuery.secureEvalJSON(response)['progress-form-completed']) {
                formToSavedState();
            }
        });
    }

    function formToSavedState() {
        var isInSavedState = (Q('#saved-state-message').length >= 1);

        if (isInSavedState) {
            return;
        }

        // hide submit button
        //jQuery('.weekly-progress-form > tfoot').hide();

        // show saved message
        jQuery('<div style="background-image: url(http://www.precisionnutrition.com/members/cp-images/tick32.png); margin: 12px 0pt; background-position: 24px 9px;" class="green-notice big" id="saved-state-message">\
              <strong>You\'ve completed this week\'s Weekly Progress Update.</strong> <a href="/coaching/home?wpucompleted=1">Return Home</a>.\
            </div>').appendTo('#weekly-progress-container .progress-canvas');
    }

    jQuery('#basic-photo-uploader-submit')
        .removeAttr('disabled');


    // if (false === bool(DISPLAY_BIRTHDATE)) {
    if ( false === bool( e20r_tracker.user_info.display_birthdate ) ) {
        jQuery('#birth-date-row').hide();
    }

    checkFormCompletion();
});