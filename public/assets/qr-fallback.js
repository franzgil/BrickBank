/* Clientseitiger QR-Fallback: greift, wenn der serverseitige PNG-Endpunkt
   (phpqrcode) nicht verfügbar ist. Nutzt die global geladene qrcode-Lib. */
function qrFallback(img) {
  try {
    if (typeof qrcode === 'undefined') {
      img.alt = 'QR: ' + img.dataset.code;
      return;
    }
    var t = qrcode(0, 'M');
    t.addData(img.dataset.code);
    t.make();
    img.src = t.createDataURL(4, 0);
    img.onerror = null;
  } catch (e) {
    img.alt = 'QR: ' + img.dataset.code;
  }
}
