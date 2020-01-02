;(function () {
  Dropzone.autoDiscover = false
  var maxFilesCount = 2

  jQuery(document).ready(function ($) {

    var groupId = $('input[name="group"]').attr('value')

    $('div.tuja-image-select').each(function (i, el) {
      var fieldName = $(this).closest('.tuja-image').data('fieldName')
      var preexistingFiles = $(this).closest('.tuja-image').data('preexisting')
      var $question = $(this).closest('.tuja-question')
      var $lock = $(this).closest('form').find('input[name="tuja_formshortcode__optimistic_lock"]')

      var isFileCountLimitReacted = function () {
        var inputFieldsCount = $question.find('.dropzone input[type="hidden"]').length
        return inputFieldsCount >= maxFilesCount
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
        }
      })
    })
  })
})()