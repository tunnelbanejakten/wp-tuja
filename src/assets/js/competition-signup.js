var tujaCompetitionSignup = (function () {

  function onRoleSelect ($, event) {
    var $input = $(event.target)
    var $form = $input.closest('form')

    var isGroupLeader = $input.val() === $form.data('roleGroupLeaderLabel')

    $('#tuja-person__name').closest('div.tuja-question').toggle(isGroupLeader)
    $('#tuja-person__phone').closest('div.tuja-question').toggle(isGroupLeader)
    $('#tuja-person__pno').closest('div.tuja-question').toggle(isGroupLeader)

    return false
  }

  return {
    init: function ($) {
      $('input[name="tuja-person__role"]').change(onRoleSelect.bind(null, $))
      $('input[name="tuja-person__role"]:checked').change()
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaCompetitionSignup.init($)
})

