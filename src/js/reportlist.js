function setUpMobileView()
{
    const tables = document.getElementsByClassName('einsatzverwaltung-reportlist');
    for (let tablecounter = 0; tablecounter < tables.length; tablecounter++) {
        const table = tables.item(tablecounter);
        const cells = table.getElementsByClassName('smallscreen');
        for (let cellcounter = 0; cellcounter < cells.length; cellcounter++) {
            const cell = cells.item(cellcounter);
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