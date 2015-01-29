jQuery(document).ready(function() {
    jQuery("#columns-available").find(".columns ul").sortable({
        connectWith: '#columns-enabled .columns ul',
        forcePlaceholderSize: true,
        helper: 'clone',
        items: 'li',
        opacity: 0.8,
        placeholder: 'dropzone'
    });

    jQuery("#columns-enabled").find(".columns ul").sortable({
        connectWith: '#columns-available .columns ul',
        forcePlaceholderSize: true,
        helper: 'clone',
        items: 'li',
        opacity: 0.8,
        placeholder: 'dropzone'
    });
});
