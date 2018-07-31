var tujaUpload = (function () {

  var DATAURL_PREFIX = 'data:image/jpeg;base64,'
  var JPEG_QUALITY = 0.7

  var resize = function (file, size, callback) {
    // Sources and articles:
    // - https://hacks.mozilla.org/2011/01/how-to-develop-a-html5-image-uploader/
    // - https://stackoverflow.com/questions/10333971/html5-pre-resize-images-before-uploading

    // Alternatives:
    // - https://www.plupload.com/
    // - https://www.realuploader.com/
    // - https://www.dropzonejs.com/

    // Only deselect image if image is successfully resized.

    // Test on:
    // - Mac Chrome
    // - Mac Safari
    // - Windows Chrome
    // - Windows Firefox
    // - Samsung S8 (default browser and Chrome?)
    // - iPhone (deafult browser and Chrome?)

    var img = document.createElement('img')

    var reader = new FileReader()
    reader.onload = function (e) {

      img.src = e.target.result
      img.onload = function (ev) {
        var width = img.width
        var height = img.height

        var canvas = document.createElement('canvas')
        var ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0)

        var MAX_WIDTH = size
        var MAX_HEIGHT = size
        var width = img.width
        var height = img.height

        if (width > height) {
          if (width > MAX_WIDTH) {
            height *= MAX_WIDTH / width
            width = MAX_WIDTH
          }
        } else {
          if (height > MAX_HEIGHT) {
            width *= MAX_HEIGHT / height
            height = MAX_HEIGHT
          }
        }
        canvas.width = width
        canvas.height = height
        var ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, width, height)

        var dataurl = canvas.toDataURL('image/jpeg', JPEG_QUALITY)

        if (dataurl && dataurl.substr(0, DATAURL_PREFIX.length) === DATAURL_PREFIX && dataurl.substr(DATAURL_PREFIX.length) !== '') {
          callback(dataurl)
        }
      }
    }
    reader.readAsDataURL(file)
  }

  return {
    onSelect: function (element) {
      var files = element.files
      if (files.length === 1) {
        var file = files[0]

        resize(file, 1500, function (dataurl) {
          var imgPreview = document.createElement('img')
          imgPreview.src = dataurl
          element.parentNode.appendChild(imgPreview)

          console.log('Data URL is ' + dataurl.length + ' b long.')

          var hiddenInput = document.createElement('input')
          hiddenInput.setAttribute('type', 'hidden')
          hiddenInput.setAttribute('value', dataurl.substr(DATAURL_PREFIX.length))
          hiddenInput.setAttribute('name', element.getAttribute('name') + '_resized')
          hiddenInput.setAttribute('id', element.getAttribute('name') + '_resized')
          element.parentNode.appendChild(hiddenInput)
        })
      }
    },
    removeRedundantFileFields: function () {
      var fileFields = document.querySelectorAll('input[type=file]')
      for (var i = 0; i < fileFields.length; i++) {
        var fileField = fileFields[i]
        var resizedDataUrlField = document.getElementById(fileField.getAttribute('name') + '_resized')
        if (resizedDataUrlField) {
          console.log('Removing redundant file field', fileField.getAttribute('name'))
          fileField.parentNode.removeChild(fileField)
        }
      }
    }
  }
})()
