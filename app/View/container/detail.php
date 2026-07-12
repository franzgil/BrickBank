<?php
/** @var array $container @var array $contents @var string $csrf
 *  @var \App\Integration\WcfUser|null $currentUser */
use App\Service\ContainerRules;
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span>
  <a href="<?= e(base_url('container')) ?>">Behälter</a><span class="sep">›</span>
  <span><?= e($container['code']) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>
    <?php if (!empty($container['code'])): ?><span class="codeTag"><?= e($container['code']) ?></span>&nbsp; <?php endif; ?><?= e(ContainerRules::label($container['kind'])) ?> · <?= e($container['name']) ?>
  </div>
  <div class="contentBoxBody">
    <table>
      <tr><th style="width:200px">Aktueller Standort</th>
        <td>
          <?php if (!empty($container['parent_code'])): ?><span class="codeTag"><?= e($container['parent_code']) ?></span> <?php endif; ?>
          <?= e($container['parent_name'] ?? '– kein Standort –') ?>
        </td></tr>
      <?php if (!empty($container['code'])): ?>
      <tr><th>Automatische ID</th><td><span class="codeTag"><?= e($container['code']) ?></span></td></tr>
      <tr><th>Eigene ID</th>
        <td>
          <?php if ($currentUser !== null): ?>
            <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/custom-code')) ?>" style="display:flex;gap:6px;align-items:center">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="text" name="custom_code" value="<?= e($container['custom_code'] ?? '') ?>" maxlength="32" placeholder="optional, parallel zur automatischen ID" style="max-width:260px">
              <button type="submit" class="btn-ghost btn-sm">Speichern</button>
            </form>
          <?php else: ?>
            <?= !empty($container['custom_code']) ? '<span class="codeTag">' . e($container['custom_code']) . '</span>' : '–' ?>
          <?php endif; ?>
        </td></tr>
      <tr><th>RFID-EPC</th><td><?= e($container['rfid_epc'] ?? '–') ?></td></tr>
      <?php endif; ?>
      <tr><th>Notiz</th><td><?= e($container['note'] ?? '–') ?></td></tr>
    </table>

    <p style="margin-top:14px">
      <?php if ($currentUser !== null): ?>
        <a class="btn btn-sm btn-accent" href="<?= e(base_url('container/' . $container['id'] . '/add')) ?>">+ Bestand erfassen</a>
        <?php if (!empty($container['code'])): ?>
          <a class="btn btn-sm" href="<?= e(base_url('move?code=' . urlencode($container['code']))) ?>">Umräumen</a>
        <?php endif; ?>
      <?php endif; ?>
      <?php if (!empty($container['code'])): ?>
        <a class="btn-ghost btn-sm" href="<?= e(base_url('container/' . $container['id'] . '/history')) ?>">Verlauf</a>
      <?php endif; ?>
      <?php if ($currentUser !== null): ?>
        <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/delete')) ?>"
              style="display:inline" onsubmit="return confirm('Diesen Behälter löschen? Geht nur, wenn er leer ist.')">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <button type="submit" class="btn-ghost btn-sm">Löschen</button>
        </form>
      <?php endif; ?>
    </p>
  </div>
</article>

