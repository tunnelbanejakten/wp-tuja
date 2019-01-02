var tujaMessageTemplates = (function () {


  function generateId () {
    // Current timestamp is good enough for the purposes of this function: ensuring each click on Add Person gets an unique id.
    return new Date().getTime()
  }

  function addMessageTemplate ($, event) {
    var form = $(event.target).closest('form')
    var container = form.find('div.tuja-messagetemplates-existing')
    var newPersonForm = form.find('div.tuja-messagetemplate-template > div.tuja-messagetemplate-form').clone()
    var id = generateId()
    newPersonForm.find('button.tuja-delete-messagetemplate').click(deleteMessageTemplate.bind(null, $))
    newPersonForm.find('input, textarea').each(function () {
      var input = $(this)
      input
      // .attr('id', input.attr('id') + id)
        .attr('name', input.attr('name') + id)
    })
    newPersonForm.appendTo(container)
    return false
  }

  function deleteMessageTemplate ($, event) {
    $(event.target).closest('div.tuja-messagetemplate-form').remove()
    return false
  }

  return {
    init: function ($) {
      $('button.tuja-add-messagetemplate').click(addMessageTemplate.bind(null, $))
      $('button.tuja-delete-messagetemplate').click(deleteMessageTemplate.bind(null, $))
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaMessageTemplates.init($)
})

