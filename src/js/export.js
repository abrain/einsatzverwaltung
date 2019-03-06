jQuery(function($) {
    let form = $('#export-form'),
        options = form.find('.export-options'),
        inputs = form.find('input[name="format"]');
    
    inputs.change(function() {
        let formatkey = $(this).val();
        options.each(function () {
            let optionContainer = $(this);
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
