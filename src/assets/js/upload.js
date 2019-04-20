;(function() {
	Dropzone.autoDiscover = false;
	var maxFilesCount = 2;
	
	jQuery(document).ready(function($) {

		var dropzones = [];
		var groupId = $('input[name="group"]').val();

		$('.dropzone').each(function(i, el) {
			var answerName = $(this).closest('.tuja-image').attr('id');
			var $question = $(this).closest('.tuja-question');
			var $lock = $(this).closest('form').find('input[name="tuja_formshortcode__optimistic_lock"]');

			var dz = new Dropzone(el, {
				url: WPAjax.ajaxUrl,
				resizeWidth: 1000,
				acceptedFiles: 'image/*',
				parallelUploads: 2,
				thumbnailMethod: 'contain',
				maxFiles: maxFilesCount,
				uploadMultiple: false,
				dictDefaultMessage: 'Klicka här för att ladda upp bilder',
				init: function() {
					var self = this;

					self.on('sending', function(file, xhr, formData) {
						formData.append('action', 'tuja_upload_images');
						formData.append('group', groupId);
						formData.append('question', $question.data('id'));
						formData.append('lock', $lock.val());
					});

					$question.on('click', '.clear-image-field', function() {
						$question.find('input').first().val('');
						$question.find('input').slice(1).remove();
						$question.find('.dz-preview').remove();
						self.removeAllFiles();
						self.options.maxFiles = maxFilesCount;
						self.emit('reset');
					});
				},
				success: function(f, res)  {
					if(res.error === false && res.image) {
						var $oldImage = $('input[name="' + answerName + '[images][]"]').first();

						if($oldImage.val() === '') {
							$oldImage.val(res.image);
						} else {
							var $newImage = $('input[name="' + answerName + '[images][]"]').first().clone(false);
							$newImage.val(res.image);
							$oldImage.after($newImage);
						}
					}
				}
			});

			if($('input[name="' + answerName + '[images][]"]').val() !== '') {
				var mockFile = { name: "Bild", size: 12345 };

				$('input[name="' + answerName + '[images][]"]').each(function(i, o) {
					var imageUrl = WPAjax.base_image_url + 'group-' + groupId + '/' + $(o).val();

					dz.emit('addedfile', mockFile);
					dz.emit('thumbnail', mockFile, imageUrl);
					dz.emit('complete', mockFile);
					dz.options.maxFiles = dz.options.maxFiles - 1;
				});
			}

			dropzones.push(dz);
		});
	});
})();