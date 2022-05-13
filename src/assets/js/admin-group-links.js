jQuery.noConflict()

jQuery(document).ready(function ($) {
  $('a.thickbox').click(function (event) {
    var qrValue = event.target.dataset.qrValue
    if (qrValue) {
      var qr = new QRious({
        value: qrValue,
        size: 300
      });

      var targetId = event.target.dataset.targetId
      document.getElementById(targetId).src = qr.toDataURL()
    }
  })
})

