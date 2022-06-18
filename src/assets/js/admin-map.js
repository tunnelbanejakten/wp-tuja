const tujaMaps = (function () {
    const COORDINATE_PRECISION = 5
    const HEIGHT_MARGIN = 100 // Semi-arbitrary number to reduce risk of scrolling because of page header and such.
    return {
        init: function ($) {
            const container = $('#tuja-map-page')
            const statusContainer = $('#tuja-map-component-overlay')
            const desiredHeight = window.innerHeight - container.offset().top - HEIGHT_MARGIN
            container.height(desiredHeight)
            $('#tuja-map-component').height(desiredHeight)

            let currentTargets = {}

            $('.tuja-marker-raw-field').on('focus', function (event) {
                currentTargets = {
                    lat: event.target.dataset.latFieldId,
                    long: event.target.dataset.longFieldId,
                    name: event.target.id,
                }
                L.DomUtil.addClass(map._container, 'crosshair-cursor-enabled');
                statusContainer.text('Klicka för att placera ut ' + event.target.dataset.shortLabel)
            })

            const onClickHandler = function (e) {
                if (currentTargets) {
                    document.getElementById(currentTargets.lat).value = e.latlng.lat.toFixed(COORDINATE_PRECISION)
                    document.getElementById(currentTargets.long).value = e.latlng.lng.toFixed(COORDINATE_PRECISION)
                    const nameField = document.getElementById(currentTargets.name)
                    nameField.value = nameField.value || 'Kontroll'

                    currentTargets = null
                    L.DomUtil.removeClass(map._container, 'crosshair-cursor-enabled');
                    statusContainer.text('')
                }
            }
            const map = L.map('tuja-map-component').setView([59.33228, 18.064106], 13).on('click', onClickHandler);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
        }
    }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
    tujaMaps.init($)
})
