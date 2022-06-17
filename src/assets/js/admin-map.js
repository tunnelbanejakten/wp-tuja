const tujaMaps = (function () {
    const COORDINATE_PRECISION = 5
    return {
        init: function ($) {
            const container = $('#tuja-map-page')
            const statusContainer = $('#tuja-map-component-overlay')
            const desiredHeight = window.innerHeight - container.offset().top - 100
            container.height(desiredHeight)
            $('#tuja-map-component').height(desiredHeight)

            let currentMarkerFieldId = null

            $('.tuja-marker-raw-field').on('focus', function (event) {
                currentMarkerFieldId = event.target.id
                L.DomUtil.addClass(map._container, 'crosshair-cursor-enabled');
                statusContainer.text('Klicka för att placera ut ' + event.target.dataset.shortLabel)
            })

            const onClickHandler = function (e) {
                if (currentMarkerFieldId) {
                    const field = $('#' + currentMarkerFieldId)
                    const currentInput = field.val()
                    const label = currentInput.split(' ').slice(2).join(' ') || 'Namn'
                    const { lat, lng } = e.latlng
                    const newInput = [
                        lat.toFixed(COORDINATE_PRECISION),
                        lng.toFixed(COORDINATE_PRECISION),
                        label
                    ].join(' ')
                    field.val(newInput)

                    currentMarkerFieldId = null
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