<?php if (!empty($children)): ?>
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Enthaltene Äste / Behälter</div>
  <div class="contentBoxBody">
    <table>
      <thead><tr><th style="width:54px">Bild</th><th>Ast</th><th>Art</th></tr></thead>
      <tbody>
        <?php foreach ($children as $c): ?>
          <tr>
            <td>
              <?php if (!empty($c['image_path'])): ?>
                <img src="<?= e(asset_url($c['image_path'])) ?>" alt=""
                     style="width:42px;height:42px;object-fit:cover;border-radius:5px;border:1px solid var(--wcfContentBorder)">
              <?php else: ?>–<?php endif; ?>
            </td>
            <td>
              <?php if (!empty($c['code'])): ?><span class="codeTag"><?= e($c['code']) ?></span> <?php endif; ?>
              <a href="<?= e(base_url('container/' . $c['id'])) ?>"><?= e($c['name']) ?></a>
            </td>
            <td><?= e(ContainerRules::label($c['kind'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</article>
<?php endif; ?>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Bild</div>
  <div class="contentBoxBody">
    <?php if (!empty($container['image_path'])): ?>
      <p><img src="<?= e(asset_url($container['image_path'])) ?>" alt="Bild"
              style="max-width:100%;max-height:340px;border-radius:6px;border:1px solid var(--wcfContentBorder)"></p>
    <?php else: ?>
      <p class="hint">Noch kein Bild.</p>
    <?php endif; ?>
    <?php if ($currentUser !== null): ?>
      <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/image')) ?>"
            enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="file" name="image" accept="image/*" required>
        <button type="submit" class="btn btn-sm">Hochladen</button>
      </form>
      <?php if (!empty($container['image_path'])): ?>
        <form method="post" action="<?= e(base_url('container/' . $container['id'] . '/image/remove')) ?>" style="margin-top:8px">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <button type="submit" class="btn-ghost btn-sm">Bild entfernen</button>
        </form>
      <?php endif; ?>
      <div class="hint">JPG, PNG, WebP oder GIF, max. 5 MB.</div>
    <?php endif; ?>
  </div>
</article>

<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Inhalt (Bestand)</div>
  <div class="contentBoxBody">
    <?php if (empty($contents)): ?>
      <p>Für diesen Behälter sind noch keine Bestandsposten erfasst.</p>
    <?php else: ?>
      <?php $visLabels = ['privat' => 'privat', 'intern' => 'intern', 'verein' => 'für Verein']; ?>
      <table>
        <thead><tr><th style="width:54px">Bild</th><th>Teil-Nr.</th><th>Teil</th><th>Kategorie</th><th>Farbe</th><th>Zustand</th><th>Menge</th><th>Besitzer</th><th>Sichtbarkeit</th><th>Geändert</th><th>Umbuchen / Löschen</th></tr></thead>
        <tbody>
          <?php foreach ($contents as $row): ?>
            <?php
              $img = part_image_url($row['element_id'] ?? null);
              $mayEdit = ($meId !== null) && (!empty($canWrite)
                          || (int) ($row['owner_wcf_user_id'] ?? 0) === (int) $meId);
              $cid = (int) $container['id'];
              $qty = (int) $row['quantity'];
              // gemeinsame versteckte Felder zur Identifikation der Position
              $hidden = '<input type="hidden" name="csrf_token" value="' . e($csrf) . '">'
                      . '<input type="hidden" name="item_id" value="' . (int) $row['item_id'] . '">'
                      . '<input type="hidden" name="owner_id" value="' . (int) $row['owner_id'] . '">'
                      . '<input type="hidden" name="cond" value="' . e($row['cond']) . '">';
              $url = function ($a) use ($cid) { return e(base_url('container/' . $cid . '/stock/' . $a)); };
            ?>
            <tr>
              <td>
                <?php if ($img !== null): ?>
                  <img src="<?= e($img) ?>" alt="" loading="lazy"
                       style="width:42px;height:42px;object-fit:contain;border-radius:5px;border:1px solid var(--wcfContentBorder)"
                       onerror="this.style.display='none'">
                <?php else: ?>–<?php endif; ?>
              </td>
              <td><?= e($row['part_num'] ?? '–') ?></td>
              <td><a href="<?= e(base_url('item/' . $row['item_id'])) ?>"><?= e($row['part_name'] ?? ('(' . $row['item_type'] . ')')) ?></a></td>
              <td><?= e($row['category'] ?? '–') ?></td>

              <td>
                <?php if ($mayEdit && $row['item_type'] === 'element'): ?>
                  <form method="post" action="<?= $url('reclassify') ?>" style="margin:0">
                    <?= $hidden ?>
                    <input type="hidden" name="new_cond" value="<?= e($row['cond']) ?>">
                    <input type="hidden" name="quantity" value="<?= $qty ?>">
                    <?php
                      $curRgb = '';
                      foreach ($colors as $c) {
                          if ((int) $c['id'] === (int) $row['color_id']) { $curRgb = trim((string) ($c['rgb'] ?? '')); break; }
                      }
                      $selStyle = 'max-width:150px' . ($curRgb !== '' ? ';background-color:#' . e($curRgb) . ';color:' . contrast_text_color($curRgb) : '');
                    ?>
                    <select name="color_id" style="<?= $selStyle ?>" onchange="this.form.submit()">
                      <?php foreach ($colors as $c): ?>
                        <?php $rgb = trim((string) ($c['rgb'] ?? '')); ?>
                        <option value="<?= e($c['id']) ?>"
                                style="<?= $rgb !== '' ? 'background-color:#' . e($rgb) . ';color:' . contrast_text_color($rgb) : '' ?>"<?= (int) $row['color_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                <?php else: ?>
                  <?= e($row['color_name'] ?? '–') ?>
                <?php endif; ?>
              </td>

              <td>
                <?php if ($mayEdit): ?>
                  <form method="post" action="<?= $url('reclassify') ?>" style="margin:0">
                    <?= $hidden ?>
                    <input type="hidden" name="quantity" value="<?= $qty ?>">
                    <select name="new_cond" onchange="this.form.submit()">
                      <option value="gebraucht"<?= $row['cond'] === 'gebraucht' ? ' selected' : '' ?>>gebraucht</option>
                      <option value="neu"<?= $row['cond'] === 'neu' ? ' selected' : '' ?>>neu</option>
                    </select>
                  </form>
                <?php else: ?>
                  <?= e($row['cond']) ?>
                <?php endif; ?>
              </td>

              <td>
                <?php if ($mayEdit): ?>
                  <form method="post" action="<?= $url('quantity') ?>" style="margin:0">
                    <?= $hidden ?>
                    <input type="number" name="quantity" min="0" value="<?= $qty ?>" style="width:80px"
                           title="Menge direkt setzen (Enter oder Verlassen des Feldes)" onchange="this.form.submit()">
                  </form>
                  <form method="post" action="<?= $url('weight') ?>" style="margin:4px 0 0"
                        title="Menge per Waage zählen: Gesamtgewicht ÷ Einzelgewicht">
                    <?= $hidden ?>
                    <div style="display:flex;gap:3px;align-items:center;flex-wrap:wrap">
                      <input type="number" name="unit_weight" step="0.001" min="0" style="width:64px"
                             value="<?= $row['unit_weight'] !== null ? e(rtrim(rtrim((string) $row['unit_weight'], '0'), '.')) : '' ?>"
                             placeholder="g/Stk" title="Einzelgewicht (g pro Stück)">
                      <input type="number" name="total_weight" step="0.001" min="0" style="width:64px"
                             placeholder="g ges." title="Gesamtgewicht (g)">
                      <button type="submit" class="btn-ghost btn-sm" title="per Gewicht zählen">⚖</button>
                    </div>
                  </form>
                <?php else: ?>
                  <strong><?= e($row['quantity']) ?></strong>
                <?php endif; ?>
              </td>

              <td><?= e($row['owner_name']) ?></td>

              <td>
                <?php if ($mayEdit): ?>
                  <form method="post" action="<?= $url('visibility') ?>" style="margin:0">
                    <?= $hidden ?>
                    <select name="visibility" style="max-width:130px" onchange="this.form.submit()">
                      <?php foreach ($visLabels as $v => $vl): ?>
                        <option value="<?= e($v) ?>"<?= $row['visibility'] === $v ? ' selected' : '' ?>><?= e($vl) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                <?php else: ?>
                  <?= e($row['visibility']) ?>
                <?php endif; ?>
              </td>

              <td style="white-space:nowrap">
                <?php $ts = !empty($row['updated_at']) ? date('d.m.Y H:i', strtotime($row['updated_at'])) : '–'; ?>
                <?= e($ts) ?><br>
                <a class="btn-ghost btn-sm"
                   href="<?= e(base_url('container/' . $cid . '/stock/history')
                        . '?item_id=' . (int) $row['item_id']
                        . '&owner_id=' . (int) $row['owner_id']
                        . '&cond=' . urlencode($row['cond'])) ?>">Verlauf</a>
              </td>

              <td class="actions">
                <?php if ($mayEdit): ?>
                  <div style="display:flex;gap:4px;align-items:center">
                    <form method="post" action="<?= $url('move') ?>"
                          style="display:flex;gap:4px;align-items:center;margin:0"
                          onsubmit="return this.to_location_id.value !== '' || (alert('Bitte Zielort wählen.'), false)">
                      <?= $hidden ?>
                      <input type="number" name="quantity" min="1" max="<?= $qty ?>" value="<?= $qty ?>" style="width:64px" title="Menge umbuchen">
                      <select name="to_location_id" style="max-width:150px">
                        <option value="">— Zielort —</option>
                        <?php foreach ($moveTargets as $t): ?>
                          <?php if ((int) $t['id'] === $cid) continue; ?>
                          <option value="<?= e($t['id']) ?>"><?= e(!empty($t['code']) ? $t['code'] . ' ' : '') . $t['name'] ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn-ghost btn-sm" title="umbuchen">→</button>
                    </form>
                    <form method="post" action="<?= $url('delete') ?>" style="margin:0"
                          onsubmit="return confirm('Diese Position vollständig entfernen?')">
                      <?= $hidden ?>
                      <button type="submit" class="btn-ghost btn-sm" title="Position löschen">🗑</button>
                    </form>
                  </div>
                <?php else: ?>
                  <span class="hint">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="hint">Farbe, Zustand, Menge und Sichtbarkeit sind direkt editierbar – die Änderung
         wird sofort gespeichert. Menge auch per Waage zählbar: Einzelgewicht (g/Stück) und
         Gesamtgewicht eingeben, ⚖ setzt die Stückzahl (Gesamt ÷ Einzel). Das Einzelgewicht
         wird gemerkt. Über die Zielort-Auswahl umbuchen (→) oder die Position mit 🗑 löschen;
         Menge auf 0 setzen entfernt sie ebenfalls.</p>
    <?php endif; ?>
  </div>
</article>
