var tujaReviewComponent = (function () {

    function handleClick(event, $, newValue) {
        var targetField = event.target.dataset.targetField
        $('#' + targetField)
            .val(newValue)
            .closest('.tuja-admin-review-change-autoscore-container')
            .toggleClass('tuja-admin-review-autoscore-changed', newValue !== '')
        tb_remove()
        return false
    }

    function onYes($, event) {
        return handleClick(event, $, event.target.dataset.value);
    }

    function onNo($, event) {
        return handleClick(event, $, '');
    }

    return {
        init: function ($) {
            $('button.tuja-admin-review-button-yes').click(onYes.bind(null, $))
            $('button.tuja-admin-review-button-no').click(onNo.bind(null, $))
        }
    }
})()

jQuery.noConflict()

jQuery(document).ready(function ($) {
    tujaReviewComponent.init($)
})
