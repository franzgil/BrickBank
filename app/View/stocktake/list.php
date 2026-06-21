<?php
/** @var array $runs @var \App\Integration\WcfUser|null $currentUser */
?>
<div class="breadcrumbs"><a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span><span>Inventur</span></div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Inventurläufe</div>
  <div class="contentBoxBody">
    <?php if ($currentUser !== null): ?>
      <p><a class="btn btn-accent btn-sm" href="<?= e(base_url('stocktake/new')) ?>">+ Neue Inventur</a></p>
    <?php endif; ?>

    <?php if (empty($runs)): ?>
      <p>Noch keine Inventur durchgeführt.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Titel</th><th>Gestartet</th><th>Status</th><th>Scans</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($runs as $r): ?>
            <tr>
              <td><?= e($r['title']) ?></td>
              <td><?= e($r['started_at']) ?></td>
              <td>
                <?php if (!empty($r['finished_at'])): ?>
                  <span class="pill pill-ok">abgeschlossen</span>
                <?php else: ?>
                  <span class="pill pill-warn">offen</span>
                <?php endif; ?>
              </td>
              <td><?= e($r['scan_count']) ?></td>
              <td class="actions"><a class="btn-ghost btn-sm" href="<?= e(base_url('stocktake/' . $r['id'])) ?>">Auswertung</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
