<?php
/** @var array $container @var string $q @var array $results @var array|null $part
 *  @var array $colors @var string $csrf @var string $memberName
 *  @var string|null $vereinName @var bool $canVerein */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span>
  <a href="<?= e(base_url('container/' . $container['id'])) ?>"><?= e($container['code'] ?? $container['name']) ?></a><span class="sep">›</span>
  <span>Bestand erfassen</span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>Bestand erfassen in
    <?php if (!empty($container['code'])): ?><span class="codeTag"><?= e($container['code']) ?></span>&nbsp;<?php endif; ?><?= e(ContainerRules::label($container['kind'])) ?> · <?= e($container['name']) ?>
  </div>
  <div class="contentBoxBody">
    <form method="get" action="<?= e(base_url('container/' . $container['id'] . '/add')) ?>">
      <div class="formRow">
        <label for="q">Teil suchen (Teilenummer oder Name)</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="z. B. 3001 oder „Brick 2 x 4"" autofocus>
      </div>
      <button type="submit" class="btn">Suchen</button>
    </form>

    <?php if (!empty($results)): ?>
      <?php
        $addBase  = base_url('container/' . $container['id'] . '/add');
        $lastPage = (int) max(1, ceil($resultTotal / $resultLimit));
        $pageUrl  = function ($p) use ($addBase, $q) {
            return $addBase . '?q=' . urlencode($q) . '&page=' . (int) $p;
        };
      ?>
      <p class="hint">
        <?= (int) $resultTotal ?> Treffer<?php if ($resultTotal > count($results)): ?>,
          angezeigt <?= (int) $resultFrom ?>–<?= (int) $resultTo ?> (Seite <?= (int) $page ?> von <?= $lastPage ?>)<?php endif; ?>
      </p>
      <table>
        <thead><tr><th style="width:54px">Bild</th><th>Teil-Nr.</th><th>Name</th><th>Kategorie</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($results as $r): ?>
            <?php $img = part_image_url($r['element_id'] ?? null); ?>
            <tr>
              <td>
                <?php if ($img !== null): ?>
                  <img src="<?= e($img) ?>" alt="" loading="lazy"
                       style="width:42px;height:42px;object-fit:contain;border-radius:5px;border:1px solid var(--wcfContentBorder)"
                       onerror="this.style.display='none'">
                <?php else: ?>–<?php endif; ?>
              </td>
              <td><code><?= e($r['part_num']) ?></code></td>
              <td><?= e($r['name']) ?></td>
              <td><?= e($r['category'] ?? '–') ?></td>
              <td class="actions">
                <a class="btn-ghost btn-sm"
                   href="<?= e(base_url('container/' . $container['id'] . '/add?part=' . urlencode($r['part_num']))) ?>">wählen</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($lastPage > 1): ?>
        <div style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap">
          <?php if ($page > 1): ?>
            <a class="btn-ghost btn-sm" href="<?= e($pageUrl($page - 1)) ?>">← vorherige 200</a>
          <?php endif; ?>
          <span class="hint">Seite <?= (int) $page ?> von <?= $lastPage ?></span>
          <?php if ($page < $lastPage): ?>
            <a class="btn btn-sm" href="<?= e($pageUrl($page + 1)) ?>">nächste 200 →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php elseif ($q !== '' && $part === null): ?>
      <p>Keine Teile zu „<?= e($q) ?>" gefunden.</p>
    <?php endif; ?>
  </div>
</article>

<?php if ($part !== null): ?>
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Gewählt: <code><?= e($part['part_num']) ?></code> · <?= e($part['name']) ?></div>
  <div class="contentBoxBody">
    <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/inventory')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="part_num" value="<?= e($part['part_num']) ?>">

      <div class="formRow">
        <label for="color_id">Farbe</label>
        <select id="color_id" name="color_id" required>
          <?php foreach ($colors as $c): ?>
            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?> (#<?= e($c['rgb'] ?? '') ?>)</option>
          <?php endforeach; ?>
        </select>
        <div class="hint">Farben laut Rebrickable für dieses Teil (sonst alle Farben).</div>
      </div>

      <div class="formRow">
        <label for="cond">Zustand</label>
        <select id="cond" name="cond">
          <option value="gebraucht">gebraucht</option>
          <option value="neu">neu</option>
        </select>
      </div>

      <div class="formRow">
        <label for="quantity">Menge</label>
        <input type="number" id="quantity" name="quantity" min="1" value="1" required style="max-width:160px">
      </div>

      <div class="formRow">
        <label for="owner_scope">Besitzer</label>
        <select id="owner_scope" name="owner_scope">
          <option value="mein">Mein Bestand (<?= e($memberName) ?>)</option>
          <?php if ($canVerein && $vereinName !== null): ?>
            <option value="verein">Verein (<?= e($vereinName) ?>)</option>
          <?php endif; ?>
        </select>
      </div>

      <div class="formRow">
        <label for="visibility">Sichtbarkeit</label>
        <select id="visibility" name="visibility">
          <option value="privat">privat (nur ich)</option>
          <option value="intern">intern (für Mitglieder sichtbar)</option>
          <option value="verein">für Verein bereitgestellt</option>
        </select>
        <div class="hint">Gilt für „Mein Bestand". Vereinsbestand ist immer mindestens intern sichtbar.</div>
      </div>

      <button type="submit" class="btn btn-accent">Bestand buchen</button>
      <a class="btn-ghost" href="<?= e(base_url('container/' . $container['id'])) ?>">Fertig</a>
    </form>
  </div>
</article>
<?php endif; ?>
