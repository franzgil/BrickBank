<?php
/** @var array $container @var array|null $item @var array|null $owner
 *  @var string $cond @var int $locationId @var array $history */
use App\Service\ContainerRules;

$typeLabels = [
    'zugang'    => 'Zugang',
    'entnahme'  => 'Entnahme',
    'umbuchung' => 'Umbuchung',
    'korrektur' => 'Korrektur',
];
$partLabel = $item ? (($item['part_name'] ?? $item['part_num'] ?? '') . ($item['color_name'] ? ' · ' . $item['color_name'] : '')) : '(unbekannt)';
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span>
  <a href="<?= e(base_url('container/' . $container['id'])) ?>"><?= e($container['code'] ?? $container['name']) ?></a><span class="sep">›</span>
  <span>Verlauf der Position</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>Änderungsverlauf ·
    <?php if (!empty($container['code'])): ?><span class="codeTag"><?= e($container['code']) ?></span>&nbsp;<?php endif; ?>
    <?= e(ContainerRules::label($container['kind'])) ?>
  </div>
  <div class="contentBoxBody">
    <table>
      <tr><th style="width:200px">Teil</th><td><?= e($partLabel) ?></td></tr>
      <tr><th>Zustand</th><td><?= e($cond) ?></td></tr>
      <tr><th>Besitzer</th><td><?= e($owner['name'] ?? '–') ?></td></tr>
    </table>

    <?php if (empty($history)): ?>
      <p style="margin-top:14px">Für diese Position sind noch keine Änderungen protokolliert.</p>
    <?php else: ?>
      <table style="margin-top:14px">
        <thead><tr><th>Zeitpunkt</th><th>Art</th><th>Menge</th><th>Von</th><th>Nach</th><th>Benutzer (WSC)</th><th>Notiz</th></tr></thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <?php
              $intoHere = ((int) $h['to_location_id'] === (int) $locationId);
              $sign = $intoHere ? '+' : '−';
              $fromTxt = !empty($h['from_code']) ? $h['from_code'] : ($h['from_name'] ?? ($h['from_location_id'] ? '#' . $h['from_location_id'] : '–'));
              $toTxt   = !empty($h['to_code'])   ? $h['to_code']   : ($h['to_name']   ?? ($h['to_location_id']   ? '#' . $h['to_location_id']   : '–'));
            ?>
            <tr>
              <td><?= e($h['created_at']) ?></td>
              <td><?= e($typeLabels[$h['type']] ?? $h['type']) ?></td>
              <td><strong><?= e($sign . $h['quantity']) ?></strong></td>
              <td><?= e($fromTxt) ?></td>
              <td><?= e($toTxt) ?></td>
              <td><?= e($h['wcf_user_id'] ?? '–') ?></td>
              <td><?= e($h['note'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p style="margin-top:14px">
      <a class="btn-ghost btn-sm" href="<?= e(base_url('container/' . $container['id'])) ?>">← zurück zum Behälter</a>
    </p>
  </div>
</article>
