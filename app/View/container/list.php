<?php
/** @var array $containers @var string|null $kind @var \App\Integration\WcfUser|null $currentUser */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs"><a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span><span>Behälter</span></div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Behälter</div>
  <div class="contentBoxBody">
    <p>
      <a class="btn-ghost btn-sm <?= $kind === null ? 'btn' : '' ?>" href="<?= e(base_url('container')) ?>">Alle</a>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('container?kind=container')) ?>">Container</a>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('container?kind=karton')) ?>">Kartons</a>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('container?kind=tuete')) ?>">Tüten</a>
      <?php if ($currentUser !== null): ?>
        <a class="btn btn-accent btn-sm" style="float:right" href="<?= e(base_url('container/new')) ?>">+ Behälter anlegen</a>
      <?php endif; ?>
    </p>

    <?php if (empty($containers)): ?>
      <p>Noch keine Behälter erfasst.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr><th>Code</th><th>Typ</th><th>Name</th><th>Aktueller Standort</th><th>Eigentümer</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($containers as $c): ?>
            <tr>
              <td><span class="codeTag"><?= e($c['code']) ?></span></td>
              <td><?= e(ContainerRules::label($c['kind'])) ?></td>
              <td><?= e($c['name']) ?></td>
              <td>
                <?php if (!empty($c['parent_code'])): ?>
                  <span class="codeTag"><?= e($c['parent_code']) ?></span>
                <?php elseif (!empty($c['parent_name'])): ?>
                  <?= e($c['parent_name']) ?>
                <?php else: ?>
                  <em>– kein Standort –</em>
                <?php endif; ?>
              </td>
              <td><?= e($c['owner_name'] ?? '–') ?></td>
              <td class="actions"><a class="btn-ghost btn-sm" href="<?= e(base_url('container/' . $c['id'])) ?>">Details</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
