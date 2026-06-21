<?php
/** @var string $code @var array|null $container @var array $parentOptions @var string $csrf */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span><span>Umräumen</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Behälter umräumen</div>
  <div class="contentBoxBody">
    <?php if ($container !== null): ?>
      <p>Behälter <span class="codeTag"><?= e($container['code']) ?></span>
         (<?= e(ContainerRules::label($container['kind'])) ?> · <?= e($container['name']) ?>).</p>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('move')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <div class="formRow">
        <label for="code">Behälter-Code</label>
        <input type="text" id="code" name="code" value="<?= e($code) ?>" placeholder="z. B. T-000345" required>
        <div class="hint">Den Code des zu bewegenden Behälters eingeben (oder per Scan vorbelegen).</div>
      </div>

      <div class="formRow">
        <label for="target_id">Zielort wählen</label>
        <select id="target_id" name="target_id">
          <option value="">– kein Standort (entnehmen) –</option>
          <?php foreach ($parentOptions as $opt): ?>
            <?php $lbl = ucfirst($opt['kind']) . ': ' . (!empty($opt['code']) ? $opt['code'] . ' ' : '') . $opt['name']; ?>
            <option value="<?= e($opt['id']) ?>"><?= e($lbl) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Alternativ unten einen Zielort-Code eingeben – dieser hat Vorrang.</div>
      </div>

      <div class="formRow">
        <label for="target_code">…oder Zielort-Code</label>
        <input type="text" id="target_code" name="target_code" placeholder="z. B. K-00012">
      </div>

      <div class="formRow">
        <label for="note">Notiz (optional)</label>
        <input type="text" id="note" name="note" maxlength="160" placeholder="z. B. „Aufräumen Vereinslager"">
      </div>

      <button type="submit" class="btn btn-accent">Umräumen</button>
      <a class="btn-ghost" href="<?= e(base_url('container')) ?>">Abbrechen</a>
    </form>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Kamera-Scan (mobil)</div>
  <div class="contentBoxBody">
    <p class="hint">Erst auswählen, welches Feld der Scan füllt, dann Kamera starten.
       Benötigt HTTPS für den Kamerazugriff.</p>
    <p>
      <label><input type="radio" name="scanTarget" value="code" checked> Behälter-Code</label>
      &nbsp;&nbsp;
      <label><input type="radio" name="scanTarget" value="target_code"> Zielort-Code</label>
    </p>
    <div id="reader" style="max-width:320px"></div>
    <p>
      <button type="button" class="btn btn-sm" onclick="moveStartScan()">Kamera starten</button>
      <button type="button" class="btn-ghost btn-sm" onclick="BrickBank.stopScanner()">Stopp</button>
    </p>
  </div>
</article>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= e(base_url('assets/scan.js')) ?>"></script>
<script>
function moveStartScan() {
  BrickBank.startScanner('reader', function (text) {
    var which = document.querySelector('input[name=scanTarget]:checked').value;
    var field = document.getElementById(which);
    if (field) { field.value = text; field.focus(); }
  }, false);
}
</script>
