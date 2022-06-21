const tujaMaps = (function () {
    const COORDINATE_PRECISION = 5
    const HEIGHT_MARGIN = 100 // Semi-arbitrary number to reduce risk of scrolling because of page header and such.
    const LEAFLET_AVERAGE_LETTER_WIDTH = 5.6 // Value determined through trial-and-error.

    function setMarker(markerId, lat, long) {
        const controlsContainer = document.getElementById(markerId)

        const nameField = document.getElementById(controlsContainer.dataset.nameFieldId)
        nameField.value = nameField.value || 'Kontroll'

        const latField = document.getElementById(controlsContainer.dataset.latFieldId)
        latField.value = lat.toFixed(COORDINATE_PRECISION)

        const longField = document.getElementById(controlsContainer.dataset.longFieldId)
        longField.value = long.toFixed(COORDINATE_PRECISION)
    }

    function deleteMarker(markerId) {
        const controlsContainer = document.getElementById(markerId)
        document.getElementById(controlsContainer.dataset.nameFieldId).value = ''
        document.getElementById(controlsContainer.dataset.latFieldId).value = ''
        document.getElementById(controlsContainer.dataset.longFieldId).value = ''
        document.getElementById(controlsContainer.dataset.latFieldId).style.backgroundColor = ''
        document.getElementById(controlsContainer.dataset.longFieldId).style.backgroundColor = ''
    }

    let markerCounter = 0
    const GOLDEN_ANGLE = 137.5
    function nextMarkerColor() {
        const degrees = (markerCounter * GOLDEN_ANGLE) % 360
        markerCounter++
        return {
            light: "hsl(" + degrees + ", 75%, 50%, 0.25)",
            dark: "hsl(" + degrees + ", 100%, 60%, 0.75)"
        };
    }

    return {
        init: function ($) {
            let currentMarkerId = null

            const mapMarkers = {}

            const container = $('#tuja-map-page')
            const statusContainer = $('#tuja-map-component-overlay')
            const desiredHeight = window.innerHeight - container.offset().top - HEIGHT_MARGIN
            container.height(desiredHeight)
            $('#tuja-map-component').height(desiredHeight)

            function createLeafletIcon(label, lightColor, darkColor) {
                const approxWidth = label.length * LEAFLET_AVERAGE_LETTER_WIDTH
                return L.divIcon({
                    className: 'tuja-map-marker-icon-wrapper',
                    html: '' +
                        '<div class="tuja-map-marker-icon-outer" style="background-color: ' + lightColor + '">' +
                        '   <div class="tuja-map-marker-icon-inner" style="background-color: ' + darkColor + '"></div>' +
                        '</div>' +
                        '<div class="tuja-map-marker-label" style="width: ' + approxWidth + 'px; left: ' + (-approxWidth / 2) + 'px;">' + label + '</div>',
                    iconSize: L.point(2, 2)
                })
            }

            function createOnDragEndHander(id) {
                return function (event) {
                    const { lat, lng } = this.getLatLng()
                    setMarker(id, lat, lng)
                    refreshMap()
                }
            }

            function refreshMap() {
                document.querySelectorAll('.tuja-map-marker-controls').forEach(node => {
                    const id = node.id
                    const name = document.getElementById(node.dataset.nameFieldId).value
                    if (!!name) {
                        const latField = document.getElementById(node.dataset.latFieldId)
                        const longField = document.getElementById(node.dataset.longFieldId)
                        const lat = latField.value
                        const long = longField.value
                        // Marker should be shown on map.
                        if (mapMarkers[id]) {
                            // Marker already exists. Do nothing.
                        } else {
                            // Marker needs to be created
                            const { light: lightColor, dark: darkColor } = nextMarkerColor()
                            latField.style.backgroundColor = lightColor
                            longField.style.backgroundColor = lightColor
                            const newMarker = L.marker(
                                [lat, long],
                                {
                                    title: name,
                                    icon: createLeafletIcon(node.dataset.shortLabel, lightColor, darkColor),
                                    draggable: true
                                })
                                .on('dragend', createOnDragEndHander(id))
                            newMarker.addTo(map);
                            mapMarkers[id] = newMarker
                        }
                    } else {
                        // Marker should NOT be shown on map.
                        if (mapMarkers[id]) {
                            // Marker needs to be removed from the map.
                            mapMarkers[id].remove()
                            delete mapMarkers[id]
                        } else {
                            // Do nothing.
                        }
                    }
                })
            }

            function zoomToFit() {
                const coords = Object.values(mapMarkers).filter(Boolean).map(marker => marker.getLatLng())
                const latMin = Math.min(...coords.map(({ lat }) => lat))
                const latMax = Math.max(...coords.map(({ lat }) => lat))
                const longMin = Math.min(...coords.map(({ lng }) => lng))
                const longMax = Math.max(...coords.map(({ lng }) => lng))
                map.fitBounds(
                    L.latLngBounds(
                        L.latLng(latMin, longMin),
                        L.latLng(latMax, longMax)
                    )
                )
            }

            $('.tuja-map-marker-pin-button').on('click', function (event) {
                const controlsContainer = event.target.parentNode;

                currentMarkerId = controlsContainer.id

                L.DomUtil.addClass(map._container, 'crosshair-cursor-enabled');
                statusContainer.text('Klicka för att placera ut ' + controlsContainer.dataset.shortLabel).show()
            })

            $('.tuja-map-marker-delete-button').on('click', function (event) {
                const controlsContainer = event.target.parentNode;

                deleteMarker(controlsContainer.id)
                refreshMap()
            })

            const onClickHandler = function (e) {
                if (currentMarkerId) {
                    setMarker(currentMarkerId, e.latlng.lat, e.latlng.lng)
                    refreshMap()

                    currentMarkerId = null

                    L.DomUtil.removeClass(map._container, 'crosshair-cursor-enabled');
                    statusContainer.hide()
                }
            }
            const map = L.map('tuja-map-component').setView([59.33228, 18.064106], 13).on('click', onClickHandler);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            refreshMap()
            zoomToFit()
        }
    }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
    tujaMaps.init($)
})
