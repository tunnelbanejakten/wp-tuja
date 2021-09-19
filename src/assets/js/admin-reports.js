var tujaReports = (function () {
  return {
    init: function ($) {
      $('div.tuja-admin-report-config').each(function (index, el) {
        const schemaRaw = el.dataset.optionsSchema
        if (!schemaRaw) {
          return
        }
        var schema = JSON.parse(schemaRaw)
        if (!schema) {
          return
        }
        var editor = new JSONEditor(el, {
          form_name_root: 'JSONEditor_' + Math.random().toString(36).substr(2, 9),
          schema: schema,
          no_additional_properties: true,
          prompt_before_delete: false,
          disable_array_reorder: true,
          disable_edit_json: true,
          disable_properties: true,
          disable_collapse: true,
          disable_array_delete_all_rows: true,
          disable_array_delete_last_row: true
        })
        editor.on('change', function () {
          var conf = this.getValue()
          $(el).parent().find('a').attr('href', function (index, value) {
            return this.dataset.originalHref.replace('?', '?' + $.param(conf) + '&')
          })
        })
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaReports.init($)
})
