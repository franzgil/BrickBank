# Datenbank einrichten

Anwendungsreihenfolge (MySQL, Datenbank `brickbank`, utf8mb4):

```bash
mysql brickbank < sql/schema.sql
mysql brickbank < sql/migrations/001_module_behaelter_inventur.sql
mysql brickbank < sql/seed.sql        # optional: Beispiel-Stammdaten
```

- `schema.sql` — Basis-Tabellen (owner, location, part, color, element, inventory_item).
- `migrations/` — inkrementelle Änderungen in Nummern-Folge. Jede Migration genau
  einmal anwenden.
- `seed.sql` — optionale Beispieldaten (Verein-Besitzer, einige Farben).

Die WoltLab-Tabellen (`wcf1_*`) gehören **nicht** hierher — sie liegen in der
WSC-Datenbank und werden ausschließlich lesend genutzt (siehe `docs/INTEGRATION.md`).
