var tujaReportPoints = (function () {
  // TODO: Don't use jQuery

  const REFRESH_INTERVAL_SECONDS = 60 // TODO: Set proper update interval

  let isUpdating = false

  const displayWarningMessage = function (message) {
    document.getElementById('tuja-report-points-warning-message').innerHTML = message
    document.getElementById('tuja-report-points-warning-message-container').style.display = 'flex'
  }

  const dismissWarningMessage = function () {
    document.getElementById('tuja-report-points-warning-message-container').style.display = 'none'
  }

  // Credits: https://codeburst.io/throttling-and-debouncing-in-javascript-b01cad5c8edf
  const debounce = function (func, delay) {
    let inDebounce
    return function () {
      const context = this
      const args = arguments
      clearTimeout(inDebounce)
      inDebounce = setTimeout(function () {
        return func.apply(context, args)
      }, delay)
    }
  }

  const updateLockValue = function (groupId, newLockValue) {
    const section = document.querySelector(`section.tuja-team-score-container[data-group-id="${groupId}"]`)
    section.dataset.lockValue = newLockValue
  }

  return {
    init: function ($) {
      var $doc = $(document)
      var $config = $(document.getElementById('tuja-report-points-data'))
      var userKey = $config.data('userKey')
      var stationId = $config.data('stationId')
      var apiUrl = $config.data('uploadUrl')

      document.getElementById('tuja-report-points-warning-message-button').addEventListener('click', dismissWarningMessage)

      setInterval(function () {
        if (isUpdating) {
          console.log('Skip this one time. Page is already being updated.')
          return
        }
        isUpdating = true
        $('section.tuja-team-score-container').toggleClass('tuja-team-score-loading')
        var data = new FormData()
        data.append('action', 'tuja_station_points_get_all')
        data.append('station', stationId)
        data.append('user', userKey)
        fetch(apiUrl, {
          method: 'POST',
          body: data
        })
          .then(response => {
            return Promise.all([Promise.resolve(response.ok), response.json()])
          })
          .then(([ok, data]) => {
            if (!ok) {
              throw new Error(data.error ?? 'Failed to fetch latest points.')
            }
            data.forEach(({ group_id: groupId, lock: newLockValue, points }) => {
              const section = document.querySelector(`section.tuja-team-score-container[data-group-id="${groupId}"]`)
              if (section.dataset.lockValue !== newLockValue) {
                console.log(`Lock for group ${groupId} has changed`)
                section.dataset.lockValue = newLockValue
                document.getElementById(`tuja_crewview__station__${stationId}__${groupId}`).value = points
              }
            });
            $('section.tuja-team-score-container').toggleClass('tuja-team-score-loading')
            isUpdating = false
          })
          .catch(error => {
            displayWarningMessage(error.message)
            $('section.tuja-team-score-container').toggleClass('tuja-team-score-loading')
            isUpdating = false
          })
      }, REFRESH_INTERVAL_SECONDS * 1000)

      const onInputChange = function (event) {
        if (isUpdating) {
          console.log('Skip this one time. Page is already being updated.')
          return
        }
        isUpdating = true
        var $input = $(this)
        var $section = $input.closest('section')
        var groupId = $section.get(0).dataset.groupId
        var lockValue = $section.get(0).dataset.lockValue

        var data = new FormData()
        data.append('action', 'tuja_station_points_set')
        data.append('station', stationId)
        data.append('group', groupId)
        data.append('user', userKey)
        data.append('lock', lockValue)
        data.append('points', parseInt($input.val()))

        $section.toggleClass('tuja-team-score-loading')
        fetch(apiUrl, {
          method: 'POST',
          body: data
        })
          .then(response => {
            return Promise.all([Promise.resolve(response.ok), response.json()])
          })
          .then(([ok, data]) => {
            if (!ok) {
              throw new Error(data.error ?? 'Failed to fetch latest points.')
            }
            updateLockValue(groupId, data.lock)
            $section.toggleClass('tuja-team-score-loading')
            isUpdating = false
          })
          .catch(error => {
            displayWarningMessage(error.message)
            $section.toggleClass('tuja-team-score-loading')
            isUpdating = false
          })
      }
      $('.tuja-field input').on('change', debounce(onInputChange, 1000));
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaReportPoints.init($)
})

