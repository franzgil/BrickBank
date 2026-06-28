# Datenmodell

Das Datenmodell ist auf **MySQL** ausgelegt. Es trennt den *Katalog* (welche
Teile/Farben gibt es überhaupt) von den *Beständen* (was haben wir konkret, wo
und wem gehört es).

> **Tabellen-Präfix:** Alle physischen BrickBank-Tabellen tragen das Präfix
> `bb_` (z. B. `bb_owner`, `bb_location`, `bb_location_label`). In der Prosa unten
> werden die Entitäten ohne Präfix benannt; die DDL- und SQL-Blöcke verwenden den
> physischen Namen mit `bb_`. Die WoltLab-Tabellen (`wcf1_*`) sind davon
> unberührt.

## Entitäten im Überblick

```
owner (Besitzer)            location (Lagerort, hierarchisch)
   │                              │
   │                              │
   └──────────┐         ┌─────────┘
              ▼         ▼
          inventory_item (Bestandsposten)
                   │
                   ▼
              element (Teil + Farbe)
              ┌────┴────┐
            part      color
         (Teil)     (Farbe)
```

## Tabellen

### `owner` — Besitzer
Ein Besitzer ist entweder der Verein oder ein einzelnes Mitglied. Privatbesitzer
sind über die WoltLab-`userID` mit einem Forumsmitglied verknüpft (SSO, siehe
[INTEGRATION.md](INTEGRATION.md)).

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `type` | ENUM('verein','privat') | Vereins- oder Privatbestand |
| `wcf_user_id` | INT, NULL | logische Referenz auf `wcf1_user.userID` (nur bei `type='privat'`) |
| `name` | VARCHAR(120) | Anzeigename (z. B. „afol.lu“ oder Mitgliedsname) |
| `email` | VARCHAR(190), NULL | optional, für Mitglieder |
| `created_at` | DATETIME | |

### `location` — Lagerort (hierarchisch)
Selbst-referenzierend über `parent_id`, dadurch beliebig tiefe Hierarchien
(Raum → Schrank → Schublade → Box/Fach).

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `parent_id` | INT, NULL, FK → location.id | übergeordneter Ort (NULL = Wurzel) |
| `name` | VARCHAR(120) | z. B. „Schublade B2“ |
| `kind` | ENUM('raum','schrank','schublade','box','fach','sonstiges') | Art des Ortes |
| `note` | VARCHAR(255), NULL | freie Notiz |

### Katalog (Teile & Farben) — aus Rebrickable
Teile, Farben und ihre Detaildaten kommen aus dem **Rebrickable-Datensatz**
(`rb_parts`, `rb_part_categories`, `rb_colors`, `rb_elements`, …) in derselben
Datenbank, **nur lesend**. BrickBank pflegt dafür keine eigenen Teil-/Farbtabellen
mehr. Details: [REBRICKABLE.md](REBRICKABLE.md).

### `element` — Teil + Farbe (zählbare Einheit)
Verweist auf den Rebrickable-Katalog. Eindeutig je Kombination aus
`part_num` + `color_id`.

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `part_num` | VARCHAR(20) | → `rb_parts.part_num` |
| `color_id` | INT | → `rb_colors.id` |
| | UNIQUE(`part_num`,`color_id`) | verhindert Dubletten |

> Bewusst **keine** harten Fremdschlüssel auf `rb_*`, damit der Rebrickable-
> Datensatz unabhängig neu eingespielt werden kann.

### `inventory_item` — Bestandsposten
Das Herzstück: „**Menge** eines **Elements** an einem **Ort**, das einem
**Besitzer** gehört.“

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `element_id` | INT, FK → element.id | welches Teil+Farbe |
| `location_id` | INT, FK → location.id | wo gelagert |
| `owner_id` | INT, FK → owner.id | wem es gehört |
| `quantity` | INT, ≥ 0 | Anzahl Stück |
| `updated_at` | DATETIME | letzte Änderung |
| | UNIQUE(`element_id`,`location_id`,`owner_id`) | ein Posten je Kombination |

## Typische Abfragen

- **Gesamtmenge eines Elements** (über alle Orte/Besitzer):
  `SUM(quantity)` gruppiert nach `element_id`.
