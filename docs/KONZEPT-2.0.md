# BrickBank – Konzept 2.0 (überarbeitete Logik)

> **Status: Entwurf zur Abstimmung. Noch keine Implementierung.**
> Dieses Dokument überdenkt die Logik des Verwaltungssystems und ersetzt
> konzeptionell die enger gefassten Annahmen aus `KONZEPT.md`/`DATENMODELL.md`.
> Die bereits gebauten Teile (Behälter/Etiketten/Inventur, Orte, SSO,
> Rebrickable-Katalog) bleiben bestehen und docken an das allgemeinere Modell an.

## 1. Ziel & Umfang

BrickBank soll **alles** können: Teile **auffinden**, **exakte Mengen** führen,
**Eigentum** trennen, **verleihen** und **Projekte/MOCs** planen — für **lose
Teile, komplette Sets und Minifiguren**.

Festlegungen (abgestimmt):
- **Mengen:** exakte Stückzahlen, mit **Bewegungsprotokoll** (jede Änderung
  nachvollziehbar — sonst stimmen exakte Zahlen auf Dauer nicht).
- **Zustand:** `neu` / `gebraucht`.
- **Sichtbarkeit:** jeder Bestandsposten ist `privat` / `intern` / `verein`
  freigebbar; Vereinsbestand wird in einem **Lager** (Ort mit Lagerwart) geführt
  (Details §3.6).
- **Verleih:** nur an **WSC-Mitglieder** (über `wcf_user_id` aus dem SSO).
- **Projekte:** **Bestand und Verfügbarkeit** verwalten — reservierte Teile
  mindern die *Verfügbarkeit*, der physische *Bestand* bleibt unverändert.

## 2. Kernidee: ein gemeinsames „Item"

Damit Teile, Sets und Minifiguren nicht drei parallele Welten werden, gibt es
**eine** abstrakte zählbare Einheit `bb_item`:

```
bb_item.type ∈ { element, set, minifig }
   element → part_num + color_id   (→ rb_parts / rb_colors)
   set     → set_num               (→ rb_sets)
   minifig → fig_num               (→ rb_minifigs)
```

**Alles andere hängt nur an `bb_item`** — eine Bestands-, eine Bewegungs-, eine
Verleih- und eine Projektlogik für *alle* Objektarten.

## 3. Datenmodell (Überblick)

```
                 ┌──────────────── bb_item (element|set|minifig) ───────────────┐
                 │                                                              │
   bb_holding (Bestand)         bb_inventory_movement (Audit)     bb_project_part (Stückliste)
   item·ort·owner·zustand·menge   item·owner·von→nach·menge·typ     item·benötigt·reserviert
        │                                                              │
   bb_location (Orte + Behälter) ── bb_location_label / _movement   bb_project (MOC)
   bb_owner (Verein/Privat)
   bb_loan (Verleih an WSC-Mitglied: Behälter ODER Menge, mit Frist/Rückgabe)
```

Bestehend & unverändert: `bb_location`, `bb_location_label`,
`bb_location_movement`, `bb_stocktake(_scan)`, `bb_owner`, der `rb_*`-Katalog.

### 3.1 `bb_item` — zählbare Katalog-Einheit (NEU)
Ersetzt `bb_element` (das wird der Spezialfall `type='element'`).

