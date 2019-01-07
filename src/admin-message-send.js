var tujaMessageSend = (function () {

  function onTemplateSelect ($, event) {
    var value = event.target.dataset.value
    var subject = decodeURIComponent(value.substr(0, value.indexOf(';')))
    $('#tuja-message-subject').val(subject)
    var body = decodeURIComponent(value.substr(value.indexOf(';')+1))
    $('#tuja-message-body').val(body)
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
