# Roadmap

Schrittweise Umsetzung — vom kleinsten nutzbaren Stand (MVP) zum Ausbau.
Jede Phase ist für sich genommen lauffähig und liefert Mehrwert.

## Phase 0 — Konzept & Planung ✅ (aktuell)
- README, Konzept, Datenmodell, Architektur, Integration dokumentiert.
- Technologie festgelegt: eigenständige **MVC-App** (PHP/MySQL), gekoppelt an
  **WoltLab Suite 5.5** per **SSO**.

## Phase 1 — MVC-Grundgerüst & Datenbank
- Projektstruktur anlegen (`public/`, `app/Core|Controller|Model|View|Integration`, `sql/`, `config/`).
- Front-Controller + Router + Controller-Basisklasse + View-Renderer (MVC-Kern).
- `sql/schema.sql` umsetzen (Tabellen aus [DATENMODELL.md](DATENMODELL.md)).
- PDO-Verbindung + Konfigurationsvorlage; „Hello BrickBank“-Seite im afol.lu-Design.

## Phase 1b — WoltLab-SSO-Anbindung
- `Integration/`-Layer: WSC-Session-Cookie lesen, Session/`userID` auflösen.
- Auth-Middleware: kein Login → Redirect zur WSC-Login-Seite mit Rücksprung.
- WSC-Benutzer (read-only) + Benutzergruppen für die Autorisierung laden.
- Privat-`owner` automatisch mit `wcf_user_id` verknüpfen/anlegen.

## Modul Behälter/Etiketten/Inventur ✅ umgesetzt
Eigenständig lauffähiges Modul (siehe [MODUL-BEHAELTER-INVENTUR.md](MODUL-BEHAELTER-INVENTUR.md)):
- MVC-Fundament (Router, PDO, View, CSRF, WSC-SSO-Layer).
- Schema-Migration 001 (Behälter als `location` + `location_label`,
  `location_movement`, `stocktake`, `stocktake_scan`).
- Behälter-CRUD mit ortsneutralen Codes (C-/K-/T-), Umräumen mit Historie,
  QR-Etiketten (Druck + CSV-Export), Inventur mit Soll-Ist-Abgleich,
  mobiler Kamera-Scan.

## Phase 2 — Stammdaten pflegen
- CRUD für **Besitzer** (Verein/Privat).
- CRUD für **Lagerorte** inkl. Hierarchie (Eltern-Auswahl).
- CRUD für **Teile** und **Farben**; Seed mit gängigen Farben.

## Phase 3 — Bestandsführung (Kernfunktion)
- **Bestandsposten erfassen**: Element (Teil+Farbe) + Ort + Besitzer + Menge.
- Menge **entnehmen/zurücklegen** (UC3).
- Posten **umlagern** (UC4).
- Listenansicht aller Bestände mit Mengen und Lagerorten.

## Phase 4 — Suche & Übersichten
- Suche/Filter nach Teil, Farbe, Ort, Besitzer (UC2).
- Aggregierte Sicht: Gesamtmenge je Element über alle Orte.
- Lagerort-Übersicht „Was liegt in X?“ inkl. Unter-Orte (UC6).
- Trennung/Filter Vereins- vs. Privatbestand (UC5).

## Phase 5 — Komfort & Härtung
- CSRF-Schutz, durchgängige Validierung, Fehlerseiten.
- Mehrsprachigkeit (LU/FR/DE/EN), falls gewünscht.
- Optional: Mitglieder-Logins und Rollen.

## Spätere Ideen (Backlog)
- Import aus externem Katalog (z. B. BrickLink) für Teile/Farben.
- Barcode-/QR-Etiketten für Lagerorte.
- Verleih-Workflow (Reservierung, Rückgabe).
- Export/Reports (CSV, Druckansicht).
