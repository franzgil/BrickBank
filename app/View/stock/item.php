<?php
/** @var array $item @var int $onHand @var int $available @var array $places
 *  @var array $history @var array $targets @var string $csrf
 *  @var \App\Integration\WcfUser|null $currentUser */
$title = $item['part_name'] ?? ('Item ' . $item['id']);
$loggedIn = $currentUser !== null;
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('stock')) ?>">Bestand</a><span class="sep">›</span><span><?= e($title) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>
    <?php if (!empty($item['part_num'])): ?><code><?= e($item['part_num']) ?></code> · <?php endif; ?>
    <?= e($title) ?><?php if (!empty($item['color_name'])): ?> · <?= e($item['color_name']) ?><?php endif; ?>
  </div>
  <div class="contentBoxBody">
    <p>
      Bestand: <strong><?= (int) $onHand ?></strong>
      &nbsp;·&nbsp; verfügbar: <strong><?= (int) $available ?></strong>
      <span class="hint">(verfügbar = Bestand − Reservierungen; Reservierungen folgen in Phase C/D)</span>
    </p>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Wo liegt es?</div>
  <div class="contentBoxBody">
    <?php if (empty($places)): ?>
      <p>Kein sichtbarer Bestand.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Ort</th><th>Besitzer</th><th>Zustand</th><th>Sichtbar</th><th>Menge</th><?php if ($loggedIn): ?><th>Entnehmen</th><th>Umbuchen</th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach ($places as $p): ?>
            <tr>
              <td><?php if (!empty($p['location_code'])): ?><span class="codeTag"><?= e($p['location_code']) ?></span> <?php endif; ?><?= e($p['location_name']) ?></td>
              <td><?= e($p['owner_name']) ?></td>
              <td><?= e($p['cond']) ?></td>
              <td><?= e($p['visibility']) ?></td>
              <td><?= e($p['quantity']) ?></td>
              <?php if ($loggedIn): ?>
              <td class="actions">
                <form method="post" action="<?= e(base_url('item/' . $item['id'] . '/remove')) ?>" style="display:flex;gap:4px">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                  <input type="hidden" name="location_id" value="<?= e($p['location_id']) ?>">
                  <input type="hidden" name="owner_id" value="<?= e($p['owner_id']) ?>">
                  <input type="hidden" name="cond" value="<?= e($p['cond']) ?>">
                  <input type="number" name="quantity" min="1" max="<?= e($p['quantity']) ?>" value="1" style="width:70px;max-width:70px">
                  <button type="submit" class="btn-ghost btn-sm">−</button>
                </form>
              </td>
              <td class="actions">
                <form method="post" action="<?= e(base_url('item/' . $item['id'] . '/move')) ?>" style="display:flex;gap:4px">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                  <input type="hidden" name="location_id" value="<?= e($p['location_id']) ?>">
                  <input type="hidden" name="owner_id" value="<?= e($p['owner_id']) ?>">
                  <input type="hidden" name="cond" value="<?= e($p['cond']) ?>">
                  <input type="number" name="quantity" min="1" max="<?= e($p['quantity']) ?>" value="1" style="width:60px;max-width:60px">
                  <select name="to_location_id" style="max-width:160px">
                    <option value="">Zielort…</option>
                    <?php foreach ($targets as $t): ?>
                      <?php if ((int) $t['id'] === (int) $p['location_id']) continue; ?>
                      <option value="<?= e($t['id']) ?>"><?= e(ucfirst($t['kind'])) ?>: <?= e(!empty($t['code']) ? $t['code'] . ' ' : '') . $t['name'] ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-ghost btn-sm">→</button>
                </form>
              </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Bewegungen</div>
  <div class="contentBoxBody">
    <?php if (empty($history)): ?>
      <p>Keine Bewegungen.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Zeitpunkt</th><th>Typ</th><th>Zustand</th><th>Menge</th><th>Besitzer</th><th>Benutzer</th><th>Notiz</th></tr></thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= e($h['created_at']) ?></td>
              <td><?= e($h['type']) ?></td>
              <td><?= e($h['cond']) ?></td>
              <td><?= e($h['quantity']) ?></td>
              <td><?= e($h['owner_name']) ?></td>
              <td><?= e($h['wcf_user_id'] ?? '–') ?></td>
              <td><?= e($h['note'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>
