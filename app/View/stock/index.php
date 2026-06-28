<?php
/** @var string $q @var array $results */
?>
<div class="breadcrumbs"><a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span><span>Bestand</span></div>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Bestand suchen</div>
  <div class="contentBoxBody">
    <form method="get" action="<?= e(base_url('stock')) ?>">
      <div class="formRow">
        <label for="q">Teil suchen (Teilenummer oder Name)</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="z. B. 3001 oder „Brick 2x4"" autofocus>
      </div>
      <button type="submit" class="btn">Suchen</button>
    </form>

    <?php if ($q !== '' && empty($results)): ?>
      <p>Kein (sichtbarer) Bestand zu „<?= e($q) ?>".</p>
    <?php elseif (!empty($results)): ?>
      <table>
        <thead><tr><th>Teil-Nr.</th><th>Teil</th><th>Farbe</th><th>Bestand</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($results as $r): ?>
            <tr>
              <td><code><?= e($r['part_num'] ?? '–') ?></code></td>
              <td><?= e($r['part_name'] ?? ('(' . $r['item_type'] . ')')) ?></td>
              <td><?= e($r['color_name'] ?? '–') ?></td>
              <td><?= e($r['on_hand']) ?></td>
              <td class="actions"><a class="btn-ghost btn-sm" href="<?= e(base_url('item/' . $r['item_id'])) ?>">Details</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
