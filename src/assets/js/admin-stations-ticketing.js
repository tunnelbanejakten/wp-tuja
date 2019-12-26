var tujaStationsTicketing = (function () {

  function onWeightChange ($, event) {
    var twinFieldId = event.target.dataset.twinField
    $('#'+twinFieldId).val(event.target.value)
    return false
  }

  return {
    init: function ($) {
      $('input.tuja-ticket-couponweight').change(onWeightChange.bind(null, $))
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaStationsTicketing.init($)
})
