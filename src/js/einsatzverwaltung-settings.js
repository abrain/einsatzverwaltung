jQuery(function () {
    jQuery("#columns-available").find(".columns ul").sortable({
        connectWith: '#columns-enabled .columns ul',
        forcePlaceholderSize: true,
        helper: 'clone',
        items: 'li',
        opacity: 0.8,
        placeholder: 'dropzone'
    });

    const enabledColumns = jQuery("#columns-enabled").find(".columns ul");
    enabledColumns.sortable({
        connectWith: '#columns-available .columns ul',
        forcePlaceholderSize: true,
        helper: 'clone',
        items: 'li',
        opacity: 0.8,
        placeholder: 'dropzone',
        update: function (event, ui) {
            jQuery("#einsatzvw_list_columns").val(enabledColumns.sortable('toArray'));
        }
    });
});
