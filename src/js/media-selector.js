function einsatzverwaltung_selectMedia(selector)
{
    const einsatzverwaltung_media_uploader = wp.media({
        multiple: false,
        library: {
            type: 'image'
        }
    });

    einsatzverwaltung_media_uploader.on("select", function () {
        const json = einsatzverwaltung_media_uploader.state().get("selection").first().toJSON();

        const input = document.getElementById(selector);
        input.value = json.id;
        const previewImg = document.getElementById('img-' + selector);
        if (previewImg) {
            let size = json.sizes['thumbnail'] || json.sizes['full'];
            previewImg.src = size.url;
        }
    });

    einsatzverwaltung_media_uploader.open();
}

function einsatzverwaltung_clearMedia(selector)
{
    const input = document.getElementById(selector);
    input.value = '';
    const previewImg = document.getElementById('img-' + selector);
    previewImg.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
}
