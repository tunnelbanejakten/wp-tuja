var tujaEditGroup = (function () {


  function generateId () {
    // Current timestamp is good enough for the purposes of this function: ensuring each click on Add Person gets an unique id.
    return new Date().getTime()
  }

  function addPerson ($, event) {
    var form = $(event.target).closest('form')
    var container = form.find('div.tuja-people-existing')
    var newPersonForm = form.find('div.tuja-person-template > div.tuja-signup-person').clone()
    var id = generateId()
    newPersonForm.find('button.tuja-delete-person').click(deletePerson.bind(null, $))
    newPersonForm.find('input').each(function () {
      var input = $(this)
      input
        .attr('id', input.attr('id') + id)
        .attr('name', input.attr('name') + id)
    })
    newPersonForm.appendTo(container)
    return false
  }

  function deletePerson ($, event) {
    $(event.target).closest('div.tuja-signup-person').remove()
    return false
  }

  return {
    init: function ($) {
      $('button.tuja-add-person').click(addPerson.bind(null, $))
      $('button.tuja-delete-person').click(deletePerson.bind(null, $))
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaEditGroup.init($)
})

