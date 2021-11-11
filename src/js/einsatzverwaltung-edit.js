const { __ } = wp.i18n;

jQuery(function () {
    const datumsregex = /^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01]) ([01][0-9]|2[0-3]):([0-5][0-9])$/;
    const hinweistext = __('Please use the following format: YYYY-MM-DD hh:mm', 'einsatzverwaltung');

    jQuery('#einsatzverwaltung_alarmzeit').after('&nbsp;<span class="einsatzverwaltung_hint" id="einsatzverwaltung_alarmzeit_hint"></span>');
    jQuery('#einsatz_einsatzende').after('&nbsp;<span class="einsatzverwaltung_hint" id="einsatz_einsatzende_hint"></span>');

    einsatzverwaltung_register_and_execute('blur', 'einsatzverwaltung_alarmzeit', datumsregex, hinweistext, false);
    einsatzverwaltung_register_and_execute('blur', 'einsatz_einsatzende', datumsregex, hinweistext, true);

    const used_values = jQuery("#einsatzleiter_used_values");
    if (used_values.val() !== undefined) {
        const einsatzleiter_namen = used_values.val().split(',');
        jQuery("#einsatz_einsatzleiter").autocomplete({
            source: einsatzleiter_namen
        });
    }

    // Set up autocomplete for incident location input
    jQuery.post(einsatzverwaltung_ajax_object.ajax_url, {
        _ajax_nonce: einsatzverwaltung_ajax_object.nonce,
        action: 'einsatzverwaltung_used_locations',
    }, function (response) {
        if (response.success && response.data) {
            jQuery("#einsatz_einsatzort").autocomplete({
                source: response.data || []
            });
        }
    }, 'json').fail(function () {
        console.error('Could not query used locations');
    });
});

function einsatzverwaltung_checkField(id, regex, msg, allowEmpty)
{
    const field = jQuery('#' + id);
    if (field.length !== 0) {
        const val = field.val();
        jQuery('#' + id + '_hint').html(((allowEmpty && val === "") || val.match(regex)) ? '' : msg);
    }
}

function einsatzverwaltung_register_and_execute(event, id, regex, msg, allowEmpty)
{
    jQuery('#' + id).on(event, null, [id, regex, msg, allowEmpty], function () {
        einsatzverwaltung_checkField(id, regex, msg, allowEmpty);
    });
    einsatzverwaltung_checkField(id, regex, msg, allowEmpty);
}
