var tujaListItemsControls = (function () {
  return {
    init: function ($, listName) {

      function onAddClick (event) {
        var form = $(event.target).closest('form')
        var container = form.find('div.tuja-' + listName + '-existing')
        var newForm = form.find('div.tuja-' + listName + '-template > div.tuja-' + listName + '-form').clone()
        var id = new Date().getTime() // Current timestamp is good enough for the purposes of this function: ensuring each click on Add gets an unique id.
        newForm.find('button.tuja-delete-' + listName).click(onDeleteClick)
        newForm.find('input, textarea').each(function () {
          var input = $(this)
          input.attr('name', input.attr('name') + id)
        })
        newForm.appendTo(container)
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

var tujaTabs = (function () {

  function onTabHeaderClick ($, event) {

    var clickedTabId = event.target.dataset.tabId
    $(event.target).closest('.nav-tab-wrapper').find('.nav-tab').each(function () {
      var isSelected = this.dataset.tabId === clickedTabId

      $(document.getElementById(this.dataset.tabId)).toggle(isSelected)
      $(this).toggleClass('nav-tab-active', isSelected)
    })

    return false
  }

  return {
    init: function ($) {
      var wrapper = $('div.nav-tab-wrapper')
      wrapper.find('a.nav-tab').each(function () {
        var tabHeader = $(this)
        tabHeader.click(onTabHeaderClick.bind(null, $))
        var isTabSelected = tabHeader.hasClass('nav-tab-active')
        $(document.getElementById(this.dataset.tabId)).toggle(isTabSelected)
      })
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaListItemsControls.init($, 'messagetemplate')
  tujaListItemsControls.init($, 'groupcategory')
  tujaTabs.init($)
})

