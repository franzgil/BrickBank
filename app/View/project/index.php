<?php
/** @var array $projects @var int $meId @var string $csrf */
?>
<div class="breadcrumbs"><span>Projekte</span></div>

<section class="hero">
  <span class="heroBadge">Projekte</span>
  <h1>Projekte</h1>
  <p class="heroSubtitle">Ein Projekt bündelt eigene Standorte und Behälter – z. B. für ein
     großes MOC oder ein Diorama. Lege ein Projekt an und definiere anschließend seinen
     eigenen Lagerbaum mit Behältern.</p>
</section>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Projekte (<?= count($projects) ?>)</div>
  <div class="contentBoxBody">
    <?php if (empty($projects)): ?>
      <p>Noch keine Projekte. Lege unten das erste an.</p>
    <?php else: ?>
      <div class="steps">
        <?php foreach ($projects as $p): ?>
          <?php $isLead = (int) $p['wcf_user_id'] === (int) $meId; ?>
          <a class="stepCard" href="<?= e(base_url('account/' . $p['id'])) ?>">
            <div class="stepNum">🧩</div>
            <div class="stepTitle"><?= e($p['name']) ?>
              <?php if ($isLead): ?><span class="pill pill-ok" style="margin-left:6px">Leitung</span><?php endif; ?>
            </div>
            <div class="stepText">
              <?php if (!empty($p['note'])): ?><?= e($p['note']) ?><br><?php endif; ?>
              <?= (int) $p['location_count'] ?> Standort(e)/Behälter
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Projekt anlegen</div>
  <div class="contentBoxBody">
    <form method="post" action="<?= e(base_url('projects')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <div class="formRow">
        <label for="name">Projektname</label>
        <input type="text" id="name" name="name" maxlength="120" required
               placeholder="z. B. „Großes Stadt-Diorama 2026"">
      </div>
      <div class="formRow">
        <label for="note">Beschreibung (optional)</label>
        <input type="text" id="note" name="note" maxlength="255">
      </div>
      <button type="submit" class="btn btn-accent">Projekt anlegen</button>
      <div class="hint">Nach dem Anlegen wirst du direkt in den Lagerbaum des Projekts geleitet,
         wo du Standorte und Behälter definierst.</div>
    </form>
  </div>
</article>
