<?php
/**
 * Haupt-Layout im afol.lu-Design (WoltLab-Optik).
 *
 * @var string        $content
 * @var string        $title
 * @var \App\Integration\WcfUser|null $currentUser
 */
use App\Integration\WcfSession;

$nav = isset($nav) ? $nav : '';
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · afol.lu</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/app.css')) ?>">
</head>
<body>

  <header class="pageHeader">
    <div class="pageHeaderInner">
      <a class="pageHeaderLogo" href="<?= e(base_url('/')) ?>">
        <img class="logoImg" src="<?= e(asset_url('assets/afol-logo.png')) ?>" alt="AFOL.lu">
        <span class="pageHeaderTitle">
          <span class="siteTitle">BrickBank</span>
          <span class="siteSubtitle">afol.lu · Inventar-System</span>
        </span>
      </a>
      <div class="userBox">
        <?php if ($currentUser !== null): ?>
          <span class="userName">👤 <?= e($currentUser->username) ?></span>
        <?php else: ?>
          <a class="userLogin" href="<?= e(WcfSession::loginUrl()) ?>">Anmelden</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <nav class="mainMenu">
    <div class="mainMenuInner">
      <ul class="mainMenuList">
        <li<?= ($nav === 'home') ? ' class="active"' : '' ?>><a href="<?= e(base_url('/')) ?>">Start</a></li>
        <li<?= ($nav === 'dashboard') ? ' class="active"' : '' ?>><a href="<?= e(base_url('dashboard')) ?>">Dashboard</a></li>
        <li<?= ($nav === 'container') ? ' class="active"' : '' ?>><a href="<?= e(base_url('container')) ?>">Behälter</a></li>
        <li<?= ($nav === 'project') ? ' class="active"' : '' ?>><a href="<?= e(base_url('projects')) ?>">Projekte</a></li>
        <li<?= ($nav === 'stock') ? ' class="active"' : '' ?>><a href="<?= e(base_url('stock')) ?>">Bestand</a></li>
        <li<?= ($nav === 'label') ? ' class="active"' : '' ?>><a href="<?= e(base_url('label')) ?>">Etiketten</a></li>
        <li<?= ($nav === 'stocktake') ? ' class="active"' : '' ?>><a href="<?= e(base_url('stocktake')) ?>">Inventur</a></li>
        <li<?= ($nav === 'help') ? ' class="active"' : '' ?>><a href="<?= e(base_url('anleitung')) ?>">Anleitung</a></li>
      </ul>
    </div>
  </nav>

  <div class="pageWrapper">
    <main>
      <?php if (!empty($flash)): ?>
        <div class="flash flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>

  <footer class="pageFooter">
    <div class="pageFooterInner">
      <div class="footerNote">
        BrickBank · ein Projekt der <a href="https://afol55.afol.lu/">AFOL.lu a.s.b.l.</a>
        (Adult Fans of LEGO Luxembourg). LEGO® ist eine Marke der LEGO Group, die dieses Projekt
        weder sponsert noch autorisiert oder unterstützt.
      </div>
    </div>
  </footer>

</body>
</html>