- **„Wo liegen die roten 2×4?“**: Join `inventory_item → element → part/color`,
  gefiltert nach `part_no`/Farbe, ausgegeben mit `location` und `quantity`.
- **„Was liegt in Schublade B2?“**: `inventory_item` gefiltert nach `location_id`
  (inkl. Kinder-Orte via rekursiver Auflösung der Hierarchie).
- **Vereins- vs. Privatbestand**: Filter über `owner.type`.

## DDL-Skizze (MySQL)

```sql
CREATE TABLE bb_owner (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('verein','privat') NOT NULL,
  wcf_user_id INT NULL,          -- logische Referenz auf wcf1_user.userID (SSO)
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_wcf_user (wcf_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bb_location (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  name VARCHAR(120) NOT NULL,
  kind ENUM('raum','schrank','schublade','box','fach','sonstiges') NOT NULL DEFAULT 'sonstiges',
  note VARCHAR(255) NULL,
  FOREIGN KEY (parent_id) REFERENCES bb_location(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teile/Farben kommen aus Rebrickable (rb_parts, rb_colors) – keine eigenen Tabellen.
CREATE TABLE bb_element (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_num VARCHAR(20) NOT NULL,     -- → rb_parts.part_num
  color_id INT NOT NULL,             -- → rb_colors.id
  UNIQUE KEY uq_element (part_num, color_id),
  KEY idx_element_part (part_num),
  KEY idx_element_color (color_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bb_inventory_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  element_id INT NOT NULL,
  location_id INT NOT NULL,
  owner_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_item (element_id, location_id, owner_id),
  FOREIGN KEY (element_id) REFERENCES bb_element(id),
  FOREIGN KEY (location_id) REFERENCES bb_location(id),
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Modul Behälter/Inventur (Migration 001)

Physische Behälter werden als spezialisierte `location` geführt
(`kind IN ('container','karton','tuete')`), ergänzt um etiketten- und
inventurspezifische Tabellen. Vollständige Begründung und DDL:
[MODUL-BEHAELTER-INVENTUR.md](MODUL-BEHAELTER-INVENTUR.md),
Skript `sql/migrations/001_module_behaelter_inventur.sql`.

| Tabelle | Zweck |
|---------|-------|
| `location_label` | 1:1 zu einer Behälter-`location`: ortsneutraler `code` (z. B. `T-000345`), `code_seq`, optionales `owner_id`, optionale `rfid_epc`. |
| `location_movement` | lückenlose Bewegungshistorie je Behälter; `from_*`/`to_*` als Snapshot, `wcf_user_id` = wer. |
| `stocktake` | Inventurlauf, optional auf `root_location_id`/`owner_id` eingegrenzt. |
| `stocktake_scan` | einzelne gescannte Codes je Lauf (Ist-Menge). |

**Schlüsselprinzip:** Der `code` ist **ortsneutral** — der Standort steckt allein
in `location.parent_id` und ist jederzeit änderbar, ohne das Etikett neu zu drucken.

### Beispielabfragen

```sql
-- Was ist in Tüte T-000345?  (Detaildaten aus Rebrickable)
SELECT e.part_num, rp.name AS teil, rc.name AS farbe, ii.quantity
FROM bb_location_label ll
JOIN bb_inventory_item ii ON ii.location_id = ll.location_id
JOIN bb_element e  ON e.id = ii.element_id
JOIN rb_parts rp   ON rp.part_num = e.part_num
JOIN rb_colors rc  ON rc.id = e.color_id
WHERE ll.code = 'T-000345';

-- In welcher physischen Tüte liegen unsere roten 2x4 (part 3001, color 4)?
SELECT ll.code, l.name AS behaelter, ii.quantity
FROM bb_element e
JOIN bb_inventory_item ii ON ii.element_id = e.id
JOIN bb_location l        ON l.id = ii.location_id
JOIN bb_location_label ll ON ll.location_id = l.id
WHERE e.part_num = '3001' AND e.color_id = 4;

-- Bewegungshistorie eines Behälters (neueste zuerst).
SELECT moved_at, from_code, to_code, wcf_user_id, note
FROM bb_location_movement
WHERE location_id = ?
ORDER BY moved_at DESC;
```
