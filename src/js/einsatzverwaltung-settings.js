jQuery(document).ready(function() {
    jQuery("#columns-available").find(".columns ul").sortable({
        connectWith: '#columns-enabled .columns ul',
        forcePlaceholderSize: true,
        helper: 'clone',
        items: 'li',
        opacity: 0.8,
        placeholder: 'dropzone'
    });

    var $columnsEnabled = jQuery("#columns-enabled").find(".columns ul");
    $columnsEnabled.sortable({
        connectWith: '#columns-available .columns ul',
        forcePlaceholderSize: true,
        helper: 'clone',
        items: 'li',
        opacity: 0.8,
        placeholder: 'dropzone',
        update: function(event, ui) {
            jQuery("#einsatzvw_list_columns").val($columnsEnabled.sortable('toArray'));
        }
    });

    // Color picker initialisieren
    if ( jQuery.isFunction( jQuery.fn.wpColorPicker ) ) {
        jQuery( 'input.einsatzverwaltung-color-picker' ).wpColorPicker();
    }
});
