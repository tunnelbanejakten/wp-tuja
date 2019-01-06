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
        var canvas = document.createElement('canvas')
        var ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0)

        var width = img.width
        var height = img.height

        var new_width = Math.round(Math.sqrt((size * width) / height));
        var new_height = Math.round(new_width * (height / width));

        if (width * height <= new_width * new_height * 1.1) {
          // Original image is less than 10% larger than the scaled down version would. Don't bother resizing it, just use the original image instead.
          return
        }

        console.log('Resize from ' + width + 'x' + height + ' to ' + new_width + 'x' + new_height)

        canvas.width = new_width
        canvas.height = new_height
        var ctx = canvas.getContext('2d')
        ctx.drawImage(img, 0, 0, new_width, new_height)

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

        var parent = element.parentNode
        var lastChild = parent.childNodes.item(parent.childNodes.length - 1)
        if (lastChild.getAttribute('class') === 'tuja-images-resize-status') {
          parent.removeChild(lastChild)
        }

        resize(file, 2000000, function (dataurl) {
          var imgPreview = document.createElement('img')
          imgPreview.src = dataurl

          var hiddenInput = document.createElement('input')
          hiddenInput.setAttribute('type', 'hidden')
          hiddenInput.setAttribute('value', dataurl.substr(DATAURL_PREFIX.length))
          hiddenInput.setAttribute('name', element.getAttribute('name') + '_resized')
          hiddenInput.setAttribute('id', element.getAttribute('name') + '_resized')

          var resizeDescription = document.createElement('p')
          resizeDescription.setAttribute('class', 'tuja-help')
          resizeDescription.innerHTML = 'Vi förminskar bilden innan du skickar in den för att spara på din surfpott.'

          var wrapper = document.createElement('div')
          wrapper.setAttribute('class', 'tuja-images-resize-status')
          wrapper.appendChild(imgPreview)
          wrapper.appendChild(resizeDescription)
          wrapper.appendChild(hiddenInput)

          parent.appendChild(wrapper)
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
