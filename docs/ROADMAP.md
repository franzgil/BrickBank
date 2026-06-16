# Roadmap

Schrittweise Umsetzung — vom kleinsten nutzbaren Stand (MVP) zum Ausbau.
Jede Phase ist für sich genommen lauffähig und liefert Mehrwert.

## Phase 0 — Konzept & Planung ✅ (aktuell)
- README, Konzept, Datenmodell, Architektur dokumentiert.
- Technologie festgelegt: HTML + PHP 7 + MySQL.

## Phase 1 — Grundgerüst & Datenbank
- Projektstruktur anlegen (`public/`, `src/`, `templates/`, `sql/`, `config/`).
- `sql/schema.sql` umsetzen (Tabellen aus [DATENMODELL.md](DATENMODELL.md)).
- PDO-Verbindung (`src/Database.php`) + Konfigurationsvorlage.
- Front-Controller mit einfachem Routing, „Hello BrickBank“-Seite.

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
