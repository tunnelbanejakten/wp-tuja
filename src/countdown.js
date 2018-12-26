var tujaCountdown = (function () {

  var loadMs = new Date().getTime()

  var MINUTE = 60
  var HOUR = MINUTE * 60
  var DAY = HOUR * 24
  var MONTH = DAY * (365.25 / 12) // Length of average month

  var replace = function (format, replacement) {
    return format.replace('$1', replacement)
  }

  var toFuzzy = function (secondsLeft, formatPast, formatFuture) {

    var amount = Math.abs(secondsLeft)
    var isFuture = secondsLeft > 0

    var unit = 'sekunder'

    if (amount > MONTH) {
      amount = amount / MONTH
      unit = 'månader'
    } else if (amount > DAY) {
      amount = amount / DAY
      unit = 'dagar'
    } else if (amount > HOUR) {
      amount = amount / HOUR
      unit = 'timmar'
    } else if (amount > MINUTE) {
      amount = amount / MINUTE
      unit = 'minuter'
    } else {
      return replace(isFuture ? formatFuture : formatPast, 'mindre än en minut')
    }
    return replace(isFuture ? formatFuture : formatPast, Math.round(amount) + ' ' + unit)
  }

  var createTimeoutHandler = function (span, secondsLeft, formatPast, formatFuture) {

    var tMs = loadMs + (secondsLeft * 1000)

    return function () {
      var nowMs = new Date().getTime()
      var diff = tMs - nowMs
      var secondsLeft = diff / 1000
      span.innerHTML = toFuzzy(secondsLeft, formatPast, formatFuture)
    }
  }

  return {
    init: function ($) {
      var spans = document.querySelectorAll('span.tuja-countdown')
      for (var i = 0; i < spans.length; i++) {
        var span = spans[i]
        var handler = createTimeoutHandler(
          span,
          parseInt(span.dataset.secondsLeft),
          span.dataset.formatPast,
          span.dataset.formatFuture)
        setInterval(handler, 1000)
      }
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaCountdown.init($)
})

