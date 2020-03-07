;(function () {
  Dropzone.autoDiscover = false

  jQuery(document).ready(function ($) {

    var groupId = $('input[name="group"]').attr('value')

    $('div.tuja-image-select').each(function (i, el) {
      var fieldName = $(this).closest('.tuja-image').data('fieldName')
      var preexistingFiles = $(this).closest('.tuja-image').data('preexisting')
      var $question = $(this).closest('.tuja-question')
      var $lock = $(this).closest('form').find('input[name="tuja_formshortcode__optimistic_lock"]')
      var maxFilesCount = parseInt($(this).closest('.tuja-image').data('maxFilesCount')) || 1
      var $fileCounter = $(this).closest('form').find('.tuja-fieldimages-counter')

      var getFileCount = function () {
        return $question.find('.dropzone input[type="hidden"]').length
      }

      var isFileCountLimitReacted = function () {
        return getFileCount() >= maxFilesCount
      }

      var plural = function (number, zero, one, other) {
        switch (number) {
          case 0:
            return zero
          case 1:
            return one
          default:
            return other
        }
      }

      var updateFileCounter = function () {
        if (!isFileCountLimitReacted()) {
          var count = getFileCount()
          var pattern = count === 0
            ? ('Ni kan ladda upp COUNT IMAGES här.'
              .replace('COUNT', maxFilesCount)
              .replace('IMAGES', plural(maxFilesCount, 'bilder', 'bild', 'bilder')))
            : ('Ni kan ladda upp ytterligare COUNT IMAGES.'
              .replace('COUNT', maxFilesCount - count)
              .replace('IMAGES', plural(maxFilesCount - count, 'bilder', 'bild', 'bilder')))
          $fileCounter.text(pattern)
        } else {
          if (maxFilesCount > 1) {
            $fileCounter.text('Ni har laddad upp så många bilder som ni får. Vill ni byta ut en bild måste ni först ta bort en.')
          } else {
            $fileCounter.text('Vill ni byta ut bilden måste ni först ta bort den ni laddat upp.')
          }
        }
      }

      new Dropzone(el, {
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

          self.on('sending', function (file, xhr, formData) {
            formData.append('action', 'tuja_upload_images')
            formData.append('group', groupId)
            formData.append('question', $question.data('id'))
            formData.append('lock', $lock.attr('value'))
          })

          self.on('addedfile', function (file) {
            if (isFileCountLimitReacted()) {
              self.removeFile(file)
            }
          })
          self.on('removedfile', function (file) {
            updateFileCounter()
          })

          self.on('success', function (file, res) {
            var serverFilename = res.image
            // Create the remove button
            var $removeButton = $('<button class="button remove-image">Ta bort</button>')

            // Listen to the click event
            $removeButton.click(function (e) {
              // Make sure the button click doesn't submit the form:
              e.preventDefault()
              e.stopPropagation()

              // Remove the file preview.
              self.removeFile(file)
            })
            // Add the button to the file preview element.
            $(file.previewElement)
              .append($('<div class="tuja-item-buttons" />')
                .append($removeButton))
              .append($('<input/>')
                .attr('type', 'hidden')
                .attr('name', fieldName)
                .attr('value', serverFilename))
            updateFileCounter()
          })

          for (var preexistingFile of preexistingFiles) {
            var filename = preexistingFile.filename
            var resizedImageUrl = preexistingFile.resizedImageUrl
            var imageUrl = WPAjax.base_image_url + 'group-' + groupId + '/' + (resizedImageUrl || filename)
            var mockFile = { name: filename, size: 12345 }
            var mockResponse = {
              image: filename
            }

            self.emit('addedfile', mockFile)
            self.emit('thumbnail', mockFile, imageUrl)
            self.emit('complete', mockFile)
            self.emit('success', mockFile, mockResponse)
          }
          updateFileCounter()
        }
      })
    })
  })
})()