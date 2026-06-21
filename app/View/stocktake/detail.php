<?php
/** @var array $run @var array $result @var string $csrf @var \App\Integration\WcfUser|null $currentUser */
$c = $result['counts'];
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('stocktake')) ?>">Inventur</a><span class="sep">›</span><span><?= e($run['title']) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span><?= e($run['title']) ?></div>
  <div class="contentBoxBody">
    <p>
      Soll: <strong><?= (int) $c['soll'] ?></strong> ·
      <span class="pill pill-ok">gefunden <?= (int) $c['found'] ?></span>
      <span class="pill pill-err">fehlend <?= (int) $c['missing'] ?></span>
      <span class="pill pill-warn">unerwartet <?= (int) $c['unexpected'] ?></span>
    </p>
    <p>
      <?php if (empty($run['finished_at']) && $currentUser !== null): ?>
        <a class="btn btn-sm" href="<?= e(base_url('stocktake/' . $run['id'] . '/scan')) ?>">Weiter scannen</a>
        <form method="post" action="<?= e(base_url('stocktake/' . $run['id'] . '/finish')) ?>" style="display:inline">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <button type="submit" class="btn-ghost btn-sm">Inventur abschließen</button>
        </form>
      <?php elseif (!empty($run['finished_at'])): ?>
        <span class="pill pill-ok">abgeschlossen <?= e($run['finished_at']) ?></span>
      <?php endif; ?>
    </p>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Fehlend (Soll, aber nicht gescannt)</div>
  <div class="contentBoxBody">
    <?php if (empty($result['missing'])): ?>
      <p>Nichts fehlt. 🎉</p>
    <?php else: ?>
      <table><thead><tr><th>Code</th><th>Name</th></tr></thead><tbody>
        <?php foreach ($result['missing'] as $code => $name): ?>
          <tr><td><span class="codeTag"><?= e($code) ?></span></td><td><?= e($name) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Unerwartet (gescannt, aber nicht im Soll)</div>
  <div class="contentBoxBody">
    <?php if (empty($result['unexpected'])): ?>
      <p>Keine unerwarteten Codes.</p>
    <?php else: ?>
      <table><thead><tr><th>Code</th></tr></thead><tbody>
        <?php foreach ($result['unexpected'] as $code): ?>
          <tr><td><span class="codeTag"><?= e($code) ?></span></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Gefunden</div>
  <div class="contentBoxBody">
    <?php if (empty($result['found'])): ?>
      <p>Noch nichts gefunden.</p>
    <?php else: ?>
      <table><thead><tr><th>Code</th><th>Name</th></tr></thead><tbody>
        <?php foreach ($result['found'] as $code => $name): ?>
          <tr><td><span class="codeTag"><?= e($code) ?></span></td><td><?= e($name) ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</article>
