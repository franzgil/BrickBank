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
        <div class="hint">Manuell eintippen oder per Handscanner. Kamera-Scan: siehe unten.</div>
      </div>
      <button type="submit" class="btn btn-accent">Erfassen</button>
    </form>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Zuletzt gescannt</div>
  <div class="contentBoxBody">
    <?php if (empty($recent)): ?>
      <p id="recentEmpty">Noch keine Scans.</p>
    <?php else: ?>
      <table><thead><tr><th>Code</th><th>Zeitpunkt</th></tr></thead>
        <tbody id="recentList">
          <?php foreach ($recent as $s): ?>
            <tr><td><span class="codeTag"><?= e($s['code']) ?></span></td><td><?= e($s['scanned_at']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
