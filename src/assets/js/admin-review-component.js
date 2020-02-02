var tujaReviewComponent = (function () {

  function handleClick (event, $, newValue) {
    var targetField = event.target.dataset.targetField
    $('#' + targetField)
      .val(newValue)
      .closest('.tuja-admin-review-change-autoscore-container')
      .toggleClass('tuja-admin-review-autoscore-changed', newValue !== '')
    tb_remove()
    updateFinalScores($)
    return false
  }

  function onSetScore ($, event) {
    var targetField = event.target.dataset.targetField
    var score = event.target.dataset.score
    $('#' + targetField).val(score)
    updateFinalScores($)
    return false
  }

  function onYes ($, event) {
    return handleClick(event, $, event.target.dataset.value)
  }

  function onNo ($, event) {
    return handleClick(event, $, '')
  }

  function updateFinalScores ($) {
    $('.tuja-admin-review-final-score').each(function (index, el) {
      const $final = $(el)
      var $wrapper = $final.closest('tr')
      var manual = $wrapper.find('input[type="number"]').val()
      var autoCorrected = $wrapper.find('div.tuja-admin-review-change-autoscore-container.tuja-admin-review-autoscore-changed span.tuja-admin-review-corrected-autoscore span.tuja-admin-review-autoscore').text()
      var autoOriginal = $wrapper.find('div.tuja-admin-review-change-autoscore-container span.tuja-admin-review-original-autoscore span.tuja-admin-review-autoscore').text()
      $final.text(manual !== '' ? manual : (!!autoCorrected ? autoCorrected : autoOriginal))
    })
  }

  return {
    init: function ($) {
      $('button.tuja-admin-review-button-yes').click(onYes.bind(null, $))
      $('button.tuja-admin-review-button-no').click(onNo.bind(null, $))
      $('a.tuja-admin-review-set-score').click(onSetScore.bind(null, $))
      $('input[type="number"]').change(updateFinalScores.bind(null, $))
      updateFinalScores($)
    }
  }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaReviewComponent.init($)
})
