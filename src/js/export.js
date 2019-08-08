jQuery(function($) {
    var form = $('#export-form'),
        options = form.find('.export-options'),
        inputs = form.find('input[name="format"]');
    
    inputs.change(function() {
        var formatkey = $(this).val();
        options.each(function () {
            var optionContainer = $(this);
            if (optionContainer.attr('id') === formatkey + '-options') {
                optionContainer.show();
            } else {
                optionContainer.hide();
            }
        });
    });
    
    // Erste option auswählen
    inputs.first().click();
});
