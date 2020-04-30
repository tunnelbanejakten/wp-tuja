var tujaFormGenerator = (function () {
  return {
    init: function ($, $root) {
      $root = $root || $(document)
      $root.find('div.tuja-admin-formgenerator-form').each(function (index, el) {
        var schema = JSON.parse(el.dataset.schema)
        if (schema) {
          var editor = new JSONEditor(el, {
            form_name_root: el.dataset.rootName || ('JSONEditor_' + el.dataset.fieldId),
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
          editor.setValue(JSON.parse(el.dataset.values))
          editor.on('change', function () {
            var conf = this.getValue()
            $('#' + el.dataset.fieldId).val(JSON.stringify(conf))
          })
        }
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  // tujaFormGenerator.init($)
})
