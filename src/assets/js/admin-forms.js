jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaFormGenerator.init($);

  $(document.body).on('click', '.notice-dismiss', function() {
    $(this).closest('.notice').remove();
  });
})
