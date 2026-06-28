# Datenbank einrichten

`schema.sql` ist die **aktuelle vollständige Wahrheit**, `migrations/` ist die
**Evolutions-Historie** für bereits laufende Installationen.

## Frische Installation
Nur das Schema (enthält **alle** Tabellen) + optional Beispieldaten:
```bash
mysql brickbank < sql/schema.sql
mysql brickbank < sql/seed.sql        # optional: Standard-Besitzer
```
**Keine** migrations/ nötig.

## Bestehende Installation (schrittweise nachziehen)
Die Migrationen **in Reihenfolge**, jede genau einmal:
```bash
mysql brickbank < sql/migrations/001_module_behaelter_inventur.sql
mysql brickbank < sql/migrations/002_katalog_rebrickable.sql
mysql brickbank < sql/migrations/003_phase_a_items_holdings.sql   # Phase A
```
`schema.sql` hier **nicht** erneut einspielen.

> **Phase A (003):** zusammen mit dem zugehörigen Code-Stand (`git pull`)
> einspielen — die Migration ersetzt `bb_element`/`bb_inventory_item` durch
> `bb_item`/`bb_holding`/`bb_inventory_movement` (verlustfreie Datenmigration).

## Hinweise
- **Rebrickable-Katalog erforderlich:** Teile/Farben/Sets/Minifiguren kommen aus
  den `rb_*`-Tabellen (gleiche Datenbank, nur lesend, via Rebrickable-Import).
  Siehe `docs/REBRICKABLE.md`.
- Alle BrickBank-Tabellen tragen das Präfix **`bb_`**. Ohne Präfix angelegte
  Tabellen: `sql/rename-to-bb-prefix.sql`.
- WoltLab-Tabellen (`wcf1_*`) werden ausschließlich **lesend** genutzt
  (siehe `docs/INTEGRATION.md`).
