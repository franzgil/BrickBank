-- =====================================================================
-- BrickBank – Tabellen auf bb_-Präfix umbenennen.
-- NUR ausführen, wenn die Tabellen bereits OHNE Präfix angelegt wurden
-- (frische Installationen nutzen direkt schema.sql + Migration mit bb_).
-- RENAME TABLE ist atomar und zieht Fremdschlüssel-Verweise automatisch nach.
-- =====================================================================

RENAME TABLE
  owner             TO bb_owner,
  location          TO bb_location,
  part              TO bb_part,
  color             TO bb_color,
  element           TO bb_element,
  inventory_item    TO bb_inventory_item,
  location_label    TO bb_location_label,
  location_movement TO bb_location_movement,
  stocktake         TO bb_stocktake,
  stocktake_scan    TO bb_stocktake_scan;
