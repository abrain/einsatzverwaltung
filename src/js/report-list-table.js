jQuery(function () {
    // Override the WP inline edit post function
    const originalInlineEdit = inlineEditPost.edit;
    inlineEditPost.edit = function ( id ) {
        originalInlineEdit.apply(this, arguments);

        // get the post ID
        let postId = 0;
        if ( typeof( id ) == 'object' ) {
            postId = parseInt(this.getId(id));
        }

        if ( postId > 0 ) {
            let editRow = document.getElementById('edit-' + postId);
            let postRow = document.getElementById('post-' + postId);

            // Fill the inputs
            let reportNumberInput = editRow.querySelector('input[name="einsatz_number"]');
            if (reportNumberInput) {
                reportNumberInput.value = postRow.querySelector('#report_number_' + postId).textContent;
            }
        }
    };
});
