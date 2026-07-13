<?php
/** Grafische Anleitung für BrickBank. Nutzt die vorhandenen Design-Komponenten. */
$box = 'background:#fafbfc;border:1px solid var(--wcfContentBorderInner);border-radius:6px;padding:14px 16px';
?>
<div class="breadcrumbs">
  <a href="<?= e(base_url('/')) ?>">Start</a><span class="sep">›</span><span>Anleitung</span>
</div>

<section class="hero">
  <span class="heroBadge">Handbuch</span>
  <h1>🧱 BrickBank – Anleitung</h1>
  <p class="heroSubtitle">BrickBank ist das Lager- und Inventarsystem der afol.lu. Es verwaltet,
     <strong>welche LEGO-Teile</strong> es gibt, <strong>wo</strong> sie liegen, <strong>wie viele</strong>
     und <strong>wem</strong> sie gehören – für den Verein, die Mitglieder und für Projekte.</p>
</section>

<!-- ====================== WORKFLOW IN 4 SCHRITTEN ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>In 4 Schritten zum erfassten Bestand</div>
  <div class="contentBoxBody">
    <div class="steps">
      <div class="stepCard">
        <div class="stepNum">1</div>
        <div class="stepTitle">🔑 Anmelden</div>
        <div class="stepText">Mit deinem afol.lu-Forenkonto einloggen – BrickBank nutzt dasselbe Login (SSO).</div>
      </div>
      <div class="stepCard">
        <div class="stepNum">2</div>
        <div class="stepTitle">🌳 Lagerbaum anlegen</div>
        <div class="stepText">Standorte und Behälter als Baum aufbauen (Regal → Box → Einsatzkasten …).</div>
      </div>
      <div class="stepCard">
        <div class="stepNum">3</div>
        <div class="stepTitle">📦 Behälter mit ID</div>
        <div class="stepText">Boxen/Kästen bekommen automatisch einen Code (Etikett/QR), optional eine eigene ID.</div>
      </div>
      <div class="stepCard">
        <div class="stepNum">4</div>
        <div class="stepTitle">🧱 Bestand erfassen</div>
        <div class="stepText">Teil suchen, Farbe/Zustand/Menge wählen – auch bequem per Waage zählen.</div>
      </div>
    </div>
  </div>
</article>

<!-- ====================== GRUNDBEGRIFFE ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Grundbegriffe</div>
  <div class="contentBoxBody">
    <table>
      <thead><tr><th style="width:170px">Begriff</th><th>Bedeutung</th></tr></thead>
      <tbody>
        <tr><td>🗂 <strong>Konto</strong></td><td>Wurzel eines Lagerbaums: dein <em>Mein Bestand</em>, der <em>Verein</em> oder ein <em>Projekt</em>.</td></tr>
        <tr><td>🌳 <strong>Ast / Standort</strong></td><td>Ein Knoten im Baum – z. B. Raum, Schrank, Regal. Äste dürfen frei verschoben werden.</td></tr>
        <tr><td>📦 <strong>Behälter</strong></td><td>Spezieller Ast mit <strong>automatischem Code</strong>: Container, Karton, Tüte, Sortimentsbox, Einsatzkasten.</td></tr>
        <tr><td>🧱 <strong>Position (Bestand)</strong></td><td>Eine konkrete Menge eines Teils in einem Behälter: <em>Teil · Farbe · Zustand · Besitzer</em>.</td></tr>
        <tr><td>🎨 <strong>Element / Teil</strong></td><td>Ein LEGO-Teil aus dem Rebrickable-Katalog = <em>Teilenummer + Farbe</em>.</td></tr>
        <tr><td>🧩 <strong>Projekt</strong></td><td>Eigener Lagerbaum für ein Bauvorhaben (MOC/Diorama) mit eigenen Behältern.</td></tr>
      </tbody>
    </table>
  </div>
</article>

<!-- ====================== LAGERBAUM ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>🌳 Der Lagerbaum</div>
  <div class="contentBoxBody">
    <p>Wo und wie gelagert wird, ist frei als <strong>Baum</strong> aufgebaut. Die Wurzel ist das Konto
       (oder Projekt). Jeder Ast kann Unter-Äste enthalten. So bildest du deine echte Lagerstruktur ab:</p>
    <pre style="<?= $box ?>;font-family:'SFMono-Regular',Consolas,monospace;font-size:13px;overflow-x:auto;line-height:1.7">
