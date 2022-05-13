var tujaCollapsibleControls = (function () {
  return {
    init: function ($, $root) {

      function onClick (event) {
        $(event.target).closest('div.tuja-messagetemplate-collapsible').toggleClass('tuja-messagetemplate-collapsed')
        return false
      }

      $root.find('div.tuja-messagetemplate-collapsecontrol a').click(onClick)
    }
  }
})()

var tujaListItemsControls = (function () {
  return {
    init: function ($, listName) {

      function onAddClick (event) {
        var button = $(event.target)
        var form = button.closest('form')
        var container = form.find('div.tuja-' + listName + '-existing')
        var newForm = form.find('div.tuja-' + listName + '-template > div.tuja-' + listName + '-form').clone()
        var id = new Date().getTime() // Current timestamp is good enough for the purposes of this function: ensuring each click on Add gets an unique id.
        newForm.find('button.tuja-delete-' + listName).click(onDeleteClick)
        newForm.find('input, textarea, select').each(function () {
          var input = $(this)
          var field = input.attr('name').split(/__/)[1]

          var defaultValue = button.data(field)
          if (defaultValue) {
            input.val(defaultValue)
          }

          input.attr('name', input.attr('name') + id)
        })
        newForm.appendTo(container)
        tujaCollapsibleControls.init($, newForm)
        return false
      }

      function onDeleteClick (event) {
        $(event.target).closest('div.tuja-' + listName + '-form').remove()
        return false
      }

      $('button.tuja-add-' + listName).click(onAddClick)
      $('button.tuja-delete-' + listName).click(onDeleteClick)
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaListItemsControls.init($, 'messagetemplate')
  tujaCollapsibleControls.init($, $('#wpbody-content'))
})

