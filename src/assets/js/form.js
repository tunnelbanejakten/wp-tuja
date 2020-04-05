var tujaForm = (function () {

  // Credits: https://codeburst.io/throttling-and-debouncing-in-javascript-b01cad5c8edf
  const debounce = function (func, delay) {
    let inDebounce
    return function () {
      const context = this
      const args = arguments
      clearTimeout(inDebounce)
      inDebounce = setTimeout(() => func.apply(context, args), delay)
    }
  }

  return {
    init: function ($) {
      var $doc = $(document)
      var $scrollField = $('#tuja_formshortcode__scroll')

      const onFormChange = function (event) {
        $(this).closest('form').addClass('tuja-changed')

      }
      $('#tuja-form input').on('change', onFormChange);
      $('#tuja-form select').on('change', onFormChange);
      $('#tuja-form textarea').on('change', onFormChange);

      $doc.scroll(debounce(function (event) {
        var viewportHeight = $(window).height()
        var documentHeight = $doc.outerHeight()
        var scrollTop = $doc.scrollTop()
        var scrollPercent = scrollTop / (documentHeight - viewportHeight)
        $scrollField.val(scrollPercent)
      }, 250))

      if ($scrollField.val() !== '') {
        var viewportHeight = $(window).height()
        var documentHeight = $doc.outerHeight()
        var scrollPercent = parseFloat($scrollField.val())
        var scrollTop = scrollPercent * (documentHeight - viewportHeight)
        $doc.scrollTop(scrollTop)
      }
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  setTimeout(function () {
    tujaForm.init($)
  }, 1) // Need to delay initiation until after "first real paint event", and waiting 1 ms seems to do the trick.
})