🗂 <strong>Konto: Mein Bestand</strong>
└─ 🌳 Mobiler Technic Bestand
   └─ 📦 <span class="codeTag">SB-001</span> Sortimentsbox 60×40
      ├─ 🗄 <span class="codeTag">EK-001</span> Einsatzkasten ── 🧱 3001 · <span style="color:#c0392b">Rot</span> · 120 Stk
      ├─ 🗄 <span class="codeTag">EK-002</span> Einsatzkasten ── 🧱 3002 · <span style="color:#0a4a66">Blau</span> · 80 Stk
      └─ 🗄 <span class="codeTag">EK-003</span> Einsatzkasten ── 🧱 3020 · <span style="color:#2e9b7a">Grün</span> · 240 Stk</pre>
    <p class="hint">Öffne einen Konto/Projekt im <a href="<?= e(base_url('dashboard')) ?>">Dashboard</a>, um seinen Baum
       zu sehen. Pro Ast kannst du ein <strong>Bild hochladen</strong>; die Bilder erscheinen im Baum und beim Inhalt.</p>

    <h3>Behälter-Codes</h3>
    <p>Behälter erhalten automatisch eine fortlaufende ID. Zusätzlich kannst du pro Behälter eine
       <strong>eigene ID</strong> parallel führen und einen RFID/QR-Code nutzen.</p>
    <table>
      <thead><tr><th>Typ</th><th>Code-Präfix</th><th>Beispiel</th></tr></thead>
      <tbody>
        <tr><td>Container</td><td><span class="codeTag">C-</span></td><td><span class="codeTag">C-001</span></td></tr>
        <tr><td>Karton</td><td><span class="codeTag">K-</span></td><td><span class="codeTag">K-00012</span></td></tr>
        <tr><td>Tüte</td><td><span class="codeTag">T-</span></td><td><span class="codeTag">T-000345</span></td></tr>
        <tr><td>Sortimentsbox</td><td><span class="codeTag">SB-</span></td><td><span class="codeTag">SB-001</span></td></tr>
        <tr><td>Einsatzkasten</td><td><span class="codeTag">EK-</span></td><td><span class="codeTag">EK-00012</span></td></tr>
      </tbody>
    </table>
  </div>
</article>

<!-- ====================== BESTAND ERFASSEN ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>🧱 Bestand erfassen</div>
  <div class="contentBoxBody">
    <p>Öffne einen Behälter und klicke <span class="btn btn-sm btn-accent" style="pointer-events:none">+ Bestand erfassen</span>.
       Dann Teil suchen und die Details wählen:</p>
    <ol style="line-height:1.8">
      <li><strong>Teil suchen</strong> – nach Teilenummer (z. B. <code>3001</code>) oder Name (z. B. <em>Brick 2 x 4</em>).</li>
      <li><strong>Farbe</strong> – das Auswahlmenü zeigt die echte Farbe je Eintrag.</li>
      <li><strong>Zustand</strong> – neu oder gebraucht.</li>
      <li><strong>Menge</strong> – direkt eingeben <em>oder per Waage zählen</em> (siehe unten).</li>
      <li><strong>Besitzer &amp; Sichtbarkeit</strong> – siehe Abschnitt „Sichtbarkeit".</li>
    </ol>

    <h3>Clevere Suche</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px">
      <div style="<?= $box ?>">🔎 <strong>Genau</strong><br><span class="hint">„Plate" findet keine „Baseplate"; „2 x 3" findet nur 2×3-Teile.</span></div>
      <div style="<?= $box ?>">🚫 <strong>Filter</strong><br><span class="hint">Duplo, Modulex &amp; Co. sind vorab ausgeblendet – pro Suche wieder aktivierbar.</span></div>
      <div style="<?= $box ?>">🖨 <strong>Bedruckte Teile</strong><br><span class="hint">Standardmäßig aus (Code z. B. <code>2431pr0121</code>), bei Bedarf einblenden.</span></div>
      <div style="<?= $box ?>">🖼 <strong>Bilder &amp; Seiten</strong><br><span class="hint">Vorschaubilder je Treffer, Blättern in 200er-Schritten.</span></div>
    </div>
  </div>
</article>

<!-- ====================== ZÄHLEN PER GEWICHT ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>⚖ Zählen per Gewicht</div>
  <div class="contentBoxBody">
    <p>Große Mengen gleicher Teile zählst du am schnellsten mit der Waage. BrickBank rechnet die Stückzahl
       aus. Für maximale Genauigkeit zuerst <strong>kalibrieren</strong>:</p>
    <div style="<?= $box ?>;max-width:640px">
      <div style="display:flex;flex-direction:column;gap:8px;font-size:14px">
        <div>① <strong>Kalibrieren:</strong> Einzelgewicht =
          <span class="codeTag">Referenzgewicht ÷ Referenzmenge</span></div>
        <div style="margin-left:18px" class="hint">Beispiel: 10 Teile wiegen 13,10 g → 13,10 ÷ 10 = <strong>1,31 g/Stück</strong></div>
        <div>② <strong>Zählen:</strong> Stückzahl =
          <span class="codeTag">Gesamtgewicht ÷ Einzelgewicht</span></div>
        <div style="margin-left:18px" class="hint">Beispiel: 262 g ÷ 1,31 g = <strong>200 Stück</strong></div>
      </div>
    </div>
    <p class="hint">Das ermittelte Einzelgewicht wird am Teil gespeichert und ist beim nächsten Mal
       vorbelegt – dann genügt das Gesamtgewicht. Verfügbar beim Erfassen <em>und</em> direkt in der Inhaltsliste.</p>
  </div>
