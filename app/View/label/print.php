<?php
/** @var array $labels */
use App\Core\Config;
use App\Service\ContainerRules;

$w = (int) Config::get('labels.print.width_mm', 62);
$h = (int) Config::get('labels.print.height_mm', 29);
?>
<div class="noPrint">
  <div class="breadcrumbs">
    <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
    <a href="<?= e(base_url('label')) ?>">Etiketten</a><span class="sep">›</span><span>Drucken</span>
  </div>
  <p>
    <button type="button" class="btn btn-accent" onclick="window.print()">Drucken</button>
    <a class="btn-ghost" href="<?= e(base_url('label')) ?>">Zurück</a>
    <span class="hint"><?= count($labels) ?> Etikett(en) · Etikettenmaß <?= $w ?>×<?= $h ?> mm (aus Konfiguration)</span>
  </p>
</div>

<?php if (empty($labels)): ?>
  <p class="noPrint">Keine Etiketten zum Drucken.</p>
<?php else: ?>
  <div class="labelSheet" style="--label-w: <?= $w ?>mm; --label-h: <?= $h ?>mm;">
    <?php foreach ($labels as $l): ?>
      <div class="labelCard">
        <img class="qr" width="110" height="110" alt="<?= e($l['code']) ?>"
             src="<?= e(base_url('label/qr?code=' . urlencode($l['code']))) ?>"
             data-code="<?= e($l['code']) ?>" onerror="qrFallback(this)">
        <div class="labelMeta">
          <div class="lblCode"><?= e($l['code']) ?></div>
          <?php if (!empty($l['custom_code'])): ?><div><?= e($l['custom_code']) ?></div><?php endif; ?>
          <div><?= e(ContainerRules::label($l['kind'])) ?></div>
          <div><?= e($l['name']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script src="<?= e(asset_url('assets/qr-fallback.js')) ?>"></script>
