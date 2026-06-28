<?php
/** @var array $accounts @var int $meId */
?>
<div class="breadcrumbs"><span>Dashboard</span></div>

<section class="hero">
  <span class="heroBadge">Übersicht</span>
  <h1>Deine Konten</h1>
  <p class="heroSubtitle">Alle Bestände, zu denen du Zugang hast – dein eigener Bestand,
     der Verein und alles, was Mitglieder intern bzw. für den Verein freigegeben haben.</p>
</section>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Konten (<?= count($accounts) ?>)</div>
  <div class="contentBoxBody">
    <?php if (empty($accounts)): ?>
      <p>Noch kein sichtbarer Bestand. Lege im Bereich <a href="<?= e(base_url('container')) ?>">Behälter</a>
         einen Behälter an und erfasse Bestand.</p>
    <?php else: ?>
      <div class="steps">
        <?php foreach ($accounts as $a): ?>
          <?php
            $isMe  = (int) $a['wcf_user_id'] === (int) $meId;
            $isVer = $a['type'] === 'verein';
            $icon  = $isVer ? '🏛' : ($isMe ? '👤' : '🧱');
            $label = $isMe ? 'Mein Bestand' : $a['name'];
          ?>
          <a class="stepCard" href="<?= e(base_url('account/' . $a['id'])) ?>">
            <div class="stepNum"><?= $icon ?></div>
            <div class="stepTitle"><?= e($label) ?>
              <span class="pill <?= $isVer ? 'pill-ok' : 'pill-warn' ?>" style="margin-left:6px"><?= e($a['type']) ?></span>
            </div>
            <div class="stepText">
              <?= (int) $a['positions'] ?> Position(en) · <?= (int) $a['total_qty'] ?> Stück gesamt
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</article>
