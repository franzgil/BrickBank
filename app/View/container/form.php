<?php
/** @var array $owners @var array $parentOptions @var string $csrf @var array $errors @var array $old */
use App\Service\ContainerRules;

$old = $old ?? [];
$val = function ($key, $default = '') use ($old) { return isset($old[$key]) ? $old[$key] : $default; };
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span><span>Anlegen</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Behälter anlegen</div>
  <div class="contentBoxBody">
    <?php if (!empty($errors)): ?>
      <div class="flash flash-error">
        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="hint">Der Code wird automatisch und <strong>ortsneutral</strong> vergeben
       (C-/K-/T-). Der Standort lässt sich jederzeit ändern, ohne das Etikett neu zu drucken.</p>

    <form method="post" action="<?= e(base_url('container')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

      <div class="formRow">
        <label for="kind">Typ</label>
        <select id="kind" name="kind">
          <?php foreach (['container', 'karton', 'tuete', 'sortimentsbox', 'einsatzkasten'] as $k): ?>
            <option value="<?= e($k) ?>" <?= $val('kind') === $k ? 'selected' : '' ?>><?= e(ContainerRules::label($k)) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Tüte gehört in Karton, Karton in Container, Container an einen Lagerort.</div>
      </div>

      <div class="formRow">
        <label for="name">Name / Bezeichnung</label>
        <input type="text" id="name" name="name" value="<?= e($val('name')) ?>" maxlength="120" required>
      </div>

      <div class="formRow">
        <label for="parent_id">Standort (optional)</label>
        <select id="parent_id" name="parent_id">
          <option value="">– kein Standort –</option>
          <?php foreach ($parentOptions as $opt): ?>
            <?php $lbl = ucfirst($opt['kind']) . ': ' . (!empty($opt['code']) ? $opt['code'] . ' ' : '') . $opt['name']; ?>
            <option value="<?= e($opt['id']) ?>" <?= (string) $val('parentId') === (string) $opt['id'] ? 'selected' : '' ?>><?= e($lbl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="formRow">
        <label for="owner_id">Eigentümer (optional)</label>
        <select id="owner_id" name="owner_id">
          <option value="">– aus Inhalt ableiten –</option>
          <?php foreach ($owners as $o): ?>
            <option value="<?= e($o['id']) ?>" <?= (string) $val('ownerId') === (string) $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?> (<?= e($o['type']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="formRow">
        <label for="note">Notiz (optional)</label>
        <input type="text" id="note" name="note" value="<?= e($val('note')) ?>" maxlength="255">
      </div>

      <button type="submit" class="btn btn-accent">Anlegen</button>
      <a class="btn-ghost" href="<?= e(base_url('container')) ?>">Abbrechen</a>
    </form>
  </div>
</article>
