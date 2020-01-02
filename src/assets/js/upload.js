;(function () {
  Dropzone.autoDiscover = false
  var maxFilesCount = 2

  jQuery(document).ready(function ($) {

    var dropzones = []
    var groupId = $('input[name="group"]').val()

    $('.dropzone').each(function (i, el) {
      var answerName = $(this).closest('.tuja-image').attr('id')
      var $question = $(this).closest('.tuja-question')
      var $lock = $(this).closest('form').find('input[name="tuja_formshortcode__optimistic_lock"]')

      var dz = new Dropzone(el, {
        url: WPAjax.ajaxUrl,
        resizeWidth: 1000,
        acceptedFiles: 'image/*',
        parallelUploads: maxFilesCount,
        thumbnailMethod: 'contain',
        maxFiles: maxFilesCount,
        uploadMultiple: false,
        dictDefaultMessage: 'Klicka här för att ladda upp bilder',
        init: function () {
          var self = this
          var uploadCount = 0

          self.on('sending', function (file, xhr, formData) {
            formData.append('action', 'tuja_upload_images')
            formData.append('group', groupId)
            formData.append('question', $question.data('id'))
            formData.append('lock', $lock.val())
          })

          $question.find('.clear-image-field').click(function () {
            $question.find('input').first().val('')
            $question.find('input').slice(1).remove()
            $question.find('.dz-preview').remove()
            self.removeAllFiles()
            self.options.maxFiles = maxFilesCount
            self.emit('reset')
            uploadCount = 0
            self.enable()
            $question.find('.clear-image-field').toggle(uploadCount > 0)
          })

          self.on('success', function (f, res) {
              if (res.error === false && res.image) {
                const selector = 'input[name="' + answerName + '[images][]"]'
                var $oldImage = $(selector).first()
                uploadCount++

                if ($oldImage.val() === '') {
                  $oldImage.val(res.image)
                  $oldImage.attr('data-filename', f.name)
                } else {
                  var $newImage = $(selector).first().clone(false)
                  $newImage.val(res.image)
                  $newImage.attr('data-filename', f.name)
                  $oldImage.after($newImage)
                }
              }
            }
          )
          self.on('success', function (f, res) {
              $question.find('.clear-image-field').toggle(uploadCount > 0)
              if (uploadCount >= maxFilesCount) {
                self.disable()
              }
            }
          )

          if ($('input[name="' + answerName + '[images][]"]').val() !== '') {

            $('input[name="' + answerName + '[images][]"]').each(function (i, o) {
              let inputEl = $(o)
              var imageUrl = WPAjax.base_image_url + 'group-' + groupId + '/' + (inputEl.data('thumbnail-url') || inputEl.val())
              var mockFile = { name: 'Bild', size: 12345 }

              self.emit('addedfile', mockFile)
              self.emit('thumbnail', mockFile, imageUrl)
              self.emit('complete', mockFile)
              self.options.maxFiles = self.options.maxFiles - 1
              uploadCount++
            })
            $question.find('.clear-image-field').toggle(uploadCount > 0)
          }
        },
      })

      dropzones.push(dz)
    })
  })
})()