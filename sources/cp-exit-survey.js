/**
 * Created by sjolshag on 12/21/14.
 */
$(function () {

    $.ajaxSetup({ cache: false });

    if ($("#surveyForm").data('readonly') == true) {
        $("input, textarea, select").attr('readonly', 'readonly');
        $("input:radio, input:checkbox, select, button[type=submit]").attr('disabled', 'disabled');
    }

    $('input, select, textarea').change(function () {
        $.ajax({
            type: 'post',
            url: 'cp-exit-survey.php?partial=true',
            data: $('#surveyForm').serialize(),
            success: function (data) {}
        });
    });

    $('.checkbox-unhide').click(function () {
        // If .checkbox-unhide is applied to a checkbox element, clicking it toggles the ID specified in the data-unhide-id attribute
        if ($(this).attr('data-unhide-id') == undefined) {
            return;
        }
        if ($(this).is(':checked')) {
            $('#' + $(this).attr('data-unhide-id')).fadeIn(800);
        } else {
            $('#' + $(this).attr('data-unhide-id')).fadeOut(800);
        }
    });

    $('.radio-unhide').click(function () {
        // If .radio-unhide is applied to a radio element, clicking it shows the div for the
        // ID specified in the data-unhide-id attribute

        if ($(this).is(':checked')) {
            var ids_to_unhide = $(this).attr('data-unhide-id').split(",");

            for (i = 0; i < ids_to_unhide.length; i++) {
                $('#' + ids_to_unhide[i]).show();
            }
        }
    });

    $('.radio-hide').click(function () {
        // If .radio-hide is applied to a radio element, clicking it hides the div for the
        // ID specified in the data-hide-id attribute

        if ($(this).is(':checked')) {
            var ids_to_hide = $(this).attr('data-hide-id').split(",");

            for (i = 0; i < ids_to_hide.length; i++) {
                $('#' + ids_to_hide[i]).hide();
            }
        }
    });

    $.each(INTAKEANSWERS, function (key) {
        var val = String(this);
        if (val.length < 1) {
            val = "";
        }

        if (key == 'exercise-types' || key == 'injuries-list' || key == 'groceries' || key == 'cooking' ||
            key == 'shares-meals' || key == 'home-meals' || key == 'eat-out-meals' ||
            key == 'special-diet' || key == 'food-allergies' || key == 'food-sensitivities' ||
            key == 'supplements' || key == 'medications' || key == 'caregiver') {
            brackets = '[]';
            key = key.concat(brackets);
        }

        var selectorString = '[name="' + key + '"]';

        if ($(selectorString).attr("type") == "radio") {
            var selectorString = '[name="' + key + '"][value="' + val + '"]';
            $(selectorString).attr('checked', true);

            // The checkbox on the page has .checkbox-unhide, which means you should show it
            if ($(selectorString).hasClass('checkbox-unhide')) {
                $('#' + $(selectorString).attr('data-unhide-id')).show();
            }

            if ($(selectorString).hasClass('radio-unhide')) {
                var ids_to_unhide = $(selectorString).attr('data-unhide-id').split(",");

                for (i = 0; i < ids_to_unhide.length; i++) {
                    $('#' + ids_to_unhide[i]).show();
                }
            }
        } else if ($(selectorString).attr("type") == "checkbox") {
            var checked_elements = val.split(",");

            for (i = 0; i < checked_elements.length; i++) {
                var selectorString = '[name="' + key + '"][value="' + checked_elements[i] + '"]';
                $(selectorString).attr('checked', true);

                // The checkbox on the page has .checkbox-unhide, which means you should show it
                if ($(selectorString).hasClass('checkbox-unhide')) {
                    $('#' + $(selectorString).attr('data-unhide-id')).show();
                }
            }
        } else {
            $(selectorString).val(val);
        }
    });

    $("#surveyForm").validate({
        rules: {
            "weight": {
                required: true,
                number: true
            },
            "weightunits": {
                required: true
            },
            "exercise-consistency": {
                required: true
            },
            "exercise-frequency": {
                required: true
            },
            "exercise-level": {
                required: true,
                minlength: 1
            },
            "chronic-pain-yesno": {
                required: true
            },
            "chronic-pain-details": {
                required: {
                    depends: function (element) {
                        if ($('input[name=chronic-pain-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "injuries-yesno": {
                required: true
            },
            "injuries-list[]": {
                required: {
                    depends: function (element) {
                        if ($('input[name=injuries-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "injuries-details": {
                required: {
                    depends: function (element) {
                        if ($('input[name=injuries-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "groceries[]": {
                required: true,
                minlength: 1
            },
            "cooking[]": {
                required: true,
                minlength: 1
            },
            "shares-meals[]": {
                required: true,
                minlength: 1
            },
            "home-meals": {
                required: true
            },
            "eat-out-meals": {
                required: true
            },
            "food-allergies-yesno": {
                required: true
            },
            "food-allergies[]": {
                required: {
                    depends: function (element) {
                        if ($('input[name=food-allergies-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "food-sensitivities-yesno": {
                required: true
            },
            "food-sensitivities[]": {
                required: {
                    depends: function (element) {
                        if ($('input[name=food-sensitivities-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "supplements-yesno": {
                required: true
            },
            "supplements[]": {
                required: {
                    depends: function (element) {
                        if ($('input[name=supplements-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "water-intake": {
                required: true
            },
            "protein-intake": {
                required: true
            },
            "vegetable-intake": {
                required: true
            },
            "nutrition-knowledge": {
                required: true
            },
            "nutrition-confidence": {
                required: true
            },
            "medical-issues-yesno": {
                required: true
            },
            "medical-issues": {
                required: {
                    depends: function (element) {
                        if ($('input[name=medical-issues-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "other-treatments-yesno": {
                required: {
                    depends: function (element) {
                        if ($('input[name=medical-issues-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "other-treatments": {
                required: {
                    depends: function (element) {
                        if ($('input[name=other-treatments-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "medications-yesno": {
                required: true
            },
            "medications[]": {
                required: {
                    depends: function (element) {
                        if ($('input[name=medications-yesno][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "employed-yesno": {
                required: true
            },
            "work-shifts": {
                required: {
                    depends: function (element) {
                        if ($('input[name="employed-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "work-hours": {
                required: {
                    depends: function (element) {
                        if ($('input[name="employed-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "work-activity": {
                required: {
                    depends: function (element) {
                        if ($('input[name="employed-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "work-stress": {
                required: {
                    depends: function (element) {
                        if ($('input[name="employed-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "work-travel": {
                required: {
                    depends: function (element) {
                        if ($('input[name="employed-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "student-yesno": {
                required: true
            },
            "student-stress": {
                required: {
                    depends: function (element) {
                        if ($('input[name="student-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "caregiver-yesno": {
                required: true
            },
            "caregiver[]": {
                required: {
                    depends: function (element) {
                        if ($('input[name="caregiver-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                },
                minlength: 1
            },
            "caregiver-stress": {
                required: {
                    depends: function (element) {
                        if ($('input[name="caregiver-yesno"][value=yes]').is(':checked')) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            },
            "home-stress": {
                required: true
            },
            "coping-stress": {
                required: true
            },
            "alcohol-frequency": {
                required: true
            },
            "smoking-frequency": {
                required: true
            },
            "drugs-frequency": {
                required: true
            },
            "research-yesno": {
                required: true
            }
        },
        messages: {
            "groceries[]": "Please select at least 1 option.",
            "cooking[]": "Please select at least 1 option.",
            "shares-meals[]": "Please select at least 1 option.",
            "injuries-list[]": "Please select at least 1 option.",
            "food-allergies[]": "Please select at least 1 option.",
            "food-sensitivities[]": "Please select at least 1 option.",
            "supplements[]": "Please select at least 1 option.",
            "medications[]": "Please select at least 1 option.",
            "caregiver[]": "Please select at least 1 option.",
        },
        errorPlacement: function (error, element) {
            if (element.attr("name") == "weight") {
                error.appendTo(".weight-errormessage");
            } else if (element.attr("name") == "nutrition-knowledge") {
                error.appendTo(".nutrition-knowledge-errormessage");
            } else if (element.attr("name") == "nutrition-confidence") {
                error.appendTo(".nutrition-confidence-errormessage");
            } else if (element.attr("type") == "radio" || element.attr("type") == "checkbox") {
                error.append("<br />").insertBefore(element.parent("div"));
            } else if (element.is("textarea") || (element.attr("type") == "text")) {
                error.append("<br />").insertBefore(element);
            }
        }
    });
});
