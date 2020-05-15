var tujaCompetitionSignup = (function () {

  function updateForms ($) {
    var currentRole = $('input[name="tuja-person__role"]:checked').val()
    var currentGroupCategory = $('input[name="tuja-group__age"]:checked').val()
    var nextForm = $('#tuja-competitionsignup-inactive-forms div.tuja-competitionsignup-form[data-group-category-name="' + currentGroupCategory + '"][data-person-type-name="' + currentRole + '"]')
    var prevForm = $('#tuja-competitionsignup-active-form div.tuja-competitionsignup-form')

    // Copy values from form we are about to hide to all the other forms (just to keep all forms in sync so that user
    // doesn't notice that there are actually four static forms instead of just one dynamic one).
    prevForm.find('input').each(function () {
      const $input = $(this)
      $('div#tuja-competitionsignup-inactive-forms input[id="' + $input.attr('id') + '"]').val($input.val())
    })

    // Swap old/current form for newly selected form
    nextForm.appendTo('#tuja-competitionsignup-active-form')
    prevForm.appendTo('#tuja-competitionsignup-inactive-forms')
  }

  return {
    init: function ($) {
      $('input[name="tuja-group__age"]').change(updateForms.bind(null, $))
      $('input[name="tuja-person__role"]').change(updateForms.bind(null, $))

      updateForms($) // Keep view in sync with preset radio buttons.
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaCompetitionSignup.init($)
})

