# Modul: Behälter, Etiketten & Inventur (QR/RFID)

> **Auftrag für Claude Code.** Dieses Dokument beschreibt ein neues Funktionsmodul
> für BrickBank. Es ist **anschlussfähig an das bestehende Datenmodell, MVC und
> die WSC-SSO-Kopplung** (siehe `DATENMODELL.md`, `ARCHITEKTUR.md`,
> `INTEGRATION.md`). Bitte in BrickBanks Konventionen umsetzen: PDO, Prepared
> Statements, CSRF-Token für schreibende Formulare, `htmlspecialchars` bei jeder
> Ausgabe, Controller ohne SQL, Views ohne Logik, WSC nur lesend.

---

## 1. Warum dieses Modul — und wie es zu BrickBank passt

BrickBank denkt heute in **Mengen pro Element**: „150 rote 2×4 in Schublade B2,
Eigentum Verein" (`inventory_item`). Das beantwortet **wie viele** und **wo
ungefähr**.

Dieses Modul ergänzt die **physische Greifbarkeit**: die realen **Behälter**, in
denen die Steine tatsächlich stecken — mit **QR-Etikett**, beweglichem Standort
und lückenloser Bewegungshistorie. Es beantwortet **welcher physische Behälter
liegt wo, und was ist drin**.

Beide Sichten ergänzen sich:

| BrickBank heute (`inventory_item`) | Dieses Modul (Behälter)                    |
| ---------------------------------- | ------------------------------------------ |
| Menge je Element                   | physischer Behälter mit Etikett            |
| „150 rote 2×4"                     | „Tüte T-000345 mit Etikett, scanbar"       |
| logischer Lagerort (`location`)    | beweglicher Behälter mit Umzugshistorie    |
| Bestandsfrage                      | Auffind- und Inventurfrage vor Ort         |

**Wichtig:** Das Modul **ersetzt nichts**. Es fügt eine Behälter-Ebene hinzu und
verbindet sie sauber mit dem vorhandenen `location`- und `inventory_item`-Modell.

---

## 2. Designentscheidung: Behälter als spezialisierte `location`

BrickBank hat bereits eine **hierarchische `location`** (Raum → Schrank →
Schublade → Box/Fach), selbstreferenzierend über `parent_id`. Ein physischer
Behälter (Container, Karton, Tüte) **ist** im Kern ein Lagerort. Daher:

> **Behälter werden als `location`-Einträge geführt**, nicht als neue Parallel-
> Hierarchie. Das `kind`-ENUM wird um Behältertypen erweitert, und eine
> begleitende Tabelle `location_label` hält die etiketten-/inventurspezifischen
> Zusatzdaten (Code, QR, RFID, Bewegung).

Das hat drei Vorteile: bestehende `inventory_item`-Posten können **direkt** auf
einen Behälter zeigen (eine Tüte ist ein `location`), die Hierarchie
(Container→Karton→Tüte) nutzt das vorhandene `parent_id`, und Abfragen wie „was
liegt in diesem Karton?" funktionieren mit der schon vorhandenen rekursiven
Orts-Auflösung.

### 2.1 Erweiterung des `location.kind`-ENUM

```
kind ENUM('raum','schrank','schublade','box','fach','sonstiges',
          'container','karton','tuete')
```

`container`, `karton`, `tuete` sind die neuen, etikettierbaren Behältertypen.
Die bestehenden Werte bleiben unverändert.

### 2.2 Neue Tabelle `location_label`

Hält genau die Daten, die ein **physisches, bewegliches, etikettiertes** Objekt
braucht — 1:1 zu einer `location`.

| Spalte         | Typ                              | Beschreibung                                        |
| -------------- | -------------------------------- | --------------------------------------------------- |
| `id`           | INT, PK, AUTO_INCREMENT          |                                                     |
| `location_id`  | INT, FK → location.id, UNIQUE    | das zugehörige Behälter-`location`                  |
| `code`         | VARCHAR(16), UNIQUE              | **ortsneutraler** Code, z. B. `T-000345`            |
| `code_seq`     | INT                              | laufende Nummer je Typ (für die Code-Vergabe)       |
| `owner_id`     | INT, NULL, FK → owner.id         | optionaler Eigentümer des Behälters als Ganzes      |
| `rfid_epc`     | VARCHAR(64), NULL               | optionale UHF-RFID-EPC                              |
| `created_at`   | DATETIME                         |                                                     |