```sql
CREATE TABLE bb_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('element','set','minifig') NOT NULL,
  part_num VARCHAR(20) NULL,   -- element → rb_parts.part_num
  color_id INT NULL,           -- element → rb_colors.id
  set_num  VARCHAR(20) NULL,   -- set     → rb_sets.set_num
  fig_num  VARCHAR(20) NULL,   -- minifig → rb_minifigs.fig_num
  item_key VARCHAR(48) NOT NULL,   -- kanonischer Schlüssel (app-vergeben)
  UNIQUE KEY uq_item_key (item_key),
  KEY idx_item_element (part_num, color_id),
  KEY idx_item_set (set_num),
  KEY idx_item_fig (fig_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
`item_key` macht die Eindeutigkeit robust (NULL-Spalten taugen nicht als
UNIQUE): `E:3001:4`, `S:10030-1`, `M:fig-000123`. Keine harten FKs auf `rb_*`
(Datensatz bleibt unabhängig neu importierbar).

### 3.2 `bb_holding` — Bestand (NEU, ersetzt `bb_inventory_item`)
„Menge eines **Items** an einem **Ort**, einem **Besitzer** gehörend, in einem
**Zustand**."

```sql
CREATE TABLE bb_holding (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  location_id INT NOT NULL,
  owner_id INT NOT NULL,                  -- Eigentum (Verein oder Mitglied)
  visibility ENUM('privat','intern','verein') NOT NULL DEFAULT 'privat',  -- Freigabe/Sichtbarkeit
  cond ENUM('neu','gebraucht') NOT NULL DEFAULT 'gebraucht',  -- "condition" ist MySQL-Schlüsselwort
  quantity INT NOT NULL DEFAULT 0,        -- Saldo, >= 0
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_holding (item_id, location_id, owner_id, cond),
  KEY idx_holding_loc (location_id),
  FOREIGN KEY (item_id) REFERENCES bb_item(id),
  FOREIGN KEY (location_id) REFERENCES bb_location(id),
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 `bb_inventory_movement` — Mengen-Audit (NEU)
Jede Änderung an `bb_holding.quantity` schreibt eine Bewegung. Der Saldo bleibt
schnell abfragbar, die Bewegung ist die nachvollziehbare Wahrheit dahinter.

```sql
CREATE TABLE bb_inventory_movement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  owner_id INT NOT NULL,
  cond ENUM('neu','gebraucht') NOT NULL,
  from_location_id INT NULL,
  to_location_id INT NULL,
  quantity INT NOT NULL,          -- Betrag (> 0)
  type ENUM('zugang','entnahme','umbuchung','korrektur') NOT NULL,
  wcf_user_id INT NULL,           -- wer (aus SSO)
  note VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_im_item (item_id),
  KEY idx_im_time (created_at),
  FOREIGN KEY (item_id) REFERENCES bb_item(id),
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id),
  FOREIGN KEY (from_location_id) REFERENCES bb_location(id) ON DELETE SET NULL,
  FOREIGN KEY (to_location_id) REFERENCES bb_location(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
- `zugang` → nur `to_location` · `entnahme` → nur `from_location`
- `umbuchung` → beide (von Ort A nach Ort B) · `korrektur` → Inventur-Anpassung

### 3.4 `bb_loan` — Verleih (NEU, nur an WSC-Mitglieder)
Zwei Varianten: **ganzer Behälter** oder **Menge eines Items**.

```sql
CREATE TABLE bb_loan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('container','menge') NOT NULL,
  location_id INT NULL,             -- container-Leihe (Behälter-location)
  item_id INT NULL,                 -- mengen-Leihe
  owner_id INT NULL,                -- aus wessen Bestand
  cond ENUM('neu','gebraucht') NULL,
  quantity INT NULL,
  borrower_wcf_user_id INT NOT NULL,-- Entleiher = WSC-Mitglied
  lent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  due_at DATETIME NULL,
  returned_at DATETIME NULL,        -- NULL = offen
  wcf_user_id INT NULL,             -- wer hat verbucht
  note VARCHAR(160) NULL,
  KEY idx_loan_open (returned_at),
  KEY idx_loan_borrower (borrower_wcf_user_id),
  FOREIGN KEY (location_id) REFERENCES bb_location(id) ON DELETE SET NULL,
  FOREIGN KEY (item_id) REFERENCES bb_item(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
- **Offen** = `returned_at IS NULL` · **überfällig** = `due_at < NOW() AND returned_at IS NULL`.
- Offene **Mengen**-Leihen mindern die **Verfügbarkeit** (siehe §4). Der Behälter
  einer Container-Leihe gilt als „aktuell beim Mitglied".

### 3.5 `bb_project` / `bb_project_part` — MOC/Projekte (NEU)

```sql
CREATE TABLE bb_project (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  status ENUM('geplant','in_bau','fertig','archiviert') NOT NULL DEFAULT 'geplant',
  wcf_user_id INT NULL,             -- Ersteller/Verantwortlicher (WSC)
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bb_project_part (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  item_id INT NOT NULL,
  qty_needed INT NOT NULL DEFAULT 0,
  qty_allocated INT NOT NULL DEFAULT 0,   -- reserviert aus Bestand
  UNIQUE KEY uq_project_item (project_id, item_id),
  FOREIGN KEY (project_id) REFERENCES bb_project(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES bb_item(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Optional komfortabel: die Stückliste eines Sets lässt sich aus
`rb_inventory_parts` automatisch erzeugen („was fehlt mir für Set X?").

### 3.6 Eigentum, Sichtbarkeit & Verwaltung (Mandanten-Logik)

Drei **getrennte** Konzepte – „Bestand" = eine einzelne Position (`bb_holding`,
z. B. eine Tüte oder ein Element):

- **Eigentum** (`bb_holding.owner_id`): Verein **oder** ein Mitglied.
- **Sichtbarkeit/Freigabe** (`bb_holding.visibility`): wer den Posten *sehen* darf.
- **Verwaltung/Lager** (`bb_location.managed_by_wcf_user_id`): welches Mitglied
  einen Ort/„Lager" betreut (für Vereinsbestand, der vor Ort verwaltet wird).

**Sichtbarkeitsstufen je Bestandsposition:**

| Stufe | Bedeutung | Wer sieht ihn |
|-------|-----------|---------------|
| `privat` | nur für den Besitzer | nur das eigene Mitglied |
| `intern` | für alle Mitglieder sichtbar (Info/Koordination) | alle eingeloggten Mitglieder |
| `verein` | dem Verein bereitgestellt (Nutzung für Events/Projekte), Eigentum bleibt beim Mitglied | alle Mitglieder + Verein |

**Vereinsbestand** (`owner = Verein`) ist für Mitglieder sichtbar und wird über
ein **Lager** geführt: ein `bb_location` mit `managed_by_wcf_user_id` (Lagerwart).

```sql
-- Erweiterung des bestehenden bb_location um die Lager-Verwaltung:
ALTER TABLE bb_location
  ADD COLUMN managed_by_wcf_user_id INT NULL;   -- Lagerwart (WSC-Mitglied), optional
```

**Die vier praktischen Fälle:**

| Fall | owner | visibility | Lagerwart |
|------|-------|------------|-----------|
| Privater Bestand eines Mitglieds | Mitglied | `privat` | – |
| Interner Bestand (für Mitglieder offen) | Mitglied | `intern` | – |
| Für den Verein freigegeben | Mitglied | `verein` | – |
| Vereinslager (Verein-Eigentum, Mitglied verwaltet) | Verein | `intern`/`verein` | Mitglied |

**Zugriffsregeln (eingeloggtes Mitglied U):**
- **Lesen:** eigener Bestand immer; fremder Mitglieds-Bestand nur bei
  `intern`/`verein`; Vereinsbestand immer. `privat` fremder Mitglieder: nie.
- **Schreiben:** Mitglieds-Bestand nur der Besitzer; Vereinsbestand der
  zuständige **Lagerwart** (und berechtigte WSC-Gruppen, z. B. Vorstand, über
  `wsc.allowed_group_ids`).
- **Gäste (nicht eingeloggt):** kein Zugriff (App ist mitgliederintern).

> **Freigeben ≠ Verschenken.** `visibility = 'verein'` *stellt bereit*, das
> Eigentum bleibt beim Mitglied. Eine echte **Eigentumsübertragung** an den
> Verein ist eine separate, ausdrückliche Aktion (ändert `owner_id`).

## 4. Die zentrale Logik in Formeln

```
Bestand(item)     = Σ bb_holding.quantity            (über alle Orte/Owner/Zustände)
Reserviert(item)  = Σ bb_project_part.qty_allocated
                  + Σ bb_loan.quantity  (type='menge' AND returned_at IS NULL)
Verfügbar(item)   = Bestand(item) − Reserviert(item)
```
Alle drei lassen sich auch je **Owner** oder je **Zustand** filtern (z. B.
„verfügbarer Vereinsbestand, gebraucht").

## 5. Regeln, die das Modell sauber halten
1. **Eigentum lebt an der Buchung** (`bb_holding.owner_id`). Behälter-Eigentum
   (`location_label.owner_id`) ist nur ein **Default-Vorschlag** beim Erfassen.
2. **Mengen ändern sich nur über `bb_inventory_movement`** → jeder Saldo ist erklärbar.
3. **Bestand hängt an einem Ort.** Ein etikettierter Behälter ist der Normalfall
   (Auffindbarkeit), loser Bestand *darf* auch direkt in einem Ort liegen.
4. **Reservierung ≠ Entnahme.** Projektzuteilung und offene Mengen-Leihe mindern
   nur die **Verfügbarkeit**; der physische Bestand sinkt erst bei echter Entnahme.
5. **Verleih nur an WSC-Mitglieder** (`borrower_wcf_user_id`).
6. **Sichtbarkeit pro Position:** `privat` (nur Besitzer) / `intern` (alle
   Mitglieder) / `verein` (bereitgestellt). Vereinsbestand wird vom **Lagerwart**
   des jeweiligen Lagers verwaltet. Freigeben ändert kein Eigentum (§3.6).

## 6. Beispielabfragen
```sql
-- Bestand & Verfügbarkeit eines Teils (3001 in Rot=4)
SELECT i.id,
       COALESCE(SUM(h.quantity),0) AS bestand
FROM bb_item i LEFT JOIN bb_holding h ON h.item_id = i.id
WHERE i.item_key = 'E:3001:4' GROUP BY i.id;
-- Verfügbar = bestand − (Σ project_part.qty_allocated + Σ offene Mengen-Leihen)

-- In welchen Behältern liegt ein Item?
SELECT ll.code, l.name, h.cond, h.quantity
FROM bb_holding h
JOIN bb_location l ON l.id = h.location_id
LEFT JOIN bb_location_label ll ON ll.location_id = l.id
WHERE h.item_id = ?;

-- Offene/überfällige Leihen
SELECT * FROM bb_loan
WHERE returned_at IS NULL
ORDER BY (due_at IS NOT NULL AND due_at < NOW()) DESC, due_at;

-- Fehlmenge für ein Projekt
SELECT pp.item_id, pp.qty_needed, pp.qty_allocated,
       GREATEST(pp.qty_needed - pp.qty_allocated, 0) AS offen
FROM bb_project_part pp WHERE pp.project_id = ?;
```

## 7. Verhältnis zum heutigen Stand (konzeptionelle Migration)
| heute | wird |
|-------|------|
| `bb_element(part_num,color_id)` | `bb_item(type='element', …)` + `item_key` |
| `bb_inventory_item` | `bb_holding` (+ `item_id`, + `cond`) |
| – | NEU: `bb_inventory_movement`, `bb_loan`, `bb_project`, `bb_project_part` |
| Behälter/Etiketten/Inventur/Orte/SSO/Rebrickable | **unverändert** |

Bestehende Behälter-Inhalte (heute `bb_inventory_item`) werden 1:1 zu
`bb_holding` (Zustand-Default `gebraucht`); jede Altmenge erhält eine
`zugang`-Bewegung als Startbestand.

## 8. Roadmap (phasiert, jede Phase eigenständig nutzbar)
- **Phase A — Fundament 2.0:** `bb_item` + `bb_holding` + `bb_inventory_movement`;
  Migration von `bb_element`/`bb_inventory_item`; Erfassen/Entnehmen/Umbuchen mit
  Audit; **Bestand & Verfügbarkeit** anzeigen.
- **Phase B — Sets & Minifiguren:** als Items suchen/erfassen (aus `rb_sets`/`rb_minifigs`).
- **Phase C — Verleih:** `bb_loan`; Übersicht offen/überfällig; Rückgabe.
- **Phase D — Projekte/MOC:** `bb_project`/`bb_project_part`; Stückliste,
  Reservierung, Fehlmengen; optional Auto-Stückliste aus `rb_inventory_parts`.

## 9. Bewusst offen / später
- Owner-genaue Verfügbarkeit (Reservierung gegen bestimmten Besitzerbestand).
- Mengen-Inventur (Soll/Ist auf Item-Ebene) zusätzlich zur Behälter-Inventur.
- Mehrere Etiketten-/Code-Schemata für Sets/Minifiguren (falls gewünscht).
