jQuery(document).ready(function() {
    $colAvail = jQuery("#columns-available");
    $colAvail.droppable();
    $colAvail.sortable({
        placeholder: "dropzone",
        helper: "clone",
        connectWith: "#columns-enabled",
        forcePlaceholderSize: true
    });

    $colEnabled = jQuery("#columns-enabled");
    $colEnabled.droppable();
    $colEnabled.sortable({
        placeholder: "dropzone",
        forcePlaceholderSize: true,
        helper: "clone",
        connectWith: '#columns-available'
    });

    jQuery(".evw-column").draggable({
        connectToSortable: "#columns-available, #columns-enabled"
    });
});
