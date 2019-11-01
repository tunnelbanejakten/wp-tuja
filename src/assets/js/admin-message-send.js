var tujaMessageSend = (function () {

  function onTemplateSelect ($, event) {
    var value = event.target.dataset.value
    var values = value.split(/;/g)
    var subject = decodeURIComponent(values[0])
    $('#tuja-message-subject').val(subject)
    var body = decodeURIComponent(values[1])
    $('#tuja-message-body').val(body)
    var deliveryMethod = decodeURIComponent(values[2])
    $('#tuja-message-deliverymethod').val(deliveryMethod)
    return false
  }

  return {
    init: function ($) {
      $('a.tuja-messages-template-link').click(onTemplateSelect.bind(null, $))
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaMessageSend.init($)
})
