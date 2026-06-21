<?php
/** @var array $run @var array $recent @var int $count @var string $csrf */
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('stocktake')) ?>">Inventur</a><span class="sep">›</span>
  <a href="<?= e(base_url('stocktake/' . $run['id'])) ?>"><?= e($run['title']) ?></a><span class="sep">›</span><span>Scannen</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Scannen · <?= e($run['title']) ?></div>
  <div class="contentBoxBody">
    <p>Bisher erfasst: <strong id="scanCount"><?= (int) $count ?></strong> Scan(s).
       <a class="btn-ghost btn-sm" href="<?= e(base_url('stocktake/' . $run['id'])) ?>">Zur Auswertung</a></p>

    <form method="post" action="<?= e(base_url('stocktake/' . $run['id'] . '/scan')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <div class="formRow">
        <label for="code">Code erfassen</label>
        <input type="text" id="code" name="code" placeholder="z. B. T-000345" autofocus required>
        <div class="hint">Manuell eintippen, per Handscanner – oder unten mit der Kamera scannen.</div>
      </div>
      <button type="submit" class="btn btn-accent">Erfassen</button>
    </form>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Kamera-Scan (mobil)</div>
  <div class="contentBoxBody">
    <p class="hint">Fortlaufender Scan: jeder erkannte Code wird sofort erfasst.
       Benötigt HTTPS für den Kamerazugriff.</p>
    <div id="reader" style="max-width:320px"></div>
    <p>
      <button type="button" class="btn btn-sm" onclick="stStart()">Kamera starten</button>
      <button type="button" class="btn-ghost btn-sm" onclick="BrickBank.stopScanner()">Stopp</button>
      <span id="scanFeedback" class="hint"></span>
    </p>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Zuletzt gescannt</div>
  <div class="contentBoxBody">
    <table>
      <thead><tr><th>Code</th><th>Zeitpunkt</th></tr></thead>
      <tbody id="recentList">
        <?php foreach ($recent as $s): ?>
          <tr><td><span class="codeTag"><?= e($s['code']) ?></span></td><td><?= e($s['scanned_at']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</article>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= e(base_url('assets/scan.js')) ?>"></script>
<script>
var ST_URL  = <?= json_encode(base_url('stocktake/' . $run['id'] . '/scan')) ?>;
var ST_CSRF = <?= json_encode($csrf) ?>;
var lastCode = null, lastTime = 0;

function stStart() {
  BrickBank.startScanner('reader', stRecord, true);
}

function stRecord(code) {
  var now = Date.now();
  if (code === lastCode && (now - lastTime) < 2500) { return; } // entprellen
  lastCode = code; lastTime = now;

  fetch(ST_URL, {
    method: 'POST',
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'csrf_token=' + encodeURIComponent(ST_CSRF) + '&code=' + encodeURIComponent(code)
  }).then(function (r) { return r.json(); }).then(function (d) {
    if (d && d.ok) {
      document.getElementById('scanCount').textContent = d.count;
      prependRecent(d.code, d.known);
      feedback(d.code + (d.known ? ' ✓' : ' – unbekannt'), d.known);
    }
  }).catch(function () { feedback('Fehler beim Senden', false); });
}

function prependRecent(code, known) {
  var tbody = document.getElementById('recentList');
  var tr = document.createElement('tr');
  tr.innerHTML = '<td><span class="codeTag"></span></td><td>jetzt</td>';
  tr.querySelector('.codeTag').textContent = code;
  tbody.insertBefore(tr, tbody.firstChild);
}

function feedback(msg, ok) {
  var el = document.getElementById('scanFeedback');
  el.textContent = msg;
  el.style.color = ok ? '#2e9b7a' : '#b8860b';
}
</script>
