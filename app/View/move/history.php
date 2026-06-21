<?php
/** @var array $container @var array $history */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span>
  <a href="<?= e(base_url('container/' . $container['id'])) ?>"><?= e($container['code']) ?></a><span class="sep">›</span>
  <span>Verlauf</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>Bewegungsverlauf · <span class="codeTag"><?= e($container['code']) ?></span>
    &nbsp;<?= e(ContainerRules::label($container['kind'])) ?>
  </div>
  <div class="contentBoxBody">
    <?php if (empty($history)): ?>
      <p>Für diesen Behälter sind noch keine Bewegungen protokolliert.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Zeitpunkt</th><th>Von</th><th>Nach</th><th>Benutzer (WSC)</th><th>Notiz</th></tr></thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= e($h['moved_at']) ?></td>
              <td><?= !empty($h['from_code']) ? '<span class="codeTag">' . e($h['from_code']) . '</span>' : e($h['from_parent_id'] ?? '–') ?></td>
              <td><?= !empty($h['to_code']) ? '<span class="codeTag">' . e($h['to_code']) . '</span>' : e($h['to_parent_id'] ?? '–') ?></td>
              <td><?= e($h['wcf_user_id'] ?? '–') ?></td>
              <td><?= e($h['note'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
