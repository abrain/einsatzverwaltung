function setUpMobileView()
{
    var tables = document.getElementsByClassName('einsatzverwaltung-reportlist');
    for (var tablecounter = 0; tablecounter < tables.length; tablecounter++) {
        var table = tables.item(tablecounter);
        var cells = table.getElementsByClassName('smallscreen');
        for (var cellcounter = 0; cellcounter < cells.length; cellcounter++) {
            var cell = cells.item(cellcounter);
            cell.onclick = function (event) {
                // Let links do what they do
                if (event.target.tagName === 'A') {
                    return;
                }

                if (!this.dataset.hasOwnProperty('permalink') || this.dataset.permalink === '') {
                    return;
                }

                document.location.assign(this.dataset.permalink);
            };
        }
    }
}

window.onload = function () {
    setUpMobileView();
};