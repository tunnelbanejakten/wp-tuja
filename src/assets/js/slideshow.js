var tujaSlideshow = (function () {

  // Source and inspiration: https://css-tricks.com/snippets/jquery/simple-auto-playing-slideshow/

  return {
    start: function ($) {
      $('#tuja-slideshow').show()
      $('#tuja-slideshow > figure:gt(0)').hide()

      var duration = (parseInt($('input[name="duration"]:checked').val()) || 5) * 1000

      var timer = setInterval(function () {
        $('#tuja-slideshow > figure:first')
          .fadeOut(duration / 4)
          .next()
          .fadeIn(duration / 4)
          .end()
          .appendTo('#tuja-slideshow')
      }, duration)

      const stopSlideshow = function () {
        clearInterval(timer)
        $(document).off('keyup', escKeyListener)
        $('#tuja-slideshow-close').off('click', stopSlideshow)
        $('#tuja-slideshow').hide()
      }

      const escKeyListener = function (evt) {
        if (evt.keyCode === 27) {
          stopSlideshow()
        }
      }

      $(document).on('keyup', escKeyListener)
      $('#tuja-slideshow-close').on('click', stopSlideshow)
    }
  }

})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  $('#tuja-slideshow').hide()
  if (document.querySelectorAll('figure').length > 0) {
    tujaSlideshow.start($)
  }
})