**Ortsneutraler Code (Kernprinzip):** Der Code trägt **keinen** Standort im Namen
(`T-000345`, nicht `C01-K012-T0345`). Der Standort ist `location.parent_id` und
**jederzeit änderbar** — ein Behälter zieht um, **ohne dass das Etikett neu
gedruckt** werden muss. Codeformat:

- Container: `C-001`   (Präfix `C-`, 3-stellig, nullgefüllt)
- Karton:    `K-00012` (Präfix `K-`, 5-stellig)
- Tüte:      `T-000345`(Präfix `T-`, 6-stellig)

Stellenbreiten als Konstanten konfigurierbar.

### 2.3 Neue Tabelle `location_movement` (Bewegungshistorie)

Protokolliert **jeden** Standortwechsel eines Behälters lückenlos.

| Spalte        | Typ                              | Beschreibung                              |
| ------------- | -------------------------------- | ----------------------------------------- |
| `id`          | INT, PK, AUTO_INCREMENT          |                                           |
| `location_id` | INT, FK → location.id            | bewegter Behälter                         |
| `code`        | VARCHAR(16)                      | Code redundant (Historie bleibt lesbar)   |
| `from_parent_id` | INT, NULL, FK → location.id   | bisheriger übergeordneter Ort             |
| `from_code`   | VARCHAR(16), NULL               | dessen Code (Snapshot)                    |
| `to_parent_id`| INT, NULL, FK → location.id      | neuer übergeordneter Ort                  |
| `to_code`     | VARCHAR(16), NULL               | dessen Code (Snapshot)                    |
| `wcf_user_id` | INT, NULL                        | wer hat umgeräumt (aus SSO)               |
| `note`        | VARCHAR(160), NULL              | z. B. „per Scan", „Erstzuweisung"         |
| `moved_at`    | DATETIME, DEFAULT CURRENT_TIMESTAMP |                                       |

> `from_code`/`to_code` werden als **Snapshot** gespeichert, damit die Historie
> auch lesbar bleibt, falls ein übergeordneter Ort später umbenannt/gelöscht wird.

### 2.4 DDL-Skizze (an BrickBanks Stil angelehnt)

