/**
 * Created by sjolshag on 1/4/15.
 */

jQuery(function() {

    var $actions = {
        'info': {
            'action': 'e20r_clientDetail',
            'element': '#e20r-client-info',
            'div': '#e20r-progr-info'
        },
        'measurements': {
            'action': 'e20r_measurementDataForUser',
            'element': '#e20r-client-measurements',
            'div': '#e20r-progr-measurements'
        },
        'compliance': {
            'action': 'e20r_complianceData',
            'element': '#e20r-client-compliance',
            'div': '#e20r-progr-compliance'
        },
        'assignment': {
            'action': 'e20r_assignmentsData',
            'element': '#e20r-client-assignments',
            'div': '#e20r-progr-assignment'
        }
    };

    var ProgressView = {
        init: function (self, type) {
            self.$clientIdCtl = jQuery('#e20r_tracker_client');
            // self.$levelIdCtl = jQuery('e20r_levels');
            self.$oldClientId = self.$clientIdCtl.find('option:selected').val();


        },
        measurementsGraph: function($data, $type, $actionStr) {

        },
        saveId: function( handler ) {

        },
        getMemberList: function( levelId ) {

            jQuery('#spin-for-level').show();
            jQuery('#e20r_tracker_client').prop('disabled', true);

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 5000,
                dataType: 'JSON',
                data: {
                    action: 'get_memberlistForLevel',
                    e20r_tracker_levels_nonce: jQuery('#e20r_tracker_levels_nonce').val(),
                    hidden_e20r_level: $levelId
                },
                error: function ($data) {
                    if ($data.data !== '') {
                        alert($data.data);
                    }
                },
                success: function ($data) {

                    // console.dir( $data );

                    // Refresh the sequence post list (include the new post.
                    if ($data.data !== '') {
                        jQuery(".e20r-selectMember").html($data.data);
                        jQuery(".e20r-selectMember").show();
                        jQuery(".e20r-admin-hr").show();
                        jQuery(".e20r-data-choices").show();
                    }
                },
                complete: function () {

                    // Enable the Save button again.
                    // saveBtn.removeAttr('disabled');

                    // Reset the text for the 'Save Settings" button
                    // saveBtn.html(e20r-tracker-admin.lang.saveSettings);

                    jQuery('#spin-for-level').hide();
                    jQuery('#e20r_tracker_client').prop('disabled', false);
                    //jQuery('#e20r_tracker_client').removeAttr('disabled');
                }
            });
        }

    } // End of ProgressMeasurements class

    var $Progress_Measurements = construct(ProgressView);
});
