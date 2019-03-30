;(function() {
	Dropzone.autoDiscover = false;
	
	jQuery(document).ready(function($) {

		var dropzones = [];

		$('.dropzone').each(function(i, el) {
			var dz = new Dropzone(el, {
				url: WPAjax.ajaxUrl,
				resizeWidth: 600,
				acceptedFiles: 'image/*',
				parallelUploads: 2,
				maxFiles: 2,
				uploadMultiple: true,
				dictDefaultMessage: 'Klicka här för att ladda upp bild',
				init: function() {
					this.on('sending', function(file, xhr, formData) {
						formData.append('action', 'tuja_upload_images');
						formData.append('group', $('input[name="group"]').val());
					});
				}
			});
			dropzones.push(dz);
		});
	
		$('button.remove').click(function() {
			$(this).closest('.tuja-image').remove();
		});
	
	});
})();