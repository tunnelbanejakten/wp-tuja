jQuery.noConflict()

jQuery(document).ready(function ($) {
  tujaFormGenerator.init($);

  $('.tuja-admin-form').on('click', '.form-control.repeat button.add', function() {
    const list = $(this).siblings('ol').first();
    const template = list.find('template').get(0);

    if(!list || !template) return;

    const clone = template.content.cloneNode(true);
    list.append(clone);
  });

  $('.tuja-admin-form').on('click', '.form-control.repeat button.remove', function() {
    $(this).closest('li').remove();
  });
})
