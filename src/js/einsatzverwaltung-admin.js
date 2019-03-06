jQuery(document).ready(function() {
    // Color picker initialisieren
    if ( jQuery.isFunction( jQuery.fn.wpColorPicker ) ) {
        jQuery( 'input.einsatzverwaltung-color-picker' ).wpColorPicker();
    }
});
