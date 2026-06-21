<?php
/** @var array $locations @var array $owners @var string $csrf */
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('stocktake')) ?>">Inventur</a><span class="sep">›</span><span>Neu</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Neue Inventur</div>
  <div class="contentBoxBody">
    <form method="post" action="<?= e(base_url('stocktake')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <div class="formRow">
        <label for="title">Titel</label>
        <input type="text" id="title" name="title" maxlength="120" required placeholder="z. B. Jahresinventur Vereinslager">
      </div>

      <div class="formRow">
        <label for="root_location_id">Bereich eingrenzen (optional)</label>
        <select id="root_location_id" name="root_location_id">
          <option value="">– gesamtes Lager –</option>
          <?php foreach ($locations as $l): ?>
            <option value="<?= e($l['id']) ?>"><?= e(ucfirst($l['kind'])) ?>: <?= e($l['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Schränkt die Soll-Liste auf diesen Ort und alle darunterliegenden Behälter ein.</div>
      </div>

      <div class="formRow">
        <label for="owner_id">Auf Eigentümer eingrenzen (optional)</label>
        <select id="owner_id" name="owner_id">
          <option value="">– alle –</option>
          <?php foreach ($owners as $o): ?>
            <option value="<?= e($o['id']) ?>"><?= e($o['name']) ?> (<?= e($o['type']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-accent">Starten &amp; scannen</button>
      <a class="btn-ghost" href="<?= e(base_url('stocktake')) ?>">Abbrechen</a>
    </form>
  </div>
</article>
