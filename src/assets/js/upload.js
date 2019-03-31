;(function() {
	Dropzone.autoDiscover = false;
	
	jQuery(document).ready(function($) {

		var dropzones = [];
		var groupId = $('input[name="group"]').val();

		$('.dropzone').each(function(i, el) {
			var answerName = $(this).closest('.tuja-image').attr('id');
			var questionId = $(this).closest('.tuja-question').data('id');

			var dz = new Dropzone(el, {
				url: WPAjax.ajaxUrl,
				resizeWidth: 600,
				acceptedFiles: 'image/*',
				parallelUploads: 2,
				maxFiles: 2,
				uploadMultiple: true,
				dictDefaultMessage: 'Klicka här för att ladda upp bilder',
				init: function() {
					this.on('sending', function(file, xhr, formData) {
						formData.append('action', 'tuja_upload_images');
						formData.append('group', groupId);
						formData.append('question', questionId);
					});
				}
			});

			if($('input[name="' + answerName + '[images][]"]').val() !== '') {
				var mockFile = { name: "Filename", size: 12345 };

				$('input[name="' + answerName + '[images][]"]').each(function(i, o) {
					var imageUrl = WPAjax.base_image_url + 'group-' + groupId + '/' + $(o).val();

					dz.emit('addedfile', mockFile);
					dz.emit('thumbnail', mockFile, imageUrl);
					dz.emit('complete', mockFile);
				});
			}

			dropzones.push(dz);
		});
	
		$('button.remove').click(function() {
			$(this).closest('.tuja-image').remove();
		});

	});
})();