var vehicle_media_uploader = null;

function selectVehicleMedia(selector, requestedSize)
{
    if (!requestedSize || requestedSize === '') {
        requestedSize = 'thumbnail'
    }

    vehicle_media_uploader = wp.media({
        multiple: false,
        library: {
            type: 'image'
        }
    });

    vehicle_media_uploader.on("select", function () {
        var json = vehicle_media_uploader.state().get("selection").first().toJSON();

        var input = document.getElementById(selector);
        input.value = json.id;
        var previewImg = document.getElementById('img-' + selector);
        if (previewImg) {
            var size = json.sizes[requestedSize];
            if (!size) {
                // TODO
                size = json.sizes['thumbnail'];
            }
            previewImg.src = size.url;
        }
    });

    vehicle_media_uploader.open();
}