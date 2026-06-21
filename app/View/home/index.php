<?php
/** @var \App\Integration\WcfUser|null $currentUser */
$nav = 'home';
?>
<div class="breadcrumbs">
  <span>Start</span>
</div>

<section class="hero">
  <span class="heroBadge">Inventar-System</span>
  <h1>BrickBank 🧱</h1>
  <p class="heroSubtitle">Wie viele Steine haben wir – und wo sind sie? Vereins- und
     Privatbestände, physische Behälter mit QR-Etikett und Inventur an einem Ort.</p>
</section>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Module</div>
  <div class="contentBoxBody">
    <div class="steps">
      <a class="stepCard" href="<?= e(base_url('container')) ?>">
        <div class="stepNum">📦</div>
        <div class="stepTitle">Behälter</div>
        <div class="stepText">Container, Kartons und Tüten mit ortsneutralem Code anlegen und verwalten.</div>
      </a>
      <a class="stepCard" href="<?= e(base_url('label')) ?>">
        <div class="stepNum">🏷️</div>
        <div class="stepTitle">Etiketten</div>
        <div class="stepText">QR-Etiketten als Druckansicht und CSV-Export für den P-touch-Editor.</div>
      </a>
      <a class="stepCard" href="<?= e(base_url('stocktake')) ?>">
        <div class="stepNum">✓</div>
        <div class="stepTitle">Inventur</div>
        <div class="stepText">Inventurläufe per Scan mit Soll-Ist-Abgleich (gefunden/fehlend/unerwartet).</div>
      </a>
    </div>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Anmeldung</div>
  <div class="contentBoxBody">
    <?php if ($currentUser !== null): ?>
      <p>Angemeldet als <strong><?= e($currentUser->username) ?></strong> (WoltLab-SSO).
         Schreibende Aktionen sind freigeschaltet, sofern deine Benutzergruppe berechtigt ist.</p>
    <?php else: ?>
      <p>Du bist nicht angemeldet. Lesen ist möglich; zum Erfassen und Umräumen bitte über das
         afol.lu-Forum <a href="<?= e(\App\Integration\WcfSession::loginUrl()) ?>">anmelden</a>
         (Single Sign-on).</p>
    <?php endif; ?>
  </div>
</article>