</article>

<!-- ====================== INHALT BEARBEITEN ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>✏️ Inhalt bearbeiten &amp; nachverfolgen</div>
  <div class="contentBoxBody">
    <p>In der Inhaltsliste eines Behälters ist jede Position <strong>direkt editierbar</strong> – Änderungen
       werden sofort gespeichert:</p>
    <table>
      <thead><tr><th style="width:150px">Bedienelement</th><th>Funktion</th></tr></thead>
      <tbody>
        <tr><td>🎨 Farbe / Zustand</td><td>Auswahlmenü ändern → Position wird umklassifiziert.</td></tr>
        <tr><td>🔢 Menge</td><td>Zahl direkt setzen; <strong>0</strong> entfernt die Position.</td></tr>
        <tr><td>⚖ Gewicht</td><td>Einzel-/Gesamtgewicht → Stückzahl wird gesetzt.</td></tr>
        <tr><td>👁 Sichtbarkeit</td><td>privat / intern / für Verein umstellen.</td></tr>
        <tr><td>→ Umbuchen</td><td>Menge an einen anderen Ort verschieben.</td></tr>
        <tr><td>🗑 Löschen</td><td>Position vollständig entfernen.</td></tr>
        <tr><td>🕘 Verlauf</td><td>Spalte „Geändert" zeigt den Zeitpunkt; „Verlauf" listet <strong>alle</strong> Änderungen der Position.</td></tr>
      </tbody>
    </table>
    <p class="hint">Jede Mengenänderung wird protokolliert (Zugang, Entnahme, Umbuchung, Korrektur) –
       lückenlos nachvollziehbar.</p>
  </div>
</article>

<!-- ====================== SICHTBARKEIT ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>👁 Sichtbarkeit &amp; Besitzer</div>
  <div class="contentBoxBody">
    <p>Jede Position hat einen Besitzer (du oder der Verein) und eine Sichtbarkeit:</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
      <div style="<?= $box ?>">
        <span class="pill pill-err">privat</span>
        <p style="margin:8px 0 0">Nur du siehst diesen Bestand.</p>
      </div>
      <div style="<?= $box ?>">
        <span class="pill pill-warn">intern</span>
        <p style="margin:8px 0 0">Für alle angemeldeten Mitglieder sichtbar.</p>
      </div>
      <div style="<?= $box ?>">
        <span class="pill pill-ok">für Verein</span>
        <p style="margin:8px 0 0">Sichtbar und dem Verein bereitgestellt.</p>
      </div>
    </div>
    <p class="hint">Vereinsbestand ist immer mindestens <em>intern</em> sichtbar. Der Verein-Bestand
       (Lager) wird von Lagerwart/Vorstand verwaltet.</p>
  </div>
</article>

<!-- ====================== PROJEKTE / ETIKETTEN / INVENTUR ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>Weitere Bereiche</div>
  <div class="contentBoxBody">
    <div class="steps">
      <a class="stepCard" href="<?= e(base_url('projects')) ?>">
        <div class="stepNum">🧩</div>
        <div class="stepTitle">Projekte</div>
        <div class="stepText">Eigener Lagerbaum je Bauvorhaben. Anlegen und wie ein Konto mit Behältern füllen.</div>
      </a>
      <a class="stepCard" href="<?= e(base_url('label')) ?>">
        <div class="stepNum">🏷</div>
        <div class="stepTitle">Etiketten &amp; QR</div>
        <div class="stepText">Codes als Etiketten drucken und Behälter per QR schnell wiederfinden.</div>
      </a>
      <a class="stepCard" href="<?= e(base_url('stocktake')) ?>">
        <div class="stepNum">✅</div>
        <div class="stepTitle">Inventur</div>
        <div class="stepText">Soll/Ist je Behälter prüfen und Bestände abgleichen.</div>
      </a>
      <a class="stepCard" href="<?= e(base_url('stock')) ?>">
        <div class="stepNum">🔍</div>
        <div class="stepTitle">Bestand suchen</div>
        <div class="stepText">Global nach Teilen suchen: wo liegt was, wie viel ist verfügbar.</div>
      </a>
    </div>
  </div>
</article>

<!-- ====================== TIPPS ====================== -->
<article class="contentBox">
  <div class="contentBoxHeader"><span class="icon"></span>💡 Tipps</div>
  <div class="contentBoxBody">
    <div class="flash flash-success" style="border-left-width:4px">Wiege einmal ein Referenzstück – danach zählst du jede Menge in Sekunden per Gesamtgewicht.</div>
    <div class="flash flash-info">Nutze eigene IDs parallel zur automatischen ID, wenn du bereits ein Etikettensystem hast.</div>
    <div class="flash flash-warning">Lege sensiblen Bestand als <em>privat</em> an – erst <em>intern</em>/<em>für Verein</em> macht ihn für andere sichtbar.</div>
  </div>
</article>
