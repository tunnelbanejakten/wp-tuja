;(function() {
	Dropzone.autoDiscover = false;
	
	jQuery(document).ready(function($) {

		var dropzones = [];

		$('.dropzone').each(function(i, el) {
			var dz = new Dropzone(el, {
				url: window.location.pathname,
				autoProcessQueue: false,
				resizeWidth: 600,
				resizeHeight: 600,
				resizeMethod: 'contain',
				acceptedFiles: 'image/*',
				parallelUploads: 100,
				maxFiles: 100,
				uploadMultiple: false,
				paramName: 'file-' + i,
				dictDefaultMessage: 'Klicka här för att ladda upp bild'
			});
			dropzones.push(dz);
		});

		$('button[type="submit"]').click(function(e) {
			// e.preventDefault();
			// e.stopPropagation();

			var form = new FormData(document.getElementById('main-form'));
			$.each(dropzones, function(i, dz) {
				$.each(dz.files, function(j, file) {
					form.append(dz.options.paramName + '[' + j + ']', file);
				});
			});

			// $(this).closest('form').append('<input type="hidden" name="tuja_formshortcode_action" value="update"').submit();
		});
	
		$('button.remove').click(function() {
			$(this).closest('.tuja-image').remove();
		});
	
	});
})();