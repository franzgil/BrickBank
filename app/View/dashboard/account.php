<?php
/** @var array $owner @var bool $isMe @var array $byParent @var array $branches
 *  @var array $summary @var bool $canEdit @var string $csrf */
use App\Service\CodeGenerator;
use App\Service\ContainerRules;

$label  = $isMe ? 'Mein Bestand' : $owner['name'];
$accId  = (int) $owner['id'];
$placeKinds     = ['raum', 'schrank', 'schublade', 'box', 'fach', 'sonstiges'];
$containerKinds = ['container', 'karton', 'tuete', 'sortimentsbox', 'einsatzkasten'];

if (!function_exists('bb_render_branches')) {
    function bb_render_branches(int $parentKey, array $byParent, array $summary, array $branches, int $accId, bool $canEdit, string $csrf, int $depth): void
    {
        if (empty($byParent[$parentKey])) {
            return;
        }
        foreach ($byParent[$parentKey] as $b) {
            $id   = (int) $b['id'];
            $isC  = CodeGenerator::isContainerKind($b['kind']);
            $sum  = $summary[$id] ?? ['cnt' => 0, 'qty' => 0];
            $pad  = $depth * 22;
            ?>
            <tr>
              <td style="padding-left:<?= (12 + $pad) ?>px">
                <?= $depth > 0 ? '└ ' : '' ?>
                <?php if (!empty($b['image_path'])): ?><img src="<?= e(asset_url($b['image_path'])) ?>" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:5px;vertical-align:middle;margin-right:6px;border:1px solid var(--wcfContentBorder)"> <?php endif; ?>
                <?php if (!empty($b['code'])): ?><span class="codeTag"><?= e($b['code']) ?></span> <?php endif; ?>
                <a href="<?= e(base_url('container/' . $id)) ?>"><?= e($b['name']) ?></a>
              </td>
              <td><?= e(ucfirst($b['kind'])) ?></td>
              <td><?= $sum['cnt'] > 0 ? ((int) $sum['cnt'] . ' Pos. · ' . (int) $sum['qty'] . ' Stk') : '–' ?></td>
              <?php if ($canEdit): ?>
              <td class="actions">
                <form method="post" action="<?= e(base_url('account/' . $accId . '/branch/move')) ?>" style="display:flex;gap:4px">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                  <input type="hidden" name="location_id" value="<?= $id ?>">
                  <select name="to_parent_id" style="max-width:170px">
                    <option value="">— Konto-Root —</option>
                    <?php foreach ($branches as $t): ?>
                      <?php if ((int) $t['id'] === $id) continue; ?>
                      <option value="<?= e($t['id']) ?>"><?= e(!empty($t['code']) ? $t['code'] . ' ' : '') . $t['name'] ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-ghost btn-sm" title="verschieben">→</button>
                </form>
                <?php if (empty($byParent[$id]) && (($summary[$id]['cnt'] ?? 0) === 0)): ?>
                  <form method="post" action="<?= e(base_url('account/' . $accId . '/branch/delete')) ?>"
                        style="display:inline" onsubmit="return confirm('Diesen leeren Ast löschen?')">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="location_id" value="<?= $id ?>">
                    <button type="submit" class="btn-ghost btn-sm" title="löschen">🗑</button>
                  </form>
                <?php endif; ?>
              </td>
              <?php endif; ?>
            </tr>
            <?php
            bb_render_branches($id, $byParent, $summary, $branches, $accId, $canEdit, $csrf, $depth + 1);
        }
    }
}
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('dashboard')) ?>">Dashboard</a><span class="sep">›</span><span><?= e($label) ?></span>
</div>

<article class="contentBox">
  <div class="contentBoxHeader">
    <span class="icon"></span>Lagerbaum · <?= e($label) ?>
    <span class="pill <?= $owner['type'] === 'verein' ? 'pill-ok' : 'pill-warn' ?>" style="margin-left:6px"><?= e($owner['type']) ?></span>
  </div>
  <div class="contentBoxBody">
    <p class="hint">Wurzel ist das Konto. Äste ohne Eltern hängen direkt am Konto.
       Jeder Ast darf frei verschoben werden.</p>
    <?php if (empty($branches)): ?>
      <p>Noch keine Äste. Lege unten einen an.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Ast</th><th>Art</th><th>Bestand</th><?php if ($canEdit): ?><th>Verschieben nach</th><?php endif; ?></tr></thead>
        <tbody>
          <?php bb_render_branches(0, $byParent, $summary, $branches, $accId, $canEdit, $csrf, 0); ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</article>

<?php if ($canEdit): ?>
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Ast hinzufügen</div>
  <div class="contentBoxBody">
    <form method="post" action="<?= e(base_url('account/' . $accId . '/branch')) ?>">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <div class="formRow">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" maxlength="120" required placeholder="z. B. Keller, Regal A oder „Tüte rote 1x1"">
      </div>
      <div class="formRow">
        <label for="kind">Art</label>
        <select id="kind" name="kind">
          <optgroup label="Standort">
            <?php foreach ($placeKinds as $k): ?><option value="<?= e($k) ?>"><?= e(ucfirst($k)) ?></option><?php endforeach; ?>
          </optgroup>
          <optgroup label="Behälter (bekommt Code/Etikett)">
            <?php foreach ($containerKinds as $k): ?><option value="<?= e($k) ?>"><?= e(ContainerRules::label($k)) ?></option><?php endforeach; ?>
          </optgroup>
        </select>
        <div class="hint">Behälter (Container/Karton/Tüte) erhalten automatisch einen Code. Reihenfolge ist frei – nur Empfehlung.</div>
      </div>
      <div class="formRow">
        <label for="parent_id">Übergeordneter Ast</label>
        <select id="parent_id" name="parent_id">
          <option value="">— Konto-Root (direkt am Konto) —</option>
          <?php foreach ($branches as $t): ?>
            <option value="<?= e($t['id']) ?>"><?= e(!empty($t['code']) ? $t['code'] . ' ' : '') . $t['name'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="formRow">
        <label for="count">Anzahl</label>
        <input type="number" id="count" name="count" min="1" max="200" value="1" style="max-width:120px">
        <div class="hint">Mehrere auf einmal anlegen (z. B. 77 Einsatzkästen) – sie werden durchnummeriert.</div>
      </div>
      <div class="formRow">
        <label for="note">Notiz (optional)</label>
        <input type="text" id="note" name="note" maxlength="255">
      </div>
      <button type="submit" class="btn btn-accent">Ast(e) anlegen</button>
    </form>
  </div>
</article>
<?php endif; ?>
