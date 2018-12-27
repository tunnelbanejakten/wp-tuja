var tujaCountdown = (function () {

  var loadMs = new Date().getTime()

  var SECOND = 1
  var MINUTE = SECOND * 60
  var HOUR = MINUTE * 60
  var DAY = HOUR * 24
  var MONTH = DAY * (365.25 / 12) // Length of average month

  var replace = function (format, replacement) {
    return format.replace('$1', replacement)
  }

  var joinTwoTimeValues = function (amount, divisor1, label1, divisor2, label2) {
    var amount1 = Math.floor(Math.abs(amount) / divisor1)
    var amount2 = Math.floor((amount - amount1 * divisor1) / divisor2)
    if (amount2 > 0 && divisor2 > SECOND) {
      return amount1 + ' ' + label1 + ' och ' + amount2 + ' ' + label2
    } else {
      return amount1 + ' ' + label1
    }
  }

  // The PHP and Javascript implementations of "fuzzy time" are very similar but not identical
  var toFuzzy = function (secondsLeft, formatPast, formatFuture) {

    var amount = Math.abs(secondsLeft)
    var isFuture = secondsLeft > 0
    var format = isFuture ? formatFuture : formatPast

    var unit = 'sekunder'

    if (amount > MONTH) {
      return replace(format, joinTwoTimeValues(amount, MONTH, 'månader', DAY, 'dagar'))
    } else if (amount > DAY) {
      return replace(format, joinTwoTimeValues(amount, DAY, 'dagar', HOUR, 'tim'))
    } else if (amount > HOUR) {
      return replace(format, joinTwoTimeValues(amount, HOUR, 'tim', MINUTE, 'min'))
    } else if (amount > MINUTE) {
      return replace(format, joinTwoTimeValues(amount, MINUTE, 'min', SECOND, 's'))
    } else {
      return replace(format, 'mindre än en minut')
    }
    return replace(format, Math.round(amount) + ' ' + unit)
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
        setInterval(handler, 1000) // TODO: Updating every second is probably unnecessarily often.
      }
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaCountdown.init($)
})

