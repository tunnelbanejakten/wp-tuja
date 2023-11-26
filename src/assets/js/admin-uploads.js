var tujaUploads = (function () {
  return {
    init: function ($) {
      var $favouriteButtons = $('.tuja-toggle-favourite-upload')
      $favouriteButtons.click(function (event) {
        $button = $(this)
        var url = event.target.dataset.endpoint
        var body = new FormData()
        body.append('is_favourite', this.checked)
        fetch(url, {
          method: 'POST',
          body
        })
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaUploads.init($)
})
