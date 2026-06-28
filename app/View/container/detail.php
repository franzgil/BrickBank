<?php
/** @var array $container @var array $contents @var string $csrf
 *  @var \App\Integration\WcfUser|null $currentUser */
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
      <tr><th>Automatische ID</th><td><span class="codeTag"><?= e($container['code']) ?></span></td></tr>
      <tr><th>Eigene ID</th>
        <td>
          <?php if ($currentUser !== null): ?>
            <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/custom-code')) ?>" style="display:flex;gap:6px;align-items:center">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="text" name="custom_code" value="<?= e($container['custom_code'] ?? '') ?>" maxlength="32" placeholder="optional, parallel zur automatischen ID" style="max-width:260px">
              <button type="submit" class="btn-ghost btn-sm">Speichern</button>
            </form>
          <?php else: ?>
            <?= !empty($container['custom_code']) ? '<span class="codeTag">' . e($container['custom_code']) . '</span>' : '–' ?>
          <?php endif; ?>
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
  <div class="contentBoxHeader"><span class="icon"></span>Bild</div>
  <div class="contentBoxBody">
    <?php if (!empty($container['image_path'])): ?>
      <p><img src="<?= e(asset_url($container['image_path'])) ?>" alt="Bild"
              style="max-width:100%;max-height:340px;border-radius:6px;border:1px solid var(--wcfContentBorder)"></p>
    <?php else: ?>
      <p class="hint">Noch kein Bild.</p>
    <?php endif; ?>
    <?php if ($currentUser !== null): ?>
      <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/image')) ?>"
            enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" class="btn btn-sm">Hochladen</button>
      </form>
      <?php if (!empty($container['image_path'])): ?>
        <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/image/remove')) ?>" style="margin-top:8px">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <button type="submit" class="btn-ghost btn-sm">Bild entfernen</button>
        </form>
      <?php endif; ?>
      <div class="hint">JPG, PNG, WebP oder GIF, max. 5 MB.</div>
    <?php endif; ?>
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
