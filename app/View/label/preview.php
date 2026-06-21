<?php
/** @var array $label */
use App\Core\Config;
use App\Service\ContainerRules;

$w = (int) Config::get('labels.print.width_mm', 62);
$h = (int) Config::get('labels.print.height_mm', 29);
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('label')) ?>">Etiketten</a><span class="sep">›</span><span><?= e($label['code']) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Etikett-Vorschau · <span class="codeTag"><?= e($label['code']) ?></span></div>
  <div class="contentBoxBody">
    <div class="labelSheet" style="--label-w: <?= $w ?>mm; --label-h: <?= $h ?>mm;">
      <div class="labelCard">
        <img class="qr" width="120" height="120" alt="<?= e($label['code']) ?>"
             src="<?= e(base_url('label/qr?code=' . urlencode($label['code']))) ?>"
             data-code="<?= e($label['code']) ?>" onerror="qrFallback(this)">
        <div class="labelMeta">
          <div class="lblCode"><?= e($label['code']) ?></div>
          <div><?= e(ContainerRules::label($label['kind'])) ?></div>
          <div><?= e($label['name']) ?></div>
        </div>
      </div>
    </div>

    <p style="margin-top:14px" class="noPrint">
      <a class="btn btn-sm" href="<?= e(base_url('label/print')) ?>">Zur Druckansicht</a>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('container/' . $label['id'])) ?>">Zum Behälter</a>
    </p>
  </div>
</article>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script src="<?= e(base_url('assets/qr-fallback.js')) ?>"></script>
