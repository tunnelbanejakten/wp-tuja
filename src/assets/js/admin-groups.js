var tujaGroups = (function () {
  return {
    init: function ($) {
      $('#tuja_group_toggle_all').change(function () {
        $('.tuja-group-checkbox').click()
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaGroups.init($)
})
