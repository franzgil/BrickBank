<?php
/** @var array $owner @var bool $isMe @var array $holdings */
$label = $isMe ? 'Mein Bestand' : $owner['name'];
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('dashboard')) ?>">Dashboard</a><span class="sep">›</span><span><?= e($label) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span><?= e($label) ?>
    <span class="pill <?= $owner['type'] === 'verein' ? 'pill-ok' : 'pill-warn' ?>" style="margin-left:6px"><?= e($owner['type']) ?></span>
  </div>
  <div class="contentBoxBody">
    <?php if (empty($holdings)): ?>
      <p>Kein sichtbarer Bestand in diesem Konto.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Teil-Nr.</th><th>Teil</th><th>Farbe</th><th>Ort</th><th>Zustand</th><th>Sichtbar</th><th>Menge</th></tr></thead>
        <tbody>
          <?php foreach ($holdings as $h): ?>
            <tr>
              <td><?= e($h['part_num'] ?? '–') ?></td>
              <td><a href="<?= e(base_url('item/' . $h['item_id'])) ?>"><?= e($h['part_name'] ?? ('(' . $h['item_type'] . ')')) ?></a></td>
              <td><?= e($h['color_name'] ?? '–') ?></td>
              <td><?php if (!empty($h['location_code'])): ?><span class="codeTag"><?= e($h['location_code']) ?></span> <?php endif; ?><?= e($h['location_name']) ?></td>
              <td><?= e($h['cond']) ?></td>
              <td><?= e($h['visibility']) ?></td>
              <td><?= e($h['quantity']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
