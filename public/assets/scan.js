/* BrickBank – Kamera-Scan-Helfer auf Basis von html5-qrcode.
   Hinweis: Kamerazugriff erfordert HTTPS (außer auf localhost). */
(function () {
  window.BrickBank = window.BrickBank || {};
  var instance = null;

  function libReady() { return typeof Html5Qrcode !== 'undefined'; }

  /**
   * Startet die Kamera und ruft onDecode(text) bei jedem Treffer.
   * continuous=false stoppt nach dem ersten Treffer (Feld-Befüllung).
   */
  BrickBank.startScanner = function (readerId, onDecode, continuous) {
    if (!libReady()) { alert('Scanner-Bibliothek konnte nicht geladen werden.'); return; }
    if (instance) { return; }
    var reader = new Html5Qrcode(readerId);
    instance = reader;
    reader.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 220 },
      function (text) {
        onDecode(text);
        if (!continuous) { BrickBank.stopScanner(); }
      },
      function () { /* Lesefehler je Frame ignorieren */ }
    ).catch(function (e) {
      instance = null;
      alert('Kamera nicht verfügbar: ' + e);
    });
  };

  BrickBank.stopScanner = function () {
    if (instance) {
      var ref = instance;
      instance = null;
      ref.stop().then(function () { ref.clear(); }).catch(function () {});
    }
  };
})();
