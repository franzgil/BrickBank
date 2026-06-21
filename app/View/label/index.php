<?php
/** @var array $labels */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs"><a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span><span>Etiketten</span></div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Etiketten</div>
  <div class="contentBoxBody">
    <p>
      <a class="btn btn-sm" href="<?= e(base_url('label/print')) ?>">Alle drucken</a>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('label/print?kind=tuete')) ?>">Nur Tüten drucken</a>
      <a class="btn-ghost btn-sm" href="<?= e(base_url('label/export')) ?>">CSV-Export (P-touch)</a>
    </p>

    <?php if (empty($labels)): ?>
      <p>Noch keine etikettierten Behälter vorhanden.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Code</th><th>Typ</th><th>Name</th><th>Standort</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($labels as $l): ?>
            <tr>
              <td><span class="codeTag"><?= e($l['code']) ?></span></td>
              <td><?= e(ContainerRules::label($l['kind'])) ?></td>
              <td><?= e($l['name']) ?></td>
              <td><?= !empty($l['parent_code']) ? '<span class="codeTag">' . e($l['parent_code']) . '</span> ' : '' ?><?= e($l['parent_name'] ?? '–') ?></td>
              <td class="actions"><a class="btn-ghost btn-sm" href="<?= e(base_url('label/' . $l['id'])) ?>">Vorschau</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
