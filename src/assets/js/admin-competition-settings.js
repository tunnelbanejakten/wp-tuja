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

var tujaStateGraph = (function () {
  return {
    init: function ($) {
      mermaid.initialize({
        startOnLoad: false,
        theme: 'neutral'
      })

      $('.tuja-stategraph').each(function () {
        var container = this
        var randomId = 'tuja-stategraph-' + Math.random().toString(36).substr(2, 9)
        mermaid.render(randomId, this.dataset.definition, function (svgGraph) {
          container.innerHTML = svgGraph
          var width = container.firstChild.width.baseVal.value
          container.firstChild.style.width = width * parseFloat(container.dataset.widthFactor)
        })
      })
    }
  }
})()

var tujaListItemsControls = (function () {
  return {
    init: function ($, listName) {

      function onAddClick (event) {
        var button = $(event.target)
        var form = button.closest('form')
        var $container = form.find('div.tuja-' + listName + '-existing')
        var $newForm = form.find('div#' + event.target.id + '_template > div.tuja-' + listName + '-form').clone()
        var id = new Date().getTime() // Current timestamp is good enough for the purposes of this function: ensuring each click on Add gets an unique id.
        $newForm.find('button.tuja-delete-' + listName).click(onDeleteClick)
        $newForm.find('input, textarea, select').each(function () {
          var input = $(this)
          const fieldName = input.attr('name')
          var field = fieldName ? fieldName.split(/__/)[1] : null
          if (!field) {
            return
          }

          var defaultValue = button.data(field)
          if (defaultValue) {
            input.val(defaultValue)
          }

          input.attr('name', fieldName + id)
          input.attr('id', fieldName + id)
        })
        $newForm.find('div.tuja-admin-' + listName + '-form').each(function (index, element) {
          this.dataset.fieldId = this.dataset.fieldId + id
          this.dataset.rootName = 'tuja-admin-' + listName + '-form-' + id
        })
        $newForm.appendTo($container)
        tujaCollapsibleControls.init($, $newForm)
        tujaFormGenerator.init($, $newForm, 'tuja-admin-' + listName + '-form')
        return false
      }

      function onDeleteClick (event) {
        $(event.target).closest('div.tuja-' + listName + '-form').remove()
        return false
      }

      // TODO: tujaForms needs to be made more generic
      tujaFormGenerator.init($, $('.tuja-' + listName + '-existing'))

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
  tujaCollapsibleControls.init($, $('#wpbody-content'))
  tujaTabs.init($)
  tujaStateGraph.init($)
})

