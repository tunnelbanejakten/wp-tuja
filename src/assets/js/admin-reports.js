var tujaReports = (function () {
  return {
    init: function ($) {
      $('div.tuja-admin-report-config').each(function (index, el) {
        var schema = JSON.parse(el.dataset.optionsSchema)
        if (schema) {
          var editor = new JSONEditor(el, {
            schema: schema,
            disable_edit_json: true,
            disable_collapse: true
          })
          editor.on('change', function () {
            var conf = this.getValue()
            $(el).parent().find('a').attr('href', function (index, value) {
              return this.dataset.originalHref.replace('?', '?' + $.param(conf) + '&')
            })
          })
        }
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaReports.init($)
})
