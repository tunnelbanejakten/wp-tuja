var tujaEditGroup = (function () {


  function generateId() {
    // Current timestamp is good enough for the purposes of this function: ensuring each click on Add Person gets an unique id.
    return new Date().getTime()
  }

  function fixAttrValue(id, attrValue) {
    var regexp = /(?<prefix>tuja-person__[a-z]+__)(?<suffix>.+)?/
    if (!attrValue) {
      return null
    }
    var matches = attrValue.match(regexp)
    return [matches.groups.prefix, id, matches.groups.suffix].join('')
  }

  // Manually click each checked radio button to make sure it's actually visibly checked.
  // For some reason, cloned radio buttons aren't always properly checked despite having checked="checked".
  function applyCheckedRadioButtonHack($, root) {
    root.find('input[checked]').each(function () {
      $(this).click()
    })
  }

  function addPerson($, event) {
    var form = $(event.target).closest('div.tuja-people')
    var container = form.find('div.tuja-people-existing')
    var newPersonForm = form.find('div.tuja-person-template > div.tuja-signup-person').clone()
    var id = generateId()
    newPersonForm.find('button.tuja-delete-person').click(deletePerson.bind(null, $))
    newPersonForm.find('input').each(function () {
      var input = $(this)
      input
        .attr('id', fixAttrValue(id, input.attr('id')))
        .attr('name', fixAttrValue(id, input.attr('name')))
    })
    newPersonForm.find('label').each(function () {
      var input = $(this)
      input
        .attr('for', fixAttrValue(id, input.attr('for')))
    })
    applyCheckedRadioButtonHack($, newPersonForm)
    newPersonForm.appendTo(container)
    return false
  }

  function deletePerson($, event) {
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

