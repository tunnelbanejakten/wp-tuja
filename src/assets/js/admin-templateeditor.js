var tujaTemplateEditor = (function () {

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

  function initTextarea ($, node) {

    var previewEndpoint = node.dataset.previewEndpoint
    var targetIframeName = node.dataset.target
    const parameters =
      Object.keys(node.dataset)
        .filter(function (key) { return 'param-' === key.substr(0, 6)})
        .reduce(
          function (res, key) {
            const raw = node.dataset[key]
            const paramKey = raw.substr(0, raw.indexOf(':'))
            const paramValue = raw.substr(raw.indexOf(':') + 1)
            res[paramKey] = paramValue
            return res
          },
          {})

    function refreshPreview (template) {
      var form = $('<form></form>')

      form.attr('method', 'post')
      form.attr('action', previewEndpoint)
      form.attr('target', targetIframeName)

      parameters.__template = template
      $.each(parameters, function (key, value) {
        var field = $('<input />')

        field.attr('type', 'hidden')
        field.attr('name', key)
        field.attr('value', value)

        form.append(field)
      })

      // The form needs to be a part of the document in
      // order for us to be able to submit it.
      $(document.body).append(form)
      form.submit()
    }

    var $textarea = $(node).find('textarea')

    var refreshHandler = debounce(function () {
      const template = $textarea.val()
      refreshPreview(template)
    }, 250)

    $textarea.on('change', refreshHandler)
    $textarea.on('keyup', refreshHandler)
  }

  return {
    init: function ($) {
      $('div.tuja-templateeditor').each(function (index, el) {
        initTextarea($, el)
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaTemplateEditor.init($)
})
