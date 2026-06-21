# Datenbank einrichten

Anwendungsreihenfolge (MySQL, Datenbank `brickbank`, utf8mb4):

```bash
mysql brickbank < sql/schema.sql
mysql brickbank < sql/migrations/001_module_behaelter_inventur.sql
mysql brickbank < sql/seed.sql        # optional: Beispiel-Stammdaten
```

Alle Tabellen tragen das Präfix **`bb_`** (z. B. `bb_owner`). Bereits ohne
Präfix angelegte Tabellen lassen sich mit `sql/rename-to-bb-prefix.sql` umbenennen.

- `schema.sql` — Basis-Tabellen (bb_owner, bb_location, bb_part, bb_color, bb_element, bb_inventory_item).
- `migrations/` — inkrementelle Änderungen in Nummern-Folge. Jede Migration genau
  einmal anwenden.
- `seed.sql` — optionale Beispieldaten (Verein-Besitzer, einige Farben).

Die WoltLab-Tabellen (`wcf1_*`) gehören **nicht** hierher — sie liegen in der
WSC-Datenbank und werden ausschließlich lesend genutzt (siehe `docs/INTEGRATION.md`).
