# Datenmodell

Das Datenmodell ist auf **MySQL** ausgelegt. Es trennt den *Katalog* (welche
Teile/Farben gibt es überhaupt) von den *Beständen* (was haben wir konkret, wo
und wem gehört es).

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
Ein Besitzer ist entweder der Verein oder ein einzelnes Mitglied.

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `type` | ENUM('verein','privat') | Vereins- oder Privatbestand |
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

### `part` — Teil (farbunabhängig)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `part_no` | VARCHAR(40), UNIQUE | Teilenummer (z. B. BrickLink/LEGO-Design-ID) |
| `name` | VARCHAR(190) | z. B. „Brick 2 x 4“ |
| `category` | VARCHAR(80), NULL | z. B. „Brick“, „Plate“, „Technic“ |

### `color` — Farbe
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `name` | VARCHAR(80) | z. B. „Bright Red“ |
| `code` | VARCHAR(20), NULL | externer Farbcode (z. B. BrickLink-ID) |
| `hex` | CHAR(6), NULL | RGB für Darstellung |

### `element` — Teil + Farbe
Die zählbare Einheit. Eindeutig je Kombination aus `part_id` + `color_id`.

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | INT, PK, AUTO_INCREMENT | |
| `part_id` | INT, FK → part.id | |
| `color_id` | INT, FK → color.id | |
| | UNIQUE(`part_id`,`color_id`) | verhindert Dubletten |

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
CREATE TABLE owner (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('verein','privat') NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE location (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  name VARCHAR(120) NOT NULL,
  kind ENUM('raum','schrank','schublade','box','fach','sonstiges') NOT NULL DEFAULT 'sonstiges',
  note VARCHAR(255) NULL,
  FOREIGN KEY (parent_id) REFERENCES location(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE part (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_no VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(190) NOT NULL,
  category VARCHAR(80) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE color (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  code VARCHAR(20) NULL,
  hex CHAR(6) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE element (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_id INT NOT NULL,
  color_id INT NOT NULL,
  UNIQUE KEY uq_element (part_id, color_id),
  FOREIGN KEY (part_id) REFERENCES part(id),
  FOREIGN KEY (color_id) REFERENCES color(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  element_id INT NOT NULL,
  location_id INT NOT NULL,
  owner_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_item (element_id, location_id, owner_id),
  FOREIGN KEY (element_id) REFERENCES element(id),
  FOREIGN KEY (location_id) REFERENCES location(id),
  FOREIGN KEY (owner_id) REFERENCES owner(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
