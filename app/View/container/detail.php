<?php
/** @var array $container @var array $contents @var \App\Integration\WcfUser|null $currentUser */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span>
  <span><?= e($container['code']) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>
    <span class="codeTag"><?= e($container['code']) ?></span>&nbsp; <?= e(ContainerRules::label($container['kind'])) ?> · <?= e($container['name']) ?>
  </div>
  <div class="contentBoxBody">
    <table>
      <tr><th style="width:200px">Aktueller Standort</th>
        <td>
          <?php if (!empty($container['parent_code'])): ?><span class="codeTag"><?= e($container['parent_code']) ?></span> <?php endif; ?>
          <?= e($container['parent_name'] ?? '– kein Standort –') ?>
        </td></tr>
      <tr><th>Notiz</th><td><?= e($container['note'] ?? '–') ?></td></tr>
      <tr><th>RFID-EPC</th><td><?= e($container['rfid_epc'] ?? '–') ?></td></tr>
    </table>

    <p style="margin-top:14px">
      <?php if ($currentUser !== null): ?>
        <a class="btn btn-sm btn-accent" href="<?= e(base_url('container/' . $container['id'] . '/add')) ?>">+ Bestand erfassen</a>
        <a class="btn btn-sm" href="<?= e(base_url('move?code=' . urlencode($container['code']))) ?>">Umräumen</a>
      <?php endif; ?>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('container/' . $container['id'] . '/history')) ?>">Verlauf</a>
    </p>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Inhalt (Bestand)</div>
  <div class="contentBoxBody">
    <?php if (empty($contents)): ?>
      <p>Für diesen Behälter sind noch keine Bestandsposten erfasst.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Teil-Nr.</th><th>Teil</th><th>Farbe</th><th>Zustand</th><th>Menge</th><th>Besitzer</th><th>Sichtbarkeit</th></tr></thead>
        <tbody>
          <?php foreach ($contents as $row): ?>
            <tr>
              <td><?= e($row['part_num'] ?? '–') ?></td>
              <td><a href="<?= e(base_url('item/' . $row['item_id'])) ?>"><?= e($row['part_name'] ?? ('(' . $row['item_type'] . ')')) ?></a></td>
              <td><?= e($row['color_name'] ?? '–') ?></td>
              <td><?= e($row['cond']) ?></td>
              <td><?= e($row['quantity']) ?></td>
              <td><?= e($row['owner_name']) ?></td>
              <td><?= e($row['visibility']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
