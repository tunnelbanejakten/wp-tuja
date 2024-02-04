jQuery.noConflict()

jQuery(document).ready(function ($) {
  document.querySelectorAll('.qr-code').forEach(function (node) {
    var qrValue = node.dataset.qrValue
    if (qrValue) {
      var qr = new QRious({
        value: qrValue,
        size: 100
      });
  
      node.src = qr.toDataURL()
    }
  })
})

