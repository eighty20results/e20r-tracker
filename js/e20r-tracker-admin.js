/**
 * Created by sjolshag on 9/5/14.
 *
 * @todo: Clean up the jQuery.ajax script sample.
 * @todo - Implement ajax_load_client_data php function.
 */

jQuery(document).ready(function ($) {

    function e20rLoadClientData($type) {

        if ($type === 'billing') {

        } else if ($type === 'compliance') {

        } else if ($type === 'assignments') {

        } else if ($type === 'measurements') {

        } else {
            console.log('Nothing to see');
        };

        $('div .seq_spinner').show();

        // Disable save button
        saveBtn.attr('disabled', 'disabled');
        saveBtn.html(e20r-tracker-admin.lang.saving);

        $.ajax({
            url: e20r-tracker-admin.ajaxurl,
            type: 'POST',
            timeout: 5000,
            dataType: 'JSON',
            data: {
                action: 'e20r_ajax_load_client_data',
                hidden_e20r_clients_nonce: $('#e20r-tracker-clients-nonce').val(),
                hidden_e20r_client_id: $('#hidden_e20r_client_id').val(),
                hidden_e20r_client_ : $('#hidden_e20r_client_').val(),
            },
            error: function ($data) {
                if ($data.data !== '') {
                    alert($data.data);
                    pmpro_seq_setErroMsg($data.data);
                }
            },
            success: function ($data) {

                setLabels();

                // Refresh the sequence post list (include the new post.
                if ($data.data !== '') {
                    $('#e20r-client-data').html($data.data);
                }
            },
            complete: function () {

                // Enable the Save button again.
                saveBtn.removeAttr('disabled');

                // Reset the text for the 'Save Settings" button
                saveBtn.html(e20r-tracker-admin.lang.saveSettings);

                // Disable the spinner again
                $('div .seq_spinner').hide();
            }
        });
    };
});

