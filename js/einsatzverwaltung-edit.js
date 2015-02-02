jQuery(document).ready(function() {
    var datumsregex = /^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01]) ([01][0-9]|2[0-3]):([0-5][0-9])$/;
    var hinweistext = 'Bitte das folgende Format einhalten: JJJJ-MM-TT hh:mm, z.B. <strong>2014-01-31 13:37</strong>';
    einsatzverwaltung_register_and_execute('keyup', 'einsatzverwaltung_alarmzeit', datumsregex, hinweistext, false);
    einsatzverwaltung_register_and_execute('keyup', 'einsatzverwaltung_einsatzende', datumsregex, hinweistext, true);

    var used_values = jQuery("#einsatzleiter_used_values");
    if (used_values != undefined) {
        var einsatzleiter_namen = used_values.val().split(',');
        jQuery("#einsatzverwaltung_einsatzleiter").autocomplete({
            source: einsatzleiter_namen
        });
    }
});

function einsatzverwaltung_checkField(id, regex, msg, allowEmpty)
{
    var field = jQuery('#' + id);
    if(field.length != 0) {
        var val = field.val();
        if((allowEmpty && val == "") || val.match(regex)) {
            jQuery('#' + id + '_hint').html("");
        } else {
             jQuery('#' + id + '_hint').html(msg);
        }
    }
}

function einsatzverwaltung_register_and_execute(event, id, regex, msg, allowEmpty)
{
    jQuery('#' + id).on(event, null, [id, regex, msg, allowEmpty], function() {
        einsatzverwaltung_checkField(id, regex, msg, allowEmpty);
    });
    einsatzverwaltung_checkField(id, regex, msg, allowEmpty);
}