```sql
ALTER TABLE location
  MODIFY kind ENUM('raum','schrank','schublade','box','fach','sonstiges',
                   'container','karton','tuete')
  NOT NULL DEFAULT 'sonstiges';

CREATE TABLE location_label (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  code_seq INT NOT NULL,
  owner_id INT NULL,
  rfid_epc VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_label_location (location_id),
  UNIQUE KEY uq_label_code (code),
  FOREIGN KEY (location_id) REFERENCES location(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE location_movement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  from_parent_id INT NULL,
  from_code VARCHAR(16) NULL,
  to_parent_id INT NULL,
  to_code VARCHAR(16) NULL,
  wcf_user_id INT NULL,
  note VARCHAR(160) NULL,
  moved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_move_location (location_id),
  KEY idx_move_time (moved_at),
  FOREIGN KEY (location_id) REFERENCES location(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.5 Inventur-Tabellen (Soll-Ist-Abgleich)

```sql
CREATE TABLE stocktake (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  root_location_id INT NULL,        -- optional auf einen Container/Raum eingegrenzt
  owner_id INT NULL,                -- optional auf einen Besitzer eingegrenzt
  wcf_user_id INT NULL,             -- wer hat die Inventur gestartet
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  FOREIGN KEY (root_location_id) REFERENCES location(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_id) REFERENCES owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stocktake_scan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stocktake_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_scan_stocktake (stocktake_id),
  KEY idx_scan_code (code),
  FOREIGN KEY (stocktake_id) REFERENCES stocktake(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3. Verbindung zu `inventory_item` — der eigentliche Mehrwert

Sobald Behälter `location`-Einträge sind, kann ein **Bestandsposten direkt auf
einen Behälter zeigen**: `inventory_item.location_id` = die `location` der Tüte.

Das erlaubt:

- **„Was ist in Tüte T-000345?"** → `inventory_item` WHERE `location_id` =
  (Tüten-`location`), join auf `element`/`part`/`color` → konkrete Teile + Mengen.
- **„Wo sind unsere roten 2×4 — in welcher physischen Tüte?"** → von `element`
  über `inventory_item` zur Behälter-`location`, deren `code` das scanbare Etikett
  ist.
- **Behälter umräumen verschiebt den Bestand mit** — weil die Posten an der
  `location` hängen, „wandern" ihre Mengen automatisch logisch mit, wenn die
  Tüte in einen anderen Karton zieht.

> So schließt sich der Kreis: BrickBanks Mengensicht und die physische
> Behältersicht sind über `location` **dieselbe Wahrheit**.

---

## 4. MVC-Umsetzung (BrickBank-Struktur)

Einfügen in die vorhandene Struktur aus `ARCHITEKTUR.md`:

```
app/
├── Controller/
│   ├── ContainerController.php     # Behälter (Container/Karton/Tüte) CRUD + Standort
│   ├── LabelController.php         # Etiketten-Vorschau, QR, Druckansicht, CSV-Export
│   ├── MoveController.php          # Umräumen (Objekt → Zielort), schreibt location_movement
│   └── StocktakeController.php     # Inventurläufe + Soll-Ist-Abgleich + Scan-Endpoint
├── Model/
│   ├── Entity/Container.php        # bzw. „LabeledLocation" — dünne Sicht auf location+label
│   ├── Entity/Movement.php
│   ├── Entity/Stocktake.php
│   ├── Repository/LabelRepository.php       # Code-Vergabe, Behälter-CRUD, Label-Daten
│   ├── Repository/MovementRepository.php     # Umzug ausführen + Historie schreiben (Transaktion)
│   └── Repository/StocktakeRepository.php    # Läufe, Scans, Soll-Ist-Mengen
├── View/
│   ├── container/ (list, form)
│   ├── label/ (preview, print, export)
│   ├── move/ (scan)
│   └── stocktake/ (list, detail, scan)
└── Service/
    ├── CodeGenerator.php           # ortsneutrale Codes (C-/K-/T- + Zähler)
    └── QrRenderer.php              # QR-PNG aus Code (GD), optional Cache
```

### 4.1 Kernlogik im MovementRepository (Pseudostruktur)

```
move(int $locationId, ?int $newParentId, ?string $note, ?int $wcfUserId): bool
  1. aktuellen parent_id + code laden
  2. Typprüfung: tuete darf nur in karton, karton nur in container,
     container hat keinen Behälter-Parent (nur Raum/Schrank o. Ä. erlaubt)
  3. wenn newParentId == aktueller parent_id → return false (kein Wechsel)
  4. Transaktion:
       UPDATE location SET parent_id = newParentId WHERE id = locationId
       INSERT INTO location_movement (...Snapshots from/to, wcfUserId, note)
  5. commit → true
```

### 4.2 Soll-Ist-Abgleich (StocktakeRepository)

- **Soll** = alle Behälter-`code`s im Bereich (`root_location_id` rekursiv
  aufgelöst; optional auf `owner` gefiltert).
- **Ist**  = `DISTINCT code` aus `stocktake_scan` des Laufs.
- Ergebnis: **gefunden** (Schnittmenge), **fehlend** (Soll ∖ Ist),
  **unerwartet** (Ist ∖ Soll).

---

## 5. Funktionsflächen (Views, im afol.lu-Design)

Alle Views nutzen das bestehende `public/assets`-Design (Navy `#1c2833`, Gold
`#c8a951`, CSS-Variablen unter `:root`).

1. **Behälterverwaltung** — Container/Karton/Tüte anlegen (Code automatisch,
   ortsneutral), Standort optional setzen, in Listen verschieben. Spalte
   „aktueller Standort" + „Verlauf".
2. **Etiketten** — Vorschau + Druckansicht (`@media print`, DK-Rollenmaße aus
   Konfig) mit QR + lesbarem Code + optionalem Inhalt. QR serverseitig via GD.
3. **CSV-Export** — UTF-8 + BOM, semikolongetrennt, Spalten
   `code;titel;typ;standort;…` für **P-touch-Editor-Seriendruck** (Brother QL).
4. **Umräumen (mobil)** — Kamera-Scan (`html5-qrcode`): erst Behälter scannen,
   dann Zielort scannen → `MoveController` schreibt Bewegung. Manuelle Eingabe
   als Fallback. **Benötigt HTTPS** (Kamerafreigabe).
5. **Inventur** — Lauf starten (optional auf Container/Owner eingegrenzt),
   scannen, Soll-Ist-Auswertung (gefunden/fehlend/unerwartet).
6. **Verlauf** — vollständige Bewegungshistorie eines Behälters, neueste oben,
   inklusive „wer" (aus SSO) und Notiz.

---

## 6. SSO / Eigentum / Sicherheit

- **Login** ausschließlich über WoltLab-SSO (`Integration/`-Layer). Kein eigenes
  Auth. Schreibende Aktionen erfordern eingeloggten Benutzer.
- **`wcf_user_id`** aus der SSO-Session wird bei Bewegungen und Inventuren als
  „wer" gespeichert (Spalte in `location_movement` / `stocktake`).
- **Eigentum:** Behälter können einem `owner` zugeordnet sein (Verein vs. privat)
  über das optionale `location_label.owner_id`. Fehlt es, leitet sich Eigentum
  implizit aus den enthaltenen `inventory_item`s ab.
- **CSRF-Token** bei allen schreibenden Formularen, **PDO Prepared Statements**,
  **Output-Escaping**, **serverseitige Validierung** (Typkonformität der Umzüge,
  Code-Format), **WSC-Tabellen nur lesend**.

---

## 7. Umsetzungsschritte (Vorschlag für die Roadmap)

0. **MVC-Fundament** — Grundgerüst (Router, Database/PDO, Core, Integration-SSO,
   Basis-Schema) anlegen, da BrickBank zuvor nur Doku enthielt.
1. **Schema-Migration** — `location.kind` erweitern, `location_label`,
   `location_movement`, `stocktake`, `stocktake_scan` anlegen; `DATENMODELL.md`
   ergänzen.
2. **CodeGenerator + LabelRepository** — ortsneutrale Codes, Behälter-CRUD.
3. **ContainerController + Views** — anlegen/auflisten/Standort setzen.
4. **MoveController + MovementRepository** — Umräumen + Historie (Transaktion).
5. **QrRenderer + LabelController** — QR, Druckansicht, CSV-Export.
6. **StocktakeController** — Inventurläufe, Scan-Endpoint, Soll-Ist.
7. **Mobile Scan-Views** — `html5-qrcode` für Umräumen und Inventur.
8. **Verknüpfung dokumentieren** — wie `inventory_item.location_id` auf Behälter
   zeigt; Beispielabfragen in `DATENMODELL.md`.

---

## 8. Bewusst offen gelassen

- **RFID (UHF):** Das Schema hält `rfid_epc` bereit; die Pulk-Erfassung (Reader,
  Bulk-Scan eines ganzen Containers) ist eine spätere Ausbaustufe. QR genügt fürs
  MVP.
- **Mengen-Buchungen je Behälter** (entnehmen/zurücklegen mit Protokoll) können
  später analog zu `location_movement` als `inventory_movement` ergänzt werden —
  bewusst nicht Teil dieses Moduls, um den Fokus auf die Behälter-/Standortebene
  zu halten.
- **Direktdruck** über native Brother-Ansteuerung; das MVP nutzt Browser-Druck
  (`@media print`) und CSV-Export für P-touch Editor.
